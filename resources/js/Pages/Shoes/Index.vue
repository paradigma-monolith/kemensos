<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import HybridNavbar from '../../Components/HybridNavbar.vue';
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
  Calendar,
  MapPin,
  UserCheck,
  X,
  Layers,
  ArrowUpDown,
  FileUp,
  RefreshCw,
  Info,
  Edit2,
  AlertTriangle
} from 'lucide-vue-next';

const props = defineProps({
  stats: {
    type: Object,
    required: true,
  },
  sizeMatrix: {
    type: Array,
    default: () => [],
  },
  recipients: {
    type: Object,
    required: true,
  },
  schools: {
    type: Array,
    default: () => [],
  },
  batches: {
    type: [Object, Array],
    default: () => ({}),
  },
  picList: {
    type: Array,
    default: () => [
      'AZRIEL',
      'WENDY',
      'ADIL',
      'AYU',
      'DWI',
      'IKHSAN dan TRI',
      'INDRA',
      'RAHAYU',
      'MIFTAH',
      'RIVALDI',
      'YUPITA',
    ],
  },
  filters: {
    type: Object,
    default: () => ({
      school_id: null,
      kategori: '',
      gender: '',
      pic: '',
      search: '',
      anomaly: false,
      tab: 'matrix',
    }),
  },
});

const page = usePage();

const batchList = computed(() => {
  if (Array.isArray(props.batches)) return props.batches;
  return props.batches?.data || [];
});

// Intelligent active tab detection (matrix | recipients | batches)
const getInitialTab = () => {
  if (typeof window !== 'undefined') {
    const params = new URLSearchParams(window.location.search);
    const urlTab = params.get('tab');
    if (urlTab && ['matrix', 'recipients', 'batches'].includes(urlTab)) {
      return urlTab;
    }
    if (params.has('batch_page')) {
      return 'batches';
    }
    if (params.has('page') || params.get('anomaly') || params.get('search')) {
      return 'recipients';
    }
  }
  if (props.filters?.tab && ['matrix', 'recipients', 'batches'].includes(props.filters.tab)) {
    return props.filters.tab;
  }
  if (props.filters?.anomaly || props.filters?.search) {
    return 'recipients';
  }
  return 'matrix';
};

const activeTab = ref(getInitialTab());

watch(
  () => props.filters?.tab,
  (newTab) => {
    if (newTab && ['matrix', 'recipients', 'batches'].includes(newTab)) {
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
    } catch (e) {
      // ignore
    }
  }
};

