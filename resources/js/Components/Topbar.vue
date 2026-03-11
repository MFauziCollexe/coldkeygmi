<template>
  <header class="h-16 border-b border-slate-700 flex items-center justify-between px-6">
    <div class="flex items-center gap-4">
      <button @click="$emit('toggle-sidebar')" class="text-slate-300 hover:text-white text-xl">☰</button>

      <div class="hidden md:flex items-center bg-slate-800 rounded-lg px-4 py-2 gap-2">
        <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" placeholder="Search or type command..." class="bg-transparent outline-none text-sm w-64 text-slate-200" />
      </div>
    </div>

    <div class="relative">
      <div class="flex items-center gap-3">
        <div class="text-right mr-2 hidden md:block">
          <div class="text-sm text-slate-300">{{ displayUserName }}</div>
          <div class="text-xs text-slate-500">{{ userEmail }}</div>
        </div>
        <button @click="profileOpen = !profileOpen" class="flex items-center gap-2 hover:bg-slate-800 px-3 py-2 rounded-lg transition">
          <img :src="avatar" alt="avatar" class="w-8 h-8 rounded-full" />
          <svg class="w-4 h-4 text-slate-300" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6" fill="currentColor"/></svg>
        </button>
      </div>

      <div v-if="profileOpen" @click.outside="profileOpen = false" class="absolute right-0 mt-3 w-64 bg-slate-800 rounded-xl shadow-xl p-4 text-sm space-y-2 z-50">
        <div>
          <p class="font-semibold">{{ displayUserName }}</p>
          <p class="text-slate-400 text-xs">{{ userEmail }}</p>
        </div>
        <hr class="border-slate-700" />
        <button @click="signOut" class="dropdown-item flex items-center gap-3 w-full text-left">Sign out</button>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';

const profileOpen = ref(false);
const page = usePage();

const displayUserName = computed(() => {
  const currentUser = page.props.auth?.user;
  
  if (!currentUser) {
    return 'Guest';
  }
  
  // Check all possible name fields
  if (currentUser.first_name && currentUser.last_name) {
    return `${currentUser.first_name} ${currentUser.last_name}`;
  }
  if (currentUser.first_name) return currentUser.first_name;
  if (currentUser.last_name) return currentUser.last_name;
  if (currentUser.name) return currentUser.name;
  
  return 'Guest';
});

const userEmail = computed(() => page.props.auth?.user?.email || '');
const avatar = computed(() => {
  const name = encodeURIComponent(displayUserName.value || 'User');
  return `https://ui-avatars.com/api/?name=${name}&background=6366f1&color=ffffff`;
});
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

function signOut() {
  profileOpen.value = false;
  router.post('/logout');
}
</script>

<style scoped>
[v-cloak] { display: none; }
</style>
