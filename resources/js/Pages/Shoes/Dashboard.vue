<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import HybridNavbar from '../../Components/HybridNavbar.vue';
import BaseChart from '../../Components/BaseChart.vue';
import {
  LayoutDashboard,
  BarChart3,
  PieChart,
  School as SchoolIcon,
  Users,
  UserCheck,
  TrendingUp,
  Sparkles,
  ArrowRightLeft,
  FileSpreadsheet,
  Download,
  Filter,
  RefreshCw,
  SlidersHorizontal,
  CheckCircle2,
  Calendar,
  Layers,
  Info,
  ChevronRight,
  User,
  Activity
} from 'lucide-vue-next';

const props = defineProps({
  summary: {
    type: Object,
    required: true,
  },
  size_distribution: {
    type: Array,
    default: () => [],
  },
  size_groups: {
    type: Object,
    default: () => ({}),
  },
  category_distribution: {
    type: Array,
    default: () => [],
  },
  gender_distribution: {
    type: Array,
    default: () => [],
  },
  schools: {
    type: Array,
    default: () => [],
  },
  comparator: {
    type: Object,
    default: () => ({ school_a: null, school_b: null }),
  },
  pic_list: {
    type: Array,
    default: () => [
      'PIC AZRIEL',
      'PIC WENDY',
      'PIC ADIL',
      'PIC AYU',
      'PIC DWI',
      'PIC IKHSAN dan TRI',
      'PIC INDRA',
      'PIC RAHAYU',
      'PIC MIFTAH',
      'PIC RIVALDI',
      'PIC YUPITA',
    ],
  },
  filters: {
    type: Object,
    default: () => ({
      school_id: null,
      compare_school_id: null,
      kategori: '',
      gender: '',
      pic: '',
    }),
  },
});

const getUrl = (path) => {
  if (typeof window !== 'undefined') {
    const match = window.location.pathname.match(/^(\/[^\/]+\/public)/);
    if (match) return `${match[1]}${path}`;
    if (window.location.pathname.includes('/hybridlaravel/public')) return `/hybridlaravel/public${path}`;
  }
  return path;
};

// Filter State
const selectedSchool = ref(props.filters.school_id ? String(props.filters.school_id) : '');
const selectedKategori = ref(props.filters.kategori || '');
const selectedGender = ref(props.filters.gender || '');
const selectedPic = ref(props.filters.pic || '');

// Side-by-Side Comparator State
const schoolAId = ref(props.comparator.school_a ? String(props.comparator.school_a.id) : '');
const schoolBId = ref(props.comparator.school_b ? String(props.comparator.school_b.id) : '');
const isComparatorOpen = ref(true);

// Dark mode state reactive to theme switcher
const isDark = ref(false);
const checkDarkMode = () => {
  if (typeof document !== 'undefined') {
    isDark.value = document.documentElement.classList.contains('dark');
  }
};

