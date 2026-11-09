#!/usr/bin/env python3
"""Generate all Pelcom routes, editable pages and catalog data from static originals."""
import argparse, hashlib, html, json, re, shutil
from pathlib import Path
from bs4 import BeautifulSoup
p=argparse.ArgumentParser();p.add_argument('--source',type=Path,required=True);p.add_argument('--out',type=Path,required=True);p.add_argument('--overwrite-content',action='store_true');p.add_argument('--origin');p.add_argument('--mode',choices=['development','production'],default='development');a=p.parse_args()
config=json.loads((Path(__file__).parent/'site.json').read_text())
a.origin=(a.origin or config['production_origin' if a.mode=='production' else 'local_origin']).rstrip('/')
from urllib.parse import urlsplit
origin_parts=urlsplit(a.origin)
if origin_parts.scheme not in ['http','https'] or not origin_parts.netloc or origin_parts.path or origin_parts.query or origin_parts.fragment:raise SystemExit('Origin must be an HTTP(S) origin without path')
src,out=a.source,a.out;out.mkdir(parents=True,exist_ok=True)
files={f.name:f.read_text() for f in sorted(src.glob('*.html')) if f.name!='index.html'}
docs={f:BeautifulSoup(s,'html.parser') for f,s in files.items()}
labels={'kiosk':'Инфокиоски','panels':'Интерактивные панели','tables':'Интерактивные столы','payment':'Терминалы оплаты','terminals':'Сенсорные терминалы','outdoor':'Уличные киоски','lectern':'Интерактивные трибуны','kids':'Детское оборудование','holo':'Голографические системы','accessible':'Доступная среда','led':'Светодиодные экраны','pylon':'Видеопилоны','queue':'Электронная очередь','nav':'Навигационные системы'}
categories=[];models=[];model_files={}
def text(tag):return tag.get_text(' ',strip=True) if tag else ''
def q(s):return "'"+str(s).replace('\\','\\\\').replace("'","\\'")+"'"
def write(path,s):
 f=out/path
 if str(path).startswith('local/content/') and f.exists() and not a.overwrite_content:return
 f.parent.mkdir(parents=True,exist_ok=True);f.write_text(s)
for file,doc in docs.items():
 cards=doc.select('a.model-card')
 if not cards:continue
 slug=file.removeprefix('pelcom-').removesuffix('.html')
 category=dict(code=slug,name=labels[slug],source=file,url=f'/catalog/{slug}/',block_code='pelcom_kiosks' if slug=='kiosk' else 'pelcom_'+slug,models=[])
 for i,card in enumerate(cards):
  filename=card['href'];d=docs[filename];code=filename.removeprefix(f'pelcom-{slug}-').removesuffix('.html')
  price=re.sub(r'\D','',text(card.select_one('.model-price')))
  m=dict(code=code,category=slug,category_name=labels[slug],name=text(card.select_one('.model-name')),sort=(i+1)*10,price=int(price) if price else None,card_specs=[text(li) for li in card.select('.model-specs li')],specs=[[text(td) for td in tr.select('td')] for tr in d.select('.st tr')],description=text(d.select_one('.hero-desc')),card_image=card.img['src'] if card.img else '',featured='featured' in card.get('class',[]),source=filename,detail_image=d.select_one('.hero-svg img')['src'] if d.select_one('.hero-svg img') else '',url=f'/catalog/{slug}/{code}/')
  assert m['name'] and all(len(row)==2 for row in m['specs']),filename
  models.append(m);category['models'].append(code);model_files[filename]=m
 categories.append(category)
links={c['source']:c['url'] for c in categories};links.update({m['source']:m['url'] for m in models})
fixed={'pelcom-landing.html':'/','products.html':'/catalog/','solutions.html':'/solutions/','software.html':'/software/','contacts.html':'/contacts/','privacy.html':'/privacy/','pelcom-about.html':'/about/','pelcom-service.html':'/service/','pelcom-partners.html':'/partners/','404.html':'/404/'}
for filename in files:
 if filename in links:continue
 links[filename]=fixed.get(filename,'/'+filename.removeprefix('pelcom-').removesuffix('.html').replace('solution-','solutions/').replace('software-','software/')+'/')
links['index.html']='/'
pattern=re.compile('|'.join(re.escape(s) for s in sorted(links,key=len,reverse=True)))
def rewrite(s):
 s=s.replace("info@pelcom.ru","info@pelcom-tech.ru")
 s=pattern.sub(lambda m:links[m[0]],s)
 s=re.sub(r'https?://(?:www\.)?(?:pelcom\.ru|pelcom-tech\.ru)(?=/|[\"\'\s<>]|$)',lambda m:a.origin,s)
 s=s.replace(a.origin+'//',a.origin+'/')
 # Rewrite HTML attributes only; do not touch JavaScript assignments like img.src=.
 s=re.sub(r'(?<![\w.])((?:src|href|data-path)=[\"\'])(?!https?:|//|/|#|mailto:|tel:|javascript:|data:)([^\"\']+)',r'\1/\2',s)
 s=re.sub(r'(?<![\w.])((?:src|href)=)(?![\"\']|https?:|//|/|#|mailto:|tel:|javascript:|data:)([^\s>]+)',r'\1/\2',s)
 return s
