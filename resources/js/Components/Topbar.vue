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
        <button
          type="button"
          class="dropdown-item flex items-center gap-3 w-full text-left"
          @click="openPasswordModal"
        >
          Change password
        </button>
        <button @click="signOut" class="dropdown-item flex items-center gap-3 w-full text-left">Sign out</button>
      </div>
    </div>

    <div
      v-if="passwordModalOpen"
      class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4"
      @click.self="closePasswordModal"
    >
      <section
        role="dialog"
        aria-modal="true"
        aria-labelledby="change-password-title"
        class="w-full max-w-md rounded-xl border border-slate-700 bg-slate-800 p-6 shadow-2xl"
      >
        <div class="mb-5 flex items-center justify-between">
          <h2 id="change-password-title" class="text-lg font-semibold">Change password</h2>
          <button type="button" class="text-slate-400 hover:text-white" aria-label="Close" @click="closePasswordModal">
            &times;
          </button>
        </div>

        <form class="space-y-4" @submit.prevent="submitPasswordChange">
          <PasswordInput
            v-model="passwordForm.current_password"
            label="Current password"
            placeholder="Enter current password"
            required
            autocomplete="current-password"
            :error="passwordForm.errors.current_password || ''"
          />
          <PasswordInput
            v-model="passwordForm.password"
            label="New password"
            placeholder="Enter new password"
            required
            autocomplete="new-password"
            :error="passwordForm.errors.password || ''"
          />
          <PasswordInput
            v-model="passwordForm.password_confirmation"
            label="Confirm new password"
            placeholder="Re-enter new password"
            required
            autocomplete="new-password"
            :error="passwordForm.errors.password_confirmation || ''"
          />
          <p v-if="passwordForm.wasSuccessful" class="text-sm text-emerald-300">
            Password berhasil diubah.
          </p>
          <div class="flex justify-end gap-2 pt-2">
            <button
              type="button"
              class="rounded-lg border border-slate-600 px-4 py-2 text-sm text-slate-200 hover:bg-slate-700"
              @click="closePasswordModal"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="passwordForm.processing"
            >
              {{ passwordForm.processing ? 'Saving...' : 'Save password' }}
            </button>
          </div>
        </form>
      </section>
    </div>
  </header>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import PasswordInput from '@/Components/PasswordInput.vue';

const profileOpen = ref(false);
const passwordModalOpen = ref(false);
const passwordForm = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});
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

function openPasswordModal() {
  profileOpen.value = false;
  passwordForm.reset();
  passwordForm.clearErrors();
  passwordModalOpen.value = true;
}

function closePasswordModal() {
  passwordModalOpen.value = false;
  passwordForm.reset();
  passwordForm.clearErrors();
}

function submitPasswordChange() {
  passwordForm.put('/password', {
    preserveScroll: true,
    onSuccess: () => closePasswordModal(),
  });
}

function signOut() {
  profileOpen.value = false;
  router.post('/logout', {}, {
    preserveState: false,
    onSuccess: () => {
      window.location.replace('/');
    },
  });
}
</script>

<style scoped>
[v-cloak] { display: none; }
</style>
