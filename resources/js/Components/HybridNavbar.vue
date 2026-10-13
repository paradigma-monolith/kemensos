<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { usePage, router as inertiaRouter } from '@inertiajs/vue3';
import { useRouter } from 'vue-router';
import { 
  LogOut, 
  User as UserIcon, 
  Bell, 
  History, 
  BookOpen, 
  Sparkles, 
  CheckCheck, 
  Radio,
  Menu,
  Compass,
  ChevronDown,
  LayoutDashboard,
  Layers,
  Package,
  Home,
  X
} from 'lucide-vue-next';
import AdminSidebar from './AdminSidebar.vue';
import FloatingChat from './FloatingChat.vue';
import ThemeSwitcher from './ThemeSwitcher.vue';
import AppModeSwitcher from './AppModeSwitcher.vue';
import { useAppMode } from '../composables/useAppMode';

const { appMode, isDemo, isProduction } = useAppMode();

const props = defineProps({
  mode: {
    type: String,
    default: 'inertia', // 'inertia' | 'spa'
  },
  currentRoute: {
    type: String,
    default: '',
  },
});

const isSpa = computed(() => props.mode === 'spa');

const prefix = computed(() => {
  if (typeof window !== 'undefined' && window.location.pathname.includes('/hybridlaravel/public')) {
    return '/hybridlaravel/public';
  }
  return '';
});

const getUrl = (path) => `${prefix.value}${path}`;

// Safely obtain Vue Router if mounted in SPA context
let vueRouter = null;
try {
  vueRouter = useRouter();
} catch (e) {
  vueRouter = null;
}

// Compute active navigation item with automatic URL pathname fallback
const activeNav = computed(() => {
  if (props.currentRoute) return props.currentRoute;
  if (typeof window === 'undefined') return '';
  const p = window.location.pathname;
  if (p.includes('/spa/api-explorer')) return 'api-explorer';
  if (p.includes('/spa/products')) return 'products';
  if (p.endsWith('/spa') || p.endsWith('/spa/')) return 'home';
  if (p.includes('/inertia/products')) return 'products';
  if (p.includes('/inertia/dashboard')) return 'dashboard';
  if (p.includes('/inertia/map-demo') || p.includes('/map-demo')) return 'map';
  if (p.includes('/audit-logs')) return 'audit';
  if (p.includes('/profile')) return 'profile';
  if (p === '/' || p.endsWith('/public') || p.endsWith('/public/')) return 'hub';
  return '';
});

// Dynamic brand pill badge depending on context (Control Hub, SPA Mode, Inertia Bridge)
const brandBadge = computed(() => {
  if (activeNav.value === 'hub') {
    return {
      text: 'Control Hub',
      badgeClass: 'bg-slate-100 dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300',
      iconClass: 'bg-gradient-to-tr from-indigo-600 via-purple-600 to-teal-400 shadow-indigo-500/20 group-hover:shadow-indigo-500/40',
    };
  }
  if (isSpa.value) {
    return {
      text: 'SPA Mode',
      badgeClass: 'bg-emerald-100 dark:bg-emerald-950/70 border-emerald-300 dark:border-emerald-700/60 text-emerald-800 dark:text-emerald-300',
      iconClass: 'bg-gradient-to-tr from-emerald-600 to-teal-400 shadow-emerald-500/20 group-hover:shadow-emerald-500/40',
    };
  }
  return {
    text: 'Inertia Bridge',
    badgeClass: 'bg-indigo-100 dark:bg-indigo-950/70 border-indigo-300 dark:border-indigo-700/60 text-indigo-800 dark:text-indigo-300',
    iconClass: 'bg-gradient-to-tr from-indigo-600 to-violet-500 shadow-indigo-500/20 group-hover:shadow-indigo-500/40',
  };
});

// Dropdown Navigation States & Element References
const inertiaDropdownOpen = ref(false);
const spaDropdownOpen = ref(false);
const showNotifications = ref(false);

const inertiaDropdownRef = ref(null);
const spaDropdownRef = ref(null);
const notifDropdownRef = ref(null);

const isInertiaActive = computed(() => {
  return !isSpa.value && ['dashboard', 'map', 'products', 'audit'].includes(activeNav.value);
});

const isSpaActive = computed(() => {
  return isSpa.value || ['api-explorer'].includes(activeNav.value);
});

