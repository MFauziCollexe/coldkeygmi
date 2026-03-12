import { writeFile, mkdir } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { sidebarMenuConfig } from '../resources/js/Config/sidebarMenu.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

function esc(value) {
  return String(value ?? '')
    .replaceAll('\r', '')
    // Avoid Mermaid bracket conflicts inside labels.
    .replaceAll('[', '(')
    .replaceAll(']', ')')
    .replaceAll('"', "'")
    // Mermaid link label delimiter; avoid breaking syntax.
    .replaceAll('|', '/');
}

function hashString(value) {
  const text = String(value ?? '');
  let hash = 2166136261;
  for (let i = 0; i < text.length; i++) {
    hash ^= text.charCodeAt(i);
    hash = Math.imul(hash, 16777619);
  }
  return (hash >>> 0).toString(36);
}

function flattenLeaves(config) {
  const leaves = [];
  const walk = (node, path = []) => {
    const nextPath = [...path, node.id || node.key || node.label];
    if (node.route) {
      leaves.push({
        title: node.label,
        groupPath: path.map((p) => String(p)),
        route: node.route,
        module_key: node.module_key || '',
        idPath: nextPath,
      });
    }
    for (const child of node.children || []) walk(child, nextPath);
  };
  for (const top of config) walk(top, [top.label]);
  return leaves;
}

