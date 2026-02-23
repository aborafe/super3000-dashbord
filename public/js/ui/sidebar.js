'use strict';

import { storage } from '../core/storage.js';

const STORAGE_KEYS = {
  pinned: 'ui.sidebar.pinned'
};

const DESKTOP_MEDIA_QUERY = '(min-width: 1200px)';

const normalizePath = (value) => {
  const path = (value || '').split('?')[0].split('#')[0];
  if (!path) {
    return '/';
  }
  return path.length > 1 ? path.replace(/\/+$/, '') : path;
};

const isRealPageHref = (href) => {
  if (!href) {
    return false;
  }
  const trimmed = href.trim().toLowerCase();
  if (!trimmed || trimmed === '#' || trimmed.startsWith('javascript:')) {
    return false;
  }
  return true;
};

const hasNativeNavigationAnchor = (event) => {
  const anchor = event.target.closest('a');
  if (!anchor) {
    return false;
  }
  const href = (anchor.getAttribute('href') || '').trim();
  return isRealPageHref(href);
};

const addTransitionClass = (root) => {
  root.classList.add('layout-transitioning');
  window.setTimeout(() => {
    root.classList.remove('layout-transitioning');
  }, 320);
};

const setPinnedIcon = (button, pinned) => {
  if (!button) {
    return;
  }
  button.classList.toggle('is-pinned', pinned);
  const icon = button.querySelector('i');
  if (!icon) {
    return;
  }
  icon.classList.toggle('bx-lock', pinned);
  icon.classList.toggle('bx-lock-open', !pinned);
};

const setDesktopCollapsed = (root, collapsed, animate = false) => {
  if (animate) {
    addTransitionClass(root);
  }
  root.classList.toggle('layout-menu-collapsed', collapsed);
};

const closeSiblingSubmenus = (item) => {
  const list = item?.parentElement;
  if (!list) {
    return;
  }
  list.querySelectorAll(':scope > .menu-item.open').forEach((sibling) => {
    if (sibling !== item) {
      sibling.classList.remove('open');
    }
  });
};

const openParentSubmenus = (item) => {
  let current = item;
  while (current) {
    current.classList.add('open');
    const parentSub = current.closest('.menu-sub');
    if (!parentSub) {
      break;
    }
    current = parentSub.closest('.menu-item');
  }
};

const syncActiveLink = (menuEl) => {
  if (!menuEl) {
    return;
  }

  const currentPath = normalizePath(window.location.pathname);
  const allMenuItems = menuEl.querySelectorAll('.menu-item');
  allMenuItems.forEach((item) => item.classList.remove('active'));

  const links = menuEl.querySelectorAll('a.menu-link[href]');
  links.forEach((link) => {
    const href = link.getAttribute('href') || '';
    if (!isRealPageHref(href)) {
      return;
    }

    let targetPath = '';
    try {
      targetPath = normalizePath(new URL(href, window.location.origin).pathname);
    } catch (error) {
      return;
    }

    const isMatch = currentPath === targetPath || currentPath.startsWith(`${targetPath}/`);
    if (!isMatch) {
      return;
    }

    const item = link.closest('.menu-item');
    if (!item) {
      return;
    }
    item.classList.add('active');
    openParentSubmenus(item);
  });
};

const bindSubmenuToggles = (menuEl) => {
  if (!menuEl) {
    return;
  }

  menuEl.addEventListener('click', (event) => {
    if (hasNativeNavigationAnchor(event)) {
      return;
    }

    const anchor = event.target.closest('a');
    if (!anchor || !menuEl.contains(anchor)) {
      return;
    }

    const href = (anchor.getAttribute('href') || '').trim();

    // Prevent default only for submenu toggles.
    if (href !== '#' || !anchor.classList.contains('menu-toggle')) {
      return;
    }

    event.preventDefault();
    const item = anchor.closest('.menu-item');
    if (!item) {
      return;
    }

    const willOpen = !item.classList.contains('open');
    closeSiblingSubmenus(item);
    item.classList.toggle('open', willOpen);
  });
};

const bindDesktopHover = (root, menuEl, isDesktop, isPinned) => {
  menuEl.addEventListener('mouseenter', () => {
    if (!isDesktop() || isPinned()) {
      return;
    }
    setDesktopCollapsed(root, false, true);
  });

  menuEl.addEventListener('mouseleave', () => {
    if (!isDesktop() || isPinned()) {
      return;
    }
    setDesktopCollapsed(root, true, true);
  });
};

const bindMobileToggle = (root, isDesktop) => {
  const overlay = document.querySelector('[data-layout-overlay]');

  const closeMobileMenu = () => {
    root.classList.remove('layout-menu-expanded');
    overlay?.classList.remove('show');
  };

  document.querySelectorAll('[data-menu-toggle="layout"]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (isDesktop()) {
        return;
      }
      const expanded = root.classList.toggle('layout-menu-expanded');
      overlay?.classList.toggle('show', expanded);
    });
  });

  overlay?.addEventListener('click', () => {
    closeMobileMenu();
  });

  return closeMobileMenu;
};

export const initSidebar = () => {
  const root = document.documentElement;
  const menuEl = document.getElementById('layout-menu');
  const pinButton = document.querySelector('[data-menu-pin-toggle]');
  if (!root || !menuEl) {
    return;
  }

  const desktopMatch = window.matchMedia(DESKTOP_MEDIA_QUERY);
  const isDesktop = () => desktopMatch.matches;
  const isPinned = () => storage.get(STORAGE_KEYS.pinned, false) === true;
  const setPinned = (value) => storage.set(STORAGE_KEYS.pinned, value === true);

  const applyViewportState = () => {
    if (isDesktop()) {
      root.classList.remove('layout-menu-expanded');
      setDesktopCollapsed(root, !isPinned(), false);
    } else {
      root.classList.remove('layout-menu-collapsed');
      root.classList.remove('layout-menu-hover');
    }
    setPinnedIcon(pinButton, isPinned());
  };

  bindSubmenuToggles(menuEl);
  bindDesktopHover(root, menuEl, isDesktop, isPinned);
  const closeMobileMenu = bindMobileToggle(root, isDesktop);

  pinButton?.addEventListener('click', () => {
    if (!isDesktop()) {
      return;
    }
    const nextPinned = !isPinned();
    setPinned(nextPinned);
    setDesktopCollapsed(root, !nextPinned, true);
    setPinnedIcon(pinButton, nextPinned);
  });

  syncActiveLink(menuEl);
  applyViewportState();

  const handleViewportChange = () => {
    closeMobileMenu();
    applyViewportState();
  };

  if (typeof desktopMatch.addEventListener === 'function') {
    desktopMatch.addEventListener('change', handleViewportChange);
  } else if (typeof desktopMatch.addListener === 'function') {
    desktopMatch.addListener(handleViewportChange);
  }

  let resizeTimer = null;
  window.addEventListener('resize', () => {
    if (resizeTimer !== null) {
      window.clearTimeout(resizeTimer);
    }
    resizeTimer = window.setTimeout(() => {
      handleViewportChange();
      resizeTimer = null;
    }, 120);
  });
};