const toggleInertiaDropdown = () => {
  inertiaDropdownOpen.value = !inertiaDropdownOpen.value;
  if (inertiaDropdownOpen.value) {
    spaDropdownOpen.value = false;
    showNotifications.value = false;
  }
};

const toggleSpaDropdown = () => {
  spaDropdownOpen.value = !spaDropdownOpen.value;
  if (spaDropdownOpen.value) {
    inertiaDropdownOpen.value = false;
    showNotifications.value = false;
  }
};

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value;
  if (showNotifications.value) {
    inertiaDropdownOpen.value = false;
    spaDropdownOpen.value = false;
  }
};

const closeAllDropdowns = () => {
  inertiaDropdownOpen.value = false;
  spaDropdownOpen.value = false;
  showNotifications.value = false;
};

const handleClickOutside = (e) => {
  if (inertiaDropdownRef.value && !inertiaDropdownRef.value.contains(e.target)) {
    inertiaDropdownOpen.value = false;
  }
  if (spaDropdownRef.value && !spaDropdownRef.value.contains(e.target)) {
    spaDropdownOpen.value = false;
  }
  if (notifDropdownRef.value && !notifDropdownRef.value.contains(e.target)) {
    showNotifications.value = false;
  }
};

// Seamless client-side navigation (Eliminates jarring full-page reloads)
const handleNav = (targetPath, e) => {
  closeAllDropdowns();

  // Case 1: In SPA mode & clicking an SPA link -> instantaneous Vue Router push
  if (isSpa.value && (targetPath === '/spa' || targetPath.startsWith('/spa/'))) {
    if (vueRouter) {
      if (e) e.preventDefault();
      const internalRoute = targetPath.replace(/^\/spa/, '') || '/';
      vueRouter.push(internalRoute);
      return;
    }
  }

  // Case 2: In Inertia mode & clicking an Inertia/Hub link -> smooth Inertia visit
  if (!isSpa.value && !targetPath.startsWith('/spa') && !targetPath.startsWith('/docs') && !targetPath.startsWith('/pulse')) {
    try {
      if (inertiaRouter && typeof inertiaRouter.visit === 'function') {
        if (e) e.preventDefault();
        inertiaRouter.visit(getUrl(targetPath));
        return;
      }
    } catch (err) {}
  }

  // Case 3: Crossing paradigms (Inertia <-> SPA) or external docs -> let browser navigate cleanly
};

// Safely access current authenticated user in either Inertia or SPA mode
const currentUser = computed(() => {
  if (isSpa.value) {
    return (typeof window !== 'undefined' && window.authUser) ? window.authUser : null;
  }
  try {
    const page = usePage();
    return page?.props?.auth?.user || null;
  } catch (e) {
    return (typeof window !== 'undefined' && window.authUser) ? window.authUser : null;
  }
});

// Sidebar Drawer State
const sidebarOpen = ref(false);

// Real-Time Notifications State
const unreadCount = ref(0);
const notifications = ref([]);

const fetchNotifications = async () => {
  if (!currentUser.value) return;
  try {
    const res = await fetch(getUrl('/notifications'));
    if (res.ok) {
      const data = await res.json();
      unreadCount.value = data.unread_count || 0;
      notifications.value = data.notifications || [];
    }
  } catch (e) {
    // silent fallback
  }
};

const markAllRead = async () => {
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    await fetch(getUrl('/notifications/read-all'), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json',
      },
    });
    unreadCount.value = 0;
    notifications.value.forEach(n => n.read_at = new Date().toISOString());
  } catch (e) {}
};

const markSingleRead = async (notification) => {
  if (!notification.read_at) {
    try {
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      await fetch(getUrl(`/notifications/${notification.id}/read`), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': token,
          'Accept': 'application/json',
        },
      });
      notification.read_at = new Date().toISOString();
      if (unreadCount.value > 0) unreadCount.value--;
    } catch (e) {}
  }
  if (notification.data?.action_url) {
    showNotifications.value = false;
    handleNav(notification.data.action_url);
  }
};

// Real-Time Incoming Toast State & Web Audio Chime
const liveToast = ref(null);
let toastTimeout = null;

const playNotificationChime = () => {
  try {
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) return;
    const ctx = new AudioCtx();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();
    osc.connect(gain);
    gain.connect(ctx.destination);
    osc.type = 'sine';
    osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
    osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.15); // A5
    gain.gain.setValueAtTime(0.08, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);
    osc.start(ctx.currentTime);
    osc.stop(ctx.currentTime + 0.35);
  } catch (e) {}
};

