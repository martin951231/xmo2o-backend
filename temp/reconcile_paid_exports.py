import zipfile, xml.etree.ElementTree as E, json, sys
from decimal import Decimal
from collections import defaultdict
sys.stdout.reconfigure(encoding='utf-8')
ns={'m':'http://schemas.openxmlformats.org/spreadsheetml/2006/main'}
def read(p):
 with zipfile.ZipFile(p) as z:
  ss=[''.join(x.itertext()) for x in E.fromstring(z.read('xl/sharedStrings.xml')).findall('m:si',ns)] if 'xl/sharedStrings.xml' in z.namelist() else []
  rows=[]
  for r in E.fromstring(z.read('xl/worksheets/sheet1.xml')).findall('m:sheetData/m:row',ns):
   d={'row':int(r.get('r'))}
   for c in r.findall('m:c',ns):
    v=c.find('m:v',ns); t=v.text if v is not None else ''
    if c.get('t')=='s': t=ss[int(t)]
    elif c.get('t')=='inlineStr': t=''.join(c.find('m:is',ns).itertext())
    d[''.join(filter(str.isalpha,c.get('r')))]=t
   rows.append(d)
  return rows
paid=read(sys.argv[1]); flow=read(sys.argv[2])
paid=[r for r in paid[3:] if r.get('L')]; flow=[r for r in flow[3:] if r.get('E')]
groups=defaultdict(list)
for r in paid: groups[r['L']].append(r)
diff=[]
for r in flow:
 rows=groups.get(r['E'],[]); total=sum((Decimal(x['P']) for x in rows),Decimal(0)); delta=Decimal(r['F'])-total
 if delta: diff.append({'flow_row':r['row'],'order':r['E'],'address':r['B'],'flow':r['F'],'paid':str(total),'delta':str(delta),'paid_rows':[x['row'] for x in rows],'flow_projects':r['D'],'paid_projects':[x['J'] for x in rows],'refund':str(sum((Decimal(x['AC'] or 0) for x in rows),Decimal(0)))})
flow_ids={r['E'] for r in flow}; missing=[r for r in paid if r['L'] not in flow_ids]
print(json.dumps({'paid_count':len(paid),'paid_orders':len(groups),'paid_total':str(sum(Decimal(r['P']) for r in paid)),'flow_total':str(sum(Decimal(r['F']) for r in flow)),'diff':diff,'paid_only_count':len(missing),'paid_only_total':str(sum((Decimal(r['P']) for r in missing),Decimal(0)))},ensure_ascii=False))
