<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import HybridNavbar from '../../Components/HybridNavbar.vue';
import { useSidebar } from '../../Composables/useSidebar';
import {
  FileSpreadsheet,
  UploadCloud,
  Download,
  School as SchoolIcon,
  Users,
  CheckCircle2,
  AlertCircle,
  Trash2,
  Search,
  Filter,
  Sparkles,
  BarChart3,
  UserCheck,
  X,
  Layers,
  FileUp,
  Info,
  Edit2,
  Briefcase,
  BadgeCheck,
  Table2,
  ArrowLeftRight
} from 'lucide-vue-next';

const { layoutPaddingClass } = useSidebar();

const props = defineProps({
  stats: {
    type: Object,
    required: true,
  },
  records: {
    type: Object,
    required: true,
  },
  rekapSekolah: {
    type: Array,
    default: () => [],
  },
  rekapStatus: {
    type: Array,
    default: () => [],
  },
  batches: {
    type: [Object, Array],
    default: () => ({}),
  },
  schoolList: {
    type: Array,
    default: () => [],
  },
  statusList: {
    type: Array,
    default: () => [],
  },
  jabatanList: {
    type: Array,
    default: () => [],
  },
  picList: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({
      sekolah: '',
      jenis_kelamin: '',
      status: '',
      jabatan: '',
      pic: '',
      search: '',
      tab: 'records',
    }),
  },
  schema: {
    type: Object,
    default: () => ({}),
  },
});

const page = usePage();

const batchList = computed(() => {
  if (Array.isArray(props.batches)) return props.batches;
  return props.batches?.data || [];
});

const getInitialTab = () => {
  if (typeof window !== 'undefined') {
    const params = new URLSearchParams(window.location.search);
    const urlTab = params.get('tab');
    if (urlTab && ['records', 'rekap', 'batches'].includes(urlTab)) {
      return urlTab;
    }
    if (params.has('batch_page')) {
      return 'batches';
    }
  }
  if (props.filters?.tab && ['records', 'rekap', 'batches'].includes(props.filters.tab)) {
    return props.filters.tab;
  }
  return 'records';
};

const activeTab = ref(getInitialTab());

watch(
  () => props.filters?.tab,
  (newTab) => {
    if (newTab && ['records', 'rekap', 'batches'].includes(newTab)) {
      activeTab.value = newTab;
    }
  }
);

const switchTab = (tab) => {
  activeTab.value = tab;
  if (typeof window !== 'undefined') {
    try {
      const url = new URL(window.location.href);
      url.searchParams.set('tab', tab);
      window.history.replaceState({}, '', url.toString());
    } catch (e) {}
  }
};

const getUrl = (path) => {
  if (typeof window !== 'undefined') {
    const match = window.location.pathname.match(/^(\/[^\/]+\/public)/);
    if (match) {
      return `${match[1]}${path}`;
    }
  }
  return path;
};

// Local filter state
const search = ref(props.filters.search || '');
const selectedSekolah = ref(props.filters.sekolah || '');
const selectedGender = ref(props.filters.jenis_kelamin || '');
const selectedStatus = ref(props.filters.status || '');
const selectedJabatan = ref(props.filters.jabatan || '');
const selectedPic = ref(props.filters.pic || '');

