<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3';
import { Mail, ArrowLeft, Send, CheckCircle2, AlertCircle } from 'lucide-vue-next';

defineProps({
  status: {
    type: String,
    default: null,
  },
});

const getUrl = (path) => {
  if (typeof window !== 'undefined' && window.location.pathname.includes('/hybridlaravel/public')) {
    return `/hybridlaravel/public${path}`;
  }
  return path;
};

const form = useForm({
  email: '',
});

const submit = () => {
  form.post(getUrl('/forgot-password'));
};
</script>

<template>
  <Head title="Lupa Kata Sandi - Sepatu SR" />

  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative selection:bg-emerald-500 selection:text-white">
    <!-- Glow -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[550px] h-[300px] bg-emerald-600/15 blur-3xl rounded-full"></div>
    </div>

    <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10 text-center">
      <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-600 via-teal-600 to-indigo-600 mx-auto flex items-center justify-center font-black text-white text-lg shadow-lg shadow-emerald-600/30">
        SR
      </div>
      <h2 class="mt-4 text-2xl font-bold tracking-tight text-white">
        Atur Ulang Kata Sandi
      </h2>
      <p class="mt-2 text-xs text-slate-400 max-w-sm mx-auto">
        Masukkan email akun Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi.
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md relative z-10 px-4 sm:px-0">
      <div class="bg-slate-900/80 backdrop-blur-xl py-8 px-6 sm:px-10 shadow-2xl rounded-3xl border border-slate-800/80">
        <!-- Flash Status -->
        <div v-if="status" class="mb-5 p-3 rounded-xl bg-emerald-950/60 border border-emerald-700/60 text-emerald-300 text-xs flex items-center gap-2">
          <CheckCircle2 class="w-4 h-4 shrink-0 text-emerald-400" />
          <span>{{ status }}</span>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
              Alamat Email Terdaftar
            </label>
            <div class="mt-1.5 relative rounded-xl shadow-sm">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                <Mail class="w-4 h-4" />
              </div>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                placeholder="nama@contoh.com"
                class="block w-full pl-10 pr-3.5 py-2.5 text-sm bg-slate-950/70 border rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 font-mono"
                :class="form.errors.email ? 'border-rose-500' : 'border-slate-800 focus:border-indigo-500'"
              />
            </div>
            <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-400">
              {{ form.errors.email }}
            </p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full py-2.5 px-4 rounded-xl font-semibold text-xs text-white bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center gap-2 disabled:opacity-60 cursor-pointer"
          >
            <Send class="w-3.5 h-3.5" />
            <span>{{ form.processing ? 'Mengirim...' : 'Kirim Tautan Reset' }}</span>
          </button>
        </form>

        <div class="mt-6 pt-5 border-t border-slate-800 text-center">
          <a :href="getUrl('/login')" class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-white transition-colors">
            <ArrowLeft class="w-3.5 h-3.5" />
            <span>Kembali ke Halaman Masuk</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>
