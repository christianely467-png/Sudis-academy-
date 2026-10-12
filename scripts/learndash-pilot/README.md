# Curso piloto en LearnDash (vía API REST)

Crea en un sitio con LearnDash el curso piloto de SUDIS (3 módulos + examen
final de 5 preguntas con aprobación al 80 % + certificado nativo), dos
estudiantes de prueba, y recorre el curso con un navegador real.

Probado el 12/10/2026 en la demo de LearnDash 5.2.0 (`*.learndash.today`):

| Estudiante | Examen | Curso | Certificado |
|---|---|---|---|
| María | 5/5 (100 %) | Completado | Sí (PDF descargado) |
| Pedro | 3/5 (60 %) | En progreso (3 de 4 pasos) | No |

LearnDash guarda cada intento con `quiz`, `course`, `percentage` y `pass`, que
son los campos que lee `sudis-academy-core` para aplicar la nota mínima.

```bash
cd scripts/learndash-pilot
export LD_URL=https://staging.ejemplo.org LD_USER=admin LD_PASS='…'   # solo staging
NODE_PATH=$(npm root -g) node crear-curso-piloto.js     # guarda los IDs en ids.json
NODE_PATH=$(npm root -g) node crear-estudiantes.js
NODE_PATH=$(npm root -g) node recorrer-curso.js maria.demo 0 maria   # 0 respuestas incorrectas
NODE_PATH=$(npm root -g) node recorrer-curso.js pedro.demo 2 pedro   # 2 incorrectas → 60 %
```

Los scripts guardan la sesión en `state.json` e IDs en `ids.json` (ignorados por Git).
