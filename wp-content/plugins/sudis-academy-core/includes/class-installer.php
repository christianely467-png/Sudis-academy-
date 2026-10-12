<?php
namespace Sudis\Academy;

defined( 'ABSPATH' ) || exit;

final class Installer {

	const DB_VERSION        = '1';
	const DB_VERSION_OPTION = 'sudis_academy_db_version';

	public static function activate(): void {
		self::create_table();
		self::protect_storage();
		self::ensure_verify_page();
	}

	public static function maybe_upgrade(): void {
		if ( get_option( self::DB_VERSION_OPTION ) !== self::DB_VERSION ) {
			self::create_table();
		}
	}

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'sudis_certificates';
	}

	private static function create_table(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();

		dbDelta(
			"CREATE TABLE {$table} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				code varchar(32) NOT NULL,
				user_id bigint(20) unsigned NOT NULL,
				course_id bigint(20) unsigned NOT NULL,
				cert_type varchar(20) NOT NULL DEFAULT 'completion',
				student_name varchar(190) NOT NULL,
				profession varchar(190) NOT NULL DEFAULT '',
				license_number varchar(64) NOT NULL DEFAULT '',
				course_title varchar(255) NOT NULL,
				hours decimal(6,2) NOT NULL DEFAULT 0,
				ce_credits decimal(6,2) NOT NULL DEFAULT 0,
				accreditor varchar(255) NOT NULL DEFAULT '',
				provider_number varchar(64) NOT NULL DEFAULT '',
				score decimal(5,2) DEFAULT NULL,
				completed_at datetime NOT NULL,
				issued_at datetime NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'valid',
				file_name varchar(190) NOT NULL DEFAULT '',
				emailed_at datetime DEFAULT NULL,
				email_attempts smallint(5) unsigned NOT NULL DEFAULT 0,
				PRIMARY KEY  (id),
				UNIQUE KEY code (code),
				KEY user_course (user_id,course_id),
				KEY status (status)
			) {$charset};"
		);

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/** Directory where generated PDFs live; never served directly. */
	public static function storage_dir(): string {
		$uploads = wp_upload_dir( null, false );
		return trailingslashit( $uploads['basedir'] ) . 'sudis-certificados';
	}

	public static function protect_storage(): void {
		$dir = self::storage_dir();
		if ( ! wp_mkdir_p( $dir ) ) {
			return;
		}
		if ( ! file_exists( $dir . '/.htaccess' ) ) {
			file_put_contents( $dir . '/.htaccess', "Require all denied\nDeny from all\n" );
		}
		if ( ! file_exists( $dir . '/index.php' ) ) {
			file_put_contents( $dir . '/index.php', "<?php\n// Silence is golden.\n" );
		}
	}

	private static function ensure_verify_page(): void {
		$page_id = (int) Settings::get( 'verify_page_id' );
		if ( $page_id && get_post_status( $page_id ) ) {
			return;
		}

		$existing = get_page_by_path( 'verificar' );
		if ( $existing ) {
			Settings::update( array( 'verify_page_id' => $existing->ID ) );
			return;
		}

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Verificar certificado',
				'post_name'    => 'verificar',
				'post_content' => '<!-- wp:shortcode -->[sudis_verificar_certificado]<!-- /wp:shortcode -->',
			)
		);
		if ( $page_id && ! is_wp_error( $page_id ) ) {
			Settings::update( array( 'verify_page_id' => $page_id ) );
		}
	}
}
