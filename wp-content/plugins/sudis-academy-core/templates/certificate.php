<?php
/**
 * Certificate template (A4 landscape, rendered by Dompdf).
 *
 * Override by copying to {theme}/sudis-academy/certificate.php.
 *
 * Available: $cert, $settings, $is_ce, $qr, $logo, $signature, $date, $verify.
 * Dompdf supports CSS 2.1 layout only: positioning and tables, no flex/grid.
 */
defined( 'ABSPATH' ) || exit;

$primary = $settings['color_primary'];
$accent  = $settings['color_accent'];
$hours   = \Sudis\Academy\Renderer::format_number( $cert->hours );
$credits = \Sudis\Academy\Renderer::format_number( $cert->ce_credits );
$prof    = trim( $cert->profession . ( '' !== $cert->license_number ? ' · Licencia Nº ' . $cert->license_number : '' ), ' ·' );
$initial = mb_strtoupper( mb_substr( (string) $settings['institution'], 0, 1 ) );
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
	@page { margin: 0; }
	* { box-sizing: border-box; }
	body { margin: 0; font-family: "DejaVu Sans", sans-serif; color: #1b2a38; background: #fffdf8; }
	.page { position: absolute; top: 0; left: 0; width: 297mm; height: 210mm; background: #fffdf8; }
	.frame-outer { position: absolute; top: 7mm; left: 7mm; right: 7mm; bottom: 7mm; border: 1.4pt solid <?php echo esc_attr( $accent ); ?>; }
	.frame-inner { position: absolute; top: 9.5mm; left: 9.5mm; right: 9.5mm; bottom: 9.5mm; border: 0.5pt solid <?php echo esc_attr( $accent ); ?>; }
	.band { position: absolute; top: 9.5mm; left: 9.5mm; width: 4mm; bottom: 9.5mm; background: <?php echo esc_attr( $primary ); ?>; }

	.top { position: absolute; top: 18mm; left: 24mm; right: 22mm; }
	.logo-img { height: 16mm; }
	.logo-mark { display: inline-block; width: 12mm; height: 12mm; line-height: 12mm; text-align: center; background: <?php echo esc_attr( $primary ); ?>; color: #fff; font-weight: bold; font-size: 16pt; border-radius: 1.5mm; vertical-align: middle; }
	.logo-name { display: inline-block; vertical-align: middle; margin-left: 3mm; font-family: "DejaVu Serif", serif; font-weight: bold; font-size: 14pt; color: #132a3e; }
	.number { position: absolute; top: 0; right: 0; text-align: right; font-size: 7.5pt; color: #566676; line-height: 1.5; }
	.number strong { font-family: "DejaVu Sans Mono", monospace; font-size: 9pt; color: #132a3e; }

	.middle { position: absolute; top: 56mm; left: 30mm; right: 30mm; text-align: center; }
	.type { font-size: 9pt; letter-spacing: 3pt; text-transform: uppercase; color: <?php echo esc_attr( $accent ); ?>; font-weight: bold; }
	.small { font-size: 9.5pt; color: #566676; margin-top: 4mm; }
	.name { font-family: "DejaVu Serif", serif; font-weight: bold; font-size: 27pt; color: #132a3e; margin: 2mm auto 0; padding: 0 10mm 1.5mm; border-bottom: 0.6pt solid <?php echo esc_attr( $accent ); ?>; display: inline-block; }
	.prof { font-size: 9.5pt; color: #33475a; margin-top: 2.5mm; }
	.course { font-family: "DejaVu Serif", serif; font-weight: bold; font-size: 15pt; color: #132a3e; margin-top: 2mm; }
	.hours { font-size: 10pt; color: #33475a; margin-top: 2.5mm; }
	.ce-line { font-size: 8pt; color: #33475a; margin-top: 3mm; }

	.bottom { position: absolute; left: 24mm; right: 22mm; bottom: 19mm; }
	.bottom table { width: 100%; border-collapse: collapse; }
	.bottom td { vertical-align: bottom; }
	.sig-img { height: 15mm; }
	.sig-line { border-top: 0.6pt solid #8a99a6; width: 72mm; padding-top: 1.5mm; font-size: 8pt; color: #33475a; line-height: 1.45; }
	.sig-line strong { color: #132a3e; font-size: 9pt; }
	.qr { text-align: center; font-size: 6.5pt; color: #566676; }
	.qr img { width: 24mm; height: 24mm; }
	.date { text-align: right; font-size: 8pt; color: #33475a; line-height: 1.5; }
	.date strong { font-size: 10pt; color: #132a3e; }
</style>
</head>
<body>
<div class="page">
	<div class="frame-outer"></div>
	<div class="frame-inner"></div>
	<div class="band"></div>

	<div class="top">
		<?php if ( $logo ) : ?>
			<img class="logo-img" src="<?php echo esc_attr( $logo ); ?>" alt="">
		<?php else : ?>
			<span class="logo-mark"><?php echo esc_html( $initial ); ?></span><span class="logo-name"><?php echo esc_html( $settings['institution'] ); ?></span>
		<?php endif; ?>
		<div class="number">Certificado Nº<br><strong><?php echo esc_html( $cert->code ); ?></strong></div>
	</div>

	<div class="middle">
		<div class="type"><?php echo $is_ce ? 'Certificado de Educación Continua' : 'Certificado de Finalización'; ?></div>
		<div class="small">Se otorga a</div>
		<div class="name"><?php echo esc_html( $cert->student_name ); ?></div>
		<?php if ( '' !== $prof ) : ?>
			<div class="prof"><?php echo esc_html( $prof ); ?></div>
		<?php endif; ?>
		<div class="small">por haber completado y aprobado el curso</div>
		<div class="course"><?php echo esc_html( $cert->course_title ); ?></div>
		<div class="hours">
			<?php
			$parts = array();
			if ( (float) $cert->hours > 0 ) {
				$parts[] = $hours . ( 1.0 === (float) $cert->hours ? ' hora contacto' : ' horas contacto' );
			}
			if ( $is_ce && (float) $cert->ce_credits > 0 ) {
				$parts[] = $credits . ' CEU';
			}
			echo esc_html( implode( ' · ', $parts ) );
			?>
		</div>
		<?php if ( $is_ce && ( $cert->accreditor || $cert->provider_number ) ) : ?>
			<div class="ce-line">
				Actividad de educación continua aprobada por <?php echo esc_html( $cert->accreditor ); ?>
				<?php if ( $cert->provider_number ) : ?> · Proveedor Nº <?php echo esc_html( $cert->provider_number ); ?><?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<div class="bottom">
		<table><tr>
			<td style="width:40%">
				<?php if ( $signature ) : ?>
					<img class="sig-img" src="<?php echo esc_attr( $signature ); ?>" alt=""><br>
				<?php endif; ?>
				<div class="sig-line">
					<strong><?php echo esc_html( $settings['signer_name'] ?: 'Firma autorizada' ); ?></strong><br>
					<?php echo esc_html( trim( ( $settings['signer_title'] ? $settings['signer_title'] . ' · ' : '' ) . $settings['institution'] ) ); ?>
				</div>
			</td>
			<td class="qr" style="width:20%">
				<img src="<?php echo esc_attr( $qr ); ?>" alt=""><br>Verificar autenticidad
			</td>
			<td class="date" style="width:40%">
				Fecha de finalización<br>
				<strong><?php echo esc_html( $date ); ?></strong><br>
				<?php echo esc_html( $verify ); ?>
			</td>
		</tr></table>
	</div>
</div>
</body>
</html>