const showLiveToast = (notification) => {
  liveToast.value = {
    id: notification.id,
    title: notification.title || 'Notifikasi Reverb Baru',
    message: notification.message || '',
    action_url: notification.action_url || null,
  };
  if (toastTimeout) clearTimeout(toastTimeout);
  toastTimeout = setTimeout(() => {
    liveToast.value = null;
  }, 5000);
};

const sendDemoNotification = async () => {
  try {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    await fetch(getUrl('/notifications/demo'), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json',
      },
    });
    fetchNotifications();
  } catch (e) {}
};

onMounted(() => {
  fetchNotifications();

  // Listen for real-time Reverb WebSocket notifications
  if (typeof window !== 'undefined' && window.Echo && currentUser.value) {
    window.Echo.private(`App.Models.User.${currentUser.value.id}`)
      .notification((notification) => {
        unreadCount.value++;
        notifications.value.unshift({
          id: notification.id,
          data: {
            title: notification.title,
            message: notification.message,
            type: notification.type || 'info',
            action_url: notification.action_url || null,
            time: 'baru saja',
          },
          read_at: null,
          created_at: new Date().toISOString(),
        });
        showLiveToast(notification);
        playNotificationChime();
      });
  }

  // Click outside listener to dismiss dropdowns
  if (typeof document !== 'undefined') {
    document.addEventListener('click', handleClickOutside);
  }

  // Toggle sidebar via keyboard shortcut (Ctrl+B / Cmd+B) and close dropdowns (Escape)
  if (typeof window !== 'undefined') {
    window.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
        e.preventDefault();
        sidebarOpen.value = !sidebarOpen.value;
      }
      if (e.key === 'Escape') {
        closeAllDropdowns();
      }
    });
  }
});

onUnmounted(() => {
  if (typeof document !== 'undefined') {
    document.removeEventListener('click', handleClickOutside);
  }
  if (toastTimeout) {
    clearTimeout(toastTimeout);
  }
});

const handleLogout = () => {
  window.location.href = getUrl('/logout');
};
</script>

