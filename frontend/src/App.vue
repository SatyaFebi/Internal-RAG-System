<script setup>
import { RouterLink, RouterView } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
</script>

<template>
  <div class="min-h-screen bg-zinc-950 font-sans antialiased text-zinc-300">
    <nav v-if="auth.isAuthenticated" class="h-16 border-b border-zinc-800 bg-zinc-900/50 backdrop-blur-md flex items-center justify-between px-8 sticky top-0 z-50">
      <div class="flex items-center gap-8">
        <RouterLink to="/" class="text-xl font-black text-white tracking-tighter hover:text-indigo-400 transition-colors">
          Satya <span class="text-indigo-500 hover:text-white transition-colors">RAG</span>
        </RouterLink>
        <div class="flex items-center gap-6">
          <RouterLink to="/" class="text-sm font-medium hover:text-white transition-colors" active-class="text-white">Dashboard</RouterLink>
          <RouterLink to="/chat" class="text-sm font-medium hover:text-white transition-colors" active-class="text-white">Chat AI</RouterLink>
        </div>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-xs text-zinc-500 px-3 py-1 border border-zinc-700 rounded-full">{{ auth.user?.email }}</span>
        <button @click="auth.logout" class="text-xs font-bold text-red-400 hover:text-red-300 transition-colors uppercase tracking-widest">Logout</button>
      </div>
    </nav>
    <main>
      <RouterView />
    </main>
  </div>
</template>

<style>
@import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

body {
  font-family: 'Plus Jakarta Sans', sans-serif;
}
</style>
