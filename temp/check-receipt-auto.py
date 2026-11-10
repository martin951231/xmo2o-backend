import re
from pathlib import Path
from pypdf import PdfReader
import pdfplumber
import pypdfium2 as pdfium

for file in Path('temp/receipt-print-tests').glob('auto-*.pdf'):
    pages = PdfReader(file).pages
    orders = [re.findall(r'BILL-([0-9]+)', page.extract_text()) for page in pages]
    assert [int(i) for row in orders for i in row] == list(range(1, 10)), (file, orders)
    assert [len(row) for row in orders] == [4, 4, 1], (file, orders)
    for page, row in zip(pages, orders):
        text = re.sub(r'\s+', '', page.extract_text())
        assert text.count('100.00') == len(row), (file, row, 'amount missing')
        assert text.count('管理员') == len(row), (file, row, 'footer split')
    print(file.name, [len(row) for row in orders])
with pdfplumber.open('temp/receipt-print-tests/auto-A4-1.pdf') as doc:
    chars = [c for c in doc.pages[0].chars if c['text'] == 'B']
    assert chars and all(12.8 < c['size'] < 13.1 for c in chars), [c['size'] for c in chars]
    print('A4 normal receipt body font: 13pt verified')
doc = pdfium.PdfDocument('temp/receipt-print-tests/auto-A4-1.pdf')
doc[0].render(scale=1.2).to_pil().save('temp/receipt-print-tests/auto-A4-final.png')
