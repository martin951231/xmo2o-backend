// Use physical type sizes and normal HTML reflow for ordinary receipts. Only a
// receipt larger than the printable sheet uses an atomic, scaled SVG fallback.
export async function createReceiptPrintDocument(root) {
    if (document.fonts && document.fonts.ready) await document.fonts.ready
    await Promise.all(Array.from(root.querySelectorAll('img')).map(image => {
        if (image.complete) return Promise.resolve()
        return new Promise(resolve => {
            image.addEventListener('load', resolve, { once: true })
            image.addEventListener('error', resolve, { once: true })
        })
    }))
    const output = document.createElement('div')
    output.className = 'receipt-print-document'
    const stage = document.createElement('div')
    stage.style.cssText = 'position:absolute;left:-10000px;top:0;width:128mm;visibility:hidden;'
    const style = document.createElement('style')
    style.textContent = createReceiptPrintStyle()
    stage.appendChild(style)
    document.body.appendChild(stage)
    const rules = []
    try {
        Array.from(root.children).forEach((receipt, index) => {
            const content = receipt.cloneNode(true)
            content.classList.add('receipt-print-content')
            const sources = [receipt, ...receipt.querySelectorAll('*')]
            const targets = [content, ...content.querySelectorAll('*')]
            sources.forEach((source, i) => {
                const computed = window.getComputedStyle(source)
                targets[i].removeAttribute('id')
                // Preserve template appearance without freezing screen widths,
                // font sizes or heights into the printed document.
                for (const property of ['display', 'color', 'font-weight', 'font-style',
                    'text-align', 'text-decoration', 'border', 'border-collapse',
                    'flex-direction', 'align-items', 'justify-content', 'flex-wrap']) {
                    targets[i].style.setProperty(property, computed.getPropertyValue(property))
                }
            })
            stage.appendChild(content)
            // Reserve room for stamps protruding below their positioned parent.
            const bounds = content.getBoundingClientRect()
            const bottom = Math.max(bounds.bottom, ...Array.from(content.querySelectorAll('*')).map(el => el.getBoundingClientRect().bottom))
            if (bottom > bounds.bottom) content.style.paddingBottom = (bottom - bounds.bottom + 4) + 'px'
            const measured = content.getBoundingClientRect()
            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg')
            svg.setAttribute('class', 'receipt-print-fallback')
            svg.setAttribute('viewBox', `0 0 ${Math.ceil(measured.width) + 2} ${Math.ceil(measured.height) + 2}`)
            svg.setAttribute('preserveAspectRatio', 'xMidYMin meet')
            const foreign = document.createElementNS('http://www.w3.org/2000/svg', 'foreignObject')
            foreign.setAttribute('width', Math.ceil(measured.width) + 2)
            foreign.setAttribute('height', Math.ceil(measured.height) + 2)
            const snapshot = content.cloneNode(true)
            const live = [content, ...content.querySelectorAll('*')]
            const copies = [snapshot, ...snapshot.querySelectorAll('*')]
            live.forEach((node, i) => {
                const computed = window.getComputedStyle(node)
                for (let j = 0; j < computed.length; j++) {
                    copies[i].style.setProperty(computed[j], computed.getPropertyValue(computed[j]))
                }
                copies[i].style.visibility = 'visible'
            })
            snapshot.setAttribute('xmlns', 'http://www.w3.org/1999/xhtml')
            foreign.appendChild(snapshot)
            svg.appendChild(foreign)
            const wrapper = document.createElement('div')
            wrapper.className = 'receipt-print-item receipt-print-item-' + index
            wrapper.appendChild(content)
            wrapper.appendChild(svg)
            output.appendChild(wrapper)
            const minimumPageHeight = Math.ceil(measured.height * 25.4 / 96 + 28)
            const selector = '.receipt-print-item-' + index
            rules.push(`@media print and (max-height: ${minimumPageHeight}mm), print and (max-width: 130mm) {
                ${selector} > .receipt-print-content { display: none !important; }
                ${selector} > .receipt-print-fallback { display: block; }
            }`)
        })
    } finally {
        stage.remove()
    }
    const fallbackRules = document.createElement('style')
    fallbackRules.textContent = rules.join('\n')
    output.appendChild(fallbackRules)
    return output
}

export function createReceiptPrintStyle() {
    return `
@page { size: auto; margin: 10mm; }
html, body { margin: 0 !important; padding: 0 !important; }
.receipt-print-document { width: 100%; }
.receipt-print-item {
    box-sizing: border-box; width: 100%; margin: 0 0 3mm;
    /* Five items cannot fit even when receipts are very short. Longer receipts
       naturally reduce the number per sheet without paper-size detection. */
    min-height: calc((100vh - 20mm) / 5 + 1mm);
    break-inside: avoid; page-break-inside: avoid;
}
.receipt-print-content {
    display: flow-root !important; width: 100% !important;
    height: auto !important; margin: 0 !important; color: #000;
}
.receipt-print-content, .receipt-print-content * {
    box-sizing: border-box; font-family: SimSun, 'Songti SC', serif !important;
    font-size: 12pt !important; line-height: 1.4 !important;
    white-space: normal !important; overflow-wrap: anywhere;
}
.receipt-print-content > span:first-child { font-size: 16pt !important; }
.receipt-print-content .receipt { width: 100% !important; margin: 0 !important; padding: 3mm 2mm !important; }
.receipt-print-content .company-info { margin-bottom: 3mm !important; }
.receipt-print-content .info-item { display: flex !important; flex-wrap: wrap; margin-bottom: 3mm !important; overflow: visible !important; }
.receipt-print-content .info-label { width: 30mm !important; flex-shrink: 0; font-weight: bold; }
.receipt-print-content .info-content { flex: 1; min-width: 0; text-decoration: underline; }
.receipt-print-content .text-nowrap { width: 53mm !important; }
.receipt-print-content .text-underline { text-decoration: underline; }
.receipt-print-content .handler { margin-top: 3mm !important; }
.receipt-print-content .table_item, .receipt-print-content .table_top,
.receipt-print-content .left_title, .receipt-print-content .right_content,
.receipt-print-content .right_71, .receipt-print-content .right_72,
.receipt-print-content .right_73 { height: auto !important; min-height: 7mm; }
.receipt-print-content .table_item { font-size: 10.5pt !important; }
.receipt-print-content .table_item span { font-size: inherit !important; }
.receipt-print-content .ant-descriptions-item-colon::after { content: ''; }
.receipt-print-fallback { display: none; width: 100%; height: auto; max-height: calc(100vh - 28mm); }
`
}
