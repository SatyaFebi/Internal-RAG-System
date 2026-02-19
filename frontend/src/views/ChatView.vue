<script setup>
import { ref, onMounted, nextTick } from 'vue';
import axios from '@/lib/axios';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const chatHistory = ref([
    { role: 'assistant', content: 'Halo! Saya asisten AI RAG Anda. Silakan upload dokumen atau tanyakan apa pun terkait data yang sudah saya pelajari.' }
]);
const userMessage = ref('');
const isLoading = ref(false);
const isUploading = ref(false);
const fileInput = ref(null);
const uploadStatus = ref('');
const messagesContainer = ref(null);

const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const sendChat = async () => {
    if (!userMessage.value.trim() || isLoading.value) return;

    const message = userMessage.value;
    chatHistory.value.push({ role: 'user', content: message });
    userMessage.value = '';
    isLoading.value = true;
    scrollToBottom();

    try {
        const res = await axios.post('/chat', { message });
        chatHistory.value.push({ 
            role: 'assistant', 
            content: res.data.answer,
            context: res.data.context_used 
        });
    } catch (error) {
        console.error(error);
        chatHistory.value.push({ role: 'assistant', content: 'Maaf, terjadi kesalahan saat menghubungi server AI.' });
    } finally {
        isLoading.value = false;
        scrollToBottom();
    }
};

const handleFileUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    isUploading.value = true;
    uploadStatus.value = 'Sedang mengupload dan mempelajari dokumen...';

    try {
        const res = await axios.post('/ingest', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        uploadStatus.value = res.data.message;
        chatHistory.value.push({ 
            role: 'assistant', 
            content: `Saya telah berhasil mempelajari dokumen: **${file.name}**. Sekarang Anda bisa bertanya tentang isinya!` 
        });
    } catch (error) {
        console.error(error);
        uploadStatus.value = 'Gagal mengupload dokumen.';
    } finally {
        isUploading.value = false;
        event.target.value = ''; // Reset input
        scrollToBottom();
    }
};

const triggerFileInput = () => {
    fileInput.value.click();
};

onMounted(() => {
    scrollToBottom();
});
</script>

