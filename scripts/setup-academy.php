<?php
/**
 * Creates the SUDIS Academy pages under /academia/ (idempotent).
 *
 * Run with: wp eval-file scripts/setup-academy.php
 * Safe to re-run: existing pages are updated in place, never duplicated.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$ld = defined( 'LEARNDASH_VERSION' );

$catalog = $ld
	? '<!-- wp:shortcode -->[ld_course_list col="3" progress_bar="true"]<!-- /wp:shortcode -->'
	: '<!-- wp:paragraph {"className":"sudis-placeholder"} --><p class="sudis-placeholder"><em>Aquí aparecerá el catálogo de cursos en cuanto LearnDash esté activo.</em></p><!-- /wp:paragraph -->';

$pages = array(
	'academia'                => array(
		'title'   => 'Academia',
		'parent'  => '',
		'content' => <<<HTML
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">
<!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center">
<!-- wp:column {"verticalAlignment":"center","width":"58%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:58%">
<!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.12em","fontWeight":"600"}},"fontSize":"small"} -->
<p class="has-small-font-size" style="font-weight:600;letter-spacing:0.12em;text-transform:uppercase">SUDIS Academy</p>
<!-- /wp:paragraph -->
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Educación continua para profesionales de Puerto Rico y Estados Unidos</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"fontSize":"medium"} -->
<p class="has-medium-font-size">Cursos en línea a tu ritmo, examen final y certificado con número único y verificación por QR, enviado a tu correo al aprobar.</p>
<!-- /wp:paragraph -->
<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#cursos">Ver cursos</a></div><!-- /wp:button -->
<!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/academia/organizaciones/">Matrícula para organizaciones</a></div><!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:column -->
<!-- wp:column {"verticalAlignment":"center","width":"42%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:42%">
<!-- wp:list {"className":"sudis-facts"} -->
<ul class="wp-block-list sudis-facts">
<!-- wp:list-item --><li><strong>80 %</strong> nota mínima en el examen final para obtener el certificado</li><!-- /wp:list-item -->
<!-- wp:list-item --><li><strong>PDF</strong> certificado enviado por correo y disponible en tu cuenta</li><!-- /wp:list-item -->
<!-- wp:list-item --><li><strong>QR</strong> verificación pública de autenticidad</li><!-- /wp:list-item -->
</ul>
<!-- /wp:list -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:group {"anchor":"cursos","layout":{"type":"constrained"}} -->
<div id="cursos" class="wp-block-group">
<!-- wp:heading -->
<h2 class="wp-block-heading">Cursos disponibles</h2>
<!-- /wp:heading -->
{$catalog}
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"}}},"backgroundColor":"accent-5","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-accent-5-background-color has-background" style="padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40)">
<!-- wp:columns {"verticalAlignment":"center"} -->
<div class="wp-block-columns are-vertically-aligned-center">
<!-- wp:column {"verticalAlignment":"center","width":"70%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:70%">
<!-- wp:paragraph --><p><strong>¿Tienes un certificado SUDIS?</strong> Verifica su autenticidad con el número o el código QR.</p><!-- /wp:paragraph -->
</div>
<!-- /wp:column -->
<!-- wp:column {"verticalAlignment":"center","width":"30%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:30%">
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"right"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/academia/verificar/">Verificar certificado</a></div><!-- /wp:button --></div>
<!-- /wp:buttons -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->
HTML,
	),
	'academia/mi-cuenta'      => array(
		'title'   => 'Mi cuenta',
		'parent'  => 'academia',
		'content' => ( $ld ? "<!-- wp:heading --><h2 class=\"wp-block-heading\">Mis cursos</h2><!-- /wp:heading -->\n<!-- wp:shortcode -->[ld_profile]<!-- /wp:shortcode -->\n" : '' )
			. "<!-- wp:heading --><h2 class=\"wp-block-heading\">Mis certificados</h2><!-- /wp:heading -->\n<!-- wp:shortcode -->[sudis_mis_certificados]<!-- /wp:shortcode -->\n"
			. "<!-- wp:heading --><h2 class=\"wp-block-heading\">Perfil profesional</h2><!-- /wp:heading -->\n<!-- wp:shortcode -->[sudis_perfil_profesional]<!-- /wp:shortcode -->",
	),
	'academia/verificar'      => array(
		'title'   => 'Verificar certificado',
		'parent'  => 'academia',
		'content' => "<!-- wp:paragraph --><p>Introduce el número que aparece en el certificado o escanea su código QR.</p><!-- /wp:paragraph -->\n<!-- wp:shortcode -->[sudis_verificar_certificado]<!-- /wp:shortcode -->",
	),
	'academia/organizaciones' => array(
		'title'   => 'Matrícula para organizaciones',
		'parent'  => 'academia',
		'content' => <<<HTML
<!-- wp:paragraph --><p>Capacita a tu equipo con los cursos de SUDIS Academy. Cada organización recibe un grupo propio y una persona responsable que puede ver el progreso y los certificados de sus participantes.</p><!-- /wp:paragraph -->
<!-- wp:list {"ordered":true} -->
<ol class="wp-block-list">
<!-- wp:list-item --><li>Escríbenos con los cursos y el número de participantes.</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Te enviamos la factura o el enlace de pago.</li><!-- /wp:list-item -->
<!-- wp:list-item --><li>Creamos el grupo, inscribimos a tu equipo y damos acceso a la persona responsable.</li><!-- /wp:list-item -->
</ol>
<!-- /wp:list -->
<!-- wp:paragraph --><p>Contacto: <strong>info@sudisinstitute.org</strong></p><!-- /wp:paragraph -->
HTML,
	),
);

$ids = array();
foreach ( $pages as $path => $page ) {
	$existing  = get_page_by_path( $path );
	$parent_id = $page['parent'] ? ( $ids[ $page['parent'] ] ?? ( get_page_by_path( $page['parent'] )->ID ?? 0 ) ) : 0;
	$slug      = basename( $path );
	$postarr   = array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $page['title'],
		'post_name'    => $slug,
		'post_parent'  => $parent_id,
		'post_content' => $page['content'],
	);
	if ( $existing ) {
		$postarr['ID'] = $existing->ID;
		$ids[ $path ]  = wp_update_post( $postarr );
	} else {
		$ids[ $path ] = wp_insert_post( $postarr );
	}
	echo "Página /{$path}/ → ID {$ids[ $path ]}\n";
}

// The plugin creates /verificar/ on activation; point it to /academia/verificar/ instead.
if ( class_exists( 'Sudis\\Academy\\Settings' ) ) {
	$old = get_page_by_path( 'verificar' );
	if ( $old && (int) $old->ID !== (int) $ids['academia/verificar'] && false !== strpos( $old->post_content, 'sudis_verificar_certificado' ) ) {
		wp_trash_post( $old->ID );
	}
	Sudis\Academy\Settings::update( array( 'verify_page_id' => (int) $ids['academia/verificar'] ) );
}

// LearnDash course URLs under /academia/cursos/ when LearnDash is active.
if ( $ld && class_exists( 'LearnDash_Settings_Section' ) ) {
	LearnDash_Settings_Section::set_section_setting( 'LearnDash_Settings_Section_Permalinks', 'courses', 'academia/cursos' );
	LearnDash_Settings_Section::set_section_setting( 'LearnDash_Settings_Section_Permalinks', 'lessons', 'lecciones' );
	LearnDash_Settings_Section::set_section_setting( 'LearnDash_Settings_Section_Permalinks', 'quizzes', 'examenes' );
}

flush_rewrite_rules();
echo "Listo.\n";
