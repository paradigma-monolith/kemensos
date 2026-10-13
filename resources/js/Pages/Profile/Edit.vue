<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import HybridNavbar from '../../Components/HybridNavbar.vue';
import { 
  User as UserIcon, 
  Mail, 
  Lock, 
  Camera, 
  ShieldCheck, 
  KeyRound, 
  CheckCircle2, 
  Save, 
  UploadCloud,
  Globe,
  Clock,
  ShieldAlert,
  Smartphone,
  Tablet,
  Laptop,
  Users,
  Activity,
  Radio
} from 'lucide-vue-next';

const props = defineProps({
  user: {
    type: Object,
    required: true,
  },
  login_histories: {
    type: Array,
    default: () => [],
  },
  current_ip: {
    type: String,
    default: '127.0.0.1',
  },
  scope: {
    type: String,
    default: 'all',
  },
  status: {
    type: String,
    default: null,
  },
});

const formatDate = (dateStr) => {
  if (!dateStr) return '-';
  try {
    const d = new Date(dateStr);
    return d.toLocaleString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch (e) {
    return dateStr;
  }
};

const getUrl = (path) => {
  if (typeof window !== 'undefined' && window.location.pathname.includes('/hybridlaravel/public')) {
    return `/hybridlaravel/public${path}`;
  }
  return path;
};

const setScope = (newScope) => {
  router.get(getUrl('/profile'), { scope: newScope }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const historyStats = computed(() => {
  const total = props.login_histories.length;
  const success = props.login_histories.filter(h => h.status === 'success').length;
  const failed = props.login_histories.filter(h => h.status === 'failed').length;
  const uniqueIps = new Set(props.login_histories.map(h => h.ip_address)).size;
  return { total, success, failed, uniqueIps };
});

// Form 1: Profile Info
const profileForm = useForm({
  name: props.user.name,
  email: props.user.email,
});

const updateProfile = () => {
  profileForm.patch(getUrl('/profile'), {
    preserveScroll: true,
  });
};

// Form 2: Password
const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const updatePassword = () => {
  passwordForm.put(getUrl('/profile/password'), {
    preserveScroll: true,
    onSuccess: () => passwordForm.reset(),
  });
};

// Form 3: Avatar Upload
const avatarPreview = ref(props.user.avatar_url);
const avatarInput = ref(null);
const avatarForm = useForm({
  avatar: null,
});

const handleAvatarChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    avatarForm.avatar = file;
    avatarPreview.value = URL.createObjectURL(file);
    avatarForm.post(getUrl('/profile/avatar'), {
      preserveScroll: true,
    });
  }
};

const triggerAvatarSelect = () => {
  avatarInput.value.click();
};
</script>

<template>
  <Head title="Pengaturan Profil & Keamanan - Hybrid Laravel" />

  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 selection:bg-indigo-500 selection:text-white pb-20 transition-colors duration-200 overflow-x-clip">
    <HybridNavbar mode="inertia" currentRoute="profile" />

    <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-8">
        <div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
            Pengaturan Akun &amp; Profil
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
            Kelola data profil, foto avatar, keamanan kata sandi, dan log riwayat sesi login.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <span v-for="role in user.roles" :key="role" class="px-3 py-1 rounded-full text-xs font-mono font-medium bg-indigo-100 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700/60 flex items-center gap-1.5">
            <ShieldCheck class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" />
            <span>{{ role }}</span>
          </span>
        </div>
      </div>

      <!-- Flash Status -->
      <div v-if="status" class="mb-6 p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-700/60 text-emerald-800 dark:text-emerald-300 text-xs flex items-center gap-2.5 shadow-sm">
        <CheckCircle2 class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
        <span>{{ status }}</span>
      </div>

      <!-- 2-Column Forms Grid (Cleanly Separated) -->
      <div class="grid lg:grid-cols-3 gap-6 mb-8">
        <!-- Left Column: Avatar & Roles Info Card -->
        <div class="lg:col-span-1 space-y-6">
          <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-3xl p-6 backdrop-blur-xl text-center shadow-sm">
            <div class="relative w-28 h-28 mx-auto mb-4 group cursor-pointer" @click="triggerAvatarSelect" title="Klik untuk mengubah foto profil">
              <img
                :src="avatarPreview"
                :alt="user.name"
                class="w-full h-full rounded-3xl object-cover border-2 border-indigo-500/40 shadow-lg group-hover:opacity-80 transition-opacity"
              />
              <div class="absolute inset-0 rounded-3xl bg-slate-950/60 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity text-white text-xs font-medium">
                <Camera class="w-5 h-5" />
              </div>
            </div>

            <input
              ref="avatarInput"
              type="file"
              accept="image/*"
              class="hidden"
              @change="handleAvatarChange"
            />

            <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ user.name }}</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-mono mt-0.5 truncate">{{ user.email }}</p>

            <button
              type="button"
              @click="triggerAvatarSelect"
              :disabled="avatarForm.processing"
              class="mt-4 w-full py-2.5 px-3 rounded-xl text-xs font-semibold bg-indigo-50 dark:bg-indigo-600/20 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-600/30 border border-indigo-200 dark:border-indigo-500/30 transition-all flex items-center justify-center gap-1.5 cursor-pointer"
            >
              <UploadCloud class="w-3.5 h-3.5" />
              <span>{{ avatarForm.processing ? 'Mengunggah...' : 'Ubah Foto Avatar' }}</span>
            </button>
            <p v-if="avatarForm.errors.avatar" class="text-xs text-rose-500 mt-2">
              {{ avatarForm.errors.avatar }}
            </p>
          </div>

          <!-- Permissions Badge Card -->
          <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-3xl p-5 backdrop-blur-xl shadow-sm">
            <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3 flex items-center gap-1.5 font-mono">
              <ShieldCheck class="w-3.5 h-3.5 text-indigo-500" />
              <span>Izin Hak Akses (RBAC)</span>
            </h4>
            <div class="flex flex-wrap gap-1.5">
              <span v-for="perm in user.permissions" :key="perm" class="px-2 py-0.5 rounded-lg text-[10px] font-mono bg-slate-100 dark:bg-slate-950 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-800">
                {{ perm }}
              </span>
              <span v-if="user.permissions.length === 0" class="text-xs text-slate-500 italic">
                Akses level default
              </span>
            </div>
          </div>
        </div>

        <!-- Right Column: Profile & Password Forms -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Profile Information Form -->
          <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-sm">
            <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white mb-0.5">Informasi Pengguna</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-5">Perbarui nama lengkap dan alamat email akun Anda.</p>

            <form @submit.prevent="updateProfile" class="space-y-4">
              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 font-mono">Nama Lengkap</label>
                <div class="mt-1.5 relative rounded-xl shadow-xs">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <UserIcon class="w-4 h-4" />
                  </div>
                  <input
                    v-model="profileForm.name"
                    type="text"
                    required
                    class="block w-full pl-10 pr-3.5 py-2.5 text-sm bg-slate-50 dark:bg-slate-950/70 border rounded-xl text-slate-900 dark:text-white border-slate-200 dark:border-slate-800 focus:border-indigo-500 focus:outline-none transition-colors"
                  />
                </div>
                <p v-if="profileForm.errors.name" class="text-xs text-rose-500 mt-1">{{ profileForm.errors.name }}</p>
              </div>

              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 font-mono">Alamat Email</label>
                <div class="mt-1.5 relative rounded-xl shadow-xs">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <Mail class="w-4 h-4" />
                  </div>
                  <input
                    v-model="profileForm.email"
                    type="email"
                    required
                    class="block w-full pl-10 pr-3.5 py-2.5 text-sm bg-slate-50 dark:bg-slate-950/70 border rounded-xl text-slate-900 dark:text-white border-slate-200 dark:border-slate-800 focus:border-indigo-500 focus:outline-none font-mono transition-colors"
                  />
                </div>
                <p v-if="profileForm.errors.email" class="text-xs text-rose-500 mt-1">{{ profileForm.errors.email }}</p>
              </div>

              <div class="pt-2 flex justify-end">
                <button
                  type="submit"
                  :disabled="profileForm.processing"
                  class="py-2.5 px-5 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/20 transition-all flex items-center gap-1.5 disabled:opacity-60 cursor-pointer"
                >
                  <Save class="w-3.5 h-3.5" />
                  <span>{{ profileForm.processing ? 'Menyimpan...' : 'Simpan Profil' }}</span>
                </button>
              </div>
            </form>
          </div>

          <!-- Update Password Form -->
          <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-sm">
            <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white mb-0.5">Keamanan Kata Sandi</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mb-5">Pastikan akun Anda menggunakan kata sandi yang kuat dan aman.</p>

            <form @submit.prevent="updatePassword" class="space-y-4">
              <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 font-mono">Kata Sandi Saat Ini</label>
                <div class="mt-1.5 relative rounded-xl shadow-xs">
                  <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <Lock class="w-4 h-4" />
                  </div>
                  <input
                    v-model="passwordForm.current_password"
                    type="password"
                    required
                    placeholder="••••••••"
                    class="block w-full pl-10 pr-3.5 py-2.5 text-sm bg-slate-50 dark:bg-slate-950/70 border rounded-xl text-slate-900 dark:text-white border-slate-200 dark:border-slate-800 focus:border-indigo-500 focus:outline-none font-mono transition-colors"
                  />
                </div>
                <p v-if="passwordForm.errors.current_password" class="text-xs text-rose-500 mt-1">{{ passwordForm.errors.current_password }}</p>
              </div>

              <div class="grid sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 font-mono">Kata Sandi Baru</label>
                  <div class="mt-1.5 relative rounded-xl shadow-xs">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                      <KeyRound class="w-4 h-4" />
                    </div>
                    <input
                      v-model="passwordForm.password"
                      type="password"
                      required
                      placeholder="Minimal 8 karakter"
                      class="block w-full pl-10 pr-3.5 py-2.5 text-sm bg-slate-50 dark:bg-slate-950/70 border rounded-xl text-slate-900 dark:text-white border-slate-200 dark:border-slate-800 focus:border-indigo-500 focus:outline-none font-mono transition-colors"
                    />
                  </div>
                  <p v-if="passwordForm.errors.password" class="text-xs text-rose-500 mt-1">{{ passwordForm.errors.password }}</p>
                </div>

                <div>
                  <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 font-mono">Ulangi Kata Sandi Baru</label>
                  <div class="mt-1.5 relative rounded-xl shadow-xs">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                      <KeyRound class="w-4 h-4" />
                    </div>
                    <input
                      v-model="passwordForm.password_confirmation"
                      type="password"
                      required
                      placeholder="Konfirmasi sandi"
                      class="block w-full pl-10 pr-3.5 py-2.5 text-sm bg-slate-50 dark:bg-slate-950/70 border rounded-xl text-slate-900 dark:text-white border-slate-200 dark:border-slate-800 focus:border-indigo-500 focus:outline-none font-mono transition-colors"
                    />
                  </div>
                </div>
              </div>

              <div class="pt-2 flex justify-end">
                <button
                  type="submit"
                  :disabled="passwordForm.processing"
                  class="py-2.5 px-5 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-md shadow-indigo-600/20 transition-all flex items-center gap-1.5 disabled:opacity-60 cursor-pointer"
                >
                  <Lock class="w-3.5 h-3.5" />
                  <span>{{ passwordForm.processing ? 'Menyimpan...' : 'Perbarui Kata Sandi' }}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Full-Width Section: Riwayat Sesi & Keamanan Login Pengguna -->
      <div class="bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 rounded-2xl sm:rounded-3xl p-4 sm:p-6 lg:p-8 backdrop-blur-xl shadow-xl transition-colors">
        <!-- Section Header -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 sm:pb-6 border-b border-slate-200 dark:border-slate-800/80 mb-5 sm:mb-6">
          <div class="flex items-center gap-3 sm:gap-3.5">
            <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-2xl bg-cyan-100 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-700/60 flex items-center justify-center flex-shrink-0 shadow-xs">
              <Globe class="w-5 h-5" />
            </div>
            <div>
              <h2 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                <span>Riwayat Sesi &amp; Keamanan Login</span>
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-cyan-100 dark:bg-cyan-950/70 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-700/60 font-mono">
                  IP &amp; Device Audit
                </span>
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Pemantauan alamat IP, perangkat, sistem operasi, peramban, dan riwayat login akun.
              </p>
            </div>
          </div>

          <!-- Controls: Scope Switcher for Super Admin & Current IP Indicator -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between lg:justify-end gap-2.5 sm:gap-3 w-full lg:w-auto">
            <!-- Super Admin Scope Switcher -->
            <div v-if="user.is_super_admin" class="grid grid-cols-2 sm:flex sm:items-center gap-1 p-1 rounded-2xl bg-slate-100 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 text-xs font-medium w-full sm:w-auto">
              <button
                type="button"
                @click="setScope('all')"
                class="px-2.5 sm:px-3 py-1.5 rounded-xl font-semibold transition-all flex items-center justify-center gap-1.5 cursor-pointer text-[11px] sm:text-xs"
                :class="scope === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
              >
                <Users class="w-3.5 h-3.5 shrink-0" />
                <span class="truncate">Semua Akun</span>
                <span class="hidden md:inline">(Super Admin)</span>
              </button>
              <button
                type="button"
                @click="setScope('me')"
                class="px-2.5 sm:px-3 py-1.5 rounded-xl font-semibold transition-all flex items-center justify-center gap-1.5 cursor-pointer text-[11px] sm:text-xs"
                :class="scope === 'me' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
              >
                <UserIcon class="w-3.5 h-3.5 shrink-0" />
                <span class="truncate">Hanya Akun Saya</span>
              </button>
            </div>

            <!-- Current IP Indicator Pill -->
            <div class="flex items-center justify-between sm:justify-start gap-2 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-950/90 border border-slate-200 dark:border-slate-800 text-xs font-mono w-full sm:w-auto">
              <div class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span class="text-slate-500 dark:text-slate-400">IP Anda:</span>
              </div>
              <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ current_ip }}</span>
            </div>
          </div>
        </div>

        <!-- Metric Summary Cards (Responsive 2-cols mobile, 4-cols tablet & desktop) -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-3 mb-5 sm:mb-6">
          <div class="p-3 sm:p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80">
            <div class="text-[10px] font-mono text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Riwayat</div>
            <div class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white mt-0.5">{{ historyStats.total }}</div>
          </div>
          <div class="p-3 sm:p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80">
            <div class="text-[10px] font-mono text-slate-500 dark:text-slate-400 uppercase tracking-wider">Login Berhasil</div>
            <div class="text-base sm:text-lg font-extrabold text-emerald-600 dark:text-emerald-400 mt-0.5">{{ historyStats.success }}</div>
          </div>
          <div class="p-3 sm:p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80">
            <div class="text-[10px] font-mono text-slate-500 dark:text-slate-400 uppercase tracking-wider">Upaya Gagal</div>
            <div class="text-base sm:text-lg font-extrabold text-rose-600 dark:text-rose-400 mt-0.5">{{ historyStats.failed }}</div>
          </div>
          <div class="p-3 sm:p-3.5 rounded-2xl bg-slate-50 dark:bg-slate-950/60 border border-slate-200 dark:border-slate-800/80">
            <div class="text-[10px] font-mono text-slate-500 dark:text-slate-400 uppercase tracking-wider">IP Unik</div>
            <div class="text-base sm:text-lg font-extrabold text-cyan-600 dark:text-cyan-400 mt-0.5">{{ historyStats.uniqueIps }}</div>
          </div>
        </div>

        <div v-if="login_histories && login_histories.length > 0">
          <!-- 1. Desktop & Tablet View: Full Data Table (Hidden on Mobile) -->
          <div class="hidden md:block overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800/80">
            <table class="w-full text-left text-xs font-mono">
              <thead class="uppercase bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 text-[11px]">
                <tr>
                  <th class="px-4 py-3.5 whitespace-nowrap">Waktu</th>
                  <th class="px-4 py-3.5 whitespace-nowrap">Status</th>
                  <th v-if="user.is_super_admin && scope === 'all'" class="px-4 py-3.5 whitespace-nowrap">Akun / Pengguna</th>
                  <th class="px-4 py-3.5 whitespace-nowrap">Alamat IP</th>
                  <th class="px-4 py-3.5 whitespace-nowrap">Perangkat &amp; Platform</th>
                  <th class="px-4 py-3.5 whitespace-nowrap">Peramban (Browser)</th>
                  <th class="px-4 py-3.5 whitespace-nowrap">Lokasi / Jaringan</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 bg-white dark:bg-slate-900/40">
                <tr v-for="item in login_histories" :key="'desk-' + item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition-colors">
                  <!-- Waktu -->
                  <td class="px-4 py-3.5 whitespace-nowrap text-slate-700 dark:text-slate-300">
                    <div class="flex items-center gap-1.5">
                      <Clock class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" />
                      <span>{{ formatDate(item.login_at) }}</span>
                    </div>
                  </td>

                  <!-- Status -->
                  <td class="px-4 py-3.5 whitespace-nowrap">
                    <span v-if="item.status === 'success'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700/60">
                      <CheckCircle2 class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                      <span>Berhasil</span>
                    </span>
                    <span v-else class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-700/60">
                      <ShieldAlert class="w-3 h-3 text-rose-600 dark:text-rose-400" />
                      <span>Gagal</span>
                    </span>
                  </td>

                  <!-- User / Account Column (Super Admin Scope) -->
                  <td v-if="user.is_super_admin && scope === 'all'" class="px-4 py-3.5 whitespace-nowrap font-sans">
                    <div v-if="item.user" class="flex items-center gap-2">
                      <img
                        :src="item.user.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(item.user.name)"
                        class="w-6 h-6 rounded-full object-cover border border-slate-300 dark:border-slate-700 shrink-0"
                        :alt="item.user.name"
                      />
                      <div class="min-w-0">
                        <div class="font-semibold text-slate-900 dark:text-white text-xs leading-tight truncate">{{ item.user.name }}</div>
                        <div class="text-[10px] text-slate-500 font-mono leading-tight truncate">{{ item.user.email }}</div>
                      </div>
                    </div>
                    <div v-else class="flex items-center gap-1.5 text-rose-600 dark:text-rose-400">
                      <ShieldAlert class="w-3.5 h-3.5 shrink-0" />
                      <span class="text-xs font-mono font-semibold truncate">{{ item.email || 'Anonim' }}</span>
                    </div>
                  </td>

                  <!-- Alamat IP -->
                  <td class="px-4 py-3.5 whitespace-nowrap">
                    <div class="flex items-center gap-2">
                      <span class="font-bold text-slate-900 dark:text-slate-100">{{ item.ip_address }}</span>
                      <span v-if="item.ip_address === current_ip" class="px-1.5 py-0.5 text-[9px] rounded-md bg-emerald-100 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-500/40 font-semibold">
                        Sesi Ini
                      </span>
                    </div>
                  </td>

                  <!-- Perangkat & Platform -->
                  <td class="px-4 py-3.5 whitespace-nowrap">
                    <div class="flex items-center gap-2 text-slate-800 dark:text-slate-200">
                      <Smartphone v-if="item.device === 'Mobile'" class="w-3.5 h-3.5 text-indigo-500" />
                      <Tablet v-else-if="item.device === 'Tablet'" class="w-3.5 h-3.5 text-indigo-500" />
                      <Laptop v-else class="w-3.5 h-3.5 text-indigo-500" />
                      <span>{{ item.device || 'Desktop' }} &bull; {{ item.platform || 'OS' }}</span>
                    </div>
                  </td>

                  <!-- Peramban -->
                  <td class="px-4 py-3.5 whitespace-nowrap text-slate-600 dark:text-slate-400">
                    {{ item.browser || 'Web Browser' }}
                  </td>

                  <!-- Lokasi / Jaringan -->
                  <td class="px-4 py-3.5 whitespace-nowrap text-slate-600 dark:text-slate-400">
                    {{ item.location || 'Jaringan Lokal' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- 2. Mobile View: Responsive Card Feed (Visible on Mobile < 768px) -->
          <div class="block md:hidden space-y-3">
            <div 
              v-for="item in login_histories" 
              :key="'mob-' + item.id"
              class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800/80 space-y-3 transition-colors shadow-2xs"
            >
              <!-- Card Top: Time and Status -->
              <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400 font-mono">
                  <Clock class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500 shrink-0" />
                  <span>{{ formatDate(item.login_at) }}</span>
                </div>
                <span v-if="item.status === 'success'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700/60">
                  <CheckCircle2 class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                  <span>Berhasil</span>
                </span>
                <span v-else class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-700/60">
                  <ShieldAlert class="w-3 h-3 text-rose-600 dark:text-rose-400" />
                  <span>Gagal</span>
                </span>
              </div>

              <!-- Super Admin Scope: Account Details -->
              <div v-if="user.is_super_admin && scope === 'all'" class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center gap-2.5">
                <template v-if="item.user">
                  <img
                    :src="item.user.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(item.user.name)"
                    class="w-7 h-7 rounded-full object-cover border border-slate-300 dark:border-slate-700 shrink-0"
                    :alt="item.user.name"
                  />
                  <div class="min-w-0 flex-1">
                    <div class="font-semibold text-slate-900 dark:text-white text-xs truncate leading-tight">{{ item.user.name }}</div>
                    <div class="text-[10px] text-slate-500 font-mono truncate leading-tight mt-0.5">{{ item.user.email }}</div>
                  </div>
                </template>
                <template v-else>
                  <div class="w-7 h-7 rounded-full bg-rose-100 dark:bg-rose-950 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                    <ShieldAlert class="w-4 h-4" />
                  </div>
                  <div class="min-w-0 flex-1">
                    <div class="font-semibold text-rose-600 dark:text-rose-400 text-xs truncate leading-tight">Upaya Login Anonim</div>
                    <div class="text-[10px] text-slate-500 font-mono truncate leading-tight mt-0.5">{{ item.email || 'Email tidak dikenal' }}</div>
                  </div>
                </template>
              </div>

              <!-- IP & Location Details Grid -->
              <div class="grid grid-cols-2 gap-2 text-xs font-mono">
                <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-800/80">
                  <div class="text-[9px] uppercase tracking-wider text-slate-400 font-semibold mb-0.5">Alamat IP</div>
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="font-bold text-slate-900 dark:text-slate-100 text-[11px]">{{ item.ip_address }}</span>
                    <span v-if="item.ip_address === current_ip" class="px-1 py-0.2 text-[8px] rounded bg-emerald-100 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 font-semibold border border-emerald-300 dark:border-emerald-500/40">
                      Sesi Ini
                    </span>
                  </div>
                </div>

                <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-800/80">
                  <div class="text-[9px] uppercase tracking-wider text-slate-400 font-semibold mb-0.5">Jaringan / Lokasi</div>
                  <div class="text-slate-700 dark:text-slate-300 text-[11px] truncate">{{ item.location || 'Jaringan Lokal' }}</div>
                </div>
              </div>

              <!-- Device, OS & Browser Pill -->
              <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-200/80 dark:border-slate-800/60 text-xs">
                <div class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300 font-mono text-[11px]">
                  <Smartphone v-if="item.device === 'Mobile'" class="w-3.5 h-3.5 text-indigo-500 shrink-0" />
                  <Tablet v-else-if="item.device === 'Tablet'" class="w-3.5 h-3.5 text-indigo-500 shrink-0" />
                  <Laptop v-else class="w-3.5 h-3.5 text-indigo-500 shrink-0" />
                  <span>{{ item.device || 'Desktop' }} &bull; {{ item.platform || 'OS' }}</span>
                </div>
                <div class="text-[10px] font-mono text-slate-500 dark:text-slate-400 px-2 py-0.5 rounded-lg bg-slate-200/60 dark:bg-slate-800/60 truncate max-w-[130px]">
                  {{ item.browser || 'Web' }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="text-center py-10 text-xs text-slate-500 dark:text-slate-400 font-mono">
          <Globe class="w-8 h-8 text-slate-400 dark:text-slate-600 mx-auto mb-2 opacity-60" />
          <p>Belum ada riwayat sesi login yang tercatat.</p>
        </div>
      </div>
    </main>
  </div>
</template>

