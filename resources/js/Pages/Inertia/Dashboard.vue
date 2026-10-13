<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import HybridNavbar from '../../Components/HybridNavbar.vue';
import BaseChart from '../../Components/BaseChart.vue';
import {
  Activity,
  TrendingUp,
  BarChart3,
  PieChart,
  RotateCw,
  Sliders,
  Layers,
  Zap,
  Database,
  Cpu,
  ArrowUpRight,
  ShieldCheck,
  Server,
  FlaskConical,
  Rocket
} from 'lucide-vue-next';
import { useAppMode } from '../../composables/useAppMode';

const { appMode, toggleMode } = useAppMode();

const props = defineProps({
  products: {
    type: Object,
    required: true,
  },
  summary: {
    type: Object,
    required: true,
  },
  analytics: {
    type: Object,
    default: () => ({ categories: [], channels: {} }),
  },
});

const getUrl = (path) => {
  if (typeof window !== 'undefined' && window.location.pathname.includes('/hybridlaravel/public')) {
    return `/hybridlaravel/public${path}`;
  }
  return path;
};

// Theme Detection for ECharts Options
const isDark = ref(false);
const updateThemeState = () => {
  if (typeof document !== 'undefined') {
    isDark.value = document.documentElement.classList.contains('dark');
  }
};

let themeObserver = null;
onMounted(() => {
  updateThemeState();
  if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
    themeObserver = new MutationObserver(() => updateThemeState());
    themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
  }
});

onUnmounted(() => {
  if (themeObserver) {
    themeObserver.disconnect();
    themeObserver = null;
  }
});

// Interactive State
const selectedTimeframe = ref('24h'); // '24h' | '7d' | '30d'
const isSimulating = ref(false);
const simulationOffset = ref(0);

const simulateLiveRefresh = () => {
  isSimulating.value = true;
  simulationOffset.value = Math.floor(Math.random() * 40) - 20;
  setTimeout(() => {
    isSimulating.value = false;
  }, 400);
};

// Chart Colors & Styling Tokens
const textColor = computed(() => isDark.value ? '#cbd5e1' : '#475569');
const titleColor = computed(() => isDark.value ? '#f8fafc' : '#0f172a');
const gridLineColor = computed(() => isDark.value ? 'rgba(255, 255, 255, 0.07)' : 'rgba(0, 0, 0, 0.06)');
const axisLineColor = computed(() => isDark.value ? '#334155' : '#cbd5e1');

