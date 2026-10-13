import { createRouter, createWebHistory } from 'vue-router';
import HomeView from '../views/HomeView.vue';
import ProductsView from '../views/ProductsView.vue';
import ApiExplorerView from '../views/ApiExplorerView.vue';

const routes = [
  {
    path: '/',
    name: 'spa-home',
    component: HomeView,
  },
  {
    path: '/products',
    name: 'spa-products',
    component: ProductsView,
  },
  {
    path: '/api-explorer',
    name: 'spa-api-explorer',
    component: ApiExplorerView,
  },
];

const isSubfolder = typeof window !== 'undefined' && window.location.pathname.includes('/hybridlaravel/public');
const base = isSubfolder ? '/hybridlaravel/public/spa' : '/spa';

const router = createRouter({
  history: createWebHistory(base),
  routes,
  scrollBehavior() {
    return { top: 0 };
  },
});

export default router;
