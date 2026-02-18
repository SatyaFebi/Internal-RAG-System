<template>
  <div class="p-6 bg-white rounded-xl shadow-md">
    <h3 class="text-lg font-bold mb-4">Add to AI Memory</h3>
    <textarea 
      v-model="content" 
      class="w-full p-3 border rounded-lg" 
      placeholder="Masukkan informasi yang ingin diingat AI..."
    ></textarea>
    <button 
      @click="saveMemory" 
      :disabled="loading"
      class="mt-3 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50"
    >
      {{ loading ? 'Saving...' : 'Save to Memory' }}
    </button>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';

const content = ref('');
const loading = ref(false);

const saveMemory = async () => {
  if (!content.value) return;
  loading.value = true;
  try {
    await axios.post('/api/ingest', { content: content.value });
    alert('AI successfully remembered that!');
    content.value = '';
  } catch (error) {
    console.error(error);
    alert('Failed to save memory.');
  } finally {
    loading.value = false;
  }
};
</script>