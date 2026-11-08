#!/usr/bin/env python3
"""Local Bitrix lifecycle. The runtime contains private data; never commit it."""
import argparse,json,os,shutil,socket,subprocess,time
from pathlib import Path
p=argparse.ArgumentParser();p.add_argument('action',choices=['status','start','stop','build','import']);p.add_argument('--runtime',type=Path);p.add_argument('--mode',choices=['development','production'],default='development');p.add_argument('--out',type=Path);a=p.parse_args()
here=Path(__file__).resolve().parent;repo=here.parent
config=here/'.runtime-path'
runtime=(a.runtime or (Path(config.read_text().strip()) if config.exists() else here/'.runtime')).resolve()
root=runtime/'www'
php=shutil.which('php');mysql='/usr/local/mysql/bin/mysqld'
def alive(port):
    try:
        with socket.create_connection(('127.0.0.1',port),1): return True
    except OSError:return False
if a.action=='status':
    print('Runtime:',runtime);print('PHP:',alive(18080),'MySQL:',alive(13316));raise SystemExit()
if a.action=='build':
    if a.mode=='production' and not a.out:raise SystemExit('Production build requires --out to keep local development unchanged')
    if a.out:root=a.out.resolve()
    subprocess.run(['python3',str(here/'build.py'),'--source',str(repo),'--out',str(root),'--mode',a.mode],check=True)
    shutil.copytree(here/'overlay',root,dirs_exist_ok=True,ignore=shutil.ignore_patterns('content','parts','kiosks.json','catalog.json'))
    print('Built:',root);raise SystemExit()
if a.action=='import':
    subprocess.run([php,'-n','-d','short_open_tag=1','-d','memory_limit=512M','-d','date.timezone=Europe/Moscow',str(root/'local/migrations/import.php'),str(root)],check=True);raise SystemExit()
if a.action=='stop':
    for name in ['php','mysql']:
        f=runtime/(name+'.pid')
        if f.exists():
            try:os.kill(int(f.read_text().strip()),15)
            except ProcessLookupError:pass
            f.unlink()
    for _ in range(40):
        if not alive(18080) and not alive(13316):break
        time.sleep(.25)
    print('Stopped processes recorded by this launcher');raise SystemExit()
if not (root/'bitrix/.settings.php').exists():raise SystemExit('Install Bitrix in runtime/www first; see README.md')
if not alive(13316):
    log=open(runtime/'mysql-console.log','ab')
    proc=subprocess.Popen([mysql,'--no-defaults','--datadir='+str(runtime/'mysql'),'--socket='+str(runtime/'mysql.sock'),'--port=13316','--bind-address=127.0.0.1','--mysqlx=OFF','--pid-file='+str(runtime/'mysql.pid'),'--log-error='+str(runtime/'mysql.log')],stdout=log,stderr=log,start_new_session=True)
    for _ in range(40):
        if alive(13316):break
        if proc.poll() is not None:raise SystemExit('MySQL failed: inspect mysql.log')
        time.sleep(.25)
if not alive(18080):
    log=open(runtime/'php.log','ab')
    proc=subprocess.Popen([php,'-n','-d','short_open_tag=1','-d','memory_limit=512M','-d','date.timezone=Europe/Moscow','-d','display_errors=0','-d','opcache.revalidate_freq=0','-S','127.0.0.1:18080','-t',str(root),str(here/'router.php')],stdout=log,stderr=log,start_new_session=True)
    (runtime/'php.pid').write_text(str(proc.pid))
    time.sleep(.5)
print('Home: http://127.0.0.1:18080/\nCatalog: http://127.0.0.1:18080/catalog/kiosk/\nAdmin: http://127.0.0.1:18080/bitrix/admin/')
