<script setup>
import { Head, Link } from '@inertiajs/vue3';
import HybridNavbar from '../Components/HybridNavbar.vue';
import { 
  Zap, 
  Shield, 
  Radio, 
  Activity, 
  HeartPulse, 
  Database,
  UserCheck,
  FileSpreadsheet,
  BookOpen,
  History,
  Bell,
  FileText,
  Lock
} from 'lucide-vue-next';

const props = defineProps({
  stats: {
    type: Object,
    required: true,
  },
});

const getUrl = (path) => {
  if (typeof window !== 'undefined') {
    const match = window.location.pathname.match(/^(\/[^\/]+\/public)/);
    if (match) return `${match[1]}${path}`;
    if (window.location.pathname.includes('/hybridlaravel/public')) {
      return `/hybridlaravel/public${path}`;
    }
  }
  return path;
};
</script>

<template>
  <Head title="Hybrid Architecture Hub" />

  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 selection:bg-indigo-500 selection:text-white transition-colors duration-200 overflow-x-clip">
    <!-- Hybrid Navbar -->
    <HybridNavbar mode="inertia" currentRoute="hub" />

    <!-- Hero Section -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-24">
      <!-- Glow Gradients -->
      <div class="relative overflow-hidden pointer-events-none">
        <div class="absolute -top-24 left-1/2 -translate-x-1/2 w-[700px] max-w-[100vw] h-[350px] bg-gradient-to-r from-indigo-600/20 via-purple-600/20 to-emerald-600/20 blur-3xl -z-10 rounded-full"></div>
      </div>

      <!-- Header Content -->
      <div class="text-center max-w-3xl mx-auto">
        <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-indigo-950/80 border border-indigo-700/50 text-indigo-300 mb-6 shadow-inner">
          <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
          <span>Sesi Aktif: {{ $page.props.auth?.user?.name || 'Admin' }} ({{ $page.props.auth?.user?.email || 'admin@hybrid.local' }}) &bull; MySQL Database</span>
        </div>

        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 dark:text-white">
          Dua Paradigma Modern dalam
          <span class="bg-gradient-to-r from-indigo-600 dark:from-indigo-400 via-purple-600 dark:via-purple-300 to-emerald-600 dark:to-emerald-400 bg-clip-text text-transparent"> Satu Monolith</span>
        </h1>

        <p class="mt-6 text-lg text-slate-600 dark:text-slate-300 leading-relaxed">
          Kombinasi fleksibel antara kecepatan <strong class="text-indigo-600 dark:text-indigo-400 font-semibold">Inertia.js Bridge</strong> (tanpa boilerplate API) dan keleluasaan <strong class="text-emerald-600 dark:text-emerald-400 font-semibold">Traditional SPA</strong> (decoupled client-side routing &amp; REST API) yang berjalan harmonis di atas <strong class="text-slate-900 dark:text-white font-semibold">MySQL</strong>.
        </p>

        <!-- CTA Buttons -->
        <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
          <a :href="getUrl('/inertia/dashboard')" class="px-6 py-3 rounded-xl font-semibold text-white bg-indigo-600 hover:bg-indigo-500 shadow-lg shadow-indigo-600/25 hover:shadow-indigo-600/40 transition-all flex items-center gap-2 group">
            <span>Uji Inertia.js Bridge</span>
            <span class="group-hover:translate-x-0.5 transition-transform">→</span>
          </a>

          <a :href="getUrl('/spa')" class="px-6 py-3 rounded-xl font-semibold text-white bg-emerald-600 hover:bg-emerald-500 shadow-lg shadow-emerald-600/25 hover:shadow-emerald-600/40 transition-all flex items-center gap-2 group">
            <span>Uji Traditional SPA (API)</span>
            <span class="group-hover:translate-x-0.5 transition-transform">→</span>
          </a>

          <a :href="getUrl('/api/v1/products')" target="_blank" class="px-5 py-3 rounded-xl font-mono text-sm text-slate-300 bg-slate-900 hover:bg-slate-800 border border-slate-800 hover:border-slate-700 transition-all flex items-center gap-2">
            <span>Inspect JSON /api/v1</span>
            <span class="text-xs text-slate-500">↗</span>
          </a>
        </div>
      </div>

      <!-- Live Database & Environment Status Pill Grid -->
      <div class="mt-16 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 backdrop-blur-sm">
          <div class="text-xs font-mono text-slate-400 uppercase tracking-wider">Database Driver</div>
          <div class="mt-2 text-xl font-bold text-white flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400"></span>
            <span>MySQL 8.0</span>
          </div>
          <div class="text-xs font-mono text-slate-500 mt-1">DB: {{ stats.database.database }}</div>
        </div>

        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 backdrop-blur-sm">
          <div class="text-xs font-mono text-slate-400 uppercase tracking-wider">Total Produk</div>
          <div class="mt-2 text-xl font-bold text-white">{{ stats.total_products }} Items</div>
          <div class="text-xs text-slate-400 mt-1">Stok Total: {{ stats.total_stock }} unit</div>
        </div>

        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 backdrop-blur-sm">
          <div class="text-xs font-mono text-slate-400 uppercase tracking-wider">Framework Stack</div>
          <div class="mt-2 text-xl font-bold text-indigo-300">Laravel {{ stats.environment.laravel_version }}</div>
          <div class="text-xs font-mono text-slate-500 mt-1">PHP {{ stats.environment.php_version }}</div>
        </div>

        <div class="bg-slate-900/60 border border-slate-800/80 rounded-2xl p-4 backdrop-blur-sm">
          <div class="text-xs font-mono text-slate-400 uppercase tracking-wider">Frontend Engine</div>
          <div class="mt-2 text-xl font-bold text-emerald-300">Vue 3 + Vite 7</div>
          <div class="text-xs text-slate-400 mt-1">Tailwind CSS 4</div>
        </div>
      </div>

      <!-- High-Performance Infrastructure & Telemetry Stack (Octane, Sentry, Reverb) -->
      <section class="mt-16">
        <div class="flex items-center justify-between mb-6">
          <div>
            <div class="inline-flex items-center gap-2 text-xs font-mono font-semibold text-amber-400 uppercase tracking-wider">
              <Activity class="w-4 h-4 animate-pulse" />
              <span>Next-Gen Enterprise Engine</span>
            </div>
            <h2 class="text-2xl font-bold text-white mt-1">Infrastruktur &amp; Telemetri Berperforma Tinggi</h2>
            <p class="text-xs text-slate-400 mt-0.5">Lima pilar performa modern yang terintegrasi aktif dalam aplikasi ini:</p>
          </div>
          <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-mono bg-slate-900 border border-slate-800 text-slate-400">
            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
            Production Ready
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-5">
          <!-- Card 1: Laravel Octane -->
          <div class="relative bg-gradient-to-b from-amber-950/30 to-slate-900/60 border border-amber-800/40 hover:border-amber-500/60 transition-all rounded-3xl p-6 backdrop-blur-sm group flex flex-col justify-between">
            <div>
              <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 group-hover:scale-105 transition-transform shadow-inner">
                  <Zap class="w-6 h-6" />
                </div>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-mono font-semibold flex items-center gap-1.5"
                  :class="stats.infrastructure?.octane?.in_octane ? 'bg-amber-950 text-amber-300 border border-amber-700/60' : 'bg-slate-950 text-slate-300 border border-slate-800'">
                  <span class="w-1.5 h-1.5 rounded-full" :class="stats.infrastructure?.octane?.in_octane ? 'bg-amber-400 animate-ping' : 'bg-emerald-400'"></span>
                  {{ stats.infrastructure?.octane?.in_octane ? 'Running' : 'Ready' }}
                </span>
              </div>

              <h3 class="text-lg font-bold text-white group-hover:text-amber-300 transition-colors flex items-center gap-2">
                Laravel Octane
                <span class="text-xs font-normal text-amber-400/80 font-mono">v2.x</span>
              </h3>
              <p class="text-xs text-amber-300/80 font-mono mt-0.5">RoadRunner Server (rr.exe)</p>
              <p class="text-xs text-slate-300 mt-3 leading-relaxed">
                Menjaga framework tetap hidup di RAM (*in-memory*). Menghilangkan bootstrap overhead dan latensi sub-millisecond.
              </p>
            </div>

            <div class="mt-5 space-y-2 font-mono text-[11px]">
              <div class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400">Driver</span>
                <span class="text-amber-300 font-semibold uppercase">{{ stats.infrastructure?.octane?.server || 'roadrunner' }}</span>
              </div>
              <div class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400">Concurrency</span>
                <span class="text-amber-300">In-Memory Pool</span>
              </div>
              <div class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400">Runner</span>
                <span class="text-amber-300/80 truncate">composer run octane</span>
              </div>
            </div>
          </div>

          <!-- Card 2: Redis In-Memory Store -->
          <div class="relative bg-gradient-to-b from-red-950/30 to-slate-900/60 border border-red-800/40 hover:border-red-500/60 transition-all rounded-3xl p-6 backdrop-blur-sm group flex flex-col justify-between">
            <div>
              <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-red-500/10 border border-red-500/30 flex items-center justify-center text-red-400 group-hover:scale-105 transition-transform shadow-inner">
                  <Database class="w-6 h-6" />
                </div>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-mono font-semibold flex items-center gap-1.5"
                  :class="stats.infrastructure?.redis?.connected ? 'bg-red-950 text-red-300 border border-red-700/60' : 'bg-slate-950 text-slate-300 border border-slate-800'">
                  <span class="w-1.5 h-1.5 rounded-full" :class="stats.infrastructure?.redis?.connected ? 'bg-red-400 animate-ping' : 'bg-emerald-400'"></span>
                  {{ stats.infrastructure?.redis?.status || 'Predis v3.7' }}
                </span>
              </div>

              <h3 class="text-lg font-bold text-white group-hover:text-red-300 transition-colors flex items-center gap-2">
                Redis Store
                <span class="text-xs font-normal text-red-400/80 font-mono">v7.x</span>
              </h3>
              <p class="text-xs text-red-300/80 font-mono mt-0.5">In-Memory Key-Value DB</p>
              <p class="text-xs text-slate-300 mt-3 leading-relaxed">
                Penyimpanan data in-memory super cepat untuk caching, session sharing, antrean queue, dan pub/sub event.
              </p>
            </div>

            <div class="mt-5 space-y-2 font-mono text-[11px]">
              <div class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400">Client Driver</span>
                <span class="text-red-300 font-semibold uppercase">{{ stats.infrastructure?.redis?.client || 'predis' }}</span>
              </div>
              <div class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400">Connection</span>
                <span class="text-red-300">{{ stats.infrastructure?.redis?.host || '127.0.0.1' }}:{{ stats.infrastructure?.redis?.port || 6379 }}</span>
              </div>
              <div class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400">Scope</span>
                <span class="text-red-300/80 truncate">Cache, Session, Queue</span>
              </div>
            </div>
          </div>

          <!-- Card 2: Laravel Pulse -->
          <div class="relative bg-gradient-to-b from-rose-950/30 to-slate-900/60 border border-rose-800/40 hover:border-rose-500/60 transition-all rounded-3xl p-6 backdrop-blur-sm group flex flex-col justify-between">
            <div>
              <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400 group-hover:scale-105 transition-transform shadow-inner">
                  <HeartPulse class="w-6 h-6" />
                </div>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-mono font-semibold flex items-center gap-1.5 bg-rose-950 text-rose-300 border border-rose-700/60">
                  <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-ping"></span>
                  Live Telemetry
                </span>
              </div>

              <h3 class="text-lg font-bold text-white group-hover:text-rose-300 transition-colors flex items-center gap-2">
                Laravel Pulse
                <span class="text-xs font-normal text-rose-400/80 font-mono">v1.x</span>
              </h3>
              <p class="text-xs text-rose-300/80 font-mono mt-0.5">Real-Time Performance Dashboard</p>
              <p class="text-xs text-slate-300 mt-3 leading-relaxed">
                Memantau performa aplikasi, slow queries database, beban CPU &amp; memori server, serta antrean queue secara real-time.
              </p>
            </div>

            <div class="mt-5 space-y-2 font-mono text-[11px]">
              <div class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400">Route URL</span>
                <span class="text-rose-300 font-semibold">/pulse</span>
              </div>
              <div class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400">Recorders</span>
                <span class="text-rose-300">Server, Queries, Jobs</span>
              </div>
              <a :href="getUrl('/pulse')" target="_blank"
                class="flex items-center justify-between p-2 rounded-xl bg-rose-950/60 hover:bg-rose-900/60 border border-rose-700/60 text-rose-300 hover:text-white transition-all group/btn font-sans text-xs font-semibold">
                <span>Buka Dashboard Pulse</span>
                <span class="group-hover/btn:translate-x-0.5 transition-transform">↗</span>
              </a>
            </div>
          </div>

          <!-- Card 3: Sentry Full-Stack -->
          <div class="relative bg-gradient-to-b from-purple-950/30 to-slate-900/60 border border-purple-800/40 hover:border-purple-500/60 transition-all rounded-3xl p-6 backdrop-blur-sm group flex flex-col justify-between">
            <div>
              <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center text-purple-400 group-hover:scale-105 transition-transform shadow-inner">
                  <Shield class="w-6 h-6" />
                </div>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-mono font-semibold flex items-center gap-1.5"
                  :class="stats.infrastructure?.sentry?.configured ? 'bg-purple-950 text-purple-300 border border-purple-700/60' : 'bg-slate-950 text-slate-400 border border-slate-800'">
                  <span class="w-1.5 h-1.5 rounded-full" :class="stats.infrastructure?.sentry?.configured ? 'bg-purple-400 animate-ping' : 'bg-emerald-400'"></span>
                  {{ stats.infrastructure?.sentry?.configured ? 'Live Telemetry' : 'Full-Stack Ready' }}
                </span>
              </div>

              <h3 class="text-lg font-bold text-white group-hover:text-purple-300 transition-colors flex items-center gap-2">
                Sentry Full-Stack
                <span class="text-xs font-normal text-purple-400/80 font-mono">v4 / v11</span>
              </h3>
              <p class="text-xs text-purple-300/80 font-mono mt-0.5">Laravel 12 + Vue 3 Client</p>
              <p class="text-xs text-slate-300 mt-3 leading-relaxed">
                Pelacakan exception otomatis di server &amp; browser, dilengkapi Browser Tracing dan Session Replay rekaman crash.
              </p>
            </div>

            <div class="mt-5 space-y-2 font-mono text-[11px]">
              <div class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400">Backend Scope</span>
                <span class="text-purple-300 font-semibold truncate">Exceptions &amp; Monolog</span>
              </div>
              <div class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400">Frontend Scope</span>
                <span class="text-purple-300">Replay &amp; Tracing</span>
              </div>
              <div class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400">Testing CLI</span>
                <span class="text-purple-300/80 truncate">php artisan sentry:test</span>
              </div>
            </div>
          </div>

          <!-- Card 4: Laravel Reverb -->
          <div class="relative bg-gradient-to-b from-cyan-950/30 to-slate-900/60 border border-cyan-800/40 hover:border-cyan-500/60 transition-all rounded-3xl p-6 backdrop-blur-sm group flex flex-col justify-between">
            <div>
              <div class="flex items-start justify-between mb-4">
                <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400 group-hover:scale-105 transition-transform shadow-inner">
                  <Radio class="w-6 h-6" />
                </div>
                <span class="px-2.5 py-1 rounded-full text-[11px] font-mono font-semibold flex items-center gap-1.5 bg-cyan-950 text-cyan-300 border border-cyan-700/60">
                  <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-ping"></span>
                  First-Party
                </span>
              </div>

              <h3 class="text-lg font-bold text-white group-hover:text-cyan-300 transition-colors flex items-center gap-2">
                Laravel Reverb
                <span class="text-xs font-normal text-cyan-400/80 font-mono">v1.x</span>
              </h3>
              <p class="text-xs text-cyan-300/80 font-mono mt-0.5">High-Speed WebSocket Server</p>
              <p class="text-xs text-slate-300 mt-3 leading-relaxed">
                Server WebSocket first-party dari Laravel untuk komunikasi real-time dua arah, terhubung ke modul Echo &amp; Pusher JS.
              </p>
            </div>

            <div class="mt-5 space-y-2 font-mono text-[11px]">
              <div class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400">Broadcaster</span>
                <span class="text-cyan-300 font-semibold uppercase">{{ stats.infrastructure?.reverb?.broadcaster || 'reverb' }}</span>
              </div>
              <div class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400">Socket Port</span>
                <span class="text-cyan-300">:{{ stats.infrastructure?.reverb?.port || 8080 }} (ws://)</span>
              </div>
              <div class="flex items-center justify-between p-2 rounded-xl bg-slate-950/60 border border-slate-800/80">
                <span class="text-slate-400">Runner</span>
                <span class="text-cyan-300/80 truncate">composer run reverb</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Sepatu SR: Import & Rekap Data Ukuran Sepatu Module -->
      <div class="mt-16 relative bg-gradient-to-r from-indigo-950/60 via-purple-950/40 to-slate-900/60 border border-indigo-700/50 hover:border-indigo-500 rounded-3xl p-8 backdrop-blur-md shadow-2xl transition-all group overflow-hidden">
        <div class="absolute -right-12 -top-12 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6 relative z-10">
          <div class="space-y-2 max-w-2xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-mono font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
              <FileSpreadsheet class="w-3.5 h-3.5 text-indigo-400" />
              <span>Modul Produksi Sepatu SR</span>
            </div>
            <h3 class="text-2xl font-extrabold text-white tracking-tight">
              Sistem Import &amp; Rekapitulasi Data Ukuran Sepatu
            </h3>
            <p class="text-sm text-slate-300 leading-relaxed">
              Dukungan multi-sekolah untuk membaca file Excel konfirmasi (Siswa, Guru Laki-laki, dan Guru Perempuan). Kalkulasi otomatis matriks ukuran sepatu (36–47), normalisasi data, serta export Excel &amp; PDF siap cetak untuk vendor.
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <a
              :href="getUrl('/shoes')"
              class="inline-flex items-center gap-2 px-6 py-3 rounded-2xl font-bold text-sm text-white bg-indigo-600 hover:bg-indigo-500 shadow-lg shadow-indigo-600/30 active:scale-95 transition-all"
            >
              <FileSpreadsheet class="w-4 h-4" />
              <span>Buka Modul Sepatu SR</span>
              <span>&rarr;</span>
            </a>
          </div>
        </div>
      </div>

      <!-- Interactive Architectural Comparison Cards -->
      <div class="mt-16 grid md:grid-cols-2 gap-8">
        <!-- Paradigm 1: Inertia.js Bridge -->
        <div class="relative bg-gradient-to-b from-indigo-950/30 to-slate-900/50 border border-indigo-900/50 hover:border-indigo-600/60 transition-all rounded-3xl p-8 backdrop-blur-sm group">
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-indigo-600/20 border border-indigo-500/30 flex items-center justify-center text-indigo-400 font-bold">
                IB
              </div>
              <div>
                <h3 class="text-xl font-bold text-white group-hover:text-indigo-300 transition-colors">Inertia.js Bridge</h3>
                <p class="text-xs text-indigo-300/80 font-mono">The Modern Monolith Experience</p>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-mono font-medium bg-indigo-950 text-indigo-300 border border-indigo-800">
              Active: /inertia/*
            </span>
          </div>

          <p class="text-slate-300 text-sm leading-relaxed mb-6">
            Menghubungkan backend Laravel dan frontend Vue 3 secara langsung tanpa perlu membangun endpoint REST API terpisah. Controller Laravel mengembalikan komponen Vue melalui <code class="text-indigo-300 font-mono text-xs bg-slate-950 px-1.5 py-0.5 rounded">Inertia::render()</code> dengan data props yang terhidrasi secara otomatis.
          </p>

          <div class="space-y-3 font-mono text-xs">
            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80">
              <span class="text-slate-400">Data Transfer</span>
              <span class="text-indigo-300">Direct Eloquent Props</span>
            </div>
            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80">
              <span class="text-slate-400">State Management</span>
              <span class="text-indigo-300">Server Session &amp; Shared Props</span>
            </div>
            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80">
              <span class="text-slate-400">Routing</span>
              <span class="text-indigo-300">Laravel routes/web.php</span>
            </div>
            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80">
              <span class="text-slate-400">Cocok Untuk</span>
              <span class="text-indigo-300">Admin Dashboard, ERP, SaaS Internal</span>
            </div>
          </div>

          <div class="mt-8 flex items-center justify-between pt-6 border-t border-slate-800">
            <span class="text-xs text-slate-400 font-mono">Produk dibuat: {{ stats.channel_breakdown.inertia }}</span>
            <a :href="getUrl('/inertia/products')" class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-400 hover:text-indigo-300">
              <span>Buka Inertia CRUD</span>
              <span>→</span>
            </a>
          </div>
        </div>

        <!-- Paradigm 2: Traditional SPA (API) -->
        <div class="relative bg-gradient-to-b from-emerald-950/30 to-slate-900/50 border border-emerald-900/50 hover:border-emerald-600/60 transition-all rounded-3xl p-8 backdrop-blur-sm group">
          <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-xl bg-emerald-600/20 border border-emerald-500/30 flex items-center justify-center text-emerald-400 font-bold">
                SPA
              </div>
              <div>
                <h3 class="text-xl font-bold text-white group-hover:text-emerald-300 transition-colors">Traditional SPA (API)</h3>
                <p class="text-xs text-emerald-300/80 font-mono">Decoupled Client-Side Routing</p>
              </div>
            </div>
            <span class="px-2.5 py-1 rounded-full text-xs font-mono font-medium bg-emerald-950 text-emerald-300 border border-emerald-800">
              Active: /spa/*
            </span>
          </div>

          <p class="text-slate-300 text-sm leading-relaxed mb-6">
            Aplikasi Vue 3 mandiri yang berjalan di sisi klien menggunakan <strong class="text-white">Vue Router</strong> dan <strong class="text-white">Pinia</strong>. Berkomunikasi dengan Laravel murni melalui permintaan HTTP asinkron (Axios) ke endpoint <code class="text-emerald-300 font-mono text-xs bg-slate-950 px-1.5 py-0.5 rounded">/api/v1/*</code> dengan format payload JSON standar.
          </p>

          <div class="space-y-3 font-mono text-xs">
            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80">
              <span class="text-slate-400">Data Transfer</span>
              <span class="text-emerald-300">REST API (JSON via Axios)</span>
            </div>
            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80">
              <span class="text-slate-400">State Management</span>
              <span class="text-emerald-300">Client Pinia Reactive Store</span>
            </div>
            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80">
              <span class="text-slate-400">Routing</span>
              <span class="text-emerald-300">Client-Side Vue Router (History)</span>
            </div>
            <div class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950/60 border border-slate-800/80">
              <span class="text-slate-400">Cocok Untuk</span>
              <span class="text-emerald-300">Public Portal, Mobile API, Decoupled Front</span>
            </div>
          </div>

          <div class="mt-8 flex items-center justify-between pt-6 border-t border-slate-800">
            <span class="text-xs text-slate-400 font-mono">Produk dibuat: {{ stats.channel_breakdown.spa_api }}</span>
            <a :href="getUrl('/spa/products')" class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-400 hover:text-emerald-300">
              <span>Buka SPA (API) CRUD</span>
              <span>→</span>
            </a>
          </div>
        </div>
      </div>

      <!-- Real-Time Database Record Showcase -->
      <div class="mt-16 bg-slate-900/60 border border-slate-800/80 rounded-3xl p-8 backdrop-blur-sm">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
          <div>
            <h3 class="text-xl font-bold text-white">Data Terpadu dalam MySQL (hybrid_laravel)</h3>
            <p class="text-xs text-slate-400">Tabel yang sama diakses sekaligus oleh Controller Inertia dan REST API Controller.</p>
          </div>
          <div class="flex items-center gap-2">
            <span class="px-3 py-1 rounded-full text-xs font-mono font-medium bg-slate-800 text-slate-300 border border-slate-700">
              5 Produk Terakhir
            </span>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-300">
            <thead class="text-xs uppercase bg-slate-950/60 text-slate-400 border-b border-slate-800">
              <tr>
                <th class="px-4 py-3">ID</th>
                <th class="px-4 py-3">Nama Produk</th>
                <th class="px-4 py-3">Kategori</th>
                <th class="px-4 py-3">Harga</th>
                <th class="px-4 py-3">Stok</th>
                <th class="px-4 py-3">Dibuat Melalui</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60 font-mono text-xs">
              <tr v-for="item in stats.recent_products" :key="item.id" class="hover:bg-slate-800/30 transition-colors">
                <td class="px-4 py-3 text-slate-500">#{{ item.id }}</td>
                <td class="px-4 py-3 font-sans font-medium text-white">{{ item.name }}</td>
                <td class="px-4 py-3 text-slate-400">{{ item.category }}</td>
                <td class="px-4 py-3 text-white font-semibold">${{ Number(item.price).toFixed(2) }}</td>
                <td class="px-4 py-3">{{ item.stock }}</td>
                <td class="px-4 py-3">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border"
                    :class="{
                      'bg-indigo-950/60 border-indigo-700/60 text-indigo-300': item.created_by_channel === 'Inertia Bridge',
                      'bg-emerald-950/60 border-emerald-700/60 text-emerald-300': item.created_by_channel === 'SPA API',
                      'bg-slate-800 border-slate-700 text-slate-300': item.created_by_channel === 'System'
                    }">
                    {{ item.created_by_channel }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Enterprise Application Modules Showcase (RBAC, Audit, Exports, Notifications, Profile, Docs) -->
      <section class="mt-16">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-6">
          <div>
            <div class="inline-flex items-center gap-2 text-xs font-mono font-semibold text-emerald-400 uppercase tracking-wider">
              <Sparkles class="w-4 h-4" />
              <span>Full-Spectrum Enterprise Features</span>
            </div>
            <h2 class="text-2xl font-bold text-white mt-1">Modul Aplikasi Enterprise Siap Pakai</h2>
            <p class="text-xs text-slate-400 mt-0.5">Semua modul esensial untuk membangun sistem skala bisnis apapun sudah terpasang dan aktif:</p>
          </div>
          <span class="px-3 py-1 rounded-full text-xs font-mono bg-emerald-950 text-emerald-300 border border-emerald-800">
            100% Ready
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <!-- Modul 1: RBAC -->
          <div class="bg-slate-900/60 border border-slate-800/80 hover:border-indigo-500/50 transition-all rounded-3xl p-6 backdrop-blur-sm flex flex-col justify-between">
            <div>
              <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400 mb-4">
                <UserCheck class="w-5 h-5" />
              </div>
              <h3 class="text-base font-bold text-white">Role &amp; Permission (RBAC)</h3>
              <p class="text-xs text-indigo-300 font-mono mt-0.5">Spatie Laravel Permission v6</p>
              <p class="text-xs text-slate-300 mt-2.5 leading-relaxed">
                Manajemen hak akses pengguna bertingkat (Super Admin, Admin, Staff) dengan otorisasi granular di level middleware dan controller.
              </p>
            </div>
            <div class="mt-5 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs">
              <span class="font-mono text-slate-500 text-[11px]">3 Roles &bull; 6 Permissions</span>
              <a :href="getUrl('/profile')" class="text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1">
                <span>Cek Profil</span>
                <span>→</span>
              </a>
            </div>
          </div>

          <!-- Modul 2: Audit Trail -->
          <div class="bg-slate-900/60 border border-slate-800/80 hover:border-indigo-500/50 transition-all rounded-3xl p-6 backdrop-blur-sm flex flex-col justify-between">
            <div>
              <div class="w-10 h-10 rounded-2xl bg-purple-500/10 border border-purple-500/30 flex items-center justify-center text-purple-400 mb-4">
                <History class="w-5 h-5" />
              </div>
              <h3 class="text-base font-bold text-white">Audit Trail &amp; Activity Log</h3>
              <p class="text-xs text-purple-300 font-mono mt-0.5">Spatie Activitylog v4</p>
              <p class="text-xs text-slate-300 mt-2.5 leading-relaxed">
                Mencatat otomatis riwayat perubahan data model (created, updated, deleted), user pelaku, dan timestamp untuk audit kepatuhan.
              </p>
            </div>
            <div class="mt-5 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs">
              <span class="font-mono text-slate-500 text-[11px]">Auto Model Tracking</span>
              <a :href="getUrl('/audit-logs')" class="text-purple-400 hover:text-purple-300 font-semibold flex items-center gap-1">
                <span>Buka Log</span>
                <span>→</span>
              </a>
            </div>
          </div>

          <!-- Modul 3: Data Export -->
          <div class="bg-slate-900/60 border border-slate-800/80 hover:border-emerald-500/50 transition-all rounded-3xl p-6 backdrop-blur-sm flex flex-col justify-between">
            <div>
              <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-4">
                <FileSpreadsheet class="w-5 h-5" />
              </div>
              <h3 class="text-base font-bold text-white">Export Data (Excel, CSV, PDF)</h3>
              <p class="text-xs text-emerald-300 font-mono mt-0.5">Laravel Excel + DomPDF</p>
              <p class="text-xs text-slate-300 mt-2.5 leading-relaxed">
                Ekspor data inventaris instan ke format spreadsheet Excel (.xlsx), format raw CSV, serta berkas PDF siap cetak dengan styling rapi.
              </p>
            </div>
            <div class="mt-5 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs">
              <a :href="getUrl('/exports/products/excel')" class="text-emerald-400 hover:text-emerald-300 font-semibold font-mono text-[11px]">
                Download .XLSX
              </a>
              <a :href="getUrl('/exports/products/pdf')" class="text-rose-400 hover:text-rose-300 font-semibold font-mono text-[11px]">
                Download .PDF
              </a>
            </div>
          </div>

          <!-- Modul 4: Notification Bell -->
          <div class="bg-slate-900/60 border border-slate-800/80 hover:border-cyan-500/50 transition-all rounded-3xl p-6 backdrop-blur-sm flex flex-col justify-between">
            <div>
              <div class="w-10 h-10 rounded-2xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400 mb-4">
                <Bell class="w-5 h-5" />
              </div>
              <h3 class="text-base font-bold text-white">Notifikasi Real-Time (WebSockets)</h3>
              <p class="text-xs text-cyan-300 font-mono mt-0.5">Reverb + Database Queue</p>
              <p class="text-xs text-slate-300 mt-2.5 leading-relaxed">
                Lonceng notifikasi interaktif di navbar dengan badge counter unread, penyimpanan ke database, dan broadcast instan melalui Reverb.
              </p>
            </div>
            <div class="mt-5 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs">
              <span class="font-mono text-cyan-300 text-[11px]">Live Reverb Echo</span>
              <span class="text-slate-400 text-[11px]">Aktif di Navbar</span>
            </div>
          </div>

          <!-- Modul 5: Profile & Avatar Upload -->
          <div class="bg-slate-900/60 border border-slate-800/80 hover:border-indigo-500/50 transition-all rounded-3xl p-6 backdrop-blur-sm flex flex-col justify-between">
            <div>
              <div class="w-10 h-10 rounded-2xl bg-indigo-500/10 border border-indigo-500/30 flex items-center justify-center text-indigo-400 mb-4">
                <UserCheck class="w-5 h-5" />
              </div>
              <h3 class="text-base font-bold text-white">Profil &amp; Upload Media Storage</h3>
              <p class="text-xs text-indigo-300 font-mono mt-0.5">Disk Storage / Symbolic Link</p>
              <p class="text-xs text-slate-300 mt-2.5 leading-relaxed">
                Manajemen akun mandiri, upload foto avatar ke disk storage terproteksi, pembaruan password aman, dan pemulihan lupa sandi.
              </p>
            </div>
            <div class="mt-5 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs">
              <span class="font-mono text-slate-500 text-[11px]">storage/app/public</span>
              <a :href="getUrl('/profile')" class="text-indigo-400 hover:text-indigo-300 font-semibold flex items-center gap-1">
                <span>Buka Profil</span>
                <span>→</span>
              </a>
            </div>
          </div>

          <!-- Modul 6: Scramble API Docs -->
          <div class="bg-slate-900/60 border border-slate-800/80 hover:border-amber-500/50 transition-all rounded-3xl p-6 backdrop-blur-sm flex flex-col justify-between">
            <div>
              <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 mb-4">
                <BookOpen class="w-5 h-5" />
              </div>
              <h3 class="text-base font-bold text-white">Dokumentasi API Interaktif</h3>
              <p class="text-xs text-amber-300 font-mono mt-0.5">Dedoc Scramble OpenAPI</p>
              <p class="text-xs text-slate-300 mt-2.5 leading-relaxed">
                Dokumentasi OpenAPI / Swagger yang otomatis ter-generate dari rute dan controller tanpa perlu menulis anotasi PHPDoc manual.
              </p>
            </div>
            <div class="mt-5 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs">
              <span class="font-mono text-slate-500 text-[11px]">URL: /docs/api</span>
              <a :href="getUrl('/docs/api')" target="_blank" class="text-amber-400 hover:text-amber-300 font-semibold flex items-center gap-1">
                <span>Buka Dokumentasi</span>
                <span>↗</span>
              </a>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>
</template>
