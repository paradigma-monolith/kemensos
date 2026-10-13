<script setup>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import HybridNavbar from '../Components/HybridNavbar.vue';

const route = useRoute();

const currentNav = computed(() => {
  if (route.name === 'spa-products') return 'products';
  if (route.name === 'spa-api-explorer') return 'api-explorer';
  return 'home';
});
</script>

<template>
  <div class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 selection:bg-emerald-500 selection:text-white transition-colors duration-200">
    <!-- Hybrid Navbar in SPA Mode -->
    <HybridNavbar mode="spa" :currentRoute="currentNav" />

    <!-- Router View Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}

.fade-enter-from {
  opacity: 0;
  transform: translateY(6px);
}

.fade-leave-to {
  opacity: 0;
  transform: translateY(-6px);
}
</style>
