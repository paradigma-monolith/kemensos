import { ref, readonly } from 'vue';

// Global singleton reactive state for App Mode ('demo' | 'production')
const currentMode = ref(
  typeof window !== 'undefined'
    ? localStorage.getItem('hybrid_app_mode') || 'demo'
    : 'demo'
);

if (typeof window !== 'undefined') {
  window.addEventListener('storage', (e) => {
    if (e.key === 'hybrid_app_mode' && e.newValue) {
      currentMode.value = e.newValue;
    }
  });

  window.addEventListener('app-mode-changed', (e) => {
    if (e.detail && (e.detail === 'demo' || e.detail === 'production')) {
      currentMode.value = e.detail;
    }
  });
}

export function useAppMode() {
  const setMode = (newMode) => {
    if (newMode !== 'demo' && newMode !== 'production') return;
    currentMode.value = newMode;
    if (typeof window !== 'undefined') {
      localStorage.setItem('hybrid_app_mode', newMode);
      window.dispatchEvent(new CustomEvent('app-mode-changed', { detail: newMode }));
    }
  };

  const toggleMode = () => {
    setMode(currentMode.value === 'demo' ? 'production' : 'demo');
  };

  return {
    appMode: readonly(currentMode),
    setMode,
    toggleMode,
    isDemo: () => currentMode.value === 'demo',
    isProduction: () => currentMode.value === 'production',
  };
}

export default useAppMode;
