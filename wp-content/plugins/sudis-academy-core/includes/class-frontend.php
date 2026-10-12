<?php
namespace Sudis\Academy;

defined( 'ABSPATH' ) || exit;

/** Public verification page, "Mis certificados" and protected downloads. */
final class Frontend {

	public static function init(): void {
		add_shortcode( 'sudis_verificar_certificado', array( __CLASS__, 'verify_shortcode' ) );
		add_shortcode( 'sudis_mis_certificados', array( __CLASS__, 'my_certificates_shortcode' ) );
		add_action( 'template_redirect', array( __CLASS__, 'maybe_download' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
	}

	public static function register_assets(): void {
		wp_register_style( 'sudis-academy', SUDIS_ACADEMY_URL . 'assets/sudis-academy.css', array(), SUDIS_ACADEMY_VERSION );
	}

	private static function enqueue(): void {
		if ( ! wp_style_is( 'sudis-academy', 'registered' ) ) {
			self::register_assets();
		}
		wp_enqueue_style( 'sudis-academy' );
	}

	public static function download_url( object $cert ): string {
		return add_query_arg(
			array(
				'sudis_descargar' => rawurlencode( $cert->code ),
				'_wpnonce'        => wp_create_nonce( 'sudis_dl_' . $cert->code ),
			),
			home_url( '/' )
		);
	}

	public static function maybe_download(): void {
		if ( empty( $_GET['sudis_descargar'] ) ) {
			return;
		}
		$code = sanitize_text_field( wp_unslash( $_GET['sudis_descargar'] ) );

		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sudis_dl_' . $code ) ) {
			wp_die( esc_html__( 'El enlace de descarga caducó. Vuelve a tu cuenta y descárgalo de nuevo.', 'sudis-academy' ), '', array( 'response' => 403 ) );
		}

		$cert = Repository::find_by_code( $code );
		if ( ! $cert || ( (int) $cert->user_id !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) ) {
			wp_die( esc_html__( 'Certificado no encontrado.', 'sudis-academy' ), '', array( 'response' => 404 ) );
		}
		if ( 'valid' !== $cert->status && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Este certificado fue revocado.', 'sudis-academy' ), '', array( 'response' => 410 ) );
		}

		$path = Renderer::ensure_pdf( $cert );
		if ( is_wp_error( $path ) ) {
			wp_die( esc_html( $path->get_error_message() ) );
		}

		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: inline; filename="' . basename( $path ) . '"' );
		header( 'Content-Length: ' . filesize( $path ) );
		header( 'X-Robots-Tag: noindex' );
		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}

	public static function verify_shortcode(): string {
		self::enqueue();
		$code = isset( $_GET['codigo'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['codigo'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		ob_start();
		?>
		<div class="sudis-verify">
			<form method="get" class="sudis-verify-form" action="<?php echo esc_url( Settings::verify_url() ); ?>">
				<label for="sudis-codigo"><?php esc_html_e( 'Número de certificado', 'sudis-academy' ); ?></label>
				<div class="sudis-verify-row">
					<input id="sudis-codigo" name="codigo" type="text" value="<?php echo esc_attr( $code ); ?>" placeholder="<?php echo esc_attr( Settings::get( 'code_prefix' ) . '-' . wp_date( 'Y' ) . '-XXXXXXXX' ); ?>" autocomplete="off" spellcheck="false" required>
					<button type="submit" class="wp-element-button"><?php esc_html_e( 'Verificar', 'sudis-academy' ); ?></button>
				</div>
			</form>
			<?php
			if ( '' !== $code ) {
				$cert = Repository::find_by_code( $code );
				if ( ! $cert ) {
					echo '<div class="sudis-result sudis-result--none" role="status"><strong>' . esc_html__( 'Certificado no encontrado', 'sudis-academy' ) . '</strong><p>' . esc_html__( 'Revisa que el número esté escrito correctamente, incluidos los guiones.', 'sudis-academy' ) . '</p></div>';
				} else {
					echo self::result_card( $cert ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
			}
			?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private static function result_card( object $cert ): string {
		$valid = 'valid' === $cert->status;
		$rows  = array(
			__( 'Participante', 'sudis-academy' )            => $cert->student_name,
			__( 'Curso', 'sudis-academy' )                   => $cert->course_title,
			__( 'Tipo', 'sudis-academy' )                    => 'ce' === $cert->cert_type ? __( 'Educación continua acreditada', 'sudis-academy' ) : __( 'Finalización', 'sudis-academy' ),
			__( 'Horas contacto', 'sudis-academy' )          => (float) $cert->hours > 0 ? Renderer::format_number( $cert->hours ) : '',
			__( 'Créditos CEU', 'sudis-academy' )            => 'ce' === $cert->cert_type && (float) $cert->ce_credits > 0 ? Renderer::format_number( $cert->ce_credits ) : '',
			__( 'Entidad acreditadora', 'sudis-academy' )    => 'ce' === $cert->cert_type ? $cert->accreditor : '',
			__( 'Fecha de finalización', 'sudis-academy' )   => Renderer::format_date( $cert->completed_at ),
			__( 'Número de certificado', 'sudis-academy' )   => $cert->code,
		);

		$out  = '<div class="sudis-result ' . ( $valid ? 'sudis-result--valid' : 'sudis-result--revoked' ) . '" role="status">';
		$out .= '<span class="sudis-badge">' . ( $valid ? esc_html__( 'Certificado válido', 'sudis-academy' ) : esc_html__( 'Certificado revocado', 'sudis-academy' ) ) . '</span>';
		$out .= '<p class="sudis-result-lead">' . ( $valid
			? sprintf( esc_html__( 'Este certificado fue emitido por %s y es auténtico.', 'sudis-academy' ), esc_html( Settings::get( 'institution' ) ) )
			: esc_html__( 'Este certificado fue anulado y ya no tiene validez.', 'sudis-academy' ) ) . '</p>';
		$out .= '<dl>';
		foreach ( $rows as $label => $value ) {
			if ( '' === (string) $value ) {
				continue;
			}
			$out .= '<dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( (string) $value ) . '</dd>';
		}
		$out .= '</dl></div>';
		return $out;
	}

	public static function my_certificates_shortcode(): string {
		self::enqueue();
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Inicia sesión para ver tus certificados.', 'sudis-academy' ) . '</p>';
		}
		$certs = Repository::for_user( get_current_user_id() );
		if ( ! $certs ) {
			return '<div class="sudis-empty"><p>' . esc_html__( 'Todavía no tienes certificados. Aparecerán aquí cuando completes un curso y apruebes su examen final.', 'sudis-academy' ) . '</p></div>';
		}

		$out  = '<div class="sudis-table-wrap"><table class="sudis-table"><thead><tr>';
		$out .= '<th>' . esc_html__( 'Curso', 'sudis-academy' ) . '</th><th>' . esc_html__( 'Tipo', 'sudis-academy' ) . '</th><th>' . esc_html__( 'Horas', 'sudis-academy' ) . '</th><th>' . esc_html__( 'Fecha', 'sudis-academy' ) . '</th><th>' . esc_html__( 'Número', 'sudis-academy' ) . '</th><th></th>';
		$out .= '</tr></thead><tbody>';
		foreach ( $certs as $cert ) {
			$out .= '<tr>';
			$out .= '<td>' . esc_html( $cert->course_title ) . '</td>';
			$out .= '<td>' . ( 'ce' === $cert->cert_type ? esc_html__( 'Educación continua', 'sudis-academy' ) : esc_html__( 'Finalización', 'sudis-academy' ) ) . '</td>';
			$out .= '<td>' . esc_html( Renderer::format_number( $cert->hours ) ) . '</td>';
			$out .= '<td>' . esc_html( Renderer::format_date( $cert->completed_at ) ) . '</td>';
			$out .= '<td><code>' . esc_html( $cert->code ) . '</code></td>';
			$out .= '<td><a class="wp-element-button sudis-btn-sm" href="' . esc_url( self::download_url( $cert ) ) . '">' . esc_html__( 'Descargar PDF', 'sudis-academy' ) . '</a></td>';
			$out .= '</tr>';
		}
		$out .= '</tbody></table></div>';
		return $out;
	}
}
