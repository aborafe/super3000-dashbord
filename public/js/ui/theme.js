'use strict';

const STORAGE_KEY = 'theme';
const THEME_CONTROL_SELECTOR = 'a[data-theme], button[data-theme]';

const getSystemTheme = () => (
  window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
);

const normalizeTheme = (value) => (value === 'dark' || value === 'light' ? value : getSystemTheme());

const applyTheme = (theme) => {
  const root = document.documentElement;
  root.setAttribute('data-theme', theme);
  root.classList.remove('light-style', 'dark-style');
  root.classList.add(theme === 'dark' ? 'dark-style' : 'light-style');
  root.style.colorScheme = theme;

  const icon = document.querySelector('[data-theme-icon]');
  if (icon) {
    icon.classList.remove('bx-sun', 'bx-moon');
    icon.classList.add(theme === 'dark' ? 'bx-moon' : 'bx-sun');
  }

  document.querySelectorAll(THEME_CONTROL_SELECTOR).forEach((item) => {
    item.classList.toggle('active', item.getAttribute('data-theme') === theme);
  });
};

const saveTheme = (theme) => {
  try {
    window.localStorage.setItem(STORAGE_KEY, theme);
  } catch (error) {
    // Ignore storage errors in private modes.
  }
};

const readTheme = () => {
  try {
    return normalizeTheme(window.localStorage.getItem(STORAGE_KEY));
  } catch (error) {
    return getSystemTheme();
  }
};

export const initTheme = () => {
  const toggle = document.getElementById('theme-toggle');
  const initialTheme = readTheme();

  applyTheme(initialTheme);
  saveTheme(initialTheme);

  if (toggle) {
    toggle.addEventListener('click', () => {
      const current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
      const nextTheme = current === 'dark' ? 'light' : 'dark';
      applyTheme(nextTheme);
      saveTheme(nextTheme);
    });
  }

  document.querySelectorAll(THEME_CONTROL_SELECTOR).forEach((item) => {
    item.addEventListener('click', (event) => {
      event.preventDefault();
      const picked = normalizeTheme(item.getAttribute('data-theme'));
      applyTheme(picked);
      saveTheme(picked);
    });
  });
};
