<template>
  <form @submit.prevent="submit">
    <div class="space-y-4">
      <slot />
      <div class="flex justify-end">
        <button type="submit" class="bg-indigo-600 px-4 py-2 rounded text-white">{{ submitText }}</button>
      </div>
    </div>
  </form>
</template>

<script setup>
import { defineProps } from 'vue';
const props = defineProps({ submitText: { type: String, default: 'Save' } });

function submit() {
  // rely on parent form handling via useForm
  const event = new Event('submit', { bubbles: true });
  (document.activeElement || document.body).dispatchEvent(event);
}
</script>
