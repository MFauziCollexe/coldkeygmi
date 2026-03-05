<template>
  <AppLayout>
    <div class="p-6">
      <h2 class="text-2xl font-bold mb-6">Add Employee</h2>

      <form @submit.prevent="submit">
        <div class="bg-slate-800 rounded p-6 max-w-4xl">
          <h3 class="text-lg font-semibold mb-4 text-indigo-400">Select User</h3>
          
          <!-- User Selection -->
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">User *</label>
            <SearchableSelect
              v-model="form.user_id"
              :options="normalizedUsers"
              option-value="id"
              option-label="display_name"
              placeholder="Select User"
              empty-label="Select User"
              input-class="bg-white dark:bg-gray-700"
              required
            />
            <p v-if="errors.user_id" class="text-red-500 text-sm mt-1">{{ errors.user_id }}</p>
          </div>

          <h3 class="text-lg font-semibold mb-4 mt-8 text-indigo-400">Employee Information</h3>

          <!-- NIK -->
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">NIK</label>
            <input
              v-model="form.nik"
              type="text"
              class="w-full px-4 py-2 border rounded bg-white dark:bg-gray-700"
            />
            <p v-if="errors.nik" class="text-red-500 text-sm mt-1">{{ errors.nik }}</p>
          </div>

          <!-- Work Group -->
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Work Group</label>
            <div class="flex items-center gap-3">
              <label class="inline-flex items-center gap-2">
                <input v-model="isOffice" type="checkbox" />
                <span>Office</span>
              </label>
              <span class="text-xs text-slate-400">
                Jika tidak dicentang, otomatis Operational.
              </span>
            </div>
            <p v-if="errors.work_group" class="text-red-500 text-sm mt-1">{{ errors.work_group }}</p>
          </div>

          <!-- Join Date -->
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Join Date</label>
            <EnhancedDatePicker v-model="form.join_date" placeholder="dd/mm/yyyy" />
          </div>

          <!-- Phone -->
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Phone</label>
            <input
              v-model="form.phone"
              type="text"
              class="w-full px-4 py-2 border rounded bg-white dark:bg-gray-700"
            />
          </div>

          <!-- Address -->
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Address</label>
            <textarea
              v-model="form.address"
              rows="3"
              class="w-full px-4 py-2 border rounded bg-white dark:bg-gray-700"
            ></textarea>
          </div>

          <!-- Birth Date -->
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Birth Date</label>
            <EnhancedDatePicker v-model="form.birth_date" placeholder="dd/mm/yyyy" />
          </div>

          <!-- Birth Place -->
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Birth Place</label>
            <input
              v-model="form.birth_place"
              type="text"
              class="w-full px-4 py-2 border rounded bg-white dark:bg-gray-700"
            />
          </div>

          <!-- Gender -->
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Gender</label>
            <select
              v-model="form.gender"
              class="w-full px-4 py-2 border rounded bg-white dark:bg-gray-700"
            >
              <option value="">Select Gender</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
            </select>
          </div>

          <!-- Religion -->
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Religion</label>
            <input
              v-model="form.religion"
              type="text"
              class="w-full px-4 py-2 border rounded bg-white dark:bg-gray-700"
            />
          </div>

          <!-- Marital Status -->
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Marital Status</label>
            <input
              v-model="form.marital_status"
              type="text"
              class="w-full px-4 py-2 border rounded bg-white dark:bg-gray-700"
            />
          </div>

          <!-- Education -->
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Education</label>
            <input
              v-model="form.education"
              type="text"
              class="w-full px-4 py-2 border rounded bg-white dark:bg-gray-700"
            />
          </div>

          <!-- Buttons -->
          <div class="flex gap-4 mt-6">
            <button
              type="submit"
              class="bg-indigo-600 px-6 py-2 rounded text-white hover:bg-indigo-700"
            >
              Save
            </button>
            <Link
              href="/master-data/employee"
              class="bg-gray-500 px-6 py-2 rounded text-white hover:bg-gray-600"
            >
              Cancel
            </Link>
          </div>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, ref, reactive } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Inertia } from '@inertiajs/inertia';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
  availableUsers: Object,
  departments: Object,
  positions: Object
});

const errors = ref({});
const isOffice = ref(false);
const normalizedUsers = computed(() => (props.availableUsers || []).map((user) => ({
  ...user,
  display_name: `${user.first_name || user.name || ''} ${user.last_name || ''}`.trim() + ` (${user.email || '-'})`,
})));

const form = reactive({
  user_id: '',
  nik: '',
  work_group: '',
  join_date: '',
  phone: '',
  address: '',
  birth_date: '',
  birth_place: '',
  gender: '',
  religion: '',
  marital_status: '',
  education: ''
});

function submit() {
  errors.value = {};
  form.work_group = isOffice.value ? 'office' : 'operational';
  
  Inertia.post('/master-data/employee', form, {
    onError: (errors) => {
      errors.value = errors;
    }
  });
}
</script>
