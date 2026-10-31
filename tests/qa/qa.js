const {chromium}=require('playwright');
const path=require('path');const base='file://'+path.resolve(__dirname,'../../site/dist/dentora-demo/index.html');
const routes=['','#aligneri','#ljuskice','#izbjeljivanje','#restaurativna','#oralna-kirurgija','#protetika','#parodontologija','#djecja','#ortodoncija'];
const widths=[320,390,768,1024,1440,1920];
(async()=>{
const b=await chromium.launch();const R={issues:[],checks:{}};
const add=(s)=>R.issues.push(s);
for(const w of widths){for(const r of routes){
  const p=await b.newPage({viewport:{width:w,height:w<=414?844:900}});
  const errs=[];p.on('pageerror',e=>errs.push(e.message));p.on('console',m=>{if(m.type()==='error'&&!/fonts\.g/.test(m.text()))errs.push(m.text())});
  await p.goto(base+r);await p.waitForTimeout(500);
  await p.evaluate(()=>{document.querySelectorAll('.pre,.pre2').forEach(e=>e.classList.remove('pre','pre2'));document.querySelectorAll('img[loading]').forEach(i=>i.loading='eager')});
  await p.waitForTimeout(500);
  const m=await p.evaluate((w)=>{
    const out={};const vis=e=>{const s=getComputedStyle(e);const rc=e.getBoundingClientRect();return s.display!=='none'&&s.visibility!=='hidden'&&rc.width>0&&rc.height>0&&!e.closest('[hidden],.drawer:not(.is-open),#formModal[hidden]')};
    out.overflow=document.documentElement.scrollWidth-innerWidth;
    out.placeholder=/Ovdje dolaze|Čekamo materijale/.test(document.body.innerText);
    out.broken=[...document.images].filter(i=>i.getAttribute('src')&&i.complete&&!i.naturalWidth).map(i=>i.getAttribute('src').slice(0,60));
    out.h1=[...document.querySelectorAll('h1')].filter(vis).length;
    if(w<=414){
      out.small=[...document.querySelectorAll('a,button,[role=tab],[role=slider],input,select,textarea')].filter(vis).filter(e=>!e.closest('p,li,dd,.crumb,footer')).map(e=>{const r=e.getBoundingClientRect();return [((e.innerText||e.getAttribute('aria-label')||e.className)+'').trim().slice(0,30),Math.round(r.width),Math.round(r.height)]}).filter(x=>x[2]<44||x[1]<44);
      out.smallText=[...document.querySelectorAll('#home p,#views p,#home li,#views li')].filter(vis).filter(e=>!e.matches('.eyebrow,.qnum,.crumb')&&e.innerText.trim().length>25).map(e=>parseFloat(getComputedStyle(e).fontSize)).filter(f=>f<16).length;
      const bar=document.querySelector('.mbar');out.mbar=bar&&vis(bar)&&bar.getBoundingClientRect().bottom<=innerHeight+1;
    }
    return out;},w);
  m.errs=errs;
  const key=(r||'home')+'@'+w;R.checks[key]=m;
  if(m.overflow>0)add(`overflow ${m.overflow}px ${key}`);
  if(m.placeholder)add(`placeholder text ${key}`);
  if(m.broken.length)add(`broken img ${key}: ${m.broken}`);
  if(m.h1!==1)add(`h1 count ${m.h1} ${key}`);
  if(errs.length)add(`errors ${key}: ${errs.join(' | ').slice(0,200)}`);
  if(w<=414&&m.small.length)add(`small targets ${key}: ${JSON.stringify(m.small).slice(0,400)}`);
  if(w<=414&&m.smallText)add(`text<16px ${key}: ${m.smallText} elems`);
  if(w<=414&&!m.mbar)add(`mobile booking bar not visible ${key}`);
  await p.close();
}}
// contrast
{const p=await b.newPage({viewport:{width:1440,height:900}});await p.goto(base);await p.waitForTimeout(400);
 R.contrast=await p.evaluate(()=>{const L=c=>{const v=c.match(/[\d.]+/g).slice(0,3).map(x=>{x/=255;return x<=.03928?x/12.92:((x+.055)/1.055)**2.4});return .2126*v[0]+.7152*v[1]+.0722*v[2]};
  const bgOf=e=>{while(e){const c=getComputedStyle(e).backgroundColor;if(c&&!/rgba\(0, 0, 0, 0\)|transparent/.test(c)&&!/,\s*0\)$/.test(c))return c;e=e.parentElement}return 'rgb(255,255,255)'};
  const ratio=(e)=>{const a=L(getComputedStyle(e).color),b2=L(bgOf(e));return +( (Math.max(a,b2)+.05)/(Math.min(a,b2)+.05)).toFixed(2)};
  const pick=s=>{const e=document.querySelector(s);return e?ratio(e):null};
  return {btnGold:pick('#home .btn-gold'),eyebrowCore:pick('#tretmani .eyebrow'),eyebrowReviews:pick('.reviews .eyebrow'),link:pick('#tretmani .link'),btnLine:pick('#home .btn-line'),body:pick('#tretmani .tc-body p:not(.eyebrow)')}});await p.close();}
// interactions
{const p=await b.newPage({viewport:{width:390,height:844}});const errs=[];p.on('pageerror',e=>errs.push(e.message));
 await p.goto(base);await p.waitForTimeout(500);
 const above=await p.evaluate(()=>{const e=document.querySelector('.mbar-book');const r=e.getBoundingClientRect();return r.top>=0&&r.bottom<=innerHeight});
 await p.click('.mbar-book');await p.waitForTimeout(200);
 const open=await p.evaluate(()=>!document.getElementById('formModal').hidden);
 // focus trap: tab 15 times, focus must stay inside modal
 let escaped=false;for(let i=0;i<15;i++){await p.keyboard.press('Tab');if(!(await p.evaluate(()=>document.getElementById('formModal').contains(document.activeElement))))escaped=true}
 await p.keyboard.press('Escape');await p.waitForTimeout(150);const closed=await p.evaluate(()=>document.getElementById('formModal').hidden&&!document.querySelector('header').inert);
 await p.goto(base+'#aligneri');await p.waitForTimeout(500);
 await p.click('.lp-hero [data-open-form]');await p.waitForTimeout(200);
 const pre=await p.evaluate(()=>document.querySelector('#modal-t')?.value);
 await p.keyboard.press('Escape');
 const lp=await p.evaluate(()=>({rv:document.querySelectorAll('#views .lp-rv .rv').length,more:[...document.querySelectorAll('#views .rv-more')].filter(b=>!b.hidden).length,cta:!!document.querySelector('#views .svcta'),others:document.querySelectorAll('#views .svo-card').length,ba:!!document.querySelector('#views .lp-ba')}));
 const more=await p.$('#views .rv-more:not([hidden])');let expands=null;if(more){await more.scrollIntoViewIfNeeded();await more.click();expands=await p.evaluate(()=>!!document.querySelector('#views .rv.open'))}
 await p.goto(base+'#izbjeljivanje');await p.waitForTimeout(500);const izbBa=await p.evaluate(()=>!!document.querySelector('#views .lp-ba .cmp'));
 R.interact={mbarAboveFold:above,modalOpens:open,focusEscaped:escaped,escClosesAndRestores:closed,lpPreselect:pre,lp,rvExpands:expands,izbHasSlider:izbBa,errs};
 await p.close();}
console.log(JSON.stringify({issues:R.issues,contrast:R.contrast,interact:R.interact},null,1));
require('fs').writeFileSync(path.join(__dirname,'qa.json'),JSON.stringify(R,null,1));
await b.close();})();
