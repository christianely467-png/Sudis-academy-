# SUDIS Academy

Plataforma educativa de **SUDIS Institute** (`sudisinstitute.org`) construida sobre
**WordPress + LearnDash**, integrada en `sudisinstitute.org/academia`, con venta de cursos, evaluaciones y emisión automática
de **certificados PDF personalizados enviados por correo**, con código de
verificación (modelo tipo Educadis).

> **Regla del proyecto:** el sitio actual `sudisinstitute.org` **no se modifica**
> sin autorización expresa. Todo el desarrollo ocurre primero en un entorno
> *staging* aislado. Ver [Guía paso a paso](docs/03-guia-paso-a-paso.md).

## Documentación

| # | Documento | Contenido |
|---|-----------|-----------|
| 0 | [Verificación del hosting](docs/00-verificacion-hosting.md) | Lista de solo lectura para identificar hosting, staging, copias y permisos |
| 1 | [Arquitectura](docs/01-arquitectura.md) | Componentes, flujos (registro → compra → curso → evaluación → certificado), modelo de datos, entornos |
| 2 | [Herramientas](docs/02-herramientas.md) | Plugins, servicios, licencias y alternativas, qué es obligatorio y qué opcional |
| 3 | [Guía paso a paso](docs/03-guia-paso-a-paso.md) | Fases 0–8 con tareas concretas, criterios de aceptación y puntos donde se pide tu autorización |
| 4 | [Sistema de certificados](docs/04-certificados.md) | Certificados con LearnDash + extensiones (PDF, correo, QR, verificación) y plan B propio |
| 5 | [Instalación](docs/05-instalacion.md) | Qué está construido, cómo probarlo en local y cómo instalarlo en el staging |

## Estado actual

- [x] Arquitectura v2 y selección de herramientas
- [x] Plugin `sudis-academy-core`: certificados PDF + correo + QR + verificación, profesión y licencia (probado en staging local)
- [x] Páginas de la academia (`/academia/`, mi cuenta, verificar, organizaciones)
- [ ] Fase 0 — Verificación del hosting (en curso)
- [ ] Fase 1 — Staging (clon del sitio actual)
- [ ] Fase 2 — Instalación base en staging (LearnDash, SMTP, mini-plugin)
- [ ] Fase 3 — Diseño de la academia con la identidad de SUDIS
- [ ] Fase 4 — Registro (profesión y licencia)
- [ ] Fase 5 — Pagos Stripe/PayPal en modo prueba, cupones, grupos
- [ ] Fase 6 — Cursos y evaluaciones
- [ ] Fase 7 — Certificados PDF automáticos por correo
- [ ] Fase 8 — Pruebas, seguridad y lanzamiento (requiere tu autorización)

## Estructura prevista del repositorio

```
Sudis-academy-/
├── README.md
├── docs/                       # Arquitectura y guías
├── scripts/
│   ├── staging-local.sh        # WordPress local de pruebas (SQLite) en .staging/
│   ├── setup-academy.php       # Crea las páginas /academia/
│   ├── smoke-test.php          # Prueba de punta a punta de los certificados
│   └── build-plugin.sh         # Genera dist/sudis-academy-core.zip
├── vendor-plugins/             # Aquí van los .zip comerciales (no se suben a Git)
└── wp-content/plugins/sudis-academy-core/   # Plugin propio
```

En este repositorio solo se versiona **código propio** (plugin y tema hijo). Los
plugins comerciales (LearnDash, etc.), la base de datos y los archivos subidos
**no** se suben a Git.
