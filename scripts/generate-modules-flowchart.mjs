import { writeFile, mkdir } from 'node:fs/promises';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';
import { sidebarMenuConfig } from '../resources/js/Config/sidebarMenu.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);

function escapeMermaidLabel(value) {
  return String(value ?? '')
    .replaceAll('[', '(')
    .replaceAll(']', ')')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', "'");
}

function nodeId(parts) {
  const raw = parts.filter(Boolean).join('__').replaceAll(/[^a-zA-Z0-9_]/g, '_');
  return `n_${raw}`.slice(0, 120);
}

function buildMermaidForGroup(group) {
  const lines = [];
  lines.push('flowchart TB');
  lines.push('  classDef group fill:#0f172a,stroke:#334155,color:#e2e8f0;');
  lines.push('  classDef page fill:#111827,stroke:#475569,color:#e2e8f0;');
  lines.push('  classDef leaf fill:#0b1220,stroke:#334155,color:#cbd5e1;');

  const groupNode = nodeId([group.id || group.key || group.label]);
  lines.push(`  ${groupNode}["${escapeMermaidLabel(group.label)}"]:::group`);

  const addChildren = (parentNode, children, pathParts) => {
    for (const child of children || []) {
      const childKey = child.id || child.key || child.label;
      const childNode = nodeId([...pathParts, childKey]);

      const hasRoute = typeof child.route === 'string' && child.route.trim() !== '';
      const hasChildren = Array.isArray(child.children) && child.children.length > 0;

      const routeText = hasRoute ? `<br/><small>${escapeMermaidLabel(child.route)}</small>` : '';
      const keyText = child.module_key ? `<br/><small>${escapeMermaidLabel(child.module_key)}</small>` : '';
      const label = `${escapeMermaidLabel(child.label)}${routeText}${keyText}`;

      const cls = hasChildren ? 'page' : (hasRoute ? 'leaf' : 'page');
      lines.push(`  ${childNode}["${label}"]:::${cls}`);
      lines.push(`  ${parentNode} --> ${childNode}`);

      if (hasChildren) {
        addChildren(childNode, child.children, [...pathParts, childKey]);
      }
    }
  };

  addChildren(groupNode, group.children || [], [group.id || group.key || group.label]);
  return lines.join('\n');
}

function buildHtml(diagrams) {
  const now = new Date();
  const generatedAt = now.toLocaleString('id-ID');

  const sectionsHtml = diagrams
    .map(({ title, mermaid }) => {
      return `
      <section class="page">
        <h2>${title}</h2>
        <div class="mermaid">
${mermaid}
        </div>
      </section>`;
    })
    .join('\n');

  return `<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Flowchart Modul</title>
    <style>
      @page { size: A4 landscape; margin: 12mm; }
      body { font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial, "Noto Sans", "Helvetica Neue", sans-serif; color: #0f172a; }
      h1 { margin: 0 0 8px; font-size: 22px; }
      .meta { color: #475569; font-size: 12px; margin-bottom: 14px; }
      .page { page-break-after: always; }
      .page:last-child { page-break-after: auto; }
      h2 { margin: 0 0 10px; font-size: 16px; color: #0f172a; }
      .mermaid { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px; }
      .note { font-size: 11px; color: #64748b; margin-top: 6px; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js"></script>
    <script>
      mermaid.initialize({
        startOnLoad: true,
        theme: 'base',
        flowchart: { htmlLabels: true, curve: 'basis' },
        themeVariables: {
          fontSize: '12px',
          fontFamily: 'ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Arial',
        }
      });
    </script>
  </head>
  <body>
    <h1>Flowchart Semua Modul</h1>
    <div class="meta">Generated: ${generatedAt} • Source: resources/js/Config/sidebarMenu.js</div>
    ${sectionsHtml}
    <div class="note">Catatan: Diagram ini menggambarkan struktur modul/menu dan rute utama (bukan alur proses internal).</div>
  </body>
</html>`;
}

async function main() {
  const diagrams = sidebarMenuConfig.map((group) => ({
    title: group.label,
    mermaid: buildMermaidForGroup(group),
  }));

  const outDir = resolve(__dirname, '..', 'docs', 'flowcharts');
  await mkdir(outDir, { recursive: true });

  const htmlPath = resolve(outDir, 'modules-flowchart.html');
  await writeFile(htmlPath, buildHtml(diagrams), 'utf8');

  process.stdout.write(`${htmlPath}\n`);
}

main().catch((err) => {
  console.error(err);
  process.exit(1);
});