onMounted(() => {
  checkDarkMode();
  if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
    const observer = new MutationObserver(() => {
      checkDarkMode();
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
  }
});

// Apply global filters via Inertia visit
const applyFilters = () => {
  router.get(getUrl('/dashboard'), {
    school_id: selectedSchool.value || undefined,
    compare_school_id: schoolBId.value || undefined,
    kategori: selectedKategori.value || undefined,
    gender: selectedGender.value || undefined,
    pic: selectedPic.value || undefined,
  }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const resetFilters = () => {
  selectedSchool.value = '';
  selectedKategori.value = '';
  selectedGender.value = '';
  selectedPic.value = '';
  router.get(getUrl('/dashboard'), {}, { preserveScroll: true });
};

// Quick Comparison Trigger
const triggerCompare = (schoolId) => {
  schoolBId.value = String(schoolId);
  isComparatorOpen.value = true;
};

// Resolved objects for side-by-side comparison
const schoolA = computed(() => {
  if (!schoolAId.value) return props.schools[0] || null;
  return props.schools.find(s => String(s.id) === schoolAId.value) || props.schools[0] || null;
});

const schoolB = computed(() => {
  if (!schoolBId.value) return props.schools[1] || null;
  return props.schools.find(s => String(s.id) === schoolBId.value) || props.schools[1] || null;
});

// -------------------------------------------------------------
// ECHARTS OPTIONS
// -------------------------------------------------------------

// 1. Size Distribution by Category (Grouped & Stacked Bar Chart)
const sizeDistributionChartOption = computed(() => {
  const textColor = isDark.value ? '#94a3b8' : '#475569';
  const gridColor = isDark.value ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';

  const categories = props.size_distribution.map(d => `Ukuran ${d.size}`);
  const siswaL = props.size_distribution.map(d => d.siswa_l);
  const siswaP = props.size_distribution.map(d => d.siswa_p);
  const staffL = props.size_distribution.map(d => d.staff_l);
  const staffP = props.size_distribution.map(d => d.staff_p);

  return {
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'shadow' },
      backgroundColor: isDark.value ? '#0f172a' : '#ffffff',
      borderColor: isDark.value ? '#334155' : '#e2e8f0',
      textStyle: { color: isDark.value ? '#f8fafc' : '#0f172a', fontSize: 12 },
    },
    legend: {
      data: ['Siswa (L)', 'Siswa (P)', 'Guru/Tendik (L)', 'Guru/Tendik (P)'],
      textStyle: { color: textColor, fontSize: 11 },
      top: 0,
      icon: 'roundRect',
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '8%',
      top: '14%',
      containLabel: true,
    },
    xAxis: {
      type: 'category',
      data: categories,
      axisLine: { lineStyle: { color: gridColor } },
      axisLabel: { color: textColor, fontSize: 11, interval: 0, rotate: categories.length > 8 ? 35 : 0 },
    },
    yAxis: {
      type: 'value',
      name: 'Jumlah Pasang',
      nameTextStyle: { color: textColor, fontSize: 11 },
      splitLine: { lineStyle: { color: gridColor } },
      axisLabel: { color: textColor, fontSize: 11 },
    },
    series: [
      {
        name: 'Siswa (L)',
        type: 'bar',
        stack: 'total',
        emphasis: { focus: 'series' },
        itemStyle: { color: '#3b82f6', borderRadius: [0, 0, 0, 0] },
        data: siswaL,
      },
      {
        name: 'Siswa (P)',
        type: 'bar',
        stack: 'total',
        emphasis: { focus: 'series' },
        itemStyle: { color: '#ec4899', borderRadius: [0, 0, 0, 0] },
        data: siswaP,
      },
      {
        name: 'Guru/Tendik (L)',
        type: 'bar',
        stack: 'total',
        emphasis: { focus: 'series' },
        itemStyle: { color: '#06b6d4', borderRadius: [0, 0, 0, 0] },
        data: staffL,
      },
      {
        name: 'Guru/Tendik (P)',
        type: 'bar',
        stack: 'total',
        emphasis: { focus: 'series' },
        itemStyle: { color: '#a855f7', borderRadius: [4, 4, 0, 0] },
        data: staffP,
      },
    ],
  };
});

// 2. Category Composition (Donut Chart)
const categoryChartOption = computed(() => {
  const textColor = isDark.value ? '#f8fafc' : '#0f172a';
  return {
    tooltip: {
      trigger: 'item',
      formatter: '{b}: {c} pasang ({d}%)',
      backgroundColor: isDark.value ? '#0f172a' : '#ffffff',
      borderColor: isDark.value ? '#334155' : '#e2e8f0',
      textStyle: { color: textColor, fontSize: 12 },
    },
    legend: {
      bottom: '0%',
      left: 'center',
      textStyle: { color: isDark.value ? '#94a3b8' : '#475569', fontSize: 11 },
      icon: 'circle',
    },
    series: [
      {
        name: 'Kategori',
        type: 'pie',
        radius: ['52%', '78%'],
        center: ['50%', '45%'],
        avoidLabelOverlap: false,
        itemStyle: {
          borderRadius: 8,
          borderColor: isDark.value ? '#090d16' : '#ffffff',
          borderWidth: 2,
        },
        label: {
          show: false,
          position: 'center',
        },
        emphasis: {
          label: {
            show: true,
            fontSize: 14,
            fontWeight: 'bold',
            color: textColor,
          },
        },
        data: [
          { value: props.summary.total_siswa, name: 'Siswa', itemStyle: { color: '#3b82f6' } },
          { value: props.summary.total_guru, name: 'Guru', itemStyle: { color: '#10b981' } },
          { value: props.summary.total_tendik, name: 'Tenaga Kependidikan', itemStyle: { color: '#f59e0b' } },
        ],
      },
    ],
  };
});

// 3. Gender Ratio (Donut Chart)
const genderChartOption = computed(() => {
  const textColor = isDark.value ? '#f8fafc' : '#0f172a';
  return {
    tooltip: {
      trigger: 'item',
      formatter: '{b}: {c} pasang ({d}%)',
      backgroundColor: isDark.value ? '#0f172a' : '#ffffff',
      borderColor: isDark.value ? '#334155' : '#e2e8f0',
      textStyle: { color: textColor, fontSize: 12 },
    },
    legend: {
      bottom: '0%',
      left: 'center',
      textStyle: { color: isDark.value ? '#94a3b8' : '#475569', fontSize: 11 },
      icon: 'circle',
    },
    series: [
      {
        name: 'Jenis Kelamin',
        type: 'pie',
        radius: ['52%', '78%'],
        center: ['50%', '45%'],
        avoidLabelOverlap: false,
        itemStyle: {
          borderRadius: 8,
          borderColor: isDark.value ? '#090d16' : '#ffffff',
          borderWidth: 2,
        },
        label: { show: false },
        data: [
          { value: props.summary.total_pria, name: 'Laki-Laki', itemStyle: { color: '#0284c7' } },
          { value: props.summary.total_wanita, name: 'Perempuan', itemStyle: { color: '#f43f5e' } },
        ],
      },
    ],
  };
});

// 4. Side-by-Side School Comparison Chart
const schoolComparisonChartOption = computed(() => {
  const textColor = isDark.value ? '#94a3b8' : '#475569';
  const gridColor = isDark.value ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.06)';

  if (!schoolA.value) return {};

  const sizes = [36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46];
  const dataA = sizes.map(s => schoolA.value?.sizes?.[s] || 0);
  const dataB = schoolB.value ? sizes.map(s => schoolB.value?.sizes?.[s] || 0) : [];

  const series = [
    {
      name: schoolA.value.nama_sekolah,
      type: 'bar',
      itemStyle: { color: '#6366f1', borderRadius: [4, 4, 0, 0] },
      data: dataA,
    },
  ];

  if (schoolB.value) {
    series.push({
      name: schoolB.value.nama_sekolah,
      type: 'bar',
      itemStyle: { color: '#10b981', borderRadius: [4, 4, 0, 0] },
      data: dataB,
    });
  }

  return {
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'shadow' },
      backgroundColor: isDark.value ? '#0f172a' : '#ffffff',
      borderColor: isDark.value ? '#334155' : '#e2e8f0',
      textStyle: { color: isDark.value ? '#f8fafc' : '#0f172a', fontSize: 12 },
    },
    legend: {
      textStyle: { color: textColor, fontSize: 11 },
      top: 0,
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '8%',
      top: '15%',
      containLabel: true,
    },
    xAxis: {
      type: 'category',
      data: sizes.map(s => `Sz ${s}`),
      axisLine: { lineStyle: { color: gridColor } },
      axisLabel: { color: textColor, fontSize: 11 },
    },
    yAxis: {
      type: 'value',
      splitLine: { lineStyle: { color: gridColor } },
      axisLabel: { color: textColor, fontSize: 11 },
    },
    series: series,
  };
});
</script>

