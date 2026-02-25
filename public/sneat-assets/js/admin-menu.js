(() => {
  const html = document.documentElement;
  const menu = document.getElementById('layout-menu');
  const toggles = document.querySelectorAll('.layout-menu-toggle');
  const pinButtons = document.querySelectorAll('[data-menu-pin]');
  const themeButtons = document.querySelectorAll('[data-theme-toggle]');
  const themeIcons = document.querySelectorAll('[data-theme-icon]');
  const STORAGE_KEY = 'sneatMenuPinned';
  const THEME_KEY = 'sneatTheme';

  if (!menu) {
    return;
  }

  const isDesktop = () => window.matchMedia('(min-width: 1200px)').matches;
  const isPinned = () => localStorage.getItem(STORAGE_KEY) === '1';
  const setPinned = (value) => {
    localStorage.setItem(STORAGE_KEY, value ? '1' : '0');
  };
  const setCollapsed = (collapsed) => {
    html.classList.toggle('layout-menu-collapsed', collapsed);
    updatePinVisibility();
  };

  const updatePinIcon = () => {
    pinButtons.forEach((btn) => {
      const icon = btn.querySelector('i');
      if (!icon) {
        return;
      }
      icon.classList.toggle('bx-lock-open-alt', !isPinned());
      icon.classList.toggle('bx-lock-alt', isPinned());
    });
  };

  const updatePinVisibility = () => {
    const isCollapsed = html.classList.contains('layout-menu-collapsed');
    pinButtons.forEach((btn) => {
      if (!isDesktop()) {
        btn.hidden = true;
        return;
      }
      btn.hidden = isCollapsed;
    });
  };

  const getTheme = () => localStorage.getItem(THEME_KEY) || 'light';

  const applyTheme = (theme) => {
    html.classList.toggle('dark-style', theme === 'dark');
    html.classList.toggle('light-style', theme !== 'dark');
    themeIcons.forEach((icon) => {
      icon.classList.toggle('bx-moon', theme !== 'dark');
      icon.classList.toggle('bx-sun', theme === 'dark');
    });
  };

  const toggleTheme = () => {
    const next = getTheme() === 'dark' ? 'light' : 'dark';
    localStorage.setItem(THEME_KEY, next);
    applyTheme(next);
  };

  const applyPinnedState = () => {
    if (!isDesktop()) {
      updatePinVisibility();
      return;
    }
    setCollapsed(!isPinned());
    updatePinIcon();
  };

  menu.addEventListener('mouseenter', () => {
    if (!isDesktop() || isPinned()) {
      return;
    }
    html.classList.add('layout-menu-hover');
    setCollapsed(false);
  });

  menu.addEventListener('mouseleave', () => {
    if (!isDesktop() || isPinned()) {
      return;
    }
    html.classList.remove('layout-menu-hover');
    setCollapsed(true);
  });

  toggles.forEach((toggle) => {
    toggle.addEventListener('click', (event) => {
      if (!isDesktop()) {
        return;
      }
      event.preventDefault();
      const nextPinned = !isPinned();
      setPinned(nextPinned);
      html.classList.remove('layout-menu-hover');
      setCollapsed(!nextPinned);
      updatePinIcon();
    });
  });

  pinButtons.forEach((btn) => {
    btn.addEventListener('click', (event) => {
      if (!isDesktop()) {
        return;
      }
      event.preventDefault();
      const nextPinned = !isPinned();
      setPinned(nextPinned);
      html.classList.remove('layout-menu-hover');
      setCollapsed(!nextPinned);
      updatePinIcon();
    });
  });

  themeButtons.forEach((btn) => {
    btn.addEventListener('click', (event) => {
      event.preventDefault();
      toggleTheme();
    });
  });

  window.addEventListener('resize', applyPinnedState);
  applyPinnedState();
  applyTheme(getTheme());
})();
