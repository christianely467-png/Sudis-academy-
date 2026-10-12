# 2. Herramientas necesarias

Leyenda: **🟥 Obligatorio** · **🟨 Recomendado** · **⬜ Opcional**

> Los precios cambian; verifica siempre el precio vigente en la web oficial antes
> de comprar. Las licencias anuales suelen ser por número de sitios: **la
> licencia de 1 sitio normalmente permite también usarla en staging/local**
> (revísalo en los términos de cada proveedor).

## 2.1 Base

| Herramienta | Prioridad | Para qué | Notas |
|-------------|-----------|----------|-------|
| Hosting WordPress gestionado | 🟥 | Servidor, SSL, staging, copias | Que ofrezca staging, PHP 8.1+, SSH/SFTP y cron real |
| WordPress (última versión) | 🟥 | CMS | Instalación nueva en el subdominio de la academia |
| Dominio / subdominio | 🟥 | `academia.sudisinstitute.org` | Un registro DNS en tu proveedor actual (requiere tu autorización) |

## 2.2 LMS y diseño

| Herramienta | Prioridad | Para qué | Notas |
|-------------|-----------|----------|-------|
| **LearnDash LMS** | 🟥 | Cursos, lecciones, quizzes, progreso, certificados básicos, grupos | Licencia anual de pago. Incluye bloques para el editor y reportes (ProPanel) |
| **Tema de bloques** (p. ej. *Twenty Twenty-Five*, Kadence, Astra o el tema oficial de LearnDash) | 🟥 | Diseño con el **Editor del sitio** (Build with WordPress) | Un tema de bloques permite diseñar plantillas, cabecera, pie y páginas con bloques, sin código |
| Tema hijo `sudis-academy` | 🟨 | Guardar colores, tipografías y plantillas SUDIS en código versionado | Lo creamos nosotros en este repositorio (`theme.json`) |
| LearnDash "Focus Mode" | 🟨 | Vista de estudio sin distracciones | Incluido en LearnDash, se activa en ajustes |

> **Sobre "Build with WordPress":** lo interpreto como diseñar el sitio con las
> herramientas nativas de WordPress (Editor del sitio / editor de bloques y sus
> patrones) en lugar de un constructor externo. Si te refieres a un producto o
> servicio concreto con ese nombre, indícamelo y ajusto el plan.

## 2.3 Ventas y pagos

| Herramienta | Prioridad | Para qué | Notas |
|-------------|-----------|----------|-------|
| **WooCommerce** | 🟥* | Carrito, pedidos, cupones, facturación, impuestos | Gratis. *Recomendado frente al pago nativo de LearnDash por la flexibilidad de pasarelas locales y cupones |
| **LearnDash WooCommerce Integration** | 🟥* | Inscribe automáticamente al estudiante al pagar | Add-on oficial gratuito para licencias LearnDash |
| Pasarela Stripe (WooPayments o *Stripe for WooCommerce*) | 🟨 | Tarjetas internacionales | Modo *test* para staging |
| PayPal Payments | 🟨 | PayPal | |
| Pasarela local (Mercado Pago, etc.) | ⬜ | Pagos locales según tu país | Depende del país de tus estudiantes |
| Facturación electrónica | ⬜ | Facturas fiscales | Depende de la normativa de tu país |

**Alternativa simple:** usar solo los pagos nativos de LearnDash (Stripe/PayPal)
sin WooCommerce. Menos piezas, pero menos opciones de pasarela, cupones e
informes. Recomendado solo si vas a vender pocos cursos y con tarjeta/PayPal.

## 2.4 Certificados y correo

| Herramienta | Prioridad | Para qué | Notas |
|-------------|-----------|----------|-------|
| Certificados de LearnDash | 🟥 | Base para emitir certificados por curso/quiz | Nativo, pero **no adjunta el PDF al correo ni tiene verificación pública por código** |
| **Plugin propio `sudis-academy-core`** | 🟥 | PDF personalizado, código único, QR, envío por correo con adjunto, página `/verificar` | Lo desarrollamos en este repositorio. Ver [Sistema de certificados](04-certificados.md) |
| Dompdf (librería PHP, gratis) | 🟥 | Generar el PDF a partir de una plantilla HTML/CSS | Se empaqueta dentro del plugin propio |
| Librería QR (p. ej. `endroid/qr-code` o `chillerlan/php-qrcode`, gratis) | 🟥 | QR que apunta a la verificación | Se empaqueta dentro del plugin propio |
| **Plugin SMTP** (FluentSMTP o WP Mail SMTP) | 🟥 | Que los correos lleguen y no caigan en spam | FluentSMTP es gratuito |
| Servicio transaccional (Brevo, Postmark, Amazon SES, Mailgun) | 🟥 | Envío fiable de correos | Configurar SPF, DKIM y DMARC del dominio |
| LearnDash Notifications | ⬜ | Recordatorios (inactividad, curso por vencer) | Add-on oficial gratuito |
| Uncanny Toolkit Pro | ⬜ | Alternativa *sin código* para enviar certificados por correo | Útil como solución rápida mientras se desarrolla el plugin propio, pero menos personalizable (sin verificación tipo Educadis) |

## 2.5 Registro y cuentas

| Herramienta | Prioridad | Para qué | Notas |
|-------------|-----------|----------|-------|
| Registro de LearnDash / WooCommerce | 🟥 | Alta de estudiantes | Nativo |
| Campos extra (documento, país, profesión) | 🟨 | Datos para el certificado | Con el plugin propio o con un formulario (Fluent Forms / Gravity Forms + add-on de registro) |
| Anti-spam (Cloudflare Turnstile o reCAPTCHA) | 🟨 | Evitar registros falsos | |

## 2.6 Operación, seguridad y rendimiento

| Herramienta | Prioridad | Para qué |
|-------------|-----------|----------|
| Copias de seguridad (UpdraftPlus / la del hosting) con destino remoto | 🟥 | Recuperación ante fallos |
| Seguridad (Wordfence / Solid Security) + 2FA | 🟥 | Proteger cuentas y pagos |
| Caché (LiteSpeed Cache / WP Rocket / la del hosting) | 🟨 | Velocidad. **Excluir** carrito, checkout, mi cuenta y páginas de curso |
| Cloudflare | 🟨 | CDN, DNS, protección |
| SEO (Yoast / Rank Math) | 🟨 | Posicionamiento del catálogo |
| Analítica (Site Kit / Matomo) | ⬜ | Métricas de conversión |
| Cumplimiento de cookies (Complianz / CookieYes) | 🟨 | Legal |

## 2.7 Desarrollo

| Herramienta | Para qué |
|-------------|----------|
| GitHub (este repositorio) | Versionar el plugin y tema propios |
| LocalWP o `@wordpress/env` (Docker) | Entorno local |
| Composer | Dependencias PHP del plugin (Dompdf, QR) |
| WP-CLI | Automatizar instalación y pruebas |
| PHPCS + WordPress Coding Standards | Calidad de código |
| GitHub Actions | Lint automático y empaquetado `.zip` del plugin |

## 2.8 Resumen de compras

| Concepto | Tipo de coste |
|----------|---------------|
| Hosting con staging | Mensual/anual |
| LearnDash LMS | Licencia anual |
| Servicio de correo transaccional | Gratis hasta cierto volumen, luego por uso |
| Pasarela de pago | Comisión por transacción |
| Resto de plugins recomendados | Versiones gratuitas suficientes para empezar |
| Plugin propio de certificados | Desarrollo (este repositorio), sin licencia |
