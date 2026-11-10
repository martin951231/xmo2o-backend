const fs = require('fs');
const root = 'D:/projectView/platform_dev/src/';
const path = root + 'utils/receiptPrintLayout.js';
let source = fs.readFileSync(path, 'utf8');
const old = '.receipt-print-content .info-label { width: 42mm !important; flex-shrink: 0; font-weight: bold; }';
if (!source.includes(old)) throw new Error('Label rule changed');
source = source.replace(old, '.receipt-print-content .info-label { width: 42mm !important; flex-shrink: 0; font-weight: bold; text-align: right !important; padding-right: 2em; }');
const marker = '.receipt-print-content .text-underline {';
source = source.replace(marker, `.receipt-print-content .company-info .text-nowrap {
    width: max-content !important; max-width: none !important; flex: 0 0 auto;
    text-align: right !important; white-space: nowrap !important;
    overflow-wrap: normal !important; word-break: keep-all !important;
}
` + marker);
fs.writeFileSync(path, source);
const component = root + 'views/community/village/charge/cashier/printOrder.vue';
let vue = fs.readFileSync(component, 'utf8');
vue = vue.replace('&nbsp;&nbsp;{{item_list[0]}}', '{{item_list[0]}}')
    .replace('&nbsp;&nbsp;{{numberToChinese(item_list[3] || item_list[0])}}', '{{numberToChinese(item_list[3] || item_list[0])}}');
fs.writeFileSync(component, vue);
console.log('Right-aligned labels with 2em gap; date grows left without wrapping');
