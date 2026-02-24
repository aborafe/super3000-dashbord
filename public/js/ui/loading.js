'use strict';

const LOADER_BAR_ID = 'global-loading-bar';
const LOADER_OVERLAY_ID = 'global-loading-overlay';
const MIN_VISIBLE_MS = 260;
const DEFAULT_PROGRESS_START = 12;
const MAX_AUTO_PROGRESS = 92;
const DOWNLOAD_FORMATS = new Set(['csv', 'excel', 'xls', 'xlsx', 'pdf']);

let activeCount = 0;
let barElement = null;
let fillElement = null;
let overlayElement = null;
let progressValue = 0;
let autoTimer = null;
let overlayTimer = null;
let shownAt = 0;

const isDownloadLikeUrl = (url) => {
  if (!url || typeof url.pathname !== 'string') {
    return false;
  }

  const pathname = url.pathname.toLowerCase();
  if (pathname.includes('/export/') || pathname.includes('/download/')) {
    return true;
  }

  const queryFormat = (url.searchParams.get('format') || '').toLowerCase();
  if (DOWNLOAD_FORMATS.has(queryFormat)) {
    return true;
  }

  const extensionMatch = pathname.match(/\.([a-z0-9]+)$/);
  if (extensionMatch && DOWNLOAD_FORMATS.has(extensionMatch[1])) {
    return true;
  }

  return false;
};

const shouldIgnoreLink = (link) => {
  if (!link || !link.getAttribute) {
    return true;
  }

  if (link.hasAttribute('data-no-loader')) {
    return true;
  }

  if (link.target && link.target.toLowerCase() === '_blank') {
    return true;
  }

  if (link.hasAttribute('download')) {
    return true;
  }

  const href = (link.getAttribute('href') || '').trim();
  if (!href || href.startsWith('#') || href.startsWith('javascript:')) {
    return true;
  }

  try {
    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin) {
      return true;
    }

    if (isDownloadLikeUrl(url)) {
      return true;
    }

    const samePath = url.pathname === window.location.pathname;
    const sameSearch = url.search === window.location.search;
    const onlyHashChanged = samePath && sameSearch && url.hash !== window.location.hash;
    if (onlyHashChanged) {
      return true;
    }
  } catch (error) {
    return true;
  }

  return false;
};

const shouldIgnoreRequest = (input, init) => {
  const options = init || {};
  const headers = options.headers || {};
  const toLower = (value) => String(value || '').toLowerCase();

  if (headers instanceof Headers) {
    if (toLower(headers.get('X-Background-Request')) === '1') {
      return true;
    }
  } else if (typeof headers === 'object' && headers !== null) {
    for (const [key, value] of Object.entries(headers)) {
      if (toLower(key) === 'x-background-request' && toLower(value) === '1') {
        return true;
      }
    }
  }

  let url = '';
  if (typeof input === 'string') {
    url = input;
  } else if (input && typeof input.url === 'string') {
    url = input.url;
  }

  if (url.includes('/notifications/live')) {
    return true;
  }

  return false;
};

const clearTimers = () => {
  if (autoTimer) {
    window.clearInterval(autoTimer);
    autoTimer = null;
  }

  if (overlayTimer) {
    window.clearTimeout(overlayTimer);
    overlayTimer = null;
  }
};

const ensureElements = () => {
  if (barElement && fillElement && overlayElement) {
    return;
  }

  barElement = document.getElementById(LOADER_BAR_ID);
  overlayElement = document.getElementById(LOADER_OVERLAY_ID);

  if (!barElement) {
    barElement = document.createElement('div');
    barElement.id = LOADER_BAR_ID;
    barElement.setAttribute('aria-hidden', 'true');
    barElement.innerHTML = '<div class="global-loading-fill"></div>';
    document.body.appendChild(barElement);
  }

  if (!overlayElement) {
    const isArabic = String(document.documentElement.lang || '').toLowerCase().startsWith('ar');
    const loadingText = isArabic ? 'جاري التحميل...' : 'Loading...';

    overlayElement = document.createElement('div');
    overlayElement.id = LOADER_OVERLAY_ID;
    overlayElement.setAttribute('aria-hidden', 'true');
    overlayElement.innerHTML = [
      '<div class="global-loading-panel" role="status" aria-live="polite">',
      '  <div class="global-loading-orbit">',
      '    <span></span><span></span><span></span>',
      '  </div>',
      `  <div class="global-loading-text">${loadingText}</div>`,
      '</div>',
    ].join('');
    document.body.appendChild(overlayElement);
  }

  fillElement = barElement.querySelector('.global-loading-fill');
};

const setProgress = (nextValue) => {
  ensureElements();
  progressValue = Math.max(0, Math.min(100, Number(nextValue) || 0));
  if (fillElement) {
    fillElement.style.width = `${progressValue}%`;
  }
};

const startAutoProgress = () => {
  clearTimers();
  autoTimer = window.setInterval(() => {
    if (progressValue >= MAX_AUTO_PROGRESS) {
      return;
    }

    const delta = progressValue < 45 ? 7 : progressValue < 70 ? 4 : 1.5;
    setProgress(progressValue + delta);
  }, 170);
};

