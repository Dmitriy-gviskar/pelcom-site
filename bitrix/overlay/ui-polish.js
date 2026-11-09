(function(){
 'use strict';
 function init(){
  var header=document.querySelector('header'),burger=header&&header.querySelector('.burger');
  var logo=header&&header.querySelector('.logo,.h-logo');
  if(logo){var mark=document.createElement('img');mark.src='/favicon.svg';mark.alt='';mark.className='lm';mark.width=28;mark.height=28;var previous=logo.querySelector('svg,img');if(previous)previous.replaceWith(mark);else logo.prepend(mark);}
  if(burger){
   burger.setAttribute('aria-expanded','false');
   var nav=header.querySelector('nav');if(nav){nav.id='pelcom-main-navigation';burger.setAttribute('aria-controls',nav.id);nav.setAttribute('aria-label','Основная навигация');}
   burger.addEventListener('click',function(){burger.setAttribute('aria-expanded',header.classList.contains('open')?'true':'false');});
   if(nav)nav.addEventListener('click',function(e){if(e.target.closest('a')){header.classList.remove('open');burger.setAttribute('aria-expanded','false');}});
  }
  document.querySelectorAll('.fab').forEach(function(fab,index){
   if(fab.classList.contains('pelcom-contact'))return;
   if(!fab.querySelector('a[href^="tel:"]')&&header){var phone=header.querySelector('a[href^="tel:"]');if(phone){var call=phone.cloneNode(true);call.className='ph';call.textContent='';fab.appendChild(call);}}
   var links=document.createElement('div');links.className='pelcom-contact__links';links.id='pelcom-contact-links-'+index;links.hidden=true;
   fab.querySelectorAll('a').forEach(function(a){var label=a.classList.contains('tg')?'Telegram':a.classList.contains('wa')?'WhatsApp':a.href.indexOf('tel:')===0?'Позвонить':a.getAttribute('aria-label');var text=document.createElement('span');text.textContent=label||'Связаться';a.appendChild(text);links.appendChild(a);});
   var toggle=document.createElement('button');toggle.type='button';toggle.className='pelcom-contact__toggle';toggle.textContent='Связаться';toggle.setAttribute('aria-expanded','false');toggle.setAttribute('aria-controls',links.id);
   function close(){links.hidden=true;toggle.setAttribute('aria-expanded','false');}
   toggle.addEventListener('click',function(){var open=links.hidden;links.hidden=!open;toggle.setAttribute('aria-expanded',String(open));});
   fab.appendChild(links);fab.appendChild(toggle);fab.classList.add('pelcom-contact');
   document.addEventListener('click',function(e){if(!fab.contains(e.target))close();});
   document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!links.hidden){close();toggle.focus();}});
  });
  document.addEventListener('keydown',function(e){if(e.key==='Escape'&&header&&header.classList.contains('open')){header.classList.remove('open');if(burger){burger.setAttribute('aria-expanded','false');burger.focus();}}});
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
})();
