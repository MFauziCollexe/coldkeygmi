<template>
  <AppLayout>
    <div class="p-4 md:p-6">
      <h2 class="text-2xl font-bold mb-4">No Identity</h2>

      <div class="bg-slate-800 rounded p-4 md:p-6 space-y-6">
        <div class="flex flex-wrap gap-2 md:gap-4 border-b border-slate-700">
          <button
            type="button"
            @click="activeTab = 'manual'"
            class="px-4 py-2 font-medium border-b-2 text-sm md:text-base"
            :class="activeTab === 'manual' ? 'text-slate-200 border-indigo-600' : 'text-slate-400 border-transparent hover:text-slate-300'"
          >
            Manual Input
          </button>
          <button
            type="button"
            @click="activeTab = 'import'"
            class="px-4 py-2 font-medium border-b-2 text-sm md:text-base"
            :class="activeTab === 'import' ? 'text-slate-200 border-indigo-600' : 'text-slate-400 border-transparent hover:text-slate-300'"
          >
            Import Excel
          </button>
        </div>

        <div v-if="activeTab === 'manual'" class="space-y-4">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="relative group">
              <SearchableSelect
                v-model="form.customer"
                :options="customers"
                option-value="name"
                option-label="name"
                placeholder=" "
                empty-label="Pilih Customer"
                input-class="w-full pl-3 pr-10 pt-5 pb-2 !bg-slate-800 !border-slate-700 rounded-lg text-slate-100 placeholder-transparent"
                button-class="border-0 border-l !border-slate-700 rounded-r-lg !bg-slate-800 text-slate-100"
              />
              <label
                :class="[
                  'pointer-events-none absolute left-3 z-10 transition-all',
                  (form.customer
                    ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                    : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                  'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
                ]"
              >
                Customer
              </label>
            </div>
            <div class="relative">
              <input
                v-model="form.nopol"
                type="text"
                placeholder=" "
                class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
              />
              <label
                class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
              >
                NOPOL
              </label>
            </div>
          </div>

          <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-6">
            <label class="inline-flex items-center gap-2 text-sm leading-none">
              <input v-model="form.inout_label" type="radio" value="IN" class="accent-indigo-500" />
              <span>IN</span>
            </label>
            <label class="inline-flex items-center gap-2 text-sm leading-none">
              <input v-model="form.inout_label" type="radio" value="OUT" class="accent-indigo-500" />
              <span>OUT</span>
            </label>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="relative">
              <input
                v-model="form.out"
                type="text"
                placeholder=" "
                class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
              />
              <label
                class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
              >
                {{ form.inout_label }} Code
              </label>
            </div>
            <div class="relative">
              <input
                v-model="form.po"
                type="text"
                placeholder=" "
                class="peer w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
              />
              <label
                class="pointer-events-none absolute left-3 z-10 px-1 transition-all text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2 peer-placeholder-shown:top-1/2 peer-placeholder-shown:-translate-y-1/2 peer-placeholder-shown:text-base peer-placeholder-shown:text-slate-400 peer-placeholder-shown:bg-transparent peer-placeholder-shown:px-0 peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs peer-focus:text-slate-200 peer-focus:bg-slate-800 peer-focus:px-1"
              >
                PO
              </label>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="relative group">
              <EnhancedDatePicker
                v-model="form.transc_date"
                placeholder=" "
                input-class="w-full px-3 pt-5 pb-2 rounded-lg bg-slate-800 border border-slate-700 placeholder-transparent"
              />
              <label
                :class="[
                  'pointer-events-none absolute left-3 z-10 transition-all',
                  (form.transc_date
                    ? 'px-1 text-xs text-slate-300 bg-slate-800 top-0 -translate-y-1/2'
                    : 'px-0 text-base text-slate-400 bg-transparent top-1/2 -translate-y-1/2'),
                  'group-focus-within:px-1 group-focus-within:text-xs group-focus-within:text-slate-200 group-focus-within:bg-slate-800 group-focus-within:top-0 group-focus-within:-translate-y-1/2',
                ]"
              >
                Transc Date
              </label>
            </div>
            <div class="hidden md:block"></div>
          </div>

          <div class="pt-4 border-t border-slate-700 flex flex-col-reverse gap-2 md:flex-row md:justify-end md:gap-3">
            <button @click="onCancelManual" type="button" class="px-4 py-2 rounded bg-slate-700 hover:bg-slate-600 text-white">
              Cancel
            </button>
            <button @click="previewManual" type="button" class="px-4 py-2 rounded bg-indigo-600 hover:bg-indigo-700 text-white">
              Print to PDF
            </button>
          </div>
        </div>

        <div v-else class="space-y-4">
          <div
            class="border-2 border-dashed rounded-lg p-5 md:p-8 text-center cursor-pointer transition"
            :class="dragActive ? 'border-indigo-500 bg-slate-700/40' : 'border-slate-600'"
            @click="openFileDialog"
            @dragover.prevent="onDragOver"
            @dragleave.prevent="onDragLeave"
            @drop.prevent="onDrop"
          >
            <p class="text-slate-300 font-medium mb-2">Upload file CSV</p>
            <p class="text-slate-500 text-sm mb-4">Kolom: Customer, INOUT, Nopol, TransDate, Out, PO</p>
            <p class="text-indigo-300 text-sm">Klik area ini atau drag-and-drop file</p>
            <input ref="fileInput" type="file" accept=".csv,.txt" @change="onImportFileChange" class="hidden" />
          </div>

          <div v-if="importFileName" class="bg-slate-900 border border-slate-700 rounded p-3 text-sm text-slate-300">
            File: {{ importFileName }} ({{ importRows.length }} baris)
          </div>

          <div class="pt-4 border-t border-slate-700 flex flex-col-reverse gap-2 md:flex-row md:justify-end md:gap-3">
            <button @click="onCancelImport" type="button" class="px-4 py-2 rounded bg-slate-700 hover:bg-slate-600 text-white">
              Cancel
            </button>
            <button @click="previewImport" :disabled="importRows.length === 0" type="button" class="px-4 py-2 rounded bg-indigo-600 hover:bg-indigo-700 text-white disabled:opacity-50 disabled:cursor-not-allowed">
              Preview Import
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import EnhancedDatePicker from '@/Components/EnhancedDatePicker.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';

