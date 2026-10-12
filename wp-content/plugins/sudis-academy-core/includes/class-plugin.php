<?php
namespace Sudis\Academy;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public static function boot(): void {
		Installer::maybe_upgrade();

		Profile::init();
		Course_Meta::init();
		Issuer::init();
		Frontend::init();

		if ( is_admin() ) {
			Admin::init();
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'sudis', CLI::class );
		}
	}

	/** Whether LearnDash is active. */
	public static function learndash_active(): bool {
		return defined( 'LEARNDASH_VERSION' );
	}

	/** Dompdf and the QR library are bundled through Composer. */
	public static function dependencies_loaded(): bool {
		return class_exists( '\\Dompdf\\Dompdf' ) && class_exists( '\\chillerlan\\QRCode\\QRCode' );
	}
}
