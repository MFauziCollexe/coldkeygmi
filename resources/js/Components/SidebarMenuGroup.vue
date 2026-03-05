<template>
  <li>
    <button
      @click="toggleOpen"
      class="w-full text-left flex items-center justify-between px-3 py-2 rounded hover:bg-slate-700/40 transition"
    >
      <div class="flex items-center gap-3">
        <component :is="getIcon(item.icon)" :class="`w-5 h-5 ${item.color || 'text-slate-300'}`" />
        <span v-if="sidebarOpen" :class="item.color || 'text-slate-300'">{{ item.label }}</span>
      </div>
      <span v-if="sidebarOpen" class="text-sm text-slate-400">{{ isOpen ? '▾' : '▸' }}</span>
    </button>

    <ul v-if="isOpen && sidebarOpen" class="mt-2 ml-4 space-y-1 text-slate-300">
      <template v-for="child in item.children" :key="child.id">
        <SidebarSubItem v-if="!child.children" :item="child" />
        <SidebarMenuGroup v-else :item="child" :sidebar-open="sidebarOpen" />
      </template>
    </ul>
  </li>
</template>

<script>
export default {
  name: 'SidebarMenuGroup',
};
</script>

<script setup>
import { ref } from 'vue';
import {
  FileText,
  Layers,
  ShoppingCart,
  Wrench,
  Ticket,
  Calendar,
  CalendarDays,
  CheckSquare,
  Users,
  Clipboard,
  Fingerprint,
  Clock,
  DollarSign,
  Database,
  Settings,
  User,
  Server,
  Building,
  Briefcase,
} from 'lucide-vue-next';
import SidebarSubItem from './SidebarSubItem.vue';

defineProps({
  item: {
    type: Object,
    required: true,
  },
  sidebarOpen: {
    type: Boolean,
    default: true,
  },
});

const isOpen = ref(false);

const iconMap = {
  FileText,
  Layers,
  ShoppingCart,
  Wrench,
  Ticket,
  Calendar,
  CalendarDays,
  CheckSquare,
  Users,
  Clipboard,
  Fingerprint,
  Clock,
  DollarSign,
  Database,
  Settings,
  User,
  Server,
  Building,
  Briefcase,
};

function getIcon(iconName) {
  return iconMap[iconName] || FileText;
}

function toggleOpen() {
  isOpen.value = !isOpen.value;
}
</script>