# Stable per-label technical property codes; retain existing kiosk property codes.
old={'Диагональ':'SCREEN_SIZE','Разрешение':'RESOLUTION','Сенсор':'TOUCH','Материал':'MATERIAL','ОС':'OS','ОЗУ':'RAM','SSD':'SSD','Крепление':'MOUNT','Принтер':'PRINTER','Процессор':'CPU','Сканер QR':'QR_SCANNER'}
spec_codes={label:'SPEC_'+old.get(label,hashlib.sha1(label.encode()).hexdigest()[:12].upper()) for label in sorted({r[0] for m in models for r in m['specs']})}
route_manifest=[]
for filename,raw in files.items():
 doc=docs[filename];key=filename.removesuffix('.html');url=links[filename]
 head=re.search(r'<head\b[^>]*>(.*?)</head>',raw,re.S|re.I).group(1)
 # Let Bitrix own titles and meta; retain styles, fonts, structured data and scripts.
 head=re.sub(r'<title\b.*?</title>|<meta\b[^>]*>|<link\b[^>]*rel=[\"\']?canonical[^>]*>','',head,flags=re.S|re.I)
 header=re.search(r'<header\b.*?</header>',raw,re.S|re.I)
 footer=re.search(r'<footer\b.*?</footer>',raw,re.S|re.I)
 body=re.search(r'<body\b[^>]*>(.*?)</body>',raw,re.S|re.I).group(1)
 body=re.sub(r'<script\b[^>]*>.*?</script>',lambda m: '<script src="/lead.js" defer></script>' if 'TODO(backend)' in m[0] and 'function submitLead' in m[0] else m[0],body,flags=re.S)
 for ok_id in re.findall(r'data-ok=[\"\']([^\"\']+)',body):
  body=re.sub(r'<[^>]+\bid=[\"\']'+re.escape(ok_id)+r'[\"\'][^>]*>',lambda m: m[0][:-1]+' hidden>' if not re.search(r'\bhidden\b',m[0]) else m[0],body)
 for match in [header,footer]:
  if match:body=body.replace(match.group(),'',1)
 write(f'local/templates/pelcom/parts/{key}-head.php',rewrite(head))
 write(f'local/templates/pelcom/parts/{key}-header.php',rewrite(header.group() if header else ''))
 write(f'local/templates/pelcom/parts/{key}-footer.php',rewrite(footer.group() if footer else ''))
 category=next((c for c in categories if c['source']==filename),None)
 description=doc.find('meta',attrs={'name':'description'});description=description.get('content','') if description else ''
 if filename in model_files:
  m=model_files[filename]
  # Keep each original model layout and gallery; only replace managed data fields.
  b=BeautifulSoup(body,'html.parser')
  replacements={}
  def replace_node(node,php,contents=False):
   if not node:return
   token='PELCOMPHP'+str(len(replacements))+'END';replacements[token]=php
   if contents:node.clear();node.append(token)
   else:node.replace_with(token)
  original_h1=text(b.h1)
  suffix=original_h1[len(m['name']):] if original_h1.startswith(m['name']) else ' — '+m['category_name']
  replace_node(b.h1,"<?=htmlspecialcharsbx($item['~NAME'])?>"+html.escape(suffix),True)
  replace_node(b.select_one('.hero-desc'),"<?=htmlspecialcharsbx($item['~PREVIEW_TEXT'])?>",True)
  replace_node(b.select_one('.hero-price strong'),"<?=number_format((float)$p['PRICE_FROM']['VALUE'],0,',',' ')?> ₽",True)
  replace_node(b.select_one('.st'),'''<table class="st"><?php foreach ($specs as $row): ?><tr><td><?=htmlspecialcharsbx($row[0])?></td><td><?=htmlspecialcharsbx($row[1])?></td></tr><?php endforeach; ?></table>''')
  image=b.select_one('.hero-svg img')
  if image:
   image['src']='/PELCOMIMAGEPATH';replacements['/PELCOMIMAGEPATH']="<?=htmlspecialcharsbx($p['DETAIL_PHOTO']['SRC'] ?: $p['DETAIL_IMAGE']['VALUE'])?>"
   image['alt']='PELCOMIMAGENAME';replacements['PELCOMIMAGENAME']="<?=htmlspecialcharsbx($item['~NAME'])?>"
  body=rewrite(str(b))
  for token,php in replacements.items():body=body.replace(token,php)
  write(f'local/model_views/{key}.php',body)
  content=f"$APPLICATION->IncludeComponent('pelcom:kiosks', 'detail', ['CATEGORY'=>{q(m['category'])}, 'CODE'=>{q(m['code'])}]);"
 else:
  if category:
   matches=list(re.finditer(r'<a\b[^>]*class=[\"\'][^\"\']*\bmodel-card\b[^\"\']*[\"\'][^>]*>.*?</a>',body,re.S))
   assert len(matches)==len(category['models']),filename
   component=f"<?php $APPLICATION->IncludeComponent('pelcom:kiosks', '', ['CATEGORY'=>{q(category['code'])},'WRAP'=>'N']); ?>"
   for match in reversed(matches):
    body=body[:match.start()]+(component if match==matches[0] else '')+body[match.end():]
  write(f'local/content/{key}.php',rewrite(body))
  content=f"$APPLICATION->IncludeComponent('bitrix:main.include', '', ['AREA_FILE_SHOW'=>'file','PATH'=>'/local/content/{key}.php','EDIT_TEMPLATE'=>'']);"
 status="http_response_code(404);\n" if filename=='404.html' else ''
 if filename in model_files:status+="$pelcomModel = true;\n"
 entry=f'''<?php
{status}$pelcomPage = {q(key)};
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php';
$APPLICATION->SetTitle({q(text(doc.title))});
$APPLICATION->SetPageProperty('description', {q(description)});
{content}
require $_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php';
'''
 if filename in model_files: entry="<?php require $_SERVER['DOCUMENT_ROOT'].'/local/tools/pelcom_route.php';\n"
 write('index.php' if url=='/' else url.strip('/')+'/index.php',entry)
 route_manifest.append(dict(source=filename,url=url,title=text(doc.title),kind='model' if filename in model_files else 'category' if category else 'page',category=category['code'] if category else model_files.get(filename,{}).get('category')))
 # Old URLs are server-side redirects, not static duplicates.
 write(filename,'<!doctype html><meta http-equiv="refresh" content="0;url='+url+'"><a href="'+url+'">Перейти на страницу</a>')
