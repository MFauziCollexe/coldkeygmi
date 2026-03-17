<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-transparent rounded-lg p-6 w-full max-w-lg">
      <h3 class="text-xl font-bold mb-4">{{ title }}</h3>
      
      <div class="space-y-4">
        <div v-if="canSelectEmployee" class="relative group">
          <SearchableSelect
            v-model="form.employee_id"
            :options="employees"
            option-value="id"
            option-label="label"
            placeholder=" "
            empty-label="Pilih Karyawan"
            input-class="w-full h-[52px] pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100 placeholder-transparent"
            button-class="h-[52px] border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
          />
          <label
            :class="[
              'pointer-events-none absolute left-3 z-10 transition-all',
              (form.employee_id
                ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
              'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
            ]"
          >
            Karyawan
          </label>
          <div v-if="form.errors.employee_id" class="text-red-400 text-sm mt-1">{{ form.errors.employee_id }}</div>
        </div>

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

        <div>
          <label class="block text-sm text-slate-300 mb-1">Attachment (Gambar)</label>
          <input
            type="file"
            accept="image/*,application/pdf,.pdf"
            class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 text-slate-200"
            @change="onAttachmentChange"
          />
          <div v-if="form.errors.attachment" class="text-xs text-red-400 mt-1">{{ form.errors.attachment }}</div>
          <div v-else class="text-xs text-slate-400 mt-1">Opsional, maksimal 5MB (jpg/png/webp/pdf).</div>
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
import { useForm } from '@inertiajs/vue3';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
  title: {
    type: String,
    default: 'Ajukan Permintaan Lembur'
  },
  employees: { type: Array, default: () => [] },
  canSelectEmployee: { type: Boolean, default: false },
  canSubmitForOthers: { type: Boolean, default: false },
  defaultEmployeeId: { type: [String, Number], default: '' },
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

const form = useForm({
  employee_id: props.canSelectEmployee
    ? (props.canSubmitForOthers ? '' : (props.defaultEmployeeId || ''))
    : '',
  overtime_date: props.initialData.overtime_date || '',
  start_time: props.initialData.start_time || '',
  end_time: props.initialData.end_time || '',
  reason: props.initialData.reason || '',
  attachment: null,
});

function cancel() {
  emit('close');
}

function onAttachmentChange(e) {
  const file = e?.target?.files?.[0] || null;
  form.attachment = file;
}

function submit() {
  if (!form.overtime_date || !form.start_time || !form.end_time || !form.reason) {
    alert('Mohon lengkapi semua field');
    return;
  }
  
  form.post(props.submitUrl, {
    forceFormData: true,
    onSuccess: () => {
      emit('success');
      emit('close');
    },
  });
}
</script>
