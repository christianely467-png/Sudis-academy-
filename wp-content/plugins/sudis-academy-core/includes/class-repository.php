<?php
namespace Sudis\Academy;

defined( 'ABSPATH' ) || exit;

/** Data access for wp_sudis_certificates. */
final class Repository {

	public static function insert( array $data ): int {
		global $wpdb;
		$ok = $wpdb->insert( Installer::table(), $data );
		return $ok ? (int) $wpdb->insert_id : 0;
	}

	public static function update( int $id, array $data ): bool {
		global $wpdb;
		return false !== $wpdb->update( Installer::table(), $data, array( 'id' => $id ) );
	}

	public static function find( int $id ): ?object {
		global $wpdb;
		$table = Installer::table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) ) ?: null;
	}

	public static function find_by_code( string $code ): ?object {
		global $wpdb;
		$table = Installer::table();
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE code = %s", strtoupper( trim( $code ) ) ) ) ?: null;
	}

	public static function find_valid( int $user_id, int $course_id ): ?object {
		global $wpdb;
		$table = Installer::table();
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d AND course_id = %d AND status = 'valid' ORDER BY id DESC LIMIT 1",
				$user_id,
				$course_id
			)
		) ?: null;
	}

	public static function code_exists( string $code ): bool {
		global $wpdb;
		$table = Installer::table();
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$table} WHERE code = %s", $code ) );
	}

	/** @return object[] */
	public static function for_user( int $user_id ): array {
		global $wpdb;
		$table = Installer::table();
		return $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d AND status = 'valid' ORDER BY issued_at DESC", $user_id )
		);
	}

	/** @return array{0: object[], 1: int} rows and total count */
	public static function search( string $term, int $page, int $per_page ): array {
		global $wpdb;
		$table  = Installer::table();
		$where  = '1=1';
		$params = array();

		if ( '' !== $term ) {
			$like   = '%' . $wpdb->esc_like( $term ) . '%';
			$where  = '(code LIKE %s OR student_name LIKE %s OR course_title LIKE %s)';
			$params = array( $like, $like, $like );
		}

		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
		$total     = (int) ( $params ? $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ) : $wpdb->get_var( $count_sql ) );

		$offset = max( 0, ( $page - 1 ) * $per_page );
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d",
				array_merge( $params, array( $per_page, $offset ) )
			)
		);

		return array( $rows, $total );
	}

	public static function has_any_for_user( int $user_id ): bool {
		global $wpdb;
		$table = Installer::table();
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT 1 FROM {$table} WHERE user_id = %d LIMIT 1", $user_id ) );
	}
}
