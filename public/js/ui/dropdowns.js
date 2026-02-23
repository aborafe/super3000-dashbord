'use strict';

const DEFAULT_POLL_INTERVAL_MS = 5000;

const escapeHtml = (value) => {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
};

const toInteger = (value, fallback) => {
  const parsed = Number.parseInt(String(value ?? ''), 10);
  return Number.isFinite(parsed) ? parsed : fallback;
};

const toBoolean = (value) => {
  const normalized = String(value ?? '').trim().toLowerCase();
  return normalized === '1' || normalized === 'true' || normalized === 'yes' || normalized === 'on';
};

const getCsrfToken = () => {
  const token = document.querySelector('meta[name="csrf-token"]');
  return token ? token.getAttribute('content') || '' : '';
};

const updateBadge = (unreadCount) => {
  const badge = document.querySelector('.badge-notifications');
  const count = document.getElementById('notifications-count');
  const safeCount = Math.max(0, Number(unreadCount) || 0);

  if (badge) {
    badge.textContent = String(safeCount);
    badge.classList.toggle('d-none', safeCount === 0);
  }

  if (count) {
    count.textContent = String(safeCount);
  }
};

const renderNotificationItem = (notification, indexUrl, csrfToken, markReadTitle) => {
  const isRead = Boolean(notification.is_read);
  const title = escapeHtml(notification.title);
  const message = escapeHtml(notification.message);
  const createdAt = escapeHtml(notification.created_at_human);
  const showUrl = escapeHtml(notification.show_url || indexUrl);
  const markReadUrl = escapeHtml(notification.mark_read_url);
  const readForm = isRead
    ? ''
    : `
      <form method="POST" action="${markReadUrl}" class="notification-read-form">
        <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
        <input type="hidden" name="_method" value="PATCH">
        <button type="submit"
          class="dropdown-notifications-read border-0 bg-transparent p-0"
          title="${escapeHtml(markReadTitle)}">
          <span class="badge rounded-pill bg-primary p-1 notification-dot"></span>
        </button>
      </form>
    `;

  return `
    <li class="list-group-item list-group-item-action dropdown-notifications-item ${isRead ? 'is-read' : ''}">
      <div class="d-flex align-items-start">
        <div class="flex-shrink-0 me-3">
          <div class="avatar">
            <span class="avatar-initial rounded-circle ${isRead ? 'bg-label-secondary' : 'bg-label-primary'}">
              <i class="icon-base bx bx-bell"></i>
            </span>
          </div>
        </div>
        <a href="${showUrl}" class="flex-grow-1 text-body text-decoration-none">
          <h6 class="mb-1">${title}</h6>
          <small class="text-muted d-block">${message}</small>
          <div class="small text-muted">${createdAt}</div>
        </a>
        <div class="flex-shrink-0 dropdown-notifications-actions ms-2">
          ${readForm}
        </div>
      </div>
    </li>
  `;
};

const renderNotifications = (listRoot, payload, csrfToken) => {
  if (!listRoot || !payload) {
    return;
  }

  const listGroup = listRoot.querySelector('ul.list-group');
  const markAllForm = document.getElementById('notifications-mark-all-form');
  const indexUrl = String(payload.urls?.index || listRoot.dataset.indexUrl || '#');
  const markAllUrl = String(payload.urls?.mark_all || '');
  const notifications = Array.isArray(payload.notifications) ? payload.notifications : [];
  const unreadCount = Math.max(0, Number(payload.unread_count) || 0);
  const emptyText = String(listRoot.dataset.emptyText || 'No notifications yet.');
  const markReadTitle = String(listRoot.dataset.markReadTitle || 'Mark as read');

  if (markAllForm && markAllUrl) {
    markAllForm.setAttribute('action', markAllUrl);
    markAllForm.classList.toggle('d-none', unreadCount === 0);
  }

  if (listGroup) {
    if (notifications.length === 0) {
      listGroup.innerHTML = `
        <li class="list-group-item py-3 text-center text-muted">
          ${escapeHtml(emptyText)}
        </li>
      `;
    } else {
      listGroup.innerHTML = notifications
        .map((notification) => renderNotificationItem(notification, indexUrl, csrfToken, markReadTitle))
        .join('');
    }
  }

  updateBadge(unreadCount);
};

const patchRequest = async (url, csrfToken) => {
  if (!url || !csrfToken) {
    return false;
  }

  const body = `_token=${encodeURIComponent(csrfToken)}&_method=PATCH`;

  try {
    const response = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrfToken,
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body,
    });

    return response.ok;
  } catch (error) {
    return false;
  }
};

