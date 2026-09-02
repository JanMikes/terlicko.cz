import { OrderingAction } from './components/OrderingAction';

/**
 * Guards against browser page-translation, which rewrites text nodes underneath
 * React. Once that happens React's reconciler can no longer find the nodes it
 * owns and every subsequent commit throws
 * `NotFoundError: Failed to execute 'removeChild' on 'Node'`, which takes the
 * whole admin panel down until a full reload.
 *
 * See https://github.com/strapi/strapi/issues/25544 - unresolved upstream, the
 * only causes anyone has reproduced are browser extensions and auto-translate.
 */
const preventBrowserTranslation = () => {
  document.documentElement.setAttribute('translate', 'no');
  document.documentElement.classList.add('notranslate');

  if (!document.querySelector('meta[name="google"][content="notranslate"]')) {
    const meta = document.createElement('meta');
    meta.setAttribute('name', 'google');
    meta.setAttribute('content', 'notranslate');
    document.head.appendChild(meta);
  }
};

const RECOVERY_FLAG = 'terlicko:dom-desync-recovered';

/**
 * A desynced React root never heals - the error boundary screen stays up and
 * every following click throws again. Nothing is recoverable at that point
 * (the form is already unmounted), so reload once per tab to get the editor
 * back to a working admin instead of leaving them on the crash screen.
 */
const recoverFromDomDesync = () => {
  window.addEventListener('error', (event) => {
    const error = event.error;

    if (!(error instanceof DOMException) || error.name !== 'NotFoundError') {
      return;
    }

    // Browsers localise the message, but the DOM method name stays in English.
    if (!/removeChild|insertBefore|replaceChild/.test(error.message)) {
      return;
    }

    console.error('[terlicko] React DOM desync - the page DOM was modified outside React.', {
      message: error.message,
      url: window.location.href,
      userAgent: navigator.userAgent,
      language: navigator.language,
    });

    if (window.sessionStorage.getItem(RECOVERY_FLAG)) {
      return;
    }

    window.sessionStorage.setItem(RECOVERY_FLAG, String(Date.now()));
    window.location.reload();
  });
};

export default {
  config: {
    locales: ['cs'],
  },
  bootstrap(app) {
    preventBrowserTranslation();
    recoverFromDomDesync();

    app.getPlugin('content-manager').injectComponent('listView', 'actions', {
      name: 'terlicko-ordering',
      Component: OrderingAction,
    });
  },
};