<template>
    <div class="flex h-[calc(100vh-64px)] bg-zinc-950 text-zinc-200 overflow-hidden">
        <!-- Sidebar Upload -->
        <div class="w-80 bg-zinc-900/50 border-r border-zinc-800 p-6 hidden lg:flex flex-col">
            <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Knowledge Base
            </h2>
            
            <div 
                @click="triggerFileInput"
                class="border-2 border-dashed border-zinc-700 rounded-xl p-8 text-center hover:border-indigo-500 hover:bg-indigo-500/5 transition-all cursor-pointer group mb-6"
            >
                <input 
                    type="file" 
                    ref="fileInput" 
                    class="hidden" 
                    @change="handleFileUpload"
                    accept=".pdf,.txt,.md"
                >
                <div class="bg-indigo-500/10 w-12 h-12 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                </div>
                <p class="text-sm font-medium text-zinc-300">Upload Dokumen</p>
                <p class="text-xs text-zinc-500 mt-2">PDF, TXT, MD (Max 10MB)</p>
            </div>

            <div v-if="isUploading" class="mb-4">
                <div class="flex items-center gap-2 text-xs text-indigo-400 animate-pulse">
                    <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    {{ uploadStatus }}
                </div>
            </div>

            <div class="flex-1 overflow-y-auto">
                <h3 class="text-xs uppercase tracking-widest text-zinc-500 font-bold mb-4">Instruksi</h3>
                <ul class="text-sm space-y-4 text-zinc-400">
                    <li class="flex gap-3">
                        <span class="text-indigo-500 font-bold">1.</span>
                        Upload dokumen yang ingin Anda tanyakan.
                    </li>
                    <li class="flex gap-3">
                        <span class="text-indigo-500 font-bold">2.</span>
                        Tunggu AI memproses embedding (vektorisasi).
                    </li>
                    <li class="flex gap-3">
                        <span class="text-indigo-500 font-bold">3.</span>
                        Mulai chat untuk mendapatkan informasi dari dokumen tersebut.
                    </li>
                </ul>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="flex-1 flex flex-col relative">
            <!-- Header -->
            <div class="h-16 border-b border-zinc-800 flex items-center justify-between px-8 bg-zinc-950/50 backdrop-blur-md sticky top-0 z-10">
                <div class="flex items-center gap-3">
                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                    <div>
                        <h1 class="font-bold text-white">RAG Assistant</h1>
                        <p class="text-xs text-zinc-500">Llama 3.2</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="lg:hidden">
                        <button @click="triggerFileInput" class="p-2 bg-zinc-800 rounded-lg hover:bg-zinc-700 transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div 
                ref="messagesContainer"
                class="flex-1 overflow-y-auto p-8 space-y-6 scroll-smooth"
            >
                <div 
                    v-for="(chat, index) in chatHistory" 
                    :key="index"
                    class="flex flex-col"
                    :class="chat.role === 'user' ? 'items-end' : 'items-start'"
                >
                    <div 
                        class="max-w-[80%] px-5 py-3 rounded-2xl shadow-sm leading-relaxed"
                        :class="chat.role === 'user' 
                            ? 'bg-indigo-600 text-white rounded-tr-none' 
                            : 'bg-zinc-800 text-zinc-100 rounded-tl-none border border-zinc-700'"
                    >
                        <div v-if="chat.role === 'assistant'" class="flex items-center gap-2 mb-1">
                            <span class="text-[10px] uppercase font-bold tracking-widest text-indigo-400">AI Assistant</span>
                        </div>
                        <p class="whitespace-pre-wrap text-sm md:text-base">{{ chat.content }}</p>
                        
                        <!-- Context used badge -->
                        <div v-if="chat.context && chat.context.length > 0" class="mt-3 pt-3 border-t border-zinc-700">
                            <p class="text-[10px] text-zinc-500 uppercase font-bold mb-2">Sumber Konteks:</p>
                            <div class="flex flex-wrap gap-2">
                                <span v-for="(ctx, i) in chat.context" :key="i" class="text-[10px] bg-zinc-900 px-2 py-1 rounded border border-zinc-700 text-zinc-400">
                                    Snippet #{{ i + 1 }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="isLoading" class="flex flex-col items-start px-8">
                    <div class="flex gap-2 p-3 bg-zinc-800 rounded-2xl rounded-tl-none border border-zinc-700">
                        <div class="w-2 h-2 bg-indigo-500 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-indigo-500 rounded-full animate-bounce [animation-delay:0.2s]"></div>
                        <div class="w-2 h-2 bg-indigo-500 rounded-full animate-bounce [animation-delay:0.4s]"></div>
                    </div>
                </div>
            </div>

            <!-- Input -->
            <div class="p-6 bg-zinc-950/80 backdrop-blur-sm border-t border-zinc-800">
                <div class="max-w-4xl mx-auto relative group">
                    <textarea 
                        v-model="userMessage"
                        @keydown.enter.prevent="sendChat"
                        placeholder="Tanyakan sesuatu tentang dokumen Anda..."
                        class="w-full bg-zinc-900 border border-zinc-800 rounded-2xl px-6 py-4 pr-16 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 placeholder-zinc-600 transition-all resize-none shadow-xl"
                        rows="1"
                    ></textarea>
                    <button 
                        @click="sendChat"
                        :disabled="!userMessage.trim() || isLoading"
                        class="absolute right-3 top-1/2 -translate-y-1/2 w-10 h-10 bg-indigo-600 text-white rounded-xl flex items-center justify-center hover:bg-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg active:scale-95"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z" />
                        </svg>
                    </button>
                </div>
                <p class="text-center text-[10px] text-zinc-500 mt-4">Pesan Anda diproses menggunakan asisten AI lokal dengan konteks dokumen yang diunggah.</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Hide scrollbar for Chrome, Safari and Opera */
.overflow-y-auto::-webkit-scrollbar {
    display: none;
}

/* Hide scrollbar for IE, Edge and Firefox */
.overflow-y-auto {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}
</style>
