const {chromium}=require(process.env.IFMAP_PLAYWRIGHT_PATH || 'playwright');
const fs=require('fs');
const root=require('path').resolve(__dirname,'..');
(async()=>{
 const browser=await chromium.launch({executablePath:process.env.IFMAP_CHROME_PATH || undefined,headless:true,args:['--no-sandbox']});
 const page=await browser.newPage({viewport:{width:1440,height:1050}});
 const errors=[];page.on('pageerror',e=>errors.push(e.message));
 let submitted=null;
 const check=(v,m)=>{if(!v)throw Error(m)};
 await page.route('**/*',async route=>{
  const url=new URL(route.request().url());
  if(url.hostname!=='ifmap.test')return route.fulfill({status:200,body:''});
  if(route.request().method()==='POST'){submitted={path:url.pathname,data:Object.fromEntries(new URLSearchParams(route.request().postData()))};return route.fulfill({status:200,body:'Test submission captured; no live mutation.'});}
  if(url.pathname==='/admin/commandes'){
   let html=fs.readFileSync(process.env.IFMAP_TEST_HTML_PATH || '/tmp/ifmap-orders-test.html','utf8');
   for(const css of ['functional','modern-forms','profile-manager','account-dropdown','builder-media','rich-editor','news'])html=html.replace('</head>',`<link rel="stylesheet" href="/public/assets/css/${css}.css"></head>`);
   html=html.replace('</body>','<script src="/public/assets/js/functional.js"></script></body>');
   return route.fulfill({contentType:'text/html',body:html});
  }
  if(url.pathname.startsWith('/public/')){const path=root+url.pathname;return route.fulfill({contentType:path.endsWith('.css')?'text/css':path.endsWith('.js')?'application/javascript':'image/svg+xml',body:fs.existsSync(path)?fs.readFileSync(path):''});}
  return route.fulfill({status:404,body:''});
 });
 const load=()=>page.goto('http://ifmap.test/admin/commandes');
 await load();
 await page.locator('[data-order-search]').fill('OP-123');
 check(await page.locator('[data-order-card]:visible').count()>0,'Search transaction');
 await page.locator('[data-order-search]').fill('REFUND-123');
 check(await page.locator('[data-order-card]:visible').count()===1,'Search refund reference');
 await page.locator('[data-order-search]').fill('absent-reference');
 check(await page.locator('[data-order-empty]').isVisible(),'Empty state');
 await page.locator('[data-order-reset]').click();
 await page.locator('[data-order-filter]').selectOption('cancelled');
 check(await page.locator('[data-order-card]:visible [data-order-action="cancelled"]').count()===0,'Cancelled cards have no cancellation action');
 await page.locator('[data-order-filter]').selectOption('refunded');
 check(await page.locator('[data-order-card]:visible').count()>0,'Refunded filter');
 await page.locator('[data-order-filter]').selectOption('all');
 await page.screenshot({path:'/tmp/ifmap-orders-desktop.png',fullPage:false});
 const actions=['cancelled','processing','shipping','completed','collect_cod','request_refund','complete_refund','reject_refund','receive_return'];
 // Ensure representative pending and refundable rows have relevant actions in fixture.
 for(const action of actions){
  const button=page.locator(`[data-order-action="${action}"]`).first();
  if(!await button.count())throw Error('Missing fixture action '+action);
  await button.click();
  check(await page.locator('[data-order-dialog]').isVisible(),'Dialog opens '+action);
  const note=page.locator('[data-order-form] textarea');
  if(['cancelled','collect_cod','request_refund','complete_refund','reject_refund','receive_return'].includes(action)){
   check(await note.getAttribute('required')!==null,'Note required '+action);
   await page.locator('[data-dialog-submit]').click();
   check(await page.locator('[data-order-dialog]').isVisible(),'Empty note prevents submit '+action);
  }
  if(action==='cancelled')await page.screenshot({path:'/tmp/ifmap-orders-cancel.png'});
  await note.fill('Justificatif vérifié pour le test');
  if(['collect_cod','complete_refund'].includes(action)){
   await page.locator('[name="reference"]').fill('RECEIPT-TEST');await page.locator('[name="method"]').fill('Mobile Money');
  }
  if(action==='collect_cod')await page.locator('[name="payer_phone"]').fill('0700000000');
  if(action==='receive_return')await page.locator('[data-return-type]').selectOption('receive_return_damaged');
  await page.locator('[data-dialog-submit]').click();
  await page.waitForURL(url=>url.pathname!=='/admin/commandes');
  check(submitted.data.csrf&&submitted.data.id,'CSRF and order id submitted '+action);
  check(submitted.path===(['cancelled','processing','shipping','completed'].includes(action)?'/admin/commandes/statut':'/admin/commandes/action'),'Correct endpoint '+action);
  check((submitted.data.status||submitted.data.action)===(action==='receive_return'?'receive_return_damaged':action),'Correct operation '+action);
  check(!Object.values(submitted.data).includes('undefined'),'No undefined data');
  await load();
 }
 await page.setViewportSize({width:390,height:844});
 await page.waitForTimeout(400);
 await page.screenshot({path:'/tmp/ifmap-orders-mobile.png',fullPage:false});
 check(await page.evaluate(()=>document.documentElement.scrollWidth<=window.innerWidth),'No mobile horizontal overflow');
 await page.locator('[data-order-action="cancelled"]').first().click();
 check(await page.locator('[data-dialog-submit]').isVisible(),'Mobile confirmation visible');
 await page.screenshot({path:'/tmp/ifmap-orders-mobile-dialog.png'});
 await page.keyboard.press('Escape');
 check(!await page.locator('[data-order-dialog]').isVisible(),'Escape closes modal');
 check(errors.length===0,'No JS errors: '+errors.join('; '));
 console.log('Browser: search, filters, action dialogs, required fields, POST routing, mobile and console OK');
 await browser.close();
})().catch(e=>{console.error(e);process.exit(1)});
