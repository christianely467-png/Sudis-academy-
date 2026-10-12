<?php
namespace Sudis\Academy;

defined( 'ABSPATH' ) || exit;

/** Per-course certificate settings shown as a meta box on LearnDash courses. */
final class Course_Meta {

	const POST_TYPE = 'sfwd-courses';
	const META      = '_sudis_certificate';

	public static function init(): void {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save' ), 10, 2 );
	}

	public static function defaults(): array {
		return array(
			'enabled'         => 1,
			'type'            => 'completion', // completion | ce
			'hours'           => 0,
			'ce_credits'      => 0,
			'accreditor'      => '',
			'provider_number' => '',
			'final_quiz_id'   => 0,
		);
	}

	public static function get( int $course_id ): array {
		$saved = get_post_meta( $course_id, self::META, true );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	public static function set( int $course_id, array $values ): void {
		update_post_meta( $course_id, self::META, array_merge( self::get( $course_id ), $values ) );
	}

	public static function add_box(): void {
		add_meta_box( 'sudis-certificate', __( 'Certificado SUDIS', 'sudis-academy' ), array( __CLASS__, 'render' ), self::POST_TYPE, 'side' );
	}

	public static function render( \WP_Post $post ): void {
		$m = self::get( $post->ID );
		wp_nonce_field( 'sudis_course_meta', '_sudis_course_meta' );
		?>
		<p><label><input type="checkbox" name="sudis_cert[enabled]" value="1" <?php checked( $m['enabled'], 1 ); ?>> <?php esc_html_e( 'Emitir certificado SUDIS', 'sudis-academy' ); ?></label></p>
		<p>
			<label for="sudis_cert_type"><strong><?php esc_html_e( 'Tipo', 'sudis-academy' ); ?></strong></label><br>
			<select id="sudis_cert_type" name="sudis_cert[type]" style="width:100%">
				<option value="completion" <?php selected( $m['type'], 'completion' ); ?>><?php esc_html_e( 'Certificado de finalización', 'sudis-academy' ); ?></option>
				<option value="ce" <?php selected( $m['type'], 'ce' ); ?>><?php esc_html_e( 'Educación continua acreditada', 'sudis-academy' ); ?></option>
			</select>
		</p>
		<p><label for="sudis_cert_hours"><?php esc_html_e( 'Horas contacto', 'sudis-academy' ); ?></label><br>
			<input id="sudis_cert_hours" type="number" step="0.25" min="0" name="sudis_cert[hours]" value="<?php echo esc_attr( $m['hours'] ); ?>" style="width:100%"></p>
		<p><label for="sudis_cert_ce"><?php esc_html_e( 'Créditos CE / CEU (solo acreditados)', 'sudis-academy' ); ?></label><br>
			<input id="sudis_cert_ce" type="number" step="0.01" min="0" name="sudis_cert[ce_credits]" value="<?php echo esc_attr( $m['ce_credits'] ); ?>" style="width:100%"></p>
		<p><label for="sudis_cert_acc"><?php esc_html_e( 'Entidad acreditadora', 'sudis-academy' ); ?></label><br>
			<input id="sudis_cert_acc" type="text" name="sudis_cert[accreditor]" value="<?php echo esc_attr( $m['accreditor'] ); ?>" style="width:100%"></p>
		<p><label for="sudis_cert_prov"><?php esc_html_e( 'Nº de proveedor / aprobación', 'sudis-academy' ); ?></label><br>
			<input id="sudis_cert_prov" type="text" name="sudis_cert[provider_number]" value="<?php echo esc_attr( $m['provider_number'] ); ?>" style="width:100%"></p>
		<p><label for="sudis_cert_quiz"><?php esc_html_e( 'ID del examen final (opcional)', 'sudis-academy' ); ?></label><br>
			<input id="sudis_cert_quiz" type="number" min="0" name="sudis_cert[final_quiz_id]" value="<?php echo esc_attr( $m['final_quiz_id'] ); ?>" style="width:100%">
			<span class="description"><?php printf( esc_html__( 'Si se indica, ese examen debe aprobarse con al menos %d %%. Si se deja vacío, se exige en todos los exámenes del curso.', 'sudis-academy' ), (int) Settings::get( 'min_score' ) ); ?></span></p>
		<?php
	}

	public static function save( int $post_id, \WP_Post $post ): void {
		if ( ! isset( $_POST['_sudis_course_meta'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_sudis_course_meta'] ) ), 'sudis_course_meta' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$in = isset( $_POST['sudis_cert'] ) && is_array( $_POST['sudis_cert'] ) ? wp_unslash( $_POST['sudis_cert'] ) : array();

		self::set(
			$post_id,
			array(
				'enabled'         => empty( $in['enabled'] ) ? 0 : 1,
				'type'            => ( isset( $in['type'] ) && 'ce' === $in['type'] ) ? 'ce' : 'completion',
				'hours'           => max( 0, round( (float) ( $in['hours'] ?? 0 ), 2 ) ),
				'ce_credits'      => max( 0, round( (float) ( $in['ce_credits'] ?? 0 ), 2 ) ),
				'accreditor'      => sanitize_text_field( $in['accreditor'] ?? '' ),
				'provider_number' => sanitize_text_field( $in['provider_number'] ?? '' ),
				'final_quiz_id'   => absint( $in['final_quiz_id'] ?? 0 ),
			)
		);
	}
}