const createRealtimeBridge = (listRoot, csrfToken, refreshNotifications) => {
  let connected = false;
  const noopBridge = {
    disconnect: () => {},
    isConnected: () => connected,
  };

  if (!toBoolean(listRoot.dataset.realtimeEnabled)) {
    return noopBridge;
  }

  if (typeof window === 'undefined' || typeof window.Echo !== 'function' || typeof window.Pusher !== 'function') {
    return noopBridge;
  }

  const broadcaster = String(listRoot.dataset.realtimeBroadcaster || 'reverb').trim().toLowerCase();
  const key = String(listRoot.dataset.realtimeKey || '').trim();
  const host = String(listRoot.dataset.realtimeHost || window.location.hostname).trim();
  const scheme = String(listRoot.dataset.realtimeScheme || 'http').trim().toLowerCase();
  const authEndpoint = String(listRoot.dataset.realtimeAuthEndpoint || '/broadcasting/auth').trim();
  const channelName = String(listRoot.dataset.realtimeChannel || '').trim();
  const port = toInteger(listRoot.dataset.realtimePort, scheme === 'https' ? 443 : 80);

  if (!key || !channelName) {
    return noopBridge;
  }

  if (broadcaster !== 'reverb' && broadcaster !== 'pusher') {
    return noopBridge;
  }

  try {
    const forceTLS = scheme === 'https';
    const echo = new window.Echo({
      broadcaster,
      key,
      wsHost: host,
      wsPort: port,
      wssPort: port,
      forceTLS,
      enabledTransports: ['ws', 'wss'],
      authEndpoint,
      auth: {
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest',
          Accept: 'application/json',
        },
      },
      disableStats: true,
    });

    const connection = echo?.connector?.pusher?.connection;
    if (connection && typeof connection.bind === 'function') {
      connection.bind('connected', () => {
        connected = true;
        refreshNotifications();
      });
      connection.bind('disconnected', () => {
        connected = false;
      });
      connection.bind('unavailable', () => {
        connected = false;
      });
      connection.bind('error', () => {
        connected = false;
      });
    }

    echo.private(channelName).notification(() => {
      refreshNotifications();
    });

    return {
      disconnect: () => {
        try {
          echo.leave(`private-${channelName}`);
        } catch (error) {
          // no-op
        }
        try {
          echo.disconnect();
        } catch (error) {
          // no-op
        }
      },
      isConnected: () => connected,
    };
  } catch (error) {
    return noopBridge;
  }
};

const bindNotificationsUi = () => {
  const listRoot = document.getElementById('notifications-list');
  if (!listRoot) {
    return;
  }

  const liveUrl = String(listRoot.dataset.liveUrl || '');
  const csrfToken = getCsrfToken();
  const pollInterval = Math.max(1000, toInteger(listRoot.dataset.pollIntervalMs, DEFAULT_POLL_INTERVAL_MS));
  let pending = false;

  const refreshNotifications = async () => {
    if (!liveUrl || pending) {
      return;
    }

    pending = true;

    try {
      const response = await fetch(liveUrl, {
        credentials: 'same-origin',
        cache: 'no-store',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      if (!response.ok) {
        return;
      }

      const payload = await response.json();
      if (!payload || payload.status !== true || !payload.data) {
        return;
      }

      renderNotifications(listRoot, payload.data, csrfToken);
    } catch (error) {
      // no-op: keep UI stable on transient network errors
    } finally {
      pending = false;
    }
  };

  listRoot.addEventListener('submit', async (event) => {
    const form = event.target.closest('form.notification-read-form');
    if (!form) {
      return;
    }

    event.preventDefault();
    const ok = await patchRequest(form.getAttribute('action') || '', csrfToken);
    if (ok) {
      refreshNotifications();
    }
  });

  const markAllForm = document.getElementById('notifications-mark-all-form');
  if (markAllForm) {
    markAllForm.addEventListener('submit', async (event) => {
      event.preventDefault();
      const ok = await patchRequest(markAllForm.getAttribute('action') || '', csrfToken);
      if (ok) {
        refreshNotifications();
      }
    });
  }

  const realtimeBridge = createRealtimeBridge(listRoot, csrfToken, refreshNotifications);

  if (typeof document !== 'undefined') {
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible') {
        refreshNotifications();
      }
    });
  }

  if (typeof window !== 'undefined') {
    window.addEventListener('beforeunload', () => {
      realtimeBridge.disconnect();
    });
  }

  refreshNotifications();

  window.setInterval(() => {
    if (!realtimeBridge.isConnected()) {
      refreshNotifications();
    }
  }, pollInterval);
};

export const initDropdowns = () => {
  bindNotificationsUi();
};
