// A receipt is one SVG viewport: the browser scales it as a whole instead of
// fragmenting its HTML across sheets. Keep text and borders as vector content.
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
    Array.from(root.children).forEach(receipt => {
        const bounds = receipt.getBoundingClientRect()
        if (!bounds.width || !bounds.height) return
        const clone = receipt.cloneNode(true)
        const originals = [receipt, ...receipt.querySelectorAll('*')]
        const copies = [clone, ...clone.querySelectorAll('*')]
        let width = bounds.width
        let height = bounds.height
        originals.forEach((element, index) => {
            const style = window.getComputedStyle(element)
            const target = copies[index]
            target.removeAttribute('id')
            for (let i = 0; i < style.length; i++) {
                const property = style[i]
                target.style.setProperty(property, style.getPropertyValue(property))
            }
            const rect = element.getBoundingClientRect()
            // Include stamps and other absolutely positioned content.
            width = Math.max(width, rect.right - bounds.left)
            height = Math.max(height, rect.bottom - bounds.top)
        })
        clone.style.margin = '0'
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg')
        svg.setAttribute('viewBox', `0 0 ${Math.ceil(width) + 2} ${Math.ceil(height) + 2}`)
        svg.setAttribute('preserveAspectRatio', 'xMidYMin meet')
        svg.setAttribute('class', 'receipt-print-sheet-item')
        const content = document.createElementNS('http://www.w3.org/2000/svg', 'foreignObject')
        content.setAttribute('width', Math.ceil(width) + 2)
        content.setAttribute('height', Math.ceil(height) + 2)
        clone.setAttribute('xmlns', 'http://www.w3.org/1999/xhtml')
        content.appendChild(clone)
        svg.appendChild(content)
        output.appendChild(svg)
    })
    return output
}

export const receiptPrintStyle = `
@page { size: auto; margin: 10mm; }
html, body { margin: 0 !important; padding: 0 !important; }
.receipt-print-document { width: 100%; }
.receipt-print-sheet-item {
    display: block; width: 100%; height: calc(25vh - 6mm);
    margin: 0; padding: 0; break-inside: avoid; page-break-inside: avoid;
}
.receipt-print-sheet-item:nth-child(4n + 1):not(:first-child) {
    break-before: page; page-break-before: always;
}
@media print and (orientation: portrait) and (max-height: 296mm),
       print and (orientation: portrait) and (max-width: 209mm),
       print and (orientation: landscape) and (max-width: 296mm),
       print and (orientation: landscape) and (max-height: 209mm) {
    .receipt-print-sheet-item { height: calc((100vh - 24mm) / 3); }
    .receipt-print-sheet-item:nth-child(4n + 1):not(:first-child) {
        break-before: auto; page-break-before: auto;
    }
    .receipt-print-sheet-item:nth-child(3n + 1):not(:first-child) {
        break-before: page; page-break-before: always;
    }
}`
