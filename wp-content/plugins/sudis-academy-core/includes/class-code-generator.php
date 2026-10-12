<?php
namespace Sudis\Academy;

defined( 'ABSPATH' ) || exit;

/**
 * Certificate numbers: PREFIX-YYYY-XXXXXXXX.
 *
 * The random part avoids sequential numbers so nobody can enumerate other
 * participants through the public verification page. The alphabet drops
 * 0/O and 1/I to avoid transcription errors.
 */
final class Code_Generator {

	const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
	const LENGTH   = 8;

	public static function generate(): string {
		$prefix = (string) Settings::get( 'code_prefix' );
		$year   = wp_date( 'Y' );
		$max    = strlen( self::ALPHABET ) - 1;

		do {
			$random = '';
			for ( $i = 0; $i < self::LENGTH; $i++ ) {
				$random .= self::ALPHABET[ random_int( 0, $max ) ];
			}
			$code = "{$prefix}-{$year}-{$random}";
		} while ( Repository::code_exists( $code ) );

		return $code;
	}
}
