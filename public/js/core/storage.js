'use strict';

const hasLocalStorage = () => {
  try {
    return typeof window !== 'undefined' && typeof window.localStorage !== 'undefined';
  } catch (error) {
    return false;
  }
};

const toJson = (value) => {
  try {
    return JSON.stringify(value);
  } catch (error) {
    return null;
  }
};

const fromJson = (value, fallback = null) => {
  try {
    return JSON.parse(value);
  } catch (error) {
    return fallback;
  }
};

export const storage = {
  get(key, fallback = null) {
    if (!hasLocalStorage()) {
      return fallback;
    }
    const raw = window.localStorage.getItem(key);
    if (raw === null) {
      return fallback;
    }
    return fromJson(raw, fallback);
  },

  set(key, value) {
    if (!hasLocalStorage()) {
      return;
    }
    const encoded = toJson(value);
    if (encoded === null) {
      return;
    }
    window.localStorage.setItem(key, encoded);
  },

  remove(key) {
    if (!hasLocalStorage()) {
      return;
    }
    window.localStorage.removeItem(key);
  }
};
