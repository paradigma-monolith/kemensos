import * as Sentry from '@sentry/vue';

/**
 * Initialize Sentry for Vue 3 application (both Inertia and standalone SPA).
 *
 * @param {import('vue').App} app - Vue application instance
 * @param {import('vue-router').Router|null} router - Optional Vue Router instance
 */
export function initSentry(app, router = null) {
  const dsn = import.meta.env.VITE_SENTRY_DSN_PUBLIC;

  if (!dsn || dsn.trim() === '') {
    if (import.meta.env.DEV) {
      console.info('[Sentry] VITE_SENTRY_DSN_PUBLIC is not configured. Frontend error tracking is currently dormant.');
    }
    return;
  }

  const integrations = [
    router
      ? Sentry.browserTracingIntegration({ router })
      : Sentry.browserTracingIntegration(),
    Sentry.replayIntegration({
      maskAllText: false,
      blockAllMedia: false,
    }),
  ];

  Sentry.init({
    app,
    dsn,
    integrations,
    // Performance monitoring sample rate
    tracesSampleRate: Number(import.meta.env.VITE_SENTRY_TRACES_SAMPLE_RATE || 1.0),
    // Session Replay sample rates
    replaysSessionSampleRate: 0.1,
    replaysOnErrorSampleRate: 1.0,
    // Environment
    environment: import.meta.env.MODE || 'production',
    // Vue component tracking
    trackComponents: true,
  });

  if (import.meta.env.DEV) {
    console.info('[Sentry] Successfully initialized for Vue application.');
  }
}

export { Sentry };
