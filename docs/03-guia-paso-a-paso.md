# 3. Guía paso a paso

Cada fase termina con **criterios de aceptación**. Los pasos marcados con
🔒 **AUTORIZACIÓN** tocan algo fuera del entorno de pruebas (DNS, sitio actual,
producción, pagos reales) y **no se ejecutan sin tu aprobación explícita**.

---

## Fase 0 — Decisiones previas (esta semana)

Responde estas preguntas; condicionan el resto:

1. **Ubicación:** ¿subdominio separado `academia.sudisinstitute.org` (recomendado) o dentro del sitio actual?
2. **Hosting:** ¿dónde está alojado hoy `sudisinstitute.org`? ¿Ofrece staging?
3. **Pagos:** ¿en qué países están tus estudiantes y en qué moneda cobrarás? (define pasarelas)
4. **Certificado:** ¿qué datos lleva? (nombre, documento de identidad, curso, horas, nota, fecha, firmas, logos, código, QR)
5. **Regla de emisión:** ¿al completar todo el curso, al aprobar el examen final, o ambos? ¿Nota mínima?
6. **Primer curso piloto:** cuál será, con su contenido y examen.
7. **Identidad visual:** logo en alta calidad (SVG/PNG), colores, tipografías.

✅ *Aceptación:* respuestas documentadas en este repositorio.

---

## Fase 1 — Entorno staging aislado

1. Contratar/confirmar el hosting y crear una instalación **nueva** de WordPress en un entorno de staging
   (p. ej. `staging-academia.sudisinstitute.org` o la URL temporal que dé el hosting).
2. 🔒 **AUTORIZACIÓN** — Si hace falta un registro DNS para el subdominio de staging, lo creas tú o me autorizas.
3. Proteger el staging: contraseña HTTP o plugin de "modo mantenimiento", y
   *Ajustes → Lectura → Disuadir a los motores de búsqueda*.
4. Activar HTTPS.
5. Crear usuarios: tu administrador (con 2FA) y un estudiante de prueba.

✅ *Aceptación:* staging accesible solo con contraseña, HTTPS activo, sitio actual **intacto**.

---

## Fase 2 — Instalación base

En el staging, en este orden:

1. Ajustes generales: idioma Español, zona horaria, enlaces permanentes "Nombre de la entrada".
2. Tema de bloques elegido + (más adelante) tema hijo `sudis-academy` desde este repositorio.
3. **LearnDash LMS** → activar licencia → asistente de configuración.
4. **WooCommerce** → asistente (moneda, país, sin productos físicos).
5. **LearnDash WooCommerce Integration**.
6. **FluentSMTP** (o WP Mail SMTP) + cuenta en el servicio de correo transaccional.
7. Seguridad + copias de seguridad.

Configuración clave de LearnDash (*LearnDash LMS → Ajustes*):

- Plantilla activa: **LearnDash 3.0 (LD30)**.
- **Focus Mode**: activado.
- Colores: los de SUDIS.
- Login & Registro: usar la página de registro de LearnDash o la de WooCommerce (no ambas).
- Enlaces permanentes: `/cursos/`, `/lecciones/`, `/evaluaciones/`.

✅ *Aceptación:* un correo de prueba desde FluentSMTP llega a la bandeja de entrada (no spam).

---

## Fase 3 — Diseño visual con el Editor del sitio

1. *Apariencia → Editor → Estilos*: colores, tipografías y espaciados de SUDIS
   (luego se fijan en `theme.json` del tema hijo para versionarlos).
2. Plantillas a diseñar:
   - Cabecera (logo, menú: Cursos · Verificar certificado · Mi cuenta) y pie.
   - **Inicio de la academia**: hero, cursos destacados (bloque *LearnDash Course Grid*), beneficios, testimonios, preguntas frecuentes.
   - **Catálogo** `/cursos/` con filtros por categoría.
   - **Ficha del curso**: descripción, temario, instructor, horas, precio, botón comprar.
   - **Panel del estudiante** (`/mi-cuenta/`): bloque *LearnDash Profile* + cursos inscritos + certificados.
   - **Verificar certificado** `/verificar/` (lo provee el plugin propio con un bloque/shortcode).
3. Crear **patrones** reutilizables (tarjeta de curso, llamada a la acción) para mantener coherencia.
4. Revisar en móvil.

✅ *Aceptación:* revisas el diseño en staging y lo apruebas.

---

## Fase 4 — Registro y cuentas

1. Activar registro en WooCommerce: *Ajustes → Cuentas y privacidad* →
   permitir crear cuenta en el checkout y en "Mi cuenta"; generar contraseña por enlace.
2. Campos extra necesarios para el certificado (nombre completo exacto, documento, país).
   Se añaden con el plugin propio (validación y "nombre para certificado" no editable una vez emitido).