const props = defineProps({
  customers: {
    type: Array,
    default: () => [],
  },
});

const activeTab = ref('manual');
const fileInput = ref(null);
const importFileName = ref('');
const importRows = ref([]);
const dragActive = ref(false);
const customers = props.customers || [];

const initialForm = () => ({
  customer: '',
  nopol: '',
  transc_date: '',
  inout_label: 'OUT',
  out: '',
  po: '',
  qty: '',
  gate: '',
  note: '',
  tkbm: '',
  checker: '',
});

const form = reactive(initialForm());

function onCancelManual() {
  Object.assign(form, initialForm());
}

function onCancelImport() {
  importRows.value = [];
  importFileName.value = '';
  dragActive.value = false;
  if (fileInput.value) fileInput.value.value = '';
}

function previewManual() {
  const oneRow = [{
    customer: form.customer || '-',
    nopol: form.nopol || '-',
    transcDate: formatDate(form.transc_date),
    inoutLabel: form.inout_label || 'IN',
    outValue: form.out || '-',
    po: form.po || '-',
    qty: form.qty || '',
    gate: form.gate || '',
    note: form.note || '',
    tkbm: form.tkbm || '',
    checker: form.checker || '',
  }];

  openPreviewWindow(oneRow);
}

function previewImport() {
  if (importRows.value.length === 0) return;
  openPreviewWindow(importRows.value);
}

async function onImportFileChange(event) {
  const file = event.target.files?.[0];
  await processImportFile(file);
}

function openFileDialog() {
  if (fileInput.value) fileInput.value.click();
}

function onDragOver() {
  dragActive.value = true;
}

function onDragLeave() {
  dragActive.value = false;
}

async function onDrop(event) {
  dragActive.value = false;
  const file = event.dataTransfer?.files?.[0];
  await processImportFile(file);
}

async function processImportFile(file) {
  if (!file) return;

  importFileName.value = file.name;
  const text = await file.text();
  importRows.value = parseCsvToRows(text);
}

function parseCsvToRows(text) {
  const lines = text.split(/\r?\n/).filter(line => line.trim() !== '');
  if (lines.length <= 1) return [];

  const delimiter = detectDelimiter(lines[0]);
  const dataLines = lines.slice(1);
  const rows = [];

  for (const line of dataLines) {
    const cols = splitCsvLine(line, delimiter);
    if (cols.length === 0) continue;

    const customer = (cols[0] || '').trim();
    const inoutLabel = (cols[1] || 'IN').trim() || 'IN';
    const nopol = (cols[2] || '').trim();
    const transcDateRaw = (cols[3] || '').trim();
    const outValue = (cols[4] || '').trim();
    const po = (cols[5] || '').trim();

    if (!customer && !nopol && !transcDateRaw && !outValue && !po) continue;

    rows.push({
      customer: customer || '-',
      nopol: nopol || '-',
      transcDate: formatAnyDate(transcDateRaw),
      inoutLabel,
      outValue: outValue || '-',
      po: po || '-',
      qty: '',
      gate: '',
      note: '',
      tkbm: '',
      checker: '',
    });
  }

  return rows;
}

function detectDelimiter(headerLine) {
  const candidates = [',', ';', '\t'];
  let best = ',';
  let bestCount = -1;

  for (const d of candidates) {
    const count = headerLine.split(d).length;
    if (count > bestCount) {
      bestCount = count;
      best = d;
    }
  }

  return best;
}

