const SCRIPT_URL = "booking-engine-api.php";
var bookedRanges = [];
var currentBookingId = "";
var currentBookingKey = "";

fetch(SCRIPT_URL+"?action=booked-dates")
  .then(function(r){return r.json();})
  .then(function(data){
    var rows=data.bookedDates||data.bookings||[];
    bookedRanges=normalizeBookings(rows,!!data.bookedDates&&!data.bookings);
    initPickers();
  })
  .catch(function(){initPickers();});

loadPricingTable();

function loadPricingTable(){
  var wrap=document.getElementById("dynamicPricing");
  if(!wrap) return;
  fetch(SCRIPT_URL+"?action=rate-plan")
    .then(function(r){return r.json();})
    .then(function(data){
      if(!data.ok||!Array.isArray(data.plans)||!data.plans.length) return;
      renderPricingTable(data);
    })
    .catch(function(){});
}

function renderPricingTable(data){
  var wrap=document.getElementById("dynamicPricing");
  if(!wrap) return;
  var currency=data.currency||"INR";
  var rackRate=Number(data.rack_rate||4500);
  var visibleNights={1:true,2:true,7:true};
  var plans=data.plans.slice().filter(function(plan){
    return visibleNights[Number(plan.minimum_nights||1)];
  }).sort(function(a,b){return Number(a.minimum_nights)-Number(b.minimum_nights);});
  wrap.innerHTML=plans.map(function(plan){
    var nights=Number(plan.minimum_nights||1);
    var rate=Number(plan.nightly_rate||0);
    var total=Number(plan.total||nights*rate);
    var oldTotal=nights*rackRate;
    var save=Math.max(0,oldTotal-total);
    var discount=Number(plan.discount_percent||0);
    var popular=nights===7 ? " popular" : "";
    var badge=nights===7 ? '<div class="price-badge">Best Value</div>' : "";
    var label=nights===1 ? "Per Night" : nights+"+ Nights";
    var per=nights===1 ? "offer price per night" : "daily rate for "+nights+"+ night stays";
    var totalLine=nights===1 ? "Book single-night stays" : "Example "+nights+" nights: "+currency+" "+formatMoney(total);
    var saveLine=save ? "Save "+currency+" "+formatMoney(save)+" vs rack rate" : "Best available rate";
    var discountLine=discount ? '<li>'+formatPercent(discount)+'% stay discount applied</li>' : '';
    return '<div class="price-card'+popular+'">'+badge+
      '<div class="price-label">'+label+'</div>'+
      '<div class="price-old"><sup>₹</sup>'+formatMoney(oldTotal/nights)+'</div>'+
      '<div class="price-amount"><sup>₹</sup>'+formatMoney(rate)+'</div>'+
      '<div class="price-per">'+per+'</div>'+
      '<ul class="price-features">'+
      '<li>'+totalLine+'</li>'+
      discountLine+
      '<li>'+saveLine+'</li>'+
      '<li>Full apartment access</li>'+
      '<li>All amenities included</li>'+
      '</ul>'+
      '<button class="btn btn-primary" style="width:100%" onclick="openPanel()"><i class="fa-solid fa-calendar-check" style="margin-right:6px"></i>Reserve Your Dates</button>'+
      '</div>';
  }).join("");
}

function formatMoney(value){
  value=Number(value||0);
  return value.toLocaleString("en-IN",{maximumFractionDigits:0});
}

function formatPercent(value){
  value=Number(value||0);
  return value.toLocaleString("en-IN",{maximumFractionDigits:2});
}

function normalizeBookings(rows,assumeConfirmed){
  return rows.map(function(row){
    var status=String(getValue(row,["status","Status","booking_status","Booking Status"])||"").trim().toLowerCase();
    if(!status&&assumeConfirmed) status="confirmed";
    var from=getValue(row,["check_in","checkIn","from_date","fromDate","from date","From Date","start_date","startDate"]);
    var to=getValue(row,["check_out","checkOut","to_date","toDate","to date","To Date","end_date","endDate"]);
    if(status!=="confirmed") return null;
    if(!from||!to) return null;
    return {check_in:toIsoDate(from),check_out:toIsoDate(to),status:status||"confirmed"};
  }).filter(function(row){return row&&row.check_in&&row.check_out;});
}