3. Anti-spam en registro (Turnstile/reCAPTCHA).
4. Personalizar correos de WooCommerce (*Ajustes → Correos electrónicos*): logo, colores, textos en español.
5. Páginas legales: privacidad, términos, política de reembolso; casillas de aceptación.

✅ *Aceptación:* un usuario nuevo se registra, recibe el correo de bienvenida y accede a "Mi cuenta".

---

## Fase 5 — Venta de cursos

1. Crear el curso piloto en LearnDash con *Modo de acceso: Cerrado*.
2. Crear un **producto WooCommerce** "Curso" (virtual), precio, imagen, y en la pestaña LearnDash
   **asociarlo al curso**.
3. En el curso, *URL del botón* → enlace al producto o "añadir al carrito".
4. Pasarelas en **modo prueba** (Stripe test / PayPal sandbox).
5. Configurar que los pedidos de productos virtuales pasen a **Completado** automáticamente
   (así la inscripción es inmediata).
6. Cupones de prueba (descuento %, 100% para becas).
7. Probar reembolso → el acceso al curso se retira.

✅ *Aceptación:* compra de prueba → inscripción automática → el estudiante ve el curso en su panel.

---

## Fase 6 — Cursos y evaluaciones

1. Estructura del curso: Curso → Lecciones → Temas → Quizzes.
2. Ajustes de progresión: lineal (obliga orden) o libre.
3. **Evaluación final** (*LearnDash LMS → Quizzes*):
   - Banco de preguntas, orden aleatorio, número de intentos, tiempo límite.
   - **Nota mínima para aprobar** (p. ej. 70%).
   - Asociarla al curso como paso final.
4. Configurar **Certificado de LearnDash** para el curso (sirve como respaldo y para que LearnDash marque
   "certificado disponible"; el PDF personalizado lo generará el plugin propio).
5. Metadatos SUDIS por curso: horas, plantilla de certificado, firmantes.

✅ *Aceptación:* el estudiante de prueba aprueba y suspende el examen y ve el resultado correcto.

---

## Fase 7 — Certificados PDF automáticos por correo

Desarrollo del plugin propio `sudis-academy-core` en este repositorio
(diseño completo en [04-certificados.md](04-certificados.md)):

1. Plantilla del certificado (HTML/CSS → PDF) con tu diseño, logos y firmas.
2. Código único + QR + registro en base de datos.
3. Envío por correo con el PDF adjunto al completar el curso / aprobar el examen.
4. Página pública `/verificar/`.
5. Sección "Mis certificados" en el panel del estudiante (descarga y reenvío).
6. Pantalla de administración: listado, búsqueda, reenvío, revocación, reemisión.
7. Instalación del plugin en **staging** (subiendo el `.zip`) y pruebas.

✅ *Aceptación:* al aprobar, en menos de 2 minutos llega el correo con el PDF; el QR abre la verificación y muestra "VÁLIDO".

---

## Fase 8 — Pruebas, seguridad y lanzamiento

**Pruebas en staging**

- [ ] Registro, recuperación de contraseña, inicio de sesión.
- [ ] Compra con tarjeta de prueba, PayPal sandbox, cupón 100%, pago rechazado, reembolso.
- [ ] Curso completo en escritorio y móvil.
- [ ] Certificado: aprobado, suspendido, reintento, nombres con tildes/ñ, nombres largos.
- [ ] Verificación: código válido, inexistente, revocado.
- [ ] Correos: llegan a Gmail, Outlook y Yahoo sin ir a spam (SPF/DKIM/DMARC).
- [ ] Rendimiento: caché excluye carrito, checkout, mi cuenta, cursos.
- [ ] Copia de seguridad y **restauración** probada.

**Lanzamiento**

1. 🔒 **AUTORIZACIÓN** — Crear el subdominio de producción en el DNS.
2. 🔒 **AUTORIZACIÓN** — Publicar staging en producción (herramienta del hosting).
3. 🔒 **AUTORIZACIÓN** — Pasarelas a modo **real** con tus credenciales.
4. Quitar "disuadir a buscadores", quitar contraseña, enviar sitemap.
5. 🔒 **AUTORIZACIÓN** — Añadir el enlace "Academia" en el menú de `sudisinstitute.org`
   (único cambio en el sitio actual).
6. Compra real de bajo importe para validar todo el circuito y reembolsarla.

✅ *Aceptación:* primer estudiante real recibe su certificado.

---

## Después del lanzamiento

- Actualizaciones semanales primero en staging, luego en producción.
- Revisión mensual de copias de seguridad y registros de correo.
- Nuevos cursos: duplicar el curso piloto como plantilla.
- Futuro: membresías/suscripciones, grupos para empresas (LearnDash Groups), certificados por horas acumuladas.
