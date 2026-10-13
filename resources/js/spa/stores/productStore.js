import { defineStore } from 'pinia';
import axios from 'axios';

export const useProductStore = defineStore('productStore', {
  state: () => ({
    products: [],
    stats: null,
    loading: false,
    error: null,
    apiLatencyMs: null,
    selectedCategory: 'All',
    searchQuery: '',
  }),

  getters: {
    filteredProducts: (state) => {
      return state.products.filter((item) => {
        const matchesCategory = state.selectedCategory === 'All' || item.category === state.selectedCategory;
        const matchesSearch = item.name.toLowerCase().includes(state.searchQuery.toLowerCase()) ||
          (item.description && item.description.toLowerCase().includes(state.searchQuery.toLowerCase()));
        return matchesCategory && matchesSearch;
      });
    },
    totalProductsCount: (state) => state.products.length,
    totalStockCount: (state) => state.products.reduce((acc, cur) => acc + (cur.stock || 0), 0),
  },

  actions: {
    async fetchProducts() {
      this.loading = true;
      this.error = null;
      const start = performance.now();
      try {
        const response = await axios.get('/api/v1/products', {
          params: {
            category: this.selectedCategory !== 'All' ? this.selectedCategory : undefined,
            search: this.searchQuery || undefined,
          },
        });
        this.products = response.data.data;
        this.apiLatencyMs = Math.round(performance.now() - start);
      } catch (err) {
        this.error = err.response?.data?.message || err.message;
      } finally {
        this.loading = false;
      }
    },

    async fetchStats() {
      try {
        const response = await axios.get('/api/v1/stats');
        this.stats = response.data;
      } catch (err) {
        console.error('Error fetching stats:', err);
      }
    },

    async addProduct(newProduct) {
      this.loading = true;
      this.error = null;
      try {
        const response = await axios.post('/api/v1/products', newProduct);
        this.products.unshift(response.data.data);
        return { success: true, data: response.data.data };
      } catch (err) {
        this.error = err.response?.data?.message || err.message;
        return { success: false, error: this.error };
      } finally {
        this.loading = false;
      }
    },

    async deleteProduct(id) {
      try {
        await axios.delete(`/api/v1/products/${id}`);
        this.products = this.products.filter((p) => p.id !== id);
        return { success: true };
      } catch (err) {
        this.error = err.response?.data?.message || err.message;
        return { success: false, error: this.error };
      }
    },
  },
});
