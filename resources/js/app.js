import './bootstrap';
/*
  Add custom scripts here
*/
import.meta.glob([
  '../assets/img/**',
  // '../assets/json/**',
  '../assets/vendor/fonts/**'
]);

const applyVuexyCrudFieldClasses = (root = document) => {
  const wrappers = root.querySelectorAll('[bp-field-wrapper]');

  wrappers.forEach((wrapper) => {
    wrapper.classList.add('vxy-field');

    wrapper.querySelectorAll('.checkbox, .radio').forEach((group) => {
      group.classList.add('form-check', 'mb-2');
    });

    // Labels
    wrapper.querySelectorAll('label').forEach((label) => {
      const hasCheckInput = label.querySelector('input[type="checkbox"], input[type="radio"]');
      const isCheckLabel = hasCheckInput || label.closest('.form-check') || label.closest('.form-switch');
      if (isCheckLabel) {
        label.classList.add('form-check-label');
        label.classList.remove('form-label');
      } else {
        label.classList.add('form-label');
        label.classList.remove('form-check-label');
      }
      label.classList.remove('font-weight-normal', 'fw-normal');
    });

    // Hints
    wrapper.querySelectorAll('.help-block').forEach((hint) => {
      hint.classList.add('form-text', 'text-muted');
    });

    // Inputs
    wrapper
      .querySelectorAll('input, select, textarea, [bp-field-main-input]')
      .forEach((control) => {
        const hasName = control.hasAttribute('name');
        const type = (control.getAttribute('type') || '').toLowerCase();

        if (!hasName && type !== 'checkbox' && type !== 'radio') return;
        if (type === 'hidden') return;
        if (type === 'checkbox' || type === 'radio') {
          control.classList.add('form-check-input');
          control.classList.remove('form-control');
          return;
        }
        if (type === 'range') {
          control.classList.add('form-range');
          control.classList.remove('form-control');
          return;
        }
        if (control.tagName === 'SELECT') {
          control.classList.add('form-select');
          return;
        }

        control.classList.add('form-control');
      });
  });
};

document.addEventListener('DOMContentLoaded', () => {
  applyVuexyCrudFieldClasses();

  const target = document.querySelector('main') || document.body;
  if (!target || typeof MutationObserver === 'undefined') return;

  const observer = new MutationObserver((mutations) => {
    const hasAdds = mutations.some((m) => m.addedNodes && m.addedNodes.length);
    if (hasAdds) {
      applyVuexyCrudFieldClasses(target);
    }
  });

  observer.observe(target, { childList: true, subtree: true });
});