function getValue(row,names){
  for(var i=0;i<names.length;i++){
    if(row[names[i]]!==undefined&&row[names[i]]!==null&&row[names[i]]!=="") return row[names[i]];
  }
  return "";
}

function toIsoDate(value){
  if(value instanceof Date) return value.toISOString().split("T")[0];
  var s=String(value).trim();
  if(/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;
  var m=s.match(/^(\d{1,2})[\/-](\d{1,2})[\/-](\d{4})$/);
  if(m) return m[3]+"-"+String(m[2]).padStart(2,"0")+"-"+String(m[1]).padStart(2,"0");
  var d=new Date(s);
  return isNaN(d.getTime())?"":d.toISOString().split("T")[0];
}

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
  fetch(SCRIPT_URL+"?action=availability&check_in="+encodeURIComponent(ci)+"&check_out="+encodeURIComponent(co))
    .then(function(r){return r.json();})
    .then(function(data){
      avail=!!data.available;
      if(avail){
        var bookingId=getBookingId(ci,co);
        var nightlyRate=Number(data.base_rate||data.tier_rate||0);
        var discount=Number(data.discount_percent||0);
        var rateText=nightlyRate ? " Rate: "+(data.currency||"INR")+" "+nightlyRate.toLocaleString()+" per night." : "";
        var discountText=discount ? " "+formatPercent(discount)+"% stay discount applied." : "";
        res.className="panel-result available";
        res.innerHTML="&#10003; Available! "+nights+" night"+(nights>1?"s":"")+" from "+formatDate(ci)+" to "+formatDate(co)+"."+rateText+discountText+" Total: "+(data.currency||"INR")+" "+(data.total||"").toLocaleString()+". Fill in your details below to request a booking.";
        res.style.display="block";
        document.getElementById("nightsBadge").textContent=nights+" Night"+(nights>1?"s":"")+"  ·  Check-in "+formatDate(ci)+"  ·  Check-out "+formatDate(co);
        document.getElementById("guestForm").style.display="block";
      } else {
        res.className="panel-result unavailable";
        res.innerHTML="&#10007; Sorry, those dates are not available. Please try different dates.";
        res.style.display="block";
        document.getElementById("guestForm").style.display="none";
      }
    })
    .catch(function(){
      res.className=avail?"panel-result available":"panel-result unavailable";
      res.innerHTML=avail?"&#10003; Available. Fill in your details below to request a booking.":"&#10007; Sorry, those dates are not available.";
      res.style.display="block";
      document.getElementById("guestForm").style.display=avail?"block":"none";
    });
}
function submitBooking(){
  var name=document.getElementById("guestName").value.trim(),phone=document.getElementById("guestPhone").value.trim();
  var ci=document.getElementById("checkin").value,co=document.getElementById("checkout").value;
  if(!name||!phone){alert("Please enter your name and phone number.");return;}
  var nights=Math.round((new Date(co)-new Date(ci))/(1000*60*60*24));
  var bookingId=getBookingId(ci,co);
  saveBookingEnquiry({booking_id:bookingId,name:name,phone:phone,check_in:ci,check_out:co,status:"Pending"});
  var msg=encodeURIComponent("🏡 *New Booking Request — Upper Crest*\n\n🆔 Booking ID: "+bookingId+"\n👤 Name: "+name+"\n📞 Phone: "+phone+"\n📅 Check-in: "+formatDate(ci)+" (2:00 PM)\n📅 Check-out: "+formatDate(co)+" (before 11:00 AM)\n🌙 Nights: "+nights+"\n\nPlease confirm this booking.");
  window.open("https://wa.me/919292025275?text="+msg,"_blank");
}
function getBookingId(ci,co){
  var key=ci+"|"+co;
  if(!currentBookingId||currentBookingKey!==key){
    currentBookingId=createBookingId();
    currentBookingKey=key;
  }
  return currentBookingId;
}
function saveBookingEnquiry(data){
  fetch(SCRIPT_URL,{
    method:"POST",
    headers:{"Content-Type":"application/json"},
    body:JSON.stringify({
      booking_id:data.booking_id,
      name:data.name,
      phone:data.phone,
      check_in:data.check_in,
      check_out:data.check_out,
      status:data.status||"pending",
      source:"website"
    })
  }).catch(function(){});
}
function createBookingId(){
  var d=new Date();
  var stamp=d.getFullYear()+String(d.getMonth()+1).padStart(2,"0")+String(d.getDate()).padStart(2,"0")+String(d.getHours()).padStart(2,"0")+String(d.getMinutes()).padStart(2,"0")+String(d.getSeconds()).padStart(2,"0");
  var random=Math.random().toString(36).slice(2,6).toUpperCase();
  return "UC-"+stamp+"-"+random;
}
function formatSheetDate(s){
  var parts=s.split("-");
  return parts.length===3 ? parts[2]+"/"+parts[1]+"/"+parts[0] : s;
}
function formatDate(s){return new Date(s).toLocaleDateString("en-IN",{day:"numeric",month:"short",year:"numeric"});}

