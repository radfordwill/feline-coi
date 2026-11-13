const {chromium}=require('playwright'),fs=require('fs');
(async()=>{
 const browser=await chromium.launch({headless:true,executablePath:process.env.CHROMIUM_PATH||'/usr/bin/chromium',args:['--no-sandbox']}),page=await browser.newPage({viewport:{width:1200,height:1000}}),errors=[];
 page.on('pageerror',e=>errors.push(e.message));let cats=[],revision=0,conflict=false,docs=[];
 await page.route('http://fcoi.test/**',async route=>{
  const req=route.request(),path=new URL(req.url()).pathname,method=req.method();let status=200,body;
  if(path==='/')return route.fulfill({contentType:'text/html',body:'<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><div class="fcoi-app"></div>'});
  if(path==='/api/pedigree'&&method==='GET')body={cats,revision};
  else if(path==='/api/pedigree'){if(conflict){status=409;body={message:'Your pedigree changed in another tab.'};}else{cats=req.postDataJSON().cats;revision++;body={cats,revision};}}
  else if(path==='/api/documents'&&method==='GET')body={documents:docs,reader_enabled:true};
  else if(path==='/api/documents'){docs.push({id:1,name:'CFA-test.png',cat_id:req.postDataJSON().cat_id});body={saved:true};}
  else if(path==='/api/documents/1/read')body={cat_id:cats[0].id,cat_name:'Verified Sire',registration_number:'1234-567890',sex:'M',sire_name:'Grand Sire',sire_registration_number:'1234-111111',dam_name:'Grand Dam',dam_registration_number:'1234-222222',notes:'Synthetic certificate'};
  else if(path==='/api/calculate')body={percent:25,max_generations:2,revision,coverage:[{generation:1,known:2,possible:2},{generation:2,known:4,possible:4}],method:'Test response'};
  else {status=404;body={message:'Not found'};}
  return route.fulfill({status,contentType:'application/json',body:JSON.stringify(body)});
 });
 await page.goto('http://fcoi.test/');await page.addStyleTag({content:fs.readFileSync('assets/app.css','utf8')});await page.evaluate(()=>window.FCOI_CONFIG={api:'http://fcoi.test/api/',nonce:'test'});await page.addScriptTag({content:fs.readFileSync('assets/app.js','utf8')});
 const status=t=>page.getByRole('status').filter({hasText:t}).waitFor();await status('Ready.');const editor=page.locator('.fcoi-editor');
 async function add(name,sex,reg=''){await editor.locator('[name=name]').fill(name);await editor.locator('[name=registration]').fill(reg);await editor.locator('[name=sex]').selectOption(sex);await editor.getByRole('button',{name:'Apply to pedigree'}).click();}
 await add('Sire','M','1234-567890');await add('Dam','F');await editor.locator('[name=name]').fill('Kitten');
 for(const k of ['sire','dam'])await editor.locator('[name='+k+']').selectOption(await editor.locator('[name='+k+'] option').nth(1).getAttribute('value'));
 await editor.getByRole('button',{name:'Apply to pedigree'}).click();if(await page.locator('tbody tr').count()!==3)throw Error('add failed');
 await page.getByRole('button',{name:'Calculate',exact:true}).click();await status('Save the pedigree');await page.getByRole('button',{name:'Save pedigree'}).click();await status('Pedigree saved.');
 await page.locator('.fcoi-calc [name=id]').selectOption(cats[2].id);await page.getByRole('button',{name:'Calculate',exact:true}).click();await page.locator('.fcoi-value').filter({hasText:'25%'}).waitFor();
 await page.locator('tbody tr').first().getByRole('button',{name:'Delete',exact:true}).click();await status('recorded parent');page.on('dialog',d=>d.accept());
 await page.locator('.fcoi-doc-file').setInputFiles({name:'CFA-test.png',mimeType:'image/png',buffer:Buffer.from('synthetic')});await page.getByRole('button',{name:'Upload privately'}).click();await page.getByRole('button',{name:'Read with OpenAI'}).waitFor();await page.getByRole('button',{name:'Read with OpenAI'}).click();await page.getByRole('button',{name:'Apply reviewed fields'}).waitFor();
 if(await page.locator('.fcoi-count').textContent()!=='(3/500)')throw Error('Reading changed pedigree without review');await page.getByRole('button',{name:'Apply reviewed fields'}).click();await status('Reviewed fields applied locally');if(await page.locator('.fcoi-count').textContent()!=='(5/500)')throw Error('Review apply failed');
 conflict=true;await page.getByRole('button',{name:'Save pedigree'}).click();await status('another tab');if(await page.locator('.fcoi-count').textContent()!=='(5/500)')throw Error('Conflict lost data');
 await page.setViewportSize({width:390,height:844});if(await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth))throw Error('Mobile overflow');await page.screenshot({path:'/tmp/fcoi-mobile.png',fullPage:true});
 if(errors.length)throw Error(errors.join('\n'));console.log('PASS UI: add/link, save gate, COI output, parent deletion guard, upload, review/apply, conflict preservation, mobile, no JS errors.');await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
