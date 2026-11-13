const {JSDOM}=require('jsdom'),fs=require('fs'),assert=require('node:assert/strict');
(async()=>{
 const dom=new JSDOM('<!doctype html><div class="fcoi-app"></div>',{runScripts:'outside-only',url:'https://fcoi.test/'}),w=dom.window;
 let cats=[],revision=0,conflict=false,docs=[],reads=0;
 w.FCOI_CONFIG={api:'https://fcoi.test/api/',nonce:'test'};w.TextEncoder=TextEncoder;w.confirm=()=>true;w.fetch=async(url,opts)=>{
  let data,status=200;const path=new URL(url).pathname,body=opts.body?JSON.parse(opts.body):null;
  if(path==='/api/pedigree'&&opts.method==='GET')data={cats,revision};
  else if(path==='/api/pedigree'){if(conflict){status=409;data={message:'Changed in another tab'};}else{cats=body.cats;revision++;data={cats,revision};}}
  else if(path==='/api/documents'&&opts.method==='GET')data={documents:docs,reader_enabled:true};
  else if(path==='/api/documents'){docs.push({id:1,cat_id:body.cat_id,name:'test.png'});data={saved:true};}
  else if(path==='/api/documents/1/read'){reads++;data={cat_id:cats[0].id,cat_name:'Verified Sire',registration_number:'1234-567890',sex:'M',sire_name:'Grand Sire',sire_registration_number:'1234-111111',dam_name:'Grand Dam',dam_registration_number:'1234-222222',notes:'Synthetic certificate'};}
  else if(path==='/api/calculate')data={percent:body.id===cats[0].id?0:25,revision,max_generations:2,coverage:[{generation:1,known:2,possible:2}],method:'Mock result'};
  else throw Error(path);
  assert.equal(opts.headers['X-WP-Nonce'],'test');return {ok:status===200,json:async()=>structuredClone(data)};
 };
 w.eval(fs.readFileSync('assets/app.js','utf8'));
 const d=w.document,$=s=>d.querySelector(s),click=s=>$(s).click(),tick=()=>new Promise(r=>setTimeout(r,20));await tick();
 const form=$('.fcoi-editor');
 function add(name,sex,reg=''){form.elements.name.value=name;form.elements.sex.value=sex;form.elements.registration.value=reg;form.dispatchEvent(new w.Event('submit',{bubbles:true,cancelable:true}));}
 add('Sire','M','1234-567890');add('Dam','F');form.elements.sire.selectedIndex=1;form.elements.dam.selectedIndex=1;add('Kitten','U');assert.equal($('.fcoi-count').textContent,'(3/500)');
 $('.fcoi-calc').dispatchEvent(new w.Event('submit',{cancelable:true}));assert.match($('.fcoi-status').textContent,/Save the pedigree/);
 click('[data-action=save]');await tick();assert.equal(cats.length,3);assert.equal(revision,1);
 const calc=$('.fcoi-calc');calc.elements.id.value=cats[0].id;calc.dispatchEvent(new w.Event('submit',{cancelable:true}));await tick();assert.equal($('.fcoi-value').textContent,'0%');
 calc.elements.id.value=cats[2].id;calc.dispatchEvent(new w.Event('submit',{cancelable:true}));await tick();assert.equal($('.fcoi-value').textContent,'25%');
 click('tbody tr [data-action=delete]');assert.match($('.fcoi-status').textContent,/recorded parent/);
 // Simulate upload via the browser FileReader path.
 const file=new w.File(['synthetic'],'test.png',{type:'image/png'});Object.defineProperty($('.fcoi-doc-file'),'files',{value:[file],configurable:true});click('[data-action=upload]');await tick();await tick();assert.equal(docs.length,1);
 click('[data-action=read-doc]');await tick();assert.equal(reads,1);assert.equal($('.fcoi-count').textContent,'(3/500)');
 $('.fcoi-extraction form').dispatchEvent(new w.Event('submit',{cancelable:true}));assert.equal($('.fcoi-count').textContent,'(5/500)');assert.match($('.fcoi-status').textContent,/Reviewed fields applied locally/);
 conflict=true;click('[data-action=save]');await tick();assert.match($('.fcoi-status').textContent,/another tab/);assert.equal($('.fcoi-count').textContent,'(5/500)');assert.equal(cats.length,3);
 // UI safely renders literal untrusted names.
 click('[data-action=cancel]');add('<img src=x onerror=alert(1)>','U');assert.equal(d.querySelectorAll('tbody img').length,0);assert.match($('tbody').textContent,/<img src=x/);
 console.log('PASS DOM UI: add/link, save requirement, zero/nonzero display, deletion guard, upload, extraction review/apply, conflict preservation, XSS-safe names.');dom.window.close();
})().catch(e=>{console.error(e);process.exitCode=1;});
