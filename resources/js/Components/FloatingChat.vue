<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { getEcho } from '../echo.js';
import { 
  MessageSquare, 
  Send, 
  X, 
  Minimize2, 
  Maximize2, 
  Radio, 
  Sparkles, 
  User as UserIcon,
  CheckCheck,
  Bot,
  Volume2,
  VolumeX,
  RotateCw
} from 'lucide-vue-next';

const props = defineProps({
  mode: {
    type: String,
    default: 'inertia', // 'inertia' | 'spa'
  },
});

const isSpa = computed(() => props.mode === 'spa');

const prefix = computed(() => {
  if (typeof window !== 'undefined' && window.location.pathname.includes('/hybridlaravel/public')) {
    return '/hybridlaravel/public';
  }
  return '';
});

const getUrl = (path) => `${prefix.value}${path}`;

// Safely resolve active authenticated user
const currentUser = computed(() => {
  if (isSpa.value) {
    return (typeof window !== 'undefined' && window.authUser) ? window.authUser : null;
  }
  try {
    const page = usePage();
    return page?.props?.auth?.user || null;
  } catch (e) {
    return (typeof window !== 'undefined' && window.authUser) ? window.authUser : null;
  }
});

// Chat UI State
const isOpen = ref(false);
const isExpanded = ref(false);
const isMuted = ref(false);
const inputMessage = ref('');
const isSending = ref(false);
const isTriggeringBot = ref(false);
const sendError = ref('');
const unreadCount = ref(0);
const messages = ref([]);
const messagesContainer = ref(null);
const wsConnected = ref(false);
let pollTimer = null;

// Retrieve CSRF token from document meta tag if available
const getCsrfToken = () => {
  if (typeof document !== 'undefined') {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) {
      return meta.getAttribute('content') || '';
    }
  }
  return '';
};

const quickReplies = [
  '👋 Halo tim Hybrid Laravel!',
  '🚀 Reverb WebSocket berjalan sub-10ms!',
  '⚡ Arsitektur modular siap digunakan.',
  '📦 Coba input produk baru.'
];

// Play a synthesized chime using Web Audio API
const playChime = () => {
  if (isMuted.value || typeof window === 'undefined') return;
  try {
    const AudioCtx = window.AudioContext || window.webkitAudioContext;
    if (!AudioCtx) return;
    const ctx = new AudioCtx();
    const osc = ctx.createOscillator();
    const gain = ctx.createGain();

    osc.type = 'sine';
    osc.frequency.setValueAtTime(587.33, ctx.currentTime); // D5
    osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.12); // A5

    gain.gain.setValueAtTime(0.12, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.35);

    osc.connect(gain);
    gain.connect(ctx.destination);

    osc.start();
    osc.stop(ctx.currentTime + 0.35);
  } catch (e) {}
};

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
  });
};

const toggleChat = () => {
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    unreadCount.value = 0;
    scrollToBottom();
    startPolling();
  } else {
    stopPolling();
  }
};

// Fetch latest chat history
const fetchMessages = async (silent = false) => {
  try {
    const res = await fetch(getUrl('/chat/messages'), {
      headers: { 'Accept': 'application/json' },
    });
    if (res.ok) {
      const json = await res.json();
      if (json.data && Array.isArray(json.data)) {
        const previousLength = messages.value.length;
        
        // Merge without duplicating
        const existingIds = new Set(messages.value.map(m => m.id));
        let hasNew = false;
        
        json.data.forEach(msg => {
          if (!existingIds.has(msg.id)) {
            messages.value.push(msg);
            existingIds.add(msg.id);
            hasNew = true;
          }
        });

        if (hasNew || previousLength === 0) {
          scrollToBottom();
          if (silent && hasNew && !isOpen.value) {
            unreadCount.value++;
            playChime();
          }
        }
      }
    }
  } catch (e) {
    if (!silent) console.warn('[FloatingChat] Gagal memuat pesan:', e);
  }
};

// Start background interval polling when chat is open as fallback
const startPolling = () => {
  if (pollTimer) clearInterval(pollTimer);
  pollTimer = setInterval(() => {
    if (isOpen.value) {
      fetchMessages(true);
    }
  }, 4000);
};

