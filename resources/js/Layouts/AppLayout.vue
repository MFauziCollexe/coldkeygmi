<template>
  <div class="min-h-screen w-full flex overflow-x-hidden bg-slate-900 text-slate-100">
    <Sidebar :open="sidebarOpen" @update:open="sidebarOpen = $event" />

    <div class="min-w-0 flex-1 flex flex-col">
      <Topbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />

      <main class="min-w-0 p-6 overflow-auto bg-slate-900">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import Sidebar from '@/Components/Sidebar.vue';
import Topbar from '@/Components/Topbar.vue';

const sidebarOpen = ref(true);
</script>

<style scoped>
/* minimal styles */
</style>
