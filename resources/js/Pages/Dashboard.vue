<template>
  <AppLayout>
    <div class="max-w-7xl mx-auto p-4 md:p-6">
      <div class="mb-6">
        <h1 class="text-3xl font-bold md:text-4xl">Dashboard</h1>
        <p class="mt-1 text-sm text-slate-400 md:text-base">
          {{ departmentName }} Department Overview - Welcome back, <span class="font-semibold">{{ displayUserName }}</span>
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <DashboardCard
          v-for="stat in mainStats"
          :key="stat.id"
          :title="stat.title"
          :value="stat.value"
          :icon="stat.icon"
          :bg-color="stat.bgColor"
        />
      </div>

      <div class="mt-8 bg-slate-800 border border-slate-700 rounded-lg p-4 md:p-6 shadow-inner">
        <h2 class="text-lg font-semibold mb-4">Grafik Ticket Breakdown ({{ departmentCode }})</h2>
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-stretch">
          <div class="xl:col-span-6 bg-slate-900/30 border border-slate-700 rounded-lg p-3 md:p-4 min-h-[280px] md:min-h-[340px] flex items-center justify-center overflow-hidden">
            <div v-if="itDashboard.tickets.total === 0" class="text-slate-400 text-sm w-full">
              Belum ada data ticket untuk ditampilkan.
            </div>
            <div v-else class="w-full h-full flex items-center justify-center">
              <svg viewBox="0 0 460 280" class="w-full h-[240px] md:h-[280px] max-w-[520px] overflow-visible">
                <circle
                  :cx="donutCenter.x"
                  :cy="donutCenter.y"
                  :r="pieRadius"
                  fill="transparent"
                  stroke="#334155"
                  :stroke-width="donutThickness"
                />
                <g :transform="`rotate(-90 ${donutCenter.x} ${donutCenter.y})`">
                  <circle
                    v-for="segment in pieSegments"
                    :key="segment.key"
                    :cx="donutCenter.x"
                    :cy="donutCenter.y"
                    :r="pieRadius"
                    fill="transparent"
                    :stroke="segment.color"
                    :stroke-width="donutThickness"
                    :stroke-dasharray="segment.dashArray"
                    :stroke-dashoffset="segment.dashOffset"
                  />
                </g>
                <g v-for="segment in pieSegments" :key="`${segment.key}-label`">
                  <polyline
                    :points="`${segment.lineStartX},${segment.lineStartY} ${segment.lineMidX},${segment.lineMidY} ${segment.lineEndX},${segment.lineEndY}`"
                    fill="none"
                    stroke="#94a3b8"
                    stroke-width="1.5"
                  />
                  <text
                    :x="segment.labelX"
                    :y="segment.labelY"
                    :text-anchor="segment.anchor"
                    class="fill-slate-200 text-[10px]"
                  >
                    {{ segment.label }} {{ segment.percent.toFixed(2) }}%
                  </text>
                </g>
                <text :x="donutCenter.x" :y="donutCenter.y - 2" text-anchor="middle" class="fill-white text-[22px] font-bold">
                  {{ itDashboard.tickets.total }}
                </text>
                <text :x="donutCenter.x" :y="donutCenter.y + 16" text-anchor="middle" class="fill-slate-400 text-[10px]">
                  Total Tickets
                </text>
              </svg>
            </div>
          </div>

          <div class="xl:col-span-6 grid grid-cols-1 gap-4 min-h-[340px]">
            <div class="bg-slate-900/30 border border-slate-700 rounded-lg p-4 flex flex-col justify-center">
              <h3 class="text-base font-semibold mb-4">Request Access</h3>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <StatCard label="Total" :value="itDashboard.request_access.total" text-color="text-white" />
                <StatCard label="Pending" :value="itDashboard.request_access.pending" text-color="text-amber-300" />
                <StatCard label="Approved" :value="itDashboard.request_access.approved" text-color="text-emerald-300" />
              </div>
            </div>

            <div class="bg-slate-900/30 border border-slate-700 rounded-lg p-4 flex flex-col justify-center">
              <h3 class="text-base font-semibold mb-4">Users ({{ departmentCode }})</h3>
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <StatCard label="Total" :value="itDashboard.users.total" text-color="text-white" />
                <StatCard label="Active" :value="itDashboard.users.active" text-color="text-emerald-300" />
                <StatCard label="Deactive" :value="itDashboard.users.deactive" text-color="text-rose-300" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-8 bg-slate-800 border border-slate-700 rounded-lg p-4 md:p-6 shadow-inner">
        <h2 class="text-lg font-semibold mb-4">Recent Logs</h2>
        <div v-if="itDashboard.logs.length === 0" class="text-slate-400 text-sm">
          Belum ada logs.
        </div>
        <div v-else class="overflow-x-auto">
          <table class="min-w-[760px] text-sm">
            <thead class="text-slate-400 border-b border-slate-700">
              <tr>
                <th class="text-left py-2 pr-4">Time</th>
                <th class="text-left py-2 pr-4">Table</th>
                <th class="text-left py-2 pr-4">Action</th>
                <th class="text-left py-2 pr-4">Description</th>
                <th class="text-left py-2">User</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in itDashboard.logs" :key="log.id" class="border-b border-slate-700/60">
                <td class="py-2 pr-4">{{ formatDate(log.created_date) }}</td>
                <td class="py-2 pr-4">{{ log.table_name || '-' }}</td>
                <td class="py-2 pr-4 uppercase">{{ log.action || '-' }}</td>
                <td class="py-2 pr-4">{{ log.description || '-' }}</td>
                <td class="py-2">{{ log.user_email || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <div class="mt-8 bg-slate-800 border border-slate-700 rounded-lg p-4 md:p-6 shadow-inner">
        <h2 class="text-lg font-semibold mb-4">Quick Stats</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <StatCard
            v-for="stat in quickStats"
            :key="stat.id"
            :label="stat.label"
            :value="stat.value"
            :text-color="stat.textColor"
          />
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import DashboardCard from '@/Components/DashboardCard.vue';
import StatCard from '@/Components/StatCard.vue';

const page = usePage();

const itDashboard = computed(() => page.props.itDashboard || {
  department: { name: 'IT', code: 'IT' },
  tickets: { total: 0, open: 0, in_progress: 0, hold: 0, closed: 0, resolved: 0, overdue: 0, completion_rate: 0 },
  request_access: { total: 0, pending: 0, approved: 0 },
  users: { total: 0, active: 0, deactive: 0 },
  users_all_departments: { total: 0, active: 0, deactive: 0 },
  logs: [],
});

const departmentName = computed(() => itDashboard.value.department?.name || 'IT');
const departmentCode = computed(() => itDashboard.value.department?.code || 'IT');

const displayUserName = computed(() => {
  const currentUser = page.props.auth?.user;

  if (!currentUser) return 'User';
  if (currentUser.first_name && currentUser.last_name) return `${currentUser.first_name} ${currentUser.last_name}`;
  if (currentUser.first_name) return currentUser.first_name;
  if (currentUser.last_name) return currentUser.last_name;
  if (currentUser.name) return currentUser.name;
  return 'User';
});

const mainStats = computed(() => [
  {
    id: 'users',
    title: 'Total User All Department',
    value: itDashboard.value.users_all_departments?.total ?? 0,
    icon: 'USR',
    bgColor: 'indigo',
  },
  {
    id: 'tickets',
    title: `Total Ticket ${departmentCode.value}`,
    value: itDashboard.value.tickets.total,
    icon: 'TKT',
    bgColor: 'emerald',
  },
  {
    id: 'open',
    title: 'Open Ticket',
    value: itDashboard.value.tickets.open,
    icon: 'OPN',
    bgColor: 'amber',
  },
]);

const quickStats = computed(() => [
  {
    id: 'completion-rate',
    label: 'Completion Rate',
    value: `${itDashboard.value.tickets.completion_rate}%`,
    textColor: 'text-emerald-400',
  },
  {
    id: 'overdue-tickets',
    label: 'Overdue Tickets',
    value: itDashboard.value.tickets.overdue,
    textColor: 'text-rose-400',
  },
  {
    id: 'pending-access',
    label: 'Pending Access',
    value: itDashboard.value.request_access.pending,
    textColor: 'text-amber-300',
  },
  {
    id: 'active-users',
    label: `Active Users ${departmentCode.value}`,
    value: itDashboard.value.users.active,
    textColor: 'text-white',
  },
]);

const ticketChartData = computed(() => {
  const total = itDashboard.value.tickets.total || 0;
  const rows = [
    { key: 'open', label: 'Open', value: itDashboard.value.tickets.open, color: '#f59e0b', dotColorClass: 'bg-amber-400' },
    { key: 'in_progress', label: 'In Progress', value: itDashboard.value.tickets.in_progress, color: '#38bdf8', dotColorClass: 'bg-sky-400' },
    { key: 'hold', label: 'On Hold', value: itDashboard.value.tickets.hold, color: '#fb7185', dotColorClass: 'bg-rose-400' },
    { key: 'closed', label: 'Closed', value: itDashboard.value.tickets.closed, color: '#34d399', dotColorClass: 'bg-emerald-400' },
    { key: 'resolved', label: 'Resolved', value: itDashboard.value.tickets.resolved, color: '#818cf8', dotColorClass: 'bg-indigo-400' },
  ];

  return rows.map((row) => ({
    ...row,
    percent: total > 0 ? Math.round((row.value / total) * 100) : 0,
  }));
});

const pieRadius = 64;
const donutThickness = 24;
const donutCenter = { x: 190, y: 140 };
const pieCircumference = 2 * Math.PI * pieRadius;

const pieSegments = computed(() => {
  let accumulatedPercent = 0;

  return ticketChartData.value
    .filter((item) => item.percent > 0)
    .map((item) => {
      const segmentLength = (item.percent / 100) * pieCircumference;
      const dashOffset = -((accumulatedPercent / 100) * pieCircumference);
      const startAngle = -90 + (accumulatedPercent / 100) * 360;
      accumulatedPercent += item.percent;
      const endAngle = -90 + (accumulatedPercent / 100) * 360;
      const midAngle = (startAngle + endAngle) / 2;
      const rad = (midAngle * Math.PI) / 180;
      const lineStartX = donutCenter.x + (pieRadius + 2) * Math.cos(rad);
      const lineStartY = donutCenter.y + (pieRadius + 2) * Math.sin(rad);
      const lineMidX = donutCenter.x + (pieRadius + 18) * Math.cos(rad);
      const lineMidY = donutCenter.y + (pieRadius + 18) * Math.sin(rad);
      const rightSide = Math.cos(rad) >= 0;
      const lineEndX = lineMidX + (rightSide ? 16 : -16);
      const lineEndY = lineMidY;

      return {
        key: item.key,
        label: item.label,
        percent: item.percent,
        color: item.color,
        dashArray: `${segmentLength} ${pieCircumference}`,
        dashOffset,
        lineStartX,
        lineStartY,
        lineMidX,
        lineMidY,
        lineEndX,
        lineEndY,
        labelX: lineEndX + (rightSide ? 3 : -3),
        labelY: lineEndY - 2,
        anchor: rightSide ? 'start' : 'end',
      };
    });
});

const formatDate = (value) => {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '-';
  return date.toLocaleString();
};
</script>
