(function(){
  var deferredPrompt=null;
  var installButton=document.getElementById('installAdminApp');
  var notifyButton=document.getElementById('enableAdminNotifications');
  var statusBox=document.getElementById('adminAppStatus');
  var lastPendingCount=null;
  var alertTimer=null;

  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function(){
      navigator.serviceWorker.register('admin-service-worker.js').catch(function(){});
    });
  }

  window.addEventListener('beforeinstallprompt', function(event){
    event.preventDefault();
    deferredPrompt=event;
    if(installButton) installButton.hidden=false;
  });

  if(installButton){
    installButton.addEventListener('click', function(){
      if(!deferredPrompt) return;
      deferredPrompt.prompt();
      deferredPrompt.userChoice.finally(function(){
        deferredPrompt=null;
        installButton.hidden=true;
      });
    });
  }

  if(notifyButton){
    if(!('Notification' in window)){
      notifyButton.hidden=true;
      setStatus('This browser does not support notifications.');
    } else {
      notifyButton.addEventListener('click', function(){
        Notification.requestPermission().then(function(permission){
          if(permission==='granted'){
            notifyButton.textContent='Alerts On';
            notifyButton.disabled=true;
            startAlertChecks(true);
          } else {
            setStatus('Notifications were not allowed. You can still open the admin dashboard normally.');
          }
        });
      });
      if(Notification.permission==='granted'){
        notifyButton.textContent='Alerts On';
        notifyButton.disabled=true;
        startAlertChecks(false);
      }
    }
  }

  function startAlertChecks(showReadyNotice){
    if(alertTimer) return;
    checkAdminSummary(showReadyNotice);
    alertTimer=setInterval(function(){checkAdminSummary(false);},60000);
  }

  function checkAdminSummary(showReadyNotice){
    fetch('admin-summary.php',{credentials:'same-origin',cache:'no-store'})
      .then(function(response){return response.json();})
      .then(function(data){
        if(!data.ok){
          setStatus('Login to the admin dashboard first, then return here to enable booking checks.');
          return;
        }
        var pending=Number(data.pending_count||0);
        setStatus('Booking alerts are active. Pending reservations: '+pending+'.');
        if(lastPendingCount===null){
          lastPendingCount=pending;
          if(showReadyNotice) showNotification('Upper Crest alerts enabled','We will check for pending booking enquiries while this app is open.');
          return;
        }
        if(pending>lastPendingCount){
          showNotification('New booking enquiry','You have '+pending+' pending booking request'+(pending===1?'':'s')+'.');
        }
        lastPendingCount=pending;
      })
      .catch(function(){
        setStatus('Could not check reservations now. Keep the dashboard open and try again.');
      });
  }

  function showNotification(title,body){
    if(!('Notification' in window) || Notification.permission!=='granted') return;
    try{
      new Notification(title,{body:body,icon:'images/logouppercrest.png',badge:'images/logouppercrest.png'});
    }catch(e){}
  }

  function setStatus(message){
    if(statusBox) statusBox.textContent=message;
  }
})();
