import json,re,collections,urllib.request,urllib.parse,urllib.error,hashlib
from pathlib import Path
from bs4 import BeautifulSoup
import argparse
parser=argparse.ArgumentParser(description='Audit the local Bitrix migration against original HTML')
parser.add_argument('--output',type=Path)
args=parser.parse_args()
here=Path(__file__).resolve().parent;src=here.parent
runtime=Path((here/'.runtime-path').read_text().strip())
root=runtime/'www';base='http://127.0.0.1:18080'
m=json.loads((root/'local/migrations/catalog.json').read_text());pages={};issues=[];assets=set();fragments=[];text_diffs=[]
def fetch(path):
 try:
  r=urllib.request.urlopen(base+urllib.parse.quote(path,safe='/%?=&'));return r.status,r.headers,r.read()
 except urllib.error.HTTPError as e:return e.code,e.headers,e.read()
def words(doc):
 d=BeautifulSoup(str(doc.body or doc),'html.parser')
 for x in d.select('script,style'):x.decompose()
 return collections.Counter(re.findall(r'[\w]+',d.get_text(' ',strip=True).lower()))
for r in m['routes']:
 status,headers,body=fetch(r['url']);doc=BeautifulSoup(body,'html.parser');pages[r['url']]=doc
 original=BeautifulSoup((src/r['source']).read_text(),'html.parser')
 for selector in ['title','h1']:
  left=original.select_one(selector);right=doc.select_one(selector)
  if (left.get_text(' ',strip=True) if left else '')!=(right.get_text(' ',strip=True) if right else ''):issues.append((r['url'],selector+' differs from source'))
 missing=words(original)-words(doc)
 if missing:text_diffs.append(dict(page=r['url'],missing=dict(missing)))
 for s in doc.select('script[type="application/ld+json"]'):
  try:
   data=json.loads(s.string or s.get_text())
   if 'pelcom.ru//' in str(data):issues.append((r['url'],'malformed schema URL'))
  except Exception as e:issues.append((r['url'],'invalid JSON-LD',str(e)))
 for tag in doc.select('[src],link[href],a[download]'):
  u=tag.get('src',tag.get('href',''))
  if u.startswith('//'):issues.append((r['url'],'unexpected protocol-relative asset',u))
  elif u.startswith('/'):assets.add(urllib.parse.urlsplit(u).path)
 for t in doc.select('[data-path]'):
  path=t['data-path']
  if t.get('data-frames'):
   for i in range(1,int(t['data-frames'])+1):assets.add(path+f'{i:02d}.'+t.get('data-ext','jpg'))
  else:
   for sw in t.select('[data-c]'):assets.add(path+sw['data-c']+'.jpg')
 for a in doc.select('a[href]'):
  u=urllib.parse.urlsplit(urllib.parse.urljoin(base+r['url'],a['href']))
  if u.netloc=='127.0.0.1:18080' and u.fragment:fragments.append((r['url'],u.path,u.fragment))
 if not doc.find('link',rel='canonical'):issues.append((r['url'],'missing canonical'))
 print('.',end='',flush=True)
asset_issues=[]
for path in sorted(assets):
 status,headers,b=fetch(path)
 if status!=200:asset_issues.append((path,status))
 elif path.endswith('.pdf') and not b.startswith(b'%PDF'):asset_issues.append((path,'not PDF',headers.get('Content-Type')))
fragment_issues=[]
for source,path,fragment in sorted(set(fragments)):
 target=pages.get(path)
 if target is None:
  status,_,b=fetch(path);target=BeautifulSoup(b,'html.parser')
 if not target.find(id=fragment) and not target.find('a',attrs={'name':fragment}):fragment_issues.append((source,path,fragment))
report=dict(pages=len(pages),assets=len(assets),issues=issues,asset_issues=asset_issues,fragment_issues=fragment_issues,text_diffs=text_diffs)
(args.output or runtime/'audit.json').write_text(json.dumps(report,ensure_ascii=False,indent=2))
print('\n'+json.dumps({k:v for k,v in report.items() if k!='text_diffs'},ensure_ascii=False));print('Pages with textual differences:',len(text_diffs));print(json.dumps(text_diffs[:8],ensure_ascii=False))

assert not (issues or asset_issues or fragment_issues or text_diffs), 'Migration audit found differences; inspect report'
