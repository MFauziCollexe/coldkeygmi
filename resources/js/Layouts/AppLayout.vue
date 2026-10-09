<template>
  <div class="min-h-screen w-full flex overflow-x-hidden bg-slate-900 text-slate-100">
    <div
      v-if="isMobile && sidebarOpen"
      class="fixed inset-0 z-30 bg-black/50 lg:hidden"
      @click="sidebarOpen = false"
    ></div>

    <Sidebar :open="sidebarOpen" :mobile="isMobile" @update:open="sidebarOpen = $event" />

    <div class="min-w-0 flex-1 flex flex-col">
      <div v-if="$page.props.auth?.impersonation" class="flex flex-wrap items-center justify-between gap-3 border-b border-amber-500/40 bg-amber-950 px-4 py-2 text-sm text-amber-100">
        <span>You are viewing this session as <strong>{{ $page.props.auth.user?.name }}</strong>. Started by {{ $page.props.auth.impersonation.issued_by_name }}.</span>
        <button type="button" class="rounded border border-amber-300/50 px-3 py-1 text-xs font-semibold hover:bg-amber-900" @click="stopImpersonation">Stop impersonation</button>
      </div>
      <Topbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />

      <main class="min-w-0 overflow-auto bg-slate-900">
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
  window.location.replace('/session-timeout');
}

function stopImpersonation() {
  router.post('/temporary-user-access/stop');
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
