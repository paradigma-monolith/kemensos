<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { Lock, KeyRound, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
  token: {
    type: String,
    required: true,
  },
  email: {
    type: String,
    default: '',
  },
});

const getUrl = (path) => {
  if (typeof window !== 'undefined' && window.location.pathname.includes('/hybridlaravel/public')) {
    return `/hybridlaravel/public${path}`;
  }
  return path;
};

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post(getUrl('/reset-password'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <Head title="Buat Kata Sandi Baru - Sepatu SR" />

  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative selection:bg-emerald-500 selection:text-white">
    <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10 text-center">
      <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-600 via-teal-600 to-indigo-600 mx-auto flex items-center justify-center font-black text-white text-lg shadow-lg">
        SR
      </div>
      <h2 class="mt-4 text-2xl font-bold tracking-tight text-white">
        Perbarui Kata Sandi
      </h2>
      <p class="mt-1.5 text-xs text-slate-400">
        Masukkan kata sandi baru Anda untuk akun {{ email }}.
      </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md relative z-10 px-4 sm:px-0">
      <div class="bg-slate-900/80 backdrop-blur-xl py-8 px-6 sm:px-10 shadow-2xl rounded-3xl border border-slate-800/80">
        <form @submit.prevent="submit" class="space-y-4">
          <input type="hidden" v-model="form.token" />
          <input type="hidden" v-model="form.email" />

          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
              Kata Sandi Baru
            </label>
            <div class="mt-1.5 relative rounded-xl shadow-sm">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                <Lock class="w-4 h-4" />
              </div>
              <input
                v-model="form.password"
                type="password"
                required
                placeholder="Minimal 8 karakter"
                class="block w-full pl-10 pr-3.5 py-2.5 text-sm bg-slate-950/70 border rounded-xl text-white font-mono"
                :class="form.errors.password ? 'border-rose-500' : 'border-slate-800 focus:border-indigo-500'"
              />
            </div>
            <p v-if="form.errors.password" class="mt-1 text-xs text-rose-400">{{ form.errors.password }}</p>
          </div>

          <div>
            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
              Konfirmasi Kata Sandi
            </label>
            <div class="mt-1.5 relative rounded-xl shadow-sm">
              <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                <Lock class="w-4 h-4" />
              </div>
              <input
                v-model="form.password_confirmation"
                type="password"
                required
                placeholder="Ulangi kata sandi"
                class="block w-full pl-10 pr-3.5 py-2.5 text-sm bg-slate-950/70 border rounded-xl text-white font-mono"
                :class="form.errors.password_confirmation ? 'border-rose-500' : 'border-slate-800 focus:border-indigo-500'"
              />
            </div>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full mt-2 py-2.5 px-4 rounded-xl font-semibold text-xs text-white bg-indigo-600 hover:bg-indigo-500 active:bg-indigo-700 shadow-lg shadow-indigo-600/30 transition-all flex items-center justify-center gap-2 disabled:opacity-60 cursor-pointer"
          >
            <KeyRound class="w-3.5 h-3.5" />
            <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Kata Sandi Baru' }}</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
