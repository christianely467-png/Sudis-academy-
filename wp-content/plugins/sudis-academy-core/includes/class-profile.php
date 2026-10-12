<?php
namespace Sudis\Academy;

defined( 'ABSPATH' ) || exit;

/**
 * Professional profile fields: profession and license number.
 *
 * Shown on the WordPress/LearnDash registration form (which fires the core
 * `register_form` hooks), the admin user profile and the front-end
 * [sudis_perfil_profesional] form.
 */
final class Profile {

	const META_PROFESSION = 'sudis_profesion';
	const META_LICENSE    = 'sudis_licencia';

	public static function init(): void {
		add_action( 'register_form', array( __CLASS__, 'render_register_fields' ) );
		add_filter( 'registration_errors', array( __CLASS__, 'validate_register' ), 10, 3 );
		add_action( 'user_register', array( __CLASS__, 'save_register' ) );

		add_action( 'show_user_profile', array( __CLASS__, 'render_admin_fields' ) );
		add_action( 'edit_user_profile', array( __CLASS__, 'render_admin_fields' ) );
		add_action( 'personal_options_update', array( __CLASS__, 'save_admin_fields' ) );
		add_action( 'edit_user_profile_update', array( __CLASS__, 'save_admin_fields' ) );

		add_shortcode( 'sudis_perfil_profesional', array( __CLASS__, 'shortcode' ) );
		add_action( 'init', array( __CLASS__, 'handle_front_form' ) );
	}

	/** @return string[] */
	public static function professions(): array {
		return apply_filters(
			'sudis_academy_professions',
			array(
				'Trabajador(a) Social',
				'Psicólogo(a)',
				'Consejero(a) Profesional',
				'Consejero(a) en Rehabilitación',
				'Enfermero(a)',
				'Médico(a)',
				'Terapeuta Ocupacional',
				'Terapeuta del Habla',
				'Educador(a)',
				'Estudiante',
				'Otra',
			)
		);
	}

	public static function get( int $user_id ): array {
		$user = get_userdata( $user_id );
		$name = $user ? trim( $user->first_name . ' ' . $user->last_name ) : '';
		if ( '' === $name && $user ) {
			$name = $user->display_name;
		}
		return array(
			'name'       => $name,
			'profession' => (string) get_user_meta( $user_id, self::META_PROFESSION, true ),
			'license'    => (string) get_user_meta( $user_id, self::META_LICENSE, true ),
		);
	}

	private static function sanitize_profession( string $value ): string {
		$value = sanitize_text_field( $value );
		return mb_substr( $value, 0, 190 );
	}

	private static function sanitize_license( string $value ): string {
		return mb_substr( preg_replace( '/[^\p{L}\p{N}\-\/ .]/u', '', sanitize_text_field( $value ) ), 0, 64 );
	}

