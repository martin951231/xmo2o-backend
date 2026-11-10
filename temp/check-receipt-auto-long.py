from pypdf import PdfReader
from pathlib import Path
import re

for paper in ['A4', 'A5', 'Letter']:
    for scale in ['0.8', '1', '1.1', '1.5']:
        path = Path('temp/receipt-print-tests') / (paper + '-' + scale + '.pdf')
        ids = []
        for page in PdfReader(path).pages:
            text = re.sub(r'\s+', '', page.extract_text())
            row = re.findall(r'START-BILL-([0-9]+)', text)
            assert len(row) <= 4, (path, row)
            assert row == re.findall(r'END-BILL-([0-9]+)', text), (path, row, 'end')
            assert row == re.findall(r'STAMP-([0-9]+)', text), (path, row, 'stamp')
            ids.extend(row)
        assert ids == list(map(str, range(1, 10))), (path, ids)
        print(path.name, 'PASS: actual print library, oversized receipt, stamps, <=4 per page')
