<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <h2 class="text-2xl font-bold mb-6">Add Employee</h2>

      <form @submit.prevent="submit">
        <div class="max-w-4xl rounded bg-slate-800 p-4 md:p-6">
          <h3 class="text-lg font-semibold mb-4 text-indigo-400">User (Optional)</h3>
          
          <!-- User Selection -->
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">User</label>
            <SearchableSelect
              v-model="form.user_id"
              :options="normalizedUsers"
              option-value="id"
              option-label="display_name"
              placeholder="Select User"
              empty-label="Select User"
              input-class="bg-white dark:bg-gray-700"
            />
            <p v-if="errors.user_id" class="text-red-500 text-sm mt-1">{{ errors.user_id }}</p>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Department</label>
            <SearchableSelect
              v-model="form.department_id"
              :options="departments"
              option-value="id"
              option-label="name"
              placeholder="Select Department"
              empty-label="Select Department"
              input-class="bg-white dark:bg-gray-700"
            />
            <p v-if="errors.department_id" class="text-red-500 text-sm mt-1">{{ errors.department_id }}</p>
          </div>

          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Position</label>
            <SearchableSelect
              v-model="form.position_id"
              :options="filteredPositions"
              option-value="id"
              option-label="display_name"
              placeholder="Select Position"
              empty-label="Select Position"
              input-class="bg-white dark:bg-gray-700"
            />
            <p v-if="errors.position_id" class="text-red-500 text-sm mt-1">{{ errors.position_id }}</p>
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
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
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

          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Foto Referensi Wajah</label>
            <input
              type="file"
              accept="image/*"
              class="w-full rounded border bg-white px-4 py-2 text-slate-700 dark:bg-gray-700 dark:text-white"
              @change="handleFaceReferenceChange"
            />
            <p class="mt-2 text-xs text-slate-400">
              Upload 1 foto wajah yang jelas, menghadap depan, tanpa banyak objek lain di frame.
            </p>
            <p v-if="faceProcessing" class="mt-2 text-sm text-sky-400">Memproses wajah referensi...</p>
            <p v-if="faceError" class="mt-2 text-sm text-red-400">{{ faceError }}</p>
            <img
              v-if="facePreview"
              :src="facePreview"
              alt="Preview referensi wajah"
              class="mt-3 h-32 w-32 rounded-xl border border-slate-600 object-cover"
            />
          </div>

          <!-- Buttons -->
          <div class="mt-6 flex flex-col-reverse gap-4 sm:flex-row">
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
import { computed, ref, reactive, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { extractFaceDescriptorFromImage, fileToDataUrl } from '@/Utils/faceRecognition';

const props = defineProps({
  availableUsers: Object,
  departments: Object,
  positions: Object
});

const errors = ref({});
const isOffice = ref(false);
const faceProcessing = ref(false);
const faceError = ref('');
const facePreview = ref('');
const normalizedUsers = computed(() => (props.availableUsers || []).map((user) => ({
  ...user,
  display_name: `${user.first_name || user.name || ''} ${user.last_name || ''}`.trim() + ` (${user.email || '-'})`,
})));

const normalizedPositions = computed(() => (props.positions || []).map((position) => {
  const departmentCode = String(position?.department?.code || '').trim();
  const departmentName = String(position?.department?.name || '').trim();
  const dept = departmentCode || departmentName;
  const code = String(position?.code || '').trim();
  const name = String(position?.name || '').trim();

  return {
    ...position,
    display_name: `${name}${dept ? ` - ${dept}` : ''}${code ? ` (${code})` : ''}`.trim(),
  };
}));

const form = reactive({
  user_id: '',
  department_id: '',
  position_id: '',
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
  education: '',
  face_reference_photo_data: '',
  face_reference_descriptor: [],
});

watch(() => form.user_id, (value) => {
  if (!value) return;
  const selected = (normalizedUsers.value || []).find((user) => String(user?.id || '') === String(value));
  if (!selected) return;
  if (selected.department_id && !form.department_id) form.department_id = selected.department_id;
  if (selected.position_id && !form.position_id) form.position_id = selected.position_id;
});

watch(() => form.department_id, () => {
  if (!form.position_id) return;
  const selected = normalizedPositions.value.find((position) => String(position?.id || '') === String(form.position_id));
  if (!selected) return;
  if (String(selected.department_id || '') !== String(form.department_id || '')) {
    form.position_id = '';
  }
});

const filteredPositions = computed(() => {
  if (!form.department_id) return normalizedPositions.value;
  return normalizedPositions.value.filter((position) => String(position?.department_id || '') === String(form.department_id));
});

async function handleFaceReferenceChange(event) {
  const file = event.target.files?.[0];
  faceError.value = '';

  if (!file) {
    return;
  }

  faceProcessing.value = true;

  try {
    const dataUrl = await fileToDataUrl(file);
    const descriptor = await extractFaceDescriptorFromImage(dataUrl);
    facePreview.value = dataUrl;
    form.face_reference_photo_data = dataUrl;
    form.face_reference_descriptor = descriptor;
  } catch (error) {
    facePreview.value = '';
    form.face_reference_photo_data = '';
    form.face_reference_descriptor = [];
    faceError.value = error?.message || 'Foto referensi wajah gagal diproses.';
  } finally {
    faceProcessing.value = false;
    event.target.value = '';
  }
}

function submit() {
  errors.value = {};
  form.work_group = isOffice.value ? 'office' : 'operational';
  
  router.post('/master-data/employee', form, {
    onError: (formErrors) => {
      errors.value = formErrors || {};
    }
  });
}
</script>
