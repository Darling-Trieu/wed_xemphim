// Menu thu gọn cho màn hình hẹp (≤ 900px)
document.querySelectorAll('.nav-toggle').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var header = btn.closest('.site-header');
    var open = header.classList.toggle('is-open');
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    btn.setAttribute('aria-label', open ? 'Đóng menu' : 'Mở menu');
  });
});
