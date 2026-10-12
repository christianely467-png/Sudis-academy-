const { chromium } = require('playwright');
const c = require('./common');
const fs = require('fs');
const ids = JSON.parse(fs.readFileSync('ids.json'));
const [user, wrong, tag] = [process.argv[2], parseInt(process.argv[3]), process.argv[4]];
const go = async (p, url) => { for (let t = 0; t < 3; t++) { try { await p.goto(url, {waitUntil:'load'}); await p.waitForLoadState('networkidle'); return; } catch (e) { await p.waitForTimeout(2000); } } throw new Error('no se pudo abrir ' + url); };
const CORRECT = ['Proteger a las personas', 'Existe riesgo inminente', 'Voluntario, comprensible', 'Identificar el problema', 'Precisa, oportuna'];
(async () => {
  const b = await chromium.launch({executablePath:'/opt/pw-browsers/chromium', proxy:{server: process.env.HTTPS_PROXY}});
  const ctx = await b.newContext({viewport:{width:1366,height:900}});
  const p = await ctx.newPage();
  await p.goto(c.BASE + '/wp-login.php');
  await p.fill('#user_login', user); await p.fill('#user_pass', 'Demo-SUDIS-2026'); await p.click('#wp-submit'); await p.waitForLoadState('networkidle');
  // Lessons
  for (const lid of ids.lessons) {
    await go(p, c.BASE + '/?p=' + lid);
    const btn = await p.$('form.sfwd-mark-complete input[type=submit], .learndash_mark_complete_button');
    if (btn) { await Promise.all([p.waitForNavigation({waitUntil:'load'}).catch(()=>{}), btn.click()]); await p.waitForTimeout(1500); console.log('lesson', lid, 'marked complete'); }
    else console.log('lesson', lid, 'no button (already complete?)');
  }
  // Quiz
  await go(p, c.BASE + '/?p=' + ids.quiz);
  await p.screenshot({path: `quiz-start-${tag}.png`});
  await p.click('input[name="startQuiz"]');
  for (let i = 0; i < 5; i++) {
    const item = p.locator('.wpProQuiz_listItem:visible').first();
    await item.waitFor();
    const text = await item.innerText();
    const idx = CORRECT.findIndex(t => text.includes(t.split(' ')[0]) && text.includes(t));
    const labels = item.locator('.wpProQuiz_questionListItem label');
    const n = await labels.count();
    let target = 0;
    for (let k = 0; k < n; k++) { const lt = await labels.nth(k).innerText(); if (CORRECT.some(t => lt.includes(t))) target = k; }
    if (i < wrong) target = (target + 1) % n;
    await labels.nth(target).click();
    const next = item.locator('input[name="next"]:visible, input[name="checkSingle"]:visible');
    await next.first().click();
    await p.waitForTimeout(800);
  }
  await p.waitForSelector('.wpProQuiz_results:visible', {timeout: 30000});
  await p.waitForTimeout(2500);
  const res = (await p.innerText('.wpProQuiz_results')).replace(/\s+/g,' ').slice(0,400);
  console.log('RESULT', res);
  await p.screenshot({path: `quiz-result-${tag}.png`, fullPage: true});
  // Course page
  await go(p, c.BASE + '/?p=' + ids.course);
  await p.screenshot({path: `course-${tag}.png`, fullPage: true});
  const certLink = await p.$$eval('a', as => as.map(a => a.href).filter(h => /certificate|cert-nonce|sfwd-certificates/i.test(h)));
  console.log('certificate links', certLink.slice(0,3));
  if (certLink.length) {
    const r = await ctx.request.get(certLink[0]);
    console.log('cert download', r.status(), r.headers()['content-type']);
    if ((r.headers()['content-type']||'').includes('pdf')) fs.writeFileSync(`cert-${tag}.pdf`, await r.body());
  }
  await b.close();
})().catch(e => { console.error('ERR', e.message); process.exit(1); });