function splitCsvLine(line, delimiter) {
  const result = [];
  let current = '';
  let inQuotes = false;

  for (let i = 0; i < line.length; i += 1) {
    const char = line[i];
    const next = line[i + 1];

    if (char === '"' && inQuotes && next === '"') {
      current += '"';
      i += 1;
      continue;
    }
    if (char === '"') {
      inQuotes = !inQuotes;
      continue;
    }
    if (char === delimiter && !inQuotes) {
      result.push(current);
      current = '';
      continue;
    }
    current += char;
  }

  result.push(current);
  return result;
}

function openPreviewWindow(rows) {
  const win = window.open('', '_blank');
  if (!win) return;

  const logoUrl = `${window.location.origin}/image/logo-gmi.png`;
  const pagesHtml = rows.map(row => renderPageHtml(row, logoUrl)).join('<div class="page-break"></div>');
  const html = `<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>No Identity Preview</title>
  <style>
    @page { size: A5; margin: 8mm; }
    body { font-family: Arial, sans-serif; margin: 0; color: #000; }
    .no-print { padding: 8px; }
    .sheet { page-break-inside: avoid; }
    .page-break { break-after: page; page-break-after: always; }
    table { width: 100%; border-collapse: collapse; table-layout: fixed; outline: 1px solid #000; outline-offset: -1px; }
    th, td { border: 1px solid #000; padding: 6px; font-size: 18px; color: #000; }
    th { text-align: center; font-weight: 700; }
    .left { text-align: left; }
    .top { vertical-align: top; }
    .big { font-size: 32px; font-weight: 800; text-align: center; }
    .mid { font-size: 20px; font-weight: 700; }
    .red { color: #d40000 !important; }
    .logo-cell { text-align: left; padding: 10px; min-height: 70px; }
    .logo { height: 56px; object-fit: contain; }
    .write-row td { height: 58px; vertical-align: top; padding-top: 8px; }
    @media print {
      .no-print { display: none; }
    }
  </style>
</head>
<body>
  <div class="no-print"><button onclick="window.print()">Print</button></div>
  ${pagesHtml}
  <script>
    window.addEventListener('load', function () {
      window.focus();
      window.print();
    });
  <\/script>
</body>
</html>`;

  win.document.open();
  win.document.write(html);
  win.document.close();
}

function renderPageHtml(row, logoUrl) {
  return `<div class="sheet">
    <table>
      <tr>
        <th colspan="4" class="logo-cell">
          <img src="${logoUrl}" alt="Logo GMI" class="logo" />
        </th>
      </tr>
      <tr>
        <th style="width:25%">CUSTOMER</th>
        <td colspan="3" class="big">${escapeHtml(row.customer)}</td>
      </tr>
      <tr>
        <th>NOPOL</th>
        <td colspan="3" class="big red">${escapeHtml(row.nopol)}</td>
      </tr>
      <tr>
        <th>TRANSC DATE</th>
        <td colspan="3" class="big">${escapeHtml(row.transcDate)}</td>
      </tr>
      <tr>
        <th>${escapeHtml(row.inoutLabel)}</th>
        <td colspan="3" class="big red">${escapeHtml(row.outValue)}</td>
      </tr>
      <tr>
        <th>PO</th>
        <td colspan="3" class="big red">${escapeHtml(row.po)}</td>
      </tr>
      <tr>
        <th>QTY</th>
        <th>GATE</th>
        <th colspan="2">NOTE</th>
      </tr>
      <tr class="write-row">
        <td class="mid">${escapeHtml(row.qty)}</td>
        <td class="mid">${escapeHtml(row.gate)}</td>
        <td colspan="2" rowspan="3" class="top">${escapeHtml(row.note)}</td>
      </tr>
      <tr>
        <th>TKBM</th>
        <th>CHECKER</th>
      </tr>
      <tr class="write-row">
        <td class="mid">${escapeHtml(row.tkbm)}</td>
        <td class="mid">${escapeHtml(row.checker)}</td>
      </tr>
    </table>
  </div>`;
}

function formatDate(value) {
  if (!value) return '-';
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return '-';
  return formatDateFromDateObject(d);
}

function formatAnyDate(raw) {
  if (!raw) return '-';

  const numeric = Number(raw);
  if (!Number.isNaN(numeric) && numeric > 20000) {
    const base = new Date(Date.UTC(1899, 11, 30));
    base.setUTCDate(base.getUTCDate() + numeric);
    return formatDateFromDateObject(base);
  }

  const parsed = new Date(raw);
  if (!Number.isNaN(parsed.getTime())) {
    return formatDateFromDateObject(parsed);
  }

  return raw;
}

function formatDateFromDateObject(date) {
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const dd = String(date.getDate()).padStart(2, '0');
  const mmm = months[date.getMonth()];
  const yy = String(date.getFullYear()).slice(-2);
  return `${dd}-${mmm}-${yy}`;
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
}
</script>
