import zipfile, xml.etree.ElementTree as ET, json, sys
from decimal import Decimal
from collections import Counter, defaultdict
p=sys.argv[1]
sys.stdout.reconfigure(encoding='utf-8')
ns={'m':'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
with zipfile.ZipFile(p) as z:
    strings=[]
    if 'xl/sharedStrings.xml' in z.namelist():
        strings=[''.join(e.itertext()) for e in ET.fromstring(z.read('xl/sharedStrings.xml')).findall('m:si',ns)]
    print(z.read('xl/workbook.xml').decode()[:1800])
    for name in z.namelist():
        if not name.startswith('xl/worksheets/sheet') or not name.endswith('.xml'): continue
        root=ET.fromstring(z.read(name)); rows=[]; formulas=[]
        for row in root.findall('m:sheetData/m:row',ns):
            values={}
            for c in row.findall('m:c',ns):
                v=c.find('m:v',ns); val=v.text if v is not None else ''
                if c.get('t')=='s': val=strings[int(val)]
                elif c.get('t')=='inlineStr': val=''.join(c.find('m:is',ns).itertext())
                values[c.get('r')]=val
                f=c.find('m:f',ns)
                if f is not None: formulas.append([c.get('r'),f.text,val])
            rows.append(values)
        print(json.dumps({'sheet':name,'rows':len(rows),'headers':rows[:3],'tail':rows[-1:],'formulas':formulas},ensure_ascii=False))
        data=[]
        for row in rows[3:]:
            d={''.join(filter(str.isalpha,k)):v for k,v in row.items()}
            if not d.get('E'): continue
            d['row']=int(''.join(filter(str.isdigit,next(iter(row)))))
            data.append(d)
        groups=defaultdict(list)
        for d in data: groups[d['E']].append(d)
        dup=[{'order':k,'rows':[x['row'] for x in v],'amounts':[x.get('F') for x in v]} for k,v in groups.items() if len(v)>1]
        print(json.dumps({'blank_payment_rows':[{k:d.get(k) for k in ['row','B','D','E','F','G','H','J']} for d in data if not d.get('J')], 'multiline_bill_count':sum(max(len(d.get('D','').splitlines()),len(d.get('G','').splitlines()),1) for d in data)},ensure_ascii=False))
        totals=defaultdict(Decimal); counts=Counter()
        for d in data:
            totals[d.get('J','')]+=Decimal(d.get('F') or '0'); counts[d.get('J','')]+=1
        print(json.dumps({'detail_count':len(data),'unique_orders':len(groups),'sum':str(sum((Decimal(d.get('F') or '0') for d in data),Decimal(0))),'duplicates':dup,'payment_totals':{k:str(v) for k,v in totals.items()},'payment_counts':counts,'zero_rows':[d['row'] for d in data if Decimal(d.get('F') or '0')==0],'negative_rows':[d['row'] for d in data if Decimal(d.get('F') or '0')<0]},ensure_ascii=False))