const goToPage = (targetUrl) => {
  if (!targetUrl) return;
  try {
    const urlObj = new URL(targetUrl, window.location.origin);
    urlObj.searchParams.set('tab', 'recipients');
    if (onlyAnomaly.value) {
      urlObj.searchParams.set('anomaly', '1');
    }
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

// Search & Filter local states
const search = ref(props.filters.search || '');
const selectedSchool = ref(props.filters.school_id || '');
const selectedKategori = ref(props.filters.kategori || '');
const selectedGender = ref(props.filters.gender || '');
const selectedPic = ref(props.filters.pic || '');
const onlyAnomaly = ref(Boolean(props.filters.anomaly));

// Upload Modal state (Multi-File & PIC Support)
const isUploadModalOpen = ref(false);
const dragOver = ref(false);
const selectedFiles = ref([]);
const selectedPicOption = ref('');
const customPicName = ref('');

const uploadForm = useForm({
  excel_files: [],
  pic_nama: '',
});

// Edit School Modal state
const isEditSchoolModalOpen = ref(false);
const editingSchool = ref(null);
const editSchoolForm = useForm({
  pic_nama: '',
  kota: '',
  kepala_sekolah_nama: '',
  kepala_sekolah_nip: '',
});

// Edit Recipient Modal state (for fixing shoe size / foot length anomalies)
const isEditRecipientModalOpen = ref(false);
const editingRecipient = ref(null);
const editRecipientForm = useForm({
  nama_lengkap: '',
  jenis_kelamin: 'L',
  jabatan: '',
  kategori: 'siswa',
  panjang_kaki: '',
  ukuran_sepatu: '',
  catatan: '',
});

const openEditRecipientModal = (rec) => {
  editingRecipient.value = rec;
  editRecipientForm.nama_lengkap = rec.nama_lengkap || '';
  editRecipientForm.jenis_kelamin = rec.jenis_kelamin || 'L';
  editRecipientForm.jabatan = rec.jabatan || '';
  editRecipientForm.kategori = rec.kategori || 'siswa';
  editRecipientForm.panjang_kaki = rec.panjang_kaki ?? '';
  editRecipientForm.ukuran_sepatu = rec.ukuran_sepatu ?? '';
  editRecipientForm.catatan = rec.catatan || '';
  isEditRecipientModalOpen.value = true;
};

const swapLengthAndSize = () => {
  const temp = editRecipientForm.panjang_kaki;
  editRecipientForm.panjang_kaki = editRecipientForm.ukuran_sepatu;
  editRecipientForm.ukuran_sepatu = temp ? Math.round(Number(temp)) : '';
};

const submitEditRecipient = () => {
  if (!editingRecipient.value) return;
  editRecipientForm.patch(getUrl(`/shoes/recipients/${editingRecipient.value.id}`), {
    preserveScroll: true,
    onSuccess: () => {
      isEditRecipientModalOpen.value = false;
      editingRecipient.value = null;
    },
  });
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

// Filter actions
const applyFilters = () => {
  router.get(getUrl('/shoes'), {
    school_id: selectedSchool.value || undefined,
    kategori: selectedKategori.value || undefined,
    gender: selectedGender.value || undefined,
    pic: selectedPic.value || undefined,
    search: search.value || undefined,
    anomaly: onlyAnomaly.value ? 1 : undefined,
    tab: activeTab.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

const toggleAnomalyFilter = () => {
  onlyAnomaly.value = !onlyAnomaly.value;
  activeTab.value = 'recipients';
  applyFilters();
};

const resetFilters = () => {
  search.value = '';
  selectedSchool.value = '';
  selectedKategori.value = '';
  selectedGender.value = '';
  selectedPic.value = '';
  onlyAnomaly.value = false;
  applyFilters();
};

// File handling for multi-file upload modal
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
    (f) => f.name.endsWith('.xlsx') || f.name.endsWith('.xls')
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

  // Compute final PIC name
  let finalPic = '';
  if (selectedPicOption.value === '__custom__') {
    finalPic = customPicName.value.trim();
  } else if (selectedPicOption.value === '__auto__') {
    finalPic = '';
  } else {
    finalPic = selectedPicOption.value;
  }

  uploadForm.excel_files = selectedFiles.value;
  uploadForm.pic_nama = finalPic;

  uploadForm.post(getUrl('/shoes/import'), {
    onSuccess: () => {
      isUploadModalOpen.value = false;
      selectedFiles.value = [];
      selectedPicOption.value = '';
      customPicName.value = '';
      uploadForm.reset();
    },
  });
};

// Edit school handlers
const openEditSchoolModal = (school) => {
  editingSchool.value = school;
  editSchoolForm.pic_nama = school.pic_nama || '';
  editSchoolForm.kota = school.kota || '';
  editSchoolForm.kepala_sekolah_nama = school.kepala_sekolah_nama || '';
  editSchoolForm.kepala_sekolah_nip = school.kepala_sekolah_nip || '';
  isEditSchoolModalOpen.value = true;
};

const submitEditSchool = () => {
  if (!editingSchool.value) return;
  editSchoolForm.patch(getUrl(`/shoes/schools/${editingSchool.value.id}`), {
    preserveScroll: true,
    onSuccess: () => {
      isEditSchoolModalOpen.value = false;
      editingSchool.value = null;
    },
  });
};

const deleteBatch = (batchId, schoolName) => {
  if (confirm(`Yakin ingin menghapus riwayat import batch untuk ${schoolName}? Seluruh data penerima dalam batch ini akan dihapus.`)) {
    router.delete(getUrl(`/shoes/batches/${batchId}`), {
      preserveScroll: true,
    });
  }
};

const deleteSchool = (schoolId, schoolName) => {
  if (confirm(`PERINGATAN: Yakin ingin menghapus data sekolah "${schoolName}" beserta seluruh data sepatu yang tersimpan?`)) {
    router.delete(getUrl(`/shoes/schools/${schoolId}`), {
      preserveScroll: true,
    });
  }
};

const isSyncing = ref(false);

const syncAllFromStorage = () => {
  if (confirm('Sinkronkan ulang seluruh data sepatu dari berkas Excel yang ada di storage server?\n\nProses ini akan membaca ulang seluruh file Excel asli di storage tanpa menghapus PIC yang sudah ditentukan.')) {
    isSyncing.value = true;
    router.post(getUrl('/shoes/sync-storage'), {}, {
      preserveScroll: true,
      onFinish: () => {
        isSyncing.value = false;
      },
    });
  }
};

// Max size total for progress bar calculation
const maxMatrixTotal = computed(() => {
  if (!props.sizeMatrix || props.sizeMatrix.length === 0) return 1;
  return Math.max(...props.sizeMatrix.map((item) => Number(item.total) || 1));
});

// Currently selected school object
const activeSchoolObj = computed(() => {
  if (!selectedSchool.value) return null;
  return props.schools.find((s) => s.id === Number(selectedSchool.value)) || null;
});

// Download Export Links
const excelExportUrl = computed(() => {
  const params = new URLSearchParams();
  if (selectedSchool.value) params.append('school_id', selectedSchool.value);
  return getUrl(`/shoes/export/excel?${params.toString()}`);
});
</script>

<template>
  <Head title="Import & Rekapitulasi Sepatu SR" />

  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 selection:bg-indigo-500 selection:text-white transition-colors duration-200">
    <HybridNavbar mode="inertia" currentRoute="shoes" />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 space-y-8">
      
      <!-- Flash Notifications & Warnings -->
      <div v-if="page.props.flash?.success" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-800 dark:text-emerald-300 flex items-start gap-3 backdrop-blur-md shadow-sm">
        <CheckCircle2 class="w-5 h-5 flex-shrink-0 text-emerald-600 dark:text-emerald-400 mt-0.5" />
        <div class="flex-1">
          <p class="text-sm font-semibold">{{ page.props.flash.success }}</p>
          <div v-if="page.props.flash?.import_warnings?.length" class="mt-2 text-xs space-y-1 text-emerald-700/90 dark:text-emerald-400/90">
            <p class="font-medium">Penyesuaian Data:</p>
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
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/80 dark:border-indigo-800/50">
              <Sparkles class="w-3.5 h-3.5 text-indigo-500 animate-pulse" />
              Sistem Pengadaan Sepatu SR
            </span>
            <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">Multi-Sekolah & Produksi Vendor</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
            Rekapitulasi Kebutuhan Ukuran Sepatu
          </h1>
          <p class="text-sm text-slate-600 dark:text-slate-400 mt-1 max-w-2xl">
            Import data konfirmasi Excel per sekolah, kalkulasi otomatis kebutuhan matriks ukuran sepatu (26–46), dan export laporan siap produksi untuk vendor.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <!-- Import Button -->
          <button
            @click="isUploadModalOpen = true"
            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-semibold text-sm text-white bg-gradient-to-r from-indigo-600 via-indigo-700 to-indigo-800 hover:from-indigo-500 hover:to-indigo-700 shadow-md shadow-indigo-500/20 active:scale-95 transition-all cursor-pointer"
          >
            <UploadCloud class="w-4 h-4" />
            Import Excel Baru
          </button>

          <!-- Export Excel Button -->
          <a
            :href="excelExportUrl"
            class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-xl font-medium text-sm text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 shadow-sm active:scale-95 transition-all cursor-pointer"
          >
            <FileSpreadsheet class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
            Export Excel
          </a>
        </div>
      </div>

      <!-- KPI Summary Cards -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Sepatu -->
        <div class="p-5 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-lg shadow-indigo-500/15 relative overflow-hidden">
          <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-white/10 rounded-full blur-xl pointer-events-none"></div>
          <div class="flex items-center justify-between text-indigo-100 text-xs font-semibold mb-2">
            <span>Total Kebutuhan Sepatu</span>
            <Layers class="w-4 h-4 text-indigo-200" />
          </div>
          <div class="text-3xl font-extrabold tracking-tight">
            {{ stats.total_sepatu }} <span class="text-sm font-normal text-indigo-100">Pasang</span>
          </div>
          <div class="mt-2 text-xs text-indigo-100/90 flex items-center gap-2">
            <span>{{ stats.total_pria }} Laki-laki</span>
            <span>•</span>
            <span>{{ stats.total_wanita }} Perempuan</span>
          </div>
        </div>

        <!-- Total Siswa -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800/90 shadow-sm">
          <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-xs font-semibold mb-2">
            <span>Siswa Penerima</span>
            <Users class="w-4 h-4 text-indigo-500" />
          </div>
          <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
            {{ stats.total_siswa }}
          </div>
          <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
            Kategori Siswa / Pelajar
          </div>
        </div>

        <!-- Total Guru & Tendik -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800/90 shadow-sm">
          <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-xs font-semibold mb-2">
            <span>Guru & Tendik</span>
            <UserCheck class="w-4 h-4 text-emerald-500" />
          </div>
          <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
            {{ stats.total_guru + stats.total_tendik }}
          </div>
          <div class="mt-2 text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5">
            <span class="text-emerald-600 dark:text-emerald-400 font-medium">{{ stats.total_guru }} Guru</span>
            <span>+</span>
            <span class="text-teal-600 dark:text-teal-400 font-medium">{{ stats.total_tendik }} Tendik</span>
          </div>
        </div>

        <!-- Total Sekolah -->
        <div class="p-5 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800/90 shadow-sm">
          <div class="flex items-center justify-between text-slate-500 dark:text-slate-400 text-xs font-semibold mb-2">
            <span>Sekolah Terkonfirmasi</span>
            <SchoolIcon class="w-4 h-4 text-amber-500" />
          </div>
          <div class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white">
            {{ stats.total_schools }}
          </div>
          <div class="mt-2 text-xs text-slate-500 dark:text-slate-400">
            {{ stats.total_batches }} Berkas Import Selesai
          </div>
        </div>
      </div>

      <!-- Filter & School Context Bar -->
      <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800/90 shadow-sm space-y-4">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex flex-wrap items-center gap-3">
            <!-- School Selector -->
            <div class="flex items-center gap-2">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1">
                <SchoolIcon class="w-3.5 h-3.5 text-indigo-500" />
                Sekolah:
              </label>
              <select
                v-model="selectedSchool"
                @change="applyFilters"
                class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none"
              >
                <option value="">Semua Sekolah (Agregasi Global)</option>
                <option v-for="sc in schools" :key="sc.id" :value="sc.id">
                  {{ sc.nama_sekolah }} ({{ sc.recipients_count }} pasang)
                </option>
              </select>
            </div>

            <!-- Kategori Selector -->
            <div class="flex items-center gap-2">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Kategori:</label>
              <select
                v-model="selectedKategori"
                @change="applyFilters"
                class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none"
              >
                <option value="">Semua Kategori</option>
                <option value="siswa">Siswa</option>
                <option value="guru">Guru</option>
                <option value="tendik">Tendik</option>
              </select>
            </div>

            <!-- Gender Selector -->
            <div class="flex items-center gap-2">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Gender:</label>
              <select
                v-model="selectedGender"
                @change="applyFilters"
                class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none"
              >
                <option value="">L & P</option>
                <option value="L">Laki-laki (L)</option>
                <option value="P">Perempuan (P)</option>
              </select>
            </div>

            <!-- PIC Filter Selector -->
            <div class="flex items-center gap-2">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1">
                <UserCheck class="w-3.5 h-3.5 text-indigo-500" />
                PIC:
              </label>
              <select
                v-model="selectedPic"
                @change="applyFilters"
                class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none"
              >
                <option value="">Semua PIC</option>
                <option v-for="p in picList" :key="p" :value="p">{{ p }}</option>
              </select>
            </div>
          </div>

          <!-- Search input -->
          <div class="flex items-center gap-2">
            <div class="relative flex-1 md:w-64">
              <Search class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
              <input
                v-model="search"
                @keyup.enter="applyFilters"
                type="text"
                placeholder="Cari nama, jabatan, ukuran..."
                class="w-full text-xs pl-9 pr-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none"
              />
            </div>
            <button
              @click="applyFilters"
              class="px-3 py-2 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white transition-colors cursor-pointer"
            >
              Cari
            </button>
            <button
              v-if="selectedSchool || selectedKategori || selectedGender || selectedPic || search"
              @click="resetFilters"
              class="px-2.5 py-2 rounded-xl text-xs font-medium text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 transition-colors cursor-pointer"
              title="Reset Filter"
            >
              Reset
            </button>
          </div>
        </div>

        <!-- School Info Banner if Single School is Selected -->
        <div v-if="activeSchoolObj" class="p-3.5 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/30 border border-indigo-200/60 dark:border-indigo-800/40 flex flex-wrap items-center justify-between gap-4 text-xs">
          <div class="flex flex-wrap items-center gap-y-2 gap-x-6 text-slate-700 dark:text-slate-300">
            <div class="flex items-center gap-1.5 font-bold text-indigo-700 dark:text-indigo-300">
              <SchoolIcon class="w-4 h-4" />
              {{ activeSchoolObj.nama_sekolah }}
            </div>
            <div v-if="activeSchoolObj.kota" class="flex items-center gap-1 text-slate-500 dark:text-slate-400">
              <MapPin class="w-3.5 h-3.5" />
              {{ activeSchoolObj.kota }}
            </div>
            <div v-if="activeSchoolObj.pic_nama" class="flex items-center gap-1">
              <span class="text-slate-400">PIC:</span>
              <span class="font-medium text-slate-800 dark:text-slate-200">{{ activeSchoolObj.pic_nama }}</span>
            </div>
            <div v-if="activeSchoolObj.kepala_sekolah_nama" class="flex items-center gap-1">
              <span class="text-slate-400">Kepsek:</span>
              <span class="font-medium text-slate-800 dark:text-slate-200">{{ activeSchoolObj.kepala_sekolah_nama }}</span>
              <span v-if="activeSchoolObj.kepala_sekolah_nip" class="text-slate-400">({{ activeSchoolObj.kepala_sekolah_nip }})</span>
            </div>
            <div v-if="activeSchoolObj.tanggal_konfirmasi" class="flex items-center gap-1 text-slate-500 dark:text-slate-400">
              <Calendar class="w-3.5 h-3.5" />
              {{ activeSchoolObj.tanggal_konfirmasi }}
            </div>
          </div>

          <button
            @click="deleteSchool(activeSchoolObj.id, activeSchoolObj.nama_sekolah)"
            class="text-xs text-rose-600 dark:text-rose-400 hover:text-rose-700 flex items-center gap-1 font-medium cursor-pointer"
          >
            <Trash2 class="w-3.5 h-3.5" />
            Hapus Data Sekolah Ini
          </button>
        </div>
      </div>

      <!-- Anomaly Alert Banner (Jika terdeteksi data anomali atau filter aktif) -->
      <div
        v-if="stats.total_anomalies > 0 || onlyAnomaly"
        class="p-4 rounded-2xl border shadow-sm transition-all"
        :class="onlyAnomaly ? 'bg-amber-100/90 dark:bg-amber-950/70 border-amber-300 dark:border-amber-700' : 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800/60'"
      >
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="flex items-start sm:items-center gap-3">
            <div class="p-2.5 rounded-xl bg-amber-200/80 dark:bg-amber-900/80 text-amber-800 dark:text-amber-200 shrink-0">
              <AlertTriangle class="w-5 h-5 text-amber-700 dark:text-amber-300" />
            </div>
            <div>
              <div class="flex flex-wrap items-center gap-2">
                <h4 class="text-sm font-bold text-amber-950 dark:text-amber-100">
                  Terdeteksi {{ stats.total_anomalies }} Data Anomali
                </h4>
                <span
                  v-if="stats.extreme_anomalies > 0"
                  class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300 border border-rose-300 dark:border-rose-800"
                >
                  ⚠️ {{ stats.extreme_anomalies }} Ukuran Ekstrem (&gt; 100)
                </span>
                <span
                  v-if="onlyAnomaly"
                  class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-600 text-white"
                >
                  Mode Filter Anomali Aktif
                </span>
              </div>
              <p class="text-xs text-amber-800/90 dark:text-amber-300/90 mt-0.5">
                Ditemukan ukuran sepatu di luar rentang standar (26–46) atau nilai ekstrem (&gt; 100). Anda dapat memfilter dan langsung memperbaikinya lewat tombol koreksi di tabel.
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <button
              @click="toggleAnomalyFilter"
              class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer shadow-sm active:scale-95"
              :class="onlyAnomaly ? 'bg-amber-600 hover:bg-amber-700 text-white' : 'bg-white dark:bg-slate-900 border border-amber-300 dark:border-amber-700 text-amber-800 dark:text-amber-200 hover:bg-amber-100/60 dark:hover:bg-slate-800'"
            >
              <Filter class="w-3.5 h-3.5" />
              {{ onlyAnomaly ? 'Tampilkan Seluruh Data' : 'Tampilkan Hanya Data Anomali' }}
            </button>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-800">
        <div class="flex items-center gap-2">
          <button
            @click="switchTab('matrix')"
            class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border-b-2 transition-all cursor-pointer"
            :class="activeTab === 'matrix' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
          >
            <BarChart3 class="w-4 h-4" />
            Rekap Ukuran Sepatu (Vendor Matrix)
            <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-mono">
              {{ sizeMatrix.length }} Ukuran
            </span>
          </button>

          <button
            @click="switchTab('recipients')"
            class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border-b-2 transition-all cursor-pointer"
            :class="activeTab === 'recipients' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
          >
            <Users class="w-4 h-4" />
            Daftar Rincian Penerima
            <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono">
              {{ recipients.total }} Orang
            </span>
          </button>

          <button
            @click="switchTab('batches')"
            class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border-b-2 transition-all cursor-pointer"
            :class="activeTab === 'batches' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
          >
            <Layers class="w-4 h-4" />
            Riwayat Berkas File
            <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono">
              {{ batches?.total ?? batchList.length }} Berkas
            </span>
          </button>
        </div>

        <!-- Anomaly Quick Toggle Tab Button -->
        <button
          @click="toggleAnomalyFilter"
          class="flex items-center gap-1.5 px-3 py-1.5 mb-1 rounded-xl text-xs font-semibold transition-all cursor-pointer"
          :class="onlyAnomaly ? 'bg-amber-600 text-white shadow-sm' : (stats.total_anomalies > 0 ? 'bg-amber-50 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-800 hover:bg-amber-100' : 'bg-slate-50 dark:bg-slate-800/80 text-slate-500 hover:text-slate-700 dark:hover:text-slate-300')"
          title="Filter cepat data anomali seperti ukuran sepatu > 100"
        >
          <AlertTriangle class="w-3.5 h-3.5" :class="stats.total_anomalies > 0 ? 'text-amber-500 animate-pulse' : 'text-slate-400'" />
          <span>Anomali:</span>
          <span class="font-mono font-bold">{{ stats.total_anomalies || 0 }}</span>
        </button>
      </div>

      <!-- TAB 1: REKAPITULASI UKURAN SEPATU (VENDOR MATRIX) -->
      <div v-if="activeTab === 'matrix'" class="space-y-6">
        <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
          <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th class="py-3.5 px-4 text-center">Ukuran Sepatu</th>
                <th class="py-3.5 px-4 text-center">Distribusi Kebutuhan</th>
                <th class="py-3.5 px-4 text-center">Siswa (L / P)</th>
                <th class="py-3.5 px-4 text-center">Guru (L / P)</th>
                <th class="py-3.5 px-4 text-center">Tendik (L / P)</th>
                <th class="py-3.5 px-4 text-center">Total Pria</th>
                <th class="py-3.5 px-4 text-center">Total Wanita</th>
                <th class="py-3.5 px-4 text-center bg-indigo-50/50 dark:bg-indigo-950/20 text-indigo-700 dark:text-indigo-300">Total Pasang</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-mono text-xs">
              <tr
                v-for="item in sizeMatrix"
                :key="item.ukuran_sepatu"
                class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors"
              >
                <!-- Size Badge -->
                <td class="py-3 px-4 text-center">
                  <span class="inline-flex items-center justify-center w-10 h-8 rounded-lg font-bold text-sm bg-slate-100 dark:bg-slate-800 text-slate-800 dark:text-slate-200 border border-slate-200 dark:border-slate-700">
                    {{ item.ukuran_sepatu }}
                  </span>
                </td>

                <!-- Visual Distribution Bar -->
                <td class="py-3 px-4 min-w-[140px]">
                  <div class="flex items-center gap-2">
                    <div class="flex-1 bg-slate-100 dark:bg-slate-800 rounded-full h-2.5 overflow-hidden">
                      <div
                        class="bg-gradient-to-r from-indigo-500 to-purple-600 h-2.5 rounded-full transition-all duration-500"
                        :style="{ width: `${(Number(item.total) / maxMatrixTotal) * 100}%` }"
                      ></div>
                    </div>
                    <span class="text-[11px] text-slate-400 w-8 text-right font-medium">
                      {{ Math.round((Number(item.total) / stats.total_sepatu) * 100) }}%
                    </span>
                  </div>
                </td>

                <!-- Siswa L/P -->
                <td class="py-3 px-4 text-center">
                  <span class="text-blue-600 dark:text-blue-400 font-semibold">{{ item.siswa_l }}</span>
                  <span class="text-slate-300 dark:text-slate-600 mx-1">/</span>
                  <span class="text-pink-600 dark:text-pink-400 font-semibold">{{ item.siswa_p }}</span>
                </td>

                <!-- Guru L/P -->
                <td class="py-3 px-4 text-center">
                  <span class="text-blue-600 dark:text-blue-400 font-semibold">{{ item.guru_l }}</span>
                  <span class="text-slate-300 dark:text-slate-600 mx-1">/</span>
                  <span class="text-pink-600 dark:text-pink-400 font-semibold">{{ item.guru_p }}</span>
                </td>

                <!-- Tendik L/P -->
                <td class="py-3 px-4 text-center">
                  <span class="text-blue-600 dark:text-blue-400 font-semibold">{{ item.tendik_l }}</span>
                  <span class="text-slate-300 dark:text-slate-600 mx-1">/</span>
                  <span class="text-pink-600 dark:text-pink-400 font-semibold">{{ item.tendik_p }}</span>
                </td>

                <!-- Total Pria -->
                <td class="py-3 px-4 text-center text-blue-700 dark:text-blue-300 font-semibold">
                  {{ item.total_pria }}
                </td>

                <!-- Total Wanita -->
                <td class="py-3 px-4 text-center text-pink-700 dark:text-pink-300 font-semibold">
                  {{ item.total_wanita }}
                </td>

                <!-- Total Pasang -->
                <td class="py-3 px-4 text-center font-bold text-base text-indigo-600 dark:text-indigo-400 bg-indigo-50/30 dark:bg-indigo-950/10">
                  {{ item.total }}
                </td>
              </tr>

              <tr v-if="sizeMatrix.length === 0">
                <td colspan="8" class="py-8 text-center text-slate-500 font-sans">
                  Belum ada data ukuran sepatu yang di-import.
                </td>
              </tr>
            </tbody>

            <!-- Table Footer Total -->
            <tfoot v-if="sizeMatrix.length > 0" class="bg-slate-100/80 dark:bg-slate-800/90 font-mono text-xs font-bold border-t-2 border-slate-300 dark:border-slate-700">
              <tr>
                <td class="py-3.5 px-4 text-center uppercase tracking-wider font-sans">TOTAL</td>
                <td class="py-3.5 px-4 text-center font-sans text-slate-500">{{ sizeMatrix.length }} Variasi Ukuran</td>
                <td class="py-3.5 px-4 text-center text-indigo-700 dark:text-indigo-300">{{ stats.total_siswa }}</td>
                <td class="py-3.5 px-4 text-center text-indigo-700 dark:text-indigo-300">{{ stats.total_guru }}</td>
                <td class="py-3.5 px-4 text-center text-indigo-700 dark:text-indigo-300">{{ stats.total_tendik }}</td>
                <td class="py-3.5 px-4 text-center text-blue-700 dark:text-blue-300">{{ stats.total_pria }}</td>
                <td class="py-3.5 px-4 text-center text-pink-700 dark:text-pink-300">{{ stats.total_wanita }}</td>
                <td class="py-3.5 px-4 text-center text-lg text-indigo-600 dark:text-indigo-400 bg-indigo-100/50 dark:bg-indigo-950/40">
                  {{ stats.total_sepatu }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <!-- TAB 2: DAFTAR RINCIAN PENERIMA SEPATU -->
      <div v-if="activeTab === 'recipients'" class="space-y-4">
        <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
          <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th class="py-3.5 px-4 text-center w-12">No</th>
                <th class="py-3.5 px-4">Nama Lengkap</th>
                <th class="py-3.5 px-4">Sekolah</th>
                <th class="py-3.5 px-4 text-center">Gender</th>
                <th class="py-3.5 px-4">Jabatan / Kategori</th>
                <th class="py-3.5 px-4 text-center">Panjang Kaki</th>
                <th class="py-3.5 px-4 text-center">Ukuran Sepatu</th>
                <th class="py-3.5 px-4 text-center w-24">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
              <tr
                v-for="(rec, idx) in recipients.data"
                :key="rec.id"
                class="transition-colors"
                :class="rec.is_anomaly ? 'bg-amber-50/70 dark:bg-amber-950/30 hover:bg-amber-100/60 dark:hover:bg-amber-900/40' : 'hover:bg-slate-50/60 dark:hover:bg-slate-800/40'"
              >
                <td class="py-3 px-4 text-center font-mono text-slate-400">
                  {{ (recipients.current_page - 1) * recipients.per_page + idx + 1 }}
                </td>
                <td class="py-3 px-4 font-semibold text-slate-900 dark:text-white">
                  {{ rec.nama_lengkap }}
                </td>
                <td class="py-3 px-4 text-slate-600 dark:text-slate-400">
                  <div class="font-medium text-slate-900 dark:text-slate-100">{{ rec.school?.nama_sekolah || '-' }}</div>
                  <div v-if="rec.school?.pic_nama" class="text-[11px] text-slate-400 flex items-center gap-1 mt-0.5">
                    <UserCheck class="w-3 h-3 text-indigo-500" /> PIC: {{ rec.school.pic_nama }}
                  </div>
                </td>
                <td class="py-3 px-4 text-center">
                  <span
                    class="px-2 py-0.5 rounded-md font-bold text-[11px]"
                    :class="rec.jenis_kelamin === 'L' ? 'bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300' : 'bg-pink-50 dark:bg-pink-950/60 text-pink-700 dark:text-pink-300'"
                  >
                    {{ rec.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan' }}
                  </span>
                </td>
                <td class="py-3 px-4">
                  <span
                    class="px-2 py-0.5 rounded-full text-[11px] font-medium"
                    :class="{
                      'bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300': rec.kategori === 'siswa',
                      'bg-emerald-50 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300': rec.kategori === 'guru',
                      'bg-teal-50 dark:bg-teal-950 text-teal-700 dark:text-teal-300': rec.kategori === 'tendik',
                    }"
                  >
                    {{ rec.jabatan }}
                  </span>
                </td>
                <td class="py-3 px-4 text-center font-mono text-slate-600 dark:text-slate-400">
                  {{ rec.panjang_kaki ? `${rec.panjang_kaki} cm` : '-' }}
                </td>
                <td class="py-3 px-4 text-center">
                  <span
                    class="inline-flex items-center justify-center px-2.5 py-1 rounded-lg font-bold font-mono text-xs border"
                    :class="rec.is_anomaly
                      ? (rec.ukuran_sepatu > 100 
                          ? 'bg-rose-100 dark:bg-rose-950/80 text-rose-700 dark:text-rose-300 border-rose-300 dark:border-rose-700 animate-pulse'
                          : 'bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 border-amber-300 dark:border-amber-700')
                      : 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border-indigo-200/80 dark:border-indigo-800/50'"
                  >
                    Size {{ rec.ukuran_sepatu }}
                  </span>
                  <div v-if="rec.is_anomaly" class="mt-1 flex items-center justify-center">
                    <span
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold shadow-xs cursor-help"
                      :class="rec.ukuran_sepatu > 100 ? 'bg-rose-600 text-white' : 'bg-amber-500 text-white'"
                      :title="rec.anomaly_reason"
                    >
                      <AlertTriangle class="w-2.5 h-2.5" />
                      {{ rec.ukuran_sepatu > 100 ? 'Ekstrem > 100' : 'Anomali' }}
                    </span>
                  </div>
                </td>
                <td class="py-3 px-4 text-center">
                  <button
                    @click="openEditRecipientModal(rec)"
                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer"
                    :class="rec.is_anomaly 
                      ? 'bg-amber-600 hover:bg-amber-700 text-white shadow-sm ring-2 ring-amber-400/50' 
                      : 'text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 border border-transparent hover:border-indigo-200 dark:hover:border-indigo-800'"
                    :title="rec.is_anomaly ? `Perbaiki Anomali: ${rec.anomaly_reason}` : 'Koreksi Data Penerima'"
                  >
                    <Edit2 class="w-3.5 h-3.5" />
                    <span>{{ rec.is_anomaly ? 'Koreksi' : 'Edit' }}</span>
                  </button>
                </td>
              </tr>

              <tr v-if="recipients.data.length === 0">
                <td colspan="8" class="py-8 text-center text-slate-500">
                  <div class="flex flex-col items-center justify-center gap-1.5">
                    <span v-if="onlyAnomaly" class="font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                      <CheckCircle2 class="w-4 h-4" />
                      Tidak ditemukan data anomali pada kriteria filter saat ini!
                    </span>
                    <span v-else>
                      Tidak ada data penerima yang cocok dengan kriteria pencarian.
                    </span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Controls -->
        <div v-if="recipients.total > recipients.per_page" class="flex items-center justify-between px-2 pt-2 text-xs">
          <div class="text-slate-500">
            Menampilkan {{ recipients.from }} - {{ recipients.to }} dari {{ recipients.total }} penerima
          </div>
          <div class="flex items-center gap-1">
            <template v-for="(link, lIdx) in recipients.links" :key="lIdx">
              <button
                v-if="link.url"
                type="button"
                @click="goToPage(link.url)"
                class="px-3 py-1.5 rounded-lg border font-medium transition-colors cursor-pointer"
                :class="link.active ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                v-html="link.label"
              ></button>
              <span v-else class="px-2 py-1.5 text-slate-400" v-html="link.label"></span>
            </template>
          </div>
        </div>
      </div>

      <!-- TAB 3: RIWAYAT BERKAS FILE IMPORT -->
      <div v-if="activeTab === 'batches'" class="space-y-6">
        <div>
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-3">
            <div>
              <h2 class="text-base font-bold text-slate-900 dark:text-white">
                Riwayat Berkas File yang Di-Import
              </h2>
              <p class="text-xs text-slate-500">Berkas Excel asli tersimpan aman di server storage dan siap disinkronkan kapan saja.</p>
            </div>
            <button
              @click="syncAllFromStorage"
              :disabled="isSyncing"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 transition-colors cursor-pointer disabled:opacity-50"
              title="Baca ulang semua berkas Excel di storage dan perbarui data jika ada perubahan logika/fitur"
            >
              <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isSyncing }" />
              {{ isSyncing ? 'Menyinkronkan...' : 'Sinkronkan Ulang dari Storage' }}
            </button>
          </div>
          <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
            <table class="w-full text-left text-sm">
              <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800">
                <tr>
                  <th class="py-3 px-4 w-52 max-w-[200px]">Nama File</th>
                  <th class="py-3 px-4 min-w-[180px]">Sekolah</th>
                  <th class="py-3 px-4 whitespace-nowrap">PIC Ukur</th>
                  <th class="py-3 px-4 text-center whitespace-nowrap">Total Sepatu</th>
                  <th class="py-3 px-4 text-center whitespace-nowrap">Rincian (S / G / T)</th>
                  <th class="py-3 px-4 whitespace-nowrap">Waktu Import</th>
                  <th class="py-3 px-4 text-center w-20 whitespace-nowrap">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                <tr v-for="b in batchList" :key="b.id">
                  <td class="py-3 px-4 max-w-[200px]" :title="b.file_name">
                    <div class="flex items-center gap-1.5 min-w-0">
                      <FileSpreadsheet class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                      <span class="font-mono text-xs font-medium text-slate-800 dark:text-slate-200 truncate">
                        {{ b.file_name }}
                      </span>
                    </div>
                  </td>
                  <td class="py-3 px-4 font-medium text-slate-900 dark:text-slate-100">{{ b.school?.nama_sekolah || '-' }}</td>
                  <td class="py-3 px-4 whitespace-nowrap">
                    <span
                      v-if="b.school?.pic_nama"
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60"
                    >
                      <UserCheck class="w-3 h-3 text-indigo-500 dark:text-indigo-400" />
                      {{ b.school.pic_nama }}
                    </span>
                    <span v-else class="text-slate-400 italic text-xs">Belum diatur</span>
                  </td>
                  <td class="py-3 px-4 text-center font-bold text-indigo-600 dark:text-indigo-400 font-mono whitespace-nowrap">
                    {{ b.total_sepatu }}
                  </td>
                  <td class="py-3 px-4 text-center font-mono text-slate-500 whitespace-nowrap">
                    {{ b.total_siswa }} / {{ b.total_guru }} / {{ b.total_tendik }}
                  </td>
                  <td class="py-3 px-4 text-slate-500 whitespace-nowrap">
                    {{ new Date(b.created_at).toLocaleString('id-ID') }}
                  </td>
                  <td class="py-3 px-4 text-center whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1.5">
                      <a
                        :href="getUrl(`/shoes/batches/${b.id}/download`)"
                        class="p-1.5 rounded-lg text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 transition-colors"
                        title="Unduh Berkas Excel Asli"
                      >
                        <Download class="w-4 h-4" />
                      </a>
                      <button
                        @click="deleteBatch(b.id, b.school?.nama_sekolah)"
                        class="text-rose-500 hover:text-rose-700 p-1.5 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/50 transition-colors cursor-pointer"
                        title="Hapus Batch Ini"
                      >
                        <Trash2 class="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="batchList.length === 0">
                  <td colspan="7" class="py-8 text-center text-slate-500">
                    Belum ada berkas Excel yang di-import.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Riwayat Berkas (10 per halaman) -->
          <div
            v-if="batches?.total && batches.total > batches.per_page"
            class="flex flex-col sm:flex-row items-center justify-between gap-3 px-2 pt-3 text-xs"
          >
            <div class="text-slate-500">
              Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-200">{{ batches.from }}</span> - <span class="font-semibold text-slate-700 dark:text-slate-200">{{ batches.to }}</span> dari <span class="font-semibold text-slate-700 dark:text-slate-200">{{ batches.total }}</span> berkas import
            </div>
            <div class="flex items-center gap-1 flex-wrap justify-center">
              <template v-for="(link, lIdx) in batches.links" :key="lIdx">
                <button
                  v-if="link.url"
                  type="button"
                  @click="goToBatchPage(link.url)"
                  class="px-3 py-1.5 rounded-lg border font-medium transition-colors cursor-pointer"
                  :class="link.active ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                  v-html="link.label"
                ></button>
                <span v-else class="px-2 py-1.5 text-slate-400" v-html="link.label"></span>
              </template>
            </div>
          </div>
        </div>
      </div>

    </main>

    <!-- MODAL: UPLOAD EXCEL FILE -->
    <div
      v-if="isUploadModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-xl w-full p-6 shadow-2xl relative space-y-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
              <FileUp class="w-5 h-5" />
            </div>
            <div>
              <h3 class="font-bold text-base text-slate-900 dark:text-white">Import File Excel Sepatu SR</h3>
              <p class="text-xs text-slate-500">Mendukung multi-sheet otomatis (Siswa, Guru, Tendik)</p>
            </div>
          </div>
          <button
            @click="isUploadModalOpen = false"
            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 cursor-pointer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- PIC Selection Dropdown Section -->
        <div class="p-4 rounded-2xl bg-indigo-50/60 dark:bg-indigo-950/30 border border-indigo-200/80 dark:border-indigo-800/50 space-y-2">
          <div class="flex items-center justify-between">
            <label class="text-xs font-bold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
              <UserCheck class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
              <span>Penanggung Jawab / Nama PIC:</span>
              <span class="text-rose-500">*</span>
            </label>
            <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900/60">
              Folder PIC
            </span>
          </div>

          <div class="relative">
            <select
              v-model="selectedPicOption"
              class="w-full px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-xs cursor-pointer"
            >
              <option value="" disabled>-- Silakan Pilih Nama PIC Pengumpul Berkas --</option>
              <optgroup label="Daftar Nama PIC Resmi Kemensos / Sekolah Rakyat">
                <option v-for="pic in picList" :key="pic" :value="pic">
                  👤 {{ pic }}
                </option>
              </optgroup>
              <optgroup label="Opsi Khusus">
                <option value="__custom__">✏️ Tulis Nama PIC Manual...</option>
                <option value="__auto__">📄 Ambil Otomatis dari Lembar Konfirmasi Excel</option>
              </optgroup>
            </select>
          </div>

          <!-- Input Manual jika memilih Custom -->
          <div v-if="selectedPicOption === '__custom__'" class="pt-1">
            <input
              v-model="customPicName"
              type="text"
              placeholder="Ketikkan nama PIC lengkap (contoh: YULIA)..."
              class="w-full px-3.5 py-2 rounded-xl text-xs bg-white dark:bg-slate-800 border border-indigo-400 dark:border-indigo-600 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 placeholder:text-slate-400"
            />
          </div>

          <p class="text-[11px] text-slate-500 dark:text-slate-400">
            Setiap data sekolah yang di-upload pada proses ini akan secara otomatis ditugaskan ke PIC yang dipilih.
          </p>
        </div>

        <!-- Drag & Drop Zone / File Picker -->
        <div
          @dragover.prevent="dragOver = true"
          @dragleave.prevent="dragOver = false"
          @drop.prevent="handleDrop"
          class="border-2 border-dashed rounded-2xl p-6 text-center transition-all cursor-pointer"
          :class="dragOver ? 'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/20' : 'border-slate-300 dark:border-slate-700 hover:border-indigo-400 bg-slate-50/50 dark:bg-slate-800/40'"
          @click="$refs.fileInput.click()"
        >
          <input
            ref="fileInput"
            type="file"
            multiple
            accept=".xlsx,.xls"
            class="hidden"
            @change="handleFileSelect"
          />

          <div class="w-12 h-12 mx-auto rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-3">
            <UploadCloud class="w-6 h-6" />
          </div>

          <p class="text-sm font-semibold text-slate-800 dark:text-slate-200">
            Klik untuk memilih berkas atau seret ke sini
          </p>
          <p class="text-xs text-slate-500 mt-1">
            Mendukung <strong>multi-file import</strong> sekaligus (Pilih satu atau banyak berkas .xlsx / .xls)
          </p>
        </div>

        <!-- Selected Files List -->
        <div v-if="selectedFiles.length > 0" class="space-y-2 max-h-48 overflow-y-auto pr-1">
          <div class="flex items-center justify-between text-xs font-semibold text-slate-600 dark:text-slate-300">
            <span>{{ selectedFiles.length }} Berkas Dipilih:</span>
            <button
              type="button"
              @click="$refs.fileInput.click()"
              class="text-indigo-600 dark:text-indigo-400 hover:underline cursor-pointer"
            >
              + Tambah Berkas Lain
            </button>
          </div>

          <div
            v-for="(f, fIdx) in selectedFiles"
            :key="fIdx"
            class="flex items-center justify-between p-2.5 rounded-xl bg-slate-100/80 dark:bg-slate-800/80 border border-slate-200 dark:border-slate-700 text-xs"
          >
            <div class="flex items-center gap-2 min-w-0">
              <FileSpreadsheet class="w-4 h-4 text-emerald-600 dark:text-emerald-400 flex-shrink-0" />
              <div class="truncate">
                <p class="font-semibold text-slate-800 dark:text-slate-200 truncate">{{ f.name }}</p>
                <p class="text-[10px] text-slate-400">{{ (f.size / 1024).toFixed(1) }} KB</p>
              </div>
            </div>
            <button
              type="button"
              @click.stop="removeFile(fIdx)"
              class="text-slate-400 hover:text-rose-500 p-1 cursor-pointer transition-colors"
              title="Hapus berkas ini"
            >
              <X class="w-4 h-4" />
            </button>
          </div>
        </div>

        <!-- Info note -->
        <div class="p-3.5 rounded-xl bg-slate-100 dark:bg-slate-800/80 text-xs text-slate-600 dark:text-slate-400 space-y-1">
          <div class="font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
            <Info class="w-4 h-4 text-indigo-500" />
            Fitur Pemrosesan Multi-Berkas Otomatis:
          </div>
          <ul class="list-disc list-inside space-y-0.5 text-[11px] text-slate-500 dark:text-slate-400 pl-1">
            <li>Dapat meng-upload banyak berkas Excel dari berbagai sekolah dalam satu proses.</li>
            <li>Mendeteksi otomatis lembar kerja Siswa, Guru Laki-laki, dan Guru Perempuan.</li>
            <li>Mengekstrak otomatis Nama Sekolah, Kota, PIC Pengukur, Kepala Sekolah & NIP.</li>
            <li>Normalisasi cerdas jika panjang kaki terisi milimeter (contoh: 300 mm dikonversi menjadi 30 cm).</li>
          </ul>
        </div>

        <!-- Validation / Upload Errors -->
        <div v-if="Object.keys(uploadForm.errors).length > 0" class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200 dark:border-rose-800 text-xs text-rose-600 dark:text-rose-300 space-y-1">
          <div class="font-bold flex items-center gap-1.5">
            <AlertCircle class="w-4 h-4 text-rose-500 shrink-0" />
            Kendala Upload Berkas:
          </div>
          <ul class="list-disc list-inside space-y-0.5 pl-1">
            <li v-for="(err, key) in uploadForm.errors" :key="key">{{ err }}</li>
          </ul>
        </div>

        <!-- Modal Actions -->
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
            class="px-5 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-md shadow-indigo-500/20 transition-all cursor-pointer"
          >
            <span v-if="uploadForm.processing">Sedang Mengolah {{ selectedFiles.length }} Berkas...</span>
            <span v-else>Mulai Import {{ selectedFiles.length > 0 ? `(${selectedFiles.length} Berkas)` : '' }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- MODAL: UBAH PIC & DATA SEKOLAH -->
    <div
      v-if="isEditSchoolModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
      @click.self="isEditSchoolModalOpen = false"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-md w-full p-6 shadow-2xl relative space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-xl bg-indigo-100 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
              <Edit2 class="w-4 h-4" />
            </div>
            <div>
              <h3 class="font-bold text-sm text-slate-900 dark:text-white">Ubah Penanggung Jawab / PIC</h3>
              <p class="text-xs text-slate-500 truncate max-w-[240px]">{{ editingSchool?.nama_sekolah }}</p>
            </div>
          </div>
          <button
            @click="isEditSchoolModalOpen = false"
            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 cursor-pointer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitEditSchool" class="space-y-3.5 text-xs">
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Pilih Nama PIC:
            </label>
            <select
              v-model="editSchoolForm.pic_nama"
              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            >
              <option value="">-- Tanpa PIC / Kosongkan --</option>
              <option v-for="p in picList" :key="p" :value="p">{{ p }}</option>
            </select>
          </div>

          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Kota / Wilayah:
            </label>
            <input
              v-model="editSchoolForm.kota"
              type="text"
              placeholder="Contoh: Cimahi"
              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Nama Kepala Sekolah:
            </label>
            <input
              v-model="editSchoolForm.kepala_sekolah_nama"
              type="text"
              placeholder="Nama Kepala Sekolah..."
              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
              NIP Kepala Sekolah:
            </label>
            <input
              v-model="editSchoolForm.kepala_sekolah_nip"
              type="text"
              placeholder="NIP..."
              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            <button
              type="button"
              @click="isEditSchoolModalOpen = false"
              class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="editSchoolForm.processing"
              class="px-4 py-2 rounded-xl font-semibold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-all cursor-pointer shadow-sm"
            >
              Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: KOREKSI DATA PENERIMA & UKURAN SEPATU -->
    <div
      v-if="isEditRecipientModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
      @click.self="isEditRecipientModalOpen = false"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl relative space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center gap-2">
            <div class="p-2 rounded-xl" :class="editingRecipient?.is_anomaly ? 'bg-amber-100 dark:bg-amber-950 text-amber-600 dark:text-amber-400' : 'bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400'">
              <AlertTriangle v-if="editingRecipient?.is_anomaly" class="w-5 h-5 text-amber-500" />
              <Edit2 v-else class="w-5 h-5 text-indigo-500" />
            </div>
            <div>
              <h3 class="font-bold text-base text-slate-900 dark:text-white">
                {{ editingRecipient?.is_anomaly ? 'Koreksi Data Anomali Penerima' : 'Ubah Data Penerima' }}
              </h3>
              <p class="text-xs text-slate-500">{{ editingRecipient?.school?.nama_sekolah || '-' }}</p>
            </div>
          </div>
          <button
            @click="isEditRecipientModalOpen = false"
            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg cursor-pointer"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <!-- Anomaly Reason Callout -->
        <div
          v-if="editingRecipient?.is_anomaly"
          class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2"
        >
          <AlertTriangle class="w-4 h-4 shrink-0 mt-0.5 text-amber-600 dark:text-amber-400" />
          <div>
            <span class="font-bold">Indikasi Anomali:</span>
            <span class="ml-1">{{ editingRecipient.anomaly_reason }}</span>
          </div>
        </div>

        <form @submit.prevent="submitEditRecipient" class="space-y-3.5 text-xs">
          <!-- Nama Lengkap -->
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Nama Lengkap Penerima:
            </label>
            <input
              v-model="editRecipientForm.nama_lengkap"
              type="text"
              required
              placeholder="Nama lengkap..."
              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <!-- Gender -->
            <div>
              <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                Jenis Kelamin:
              </label>
              <select
                v-model="editRecipientForm.jenis_kelamin"
                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="L">Laki-laki (L)</option>
                <option value="P">Perempuan (P)</option>
              </select>
            </div>

            <!-- Kategori -->
            <div>
              <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                Kategori:
              </label>
              <select
                v-model="editRecipientForm.kategori"
                class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
              >
                <option value="siswa">Siswa</option>
                <option value="guru">Guru</option>
                <option value="tendik">Tendik</option>
              </select>
            </div>
          </div>

          <!-- Jabatan -->
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Jabatan / Kelas:
            </label>
            <input
              v-model="editRecipientForm.jabatan"
              type="text"
              placeholder="Contoh: Siswa Kelas 7 / Guru Matematika..."
              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <!-- Ukuran Sepatu & Panjang Kaki -->
          <div class="p-3.5 rounded-2xl bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/50 space-y-2.5">
            <div class="flex items-center justify-between">
              <span class="font-bold text-slate-800 dark:text-slate-200">Koreksi Ukuran & Panjang Kaki</span>
              <button
                type="button"
                @click="swapLengthAndSize"
                class="inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 hover:underline cursor-pointer"
                title="Tukar isi Panjang Kaki dengan Ukuran Sepatu jika terbalik"
              >
                <ArrowUpDown class="w-3.5 h-3.5" />
                Tukar Nilai (Swap)
              </button>
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Ukuran Sepatu (Standar 26 - 46):
                </label>
                <input
                  v-model.number="editRecipientForm.ukuran_sepatu"
                  type="number"
                  min="15"
                  max="150"
                  required
                  placeholder="Contoh: 38"
                  class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border font-bold font-mono text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                  :class="(editRecipientForm.ukuran_sepatu > 46 || editRecipientForm.ukuran_sepatu < 26) ? 'border-rose-400 text-rose-600' : 'border-slate-200 dark:border-slate-700'"
                />
                <span v-if="editRecipientForm.ukuran_sepatu < 26 || editRecipientForm.ukuran_sepatu > 46" class="text-[10px] text-amber-600 dark:text-amber-400 font-semibold mt-1 block">
                  ⚠️ Di luar rentang standar (26 s/d 46).
                </span>
                <span v-if="editRecipientForm.ukuran_sepatu > 100" class="text-[10px] text-rose-600 dark:text-rose-400 font-semibold mt-1 block">
                  ⚠️ Nilai &gt; 100 tergolong ekstrem.
                </span>
              </div>

              <div>
                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                  Panjang Kaki (cm):
                </label>
                <input
                  v-model="editRecipientForm.panjang_kaki"
                  type="number"
                  step="0.1"
                  min="0"
                  max="100"
                  placeholder="Contoh: 24.5"
                  class="w-full px-3 py-2 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 font-mono text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                />
              </div>
            </div>
          </div>

          <!-- Catatan -->
          <div>
            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
              Catatan Khusus (Opsional):
            </label>
            <input
              v-model="editRecipientForm.catatan"
              type="text"
              placeholder="Catatan..."
              class="w-full px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
            />
          </div>

          <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100 dark:border-slate-800">
            <button
              type="button"
              @click="isEditRecipientModalOpen = false"
              class="px-4 py-2 rounded-xl text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="editRecipientForm.processing"
              class="px-4 py-2 rounded-xl font-semibold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 transition-all cursor-pointer shadow-sm"
            >
              Simpan Koreksi
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>
