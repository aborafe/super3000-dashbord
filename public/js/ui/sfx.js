'use strict';

const STORAGE_KEY = 'sfx';
const TOGGLE_SELECTOR = '[data-sfx-toggle]';
const STATUS_SELECTOR = '[data-sfx-state]';
const SUCCESS_CLICK_SELECTOR = '[data-sfx="success"]';

let audioContext = null;

const getStoredSetting = () => {
  try {
    const value = window.localStorage.getItem(STORAGE_KEY);
    return value === 'off' ? 'off' : 'on';
  } catch (error) {
    return 'on';
  }
};

const setStoredSetting = (value) => {
  try {
    window.localStorage.setItem(STORAGE_KEY, value === 'off' ? 'off' : 'on');
  } catch (error) {
    // Ignore storage errors in restricted browser modes.
  }
};

const prefersReducedMotion = () => (
  !!window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches
);

const isEnabled = () => getStoredSetting() === 'on' && !prefersReducedMotion();
const isArabicLocale = () => document.documentElement.lang === 'ar';
const statusLabel = (enabled) => {
  if (isArabicLocale()) {
    return enabled ? 'مفعّل' : 'موقّف';
  }

  return enabled ? 'On' : 'Off';
};

const getAudioContext = () => {
  const ContextClass = window.AudioContext || window.webkitAudioContext;
  if (!ContextClass) {
    return null;
  }
  if (!audioContext) {
    audioContext = new ContextClass();
  }
  return audioContext;
};

export const playSuccessSfx = () => {
  if (!isEnabled()) {
    return;
  }

  const context = getAudioContext();
  if (!context) {
    return;
  }

  if (context.state === 'suspended') {
    context.resume().catch(() => {});
  }

  const now = context.currentTime;
  const master = context.createGain();
  master.gain.setValueAtTime(0.0001, now);
  master.gain.exponentialRampToValueAtTime(0.028, now + 0.01);
  master.gain.exponentialRampToValueAtTime(0.0001, now + 0.2);
  master.connect(context.destination);

  const toneA = context.createOscillator();
  toneA.type = 'triangle';
  toneA.frequency.setValueAtTime(720, now);
  toneA.frequency.exponentialRampToValueAtTime(980, now + 0.11);
  toneA.connect(master);

  const toneB = context.createOscillator();
  toneB.type = 'sine';
  toneB.frequency.setValueAtTime(1080, now + 0.02);
  toneB.frequency.exponentialRampToValueAtTime(880, now + 0.18);
  toneB.connect(master);

  toneA.start(now);
  toneA.stop(now + 0.2);
  toneB.start(now + 0.015);
  toneB.stop(now + 0.2);
};

const updateUiState = () => {
  const enabled = getStoredSetting() === 'on';

  document.querySelectorAll(STATUS_SELECTOR).forEach((element) => {
    element.textContent = statusLabel(enabled);
  });

  document.querySelectorAll(TOGGLE_SELECTOR).forEach((toggle) => {
    toggle.setAttribute('aria-pressed', enabled ? 'true' : 'false');
  });
};

const bindToggle = () => {
  document.querySelectorAll(TOGGLE_SELECTOR).forEach((toggle) => {
    toggle.addEventListener('click', (event) => {
      event.preventDefault();
      const next = getStoredSetting() === 'on' ? 'off' : 'on';
      setStoredSetting(next);
      updateUiState();

      if (next === 'on') {
        playSuccessSfx();
      }
    });
  });
};

const bindSuccessClicks = () => {
  document.addEventListener('click', (event) => {
    const trigger = event.target.closest(SUCCESS_CLICK_SELECTOR);
    if (!trigger) {
      return;
    }

    const isDisabled = trigger.hasAttribute('disabled') || trigger.getAttribute('aria-disabled') === 'true';
    if (isDisabled) {
      return;
    }

    playSuccessSfx();
  });
};

const playForServerFlash = () => {
  const indicator = document.querySelector('[data-flash-success="1"], .alert.alert-success');
  if (!indicator) {
    return;
  }
  window.setTimeout(() => {
    playSuccessSfx();
  }, 80);
};

const autoWireSuccessButtons = () => {
  const selector = 'button[type="submit"].btn-primary, input[type="submit"].btn-primary';

  document.querySelectorAll(selector).forEach((button) => {
    const form = button.closest('form');
    const method = (form?.getAttribute('method') || 'get').trim().toLowerCase();

    if (method === 'get' || button.hasAttribute('data-sfx-skip')) {
      return;
    }

    if (!button.getAttribute('data-sfx')) {
      button.setAttribute('data-sfx', 'success');
    }
  });
};

export const initSfx = () => {
  try {
    if (!window.localStorage.getItem(STORAGE_KEY)) {
      setStoredSetting('on');
    }
  } catch (error) {
    setStoredSetting('on');
  }

  updateUiState();
  autoWireSuccessButtons();
  bindToggle();
  bindSuccessClicks();
  playForServerFlash();
};
