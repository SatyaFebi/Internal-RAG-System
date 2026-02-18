<script setup>
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
</script>

<template>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="bg-zinc-900 border border-zinc-800 rounded-2xl p-8 shadow-xl">
      <h1 class="text-3xl font-bold mb-2 text-white">Dashboard</h1>
      <p class="text-slate-400 mb-8">Welcome back, <span class="text-white font-semibold">{{ auth.user?.name || auth.user?.email }}</span>!</p>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-zinc-800/50 p-6 rounded-xl border border-zinc-700">
          <h3 class="text-zinc-500 text-sm uppercase tracking-wider font-semibold mb-2">Status</h3>
          <p class="text-2xl font-bold text-green-500">Authenticated</p>
        </div>
        <div class="bg-zinc-800/50 p-6 rounded-xl border border-zinc-700">
          <h3 class="text-zinc-500 text-sm uppercase tracking-wider font-semibold mb-2">Role</h3>
          <p class="text-2xl font-bold text-white">Administrator</p>
        </div>
        <div class="bg-zinc-800/50 p-6 rounded-xl border border-zinc-700">
          <h3 class="text-zinc-500 text-sm uppercase tracking-wider font-semibold mb-2">Token Type</h3>
          <p class="text-2xl font-bold text-blue-400">Sanctum</p>
        </div>
      </div>

      <div class="mt-12">
        <h2 class="text-xl font-bold mb-4 text-zinc-300">User Data (JSON)</h2>
        <pre class="bg-zinc-950 p-4 rounded-lg overflow-auto border border-zinc-800 text-xs text-blue-300">{{ JSON.stringify(auth.user, null, 2) }}</pre>
      </div>
    </div>
  </div>
</template>
