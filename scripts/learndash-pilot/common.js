const { chromium } = require('playwright');
exports.BASE = process.env.LD_URL; // p. ej. https://staging.sudisinstitute.org
exports.open = async () => {
  const b = await chromium.launch({executablePath:'/opt/pw-browsers/chromium', proxy:{server: process.env.HTTPS_PROXY || process.env.https_proxy}});
  const ctx = await b.newContext({viewport:{width:1366,height:900}, storageState: require('fs').existsSync('state.json') ? 'state.json' : undefined});
  const p = await ctx.newPage();
  return {b, ctx, p};
};
exports.login = async (p, ctx) => {
  await p.goto(exports.BASE + '/wp-login.php');
  await p.fill('#user_login', process.env.LD_USER); await p.fill('#user_pass', process.env.LD_PASS);
  await p.click('#wp-submit'); await p.waitForLoadState('networkidle');
  await ctx.storageState({path:'state.json'});
};
exports.api = async (p) => {
  await p.goto(exports.BASE + '/wp-admin/');
  const nonce = await p.evaluate(() => (window.wpApiSettings && wpApiSettings.nonce) || null);
  const req = p.context().request;
  const call = async (method, path, data) => {
    const r = await req.fetch(exports.BASE + '/wp-json' + path, {method, headers:{'X-WP-Nonce': nonce, 'Content-Type':'application/json'}, data: data ? JSON.stringify(data) : undefined});
    let body; try { body = await r.json(); } catch { body = await r.text(); }
    return {status: r.status(), body};
  };
  return {nonce, call};
};
