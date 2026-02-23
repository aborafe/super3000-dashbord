'use strict';

import { initSidebar } from './ui/sidebar.js';
import { initTheme } from './ui/theme.js';
import { initLanguage } from './ui/lang.js';
import { initDropdowns } from './ui/dropdowns.js';
import { initSfx } from './ui/sfx.js';

const bootstrapApp = () => {
  initSidebar();
  initTheme();
  initLanguage();
  initDropdowns();
  initSfx();
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', bootstrapApp, { once: true });
} else {
  bootstrapApp();
}
