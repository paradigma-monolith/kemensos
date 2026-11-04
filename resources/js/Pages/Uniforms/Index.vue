<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import HybridNavbar from '../../Components/HybridNavbar.vue';
import {
  Shirt,
  UploadCloud,
  Download,
  Copy,
  Check,
  FileSpreadsheet,
  Search,
  Filter,
  School as SchoolIcon,
  Users,
  CheckCircle2,
  AlertCircle,
  FileCode,
  Sparkles,
  Info,
  X,
  Footprints,
  UserCheck,
  Layers,
  RefreshCw,
  Trash2,
  Eye,
  FileUp,
  Calendar,
  MapPin,
  Briefcase
} from 'lucide-vue-next';

const props = defineProps({
  initialRecap: {
    type: Object,
    default: () => ({
      school_name: null,
      file_name: null,
      pic_nama: null,
      header_rows: [1, 2],
      data_start_row: 3,
      total_rows: 0,
      sheet_counts: {},
      recapitulation: {},
    }),
  },
  schema: {
    type: Object,
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
  schools: {
    type: Array,
    default: () => [],
  },
  batches: {
    type: [Object, Array],
    default: () => ({}),
  },
  stats: {
    type: Object,
    default: () => ({
      total_penerima: 0,
      total_siswa: 0,
      total_guru_tendik: 0,
      total_staff_khusus: 0,
      total_schools: 0,
      total_batches: 0,
      sheet_stats: {
        srd: { total: 0, pria: 0, wanita: 0 },
        srmp: { total: 0, pria: 0, wanita: 0 },
        srma: { total: 0, pria: 0, wanita: 0 },
      },
      staff_stats: {},
    }),
  },
  filters: {
    type: Object,
    default: () => ({
      school_id: null,
      pic: '',
      jenjang: '',
      kategori: '',
      gender: '',
      search: '',
      tab: 'recap',
    }),
  },
});

const page = usePage();

// Intelligent active primary tab detection ('recap' | 'batches')
const getInitialPrimaryTab = () => {
  if (typeof window !== 'undefined') {
    const params = new URLSearchParams(window.location.search);
    const urlTab = params.get('tab');
    if (urlTab && ['recap', 'batches'].includes(urlTab)) {
      return urlTab;
    }
    if (params.has('batch_page')) {
      return 'batches';
    }
  }
  if (props.filters?.tab && ['recap', 'batches'].includes(props.filters.tab)) {
    return props.filters.tab;
  }
  return 'recap';
};

const primaryTab = ref(getInitialPrimaryTab());

const switchPrimaryTab = (tab) => {
  primaryTab.value = tab;
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

// Batch list computation
const batchList = computed(() => {
  if (Array.isArray(props.batches)) return props.batches;
  return props.batches?.data || [];
});

const totalBatchesCount = computed(() => {
  return props.batches?.total ?? batchList.value.length;
});

// Master Filter States (Syncable with Backend)
const selectedSchool = ref(props.filters.school_id ? Number(props.filters.school_id) : '');
const selectedJenjang = ref(props.filters.jenjang || '');
const selectedKategori = ref(props.filters.kategori || '');
const selectedGender = ref(props.filters.gender || '');
const selectedPic = ref(props.filters.pic || '');
const search = ref(props.filters.search || '');

// Sheet sub-tab in recap mode
const activeTab = ref(props.filters.kategori ? props.filters.kategori : 'SISWA');
const isUploadModalOpen = ref(false);
const isSyncing = ref(false);
const isCopied = ref(false);
const fileInput = ref(null);
const selectedFiles = ref([]);

// PIC Selection state for modal
const selectedPicOption = ref('');
const customPicName = ref('');

// Pagination state: Maximum 15 rows per page for data table
const perPage = ref(15);
const currentPage = ref(1);

// Active school object for information banner
const activeSchoolObj = computed(() => {
  if (!selectedSchool.value) return null;
  return props.schools.find((s) => s.id === Number(selectedSchool.value)) || null;
});

// Form for Inertia file upload
const uploadForm = useForm({
  excel_files: [],
  pic_nama: '',
});

const sheetsList = [
  { key: 'SISWA', label: 'Siswa', icon: Users, note: 'SD / SMP / SMA' },
  { key: 'KEPSEK', label: 'Kepsek', icon: SchoolIcon, note: 'Kepala Sekolah' },
  { key: 'GURU LAMA', label: 'Guru Lama', icon: Users, note: 'ASN / Outsourcing' },
  { key: 'GURU BARU', label: 'Guru Baru', icon: Users, note: 'Sheet GURU masuk ke sini' },
  { key: 'TENDIK LAMA', label: 'Tendik Lama', icon: Users, note: 'Tenaga Kependidikan' },
  { key: 'TENDIK BARU', label: 'Tendik Baru', icon: Users, note: 'Sheet TENDIK masuk ke sini' },
  { key: 'SATPAM', label: 'Satpam', icon: Footprints, note: 'Termasuk Sepatu PDH' },
  { key: 'PETUGAS KEBERSIHAN', label: 'Kebersihan', icon: Users, note: 'Cleaning Service' },
  { key: 'PETUGAS DAPUR', label: 'Dapur', icon: Users, note: 'Petugas Masak' },
];

// Current recapitulation data
const currentRecap = computed(() => props.initialRecap?.recapitulation || {});
const schoolName = computed(() => props.initialRecap?.school_name || 'Belum Ada Berkas Diimpor');
const fileName = computed(() => props.initialRecap?.file_name || '-');
const picNama = computed(() => props.initialRecap?.pic_nama || null);
const totalRows = computed(() => props.initialRecap?.total_rows || 0);

// Sheet counts
const getSheetCount = (sheetKey) => {
  return currentRecap.value[sheetKey]?.data?.length || 0;
};

// Gender breakdown counts for current sheet in view
const getSheetGenderCounts = (sheetKey) => {
  const data = currentRecap.value[sheetKey]?.data || [];
  let pria = 0;
  let wanita = 0;
  for (const r of data) {
    if (r.jenis_kelamin === 'L') pria++;
    else if (r.jenis_kelamin === 'P') wanita++;
  }
  return { pria, wanita };
};

// Detailed Staff Card List Separated Sesuai Sheet
const staffCardList = computed(() => {
  const staff = props.stats?.staff_stats || {};
  return [
    {
      key: 'kepsek',
      sheet: 'KEPSEK',
      label: 'Kepala Sekolah',
      badge: 'KEPSEK',
      sublabel: 'Sheet *KEPSEK*',
      color: 'amber',
      icon: SchoolIcon,
      total: staff.kepsek?.total ?? getSheetCount('KEPSEK'),
      pria: staff.kepsek?.pria ?? getSheetGenderCounts('KEPSEK').pria,
      wanita: staff.kepsek?.wanita ?? getSheetGenderCounts('KEPSEK').wanita,
    },
    {
      key: 'guru_lama',
      sheet: 'GURU LAMA',
      label: 'Guru Lama',
      badge: 'GURU LAMA',
      sublabel: 'Sheet *GURU LAMA*',
      color: 'sky',
      icon: Users,
      total: staff.guru_lama?.total ?? getSheetCount('GURU LAMA'),
      pria: staff.guru_lama?.pria ?? getSheetGenderCounts('GURU LAMA').pria,
      wanita: staff.guru_lama?.wanita ?? getSheetGenderCounts('GURU LAMA').wanita,
    },
    {
      key: 'guru_baru',
      sheet: 'GURU BARU',
      label: 'Guru Baru',
      badge: 'GURU BARU',
      sublabel: 'Sheet *GURU / BARU*',
      color: 'indigo',
      icon: Users,
      total: staff.guru_baru?.total ?? getSheetCount('GURU BARU'),
      pria: staff.guru_baru?.pria ?? getSheetGenderCounts('GURU BARU').pria,
      wanita: staff.guru_baru?.wanita ?? getSheetGenderCounts('GURU BARU').wanita,
    },
    {
      key: 'tendik_lama',
      sheet: 'TENDIK LAMA',
      label: 'Tendik Lama',
      badge: 'TENDIK LAMA',
      sublabel: 'Sheet *TENDIK LAMA*',
      color: 'emerald',
      icon: Users,
      total: staff.tendik_lama?.total ?? getSheetCount('TENDIK LAMA'),
      pria: staff.tendik_lama?.pria ?? getSheetGenderCounts('TENDIK LAMA').pria,
      wanita: staff.tendik_lama?.wanita ?? getSheetGenderCounts('TENDIK LAMA').wanita,
    },
    {
      key: 'tendik_baru',
      sheet: 'TENDIK BARU',
      label: 'Tendik Baru',
      badge: 'TENDIK BARU',
      sublabel: 'Sheet *TENDIK / BARU*',
      color: 'teal',
      icon: Users,
      total: staff.tendik_baru?.total ?? getSheetCount('TENDIK BARU'),
      pria: staff.tendik_baru?.pria ?? getSheetGenderCounts('TENDIK BARU').pria,
      wanita: staff.tendik_baru?.wanita ?? getSheetGenderCounts('TENDIK BARU').wanita,
    },
    {
      key: 'satpam',
      sheet: 'SATPAM',
      label: 'Satpam (PDH)',
      badge: 'SATPAM',
      sublabel: 'Sheet *SATPAM (+PDH)*',
      color: 'purple',
      icon: Footprints,
      total: staff.satpam?.total ?? getSheetCount('SATPAM'),
      pria: staff.satpam?.pria ?? getSheetGenderCounts('SATPAM').pria,
      wanita: staff.satpam?.wanita ?? getSheetGenderCounts('SATPAM').wanita,
    },
    {
      key: 'kebersihan',
      sheet: 'PETUGAS KEBERSIHAN',
      label: 'Petugas Kebersihan',
      badge: 'KEBERSIHAN',
      sublabel: 'Sheet *KEBERSIHAN*',
      color: 'rose',
      icon: Users,
      total: staff.kebersihan?.total ?? getSheetCount('PETUGAS KEBERSIHAN'),
      pria: staff.kebersihan?.pria ?? getSheetGenderCounts('PETUGAS KEBERSIHAN').pria,
      wanita: staff.kebersihan?.wanita ?? getSheetGenderCounts('PETUGAS KEBERSIHAN').wanita,
    },
    {
      key: 'dapur',
      sheet: 'PETUGAS DAPUR',
      label: 'Petugas Dapur',
      badge: 'DAPUR',
      sublabel: 'Sheet *PETUGAS DAPUR*',
      color: 'orange',
      icon: Users,
      total: staff.dapur?.total ?? getSheetCount('PETUGAS DAPUR'),
      pria: staff.dapur?.pria ?? getSheetGenderCounts('PETUGAS DAPUR').pria,
      wanita: staff.dapur?.wanita ?? getSheetGenderCounts('PETUGAS DAPUR').wanita,
    },
  ];
});

// Card active/theme color classes helper
const getStaffCardClasses = (color, isSelected) => {
  const styles = {
    amber: {
      active: 'bg-amber-50 dark:bg-amber-950/60 border-amber-400 dark:border-amber-600 ring-2 ring-amber-500/30',
      badge: 'bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800',
    },
    sky: {
      active: 'bg-sky-50 dark:bg-sky-950/60 border-sky-400 dark:border-sky-600 ring-2 ring-sky-500/30',
      badge: 'bg-sky-100 dark:bg-sky-900/60 text-sky-800 dark:text-sky-300 border-sky-200 dark:border-sky-800',
    },
    indigo: {
      active: 'bg-indigo-50 dark:bg-indigo-950/60 border-indigo-400 dark:border-indigo-600 ring-2 ring-indigo-500/30',
      badge: 'bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800',
    },
    emerald: {
      active: 'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-400 dark:border-emerald-600 ring-2 ring-emerald-500/30',
      badge: 'bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
    },
    teal: {
      active: 'bg-teal-50 dark:bg-teal-950/60 border-teal-400 dark:border-teal-600 ring-2 ring-teal-500/30',
      badge: 'bg-teal-100 dark:bg-teal-900/60 text-teal-800 dark:text-teal-300 border-teal-200 dark:border-teal-800',
    },
    purple: {
      active: 'bg-purple-50 dark:bg-purple-950/60 border-purple-400 dark:border-purple-600 ring-2 ring-purple-500/30',
      badge: 'bg-purple-100 dark:bg-purple-900/60 text-purple-800 dark:text-purple-300 border-purple-200 dark:border-purple-800',
    },
    rose: {
      active: 'bg-rose-50 dark:bg-rose-950/60 border-rose-400 dark:border-rose-600 ring-2 ring-rose-500/30',
      badge: 'bg-rose-100 dark:bg-rose-900/60 text-rose-800 dark:text-rose-300 border-rose-200 dark:border-rose-800',
    },
    orange: {
      active: 'bg-orange-50 dark:bg-orange-950/60 border-orange-400 dark:border-orange-600 ring-2 ring-orange-500/30',
      badge: 'bg-orange-100 dark:bg-orange-900/60 text-orange-800 dark:text-orange-300 border-orange-200 dark:border-orange-800',
    },
  };
  const c = styles[color] || styles.indigo;
  return isSelected
    ? c.active
    : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800/90 hover:border-slate-400 dark:hover:border-slate-600';
};

// Filter by clicking on a staff card
const filterByStaffSheet = (sheetName) => {
  if (activeTab.value === sheetName && selectedKategori.value === sheetName) {
    selectedKategori.value = '';
  } else {
    selectedKategori.value = sheetName;
    activeTab.value = sheetName;
  }
  primaryTab.value = 'recap';
};

// Reset current page when active tab or filters change
watch([activeTab, search, selectedGender], () => {
  currentPage.value = 1;
});

// Sync category selection with activeTab
watch(selectedKategori, (newKat) => {
  if (!newKat) return;
  activeTab.value = newKat;
});

// All filtered rows for current active tab
const filteredRows = computed(() => {
  const sheetData = currentRecap.value[activeTab.value]?.data || [];
  return sheetData.filter((row) => {
    const query = (search.value || '').toLowerCase().trim();
    const matchesSearch = !query || 
      (row.nama_lengkap && row.nama_lengkap.toLowerCase().includes(query)) ||
      (row.konversi_vendor && row.konversi_vendor.toLowerCase().includes(query)) ||
      (row.status && row.status.toLowerCase().includes(query)) ||
      (row.jenjang && row.jenjang.toLowerCase().includes(query));

    const matchesGender = !selectedGender.value || row.jenis_kelamin === selectedGender.value;

    return matchesSearch && matchesGender;
  });
});

const totalFiltered = computed(() => filteredRows.value.length);
const totalPages = computed(() => Math.ceil(totalFiltered.value / perPage.value) || 1);

const fromIndex = computed(() => {
  if (totalFiltered.value === 0) return 0;
  return (currentPage.value - 1) * perPage.value + 1;
});

const toIndex = computed(() => {
  return Math.min(currentPage.value * perPage.value, totalFiltered.value);
});

// Paginated rows: maximum 15 per page
const paginatedSheetRows = computed(() => {
  const start = (currentPage.value - 1) * perPage.value;
  return filteredRows.value.slice(start, start + perPage.value);
});

// Smart pagination buttons generator (matching exact design)
const paginationLinks = computed(() => {
  const current = currentPage.value;
  const last = totalPages.value;
  const delta = 2;

  if (last <= 1) return [];

  const range = [];
  const rangeWithDots = [];

  for (let i = 1; i <= last; i++) {
    if (
      i === 1 || 
      i === last || 
      i === 2 || 
      i === last - 1 || 
      (current <= 6 && i <= 10) ||
      (current >= last - 5 && i >= last - 9) ||
      (i >= current - delta && i <= current + delta)
    ) {
      range.push(i);
    }
  }

  let l;
  for (const i of range) {
    if (l) {
      if (i - l === 2) {
        rangeWithDots.push(l + 1);
      } else if (i - l !== 1) {
        rangeWithDots.push('...');
      }
    }
    rangeWithDots.push(i);
    l = i;
  }

  return rangeWithDots;
});

// Apply Master Filters via Inertia Router
const applyFilters = () => {
  router.get('/uniforms', {
    school_id: selectedSchool.value || undefined,
    jenjang: selectedJenjang.value || undefined,
    kategori: selectedKategori.value || undefined,
    gender: selectedGender.value || undefined,
    pic: selectedPic.value || undefined,
    search: search.value || undefined,
    tab: primaryTab.value,
  }, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
  });
};

const filterByJenjang = (jCode) => {
  if (selectedJenjang.value === jCode) {
    selectedJenjang.value = '';
  } else {
    selectedJenjang.value = jCode;
  }
  applyFilters();
};

const resetFilters = () => {
  selectedSchool.value = '';
  selectedJenjang.value = '';
  selectedKategori.value = '';
  selectedGender.value = '';
  selectedPic.value = '';
  search.value = '';
  applyFilters();
};

// JSON representation matching exact required output
const formattedJson = computed(() => {
  return JSON.stringify(currentRecap.value, null, 2);
});

// Handle files selection
const handleFileChange = (e) => {
  const files = Array.from(e.target.files || []);
  if (files.length > 0) {
    selectedFiles.value = files;
    uploadForm.excel_files = files;
  }
};

const removeFile = (idx) => {
  selectedFiles.value.splice(idx, 1);
  uploadForm.excel_files = selectedFiles.value;
};

// Submit upload form
const submitUpload = () => {
  if (!uploadForm.excel_files || uploadForm.excel_files.length === 0) return;

  let finalPic = selectedPicOption.value;
  if (finalPic === '__custom__') {
    finalPic = customPicName.value ? customPicName.value.trim() : '';
  } else if (finalPic === '__auto__') {
    finalPic = '';
  }
  uploadForm.pic_nama = finalPic;

  uploadForm.post('/uniforms/import', {
    preserveScroll: true,
    onSuccess: () => {
      isUploadModalOpen.value = false;
      uploadForm.reset();
      selectedFiles.value = [];
      selectedPicOption.value = '';
      customPicName.value = '';
      primaryTab.value = 'recap';
    },
  });
};

// Copy JSON to clipboard
const copyJson = async () => {
  try {
    await navigator.clipboard.writeText(formattedJson.value);
    isCopied.value = true;
    setTimeout(() => {
      isCopied.value = false;
    }, 2500);
  } catch (err) {
    console.error('Failed to copy JSON:', err);
  }
};

// Download JSON file
const downloadJson = () => {
  const blob = new Blob([formattedJson.value], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  const safeSchoolName = (schoolName.value || 'rekap_seragam').replace(/[^a-zA-Z0-9_\-]/g, '_');
  a.href = url;
  a.download = `rekap_seragam_${safeSchoolName}.json`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
};

// Riwayat Berkas Operations
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

const viewBatch = (batch) => {
  primaryTab.value = 'recap';
  router.get('/uniforms', { batch_id: batch.id, tab: 'recap' }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const deleteBatch = (batchId, batchName) => {
  if (confirm(`Apakah Anda yakin ingin menghapus berkas "${batchName || 'ini'}" beserta data seragamnya?`)) {
    router.delete(`/uniforms/batches/${batchId}`, {
      preserveScroll: true,
    });
  }
};

const syncAllFromStorage = () => {
  if (confirm('Apakah Anda yakin ingin menyinkronkan ulang seluruh berkas seragam dari storage? Sistem akan membaca ulang file Excel asli di storage.')) {
    isSyncing.value = true;
    router.post('/uniforms/sync-storage', {}, {
      preserveScroll: true,
      onFinish: () => {
        isSyncing.value = false;
      },
    });
  }
};
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex flex-col font-sans transition-colors duration-300">
    <Head title="Rekapitulasi Ukuran Seragam - Sepatu SR" />

    <!-- Top Navigation Header -->
    <HybridNavbar currentRoute="uniforms" />

    <!-- Main Content Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
      <!-- Breadcrumb & Top Bar -->
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <div class="flex items-center gap-2 text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Manajemen Data SR</span>
            <span>/</span>
            <span class="text-indigo-600 dark:text-indigo-400 font-semibold">Rekapitulasi Ukuran Seragam</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white tracking-tight mt-1 flex items-center gap-2.5">
            <Shirt class="w-7 h-7 text-indigo-600 dark:text-indigo-400 shrink-0" />
            <span>Rekapitulasi Ukuran Seragam</span>
          </h1>
          <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">
            Ekstraksi terstruktur data ukuran seragam sekolah rakyat (SRD, SRMP, SRMA) berbasis standar konversi vendor.
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
          <button
            type="button"
            @click="isUploadModalOpen = true"
            class="px-4 py-2.5 rounded-xl font-semibold text-sm bg-gradient-to-r from-indigo-600 via-indigo-700 to-indigo-800 hover:from-indigo-500 hover:to-indigo-700 text-white shadow-md shadow-indigo-500/20 active:scale-95 transition-all flex items-center gap-2 cursor-pointer"
          >
            <UploadCloud class="w-4 h-4" />
            <span>Import Excel Seragam</span>
          </button>

          <a
            :href="`/uniforms/export/excel${filters.school_id ? '?school_id=' + filters.school_id : (initialRecap.batch_id ? '?batch_id=' + initialRecap.batch_id : '')}`"
            class="px-3.5 py-2.5 rounded-xl font-medium text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 shadow-xs active:scale-95 transition-all flex items-center gap-2 cursor-pointer"
            title="Export Rekapitulasi ke Excel (.xlsx)"
          >
            <FileSpreadsheet class="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
            <span>Export Excel</span>
          </a>

          <button
            type="button"
            @click="copyJson"
            :disabled="totalRows === 0"
            class="px-3.5 py-2.5 rounded-xl font-medium text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
            title="Salin data ke format JSON"
          >
            <Check v-if="isCopied" class="w-4 h-4 text-emerald-500" />
            <Copy v-else class="w-4 h-4 text-slate-400" />
            <span>{{ isCopied ? 'Tersalin!' : 'Salin JSON' }}</span>
          </button>

          <button
            type="button"
            @click="downloadJson"
            :disabled="totalRows === 0"
            class="px-3.5 py-2.5 rounded-xl font-medium text-sm bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-200 transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
            title="Unduh berkas JSON"
          >
            <Download class="w-4 h-4 text-slate-400" />
            <span>Unduh JSON</span>
          </button>
        </div>
      </div>

      <!-- Flash Notification Success -->
      <div
        v-if="$page.props.flash?.success"
        class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/60 flex items-start gap-3 text-emerald-800 dark:text-emerald-300"
      >
        <CheckCircle2 class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" />
        <div class="text-sm">
          <p class="font-semibold">Berhasil</p>
          <p class="mt-0.5 text-emerald-700 dark:text-emerald-400">{{ $page.props.flash.success }}</p>
        </div>
      </div>

      <!-- Flash Notification Error -->
      <div
        v-if="$page.props.flash?.error"
        class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 flex items-start gap-3 text-rose-800 dark:text-rose-300"
      >
        <AlertCircle class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" />
        <div class="text-sm">
          <p class="font-semibold">Pemberitahuan</p>
          <p class="mt-0.5 text-rose-700 dark:text-rose-400">{{ $page.props.flash.error }}</p>
        </div>
      </div>

      <!-- ============================================================ -->
      <!-- BAGIAN 1: REKAPITULASI SISWA BERDASARKAN JENJANG (SRD, SRMP, SRMA) -->
      <!-- ============================================================ -->
      <div v-if="stats.sheet_stats" class="space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-indigo-100 dark:bg-indigo-950/80 text-indigo-600 dark:text-indigo-400 flex items-center justify-center font-bold text-xs">
              <Layers class="w-3.5 h-3.5" />
            </span>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">
              Rekapitulasi Kebutuhan Seragam Siswa Berdasarkan Jenjang Sekolah
            </h3>
            <span class="text-[11px] font-mono text-slate-400 dark:text-slate-500 hidden sm:inline">
              (SRD • SRMP • SRMA)
            </span>
          </div>

          <span v-if="selectedJenjang" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 px-2.5 py-1 rounded-full border border-indigo-200 dark:border-indigo-800 flex items-center gap-1.5 w-fit">
            Filter Jenjang Aktif: <span class="font-bold uppercase">{{ selectedJenjang }}</span>
            <button @click="filterByJenjang('')" class="hover:text-rose-600 ml-0.5 cursor-pointer" title="Hapus filter jenjang">✕</button>
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
          <!-- Card SRD (Sekolah Dasar) -->
          <div
            @click="filterByJenjang('SRD')"
            class="p-4 rounded-2xl border transition-all cursor-pointer relative overflow-hidden group hover:shadow-md"
            :class="selectedJenjang === 'SRD'
              ? 'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-400 dark:border-emerald-600 ring-2 ring-emerald-500/30'
              : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800/90 hover:border-emerald-300 dark:hover:border-emerald-700'"
          >
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-1.5">
                <span class="px-2 py-0.5 rounded-md text-[11px] font-extrabold bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                  SRD
                </span>
                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Sekolah Dasar</span>
              </div>
              <span class="text-[10px] text-slate-400 font-mono">Jenjang *SRD*</span>
            </div>
            <div class="flex items-baseline justify-between">
              <div class="text-2xl font-black text-slate-900 dark:text-white">
                {{ stats.sheet_stats.srd?.total || 0 }}
                <span class="text-xs font-normal text-slate-500">seragam</span>
              </div>
              <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400 font-mono">
                {{ stats.total_siswa > 0 ? Math.round(((stats.sheet_stats.srd?.total || 0) / stats.total_siswa) * 100) : 0 }}% Siswa
              </span>
            </div>
            <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
              <span class="text-blue-600 dark:text-blue-400 font-medium">{{ stats.sheet_stats.srd?.pria || 0 }} Laki-laki</span>
              <span>•</span>
              <span class="text-pink-600 dark:text-pink-400 font-medium">{{ stats.sheet_stats.srd?.wanita || 0 }} Perempuan</span>
            </div>
          </div>

          <!-- Card SRMP (Menengah Pertama) -->
          <div
            @click="filterByJenjang('SRMP')"
            class="p-4 rounded-2xl border transition-all cursor-pointer relative overflow-hidden group hover:shadow-md"
            :class="selectedJenjang === 'SRMP'
              ? 'bg-blue-50 dark:bg-blue-950/60 border-blue-400 dark:border-blue-600 ring-2 ring-blue-500/30'
              : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800/90 hover:border-blue-300 dark:hover:border-blue-700'"
          >
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-1.5">
                <span class="px-2 py-0.5 rounded-md text-[11px] font-extrabold bg-blue-100 dark:bg-blue-900/60 text-blue-800 dark:text-blue-300 border border-blue-200 dark:border-blue-800">
                  SRMP
                </span>
                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Menengah Pertama</span>
              </div>
              <span class="text-[10px] text-slate-400 font-mono">Jenjang *SRMP*</span>
            </div>
            <div class="flex items-baseline justify-between">
              <div class="text-2xl font-black text-slate-900 dark:text-white">
                {{ stats.sheet_stats.srmp?.total || 0 }}
                <span class="text-xs font-normal text-slate-500">seragam</span>
              </div>
              <span class="text-xs font-bold text-blue-600 dark:text-blue-400 font-mono">
                {{ stats.total_siswa > 0 ? Math.round(((stats.sheet_stats.srmp?.total || 0) / stats.total_siswa) * 100) : 0 }}% Siswa
              </span>
            </div>
            <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
              <span class="text-blue-600 dark:text-blue-400 font-medium">{{ stats.sheet_stats.srmp?.pria || 0 }} Laki-laki</span>
              <span>•</span>
              <span class="text-pink-600 dark:text-pink-400 font-medium">{{ stats.sheet_stats.srmp?.wanita || 0 }} Perempuan</span>
            </div>
          </div>

          <!-- Card SRMA (Menengah Atas) -->
          <div
            @click="filterByJenjang('SRMA')"
            class="p-4 rounded-2xl border transition-all cursor-pointer relative overflow-hidden group hover:shadow-md"
            :class="selectedJenjang === 'SRMA'
              ? 'bg-purple-50 dark:bg-purple-950/60 border-purple-400 dark:border-purple-600 ring-2 ring-purple-500/30'
              : 'bg-white dark:bg-slate-900 border-slate-200/90 dark:border-slate-800/90 hover:border-purple-300 dark:hover:border-purple-700'"
          >
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-1.5">
                <span class="px-2 py-0.5 rounded-md text-[11px] font-extrabold bg-purple-100 dark:bg-purple-900/60 text-purple-800 dark:text-purple-300 border border-purple-200 dark:border-purple-800">
                  SRMA
                </span>
                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300">Menengah Atas</span>
              </div>
              <span class="text-[10px] text-slate-400 font-mono">Jenjang *SRMA*</span>
            </div>
            <div class="flex items-baseline justify-between">
              <div class="text-2xl font-black text-slate-900 dark:text-white">
                {{ stats.sheet_stats.srma?.total || 0 }}
                <span class="text-xs font-normal text-slate-500">seragam</span>
              </div>
              <span class="text-xs font-bold text-purple-600 dark:text-purple-400 font-mono">
                {{ stats.total_siswa > 0 ? Math.round(((stats.sheet_stats.srma?.total || 0) / stats.total_siswa) * 100) : 0 }}% Siswa
              </span>
            </div>
            <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
              <span class="text-blue-600 dark:text-blue-400 font-medium">{{ stats.sheet_stats.srma?.pria || 0 }} Laki-laki</span>
              <span>•</span>
              <span class="text-pink-600 dark:text-pink-400 font-medium">{{ stats.sheet_stats.srma?.wanita || 0 }} Perempuan</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ============================================================ -->
      <!-- BAGIAN 2: REKAPITULASI STAFF DIPISAH SESUAI SHEET            -->
      <!-- ============================================================ -->
      <div class="space-y-3">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div class="flex items-center gap-2">
            <span class="w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-950/80 text-amber-600 dark:text-amber-400 flex items-center justify-center font-bold text-xs">
              <Briefcase class="w-3.5 h-3.5" />
            </span>
            <h3 class="text-sm font-bold text-slate-900 dark:text-white">
              Rekapitulasi Kebutuhan Seragam Staff & Tenaga Pendidik Sesuai Sheet
            </h3>
            <span class="text-[11px] font-mono text-slate-400 dark:text-slate-500 hidden sm:inline">
              (Kepsek • Guru • Tendik • Satpam • Kebersihan • Dapur)
            </span>
          </div>

          <span v-if="selectedKategori && selectedKategori !== 'SISWA'" class="text-xs font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 px-2.5 py-1 rounded-full border border-amber-200 dark:border-amber-800 flex items-center gap-1.5 w-fit">
            Filter Sheet Staff Aktif: <span class="font-bold uppercase">{{ selectedKategori }}</span>
            <button @click="selectedKategori = ''; activeTab = 'SISWA'; applyFilters()" class="hover:text-rose-600 ml-0.5 cursor-pointer" title="Hapus filter sheet staff">✕</button>
          </span>
        </div>

        <!-- Grid 8 Card Staff Sesuai Sheet -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
          <div
            v-for="card in staffCardList"
            :key="card.key"
            @click="filterByStaffSheet(card.sheet)"
            class="p-4 rounded-2xl border transition-all cursor-pointer relative overflow-hidden group hover:shadow-md"
            :class="getStaffCardClasses(card.color, activeTab === card.sheet || selectedKategori === card.sheet)"
          >
            <!-- Header Card -->
            <div class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-1.5 min-w-0">
                <span
                  class="px-2 py-0.5 rounded-md text-[10px] font-extrabold shrink-0 border"
                  :class="[
                    card.color === 'amber' ? 'bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300 border-amber-200 dark:border-amber-800' :
                    card.color === 'sky' ? 'bg-sky-100 dark:bg-sky-900/60 text-sky-800 dark:text-sky-300 border-sky-200 dark:border-sky-800' :
                    card.color === 'indigo' ? 'bg-indigo-100 dark:bg-indigo-900/60 text-indigo-800 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800' :
                    card.color === 'emerald' ? 'bg-emerald-100 dark:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800' :
                    card.color === 'teal' ? 'bg-teal-100 dark:bg-teal-900/60 text-teal-800 dark:text-teal-300 border-teal-200 dark:border-teal-800' :
                    card.color === 'purple' ? 'bg-purple-100 dark:bg-purple-900/60 text-purple-800 dark:text-purple-300 border-purple-200 dark:border-purple-800' :
                    card.color === 'rose' ? 'bg-rose-100 dark:bg-rose-900/60 text-rose-800 dark:text-rose-300 border-rose-200 dark:border-rose-800' :
                    'bg-orange-100 dark:bg-orange-900/60 text-orange-800 dark:text-orange-300 border-orange-200 dark:border-orange-800'
                  ]"
                >
                  {{ card.badge }}
                </span>
                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 truncate">
                  {{ card.label }}
                </span>
              </div>
              <span class="text-[10px] text-slate-400 font-mono shrink-0">{{ card.sublabel }}</span>
            </div>

            <!-- Body Card: Total Count -->
            <div class="flex items-baseline justify-between">
              <div class="text-2xl font-black text-slate-900 dark:text-white">
                {{ card.total }}
                <span class="text-xs font-normal text-slate-500">seragam</span>
              </div>
              <span
                v-if="card.sheet === 'SATPAM'"
                class="text-[10px] font-bold text-purple-600 dark:text-purple-400 font-mono bg-purple-50 dark:bg-purple-950/60 px-1.5 py-0.5 rounded-md border border-purple-200 dark:border-purple-800"
              >
                +PDH
              </span>
              <span
                v-else-if="card.sheet.includes('BARU')"
                class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 font-mono bg-emerald-50 dark:bg-emerald-950/60 px-1.5 py-0.5 rounded-md border border-emerald-200 dark:border-emerald-800"
              >
                Baru
              </span>
              <span
                v-else
                class="text-[10px] font-bold text-slate-500 dark:text-slate-400 font-mono"
              >
                Sheet
              </span>
            </div>

            <!-- Footer Card: Gender Breakdown -->
            <div class="mt-2.5 pt-2 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
              <span class="text-blue-600 dark:text-blue-400 font-medium">{{ card.pria }} Laki-laki</span>
              <span>•</span>
              <span class="text-pink-600 dark:text-pink-400 font-medium">{{ card.wanita }} Perempuan</span>
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- MASTER FILTER CARD (MATCHING SHOES DESIGN) -->
      <!-- ========================================== -->
      <div class="p-4 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 shadow-xs space-y-3">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3">
          <!-- Left: Filter Dropdowns -->
          <div class="flex flex-wrap items-center gap-2.5">
            <!-- Filter Sekolah -->
            <div class="flex items-center gap-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1">
                <SchoolIcon class="w-3.5 h-3.5 text-indigo-500" />
                Sekolah:
              </label>
              <select
                v-model="selectedSchool"
                @change="applyFilters"
                class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer"
              >
                <option value="">Semua Sekolah (Agregasi Global)</option>
                <option v-for="sc in schools" :key="sc.id" :value="sc.id">
                  {{ sc.nama_sekolah }} ({{ sc.uniform_recipients_count || sc.total_rows || 0 }} penerima)
                </option>
              </select>
            </div>

            <!-- Filter Jenjang -->
            <div class="flex items-center gap-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1">
                <Layers class="w-3.5 h-3.5 text-indigo-500" />
                Jenjang:
              </label>
              <select
                v-model="selectedJenjang"
                @change="applyFilters"
                class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer"
              >
                <option value="">Semua Jenjang</option>
                <option value="SRD">🏫 SRD (Sekolah Dasar)</option>
                <option value="SRMP">🏫 SRMP (Menengah Pertama)</option>
                <option value="SRMA">🏫 SRMA (Menengah Atas)</option>
              </select>
            </div>

            <!-- Filter Kategori Sheet (Pisah Sesuai Sheet) -->
            <div class="flex items-center gap-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Sheet / Kategori:</label>
              <select
                v-model="selectedKategori"
                @change="applyFilters"
                class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer"
              >
                <option value="">Semua Sheet (9 Kategori)</option>
                <optgroup label="Siswa">
                  <option value="SISWA">🎓 SISWA (SD / SMP / SMA)</option>
                </optgroup>
                <optgroup label="Staff & Tenaga Pendidik">
                  <option value="KEPSEK">🏫 KEPSEK (Kepala Sekolah)</option>
                  <option value="GURU LAMA">👨‍🏫 GURU LAMA</option>
                  <option value="GURU BARU">👨‍🏫 GURU BARU (Termasuk Sheet GURU)</option>
                  <option value="TENDIK LAMA">💼 TENDIK LAMA</option>
                  <option value="TENDIK BARU">💼 TENDIK BARU (Termasuk Sheet TENDIK)</option>
                  <option value="SATPAM">🛡️ SATPAM (Termasuk PDH)</option>
                  <option value="PETUGAS KEBERSIHAN">🧹 PETUGAS KEBERSIHAN</option>
                  <option value="PETUGAS DAPUR">🍳 PETUGAS DAPUR</option>
                </optgroup>
              </select>
            </div>

            <!-- Filter Gender -->
            <div class="flex items-center gap-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Gender:</label>
              <select
                v-model="selectedGender"
                @change="applyFilters"
                class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer"
              >
                <option value="">L & P</option>
                <option value="L">Laki-laki (L)</option>
                <option value="P">Perempuan (P)</option>
              </select>
            </div>

            <!-- Filter PIC -->
            <div class="flex items-center gap-1.5">
              <label class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 flex items-center gap-1">
                <UserCheck class="w-3.5 h-3.5 text-indigo-500" />
                PIC:
              </label>
              <select
                v-model="selectedPic"
                @change="applyFilters"
                class="text-xs font-semibold px-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none cursor-pointer"
              >
                <option value="">Semua PIC</option>
                <option v-for="p in picList" :key="p" :value="p">👤 {{ p }}</option>
              </select>
            </div>
          </div>

          <!-- Right: Search Box & Reset Button -->
          <div class="flex items-center gap-2">
            <div class="relative flex-1 md:w-64">
              <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                v-model="search"
                @keyup.enter="applyFilters"
                type="text"
                placeholder="Cari nama, status, berkas..."
                class="w-full text-xs pl-9 pr-3 py-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-indigo-500 outline-none"
              />
            </div>

            <button
              type="button"
              @click="applyFilters"
              class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white transition-colors cursor-pointer shadow-xs"
            >
              Cari
            </button>

            <button
              v-if="selectedSchool || selectedJenjang || selectedKategori || selectedGender || selectedPic || search"
              type="button"
              @click="resetFilters"
              class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
              title="Reset Semua Filter"
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
            <div v-if="activeSchoolObj.pic_nama" class="flex items-center gap-1 text-slate-600 dark:text-slate-400 font-medium">
              <UserCheck class="w-3.5 h-3.5 text-emerald-600" />
              PIC: {{ activeSchoolObj.pic_nama }}
            </div>
            <div class="flex items-center gap-1 text-slate-500 dark:text-slate-400">
              <span>{{ activeSchoolObj.uniform_recipients_count || 0 }} total data seragam tercatat</span>
            </div>
          </div>

          <button
            type="button"
            @click="selectedSchool = ''; applyFilters()"
            class="text-xs font-semibold text-rose-600 dark:text-rose-400 hover:underline cursor-pointer flex items-center gap-1"
          >
            <X class="w-3.5 h-3.5" />
            Hapus Filter Sekolah
          </button>
        </div>
      </div>

      <!-- PRIMARY NAVIGATION TABS (MATCHING USER SCREENSHOT) -->
      <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800">
        <button
          @click="switchPrimaryTab('recap')"
          class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border-b-2 transition-all cursor-pointer"
          :class="primaryTab === 'recap' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
        >
          <FileSpreadsheet class="w-4 h-4" />
          Rekapitulasi Ukuran Seragam
          <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 font-mono">
            {{ totalRows }} Penerima
          </span>
        </button>

        <button
          @click="switchPrimaryTab('batches')"
          class="flex items-center gap-2 px-4 py-2.5 text-sm font-semibold border-b-2 transition-all cursor-pointer"
          :class="primaryTab === 'batches' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'"
        >
          <Layers class="w-4 h-4" />
          Riwayat Berkas File
          <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-mono">
            {{ totalBatchesCount }} Berkas
          </span>
        </button>
      </div>

      <!-- ========================================== -->
      <!-- TAB 1: REKAPITULASI UKURAN SERAGAM         -->
      <!-- ========================================== -->
      <div v-if="primaryTab === 'recap'" class="space-y-6">
        <!-- Categories Tab Navigation (9 Sheets + Format JSON) -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-2 shadow-xs">
          <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 scrollbar-thin">
            <button
              v-for="s in sheetsList"
              :key="s.key"
              type="button"
              @click="activeTab = s.key; selectedKategori = s.key"
              class="px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-2 cursor-pointer shrink-0"
              :class="[
                activeTab === s.key
                  ? 'bg-indigo-600 text-white shadow-sm'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80'
              ]"
            >
              <span>{{ s.label }}</span>
              <span
                class="px-1.5 py-0.5 rounded-full text-[10px] font-bold"
                :class="activeTab === s.key ? 'bg-indigo-700/80 text-white' : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-300'"
              >
                {{ getSheetCount(s.key) }}
              </span>
            </button>

            <!-- Raw JSON View Tab -->
            <button
              type="button"
              @click="activeTab = 'RAW_JSON'"
              class="px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all flex items-center gap-1.5 cursor-pointer shrink-0 ml-auto"
              :class="[
                activeTab === 'RAW_JSON'
                  ? 'bg-slate-800 text-white dark:bg-slate-700 shadow-sm'
                  : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800/80'
              ]"
            >
              <FileCode class="w-3.5 h-3.5" />
              <span>Format JSON</span>
            </button>
          </div>
        </div>

        <!-- Table Section when a normal sheet is selected -->
        <div v-if="activeTab !== 'RAW_JSON'" class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-xs overflow-hidden">
          <!-- Table Toolbar -->
          <div class="p-4 border-b border-slate-200/80 dark:border-slate-800/80 flex flex-wrap items-center justify-between gap-3 bg-slate-50/50 dark:bg-slate-900/50">
            <div class="flex items-center gap-3">
              <h2 class="text-base font-bold text-slate-900 dark:text-white">
                Data Sheet: {{ activeTab }}
              </h2>
              <span class="text-xs px-2.5 py-0.5 rounded-full font-mono bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60">
                {{ totalFiltered }} penerima (Maks. {{ perPage }} / hal)
              </span>
              <span v-if="activeTab === 'GURU BARU'" class="hidden md:inline-block text-[11px] px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 font-medium border border-amber-200 dark:border-amber-800/60">
                *Sheet GURU tunggal otomatis masuk ke sini
              </span>
              <span v-if="activeTab === 'TENDIK BARU'" class="hidden md:inline-block text-[11px] px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 font-medium border border-amber-200 dark:border-amber-800/60">
                *Sheet TENDIK tunggal otomatis masuk ke sini
              </span>
              <span v-if="activeTab === 'SATPAM'" class="hidden md:inline-block text-[11px] px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-medium border border-indigo-200 dark:border-indigo-800/60">
                *Dilengkapi Ukuran Sepatu PDH
              </span>
            </div>

            <!-- Table-level Quick Search & Gender Filter -->
            <div class="flex items-center gap-2">
              <div class="relative">
                <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
                <input
                  v-model="search"
                  type="text"
                  placeholder="Cari nama / status..."
                  class="pl-9 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20"
                />
              </div>

              <select
                v-model="selectedGender"
                class="px-2.5 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-700 dark:text-slate-300 focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20 cursor-pointer"
              >
                <option value="">Semua L/P</option>
                <option value="L">Laki-laki (L)</option>
                <option value="P">Perempuan (P)</option>
              </select>
            </div>
          </div>

          <!-- Responsive Table -->
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <!-- Group Headers -->
                <tr class="bg-slate-100/70 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
                  <th colspan="4" class="py-2 px-3 font-semibold text-center border-r border-slate-200 dark:border-slate-800">INFORMASI DASAR</th>
                  <th colspan="2" class="py-2 px-3 font-semibold text-center border-r border-slate-200 dark:border-slate-800 bg-indigo-50/50 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-300">
                    ATASAN
                  </th>
                  <th :colspan="activeTab === 'SATPAM' ? 4 : 3" class="py-2 px-3 font-semibold text-center border-r border-slate-200 dark:border-slate-800 bg-purple-50/50 dark:bg-purple-950/30 text-purple-700 dark:text-purple-300">
                    BAWAHAN
                  </th>
                  <th class="py-2 px-3 font-semibold text-center">VENDOR</th>
                </tr>

                <!-- Column Label Headers -->
                <tr class="border-b border-slate-200 dark:border-slate-800 text-[11px] font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider bg-slate-50 dark:bg-slate-900">
                  <th class="py-2.5 px-3 w-12 text-center">No</th>
                  <th class="py-2.5 px-4 min-w-[180px]">Nama Lengkap</th>
                  <th class="py-2.5 px-3 text-center">JK</th>
                  <th v-if="activeTab === 'SISWA'" class="py-2.5 px-3">Jenjang</th>
                  <th v-else-if="activeTab !== 'KEPSEK'" class="py-2.5 px-3">Status</th>
                  <th v-else class="py-2.5 px-3 text-slate-400">-</th>

                  <!-- Atasan -->
                  <th class="py-2.5 px-3 text-center bg-indigo-50/30 dark:bg-indigo-950/10">Lingkar Badan (cm)</th>
                  <th class="py-2.5 px-3 text-center bg-indigo-50/30 dark:bg-indigo-950/10">Panjang Badan (cm)</th>

                  <!-- Bawahan -->
                  <th class="py-2.5 px-3 text-center bg-purple-50/30 dark:bg-purple-950/10">Lingkar Pinggang (cm)</th>
                  <th class="py-2.5 px-3 text-center bg-purple-50/30 dark:bg-purple-950/10">Panjang Celana (cm)</th>
                  <th class="py-2.5 px-3 text-center bg-purple-50/30 dark:bg-purple-950/10">Panjang Pesak (cm)</th>
                  <th v-if="activeTab === 'SATPAM'" class="py-2.5 px-3 text-center bg-purple-100/50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                    Sepatu PDH
                  </th>

                  <!-- Vendor -->
                  <th class="py-2.5 px-3 text-center">Konversi Vendor</th>
                </tr>
              </thead>

              <tbody class="divide-y divide-slate-100 dark:divide-slate-800/70">
                <tr
                  v-for="(row, idx) in paginatedSheetRows"
                  :key="idx"
                  class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors"
                >
                  <td class="py-2 px-3 text-center font-mono text-slate-500 dark:text-slate-400">
                    {{ row.no ?? (fromIndex + idx) }}
                  </td>

                  <td class="py-2 px-4 font-medium text-slate-900 dark:text-white">
                    {{ row.nama_lengkap || '-' }}
                  </td>

                  <td class="py-2 px-3 text-center">
                    <span
                      class="px-2 py-0.5 rounded-md text-[10px] font-bold"
                      :class="row.jenis_kelamin === 'L' ? 'bg-sky-100 dark:bg-sky-950/60 text-sky-700 dark:text-sky-300' : 'bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300'"
                    >
                      {{ row.jenis_kelamin || '-' }}
                    </span>
                  </td>

                  <td class="py-2 px-3 text-slate-600 dark:text-slate-300">
                    <span v-if="activeTab === 'SISWA'" class="font-medium">
                      {{ row.jenjang || '-' }}
                    </span>
                    <span v-else-if="activeTab !== 'KEPSEK'" class="font-medium text-slate-700 dark:text-slate-300">
                      {{ row.status || '-' }}
                    </span>
                    <span v-else class="text-slate-400">-</span>
                  </td>

                  <!-- Atasan -->
                  <td class="py-2 px-3 text-center font-mono font-medium text-slate-800 dark:text-slate-200 bg-indigo-50/10">
                    {{ row.atasan?.lingkar_badan_cm ?? '-' }}
                  </td>
                  <td class="py-2 px-3 text-center font-mono font-medium text-slate-800 dark:text-slate-200 bg-indigo-50/10">
                    {{ row.atasan?.panjang_badan_cm ?? '-' }}
                  </td>

                  <!-- Bawahan -->
                  <td class="py-2 px-3 text-center font-mono font-medium text-slate-800 dark:text-slate-200 bg-purple-50/10">
                    {{ row.bawahan?.lingkar_pinggang_cm ?? '-' }}
                  </td>
                  <td class="py-2 px-3 text-center font-mono font-medium text-slate-800 dark:text-slate-200 bg-purple-50/10">
                    {{ row.bawahan?.panjang_celana_cm ?? '-' }}
                  </td>
                  <td class="py-2 px-3 text-center font-mono font-medium text-slate-800 dark:text-slate-200 bg-purple-50/10">
                    {{ row.bawahan?.panjang_pesak_cm ?? '-' }}
                  </td>

                  <!-- Ukuran Sepatu PDH (Satpam only) -->
                  <td v-if="activeTab === 'SATPAM'" class="py-2 px-3 text-center font-mono font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50/40 dark:bg-indigo-950/40">
                    {{ row.bawahan?.ukuran_sepatu_pdh ?? '-' }}
                  </td>

                  <!-- Konversi Vendor -->
                  <td class="py-2 px-3 text-center">
                    <span
                      v-if="row.konversi_vendor"
                      class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60"
                    >
                      {{ row.konversi_vendor }}
                    </span>
                    <span v-else class="text-slate-400">-</span>
                  </td>
                </tr>

                <!-- Empty State -->
                <tr v-if="filteredRows.length === 0">
                  <td :colspan="activeTab === 'SATPAM' ? 11 : 10" class="py-12 text-center text-slate-400 dark:text-slate-500">
                    <div class="flex flex-col items-center justify-center gap-2">
                      <AlertCircle class="w-8 h-8 text-slate-300 dark:text-slate-600" />
                      <p class="text-sm font-medium">Tidak ada data untuk sheet "{{ activeTab }}".</p>
                      <p class="text-xs text-slate-400">Silakan sesuaikan filter atau unggah berkas Excel ukuran seragam.</p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Controls Matching User Design -->
          <div v-if="totalFiltered > 0" class="flex flex-col sm:flex-row items-center justify-between gap-3 px-4 py-3.5 border-t border-slate-200/80 dark:border-slate-800/80 text-xs bg-slate-50/50 dark:bg-slate-900/50">
            <!-- Text Info -->
            <div class="text-slate-500 dark:text-slate-400">
              Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-200">{{ fromIndex }}</span> - <span class="font-semibold text-slate-700 dark:text-slate-200">{{ toIndex }}</span> dari <span class="font-semibold text-slate-700 dark:text-slate-200">{{ totalFiltered }}</span> penerima
            </div>

            <!-- Page Navigation Buttons -->
            <div v-if="totalPages > 1" class="flex items-center gap-1 flex-wrap justify-center">
              <!-- Previous Button -->
              <button
                type="button"
                :disabled="currentPage === 1"
                @click="currentPage = Math.max(1, currentPage - 1)"
                class="px-3 py-1.5 rounded-lg border font-medium transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800"
              >
                &laquo; Previous
              </button>

              <!-- Page Number Buttons & Ellipsis -->
              <template v-for="(item, idx) in paginationLinks" :key="idx">
                <span v-if="item === '...'" class="px-2 py-1.5 text-slate-400 font-medium">...</span>
                <button
                  v-else
                  type="button"
                  @click="currentPage = item"
                  class="min-w-[34px] px-2.5 py-1.5 rounded-lg border font-semibold transition-all cursor-pointer text-center"
                  :class="currentPage === item 
                    ? 'bg-indigo-600 border-indigo-600 text-white shadow-xs' 
                    : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                >
                  {{ item }}
                </button>
              </template>

              <!-- Next Button -->
              <button
                type="button"
                :disabled="currentPage === totalPages"
                @click="currentPage = Math.min(totalPages, currentPage + 1)"
                class="px-3 py-1.5 rounded-lg border font-medium transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800"
              >
                Next &raquo;
              </button>
            </div>
          </div>
        </div>

        <!-- Raw JSON Viewer -->
        <div v-else class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl shadow-xs overflow-hidden">
          <div class="p-4 border-b border-slate-200/80 dark:border-slate-800/80 flex items-center justify-between bg-slate-50/50 dark:bg-slate-900/50">
            <div>
              <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <FileCode class="w-4 h-4 text-indigo-500" />
                Output Rekapitulasi JSON Sesuai Spesifikasi
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">
                Struktur JSON 9 sheet lengkap dengan konversi atasan, bawahan, dan vendor.
              </p>
            </div>

            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="copyJson"
                class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-white dark:bg-slate-950 border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-900 text-slate-700 dark:text-slate-300 transition-colors flex items-center gap-1.5 cursor-pointer"
              >
                <Check v-if="isCopied" class="w-3.5 h-3.5 text-emerald-500" />
                <Copy v-else class="w-3.5 h-3.5 text-slate-400" />
                <span>{{ isCopied ? 'Tersalin' : 'Salin JSON' }}</span>
              </button>
              <button
                type="button"
                @click="downloadJson"
                class="px-3 py-1.5 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white transition-colors flex items-center gap-1.5 cursor-pointer"
              >
                <Download class="w-3.5 h-3.5" />
                <span>Unduh .json</span>
              </button>
            </div>
          </div>

          <div class="p-4 bg-slate-950 text-slate-200 font-mono text-xs overflow-x-auto max-h-[600px] leading-relaxed">
            <pre>{{ formattedJson }}</pre>
          </div>
        </div>
      </div>

      <!-- ========================================== -->
      <!-- TAB 2: RIWAYAT BERKAS FILE IMPORT          -->
      <!-- ========================================== -->
      <div v-if="primaryTab === 'batches'" class="space-y-6">
        <div>
          <!-- Header and Sync Button -->
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div>
              <h2 class="text-base font-bold text-slate-900 dark:text-white">
                Riwayat Berkas File yang Di-Import
              </h2>
              <p class="text-xs text-slate-500">Berkas Excel asli tersimpan aman di server storage dan siap disinkronkan kapan saja.</p>
            </div>

            <button
              type="button"
              @click="syncAllFromStorage"
              :disabled="isSyncing"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100 dark:hover:bg-indigo-900/60 transition-colors cursor-pointer disabled:opacity-50"
              title="Baca ulang semua berkas Excel di storage dan perbarui data jika ada perubahan"
            >
              <RefreshCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isSyncing }" />
              <span>{{ isSyncing ? 'Menyinkronkan...' : 'Sinkronkan Ulang dari Storage' }}</span>
            </button>
          </div>

          <!-- Batches Table -->
          <div class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm">
            <table class="w-full text-left text-sm">
              <thead class="bg-slate-50 dark:bg-slate-800/80 text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300 border-b border-slate-200 dark:border-slate-800">
                <tr>
                  <th class="py-3 px-4 w-52 max-w-[200px]">Nama File</th>
                  <th class="py-3 px-4 min-w-[180px]">Sekolah</th>
                  <th class="py-3 px-4 whitespace-nowrap">PIC Ukur</th>
                  <th class="py-3 px-4 text-center whitespace-nowrap">Total Seragam</th>
                  <th class="py-3 px-4 text-center whitespace-nowrap">Rincian (S / G / T / Staff)</th>
                  <th class="py-3 px-4 whitespace-nowrap">Waktu Import</th>
                  <th class="py-3 px-4 text-center w-24 whitespace-nowrap">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                <tr v-for="b in batchList" :key="b.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                  <!-- Nama File -->
                  <td class="py-3 px-4 max-w-[200px]" :title="b.file_name">
                    <div class="flex items-center gap-1.5 min-w-0">
                      <FileSpreadsheet class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                      <span class="font-mono text-xs font-medium text-slate-800 dark:text-slate-200 truncate">
                        {{ b.file_name }}
                      </span>
                    </div>
                  </td>

                  <!-- Sekolah -->
                  <td class="py-3 px-4 font-medium text-slate-900 dark:text-slate-100">
                    {{ b.school?.nama_sekolah || b.school_name || '-' }}
                  </td>

                  <!-- PIC -->
                  <td class="py-3 px-4 whitespace-nowrap">
                    <span
                      v-if="b.pic_nama || b.school?.pic_nama"
                      class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200/60 dark:border-indigo-800/60"
                    >
                      <UserCheck class="w-3 h-3 text-indigo-500 dark:text-indigo-400" />
                      {{ b.pic_nama || b.school?.pic_nama }}
                    </span>
                    <span v-else class="text-slate-400 italic text-xs">Belum diatur</span>
                  </td>

                  <!-- Total -->
                  <td class="py-3 px-4 text-center font-bold text-indigo-600 dark:text-indigo-400 font-mono whitespace-nowrap">
                    {{ b.total_rows }}
                  </td>

                  <!-- Breakdown -->
                  <td class="py-3 px-4 text-center font-mono text-slate-500 whitespace-nowrap">
                    {{ b.total_siswa }} / {{ b.total_guru_lama + b.total_guru_baru }} / {{ b.total_tendik_lama + b.total_tendik_baru }} / {{ b.total_satpam + b.total_kebersihan + b.total_dapur }}
                  </td>

                  <!-- Waktu -->
                  <td class="py-3 px-4 text-slate-500 whitespace-nowrap">
                    {{ new Date(b.created_at).toLocaleString('id-ID') }}
                  </td>

                  <!-- Aksi -->
                  <td class="py-3 px-4 text-center whitespace-nowrap">
                    <div class="flex items-center justify-center gap-1">
                      <!-- View / Load Batch to Recap Tab -->
                      <button
                        type="button"
                        @click="viewBatch(b)"
                        class="p-1.5 rounded-lg text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/50 transition-colors cursor-pointer"
                        title="Tampilkan Data Rekapitulasi Berkas Ini"
                      >
                        <Eye class="w-4 h-4" />
                      </button>

                      <!-- Download original file -->
                      <a
                        :href="`/uniforms/batches/${b.id}/download`"
                        class="p-1.5 rounded-lg text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/50 transition-colors"
                        title="Unduh Berkas Excel Asli"
                      >
                        <Download class="w-4 h-4" />
                      </a>

                      <!-- Delete batch -->
                      <button
                        type="button"
                        @click="deleteBatch(b.id, b.school_name || b.school?.nama_sekolah)"
                        class="text-rose-500 hover:text-rose-700 p-1.5 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/50 transition-colors cursor-pointer"
                        title="Hapus Batch Ini"
                      >
                        <Trash2 class="w-4 h-4" />
                      </button>
                    </div>
                  </td>
                </tr>

                <tr v-if="batchList.length === 0">
                  <td colspan="7" class="py-12 text-center text-slate-400 dark:text-slate-500">
                    <div class="flex flex-col items-center justify-center gap-2">
                      <AlertCircle class="w-8 h-8 text-slate-300 dark:text-slate-600" />
                      <p class="text-sm font-medium">Belum ada berkas Excel seragam yang cocok dengan filter yang dipilih.</p>
                      <p class="text-xs text-slate-400">Silakan ubah kata kunci pencarian atau reset filter.</p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Pagination Riwayat Berkas (15 per halaman) -->
          <div
            v-if="batches?.total && batches.total > batches.per_page"
            class="flex flex-col sm:flex-row items-center justify-between gap-3 px-2 pt-4 text-xs"
          >
            <div class="text-slate-500 dark:text-slate-400">
              Menampilkan <span class="font-semibold text-slate-700 dark:text-slate-200">{{ batches.from }}</span> - <span class="font-semibold text-slate-700 dark:text-slate-200">{{ batches.to }}</span> dari <span class="font-semibold text-slate-700 dark:text-slate-200">{{ batches.total }}</span> berkas import
            </div>

            <div class="flex items-center gap-1 flex-wrap justify-center">
              <template v-for="(link, lIdx) in batches.links" :key="lIdx">
                <button
                  v-if="link.url"
                  type="button"
                  @click="goToBatchPage(link.url)"
                  class="px-3 py-1.5 rounded-lg border font-medium transition-colors cursor-pointer"
                  :class="link.active 
                    ? 'bg-indigo-600 border-indigo-600 text-white shadow-xs' 
                    : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800'"
                  v-html="link.label"
                ></button>
                <span v-else class="px-2 py-1.5 text-slate-400" v-html="link.label"></span>
              </template>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- ========================================== -->
    <!-- MODAL: UPLOAD EXCEL FILE (MULTI & PIC)     -->
    <!-- ========================================== -->
    <div
      v-if="isUploadModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-xl w-full p-6 shadow-2xl relative space-y-5">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-3 border-b border-slate-100 dark:border-slate-800">
          <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
              <FileUp class="w-5 h-5" />
            </div>
            <div>
              <h3 class="font-bold text-base text-slate-900 dark:text-white">Import File Excel Seragam SR</h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">Pilih berkas Excel (.xlsx / .xls) ukuran seragam SR.</p>
            </div>
          </div>
          <button
            type="button"
            @click="isUploadModalOpen = false"
            class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 p-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
          >
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitUpload" class="space-y-4">
          <!-- PIC Ukur Selector -->
          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <label class="text-xs font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                <Users class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                <span>Penanggung Jawab / Nama PIC:</span>
                <span class="text-rose-500">*</span>
              </label>
              <span class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900/60">
                Folder PIC
              </span>
            </div>

            <div>
              <select
                v-model="selectedPicOption"
                class="w-full px-3.5 py-2.5 rounded-xl text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-xs cursor-pointer"
              >
                <option value="" disabled>-- Silakan Pilih Nama PIC Pengumpul Berkas --</option>
                <optgroup label="Daftar Master PIC Standar SR">
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

            <!-- Custom PIC input if selected -->
            <div v-if="selectedPicOption === '__custom__'" class="mt-2">
              <input
                v-model="customPicName"
                type="text"
                placeholder="Ketikkan nama PIC lengkap (contoh: YULIA)..."
                class="w-full px-3.5 py-2 rounded-xl text-xs bg-white dark:bg-slate-800 border border-indigo-400 dark:border-indigo-600 text-slate-800 dark:text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 placeholder:text-slate-400"
              />
            </div>
            <p class="text-[11px] text-slate-500 dark:text-slate-400">
              Setiap data ukuran seragam yang di-import pada proses ini akan ditugaskan ke PIC yang dipilih.
            </p>
          </div>

          <!-- File Upload Drag & Drop Area (Supports Multiple Files) -->
          <div
            @click="$refs.fileInput.click()"
            class="border-2 border-dashed border-slate-200 dark:border-slate-800 hover:border-indigo-500 dark:hover:border-indigo-500 rounded-2xl p-6 text-center cursor-pointer bg-slate-50/50 dark:bg-slate-950/50 transition-colors"
          >
            <input
              ref="fileInput"
              type="file"
              multiple
              accept=".xlsx,.xls,.csv"
              @change="handleFileChange"
              class="hidden"
            />
            <FileSpreadsheet class="w-10 h-10 text-indigo-500 mx-auto" />
            <p class="mt-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
              Klik untuk memilih atau seret satu atau banyak berkas Excel ke sini
            </p>
            <p class="text-xs text-slate-400 mt-1">
              Mendukung: Data Ukuran Seragam SRMA 13 Bekasi (OK).xlsx, dsb. (Bisa pilih sekaligus)
            </p>
          </div>

          <!-- Selected files list -->
          <div v-if="selectedFiles.length > 0" class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
            <div
              v-for="(f, fIdx) in selectedFiles"
              :key="fIdx"
              class="flex items-center justify-between p-2 rounded-xl bg-slate-100 dark:bg-slate-800/60 border border-slate-200 dark:border-slate-700 text-xs"
            >
              <div class="flex items-center gap-2 truncate">
                <FileSpreadsheet class="w-4 h-4 text-emerald-600 shrink-0" />
                <span class="font-medium text-slate-800 dark:text-slate-200 truncate">{{ f.name }}</span>
                <span class="text-[10px] text-slate-400">({{ (f.size / 1024).toFixed(1) }} KB)</span>
              </div>
              <button
                type="button"
                @click.stop="removeFile(fIdx)"
                class="text-rose-500 hover:text-rose-700 p-1"
                title="Hapus"
              >
                <X class="w-3.5 h-3.5" />
              </button>
            </div>
          </div>

          <!-- Notes / Rules Reminder -->
          <div class="bg-indigo-50/50 dark:bg-indigo-950/20 border border-indigo-100 dark:border-indigo-900/50 rounded-xl p-3 text-xs text-indigo-900 dark:text-indigo-300 space-y-1">
            <p class="font-semibold flex items-center gap-1.5">
              <Info class="w-3.5 h-3.5 text-indigo-500" />
              <span>Aturan Pemrosesan Otomatis:</span>
            </p>
            <ul class="list-disc list-inside space-y-0.5 text-[11px] text-indigo-800 dark:text-indigo-300">
              <li>Nama sekolah dideteksi otomatis dari awalan <strong>SRD</strong>, <strong>SRMP</strong>, atau <strong>SRMA</strong> pada nama berkas.</li>
              <li>Sheet dengan nama <strong>GURU</strong> saja otomatis masuk ke sheet <strong>GURU BARU</strong>.</li>
              <li>Sheet dengan nama <strong>TENDIK</strong> saja otomatis masuk ke sheet <strong>TENDIK BARU</strong>.</li>
              <li>Berkas asli tersimpan aman di server dan riwayat berkas dapat diunduh / disinkronkan kembali kapan saja.</li>
            </ul>
          </div>

          <!-- Action Buttons -->
          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
            <button
              type="button"
              @click="isUploadModalOpen = false"
              class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="selectedFiles.length === 0 || uploadForm.processing"
              class="px-4 py-2 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer flex items-center gap-2"
            >
              <UploadCloud class="w-4 h-4" />
              <span>{{ uploadForm.processing ? 'Memproses Import...' : `Mulai Import (${selectedFiles.length} Berkas)` }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
