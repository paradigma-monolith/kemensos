<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import HybridNavbar from '../../Components/HybridNavbar.vue';
import { 
  History, 
  ShieldAlert, 
  CheckCircle2, 
  Clock, 
  User as UserIcon, 
  Globe, 
  Smartphone, 
  Tablet, 
  Laptop,
  ShieldCheck,
  Activity
} from 'lucide-vue-next';

const props = defineProps({
  activities: {
    type: Object,
    required: true,
  },
  login_histories: {
    type: Object,
    default: () => ({ data: [] }),
  },
  current_ip: {
    type: String,
    default: '127.0.0.1',
  },
});

const activeTab = ref('activities'); // 'activities' | 'logins'

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
</script>

<template>
  <Head title="Audit Trail & Riwayat Keamanan - Hybrid Laravel" />

  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 selection:bg-indigo-500 selection:text-white pb-20 transition-colors duration-200 overflow-x-clip">
    <HybridNavbar mode="inertia" currentRoute="audit" />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
        <div>
          <div class="inline-flex items-center gap-2 text-xs font-mono font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider mb-1">
            <History class="w-4 h-4" />
            <span>Audit Trail &bull; Enterprise Security</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
            Pusat Audit &amp; Keamanan Sistem
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
            Rekam jejak mutasi database, aktivitas operasional, serta riwayat login seluruh akun pengguna.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 sm:gap-3 w-full sm:w-auto">
          <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-white dark:bg-slate-900/90 border border-slate-200 dark:border-slate-800 text-xs font-mono shadow-xs">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-slate-500 dark:text-slate-400">IP Anda:</span>
            <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ current_ip }}</span>
          </div>

          <a :href="getUrl('/api/v1/audit-logs')" target="_blank" class="px-3 py-1.5 rounded-xl font-mono text-xs text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-900 hover:bg-slate-100 dark:hover:bg-slate-800 border border-slate-200 dark:border-slate-800 flex items-center gap-1.5 shadow-xs transition-colors">
            <span>JSON Feed</span>
            <span class="text-slate-400">↗</span>
          </a>
        </div>
      </div>

      <!-- Tab Switcher Navigation (Responsive segmented control) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 mb-6 p-1.5 bg-slate-200/70 dark:bg-slate-900/90 rounded-2xl border border-slate-200 dark:border-slate-800 w-full sm:w-auto sm:inline-flex">
        <button
          type="button"
          @click="activeTab = 'activities'"
          class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all flex items-center justify-between sm:justify-start gap-2 cursor-pointer"
          :class="activeTab === 'activities' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
        >
          <div class="flex items-center gap-2">
            <Activity class="w-4 h-4 text-indigo-500 shrink-0" />
            <span>Log Aktivitas Data (Spatie)</span>
          </div>
          <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-mono">
            {{ activities.total || activities.data.length }}
          </span>
        </button>

        <button
          type="button"
          @click="activeTab = 'logins'"
          class="px-3.5 py-2 rounded-xl text-xs font-semibold transition-all flex items-center justify-between sm:justify-start gap-2 cursor-pointer"
          :class="activeTab === 'logins' ? 'bg-white dark:bg-slate-800 text-slate-900 dark:text-white shadow-sm' : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
        >
          <div class="flex items-center gap-2">
            <Globe class="w-4 h-4 text-cyan-500 shrink-0" />
            <span>Riwayat Login IP (Semua Akun)</span>
          </div>
          <span class="text-[10px] px-1.5 py-0.2 rounded-full bg-cyan-100 dark:bg-cyan-950 text-cyan-700 dark:text-cyan-300 font-mono">
            {{ login_histories.total || login_histories.data.length }}
          </span>
        </button>
      </div>

      <!-- Tab 1: Activity Log Table (Spatie) -->
      <div v-if="activeTab === 'activities'" class="bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 rounded-2xl sm:rounded-3xl p-4 sm:p-6 backdrop-blur-xl shadow-xl transition-colors">
        <!-- Desktop/Tablet View -->
        <div class="hidden md:block overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800/80">
          <table class="w-full text-left text-sm text-slate-700 dark:text-slate-300">
            <thead class="text-xs uppercase bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th class="px-5 py-3.5">Waktu</th>
                <th class="px-5 py-3.5">Event</th>
                <th class="px-5 py-3.5">Pelaku (Causer)</th>
                <th class="px-5 py-3.5">Objek / Modul</th>
                <th class="px-5 py-3.5">Deskripsi</th>
                <th class="px-5 py-3.5">Perubahan (Properties)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 font-mono text-xs">
              <tr v-for="item in activities.data" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                <td class="px-5 py-3.5 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                  {{ formatDate(item.created_at) }}
                </td>
                <td class="px-5 py-3.5">
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold border"
                    :class="{
                      'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700/60': item.event === 'created',
                      'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border-amber-300 dark:border-amber-700/60': item.event === 'updated',
                      'bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-300 border-rose-300 dark:border-rose-700/60': item.event === 'deleted',
                      'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700': !item.event
                    }">
                    {{ item.event || 'action' }}
                  </span>
                </td>
                <td class="px-5 py-3.5 text-slate-800 dark:text-slate-200 font-sans">
                  <div class="flex items-center gap-1.5">
                    <UserIcon class="w-3.5 h-3.5 text-indigo-500" />
                    <span>{{ item.causer?.name || 'Sistem / Anonim' }}</span>
                  </div>
                </td>
                <td class="px-5 py-3.5 text-indigo-600 dark:text-indigo-300 font-sans">
                  {{ item.subject_type ? item.subject_type.split('\\').pop() : 'System' }} #{{ item.subject_id || '-' }}
                </td>
                <td class="px-5 py-3.5 text-slate-700 dark:text-slate-300 font-sans">
                  {{ item.description }}
                </td>
                <td class="px-5 py-3.5 text-slate-500 dark:text-slate-400 max-w-xs truncate">
                  <span v-if="item.properties && Object.keys(item.properties).length" class="text-slate-700 dark:text-slate-300 font-mono text-[10px]">
                    {{ JSON.stringify(item.properties) }}
                  </span>
                  <span v-else class="text-slate-400">-</span>
                </td>
              </tr>

              <tr v-if="activities.data.length === 0">
                <td colspan="6" class="px-5 py-10 text-center text-slate-500 font-sans text-sm">
                  Belum ada log aktivitas tercatat. Tambah atau ubah produk untuk menguji pencatatan audit trail!
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile View: Activity Card Feed -->
        <div class="block md:hidden space-y-3">
          <div 
            v-for="item in activities.data" 
            :key="'mob-act-' + item.id"
            class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800/80 space-y-2.5 transition-colors shadow-2xs"
          >
            <div class="flex items-center justify-between gap-2">
              <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                {{ formatDate(item.created_at) }}
              </span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border"
                :class="{
                  'bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700/60': item.event === 'created',
                  'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border-amber-300 dark:border-amber-700/60': item.event === 'updated',
                  'bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-300 border-rose-300 dark:border-rose-700/60': item.event === 'deleted',
                  'bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700': !item.event
                }">
                {{ item.event || 'action' }}
              </span>
            </div>

            <div class="flex items-center gap-2 text-xs font-semibold text-slate-900 dark:text-white">
              <UserIcon class="w-3.5 h-3.5 text-indigo-500 shrink-0" />
              <span>{{ item.causer?.name || 'Sistem / Anonim' }}</span>
              <span class="text-slate-400">&bull;</span>
              <span class="text-indigo-600 dark:text-indigo-400 font-mono text-[11px]">
                {{ item.subject_type ? item.subject_type.split('\\').pop() : 'System' }} #{{ item.subject_id || '-' }}
              </span>
            </div>

            <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
              {{ item.description }}
            </p>

            <div v-if="item.properties && Object.keys(item.properties).length" class="p-2 rounded-xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 text-[10px] font-mono text-slate-500 dark:text-slate-400 break-all">
              {{ JSON.stringify(item.properties) }}
            </div>
          </div>

          <div v-if="activities.data.length === 0" class="py-8 text-center text-xs text-slate-500">
            Belum ada log aktivitas tercatat.
          </div>
        </div>
      </div>

      <!-- Tab 2: Login Histories (All Accounts) -->
      <div v-else class="bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-800/80 rounded-2xl sm:rounded-3xl p-4 sm:p-6 backdrop-blur-xl shadow-xl transition-colors">
        <!-- Desktop/Tablet View -->
        <div class="hidden md:block overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800/80">
          <table class="w-full text-left text-xs font-mono">
            <thead class="uppercase bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 text-[11px]">
              <tr>
                <th class="px-5 py-3.5 whitespace-nowrap">Waktu</th>
                <th class="px-5 py-3.5 whitespace-nowrap">Status</th>
                <th class="px-5 py-3.5 whitespace-nowrap font-sans">Akun / Pengguna</th>
                <th class="px-5 py-3.5 whitespace-nowrap">Alamat IP</th>
                <th class="px-5 py-3.5 whitespace-nowrap">Perangkat &amp; Platform</th>
                <th class="px-5 py-3.5 whitespace-nowrap">Peramban (Browser)</th>
                <th class="px-5 py-3.5 whitespace-nowrap">Lokasi / Jaringan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 dark:divide-slate-800/60 bg-white dark:bg-slate-900/40">
              <tr v-for="item in login_histories.data" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                <!-- Waktu -->
                <td class="px-5 py-3.5 whitespace-nowrap text-slate-600 dark:text-slate-300">
                  <div class="flex items-center gap-1.5">
                    <Clock class="w-3.5 h-3.5 text-slate-400 dark:text-slate-500" />
                    <span>{{ formatDate(item.login_at) }}</span>
                  </div>
                </td>

                <!-- Status -->
                <td class="px-5 py-3.5 whitespace-nowrap">
                  <span v-if="item.status === 'success'" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-700/60">
                    <CheckCircle2 class="w-3 h-3 text-emerald-600 dark:text-emerald-400" />
                    <span>Berhasil</span>
                  </span>
                  <span v-else class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-rose-100 dark:bg-rose-950/60 text-rose-800 dark:text-rose-300 border border-rose-300 dark:border-rose-700/60">
                    <ShieldAlert class="w-3 h-3 text-rose-600 dark:text-rose-400" />
                    <span>Gagal</span>
                  </span>
                </td>

                <!-- Akun / Pengguna -->
                <td class="px-5 py-3.5 whitespace-nowrap font-sans">
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
                <td class="px-5 py-3.5 whitespace-nowrap">
                  <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-900 dark:text-slate-100">{{ item.ip_address }}</span>
                    <span v-if="item.ip_address === current_ip" class="px-1.5 py-0.5 text-[9px] rounded-md bg-emerald-100 dark:bg-emerald-500/20 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-500/40 font-semibold">
                      Sesi Ini
                    </span>
                  </div>
                </td>

                <!-- Perangkat & Platform -->
                <td class="px-5 py-3.5 whitespace-nowrap">
                  <div class="flex items-center gap-2 text-slate-800 dark:text-slate-200">
                    <Smartphone v-if="item.device === 'Mobile'" class="w-3.5 h-3.5 text-indigo-500" />
                    <Tablet v-else-if="item.device === 'Tablet'" class="w-3.5 h-3.5 text-indigo-500" />
                    <Laptop v-else class="w-3.5 h-3.5 text-indigo-500" />
                    <span>{{ item.device || 'Desktop' }} &bull; {{ item.platform || 'OS' }}</span>
                  </div>
                </td>

                <!-- Peramban -->
                <td class="px-5 py-3.5 whitespace-nowrap text-slate-600 dark:text-slate-400">
                  {{ item.browser || 'Web Browser' }}
                </td>

                <!-- Lokasi -->
                <td class="px-5 py-3.5 whitespace-nowrap text-slate-600 dark:text-slate-400">
                  {{ item.location || 'Jaringan Lokal' }}
                </td>
              </tr>

              <tr v-if="login_histories.data.length === 0">
                <td colspan="7" class="px-5 py-10 text-center text-slate-500 font-sans text-sm">
                  Belum ada log riwayat login tercatat.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Mobile View: Login History Card Feed -->
        <div class="block md:hidden space-y-3">
          <div 
            v-for="item in login_histories.data" 
            :key="'mob-log-' + item.id"
            class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-950/70 border border-slate-200 dark:border-slate-800/80 space-y-3 transition-colors shadow-2xs"
          >
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

            <!-- User Info Card -->
            <div class="p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center gap-2.5">
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

            <!-- IP & Location Grid -->
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

          <div v-if="login_histories.data.length === 0" class="py-8 text-center text-xs text-slate-500">
            Belum ada log riwayat login tercatat.
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
