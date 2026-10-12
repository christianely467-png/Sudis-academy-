<?php
namespace Sudis\Academy;

defined( 'ABSPATH' ) || exit;

/**
 * Issues a certificate when LearnDash marks a course as completed and the
 * final exam was passed with the minimum score, then delivers it by email
 * in a background (WP-Cron) task so the student never waits on PDF rendering.
 */
final class Issuer {

	const DELIVER_HOOK = 'sudis_academy_deliver_certificate';
	const MAX_ATTEMPTS = 3;

	public static function init(): void {
		add_action( 'learndash_course_completed', array( __CLASS__, 'on_course_completed' ), 20 );
		add_action( self::DELIVER_HOOK, array( __CLASS__, 'deliver' ), 10, 2 );
	}

	/** @param array $data LearnDash payload: user, course, progress, course_completed. */
	public static function on_course_completed( $data ): void {
		$user   = $data['user'] ?? null;
		$course = $data['course'] ?? null;
		if ( ! $user instanceof \WP_User || ! $course instanceof \WP_Post ) {
			return;
		}
		$completed = ! empty( $data['course_completed'] ) ? (int) $data['course_completed'] : time();

		$result = self::issue( $user->ID, $course->ID, $completed );
		if ( is_wp_error( $result ) && 'sudis_exists' !== $result->get_error_code() ) {
			error_log( sprintf( '[SUDIS Academy] No se emitió certificado (usuario %d, curso %d): %s', $user->ID, $course->ID, $result->get_error_message() ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		}
	}

	/**
	 * Best percentage per quiz for this user and course, from LearnDash's
	 * `_sfwd-quizzes` user meta (one entry per attempt).
	 *
	 * @return array<int, float> quiz_id => best percentage
	 */
	public static function best_scores( int $user_id, int $course_id ): array {
		$attempts = get_user_meta( $user_id, '_sfwd-quizzes', true );
		$best     = array();
		if ( ! is_array( $attempts ) ) {
			return $best;
		}
		foreach ( $attempts as $attempt ) {
			if ( ! is_array( $attempt ) || empty( $attempt['quiz'] ) ) {
				continue;
			}
			if ( isset( $attempt['course'] ) && (int) $attempt['course'] !== $course_id ) {
				continue;
			}
			$quiz = (int) $attempt['quiz'];
			$pct  = (float) ( $attempt['percentage'] ?? 0 );
			$best[ $quiz ] = max( $best[ $quiz ] ?? 0, $pct );
		}
		return $best;
	}

	/**
	 * Checks the score rule: the configured final exam (or every exam taken
	 * in the course when none is configured) must reach the minimum score.
	 *
	 * @return array{ok: bool, score: ?float, reason: string}
	 */
	public static function check_score( int $user_id, int $course_id ): array {
		$min    = (float) Settings::get( 'min_score' );
		$meta   = Course_Meta::get( $course_id );
		$scores = self::best_scores( $user_id, $course_id );

		if ( $meta['final_quiz_id'] ) {
			$score = $scores[ (int) $meta['final_quiz_id'] ] ?? null;
			if ( null === $score ) {
				return array( 'ok' => false, 'score' => null, 'reason' => 'No hay intentos del examen final.' );
			}
			return array(
				'ok'     => $score >= $min,
				'score'  => $score,
				'reason' => $score >= $min ? '' : sprintf( 'Nota del examen final %.2f %% inferior al mínimo %.0f %%.', $score, $min ),
			);
		}

		if ( ! $scores ) {
			// Course without exams (e.g. free introductory course).
			return array( 'ok' => true, 'score' => null, 'reason' => '' );
		}

		$lowest = min( $scores );
		return array(
			'ok'     => $lowest >= $min,
			'score'  => $lowest,
			'reason' => $lowest >= $min ? '' : sprintf( 'Nota %.2f %% inferior al mínimo %.0f %%.', $lowest, $min ),
		);
	}

	/**
	 * Creates the certificate record and queues its delivery.
	 *
	 * @param bool $force Skip the score rule (manual issue by an administrator).
	 * @return object|\WP_Error The certificate row.
	 */
	public static function issue( int $user_id, int $course_id, ?int $completed_ts = null, bool $force = false ) {
		$user   = get_userdata( $user_id );
		$course = get_post( $course_id );
		if ( ! $user || ! $course ) {
			return new \WP_Error( 'sudis_invalid', 'Usuario o curso inexistente.' );
		}

		$meta = Course_Meta::get( $course_id );
		if ( ! $meta['enabled'] && ! $force ) {
			return new \WP_Error( 'sudis_disabled', 'El curso no emite certificado SUDIS.' );
		}

		$existing = Repository::find_valid( $user_id, $course_id );
		if ( $existing ) {
			return new \WP_Error( 'sudis_exists', 'Ya existe un certificado vigente.', $existing );
		}

		$check = self::check_score( $user_id, $course_id );
		if ( ! $check['ok'] && ! $force ) {
			return new \WP_Error( 'sudis_score', $check['reason'] );
		}

		$profile = Profile::get( $user_id );
		$code    = Code_Generator::generate();
		$now     = current_time( 'mysql', true );

		$row = array(
			'code'            => $code,
			'user_id'         => $user_id,
			'course_id'       => $course_id,
			'cert_type'       => $meta['type'],
			'student_name'    => $profile['name'],
			'profession'      => $profile['profession'],
			'license_number'  => $profile['license'],
			'course_title'    => wp_strip_all_tags( get_the_title( $course ) ),
			'hours'           => (float) $meta['hours'],
			'ce_credits'      => 'ce' === $meta['type'] ? (float) $meta['ce_credits'] : 0,
			'accreditor'      => 'ce' === $meta['type'] ? $meta['accreditor'] : '',
			'provider_number' => 'ce' === $meta['type'] ? $meta['provider_number'] : '',
			'score'           => $check['score'],
			'completed_at'    => gmdate( 'Y-m-d H:i:s', $completed_ts ?: time() ),
			'issued_at'       => $now,
			'status'          => 'valid',
			'file_name'       => 'Certificado-' . $code . '.pdf',
		);

		$id = Repository::insert( apply_filters( 'sudis_academy_certificate_data', $row, $user, $course ) );
		if ( ! $id ) {
			return new \WP_Error( 'sudis_db', 'No se pudo guardar el certificado.' );
		}

		self::queue_delivery( $id );
		do_action( 'sudis_academy_certificate_issued', $id, $user_id, $course_id );

		return Repository::find( $id );
	}

	public static function queue_delivery( int $id, int $attempt = 1, int $delay = 0 ): void {
		wp_schedule_single_event( time() + $delay, self::DELIVER_HOOK, array( $id, $attempt ) );
	}

	/** Cron task: render the PDF and email it, retrying on failure. */
	public static function deliver( $id, $attempt = 1 ): void {
		$cert = Repository::find( (int) $id );
		if ( ! $cert || 'valid' !== $cert->status ) {
			return;
		}

		$path = Renderer::ensure_pdf( $cert );
		$sent = ! is_wp_error( $path ) && Mailer::send_certificate( $cert, $path );

		Repository::update(
			(int) $cert->id,
			array_filter(
				array(
					'email_attempts' => (int) $cert->email_attempts + 1,
					'emailed_at'     => $sent ? current_time( 'mysql', true ) : null,
				),
				static fn( $v ) => null !== $v
			)
		);

		if ( $sent ) {
			return;
		}

		if ( (int) $attempt < self::MAX_ATTEMPTS ) {
			self::queue_delivery( (int) $cert->id, (int) $attempt + 1, 300 * (int) $attempt );
			return;
		}

		$error = is_wp_error( $path ) ? $path->get_error_message() : Mailer::last_error();
		wp_mail(
			get_option( 'admin_email' ),
			'[SUDIS Academy] No se pudo enviar un certificado',
			sprintf( "Certificado %s para %s (%s).\nError: %s\nReenvíalo desde SUDIS Academy → Certificados.", $cert->code, $cert->student_name, $cert->course_title, $error )
		);
	}
}
