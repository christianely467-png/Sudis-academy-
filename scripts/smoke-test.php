<?php
/**
 * End-to-end check of the certificate flow on a staging site.
 *
 * Run with: wp eval-file scripts/smoke-test.php
 *
 * Simulates what LearnDash does when a student finishes a course
 * (quiz attempts in `_sfwd-quizzes` + the `learndash_course_completed`
 * action), so it works with or without LearnDash installed. Creates demo
 * data marked "[DEMO]"; safe to re-run.
 */

use Sudis\Academy\Course_Meta;
use Sudis\Academy\Issuer;
use Sudis\Academy\Renderer;
use Sudis\Academy\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$failures = 0;
$check    = static function ( bool $ok, string $label ) use ( &$failures ) {
	echo ( $ok ? '  OK   ' : '  FAIL ' ) . $label . "\n";
	if ( ! $ok ) {
		++$failures;
	}
};

$course_for = static function ( string $title, array $meta ): int {
	$found    = get_posts( array( 'post_type' => 'sfwd-courses', 'title' => $title, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids' ) );
	$id       = $found ? (int) $found[0] : wp_insert_post( array( 'post_type' => 'sfwd-courses', 'post_status' => 'publish', 'post_title' => $title ) );
	Course_Meta::set( $id, $meta );
	return $id;
};

$student = static function ( string $login, string $first, string $last, string $profession, string $license ): int {
	$user = get_user_by( 'login', $login );
	$id   = $user ? $user->ID : wp_insert_user( array( 'user_login' => $login, 'user_email' => $login . '@ejemplo.test', 'user_pass' => wp_generate_password(), 'role' => 'subscriber' ) );
	wp_update_user( array( 'ID' => $id, 'first_name' => $first, 'last_name' => $last, 'display_name' => "$first $last" ) );
	update_user_meta( $id, 'sudis_profesion', $profession );
	update_user_meta( $id, 'sudis_licencia', $license );
	return $id;
};

$complete = static function ( int $user_id, int $course_id, int $quiz_id, float $pct ): void {
	update_user_meta( $user_id, '_sfwd-quizzes', array( array( 'quiz' => $quiz_id, 'course' => $course_id, 'percentage' => $pct, 'pass' => $pct >= 80 ? 1 : 0, 'time' => time() ) ) );
	do_action( 'learndash_course_completed', array( 'user' => get_userdata( $user_id ), 'course' => get_post( $course_id ), 'progress' => array(), 'course_completed' => time() ) );
};

echo "SUDIS Academy · prueba de certificados\n";

$ce_course  = $course_for( '[DEMO] Ética profesional y confidencialidad', array( 'type' => 'ce', 'hours' => 6, 'ce_credits' => 0.6, 'accreditor' => '[Entidad acreditadora]', 'provider_number' => '[Nº de proveedor]', 'final_quiz_id' => 9001 ) );
$fin_course = $course_for( '[DEMO] Documentación clínica efectiva', array( 'type' => 'completion', 'hours' => 3 ) );

$maria = $student( 'demo-maria', 'María', 'Rodríguez Ortiz', 'Trabajador(a) Social', '12345' );
$jose  = $student( 'demo-jose', 'José', 'Núñez Peña', 'Psicólogo(a)', '' );
$pedro = $student( 'demo-pedro', 'Pedro', 'Colón Díaz', 'Consejero(a) Profesional', 'C-778' );

// 1. Approved CE course (92 %).
$complete( $maria, $ce_course, 9001, 92 );
$cert = Repository::find_valid( $maria, $ce_course );
$check( (bool) $cert, 'Aprobado con 92 %: se emite certificado de educación continua' );

if ( $cert ) {
	$check( (bool) preg_match( '/^SUDIS-\d{4}-[A-HJ-NP-Z2-9]{8}$/', $cert->code ), "Número con formato correcto ({$cert->code})" );
	$check( 'ce' === $cert->cert_type && 'María Rodríguez Ortiz' === $cert->student_name && '12345' === $cert->license_number, 'Datos del participante guardados (nombre, licencia, tipo CE)' );
	$check( (bool) wp_next_scheduled( Issuer::DELIVER_HOOK, array( (int) $cert->id, 1 ) ), 'Envío por correo encolado en segundo plano' );

	Issuer::deliver( (int) $cert->id, 1 );
	$cert = Repository::find( (int) $cert->id );
	$path = Renderer::path( $cert );
	$check( file_exists( $path ) && str_starts_with( (string) file_get_contents( $path, false, null, 0, 5 ), '%PDF' ), 'PDF generado: ' . $path );
	$check( (bool) $cert->emailed_at, 'Correo enviado con el PDF adjunto' );
}

// 2. Re-completing must not duplicate.
$complete( $maria, $ce_course, 9001, 95 );
list( , $total ) = Repository::search( 'Rodríguez', 1, 50 );
$check( 1 === $total, 'Completar otra vez no duplica el certificado' );

// 3. Completion course without license.
$complete( $jose, $fin_course, 9002, 85 );
$cert2 = Repository::find_valid( $jose, $fin_course );
$check( $cert2 && 'completion' === $cert2->cert_type, 'Curso de finalización con 85 %: se emite certificado de finalización' );
if ( $cert2 ) {
	Issuer::deliver( (int) $cert2->id, 1 );
}

// 4. Below the minimum score.
$complete( $pedro, $ce_course, 9001, 70 );
$check( null === Repository::find_valid( $pedro, $ce_course ), 'Nota 70 %: NO se emite certificado' );

echo $failures ? "\n{$failures} prueba(s) fallaron.\n" : "\nTodas las pruebas pasaron.\n";
