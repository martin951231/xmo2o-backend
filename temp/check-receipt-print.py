import re
from pypdf import PdfReader

cases = {'A4-landscape': 4}
for paper, count in {'A4': 4, 'A5': 3, 'Letter': 3}.items():
    for scale in ['0.8', '1', '1.1', '1.5']:
        cases[paper + '-' + scale] = count
for name, count in cases.items():
    pages = PdfReader('temp/receipt-print-tests/' + name + '.pdf').pages
    ids = list(range(1, 10))
    expected = [ids[i:i + count] for i in range(0, len(ids), count)]
    assert len(pages) == len(expected), name
    for page, receipt_ids in zip(pages, expected):
        for marker in ['START-BILL-', 'END-BILL-', 'STAMP-']:
            found = list(map(int, re.findall(marker + r'([0-9]+)', page.extract_text())))
            assert found == receipt_ids, (name, marker, found, receipt_ids)
    print(name + ': PASS (page count, bill count, complete receipt and stamp)')
