<template>
  <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
    <div class="w-full max-w-lg rounded-lg bg-transparent p-4 md:p-6">
      <h3 class="text-xl font-bold mb-4">{{ title }}</h3>
      
      <div class="space-y-4">
        <!-- Leave Type -->
        <div class="relative group">
          <select v-model="form.type" class="w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700">
            <option value="cuti">Cuti</option>
            <option value="izin">Izin</option>
            <option value="sakit">Sakit</option>
            <option value="dinas_luar">Dinas Luar</option>
          </select>
          <label
            class="pointer-events-none absolute left-3 z-10 px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2"
          >
            Jenis Permintaan
          </label>
        </div>
        
        <!-- Start Date -->
        <div class="relative group">
          <EnhancedDatePicker
            v-model="form.start_date"
            placeholder=" "
            input-class="w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
          />
          <label
            :class="[
              'pointer-events-none absolute left-3 z-10 transition-all',
              (form.start_date
                ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
              'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
            ]"
          >
            Tanggal Mulai
          </label>
        </div>
        
        <!-- End Date -->
        <div class="relative group">
          <EnhancedDatePicker
            v-model="form.end_date"
            placeholder=" "
            input-class="w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
          />
          <label
            :class="[
              'pointer-events-none absolute left-3 z-10 transition-all',
              (form.end_date
                ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
              'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
            ]"
          >
            Tanggal Selesai
          </label>
        </div>
        
        <!-- Reason -->
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

      <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
        <button @click="cancel" class="rounded bg-slate-700 px-4 py-2 text-slate-300">
          Batal
        </button>
        <button @click="submit" class="rounded bg-indigo-600 px-4 py-2 text-white">
          Simpan
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';

const props = defineProps({
  title: {
    type: String,
    default: 'Ajukan Permintaan'
  },
  initialData: {
    type: Object,
    default: () => ({
      type: 'cuti',
      start_date: '',
      end_date: '',
      reason: ''
    })
  },
  isEdit: {
    type: Boolean,
    default: false
  },
  itemId: {
    type: Number,
    default: null
  },
  submitUrl: {
    type: String,
    default: '/leave-permission'
  },
  updateUrl: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['close', 'success']);

const form = reactive({
  type: props.initialData.type || 'cuti',
  start_date: props.initialData.start_date || '',
  end_date: props.initialData.end_date || '',
  reason: props.initialData.reason || ''
});

function cancel() {
  emit('close');
}

function submit() {
  if (!form.start_date || !form.end_date || !form.reason) {
    alert('Mohon lengkapi semua field');
    return;
  }
  
  if (props.isEdit && props.itemId) {
    // Update existing
    router.put(`${props.submitUrl}/${props.itemId}`, form, {
      onSuccess: () => {
        emit('success');
        emit('close');
      },
    });
  } else {
    // Create new
    router.post(props.submitUrl, form, {
      onSuccess: () => {
        emit('success');
        emit('close');
      },
    });
  }
}

// Reset form when initialData changes
watch(() => props.initialData, (newVal) => {
  form.type = newVal.type || 'cuti';
  form.start_date = newVal.start_date || '';
  form.end_date = newVal.end_date || '';
  form.reason = newVal.reason || '';
}, { deep: true });
</script>
