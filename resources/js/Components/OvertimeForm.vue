<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-transparent rounded-lg p-6 w-full max-w-lg">
      <h3 class="text-xl font-bold mb-4">{{ title }}</h3>
      
      <div class="space-y-4">
        <div class="relative group">
          <EnhancedDatePicker
            v-model="form.overtime_date"
            placeholder=" "
            input-class="w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
          />
          <label
            :class="[
              'pointer-events-none absolute left-3 z-10 transition-all',
              (form.overtime_date
                ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
              'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
            ]"
          >
            Tanggal Lembur
          </label>
        </div>
        
        <div class="relative">
          <input
            v-model="form.start_time"
            type="time"
            placeholder=" "
            class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
          />
          <label
            class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
          >
            Jam Mulai
          </label>
        </div>
        
        <div class="relative">
          <input
            v-model="form.end_time"
            type="time"
            placeholder=" "
            class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
          />
          <label
            class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
          >
            Jam Selesai
          </label>
        </div>
        
        <div class="relative">
          <textarea
            v-model="form.reason"
            rows="3"
            placeholder=" "
            class="peer w-full px-3 pt-6 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
          ></textarea>
          <label
            class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-4 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
          >
            Alasan
          </label>
        </div>
      </div>

      <div class="flex justify-end gap-2 mt-6">
        <button @click="cancel" class="px-4 py-2 rounded bg-slate-700 text-slate-300">
          Batal
        </button>
        <button @click="submit" class="px-4 py-2 rounded bg-indigo-600 text-white">
          Simpan
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue';
import { Inertia } from '@inertiajs/inertia';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';

const props = defineProps({
  title: {
    type: String,
    default: 'Ajukan Permintaan Lembur'
  },
  initialData: {
    type: Object,
    default: () => ({
      overtime_date: '',
      start_time: '',
      end_time: '',
      reason: ''
    })
  },
  submitUrl: {
    type: String,
    default: '/overtime'
  }
});

const emit = defineEmits(['close', 'success']);

const form = reactive({
  overtime_date: props.initialData.overtime_date || '',
  start_time: props.initialData.start_time || '',
  end_time: props.initialData.end_time || '',
  reason: props.initialData.reason || ''
});

function cancel() {
  emit('close');
}

function submit() {
  if (!form.overtime_date || !form.start_time || !form.end_time || !form.reason) {
    alert('Mohon lengkapi semua field');
    return;
  }
  
  Inertia.post(props.submitUrl, form, {
    onSuccess: () => {
      emit('success');
      emit('close');
    },
  });
}
</script>