const stopPolling = () => {
  if (pollTimer) {
    clearInterval(pollTimer);
    pollTimer = null;
  }
};

// Send message via POST /chat/messages
const sendMessage = async (textToSend = null) => {
  const content = (typeof textToSend === 'string' ? textToSend : inputMessage.value).trim();
  if (!content || isSending.value) return;

  isSending.value = true;
  sendError.value = '';

  try {
    const token = getCsrfToken();
    const res = await fetch(getUrl('/chat/messages'), {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      body: JSON.stringify({ message: content }),
    });

    if (res.ok) {
      const data = await res.json();
      if (data.message) {
        if (!messages.value.some(m => m.id === data.message.id)) {
          messages.value.push(data.message);
          scrollToBottom();
        }
      }
      inputMessage.value = '';
    } else {
      if (res.status === 401) {
        sendError.value = 'Sesi login telah berakhir. Silakan login kembali.';
      } else if (res.status === 403) {
        sendError.value = 'Anda tidak memiliki hak akses untuk mengirim chat.';
      } else {
        const errorData = await res.json().catch(() => null);
        sendError.value = errorData?.message || `Gagal mengirim pesan (HTTP ${res.status}).`;
      }
      console.error('[FloatingChat] Send status:', res.status);
    }
  } catch (e) {
    sendError.value = 'Koneksi terputus. Silakan coba beberapa saat lagi.';
    console.error('[FloatingChat] Send error:', e);
  } finally {
    isSending.value = false;
  }
};

// Trigger automated bot reply to test real-time reception in 1 window
const triggerDemoReply = async () => {
  if (isTriggeringBot.value) return;
  isTriggeringBot.value = true;
  sendError.value = '';

  try {
    const token = getCsrfToken();
    const res = await fetch(getUrl('/chat/demo-reply'), {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
    });

    if (res.ok) {
      const data = await res.json();
      if (data.message && !messages.value.some(m => m.id === data.message.id)) {
        messages.value.push(data.message);
        scrollToBottom();
        playChime();
      }
    }
  } catch (e) {
    console.warn('[FloatingChat] Demo bot reply error:', e);
  } finally {
    isTriggeringBot.value = false;
  }
};

onMounted(() => {
  fetchMessages();

  // Initialize and listen to Reverb WebSocket channel 'chat-channel'
  try {
    const echoInstance = getEcho();
    if (echoInstance) {
      const channel = echoInstance.channel('chat-channel');
      wsConnected.value = true;

      channel.listen('.chat.message', (event) => {
        if (!messages.value.some(m => m.id === event.id)) {
          messages.value.push({
            id: event.id,
            user_id: event.user_id,
            user_name: event.user_name,
            user_avatar: event.user_avatar,
            message: event.message,
            created_at: event.created_at,
          });

          if (!isOpen.value) {
            unreadCount.value++;
          } else {
            scrollToBottom();
          }

          if (event.user_id !== currentUser.value?.id) {
            playChime();
          }
        }
      });
    }
  } catch (err) {
    console.warn('[FloatingChat] Echo subscription error:', err);
  }
});

onUnmounted(() => {
  stopPolling();
  try {
    const echoInstance = getEcho();
    if (echoInstance) {
      echoInstance.leaveChannel('chat-channel');
    }
  } catch (e) {}
});
</script>