for name in ['img','specs']:shutil.copytree(src/name,out/name,dirs_exist_ok=True)
for f in src.iterdir():
 if f.is_file() and f.suffix in ['.css','.js','.svg','.png']:shutil.copy2(f,out/f.name)
lead=(out/'lead.js').read_text().replace('return Promise.resolve();','return Promise.reject(new Error("Lead delivery is not configured"));');write('lead.js',lead)
manifest=dict(categories=categories,models=models,spec_codes=spec_codes,routes=route_manifest,redirects=links)
write('local/migrations/catalog.json',json.dumps(manifest,ensure_ascii=False,indent=2))
write('local/migrations/kiosks.json',json.dumps([m for m in models if m['category']=='kiosk'],ensure_ascii=False,indent=2))
write('local/config/pelcom.php', '<?php\nreturn [\n'+"    'origin'=>"+q(a.origin)+",\n    'production_origin'=>"+q(config['production_origin'])+",\n    'indexing'=>"+('true' if a.mode=='production' else 'false')+",\n];\n")
write('robots.txt', ('User-agent: *\nAllow: /\nDisallow: /bitrix/\nDisallow: /local/\nDisallow: /upload/tmp/\nSitemap: '+a.origin+'/sitemap.xml\n') if a.mode=='production' else 'User-agent: *\nDisallow: /\n')
from xml.etree.ElementTree import Element,SubElement,tostring
sitemap=Element('urlset',xmlns='http://www.sitemaps.org/schemas/sitemap/0.9')
for route in route_manifest:
 if route['source']!='404.html':SubElement(SubElement(sitemap,'url'),'loc').text=a.origin.rstrip('/')+route['url']
write('sitemap.xml',tostring(sitemap,encoding='unicode'))
canonical_rules=''
if a.mode=='production':
 canonical_rules='RewriteCond %{HTTP_HOST} !^'+re.escape(origin_parts.netloc)+'$ [NC,OR]\nRewriteCond %{SERVER_PORT} !^443$\nRewriteRule ^ '+a.origin+'%{REQUEST_URI} [R=301,L]\n'
write('.htaccess', '''Options -Indexes
DirectoryIndex index.php
ErrorDocument 404 /404/index.php
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule ^sitemap\\.xml$ /local/tools/pelcom_sitemap.php [L]
'''+canonical_rules+'''RewriteRule ^(?:index|pelcom-landing)\\.html$ / [R=301,L]
'''+''.join(f'RewriteRule ^{re.escape(old)}$ {url} [R=301,L]\n' for old,url in links.items() if old not in ['index.html','pelcom-landing.html'])+'''RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ /bitrix/urlrewrite.php [L]
</IfModule>
''')
print(f'Built {len(route_manifest)} CMS pages; {len(categories)} categories; {len(models)} models')

write('urlrewrite.php', "<?php\n$arUrlRewrite = [['CONDITION'=>'#^/#','RULE'=>'','ID'=>'','PATH'=>'/local/tools/pelcom_route.php']];\n")
