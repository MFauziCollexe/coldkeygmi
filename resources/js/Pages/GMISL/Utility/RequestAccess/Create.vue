<template>
  <AppLayout>
    <div class="p-4 md:p-6 max-w-4xl">
      <h2 class="text-2xl font-bold mb-4">Create Request Access</h2>

      <form @submit.prevent="submit" class="space-y-4 bg-slate-800 p-4 rounded">
        <!-- Request Type Selection -->
        <div>
          <label class="block text-sm text-slate-300 mb-2">Request Type</label>
          <div class="flex flex-col gap-3 md:flex-row md:gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="form.type" value="existing_user" @change="onTypeChange" />
              <span>Existing User (Request Access)</span>
            </label>
            <label v-if="canRequestNewUser" class="flex items-center gap-2 cursor-pointer">
              <input type="radio" v-model="form.type" value="new_user" @change="onTypeChange" />
              <span>New User (Request New User + Access)</span>
            </label>
          </div>
        </div>

        <!-- Existing User Fields -->
        <div v-if="form.type === 'existing_user'">
          <div class="bg-slate-700 p-3 rounded mb-4">
            <p class="text-sm text-slate-300">
              Request access for existing user in your department. Select the user who needs additional module permissions.
            </p>
          </div>

          <!-- User Selection - Filtered by Department -->
          <div>
            <label class="block text-sm text-slate-300">Select User <span class="text-slate-400">(Same Department: {{ currentUserDepartmentName }})</span></label>
            <SearchableSelect
              v-model="form.user_id"
              :options="normalizedUsers"
              option-value="id"
              option-label="display_name"
              placeholder="Select User"
              empty-label="Select User"
              input-class="bg-slate-800"
            />
          </div>
        </div>

        <!-- New User Fields (only for Manager/HR) -->
        <div v-if="form.type === 'new_user' && canRequestNewUser">
          <div class="bg-slate-700 p-3 rounded mb-4">
            <p class="text-sm text-slate-300">
              Request creation of a new user account with specific module access. This is typically used by HR/Manager to onboard new employees.
            </p>
          </div>

          <div>
            <label class="block text-sm text-slate-300">New User Name</label>
            <input v-model="form.target_user_name" class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-slate-500" placeholder="Full Name" />
          </div>

          <div>
            <label class="block text-sm text-slate-300">New User Email</label>
            <input v-model="form.target_user_email" type="email" class="w-full px-3 py-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-slate-500" placeholder="email@company.com" />
          </div>

          <div>
            <label class="block text-sm text-slate-300">Department</label>
            <SearchableSelect
              v-model="form.target_department_id"
              :options="normalizedDepartments"
              option-value="id"
              option-label="display_name"
              placeholder="Select Department"
              empty-label="Select Department"
              input-class="bg-slate-800"
            />
          </div>
        </div>

        <!-- Module Selection - Multi Select -->
        <div>
          <label class="block text-sm text-slate-300 mb-2">
            Module <span class="text-slate-400">(Select multiple modules)</span>
          </label>
          
          <div class="bg-slate-700 p-3 rounded max-h-64 overflow-auto">
            <div v-for="group in flatModules" :key="group.key" class="mb-4">
              <div class="font-semibold text-indigo-300 mb-2">{{ group.label }}</div>
              <div class="pl-2 space-y-1">
                <label v-for="mod in group.children" :key="mod.key" class="flex items-center gap-2 cursor-pointer hover:bg-slate-800 p-1 rounded">
                  <input 
                    type="checkbox" 
                    :value="mod.key" 
                    v-model="form.module_keys"
                    class="w-4 h-4 rounded"
                  />
                  <span class="text-sm">{{ mod.label }}</span>
                </label>
              </div>
            </div>
          </div>
          
          <p v-if="form.module_keys.length > 0" class="text-sm text-green-400 mt-2">
            Selected: {{ form.module_keys.length }} module(s)
          </p>
          <p v-else class="text-sm text-red-400 mt-2">
            Please select at least one module
          </p>
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
            Reason
          </label>
        </div>

        <div class="flex justify-end">
          <button type="submit" class="w-full md:w-auto bg-indigo-600 px-4 py-2 rounded text-white" :disabled="form.processing || form.module_keys.length === 0">
            Submit Request
          </button>
        </div>
      </form>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({ 
  modules: Array, 
  departments: Array, 
  users: Array,
  canRequestNewUser: Boolean,
  currentUser: Object 
});

const form = useForm({
  type: 'existing_user',
  user_id: props.currentUser?.id || null,
  target_user_name: '',
  target_user_email: '',
  target_department_id: '',
  module_keys: [],
  reason: '',
});

// Get current user's department name
const currentUserDepartmentName = computed(() => {
  if (!props.currentUser?.department_id || !props.departments) return '';
  const dept = props.departments.find(d => d.id === props.currentUser.department_id);
  return dept ? dept.name : '';
});

const normalizedUsers = computed(() => (props.users || []).map((user) => ({
  ...user,
  display_name: `${user.name || '-'} (${user.email || '-'})`,
})));

const normalizedDepartments = computed(() => (props.departments || []).map((dept) => ({
  ...dept,
  display_name: `${dept.name || '-'} (${dept.code || '-'})`,
})));

// Flatten modules for display - get all modules including nested sub-modules
const flatModules = computed(() => {
  if (!props.modules) return [];
  
  const result = [];
  
  for (const group of props.modules) {
    // Skip if no children
    if (!group.children || group.children.length === 0) continue;
    
    const children = [];
    
    for (const child of group.children) {
      // If child has nested children (like utility with tickets, date_code, etc.)
      if (child.children && child.children.length > 0) {
        // Add each nested child as separate option
        for (const subChild of child.children) {
          children.push({
            key: subChild.key,
            label: `${child.label} > ${subChild.label}`
          });
        }
      } else {
        // Direct child module
        children.push({
          key: child.key,
          label: child.label
        });
      }
    }
    
    if (children.length > 0) {
      result.push({
        key: group.key,
        label: group.label,
        children: children
      });
    }
  }
  
  return result;
});

function onTypeChange() {
  // Reset fields when type changes
  if (form.type === 'existing_user') {
    form.user_id = props.currentUser?.id || null;
    form.target_user_name = '';
    form.target_user_email = '';
    form.target_department_id = '';
  } else {
    form.user_id = null;
  }
}

function submit() {
  if (form.module_keys.length === 0) {
    alert('Please select at least one module');
    return;
  }
  
  if (form.type === 'existing_user' && !form.user_id) {
    alert('Please select a user');
    return;
  }
  
  form.post('/request-access');
}
</script>