<template>
  <!-- Fixed Floating Chat Container strictly positioned at Bottom-Right -->
  <div class="fixed bottom-4 right-4 sm:bottom-5 sm:right-5 z-50 font-sans">
    
    <!-- Expanded Chat Window Panel -->
    <transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 translate-y-6 scale-95 origin-bottom-right"
      enter-to-class="opacity-100 translate-y-0 scale-100 origin-bottom-right"
      leave-active-class="transition-all duration-200 ease-in"
      leave-from-class="opacity-100 translate-y-0 scale-100 origin-bottom-right"
      leave-to-class="opacity-0 translate-y-6 scale-95 origin-bottom-right"
    >
      <div 
        v-if="isOpen"
        class="mb-3 bg-slate-900/95 border border-slate-700/80 rounded-2xl shadow-2xl backdrop-blur-xl flex flex-col overflow-hidden transition-all duration-200"
        :class="isExpanded ? 'w-[calc(100vw-2rem)] sm:w-[480px] max-w-[480px] h-[min(600px,calc(100vh-100px))]' : 'w-[calc(100vw-2rem)] sm:w-[380px] max-w-[380px] h-[min(500px,calc(100vh-100px))]'"
      >
        <!-- Chat Header -->
        <div class="px-4 py-3 bg-gradient-to-r from-slate-900 via-indigo-950/70 to-slate-900 border-b border-slate-800 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="relative">
              <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-indigo-600 to-cyan-500 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
                <MessageSquare class="w-4 h-4" />
              </div>
              <span 
                class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full ring-2 ring-slate-900 animate-pulse"
                :class="wsConnected ? 'bg-emerald-400' : 'bg-cyan-400'"
                :title="wsConnected ? 'Reverb WebSocket Aktif' : 'Polling Synced'"
              ></span>
            </div>
            <div>
              <div class="flex items-center gap-1.5">
                <span class="text-xs font-bold text-white tracking-tight">Hybrid Reverb Chat</span>
                <span v-if="currentUser" class="text-[9px] font-mono px-1.5 py-0.2 rounded bg-indigo-950 text-indigo-300 border border-indigo-800">
                  {{ currentUser.name }} ({{ currentUser.roles?.[0] || 'User' }})
                </span>
              </div>
              <div class="text-[10px] text-slate-400 flex items-center gap-1">
                <Radio class="w-2.5 h-2.5 text-emerald-400 animate-pulse" />
                <span>Channel: <strong class="text-slate-300">chat-channel</strong></span>
              </div>
            </div>
          </div>

          <!-- Window Controls -->
          <div class="flex items-center gap-1 text-slate-400">
            <!-- Mute Toggle -->
            <button
              type="button"
              @click="isMuted = !isMuted"
              class="p-1 rounded-lg hover:text-white hover:bg-slate-800 transition-colors cursor-pointer"
              :title="isMuted ? 'Bunyikan nada' : 'Bisukan nada'"
            >
              <VolumeX v-if="isMuted" class="w-3.5 h-3.5 text-rose-400" />
              <Volume2 v-else class="w-3.5 h-3.5 text-slate-400" />
            </button>

            <!-- Refresh Messages -->
            <button
              type="button"
              @click="fetchMessages()"
              class="p-1 rounded-lg hover:text-white hover:bg-slate-800 transition-colors cursor-pointer"
              title="Perbarui riwayat"
            >
              <RotateCw class="w-3.5 h-3.5" />
            </button>

            <!-- Expand / Minimize -->
            <button 
              type="button" 
              @click="isExpanded = !isExpanded" 
              class="p-1 rounded-lg hover:text-white hover:bg-slate-800 transition-colors cursor-pointer"
              :title="isExpanded ? 'Perkecil jendela' : 'Perbesar jendela'"
            >
              <Minimize2 v-if="isExpanded" class="w-3.5 h-3.5" />
              <Maximize2 v-else class="w-3.5 h-3.5" />
            </button>

            <!-- Close Chat -->
            <button 
              type="button" 
              @click="toggleChat" 
              class="p-1 rounded-lg hover:text-white hover:bg-slate-800 transition-colors cursor-pointer"
              title="Tutup obrolan"
            >
              <X class="w-3.5 h-3.5" />
            </button>
          </div>
        </div>

        <!-- Chat Stream Body -->
        <div 
          ref="messagesContainer"
          class="flex-1 overflow-y-auto p-4 space-y-3.5 text-xs bg-slate-950/50 scroll-smooth"
        >
          <!-- Intro Beacon Card with Instant Demo Test Trigger -->
          <div class="p-3 rounded-xl bg-indigo-950/30 border border-indigo-900/40 text-[11px] text-indigo-200">
            <div class="flex items-start justify-between gap-2">
              <div class="flex items-start gap-2">
                <Sparkles class="w-4 h-4 text-cyan-400 flex-shrink-0 mt-0.5" />
                <div>
                  <p class="font-semibold text-white">Live WebSocket Hub</p>
                  <p class="text-slate-300 text-[10px] mt-0.5 leading-relaxed">
                    Pesan disiarkan instan ke seluruh pengguna melalui <strong>Laravel Reverb</strong> & basis data MySQL.
                  </p>
                </div>
              </div>
            </div>

            <!-- Instant Demo Bot Reply Trigger -->
            <div class="mt-2 pt-2 border-t border-indigo-900/40 flex items-center justify-between">
              <span class="text-[10px] text-slate-400">Uji pesan real-time otomatis:</span>
              <button
                type="button"
                @click="triggerDemoReply"
                :disabled="isTriggeringBot"
                class="px-2 py-0.5 text-[10px] font-semibold rounded bg-cyan-950 text-cyan-300 hover:bg-cyan-900 border border-cyan-800/80 transition-all flex items-center gap-1 cursor-pointer disabled:opacity-50"
              >
                <Bot class="w-3 h-3 text-cyan-400" />
                <span>{{ isTriggeringBot ? 'Mengirim...' : '⚡ Tes Balas Otomatis' }}</span>
              </button>
            </div>
          </div>

          <!-- Empty State -->
          <div v-if="messages.length === 0" class="py-12 text-center text-slate-500">
            <Bot class="w-8 h-8 mx-auto mb-2 text-slate-600" />
            <p class="text-xs font-medium">Belum ada obrolan.</p>
            <p class="text-[10px] text-slate-600 mt-0.5">Ketik pesan pertama Anda di bawah ini!</p>
          </div>

          <!-- Messages Stream -->
          <div 
            v-for="msg in messages" 
            :key="msg.id"
            class="flex items-end gap-2"
            :class="msg.user_id === currentUser?.id ? 'justify-end' : 'justify-start'"
          >
            <!-- Other User Avatar -->
            <img 
              v-if="msg.user_id !== currentUser?.id"
              :src="msg.user_avatar || 'https://ui-avatars.com/api/?name=Guest&background=6366f1&color=fff'"
              :alt="msg.user_name"
              class="w-6 h-6 rounded-full object-cover border border-slate-700 flex-shrink-0 mb-1"
            />

            <!-- Message Bubble -->
            <div 
              class="max-w-[78%] rounded-2xl px-3.5 py-2 transition-all"
              :class="msg.user_id === currentUser?.id 
                ? 'bg-gradient-to-r from-indigo-600 to-indigo-700 text-white rounded-br-xs shadow-md shadow-indigo-600/20' 
                : 'bg-slate-800/90 text-slate-200 rounded-bl-xs border border-slate-700/60 shadow-sm'"
            >
              <!-- Sender Name (if not current user) -->
              <div 
                v-if="msg.user_id !== currentUser?.id" 
                class="text-[10px] font-bold text-teal-300 mb-0.5 flex items-center gap-1"
              >
                <span>{{ msg.user_name }}</span>
              </div>

              <!-- Message Text -->
              <p class="text-[12px] leading-relaxed break-words whitespace-pre-wrap">
                {{ msg.message }}
              </p>

              <!-- Timestamp & Status -->
              <div 
                class="flex items-center justify-end gap-1 mt-1 text-[9px] font-mono"
                :class="msg.user_id === currentUser?.id ? 'text-indigo-200' : 'text-slate-400'"
              >
                <span>{{ msg.created_at || 'now' }}</span>
                <CheckCheck v-if="msg.user_id === currentUser?.id" class="w-3 h-3 text-cyan-300" />
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Replies Chips -->
        <div class="px-3 py-1.5 bg-slate-900/90 border-t border-slate-800/80 overflow-x-auto flex items-center gap-1.5 scrollbar-none">
          <button
            v-for="(chip, index) in quickReplies"
            :key="index"
            type="button"
            @click="sendMessage(chip)"
            class="flex-shrink-0 text-[10px] px-2 py-0.5 rounded-full bg-slate-800/80 hover:bg-indigo-950 hover:text-indigo-200 border border-slate-700 hover:border-indigo-700/60 text-slate-300 transition-all cursor-pointer whitespace-nowrap"
          >
            {{ chip }}
          </button>
        </div>

        <!-- Send Error Alert Banner -->
        <div 
          v-if="sendError" 
          class="px-3.5 py-2 bg-rose-950/90 border-t border-rose-800/80 text-[11px] text-rose-200 flex items-center justify-between gap-2"
        >
          <div class="flex items-center gap-1.5 flex-1">
            <span class="w-1.5 h-1.5 rounded-full bg-rose-400 animate-ping"></span>
            <span>{{ sendError }}</span>
          </div>
          <button 
            type="button" 
            @click="sendError = ''" 
            class="text-rose-400 hover:text-white p-0.5 rounded cursor-pointer"
          >
            <X class="w-3.5 h-3.5" />
          </button>
        </div>

        <!-- Chat Input Footer -->
        <form 
          @submit.prevent="sendMessage()" 
          class="p-2.5 bg-slate-900 border-t border-slate-800 flex items-center gap-2"
        >
          <input
            v-model="inputMessage"
            type="text"
            maxlength="1000"
            placeholder="Tulis pesan real-time... (Enter)"
            class="flex-1 bg-slate-950 border border-slate-800 focus:border-indigo-500 rounded-xl px-3 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 transition-all"
            :disabled="isSending"
            @keydown.enter.exact.prevent="sendMessage()"
          />

          <button
            type="submit"
            :disabled="!inputMessage.trim() || isSending"
            class="px-3.5 py-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 disabled:opacity-40 disabled:cursor-not-allowed text-white text-xs font-semibold shadow-md shadow-indigo-600/30 transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <span v-if="isSending" class="w-3 h-3 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            <Send v-else class="w-3.5 h-3.5" />
            <span class="hidden sm:inline">Kirim</span>
          </button>
        </form>
      </div>
    </transition>

    <!-- Floating Toggle Pill (Strictly Visible at Bottom-Right) -->
    <div class="flex justify-end">
      <button
        type="button"
        @click="toggleChat"
        class="group relative flex items-center gap-2.5 px-3.5 py-2.5 rounded-full bg-slate-900/95 hover:bg-slate-850 text-white border border-indigo-500/40 hover:border-indigo-400 shadow-xl shadow-indigo-950/60 backdrop-blur-xl transition-all duration-300 transform hover:scale-105 active:scale-95 cursor-pointer"
        :class="isOpen ? 'ring-2 ring-indigo-500 ring-offset-2 ring-offset-slate-950' : ''"
        title="Buka Live Chat Reverb (Kanan Bawah)"
      >
        <!-- Chat Icon with Glowing Pulse -->
        <div class="relative flex items-center justify-center w-7 h-7 rounded-full bg-gradient-to-tr from-indigo-600 to-violet-500 text-white shadow-md shadow-indigo-500/30 group-hover:from-indigo-500 group-hover:to-cyan-500 transition-all">
          <MessageSquare class="w-3.5 h-3.5" />
          <span 
            class="absolute -top-0.5 -right-0.5 w-2.5 h-2.5 rounded-full ring-2 ring-slate-900 animate-pulse"
            :class="wsConnected ? 'bg-emerald-400' : 'bg-cyan-400'"
          ></span>
        </div>

        <!-- Text Label -->
        <div class="text-left pr-1">
          <span class="block text-xs font-bold text-white tracking-tight group-hover:text-indigo-200 transition-colors">
            Live Chat
          </span>
          <span class="block text-[9px] font-mono leading-none" :class="wsConnected ? 'text-emerald-400' : 'text-cyan-400'">
            {{ wsConnected ? 'Reverb Active' : 'Online' }}
          </span>
        </div>

        <!-- Unread Badge -->
        <span 
          v-if="unreadCount > 0" 
          class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-500 text-white animate-bounce shadow-md shadow-rose-500/50"
        >
          {{ unreadCount > 9 ? '9+' : unreadCount }}
        </span>
      </button>
    </div>

  </div>
</template>
