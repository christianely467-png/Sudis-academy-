# 1. Arquitectura de SUDIS Academy

## 1.1 Objetivos funcionales

| Objetivo | Cómo se resuelve |
|----------|------------------|
| Los estudiantes se registran | Registro de LearnDash / WooCommerce + correo de bienvenida |
| Compran cursos | WooCommerce (pasarelas Stripe / PayPal / locales) → inscripción automática en LearnDash |
| Estudian y completan evaluaciones | Cursos, lecciones, temas y cuestionarios (*quizzes*) de LearnDash |
| Reciben un certificado PDF personalizado por correo | Plugin propio `sudis-academy-core`: genera PDF, asigna código único, lo adjunta por correo |
| Terceros verifican el certificado | Página pública `/verificar` con código o QR (como Educadis) |

## 1.2 Decisión de despliegue: ¿dónde vive la academia?

Para cumplir la regla de **no tocar el sitio actual**, se recomienda:

**Opción recomendada — instalación separada en subdominio**
`academia.sudisinstitute.org` (o `academy.`), con su propia instalación de WordPress.

- ✅ Cero riesgo para el sitio institucional actual (otra base de datos, otros plugins).
- ✅ LearnDash + WooCommerce cargan muchos recursos; aislados no ralentizan el sitio principal.
- ✅ Se puede probar, romper y reinstalar sin afectar nada.
- ➖ El sitio principal solo necesita (cuando autorices) un enlace/menú "Academia".
- ➖ Las cuentas de usuario son independientes del sitio principal (normalmente no es un problema: los estudiantes solo usan la academia).

**Alternativa — misma instalación de `sudisinstitute.org`**
Solo si necesitas que los mismos usuarios del sitio actual entren a los cursos.
Implica instalar plugins en el sitio en producción, por lo que exigiría tu
autorización explícita en cada paso y un staging clonado del sitio actual.

> La guía asume la opción recomendada. Si prefieres la alternativa, los pasos son
> los mismos pero el staging se crea **clonando** el sitio actual.

## 1.3 Diagrama de componentes

```mermaid
flowchart LR
    subgraph Visitante
        B[Navegador del estudiante]
        V[Empleador / tercero que verifica]
    end

    subgraph "academia.sudisinstitute.org (WordPress)"
        T[Tema de bloques + Editor de sitio<br/>diseño SUDIS]
        LD[LearnDash LMS<br/>cursos · lecciones · quizzes · progreso]
        WC[WooCommerce<br/>catálogo · carrito · pedidos · cupones]
        INT[LearnDash–WooCommerce<br/>integración oficial]
        CORE[sudis-academy-core<br/>plugin propio]
        SMTP[Plugin SMTP]
        SEC[Seguridad · Caché · Copias]
    end

    subgraph Servicios externos
        PAY[Pasarela de pago<br/>Stripe / PayPal / Mercado Pago]
        MAIL[Servicio de correo transaccional<br/>Brevo / Postmark / Amazon SES]
        BK[Almacenamiento de copias<br/>remoto]
    end

    B --> T --> LD
    B --> WC --> PAY
    WC --> INT --> LD
    LD -- "curso / quiz completado" --> CORE
    CORE -- "PDF + código" --> SMTP --> MAIL --> B
    V -- "/verificar?codigo=..." --> CORE
    SEC --> BK
```

## 1.4 Flujo principal del estudiante

```mermaid
sequenceDiagram
    actor E as Estudiante
    participant WP as WordPress
    participant WC as WooCommerce
    participant P as Pasarela de pago
    participant LD as LearnDash
    participant C as sudis-academy-core
    participant M as Correo (SMTP)

    E->>WP: Visita catálogo de cursos
    E->>WC: Añade curso al carrito y paga
    WC->>P: Cobro
    P-->>WC: Pago aprobado
    WC->>WP: Crea cuenta (si no existe) + pedido "Completado"
    WC->>LD: Inscribe al estudiante en el curso
    M-->>E: Bienvenida + acceso al curso
    E->>LD: Estudia lecciones
    E->>LD: Rinde la evaluación final
    LD->>LD: Calcula nota (≥ nota mínima)
    LD->>C: Hook learndash_course_completed
    C->>C: Genera código único + QR + PDF
    C->>C: Guarda registro del certificado
    C->>M: Envía correo con PDF adjunto
    M-->>E: "¡Felicitaciones! Tu certificado SUDIS"
```

