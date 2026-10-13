<script setup>
import { ref } from 'vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import HybridNavbar from '../../Components/HybridNavbar.vue';

const props = defineProps({
  products: {
    type: Array,
    required: true,
  },
  filters: {
    type: Object,
    default: () => ({ search: '' }),
  },
  meta: {
    type: Object,
    required: true,
  },
});

const page = usePage();
const search = ref(props.filters.search || '');
const showModal = ref(false);

const getUrl = (path) => {
  if (typeof window !== 'undefined' && window.location.pathname.includes('/hybridlaravel/public')) {
    return `/hybridlaravel/public${path}`;
  }
  return path;
};

const form = useForm({
  name: '',
  category: 'Hardware',
  price: '',
  stock: 10,
  description: '',
});

const handleSearch = () => {
  router.get(getUrl('/inertia/products'), { search: search.value }, {
    preserveState: true,
    replace: true,
  });
};

const submitProduct = () => {
  form.post(getUrl('/inertia/products'), {
    onSuccess: () => {
      form.reset();
      showModal.value = false;
    },
  });
};

const deleteProduct = (id) => {
  if (confirm('Yakin ingin menghapus produk ini melalui Inertia Bridge?')) {
    router.delete(getUrl(`/inertia/products/${id}`));
  }
};
</script>

