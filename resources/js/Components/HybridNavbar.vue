<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { usePage, router as inertiaRouter } from '@inertiajs/vue3';
import { 
  LogOut, 
  User as UserIcon, 
  Bell, 
  History, 
  FileSpreadsheet,
  LayoutDashboard,
  Menu,
  X,
  Radio,
  CheckCheck,
  Shirt
} from 'lucide-vue-next';
import AdminSidebar from './AdminSidebar.vue';
import ThemeSwitcher from './ThemeSwitcher.vue';
import { useSidebar } from '../composables/useSidebar';

const { isSidebarOpen, isCollapsed, toggleSidebar, layoutPaddingClass } = useSidebar();

const props = defineProps({
  mode: {
    type: String,
    default: 'inertia',
  },
  currentRoute: {
    type: String,
    default: '',
  },
});

const prefix = computed(() => {
  if (typeof window !== 'undefined') {
    const match = window.location.pathname.match(/^(\/[^\/]+\/public)/);
    if (match) return match[1];
    if (window.location.pathname.includes('/hybridlaravel/public')) return '/hybridlaravel/public';
  }
  return '';
});

const getUrl = (path) => `${prefix.value}${path}`;

// Compute active navigation item
const activeNav = computed(() => {
  if (props.currentRoute) return props.currentRoute;
  if (typeof window === 'undefined') return '';
  const p = window.location.pathname;
  if (p.includes('/audit-logs')) return 'audit';
  if (p.includes('/profile')) return 'profile';
  if (p.includes('/uniforms') || p.includes('/seragam')) return 'uniforms';
  if (p.includes('/shoes')) return 'shoes';
  if (p.includes('/dashboard') || p === '/' || p.endsWith('/public') || p.endsWith('/public/')) return 'dashboard';
  return '';
});

const activeNavTitle = computed(() => {
  if (activeNav.value === 'uniforms') return 'Rekapitulasi Seragam';
  if (activeNav.value === 'shoes') return 'Rekapitulasi Sepatu';
  if (activeNav.value === 'audit') return 'Audit Trail Logs';
  if (activeNav.value === 'profile') return 'Profil Akun';
  return 'Dashboard Analitik';
});

// Brand pill badge
const brandBadge = {
  text: 'Sistem Rekapitulasi',
  badgeClass: 'bg-emerald-100 dark:bg-emerald-950/70 border-emerald-300 dark:border-emerald-700/60 text-emerald-800 dark:text-emerald-300',
  iconClass: 'bg-gradient-to-tr from-emerald-600 via-teal-600 to-indigo-600 shadow-emerald-500/20 group-hover:shadow-emerald-500/40',
};

// Dropdown Navigation States
const showNotifications = ref(false);
const notifDropdownRef = ref(null);

const toggleNotifications = () => {
  showNotifications.value = !showNotifications.value;
};

const closeAllDropdowns = () => {
  showNotifications.value = false;
};

const handleClickOutside = (e) => {
  if (notifDropdownRef.value && !notifDropdownRef.value.contains(e.target)) {
    showNotifications.value = false;
  }
};

// Seamless client-side navigation
const handleNav = (targetPath, e) => {
  closeAllDropdowns();
  try {
    if (inertiaRouter && typeof inertiaRouter.visit === 'function') {
      if (e) e.preventDefault();
      inertiaRouter.visit(getUrl(targetPath));
      return;
    }
  } catch (err) {}
};

