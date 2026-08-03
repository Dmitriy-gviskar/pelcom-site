from selenium import webdriver
from selenium.webdriver.common.by import By
import time, re, json, os

# BM category → Pelcom category mapping
CAT_MAP = {
    'interaktivnye-stoly': 'pelcom-tables.html',
    'sensornyj-kiosk': 'pelcom-kiosk.html',
    'ulichnye-sensornye-kioski': 'pelcom-outdoor.html',
    'terminal-samoobsluzhivaniya': 'pelcom-terminals.html',
    'interaktivnye-paneli': 'pelcom-panels.html',
    'sensornyie-terminaly': 'pelcom-terminals.html',  # merged into terminals
    'interaktivnaya-tribuna': 'pelcom-lectern.html',
    'interaktivnoe-oborudovanie-dlya-detej': 'pelcom-kids.html',
    'golograficheskie-piramidy-i-kuby': 'pelcom-holo.html',
    'videopilon': 'pelcom-pylon.html',
    'oborudovanie-dostupnaya-sreda': 'pelcom-accessible.html',
}

from selenium.webdriver.chrome.options import Options
opts = Options()
opts.add_argument('--headless=new')
opts.add_argument('--no-sandbox')
opts.add_argument('--disable-dev-shm-usage')
opts.add_argument('--disable-gpu')
driver = webdriver.Chrome(options=opts)

try:
    # Test with one category
    url = 'https://bm-technology.ru/products/sensornyj-kiosk/'
    print(f'Loading: {url}')
    driver.get(url)
    time.sleep(5)
    
    # Get all text
    body = driver.find_element(By.TAG_NAME, 'body')
    text = body.text
    
    # Save for analysis
    with open('/tmp/bm_kiosk_text.txt', 'w') as f:
        f.write(text)
    
    print(f'Got {len(text)} chars of text')
    print('First 1500 chars:')
    print(text[:1500])
    
finally:
    driver.quit()
