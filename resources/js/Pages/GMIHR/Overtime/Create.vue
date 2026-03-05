<template>
  <AppLayout>
    <div class="p-6 max-w-2xl">
      <h2 class="text-2xl font-bold mb-4">Ajukan Permintaan Lembur</h2>

      <form @submit.prevent="submit" class="space-y-4 bg-slate-800 p-4 rounded">
        <div>
          <label class="block text-sm text-slate-300 mb-1">Tanggal Lembur</label>
          <EnhancedDatePicker v-model="form.overtime_date" placeholder="dd/mm/yyyy" />
        </div>

        <div>
          <label class="block text-sm text-slate-300 mb-1">Jam Mulai</label>
          <input v-model="form.start_time" type="time" class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-slate-500" />
        </div>

        <div>
          <label class="block text-sm text-slate-300 mb-1">Jam Selesai</label>
          <input v-model="form.end_time" type="time" class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-slate-500" />
        </div>

        <div>
          <label class="block text-sm text-slate-300 mb-1">Alasan</label>
          <textarea v-model="form.reason" rows="3" class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-slate-500" placeholder="Masukkan alasan lembur..."></textarea>
        </div>

        <div class="flex justify-end gap-2">
          <button type="button" @click="cancel" class="px-4 py-2 rounded bg-slate-700 text-slate-300">
            Batal
          </button>
          <button type="submit" class="px-4 py-2 rounded bg-indigo-600 text-white">
            Simpan
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import { Inertia } from '@inertiajs/inertia';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';

const form = useForm({
  overtime_date: '',
  start_time: '',
  end_time: '',
  reason: '',
});

function submit() {
  if (!form.overtime_date || !form.start_time || !form.end_time || !form.reason) {
    alert('Mohon lengkapi semua field');
    return;
  }
  
  form.post('/overtime', {
    onSuccess: () => {
      Inertia.get('/overtime');
    },
  });
}

function cancel() {
  Inertia.get('/overtime');
}
</script>
