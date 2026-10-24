<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage, router as inertiaRouter } from '@inertiajs/vue3';
import { useRouter } from 'vue-router';
import { 
  Home, 
  LayoutDashboard, 
  Globe,
  Boxes, 
  ShoppingBag, 
  Compass, 
  History, 
  User as UserIcon, 
  BookOpen, 
  Activity, 
  LogOut, 
  ChevronLeft, 
  ChevronRight, 
  Sparkles,
  Layers,
  X,
  Pin,
  PinOff,
  ShieldCheck,
  FileSpreadsheet
} from 'lucide-vue-next';

const props = defineProps({
  mode: {
    type: String,
    default: 'inertia', // 'inertia' | 'spa'
  },
  currentRoute: {
    type: String,
    default: '',
  },
  open: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(['update:open', 'toggleCollapse']);

const isSpa = computed(() => props.mode === 'spa');

const prefix = computed(() => {
  if (typeof window !== 'undefined') {
    const match = window.location.pathname.match(/^(\/[^\/]+\/public)/);
    if (match) return match[1];
    if (window.location.pathname.includes('/hybridlaravel/public')) return '/hybridlaravel/public';
  }
  return '';
});

const getUrl = (path) => `${prefix.value}${path}`;

// Safely obtain Vue Router if in SPA mode
let vueRouter = null;
try {
  vueRouter = useRouter();
} catch (e) {
  vueRouter = null;
}

// Safely resolve active authenticated user
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

// Sidebar Collapsed & Pinned states (persisted to localStorage)
const isCollapsed = ref(false);
const isPinned = ref(false);

onMounted(() => {
  const savedCollapsed = localStorage.getItem('hybrid_sidebar_collapsed');
  if (savedCollapsed !== null) {
    isCollapsed.value = savedCollapsed === 'true';
  }

  const savedPinned = localStorage.getItem('hybrid_sidebar_pinned');
  if (savedPinned !== null) {
    isPinned.value = savedPinned === 'true';
  }
});

const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value;
  localStorage.setItem('hybrid_sidebar_collapsed', isCollapsed.value.toString());
  emit('toggleCollapse', isCollapsed.value);
};

const togglePin = () => {
  isPinned.value = !isPinned.value;
  localStorage.setItem('hybrid_sidebar_pinned', isPinned.value.toString());
};

const closeSidebar = () => {
  emit('update:open', false);
};

const handleNavClick = (targetPath, isExternal, e) => {
  if (!isPinned.value) {
    closeSidebar();
  }
  if (isExternal || !targetPath) return;

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
};

const handleLogout = () => {
  window.location.href = getUrl('/logout');
};

const navSections = computed(() => [
  {
    title: 'Manajemen Sepatu SR',
    items: [
      {
        name: 'Dashboard Analitik',
        path: '/',
        url: getUrl('/'),
        icon: LayoutDashboard,
        active: props.currentRoute === 'dashboard' || (!props.currentRoute && (typeof window !== 'undefined' && (window.location.pathname === '/' || window.location.pathname.endsWith('/public') || window.location.pathname.endsWith('/public/')))),
        badge: 'Interaktif',
        badgeColor: 'bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
      },
      {
        name: 'Rekapitulasi Ukuran',
        path: '/shoes',
        url: getUrl('/shoes'),
        icon: FileSpreadsheet,
        active: props.currentRoute === 'shoes' || (typeof window !== 'undefined' && window.location.pathname.includes('/shoes')),
        badge: 'Data Excel',
        badgeColor: 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
      },
    ],
  },
  {
    title: 'Keamanan & Tata Kelola',
    items: [
      {
        name: 'Audit Trail Logs',
        path: '/audit-logs',
        url: getUrl('/audit-logs'),
        icon: History,
        active: props.currentRoute === 'audit',
        badge: 'Spatie',
        badgeColor: 'bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800',
      },
      {
        name: 'Profil & Keamanan',
        path: '/profile',
        url: getUrl('/profile'),
        icon: UserIcon,
        active: props.currentRoute === 'profile',
        badge: 'RBAC',
        badgeColor: 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800',
      },
    ],
  },
]);
</script>

<template>
  <div>
    <!-- Backdrop (shown when sidebar is open in drawer mode) -->
    <transition
      enter-active-class="transition-opacity duration-300 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-200 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div 
        v-if="open && !isPinned" 
        @click="closeSidebar"
        class="fixed inset-0 z-40 bg-slate-950/80 backdrop-blur-sm"
      ></div>
    </transition>

    <!-- Sidebar Aside Element -->
    <aside
      class="fixed top-0 bottom-0 left-0 z-40 bg-white/95 dark:bg-slate-950/95 border-r border-slate-200 dark:border-slate-800/90 backdrop-blur-2xl flex flex-col transition-all duration-300 font-sans"
      :class="[
        // Visibility check: open OR pinned on desktop
        (open || isPinned) ? 'translate-x-0' : '-translate-x-full',
        // Desktop collapsed width
        isCollapsed ? 'w-20' : 'w-72 max-w-[85vw]',
        isPinned ? 'shadow-none' : 'shadow-2xl shadow-indigo-950/20 dark:shadow-indigo-950/40'
      ]"
    >
      <!-- Sidebar Header -->
      <div class="h-16 px-4 border-b border-slate-200 dark:border-slate-800/80 flex items-center justify-between">
        <a :href="getUrl('/')" @click="handleNavClick('/', false, $event)" class="flex items-center gap-3 overflow-hidden group cursor-pointer">
          <div 
            class="w-9 h-9 rounded-xl flex-shrink-0 flex items-center justify-center font-black text-white shadow-lg transition-all bg-gradient-to-tr from-indigo-600 via-purple-600 to-indigo-700 shadow-indigo-500/20"
          >
            <span class="text-xs tracking-wider">SR</span>
          </div>

          <div v-if="!isCollapsed" class="transition-opacity duration-200">
            <span class="block text-sm font-bold text-slate-900 dark:text-white tracking-tight group-hover:text-indigo-600 dark:group-hover:text-slate-200 transition-colors">
              Sepatu SR
            </span>
            <span class="block text-[10px] font-mono text-indigo-600 dark:text-indigo-400">
              Sistem Rekapitulasi
            </span>
          </div>
        </a>

        <!-- Controls: Collapse, Pin, Close -->
        <div class="flex items-center gap-1">
          <!-- Desktop Collapse Button -->
          <button
            type="button"
            @click="toggleCollapse"
            class="hidden lg:flex p-1.5 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800 transition-colors cursor-pointer"
            :title="isCollapsed ? 'Perluas Sidebar' : 'Ciutkan Sidebar'"
          >
            <ChevronRight v-if="isCollapsed" class="w-4 h-4" />
            <ChevronLeft v-else class="w-4 h-4" />
          </button>

          <!-- Pin/Unpin Button -->
          <button
            v-if="!isCollapsed"
            type="button"
            @click="togglePin"
            class="hidden lg:flex p-1.5 rounded-lg transition-colors cursor-pointer"
            :class="isPinned 
              ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 dark:hover:bg-indigo-900/60' 
              : 'text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80 border border-slate-200 dark:border-slate-800'"
            :title="isPinned ? 'Lepas Pin Sidebar' : 'Pin Sidebar di Sisi Kiri'"
          >
            <PinOff v-if="isPinned" class="w-4 h-4" />
            <Pin v-else class="w-4 h-4" />
          </button>

          <!-- Close Drawer Button -->
          <button
            type="button"
            @click="closeSidebar"
            class="p-1.5 rounded-lg text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            :class="isPinned ? 'lg:hidden' : ''"
            title="Tutup Navigasi"
          >
            <X class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- Navigation Links Container -->
      <div class="flex-1 overflow-y-auto px-3 py-4 space-y-5 scrollbar-thin scrollbar-thumb-slate-300 dark:scrollbar-thumb-slate-800">
        <div v-for="(section, sIndex) in navSections" :key="sIndex" class="space-y-1">
          <!-- Section Title (Hidden when collapsed) -->
          <div 
            v-if="!isCollapsed" 
            class="px-2.5 pb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500"
          >
            {{ section.title }}
          </div>
          <div v-else class="h-[1px] bg-slate-200 dark:bg-slate-800/70 my-2 mx-1"></div>

          <!-- Section Links -->
          <a
            v-for="(item, iIndex) in section.items"
            :key="iIndex"
            :href="item.url"
            :target="item.external ? '_blank' : '_self'"
            @click="handleNavClick(item.path, item.external, $event)"
            class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-medium transition-all group relative cursor-pointer"
            :class="item.active 
              ? (isSpa ? 'bg-teal-50 dark:bg-teal-600/20 text-teal-800 dark:text-teal-200 font-semibold border border-teal-200 dark:border-teal-700/40' : 'bg-indigo-50 dark:bg-indigo-600/20 text-indigo-800 dark:text-indigo-200 font-semibold border border-indigo-200 dark:border-indigo-700/40')
              : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-900 border border-transparent'"
            :title="isCollapsed ? item.name : ''"
          >
            <component 
              :is="item.icon" 
              class="w-4 h-4 flex-shrink-0 transition-transform group-hover:scale-110"
              :class="item.active ? (isSpa ? 'text-teal-600 dark:text-teal-400' : 'text-indigo-600 dark:text-indigo-400') : 'text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-200'"
            />

            <!-- Label & Badge -->
            <div v-if="!isCollapsed" class="flex-1 flex items-center justify-between overflow-hidden">
              <span class="truncate">{{ item.name }}</span>
              <span 
                v-if="item.badge" 
                class="text-[9px] font-mono px-1.5 py-0.2 rounded border"
                :class="item.badgeColor"
              >
                {{ item.badge }}
              </span>
            </div>

            <!-- Tooltip Popup when collapsed -->
            <div 
              v-if="isCollapsed" 
              class="absolute left-full ml-3 px-2.5 py-1 bg-slate-900 text-white text-[11px] font-medium rounded-lg shadow-xl border border-slate-700 whitespace-nowrap opacity-0 pointer-events-none group-hover:opacity-100 transition-opacity z-50"
            >
              {{ item.name }}
              <span v-if="item.external" class="text-cyan-400 ml-1">↗</span>
            </div>
          </a>
        </div>
      </div>

      <!-- Sidebar Footer: User Profile & Quick Logout -->
      <div class="p-3 border-t border-slate-200 dark:border-slate-800/80 bg-slate-50/90 dark:bg-slate-950/90">
        <div class="flex items-center gap-2.5" :class="isCollapsed ? 'justify-center' : 'justify-between'">
          
          <!-- Avatar + Info -->
          <a 
            :href="getUrl('/profile')"
            @click="handleNavClick('/profile', false, $event)"
            class="flex items-center gap-2.5 overflow-hidden group cursor-pointer"
            :title="isCollapsed ? (currentUser?.name || 'Profil Akun') : ''"
          >
            <img
              :src="currentUser?.avatar_url || 'https://ui-avatars.com/api/?name=Admin&background=6366f1&color=fff'"
              :alt="currentUser?.name || 'User'"
              class="w-8 h-8 rounded-full object-cover border border-indigo-500/40 flex-shrink-0 group-hover:border-indigo-400 transition-colors"
            />
            <div v-if="!isCollapsed" class="overflow-hidden text-left">
              <span class="block text-xs font-semibold text-slate-800 dark:text-slate-200 group-hover:text-indigo-600 dark:group-hover:text-white truncate transition-colors">
                {{ currentUser?.name || 'Administrator' }}
              </span>
              <span class="block text-[10px] font-mono text-indigo-600 dark:text-indigo-400 truncate">
                {{ currentUser?.roles?.[0] || 'Superadmin' }}
              </span>
            </div>
          </a>

          <!-- Logout Button -->
          <button
            v-if="!isCollapsed"
            type="button"
            @click="handleLogout"
            class="p-2 rounded-xl text-slate-400 hover:text-rose-600 dark:hover:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-950/40 border border-transparent hover:border-rose-200 dark:hover:border-rose-900/40 transition-all cursor-pointer"
            title="Keluar dari sesi"
          >
            <LogOut class="w-4 h-4" />
          </button>
        </div>
      </div>

    </aside>
  </div>
</template>