const showLoader = (showOverlay) => {
  ensureElements();
  shownAt = Date.now();
  setProgress(DEFAULT_PROGRESS_START);
  document.body.classList.add('has-global-loading');
  barElement.classList.add('is-active');

  if (showOverlay) {
    overlayTimer = window.setTimeout(() => {
      if (activeCount > 0) {
        overlayElement.classList.add('is-active');
      }
    }, 130);
  }

  startAutoProgress();
};

const hideLoader = (failed) => {
  const elapsed = Date.now() - shownAt;
  const waitMs = elapsed < MIN_VISIBLE_MS ? MIN_VISIBLE_MS - elapsed : 0;

  const finalize = () => {
    clearTimers();
    setProgress(0);

    if (barElement) {
      barElement.classList.remove('is-active', 'is-failed');
    }

    if (overlayElement) {
      overlayElement.classList.remove('is-active');
    }

    document.body.classList.remove('has-global-loading');
  };

  setProgress(100);
  if (failed && barElement) {
    barElement.classList.add('is-failed');
  }

  window.setTimeout(finalize, waitMs + 180);
};

const begin = (options = {}) => {
  activeCount += 1;
  if (activeCount === 1) {
    showLoader(Boolean(options.overlay));
  } else if (options.overlay && overlayElement) {
    overlayElement.classList.add('is-active');
  }
};

const end = (failed = false) => {
  if (activeCount > 0) {
    activeCount -= 1;
  }

  if (activeCount === 0) {
    hideLoader(failed);
  }
};

const bindNavigation = () => {
  document.addEventListener('click', (event) => {
    if (event.defaultPrevented) {
      return;
    }

    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
      return;
    }

    const link = event.target.closest('a[href]');
    if (shouldIgnoreLink(link)) {
      return;
    }

    begin({ overlay: true });
  }, true);

  window.addEventListener('beforeunload', () => {
    begin({ overlay: true });
  });

  window.addEventListener('pageshow', () => {
    activeCount = 0;
    hideLoader(false);
  });
};

const bindFormSubmit = () => {
  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    if (form.hasAttribute('data-no-loader')) {
      return;
    }

    const target = (form.getAttribute('target') || '').toLowerCase();
    if (target === '_blank') {
      return;
    }

    if (form.dataset.loaderSubmitted === '1') {
      return;
    }

    // These forms are handled via AJAX in dropdowns.js.
    if (form.id === 'notifications-mark-all-form' || form.classList.contains('notification-read-form')) {
      return;
    }

    form.dataset.loaderSubmitted = '1';
    begin({ overlay: true });

    // Keep UI protected from duplicate clicks while request is in progress.
    const submitButtons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
    submitButtons.forEach((button) => {
      button.setAttribute('disabled', 'disabled');
      button.classList.add('is-submitting');
    });
  }, true);
};

const patchFetch = () => {
  if (typeof window.fetch !== 'function') {
    return;
  }

  const nativeFetch = window.fetch.bind(window);
  window.fetch = async (input, init) => {
    const ignored = shouldIgnoreRequest(input, init);
    if (!ignored) {
      begin({ overlay: false });
    }

    try {
      const response = await nativeFetch(input, init);
      if (!ignored) {
        end(!response.ok && response.status >= 500);
      }
      return response;
    } catch (error) {
      if (!ignored) {
        end(true);
      }
      throw error;
    }
  };
};

const patchXHR = () => {
  if (typeof window.XMLHttpRequest !== 'function') {
    return;
  }

  const openNative = XMLHttpRequest.prototype.open;
  const sendNative = XMLHttpRequest.prototype.send;
  const setHeaderNative = XMLHttpRequest.prototype.setRequestHeader;

  XMLHttpRequest.prototype.open = function open(method, url, async, user, password) {
    this.__loaderUrl = typeof url === 'string' ? url : '';
    this.__loaderIgnore = this.__loaderUrl.includes('/notifications/live');
    this.__loaderBackground = false;
    return openNative.call(this, method, url, async, user, password);
  };

  XMLHttpRequest.prototype.setRequestHeader = function setRequestHeader(name, value) {
    if (String(name).toLowerCase() === 'x-background-request' && String(value) === '1') {
      this.__loaderBackground = true;
    }

    return setHeaderNative.call(this, name, value);
  };

  XMLHttpRequest.prototype.send = function send(body) {
    const ignored = this.__loaderIgnore || this.__loaderBackground;
    if (!ignored) {
      begin({ overlay: body instanceof FormData });

      if (this.upload && typeof this.upload.addEventListener === 'function') {
        this.upload.addEventListener('progress', (event) => {
          if (event.lengthComputable) {
            const percent = Math.round((event.loaded / event.total) * 85);
            setProgress(Math.max(progressValue, percent));
          }
        });
      }
    }

    this.addEventListener('loadend', () => {
      if (!ignored) {
        const failed = this.status >= 500 || this.status === 0;
        end(failed);
      }
    }, { once: true });

    return sendNative.call(this, body);
  };
};

export const initLoading = () => {
  bindNavigation();
  bindFormSubmit();
  patchFetch();
  patchXHR();
};