// 1. Throughput & Latency Trend Chart (Line & Area)
const throughputOption = computed(() => {
  let categories = [];
  let throughputData = [];
  let latencyData = [];

  const offset = simulationOffset.value;

  if (selectedTimeframe.value === '24h') {
    categories = ['00:00', '02:00', '04:00', '06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00'];
    throughputData = [120, 95, 80, 140, 480, 890, 1150, 980, 1240, 1420, 1100, 650].map(v => Math.max(50, v + offset * 5));
    latencyData = [12.4, 11.8, 11.2, 13.5, 18.2, 22.4, 24.1, 21.0, 25.6, 28.2, 20.1, 15.3].map(v => Number((v + (offset * 0.1)).toFixed(1)));
  } else if (selectedTimeframe.value === '7d') {
    categories = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'];
    throughputData = [4820, 5630, 6120, 5940, 7250, 4100, 3950].map(v => Math.max(1000, v + offset * 25));
    latencyData = [18.2, 17.5, 19.1, 18.4, 22.0, 14.8, 14.2].map(v => Number((v + (offset * 0.1)).toFixed(1)));
  } else {
    categories = ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'];
    throughputData = [34200, 41800, 46900, 52400].map(v => Math.max(10000, v + offset * 100));
    latencyData = [19.4, 18.1, 17.8, 16.9].map(v => Number((v + (offset * 0.1)).toFixed(1)));
  }

  return {
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'cross', label: { backgroundColor: '#4f46e5' } },
      backgroundColor: isDark.value ? 'rgba(15, 23, 42, 0.92)' : 'rgba(255, 255, 255, 0.95)',
      borderColor: isDark.value ? '#334155' : '#e2e8f0',
      textStyle: { color: isDark.value ? '#f1f5f9' : '#0f172a', fontSize: 12 },
      borderRadius: 12,
      padding: [10, 14],
    },
    legend: {
      data: ['Throughput (Req/s)', 'Rata-rata Latensi (ms)'],
      textStyle: { color: textColor.value, fontSize: 12 },
      top: 4,
      right: 12,
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '3%',
      top: '16%',
      containLabel: true,
    },
    xAxis: {
      type: 'category',
      boundaryGap: false,
      data: categories,
      axisLine: { lineStyle: { color: axisLineColor.value } },
      axisLabel: { color: textColor.value, fontSize: 11 },
    },
    yAxis: [
      {
        type: 'value',
        name: 'Req / Detik',
        nameTextStyle: { color: textColor.value, fontSize: 11 },
        splitLine: { lineStyle: { color: gridLineColor.value } },
        axisLabel: { color: textColor.value, fontSize: 11 },
      },
      {
        type: 'value',
        name: 'Latensi (ms)',
        nameTextStyle: { color: textColor.value, fontSize: 11 },
        splitLine: { show: false },
        axisLabel: { color: textColor.value, fontSize: 11, formatter: '{value} ms' },
      },
    ],
    series: [
      {
        name: 'Throughput (Req/s)',
        type: 'line',
        smooth: true,
        showSymbol: false,
        symbolSize: 6,
        yAxisIndex: 0,
        itemStyle: { color: '#6366f1' },
        lineStyle: { width: 3, color: '#6366f1' },
        areaStyle: {
          color: {
            type: 'linear',
            x: 0,
            y: 0,
            x2: 0,
            y2: 1,
            colorStops: [
              { offset: 0, color: 'rgba(99, 102, 241, 0.45)' },
              { offset: 1, color: 'rgba(99, 102, 241, 0.01)' },
            ],
          },
        },
        data: throughputData,
      },
      {
        name: 'Rata-rata Latensi (ms)',
        type: 'line',
        smooth: true,
        showSymbol: false,
        symbolSize: 6,
        yAxisIndex: 1,
        itemStyle: { color: '#10b981' },
        lineStyle: { width: 2.5, type: 'dashed', color: '#10b981' },
        data: latencyData,
      },
    ],
  };
});

// 2. Category Stock & Price Distribution (Bar Chart)
const categoryOption = computed(() => {
  const catItems = props.analytics?.categories?.length
    ? props.analytics.categories
    : [
        { category: 'Electronics', total_stock: 450, avg_price: 299 },
        { category: 'Accessories', total_stock: 720, avg_price: 49 },
        { category: 'Software', total_stock: 310, avg_price: 180 },
        { category: 'Peripherals', total_stock: 580, avg_price: 85 },
        { category: 'Services', total_stock: 220, avg_price: 450 },
      ];

  const categories = catItems.map(c => c.category || 'General');
  const stockData = catItems.map(c => Number(c.total_stock || 0));
  const priceData = catItems.map(c => Number(c.avg_price || 0));

  return {
    tooltip: {
      trigger: 'axis',
      axisPointer: { type: 'shadow' },
      backgroundColor: isDark.value ? 'rgba(15, 23, 42, 0.92)' : 'rgba(255, 255, 255, 0.95)',
      borderColor: isDark.value ? '#334155' : '#e2e8f0',
      textStyle: { color: isDark.value ? '#f1f5f9' : '#0f172a', fontSize: 12 },
      borderRadius: 12,
    },
    legend: {
      data: ['Total Stok Unit', 'Rata-rata Harga ($)'],
      textStyle: { color: textColor.value, fontSize: 11 },
      top: 4,
      right: 10,
    },
    grid: {
      left: '3%',
      right: '4%',
      bottom: '3%',
      top: '18%',
      containLabel: true,
    },
    xAxis: {
      type: 'category',
      data: categories,
      axisLine: { lineStyle: { color: axisLineColor.value } },
      axisLabel: { color: textColor.value, fontSize: 11, interval: 0, rotate: categories.length > 5 ? 25 : 0 },
    },
    yAxis: [
      {
        type: 'value',
        name: 'Unit',
        nameTextStyle: { color: textColor.value, fontSize: 11 },
        splitLine: { lineStyle: { color: gridLineColor.value } },
        axisLabel: { color: textColor.value, fontSize: 11 },
      },
      {
        type: 'value',
        name: 'Harga ($)',
        nameTextStyle: { color: textColor.value, fontSize: 11 },
        splitLine: { show: false },
        axisLabel: { color: textColor.value, fontSize: 11, formatter: '${value}' },
      },
    ],
    series: [
      {
        name: 'Total Stok Unit',
        type: 'bar',
        barMaxWidth: 32,
        itemStyle: {
          borderRadius: [6, 6, 0, 0],
          color: {
            type: 'linear',
            x: 0,
            y: 0,
            x2: 0,
            y2: 1,
            colorStops: [
              { offset: 0, color: '#6366f1' },
              { offset: 1, color: '#4338ca' },
            ],
          },
        },
        data: stockData,
      },
      {
        name: 'Rata-rata Harga ($)',
        type: 'bar',
        barMaxWidth: 32,
        yAxisIndex: 1,
        itemStyle: {
          borderRadius: [6, 6, 0, 0],
          color: {
            type: 'linear',
            x: 0,
            y: 0,
            x2: 0,
            y2: 1,
            colorStops: [
              { offset: 0, color: '#10b981' },
              { offset: 1, color: '#047857' },
            ],
          },
        },
        data: priceData,
      },
    ],
  };
});

