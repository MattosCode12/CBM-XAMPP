(() => {
  const button = document.querySelector('[data-menu]');
  const nav = document.querySelector('[data-nav]');
  if (button && nav) {
    button.addEventListener('click', () => {
      const open = nav.classList.toggle('open');
      button.setAttribute('aria-expanded', String(open));
    });
  }
  document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
      if (!window.confirm(form.dataset.confirm)) event.preventDefault();
    });
  });
  const photo = document.querySelector('input[type=file][name=photo]');
  if (photo) photo.addEventListener('change', event => {
    const file = event.target.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
      window.alert('A foto deve ter até 5 MB.');
      photo.value = '';
      return;
    }
    const preview = document.querySelector('[data-preview]');
    if (preview) preview.src = URL.createObjectURL(file);
  });
})();
