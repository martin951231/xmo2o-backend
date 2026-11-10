import pdfplumber
import pypdfium2 as pdfium

for scale in ['0.8', '1', '1.1', '1.5']:
    path = 'temp/receipt-print-tests/A4-' + scale + '.pdf'
    with pdfplumber.open(path) as doc:
        words = doc.pages[0].extract_words()
        for i in range(1, 4):
            end = next(w for w in words if w['text'] == 'STAMP-' + str(i))
            start = next(w for w in words if w['text'] == 'START-BILL-' + str(i + 1))
            gap = (start['top'] - end['bottom']) * 25.4 / 72
            # Includes the next receipt's padding and heading margin.
            assert 0 < gap < 15, (scale, i, gap)
        print('A4 ' + scale + ': compact receipt gaps verified (<15mm including internal padding)')
doc = pdfium.PdfDocument('temp/receipt-print-tests/A4-1.1.pdf')
doc[0].render(scale=1.2).to_pil().save('temp/receipt-print-tests/A4-compact.png')
