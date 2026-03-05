<template>
  <li :class="['px-3 py-1 rounded hover:bg-slate-700/40 cursor-pointer flex items-center gap-2', item.color || 'text-slate-300']">
    <component :is="getIcon(item.icon)" :class="['w-4 h-4', item.color || 'text-slate-300']" />
    <template v-if="item.route">
      <Link :href="item.route" class="flex-1">{{ item.label }}</Link>
    </template>
    <template v-else>
      <span class="flex-1">{{ item.label }}</span>
    </template>
  </li>
</template>

<script setup>
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
import { Link } from '@inertiajs/vue3';

defineProps({
  item: {
    type: Object,
    required: true,
  },
});

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
</script>
