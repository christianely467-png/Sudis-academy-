<?php
namespace Sudis\Academy;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Dompdf\Dompdf;
use Dompdf\Options;

defined( 'ABSPATH' ) || exit;

/** Renders certificate PDFs from templates/certificate.php with Dompdf. */
final class Renderer {

	public static function path( object $cert ): string {
		return Installer::storage_dir() . '/' . sanitize_file_name( $cert->file_name );
	}

	/** @return string|\WP_Error Path to the PDF, rendering it if missing. */
	public static function ensure_pdf( object $cert, bool $regenerate = false ) {
		$path = self::path( $cert );
		if ( ! $regenerate && file_exists( $path ) ) {
			return $path;
		}

		$pdf = self::render( $cert );
		if ( is_wp_error( $pdf ) ) {
			return $pdf;
		}

		Installer::protect_storage();
		if ( false === file_put_contents( $path, $pdf ) ) {
			return new \WP_Error( 'sudis_write', 'No se pudo guardar el PDF en ' . $path );
		}
		return $path;
	}

	/** @return string|\WP_Error Raw PDF bytes. */
	public static function render( object $cert ) {
		if ( ! Plugin::dependencies_loaded() ) {
			return new \WP_Error( 'sudis_deps', 'Faltan las librerías PDF/QR (ejecuta composer install en el plugin).' );
		}

		try {
			$html = self::html( $cert );

			$options = new Options();
			$options->set( 'isRemoteEnabled', false );
			$options->set( 'isHtml5ParserEnabled', true );
			$options->set( 'defaultFont', 'DejaVu Sans' );
			$options->set( 'tempDir', get_temp_dir() );
			$options->set( 'fontCache', get_temp_dir() );
			$options->set( 'chroot', array( SUDIS_ACADEMY_DIR ) );

			$dompdf = new Dompdf( $options );
			$dompdf->loadHtml( $html, 'UTF-8' );
			$dompdf->setPaper( 'A4', 'landscape' );
			$dompdf->render();
			return (string) $dompdf->output();
		} catch ( \Throwable $e ) {
			return new \WP_Error( 'sudis_render', $e->getMessage() );
		}
	}

	public static function html( object $cert ): string {
		$settings = Settings::all();
		$vars     = array(
			'cert'      => $cert,
			'settings'  => $settings,
			'is_ce'     => 'ce' === $cert->cert_type,
			'qr'        => self::qr_data_uri( Settings::verify_url( $cert->code ) ),
			'logo'      => self::image_data_uri( (string) $settings['logo_url'] ),
			'signature' => self::image_data_uri( (string) $settings['signature_url'] ),
			'date'      => self::format_date( $cert->completed_at ),
			'verify'    => preg_replace( '#^https?://#', '', untrailingslashit( Settings::verify_url() ) ),
		);

		$template = locate_template( 'sudis-academy/certificate.php' ) ?: SUDIS_ACADEMY_DIR . 'templates/certificate.php';
		$template = apply_filters( 'sudis_academy_certificate_template', $template, $cert );

		ob_start();
		( static function () use ( $template, $vars ) {
			extract( $vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract
			include $template;
		} )();
		return (string) ob_get_clean();
	}

	public static function qr_data_uri( string $url ): string {
		$options = new QROptions(
			array(
				'outputInterface' => QRGdImagePNG::class,
				'outputBase64'    => true,
				'eccLevel'        => EccLevel::M,
				'scale'           => 8,
				'addQuietzone'    => true,
				'quietzoneSize'   => 2,
			)
		);
		return ( new QRCode( $options ) )->render( $url );
	}

	/** Embeds a media-library image (local file only; remote loading is off). */
	public static function image_data_uri( string $url ): string {
		if ( '' === $url ) {
			return '';
		}
		$file = '';
		$id   = attachment_url_to_postid( $url );
		if ( $id ) {
			$file = (string) get_attached_file( $id );
		}
		if ( ! $file ) {
			$uploads = wp_upload_dir( null, false );
			if ( str_starts_with( $url, $uploads['baseurl'] ) ) {
				$file = $uploads['basedir'] . substr( $url, strlen( $uploads['baseurl'] ) );
			} elseif ( str_starts_with( $url, SUDIS_ACADEMY_URL ) ) {
				$file = SUDIS_ACADEMY_DIR . substr( $url, strlen( SUDIS_ACADEMY_URL ) );
			}
		}
		if ( ! $file || ! is_readable( $file ) ) {
			return '';
		}
		$type = wp_check_filetype( $file )['type'];
		if ( ! $type || ! str_starts_with( $type, 'image/' ) ) {
			return '';
		}
		return 'data:' . $type . ';base64,' . base64_encode( (string) file_get_contents( $file ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	}

	/** "12 de octubre de 2026" in the site's timezone, always in Spanish. */
	public static function format_date( string $gmt ): string {
		$months = array( 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre' );
		$ts     = strtotime( $gmt . ' UTC' );
		return sprintf( '%d de %s de %s', (int) wp_date( 'j', $ts ), $months[ (int) wp_date( 'n', $ts ) - 1 ], wp_date( 'Y', $ts ) );
	}

	public static function format_number( $value ): string {
		$n = (float) $value;
		return rtrim( rtrim( number_format( $n, 2, '.', '' ), '0' ), '.' );
	}

	/** Fake certificate for previews (no database row). */
	public static function sample(): object {
		return (object) array(
			'id'              => 0,
			'code'            => Settings::get( 'code_prefix' ) . '-' . wp_date( 'Y' ) . '-MUESTRA1',
			'user_id'         => 0,
			'course_id'       => 0,
			'cert_type'       => 'ce',
			'student_name'    => 'María Rodríguez Ortiz',
			'profession'      => 'Trabajador(a) Social',
			'license_number'  => '12345',
			'course_title'    => 'Ética profesional y confidencialidad',
			'hours'           => 6,
			'ce_credits'      => 0.6,
			'accreditor'      => '[Entidad acreditadora]',
			'provider_number' => '[Nº de proveedor]',
			'score'           => 92,
			'completed_at'    => current_time( 'mysql', true ),
			'issued_at'       => current_time( 'mysql', true ),
			'status'          => 'valid',
			'file_name'       => 'muestra.pdf',
		);
	}
}
