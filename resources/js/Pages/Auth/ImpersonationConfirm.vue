<template>
  <main class="flex min-h-screen items-center justify-center bg-slate-950 px-4 py-10 text-slate-100">
    <section class="w-full max-w-lg border border-slate-800 bg-slate-900 p-6 shadow-xl sm:p-8">
      <p class="text-xs font-semibold uppercase tracking-widest text-amber-300">Temporary admin access</p>
      <h1 class="mt-3 text-2xl font-bold">Continue as {{ target.name }}?</h1>
      <p class="mt-3 text-sm leading-6 text-slate-300">
        This one-time link will sign in to account <strong class="text-white">{{ target.account }}</strong>.
        It expires at {{ formattedExpiry }} and will be consumed only after you continue.
      </p>
      <div class="mt-5 border-l-2 border-amber-400 bg-amber-950/30 px-4 py-3 text-sm text-amber-100">
        Use this page only if you requested the link from Control Panel. The account session is separate from the administrator session.
      </div>
      <form class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end" @submit.prevent="continueAsUser">
        <a href="/" class="rounded border border-slate-700 px-4 py-2 text-center text-sm font-semibold text-slate-300 hover:bg-slate-800">Cancel</a>
        <button type="submit" :disabled="processing" class="rounded bg-amber-400 px-4 py-2 text-sm font-bold text-slate-950 hover:bg-amber-300 disabled:opacity-50">
          {{ processing ? 'Signing in...' : `Continue as ${target.account}` }}
        </button>
      </form>
      <p v-if="errorMessage" class="mt-4 text-sm text-red-300">{{ errorMessage }}</p>
    </section>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  token: { type: String, required: true },
  target: { type: Object, required: true },
  expiresAt: { type: String, required: true },
});

const processing = ref(false);
const errorMessage = ref('');
const formattedExpiry = computed(() => new Date(props.expiresAt).toLocaleString());

onMounted(() => {
  window.history.replaceState({}, document.title, '/');
});

function continueAsUser() {
  processing.value = true;
  errorMessage.value = '';
  router.post(`/temporary-user-access/${encodeURIComponent(props.token)}`, {}, {
    onError: () => {
      errorMessage.value = 'This link is invalid, expired, or already used. Request a new link from Control Panel.';
    },
    onFinish: () => {
      processing.value = false;
    },
  });
}
</script>