loadGoogleReviews();

function loadGoogleReviews(){
  var grid=document.getElementById("googleReviewsGrid");
  if(!grid) return;
  fetch("google-reviews.php")
    .then(function(r){return r.json();})
    .then(function(data){
      if(!data.ok) throw new Error(data.error||"Google reviews unavailable");
      renderGoogleReviews(data);
    })
    .catch(function(){
      grid.innerHTML='<div class="review-card"><div class="review-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div><p class="review-text">Google reviews are currently available on our Google Business Profile.</p><div class="review-author"><div class="review-avatar">G</div><div><div class="review-name">The Upper Crest</div><div class="review-meta">Google Reviews</div></div></div></div>';
    });
}

function renderGoogleReviews(data){
  var grid=document.getElementById("googleReviewsGrid");
  var summary=document.getElementById("googleReviewSummary");
  var ratingValue=document.getElementById("googleRatingValue");
  var link=document.getElementById("googleReviewsLink");
  var writeLink=document.getElementById("googleWriteReviewLink");
  var reviews=(data.reviews||[]).filter(function(review){return Number(review.rating)===5;}).slice(0,3);
  if(link&&data.googleMapsUrl) link.href=data.googleMapsUrl;
  if(writeLink&&data.googleMapsUrl) writeLink.href=data.googleMapsUrl;
  if(ratingValue&&data.rating) ratingValue.textContent=Number(data.rating).toFixed(1);
  if(summary){
    summary.textContent=data.userRatingCount ? data.userRatingCount+" reviews" : "Google Reviews";
  }
  if(!reviews.length){
    grid.innerHTML='<div class="review-card"><div class="review-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div><p class="review-text">Visit our Google Business Profile to read guest reviews for The Upper Crest.</p><div class="review-author"><div class="review-avatar">G</div><div><div class="review-name">The Upper Crest</div><div class="posted-google"><span>Posted on</span><span class="google-logo-mark">G</span><span>Google</span></div></div></div></div>';
    return;
  }
  grid.innerHTML=reviews.map(function(review){
    var name=escapeHtml(review.author_name||"Google reviewer");
    var initial=escapeHtml(name.charAt(0).toUpperCase()||"G");
    var time=escapeHtml(review.relative_time_description||"Google Review");
    var text=escapeHtml(review.text||"Rated 5 stars on Google.");
    var authorUrl=review.author_url ? String(review.author_url) : "";
    var nameHtml=authorUrl ? '<a href="'+escapeAttribute(authorUrl)+'" target="_blank" rel="noopener">'+name+'</a>' : name;
    return '<div class="review-card"><div class="review-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</div><p class="review-text">"'+text+'"</p><div class="review-more">Read more</div><div class="review-author"><div class="review-avatar">'+initial+'</div><div><div class="review-name">'+nameHtml+'</div><div class="review-meta">'+time+'</div><div class="posted-google"><span>Posted on</span><span class="google-logo-mark">G</span><span>Google</span></div></div></div></div>';
  }).join("");
}

function escapeHtml(value){
  return String(value).replace(/[&<>"']/g,function(ch){
    return {"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[ch];
  });
}

function escapeAttribute(value){
  return escapeHtml(value).replace(/`/g,"&#96;");
}