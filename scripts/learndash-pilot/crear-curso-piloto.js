const c = require('./common');
const fs = require('fs');
const ids = fs.existsSync('ids.json') ? JSON.parse(fs.readFileSync('ids.json')) : {};
const save = () => fs.writeFileSync('ids.json', JSON.stringify(ids, null, 1));
const must = (r, what) => { if (r.status >= 300) { console.log('FAIL', what, r.status, JSON.stringify(r.body).slice(0,600)); process.exit(1);} console.log('ok', what, r.body.id ?? ''); return r.body; };
(async () => {
  const {b, p} = await c.open();
  const {call} = await c.api(p);

  if (!ids.cert) ids.cert = must(await call('POST', '/wp/v2/sfwd-certificates', {
    title: 'SUDIS – Certificado de Finalización (piloto)', status: 'publish',
    content: `<p style="text-align:center;font-size:14px;color:#1d5f8a"><strong>SUDIS INSTITUTE</strong></p>
<p style="text-align:center;font-size:12px;letter-spacing:3px;color:#9a7330">CERTIFICADO DE FINALIZACIÓN</p>
<p style="text-align:center;font-size:11px">Se otorga a</p>
<p style="text-align:center;font-size:26px"><strong>[usermeta field="first_name"] [usermeta field="last_name"]</strong></p>
<p style="text-align:center;font-size:11px">por haber completado y aprobado el curso</p>
<p style="text-align:center;font-size:18px"><strong>[courseinfo show="course_title"]</strong></p>
<p style="text-align:center;font-size:12px">6 horas contacto</p>
<p style="text-align:center;font-size:11px">Fecha de finalización: [courseinfo show="completed_on" format="d/m/Y"]</p>`
  }), 'certificado').id; save();

  if (!ids.course) ids.course = must(await call('POST', '/ldlms/v2/sfwd-courses', {
    title: 'SUDIS · Ética profesional y confidencialidad (piloto)', status: 'publish',
    content: '<p>Curso piloto de SUDIS Academy. 3 módulos y examen final. Se requiere 80 % para obtener el certificado.</p>',
    price_type: 'free', progression_disabled: false, certificate: ids.cert
  }), 'curso').id; save();

  const lessons = ['Módulo 1 · Principios éticos', 'Módulo 2 · Confidencialidad y sus límites', 'Módulo 3 · Toma de decisiones éticas'];
  ids.lessons = ids.lessons || [];
  for (let i = ids.lessons.length; i < lessons.length; i++) {
    ids.lessons.push(must(await call('POST', '/ldlms/v2/sfwd-lessons', {title: lessons[i], status: 'publish', menu_order: i+1,
      content: `<p>Contenido de ejemplo del ${lessons[i].split(' · ')[0].toLowerCase()}.</p>`, course: ids.course}), 'lección ' + (i+1)).id); save();
  }

  if (!ids.quiz) ids.quiz = must(await call('POST', '/ldlms/v2/sfwd-quiz', {
    title: 'Examen final · Ética profesional', status: 'publish', course: ids.course,
    passing_percentage: 80, visible_after_specific_date: 0, content: '<p>5 preguntas. Necesitas 80 % o más para aprobar.</p>'
  }), 'examen').id; save();

  const qs = [
    ['¿Cuál es el propósito principal de un código de ética profesional?', ['Proteger a las personas servidas y guiar la conducta profesional', 'Aumentar los ingresos del profesional', 'Sustituir las leyes estatales']],
    ['La confidencialidad puede romperse cuando…', ['Existe riesgo inminente de daño a la persona o a terceros', 'Un familiar lo pide por curiosidad', 'El profesional lo considera interesante']],
    ['El consentimiento informado debe ser…', ['Voluntario, comprensible y documentado', 'Verbal y opcional', 'Firmado solo por el profesional']],
    ['Ante un dilema ético, el primer paso recomendado es…', ['Identificar el problema y los principios en conflicto', 'Actuar de inmediato sin consultar', 'Ignorarlo si no hay queja']],
    ['La documentación clínica debe ser…', ['Precisa, oportuna y protegida', 'Opcional en casos sencillos', 'Compartida en redes sociales']],
  ];
  ids.questions = ids.questions || [];
  for (let i = ids.questions.length; i < qs.length; i++) {
    const [title, ans] = qs[i];
    ids.questions.push(must(await call('POST', '/ldlms/v2/sfwd-question', {title: 'Pregunta ' + (i+1), content: `<p>${title}</p>`, status: 'publish', quiz: ids.quiz, menu_order: i+1,
      question_type: 'single', points_total: 1,
      answers: ans.map((a, k) => ({_answer: a, _html: false, _points: k === 0 ? 1 : 0, _correct: k === 0, _sortString: '', _sortStringHtml: false, _graded: false, _gradingProgression: 'not-graded-none', _gradedType: 'text', _type: 'answer'}))
    }), 'pregunta ' + (i+1)).id); save();
  }

  const steps = await call('GET', `/ldlms/v2/sfwd-courses/${ids.course}/steps?type=h`);
  console.log('steps now', steps.status, JSON.stringify(steps.body).slice(0,500));
  await b.close();
})().catch(e => { console.error(e.message); process.exit(1); });