<template>
  <header class="sticky top-0 z-50 w-full backdrop-blur-xl bg-white/85 dark:bg-slate-950/85 border-b border-slate-200/80 dark:border-slate-800/80 transition-colors duration-300">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <!-- Sidebar Trigger & Brand -->
        <div class="flex items-center gap-2 sm:gap-2.5 shrink-0">
          <button
            type="button"
            @click="sidebarOpen = !sidebarOpen"
            class="p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-900/80 hover:bg-slate-200 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 transition-all cursor-pointer flex items-center justify-center group shrink-0"
            title="Buka Navigasi Sidebar (Ctrl+B)"
          >
            <Menu class="w-4 h-4 transition-transform group-hover:scale-110" />
          </button>

          <a :href="getUrl('/')" @click="handleNav('/', $event)" class="flex items-center gap-2 sm:gap-2.5 group cursor-pointer shrink-0">
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl flex items-center justify-center font-black text-white shadow-lg transition-all duration-300 shrink-0"
              :class="brandBadge.iconClass">
              <span class="text-xs sm:text-sm tracking-wider">HL</span>
            </div>
            <div class="flex items-center whitespace-nowrap">
              <span class="hidden min-[420px]:inline-block text-sm sm:text-base font-bold text-slate-900 dark:text-white tracking-tight group-hover:text-indigo-600 dark:group-hover:text-slate-200 transition-colors whitespace-nowrap">
                Hybrid Laravel
              </span>
              <span class="hidden xl:inline-block ml-2 text-xs px-2.5 py-0.5 rounded-full font-mono font-medium border transition-colors whitespace-nowrap"
                :class="brandBadge.badgeClass">
                {{ brandBadge.text }}
              </span>
            </div>
          </a>
        </div>

        <!-- Navigation Links (Streamlined with Dropdowns) -->
        <nav class="hidden lg:flex items-center gap-1 xl:gap-1.5 text-xs font-medium shrink-0">
          <!-- Hub link -->
          <a :href="getUrl('/')" 
            @click="handleNav('/', $event)"
            class="px-2.5 py-1.5 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer"
            :class="activeNav === 'hub' ? 'bg-slate-200 dark:bg-slate-800 text-slate-900 dark:text-white font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60'">
            <Home class="w-3.5 h-3.5" />
            <span>Hub</span>
          </a>

          <!-- 1. Inertia Bridge Dropdown -->
          <div class="relative" ref="inertiaDropdownRef">
            <button
              type="button"
              @click="toggleInertiaDropdown"
              class="px-2.5 py-1.5 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer font-medium select-none"
              :class="[
                isInertiaActive 
                  ? 'bg-indigo-100 dark:bg-indigo-600/20 text-indigo-700 dark:text-indigo-300 font-semibold ring-1 ring-indigo-300 dark:ring-indigo-500/40' 
                  : 'text-slate-600 dark:text-slate-400 hover:text-indigo-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60',
                inertiaDropdownOpen ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white' : ''
              ]"
              :aria-expanded="inertiaDropdownOpen"
              aria-haspopup="true"
            >
              <div class="w-1.5 h-1.5 rounded-full" :class="isInertiaActive ? 'bg-indigo-500 animate-pulse' : 'bg-slate-400/60 dark:bg-slate-600'"></div>
              <span>Inertia Bridge</span>
              <ChevronDown class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': inertiaDropdownOpen }" />
            </button>

            <!-- Inertia Dropdown Menu -->
            <Transition
              enter-active-class="transition duration-150 ease-out"
              enter-from-class="transform opacity-0 scale-95 -translate-y-1"
              enter-to-class="transform opacity-100 scale-100 translate-y-0"
              leave-active-class="transition duration-100 ease-in"
              leave-from-class="transform opacity-100 scale-100 translate-y-0"
              leave-to-class="transform opacity-0 scale-95 -translate-y-1"
            >
              <div
                v-if="inertiaDropdownOpen"
                class="absolute left-0 mt-2 w-72 bg-white/95 dark:bg-slate-900/95 border border-slate-200/90 dark:border-slate-800 rounded-2xl shadow-2xl backdrop-blur-xl p-2 z-50 text-slate-800 dark:text-slate-200 ring-1 ring-black/5 dark:ring-white/5"
              >
                <div class="px-2.5 py-1.5 border-b border-slate-100 dark:border-slate-800/80 mb-1 flex items-center justify-between">
                  <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 font-mono">
                    Inertia.js Monolith
                  </span>
                  <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/50 font-mono">
                    SSR/CSR
                  </span>
                </div>

                <div class="space-y-0.5">
                  <!-- Dashboard -->
                  <a
                    :href="getUrl('/inertia/dashboard')"
                    @click="handleNav('/inertia/dashboard', $event)"
                    class="flex items-center gap-3 p-2 rounded-xl transition-all group cursor-pointer"
                    :class="!isSpa && activeNav === 'dashboard' ? 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-300' : 'hover:bg-slate-100/80 dark:hover:bg-slate-800/60 text-slate-700 dark:text-slate-300'"
                  >
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors"
                      :class="!isSpa && activeNav === 'dashboard' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 group-hover:bg-indigo-600 group-hover:text-white'">
                      <LayoutDashboard class="w-4 h-4" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-xs font-semibold leading-tight">Dashboard</div>
                      <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight truncate mt-0.5">Metrik ECharts & Statistik</div>
                    </div>
                  </a>

                  <!-- MapLibre (Demo Only) -->
                  <a
                    v-if="appMode === 'demo'"
                    :href="getUrl('/inertia/map-demo')"
                    @click="handleNav('/inertia/map-demo', $event)"
                    class="flex items-center gap-3 p-2 rounded-xl transition-all group cursor-pointer"
                    :class="!isSpa && activeNav === 'map' ? 'bg-teal-50 dark:bg-teal-950/40 text-teal-600 dark:text-teal-300' : 'hover:bg-slate-100/80 dark:hover:bg-slate-800/60 text-slate-700 dark:text-slate-300'"
                  >
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors"
                      :class="!isSpa && activeNav === 'map' ? 'bg-teal-600 text-white shadow-sm' : 'bg-teal-100 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 group-hover:bg-teal-600 group-hover:text-white'">
                      <Compass class="w-4 h-4" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center gap-1.5">
                        <span class="text-xs font-semibold leading-tight">Peta (MapLibre)</span>
                        <span class="text-[9px] px-1 py-0.2 rounded bg-teal-100 dark:bg-teal-950/60 text-teal-700 dark:text-teal-300 font-mono">Demo</span>
                      </div>
                      <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight truncate mt-0.5">Visualisasi geospasial interaktif</div>
                    </div>
                  </a>

                  <!-- Produk / Katalog Data -->
                  <a
                    :href="getUrl('/inertia/products')"
                    @click="handleNav('/inertia/products', $event)"
                    class="flex items-center gap-3 p-2 rounded-xl transition-all group cursor-pointer"
                    :class="!isSpa && activeNav === 'products' ? 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-300' : 'hover:bg-slate-100/80 dark:hover:bg-slate-800/60 text-slate-700 dark:text-slate-300'"
                  >
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors"
                      :class="!isSpa && activeNav === 'products' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-indigo-100 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 group-hover:bg-indigo-600 group-hover:text-white'">
                      <Package class="w-4 h-4" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-xs font-semibold leading-tight">
                        {{ appMode === 'production' ? 'Katalog Data' : 'Produk (Inertia)' }}
                      </div>
                      <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight truncate mt-0.5">CRUD server-driven & pagination</div>
                    </div>
                  </a>

                  <!-- Audit Trail -->
                  <a
                    :href="getUrl('/audit-logs')"
                    @click="handleNav('/audit-logs', $event)"
                    class="flex items-center gap-3 p-2 rounded-xl transition-all group cursor-pointer"
                    :class="!isSpa && activeNav === 'audit' ? 'bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600 dark:text-indigo-300' : 'hover:bg-slate-100/80 dark:hover:bg-slate-800/60 text-slate-700 dark:text-slate-300'"
                  >
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors"
                      :class="!isSpa && activeNav === 'audit' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300 group-hover:bg-indigo-600 group-hover:text-white'">
                      <History class="w-4 h-4" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-xs font-semibold leading-tight">Audit Trail</div>
                      <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight truncate mt-0.5">Catatan aktivitas & log sistem</div>
                    </div>
                  </a>
                </div>
              </div>
            </Transition>
          </div>

          <!-- Visual Divider between Inertia Bridge & SPA Mode -->
          <div v-if="appMode === 'demo' || isSpa" class="h-4 w-[1px] bg-slate-300 dark:bg-slate-700/80 mx-1 self-center" aria-hidden="true"></div>

          <!-- 2. Vue 3 SPA Dropdown (Shown in Demo mode or when active in SPA) -->
          <div v-if="appMode === 'demo' || isSpa" class="relative" ref="spaDropdownRef">
            <button
              type="button"
              @click="toggleSpaDropdown"
              class="px-2.5 py-1.5 rounded-xl transition-all flex items-center gap-1.5 cursor-pointer font-medium select-none"
              :class="[
                isSpaActive 
                  ? 'bg-emerald-100 dark:bg-emerald-600/20 text-emerald-800 dark:text-emerald-300 font-semibold ring-1 ring-emerald-300 dark:ring-emerald-500/40' 
                  : 'text-slate-600 dark:text-slate-400 hover:text-emerald-600 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/60',
                spaDropdownOpen ? 'bg-slate-100 dark:bg-slate-800 text-slate-900 dark:text-white' : ''
              ]"
              :aria-expanded="spaDropdownOpen"
              aria-haspopup="true"
            >
              <div class="w-1.5 h-1.5 rounded-full" :class="isSpaActive ? 'bg-emerald-500 animate-pulse' : 'bg-slate-400/60 dark:bg-slate-600'"></div>
              <span>SPA Mode</span>
              <ChevronDown class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': spaDropdownOpen }" />
            </button>

            <!-- SPA Dropdown Menu -->
            <Transition
              enter-active-class="transition duration-150 ease-out"
              enter-from-class="transform opacity-0 scale-95 -translate-y-1"
              enter-to-class="transform opacity-100 scale-100 translate-y-0"
              leave-active-class="transition duration-100 ease-in"
              leave-from-class="transform opacity-100 scale-100 translate-y-0"
              leave-to-class="transform opacity-0 scale-95 -translate-y-1"
            >
              <div
                v-if="spaDropdownOpen"
                class="absolute left-0 mt-2 w-72 bg-white/95 dark:bg-slate-900/95 border border-slate-200/90 dark:border-slate-800 rounded-2xl shadow-2xl backdrop-blur-xl p-2 z-50 text-slate-800 dark:text-slate-200 ring-1 ring-black/5 dark:ring-white/5"
              >
                <div class="px-2.5 py-1.5 border-b border-slate-100 dark:border-slate-800/80 mb-1 flex items-center justify-between">
                  <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400 font-mono">
                    Vue 3 SPA Stack
                  </span>
                  <span class="text-[9px] px-1.5 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50 font-mono">
                    Client Router
                  </span>
                </div>

                <div class="space-y-0.5">
                  <!-- SPA Home -->
                  <a
                    :href="getUrl('/spa')"
                    @click="handleNav('/spa', $event)"
                    class="flex items-center gap-3 p-2 rounded-xl transition-all group cursor-pointer"
                    :class="isSpa && activeNav === 'home' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300' : 'hover:bg-slate-100/80 dark:hover:bg-slate-800/60 text-slate-700 dark:text-slate-300'"
                  >
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors"
                      :class="isSpa && activeNav === 'home' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 group-hover:bg-emerald-600 group-hover:text-white'">
                      <Layers class="w-4 h-4" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-xs font-semibold leading-tight">SPA Home</div>
                      <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight truncate mt-0.5">Vue Router instant client shell</div>
                    </div>
                  </a>

                  <!-- SPA Produk -->
                  <a
                    :href="getUrl('/spa/products')"
                    @click="handleNav('/spa/products', $event)"
                    class="flex items-center gap-3 p-2 rounded-xl transition-all group cursor-pointer"
                    :class="isSpa && activeNav === 'products' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300' : 'hover:bg-slate-100/80 dark:hover:bg-slate-800/60 text-slate-700 dark:text-slate-300'"
                  >
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors"
                      :class="isSpa && activeNav === 'products' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 group-hover:bg-emerald-600 group-hover:text-white'">
                      <Package class="w-4 h-4" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-xs font-semibold leading-tight">SPA Produk</div>
                      <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight truncate mt-0.5">Pinia store & REST API fetching</div>
                    </div>
                  </a>

                  <!-- API Explorer -->
                  <a
                    :href="getUrl('/spa/api-explorer')"
                    @click="handleNav('/spa/api-explorer', $event)"
                    class="flex items-center gap-3 p-2 rounded-xl transition-all group cursor-pointer"
                    :class="isSpa && activeNav === 'api-explorer' ? 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300' : 'hover:bg-slate-100/80 dark:hover:bg-slate-800/60 text-slate-700 dark:text-slate-300'"
                  >
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 transition-colors"
                      :class="isSpa && activeNav === 'api-explorer' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-cyan-100 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 group-hover:bg-cyan-600 group-hover:text-white'">
                      <Compass class="w-4 h-4" />
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-xs font-semibold leading-tight font-mono">API Explorer</div>
                      <div class="text-[11px] text-slate-500 dark:text-slate-400 leading-tight truncate mt-0.5">Pengujian endpoint & Sanctum token</div>
                    </div>
                  </a>
                </div>
              </div>
            </Transition>
          </div>

          <!-- Auto-Generated Scramble API Docs -->
          <a :href="getUrl('/docs/api')" target="_blank"
            class="px-2.5 py-1.5 rounded-xl transition-all text-amber-600 dark:text-amber-400/90 hover:text-amber-700 dark:hover:text-amber-300 hover:bg-amber-100/60 dark:hover:bg-amber-950/40 flex items-center gap-1 font-mono text-[11px] border border-amber-200/50 dark:border-amber-800/30">
            <BookOpen class="w-3.5 h-3.5" />
            <span>API Docs ↗</span>
          </a>
        </nav>

        <!-- Right Side: Mode Switcher, Theme Switcher, Notifications, User Profile & Logout -->
        <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
          <!-- Interactive App Mode Switcher (Demo vs Production - shown on 480px+) -->
          <div class="hidden min-[480px]:block">
            <AppModeSwitcher />
          </div>

          <!-- 3-Way Theme Switcher (Dark / Light / System) -->
          <ThemeSwitcher />

          <!-- Notification Bell Dropdown -->
          <div class="relative" ref="notifDropdownRef">
            <button
              type="button"
              @click="toggleNotifications"
              class="relative p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-900/80 hover:bg-slate-200 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 transition-all cursor-pointer"
              title="Notifikasi Real-Time Reverb"
            >
              <Bell class="w-4 h-4" />
              <span v-if="unreadCount > 0" class="absolute -top-1 -right-1 w-4 h-4 bg-rose-500 text-white rounded-full text-[10px] font-bold flex items-center justify-center animate-pulse">
                {{ unreadCount > 9 ? '9+' : unreadCount }}
              </span>
            </button>

            <!-- Mobile Backdrop (tap anywhere outside to dismiss) -->
            <div 
              v-if="showNotifications" 
              @click="showNotifications = false" 
              class="fixed inset-0 bg-slate-950/60 backdrop-blur-xs z-40 sm:hidden"
            ></div>

            <!-- Notification Menu Dropdown (Fixed & centered on mobile, absolute anchored on desktop) -->
            <Transition
              enter-active-class="transition duration-200 ease-out"
              enter-from-class="transform opacity-0 scale-95 -translate-y-2"
              enter-to-class="transform opacity-100 scale-100 translate-y-0"
              leave-active-class="transition duration-150 ease-in"
              leave-from-class="transform opacity-100 scale-100 translate-y-0"
              leave-to-class="transform opacity-0 scale-95 -translate-y-2"
            >
              <div 
                v-if="showNotifications" 
                class="fixed inset-x-3 top-16 mt-2 max-w-sm mx-auto sm:absolute sm:inset-x-auto sm:right-0 sm:top-full sm:mt-2 sm:w-84 sm:max-w-md sm:mx-0 max-h-[min(520px,calc(100vh-5.5rem))] flex flex-col bg-white/95 dark:bg-slate-900/95 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl backdrop-blur-xl p-3.5 z-50 text-slate-800 dark:text-slate-200 ring-1 ring-black/5 dark:ring-white/5"
              >
                <!-- Header with Title, Actions & Mobile Close Button -->
                <div class="flex items-center justify-between pb-2.5 mb-2.5 border-b border-slate-200 dark:border-slate-800 text-xs shrink-0">
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                      <Radio class="w-3.5 h-3.5 text-cyan-600 dark:text-cyan-400 animate-pulse" />
                      <span>Notifikasi Reverb</span>
                    </span>
                    <span v-if="unreadCount > 0" class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                      {{ unreadCount }} Baru
                    </span>
                  </div>

                  <div class="flex items-center gap-1.5">
                    <button
                      type="button"
                      @click="markAllRead"
                      class="text-[11px] text-slate-500 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-300 flex items-center gap-1 cursor-pointer py-1 px-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                      title="Tandai semua notifikasi sudah dibaca"
                    >
                      <CheckCheck class="w-3.5 h-3.5 text-indigo-500" />
                      <span class="hidden min-[380px]:inline">Tandai Dibaca</span>
                    </button>

                    <!-- Mobile Close Button -->
                    <button
                      type="button"
                      @click="showNotifications = false"
                      class="sm:hidden p-1 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
                      title="Tutup Notifikasi"
                    >
                      <X class="w-4 h-4" />
                    </button>
                  </div>
                </div>

                <!-- Notifications Stream List (Scrollable, never overflows viewport) -->
                <div class="flex-1 min-h-0 overflow-y-auto space-y-2 overscroll-contain pr-1">
                  <div 
                    v-for="n in notifications" 
                    :key="n.id" 
                    @click="markSingleRead(n)"
                    class="p-2.5 rounded-xl text-xs transition-colors cursor-pointer hover:border-indigo-400 dark:hover:border-indigo-600 border" 
                    :class="n.read_at ? 'bg-slate-100/70 dark:bg-slate-950/40 border-transparent text-slate-500 dark:text-slate-400' : 'bg-indigo-50/80 dark:bg-indigo-950/40 border-indigo-200 dark:border-indigo-900/50 text-slate-800 dark:text-slate-200 shadow-2xs'"
                  >
                    <div class="flex items-start justify-between gap-1.5">
                      <div class="font-semibold text-slate-900 dark:text-white text-xs leading-tight">
                        {{ n.data?.title || 'Pemberitahuan Sistem' }}
                      </div>
                      <span v-if="!n.read_at" class="w-1.5 h-1.5 rounded-full bg-indigo-500 shrink-0 mt-1"></span>
                    </div>
                    <div class="text-[11px] text-slate-600 dark:text-slate-300 mt-1 leading-relaxed">
                      {{ n.data?.message }}
                    </div>
                    <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-1.5 font-mono">
                      {{ n.data?.time || 'baru saja' }}
                    </div>
                  </div>

                  <div v-if="notifications.length === 0" class="py-8 text-center text-xs text-slate-500 font-mono">
                    <Radio class="w-6 h-6 text-slate-400 dark:text-slate-600 mx-auto mb-2 opacity-50" />
                    <p>Belum ada notifikasi baru.</p>
                  </div>
                </div>

                <!-- Quick Action: Trigger Demo Broadcast -->
                <div class="mt-2.5 pt-2.5 border-t border-slate-200 dark:border-slate-800 shrink-0">
                  <button
                    type="button"
                    @click="sendDemoNotification"
                    class="w-full py-2 px-3 rounded-xl text-xs font-semibold bg-cyan-50 dark:bg-cyan-950/60 text-cyan-700 dark:text-cyan-300 hover:bg-cyan-100 dark:hover:bg-cyan-900/50 border border-cyan-200 dark:border-cyan-800/60 transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs"
                  >
                    <Sparkles class="w-3.5 h-3.5 text-cyan-600 dark:text-cyan-400" />
                    <span>⚡ Kirim Notifikasi Demo (WebSocket)</span>
                  </button>
                </div>
              </div>
            </Transition>
          </div>

          <!-- User Profile Avatar Pill -->
          <a
            :href="getUrl('/profile')"
            @click="handleNav('/profile', $event)"
            title="Kelola Profil Akun"
            class="flex items-center gap-2 p-1 sm:pl-1.5 sm:pr-4 py-1 rounded-full bg-slate-100 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 hover:border-indigo-500/50 transition-all group cursor-pointer shrink-0 max-w-[190px]"
          >
            <img
              :src="currentUser?.avatar_url || 'https://ui-avatars.com/api/?name=Admin&background=6366f1&color=fff'"
              :alt="currentUser?.name || 'User'"
              class="w-7 h-7 rounded-full object-cover border border-indigo-500/40 shrink-0"
            />
            <div class="hidden md:block text-left min-w-0 flex-1">
              <span class="block font-semibold text-slate-800 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-white truncate text-xs leading-tight transition-colors">
                {{ currentUser?.name }}
              </span>
              <span class="block text-[10px] font-mono text-indigo-600 dark:text-indigo-400 truncate leading-tight mt-0.5">
                {{ currentUser?.roles?.[0] || 'User' }}
              </span>
            </div>
          </a>

          <!-- Logout Button (Shown on md+ screens; on mobile, available in sidebar drawer & profile) -->
          <button
            type="button"
            @click="handleLogout"
            title="Keluar dari sesi"
            class="hidden md:inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-300 bg-slate-100 dark:bg-slate-900/80 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-slate-200 dark:border-slate-800 hover:border-rose-300 dark:hover:border-rose-800/60 transition-all cursor-pointer shrink-0"
          >
            <LogOut class="w-3.5 h-3.5" />
            <span class="hidden lg:inline">Keluar</span>
          </button>
        </div>
      </div>
    </div>
  </header>

  <!-- Real-Time Reverb Live Toast (Mobile & Desktop) -->
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="transform -translate-y-4 opacity-0 scale-95"
    enter-to-class="transform translate-y-0 opacity-100 scale-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="transform translate-y-0 opacity-100 scale-100"
    leave-to-class="transform -translate-y-4 opacity-0 scale-95"
  >
    <div 
      v-if="liveToast"
      class="fixed top-20 inset-x-3 sm:inset-x-auto sm:right-6 sm:w-96 z-50 bg-slate-900/95 dark:bg-slate-950/95 text-white border border-cyan-500/50 rounded-2xl p-3 shadow-2xl backdrop-blur-xl flex items-start gap-3 ring-1 ring-cyan-500/20"
    >
      <div class="w-8 h-8 rounded-xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center shrink-0 mt-0.5 animate-pulse">
        <Radio class="w-4 h-4" />
      </div>
      <div class="flex-1 min-w-0 cursor-pointer" @click="showNotifications = true; liveToast = null;">
        <div class="flex items-center justify-between gap-1">
          <span class="text-xs font-bold text-cyan-300 leading-tight truncate">
            {{ liveToast.title }}
          </span>
          <span class="text-[10px] text-slate-400 font-mono shrink-0">baru saja</span>
        </div>
        <p class="text-[11px] text-slate-200 mt-0.5 line-clamp-2 leading-relaxed">
          {{ liveToast.message }}
        </p>
      </div>
      <button 
        type="button" 
        @click="liveToast = null" 
        class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors shrink-0 cursor-pointer"
        title="Tutup Toast"
      >
        <X class="w-3.5 h-3.5" />
      </button>
    </div>
  </Transition>

  <!-- Enterprise Collapsible Sidebar Navigation -->
  <AdminSidebar 
    v-model:open="sidebarOpen" 
    :mode="mode" 
    :currentRoute="currentRoute" 
  />

  <!-- Real-Time Floating Chat strictly at Bottom-Right -->
  <FloatingChat :mode="mode" />
</template>