<template>
  <Head title="Inertia.js Products Management" />

  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 selection:bg-indigo-500 selection:text-white transition-colors duration-200 overflow-x-clip">
    <HybridNavbar mode="inertia" currentRoute="products" />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10">
      <!-- Flash Alert Banner -->
      <transition enter-active-class="transition duration-300 ease-out" enter-from-class="transform -translate-y-2 opacity-0" enter-to-class="transform translate-y-0 opacity-100">
        <div v-if="page.props.flash?.success" class="mb-6 p-4 rounded-2xl bg-indigo-50 dark:bg-indigo-950/90 border border-indigo-200 dark:border-indigo-700/80 text-indigo-900 dark:text-indigo-200 flex items-center justify-between shadow-xs">
          <div class="flex items-center gap-3">
            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
            <span class="font-medium text-sm">{{ page.props.flash.success }}</span>
          </div>
          <span class="text-xs font-mono text-indigo-600 dark:text-indigo-400">Inertia Flash Notification</span>
        </div>
      </transition>

      <!-- Header & Actions -->
      <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 mb-8">
        <div>
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-mono font-medium bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 mb-2">
            <span>{{ meta.channel }}</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">Katalog Produk (Inertia CRUD)</h1>
          <p class="text-slate-500 dark:text-slate-400 text-sm mt-1">{{ meta.explanation }}</p>
        </div>

        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
          <!-- Export Buttons -->
          <a :href="getUrl('/exports/products/excel')" class="px-3 py-2 rounded-xl text-xs font-semibold bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/60 hover:bg-emerald-100 dark:hover:bg-emerald-900/60 transition-all flex items-center gap-1.5 shadow-2xs" title="Download Spreadsheet Excel">
            <span>Export Excel</span>
          </a>
          <a :href="getUrl('/exports/products/pdf')" class="px-3 py-2 rounded-xl text-xs font-semibold bg-rose-50 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800/60 hover:bg-rose-100 dark:hover:bg-rose-900/60 transition-all flex items-center gap-1.5 shadow-2xs" title="Download Laporan PDF">
            <span>Cetak PDF</span>
          </a>
          <button @click="showModal = true" class="px-4 py-2 rounded-xl font-semibold text-white bg-indigo-600 hover:bg-indigo-500 transition-all text-xs shadow-md shadow-indigo-600/20 flex items-center gap-1.5 cursor-pointer">
            <span>+ Tambah Produk Baru</span>
          </button>
        </div>
      </div>

      <!-- Filter Bar -->
      <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-2xl p-3.5 sm:p-4 mb-8 flex flex-col sm:flex-row items-center gap-3 sm:gap-4 shadow-sm">
        <div class="relative flex-1 w-full">
          <input 
            v-model="search" 
            @keyup.enter="handleSearch"
            type="text" 
            placeholder="Cari produk berdasarkan nama atau kategori... (tekan enter)" 
            class="w-full bg-slate-50 dark:bg-slate-950/80 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 dark:placeholder-slate-500 focus:outline-none focus:border-indigo-500 transition-colors"
          />
        </div>
        <button @click="handleSearch" class="w-full sm:w-auto px-5 py-2.5 bg-indigo-50 dark:bg-slate-800 hover:bg-indigo-100 dark:hover:bg-slate-700 text-indigo-700 dark:text-white rounded-xl text-sm font-semibold transition-colors cursor-pointer border border-indigo-200 dark:border-slate-700">
          Cari
        </button>
      </div>

      <!-- Products Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6">
        <div v-for="item in products" :key="item.id" class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-2xl p-5 sm:p-6 hover:border-indigo-500/50 transition-all group flex flex-col justify-between shadow-xs">
          <div>
            <div class="flex items-start justify-between gap-3 mb-3">
              <span class="text-xs font-mono px-2.5 py-1 rounded-lg bg-indigo-50 dark:bg-slate-950 text-indigo-700 dark:text-indigo-400 border border-indigo-200 dark:border-slate-800">
                {{ item.category }}
              </span>
              <span class="text-xs font-mono text-slate-400 dark:text-slate-500">ID: #{{ item.id }}</span>
            </div>

            <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-300 transition-colors">
              {{ item.name }}
            </h3>
            
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-2 line-clamp-2 leading-relaxed">
              {{ item.description || 'Tidak ada deskripsi.' }}
            </p>
          </div>

          <div class="mt-5 pt-4 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
            <div>
              <div class="text-[11px] font-mono text-slate-400">Harga &amp; Stok</div>
              <div class="text-base sm:text-lg font-extrabold text-slate-900 dark:text-white">
                ${{ Number(item.price).toFixed(2) }}
                <span class="text-xs font-normal text-slate-500 dark:text-slate-400">({{ item.stock }} pcs)</span>
              </div>
            </div>

            <button @click="deleteProduct(item.id)" class="px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 dark:text-rose-400 hover:text-white hover:bg-rose-600 dark:hover:bg-rose-600/40 bg-rose-50 dark:bg-transparent border border-rose-200 dark:border-rose-900/40 transition-colors cursor-pointer">
              Hapus
            </button>
          </div>
        </div>
      </div>

      <!-- Modal Create Product -->
      <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-lg p-6 sm:p-8 shadow-2xl relative text-slate-900 dark:text-white">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg sm:text-xl font-bold text-slate-900 dark:text-white">Tambah Produk (Inertia Form)</h3>
            <button @click="showModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white text-2xl cursor-pointer">&times;</button>
          </div>

          <form @submit.prevent="submitProduct" class="space-y-4">
            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Nama Produk</label>
              <input v-model="form.name" type="text" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition-colors" placeholder="e.g. Mechanical Keyboard" />
              <div v-if="form.errors.name" class="text-xs text-rose-500 mt-1">{{ form.errors.name }}</div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Kategori</label>
                <select v-model="form.category" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition-colors">
                  <option value="Hardware">Hardware</option>
                  <option value="Software">Software</option>
                  <option value="Cloud Service">Cloud Service</option>
                  <option value="Gadget">Gadget</option>
                </select>
              </div>

              <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Stok Unit</label>
                <input v-model="form.stock" type="number" min="0" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition-colors" />
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Harga (USD)</label>
              <input v-model="form.price" type="number" step="0.01" min="0" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition-colors" placeholder="99.99" />
              <div v-if="form.errors.price" class="text-xs text-rose-500 mt-1">{{ form.errors.price }}</div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Deskripsi</label>
              <textarea v-model="form.description" rows="3" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-indigo-500 transition-colors" placeholder="Deskripsi spesifikasi produk..."></textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3">
              <button type="button" @click="showModal = false" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-500 hover:text-slate-800 dark:hover:text-white cursor-pointer">
                Batal
              </button>
              <button type="submit" :disabled="form.processing" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-500 disabled:opacity-50 transition-all shadow-md shadow-indigo-600/30 cursor-pointer">
                {{ form.processing ? 'Menyimpan...' : 'Simpan Produk' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</template>
