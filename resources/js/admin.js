const storageKey = 'templateCustomizer';

const ready = (callback) => {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', callback, { once: true });
  } else {
    callback();
  }
};

ready(() => {
  const root = document.documentElement;
  const toggle = document.querySelector('[data-theme-toggle]');
  if (!toggle) return;

  const readTheme = () => {
    try {
      const stored = JSON.parse(localStorage.getItem(storageKey) || '{}');
      if (stored && (stored.theme === 'dark' || stored.theme === 'light')) {
        return stored.theme;
      }
    } catch (e) {
      // ignore
    }
    return null;
  };

  const writeTheme = (theme) => {
    try {
      const stored = JSON.parse(localStorage.getItem(storageKey) || '{}');
      stored.theme = theme;
      localStorage.setItem(storageKey, JSON.stringify(stored));
    } catch (e) {
      // ignore
    }
  };

  const applyTheme = (theme) => {
    root.setAttribute('data-bs-theme', theme);
    root.classList.remove('light-style', 'dark-style');
    root.classList.add(theme === 'dark' ? 'dark-style' : 'light-style');
    const icon = toggle.querySelector('i');
    if (icon) {
      icon.classList.toggle('bx-sun', theme === 'dark');
      icon.classList.toggle('bx-moon', theme !== 'dark');
    }
  };

  const initialTheme = readTheme() || root.getAttribute('data-bs-theme') || 'light';
  applyTheme(initialTheme);

  toggle.addEventListener('click', (event) => {
    event.preventDefault();
    const nextTheme = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    writeTheme(nextTheme);
    applyTheme(nextTheme);
  });
});

