<script setup>
import { ref, shallowRef, onMounted, onUnmounted, watch } from 'vue';
import * as echarts from 'echarts';

const props = defineProps({
  option: {
    type: Object,
    required: true,
  },
  height: {
    type: String,
    default: '340px',
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

const chartRef = ref(null);
const chartInstance = shallowRef(null);
let resizeObserver = null;
let themeObserver = null;

const isDarkMode = () => {
  if (typeof document === 'undefined') return false;
  return document.documentElement.classList.contains('dark');
};

const initChart = () => {
  if (!chartRef.value) return;

  if (chartInstance.value) {
    chartInstance.value.dispose();
  }

  const dark = isDarkMode();
  chartInstance.value = echarts.init(
    chartRef.value, 
    dark ? 'dark' : undefined, 
    { 
      renderer: 'canvas',
      backgroundColor: 'transparent',
    }
  );

  if (props.loading) {
    chartInstance.value.showLoading({
      color: '#6366f1',
      textColor: dark ? '#cbd5e1' : '#475569',
      maskColor: dark ? 'rgba(15, 23, 42, 0.6)' : 'rgba(255, 255, 255, 0.6)',
    });
  } else {
    chartInstance.value.setOption(props.option, true);
  }
};

onMounted(() => {
  initChart();

  // Responsive Auto-Resize via ResizeObserver
  if (typeof ResizeObserver !== 'undefined' && chartRef.value) {
    resizeObserver = new ResizeObserver(() => {
      if (chartInstance.value) {
        chartInstance.value.resize();
      }
    });
    resizeObserver.observe(chartRef.value);
  }

  // React to ThemeSwitcher (dark / light mode toggles)
  if (typeof MutationObserver !== 'undefined' && typeof document !== 'undefined') {
    themeObserver = new MutationObserver((mutations) => {
      for (const mutation of mutations) {
        if (mutation.attributeName === 'class') {
          initChart();
          break;
        }
      }
    });
    themeObserver.observe(document.documentElement, { attributes: true });
  }
});

watch(() => props.option, (newVal) => {
  if (chartInstance.value && newVal) {
    chartInstance.value.setOption(newVal, true);
  }
}, { deep: true });

watch(() => props.loading, (isLoading) => {
  if (!chartInstance.value) return;
  const dark = isDarkMode();
  if (isLoading) {
    chartInstance.value.showLoading({
      color: '#6366f1',
      textColor: dark ? '#cbd5e1' : '#475569',
      maskColor: dark ? 'rgba(15, 23, 42, 0.6)' : 'rgba(255, 255, 255, 0.6)',
    });
  } else {
    chartInstance.value.hideLoading();
  }
});

onUnmounted(() => {
  if (resizeObserver) {
    resizeObserver.disconnect();
    resizeObserver = null;
  }
  if (themeObserver) {
    themeObserver.disconnect();
    themeObserver = null;
  }
  if (chartInstance.value) {
    chartInstance.value.dispose();
    chartInstance.value = null;
  }
});

defineExpose({
  getEchartInstance: () => chartInstance.value,
  resize: () => chartInstance.value?.resize(),
});
</script>

<template>
  <div class="w-full relative">
    <div ref="chartRef" :style="{ height, width: '100%' }"></div>
  </div>
</template>
