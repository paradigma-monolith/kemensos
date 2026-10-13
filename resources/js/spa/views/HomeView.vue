<script setup>
import { onMounted } from 'vue';
import { useProductStore } from '../stores/productStore';
import { Zap, Shield, Radio, Activity, HeartPulse, Database } from 'lucide-vue-next';

const store = useProductStore();

const getUrl = (path) => {
  if (typeof window !== 'undefined' && window.location.pathname.includes('/hybridlaravel/public')) {
    return `/hybridlaravel/public${path}`;
  }
  return path;
};

onMounted(async () => {
  await store.fetchStats();
  await store.fetchProducts();
});
</script>

<template>
  <div class="space-y-10">
    <!-- Hero Header -->
    <div class="relative bg-gradient-to-r from-emerald-950/40 via-teal-950/30 to-slate-900/60 border border-emerald-700/40 rounded-3xl p-8 backdrop-blur-sm">
      <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <div>
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-mono font-medium bg-emerald-950 text-emerald-300 border border-emerald-700/50 mb-3">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            <span>Traditional SPA Mode (Decoupled Vue 3 + REST API)</span>
          </div>
          <h1 class="text-3xl font-extrabold text-white tracking-tight">
            Single Page Application (SPA) Mandiri
          </h1>
          <p class="mt-2 text-slate-300 text-sm max-w-2xl">
            Aplikasi ini berjalan independen di browser menggunakan <strong class="text-emerald-300">Vue Router</strong> dan <strong class="text-emerald-300">Pinia</strong>. Seluruh interaksi data dilakukan melalui permintaan asinkron HTTP (Axios) ke endpoint Laravel REST API.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <router-link to="/products" class="px-5 py-2.5 rounded-xl font-semibold text-white bg-emerald-600 hover:bg-emerald-500 transition-all text-sm shadow-lg shadow-emerald-600/30">
            Kelola Produk (API) →
          </router-link>
          <router-link to="/api-explorer" class="px-5 py-2.5 rounded-xl font-semibold text-slate-300 bg-slate-900 hover:bg-slate-800 border border-slate-800 transition-all text-sm">
            REST API Explorer
          </router-link>
        </div>
      </div>
    </div>

    <!-- Live API Telemetry & Latency -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
      <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6">
        <div class="text-xs font-mono text-slate-400 uppercase tracking-wider">REST API Latency</div>
        <div class="text-3xl font-extrabold text-white mt-2 flex items-baseline gap-2">
          <span>{{ store.apiLatencyMs !== null ? `${store.apiLatencyMs}ms` : 'Mengukur...' }}</span>
          <span class="text-xs font-mono text-emerald-400">HTTP GET /api/v1/products</span>
        </div>
        <p class="text-xs text-slate-500 mt-1">Waktu roundtrip jaringan ke backend Laravel</p>
      </div>

      <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6">
        <div class="text-xs font-mono text-slate-400 uppercase tracking-wider">State Management</div>
        <div class="text-3xl font-extrabold text-emerald-300 mt-2">Pinia Store</div>
        <p class="text-xs text-slate-500 mt-1">Reactive client-side cache &amp; status filter</p>
      </div>

      <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-6">
        <div class="text-xs font-mono text-slate-400 uppercase tracking-wider">Database MySQL</div>
        <div class="text-3xl font-extrabold text-teal-300 mt-2">{{ store.products.length }} Record</div>
        <p class="text-xs text-slate-500 mt-1">Dimuat dari tabel products database MySQL</p>
      </div>
    </div>

    <!-- High-Performance Stack Cards for SPA -->
    <div class="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-8 backdrop-blur-sm">
      <div class="flex items-center justify-between mb-6">
        <div>
          <div class="inline-flex items-center gap-2 text-xs font-mono font-semibold text-amber-400 uppercase tracking-wider">
            <Activity class="w-4 h-4 animate-pulse" />
            <span>Enterprise Engine Status</span>
          </div>
          <h2 class="text-xl font-bold text-white mt-1">Integrasi Octane, Redis, Pulse, Sentry &amp; Reverb</h2>
          <p class="text-xs text-slate-400 mt-0.5">Informasi stack infrastruktur &amp; telemetri berkinerja tinggi yang aktif:</p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-mono bg-emerald-950 text-emerald-300 border border-emerald-800/60">
          Connected
        </span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-5">
        <!-- Octane -->
        <div class="bg-slate-950/70 border border-amber-900/40 rounded-2xl p-5 hover:border-amber-600/50 transition-all flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-amber-500/20 text-amber-400 flex items-center justify-center">
                  <Zap class="w-4 h-4" />
                </div>
                <h3 class="text-sm font-bold text-white">Laravel Octane</h3>
              </div>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-amber-950 text-amber-300 border border-amber-800">
                {{ store.stats?.infrastructure?.octane?.server || 'RoadRunner' }}
              </span>
            </div>
            <p class="text-xs text-slate-300 leading-relaxed">
              In-memory execution via binary RoadRunner. Request API diproses tanpa me-reboot framework.
            </p>
          </div>
          <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-[11px] font-mono text-slate-400">
            <span>Concurrency</span>
            <span class="text-amber-400 font-semibold">In-Memory RAM</span>
          </div>
        </div>

        <!-- Redis -->
        <div class="bg-slate-950/70 border border-red-900/40 rounded-2xl p-5 hover:border-red-600/50 transition-all flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-red-500/20 text-red-400 flex items-center justify-center">
                  <Database class="w-4 h-4" />
                </div>
                <h3 class="text-sm font-bold text-white">Redis Store</h3>
              </div>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-red-950 text-red-300 border border-red-800">
                {{ store.stats?.infrastructure?.redis?.status || 'Predis' }}
              </span>
            </div>
            <p class="text-xs text-slate-300 leading-relaxed">
              Penyimpanan data in-memory cepat untuk caching, session sharing, antrean queue, dan event pub/sub.
            </p>
          </div>
          <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-[11px] font-mono text-slate-400">
            <span>Driver</span>
            <span class="text-red-400 font-semibold uppercase">{{ store.stats?.infrastructure?.redis?.client || 'predis' }}</span>
          </div>
        </div>

        <!-- Pulse -->
        <div class="bg-slate-950/70 border border-rose-900/40 rounded-2xl p-5 hover:border-rose-600/50 transition-all flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-rose-500/20 text-rose-400 flex items-center justify-center">
                  <HeartPulse class="w-4 h-4" />
                </div>
                <h3 class="text-sm font-bold text-white">Laravel Pulse</h3>
              </div>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-rose-950 text-rose-300 border border-rose-800">
                Dashboard
              </span>
            </div>
            <p class="text-xs text-slate-300 leading-relaxed">
              Memantau beban server (CPU/RAM), slow queries, dan queue jobs secara real-time.
            </p>
          </div>
          <div class="mt-4 pt-3 border-t border-slate-800">
            <a :href="getUrl('/pulse')" target="_blank"
              class="flex items-center justify-between p-1.5 rounded-lg bg-rose-950/50 border border-rose-800/60 text-[11px] text-rose-300 hover:text-white transition-colors font-medium">
              <span>Buka Pulse</span>
              <span>↗</span>
            </a>
          </div>
        </div>

        <!-- Sentry -->
        <div class="bg-slate-950/70 border border-purple-900/40 rounded-2xl p-5 hover:border-purple-600/50 transition-all flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-purple-500/20 text-purple-400 flex items-center justify-center">
                  <Shield class="w-4 h-4" />
                </div>
                <h3 class="text-sm font-bold text-white">Sentry Telemetry</h3>
              </div>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-purple-950 text-purple-300 border border-purple-800">
                Full-Stack
              </span>
            </div>
            <p class="text-xs text-slate-300 leading-relaxed">
              Pelacakan error real-time, Session Replay, dan Browser Tracing untuk client Vue &amp; backend Laravel.
            </p>
          </div>
          <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-[11px] font-mono text-slate-400">
            <span>Monitoring</span>
            <span class="text-purple-400 font-semibold">Backend + SPA</span>
          </div>
        </div>

        <!-- Reverb -->
        <div class="bg-slate-950/70 border border-cyan-900/40 rounded-2xl p-5 hover:border-cyan-600/50 transition-all flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-cyan-500/20 text-cyan-400 flex items-center justify-center">
                  <Radio class="w-4 h-4" />
                </div>
                <h3 class="text-sm font-bold text-white">Laravel Reverb</h3>
              </div>
              <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-cyan-950 text-cyan-300 border border-cyan-800">
                WebSockets
              </span>
            </div>
            <p class="text-xs text-slate-300 leading-relaxed">
              Server WebSocket first-party berkecepatan tinggi, terhubung dengan modul client Echo di browser.
            </p>
          </div>
          <div class="mt-4 pt-3 border-t border-slate-800 flex items-center justify-between text-[11px] font-mono text-slate-400">
            <span>Echo Client</span>
            <span class="text-cyan-400 font-semibold">:8080 (Active)</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Architectural Deep Dive Cards -->
    <div class="bg-slate-900/60 border border-slate-800/80 rounded-3xl p-8">
      <h2 class="text-xl font-bold text-white mb-6">Bagaimana Tradisional SPA Beroperasi di Laravel?</h2>
      
      <div class="grid md:grid-cols-3 gap-6">
        <div class="bg-slate-950/70 border border-slate-800/80 rounded-2xl p-5">
          <div class="w-8 h-8 rounded-lg bg-emerald-600/20 text-emerald-400 flex items-center justify-center font-bold text-sm mb-3">1</div>
          <h3 class="text-base font-semibold text-white mb-2">Blade Catch-All Shell</h3>
          <p class="text-xs text-slate-400 leading-relaxed">
            Laravel merespons rute <code class="text-emerald-300 bg-slate-900 px-1 py-0.5 rounded">/spa/{any}</code> dengan mengembalikan template Blade tunggal berisi kontainer <code class="text-emerald-300 bg-slate-900 px-1 py-0.5 rounded">&lt;div id="spa-app"&gt;</code>.
          </p>
        </div>

        <div class="bg-slate-950/70 border border-slate-800/80 rounded-2xl p-5">
          <div class="w-8 h-8 rounded-lg bg-emerald-600/20 text-emerald-400 flex items-center justify-center font-bold text-sm mb-3">2</div>
          <h3 class="text-base font-semibold text-white mb-2">Client-Side Routing</h3>
          <p class="text-xs text-slate-400 leading-relaxed">
            Vue Router mengambil kendali penuh atas navigasi browser tanpa reload halaman (HTML5 History API) menggunakan prefix dasar <code class="text-emerald-300 bg-slate-900 px-1 py-0.5 rounded">/spa/</code>.
          </p>
        </div>

        <div class="bg-slate-950/70 border border-slate-800/80 rounded-2xl p-5">
          <div class="w-8 h-8 rounded-lg bg-emerald-600/20 text-emerald-400 flex items-center justify-center font-bold text-sm mb-3">3</div>
          <h3 class="text-base font-semibold text-white mb-2">Decoupled REST API</h3>
          <p class="text-xs text-slate-400 leading-relaxed">
            Komponen Vue melakukan mutasi data via Axios ke endpoint <code class="text-emerald-300 bg-slate-900 px-1 py-0.5 rounded">/api/v1/*</code>. Endpoint ini juga dapat dikonsumsi oleh aplikasi mobile Android/iOS.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