// Current authenticated user
const currentUser = computed(() => {
  try {
    const page = usePage();
    return page?.props?.auth?.user || null;
  } catch (e) {
    return (typeof window !== 'undefined' && window.authUser) ? window.authUser : null;
  }
});

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
    osc.frequency.setValueAtTime(587.33, ctx.currentTime);
    osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.15);
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

  if (typeof document !== 'undefined') {
    document.addEventListener('click', handleClickOutside);
  }

  if (typeof window !== 'undefined') {
    window.addEventListener('keydown', (e) => {
      if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'b') {
        e.preventDefault();
        toggleSidebar();
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
  <header class="sticky top-0 z-30 w-full backdrop-blur-xl bg-white/85 dark:bg-slate-950/85 border-b border-slate-200/80 dark:border-slate-800/80 transition-colors duration-200">
    <div class="w-full px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <!-- Sidebar Trigger & Breadcrumb / Brand -->
        <div class="flex items-center gap-3 shrink-0">
          <!-- Toggle Sidebar Button (Collapse on Desktop / Open Drawer on Mobile) -->
          <button
            type="button"
            @click="toggleSidebar"
            class="p-2 rounded-xl text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white bg-slate-100 dark:bg-slate-900/80 hover:bg-slate-200 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 transition-all cursor-pointer flex items-center justify-center group shrink-0"
            :title="isCollapsed ? 'Perluas Sidebar (Ctrl+B)' : 'Ciutkan Sidebar (Ctrl+B)'"
          >
            <Menu class="w-4 h-4 transition-transform group-hover:scale-110" />
          </button>

          <!-- Desktop Section Breadcrumb -->
          <div class="hidden lg:flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
            <span class="font-bold text-slate-900 dark:text-white text-sm tracking-tight">Sepatu SR</span>
            <span>/</span>
            <span class="px-2.5 py-0.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-semibold border border-indigo-200/80 dark:border-indigo-800/80 font-mono text-[11px]">
              {{ activeNavTitle }}
            </span>
          </div>

          <!-- Mobile Brand Logo -->
          <a :href="getUrl('/')" @click="handleNav('/', $event)" class="flex items-center gap-2 group cursor-pointer shrink-0 lg:hidden">
            <div class="w-8 h-8 rounded-xl flex items-center justify-center font-black text-white shadow-lg transition-all"
              :class="brandBadge.iconClass">
              <span class="text-xs tracking-wider">SR</span>
            </div>
            <span class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">
              Sepatu SR
            </span>
          </a>
        </div>

        <!-- Right Side: Theme Switcher, Notifications, User Profile & Logout -->
        <div class="flex items-center gap-1.5 sm:gap-2 shrink-0">
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

            <!-- Notification Menu Dropdown -->
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
                      <span>Notifikasi Sistem</span>
                    </span>
                    <span v-if="unreadCount > 0" class="px-1.5 py-0.2 rounded-full text-[10px] font-mono bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                      {{ unreadCount }} Baru
                    </span>
                  </div>

                  <div class="flex items-center gap-1.5">
                    <button
                      type="button"
                      @click="markAllRead"
                      class="text-[11px] text-slate-500 hover:text-emerald-600 dark:text-slate-400 dark:hover:text-emerald-300 flex items-center gap-1 cursor-pointer py-1 px-1.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                      title="Tandai semua notifikasi sudah dibaca"
                    >
                      <CheckCheck class="w-3.5 h-3.5 text-emerald-500" />
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

                <!-- Notifications Stream List -->
                <div class="flex-1 min-h-0 overflow-y-auto space-y-2 overscroll-contain pr-1">
                  <div 
                    v-for="n in notifications" 
                    :key="n.id" 
                    @click="markSingleRead(n)"
                    class="p-2.5 rounded-xl text-xs transition-colors cursor-pointer hover:border-emerald-400 dark:hover:border-emerald-600 border" 
                    :class="n.read_at ? 'bg-slate-100/70 dark:bg-slate-950/40 border-transparent text-slate-500 dark:text-slate-400' : 'bg-emerald-50/80 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-900/50 text-slate-800 dark:text-slate-200 shadow-2xs'"
                  >
                    <div class="flex items-start justify-between gap-1.5">
                      <div class="font-semibold text-slate-900 dark:text-white text-xs leading-tight">
                        {{ n.data?.title || 'Pemberitahuan Sistem' }}
                      </div>
                      <span v-if="!n.read_at" class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0 mt-1"></span>
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
              </div>
            </Transition>
          </div>

          <!-- User Profile Avatar Pill -->
          <a
            :href="getUrl('/profile')"
            @click="handleNav('/profile', $event)"
            title="Kelola Profil Akun"
            class="flex items-center gap-2 p-1 sm:pl-1.5 sm:pr-4 py-1 rounded-full bg-slate-100 dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 hover:border-emerald-500/50 transition-all group cursor-pointer shrink-0 max-w-[190px]"
          >
            <img
              :src="currentUser?.avatar_url || 'https://ui-avatars.com/api/?name=Admin&background=059669&color=fff'"
              :alt="currentUser?.name || 'User'"
              class="w-7 h-7 rounded-full object-cover border border-emerald-500/40 shrink-0"
            />
            <div class="hidden md:block text-left min-w-0 flex-1">
              <span class="block font-semibold text-slate-800 dark:text-slate-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 truncate text-xs leading-tight transition-colors">
                {{ currentUser?.name }}
              </span>
              <span class="block text-[10px] font-mono text-emerald-600 dark:text-emerald-400 truncate leading-tight mt-0.5">
                {{ currentUser?.roles?.[0] || 'User' }}
              </span>
            </div>
          </a>

          <!-- Logout Button -->
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
      class="fixed top-20 inset-x-3 sm:inset-x-auto sm:right-6 sm:w-96 z-50 bg-slate-900/95 dark:bg-slate-950/95 text-white border border-emerald-500/50 rounded-2xl p-3 shadow-2xl backdrop-blur-xl flex items-start gap-3 ring-1 ring-emerald-500/20"
    >
      <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5 animate-pulse">
        <Radio class="w-4 h-4" />
      </div>
      <div class="flex-1 min-w-0 cursor-pointer" @click="showNotifications = true; liveToast = null;">
        <div class="flex items-center justify-between gap-1">
          <span class="text-xs font-bold text-emerald-300 leading-tight truncate">
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

  <!-- Admin Sidebar Navigation -->
  <AdminSidebar 
    :mode="mode" 
    :currentRoute="currentRoute" 
  />
</template>
