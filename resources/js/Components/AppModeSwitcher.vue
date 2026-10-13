<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { useAppMode } from '../composables/useAppMode';
import { FlaskConical, Rocket, Check, Sparkles, ChevronDown } from 'lucide-vue-next';

const { appMode, setMode } = useAppMode();
const isOpen = ref(false);
const dropdownRef = ref(null);

const modes = [
  {
    id: 'demo',
    label: 'Mode Demo (Showcase)',
    desc: 'Menampilkan seluruh playground ECharts, MapLibre GL, dan data simulasi.',
    badge: 'Playground',
    badgeClass: 'bg-purple-100 dark:bg-purple-950 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800',
    icon: FlaskConical,
    iconColor: 'text-purple-500',
  },
  {
    id: 'production',
    label: 'Mode Produksi (Clean)',
    desc: 'Menyembunyikan dummy playground. Antarmuka operasional bisnis bersih.',
    badge: 'Production',
    badgeClass: 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
    icon: Rocket,
    iconColor: 'text-emerald-500',
  },
];

const selectMode = (modeId) => {
  setMode(modeId);
  isOpen.value = false;
};

const handleClickOutside = (e) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isOpen.value = false;
  }
};

onMounted(() => {
  if (typeof document !== 'undefined') {
    document.addEventListener('click', handleClickOutside);
  }
});

onUnmounted(() => {
  if (typeof document !== 'undefined') {
    document.removeEventListener('click', handleClickOutside);
  }
});
</script>

<template>
  <div class="relative font-sans" ref="dropdownRef">
    <!-- Trigger Pill Button -->
    <button
      type="button"
      @click="isOpen = !isOpen"
      class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl text-xs font-semibold border transition-all cursor-pointer group shadow-2xs"
      :class="appMode === 'demo'
        ? 'bg-purple-50 dark:bg-purple-950/70 text-purple-700 dark:text-purple-300 border-purple-200 dark:border-purple-800/70 hover:bg-purple-100 dark:hover:bg-purple-900/60'
        : 'bg-emerald-50 dark:bg-emerald-950/70 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800/70 hover:bg-emerald-100 dark:hover:bg-emerald-900/60'"
      :title="`Mode Aplikasi: ${appMode === 'demo' ? 'Mode Demo (Showcase)' : 'Mode Produksi'}. Klik untuk beralih.`"
    >
      <span class="relative flex h-2 w-2">
        <span 
          class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
          :class="appMode === 'demo' ? 'bg-purple-400' : 'bg-emerald-400'"
        ></span>
        <span 
          class="relative inline-flex rounded-full h-2 w-2"
          :class="appMode === 'demo' ? 'bg-purple-500' : 'bg-emerald-500'"
        ></span>
      </span>

      <component 
        :is="appMode === 'demo' ? FlaskConical : Rocket" 
        class="w-3.5 h-3.5 transition-transform group-hover:scale-110" 
      />

      <span class="hidden sm:inline">
        {{ appMode === 'demo' ? 'Demo Mode' : 'Produksi' }}
      </span>

      <ChevronDown class="w-3 h-3 text-slate-400 transition-transform" :class="{ 'rotate-180': isOpen }" />
    </button>

    <!-- Dropdown Menu -->
    <transition
      enter-active-class="transition-all duration-200 ease-out"
      enter-from-class="opacity-0 scale-95 -translate-y-1"
      enter-to-class="opacity-100 scale-100 translate-y-0"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100 scale-100 translate-y-0"
      leave-to-class="opacity-0 scale-95 -translate-y-1"
    >
      <div
        v-if="isOpen"
        class="absolute right-0 mt-2 w-72 bg-white/95 dark:bg-slate-900/95 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-2xl backdrop-blur-xl p-2 z-50 overflow-hidden"
      >
        <div class="px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 border-b border-slate-100 dark:border-slate-800/80 mb-1 flex items-center justify-between">
          <span>Mode Arsitektur Sistem</span>
          <span class="font-mono text-[9px] text-indigo-500">Hybrid Laravel</span>
        </div>

        <div class="space-y-1">
          <button
            v-for="m in modes"
            :key="m.id"
            type="button"
            @click="selectMode(m.id)"
            class="w-full flex items-start justify-between p-2.5 rounded-xl text-xs transition-all cursor-pointer group text-left border"
            :class="appMode === m.id
              ? (m.id === 'demo' 
                  ? 'bg-purple-50 dark:bg-purple-950/60 border-purple-200 dark:border-purple-800/60 text-purple-900 dark:text-purple-200 font-semibold' 
                  : 'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-200 dark:border-emerald-800/60 text-emerald-900 dark:text-emerald-200 font-semibold')
              : 'border-transparent text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800/60 hover:text-slate-900 dark:hover:text-white'"
          >
            <div class="flex items-start gap-2.5">
              <div 
                class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5 transition-colors"
                :class="appMode === m.id
                  ? (m.id === 'demo' ? 'bg-purple-100 dark:bg-purple-900/60 text-purple-600' : 'bg-emerald-100 dark:bg-emerald-900/60 text-emerald-600')
                  : 'bg-slate-100 dark:bg-slate-800 text-slate-500 group-hover:text-slate-900 dark:group-hover:text-white'"
              >
                <component :is="m.icon" class="w-4 h-4" />
              </div>

              <div>
                <div class="flex items-center gap-1.5">
                  <span class="font-bold leading-tight">{{ m.label }}</span>
                  <span class="text-[9px] font-mono px-1.5 py-0.2 rounded border" :class="m.badgeClass">
                    {{ m.badge }}
                  </span>
                </div>
                <span class="block text-[10px] text-slate-500 dark:text-slate-400 mt-1 leading-relaxed font-normal">
                  {{ m.desc }}
                </span>
              </div>
            </div>

            <Check 
              v-if="appMode === m.id" 
              class="w-4 h-4 flex-shrink-0 mt-1" 
              :class="m.id === 'demo' ? 'text-purple-600 dark:text-purple-400' : 'text-emerald-600 dark:text-emerald-400'"
            />
          </button>
        </div>

        <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-800/80 px-2 text-[10px] text-slate-400 leading-tight">
          💡 Mode tersimpan di browser dan langsung menyaring navigasi sidebar & navbar.
        </div>
      </div>
    </transition>
  </div>
</template>
