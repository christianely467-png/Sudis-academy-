<?php
namespace Sudis\Academy;

defined( 'ABSPATH' ) || exit;

/**
 * Manage SUDIS Academy certificates.
 *
 * ## EXAMPLES
 *
 *     wp sudis issue --user=maria@example.com --course=42
 *     wp sudis deliver SUDIS-2026-7K3P9QXA
 *     wp sudis sample /tmp/muestra.pdf --type=ce
 */
final class CLI {

	/**
	 * Issue a certificate.
	 *
	 * ## OPTIONS
	 *
	 * --user=<user>
	 * : User ID, login or email.
	 *
	 * --course=<id>
	 * : LearnDash course ID.
	 *
	 * [--force]
	 * : Skip the minimum score rule.
	 *
	 * [--now]
	 * : Deliver immediately instead of through WP-Cron.
	 */
	public function issue( $args, $assoc ): void {
		$user = $this->user( (string) $assoc['user'] );
		$cert = Issuer::issue( $user->ID, (int) $assoc['course'], null, ! empty( $assoc['force'] ) );
		if ( is_wp_error( $cert ) ) {
			\WP_CLI::error( $cert->get_error_message() );
		}
		\WP_CLI::success( "Emitido {$cert->code}" );
		if ( ! empty( $assoc['now'] ) ) {
			$this->deliver( array( $cert->code ), array() );
		}
	}

	/**
	 * Render and email a certificate now.
	 *
	 * <code>
	 * : Certificate number.
	 */
	public function deliver( $args, $assoc ): void {
		$cert = Repository::find_by_code( $args[0] );
		if ( ! $cert ) {
			\WP_CLI::error( 'No existe.' );
		}
		Issuer::deliver( (int) $cert->id, Issuer::MAX_ATTEMPTS );
		$cert = Repository::find( (int) $cert->id );
		$cert->emailed_at ? \WP_CLI::success( 'Enviado a las ' . $cert->emailed_at . ' UTC. PDF: ' . Renderer::path( $cert ) ) : \WP_CLI::error( 'No se envió: ' . Mailer::last_error() );
	}

	/**
	 * Write a sample certificate PDF.
	 *
	 * <file>
	 * : Output path.
	 *
	 * [--type=<type>]
	 * : completion or ce.
	 * ---
	 * default: ce
	 * ---
	 */
	public function sample( $args, $assoc ): void {
		$cert            = Renderer::sample();
		$cert->cert_type = 'completion' === $assoc['type'] ? 'completion' : 'ce';
		$pdf             = Renderer::render( $cert );
		if ( is_wp_error( $pdf ) ) {
			\WP_CLI::error( $pdf->get_error_message() );
		}
		file_put_contents( $args[0], $pdf );
		\WP_CLI::success( $args[0] );
	}

	/**
	 * List certificates.
	 *
	 * [--search=<term>]
	 * : Number, name or course.
	 */
	public function list( $args, $assoc ): void {
		list( $rows ) = Repository::search( (string) ( $assoc['search'] ?? '' ), 1, 200 );
		\WP_CLI\Utils\format_items( 'table', $rows, array( 'code', 'student_name', 'course_title', 'cert_type', 'score', 'status', 'emailed_at' ) );
	}

	private function user( string $value ): \WP_User {
		$user = is_numeric( $value ) ? get_userdata( (int) $value ) : ( is_email( $value ) ? get_user_by( 'email', $value ) : get_user_by( 'login', $value ) );
		if ( ! $user ) {
			\WP_CLI::error( "Usuario no encontrado: {$value}" );
		}
		return $user;
	}
}
