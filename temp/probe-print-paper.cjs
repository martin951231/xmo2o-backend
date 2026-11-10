const { chromium } = require('C:/Users/GIGABYTE/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
(async () => {
 const browser = await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 const page = await browser.newPage();
 let css='@page {size:auto;margin:10mm} div{display:none}';
 let html='';
 for(const kind of ['width','height','device-width','device-height']) {
  for(const value of [190,209,210,211,260,276,280,296,297,298,320,400]) {
   const id=kind+value;css+=`@media print and (min-${kind}:${value}mm){#${id}{display:block}}`;
   html+=`<div id="${id}">${kind} &gt;= ${value}mm</div>`;
  }
 }
 await page.setContent('<style>'+css+'</style>'+html);
 for(const scale of [0.8,1,1.1])await page.pdf({path:`temp/receipt-print-tests/probe-${scale}.pdf`,format:'A4',scale});
 await browser.close();
})();
