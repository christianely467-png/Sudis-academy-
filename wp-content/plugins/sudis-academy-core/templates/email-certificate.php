<?php
/**
 * Certificate email (HTML). Override in {theme}/sudis-academy/email-certificate.php.
 *
 * Available: $cert, $settings, $verify_url, $account, $date.
 */
defined( 'ABSPATH' ) || exit;

$primary = esc_attr( $settings['color_primary'] );
$first   = strtok( (string) $cert->student_name, ' ' );
?>
<!DOCTYPE html>
<html lang="es">
<body style="margin:0;padding:0;background:#f3f6f9;font-family:Arial,Helvetica,sans-serif;color:#132a3e;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f6f9;padding:24px 12px;">
	<tr><td align="center">
		<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid #d9e1e8;border-radius:8px;">
			<tr><td style="padding:20px 28px;border-bottom:3px solid <?php echo $primary; // phpcs:ignore ?>;font-weight:bold;font-size:16px;">
				<?php echo esc_html( $settings['institution'] ); ?> · Academia
			</td></tr>
			<tr><td style="padding:28px;font-size:15px;line-height:1.6;">
				<p style="margin:0 0 14px;font-size:20px;font-weight:bold;">¡Felicitaciones, <?php echo esc_html( $first ); ?>!</p>
				<p style="margin:0 0 14px;">Completaste y aprobaste el curso <strong><?php echo esc_html( $cert->course_title ); ?></strong> el <?php echo esc_html( $date ); ?>.</p>
				<p style="margin:0 0 14px;">Adjuntamos tu certificado en PDF. También puedes descargarlo cuando quieras desde tu cuenta.</p>
				<table role="presentation" cellpadding="0" cellspacing="0" style="margin:18px 0;background:#f3f6f9;border-radius:6px;width:100%;">
					<tr><td style="padding:14px 16px;font-size:14px;">
						Número de certificado<br>
						<strong style="font-family:Consolas,Menlo,monospace;font-size:16px;"><?php echo esc_html( $cert->code ); ?></strong>
					</td></tr>
				</table>
				<p style="margin:0 0 22px;">
					<a href="<?php echo esc_url( $verify_url ); ?>" style="display:inline-block;background:<?php echo $primary; // phpcs:ignore ?>;color:#ffffff;text-decoration:none;padding:11px 18px;border-radius:6px;font-weight:bold;">Verificar certificado</a>
					&nbsp;
					<a href="<?php echo esc_url( $account ); ?>" style="color:<?php echo $primary; // phpcs:ignore ?>;font-weight:bold;">Ir a mi cuenta</a>
				</p>
				<p style="margin:0;font-size:13px;color:#566676;">Cualquier persona puede comprobar la autenticidad del certificado escaneando su código QR o introduciendo el número en la página de verificación.</p>
			</td></tr>
			<tr><td style="padding:16px 28px;border-top:1px solid #d9e1e8;font-size:12px;color:#566676;">
				<?php echo esc_html( $settings['institution'] ); ?> · <?php echo esc_html( preg_replace( '#^https?://#', '', home_url() ) ); ?>
			</td></tr>
		</table>
	</td></tr>
</table>
</body>
</html>
