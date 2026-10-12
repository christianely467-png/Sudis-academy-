<?php
namespace Sudis\Academy;

defined( 'ABSPATH' ) || exit;

/** Admin screens: certificate list and actions, manual issue, settings. */
final class Admin {

	const CAP  = 'manage_options';
	const SLUG = 'sudis-academy';

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_post_sudis_cert_action', array( __CLASS__, 'handle_action' ) );
		add_action( 'admin_post_sudis_cert_issue', array( __CLASS__, 'handle_issue' ) );
		add_action( 'admin_post_sudis_cert_sample', array( __CLASS__, 'handle_sample' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
	}

	public static function menu(): void {
		add_menu_page( 'SUDIS Academy', 'SUDIS Academy', self::CAP, self::SLUG, array( __CLASS__, 'page_list' ), 'dashicons-awards', 58 );
		add_submenu_page( self::SLUG, __( 'Certificados', 'sudis-academy' ), __( 'Certificados', 'sudis-academy' ), self::CAP, self::SLUG, array( __CLASS__, 'page_list' ) );
		add_submenu_page( self::SLUG, __( 'Emitir certificado', 'sudis-academy' ), __( 'Emitir manualmente', 'sudis-academy' ), self::CAP, self::SLUG . '-issue', array( __CLASS__, 'page_issue' ) );
		add_submenu_page( self::SLUG, __( 'Ajustes', 'sudis-academy' ), __( 'Ajustes', 'sudis-academy' ), self::CAP, self::SLUG . '-settings', array( __CLASS__, 'page_settings' ) );
	}

	public static function register_settings(): void {
		register_setting( 'sudis_academy', Settings::OPTION, array( 'sanitize_callback' => array( Settings::class, 'sanitize' ) ) );
	}

	public static function notices(): void {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		if ( ! Plugin::learndash_active() ) {
			echo '<div class="notice notice-warning"><p><strong>SUDIS Academy:</strong> ' . esc_html__( 'LearnDash no está activo. Los certificados se emitirán automáticamente cuando LearnDash esté instalado.', 'sudis-academy' ) . '</p></div>';
		}
		if ( ! Plugin::dependencies_loaded() ) {
			echo '<div class="notice notice-error"><p><strong>SUDIS Academy:</strong> ' . esc_html__( 'Faltan las librerías para generar PDF. Instala el plugin desde el archivo .zip empaquetado (incluye la carpeta vendor).', 'sudis-academy' ) . '</p></div>';
		}
		if ( isset( $_GET['sudis_msg'] ) && str_starts_with( (string) ( $_GET['page'] ?? '' ), self::SLUG ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$msg  = sanitize_text_field( wp_unslash( $_GET['sudis_msg'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			$type = isset( $_GET['sudis_err'] ) ? 'error' : 'success'; // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $msg ) . '</p></div>';
		}
	}

	private static function redirect( string $page, string $msg, bool $error = false ): void {
		$args = array( 'page' => $page, 'sudis_msg' => rawurlencode( $msg ) );
		if ( $error ) {
			$args['sudis_err'] = 1;
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/* List ------------------------------------------------------------- */

	public static function page_list(): void {
		$term     = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$paged    = max( 1, absint( $_GET['paged'] ?? 1 ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$per_page = 25;
		list( $rows, $total ) = Repository::search( $term, $paged, $per_page );
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php esc_html_e( 'Certificados', 'sudis-academy' ); ?></h1>
			<a class="page-title-action" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SLUG . '-issue' ) ); ?>"><?php esc_html_e( 'Emitir manualmente', 'sudis-academy' ); ?></a>
			<hr class="wp-header-end">
			<form method="get">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::SLUG ); ?>">
				<p class="search-box">
					<label class="screen-reader-text" for="sudis-search"><?php esc_html_e( 'Buscar', 'sudis-academy' ); ?></label>
					<input type="search" id="sudis-search" name="s" value="<?php echo esc_attr( $term ); ?>" placeholder="<?php esc_attr_e( 'Número, nombre o curso', 'sudis-academy' ); ?>">
					<button class="button"><?php esc_html_e( 'Buscar', 'sudis-academy' ); ?></button>
				</p>
			</form>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Número', 'sudis-academy' ); ?></th>
					<th><?php esc_html_e( 'Participante', 'sudis-academy' ); ?></th>
					<th><?php esc_html_e( 'Curso', 'sudis-academy' ); ?></th>
					<th><?php esc_html_e( 'Tipo', 'sudis-academy' ); ?></th>
					<th><?php esc_html_e( 'Nota', 'sudis-academy' ); ?></th>
					<th><?php esc_html_e( 'Finalización', 'sudis-academy' ); ?></th>
					<th><?php esc_html_e( 'Correo', 'sudis-academy' ); ?></th>
					<th><?php esc_html_e( 'Estado', 'sudis-academy' ); ?></th>
					<th><?php esc_html_e( 'Acciones', 'sudis-academy' ); ?></th>
				</tr></thead>
				<tbody>
				<?php if ( ! $rows ) : ?>
					<tr><td colspan="9"><?php esc_html_e( 'No hay certificados todavía.', 'sudis-academy' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $rows as $c ) : ?>
					<tr>
						<td><code><?php echo esc_html( $c->code ); ?></code></td>
						<td><a href="<?php echo esc_url( get_edit_user_link( (int) $c->user_id ) ); ?>"><?php echo esc_html( $c->student_name ); ?></a></td>
						<td><?php echo esc_html( $c->course_title ); ?></td>
						<td><?php echo 'ce' === $c->cert_type ? esc_html__( 'Educación continua', 'sudis-academy' ) : esc_html__( 'Finalización', 'sudis-academy' ); ?></td>
						<td><?php echo null === $c->score ? '—' : esc_html( Renderer::format_number( $c->score ) . ' %' ); ?></td>
						<td><?php echo esc_html( Renderer::format_date( $c->completed_at ) ); ?></td>
						<td><?php echo $c->emailed_at ? esc_html__( 'Enviado', 'sudis-academy' ) : ( (int) $c->email_attempts ? esc_html__( 'Falló', 'sudis-academy' ) : esc_html__( 'En cola', 'sudis-academy' ) ); ?></td>
						<td><?php echo 'valid' === $c->status ? esc_html__( 'Válido', 'sudis-academy' ) : '<strong style="color:#b3261e">' . esc_html__( 'Revocado', 'sudis-academy' ) . '</strong>'; ?></td>
						<td>
							<a href="<?php echo esc_url( Frontend::download_url( $c ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'PDF', 'sudis-academy' ); ?></a> ·
							<a href="<?php echo esc_url( Settings::verify_url( $c->code ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Verificar', 'sudis-academy' ); ?></a> ·
							<?php echo self::action_link( $c, 'resend', __( 'Reenviar', 'sudis-academy' ) ); // phpcs:ignore ?> ·
							<?php echo self::action_link( $c, 'regenerate', __( 'Regenerar PDF', 'sudis-academy' ) ); // phpcs:ignore ?> ·
							<?php echo 'valid' === $c->status ? self::action_link( $c, 'revoke', __( 'Revocar', 'sudis-academy' ) ) : self::action_link( $c, 'restore', __( 'Restaurar', 'sudis-academy' ) ); // phpcs:ignore ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php
			$pages = (int) ceil( $total / $per_page );
			if ( $pages > 1 ) {
				echo '<div class="tablenav"><div class="tablenav-pages">' . paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'current' => $paged, 'total' => $pages ) ) . '</div></div>'; // phpcs:ignore
			}
			?>
		</div>
		<?php
	}

	private static function action_link( object $c, string $action, string $label ): string {
		$url = wp_nonce_url(
			add_query_arg( array( 'action' => 'sudis_cert_action', 'do' => $action, 'id' => (int) $c->id ), admin_url( 'admin-post.php' ) ),
			'sudis_cert_' . $action . '_' . (int) $c->id
		);
		$confirm = 'revoke' === $action ? ' onclick="return confirm(\'' . esc_js( __( '¿Revocar este certificado? Dejará de aparecer como válido en la verificación.', 'sudis-academy' ) ) . '\')"' : '';
		return '<a href="' . esc_url( $url ) . '"' . $confirm . '>' . esc_html( $label ) . '</a>';
	}

	public static function handle_action(): void {
		$id     = absint( $_GET['id'] ?? 0 );
		$action = sanitize_key( $_GET['do'] ?? '' );
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'No tienes permiso.', 'sudis-academy' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'sudis_cert_' . $action . '_' . $id );

		$cert = Repository::find( $id );
		if ( ! $cert ) {
			self::redirect( self::SLUG, __( 'Certificado no encontrado.', 'sudis-academy' ), true );
		}

		switch ( $action ) {
			case 'resend':
				$path = Renderer::ensure_pdf( $cert );
				$ok   = ! is_wp_error( $path ) && Mailer::send_certificate( $cert, $path );
				if ( $ok ) {
					Repository::update( $id, array( 'emailed_at' => current_time( 'mysql', true ), 'email_attempts' => (int) $cert->email_attempts + 1 ) );
				}
				self::redirect( self::SLUG, $ok ? __( 'Certificado reenviado.', 'sudis-academy' ) : __( 'No se pudo enviar: ', 'sudis-academy' ) . ( is_wp_error( $path ) ? $path->get_error_message() : Mailer::last_error() ), ! $ok );
				break;
			case 'regenerate':
				$path = Renderer::ensure_pdf( $cert, true );
				self::redirect( self::SLUG, is_wp_error( $path ) ? $path->get_error_message() : __( 'PDF regenerado.', 'sudis-academy' ), is_wp_error( $path ) );
				break;
			case 'revoke':
				Repository::update( $id, array( 'status' => 'revoked' ) );
				self::redirect( self::SLUG, __( 'Certificado revocado.', 'sudis-academy' ) );
				break;
			case 'restore':
				Repository::update( $id, array( 'status' => 'valid' ) );
				self::redirect( self::SLUG, __( 'Certificado restaurado.', 'sudis-academy' ) );
				break;
		}
		self::redirect( self::SLUG, __( 'Acción desconocida.', 'sudis-academy' ), true );
	}

	/* Manual issue ----------------------------------------------------- */

	public static function page_issue(): void {
		$courses = get_posts( array( 'post_type' => Course_Meta::POST_TYPE, 'numberposts' => -1, 'post_status' => 'publish', 'orderby' => 'title', 'order' => 'ASC' ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Emitir certificado manualmente', 'sudis-academy' ); ?></h1>
			<p><?php esc_html_e( 'Para casos especiales (p. ej. cursos presenciales o completados antes de activar el sistema). No exige la nota mínima.', 'sudis-academy' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="sudis_cert_issue">
				<?php wp_nonce_field( 'sudis_cert_issue' ); ?>
				<table class="form-table" role="presentation">
					<tr><th><label for="sudis-user"><?php esc_html_e( 'Correo o usuario del estudiante', 'sudis-academy' ); ?></label></th>
						<td><input id="sudis-user" name="user" type="text" class="regular-text" required></td></tr>
					<tr><th><label for="sudis-course"><?php esc_html_e( 'Curso', 'sudis-academy' ); ?></label></th>
						<td><select id="sudis-course" name="course" required>
							<option value=""><?php esc_html_e( '— Selecciona —', 'sudis-academy' ); ?></option>
							<?php foreach ( $courses as $course ) : ?>
								<option value="<?php echo (int) $course->ID; ?>"><?php echo esc_html( get_the_title( $course ) ); ?></option>
							<?php endforeach; ?>
						</select></td></tr>
					<tr><th><label for="sudis-date"><?php esc_html_e( 'Fecha de finalización', 'sudis-academy' ); ?></label></th>
						<td><input id="sudis-date" name="date" type="date" value="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"></td></tr>
				</table>
				<?php submit_button( __( 'Emitir y enviar por correo', 'sudis-academy' ) ); ?>
			</form>
		</div>
		<?php
	}

	public static function handle_issue(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'No tienes permiso.', 'sudis-academy' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'sudis_cert_issue' );
		$login  = sanitize_text_field( wp_unslash( $_POST['user'] ?? '' ) );
		$user   = is_email( $login ) ? get_user_by( 'email', $login ) : get_user_by( 'login', $login );
		$course = absint( $_POST['course'] ?? 0 );
		$date   = sanitize_text_field( wp_unslash( $_POST['date'] ?? '' ) );
		$ts     = $date ? (int) ( new \DateTimeImmutable( $date . ' 12:00', wp_timezone() ) )->getTimestamp() : time();

		if ( ! $user ) {
			self::redirect( self::SLUG . '-issue', __( 'No existe ese usuario.', 'sudis-academy' ), true );
		}
		$result = Issuer::issue( $user->ID, $course, $ts, true );
		if ( is_wp_error( $result ) ) {
			self::redirect( self::SLUG . '-issue', $result->get_error_message(), true );
		}
		self::redirect( self::SLUG, sprintf( __( 'Certificado %s emitido; el correo se enviará en unos instantes.', 'sudis-academy' ), $result->code ) );
	}

	/* Settings --------------------------------------------------------- */

	public static function handle_sample(): void {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'No tienes permiso.', 'sudis-academy' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'sudis_cert_sample' );
		$sample = Renderer::sample();
		if ( isset( $_GET['type'] ) && 'completion' === $_GET['type'] ) {
			$sample->cert_type = 'completion';
			$sample->hours     = 3;
		}
		$pdf = Renderer::render( $sample );
		if ( is_wp_error( $pdf ) ) {
			wp_die( esc_html( $pdf->get_error_message() ) );
		}
		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: inline; filename="muestra-certificado.pdf"' );
		echo $pdf; // phpcs:ignore WordPress.Security.EscapeOutput
		exit;
	}

	public static function page_settings(): void {
		$s     = Settings::all();
		$name  = Settings::OPTION;
		$pages = get_pages();
		$field = static function ( string $key, string $label, string $type = 'text', string $help = '' ) use ( $s, $name ) {
			printf(
				'<tr><th><label for="sudis-%1$s">%2$s</label></th><td><input id="sudis-%1$s" name="%3$s[%1$s]" type="%4$s" value="%5$s" class="regular-text">%6$s</td></tr>',
				esc_attr( $key ),
				esc_html( $label ),
				esc_attr( $name ),
				esc_attr( $type ),
				esc_attr( (string) $s[ $key ] ),
				$help ? '<p class="description">' . esc_html( $help ) . '</p>' : ''
			);
		};
		$sample = static fn( string $type ) => wp_nonce_url( admin_url( 'admin-post.php?action=sudis_cert_sample&type=' . $type ), 'sudis_cert_sample' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Ajustes de SUDIS Academy', 'sudis-academy' ); ?></h1>
			<p>
				<a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( $sample( 'completion' ) ); ?>"><?php esc_html_e( 'Ver muestra: Finalización', 'sudis-academy' ); ?></a>
				<a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( $sample( 'ce' ) ); ?>"><?php esc_html_e( 'Ver muestra: Educación continua', 'sudis-academy' ); ?></a>
			</p>
			<form method="post" action="options.php">
				<?php settings_fields( 'sudis_academy' ); ?>
				<h2><?php esc_html_e( 'Reglas', 'sudis-academy' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php $field( 'min_score', __( 'Nota mínima (%)', 'sudis-academy' ), 'number', __( 'Configura también la misma nota de aprobación en el examen final de LearnDash.', 'sudis-academy' ) ); ?>
					<?php $field( 'code_prefix', __( 'Prefijo del número', 'sudis-academy' ), 'text', __( 'Ejemplo: SUDIS-2026-7K3P9QXA', 'sudis-academy' ) ); ?>
					<tr><th><label for="sudis-verify_page_id"><?php esc_html_e( 'Página de verificación', 'sudis-academy' ); ?></label></th><td>
						<select id="sudis-verify_page_id" name="<?php echo esc_attr( $name ); ?>[verify_page_id]">
							<?php foreach ( $pages as $p ) : ?>
								<option value="<?php echo (int) $p->ID; ?>" <?php selected( (int) $s['verify_page_id'], $p->ID ); ?>><?php echo esc_html( get_page_uri( $p ) ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description"><?php esc_html_e( 'Debe contener el shortcode [sudis_verificar_certificado]. El QR apunta a esta página.', 'sudis-academy' ); ?></p>
					</td></tr>
				</table>
				<h2><?php esc_html_e( 'Diseño del certificado', 'sudis-academy' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php $field( 'institution', __( 'Institución', 'sudis-academy' ) ); ?>
					<?php $field( 'logo_url', __( 'URL del logotipo', 'sudis-academy' ), 'url', __( 'Súbelo a Medios y pega aquí su URL. PNG o JPG.', 'sudis-academy' ) ); ?>
					<?php $field( 'signature_url', __( 'URL de la firma', 'sudis-academy' ), 'url', __( 'PNG con fondo transparente, subido a Medios.', 'sudis-academy' ) ); ?>
					<?php $field( 'signer_name', __( 'Nombre del firmante', 'sudis-academy' ) ); ?>
					<?php $field( 'signer_title', __( 'Cargo del firmante', 'sudis-academy' ) ); ?>
					<?php $field( 'color_primary', __( 'Color principal', 'sudis-academy' ), 'color' ); ?>
					<?php $field( 'color_accent', __( 'Color de acento (bordes)', 'sudis-academy' ), 'color' ); ?>
				</table>
				<h2><?php esc_html_e( 'Correo', 'sudis-academy' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php $field( 'email_from_name', __( 'Nombre del remitente', 'sudis-academy' ) ); ?>
					<?php $field( 'email_from', __( 'Correo del remitente', 'sudis-academy' ), 'email', __( 'Vacío = el configurado en el plugin SMTP.', 'sudis-academy' ) ); ?>
					<?php $field( 'email_bcc', __( 'Copia oculta a', 'sudis-academy' ), 'email', __( 'Opcional: recibe copia de cada certificado enviado.', 'sudis-academy' ) ); ?>
					<?php $field( 'email_subject', __( 'Asunto', 'sudis-academy' ), 'text', __( '{curso} se reemplaza por el título del curso.', 'sudis-academy' ) ); ?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
