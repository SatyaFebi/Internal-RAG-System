<script setup>
import { ref, reactive } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

const auth = useAuthStore();
const router = useRouter();

const form = reactive({
    email: '',
    password: '',
});

const error = ref('');
const loading = ref(false);

const handleLogin = async () => {
    loading.value = true;
    error.value = '';
    try {
        await auth.login(form);
        router.push({ name: 'home' });
    } catch (err) {
        error.value = err.response?.data?.message || 'Invalid credentials';
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <div class="min-h-screen flex items-center justify-center bg-zinc-950 px-4">
        <div class="w-full max-w-md">
            <!-- Glassmorphism Card -->
            <div class="bg-zinc-900/50 backdrop-blur-xl border border-zinc-800 p-8 rounded-2xl shadow-2xl">
                <div class="mb-8 text-center">
                    <h1 class="text-3xl font-bold text-white tracking-tight">Welcome</h1>
                    <p class="text-zinc-400 mt-2">Login to your account to continue</p>
                </div>

                <form @submit.prevent="handleLogin" class="space-y-6">
                    <div v-if="error" class="bg-red-500/10 border border-red-500/20 text-red-500 p-3 rounded-lg text-sm text-center">
                        {{ error }}
                    </div>

                    <div class="space-y-2">
                        <label for="email" class="text-sm font-medium text-zinc-300">Email Address</label>
                        <input 
                            v-model="form.email"
                            type="email" 
                            id="email" 
                            required
                            placeholder="admin@test.com"
                            class="w-full bg-zinc-800/50 border border-zinc-700 rounded-lg px-4 py-3 text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all"
                        >
                    </div>

                    <div class="space-y-2">
                        <label for="password" class="text-sm font-medium text-zinc-300">Password</label>
                        <input 
                            v-model="form.password"
                            type="password" 
                            id="password" 
                            required
                            placeholder="••••••••"
                            class="w-full bg-zinc-800/50 border border-zinc-700 rounded-lg px-4 py-3 text-white placeholder-zinc-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all"
                        >
                    </div>

                    <button 
                        type="submit" 
                        :disabled="loading"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition-all transform active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                    >
                        <span v-if="loading" class="mr-2 h-4 w-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                        {{ loading ? 'Signing in...' : 'Sign In' }}
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-zinc-800 text-center">
                    <p class="text-sm text-zinc-500">
                        Don't have an account? 
                        <a href="#" class="text-blue-500 hover:text-blue-400 font-medium ml-1">Contact admin</a>
                    </p>
                </div>
            </div>

            <!-- Footer Glow Decor -->
            <div class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-lg h-px bg-gradient-to-r from-transparent via-blue-500/50 to-transparent"></div>
        </div>
    </div>
</template>

<style scoped>
/* Optional: specific view transitions or extra micro-animations */
</style>
