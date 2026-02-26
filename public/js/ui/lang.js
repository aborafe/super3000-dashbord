'use strict';

import { storage } from '../core/storage.js';

const STORAGE_KEY = 'ui.lang.code';

const normalizeLang = (value) => (value === 'ar' ? 'ar' : 'en');

const isRealNavigationHref = (href) => {
  if (!href) {
    return false;
  }
  const value = href.trim().toLowerCase();
  if (!value || value === '#' || value.startsWith('javascript:')) {
    return false;
  }
  return true;
};

const applyDirection = (root, lang) => {
  root.setAttribute('lang', lang);
  root.setAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr');
};

const updateLangMenuState = (lang) => {
  document.querySelectorAll('[data-lang], [data-locale]').forEach((item) => {
    const value = normalizeLang(item.getAttribute('data-lang') || item.getAttribute('data-locale'));
    item.classList.toggle('active', value === lang);
  });
};

const applyTranslations = (dictionary) => {
  if (!dictionary || typeof dictionary !== 'object') {
    return;
  }

  document.querySelectorAll('[data-i18n]').forEach((element) => {
    const key = element.getAttribute('data-i18n');
    if (!key || !Object.prototype.hasOwnProperty.call(dictionary, key)) {
      return;
    }
    element.textContent = dictionary[key];
  });

  document.querySelectorAll('[data-i18n-attr]').forEach((element) => {
    const rules = (element.getAttribute('data-i18n-attr') || '').split(',');
    rules.forEach((rawRule) => {
      const rule = rawRule.trim();
      if (!rule) {
        return;
      }
      const separatorIndex = rule.indexOf(':');
      if (separatorIndex <= 0) {
        return;
      }
      const attr = rule.slice(0, separatorIndex).trim();
      const key = rule.slice(separatorIndex + 1).trim();
      if (!attr || !key || !Object.prototype.hasOwnProperty.call(dictionary, key)) {
        return;
      }
      element.setAttribute(attr, dictionary[key]);
    });
  });
};

const loadDictionary = async (lang, path) => {
  try {
    const response = await fetch(`${path}/${lang}.json`, { cache: 'no-cache' });
    if (!response.ok) {
      return {};
    }
    return await response.json();
  } catch (error) {
    return {};
  }
};

export const initLanguage = () => {
  const root = document.documentElement;
  const i18nPath = root.getAttribute('data-i18n-path') || '/i18n';
  const initial = normalizeLang(root.getAttribute('lang') || 'ar');

  const applyLocalLanguage = async (lang) => {
    const next = normalizeLang(lang);
    storage.set(STORAGE_KEY, next);
    applyDirection(root, next);
    updateLangMenuState(next);
    const dictionary = await loadDictionary(next, i18nPath);
    applyTranslations(dictionary);
  };

  void applyLocalLanguage(initial);

  document.querySelectorAll('[data-lang], [data-locale]').forEach((item) => {
    item.addEventListener('click', (event) => {
      const anchor = event.target.closest('a');
      if (anchor) {
        const href = (anchor.getAttribute('href') || '').trim();
        if (isRealNavigationHref(href)) {
          return;
        }
      }

      const next = normalizeLang(item.getAttribute('data-lang') || item.getAttribute('data-locale'));
      storage.set(STORAGE_KEY, next);
      event.preventDefault();
      void applyLocalLanguage(next);
    });
  });
};
