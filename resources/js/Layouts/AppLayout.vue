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
import { ref, onMounted, onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Topbar from '@/Components/Topbar.vue';

const sidebarOpen = ref(true);

const IDLE_TIMEOUT_MS = 10 * 60 * 1000;
let idleTimer = null;
let isLoggingOut = false;

const activityEvents = [
  'mousemove',
  'mousedown',
  'keydown',
  'scroll',
  'touchstart',
  'click',
  'wheel',
];

function clearIdleTimer() {
  if (idleTimer) {
    clearTimeout(idleTimer);
    idleTimer = null;
  }
}

function triggerLogout() {
  if (isLoggingOut) return;
  isLoggingOut = true;
  router.post('/logout', {}, { preserveState: false, preserveScroll: false });
}

function resetIdleTimer() {
  clearIdleTimer();
  idleTimer = setTimeout(() => {
    triggerLogout();
  }, IDLE_TIMEOUT_MS);
}

function onActivity() {
  resetIdleTimer();
}

onMounted(() => {
  resetIdleTimer();
  activityEvents.forEach((eventName) => {
    window.addEventListener(eventName, onActivity, { passive: true });
  });
});

onBeforeUnmount(() => {
  clearIdleTimer();
  activityEvents.forEach((eventName) => {
    window.removeEventListener(eventName, onActivity);
  });
});
</script>

<style scoped>
/* minimal styles */
</style>