function flowForLeaf(leaf) {
  const route = String(leaf.route || '').trim();
  const title = `${leaf.title} (${route})`;

  const prefix = `m_${hashString(route)}`;
  const nid = (suffix) => `${prefix}_${suffix}`;

  const start = nid('start');
  const end = nid('end');

  const lines = [];
  lines.push('flowchart LR');
  // Keep syntax minimal for maximum compatibility across Mermaid versions.
  lines.push(`  ${start}([Mulai])`);
  lines.push(`  ${end}([Selesai])`);

  const link = (a, b, label = '') => {
    const text = String(label ?? '').trim();
    if (text === '') {
      lines.push(`  ${a} --> ${b}`);
      return;
    }
    // Mermaid flowchart edge label syntax uses pipes (not quotes).
    lines.push(`  ${a} -->|${esc(text)}| ${b}`);
  };
  const nodeProc = (key, label) => lines.push(`  ${key}["${esc(label)}"]`);
  const nodeDecision = (key, label) => lines.push(`  ${key}{"${esc(label)}"}`);
  // Use standard node syntax to avoid Mermaid parsing issues with slashes in labels (routes, dates, etc).
  const nodeData = (key, label) => lines.push(`  ${key}["${esc(label)}"]`);

  // Templates by route pattern
  if (route.startsWith('/master-data/')) {
    const open = nid('open');
    const load = nid('load');
    const action = nid('action');
    const create = nid('create');
    const edit = nid('edit');
    const del = nid('delete');
    const confirm = nid('confirm');
    const validate = nid('validate');
    const save = nid('save');
    const refresh = nid('refresh');
    const error = nid('error');

    nodeProc(open, `Buka halaman ${leaf.title}`);
    nodeData(load, 'Load data + filter + pagination');
    nodeDecision(action, 'Pilih aksi?');
    nodeProc(create, 'Tambah data');
    nodeProc(edit, 'Edit data');
    nodeProc(del, 'Hapus data');
    nodeDecision(confirm, 'Konfirmasi?');
    nodeProc(validate, 'Validasi input');
    nodeProc(save, 'Simpan ke database');
    nodeProc(refresh, 'Reload list');
    nodeProc(error, 'Tampilkan error');

    link(start, open);
    link(open, load);
    link(load, action);
    link(action, create, 'Tambah');
    link(action, edit, 'Edit');
    link(action, del, 'Hapus');
    link(create, validate);
    link(edit, validate);
    link(validate, save, 'Valid');
    link(validate, error, 'Tidak valid');
    link(error, action);
    link(del, confirm);
    link(confirm, save, 'Ya');
    link(confirm, action, 'Tidak');
    link(save, refresh);
    link(refresh, end);
  } else if (route === '/fingerprint') {
    const open = nid('open');
    const upload = nid('upload');
    const preview = nid('preview');
    const dupe = nid('dupe');
    const confirmSave = nid('confirmSave');
    const saved = nid('saved');
    const list = nid('list');
    const clear = nid('clear');
    const clearConfirm = nid('clearConfirm');

    nodeProc(open, 'Buka halaman Fingerprint');
    nodeData(upload, 'Upload CSV scanlog');
    nodeProc(preview, 'Preview + cek duplikat');
    nodeDecision(dupe, 'Ada duplikat?');
    nodeDecision(confirmSave, 'Konfirmasi simpan?');
    nodeProc(saved, 'Simpan fingerprints + update/create employee');
    nodeData(list, 'Lihat data + cari + pagination');
    nodeProc(clear, 'Clear semua data');
    nodeDecision(clearConfirm, 'Konfirmasi clear?');

    link(start, open);
    link(open, upload);
    link(upload, preview);
    link(preview, dupe);
    link(dupe, confirmSave, 'Lanjut');
    link(confirmSave, saved, 'Ya');
    link(confirmSave, list, 'Tidak');
    link(saved, list);
    link(list, clear, 'Opsional');
    link(clear, clearConfirm);
    link(clearConfirm, list, 'Tidak');
    link(clearConfirm, end, 'Ya');
  } else if (route === '/attendance-log') {
    const open = nid('open');
    const filter = nid('filter');
    const scope = nid('scope');
    const load = nid('load');
    const rule = nid('rule');
    const list = nid('list');
    const exportD = nid('export');

    nodeProc(open, 'Buka Attendance Log');
    nodeData(filter, 'Pilih filter: month/year/status/q/per_page');
    nodeDecision(scope, 'Month & Year dipilih?');
    nodeData(load, 'Ambil data fingerprints + roster + izin + libur');
    nodeDecision(rule, 'Karyawan Non Active?');
    nodeProc(list, 'Tampilkan rows + pagination');
    nodeProc(exportD, 'Export (opsional)');

    link(start, open);
    link(open, filter);
    link(filter, scope);
    link(scope, load, 'Ya');
    link(scope, load, 'Tidak');
    link(load, rule);
    link(rule, list, 'Active');
    link(rule, list, 'Non Active & ada scan bulan itu');
    link(rule, end, 'Non Active & tidak ada scan');
    link(list, exportD, 'Export?');
    link(exportD, end);
    link(list, end);
  } else if (route === '/leave-permission') {
    const open = nid('open');
    const create = nid('create');
    const submit = nid('submit');
    const approve = nid('approve');
    const reject = nid('reject');
    const decision = nid('decision');
    const list = nid('list');

    nodeProc(open, 'Buka Leave & Permission');
    nodeProc(create, 'Buat permintaan');
    nodeProc(submit, 'Submit');
    nodeDecision(decision, 'Approve?');
    nodeProc(approve, 'Set status approved');
    nodeProc(reject, 'Set status rejected + notes');
    nodeData(list, 'List permintaan + filter + pagination');

    link(start, open);
    link(open, list);
    link(list, create, 'Tambah');
    link(create, submit);
    link(submit, decision);
    link(decision, approve, 'Ya');
    link(decision, reject, 'Tidak');
    link(approve, list);
    link(reject, list);
    link(list, end);
  } else if (route === '/overtime') {
    const open = nid('open');
    const create = nid('create');
    const submit = nid('submit');
    const decision = nid('decision');
    const approve = nid('approve');
    const reject = nid('reject');
    const list = nid('list');

    nodeProc(open, 'Buka Overtime');
    nodeProc(create, 'Buat pengajuan lembur');
    nodeProc(submit, 'Submit pengajuan');
    nodeDecision(decision, 'Manager/Admin approve?');
    nodeProc(approve, 'Approve');
    nodeProc(reject, 'Reject + notes');
    nodeData(list, 'List + filter + pagination');

    link(start, open);
    link(open, list);
    link(list, create, 'Tambah');
    link(create, submit);
    link(submit, decision);
    link(decision, approve, 'Ya');
    link(decision, reject, 'Tidak');
    link(approve, list);
    link(reject, list);
    link(list, end);
  } else if (route === '/roster/upload') {
    const open = nid('open');
    const upload = nid('upload');
    const validate = nid('validate');
    const submit = nid('submit');
    const approve = nid('approve');
    const list = nid('list');

    nodeProc(open, 'Buka Roster Upload');
    nodeData(upload, 'Upload file roster');
    nodeProc(validate, 'Validasi format + preview');
    nodeProc(submit, 'Submit batch');
    nodeDecision(approve, 'Admin approve batch?');
    nodeData(list, 'Batch tersimpan (lihat di list)');

    link(start, open);
    link(open, upload);
    link(upload, validate);
    link(validate, submit);
    link(submit, approve);
    link(approve, list, 'Ya');
    link(approve, list, 'Tidak');
    link(list, end);
  } else if (route === '/roster/list') {
    const open = nid('open');
    const filter = nid('filter');
    const list = nid('list');
    const setCurrent = nid('setCurrent');

    nodeProc(open, 'Buka Roster List');
    nodeData(filter, 'Filter batch/status');
    nodeData(list, 'Lihat batch + detail');
    nodeDecision(setCurrent, 'Set sebagai current?');

    link(start, open);
    link(open, filter);
    link(filter, list);
    link(list, setCurrent);
    link(setCurrent, end, 'Ya');
    link(setCurrent, list, 'Tidak');
  } else if (route === '/gmium/plugging') {
    const open = nid('open');
    const create = nid('create');
    const submit = nid('submit');
    const list = nid('list');
    const del = nid('del');
    const confirm = nid('confirm');

    nodeProc(open, 'Buka Plugging');
    nodeProc(create, 'Create Plugging');
    nodeProc(submit, 'Submit');
    nodeData(list, 'List + filter + pagination');
    nodeProc(del, 'Delete data');
    nodeDecision(confirm, 'Konfirmasi?');

    link(start, open);
    link(open, list);
    link(list, create, 'Tambah');
    link(create, submit);
    link(submit, list);
    link(list, del, 'Hapus');
    link(del, confirm);
    link(confirm, list, 'Tidak');
    link(confirm, end, 'Ya');
  } else if (route === '/gmium/plugging/approval') {
    const open = nid('open');
    const list = nid('list');
    const select = nid('select');
    const approve = nid('approve');
    const done = nid('done');

    nodeProc(open, 'Buka Plugging Approval');
    nodeData(list, 'List pending + filter + pagination');
    nodeProc(select, 'Pilih record');
    nodeDecision(approve, 'Approve?');
    nodeProc(done, 'Set status approved + simpan tanda tangan');

    link(start, open);
    link(open, list);
    link(list, select);
    link(select, approve);
    link(approve, done, 'Ya');
    link(approve, list, 'Tidak');
    link(done, end);
  } else if (route === '/control-panel/user') {
    const open = nid('open');
    const list = nid('list');
    const action = nid('action');
    const create = nid('create');
    const edit = nid('edit');
    const del = nid('del');
    const confirm = nid('confirm');

    nodeProc(open, 'Buka Control Users');
    nodeData(list, 'List + filter + pagination');
    nodeDecision(action, 'Pilih aksi?');
    nodeProc(create, 'Tambah user');
    nodeProc(edit, 'Edit user');
    nodeProc(del, 'Delete user');
    nodeDecision(confirm, 'Konfirmasi delete?');

    link(start, open);
    link(open, list);
    link(list, action);
    link(action, create, 'Tambah');
    link(action, edit, 'Edit');
    link(action, del, 'Delete');
    link(del, confirm);
    link(confirm, end, 'Ya');
    link(confirm, action, 'Tidak');
    link(create, end);
    link(edit, end);
  } else if (route === '/control-panel/module-control') {
    const open = nid('open');
    const selectUser = nid('selectUser');
    const fetchPerms = nid('fetchPerms');
    const toggle = nid('toggle');
    const save = nid('save');
    const done = nid('done');

    nodeProc(open, 'Buka Modul Control');
    nodeProc(selectUser, 'Pilih user');
    nodeData(fetchPerms, 'Load permission user');
    nodeProc(toggle, 'Toggle checklist modul');
    nodeDecision(save, 'Simpan perubahan?');
    nodeProc(done, 'Simpan ke database');

    link(start, open);
    link(open, selectUser);
    link(selectUser, fetchPerms);
    link(fetchPerms, toggle);
    link(toggle, save);
    link(save, done, 'Ya');
    link(save, toggle, 'Tidak');
    link(done, end);
  } else if (route === '/control-panel/logs') {
    const open = nid('open');
    const filter = nid('filter');
    const list = nid('list');
    const view = nid('view');

    nodeProc(open, 'Buka Logs');
    nodeData(filter, 'Filter search/table/action/user');
    nodeData(list, 'List + pagination');
    nodeProc(view, 'View detail (modal)');

    link(start, open);
    link(open, filter);
    link(filter, list);
    link(list, view);
    link(view, end);
  } else {
    // Generic flow for pages that are mostly listing/reporting.
    const open = nid('open');
    const filter = nid('filter');
    const load = nid('load');
    const action = nid('action');
    const exportD = nid('export');

    nodeProc(open, `Buka halaman ${leaf.title}`);
    nodeData(filter, 'Set filter (opsional)');
    nodeData(load, 'Load data');
    nodeDecision(action, 'Aksi lanjutan?');
    nodeProc(exportD, 'Export/Download (opsional)');

    link(start, open);
    link(open, filter);
    link(filter, load);
    link(load, action);
    link(action, exportD, 'Export');
    link(action, end, 'Selesai');
    link(exportD, end);
  }

  return { title, mermaid: lines.join('\n') };
}

