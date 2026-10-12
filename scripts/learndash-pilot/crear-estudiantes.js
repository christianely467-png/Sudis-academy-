const c = require('./common');
const fs = require('fs');
const ids = JSON.parse(fs.readFileSync('ids.json'));
(async () => {
  const {b, p} = await c.open();
  const {call} = await c.api(p);
  const mk = async (username, first, last) => {
    let u = await call('GET', `/wp/v2/users?search=${username}&context=edit`);
    let user = (u.body||[]).find(x => x.username === username);
    if (!user) {
      const r = await call('POST', '/wp/v2/users', {username, email: `${username}@ejemplo.test`, password: 'Demo-SUDIS-2026', first_name: first, last_name: last, name: `${first} ${last}`, roles: ['subscriber']});
      if (r.status >= 300) { console.log('FAIL user', JSON.stringify(r.body).slice(0,300)); process.exit(1); }
      user = r.body;
    }
    const e = await call('POST', `/ldlms/v2/users/${user.id}/courses`, {course_ids: [ids.course]});
    console.log(username, user.id, 'enroll', e.status);
    return user.id;
  };
  ids.maria = await mk('maria.demo', 'María', 'Rodríguez Ortiz');
  ids.pedro = await mk('pedro.demo', 'Pedro', 'Colón Díaz');
  fs.writeFileSync('ids.json', JSON.stringify(ids, null, 1));
  const course = await call('GET', `/ldlms/v2/sfwd-courses/${ids.course}`);
  console.log('course link', course.body.link);
  await b.close();
})().catch(e => { console.error(e.message); process.exit(1); });
