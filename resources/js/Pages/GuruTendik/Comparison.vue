<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import HybridNavbar from '../../Components/HybridNavbar.vue';
import {
  ArrowLeftRight,
  FileSpreadsheet,
  Users,
  CheckCircle2,
  XCircle,
  AlertTriangle,
  School as SchoolIcon,
  Search,
  Filter,
  BarChart3,
  Layers,
  Table2,
  Sparkles,
  UserCheck,
  UserX,
  UserPlus,
  Footprints,
  Info,
  X,
  ChevronRight
} from 'lucide-vue-next';

const props = defineProps({
  summary: {
    type: Object,
    required: true,
  },
  schoolComparisons: {
    type: Array,
    default: () => [],
  },
  records: {
    type: Object,
    required: true,
  },
  filterOptions: {
    type: Object,
    default: () => ({ schools: [], status_kepegawaian: [] }),
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

// Detect subdirectory prefix if hosted under /public
const prefix = computed(() => {
  if (typeof window !== 'undefined') {
    const match = window.location.pathname.match(/^(\/[^\/]+\/public)/);
    if (match) return match[1];
  }
  return '';
});

const getUrl = (path) => `${prefix.value}${path}`;

// Active Tab ('records' | 'schools' | 'analytics')
const activeTab = ref(props.filters?.tab || 'records');

// Filter States
const search = ref(props.filters?.search || '');
const matchStatus = ref(props.filters?.match_status || '');
const selectedSekolah = ref(props.filters?.sekolah || '');
const statusSekolah = ref(props.filters?.status_sekolah || '');
const selectedGender = ref(props.filters?.jenis_kelamin || '');
const selectedKategori = ref(props.filters?.kategori || '');
const selectedStatusPeg = ref(props.filters?.status_kepegawaian || '');

let searchTimeout = null;
const buildFilterQuery = (overrideTab = null) => {
  const q = {};
  if (search.value) q.search = search.value;
  if (matchStatus.value) q.match_status = matchStatus.value;
  if (selectedSekolah.value) q.sekolah = selectedSekolah.value;
  if (statusSekolah.value) q.status_sekolah = statusSekolah.value;
  if (selectedGender.value) q.jenis_kelamin = selectedGender.value;
  if (selectedKategori.value) q.kategori = selectedKategori.value;
  if (selectedStatusPeg.value) q.status_kepegawaian = selectedStatusPeg.value;
  q.tab = overrideTab || activeTab.value;
  return q;
};

const applyFilters = (overrideTab = null) => {
  if (overrideTab) {
    activeTab.value = overrideTab;
  }
  router.get(
    getUrl('/guru-tendik-comparison'),
    buildFilterQuery(overrideTab),
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};

const onSearchInput = () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 350);
};

const filterByMatchStatus = (status) => {
  matchStatus.value = matchStatus.value === status ? '' : status;
  applyFilters('records');
};

const filterBySchoolStatus = (st) => {
  statusSekolah.value = statusSekolah.value === st ? '' : st;
  applyFilters();
};

const inspectSchool = (schoolName, statusFilter = '') => {
  selectedSekolah.value = schoolName;
  matchStatus.value = statusFilter;
  applyFilters('records');
};

const resetFilters = () => {
  search.value = '';
  matchStatus.value = '';
  selectedSekolah.value = '';
  statusSekolah.value = '';
  selectedGender.value = '';
  selectedKategori.value = '';
  selectedStatusPeg.value = '';
  applyFilters();
};

const hasActiveFilters = computed(() => {
  return Boolean(
    search.value ||
    matchStatus.value ||
    selectedSekolah.value ||
    statusSekolah.value ||
    selectedGender.value ||
    selectedKategori.value ||
    selectedStatusPeg.value
  );
});

const goToPage = (targetUrl) => {
  if (!targetUrl) return;
  try {
    const urlObj = new URL(targetUrl, window.location.origin);
    urlObj.searchParams.set('tab', 'records');
    router.get(urlObj.pathname + urlObj.search, {}, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  } catch (e) {
    router.get(targetUrl, {}, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  }
};

const excelExportUrl = computed(() => {
  const params = new URLSearchParams();
  if (matchStatus.value) params.append('match_status', matchStatus.value);
  if (selectedSekolah.value) params.append('sekolah', selectedSekolah.value);
  if (statusSekolah.value) params.append('status_sekolah', statusSekolah.value);
  if (selectedGender.value) params.append('jenis_kelamin', selectedGender.value);
  if (selectedKategori.value) params.append('kategori', selectedKategori.value);
  if (selectedStatusPeg.value) params.append('status_kepegawaian', selectedStatusPeg.value);
  if (search.value) params.append('search', search.value);
  const query = params.toString();
  return getUrl(query ? `/guru-tendik-comparison/export/excel?${query}` : '/guru-tendik-comparison/export/excel');
});

const maxShoeSizeCount = computed(() => {
  const sizes = props.summary?.breakdowns?.shoe_sizes || [];
  if (!sizes.length) return 1;
  return Math.max(1, ...sizes.map((s) => s.total));
});
</script>

<template>
  <Head title="Perbandingan Sepatu vs Guru & Tendik" />

  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 transition-colors duration-300">
    <!-- Top Hybrid Navigation Bar -->
    <HybridNavbar currentRoute="guru-tendik-comparison" />

    <!-- Main Content Container -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
      <!-- Page Header & Action Bar -->
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 bg-white dark:bg-slate-900/80 p-6 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div class="space-y-1">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-violet-100 dark:bg-violet-950/80 text-violet-800 dark:text-violet-300 border border-violet-200 dark:border-violet-800/60">
            <ArrowLeftRight class="w-3.5 h-3.5" />
            <span>Komparasi Lintas Modul • Rekapitulasi Sepatu vs Database Guru &amp; Tendik</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
            Perbandingan Data Sepatu (Guru &amp; Tendik)
          </h1>
          <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-3xl">
            Membandingkan data ukuran sepatu khusus <span class="font-semibold text-slate-700 dark:text-slate-200">Guru &amp; Tenaga Kependidikan</span> pada menu Rekapitulasi Sepatu dengan daftar induk <span class="font-semibold text-slate-700 dark:text-slate-200">Database Guru &amp; Tendik</span> (<span class="font-mono text-violet-600 dark:text-violet-400">FORMAT REKAP TOTAL REVISI.xlsx</span>).
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
          <a
            :href="getUrl('/shoes')"
            @click.prevent="router.visit(getUrl('/shoes'))"
            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl font-medium text-xs sm:text-sm text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-xs transition-all cursor-pointer"
          >
            <Footprints class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
            <span>Rekap Sepatu</span>
          </a>

          <a
            :href="getUrl('/guru-tendik')"
            @click.prevent="router.visit(getUrl('/guru-tendik'))"
            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl font-medium text-xs sm:text-sm text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-xs transition-all cursor-pointer"
          >
            <Users class="w-4 h-4 text-cyan-600 dark:text-cyan-400" />
            <span>Database Guru &amp; Tendik</span>
          </a>

          <a
            :href="excelExportUrl"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-xs sm:text-sm text-white bg-gradient-to-r from-violet-600 via-indigo-600 to-emerald-600 hover:from-violet-500 hover:to-emerald-500 shadow-md shadow-violet-500/20 active:scale-95 transition-all cursor-pointer"
          >
            <FileSpreadsheet class="w-4 h-4" />
            <span>Export Laporan Komparasi (Excel)</span>
          </a>
        </div>
      </div>

      <!-- KPI Summary Cards (Clickable for Quick Filtering) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Card 1: Total Master Guru & Tendik -->
        <div
          @click="resetFilters()"
          class="p-4 rounded-2xl bg-gradient-to-br from-slate-800 via-slate-900 to-indigo-950 text-white shadow-lg relative overflow-hidden cursor-pointer hover:scale-[1.01] transition-all"
        >
          <div class="flex items-center justify-between text-slate-300 text-xs font-semibold mb-1.5">
            <span>Total Master Guru &amp; Tendik</span>
            <Users class="w-4 h-4 text-cyan-400" />
          </div>
          <div class="text-2xl sm:text-3xl font-extrabold tracking-tight">
            {{ summary.total_master.toLocaleString('id-ID') }}
          </div>
          <div class="mt-2 text-[11px] text-slate-300 flex items-center justify-between">
            <span>{{ summary.school_stats?.total_schools_master || 0 }} Sekolah Master</span>
            <span class="px-1.5 py-0.5 rounded bg-white/10 font-mono text-[10px]">
              Sepatu: {{ summary.total_shoe_recap.toLocaleString('id-ID') }}
            </span>
          </div>
        </div>

        <!-- Card 2: Sudah Ada Data Sepatu -->
        <div
          @click="filterByMatchStatus('sudah_ada')"
          class="p-4 rounded-2xl bg-white dark:bg-slate-900 border transition-all cursor-pointer shadow-sm"
          :class="matchStatus === 'sudah_ada' ? 'border-emerald-500 ring-2 ring-emerald-500/20 bg-emerald-50/20 dark:bg-emerald-950/20' : 'border-slate-200 dark:border-slate-800 hover:border-emerald-400'"
        >
          <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-xs font-semibold mb-1.5">
            <span>Sudah Ada Data Sepatu</span>
            <UserCheck class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-2xl sm:text-3xl font-extrabold text-emerald-600 dark:text-emerald-400">
              {{ summary.sudah_ada_data.toLocaleString('id-ID') }}
            </span>
            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
              {{ summary.persentase_lengkap }}%
            </span>
          </div>
          <div class="mt-2 text-[11px] text-slate-500 dark:text-slate-400 flex items-center gap-2">
            <span>{{ summary.exact_match_count }} cocok persis</span>
            <span v-if="summary.similar_match_count > 0">• {{ summary.similar_match_count }} mirip</span>
          </div>
        </div>

        <!-- Card 3: Belum Ada Data Sepatu -->
        <div
          @click="filterByMatchStatus('belum_ada')"
          class="p-4 rounded-2xl bg-white dark:bg-slate-900 border transition-all cursor-pointer shadow-sm"
          :class="matchStatus === 'belum_ada' ? 'border-rose-500 ring-2 ring-rose-500/20 bg-rose-50/20 dark:bg-rose-950/20' : 'border-slate-200 dark:border-slate-800 hover:border-rose-400'"
        >
          <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-xs font-semibold mb-1.5">
            <span>Belum Ada Data Sepatu</span>
            <UserX class="w-4 h-4 text-rose-600 dark:text-rose-400" />
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-2xl sm:text-3xl font-extrabold text-rose-600 dark:text-rose-400">
              {{ summary.belum_ada_data.toLocaleString('id-ID') }}
            </span>
            <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300">
              {{ summary.persentase_belum }}%
            </span>
          </div>
          <div class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">
            Terdaftar di Master, belum ada ukuran sepatu
          </div>
        </div>

        <!-- Card 4: Hanya di Rekap Sepatu (Tambahan) -->
        <div
          @click="filterByMatchStatus('hanya_di_sepatu')"
          class="p-4 rounded-2xl bg-white dark:bg-slate-900 border transition-all cursor-pointer shadow-sm"
          :class="matchStatus === 'hanya_di_sepatu' ? 'border-amber-500 ring-2 ring-amber-500/20 bg-amber-50/20 dark:bg-amber-950/20' : 'border-slate-200 dark:border-slate-800 hover:border-amber-400'"
        >
          <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-xs font-semibold mb-1.5">
            <span>Hanya di Rekap Sepatu</span>
            <UserPlus class="w-4 h-4 text-amber-600 dark:text-amber-400" />
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-2xl sm:text-3xl font-extrabold text-amber-600 dark:text-amber-400">
              {{ summary.hanya_di_sepatu.toLocaleString('id-ID') }}
            </span>
            <span class="text-xs font-medium text-slate-400">Orang</span>
          </div>
          <div class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">
            Ada ukuran sepatu, belum masuk Master Revisi
          </div>
        </div>

        <!-- Card 5: Progres Sekolah & Perbedaan Atribut -->
        <div
          @click="activeTab = 'schools'"
          class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:border-violet-400 transition-all cursor-pointer shadow-sm"
        >
          <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-xs font-semibold mb-1.5">
            <span>Sekolah Masuk Sepatu</span>
            <SchoolIcon class="w-4 h-4 text-violet-600 dark:text-violet-400" />
          </div>
          <div class="flex items-baseline gap-2">
            <span class="text-2xl sm:text-3xl font-extrabold text-violet-600 dark:text-violet-400">
              {{ summary.school_stats?.total_schools_shoe || 0 }}
            </span>
            <span class="text-xs text-slate-500 dark:text-slate-400">
              / {{ summary.school_stats?.total_schools_master || 0 }} Sekolah
            </span>
          </div>
          <div class="mt-2 text-[11px] text-slate-500 dark:text-slate-400 flex items-center justify-between">
            <span>{{ summary.school_stats?.schools_complete || 0 }} lengkap • {{ summary.school_stats?.schools_partial || 0 }} sebagian</span>
            <span
              v-if="summary.diff_attribute_count > 0"
              @click.stop="filterByMatchStatus('diff_attr')"
              class="text-indigo-600 dark:text-indigo-400 font-semibold hover:underline"
              title="Klik untuk melihat data yang berbeda atribut JK/Jabatan"
            >
              {{ summary.diff_attribute_count }} beda atribut
            </span>
          </div>
        </div>
      </div>

      <!-- Interactive Filter Bar -->
      <div class="bg-white dark:bg-slate-900/90 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
          <!-- Search Input -->
          <div class="relative lg:col-span-2">
            <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
            <input
              v-model="search"
              @input="onSearchInput"
              type="text"
              placeholder="Cari nama guru/tendik, sekolah, jabatan..."
              class="w-full pl-9 pr-8 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 focus:border-violet-500 focus:ring-2 focus:ring-violet-500/20 outline-none transition-all"
            />
            <button
              v-if="search"
              @click="search = ''; applyFilters()"
              class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>

          <!-- Filter Status Pencocokan -->
          <div>
            <select
              v-model="matchStatus"
              @change="applyFilters()"
              class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 focus:border-violet-500 outline-none cursor-pointer"
            >
              <option value="">Semua Status Pencocokan</option>
              <option value="sudah_ada">✅ Sudah Ada Data Sepatu ({{ summary.sudah_ada_data }})</option>
              <option value="belum_ada">❌ Belum Ada Data Sepatu ({{ summary.belum_ada_data }})</option>
              <option value="hanya_di_sepatu">➕ Hanya di Rekap Sepatu ({{ summary.hanya_di_sepatu }})</option>
              <option value="diff_attr">⚠️ Beda Atribut JK/Jabatan ({{ summary.diff_attribute_count }})</option>
            </select>
          </div>

          <!-- Filter Sekolah -->
          <div>
            <select
              v-model="selectedSekolah"
              @change="applyFilters()"
              class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 focus:border-violet-500 outline-none cursor-pointer"
            >
              <option value="">Semua Sekolah ({{ filterOptions.schools?.length || 0 }})</option>
              <option
                v-for="sc in filterOptions.schools"
                :key="sc.school_key"
                :value="sc.nama_sekolah"
              >
                {{ sc.nama_sekolah }} (Sepatu: {{ sc.sudah_ada }}/{{ sc.master_total }})
              </option>
            </select>
          </div>

          <!-- Filter Jenis Kelamin -->
          <div>
            <select
              v-model="selectedGender"
              @change="applyFilters()"
              class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 focus:border-violet-500 outline-none cursor-pointer"
            >
              <option value="">Semua Jenis Kelamin</option>
              <option value="L">Laki-laki (L)</option>
              <option value="P">Perempuan (P)</option>
            </select>
          </div>

          <!-- Filter Kategori & Status -->
          <div class="flex items-center gap-2">
            <select
              v-model="selectedKategori"
              @change="applyFilters()"
              class="w-full px-3 py-2 rounded-xl text-xs bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 focus:border-violet-500 outline-none cursor-pointer"
            >
              <option value="">Semua Kategori</option>
              <option value="Guru">Guru</option>
              <option value="Tendik">Tendik</option>
            </select>

            <button
              v-if="hasActiveFilters"
              type="button"
              @click="resetFilters"
              class="px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 hover:bg-rose-100 transition-colors shrink-0 cursor-pointer"
              title="Reset Semua Filter"
            >
              Reset
            </button>
          </div>
        </div>

        <!-- Secondary Filter Pills: Status Sekolah & Status Kepegawaian -->
        <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 dark:border-slate-800/70 text-xs">
          <div class="flex flex-wrap items-center gap-1.5">
            <span class="text-[11px] font-semibold text-slate-400 mr-1">Filter Status Sekolah:</span>
            <button
              type="button"
              @click="filterBySchoolStatus('')"
              class="px-2.5 py-1 rounded-lg text-[11px] font-medium transition-colors cursor-pointer"
              :class="statusSekolah === '' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 font-semibold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'"
            >
              Semua Sekolah
            </button>
            <button
              type="button"
              @click="filterBySchoolStatus('lengkap')"
              class="px-2.5 py-1 rounded-lg text-[11px] font-medium transition-colors cursor-pointer"
              :class="statusSekolah === 'lengkap' ? 'bg-emerald-600 text-white font-semibold' : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100'"
            >
              Lengkap 100% ({{ summary.school_stats?.schools_complete || 0 }})
            </button>
            <button
              type="button"
              @click="filterBySchoolStatus('sebagian')"
              class="px-2.5 py-1 rounded-lg text-[11px] font-medium transition-colors cursor-pointer"
              :class="statusSekolah === 'sebagian' ? 'bg-indigo-600 text-white font-semibold' : 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100'"
            >
              Sebagian Terdata ({{ summary.school_stats?.schools_partial || 0 }})
            </button>
            <button
              type="button"
              @click="filterBySchoolStatus('belum_ada')"
              class="px-2.5 py-1 rounded-lg text-[11px] font-medium transition-colors cursor-pointer"
              :class="statusSekolah === 'belum_ada' ? 'bg-rose-600 text-white font-semibold' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 hover:bg-rose-100'"
            >
              Sekolah Belum Kirim Sepatu ({{ summary.school_stats?.schools_empty || 0 }})
            </button>
          </div>

          <div class="flex items-center gap-2">
            <span class="text-[11px] text-slate-400">Status Kepegawaian:</span>
            <select
              v-model="selectedStatusPeg"
              @change="applyFilters()"
              class="px-2.5 py-1 rounded-lg text-[11px] bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 outline-none cursor-pointer"
            >
              <option value="">Semua Status</option>
              <option v-for="st in filterOptions.status_kepegawaian" :key="st" :value="st">
                {{ st }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 dark:border-slate-800 pb-3">
        <div class="flex flex-wrap items-center gap-2">
          <button
            type="button"
            @click="activeTab = 'records'"
            class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-2 transition-all cursor-pointer"
            :class="activeTab === 'records'
              ? 'bg-violet-600 text-white shadow-md shadow-violet-500/20'
              : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800 hover:bg-slate-100'"
          >
            <Table2 class="w-4 h-4" />
            <span>Daftar Perbandingan Individu</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono"
              :class="activeTab === 'records' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'">
              {{ (records?.total || 0).toLocaleString('id-ID') }}
            </span>
          </button>

          <button
            type="button"
            @click="activeTab = 'schools'"
            class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-2 transition-all cursor-pointer"
            :class="activeTab === 'schools'
              ? 'bg-violet-600 text-white shadow-md shadow-violet-500/20'
              : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800 hover:bg-slate-100'"
          >
            <SchoolIcon class="w-4 h-4" />
            <span>Perbandingan Per Sekolah</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-mono"
              :class="activeTab === 'schools' ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300'">
              {{ schoolComparisons.length }}
            </span>
          </button>

          <button
            type="button"
            @click="activeTab = 'analytics'"
            class="px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold flex items-center gap-2 transition-all cursor-pointer"
            :class="activeTab === 'analytics'
              ? 'bg-violet-600 text-white shadow-md shadow-violet-500/20'
              : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800 hover:bg-slate-100'"
          >
            <BarChart3 class="w-4 h-4" />
            <span>Perbandingan Gender, Jabatan &amp; Ukuran Sepatu</span>
          </button>
        </div>

        <!-- Quick Match Status Filter Pills when on Records Tab -->
        <div v-if="activeTab === 'records'" class="flex flex-wrap items-center gap-1.5">
          <button
            type="button"
            @click="filterByMatchStatus('')"
            class="px-3 py-1.5 rounded-xl text-xs font-medium transition-all cursor-pointer"
            :class="matchStatus === '' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900 font-semibold' : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400 border border-slate-200 dark:border-slate-800'"
          >
            Semua
          </button>
          <button
            type="button"
            @click="filterByMatchStatus('sudah_ada')"
            class="px-3 py-1.5 rounded-xl text-xs font-medium transition-all cursor-pointer flex items-center gap-1.5"
            :class="matchStatus === 'sudah_ada' ? 'bg-emerald-600 text-white font-semibold' : 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60'"
          >
            <CheckCircle2 class="w-3.5 h-3.5" />
            <span>Sudah Ada ({{ summary.sudah_ada_data }})</span>
          </button>
          <button
            type="button"
            @click="filterByMatchStatus('belum_ada')"
            class="px-3 py-1.5 rounded-xl text-xs font-medium transition-all cursor-pointer flex items-center gap-1.5"
            :class="matchStatus === 'belum_ada' ? 'bg-rose-600 text-white font-semibold' : 'bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60'"
          >
            <XCircle class="w-3.5 h-3.5" />
            <span>Belum Ada ({{ summary.belum_ada_data }})</span>
          </button>
          <button
            type="button"
            @click="filterByMatchStatus('hanya_di_sepatu')"
            class="px-3 py-1.5 rounded-xl text-xs font-medium transition-all cursor-pointer flex items-center gap-1.5"
            :class="matchStatus === 'hanya_di_sepatu' ? 'bg-amber-600 text-white font-semibold' : 'bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60'"
          >
            <AlertTriangle class="w-3.5 h-3.5" />
            <span>Hanya di Sepatu ({{ summary.hanya_di_sepatu }})</span>
          </button>
        </div>
      </div>

      <!-- =================================================================== -->
      <!-- TAB 1: DAFTAR PERBANDINGAN INDIVIDU (GURU & TENDIK)                 -->
      <!-- =================================================================== -->
      <div v-if="activeTab === 'records'" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
          <div>
            <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <ArrowLeftRight class="w-4 h-4 text-violet-600 dark:text-violet-400" />
              <span>Tabel Komparasi Guru &amp; Tendik (Master vs Rekap Sepatu)</span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              Menampilkan {{ records.from || 0 }}–{{ records.to || 0 }} dari total {{ (records.total || 0).toLocaleString('id-ID') }} data hasil pencocokan.
            </p>
          </div>
          <div v-if="selectedSekolah" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-violet-50 dark:bg-violet-950/70 border border-violet-200 dark:border-violet-800 text-xs font-semibold text-violet-700 dark:text-violet-300">
            <SchoolIcon class="w-3.5 h-3.5" />
            <span>Sekolah: {{ selectedSekolah }}</span>
            <button type="button" @click="selectedSekolah = ''; applyFilters()" class="hover:text-rose-600 cursor-pointer">
              <X class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-100/80 dark:bg-slate-950/80 text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider text-[10px] font-bold">
                <th class="py-3.5 px-4 w-12 text-center">No</th>
                <th class="py-3.5 px-4">Status Data Sepatu</th>
                <th class="py-3.5 px-4">Sekolah</th>
                <th class="py-3.5 px-4">Nama Lengkap (Master vs Sepatu)</th>
                <th class="py-3.5 px-4 text-center">Jenis Kelamin</th>
                <th class="py-3.5 px-4">Status &amp; Jabatan</th>
                <th class="py-3.5 px-4 text-center">Ukuran Sepatu</th>
                <th class="py-3.5 px-4">Keterangan Perbandingan</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800/70">
              <tr
                v-for="(item, idx) in records.data"
                :key="item.id"
                class="hover:bg-slate-50/90 dark:hover:bg-slate-800/40 transition-colors"
              >
                <td class="py-3 px-4 text-center font-mono text-slate-400">
                  {{ (records.from || 1) + idx }}
                </td>

                <!-- Status Data Sepatu -->
                <td class="py-3 px-4">
                  <div class="flex flex-col items-start gap-1">
                    <span
                      v-if="item.match_status === 'sudah_ada'"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 dark:bg-emerald-950/80 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800"
                    >
                      <CheckCircle2 class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
                      <span>Sudah Ada Data</span>
                    </span>

                    <span
                      v-else-if="item.match_status === 'belum_ada'"
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 dark:bg-rose-950/80 text-rose-800 dark:text-rose-300 border border-rose-200 dark:border-rose-800"
                    >
                      <XCircle class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400" />
                      <span>Belum Ada Data</span>
                    </span>

                    <span
                      v-else
                      class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800"
                    >
                      <AlertTriangle class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" />
                      <span>Hanya di Rekap Sepatu</span>
                    </span>

                    <span
                      v-if="item.match_type === 'similar'"
                      class="text-[10px] font-mono px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800"
                    >
                      Kemiripan Nama
                    </span>
                    <span
                      v-else-if="item.match_type === 'cross_school'"
                      class="text-[10px] font-mono px-2 py-0.5 rounded bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-800"
                    >
                      Lintas Sekolah
                    </span>
                  </div>
                </td>

                <!-- Sekolah -->
                <td class="py-3 px-4">
                  <button
                    type="button"
                    @click="inspectSchool(item.sekolah)"
                    class="font-semibold text-slate-800 dark:text-slate-200 hover:text-violet-600 dark:hover:text-violet-400 text-left cursor-pointer transition-colors"
                  >
                    {{ item.sekolah }}
                  </button>
                  <div v-if="item.sekolah_sepatu && item.sekolah_master && item.sekolah_sepatu !== item.sekolah_master" class="text-[10px] text-slate-400 mt-0.5">
                    Sepatu: {{ item.sekolah_sepatu }}
                  </div>
                  <div v-if="item.pic_nama" class="text-[10px] font-mono text-emerald-600 dark:text-emerald-400 mt-0.5">
                    PIC: {{ item.pic_nama }}
                  </div>
                </td>

                <!-- Nama Lengkap (Master vs Sepatu) -->
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-900 dark:text-white">
                    {{ item.nama_display }}
                  </div>
                  <div v-if="item.match_status === 'sudah_ada'" class="mt-1 space-y-0.5 text-[11px]">
                    <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                      <span class="px-1.5 py-0.2 rounded bg-cyan-50 dark:bg-cyan-950 text-cyan-700 dark:text-cyan-300 font-mono text-[10px]">Master</span>
                      <span>{{ item.nama_master }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                      <span class="px-1.5 py-0.2 rounded bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 font-mono text-[10px]">Sepatu</span>
                      <span>{{ item.nama_sepatu }}</span>
                    </div>
                  </div>
                  <div v-else-if="item.match_status === 'belum_ada'" class="mt-0.5 text-[10px] text-slate-400">
                    Terdaftar di Master • Belum ada di Rekap Sepatu
                  </div>
                  <div v-else class="mt-0.5 text-[10px] text-amber-600 dark:text-amber-400">
                    Ada di Rekap Sepatu • Tidak ditemukan di Master
                  </div>
                </td>

                <!-- Jenis Kelamin -->
                <td class="py-3 px-4 text-center">
                  <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold"
                    :class="item.jk_code === 'P'
                      ? 'bg-pink-100 dark:bg-pink-950/70 text-pink-700 dark:text-pink-300 border border-pink-200 dark:border-pink-800'
                      : 'bg-sky-100 dark:bg-sky-950/70 text-sky-700 dark:text-sky-300 border border-sky-200 dark:border-sky-800'"
                  >
                    {{ item.jk_code === 'P' ? 'Perempuan' : 'Laki-laki' }}
                  </span>
                  <div v-if="item.match_status === 'sudah_ada' && item.jk_master !== item.jk_sepatu" class="mt-1 text-[10px] font-semibold text-rose-600 dark:text-rose-400">
                    Master: {{ item.jk_master }} ≠ Sepatu: {{ item.jk_sepatu }}
                  </div>
                </td>

                <!-- Status & Jabatan -->
                <td class="py-3 px-4">
                  <div class="flex flex-wrap items-center gap-1.5 mb-1">
                    <span
                      v-if="item.status_kepegawaian && item.status_kepegawaian !== '-'"
                      class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 dark:bg-indigo-950/80 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800"
                    >
                      {{ item.status_kepegawaian }}
                    </span>
                    <span
                      class="px-2 py-0.5 rounded text-[10px] font-semibold"
                      :class="item.kategori === 'Guru'
                        ? 'bg-teal-50 dark:bg-teal-950/80 text-teal-700 dark:text-teal-300 border border-teal-200 dark:border-teal-800'
                        : 'bg-amber-50 dark:bg-amber-950/80 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800'"
                    >
                      {{ item.kategori }}
                    </span>
                  </div>
                  <div class="text-[11px] text-slate-600 dark:text-slate-300">
                    <span v-if="item.jabatan_master && item.jabatan_master !== '-'">Master: <strong class="font-semibold">{{ item.jabatan_master }}</strong></span>
                    <span v-if="item.jabatan_sepatu && item.jabatan_sepatu !== '-'" class="block text-slate-500">
                      Sepatu: {{ item.jabatan_sepatu }}
                    </span>
                  </div>
                </td>

                <!-- Ukuran Sepatu & Panjang Kaki -->
                <td class="py-3 px-4 text-center">
                  <div v-if="item.ukuran_sepatu" class="inline-flex flex-col items-center">
                    <span
                      class="px-3 py-1 rounded-xl font-mono text-sm font-extrabold border"
                      :class="item.is_anomaly
                        ? 'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 border-rose-300 dark:border-rose-700'
                        : 'bg-emerald-50 dark:bg-emerald-950/80 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800'"
                    >
                      Uk. {{ item.ukuran_sepatu }}
                    </span>
                    <span v-if="item.panjang_kaki" class="text-[10px] font-mono text-slate-500 mt-0.5">
                      {{ item.panjang_kaki }} cm
                    </span>
                  </div>
                  <span v-else class="inline-block px-2.5 py-1 rounded-lg text-[11px] font-mono bg-slate-100 dark:bg-slate-800 text-slate-400">
                    Belum Ada Ukuran
                  </span>
                </td>

                <!-- Keterangan Perbandingan -->
                <td class="py-3 px-4">
                  <div class="text-[11px] leading-relaxed"
                    :class="item.has_attribute_diff ? 'text-amber-700 dark:text-amber-300 font-medium' : 'text-slate-600 dark:text-slate-400'">
                    {{ item.match_note }}
                  </div>
                </td>
              </tr>

              <tr v-if="!records.data || records.data.length === 0">
                <td colspan="8" class="py-12 text-center text-slate-400">
                  <ArrowLeftRight class="w-8 h-8 mx-auto mb-2 opacity-40" />
                  <p class="text-sm font-semibold">Tidak ada data perbandingan yang sesuai dengan filter.</p>
                  <button
                    type="button"
                    @click="resetFilters"
                    class="mt-3 px-4 py-1.5 rounded-xl text-xs font-semibold bg-violet-600 text-white hover:bg-violet-500 cursor-pointer"
                  >
                    Reset Filter
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Bar -->
        <div
          v-if="records.last_page > 1"
          class="px-5 py-4 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-950/40"
        >
          <div class="text-xs text-slate-500 dark:text-slate-400">
            Halaman <span class="font-bold text-slate-800 dark:text-slate-200">{{ records.current_page }}</span> dari <span class="font-bold text-slate-800 dark:text-slate-200">{{ records.last_page }}</span>
            ({{ (records.total || 0).toLocaleString('id-ID') }} data)
          </div>

          <div class="flex flex-wrap items-center gap-1.5">
            <button
              type="button"
              :disabled="!records.prev_page_url"
              @click="goToPage(records.prev_page_url)"
              class="px-3 py-1.5 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 disabled:opacity-40 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer disabled:cursor-not-allowed"
            >
              ← Sebelumnya
            </button>
            <button
              type="button"
              :disabled="!records.next_page_url"
              @click="goToPage(records.next_page_url)"
              class="px-3 py-1.5 rounded-xl text-xs font-semibold border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 disabled:opacity-40 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer disabled:cursor-not-allowed"
            >
              Berikutnya →
            </button>
          </div>
        </div>
      </div>

      <!-- =================================================================== -->
      <!-- TAB 2: PERBANDINGAN PER SEKOLAH                                     -->
      <!-- =================================================================== -->
      <div v-if="activeTab === 'schools'" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
          <div>
            <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <SchoolIcon class="w-4 h-4 text-violet-600 dark:text-violet-400" />
              <span>Rekapitulasi Perbandingan Guru &amp; Tendik Per Sekolah ({{ schoolComparisons.length }} Sekolah)</span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              Klik pada baris sekolah untuk melihat daftar Guru &amp; Tendik yang sudah maupun belum memiliki data sepatu di sekolah tersebut.
            </p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-100/80 dark:bg-slate-950/80 text-slate-600 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800 uppercase tracking-wider text-[10px] font-bold">
                <th class="py-3.5 px-4 w-12 text-center">No</th>
                <th class="py-3.5 px-4">Nama Sekolah</th>
                <th class="py-3.5 px-4 text-center">Total Master (L/P)</th>
                <th class="py-3.5 px-4 text-center">Master (Guru / Tendik)</th>
                <th class="py-3.5 px-4 text-center">Data Sepatu Masuk</th>
                <th class="py-3.5 px-4 text-center">Sudah Ada Data</th>
                <th class="py-3.5 px-4 text-center">Belum Ada Data</th>
                <th class="py-3.5 px-4 text-center">Hanya di Sepatu</th>
                <th class="py-3.5 px-4 w-48">Progres Kelengkapan</th>
                <th class="py-3.5 px-4 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-200/70 dark:divide-slate-800/70">
              <tr
                v-for="(sc, idx) in schoolComparisons"
                :key="sc.school_key"
                class="hover:bg-slate-50/90 dark:hover:bg-slate-800/40 transition-colors"
              >
                <td class="py-3 px-4 text-center font-mono text-slate-400">{{ idx + 1 }}</td>
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-900 dark:text-white">{{ sc.nama_sekolah }}</div>
                  <div v-if="sc.nama_sekolah_sepatu && sc.nama_sekolah_sepatu !== sc.nama_sekolah" class="text-[10px] text-emerald-600 dark:text-emerald-400">
                    Rekap Sepatu: {{ sc.nama_sekolah_sepatu }}
                  </div>
                  <div v-if="sc.pic_nama" class="text-[10px] font-mono text-slate-400">
                    PIC: {{ sc.pic_nama }}
                  </div>
                </td>
                <td class="py-3 px-4 text-center">
                  <span class="font-bold text-slate-900 dark:text-white">{{ sc.master_total }}</span>
                  <span class="block text-[10px] font-mono text-slate-400">{{ sc.master_l }}L • {{ sc.master_p }}P</span>
                </td>
                <td class="py-3 px-4 text-center font-mono text-[11px]">
                  <span class="text-teal-600 dark:text-teal-400 font-semibold">{{ sc.master_guru }} Guru</span>
                  <span class="mx-1 text-slate-300">•</span>
                  <span class="text-amber-600 dark:text-amber-400 font-semibold">{{ sc.master_tendik }} Tendik</span>
                </td>
                <td class="py-3 px-4 text-center">
                  <span class="font-bold" :class="sc.shoe_total > 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400'">
                    {{ sc.shoe_total }}
                  </span>
                  <span v-if="sc.shoe_total > 0" class="block text-[10px] font-mono text-slate-400">
                    {{ sc.shoe_l }}L • {{ sc.shoe_p }}P
                  </span>
                </td>
                <td class="py-3 px-4 text-center">
                  <button
                    type="button"
                    @click="inspectSchool(sc.nama_sekolah, 'sudah_ada')"
                    class="px-2.5 py-1 rounded-lg font-bold text-xs cursor-pointer transition-colors"
                    :class="sc.sudah_ada > 0 ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-200' : 'text-slate-400'"
                  >
                    {{ sc.sudah_ada }}
                  </button>
                </td>
                <td class="py-3 px-4 text-center">
                  <button
                    type="button"
                    @click="inspectSchool(sc.nama_sekolah, 'belum_ada')"
                    class="px-2.5 py-1 rounded-lg font-bold text-xs cursor-pointer transition-colors"
                    :class="sc.belum_ada > 0 ? 'bg-rose-100 dark:bg-rose-950 text-rose-700 dark:text-rose-300 hover:bg-rose-200' : 'text-slate-400'"
                  >
                    {{ sc.belum_ada }}
                  </button>
                </td>
                <td class="py-3 px-4 text-center">
                  <button
                    type="button"
                    @click="inspectSchool(sc.nama_sekolah, 'hanya_di_sepatu')"
                    class="px-2.5 py-1 rounded-lg font-bold text-xs cursor-pointer transition-colors"
                    :class="sc.hanya_di_sepatu > 0 ? 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300 hover:bg-amber-200' : 'text-slate-400'"
                  >
                    {{ sc.hanya_di_sepatu }}
                  </button>
                </td>
                <td class="py-3 px-4">
                  <div class="flex items-center justify-between text-[11px] font-semibold mb-1">
                    <span
                      :class="sc.status_sekolah === 'lengkap'
                        ? 'text-emerald-600 dark:text-emerald-400'
                        : (sc.status_sekolah === 'sebagian' ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400')"
                    >
                      {{ sc.status_label }}
                    </span>
                    <span class="font-mono">{{ sc.persentase }}%</span>
                  </div>
                  <div class="w-full h-2 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                    <div
                      class="h-full rounded-full transition-all duration-500"
                      :class="sc.persentase >= 100 ? 'bg-emerald-500' : (sc.persentase > 0 ? 'bg-indigo-500' : 'bg-slate-300 dark:bg-slate-700')"
                      :style="{ width: `${Math.min(100, sc.persentase)}%` }"
                    ></div>
                  </div>
                </td>
                <td class="py-3 px-4 text-center">
                  <button
                    type="button"
                    @click="inspectSchool(sc.nama_sekolah)"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-[11px] font-semibold bg-violet-50 dark:bg-violet-950/70 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-800 hover:bg-violet-100 cursor-pointer"
                  >
                    <span>Detail</span>
                    <ChevronRight class="w-3.5 h-3.5" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- =================================================================== -->
      <!-- TAB 3: ANALISIS & PERBANDINGAN LAINNYA (GENDER, JABATAN, UKURAN)    -->
      <!-- =================================================================== -->
      <div v-if="activeTab === 'analytics'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- 1. Perbandingan Berdasarkan Jenis Kelamin -->
          <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <Users class="w-4 h-4 text-cyan-600 dark:text-cyan-400" />
              <span>Perbandingan Berdasarkan Jenis Kelamin</span>
            </h3>
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs border-collapse">
                <thead>
                  <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] uppercase text-slate-400 font-bold">
                    <th class="py-2">Jenis Kelamin</th>
                    <th class="py-2 text-center">Master</th>
                    <th class="py-2 text-center text-emerald-600">Sudah Ada</th>
                    <th class="py-2 text-center text-rose-600">Belum Ada</th>
                    <th class="py-2 text-center text-amber-600">Hanya di Sepatu</th>
                    <th class="py-2 text-right">Kelengkapan</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                  <tr v-for="g in summary.breakdowns?.gender || []" :key="g.label">
                    <td class="py-3 font-semibold text-slate-800 dark:text-slate-200">{{ g.label }}</td>
                    <td class="py-3 text-center font-mono font-bold">{{ g.master.toLocaleString('id-ID') }}</td>
                    <td class="py-3 text-center font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ g.sudah_ada.toLocaleString('id-ID') }}</td>
                    <td class="py-3 text-center font-mono font-bold text-rose-600 dark:text-rose-400">{{ g.belum_ada.toLocaleString('id-ID') }}</td>
                    <td class="py-3 text-center font-mono font-bold text-amber-600 dark:text-amber-400">{{ g.hanya_di_sepatu.toLocaleString('id-ID') }}</td>
                    <td class="py-3 text-right font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ g.persentase }}%</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- 2. Perbandingan Berdasarkan Kategori (Guru vs Tendik) -->
          <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <Layers class="w-4 h-4 text-violet-600 dark:text-violet-400" />
              <span>Perbandingan Berdasarkan Kategori (Guru vs Tendik)</span>
            </h3>
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs border-collapse">
                <thead>
                  <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] uppercase text-slate-400 font-bold">
                    <th class="py-2">Kategori</th>
                    <th class="py-2 text-center">Master</th>
                    <th class="py-2 text-center text-emerald-600">Sudah Ada</th>
                    <th class="py-2 text-center text-rose-600">Belum Ada</th>
                    <th class="py-2 text-center text-amber-600">Hanya di Sepatu</th>
                    <th class="py-2 text-right">Kelengkapan</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                  <tr v-for="kt in summary.breakdowns?.kategori || []" :key="kt.label">
                    <td class="py-3 font-semibold text-slate-800 dark:text-slate-200">{{ kt.label }}</td>
                    <td class="py-3 text-center font-mono font-bold">{{ kt.master.toLocaleString('id-ID') }}</td>
                    <td class="py-3 text-center font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ kt.sudah_ada.toLocaleString('id-ID') }}</td>
                    <td class="py-3 text-center font-mono font-bold text-rose-600 dark:text-rose-400">{{ kt.belum_ada.toLocaleString('id-ID') }}</td>
                    <td class="py-3 text-center font-mono font-bold text-amber-600 dark:text-amber-400">{{ kt.hanya_di_sepatu.toLocaleString('id-ID') }}</td>
                    <td class="py-3 text-right font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ kt.persentase }}%</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- 3. Perbandingan Berdasarkan Status Kepegawaian & Distribusi Ukuran Sepatu -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- Status Kepegawaian -->
          <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <Sparkles class="w-4 h-4 text-amber-500" />
              <span>Perbandingan Berdasarkan Status Kepegawaian (Master)</span>
            </h3>
            <div class="overflow-x-auto max-h-80">
              <table class="w-full text-left text-xs border-collapse">
                <thead>
                  <tr class="border-b border-slate-200 dark:border-slate-800 text-[10px] uppercase text-slate-400 font-bold">
                    <th class="py-2">Status Kepegawaian</th>
                    <th class="py-2 text-center">Total Master</th>
                    <th class="py-2 text-center text-emerald-600">Sudah Ada Sepatu</th>
                    <th class="py-2 text-center text-rose-600">Belum Ada Sepatu</th>
                    <th class="py-2 text-right">Persentase</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                  <tr v-for="st in summary.breakdowns?.status_kepegawaian || []" :key="st.status">
                    <td class="py-2.5 font-semibold text-slate-800 dark:text-slate-200">{{ st.status }}</td>
                    <td class="py-2.5 text-center font-mono font-bold">{{ st.master.toLocaleString('id-ID') }}</td>
                    <td class="py-2.5 text-center font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ st.sudah_ada.toLocaleString('id-ID') }}</td>
                    <td class="py-2.5 text-center font-mono font-bold text-rose-600 dark:text-rose-400">{{ st.belum_ada.toLocaleString('id-ID') }}</td>
                    <td class="py-2.5 text-right font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ st.persentase }}%</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Distribusi Ukuran Sepatu Guru & Tendik -->
          <div class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
            <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <Footprints class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
              <span>Distribusi Ukuran Sepatu Guru &amp; Tendik yang Sudah Masuk</span>
            </h3>

            <div v-if="(summary.breakdowns?.shoe_sizes || []).length > 0" class="space-y-2.5 max-h-80 overflow-y-auto pr-1">
              <div
                v-for="sz in summary.breakdowns.shoe_sizes"
                :key="sz.ukuran"
                class="space-y-1"
              >
                <div class="flex items-center justify-between text-xs">
                  <span class="font-mono font-bold text-slate-800 dark:text-slate-200">Ukuran {{ sz.ukuran }}</span>
                  <span class="text-[11px] font-mono text-slate-500">
                    <strong class="text-slate-900 dark:text-white">{{ sz.total }} pasang</strong>
                    ({{ sz.laki_laki }} L • {{ sz.perempuan }} P)
                  </span>
                </div>
                <div class="w-full h-2.5 rounded-full bg-slate-100 dark:bg-slate-800 overflow-hidden">
                  <div
                    class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-600"
                    :style="{ width: `${Math.max(4, Math.round((sz.total / maxShoeSizeCount) * 100))}%` }"
                  ></div>
                </div>
              </div>
            </div>
            <div v-else class="py-10 text-center text-xs text-slate-400">
              Belum ada data ukuran sepatu Guru &amp; Tendik yang diunggah pada menu Rekapitulasi Sepatu.
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

