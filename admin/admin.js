/* Admin helpers: confirm before destructive actions, live preview for chosen images. */
document.addEventListener('click', e => {
  const b = e.target.closest('[data-confirm]');
  if (b && !confirm(b.dataset.confirm)) e.preventDefault();
});

document.addEventListener('change', e => {
  const input = e.target;
  if (input.type !== 'file' || !input.files[0]) return;
  const prev = input.closest('.img-field')?.querySelector('.img-prev');
  if (!prev) return;
  const img = document.createElement('img');
  img.src = URL.createObjectURL(input.files[0]);
  prev.replaceChildren(img);
});
