(function () {
  var toggle = document.getElementById('bookingMenuToggle');
  var menu = document.getElementById('bookingAdminMenu');
  var layout = document.getElementById('bookingAdminLayout');
  if (!toggle || !menu || !layout) return;
  var collapsed = window.matchMedia('(max-width: 620px)').matches;
  try {
    var saved = localStorage.getItem('uppercrest-admin-menu-collapsed');
    if (saved !== null) collapsed = saved === 'true';
  } catch (error) {}
  function render() {
    menu.hidden = collapsed;
    layout.classList.toggle('menu-collapsed', collapsed);
    toggle.setAttribute('aria-expanded', String(!collapsed));
  }
  toggle.addEventListener('click', function () {
    collapsed = !collapsed;
    render();
    try { localStorage.setItem('uppercrest-admin-menu-collapsed', String(collapsed)); } catch (error) {}
  });
  render();
}());