const applyFilters = () => {
  router.get(getUrl('/guru-tendik'), {
    sekolah: selectedSekolah.value || undefined,
    jenis_kelamin: selectedGender.value || undefined,
    status: selectedStatus.value || undefined,
    jabatan: selectedJabatan.value || undefined,
    pic: selectedPic.value || undefined,
    search: search.value || undefined,
    tab: activeTab.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

const filterByGender = (genderVal) => {
  if (selectedGender.value === genderVal) {
    selectedGender.value = '';
  } else {
    selectedGender.value = genderVal;
  }
  applyFilters();
};

const resetFilters = () => {
  search.value = '';
  selectedSekolah.value = '';
  selectedGender.value = '';
  selectedStatus.value = '';
  selectedJabatan.value = '';
  selectedPic.value = '';
  applyFilters();
};

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

const goToBatchPage = (targetUrl) => {
  if (!targetUrl) return;
  try {
    const urlObj = new URL(targetUrl, window.location.origin);
    urlObj.searchParams.set('tab', 'batches');
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

// Export Excel with Deduplicate option
const deduplicateExport = ref(false);

const excelExportUrl = computed(() => {
  const params = new URLSearchParams();
  if (selectedSekolah.value) params.append('sekolah', selectedSekolah.value);
  if (selectedGender.value) params.append('jenis_kelamin', selectedGender.value);
  if (selectedStatus.value) params.append('status', selectedStatus.value);
  if (selectedJabatan.value) params.append('jabatan', selectedJabatan.value);
  if (selectedPic.value) params.append('pic', selectedPic.value);
  if (search.value) params.append('search', search.value);
  if (deduplicateExport.value) params.append('deduplicate', '1');
  const query = params.toString();
  return getUrl(query ? `/guru-tendik/export/excel?${query}` : '/guru-tendik/export/excel');
});

// Upload Modal state
const isUploadModalOpen = ref(false);
const dragOver = ref(false);
const selectedFiles = ref([]);
const selectedPicOption = ref('');
const customPicName = ref('');

const uploadForm = useForm({
  excel_files: [],
  pic_nama: '',
});

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files);
  if (files.length > 0) {
    selectedFiles.value = [...selectedFiles.value, ...files];
    uploadForm.excel_files = selectedFiles.value;
  }
};

const handleDrop = (event) => {
  dragOver.value = false;
  const files = Array.from(event.dataTransfer.files).filter(
    (f) => f.name.endsWith('.xlsx') || f.name.endsWith('.xls') || f.name.endsWith('.csv')
  );
  if (files.length > 0) {
    selectedFiles.value = [...selectedFiles.value, ...files];
    uploadForm.excel_files = selectedFiles.value;
  }
};

const removeFile = (idx) => {
  selectedFiles.value.splice(idx, 1);
  uploadForm.excel_files = selectedFiles.value;
};

const submitUpload = () => {
  if (selectedFiles.value.length === 0) return;

  let finalPic = '';
  if (selectedPicOption.value === '__custom__') {
    finalPic = customPicName.value.trim();
  } else {
    finalPic = selectedPicOption.value;
  }

  uploadForm.excel_files = selectedFiles.value;
  uploadForm.pic_nama = finalPic;

  uploadForm.post(getUrl('/guru-tendik/import'), {
    onSuccess: () => {
      isUploadModalOpen.value = false;
      selectedFiles.value = [];
      selectedPicOption.value = '';
      customPicName.value = '';
      uploadForm.reset();
    },
  });
};

// Edit Record Modal state
const isEditRecordModalOpen = ref(false);
const editingRecord = ref(null);
const editRecordForm = useForm({
  no: '',
  nama_lengkap: '',
  jenis_kelamin: 'Laki-laki',
  status: '',
  jabatan: '',
  sekolah: '',
});

const openEditRecordModal = (rec) => {
  editingRecord.value = rec;
  editRecordForm.no = rec.no ?? '';
  editRecordForm.nama_lengkap = rec.nama_lengkap || '';
  editRecordForm.jenis_kelamin = rec.jenis_kelamin || 'Laki-laki';
  editRecordForm.status = rec.status || '';
  editRecordForm.jabatan = rec.jabatan || '';
  editRecordForm.sekolah = rec.sekolah || '';
  isEditRecordModalOpen.value = true;
};

const submitEditRecord = () => {
  if (!editingRecord.value) return;
  editRecordForm.patch(getUrl(`/guru-tendik/records/${editingRecord.value.id}`), {
    preserveScroll: true,
    onSuccess: () => {
      isEditRecordModalOpen.value = false;
      editingRecord.value = null;
    },
  });
};

const deleteRecord = (rec) => {
  if (confirm(`Yakin ingin menghapus data "${rec.nama_lengkap}" (${rec.sekolah})?`)) {
    router.delete(getUrl(`/guru-tendik/records/${rec.id}`), {
      preserveScroll: true,
    });
  }
};

const deleteBatch = (batchId, fileName) => {
  if (confirm(`Yakin ingin menghapus riwayat import berkas "${fileName}" beserta seluruh data di dalamnya?`)) {
    router.delete(getUrl(`/guru-tendik/batches/${batchId}`), {
      preserveScroll: true,
    });
  }
};

const maxRekapSekolahTotal = computed(() => {
  if (!props.rekapSekolah || props.rekapSekolah.length === 0) return 1;
  return Math.max(...props.rekapSekolah.map((item) => Number(item.total) || 1));
});
</script>

<template>
  <Head title="Database Import Guru & Tendik — Format Rekap Total Revisi" />

  <div
    class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 selection:bg-indigo-500 selection:text-white transition-all duration-300"
    :class="layoutPaddingClass"
  >
    <HybridNavbar mode="inertia" currentRoute="guru-tendik" />

    <main class="w-full px-4 sm:px-6 lg:px-8 py-8 sm:py-10 space-y-8">
      <!-- Flash Notifications & Warnings -->
      <div v-if="page.props.flash?.success" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300 flex items-start gap-3 backdrop-blur-md shadow-sm">
        <CheckCircle2 class="w-5 h-5 flex-shrink-0 text-emerald-600 dark:text-emerald-400 mt-0.5" />
        <div class="flex-1">
          <p class="text-sm font-semibold">{{ page.props.flash.success }}</p>
          <div v-if="page.props.flash?.import_warnings?.length" class="mt-2 text-xs space-y-1 text-emerald-700/90 dark:text-emerald-400/90">
            <p class="font-medium">Catatan Penyesuaian Data:</p>
            <ul class="list-disc list-inside">
              <li v-for="(warn, wIdx) in page.props.flash.import_warnings" :key="wIdx">{{ warn }}</li>
            </ul>
          </div>
        </div>
      </div>

      <div v-if="page.props.flash?.error" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-800 dark:text-rose-300 flex items-start gap-3 backdrop-blur-md shadow-sm">
        <AlertCircle class="w-5 h-5 flex-shrink-0 text-rose-600 dark:text-rose-400 mt-0.5" />
        <p class="text-sm font-semibold">{{ page.props.flash.error }}</p>
      </div>

      <!-- Hero & Quick Action Bar -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 pb-2 border-b border-slate-200/80 dark:border-slate-800/80">
        <div>
          <div class="flex items-center gap-2.5 mb-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-cyan-50 dark:bg-cyan-950/60 text-cyan-700 dark:text-cyan-300 border border-cyan-200/80 dark:border-cyan-800/50">
              <Sparkles class="w-3.5 h-3.5 text-cyan-500 animate-pulse" />
              Database Guru &amp; Tendik
            </span>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">FORMAT REKAP TOTAL REVISI.xlsx</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
            Database Import &amp; Rekapitulasi Guru, Tendik
          </h1>
          <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 max-w-2xl">
            Manajemen terpadu data Guru &amp; Tenaga Kependidikan dari sheet <strong>GURU,TENDIK (LAKI LAKI)</strong> dan <strong>GURU,TENDIK (PEREMPUAN)</strong> beserta status kepegawaian, jabatan, dan sekolah.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <!-- Import Button -->
          <button
            @click="isUploadModalOpen = true"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm text-white bg-gradient-to-r from-cyan-600 via-teal-600 to-indigo-600 hover:from-cyan-500 hover:to-indigo-500 shadow-md shadow-cyan-500/20 active:scale-95 transition-all cursor-pointer"
          >
            <UploadCloud class="w-4 h-4" />
            Import Excel Revisi
          </button>

          <!-- Download Template -->
          <a
            :href="getUrl('/guru-tendik/template')"
            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl font-medium text-sm text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-sm active:scale-95 transition-all cursor-pointer"
            title="Unduh Template FORMAT REKAP TOTAL REVISI.xlsx"
          >
            <Download class="w-4 h-4 text-cyan-600 dark:text-cyan-400" />
            Template Excel
          </a>

          <!-- Quick Navigation to Comparison Page -->
          <a
            :href="getUrl('/guru-tendik-comparison')"
            @click.prevent="router.visit(getUrl('/guru-tendik-comparison'))"
            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl font-semibold text-sm text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-950/60 border border-violet-200 dark:border-violet-800 hover:bg-violet-100 dark:hover:bg-violet-900/60 shadow-sm active:scale-95 transition-all cursor-pointer"
            title="Bandingkan dengan Rekapitulasi Sepatu (Guru & Tendik)"
          >
            <ArrowLeftRight class="w-4 h-4 text-violet-600 dark:text-violet-400" />
            Bandingkan Data Sepatu
          </a>

          <!-- Export Excel Group with Deduplicate Option -->
          <div class="inline-flex flex-wrap items-center gap-2 p-1 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm">
            <label
              class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-medium cursor-pointer select-none transition-colors"
              :class="deduplicateExport
                ? 'bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800'
                : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-slate-800/60 border border-transparent'"
              title="Jika dicentang, data Guru/Tendik dengan Nama Lengkap dan Sekolah yang sama akan dihapus salah satunya agar tidak duplikat"
            >
              <input
                v-model="deduplicateExport"
                type="checkbox"
                class="w-3.5 h-3.5 rounded text-emerald-600 focus:ring-emerald-500 border-slate-300 dark:border-slate-600 cursor-pointer"
              />
              <span>Hapus Duplikat (Nama &amp; Sekolah)</span>
            </label>

            <a
              :href="excelExportUrl"
              class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl font-medium text-sm text-slate-700 dark:text-slate-200 bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 shadow-xs active:scale-95 transition-all cursor-pointer"
            >
              <FileSpreadsheet class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
              <span>{{ deduplicateExport ? 'Export Excel (Tanpa Duplikat)' : 'Export Excel' }}</span>
            </a>
          </div>
        </div>
      </div>

      <!-- KPI Summary Cards (Clickable for Quick Gender/Sheet Filter) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Guru & Tendik -->
        <div class="p-5 rounded-2xl bg-gradient-to-br from-cyan-600 via-teal-600 to-indigo-700 text-white shadow-lg shadow-cyan-500/15 relative overflow-hidden">
          <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
          <div class="flex items-center justify-between text-cyan-100 text-xs font-semibold mb-2">
            <span>Total Guru &amp; Tendik</span>
            <Users class="w-4 h-4 text-cyan-200" />
          </div>
          <div class="text-3xl font-extrabold tracking-tight">
            {{ stats.total_data }} <span class="text-sm font-normal text-cyan-100">Orang</span>
          </div>
          <div class="mt-2 text-xs text-cyan-100/90 flex items-center gap-2">
            <span>{{ stats.total_laki }} Laki-laki</span>
            <span>•</span>
            <span>{{ stats.total_perempuan }} Perempuan</span>
          </div>
        </div>

        <!-- Sheet 0: GURU,TENDIK (LAKI LAKI) -->
        <div
          @click="filterByGender('Laki-laki')"
          class="p-5 rounded-2xl border shadow-sm transition-all cursor-pointer"
          :class="selectedGender === 'Laki-laki'
            ? 'bg-blue-50 dark:bg-blue-950/60 border-blue-400 dark:border-blue-600 ring-2 ring-blue-500/30'
            : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800/90 hover:border-blue-300 dark:hover:border-blue-700'"
        >
          <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-xs font-semibold mb-2">
            <span>Sheet 1: Laki-laki</span>
            <span class="px-2 py-0.5 rounded-md text-[10px] font-mono bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300">
              GURU,TENDIK (LAKI LAKI)
            </span>
          </div>
          <div class="text-2xl sm:text-3xl font-extrabold text-blue-600 dark:text-blue-400">
            {{ stats.total_laki }}
          </div>
          <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
            <span>Default: Laki-laki</span>
            <span class="font-mono font-semibold text-blue-600 dark:text-blue-400">
              {{ stats.total_data > 0 ? Math.round((stats.total_laki / stats.total_data) * 100) : 0 }}%
            </span>
          </div>
        </div>

        <!-- Sheet 1: GURU,TENDIK (PEREMPUAN) -->
        <div
          @click="filterByGender('Perempuan')"
          class="p-5 rounded-2xl border shadow-sm transition-all cursor-pointer"
          :class="selectedGender === 'Perempuan'
            ? 'bg-pink-50 dark:bg-pink-950/60 border-pink-400 dark:border-pink-600 ring-2 ring-pink-500/30'
            : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800/90 hover:border-pink-300 dark:hover:border-pink-700'"
        >
          <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-xs font-semibold mb-2">
            <span>Sheet 2: Perempuan</span>
            <span class="px-2 py-0.5 rounded-md text-[10px] font-mono bg-pink-100 dark:bg-pink-900/60 text-pink-700 dark:text-pink-300">
              GURU,TENDIK (PEREMPUAN)
            </span>
          </div>
          <div class="text-2xl sm:text-3xl font-extrabold text-pink-600 dark:text-pink-400">
            {{ stats.total_perempuan }}
          </div>
          <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center justify-between">
            <span>Default: Perempuan</span>
            <span class="font-mono font-semibold text-pink-600 dark:text-pink-400">
              {{ stats.total_data > 0 ? Math.round((stats.total_perempuan / stats.total_data) * 100) : 0 }}%
            </span>
          </div>
        </div>

        <!-- Total Sekolah & Berkas -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800/90 shadow-sm">
          <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-xs font-semibold mb-2">
            <span>Cakupan Sekolah</span>
            <SchoolIcon class="w-4 h-4 text-amber-500" />
          </div>
          <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
            {{ stats.total_sekolah }}
          </div>
          <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
            Dari {{ stats.total_batches }} Berkas Import Selesai
          </div>
        </div>
      </div>

      <!-- Filter & Search Toolbar -->
      <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800/90 shadow-sm space-y-4">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
          <div class="flex flex-wrap items-center gap-3">
            <!-- Sekolah Filter -->
            <div class="flex items-center gap-2">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1">
                <SchoolIcon class="w-3.5 h-3.5 text-cyan-500" />
                Sekolah:
              </label>
              <select
                v-model="selectedSekolah"
                @change="applyFilters"
                class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-cyan-500 outline-none max-w-[220px]"
              >
                <option value="">Semua Sekolah</option>
                <option v-for="sch in schoolList" :key="sch" :value="sch">{{ sch }}</option>
              </select>
            </div>

            <!-- Jenis Kelamin / Sheet Filter -->
            <div class="flex items-center gap-2">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                Jenis Kelamin:
              </label>
              <select
                v-model="selectedGender"
                @change="applyFilters"
                class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-cyan-500 outline-none"
              >
                <option value="">Semua (Laki-laki &amp; Perempuan)</option>
                <option value="Laki-laki">Laki-laki (Sheet 1)</option>
                <option value="Perempuan">Perempuan (Sheet 2)</option>
              </select>
            </div>

            <!-- Status Filter -->
            <div class="flex items-center gap-2">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1">
                <BadgeCheck class="w-3.5 h-3.5 text-cyan-500" />
                Status:
              </label>
              <select
                v-model="selectedStatus"
                @change="applyFilters"
                class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-cyan-500 outline-none"
              >
                <option value="">Semua Status</option>
                <option v-for="st in statusList" :key="st" :value="st">{{ st }}</option>
              </select>
            </div>

            <!-- Jabatan Filter -->
            <div class="flex items-center gap-2">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1">
                <Briefcase class="w-3.5 h-3.5 text-cyan-500" />
                Jabatan:
              </label>
              <select
                v-model="selectedJabatan"
                @change="applyFilters"
                class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-cyan-500 outline-none max-w-[200px]"
              >
                <option value="">Semua Jabatan</option>
                <option v-for="jb in jabatanList" :key="jb" :value="jb">{{ jb }}</option>
              </select>
            </div>

            <!-- PIC Filter -->
            <div class="flex items-center gap-2">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1">
                <UserCheck class="w-3.5 h-3.5 text-cyan-500" />
                PIC:
              </label>
              <select
                v-model="selectedPic"
                @change="applyFilters"
                class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-cyan-500 outline-none"
              >
                <option value="">Semua PIC</option>
                <option v-for="p in picList" :key="p" :value="p">{{ p }}</option>
              </select>
            </div>
          </div>

          <!-- Search Input -->
          <div class="flex items-center gap-2">
            <div class="relative flex-1 md:w-64">
              <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
              <input
                v-model="search"
                @keyup.enter="applyFilters"
                type="text"
                placeholder="Cari nama, status, jabatan, sekolah..."
                class="w-full text-xs pl-9 pr-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-cyan-500 outline-none"
              />
            </div>
            <button
              @click="applyFilters"
              class="px-3 py-2 rounded-xl text-xs font-semibold bg-cyan-600 hover:bg-cyan-700 text-white transition-colors cursor-pointer"
            >
              Cari
            </button>
            <button
              v-if="selectedSekolah || selectedGender || selectedStatus || selectedJabatan || selectedPic || search"
              @click="resetFilters"
              class="px-2.5 py-2 rounded-xl text-xs font-medium text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 transition-colors cursor-pointer"
            >
              Reset
            </button>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-2">
          <button
            @click="switchTab('records')"
            class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border-b-2 transition-all cursor-pointer"
            :class="activeTab === 'records' ? 'border-cyan-600 text-cyan-600 dark:text-cyan-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
          >
            <Table2 class="w-4 h-4" />
            Daftar Guru &amp; Tendik
            <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-cyan-100 dark:bg-cyan-950 text-cyan-700 dark:text-cyan-300 font-mono">
              {{ records.total }} Data
            </span>
          </button>

          <button
            @click="switchTab('rekap')"
            class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border-b-2 transition-all cursor-pointer"
            :class="activeTab === 'rekap' ? 'border-cyan-600 text-cyan-600 dark:text-cyan-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
          >
            <BarChart3 class="w-4 h-4" />
            Rekap Per Sekolah &amp; Status
            <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono">
              {{ rekapSekolah.length }} Sekolah
            </span>
          </button>

          <button
            @click="switchTab('batches')"
            class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border-b-2 transition-all cursor-pointer"
            :class="activeTab === 'batches' ? 'border-cyan-600 text-cyan-600 dark:text-cyan-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
          >
            <Layers class="w-4 h-4" />
            Riwayat Berkas &amp; Schema
            <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono">
              {{ batches?.total ?? batchList.length }} Berkas
            </span>
          </button>
        </div>
      </div>

      <!-- TAB 1: DAFTAR GURU & TENDIK -->
      <div v-if="activeTab === 'records'" class="space-y-4">
        <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
          <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th class="py-3.5 px-4 text-center w-14">No (A)</th>
                <th class="py-3.5 px-4">Nama Lengkap (B)</th>
                <th class="py-3.5 px-4 text-center">Jenis Kelamin (C)</th>
                <th class="py-3.5 px-4 text-center">Status (D)</th>
                <th class="py-3.5 px-4">Jabatan (E)</th>
                <th class="py-3.5 px-4">Sekolah (F)</th>
                <th class="py-3.5 px-4 text-center w-28">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
              <tr
                v-for="(rec, idx) in records.data"
                :key="rec.id"
                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
              >
                <td class="py-3 px-4 text-center font-mono text-slate-500">
                  {{ rec.no ?? ((records.current_page - 1) * records.per_page + idx + 1) }}
                </td>
                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                  {{ rec.nama_lengkap }}
                </td>
                <td class="py-3 px-4 text-center">
                  <div class="flex flex-col items-center gap-0.5">
                    <span
                      class="px-2.5 py-0.5 rounded-md font-bold text-[11px]"
                      :class="rec.jenis_kelamin === 'Laki-laki'
                        ? 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-800'
                        : 'bg-pink-50 dark:bg-pink-950/60 text-pink-700 dark:text-pink-300 border border-pink-200 dark:border-pink-800'"
                    >
                      {{ rec.jenis_kelamin }}
                    </span>
                    <span v-if="rec.sheet_name" class="text-[10px] font-mono text-slate-400 truncate max-w-[140px]" :title="rec.sheet_name">
                      {{ rec.sheet_name }}
                    </span>
                  </div>
                </td>
                <td class="py-3 px-4 text-center">
                  <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                    {{ rec.status }}
                  </span>
                </td>
                <td class="py-3 px-4 font-medium text-slate-700 dark:text-slate-300">
                  {{ rec.jabatan }}
                </td>
                <td class="py-3 px-4">
                  <div class="font-semibold text-slate-900 dark:text-slate-100">{{ rec.sekolah }}</div>
                  <div v-if="rec.pic_nama" class="text-[10px] text-slate-400 flex items-center gap-1 mt-0.5">
                    <UserCheck class="w-3 h-3 text-cyan-500" /> PIC: {{ rec.pic_nama }}
                  </div>
                </td>
                <td class="py-3 px-4 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <button
                      @click="openEditRecordModal(rec)"
                      class="p-1.5 rounded-lg text-cyan-600 dark:text-cyan-400 hover:bg-cyan-50 dark:hover:bg-cyan-950/60 transition-colors cursor-pointer"
                      title="Edit Data"
                    >
                      <Edit2 class="w-3.5 h-3.5" />
                    </button>
                    <button
                      @click="deleteRecord(rec)"
                      class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/60 transition-colors cursor-pointer"
                      title="Hapus Data"
                    >
                      <Trash2 class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="records.data.length === 0">
                <td colspan="7" class="py-10 text-center text-slate-500">
                  Belum ada data Guru &amp; Tendik yang di-import atau sesuai filter pencarian.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Controls -->
        <div v-if="records.total > records.per_page" class="flex flex-col sm:flex-row items-center justify-between gap-3 px-2 pt-2 text-xs">
          <div class="text-slate-500">
            Menampilkan {{ records.from }} - {{ records.to }} dari {{ records.total }} data Guru &amp; Tendik
          </div>
          <div class="flex items-center gap-1 flex-wrap justify-center">
            <template v-for="(link, lIdx) in records.links" :key="lIdx">
              <button
                v-if="link.url"
                type="button"
                @click="goToPage(link.url)"
                class="px-3 py-1.5 rounded-lg border font-medium transition-colors cursor-pointer"
                :class="link.active ? 'bg-cyan-600 border-cyan-600 text-white' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                v-html="link.label"
              ></button>
              <span v-else class="px-2 py-1.5 text-slate-400" v-html="link.label"></span>
            </template>
          </div>
        </div>
      </div>

      <!-- TAB 2: REKAPITULASI PER SEKOLAH & STATUS -->
      <div v-if="activeTab === 'rekap'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Rekap Per Sekolah (2 Columns wide) -->
        <div class="lg:col-span-2 space-y-3">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <SchoolIcon class="w-4 h-4 text-cyan-500" />
            Rekapitulasi Guru &amp; Tendik Berdasarkan Sekolah
          </h3>
          <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
            <table class="w-full text-left text-sm">
              <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800">
                <tr>
                  <th class="py-3.5 px-4 text-center w-12">No</th>
                  <th class="py-3.5 px-4">Nama Sekolah</th>
                  <th class="py-3.5 px-4 text-center text-blue-600 dark:text-blue-400">Laki-laki</th>
                  <th class="py-3.5 px-4 text-center text-pink-600 dark:text-pink-400">Perempuan</th>
                  <th class="py-3.5 px-4 text-center">Total</th>
                  <th class="py-3.5 px-4">Proporsi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs font-mono">
                <tr
                  v-for="(item, sIdx) in rekapSekolah"
                  :key="item.sekolah"
                  class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                >
                  <td class="py-3 px-4 text-center text-slate-400">{{ sIdx + 1 }}</td>
                  <td class="py-3 px-4 font-sans font-semibold text-slate-900 dark:text-white">
                    {{ item.sekolah }}
                  </td>
                  <td class="py-3 px-4 text-center font-bold text-blue-600 dark:text-blue-400">{{ item.laki }}</td>
                  <td class="py-3 px-4 text-center font-bold text-pink-600 dark:text-pink-400">{{ item.perempuan }}</td>
                  <td class="py-3 px-4 text-center font-extrabold text-cyan-600 dark:text-cyan-400">{{ item.total }}</td>
                  <td class="py-3 px-4 min-w-[120px]">
                    <div class="w-full bg-slate-100 dark:bg-slate-800 rounded-full h-2 overflow-hidden">
                      <div
                        class="bg-gradient-to-r from-cyan-500 to-indigo-600 h-2 rounded-full"
                        :style="{ width: `${(Number(item.total) / maxRekapSekolahTotal) * 100}%` }"
                      ></div>
                    </div>
                  </td>
                </tr>
                <tr v-if="rekapSekolah.length === 0">
                  <td colspan="6" class="py-8 text-center text-slate-500 font-sans">Belum ada data rekapitulasi sekolah.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Rekap Per Status (1 Column wide) -->
        <div class="space-y-3">
          <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
            <BadgeCheck class="w-4 h-4 text-emerald-500" />
            Rekap Berdasarkan Status
          </h3>
          <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
            <table class="w-full text-left text-sm">
              <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800">
                <tr>
                  <th class="py-3.5 px-4">Status</th>
                  <th class="py-3.5 px-3 text-center text-blue-600 dark:text-blue-400">L</th>
                  <th class="py-3.5 px-3 text-center text-pink-600 dark:text-pink-400">P</th>
                  <th class="py-3.5 px-4 text-center">Total</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs font-mono">
                <tr
                  v-for="st in rekapStatus"
                  :key="st.status"
                  class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
                >
                  <td class="py-3 px-4 font-sans font-semibold text-slate-900 dark:text-white">{{ st.status }}</td>
                  <td class="py-3 px-3 text-center text-blue-600 dark:text-blue-400">{{ st.laki }}</td>
                  <td class="py-3 px-3 text-center text-pink-600 dark:text-pink-400">{{ st.perempuan }}</td>
                  <td class="py-3 px-4 text-center font-bold text-emerald-600 dark:text-emerald-400">{{ st.total }}</td>
                </tr>
                <tr v-if="rekapStatus.length === 0">
                  <td colspan="4" class="py-8 text-center text-slate-500 font-sans">Belum ada data status.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 3: RIWAYAT BERKAS IMPORT & SPESIFIKASI SCHEMA -->
      <div v-if="activeTab === 'batches'" class="space-y-6">
        <!-- Schema Specification Banner -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 shadow-sm space-y-4">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
            <div>
              <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <FileSpreadsheet class="w-4 h-4 text-cyan-500" />
                Spesifikasi Struktur Database Import: <span class="font-mono text-cyan-600 dark:text-cyan-400">{{ schema.file_name || 'FORMAT REKAP TOTAL REVISI.xlsx' }}</span>
              </h3>
              <p class="text-xs text-slate-500 mt-0.5">
                Baris Header: <strong>Row 3</strong> • Awal Baris Data: <strong>Row 4</strong> • Kolom A s/d F
              </p>
            </div>
            <a
              :href="getUrl('/guru-tendik/template')"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-cyan-50 dark:bg-cyan-950/60 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-800 hover:bg-cyan-100 transition-colors"
            >
              <Download class="w-3.5 h-3.5" />
              Unduh Template Sesuai Schema
            </a>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div
              v-for="s in (schema.sheets || [])"
              :key="s.sheet_index"
              class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-slate-200/80 dark:border-slate-700/80 space-y-2.5 text-xs"
            >
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-900 dark:text-white font-mono">
                  Sheet #{{ s.sheet_index + 1 }}: {{ s.sheet_name }}
                </span>
                <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-cyan-100 dark:bg-cyan-900/60 text-cyan-800 dark:text-cyan-300">
                  Default JK: {{ s.default_values?.jenis_kelamin }}
                </span>
              </div>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-1.5 pt-1">
                <div
                  v-for="col in s.columns"
                  :key="col.column_letter"
                  class="px-2.5 py-1.5 rounded-lg bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 flex items-center justify-between"
                >
                  <div>
                    <span class="font-mono font-bold text-cyan-600 dark:text-cyan-400 mr-1">{{ col.column_letter }}:</span>
                    <span class="font-medium text-slate-700 dark:text-slate-200">{{ col.header_name }}</span>
                  </div>
                  <span v-if="col.required" class="text-[10px] text-rose-500 font-bold" title="Wajib diisi">*</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Batches Table -->
        <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
          <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th class="py-3.5 px-4">Nama Berkas Excel</th>
                <th class="py-3.5 px-4 text-center">PIC</th>
                <th class="py-3.5 px-4 text-center">Laki-laki</th>
                <th class="py-3.5 px-4 text-center">Perempuan</th>
                <th class="py-3.5 px-4 text-center">Total Data</th>
                <th class="py-3.5 px-4 text-center">Sekolah</th>
                <th class="py-3.5 px-4">Diimpor Oleh</th>
                <th class="py-3.5 px-4 text-center">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
              <tr
                v-for="b in batchList"
                :key="b.id"
                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
              >
                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white flex items-center gap-2">
                  <FileSpreadsheet class="w-4 h-4 text-emerald-600 shrink-0" />
                  <span class="truncate max-w-xs">{{ b.file_name }}</span>
                </td>
                <td class="py-3 px-4 text-center font-mono">{{ b.pic_nama || '-' }}</td>
                <td class="py-3 px-4 text-center font-mono font-bold text-blue-600 dark:text-blue-400">{{ b.total_laki }}</td>
                <td class="py-3 px-4 text-center font-mono font-bold text-pink-600 dark:text-pink-400">{{ b.total_perempuan }}</td>
                <td class="py-3 px-4 text-center font-mono font-extrabold text-cyan-600 dark:text-cyan-400">{{ b.total_data }}</td>
                <td class="py-3 px-4 text-center font-mono">{{ b.total_sekolah }}</td>
                <td class="py-3 px-4 text-slate-500">{{ b.user?.name || 'System' }}</td>
                <td class="py-3 px-4 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <a
                      :href="getUrl(`/guru-tendik/batches/${b.id}/download`)"
                      class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 p-1.5 rounded-lg hover:bg-indigo-50 dark:hover:bg-indigo-950/50 transition-colors"
                      title="Unduh Berkas Excel Asli"
                    >
                      <Download class="w-4 h-4" />
                    </a>
                    <button
                      @click="deleteBatch(b.id, b.file_name)"
                      class="text-rose-500 hover:text-rose-700 p-1.5 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/50 transition-colors cursor-pointer"
                      title="Hapus Riwayat Batch Ini"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="batchList.length === 0">
                <td colspan="8" class="py-8 text-center text-slate-500">
                  Belum ada berkas Excel yang di-import.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Batches -->
        <div
          v-if="batches?.total && batches.total > batches.per_page"
          class="flex flex-col sm:flex-row items-center justify-between gap-3 px-2 pt-2 text-xs"
        >
          <div class="text-slate-500">
            Menampilkan {{ batches.from }} - {{ batches.to }} dari {{ batches.total }} berkas import
          </div>
          <div class="flex items-center gap-1 flex-wrap justify-center">
            <template v-for="(link, lIdx) in batches.links" :key="lIdx">
              <button
                v-if="link.url"
                type="button"
                @click="goToBatchPage(link.url)"
                class="px-3 py-1.5 rounded-lg border font-medium transition-colors cursor-pointer"
                :class="link.active ? 'bg-cyan-600 border-cyan-600 text-white' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                v-html="link.label"
              ></button>
              <span v-else class="px-2 py-1.5 text-slate-400" v-html="link.label"></span>
            </template>
          </div>
        </div>
      </div>
    </main>

    <!-- MODAL: UPLOAD EXCEL FILE -->
    <div
      v-if="isUploadModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
      @click.self="isUploadModalOpen = false"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-xl w-full p-6 shadow-2xl relative space-y-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-xl bg-cyan-100 dark:bg-cyan-950/60 flex items-center justify-center text-cyan-600 dark:text-cyan-400">
              <FileUp class="w-5 h-5" />
            </div>
            <div>
              <h3 class="font-bold text-base text-slate-900 dark:text-white">Import Excel Guru &amp; Tendik</h3>
              <p class="text-xs text-slate-500 font-mono">FORMAT REKAP TOTAL REVISI.xlsx</p>
            </div>
          </div>
          <button
            @click="isUploadModalOpen = false"
            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 cursor-pointer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- PIC Selection (Optional) -->
        <div class="p-4 rounded-2xl bg-cyan-50/60 dark:bg-cyan-950/30 border border-cyan-200/80 dark:border-cyan-800/50 space-y-2">
          <label class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
            <UserCheck class="w-4 h-4 text-cyan-600 dark:text-cyan-400" />
            <span>Penanggung Jawab / Nama PIC (Opsional):</span>
          </label>
          <select
            v-model="selectedPicOption"
            class="w-full px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-500 cursor-pointer"
          >
            <option value="">-- Tanpa PIC / Umum --</option>
            <option v-for="pic in picList" :key="pic" :value="pic">👤 {{ pic }}</option>
            <option value="__custom__">✏️ Tulis Nama PIC Manual...</option>
          </select>
          <div v-if="selectedPicOption === '__custom__'" class="pt-1">
            <input
              v-model="customPicName"
              type="text"
              placeholder="Ketikkan nama PIC..."
              class="w-full px-3.5 py-2 rounded-xl text-xs bg-white dark:bg-slate-800 border border-cyan-400 dark:border-cyan-600 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-cyan-500"
            />
          </div>
        </div>

        <!-- Drag & Drop Zone -->
        <div
          @dragover.prevent="dragOver = true"
          @dragleave.prevent="dragOver = false"
          @drop.prevent="handleDrop"
          class="border-2 border-dashed rounded-2xl p-6 text-center transition-all cursor-pointer"
          :class="dragOver ? 'border-cyan-500 bg-cyan-50/50 dark:bg-cyan-950/20' : 'border-slate-300 dark:border-slate-700 hover:border-cyan-400 bg-slate-50/50 dark:bg-slate-800/40'"
          @click="$refs.fileInput.click()"
        >
          <input
            ref="fileInput"
            type="file"
            multiple
            accept=".xlsx,.xls,.csv"
            class="hidden"
            @change="handleFileSelect"
          />

          <div class="w-12 h-12 mx-auto rounded-2xl bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 flex items-center justify-center mb-3">
            <UploadCloud class="w-6 h-6" />
          </div>

          <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">
            Klik untuk memilih berkas atau seret ke sini
          </p>
          <p class="text-xs text-slate-500 mt-1">
            Mendukung berkas <strong>FORMAT REKAP TOTAL REVISI.xlsx</strong> (.xlsx / .xls)
          </p>
        </div>

        <!-- Selected Files List -->
        <div v-if="selectedFiles.length > 0" class="space-y-2 max-h-44 overflow-y-auto pr-1">
          <div
            v-for="(f, fIdx) in selectedFiles"
            :key="fIdx"
            class="flex items-center justify-between p-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs"
          >
            <div class="flex items-center gap-2 min-w-0">
              <FileSpreadsheet class="w-4 h-4 text-emerald-600 shrink-0" />
              <div class="truncate">
                <p class="font-semibold text-slate-800 dark:text-slate-200 truncate">{{ f.name }}</p>
                <p class="text-[10px] text-slate-400">{{ (f.size / 1024).toFixed(1) }} KB</p>
              </div>
            </div>
            <button
              type="button"
              @click.stop="removeFile(fIdx)"
              class="text-slate-400 hover:text-rose-500 p-1 cursor-pointer"
            >
              <X class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Schema Info Note -->
        <div class="p-3.5 rounded-xl bg-slate-100 dark:bg-slate-800/80 text-xs text-slate-600 dark:text-slate-400 space-y-1">
          <div class="font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
            <Info class="w-4 h-4 text-cyan-500" />
            Struktur Sheet &amp; Kolom Otomatis:
          </div>
          <ul class="list-disc list-inside space-y-0.5 text-[11px] text-slate-500 dark:text-slate-400 pl-1">
            <li><strong>Sheet 1</strong>: <code>GURU,TENDIK (LAKI LAKI)</code> (Default JK: Laki-laki)</li>
            <li><strong>Sheet 2</strong>: <code>GURU,TENDIK (PEREMPUAN)</code> (Default JK: Perempuan)</li>
            <li><strong>Header Row 3 &amp; Data Row 4</strong>: Kolom A (No), B (Nama Lengkap), C (Jenis Kelamin), D (Status), E (Jabatan), F (Sekolah).</li>
          </ul>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button
            type="button"
            @click="isUploadModalOpen = false"
            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
          >
            Batal
          </button>
          <button
            type="button"
            @click="submitUpload"
            :disabled="selectedFiles.length === 0 || uploadForm.processing"
            class="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-cyan-600 hover:bg-cyan-700 disabled:opacity-50 shadow-md shadow-cyan-500/20 transition-all cursor-pointer"
          >
            <span v-if="uploadForm.processing">Sedang Mengimpor...</span>
            <span v-else>Mulai Import {{ selectedFiles.length > 0 ? `(${selectedFiles.length} Berkas)` : '' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: EDIT DATA GURU & TENDIK -->
    <div
      v-if="isEditRecordModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
      @click.self="isEditRecordModalOpen = false"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl relative space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center gap-2">
            <div class="p-2 rounded-xl bg-cyan-50 dark:bg-cyan-950 text-cyan-600 dark:text-cyan-400">
              <Edit2 class="w-5 h-5" />
            </div>
            <div>
              <h3 class="font-bold text-base text-slate-900 dark:text-white">Ubah Data Guru &amp; Tendik</h3>
              <p class="text-xs text-slate-500">{{ editingRecord?.sekolah }}</p>
            </div>
          </div>
          <button
            @click="isEditRecordModalOpen = false"
            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 cursor-pointer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitEditRecord" class="space-y-3.5 text-xs">
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">No Urut (A):</label>
              <input
                v-model.number="editRecordForm.no"
                type="number"
                min="1"
                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100"
              />
            </div>
            <div class="col-span-2">
              <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jenis Kelamin (C):</label>
              <select
                v-model="editRecordForm.jenis_kelamin"
                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100"
              >
                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Lengkap (B):</label>
            <input
              v-model="editRecordForm.nama_lengkap"
              type="text"
              required
              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Status (D):</label>
              <input
                v-model="editRecordForm.status"
                type="text"
                required
                placeholder="Contoh: PNS / PPPK / Honorer..."
                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100"
              />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Jabatan (E):</label>
              <input
                v-model="editRecordForm.jabatan"
                type="text"
                required
                placeholder="Contoh: Guru Kelas..."
                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100"
              />
            </div>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Sekolah (F):</label>
            <input
              v-model="editRecordForm.sekolah"
              type="text"
              required
              placeholder="Nama Sekolah..."
              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100"
            />
          </div>

          <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            <button
              type="button"
              @click="isEditRecordModalOpen = false"
              class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="editRecordForm.processing"
              class="px-4 py-2 rounded-xl font-semibold text-white bg-cyan-600 hover:bg-cyan-700 disabled:opacity-50 cursor-pointer shadow-sm"
            >
              Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

