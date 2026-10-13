<script setup>
import { ref, onMounted } from 'vue';
import { useProductStore } from '../stores/productStore';
import { 
  Plus, 
  Search, 
  FileSpreadsheet, 
  FileText, 
  Trash2, 
  AlertCircle, 
  RefreshCw, 
  X, 
  Boxes,
  ShoppingBag,
  Sparkles
} from 'lucide-vue-next';

const store = useProductStore();
const showModal = ref(false);
const toastMessage = ref('');

const newProduct = ref({
  name: '',
  category: 'Hardware',
  price: '',
  stock: 15,
  description: '',
});

const getUrl = (path) => {
  if (typeof window !== 'undefined' && window.location.pathname.includes('/hybridlaravel/public')) {
    return `/hybridlaravel/public${path}`;
  }
  return path;
};

onMounted(() => {
  store.fetchProducts();
});

const handleCategoryChange = (cat) => {
  store.selectedCategory = cat;
  store.fetchProducts();
};

const handleSearch = () => {
  store.fetchProducts();
};

const triggerToast = (msg) => {
  toastMessage.value = msg;
  setTimeout(() => {
    toastMessage.value = '';
  }, 4000);
};

const submitForm = async () => {
  const result = await store.addProduct({
    name: newProduct.value.name,
    category: newProduct.value.category,
    price: parseFloat(newProduct.value.price),
    stock: parseInt(newProduct.value.stock),
    description: newProduct.value.description,
  });

  if (result.success) {
    showModal.value = false;
    newProduct.value = { name: '', category: 'Hardware', price: '', stock: 15, description: '' };
    triggerToast('Produk baru berhasil disimpan ke MySQL via REST API endpoint!');
  } else {
    alert(result.error || 'Gagal menambahkan produk.');
  }
};

const removeProduct = async (id) => {
  if (confirm('Yakin ingin menghapus produk ini melalui REST API?')) {
    const res = await store.deleteProduct(id);
    if (res.success) {
      triggerToast('Produk berhasil dihapus dari database via DELETE /api/v1/products/' + id);
    }
  }
};
</script>

