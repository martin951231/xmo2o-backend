const fs=require('fs');
const path='D:/projectView/platform_dev/src/utils/receiptPrintLayout.js';
let source=fs.readFileSync(path,'utf8');
source=source.replace('width: max-content !important; max-width: none !important; flex: 0 0 auto;', 'width: 200px !important; max-width: none !important; flex: 0 0 auto;')
 .replace('text-align: right !important; white-space: nowrap !important;', 'text-align: left !important; white-space: nowrap !important;');
source=source.replace('            const measured = content.getBoundingClientRect()', `            const measured = content.getBoundingClientRect()
            const firstLabel = content.querySelector('.info-label')
            const receiptBox = content.querySelector('.receipt')
            const lineStart = firstLabel
                ? firstLabel.getBoundingClientRect().left - measured.left + parseFloat(getComputedStyle(firstLabel).paddingLeft || 0)
                : 0
            const lineEnd = receiptBox
                ? receiptBox.getBoundingClientRect().right - measured.left - parseFloat(getComputedStyle(receiptBox).paddingRight || 0)
                : measured.width`);
source=source.replace("separator.setAttribute('viewBox', '0 0 300 4')", "separator.setAttribute('viewBox', `0 0 ${Math.ceil(measured.width) + 2} 4`)\n            separator.setAttribute('preserveAspectRatio', 'none')");
source=source.replace("'display:block;width:45%;max-width:85mm;height:1mm;margin:2mm auto 0;'", "'display:block;width:100%;height:1mm;margin:2mm 0 0;'");
source=source.replace("line.setAttribute('x1', '0')", "line.setAttribute('x1', String(lineStart))")
 .replace("line.setAttribute('x2', '300')", "line.setAttribute('x2', String(lineEnd))")
 .replace('            wrapper.appendChild(separator)', '            if (index < root.children.length - 1) wrapper.appendChild(separator)');
fs.writeFileSync(path,source);
console.log('Date aligned to amount label; separator spans labels to right edge; final receipt has no line');