	private static function fields_html( string $profession, string $license, ?string $first = null, ?string $last = null ): string {
		ob_start();
		$list    = self::professions();
		$is_other = '' !== $profession && ! in_array( $profession, $list, true );
		?>
		<?php if ( null !== $first ) : ?>
			<p>
				<label for="sudis_first_name"><?php esc_html_e( 'Nombre', 'sudis-academy' ); ?></label>
				<input type="text" name="sudis_first_name" id="sudis_first_name" class="input" value="<?php echo esc_attr( $first ); ?>" autocomplete="given-name" required>
			</p>
			<p>
				<label for="sudis_last_name"><?php esc_html_e( 'Apellidos', 'sudis-academy' ); ?></label>
				<input type="text" name="sudis_last_name" id="sudis_last_name" class="input" value="<?php echo esc_attr( $last ); ?>" autocomplete="family-name" required>
			</p>
		<?php endif; ?>
		<p>
			<label for="sudis_profesion"><?php esc_html_e( 'Profesión', 'sudis-academy' ); ?></label>
			<select name="sudis_profesion" id="sudis_profesion" class="input">
				<option value=""><?php esc_html_e( '— Selecciona —', 'sudis-academy' ); ?></option>
				<?php foreach ( $list as $item ) : ?>
					<option value="<?php echo esc_attr( $item ); ?>" <?php selected( $is_other ? 'Otra' : $profession, $item ); ?>><?php echo esc_html( $item ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>
		<p>
			<label for="sudis_profesion_otra"><?php esc_html_e( 'Si elegiste "Otra", indica cuál', 'sudis-academy' ); ?></label>
			<input type="text" name="sudis_profesion_otra" id="sudis_profesion_otra" class="input" value="<?php echo esc_attr( $is_other ? $profession : '' ); ?>">
		</p>
		<p>
			<label for="sudis_licencia"><?php esc_html_e( 'Número de licencia profesional (si aplica)', 'sudis-academy' ); ?></label>
			<input type="text" name="sudis_licencia" id="sudis_licencia" class="input" value="<?php echo esc_attr( $license ); ?>">
		</p>
		<?php
		return (string) ob_get_clean();
	}

	private static function profession_from_request(): string {
		$choice = isset( $_POST['sudis_profesion'] ) ? wp_unslash( $_POST['sudis_profesion'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( 'Otra' === $choice ) {
			$other = isset( $_POST['sudis_profesion_otra'] ) ? wp_unslash( $_POST['sudis_profesion_otra'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$choice = '' !== trim( $other ) ? $other : 'Otra';
		}
		return self::sanitize_profession( (string) $choice );
	}

	/* Registration ---------------------------------------------------- */

	public static function render_register_fields(): void {
		// phpcs:disable WordPress.Security.NonceVerification
		$first = isset( $_POST['sudis_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sudis_first_name'] ) ) : '';
		$last  = isset( $_POST['sudis_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sudis_last_name'] ) ) : '';
		$lic   = isset( $_POST['sudis_licencia'] ) ? sanitize_text_field( wp_unslash( $_POST['sudis_licencia'] ) ) : '';
		// phpcs:enable
		echo self::fields_html( self::profession_from_request(), $lic, $first, $last ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	public static function validate_register( \WP_Error $errors ): \WP_Error {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( isset( $_POST['sudis_first_name'] ) && '' === trim( sanitize_text_field( wp_unslash( $_POST['sudis_first_name'] ) ) ) ) {
			$errors->add( 'sudis_first_name', __( '<strong>Error</strong>: escribe tu nombre tal como debe aparecer en el certificado.', 'sudis-academy' ) );
		}
		if ( isset( $_POST['sudis_last_name'] ) && '' === trim( sanitize_text_field( wp_unslash( $_POST['sudis_last_name'] ) ) ) ) {
			$errors->add( 'sudis_last_name', __( '<strong>Error</strong>: escribe tus apellidos.', 'sudis-academy' ) );
		}
		if ( isset( $_POST['sudis_profesion'] ) && '' === self::profession_from_request() ) {
			$errors->add( 'sudis_profesion', __( '<strong>Error</strong>: selecciona tu profesión.', 'sudis-academy' ) );
		}
		// phpcs:enable
		return $errors;
	}

	public static function save_register( int $user_id ): void {
		// phpcs:disable WordPress.Security.NonceVerification
		if ( isset( $_POST['sudis_first_name'] ) ) {
			update_user_meta( $user_id, 'first_name', sanitize_text_field( wp_unslash( $_POST['sudis_first_name'] ) ) );
		}
		if ( isset( $_POST['sudis_last_name'] ) ) {
			update_user_meta( $user_id, 'last_name', sanitize_text_field( wp_unslash( $_POST['sudis_last_name'] ) ) );
		}
		if ( isset( $_POST['sudis_profesion'] ) ) {
			update_user_meta( $user_id, self::META_PROFESSION, self::profession_from_request() );
		}
		if ( isset( $_POST['sudis_licencia'] ) ) {
			update_user_meta( $user_id, self::META_LICENSE, self::sanitize_license( wp_unslash( $_POST['sudis_licencia'] ) ) );
		}
		// phpcs:enable
	}

	/* Admin profile ---------------------------------------------------- */

	public static function render_admin_fields( \WP_User $user ): void {
		$data = self::get( $user->ID );
		echo '<h2>' . esc_html__( 'SUDIS Academy: datos profesionales', 'sudis-academy' ) . '</h2>';
		echo '<div class="sudis-profile-fields">';
		echo self::fields_html( $data['profession'], $data['license'] ); // phpcs:ignore WordPress.Security.EscapeOutput
		echo '<p class="description">' . esc_html__( 'Se imprimen en los certificados que se emitan a partir de ahora. Los certificados ya emitidos conservan los datos con que se emitieron.', 'sudis-academy' ) . '</p>';
		echo '</div>';
	}

	public static function save_admin_fields( int $user_id ): void {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}
		check_admin_referer( 'update-user_' . $user_id );
		if ( isset( $_POST['sudis_profesion'] ) ) {
			update_user_meta( $user_id, self::META_PROFESSION, self::profession_from_request() );
		}
		if ( isset( $_POST['sudis_licencia'] ) ) {
			update_user_meta( $user_id, self::META_LICENSE, self::sanitize_license( wp_unslash( $_POST['sudis_licencia'] ) ) );
		}
	}

	/* Front-end form --------------------------------------------------- */

	public static function shortcode(): string {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Inicia sesión para completar tu perfil profesional.', 'sudis-academy' ) . '</p>';
		}
		$user = wp_get_current_user();
		$data = self::get( $user->ID );

		$out = '<form method="post" class="sudis-form sudis-profile-form">';
		if ( isset( $_GET['sudis_perfil'] ) && 'ok' === $_GET['sudis_perfil'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$out .= '<p class="sudis-notice">' . esc_html__( 'Perfil actualizado.', 'sudis-academy' ) . '</p>';
		}
		$out .= self::fields_html( $data['profession'], $data['license'], $user->first_name, $user->last_name );
		$out .= wp_nonce_field( 'sudis_profile_' . $user->ID, '_sudis_profile', true, false );
		$out .= '<input type="hidden" name="sudis_profile_submit" value="1">';
		$out .= '<p><button type="submit" class="wp-element-button">' . esc_html__( 'Guardar perfil', 'sudis-academy' ) . '</button></p>';
		$out .= '<p class="sudis-help">' . esc_html__( 'Escribe tu nombre exactamente como debe aparecer en tus certificados.', 'sudis-academy' ) . '</p>';
		$out .= '</form>';
		return $out;
	}

	public static function handle_front_form(): void {
		if ( empty( $_POST['sudis_profile_submit'] ) || ! is_user_logged_in() ) {
			return;
		}
		$user_id = get_current_user_id();
		if ( ! isset( $_POST['_sudis_profile'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_sudis_profile'] ) ), 'sudis_profile_' . $user_id ) ) {
			return;
		}

		$first = isset( $_POST['sudis_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sudis_first_name'] ) ) : '';
		$last  = isset( $_POST['sudis_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['sudis_last_name'] ) ) : '';
		if ( '' !== $first ) {
			update_user_meta( $user_id, 'first_name', $first );
		}
		if ( '' !== $last ) {
			update_user_meta( $user_id, 'last_name', $last );
		}
		update_user_meta( $user_id, self::META_PROFESSION, self::profession_from_request() );
		update_user_meta( $user_id, self::META_LICENSE, self::sanitize_license( isset( $_POST['sudis_licencia'] ) ? wp_unslash( $_POST['sudis_licencia'] ) : '' ) );

		wp_safe_redirect( add_query_arg( 'sudis_perfil', 'ok', wp_get_referer() ?: home_url( '/' ) ) );
		exit;
	}
}