<template>
  <div class="space-y-8 font-sans">
    <!-- Toast Banner -->
    <transition enter-active-class="transition duration-300 ease-out" enter-from-class="transform -translate-y-2 opacity-0" enter-to-class="transform translate-y-0 opacity-100">
      <div v-if="toastMessage" class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/90 border border-emerald-300 dark:border-emerald-700/80 text-emerald-800 dark:text-emerald-200 flex items-center justify-between shadow-lg">
        <div class="flex items-center gap-3">
          <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
          <span class="font-medium text-sm">{{ toastMessage }}</span>
        </div>
        <span class="text-xs font-mono text-emerald-600 dark:text-emerald-400">Axios HTTP 200 OK</span>
      </div>
    </transition>

    <!-- Header & Action -->
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
      <div>
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-mono font-medium bg-emerald-100 dark:bg-emerald-950 text-emerald-800 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800 mb-2">
          <span>Decoupled API Client &bull; Pinia Reactive State</span>
        </div>
        <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
          <ShoppingBag class="w-7 h-7 text-emerald-600 dark:text-emerald-400" />
          <span>Katalog Produk (SPA REST API)</span>
        </h1>
        <p class="text-slate-600 dark:text-slate-400 text-sm mt-1">
          Data dikonsumsi secara client-side melalui endpoint <code class="text-emerald-700 dark:text-emerald-300 font-mono font-medium">/api/v1/products</code> menggunakan Axios.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <a :href="getUrl('/api/v1/exports/products/excel')" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-slate-200 dark:border-emerald-800/60 hover:bg-slate-100 dark:hover:bg-emerald-900/60 transition-all flex items-center gap-1.5 shadow-sm" title="Export Spreadsheet Excel">
          <FileSpreadsheet class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" />
          <span>Export Excel</span>
        </a>
        <a :href="getUrl('/api/v1/exports/products/pdf')" class="px-3.5 py-2 rounded-xl text-xs font-semibold bg-white dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-slate-200 dark:border-rose-800/60 hover:bg-slate-100 dark:hover:bg-rose-900/60 transition-all flex items-center gap-1.5 shadow-sm" title="Download PDF">
          <FileText class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400" />
          <span>Cetak PDF</span>
        </a>
        <button @click="showModal = true" class="px-4 py-2 rounded-xl font-semibold text-white bg-emerald-600 hover:bg-emerald-500 transition-all text-xs shadow-lg shadow-emerald-600/30 flex items-center gap-1.5 cursor-pointer">
          <Plus class="w-4 h-4" />
          <span>+ Tambah Produk (API)</span>
        </button>
      </div>
    </div>

    <!-- Error Alert Banner -->
    <div v-if="store.error" class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/80 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-200 flex items-center justify-between shadow-sm">
      <div class="flex items-center gap-3">
        <AlertCircle class="w-5 h-5 text-rose-500 flex-shrink-0" />
        <div>
          <span class="text-sm font-semibold block">Gagal memuat data produk</span>
          <span class="text-xs text-rose-600 dark:text-rose-300">{{ store.error }}</span>
        </div>
      </div>
      <button @click="store.fetchProducts()" class="px-3 py-1.5 rounded-xl bg-rose-600 text-white text-xs font-semibold hover:bg-rose-500 flex items-center gap-1.5 transition-colors cursor-pointer">
        <RefreshCw class="w-3.5 h-3.5" />
        <span>Coba Lagi</span>
      </button>
    </div>

    <!-- Filter & Category Tabs -->
    <div class="bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 rounded-2xl p-4 flex flex-col md:flex-row items-center justify-between gap-4 shadow-sm dark:shadow-none">
      <!-- Search Input -->
      <div class="relative w-full md:w-96">
        <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
        <input 
          v-model="store.searchQuery" 
          @input="handleSearch"
          type="text" 
          placeholder="Cari secara reaktif... (nama / deskripsi)" 
          class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:border-emerald-500 transition-colors"
        />
      </div>

      <!-- Category Filter Pills -->
      <div class="flex flex-wrap items-center gap-2 w-full md:w-auto">
        <button 
          v-for="cat in ['All', 'Hardware', 'Software', 'Cloud Service']" 
          :key="cat"
          @click="handleCategoryChange(cat)"
          class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all cursor-pointer"
          :class="store.selectedCategory === cat 
            ? 'bg-emerald-600 text-white shadow-sm' 
            : 'bg-slate-100 dark:bg-slate-950 text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white border border-slate-200 dark:border-slate-800'">
          {{ cat }}
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="store.loading" class="text-center py-16 bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/80 rounded-3xl p-8 shadow-sm dark:shadow-none">
      <div class="inline-block w-8 h-8 border-4 border-emerald-500/30 border-t-emerald-500 rounded-full animate-spin"></div>
      <p class="mt-4 text-xs font-mono text-slate-500 dark:text-slate-400">Memuat data dari REST API...</p>
    </div>

    <!-- Empty State -->
    <div v-else-if="store.filteredProducts.length === 0" class="py-16 text-center bg-white dark:bg-slate-900/40 border border-slate-200 dark:border-slate-800/80 rounded-3xl p-8 backdrop-blur-sm shadow-sm dark:shadow-none">
      <div class="w-16 h-16 rounded-2xl bg-emerald-50 dark:bg-slate-800 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mx-auto mb-4">
        <Boxes class="w-8 h-8" />
      </div>
      <h3 class="text-lg font-bold text-slate-900 dark:text-white">Tidak Ada Produk Ditemukan</h3>
      <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 max-w-md mx-auto">
        {{ store.searchQuery || store.selectedCategory !== 'All' ? 'Tidak ada produk yang cocok dengan kata kunci atau filter saat ini.' : 'Belum ada produk yang tersimpan di basis data MySQL.' }}
      </p>
      <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
        <button 
          v-if="store.searchQuery || store.selectedCategory !== 'All'" 
          @click="store.searchQuery = ''; store.selectedCategory = 'All'; store.fetchProducts();" 
          class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-all cursor-pointer"
        >
          Reset Filter
        </button>
        <button 
          @click="showModal = true" 
          class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-500 transition-all shadow-md shadow-emerald-600/30 flex items-center gap-1.5 cursor-pointer"
        >
          <Plus class="w-4 h-4" />
          <span>Tambah Produk Baru</span>
        </button>
      </div>
    </div>

    <!-- Products Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="item in store.filteredProducts" :key="item.id" class="bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800/80 rounded-2xl p-6 hover:border-emerald-500/50 hover:shadow-lg transition-all group flex flex-col justify-between shadow-sm dark:shadow-none">
        <div>
          <div class="flex items-start justify-between gap-3 mb-3">
            <span class="text-xs font-mono px-2.5 py-1 rounded-lg bg-emerald-50 dark:bg-slate-950 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-slate-800">
              {{ item.category }}
            </span>
            <span class="text-xs font-mono text-slate-400 dark:text-slate-500">API ID: #{{ item.id }}</span>
          </div>

          <h3 class="text-lg font-bold text-slate-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-300 transition-colors">
            {{ item.name }}
          </h3>
          
          <p class="text-xs text-slate-600 dark:text-slate-400 mt-2 line-clamp-2 leading-relaxed">
            {{ item.description || 'Tidak ada deskripsi.' }}
          </p>
        </div>

        <div class="mt-6 pt-4 border-t border-slate-100 dark:border-slate-800/80 flex items-center justify-between">
          <div>
            <div class="text-[11px] text-slate-400 dark:text-slate-500">Harga &amp; Stok</div>
            <div class="text-lg font-bold text-slate-900 dark:text-white">
              ${{ Number(item.price).toFixed(2) }}
              <span class="text-xs font-normal text-slate-500 dark:text-slate-400">({{ item.stock }} pcs)</span>
            </div>
          </div>

          <button @click="removeProduct(item.id)" class="px-3 py-1.5 rounded-lg text-xs font-medium text-rose-600 dark:text-rose-400 hover:text-white hover:bg-rose-600/90 dark:hover:bg-rose-600/30 border border-rose-200 dark:border-rose-900/40 transition-colors cursor-pointer flex items-center gap-1">
            <Trash2 class="w-3.5 h-3.5" />
            <span>Hapus</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Create Product Modal -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl w-full max-w-lg p-6 sm:p-8 shadow-2xl relative text-slate-900 dark:text-white">
        <div class="flex items-center justify-between mb-6">
          <div>
            <h3 class="text-xl font-bold text-slate-900 dark:text-white">Tambah Produk via REST API</h3>
            <p class="text-xs font-mono text-emerald-600 dark:text-emerald-400 mt-0.5">POST /api/v1/products</p>
          </div>
          <button @click="showModal = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 cursor-pointer">
            <X class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitForm" class="space-y-4">
          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Nama Produk</label>
            <input v-model="newProduct.name" type="text" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500" placeholder="e.g. Wireless Ergonomic Mouse" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Kategori</label>
              <select v-model="newProduct.category" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500">
                <option value="Hardware">Hardware</option>
                <option value="Software">Software</option>
                <option value="Cloud Service">Cloud Service</option>
                <option value="Gadget">Gadget</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Stok Unit</label>
              <input v-model="newProduct.stock" type="number" min="0" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500" />
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Harga (USD)</label>
            <input v-model="newProduct.price" type="number" step="0.01" min="0" required class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500" placeholder="149.00" />
          </div>

          <div>
            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Deskripsi</label>
            <textarea v-model="newProduct.description" rows="3" class="w-full bg-slate-50 dark:bg-slate-950 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2 text-sm text-slate-900 dark:text-white focus:outline-none focus:border-emerald-500" placeholder="Spesifikasi produk via API..."></textarea>
          </div>

          <div class="pt-4 flex items-center justify-end gap-3">
            <button type="button" @click="showModal = false" class="px-4 py-2 rounded-xl text-sm font-medium text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white cursor-pointer">
              Batal
            </button>
            <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-500 transition-all shadow-lg shadow-emerald-600/30 cursor-pointer">
              Kirim Payload API
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
