/* UWB.Learn — UI only (le backend est PHP/MySQL, design inchangé) */
(function () {
  "use strict";
  function toggleScrolled() {
    const h = document.querySelector('#header'); if (!h) return;
    if (!h.classList.contains('sticky-top') && !h.classList.contains('fixed-top')) return;
    window.scrollY > 100 ? document.body.classList.add('scrolled') : document.body.classList.remove('scrolled');
  }
  document.addEventListener('scroll', toggleScrolled); window.addEventListener('load', toggleScrolled);
  const t = document.querySelector('.mobile-nav-toggle');
  if (t) t.addEventListener('click', () => {
    document.body.classList.toggle('mobile-nav-active');
    t.classList.toggle('bi-list'); t.classList.toggle('bi-x');
  });
  document.querySelectorAll('#navmenu a').forEach(a => a.addEventListener('click', () => {
    if (document.querySelector('.mobile-nav-active')) {
      document.body.classList.remove('mobile-nav-active');
      if (t) { t.classList.add('bi-list'); t.classList.remove('bi-x'); }
    }
  }));
  const pre = document.querySelector('#preloader');
  if (pre) window.addEventListener('load', () => pre.remove());
  const st = document.querySelector('.scroll-top');
  function tst() { if (st) window.scrollY > 100 ? st.classList.add('active') : st.classList.remove('active'); }
  if (st) st.addEventListener('click', e => { e.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' }); });
  window.addEventListener('load', tst); document.addEventListener('scroll', tst);
  window.addEventListener('load', () => { if (typeof AOS !== 'undefined') AOS.init({ duration: 600, easing: 'ease-in-out', once: true, mirror: false }); });
  // Petits helpers visuels (loupe + effacer) — même comportement qu'avant
  window.uwbSearchType = function (input) {
    const f = input.closest('.search-field,.pro-input'); if (f) f.classList.toggle('has-text', (input.value || '').length > 0);
  };
})();
// Copie lien Meet
function uwbCopyLink(link, btn) {
  const done = () => { const o = btn.innerHTML; btn.innerHTML = '<i class="bi bi-check-lg"></i>'; setTimeout(() => btn.innerHTML = o, 1500); };
  if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(link).then(done).catch(() => prompt('Copiez le lien :', link));
  else prompt('Copiez le lien :', link);
}
function togglePass(id, btn) {
  const i = document.getElementById(id); const show = i.type === 'password';
  i.type = show ? 'text' : 'password';
  btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
}
