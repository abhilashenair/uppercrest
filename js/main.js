const SCRIPT_URL = "https://script.google.com/macros/s/AKfycbxZ0GDPcMmRdq2duuhd6wiW2PjP_A7LpaDXTWrtOJVGKCBMMyp99syPhcQyD2ia1V_QMQ/exec";
var bookedRanges = [];

fetch(SCRIPT_URL)
  .then(function(r){return r.json();})
  .then(function(data){bookedRanges=data.bookedDates||[];initPickers();})
  .catch(function(){initPickers();});

function initPickers(){
  if(typeof flatpickr==='undefined') return;
  var disabled=getDisabledDates(bookedRanges);
  var checkinPicker=flatpickr("#checkin",{minDate:"today",dateFormat:"Y-m-d",disable:disabled,onChange:function(d){if(d[0]){var n=new Date(d[0]);n.setDate(n.getDate()+1);checkoutPicker.set("minDate",n);checkoutPicker.clear();hideResult();}}});
  var checkoutPicker=flatpickr("#checkout",{minDate:"today",dateFormat:"Y-m-d",disable:disabled,onChange:function(){hideResult();}});
}

function getDisabledDates(ranges){
  var disabled=[];
  ranges.forEach(function(r){
    var s=new Date(r.check_in),e=new Date(r.check_out),c=new Date(s);
    while(c<e){disabled.push(c.toISOString().split("T")[0]);c.setDate(c.getDate()+1);}
  });
  return disabled;
}

function openPanel(){
  document.getElementById("bookingPanel").classList.add("open");
  document.getElementById("panelOverlay").classList.add("open");
  document.body.style.overflow="hidden";
}
function closePanel(){
  document.getElementById("bookingPanel").classList.remove("open");
  document.getElementById("panelOverlay").classList.remove("open");
  document.body.style.overflow="";
}
function hideResult(){
  document.getElementById("availResult").style.display="none";
  document.getElementById("guestForm").style.display="none";
}
function checkAvailability(){
  var ci=document.getElementById("checkin").value,co=document.getElementById("checkout").value,res=document.getElementById("availResult");
  if(!ci||!co){res.className="panel-result unavailable";res.textContent="Please select both check-in and check-out dates.";res.style.display="block";return;}
  var ciD=new Date(ci),coD=new Date(co),nights=Math.round((coD-ciD)/(1000*60*60*24));
  var avail=!bookedRanges.some(function(r){return ciD<new Date(r.check_out)&&coD>new Date(r.check_in);});
  if(avail){
    res.className="panel-result available";
    res.innerHTML="&#10003; Available! "+nights+" night"+(nights>1?"s":"")+" from "+formatDate(ci)+" to "+formatDate(co)+". Fill in your details below to request a booking.";
    res.style.display="block";
    document.getElementById("nightsBadge").textContent=nights+" Night"+(nights>1?"s":"")+"  ·  Check-in "+formatDate(ci)+"  ·  Check-out "+formatDate(co);
    document.getElementById("guestForm").style.display="block";
  } else {
    res.className="panel-result unavailable";
    res.innerHTML="&#10007; Sorry, those dates are not available. Please try different dates.";
    res.style.display="block";
    document.getElementById("guestForm").style.display="none";
  }
}
function submitBooking(){
  var name=document.getElementById("guestName").value.trim(),phone=document.getElementById("guestPhone").value.trim();
  var ci=document.getElementById("checkin").value,co=document.getElementById("checkout").value;
  if(!name||!phone){alert("Please enter your name and phone number.");return;}
  var nights=Math.round((new Date(co)-new Date(ci))/(1000*60*60*24));
  fetch(SCRIPT_URL,{method:"POST",body:JSON.stringify({name:name,phone:phone,check_in:ci,check_out:co})}).catch(function(){});
  var msg=encodeURIComponent("🏡 *New Booking Request — Upper Crest*\n\n👤 Name: "+name+"\n📞 Phone: "+phone+"\n📅 Check-in: "+formatDate(ci)+" (2:00 PM)\n📅 Check-out: "+formatDate(co)+" (12:00 PM)\n🌙 Nights: "+nights+"\n\nPlease confirm this booking.");
  window.open("https://wa.me/919292025275?text="+msg,"_blank");
}
function formatDate(s){return new Date(s).toLocaleDateString("en-IN",{day:"numeric",month:"short",year:"numeric"});}
