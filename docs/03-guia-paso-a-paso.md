# 3. Guía paso a paso (v2)

Los pasos marcados 🔒 **AUTORIZACIÓN** afectan al sitio publicado, al DNS o a
pagos reales, y **no se ejecutan sin tu aprobación explícita**.

## Fase 0 — Verificación del hosting ⏳ en curso

Lista completa en [00-verificacion-hosting.md](00-verificacion-hosting.md):
proveedor, plan, staging, copias, accesos, inventario de plugins y versión de PHP.

✅ *Aceptación:* hosting identificado, copia completa descargada y guardada fuera del servidor.

## Fase 1 — Staging (clon del sitio actual)

1. 🔒 **AUTORIZACIÓN** — Crear el staging con la herramienta del hosting (clona sin modificar producción).
   Si el hosting no tiene staging: restaurar la copia en un entorno local.
2. Proteger el staging: contraseña, *noindex*, desactivar correos salientes a clientes reales.
3. Comprobar que el clon funciona igual que producción.

✅ *Aceptación:* staging idéntico al sitio, accesible solo con contraseña; producción sin cambios.

## Fase 2 — Instalación base en staging

1. Comprar LearnDash (empieza a correr la garantía de devolución).
2. Instalar LearnDash + Certificate Builder; asistente inicial.
3. Ajustes: plantilla LD30, Focus Mode, colores SUDIS, *slugs* bajo `/academia/`.
4. FluentSMTP + Brevo/SES; configurar SPF, DKIM y DMARC (🔒 el DNS de producción se cambia solo con autorización; en staging se usa un remitente de pruebas).
5. Mini-plugin `sudis-academy-core` (campos profesión y licencia).

✅ *Aceptación:* sitio institucional en staging sigue funcionando igual; correo de prueba llega a la bandeja de entrada.

## Fase 3 — Diseño de la academia

Con la identidad visual actual del sitio (tema, colores, tipografías):
portada `/academia/`, catálogo, ficha de curso, panel del estudiante,
organizaciones y verificar. Propuesta inicial de diseño publicada para tu revisión.

✅ *Aceptación:* apruebas el diseño en staging.

## Fase 4 — Registro

Registro de LearnDash con nombre, apellidos, correo, profesión y licencia;
anti-spam; páginas legales (privacidad, términos, reembolsos) en español e inglés si aplica.

## Fase 5 — Pagos (modo prueba)

1. Stripe Connect en **modo test**; PayPal sandbox opcional; moneda USD.
2. Curso gratuito (acceso *Gratis*) y curso de pago (*Comprar ahora*).
3. Cupones: % de descuento, monto fijo, 100 % (becas).
4. Grupo de prueba con un *Group Leader*.

✅ *Aceptación:* compra con tarjeta de prueba → inscripción inmediata; cupón aplicado; grupo funcionando.

## Fase 6 — Curso piloto y examen

Curso con progresión lineal, examen final al 80 %, intentos definidos, horas contacto en CE Credits.

## Fase 7 — Certificados

1. Comprar Uncanny Toolkit Pro, CE Credits y Certificate Verify & Share.
2. Diseñar las dos plantillas (Finalización y Educación Continua).
3. Activar *Email Course Certificates*.
4. Pruebas de la lista de riesgos de [02-herramientas.md §2.5](02-herramientas.md).
5. Si algo falla y no tiene arreglo → plan B (plugin propio).

✅ *Aceptación:* al aprobar con ≥ 80 % llega el correo con el PDF; el QR verifica el certificado; se descarga desde el perfil.

## Fase 8 — Lanzamiento

1. Pruebas completas (registro, pagos, cupones, grupos, certificados, móvil, correo en Gmail/Outlook).
2. Copia completa de producción justo antes.
3. 🔒 **AUTORIZACIÓN** — Pasar los cambios de staging a producción (con el sitio en una ventana de bajo tráfico).
4. 🔒 **AUTORIZACIÓN** — Stripe y PayPal a modo real.
5. 🔒 **AUTORIZACIÓN** — Añadir "Academia" al menú principal.
6. Compra real de bajo importe y reembolso para validar.
