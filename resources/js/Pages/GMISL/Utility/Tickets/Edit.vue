<template>
  <AppLayout>
    <div class="p-4 md:p-6 max-w-2xl">
      <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <h2 class="text-2xl font-bold">Edit Ticket</h2>
        <Link href="/tickets" class="text-indigo-400 hover:underline text-sm">← Back to List</Link>
      </div>

      <form @submit.prevent="submit" class="space-y-4 bg-slate-800 p-4 rounded">
        <div class="relative">
          <input
            v-model="form.title"
            placeholder=" "
            class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700"
          />
          <label
            class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
          >
            Title
          </label>
        </div>

        <div class="relative">
          <textarea
            v-model="form.description"
            rows="4"
            placeholder=" "
            class="peer w-full px-3 pt-6 pb-2 rounded-lg bg-slate-800 border border-slate-700"
          ></textarea>
          <label
            class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-4 peer-placeholder-shown:translate-y-0 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
          >
            Description
          </label>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="relative group">
            <EnhancedDatePicker
              v-model="form.deadline"
              placeholder=" "
              input-class="w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
            />
            <label
              :class="[
                'pointer-events-none absolute left-3 z-10 transition-all',
                (form.deadline
                  ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                  : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
              ]"
            >
              Deadline
            </label>
          </div>
          <div class="relative group">
            <SearchableSelect
              v-model="form.department_id"
              :options="departments"
              option-value="id"
              option-label="name"
              placeholder=" "
              empty-label="Select Department"
              input-class="w-full pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100"
              button-class="border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
            />
            <label
              :class="[
                'pointer-events-none absolute left-3 z-10 transition-all',
                (form.department_id
                  ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                  : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
              ]"
            >
              Department
            </label>
          </div>
        </div>

        <div class="flex flex-col-reverse gap-2 md:flex-row md:justify-end">
          <Link href="/tickets" class="px-4 py-2 rounded bg-slate-700 text-white text-center">Cancel</Link>
          <button type="submit" class="bg-indigo-600 px-4 py-2 rounded text-white" :disabled="form.processing">
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
  ticket: {
    type: Object,
    required: true,
  },
  departments: {
    type: Array,
    default: () => [],
  },
});

const form = useForm({
  title: props.ticket.title || '',
  description: props.ticket.description || '',
  deadline: props.ticket.deadline || '',
  department_id: props.ticket.department_id || '',
});

function submit() {
  form.put(`/tickets/${props.ticket.id}`);
}
</script>