// 3. Traffic Channel Share (Doughnut / Pie Chart)
const channelOption = computed(() => {
  const inertiaCount = Number(props.summary?.inertia_count || 14);
  const apiCount = Number(props.summary?.api_count || 9);
  const systemCount = Number(props.summary?.system_count || 5);
  const mobileCount = 7; // Simulated mobile client distribution

  return {
    tooltip: {
      trigger: 'item',
      backgroundColor: isDark.value ? 'rgba(15, 23, 42, 0.92)' : 'rgba(255, 255, 255, 0.95)',
      borderColor: isDark.value ? '#334155' : '#e2e8f0',
      textStyle: { color: isDark.value ? '#f1f5f9' : '#0f172a', fontSize: 12 },
      borderRadius: 12,
      formatter: '{b}<br/><b>{c} requests ({d}%)</b>',
    },
    legend: {
      orient: 'vertical',
      right: '4%',
      top: 'center',
      textStyle: { color: textColor.value, fontSize: 11 },
      itemGap: 12,
    },
    series: [
      {
        name: 'Channel Traffic',
        type: 'pie',
        radius: ['45%', '72%'],
        center: ['38%', '50%'],
        avoidLabelOverlap: false,
        itemStyle: {
          borderRadius: 8,
          borderColor: isDark.value ? '#0f172a' : '#ffffff',
          borderWidth: 2,
        },
        label: {
          show: false,
          position: 'center',
        },
        emphasis: {
          label: {
            show: true,
            fontSize: 14,
            fontWeight: 'bold',
            color: titleColor.value,
            formatter: '{b}\n{d}%',
          },
          itemStyle: {
            shadowBlur: 12,
            shadowOffsetX: 0,
            shadowColor: 'rgba(0, 0, 0, 0.3)',
          },
        },
        labelLine: { show: false },
        data: [
          { value: inertiaCount, name: 'Inertia Monolith Bridge', itemStyle: { color: '#6366f1' } },
          { value: apiCount, name: 'Decoupled SPA (REST API)', itemStyle: { color: '#10b981' } },
          { value: mobileCount, name: 'Mobile Gateway (OAuth)', itemStyle: { color: '#f59e0b' } },
          { value: systemCount, name: 'System Queues & CRON', itemStyle: { color: '#8b5cf6' } },
        ],
      },
    ],
  };
});

