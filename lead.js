/* Pelcom — обработка заявок с форм (лендинг + контакты).
   Канал доставки выбирает заказчик. Чтобы включить отправку:
   1) Formspree / serverless / своя ручка: впишите URL в LEAD_ENDPOINT — заявка уйдёт POST JSON.
   2) Telegram-бот: замените тело sendLead() запросом к api.telegram.org (sendMessage с токеном и chat_id).
   Пока LEAD_ENDPOINT пуст — форма работает в демо-режиме (валидация и UX есть, отправки нет). */
(function(){
  "use strict";
  var LEAD_ENDPOINT = ""; // ← ВПИСАТЬ URL своей ручки (POST JSON). Пока пусто = демо-режим: валидация работает, отправки нет.

  function sendLead(payload){
    if(!LEAD_ENDPOINT){
      console.warn("[lead] LEAD_ENDPOINT не задан — заявка НЕ отправлена (демо-режим):", payload);
      return Promise.resolve();
    }
    return fetch(LEAD_ENDPOINT,{
      method:"POST",
      headers:{"Content-Type":"application/json"},
      body:JSON.stringify(payload)
    }).then(function(r){ if(!r.ok) throw new Error("HTTP "+r.status); });
  }

  function collect(f){
    var d={};
    new FormData(f).forEach(function(v,k){ if(k!=="_gotcha") d[k]=v; });
    d.page=location.pathname;
    return d;
  }

  function showErr(f,msg){
    var e=f.querySelector(".form-err");
    if(!e){ e=document.createElement("div"); e.className="form-err"; e.setAttribute("role","alert"); f.appendChild(e); }
    e.textContent=msg; e.hidden=false;
  }

  function submitLead(e){
    e.preventDefault();
    var f=e.currentTarget;
    var hp=f.querySelector('input[name="_gotcha"]');
    if(hp && hp.value) return;                       // honeypot: бот заполнил — молча игнор
    if(!f.checkValidity()){ f.reportValidity(); return; }
    var btn=f.querySelector('[type="submit"]'), label=btn?btn.textContent:"";
    if(btn){ btn.disabled=true; btn.textContent="Отправляем…"; }
    var err=f.querySelector(".form-err"); if(err) err.hidden=true;
    sendLead(collect(f)).then(function(){
      var ok=document.getElementById(f.getAttribute("data-ok"));
      f.style.display="none";
      if(ok){ ok.hidden=false; ok.scrollIntoView({behavior:"smooth",block:"center"}); }
    }).catch(function(){
      if(btn){ btn.disabled=false; btn.textContent=label; }
      showErr(f,"Не удалось отправить заявку. Позвоните нам или попробуйте позже.");
    });
  }

  function init(){
    if(!document.querySelector(".form-err-style")){
      var s=document.createElement("style");
      s.className="form-err-style";
      s.textContent=".form-err{color:#c0392b;font:600 13px/1.45 Manrope,system-ui,sans-serif;margin-top:12px}";
      document.head.appendChild(s);
    }
    document.querySelectorAll("form[data-lead]").forEach(function(f){
      f.addEventListener("submit",submitLead);
    });
  }
  if(document.readyState==="loading") document.addEventListener("DOMContentLoaded",init);
  else init();
})();
