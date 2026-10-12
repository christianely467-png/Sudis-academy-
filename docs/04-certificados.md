# 4. Sistema de certificados (v2)

> **Actualización:** se construyó el plan B (§4.6) como plugin `sudis-academy-core`
> y está probado. Es la opción recomendada ahora porque elimina tres licencias
> anuales. Instalación y uso: [05-instalacion.md](05-instalacion.md).

Modelo funcional de referencia: Educadis (certificado PDF automático, número
único y verificación por QR), con diseño propio de SUDIS.

## 4.1 Contenido del certificado

| Campo | Origen | Shortcode / fuente (a validar en staging) |
|---|---|---|
| Logotipo SUDIS Institute | Imagen en la plantilla | Certificate Builder |
| Nombre completo | Perfil del usuario | `[usermeta field="first_name"] [usermeta field="last_name"]` |
| Profesión | Campo extra (mini-plugin) | `[usermeta field="sudis_profesion"]` |
| Nº de licencia (si aplica) | Campo extra (mini-plugin) | `[usermeta field="sudis_licencia"]` (se oculta si está vacío) |
| Título del curso | Curso | `[courseinfo show="course_title"]` |
| Horas contacto | Uncanny CE Credits | Shortcode de créditos del plugin |
| Fecha de finalización | LearnDash | `[courseinfo show="completed_on" format="F j, Y"]` |
| Número único | Certificate Verify & Share | Shortcode de ID del plugin |
| Firma autorizada | Imagen PNG transparente | Certificate Builder |
| QR de verificación | Certificate Verify & Share | `[ld_cvss_certificate_qr_code]` |
| Tipo (Finalización / Educación Continua) | Plantilla asignada al curso | — |
| Nº de proveedor y entidad acreditadora (solo CE) | Texto fijo en la plantilla CE | — |

## 4.2 Regla de emisión

"Completar **todos** los módulos **y** aprobar el examen final con **80 %**":

1. Progresión del curso **lineal** (no se puede saltar lecciones).
2. Examen final como **último paso** del curso, con *nota de aprobación = 80 %*.
3. Certificado asignado al **curso** (no al quiz): LearnDash solo marca el curso
   como completado cuando todos los pasos están completos, incluido el examen
   aprobado. Así se cumplen ambas condiciones sin código.
4. Intentos del examen: definir cuántos se permiten (p. ej. 3) y si hay espera entre intentos.

## 4.3 Envío y conservación

- **Correo automático:** Uncanny Toolkit Pro → módulo *Email Course Certificates*:
  al completar el curso envía el PDF adjunto al estudiante (y copia opcional a
  administración). Asunto y cuerpo editables con datos del estudiante y curso.
- **Perfil:** el bloque/shortcode de perfil de LearnDash lista los cursos
  completados con su enlace de certificado para descargar en cualquier momento.
- **Archivo histórico:** Uncanny CE Credits ofrece guardar los PDF en el
  servidor y un historial (*transcript*) de horas/créditos por estudiante, útil
  para auditorías de educación continua.

## 4.4 Verificación pública

- Página `/academia/verificar/` con el formulario de Certificate Verify & Share.
- El QR del certificado abre directamente la verificación de ese certificado.
- Datos mostrados: nombre, curso, horas, fecha, estado. **No** se muestra el nº de licencia ni el correo.

## 4.5 Mini-plugin `sudis-academy-core` (lo único propio en fase 1)

Alcance reducido y de bajo mantenimiento:

- Campos *Profesión* (lista + "Otra") y *Nº de licencia* (opcional) en el
  registro y en el perfil.
- Validación básica y saneamiento de los datos.
- Bloqueo de edición del nombre y la licencia después de emitido un
  certificado (para que el PDF y la verificación coincidan); cambios solo por administración.
- Si hace falta: formato del número de certificado `SUDIS-AAAA-NNNNNN`.

## 4.6 Plan B: plugin de certificados propio

Solo si las extensiones fallan las pruebas de la Fase 7 (p. ej. el QR no aparece
en el PDF enviado por correo, o no se puede cumplir algún requisito de una
entidad acreditadora). Diseño ya previsto:

- Escucha `learndash_course_completed`, genera el PDF con Dompdf a partir de una
  plantilla HTML, número único y QR propios, tabla `wp_sudis_certificates`,
  envío con `wp_mail` y página de verificación propia.
- Ventaja: control total y sin licencias. Desventaja: el mantenimiento recae en SUDIS.

## 4.7 Material que necesito de ti

- Logotipo original (SVG o PNG de alta resolución).
- Firma(s) autorizada(s) en PNG con fondo transparente, nombre y cargo del firmante.
- Lista de profesiones a mostrar en el registro.
- Para cursos acreditados: entidad acreditadora, nº de proveedor y requisitos de la entidad sobre el certificado.