// 4. Architecture Radar Benchmark (Radar Chart)
const radarOption = computed(() => {
  return {
    tooltip: {
      backgroundColor: isDark.value ? 'rgba(15, 23, 42, 0.92)' : 'rgba(255, 255, 255, 0.95)',
      borderColor: isDark.value ? '#334155' : '#e2e8f0',
      textStyle: { color: isDark.value ? '#f1f5f9' : '#0f172a', fontSize: 12 },
      borderRadius: 12,
    },
    legend: {
      data: ['Inertia Monolith Bridge', 'Decoupled SPA API'],
      textStyle: { color: textColor.value, fontSize: 11 },
      top: 4,
    },
    radar: {
      shape: 'polygon',
      indicator: [
        { name: 'SSR Speed (ms)', max: 100 },
        { name: 'SEO Indexing', max: 100 },
        { name: 'Dev Velocity (DX)', max: 100 },
        { name: 'Cache Hit Rate', max: 100 },
        { name: 'Mobile API Reuse', max: 100 },
        { name: 'Low Memory Footprint', max: 100 },
      ],
      axisName: {
        color: textColor.value,
        fontSize: 10.5,
      },
      splitLine: { lineStyle: { color: gridLineColor.value } },
      splitArea: {
        show: true,
        areaStyle: {
          color: isDark.value
            ? ['rgba(30, 41, 59, 0.4)', 'rgba(15, 23, 42, 0.3)']
            : ['rgba(241, 245, 249, 0.6)', 'rgba(255, 255, 255, 0.4)'],
        },
      },
      axisLine: { lineStyle: { color: gridLineColor.value } },
    },
    series: [
      {
        name: 'Hybrid Architecture Comparison',
        type: 'radar',
        data: [
          {
            value: [96, 94, 98, 86, 72, 88],
            name: 'Inertia Monolith Bridge',
            itemStyle: { color: '#6366f1' },
            lineStyle: { width: 2.5, color: '#6366f1' },
            areaStyle: { color: 'rgba(99, 102, 241, 0.3)' },
          },
          {
            value: [80, 68, 84, 94, 98, 92],
            name: 'Decoupled SPA API',
            itemStyle: { color: '#10b981' },
            lineStyle: { width: 2.5, color: '#10b981' },
            areaStyle: { color: 'rgba(16, 185, 129, 0.25)' },
          },
        ],
      },
    ],
  };
});

// 5. Engine Health & Memory Efficiency (Speedometer Gauge Chart)
const gaugeOption = computed(() => {
  const healthScore = 98.4;

  return {
    tooltip: {
      formatter: '{a} <br/>{b} : {c}%',
      backgroundColor: isDark.value ? 'rgba(15, 23, 42, 0.92)' : 'rgba(255, 255, 255, 0.95)',
      borderColor: isDark.value ? '#334155' : '#e2e8f0',
      textStyle: { color: isDark.value ? '#f1f5f9' : '#0f172a', fontSize: 12 },
    },
    series: [
      {
        name: 'Engine Telemetry',
        type: 'gauge',
        startAngle: 190,
        endAngle: -10,
        min: 0,
        max: 100,
        splitNumber: 5,
        radius: '88%',
        center: ['50%', '58%'],
        itemStyle: {
          color: '#10b981',
          shadowColor: 'rgba(16, 185, 129, 0.4)',
          shadowBlur: 10,
        },
        progress: {
          show: true,
          roundCap: true,
          width: 14,
        },
        pointer: {
          icon: 'path://M12.8,0.7l12,40.1H0.7L12.8,0.7z',
          length: '12%',
          width: 14,
          offsetCenter: [0, '-55%'],
          itemStyle: {
            color: 'auto',
          },
        },
        axisLine: {
          roundCap: true,
          lineStyle: {
            width: 14,
            color: [
              [0.3, '#f59e0b'],
              [0.7, '#6366f1'],
              [1, '#10b981'],
            ],
          },
        },
        axisTick: {
          distance: -20,
          splitNumber: 5,
          lineStyle: {
            width: 1.5,
            color: textColor.value,
          },
        },
        splitLine: {
          distance: -24,
          length: 10,
          lineStyle: {
            width: 2.5,
            color: textColor.value,
          },
        },
        axisLabel: {
          distance: -14,
          color: textColor.value,
          fontSize: 10,
        },
        title: {
          show: true,
          offsetCenter: [0, '28%'],
          fontSize: 13,
          fontWeight: 600,
          color: textColor.value,
        },
        detail: {
          valueAnimation: true,
          offsetCenter: [0, '-10%'],
          fontSize: 28,
          fontWeight: 'bolder',
          formatter: '{value}%',
          color: titleColor.value,
        },
        data: [
          {
            value: healthScore,
            name: 'RoadRunner & Octane Status',
          },
        ],
      },
    ],
  };
});
</script>

