const fs = require('fs');
const file = 'D:/projectView/platform_dev/src/utils/receiptPrintLayout.js';
let text = fs.readFileSync(file, 'utf8');
fs.copyFileSync(file, 'temp/receipt-layout-before-fixed-four.js');
text = text.replace(/\/\/ Use physical type sizes[\s\S]*?export async/, '// Lay out at the A4 printable width with physical type sizes, then fit each\n// complete receipt into at most one quarter of the selected printable page.\nexport async');
text = text.replace('width:128mm;', 'width:190mm;');
text = text.replace('    const rules = []\n', '');
text = text.replace('            wrapper.appendChild(content)\n', '            content.remove()\n');
const start = text.indexOf('            const minimumPageHeight');
const end = text.indexOf('\n        })', start);
if(start < 0 || end < 0)throw new Error('Expected conditional layout not found');
text = text.slice(0,start) + text.slice(end);
text = text.replace(/    const fallbackRules = document.createElement\('style'\)[\s\S]*?    return output/, '    return output');
text = text.replace(/    display: inline-block; vertical-align: top; box-sizing: border-box; width: 100%; margin: 0 0 3mm;[\s\S]*?    break-inside: avoid; page-break-inside: avoid;/,
    '    display: block; box-sizing: border-box; width: 100%; margin: 0 0 2mm;\n    break-inside: avoid; page-break-inside: avoid;');
text = text.replace('.receipt-print-content {', '.receipt-print-item:nth-child(4n + 1):not(:first-child) {\n    break-before: page; page-break-before: always;\n}\n.receipt-print-content {');
text = text.replace('font-size: 16pt !important;', 'font-size: 14pt !important;')
    .replace('font-size: 20pt !important;', 'font-size: 18pt !important;')
    .replace('font-size: 14.5pt !important;', 'font-size: 12.5pt !important;')
    .replace('display: none; width: 100%; height: auto; max-height: calc(100vh - 28mm);', 'display: block; width: 100%; height: auto; max-height: calc(25vh - 3mm);');
fs.writeFileSync(file,text);
console.log('Fonts reduced by 2pt; complete receipts fit in four-item pages');
