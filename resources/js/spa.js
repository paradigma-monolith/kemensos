import { createApp } from 'vue';
import { createPinia } from 'pinia';
import axios from 'axios';
import router from './spa/router';
import App from './spa/App.vue';
import { initSentry } from './sentry';
import './echo';

// Set Axios baseURL if hosted in Apache subfolder
if (typeof window !== 'undefined' && window.location.pathname.includes('/hybridlaravel/public')) {
  axios.defaults.baseURL = '/hybridlaravel/public';
}

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

initSentry(app, router);

app.mount('#spa-app');
