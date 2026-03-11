<template>
  <AppLayout>
    <div class="p-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-2xl font-bold">Modul Control</h2>
        <div>
          <button @click="save" class="bg-indigo-600 px-4 py-2 rounded text-white">Simpan Perubahan</button>
        </div>
      </div>

      <div class="bg-slate-800 rounded p-4 flex gap-6">
        <div class="w-1/4">
          <h3 class="text-sm text-slate-300 mb-2">Pilih User</h3>
          <input v-model="userFilter" placeholder="Cari user..." class="w-full px-3 py-2 rounded bg-slate-700 mb-3 text-sm" />
          <div class="space-y-2 max-h-[420px] overflow-auto">
            <button v-for="u in pagedUsers" :key="u.id" @click="selectUser(u)" :class="['w-full text-left px-4 py-3 rounded', selectedUser && selectedUser.id===u.id ? 'bg-indigo-600 text-white' : 'bg-slate-700 text-slate-300']">
              <div class="font-semibold">{{ u.name || '-' }}</div>
              <div class="text-xs text-slate-400">{{ u.email }}</div>
            </button>
          </div>
          <div class="flex items-center justify-between mt-2">
            <button @click="prevUsers" class="px-3 py-1 bg-slate-700 rounded text-sm" :disabled="userPage===1">Prev</button>
            <div class="text-xs text-slate-400">Page {{ userPage }}</div>
            <button @click="nextUsers" class="px-3 py-1 bg-slate-700 rounded text-sm" :disabled="(userPage * userPerPage) >= filteredUsers.length">Next</button>
          </div>
        </div>

        <div class="flex-1 border-l border-slate-700 pl-6">
          <h3 class="text-sm text-slate-300 mb-4">Permission untuk <span class="text-indigo-300">{{ selectedUser ? (selectedUser.name || '-') : '-' }}</span></h3>

          <div class="space-y-4 max-h-[480px] overflow-auto pr-4">
            <div v-for="group in modulesList" :key="group.key" class="p-4 border border-slate-700 rounded">
              <label class="flex items-center gap-3">
                <input type="checkbox" :checked="hasPermission(group.key)" @change="onToggle(group, $event.target.checked)" />
                <span class="font-semibold">{{ group.label }}</span>
              </label>

              <div v-if="group.children && group.children.length" class="pl-6 mt-3 space-y-2">
                <div v-for="child in group.children" :key="child.key">
                  <label class="flex items-center gap-3">
                    <input type="checkbox" :checked="hasPermission(child.key)" @change="onToggle(child, $event.target.checked)" />
                    <span>{{ child.label }}</span>
                  </label>
                  <div v-if="child.children" class="pl-6 mt-1">
                    <div v-for="sub in child.children" :key="sub.key">
                      <label class="flex items-center gap-3">
                        <input type="checkbox" :checked="hasPermission(sub.key)" @change="onToggle(sub, $event.target.checked)" />
                        <span>{{ sub.label }}</span>
                      </label>
                      <div v-if="sub.children && sub.children.length" class="pl-6 mt-1">
                        <div v-for="leaf in sub.children" :key="leaf.key">
                          <label class="flex items-center gap-3">
                            <input type="checkbox" :checked="hasPermission(leaf.key)" @change="onToggle(leaf, $event.target.checked)" />
                            <span>{{ leaf.label }}</span>
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import modules from '@/Config/modules.js';
import { router } from '@inertiajs/vue3';

const props = defineProps({ users: Array, modules: Object });
const users = props.users || [];

// allow server-provided modules or fallback to client modules
const modulesList = props.modules || modules;

const selectedUser = ref(null);
const selectedPermissions = ref([]);

const userFilter = ref('');
const userPage = ref(1);
const userPerPage = 8;

const filteredUsers = computed(() => {
  if (!userFilter.value) return users;
  const q = userFilter.value.toLowerCase();
  return users.filter(u => ((u.name || '').toLowerCase().includes(q) || (u.email || '').toLowerCase().includes(q)));
});

const pagedUsers = computed(() => {
  const start = (userPage.value - 1) * userPerPage;
  return filteredUsers.value.slice(start, start + userPerPage);
});

function nextUsers() { userPage.value++; }
function prevUsers() { if (userPage.value>1) userPage.value--; }

async function selectUser(u) {
  selectedUser.value = u;
  // fetch current permissions for user (JSON)
  try {
    const res = await fetch(`/control-panel/module-control/user/${u.id}`);
    if (!res.ok) {
      const text = await res.text();
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Gagal mengambil permissions: ' + res.status + '. Cek console untuk detail.',
        confirmButtonColor: '#dc2626'
      });
      selectedPermissions.value = [];
      return;
    }

    const data = await res.json();
    // normalize loaded permissions to underscore format
    selectedPermissions.value = Array.isArray(data.permissions) ? data.permissions.map(p => p.replace(/\./g, '_')) : [];
  } catch (e) {
    selectedPermissions.value = [];
  }
}

function save() {
  if (!selectedUser.value) {
    Swal.fire({
      icon: 'warning',
      title: 'Alert',
      text: 'Pilih user terlebih dahulu',
      confirmButtonColor: '#4f46e5'
    });
    return;
  }
  // normalize keys (convert dot-separated keys to underscore format)
  const normalized = selectedPermissions.value.map(k => k.replace(/\./g, '_'));
  router.post('/control-panel/module-control/save', {
    user_id: selectedUser.value.id,
    permissions: normalized,
  });
}

function hasPermission(key) {
  // normalize key to match stored format (dots -> underscores)
  const normKey = key.replace(/\./g, '_');
  // check exact key
  if (selectedPermissions.value.includes(normKey)) return true;
  // check if any descendant of this key is present
  const node = findNodeByKey(key, modulesList);
  if (!node) return false;
  const descendants = collectDescendants(node);
  // normalize descendant keys when checking
  return descendants.some(k => selectedPermissions.value.includes(k.replace(/\./g, '_')));
}

function findNodeByKey(key, list) {
  for (const node of list) {
    if (node.key === key) return node;
    if (node.children) {
      const found = findNodeByKey(key, node.children);
      if (found) return found;
    }
  }
  return null;
}

function collectDescendants(node) {
  let keys = [];
  if (node.children && node.children.length) {
    for (const c of node.children) {
      keys.push(c.key);
      keys = keys.concat(collectDescendants(c));
    }
  }
  return keys;
}

function onToggle(node, checked) {
  const keys = [node.key].concat(collectDescendants(node));
  const normalizedKeys = keys.map(k => k.replace(/\./g, '_'));
  if (checked) {
    // add normalized keys
    for (const k of normalizedKeys) {
      if (!selectedPermissions.value.includes(k)) selectedPermissions.value.push(k);
    }
  } else {
    // remove normalized keys
    selectedPermissions.value = selectedPermissions.value.filter(x => !normalizedKeys.includes(x));
  }
}
</script>

<style scoped>
.dropdown-item { display: block; }
</style>
