const fs = require('fs');
const project = 'D:/projectView/platform_dev/';
const {chromium} = require('C:/Users/GIGABYTE/.cache/codex-runtimes/codex-primary-runtime/dependencies/node/node_modules/playwright');
const compiler = require(project + 'node_modules/vue-template-compiler');
const less = require(project + 'node_modules/less');
(async () => {
 const source = compiler.parseComponent(fs.readFileSync(project+'src/views/community/village/charge/cashier/printOrder.vue','utf8'));
 const script = source.script.content.replace(/^import .*$/gm,'').replace('export default','return');
 const style = (await less.render(source.styles[0].content.replace(/\/deep\//g,' '))).css;
 const helper = fs.readFileSync(project+'src/utils/receiptPrintLayout.js','utf8').replace(/export /g,'');
 const browser = await chromium.launch({executablePath:'C:/Program Files/Google/Chrome/Application/chrome.exe',headless:true});
 const page = await browser.newPage({viewport:{width:1300,height:900}});
 await page.setContent('<div id="app"></div>');
 await page.addScriptTag({path:project+'node_modules/vue/dist/vue.js'});
 await page.addStyleTag({content:style});
 await page.evaluate(async ({script,template,helper}) => {
  Vue.prototype.$form={createForm:()=>({})};
  Vue.prototype.$store={state:{user:{info:{name:'管理员'}}}};
  Vue.component('a-modal',{template:'<div style="width:1200px"><slot /></div>'});
  const options = new Function(script)();
  options.template=template;
  options.methods.numberToChinese = () => '壹佰元整';
  const instance = new Vue(options).$mount('#app');
  Object.assign(instance,{
   printType:4, table_id:'receipt-source', visible:true, is_show:false,
   is_title:true, print_title:'物业缴费收据',
   printList1:[{title:'小区'},{title:'房号'},{title:'住户姓名'}],
   list1:[{title:'小区',value:'测试小区'},{title:'房号',value:'1栋-101'},{title:'住户姓名',value:'测试住户'}],
   printList2:[{title:'收费项目'},{title:'起始日期'},{title:'终止日期'},{title:'实收金额'}],
   printList3:[{title:'收款日期',value:'2026-10-14-2026-10-21'},{title:'经手人'}],
   tableList:Array.from({length:9},(_,i)=>['BILL-'+(i+1),'2026-10-01','2026-10-31','100.00']),
   data:Array.from({length:9},(_,i)=>({pay_time:i%2===0?'2026-10-14':'2026-10-21'}))
  });
  await Vue.nextTick();
  const api=new Function(helper+';return {createReceiptPrintDocument,createReceiptPrintStyle}')();
  const output=await api.createReceiptPrintDocument(document.getElementById('receipt-source'));
  document.body.innerHTML='';
  document.body.appendChild(output);
  const printStyle=document.createElement('style');
  printStyle.textContent=api.createReceiptPrintStyle();
  document.head.appendChild(printStyle);
 },{script,template:source.template.content,helper});
 for (const format of ['A4','A5','Letter','A6']) {
  for (const scale of [0.8,1,1.1,1.5]) {
   await page.pdf({path:`temp/receipt-print-tests/auto-${format}-${scale}.pdf`,format,scale});
  }
 }
 await page.pdf({path:'temp/receipt-print-tests/auto-A4-landscape.pdf',format:'A4',landscape:true});
 await browser.close();
 console.log('Actual receipt template: A4/A5/Letter/A6 at 80/100/110/150%, and landscape');
})().catch(e=>{console.error(e);process.exit(1)});
