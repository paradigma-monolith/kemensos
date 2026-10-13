<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { 
  LogIn, 
  Mail, 
  Lock, 
  Eye, 
  EyeOff, 
  ShieldCheck, 
  Sparkles, 
  CheckCircle2, 
  AlertCircle,
  Layers,
  Cpu
} from 'lucide-vue-next';

defineProps({
  status: {
    type: String,
    default: null,
  },
});

const showPassword = ref(false);

const getUrl = (path) => {
  if (typeof window !== 'undefined' && window.location.pathname.includes('/hybridlaravel/public')) {
    return `/hybridlaravel/public${path}`;
  }
  return path;
};

const form = useForm({
  email: '',
  password: '',
  remember: true,
});

const submit = () => {
  form.post(getUrl('/login'), {
    onFinish: () => form.reset('password'),
    onSuccess: () => {
      window.location.href = getUrl('/');
    },
  });
};

const fillDemoCredentials = () => {
  form.email = 'admin@hybrid.local';
  form.password = 'password';
  form.clearErrors();
};

const fillStaffCredentials = () => {
  form.email = 'staff@hybrid.local';
  form.password = 'password';
  form.clearErrors();
};
</script>

<template>
  <Head title="Masuk - Hybrid Laravel" />

  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative selection:bg-indigo-500 selection:text-white">
    <!-- Ambient Background Glows -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[600px] h-[350px] bg-gradient-to-tr from-indigo-600/20 via-purple-600/15 to-emerald-600/20 blur-3xl rounded-full"></div>
      <div class="absolute -bottom-40 right-10 w-[450px] h-[300px] bg-indigo-600/10 blur-3xl rounded-full"></div>
    </div>

    <!-- Header / Brand -->
    <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10 text-center">
      <div class="inline-flex items-center justify-center">
        <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center font-black text-white text-xl shadow-xl shadow-indigo-600/30 ring-1 ring-white/20">
          HL
        </div>
      </div>

      <div class="mt-4 inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-mono font-medium bg-indigo-950/80 border border-indigo-700/50 text-indigo-300">
        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
        <span>Sanctum Stateful Session Auth</span>
      </div>

      <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
        Hybrid Laravel Hub
      </h1>
      <p class="mt-2 text-sm text-slate-400">
        Satu sesi login terpadu untuk <strong class="text-indigo-400 font-semibold">Inertia.js Bridge</strong> dan <strong class="text-emerald-400 font-semibold">Traditional SPA</strong>.
      </p>
    </div>

    <!-- Main Card -->
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md relative z-10 px-4 sm:px-0">
      <div class="bg-slate-900/80 backdrop-blur-xl py-8 px-6 sm:px-10 shadow-2xl rounded-3xl border border-slate-800/80 relative">
        
        <!-- Status Flash Message -->
        <div v-if="status" class="mb-5 p-3 rounded-xl bg-emerald-950/60 border border-emerald-700/60 text-emerald-300 text-xs flex items-center gap-2">
          <CheckCircle2 class="w-4 h-4 shrink-0 text-emerald-400" />
          <span>{{ status }}</span>
        </div>

        <!-- Validation Error Alert Banner -->
        <div v-if="form.errors.email || form.errors.password" class="mb-5 p-3.5 rounded-xl bg-rose-950/60 border border-rose-800/60 text-rose-300 text-xs flex items-start gap-2.5">
          <AlertCircle class="w-4 h-4 shrink-0 text-rose-400 mt-0.5" />
          <div>
            <p class="font-semibold text-rose-200">Gagal Masuk</p>
            <p class="mt-0.5">{{ form.errors.email || form.errors.password }}</p>
          </div>
        </div>

        <!-- Login Form -->
        <form @submit.prevent="submit" class="space-y-5">
          <!-- Email Input -->
          <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
              Alamat Email
            </label>
            <div class="mt-1.5 relative rounded-xl shadow-sm">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                <Mail class="w-4 h-4" />
              </div>
              <input
                id="email"
                v-model="form.email"
                type="email"
                autocomplete="username"
                required
                placeholder="nama@contoh.com"
                class="block w-full pl-10 pr-3.5 py-2.5 text-sm bg-slate-950/70 border rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all font-mono"
                :class="form.errors.email ? 'border-rose-500/60 focus:border-rose-500' : 'border-slate-800 focus:border-indigo-500'"
              />
            </div>
            <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-400">
              {{ form.errors.email }}
            </p>
          </div>

          <!-- Password Input -->
          <div>
            <div class="flex items-center justify-between">
              <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                Kata Sandi
              </label>
            </div>
            <div class="mt-1.5 relative rounded-xl shadow-sm">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                <Lock class="w-4 h-4" />
              </div>
              <input
                id="password"
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                required
                placeholder="••••••••"
                class="block w-full pl-10 pr-10 py-2.5 text-sm bg-slate-950/70 border rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 transition-all font-mono"
                :class="form.errors.password ? 'border-rose-500/60 focus:border-rose-500' : 'border-slate-800 focus:border-indigo-500'"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-200 transition-colors"
                tabindex="-1"
              >
                <EyeOff v-if="showPassword" class="w-4 h-4" />
                <Eye v-else class="w-4 h-4" />
              </button>
            </div>
            <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-400">
              {{ form.errors.password }}
            </p>
          </div>

          <!-- Remember Me Checkbox & Forgot Password Link -->
          <div class="flex items-center justify-between pt-1">
            <label class="flex items-center gap-2 cursor-pointer select-none">
              <input
                v-model="form.remember"
                type="checkbox"
                class="w-4 h-4 rounded border-slate-700 bg-slate-950 text-indigo-600 focus:ring-indigo-500/30 focus:ring-offset-0 transition-colors cursor-pointer"
              />
              <span class="text-xs text-slate-300">Ingat saya</span>
            </label>

            <a :href="getUrl('/forgot-password')" class="text-xs text-indigo-400 hover:text-indigo-300 transition-colors">
              Lupa sandi?
            </a>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full mt-2 py-3 px-4 rounded-xl font-semibold text-sm text-white bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-slate-900 shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center gap-2 disabled:opacity-60 disabled:cursor-not-allowed group cursor-pointer"
          >
            <span v-if="form.processing" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
            <LogIn v-else class="w-4 h-4 group-hover:translate-x-0.5 transition-transform" />
            <span>{{ form.processing ? 'Memverifikasi...' : 'Masuk ke Platform' }}</span>
          </button>
        </form>

        <!-- Demo Account Auto-Fill Helper -->
        <div class="mt-6 pt-6 border-t border-slate-800/80">
          <div class="p-3.5 rounded-2xl bg-indigo-950/30 border border-indigo-800/40 hover:border-indigo-600/60 transition-all">
            <div class="flex items-center justify-between mb-2.5">
              <div class="flex items-center gap-1.5 text-xs font-semibold text-indigo-300">
                <Sparkles class="w-3.5 h-3.5 text-amber-400" />
                <span>Pilih Akun Demo Otomatis</span>
              </div>
            </div>

            <!-- 2-Option Button Grid -->
            <div class="grid grid-cols-2 gap-2 mb-2.5">
              <button
                type="button"
                @click="fillDemoCredentials"
                class="px-2.5 py-1.5 text-[11px] font-semibold rounded-xl bg-indigo-600/30 hover:bg-indigo-600/60 text-indigo-200 border border-indigo-500/40 transition-all text-left flex flex-col cursor-pointer"
              >
                <span>👑 Super Admin</span>
                <span class="text-[9px] font-mono text-indigo-300 font-normal">admin@hybrid.local</span>
              </button>

              <button
                type="button"
                @click="fillStaffCredentials"
                class="px-2.5 py-1.5 text-[11px] font-semibold rounded-xl bg-emerald-950/40 hover:bg-emerald-900/60 text-emerald-200 border border-emerald-700/50 transition-all text-left flex flex-col cursor-pointer"
              >
                <span>💼 Staff Operator</span>
                <span class="text-[9px] font-mono text-emerald-300 font-normal">staff@hybrid.local</span>
              </button>
            </div>

            <div class="font-mono text-[10px] text-slate-400 text-center">
              Password default: <span class="text-white font-bold">password</span>
            </div>
          </div>
        </div>

      </div>

      <!-- Architecture Footnote Pill -->
      <div class="mt-6 text-center space-y-2">
        <div class="inline-flex items-center gap-3 text-xs text-slate-400">
          <span class="inline-flex items-center gap-1">
            <ShieldCheck class="w-3.5 h-3.5 text-emerald-400" />
            <span>HTTP-Only Cookies</span>
          </span>
          <span class="text-slate-600">&bull;</span>
          <span class="inline-flex items-center gap-1">
            <Layers class="w-3.5 h-3.5 text-indigo-400" />
            <span>Inertia &amp; SPA Ready</span>
          </span>
          <span class="text-slate-600">&bull;</span>
          <span class="inline-flex items-center gap-1">
            <Cpu class="w-3.5 h-3.5 text-amber-400" />
            <span>Octane Fast Engine</span>
          </span>
        </div>
      </div>
    </div>
  </div>
</template>
