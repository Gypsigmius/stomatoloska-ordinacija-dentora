const {chromium}=require('playwright');const B=process.env.BASE||'http://127.0.0.1:8099/'; // start: php -S 127.0.0.1:8099 tests/plugin/router.php
(async()=>{const b=await chromium.launch();const out={};
const p=await b.newPage({viewport:{width:390,height:844}});p.on('pageerror',e=>console.log('ERR',e.message));
// 1. popup from mobile bar, empty submit -> client validation
await p.goto(B);await p.waitForTimeout(500);await p.click('.mbar-book');await p.waitForTimeout(200);
await p.click('#modalForm button[type=submit]');await p.waitForTimeout(200);
out.emptyMsg=await p.textContent('#modalForm .form-msg');out.badFields=await p.$$eval('#modalForm label.bad',l=>l.length);
// 2. fill and send (wait > 3s for timing check)
await p.fill('#modal-ime','Ana Horvat');await p.fill('#modal-tel','091 123 4567');await p.fill('#modal-mail','ana@example.com');
await p.selectOption('#modal-t','ljuskice');await p.fill('#modal-msg','Zanima me cijena ljuskica.');
await p.waitForTimeout(3200);await p.click('#modalForm button[type=submit]');await p.waitForSelector('#modalForm p.ok',{timeout:5000});
out.success=await p.textContent('#modalForm p.ok');await p.screenshot({path:require('path').join(__dirname,'e2e_ok.png')});
const mails=JSON.parse(require('fs').readFileSync(require('path').join(__dirname,'last_mails.json'),'utf8'));out.mailTo=mails.map(m=>m.to);out.subject=mails[0].subject;
await p.keyboard.press('Escape');
// 3. treatment page form (inline) with server-side validation error (bad phone passes browser check)
await p.goto(B+'#aligneri');await p.waitForTimeout(600);
await p.fill('#aligneri-ime','Ivo Ivić');await p.fill('#aligneri-tel','12');await p.fill('#aligneri-mail','ivo@example.com');
await p.waitForTimeout(3200);const btn=await p.$('#aligneri-upit button[type=submit]');await btn.scrollIntoViewIfNeeded();await btn.click();
await p.waitForSelector('#aligneri-upit .form-msg.is-err',{timeout:5000});out.serverErr=await p.textContent('#aligneri-upit .form-msg');out.telBad=await p.$eval('#aligneri-tel',i=>i.closest('label').classList.contains('bad'));
await p.screenshot({path:require('path').join(__dirname,'e2e_err.png')});
// 4. quiz form
await p.goto(B);await p.waitForTimeout(400);for(let i=0;i<3;i++){await p.click('.qopt >> nth=0');await p.waitForTimeout(100)}
await p.fill('#kviz-ime','Maja M');await p.fill('#kviz-tel','098 765 4321');await p.fill('#kviz-mail','maja@example.com');await p.waitForTimeout(3200);
await p.click('#quiz button[type=submit]');await p.waitForSelector('#quiz p.ok',{timeout:5000});
const m2=JSON.parse(require('fs').readFileSync(require('path').join(__dirname,'last_mails.json'),'utf8'));out.quizSubject=m2[0].subject;out.quizSource=m2[0].body.includes('kviz');
console.log(JSON.stringify(out,null,1));await b.close()})();
