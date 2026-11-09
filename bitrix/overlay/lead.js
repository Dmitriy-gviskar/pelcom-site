/* Pelcom: отправка заявок через серверный обработчик сайта. */
(function(){
  "use strict";
  var LEAD_ENDPOINT = "/local/tools/pelcom_lead.php";

  function sendLead(payload){
    if(!LEAD_ENDPOINT){
      console.warn("[lead] Канал отправки заявок не настроен");
      return Promise.reject(new Error("Lead delivery is not configured"));
    }
    return fetch(LEAD_ENDPOINT,{
      method:"POST",
      headers:{"Content-Type":"application/json"},
      body:JSON.stringify(payload)
    }).then(function(r){ return r.json().then(function(data){ if(!r.ok || !data.ok) throw new Error(data.message || "Не удалось отправить заявку"); }); });
  }

  function collect(f){
    var d={};
    new FormData(f).forEach(function(v,k){ d[k]=v; });
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
    if(f.dataset.pending) return;
    var hp=f.querySelector('input[name="_gotcha"]');
    if(hp && hp.value) return;                       // honeypot: бот заполнил — молча игнор
    var phone=f.querySelector('[name="phone"]'), name=f.querySelector('[name="name"]');
    if(phone) phone.setCustomValidity(phone.value.replace(/\D/g,"").length<7 ? "Укажите телефон: не менее 7 цифр" : "");
    if(name) name.setCustomValidity(!name.value.trim() ? "Укажите имя" : "");
    if(!f.checkValidity()){ var invalid=f.querySelector(":invalid"); showErr(f,invalid.validationMessage); f.reportValidity(); return; }
    var btn=f.querySelector('[type="submit"]'), label=btn?btn.textContent:"";
    f.dataset.pending="1";
    if(btn) btn.dataset.originalLabel=label;
    if(btn){ btn.disabled=true; btn.textContent="Отправляем…"; }
    var err=f.querySelector(".form-err"); if(err) err.hidden=true;
    sendLead(collect(f)).then(function(){
      var ok=document.getElementById(f.getAttribute("data-ok"));
      f.style.display="none";
      if(ok){ ok.hidden=false; ok.style.display="block"; ok.scrollIntoView({behavior:"smooth",block:"center"}); }
    }).catch(function(error){
      delete f.dataset.pending;
      if(btn){ btn.disabled=false; btn.textContent=label; }
      showErr(f,error.message === "Failed to fetch" ? "Нет связи с сервером. Проверьте интернет и попробуйте снова." : error.message);
    });
  }

  function init(){
    document.addEventListener("click",function(e){
      if(!e.target.closest('[onclick*="openLeadPopup"]'))return;
      var f=document.getElementById("lpForm");
      if(!f || f.style.display!=="none")return;
      f.reset();delete f.dataset.pending;f.style.display="";
      var ok=document.getElementById(f.getAttribute("data-ok"));if(ok){ok.hidden=true;ok.style.display="none";}
      var btn=f.querySelector('[type="submit"]');if(btn){btn.disabled=false;btn.textContent=btn.dataset.originalLabel || "Отправить заявку";}
      var err=f.querySelector(".form-err");if(err)err.hidden=true;
    },true);
    if(!document.querySelector(".form-err-style")){
      var s=document.createElement("style");
      s.className="form-err-style";
      s.textContent=".form-err{color:#c0392b;font:600 13px/1.45 Manrope,system-ui,sans-serif;margin-top:12px}";
      document.head.appendChild(s);
    }
    document.querySelectorAll("form[data-lead]").forEach(function(f){
      var ok=document.getElementById(f.getAttribute('data-ok'));
      if(ok){ok.hidden=true;ok.style.display='none';}
      f.addEventListener("input",function(e){if(e.target.setCustomValidity)e.target.setCustomValidity("");});
      f.addEventListener("submit",submitLead);
    });
  }
  if(document.readyState==="loading") document.addEventListener("DOMContentLoaded",init);
  else init();
})();
