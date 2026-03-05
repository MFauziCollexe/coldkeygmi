<template>
  <aside :class="open ? 'w-72' : 'w-20'" class="shrink-0 bg-slate-900 text-slate-200 h-screen transition-[width] duration-300 ease-in-out border-r border-slate-700">
    <Link href="/dashboard" class="h-16 flex items-center gap-3 px-5 border-b border-slate-700 hover:bg-slate-800/40 transition-colors">
      <img v-if="open" src="/image/logo-gmi-text-putih.png" alt="GMI" class="h-8 object-contain" />
      <div v-else class="w-9 h-9 bg-indigo-600 rounded-lg flex items-center justify-center shrink-0 font-bold text-white">GMI</div>
    </Link>

    <nav class="px-3 py-6 text-sm space-y-1">
      <ul class="space-y-2">
        <SidebarMenuGroup
          v-for="menu in visibleMenu"
          :key="menu.id"
          :item="menu"
          :sidebar-open="open"
        />
      </ul>
    </nav>
  </aside>
</template>

<script setup>
import { defineProps, computed, watch, watchEffect } from 'vue';
import SidebarMenuGroup from './SidebarMenuGroup.vue';
import { sidebarMenuConfig } from '@/Config/sidebarMenu.js';
import { Link, usePage } from '@inertiajs/vue3';

defineProps({
  open: {
    type: Boolean,
    default: true,
  },
});

const page = usePage();

const userPermsRef = ref([]);
const permsFetched = ref(false);

const isAdmin = computed(() => page.props.auth?.is_admin || page.props.is_admin || false);

async function fetchPermissionsFromServer() {
  try {
    const userId = page.props.auth?.user?.id || page.props.user?.id;
    if (!userId) return;
    const res = await fetch(`/control-panel/module-control/user/${userId}`);
    if (!res.ok) return;
    const data = await res.json();
    if (Array.isArray(data.permissions)) {
      userPermsRef.value = data.permissions;
    }
  } catch (e) {
  } finally {
    permsFetched.value = true;
  }
}

if (page.props && page.props.auth && Array.isArray(page.props.auth.module_permissions) && page.props.auth.module_permissions.length) {
  userPermsRef.value = page.props.auth.module_permissions;
  permsFetched.value = true;
} else {
  fetchPermissionsFromServer();
}

const userPerms = computed(() => userPermsRef.value || []);

function itemAllowed(item) {
  // if item has explicit module_key, check permission strictly
  if (item.module_key) {
    const key = item.module_key;
    const keyUnd = key.replace(/\./g, '_');
    
    // Direct match
    if (userPerms.value.includes(key) || userPerms.value.includes(keyUnd)) {
      return true;
    }
    
    // Check if any child permission matches (for parent menus like gmisl.master_data)
    if (item.children && item.children.length) {
      const hasChildPermission = item.children.some(child => {
        if (child.module_key) {
          return userPerms.value.includes(child.module_key) || userPerms.value.includes(child.module_key.replace(/\./g, '_'));
        }
        return false;
      });
      if (hasChildPermission) {
        return true;
      }
    }
    
    return false;
  }

  // if has children, allow if any child allowed
  if (item.children && item.children.length) {
    return item.children.some(child => itemAllowed(child));
  }

  // default allow
  return true;
}

function filterMenuItem(item) {
  if (!itemAllowed(item)) return null;

  const copy = { ...item };
  if (copy.children && copy.children.length) {
    const children = copy.children
      .map(child => filterMenuItem(child))
      .filter(Boolean);
    copy.children = children;
  }

  return copy;
}

const sidebarMenu = computed(() => sidebarMenuConfig.map(i => filterMenuItem(i)).filter(Boolean));

const visibleMenu = computed(() => {
  if (isAdmin.value) {
    return sidebarMenuConfig;
  }

  const filtered = sidebarMenu.value;
  if (!filtered || filtered.length === 0) {
    return [];
  }
  return filtered;
});

watch(() => page.props.auth, (val) => {
}, { immediate: true });

watchEffect(() => {
});
</script>

<script>
import { ref } from 'vue';
export default {
  name: 'Sidebar'
}
</script>
