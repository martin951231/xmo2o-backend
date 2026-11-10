const fs = require('fs');
const path = 'D:/projectView/platform_dev/src/utils/receiptPrintLayout.js';
let source = fs.readFileSync(path, 'utf8');
const anchor = '            wrapper.appendChild(svg)';
if (!source.includes(anchor)) throw new Error('Missing receipt wrapper');
source = source.replace(anchor, `${anchor}
            // A real vector stroke survives print-js cloning and does not rely
            // on generated pseudo-elements or background-graphics settings.
            const separator = document.createElementNS('http://www.w3.org/2000/svg', 'svg')
            separator.setAttribute('class', 'receipt-print-separator')
            separator.setAttribute('viewBox', '0 0 300 4')
            separator.style.cssText = 'display:block;width:45%;max-width:85mm;height:1mm;margin:2mm auto 0;'
            const line = document.createElementNS('http://www.w3.org/2000/svg', 'line')
            line.setAttribute('x1', '0')
            line.setAttribute('y1', '2')
            line.setAttribute('x2', '300')
            line.setAttribute('y2', '2')
            line.setAttribute('stroke', '#000')
            line.setAttribute('stroke-width', '1')
            line.setAttribute('stroke-dasharray', '5 3')
            separator.appendChild(line)
            wrapper.appendChild(separator)`);
source = source.replace(/\.receipt-print-item::after \{[^}]+\}\r?\n/, '');
source = source.replace('text-align: left !important; padding-left:', 'text-align: left !important; position: relative; left: 3mm; padding-left:');
fs.writeFileSync(path, source);
console.log('Added real black dashed SVG separator; moved labels 3mm right');
