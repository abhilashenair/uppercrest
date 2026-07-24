(function(){
  var page = window.location.pathname.split('/').pop() || 'index.html';

  var header = '<div class="topbar"><div class="container"><div>Private Homestay Near Kochi Airport (COK)</div><div><i class="fa-solid fa-location-dot" style="margin-right:5px"></i> Poikattusserry, Aluva · Near Nedumbassery &amp; Athani, Kerala</div><div>Airport Stopover | Short Stay | Long-Term Stay | Ayurveda Visitors</div></div></div><header><div class="container nav"><a class="brand" href="index.html"><img src="images/logouppercrest.png" alt="Upper Crest Homestay Near Kochi Airport" class="logo" width="240" height="80" loading="eager"></a><nav class="nav-links"><a href="index.html">Home</a><a href="gallery.html">Gallery</a><a href="facilities.html">Facilities</a><a href="location.html">Location</a><a href="about.html">About</a><a href="faq.html">FAQ</a><a href="blog.php">Blog</a></nav><div style="display:flex;gap:10px;align-items:center"><button class="btn btn-light nav-check-btn" onclick="openPanel()" style="padding:8px 16px"><i class="fa-regular fa-calendar" style="margin-right:6px"></i>Check Availability</button><a class="btn btn-primary" href="tel:+919292025275"><i class="fa-solid fa-phone" style="margin-right:6px"></i>Call +91 92920 25275</a></div></div></header>';

  var footer = '<footer><div class="container"><div class="footer-grid"><div class="footer-col"><div class="footer-brand">Upper Crest Homestay</div><p class="footer-tagline">Premium Furnished Homestay &amp; Homestay Near Kochi Airport</p></div><div class="footer-col"><div class="footer-heading">Address</div><address class="footer-address">Near Karakattuchira, Poikattusserry,<br>Aluva, Kerala 683578<br>India</address></div><div class="footer-col"><div class="footer-heading">Contact</div><a href="tel:+919292025275" class="footer-link"><i class="fa-solid fa-phone" style="margin-right:7px"></i>+91 92920 25275</a><br><a href="https://wa.me/919292025275?text=Hi%2C%20I%20am%20interested%20in%20booking%20Upper%20Crest%20Homestay%20near%20Kochi%20Airport." class="footer-link" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp" style="margin-right:7px"></i>WhatsApp Us</a></div><div class="footer-col"><div class="footer-heading">Quick Links</div><a href="gallery.html" class="footer-link">Gallery</a><br><a href="facilities.html" class="footer-link">Facilities</a><br><a href="location.html" class="footer-link">Location</a><br><a href="about.html" class="footer-link">About Us</a><br><a href="faq.html" class="footer-link">FAQ</a><br><a href="blog.php" class="footer-link">Blog</a><br><a href="house-rules.html" class="footer-link">House Rules</a><br><a href="guest-checkin.html" class="footer-link">Guest Check-in</a></div></div><div style="border-top:1px solid rgba(255,255,255,.08);padding:18px 0 0;font-size:11px;color:rgba(255,255,255,.25);line-height:2;text-align:center">Homestay Near Kochi Airport &nbsp;·&nbsp; Homestay Near Cochin Airport &nbsp;·&nbsp; Homestay Nedumbassery &nbsp;·&nbsp; Homestay Athani Kochi &nbsp;·&nbsp; Homestay Aluva Near Airport &nbsp;·&nbsp; Private Homestay Near COK Airport &nbsp;·&nbsp; Airport Stopover Accommodation Kochi &nbsp;·&nbsp; Transit Stay Kochi Airport &nbsp;·&nbsp; Family Stay Near Cochin Airport &nbsp;·&nbsp; Business Stay Near Kochi Airport</div><div class="footer-bottom">&copy; 2025 Upper Crest Homestay &nbsp;|&nbsp; Homestay Near Kochi Airport &nbsp;|&nbsp; Nedumbassery &nbsp;·&nbsp; Athani &nbsp;·&nbsp; Aluva, Kerala</div></div></footer>';

  var mobilebar = '<div class="mobile-bar"><a class="mb-call" href="tel:+919292025275"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.82a16 16 0 0 0 6.29 6.29l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>Call Now</a><a class="mb-wa" href="https://wa.me/919292025275?text=Hi%2C%20I%20am%20interested%20in%20booking%20Upper%20Crest%20Homestay%20near%20Kochi%20Airport." target="_blank" rel="noopener"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>WhatsApp</a><a class="mb-checkin" href="guest-checkin.html"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l2 2 4-4"/><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Check-in</a><button class="mb-book" onclick="openPanel()"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>Availability</button></div>';

  var wafloat = '<div class="panel-overlay" id="panelOverlay" onclick="closePanel()"></div><div class="booking-panel" id="bookingPanel"><div class="panel-header"><h3><i class="fa-regular fa-calendar" style="margin-right:8px"></i>Check Availability</h3><button class="panel-close" onclick="closePanel()">&#10005;</button></div><div class="panel-body"><div class="panel-field"><label>Check-in Date</label><input type="text" id="checkin" placeholder="Select date" readonly /></div><div class="panel-field"><label>Check-out Date</label><input type="text" id="checkout" placeholder="Select date" readonly /></div><button class="btn btn-primary" style="width:100%;padding:14px;border-radius:14px;font-size:15px" onclick="checkAvailability()">Check Availability</button><div class="panel-result" id="availResult"></div><div class="panel-guest" id="guestForm"><div class="panel-nights" id="nightsBadge"></div><div class="panel-field"><label>Your Name</label><input type="text" id="guestName" placeholder="Full name" /></div><div class="panel-field"><label>Phone / WhatsApp</label><input type="tel" id="guestPhone" placeholder="+91 XXXXX XXXXX" /></div><button class="btn btn-primary" style="width:100%;padding:14px;border-radius:14px;font-size:15px" onclick="submitBooking()">Request Booking via WhatsApp</button><p class="panel-note">&#9432; Check-in from 2:00 PM &nbsp;·&nbsp; Check-out before 11:00 AM<br>We will confirm your booking on WhatsApp.</p></div></div></div><div class="wa-float" id="waFloat"><button class="cal-btn" onclick="openPanel()" aria-label="Check Availability"><svg viewBox="0 0 24 24" fill="none" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" stroke="#fff"/><line x1="16" y1="2" x2="16" y2="6" stroke="#fff"/><line x1="8" y1="2" x2="8" y2="6" stroke="#fff"/><line x1="3" y1="10" x2="21" y2="10" stroke="#fff"/></svg></button><div class="wa-bubble" id="waBubble"><button class="wa-close" onclick="document.getElementById(\'waBubble\').style.display=\'none\'" aria-label="Close">&#10005;</button><strong style="display:block;margin-bottom:6px;color:var(--dark)"><i class="fa-brands fa-whatsapp" style="margin-right:6px;color:#25d366"></i>How can I help you?</strong>Looking for a stay near Kochi Airport?<br>Chat with us on WhatsApp!</div><a class="wa-btn" href="https://wa.me/919292025275?text=Hi%2C%20I%20am%20interested%20in%20booking%20Upper%20Crest%20Homestay%20near%20Kochi%20Airport." target="_blank" rel="noopener" aria-label="Chat on WhatsApp"><svg viewBox="0 0 32 32" fill="none"><circle cx="16" cy="16" r="16" fill="#25d366"/><path d="M23.5 8.5A10.43 10.43 0 0 0 16 5.5C10.75 5.5 6.5 9.75 6.5 15a9.44 9.44 0 0 0 1.35 4.92L6.5 26.5l6.77-1.33A9.5 9.5 0 0 0 16 25.5c5.25 0 9.5-4.25 9.5-9.5a9.43 9.43 0 0 0-2-6Z" fill="#fff"/><path d="M21.6 18.4c-.28-.14-1.64-.81-1.9-.9-.26-.09-.44-.14-.63.14-.18.28-.72.9-.88 1.08-.16.18-.33.2-.6.07-.28-.14-1.18-.44-2.24-1.39-.83-.74-1.38-1.65-1.55-1.93-.16-.28-.02-.43.12-.57.13-.12.28-.32.42-.48.14-.16.18-.28.28-.46.09-.18.05-.34-.02-.48-.07-.14-.63-1.52-.87-2.08-.23-.54-.46-.47-.63-.48-.16-.01-.35-.01-.54-.01-.18 0-.48.07-.73.34-.25.28-.97.95-.97 2.3s.99 2.67 1.13 2.86c.14.18 1.95 2.97 4.72 4.16.66.28 1.18.45 1.58.57.66.21 1.26.18 1.74.11.53-.08 1.64-.67 1.87-1.32.23-.65.23-1.2.16-1.32-.07-.11-.25-.18-.53-.32Z" fill="#25d366"/></svg></a></div>';

  var lb = '<div id="lb-overlay" role="dialog" aria-modal="true"><button id="lb-close" aria-label="Close">&times;</button><button id="lb-prev" class="lb-nav" aria-label="Previous image">&#10094;</button><img id="lb-img" src="" alt=""><button id="lb-next" class="lb-nav" aria-label="Next image">&#10095;</button><div id="lb-count" aria-live="polite"></div></div>';

  var checkinFloat = '<a class="checkin-float" href="guest-checkin.html" aria-label="Guest Check-in"><i class="fa-solid fa-clipboard-check"></i><span>Guest Check-in</span></a>';

  document.write(header + mobilebar + checkinFloat + wafloat + lb);

  window.addEventListener('DOMContentLoaded', function(){
    // Set active nav link
    var links = document.querySelectorAll('.nav-links a');
    links.forEach(function(l){
      if(l.getAttribute('href') === page) l.classList.add('active');
    });

    // Inject footer before </body>
    document.body.insertAdjacentHTML('beforeend', footer);

    // WA bubble hide after 8s
    setTimeout(function(){
      var b = document.getElementById('waBubble');
      if(b) b.style.display='none';
    }, 8000);

    // Lightbox
    var overlay=document.getElementById('lb-overlay'),lbImg=document.getElementById('lb-img'),lbClose=document.getElementById('lb-close'),lbPrev=document.getElementById('lb-prev'),lbNext=document.getElementById('lb-next'),lbCount=document.getElementById('lb-count');
    if(overlay){
      var galleryItems=Array.prototype.slice.call(document.querySelectorAll('.gallery-grid a'));
      var currentIndex=0;

      function showImage(index){
        if(!galleryItems.length) return;
        currentIndex=(index+galleryItems.length)%galleryItems.length;
        var item=galleryItems[currentIndex];
        var img=item.querySelector('img');
        lbImg.src=item.href;
        lbImg.alt=img ? img.alt : '';
        lbCount.textContent=(currentIndex+1)+' / '+galleryItems.length;
      }

      galleryItems.forEach(function(a,index){
        a.addEventListener('click',function(e){
          e.preventDefault();
          showImage(index);
          overlay.classList.add('open');document.body.style.overflow='hidden';
        });
      });
      function closeLb(){overlay.classList.remove('open');lbImg.src='';document.body.style.overflow='';}
      function prevImage(){showImage(currentIndex-1);}
      function nextImage(){showImage(currentIndex+1);}
      lbClose.addEventListener('click',closeLb);
      lbPrev.addEventListener('click',function(e){e.stopPropagation();prevImage();});
      lbNext.addEventListener('click',function(e){e.stopPropagation();nextImage();});
      overlay.addEventListener('click',function(e){if(e.target===overlay)closeLb();});
      document.addEventListener('keydown',function(e){
        if(!overlay.classList.contains('open')) return;
        if(e.key==='Escape') closeLb();
        if(e.key==='ArrowLeft') prevImage();
        if(e.key==='ArrowRight') nextImage();
      });
    }
  });
})();
