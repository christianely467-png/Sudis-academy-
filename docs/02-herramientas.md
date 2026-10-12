# 2. Plugins, servicios y costos (v2)

> **Actualización:** el plugin propio `sudis-academy-core` ya implementa el
> envío del PDF, las horas/CEU, el número único, el QR y la verificación (ver
> [05-instalacion.md](05-instalacion.md)). Con él **no hacen falta** Uncanny
> Toolkit Pro, Uncanny CE Credits ni Certificate Verify & Share. El costo anual
> de software queda en **LearnDash (~$259) + correo transaccional ($0–60)**.
> Las extensiones siguen siendo una alternativa si prefieres soporte de un fabricante.

Criterio: **nativo primero**, extensiones conocidas del ecosistema LearnDash después,
y código propio solo donde no haya alternativa. Se priorizan seguridad,
estabilidad, mantenimiento sencillo y costos recurrentes bajos.

> **Sobre los precios:** son referencias públicas de octubre de 2026 en USD/año.
> Varios no se pudieron confirmar en la web oficial del fabricante y están
> marcados "por confirmar". Verifícalos antes de comprar. Casi todos ofrecen
> garantía de devolución (p. ej. Uncanny Owl indica 14 días): compra cuando el
> staging esté listo para probar dentro de ese plazo.

## 2.1 Imprescindibles (fase 1)

| Plugin / servicio | Para qué | Costo aprox. | Fuente |
|---|---|---|---|
| **LearnDash LMS** | Cursos, quizzes, grupos, cupones, Stripe/PayPal, certificados, perfil | **$259/año** (plan Essentials, 1 sitio) — por confirmar qué plan incluye todo lo necesario | Reportes de 2026; el antiguo plan de $199 se habría retirado |
| LearnDash Certificate Builder | Diseño del certificado con bloques | $0 (add-on oficial) | LearnDash |
| **Uncanny Toolkit Pro** | *Email Course Certificates*: PDF adjunto por correo | Por confirmar | uncannyowl.com |
| **Uncanny Continuing Education Credits** | Horas contacto / créditos CE, certificados CE, historial (*transcript*) | ~$149/año (por confirmar) | Reseña de terceros |
| **LearnDash Certificate Verify & Share** (WooNinjas) | Número único, QR, página `/verificar` | Por confirmar | wooninjas.com |
| FluentSMTP | Envío de correo autenticado | $0 | wordpress.org |
| Brevo (300 correos/día gratis) o Amazon SES | Servicio de correo transaccional | $0 – pocos USD/mes | |
| UpdraftPlus (si el hosting no hace copias suficientes) | Copias a almacenamiento externo | $0 | wordpress.org |
| Wordfence o Solid Security + 2FA | Seguridad | $0 (versión gratis) | wordpress.org |
| `sudis-academy-core` (mini-plugin propio) | Campos profesión y licencia, ajustes SUDIS | $0 (este repositorio) | |

**Paquete alternativo:** el *All Access Pass* de Uncanny Owl incluye Toolkit Pro,
CE Credits, Uncanny Groups, Uncanny Codes y Tin Canny Reporting. Si el precio
del paquete es cercano a la suma de dos plugins sueltos, conviene por incluir
Uncanny Groups para la fase 2.

## 2.2 Comisiones de pago (por transacción, no anuales)

| Pasarela | Comisión estándar EE. UU. aprox. | Nota |
|---|---|---|
| Stripe | ~2.9 % + $0.30 por cargo con tarjeta | Puerto Rico opera como EE. UU. para Stripe; confirma al abrir la cuenta |
| PayPal | ~3.5 % + $0.49 por cargo | Opcional |

Confirma en LearnDash si su Stripe Connect cobra alguna comisión adicional de
plataforma (no se encontró información pública al respecto).

## 2.3 Fase 2 (solo si hace falta)

| Plugin | Cuándo | Costo |
|---|---|---|
| Uncanny Groups for LearnDash | Cuando las organizaciones necesiten **comprar plazas solas** (*self-service*) y asignar estudiantes con códigos | Por confirmar (incluido en All Access). **Requiere WooCommerce** |
| WooCommerce + LearnDash WooCommerce Integration | Solo si se activa Uncanny Groups o se necesita facturación avanzada | $0 |

En la fase 1, las matrículas grupales se gestionan así, sin costo adicional:
la organización paga por factura o enlace de pago de Stripe → SUDIS crea un
**Grupo** en LearnDash con sus cursos → nombra a su responsable como
**Group Leader** → este ve el progreso y certificados de su equipo.

## 2.4 Resumen anual estimado (fase 1)

| Concepto | USD/año |
|---|---|
| LearnDash | ~259 |
| Uncanny Toolkit Pro | por confirmar |
| Uncanny CE Credits | ~149 |
| Certificate Verify & Share | por confirmar |
| SMTP, copias, seguridad | 0 |
| Correo transaccional | 0 – ~60 |
| **Subtotal confirmado** | **~408 + los dos por confirmar** |
| Hosting | ya contratado (a confirmar en Fase 0 si necesita mejora) |

Comparado con desarrollar el plugin propio de certificados: las extensiones
tienen costo anual, pero las mantiene su fabricante y se actualizan con
LearnDash. El plugin propio no tiene licencia, pero requiere mantenimiento
técnico continuo. Por eso queda como plan B.

## 2.5 Riesgos a probar en staging antes de comprar definitivamente

1. Que el QR y el número de WooNinjas aparezcan en el **PDF enviado por correo** por Uncanny Toolkit Pro, no solo en el que se descarga.
2. Que Uncanny CE Credits y Certificate Verify & Share funcionen juntos en el mismo certificado.
3. Formato del número de certificado (p. ej. `SUDIS-2026-000123`): comprobar si Verify & Share permite personalizarlo; si no, ajuste pequeño en el mini-plugin.
4. Tildes y "ñ" en el PDF (nombres en español).
5. Compatibilidad con el tema y los plugins actuales del sitio.
