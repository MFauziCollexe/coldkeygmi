<template>
  <div class="min-h-screen w-full flex overflow-x-hidden bg-slate-900 text-slate-100">
    <div
      v-if="isMobile && sidebarOpen"
      class="fixed inset-0 z-30 bg-black/50 lg:hidden"
      @click="sidebarOpen = false"
    ></div>

    <Sidebar :open="sidebarOpen" :mobile="isMobile" @update:open="sidebarOpen = $event" />

    <div class="min-w-0 flex-1 flex flex-col">
      <Topbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />

      <main class="min-w-0 overflow-auto bg-slate-900">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import Sidebar from '@/Components/Sidebar.vue';
import Topbar from '@/Components/Topbar.vue';

const sidebarOpen = ref(true);
const isMobile = ref(false);

const IDLE_TIMEOUT_MS = 30 * 60 * 1000;
let idleTimer = null;
let isLoggingOut = false;
const LOGIN_PAGE_PATH = '/';

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
  clearIdleTimer();

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  if (!csrfToken) {
    window.location.replace(LOGIN_PAGE_PATH);
    return;
  }

  const form = document.createElement('form');
  form.method = 'POST';
  form.action = '/logout';
  form.style.display = 'none';

  const tokenInput = document.createElement('input');
  tokenInput.type = 'hidden';
  tokenInput.name = '_token';
  tokenInput.value = csrfToken;
  form.appendChild(tokenInput);

  document.body.appendChild(form);
  form.submit();
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

function syncViewportState() {
  isMobile.value = window.innerWidth < 1024;
  sidebarOpen.value = !isMobile.value;
}

onMounted(() => {
  syncViewportState();
  resetIdleTimer();
  window.addEventListener('resize', syncViewportState);
  activityEvents.forEach((eventName) => {
    window.addEventListener(eventName, onActivity, { passive: true });
  });
});

onBeforeUnmount(() => {
  clearIdleTimer();
  window.removeEventListener('resize', syncViewportState);
  activityEvents.forEach((eventName) => {
    window.removeEventListener(eventName, onActivity);
  });
});
</script>

<style scoped>
/* minimal styles */
</style>