## 1.5 Flujo de verificación pública

```mermaid
flowchart LR
    Q[QR o código impreso<br/>en el certificado] --> U["/verificar/?codigo=SUDIS-2026-7K3P9Q"]
    U --> C{¿Existe y está vigente?}
    C -- Sí --> OK[Muestra: nombre, curso, horas,<br/>fecha, nota, estado VÁLIDO]
    C -- No / revocado --> KO[Certificado no encontrado<br/>o revocado]
```

## 1.6 Modelo de datos

LearnDash ya guarda cursos, lecciones, quizzes, progreso e intentos. WooCommerce
guarda productos, pedidos y clientes. El plugin propio solo añade **una tabla**
para los certificados emitidos:

**Tabla `wp_sudis_certificates`**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | BIGINT PK | Identificador interno |
| `code` | VARCHAR(32) UNIQUE | Código público, p. ej. `SUDIS-2026-7K3P9Q` |
| `user_id` | BIGINT | Estudiante (WordPress) |
| `course_id` | BIGINT | Curso LearnDash |
| `quiz_id` | BIGINT NULL | Evaluación que lo habilitó (opcional) |
| `student_name` | VARCHAR(190) | Nombre tal como se imprimió (inmutable) |
| `course_title` | VARCHAR(255) | Título tal como se imprimió (inmutable) |
| `hours` | SMALLINT | Carga horaria |
| `score` | DECIMAL(5,2) NULL | Nota obtenida |
| `issued_at` | DATETIME | Fecha de emisión |
| `status` | ENUM('valid','revoked') | Estado |
| `file_path` | VARCHAR(255) | Ruta del PDF en carpeta protegida |
| `emailed_at` | DATETIME NULL | Última vez que se envió por correo |

Se guardan copias del nombre y título para que el certificado verificado siempre
coincida con el PDF impreso, aunque luego cambie el curso o el perfil.

Metadatos adicionales por curso (campos del curso en el editor):

- `_sudis_hours` — carga horaria.
- `_sudis_certificate_template` — plantilla de diseño a usar.
- `_sudis_signatories` — firmas/cargos que aparecen en el certificado.

## 1.7 Entornos

```mermaid
flowchart LR
    L[Local<br/>LocalWP / wp-env] --> G[(GitHub<br/>este repositorio)]
    G --> S[Staging<br/>staging.academia.sudisinstitute.org<br/>protegido con contraseña · noindex]
    S -- "solo con tu autorización" --> P[Producción<br/>academia.sudisinstitute.org]
```

| Entorno | Uso | Datos |
|---------|-----|-------|
| Local | Desarrollo del plugin propio | Ficticios |
| Staging | Pruebas completas, pagos en modo *test*, revisión tuya | Ficticios |
| Producción | Estudiantes reales | Reales |

## 1.8 Requisitos técnicos del hosting

- PHP 8.1 o superior, MySQL 8 / MariaDB 10.6+, HTTPS.
- `memory_limit` ≥ 256 MB, `max_execution_time` ≥ 120 s.
- Extensiones PHP: `gd` o `imagick`, `mbstring`, `dom`, `zip` (necesarias para PDF y QR).
- Cron real del servidor (no depender solo de WP-Cron) para envíos de correo en cola.
- Staging con un clic (la mayoría de hostings gestionados lo ofrecen).
- Copias de seguridad diarias.

## 1.9 Seguridad y privacidad

- Los PDF se guardan **fuera del acceso público directo** (carpeta con `.htaccess`
  `deny`/regla Nginx) y se descargan mediante un enlace con permiso.
- La página de verificación solo muestra datos mínimos (nombre, curso, fecha,
  estado), nunca correo ni documento de identidad.
- Política de privacidad y términos aceptados en el registro y en el *checkout*.
- Autenticación en dos pasos para administradores; roles separados
  (Administrador, Instructor/Group Leader, Estudiante).
