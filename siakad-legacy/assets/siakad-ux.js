(function () {
  var current = window.location.pathname.split('/').pop().replace(/\.php$/, '') || 'dashboard';
  document.querySelectorAll('.navbar-nav a.nav-link, .navbar-nav a.dropdown-item').forEach(function (link) {
    var href = (link.getAttribute('href') || '').split('?')[0].split('/').pop().replace(/\.php$/, '');
    if (!href || href === '#' || href !== current) return;

    link.setAttribute('aria-current', 'page');
    var item = link.closest('.nav-item');
    if (item) item.classList.add('active');
  });
})();
