<?php
namespace Sudis\Academy;

defined( 'ABSPATH' ) || exit;

final class Settings {

	const OPTION = 'sudis_academy_settings';

	public static function defaults(): array {
		return array(
			'min_score'        => 80,
			'code_prefix'      => 'SUDIS',
			'institution'      => 'SUDIS Institute',
			'logo_url'         => '',
			'signature_url'    => '',
			'signer_name'      => '',
			'signer_title'     => '',
			'verify_page_id'   => 0,
			'email_from_name'  => 'SUDIS Academy',
			'email_from'       => '',
			'email_bcc'        => '',
			'email_subject'    => '¡Felicitaciones! Tu certificado de {curso}',
			'color_primary'    => '#1d5f8a',
			'color_accent'     => '#9a7330',
		);
	}

	public static function all(): array {
		$saved = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	public static function get( string $key ) {
		$all = self::all();
		return $all[ $key ] ?? null;
	}

	public static function update( array $values ): void {
		update_option( self::OPTION, array_merge( self::all(), $values ) );
	}

	public static function sanitize( array $input ): array {
		$clean = self::all();

		$clean['min_score']       = max( 0, min( 100, (int) ( $input['min_score'] ?? 80 ) ) );
		$clean['code_prefix']     = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) ( $input['code_prefix'] ?? 'SUDIS' ) ) ) ?: 'SUDIS';
		$clean['institution']     = sanitize_text_field( $input['institution'] ?? '' );
		$clean['logo_url']        = esc_url_raw( $input['logo_url'] ?? '' );
		$clean['signature_url']   = esc_url_raw( $input['signature_url'] ?? '' );
		$clean['signer_name']     = sanitize_text_field( $input['signer_name'] ?? '' );
		$clean['signer_title']    = sanitize_text_field( $input['signer_title'] ?? '' );
		$clean['verify_page_id']  = absint( $input['verify_page_id'] ?? 0 );
		$clean['email_from_name'] = sanitize_text_field( $input['email_from_name'] ?? '' );
		$clean['email_from']      = sanitize_email( $input['email_from'] ?? '' );
		$clean['email_bcc']       = sanitize_email( $input['email_bcc'] ?? '' );
		$clean['email_subject']   = sanitize_text_field( $input['email_subject'] ?? '' );
		$clean['color_primary']   = sanitize_hex_color( $input['color_primary'] ?? '' ) ?: self::defaults()['color_primary'];
		$clean['color_accent']    = sanitize_hex_color( $input['color_accent'] ?? '' ) ?: self::defaults()['color_accent'];

		return $clean;
	}

	/** Public URL that verifies a given certificate code. */
	public static function verify_url( string $code = '' ): string {
		$page_id = (int) self::get( 'verify_page_id' );
		$base    = $page_id ? get_permalink( $page_id ) : home_url( '/' );
		return $code ? add_query_arg( 'codigo', rawurlencode( $code ), $base ) : $base;
	}
}