<template>
  <Head title="Inertia.js Bridge Dashboard & ECharts Analytics" />

  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 selection:bg-indigo-500 selection:text-white transition-colors duration-200 overflow-x-clip">
    <HybridNavbar mode="inertia" currentRoute="dashboard" />

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
      
      <!-- Top Banner -->
      <div class="relative overflow-hidden bg-gradient-to-r from-indigo-900/90 via-purple-900/80 to-slate-950 border border-indigo-700/40 rounded-3xl p-6 sm:p-8 backdrop-blur-md shadow-xl text-white">
        <div class="absolute -right-12 -top-12 w-64 h-64 bg-indigo-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute right-1/3 -bottom-10 w-48 h-48 bg-purple-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
          <div>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-mono font-medium border mb-3 shadow-inner"
              :class="appMode === 'production' 
                ? 'bg-emerald-950/90 text-emerald-300 border-emerald-700/50' 
                : 'bg-indigo-950/90 text-indigo-300 border-indigo-700/50'"
            >
              <span class="w-2 h-2 rounded-full animate-pulse" :class="appMode === 'production' ? 'bg-emerald-400' : 'bg-purple-400'"></span>
              <span>{{ appMode === 'production' ? '🚀 Mode Produksi Aktif • Sistem Operasional Utama' : '🧪 Mode Demo & Showcase • Apache ECharts v5 + MapLibre' }}</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight flex items-center gap-2.5">
              <span>{{ appMode === 'production' ? 'Dashboard Operasional & Metrik Bisnis' : 'Telemetry & Dashboard Analytics' }}</span>
              <Sparkles class="w-6 h-6 text-amber-400 animate-bounce" />
            </h1>
            <p class="mt-2 text-slate-300 text-sm max-w-2xl leading-relaxed">
              {{ appMode === 'production'
                ? 'Monitoring metrik data riil, inventaris aktif, dan audit transaksi server-driven dari database MySQL via Inertia.js Monolith.'
                : 'Monitoring performa hybrid stack secara real-time dengan visualisasi data interaktif Apache ECharts dan MapLibre GL JS.'
              }}
            </p>
          </div>

          <div class="flex flex-wrap items-center gap-3">
            <a :href="getUrl('/inertia/products')" class="px-5 py-2.5 rounded-xl font-semibold text-white bg-indigo-600 hover:bg-indigo-500 transition-all text-sm shadow-lg shadow-indigo-600/30 flex items-center gap-2">
              <span>{{ appMode === 'production' ? '+ Tambah Data Master' : '+ Kelola Produk (Inertia)' }}</span>
            </a>
            <template v-if="appMode === 'demo'">
              <a :href="getUrl('/inertia/map-demo')" class="px-5 py-2.5 rounded-xl font-semibold text-teal-200 bg-teal-950/80 hover:bg-teal-900 border border-teal-700/60 transition-all text-sm flex items-center gap-1.5">
                <span>Peta MapLibre GL</span>
                <ArrowUpRight class="w-4 h-4 text-teal-400" />
              </a>
              <a :href="getUrl('/spa')" class="px-5 py-2.5 rounded-xl font-semibold text-slate-200 bg-slate-900/90 hover:bg-slate-800 border border-slate-700 transition-all text-sm flex items-center gap-1.5">
                <span>Coba SPA Mode</span>
                <ArrowUpRight class="w-4 h-4 text-slate-400" />
              </a>
            </template>
            <button
              type="button"
              @click="toggleMode"
              class="px-4 py-2.5 rounded-xl font-semibold text-xs border transition-all cursor-pointer flex items-center gap-1.5"
              :class="appMode === 'production'
                ? 'bg-purple-900/40 text-purple-200 border-purple-700 hover:bg-purple-800/60'
                : 'bg-emerald-900/40 text-emerald-200 border-emerald-700 hover:bg-emerald-800/60'"
            >
              <component :is="appMode === 'production' ? FlaskConical : Rocket" class="w-3.5 h-3.5" />
              <span>{{ appMode === 'production' ? 'Beralih ke Demo' : 'Beralih ke Produksi' }}</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Quick Metrics Grid -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm dark:shadow-none hover:border-slate-300 dark:hover:border-slate-700 transition-all">
          <div class="flex items-center justify-between text-xs font-mono text-slate-500 dark:text-slate-400 uppercase tracking-wider">
            <span>Total Produk</span>
            <Database class="w-4 h-4 text-indigo-500" />
          </div>
          <div class="text-3xl font-extrabold text-slate-900 dark:text-white mt-2">{{ summary.count }}</div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-1">
            <CheckCircle2 class="w-3.5 h-3.5 text-emerald-500 inline" /> MySQL Database Records
          </p>
        </div>

        <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm dark:shadow-none hover:border-indigo-300 dark:hover:border-indigo-700/60 transition-all">
          <div class="flex items-center justify-between text-xs font-mono text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
            <span>Via Inertia Bridge</span>
            <Layers class="w-4 h-4 text-indigo-500" />
          </div>
          <div class="text-3xl font-extrabold text-indigo-600 dark:text-indigo-300 mt-2">{{ summary.inertia_count }}</div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Form submit server-side</p>
        </div>

        <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm dark:shadow-none hover:border-emerald-300 dark:hover:border-emerald-700/60 transition-all">
          <div class="flex items-center justify-between text-xs font-mono text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">
            <span>Via SPA (REST API)</span>
            <Zap class="w-4 h-4 text-emerald-500" />
          </div>
          <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-300 mt-2">{{ summary.api_count }}</div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Decoupled REST endpoint</p>
        </div>

        <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm dark:shadow-none hover:border-amber-300 dark:hover:border-amber-700/60 transition-all">
          <div class="flex items-center justify-between text-xs font-mono text-amber-600 dark:text-amber-400 uppercase tracking-wider">
            <span>Total Stok Unit</span>
            <Server class="w-4 h-4 text-amber-500" />
          </div>
          <div class="text-3xl font-extrabold text-amber-600 dark:text-amber-300 mt-2">{{ summary.total_stock || 1280 }}</div>
          <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Akumulasi inventaris gudang</p>
        </div>
      </div>

      <!-- ECharts Interactive Showcase Header & Controls -->
      <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-2xl p-5 shadow-sm dark:shadow-none flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="p-2.5 rounded-xl bg-indigo-50 dark:bg-indigo-950/70 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/50">
            <Activity class="w-5 h-5" />
          </div>
          <div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
              <span>Koleksi Chart Apache ECharts</span>
              <span class="text-xs font-normal px-2 py-0.5 rounded-md bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800">
                5 Model Visualisasi
              </span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Uji coba interaksi timeframe filter dan simulasi pergerakan data animasi real-time.
            </p>
          </div>
        </div>

        <!-- Toolbar Actions -->
        <div class="flex items-center flex-wrap gap-2.5">
          <!-- Timeframe Selector -->
          <div class="inline-flex rounded-xl bg-slate-100 dark:bg-slate-950 p-1 border border-slate-200 dark:border-slate-800 text-xs font-medium">
            <button
              v-for="tf in [
                { id: '24h', label: '24 Jam' },
                { id: '7d', label: '7 Hari' },
                { id: '30d', label: '30 Hari' }
              ]"
              :key="tf.id"
              type="button"
              @click="selectedTimeframe = tf.id"
              class="px-3 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="selectedTimeframe === tf.id
                ? 'bg-white dark:bg-indigo-600 text-indigo-600 dark:text-white font-semibold shadow-xs'
                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'"
            >
              {{ tf.label }}
            </button>
          </div>

          <!-- Simulate Live Telemetry Jitter -->
          <button
            type="button"
            @click="simulateLiveRefresh"
            :disabled="isSimulating"
            class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700/80 text-slate-700 dark:text-slate-200 border border-slate-300 dark:border-slate-700 text-xs font-medium transition-all cursor-pointer"
            title="Kocok ulang telemetry untuk menguji animasi transisi ECharts"
          >
            <RotateCw class="w-3.5 h-3.5" :class="{ 'animate-spin': isSimulating }" />
            <span>Simulasi Refresh Data</span>
          </button>
        </div>
      </div>

      <!-- Chart 1: Throughput & Latency Trend (Full-Width Area & Line Chart) -->
      <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-3xl p-5 sm:p-6 shadow-sm dark:shadow-none backdrop-blur-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/80 gap-2 mb-4">
          <div class="flex items-center gap-2.5">
            <div class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/50">
              <TrendingUp class="w-4 h-4" />
            </div>
            <div>
              <h3 class="text-base font-bold text-slate-900 dark:text-white">
                1. API Throughput & Real-time Latency (Spline Area & Line)
              </h3>
              <p class="text-xs text-slate-500 dark:text-slate-400">
                Visualisasi dual Y-axis mengombinasikan kurva volume throughput dengan latensi waktu tanggap server (ms).
              </p>
            </div>
          </div>
          <span class="text-[11px] font-mono px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 self-start sm:self-auto">
            Mode Rentang: {{ selectedTimeframe.toUpperCase() }}
          </span>
        </div>

        <BaseChart :option="throughputOption" height="350px" />
      </div>

      <!-- Charts Grid Row: 2-Columns (Bar Chart + Doughnut Chart) -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Chart 2: Category Inventory & Stock Value (Bar Chart) -->
        <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-3xl p-5 sm:p-6 shadow-sm dark:shadow-none backdrop-blur-sm flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/80 gap-2 mb-4">
              <div class="flex items-center gap-2.5">
                <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/50">
                  <BarChart3 class="w-4 h-4" />
                </div>
                <div>
                  <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    2. Inventaris & Harga per Kategori (Grouped Bar Chart)
                  </h3>
                  <p class="text-xs text-slate-500 dark:text-slate-400">
                    Membandingkan volume unit stok dengan rata-rata banderol harga per kategori.
                  </p>
                </div>
              </div>
            </div>
            <BaseChart :option="categoryOption" height="340px" />
          </div>
          <div class="mt-2 pt-3 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-mono">
            <span>Gradient fill & rounded tops</span>
            <span class="text-emerald-500 font-semibold">Data Asli Database MySQL</span>
          </div>
        </div>

        <!-- Chart 3: Hybrid Traffic Distribution (Donut / Pie Chart) -->
        <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-3xl p-5 sm:p-6 shadow-sm dark:shadow-none backdrop-blur-sm flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/80 gap-2 mb-4">
              <div class="flex items-center gap-2.5">
                <div class="p-2 rounded-lg bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-800/50">
                  <PieChart class="w-4 h-4" />
                </div>
                <div>
                  <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    3. Distribusi Kanal Traffic (Doughnut / Pie Chart)
                  </h3>
                  <p class="text-xs text-slate-500 dark:text-slate-400">
                    Proporsi request antara Inertia Monolith Bridge, Vue SPA REST API, dan Sistem.
                  </p>
                </div>
              </div>
            </div>
            <BaseChart :option="channelOption" height="340px" />
          </div>
          <div class="mt-2 pt-3 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-mono">
            <span>Inner radius 45%-72% + hover expand</span>
            <span class="text-indigo-500 font-semibold">Hybrid Protocol Split</span>
          </div>
        </div>

      </div>

      <!-- Charts Grid Row: 2-Columns (Radar Chart + Speedometer Gauge) -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Chart 4: Multi-Dimensional Architecture Benchmark (Radar Chart) -->
        <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-3xl p-5 sm:p-6 shadow-sm dark:shadow-none backdrop-blur-sm flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/80 gap-2 mb-4">
              <div class="flex items-center gap-2.5">
                <div class="p-2 rounded-lg bg-cyan-50 dark:bg-cyan-950/60 text-cyan-600 dark:text-cyan-400 border border-cyan-200 dark:border-cyan-800/50">
                  <ShieldCheck class="w-4 h-4" />
                </div>
                <div>
                  <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    4. Matriks Evaluasi Arsitektur (Radar / Spider Chart)
                  </h3>
                  <p class="text-xs text-slate-500 dark:text-slate-400">
                    Evaluasi 6 dimensi perbandingan antara Inertia Monolith Bridge vs Decoupled SPA.
                  </p>
                </div>
              </div>
            </div>
            <BaseChart :option="radarOption" height="340px" />
          </div>
          <div class="mt-2 pt-3 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-mono">
            <span>6 Indikator Dimensi Poligon</span>
            <span class="text-cyan-500 font-semibold">Inertia vs Decoupled SPA</span>
          </div>
        </div>

        <!-- Chart 5: Engine Performance & Health (Speedometer Gauge Chart) -->
        <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-3xl p-5 sm:p-6 shadow-sm dark:shadow-none backdrop-blur-sm flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/80 gap-2 mb-4">
              <div class="flex items-center gap-2.5">
                <div class="p-2 rounded-lg bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800/50">
                  <Cpu class="w-4 h-4" />
                </div>
                <div>
                  <h3 class="text-base font-bold text-slate-900 dark:text-white">
                    5. Telemetry & Efisiensi Engine (Gauge Speedometer)
                  </h3>
                  <p class="text-xs text-slate-500 dark:text-slate-400">
                    Indikator operasional in-memory framework (Octane RoadRunner + Redis Cache Hit).
                  </p>
                </div>
              </div>
            </div>
            <BaseChart :option="gaugeOption" height="340px" />
          </div>
          <div class="mt-2 pt-3 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-mono">
            <span>Segmented gradient arc 0-100%</span>
            <span class="text-emerald-500 font-semibold">Sub-millisecond Execution</span>
          </div>
        </div>

      </div>

      <!-- Geospatial MapLibre GL JS Showcase Banner -->
      <div class="relative overflow-hidden bg-gradient-to-r from-teal-900/50 via-slate-900/70 to-indigo-900/50 border border-teal-700/40 rounded-3xl p-6 sm:p-8 backdrop-blur-md shadow-lg">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
          <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-mono font-medium bg-teal-950/80 text-teal-300 border border-teal-700/50">
              <span class="w-2 h-2 rounded-full bg-teal-400 animate-ping"></span>
              <span>Baru: MapLibre GL JS Visualisasi Peta</span>
            </div>
            <h3 class="text-xl sm:text-2xl font-bold text-white">
              Monitoring Edge Telemetry Global dengan MapLibre GL JS
            </h3>
            <p class="text-sm text-slate-300 max-w-2xl leading-relaxed">
              Jelajahi visualisasi peta WebGL interaktif untuk node server Jakarta, Singapore, Tokyo, Frankfurt, Silicon Valley, dan Sydney dengan dukungan 3D pitch, auto-orbit, dan rotasi kamera.
            </p>
          </div>

          <a :href="getUrl('/inertia/map-demo')" class="px-6 py-3 rounded-2xl font-bold text-white bg-gradient-to-r from-teal-500 to-emerald-600 hover:from-teal-400 hover:to-emerald-500 transition-all text-sm shadow-xl shadow-teal-500/20 flex items-center gap-2 whitespace-nowrap">
            <span>Buka Peta MapLibre GL</span>
            <ArrowUpRight class="w-4 h-4" />
          </a>
        </div>
      </div>

      <!-- Live Paginated Products Table (Inertia Data Props) -->
      <div class="bg-white dark:bg-slate-900/70 border border-slate-200 dark:border-slate-800/80 rounded-3xl p-6 sm:p-8 shadow-sm dark:shadow-none backdrop-blur-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-100 dark:border-slate-800/80 mb-6 gap-2">
          <div>
            <h2 class="text-lg font-bold text-slate-900 dark:text-white">Daftar Produk Terkini (Props Inertia.js)</h2>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              Data tabel di bawah ini di-inject langsung dari Laravel Controller tanpa REST API fetch terpisah.
            </p>
          </div>
          <span class="text-xs font-mono text-slate-500 dark:text-slate-400 bg-slate-100 dark:bg-slate-800/70 px-3 py-1 rounded-full border border-slate-200 dark:border-slate-700 self-start sm:self-auto">
            Total Database: {{ products.total }} produk
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
            <thead class="text-xs uppercase bg-slate-50 dark:bg-slate-950/70 text-slate-500 dark:text-slate-400 border-b border-slate-200 dark:border-slate-800">
              <tr>
                <th class="px-4 py-3">Nama Produk</th>
                <th class="px-4 py-3">Kategori</th>
                <th class="px-4 py-3">Harga</th>
                <th class="px-4 py-3">Stok</th>
                <th class="px-4 py-3">Channel Pembuat</th>
                <th class="px-4 py-3 text-right">Aksi</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800/60 font-mono text-xs">
              <tr v-for="item in products.data" :key="item.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/30 transition-colors">
                <td class="px-4 py-3.5 font-sans font-medium text-slate-900 dark:text-white">{{ item.name }}</td>
                <td class="px-4 py-3.5 text-slate-500 dark:text-slate-400">{{ item.category }}</td>
                <td class="px-4 py-3.5 text-slate-900 dark:text-white font-semibold">${{ Number(item.price).toFixed(2) }}</td>
                <td class="px-4 py-3.5">{{ item.stock }}</td>
                <td class="px-4 py-3.5">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border"
                    :class="{
                      'bg-indigo-50 dark:bg-indigo-950/60 border-indigo-200 dark:border-indigo-700/60 text-indigo-700 dark:text-indigo-300': item.created_by_channel === 'Inertia Bridge',
                      'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-200 dark:border-emerald-700/60 text-emerald-700 dark:text-emerald-300': item.created_by_channel === 'SPA API',
                      'bg-slate-100 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300': item.created_by_channel === 'System'
                    }">
                    {{ item.created_by_channel }}
                  </span>
                </td>
                <td class="px-4 py-3.5 text-right font-sans">
                  <Link :href="`/inertia/products`" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 dark:hover:text-indigo-300 font-medium text-xs">
                    Kelola →
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</template>
