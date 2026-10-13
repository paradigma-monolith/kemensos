<script setup>
import { ref } from 'vue';
import axios from 'axios';

const activeUrl = ref('/api/v1/stats');
const responseData = ref(null);
const responseStatus = ref(null);
const responseTime = ref(null);
const isLoading = ref(false);

const endpoints = [
  { method: 'GET', url: '/api/v1/stats', label: 'Architecture & System Stats' },
  { method: 'GET', url: '/api/v1/products', label: 'All Products (JSON Array)' },
  { method: 'GET', url: '/api/v1/products?category=Hardware', label: 'Filter Hardware Category' },
  { method: 'GET', url: '/up', label: 'Laravel Health Check' },
];

const sendRequest = async (url) => {
  activeUrl.value = url;
  isLoading.value = true;
  responseData.value = null;
  responseStatus.value = null;
  responseTime.value = null;

  const start = performance.now();
  try {
    const res = await axios.get(url);
    responseTime.value = Math.round(performance.now() - start);
    responseStatus.value = `${res.status} ${res.statusText}`;
    responseData.value = res.data;
  } catch (err) {
    responseTime.value = Math.round(performance.now() - start);
    responseStatus.value = err.response ? `${err.response.status} ${err.response.statusText}` : 'Error';
    responseData.value = err.response ? err.response.data : { error: err.message };
  } finally {
    isLoading.value = false;
  }
};

// Initial request
sendRequest('/api/v1/stats');
</script>

<template>
  <div class="space-y-8">
    <!-- Header -->
    <div>
      <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-mono font-medium bg-emerald-950 text-emerald-300 border border-emerald-800 mb-2">
        <span>Interactive REST API Explorer</span>
      </div>
      <h1 class="text-3xl font-extrabold text-white tracking-tight">API Inspector &amp; Test Suite</h1>
      <p class="text-slate-400 text-sm mt-1">Uji endpoint REST API Laravel secara langsung dan periksa payload respon JSON yang dikirimkan ke Traditional SPA.</p>
    </div>

    <!-- Endpoint Selector Buttons -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
      <button 
        v-for="ep in endpoints" 
        :key="ep.url"
        @click="sendRequest(ep.url)"
        class="text-left p-4 rounded-2xl border transition-all"
        :class="activeUrl === ep.url 
          ? 'bg-emerald-950/70 border-emerald-500/80 shadow-lg shadow-emerald-900/30' 
          : 'bg-slate-900/60 border-slate-800 hover:border-slate-700'">
        <div class="flex items-center justify-between mb-2">
          <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-300">
            {{ ep.method }}
          </span>
          <span class="text-xs font-mono text-slate-500">200 OK</span>
        </div>
        <div class="text-xs font-mono text-slate-300 truncate">{{ ep.url }}</div>
        <div class="text-[11px] text-slate-400 mt-1">{{ ep.label }}</div>
      </button>
    </div>

    <!-- URL Bar & Action -->
    <div class="bg-slate-900/80 border border-slate-800 rounded-2xl p-3 flex items-center gap-3">
      <span class="px-3 py-1.5 rounded-lg bg-emerald-600/20 text-emerald-400 font-mono text-xs font-bold">
        GET
      </span>
      <input 
        v-model="activeUrl" 
        @keyup.enter="sendRequest(activeUrl)"
        type="text" 
        class="flex-1 bg-transparent font-mono text-sm text-white focus:outline-none"
      />
      <button 
        @click="sendRequest(activeUrl)"
        :disabled="isLoading"
        class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-medium text-xs transition-colors disabled:opacity-50">
        {{ isLoading ? 'Mengirim...' : 'Send Request' }}
      </button>
    </div>

    <!-- Response Payload Viewer -->
    <div class="bg-slate-900/80 border border-slate-800/80 rounded-3xl overflow-hidden shadow-2xl">
      <div class="px-6 py-4 bg-slate-950/80 border-b border-slate-800 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <span class="text-xs font-mono text-slate-400">Response Payload (application/json)</span>
          <span v-if="responseStatus" class="px-2.5 py-0.5 rounded-full text-xs font-mono font-bold bg-emerald-950 text-emerald-400 border border-emerald-800">
            {{ responseStatus }}
          </span>
        </div>
        <span v-if="responseTime !== null" class="text-xs font-mono text-slate-400">
          Latency: <strong class="text-white">{{ responseTime }} ms</strong>
        </span>
      </div>

      <div class="p-6 overflow-x-auto max-h-[500px]">
        <div v-if="isLoading" class="py-12 text-center text-slate-500 font-mono text-xs">
          Mengambil respon JSON...
        </div>
        <pre v-else class="font-mono text-xs text-emerald-300 leading-relaxed">{{ JSON.stringify(responseData, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>
