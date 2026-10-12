<?php
namespace Sudis\Academy;

defined( 'ABSPATH' ) || exit;

final class Mailer {

	private static string $last_error = '';

	public static function last_error(): string {
		return self::$last_error;
	}

	public static function send_certificate( object $cert, string $pdf_path ): bool {
		$user = get_userdata( (int) $cert->user_id );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			self::$last_error = 'El usuario no tiene un correo válido.';
			return false;
		}

		$settings = Settings::all();
		$subject  = str_replace( '{curso}', $cert->course_title, (string) $settings['email_subject'] );
		$body     = self::body( $cert );

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		if ( $settings['email_from'] ) {
			$headers[] = sprintf( 'From: %s <%s>', $settings['email_from_name'], $settings['email_from'] );
		}
		if ( $settings['email_bcc'] ) {
			$headers[] = 'Bcc: ' . $settings['email_bcc'];
		}

		$capture = static function ( \WP_Error $error ) {
			self::$last_error = $error->get_error_message();
		};
		add_action( 'wp_mail_failed', $capture );
		$sent = wp_mail(
			$user->user_email,
			apply_filters( 'sudis_academy_email_subject', $subject, $cert ),
			apply_filters( 'sudis_academy_email_body', $body, $cert ),
			$headers,
			array( $pdf_path )
		);
		remove_action( 'wp_mail_failed', $capture );

		return (bool) $sent;
	}

	public static function body( object $cert ): string {
		$vars = array(
			'cert'       => $cert,
			'settings'   => Settings::all(),
			'verify_url' => Settings::verify_url( $cert->code ),
			'account'    => apply_filters( 'sudis_academy_account_url', home_url( '/academia/mi-cuenta/' ) ),
			'date'       => Renderer::format_date( $cert->completed_at ),
		);
		$template = locate_template( 'sudis-academy/email-certificate.php' ) ?: SUDIS_ACADEMY_DIR . 'templates/email-certificate.php';

		ob_start();
		( static function () use ( $template, $vars ) {
			extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
			include $template;
		} )();
		return (string) ob_get_clean();
	}
}
