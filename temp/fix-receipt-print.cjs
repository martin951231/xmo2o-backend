const fs = require('fs');
const base = 'D:/projectView/platform_dev/src/';
const helperPath = base + 'utils/receiptPrintLayout.js';
const componentPath = base + 'views/community/village/charge/cashier/printOrder.vue';
let helper = fs.readFileSync(helperPath, 'utf8').replace(/\r\n/g, '\n');
let component = fs.readFileSync(componentPath, 'utf8').replace(/\r\n/g, '\n');
const start = helper.indexOf('export const receiptPrintStyle =');
if (start < 0) throw new Error('Unexpected helper version');
helper = helper.slice(0, start) + `// Print media width/height change with the browser's print scale. They cannot
// reliably identify physical A4 paper, so use the paper selected in the preview.
export function createReceiptPrintStyle(paper = 'a4') {
    const count = paper === 'small' ? 3 : 4
    return \`
@page { size: auto; margin: 10mm; }
html, body { margin: 0 !important; padding: 0 !important; }
.receipt-print-document { width: 100%; }
.receipt-print-sheet-item {
    display: block; width: 100%; height: auto;
    max-height: calc((100vh - 40mm) / \$\{count\});
    margin: 0 0 3mm; padding: 0;
    break-inside: avoid; page-break-inside: avoid;
}
.receipt-print-sheet-item:nth-child(\$\{count\}n + 1):not(:first-child) {
    break-before: page; page-break-before: always;
}
\`
}
`;
component = component.replace('createReceiptPrintDocument, receiptPrintStyle', 'createReceiptPrintDocument, createReceiptPrintStyle');
component = component.replace('style: receiptPrintStyle,', 'style: createReceiptPrintStyle(this.printPaper),');
const button = '            <a-button type="primary" @click="print">打印</a-button>';
if (!component.includes(button)) throw new Error('Print button not found');
component = component.replace(button, `            <span style="margin-right: 8px;">打印纸张：</span>
            <a-select v-model="printPaper" style="width: 180px; margin-right: 12px;">
                <a-select-option value="a4">A4（每页最多4笔）</a-select-option>
                <a-select-option value="small">小纸张（每页最多3笔）</a-select-option>
            </a-select>
            <a-button type="primary" :loading="printPreparing" @click="print">打印</a-button>
            <div style="margin-top: 8px; color: #888;">请与系统打印窗口的纸张设置保持一致；小纸张请选择每页3笔。</div>`);
component = component.replace("            title: '打印预览',", "            title: '打印预览',\n            printPaper: 'a4',\n            printPreparing: false,");
fs.mkdirSync('temp/receipt-print-fix-backup', {recursive:true});
fs.copyFileSync(helperPath, 'temp/receipt-print-fix-backup/receiptPrintLayout.js');
fs.copyFileSync(componentPath, 'temp/receipt-print-fix-backup/printOrder.vue');
fs.writeFileSync(helperPath, helper);
fs.writeFileSync(componentPath, component);
fs.writeFileSync('temp/receipt-print-layout.js', helper);
console.log('Updated compact receipt layout and explicit paper selection');