function buildHtml(diagrams) {
  const generatedAt = new Date().toLocaleString('id-ID');
  const sections = diagrams
    .map(({ title, mermaid }) => {
      return `
      <section class="page">
        <h2>${esc(title)}</h2>
<pre class="mermaid">
${mermaid}
</pre>
      </section>`;
    })
    .join('\n');

  const html = `<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Process Flowchart Semua Modul</title>
    <style>
      @page { size: A4 landscape; margin: 12mm; }
      body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, "Noto Sans", "Helvetica Neue", sans-serif; color: #0f172a; }
      h1 { margin: 0 0 8px; font-size: 20px; }
      .meta { color: #475569; font-size: 12px; margin-bottom: 14px; }
      .page { page-break-after: always; }
      .page:last-child { page-break-after: auto; }
      h2 { margin: 0 0 10px; font-size: 14px; color: #0f172a; }
      .mermaid { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
    <script>
      mermaid.initialize({
        startOnLoad: true,
        theme: 'base',
        flowchart: { htmlLabels: false, curve: 'basis' },
        themeVariables: {
          fontSize: '12px',
          fontFamily: 'ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial',
        }
      });
    </script>
  </head>
  <body>
    <h1>Process Flowchart Semua Modul</h1>
    <div class="meta">Generated: ${generatedAt} • Source: resources/js/Config/sidebarMenu.js</div>
    ${sections}
  </body>
</html>`;

  // Normalize to LF to avoid parser edge-cases with CRLF in some Mermaid builds.
  return html.replaceAll('\r\n', '\n');
}

async function main() {
  const leaves = flattenLeaves(sidebarMenuConfig);
  const diagrams = leaves.map(flowForLeaf);

  const outDir = resolve(__dirname, '..', 'docs', 'flowcharts');
  await mkdir(outDir, { recursive: true });

  const htmlPath = resolve(outDir, 'modules-process-flowcharts.html');
  await writeFile(htmlPath, buildHtml(diagrams), 'utf8');
  process.stdout.write(`${htmlPath}\n`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
