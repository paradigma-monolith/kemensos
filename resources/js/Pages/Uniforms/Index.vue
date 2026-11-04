<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
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
  UserCheck
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
});

const activeTab = ref('SISWA');
const searchQuery = ref('');
const filterGender = ref('');
const isUploadModalOpen = ref(false);
const isCopied = ref(false);
const fileInput = ref(null);
const selectedFileName = ref('');

// PIC Selection state
const selectedPicOption = ref('');
const customPicName = ref('');

// Form for Inertia file upload
const uploadForm = useForm({
  file: null,
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

// Active sheet data
const activeSheetRows = computed(() => {
  const sheetData = currentRecap.value[activeTab.value]?.data || [];
  return sheetData.filter((row) => {
    const matchesSearch = !searchQuery.value || 
      (row.nama_lengkap && row.nama_lengkap.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
      (row.konversi_vendor && row.konversi_vendor.toLowerCase().includes(searchQuery.value.toLowerCase())) ||
      (row.status && row.status.toLowerCase().includes(searchQuery.value.toLowerCase()));

    const matchesGender = !filterGender.value || row.jenis_kelamin === filterGender.value;

    return matchesSearch && matchesGender;
  });
});

// JSON representation matching exact required output
const formattedJson = computed(() => {
  return JSON.stringify(currentRecap.value, null, 2);
});

// Sheet counts
const getSheetCount = (sheetKey) => {
  return currentRecap.value[sheetKey]?.data?.length || 0;
};

// Handle file selection
const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    uploadForm.file = file;
    selectedFileName.value = file.name;
  }
};

// Submit upload form
const submitUpload = () => {
  if (!uploadForm.file) return;

  let finalPic = selectedPicOption.value;
  if (finalPic === '__custom__') {
    finalPic = customPicName.value ? customPicName.value.trim() : '';
  } else if (finalPic === '__auto__') {
    finalPic = '';
  }
  uploadForm.pic_nama = finalPic;

  uploadForm.post('/uniforms/parse', {
    preserveScroll: true,
    onSuccess: () => {
      isUploadModalOpen.value = false;
      uploadForm.reset();
      selectedFileName.value = '';
      selectedPicOption.value = '';
      customPicName.value = '';
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
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex flex-col font-sans transition-colors duration-300">
    <Head title="Rekapitulasi Ukuran Seragam - Sepatu SR" />

    <!-- Top Navigation Header -->
    <HybridNavbar currentRoute="uniforms" />

    <!-- Main Content Container -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
      <!-- Breadcrumb & Status Tag -->
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
        <div class="flex items-center gap-2.5 shrink-0">
          <button
            type="button"
            @click="isUploadModalOpen = true"
            class="px-4 py-2.5 rounded-xl font-semibold text-sm bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-600/20 hover:shadow-indigo-600/30 transition-all flex items-center gap-2 cursor-pointer"
          >
            <UploadCloud class="w-4 h-4" />
            <span>Import Excel Seragam</span>
          </button>

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
            <span>Unduh</span>
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
          <p class="font-semibold">Berhasil Mengimpor Berkas</p>
          <p class="mt-0.5 text-emerald-700 dark:text-emerald-400">{{ $page.props.flash.success }}</p>
        </div>
      </div>

      <!-- Overview Metric Cards (5 Cards) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- School Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-xs">
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Satuan Pendidikan</span>
            <SchoolIcon class="w-4 h-4 text-indigo-500" />
          </div>
          <div class="mt-2">
            <span class="text-base font-bold text-slate-900 dark:text-white truncate block" :title="schoolName">
              {{ schoolName }}
            </span>
            <span class="text-[11px] text-indigo-600 dark:text-indigo-400 font-mono mt-0.5 block">
              Awalan SRD / SRMP / SRMA
            </span>
          </div>
        </div>

        <!-- PIC Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-xs">
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Penanggung Jawab (PIC)</span>
            <UserCheck class="w-4 h-4 text-emerald-500" />
          </div>
          <div class="mt-2">
            <span v-if="picNama" class="text-base font-bold text-slate-900 dark:text-white truncate block flex items-center gap-1.5" :title="picNama">
              <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
              {{ picNama }}
            </span>
            <span v-else class="text-sm text-slate-400 italic block">
              Belum ditentukan
            </span>
            <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 block">
              {{ picNama ? 'PIC Terkonfirmasi' : 'Pilih saat import' }}
            </span>
          </div>
        </div>

        <!-- File Name Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-xs">
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Berkas Sumber</span>
            <FileSpreadsheet class="w-4 h-4 text-emerald-500" />
          </div>
          <div class="mt-2">
            <span class="text-sm font-semibold text-slate-800 dark:text-slate-200 truncate block font-mono" :title="fileName">
              {{ fileName }}
            </span>
            <span class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5 block">
              Header 1-2 | Data Baris 3
            </span>
          </div>
        </div>

        <!-- Total Recipients Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-xs">
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Total Penerima Seragam</span>
            <Users class="w-4 h-4 text-purple-500" />
          </div>
          <div class="mt-2 flex items-baseline gap-2">
            <span class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
              {{ totalRows.toLocaleString('id-ID') }}
            </span>
            <span class="text-xs text-slate-500 dark:text-slate-400">orang</span>
          </div>
        </div>

        <!-- Category Count Card -->
        <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-xs">
          <div class="flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
            <span>Kategori Sheet</span>
            <Sparkles class="w-4 h-4 text-amber-500" />
          </div>
          <div class="mt-2">
            <span class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">
              9
            </span>
            <span class="text-xs text-slate-500 dark:text-slate-400 ml-1">Kategori</span>
          </div>
        </div>
      </div>

      <!-- Categories Tab Navigation -->
      <div class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-2 shadow-xs">
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0 scrollbar-thin">
          <button
            v-for="s in sheetsList"
            :key="s.key"
            type="button"
            @click="activeTab = s.key"
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
            <span v-if="activeTab === 'GURU BARU'" class="text-[11px] px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 font-medium border border-amber-200 dark:border-amber-800/60">
              *Sheet GURU tunggal otomatis dipetakan ke sini
            </span>
            <span v-if="activeTab === 'TENDIK BARU'" class="text-[11px] px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 font-medium border border-amber-200 dark:border-amber-800/60">
              *Sheet TENDIK tunggal otomatis dipetakan ke sini
            </span>
            <span v-if="activeTab === 'SATPAM'" class="text-[11px] px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-medium border border-indigo-200 dark:border-indigo-800/60">
              *Dilengkapi Ukuran Sepatu PDH
            </span>
          </div>

          <!-- Filters -->
          <div class="flex items-center gap-2">
            <div class="relative">
              <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Cari nama / status..."
                class="pl-9 pr-3 py-1.5 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 text-slate-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-indigo-500/20"
              />
            </div>

            <select
              v-model="filterGender"
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
                v-for="(row, idx) in activeSheetRows"
                :key="idx"
                class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors"
              >
                <td class="py-2 px-3 text-center font-mono text-slate-500 dark:text-slate-400">
                  {{ row.no ?? idx + 1 }}
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
              <tr v-if="activeSheetRows.length === 0">
                <td :colspan="activeTab === 'SATPAM' ? 11 : 10" class="py-12 text-center text-slate-400 dark:text-slate-500">
                  <div class="flex flex-col items-center justify-center gap-2">
                    <AlertCircle class="w-8 h-8 text-slate-300 dark:text-slate-600" />
                    <p class="text-sm font-medium">Tidak ada data untuk sheet "{{ activeTab }}".</p>
                    <p class="text-xs text-slate-400">Silakan unggah berkas Excel ukuran seragam untuk mengisi data rekapitulasi.</p>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Raw JSON View Section -->
      <div v-else class="bg-white dark:bg-slate-900 border border-slate-200/80 dark:border-slate-800/80 rounded-2xl p-4 shadow-xs space-y-3">
        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-800 pb-3">
          <div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <FileCode class="w-4 h-4 text-indigo-500" />
              <span>Format JSON Rekapitulasi Seragam</span>
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Output terstandarisasi untuk 9 sheets lengkap dengan objek struktur dan array data.
            </p>
          </div>

          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="copyJson"
              class="px-3 py-1.5 rounded-xl text-xs font-medium bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all flex items-center gap-1.5 cursor-pointer"
            >
              <Check v-if="isCopied" class="w-3.5 h-3.5 text-emerald-500" />
              <Copy v-else class="w-3.5 h-3.5 text-slate-500" />
              <span>{{ isCopied ? 'Tersalin' : 'Salin JSON' }}</span>
            </button>
            <button
              type="button"
              @click="downloadJson"
              class="px-3 py-1.5 rounded-xl text-xs font-medium bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 transition-all flex items-center gap-1.5 cursor-pointer"
            >
              <Download class="w-3.5 h-3.5" />
              <span>Unduh .json</span>
            </button>
          </div>
        </div>

        <pre class="bg-slate-950 text-emerald-400 p-4 rounded-xl text-xs font-mono overflow-x-auto max-h-[600px] leading-relaxed select-all">{{ formattedJson }}</pre>
      </div>
    </main>

    <!-- Upload Modal -->
    <div
      v-if="isUploadModalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-xs"
    >
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl max-w-lg w-full p-6 shadow-2xl relative">
        <button
          type="button"
          @click="isUploadModalOpen = false"
          class="absolute top-4 right-4 p-1.5 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
        >
          <X class="w-5 h-5" />
        </button>

        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
            <UploadCloud class="w-5 h-5" />
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">Import Rekapitulasi Seragam</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Pilih berkas Excel (.xlsx / .xls) ukuran seragam SR.</p>
          </div>
        </div>

        <form @submit.prevent="submitUpload" class="mt-6 space-y-4">
          <!-- Pilihan Dropdown PIC (Penanggung Jawab) -->
          <div class="space-y-1.5">
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
              Setiap data ukuran seragam yang di-import pada proses ini akan ditugaskan ke PIC yang dipilih.
            </p>
          </div>

          <!-- Dropzone -->
          <div
            @click="$refs.fileInput.click()"
            class="border-2 border-dashed border-slate-200 dark:border-slate-800 hover:border-indigo-500 dark:hover:border-indigo-500 rounded-2xl p-6 text-center cursor-pointer bg-slate-50/50 dark:bg-slate-950/50 transition-colors"
          >
            <input
              ref="fileInput"
              type="file"
              accept=".xlsx,.xls,.csv"
              @change="handleFileChange"
              class="hidden"
            />
            <FileSpreadsheet class="w-10 h-10 text-indigo-500 mx-auto" />
            <p class="text-sm font-semibold text-slate-700 dark:text-slate-300 mt-2">
              {{ selectedFileName || 'Klik untuk memilih atau seret berkas ke sini' }}
            </p>
            <p class="text-xs text-slate-400 mt-1">
              Mendukung: Data Ukuran Seragam SRMA 13 Bekasi (OK).xlsx, dsb.
            </p>
          </div>

          <!-- Notes Callout -->
          <div class="p-3.5 rounded-xl bg-slate-100/70 dark:bg-slate-800/50 border border-slate-200/80 dark:border-slate-700/60 text-xs space-y-1 text-slate-600 dark:text-slate-400">
            <p class="font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
              <Info class="w-3.5 h-3.5 text-indigo-500" />
              <span>Aturan Pemrosesan Otomatis:</span>
            </p>
            <ul class="list-disc list-inside space-y-0.5 text-[11px]">
              <li>Nama sekolah dideteksi otomatis dari awalan <strong>SRD</strong>, <strong>SRMP</strong>, atau <strong>SRMA</strong> pada nama berkas.</li>
              <li>Sheet dengan nama <strong>GURU</strong> saja otomatis masuk ke sheet <strong>GURU BARU</strong>.</li>
              <li>Sheet dengan nama <strong>TENDIK</strong> saja otomatis masuk ke sheet <strong>TENDIK BARU</strong>.</li>
            </ul>
          </div>

          <!-- Submit Buttons -->
          <div class="flex items-center justify-end gap-2.5 pt-2">
            <button
              type="button"
              @click="isUploadModalOpen = false"
              class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors cursor-pointer"
            >
              Batal
            </button>
            <button
              type="submit"
              :disabled="!uploadForm.file || uploadForm.processing"
              class="px-4 py-2 rounded-xl text-xs font-semibold bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm transition-all disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer flex items-center gap-2"
            >
              <UploadCloud class="w-4 h-4" />
              <span>{{ uploadForm.processing ? 'Memproses...' : 'Mulai Import' }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
