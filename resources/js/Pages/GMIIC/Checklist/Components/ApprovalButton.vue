<template>
  <button
    type="button"
    :disabled="disabled"
    :class="[
      'rounded px-4 py-2 text-sm font-semibold transition',
      buttonClass,
      (disabled
        ? 'cursor-not-allowed bg-slate-300 text-slate-500 hover:bg-slate-300'
        : (isReady
          ? 'bg-amber-600 text-white hover:bg-amber-500'
          : 'cursor-not-allowed bg-slate-300 text-slate-500 hover:bg-slate-300')),
    ]"
    :title="tooltip"
    @click="handleClick"
  >
    {{ label }}
  </button>
</template>

<script setup>
const props = defineProps({
  isReady: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  label: { type: String, default: 'Approval' },
  tooltip: { type: String, default: '' },
  buttonClass: { type: String, default: '' },
})

const emit = defineEmits(['click'])

function handleClick(event) {
  if (props.disabled) return
  emit('click', event)
}
</script>
