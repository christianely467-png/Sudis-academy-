# SUDIS Academy

Plataforma educativa de **SUDIS Institute** (`sudisinstitute.org`) construida sobre
**WordPress + LearnDash**, con venta de cursos, evaluaciones y emisión automática
de **certificados PDF personalizados enviados por correo**, con código de
verificación (modelo tipo Educadis).

> **Regla del proyecto:** el sitio actual `sudisinstitute.org` **no se modifica**
> sin autorización expresa. Todo el desarrollo ocurre primero en un entorno
> *staging* aislado. Ver [Guía paso a paso](docs/03-guia-paso-a-paso.md).

## Documentación

| # | Documento | Contenido |
|---|-----------|-----------|
| 1 | [Arquitectura](docs/01-arquitectura.md) | Componentes, flujos (registro → compra → curso → evaluación → certificado), modelo de datos, entornos |
| 2 | [Herramientas](docs/02-herramientas.md) | Plugins, servicios, licencias y alternativas, qué es obligatorio y qué opcional |
| 3 | [Guía paso a paso](docs/03-guia-paso-a-paso.md) | Fases 0–8 con tareas concretas, criterios de aceptación y puntos donde se pide tu autorización |
| 4 | [Sistema de certificados](docs/04-certificados.md) | Diseño del módulo de certificados PDF + correo + verificación pública |

## Estado actual

- [x] Fase 0 — Arquitectura y selección de herramientas (este repositorio)
- [ ] Fase 1 — Entorno staging
- [ ] Fase 2 — Instalación base (LearnDash, tema, WooCommerce)
- [ ] Fase 3 — Diseño visual con el editor de bloques
- [ ] Fase 4 — Registro y cuentas de estudiantes
- [ ] Fase 5 — Venta de cursos y pagos
- [ ] Fase 6 — Cursos y evaluaciones
- [ ] Fase 7 — Certificados PDF automáticos por correo
- [ ] Fase 8 — Pruebas, seguridad y lanzamiento (requiere tu autorización)

## Estructura prevista del repositorio

```
Sudis-academy-/
├── README.md
├── docs/                       # Arquitectura y guías
└── wp-content/                 # (fases siguientes) solo código propio, versionado
    ├── plugins/sudis-academy-core/   # Plugin propio: certificados, verificación, ajustes
    └── themes/sudis-academy/         # Tema hijo de bloques (si se necesita)
```

En este repositorio solo se versiona **código propio** (plugin y tema hijo). Los
plugins comerciales (LearnDash, etc.), la base de datos y los archivos subidos
**no** se suben a Git.