<template>
  <Head title="Dashboard Analitik & Perbandingan Sepatu SR" />

  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 selection:bg-indigo-500 selection:text-white transition-colors duration-200">
    <HybridNavbar mode="inertia" currentRoute="dashboard" />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 space-y-8">
      
      <!-- Hero & Quick Action Header -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-4 border-b border-slate-200/80 dark:border-slate-800/80">
        <div>
          <div class="flex items-center gap-2 mb-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/50">
              <Sparkles class="w-3.5 h-3.5 text-indigo-500 animate-pulse" />
              Executive Analytics Center
            </span>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">Kemensos / Sekolah Rakyat</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
            Dashboard Analitik &amp; Komparasi Ukuran Sepatu
          </h1>
          <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 max-w-2xl">
            Visualisasi distribusi ukuran sepatu, perbandingan antar sekolah, dan estimasi kelompok produksi pengadaan vendor secara real-time.
          </p>
        </div>

        <!-- Quick Action Navigations -->
        <div class="flex flex-wrap items-center gap-3">
          <Link
            :href="getUrl('/shoes')"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm text-white bg-emerald-600 hover:bg-emerald-500 shadow-md shadow-emerald-600/20 active:scale-95 transition-all cursor-pointer"
          >
            <FileSpreadsheet class="w-4 h-4" />
            <span>Kelola &amp; Import Data</span>
          </Link>

          <a
            :href="getUrl('/shoes/export/excel')"
            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl font-medium text-sm text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/80 shadow-xs active:scale-95 transition-all cursor-pointer"
          >
            <Download class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
            <span>Ekspor Excel</span>
          </a>

          <a
            :href="getUrl('/shoes/export/pdf')"
            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl font-medium text-sm text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800/80 shadow-xs active:scale-95 transition-all cursor-pointer"
          >
            <Download class="w-4 h-4 text-rose-600 dark:text-rose-400" />
            <span>Cetak PDF</span>
          </a>
        </div>
      </div>

      <!-- Interactive Filter Toolbar -->
      <div class="p-4 rounded-2xl bg-white/80 dark:bg-slate-900/80 border border-slate-200/80 dark:border-slate-800/80 backdrop-blur-xl shadow-xs">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
            <Filter class="w-4 h-4 text-indigo-500" />
            <span>Filter Analitik:</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 flex-1 max-w-4xl">
            <!-- School Filter -->
            <div>
              <select
                v-model="selectedSchool"
                @change="applyFilters"
                class="w-full text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 px-3 py-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all cursor-pointer"
              >
                <option value="">Semua Sekolah (Agregat)</option>
                <option v-for="sch in schools" :key="sch.id" :value="String(sch.id)">
                  {{ sch.nama_sekolah }} ({{ sch.total_recipients }} psg)
                </option>
              </select>
            </div>

            <!-- PIC Filter -->
            <div>
              <select
                v-model="selectedPic"
                @change="applyFilters"
                class="w-full text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 px-3 py-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all cursor-pointer"
              >
                <option value="">Semua PIC</option>
                <option v-for="p in pic_list" :key="p" :value="p">
                  {{ p }}
                </option>
              </select>
            </div>

            <!-- Category Filter -->
            <div>
              <select
                v-model="selectedKategori"
                @change="applyFilters"
                class="w-full text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 px-3 py-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all cursor-pointer"
              >
                <option value="">Semua Kategori (Siswa &amp; Staff)</option>
                <option value="siswa">Hanya Siswa</option>
                <option value="guru">Hanya Guru</option>
                <option value="tendik">Hanya Tenaga Kependidikan</option>
              </select>
            </div>

            <!-- Gender Filter -->
            <div>
              <select
                v-model="selectedGender"
                @change="applyFilters"
                class="w-full text-xs rounded-xl bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 px-3 py-2 focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all cursor-pointer"
              >
                <option value="">Semua Gender (L &amp; P)</option>
                <option value="L">Laki-Laki</option>
                <option value="P">Perempuan</option>
              </select>
            </div>
          </div>

          <!-- Reset Filter Button -->
          <button
            v-if="selectedSchool || selectedPic || selectedKategori || selectedGender"
            @click="resetFilters"
            class="text-xs px-3 py-2 rounded-xl text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/40 border border-rose-200 dark:border-rose-800/50 transition-all cursor-pointer flex items-center justify-center gap-1.5 shrink-0"
          >
            <RefreshCw class="w-3.5 h-3.5" />
            <span>Reset Filter</span>
          </button>
        </div>
      </div>

      <!-- 6 Key Executive Metric Cards -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <!-- KPI 1: Total Sepatu -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden group hover:border-indigo-500/40 transition-all">
          <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
            <span>Total Kebutuhan</span>
            <div class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
              <Sparkles class="w-3.5 h-3.5" />
            </div>
          </div>
          <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white font-mono">
            {{ summary.total_sepatu }} <span class="text-xs font-normal text-slate-500">pasang</span>
          </div>
          <p class="text-[11px] text-indigo-600 dark:text-indigo-400 mt-1 font-medium">
            Dari {{ summary.total_sekolah }} Sekolah
          </p>
        </div>

        <!-- KPI 2: Siswa -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden group hover:border-blue-500/40 transition-all">
          <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
            <span>Total Siswa</span>
            <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center">
              <Users class="w-3.5 h-3.5" />
            </div>
          </div>
          <div class="mt-2 text-2xl font-black text-blue-600 dark:text-blue-400 font-mono">
            {{ summary.total_siswa }}
          </div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
            {{ summary.total_sepatu > 0 ? Math.round((summary.total_siswa / summary.total_sepatu) * 100) : 0 }}% dari keseluruhan
          </p>
        </div>

        <!-- KPI 3: Guru & Tendik -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden group hover:border-emerald-500/40 transition-all">
          <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
            <span>Guru &amp; Tendik</span>
            <div class="w-7 h-7 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
              <UserCheck class="w-3.5 h-3.5" />
            </div>
          </div>
          <div class="mt-2 text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">
            {{ summary.total_staff }}
          </div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
            Guru: {{ summary.total_guru }} | Tendik: {{ summary.total_tendik }}
          </p>
        </div>

        <!-- KPI 4: Top Size -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden group hover:border-amber-500/40 transition-all">
          <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
            <span>Ukuran Terbanyak</span>
            <div class="w-7 h-7 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center">
              <TrendingUp class="w-3.5 h-3.5" />
            </div>
          </div>
          <div class="mt-2 text-2xl font-black text-amber-600 dark:text-amber-400 font-mono">
            {{ summary.top_size ? `Size ${summary.top_size}` : '-' }}
          </div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
            {{ summary.top_size_count }} pasang ({{ summary.top_size_pct }}%)
          </p>
        </div>

        <!-- KPI 5: Rentang Ukuran -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden group hover:border-purple-500/40 transition-all">
          <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
            <span>Rentang Ukuran</span>
            <div class="w-7 h-7 rounded-lg bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center">
              <Layers class="w-3.5 h-3.5" />
            </div>
          </div>
          <div class="mt-2 text-2xl font-black text-purple-600 dark:text-purple-400 font-mono">
            {{ summary.min_size }} – {{ summary.max_size }}
          </div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
            Min Size s/d Max Size
          </p>
        </div>

        <!-- KPI 6: Rata-Rata Panjang Kaki -->
        <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden group hover:border-teal-500/40 transition-all">
          <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400">
            <span>Rerata Kaki</span>
            <div class="w-7 h-7 rounded-lg bg-teal-50 dark:bg-teal-950/60 text-teal-600 dark:text-teal-400 flex items-center justify-center">
              <Activity class="w-3.5 h-3.5" />
            </div>
          </div>
          <div class="mt-2 text-2xl font-black text-teal-600 dark:text-teal-400 font-mono">
            {{ summary.avg_foot_length || '-' }} <span class="text-xs font-normal text-slate-500">cm</span>
          </div>
          <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
            Rata-rata telapak kaki
          </p>
        </div>
      </div>

      <!-- Main Visualizations: Size Distribution & Donut Compositions -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left 2 Cols: Detailed Size Distribution Matrix Chart -->
        <div class="lg:col-span-2 p-5 sm:p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <BarChart3 class="w-4 h-4 text-indigo-500" />
                <span>Distribusi Kebutuhan Ukuran Sepatu (Size 36–47)</span>
              </h2>
              <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Rincian pasang sepatu berdasarkan kombinasi Kategori &amp; Gender per nomor ukuran.
              </p>
            </div>
            <span class="text-xs font-mono px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
              Bar Stacked
            </span>
          </div>

          <BaseChart :option="sizeDistributionChartOption" height="340px" />
        </div>

        <!-- Right 1 Col: Donut Composition Charts -->
        <div class="space-y-6">
          <!-- Donut 1: Category Breakdown -->
          <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5 mb-1">
              <PieChart class="w-3.5 h-3.5 text-blue-500" />
              <span>Komposisi Kategori Penerima</span>
            </h3>
            <BaseChart :option="categoryChartOption" height="175px" />
          </div>

          <!-- Donut 2: Gender Breakdown -->
          <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5 mb-1">
              <Users class="w-3.5 h-3.5 text-rose-500" />
              <span>Rasio Kebutuhan Gender</span>
            </h3>
            <BaseChart :option="genderChartOption" height="175px" />
          </div>
        </div>
      </div>

      <!-- Vendor Production Batching Modules (4 Size Groups) -->
      <div>
        <div class="flex items-center justify-between mb-3">
          <div>
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-2">
              <Layers class="w-4 h-4 text-emerald-500" />
              <span>Pengelompokan Batch Produksi Vendor</span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Pemisahan kluster ukuran sepatu untuk efisiensi pemotongan sol dan cetakan vendor.
            </p>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div 
            v-for="(grp, gKey) in size_groups" 
            :key="gKey"
            class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs relative overflow-hidden"
          >
            <div class="flex items-center justify-between text-xs font-semibold">
              <span class="text-slate-800 dark:text-slate-200">{{ grp.label }}</span>
              <span class="font-mono text-xs font-bold" :style="{ color: grp.color }">
                {{ grp.pct }}%
              </span>
            </div>

            <div class="mt-3 text-2xl font-black text-slate-900 dark:text-white font-mono">
              {{ grp.count }} <span class="text-xs font-normal text-slate-500">pasang</span>
            </div>

            <!-- Progress Bar -->
            <div class="w-full bg-slate-100 dark:bg-slate-800 h-2 rounded-full overflow-hidden mt-2.5">
              <div 
                class="h-full rounded-full transition-all duration-500"
                :style="{ width: `${grp.pct}%`, backgroundColor: grp.color }"
              ></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Interactive School-by-School Comparator (Side by Side) -->
      <div class="p-6 rounded-3xl bg-gradient-to-br from-indigo-50/70 via-white to-emerald-50/70 dark:from-slate-900/90 dark:via-slate-900 dark:to-slate-900/90 border border-indigo-200/80 dark:border-indigo-900/50 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-600 text-white shadow-xs">
              <ArrowRightLeft class="w-3 h-3" />
              <span>Komparator Interaktif</span>
            </div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white mt-1">
              Perbandingan Kebutuhan Antar Sekolah (Side-by-Side)
            </h2>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
              Bandingkan profil jumlah siswa, rasio gender, dan sebaran ukuran sepatu antara dua sekolah secara langsung.
            </p>
          </div>

          <!-- School Selector Controls -->
          <div class="flex items-center gap-2">
            <select
              v-model="schoolAId"
              class="text-xs font-semibold rounded-xl bg-white dark:bg-slate-800 border-indigo-300 dark:border-indigo-700 text-indigo-700 dark:text-indigo-300 px-3 py-2 cursor-pointer shadow-xs focus:ring-2 focus:ring-indigo-500"
            >
              <option v-for="s in schools" :key="`a-${s.id}`" :value="String(s.id)">
                Sekolah A: {{ s.nama_sekolah }}
              </option>
            </select>

            <span class="text-xs font-bold text-slate-400">VS</span>

            <select
              v-model="schoolBId"
              class="text-xs font-semibold rounded-xl bg-white dark:bg-slate-800 border-emerald-300 dark:border-emerald-700 text-emerald-700 dark:text-emerald-300 px-3 py-2 cursor-pointer shadow-xs focus:ring-2 focus:ring-emerald-500"
            >
              <option value="">Pilih Sekolah B...</option>
              <option v-for="s in schools" :key="`b-${s.id}`" :value="String(s.id)">
                Sekolah B: {{ s.nama_sekolah }}
              </option>
            </select>
          </div>
        </div>

        <!-- Comparative Cards & Comparative Chart -->
        <div v-if="schoolA" class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-2">
          
          <!-- Metric Comparison Cards -->
          <div class="grid grid-cols-2 gap-4">
            <!-- Card Sekolah A -->
            <div class="p-4 rounded-2xl bg-white/90 dark:bg-slate-800/90 border-2 border-indigo-500/40 shadow-xs space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300">
                  Sekolah A
                </span>
                <span class="text-[11px] text-slate-500 font-mono">{{ schoolA.kota }}</span>
              </div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate" :title="schoolA.nama_sekolah">
                {{ schoolA.nama_sekolah }}
              </h3>
              
              <div class="space-y-1.5 pt-1 text-xs">
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                  <span>Total Kebutuhan:</span>
                  <span class="font-bold text-indigo-600 dark:text-indigo-400 font-mono">{{ schoolA.total_recipients }} psg</span>
                </div>
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                  <span>Siswa : Staff:</span>
                  <span class="font-medium font-mono">{{ schoolA.total_siswa }} : {{ schoolA.total_staff }}</span>
                </div>
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                  <span>Pria : Wanita:</span>
                  <span class="font-medium font-mono">{{ schoolA.ratio_pria }}% : {{ schoolA.ratio_wanita }}%</span>
                </div>
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                  <span>Ukuran Dominan:</span>
                  <span class="font-bold text-amber-600 dark:text-amber-400 font-mono">Size {{ schoolA.top_size || '-' }}</span>
                </div>
              </div>
            </div>

            <!-- Card Sekolah B -->
            <div v-if="schoolB" class="p-4 rounded-2xl bg-white/90 dark:bg-slate-800/90 border-2 border-emerald-500/40 shadow-xs space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300">
                  Sekolah B
                </span>
                <span class="text-[11px] text-slate-500 font-mono">{{ schoolB.kota }}</span>
              </div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate" :title="schoolB.nama_sekolah">
                {{ schoolB.nama_sekolah }}
              </h3>
              
              <div class="space-y-1.5 pt-1 text-xs">
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                  <span>Total Kebutuhan:</span>
                  <span class="font-bold text-emerald-600 dark:text-emerald-400 font-mono">{{ schoolB.total_recipients }} psg</span>
                </div>
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                  <span>Siswa : Staff:</span>
                  <span class="font-medium font-mono">{{ schoolB.total_siswa }} : {{ schoolB.total_staff }}</span>
                </div>
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                  <span>Pria : Wanita:</span>
                  <span class="font-medium font-mono">{{ schoolB.ratio_pria }}% : {{ schoolB.ratio_wanita }}%</span>
                </div>
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                  <span>Ukuran Dominan:</span>
                  <span class="font-bold text-amber-600 dark:text-amber-400 font-mono">Size {{ schoolB.top_size || '-' }}</span>
                </div>
              </div>
            </div>

            <!-- Placeholder if school B not selected -->
            <div v-else class="p-4 rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-700 flex flex-col items-center justify-center text-center p-6 text-slate-400">
              <ArrowRightLeft class="w-8 h-8 opacity-40 mb-2" />
              <p class="text-xs font-semibold">Pilih Sekolah B di atas</p>
              <p class="text-[11px] text-slate-500">untuk melihat perbandingan langsung</p>
            </div>
          </div>

          <!-- Overlaid Size Comparison Chart -->
          <div class="p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700/80 shadow-xs">
            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 mb-2">
              Perbandingan Sebaran Ukuran: {{ schoolA.nama_sekolah }} vs {{ schoolB?.nama_sekolah || '...' }}
            </h4>
            <BaseChart :option="schoolComparisonChartOption" height="230px" />
          </div>

        </div>
      </div>

      <!-- School Summary & Comparison Leaderboard Table -->
      <div class="p-5 sm:p-6 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div>
            <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <SchoolIcon class="w-4 h-4 text-emerald-500" />
              <span>Rekapitulasi Komparatif Seluruh Sekolah ({{ schools.length }} Terdaftar)</span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              Urutan sekolah berdasarkan total volume pengadaan sepatu siswa &amp; staf.
            </p>
          </div>

          <Link
            :href="getUrl('/shoes')"
            class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:text-emerald-500 flex items-center gap-1 cursor-pointer"
          >
            <span>Buka Rincian Siswa</span>
            <ChevronRight class="w-4 h-4" />
          </Link>
        </div>

        <div class="overflow-x-auto rounded-xl border border-slate-200 dark:border-slate-800">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-100/80 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 font-semibold border-b border-slate-200 dark:border-slate-700 uppercase tracking-wider text-[11px]">
              <tr>
                <th class="py-3 px-4">Sekolah</th>
                <th class="py-3 px-4">Kota / Wilayah</th>
                <th class="py-3 px-4 text-center">Siswa (L / P)</th>
                <th class="py-3 px-4 text-center">Guru &amp; Tendik</th>
                <th class="py-3 px-4 text-center">Ukuran Dominan</th>
                <th class="py-3 px-4 text-center">Total Kebutuhan</th>
                <th class="py-3 px-4 text-right">Aksi Komparasi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-medium">
              <tr 
                v-for="sch in schools" 
                :key="sch.id"
                class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors"
                :class="selectedSchool === String(sch.id) ? 'bg-indigo-50/60 dark:bg-indigo-950/30 font-semibold' : ''"
              >
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-900 dark:text-white">{{ sch.nama_sekolah }}</div>
                  <div class="text-[11px] text-slate-400 font-normal">PIC: {{ sch.pic_nama || '-' }}</div>
                </td>
                <td class="py-3.5 px-4 font-mono text-slate-600 dark:text-slate-300">
                  {{ sch.kota }}
                </td>
                <td class="py-3.5 px-4 text-center">
                  <span class="inline-flex items-center gap-1 font-mono">
                    <span class="text-blue-600 dark:text-blue-400">{{ sch.total_pria }} L</span>
                    <span class="text-slate-400">/</span>
                    <span class="text-rose-600 dark:text-rose-400">{{ sch.total_wanita }} P</span>
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center font-mono text-slate-700 dark:text-slate-300">
                  {{ sch.total_staff }} orang
                </td>
                <td class="py-3.5 px-4 text-center">
                  <span class="px-2 py-0.5 rounded-full text-[11px] font-mono font-bold bg-amber-50 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                    Size {{ sch.top_size || '-' }}
                  </span>
                </td>
                <td class="py-3.5 px-4 text-center font-mono font-bold text-sm text-slate-900 dark:text-white">
                  {{ sch.total_recipients }} <span class="text-xs font-normal text-slate-500">pasang</span>
                </td>
                <td class="py-3.5 px-4 text-right">
                  <div class="inline-flex items-center gap-1.5">
                    <button
                      type="button"
                      @click="triggerCompare(sch.id)"
                      class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 border border-indigo-200 dark:border-indigo-800 transition-all cursor-pointer"
                      title="Bandingkan sekolah ini di Komparator Side-by-Side"
                    >
                      Bandingkan ⇄
                    </button>
                    <button
                      type="button"
                      @click="selectedSchool = String(sch.id); applyFilters();"
                      class="px-2.5 py-1 text-[11px] font-semibold rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700 transition-all cursor-pointer"
                      title="Filter dashboard khusus sekolah ini"
                    >
                      Filter Data
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="schools.length === 0">
                <td colspan="7" class="py-10 text-center text-slate-400">
                  Belum ada data sekolah yang diimpor. Silakan lakukan import berkas Excel terlebih dahulu.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>
</template>
