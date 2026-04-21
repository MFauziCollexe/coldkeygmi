<template>
  <div class="fixed bottom-5 right-5 z-40">
    <transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="translate-y-2 opacity-0"
      enter-to-class="translate-y-0 opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="translate-y-0 opacity-100"
      leave-to-class="translate-y-2 opacity-0"
    >
      <div
        v-if="open"
        class="mb-3 flex h-[min(72vh,680px)] w-[min(92vw,400px)] flex-col overflow-hidden rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl"
      >
        <div class="border-b border-slate-700 bg-slate-950/80 px-4 py-3">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="text-sm font-semibold text-white">AI Help Assistant</h3>
            </div>
            <div class="flex items-center gap-2">
              <button
                type="button"
                class="rounded-lg bg-slate-800 px-2.5 py-1.5 text-xs text-slate-300 transition hover:bg-slate-700 hover:text-white"
                @click="resetChat"
              >
                Reset
              </button>
              <button
                type="button"
                class="rounded-lg bg-slate-800 px-2.5 py-1.5 text-xs text-slate-300 transition hover:bg-slate-700 hover:text-white"
                @click="open = false"
              >
                Close
              </button>
            </div>
          </div>
        </div>

        <transition-group
          ref="messagesContainer"
          tag="div"
          class="flex-1 space-y-3 overflow-y-auto px-4 py-4 scroll-smooth"
          enter-active-class="transition-all duration-300 ease-out"
          enter-from-class="translate-y-2 opacity-0"
          enter-to-class="translate-y-0 opacity-100"
          leave-active-class="transition-all duration-200 ease-in"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-for="message in messages"
            :key="message.id"
            :class="message.role === 'user' ? 'flex justify-end' : 'flex justify-start'"
          >
            <div
              :class="message.role === 'user'
                ? 'max-w-[85%] rounded-2xl rounded-br-md bg-sky-600 px-3 py-2 text-sm text-white shadow-lg shadow-sky-950/25 transition-all duration-300'
                : 'max-w-[88%] rounded-2xl rounded-bl-md border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-100 shadow-lg shadow-slate-950/20 transition-all duration-300'"
            >
              <template v-if="message.role === 'assistant'">
                <div class="space-y-3">
                  <div
                    v-for="(block, blockIndex) in formatAssistantMessage(message.text)"
                    :key="`${message.id}-block-${blockIndex}`"
                  >
                    <p v-if="block.type === 'paragraph'" class="leading-6 text-slate-100">
                      {{ block.content }}
                    </p>
                    <ul v-else-if="block.type === 'list'" class="space-y-2">
                      <li
                        v-for="(item, itemIndex) in block.items"
                        :key="`${message.id}-item-${itemIndex}`"
                        class="flex items-start gap-2 leading-6 text-slate-100"
                      >
                        <span class="mt-2 h-1.5 w-1.5 rounded-full bg-sky-400"></span>
                        <span class="flex-1">{{ item }}</span>
                      </li>
                    </ul>
                    <ol v-else-if="block.type === 'ordered-list'" class="space-y-2">
                      <li
                        v-for="(item, itemIndex) in block.items"
                        :key="`${message.id}-ordered-item-${itemIndex}`"
                        class="flex items-start gap-3 leading-6 text-slate-100"
                      >
                        <span class="min-w-[1.5rem] text-sky-300">{{ itemIndex + 1 }}.</span>
                        <span class="flex-1">{{ item }}</span>
                      </li>
                    </ol>
                  </div>
                </div>
              </template>
              <div v-else class="whitespace-pre-line leading-6">{{ message.text }}</div>

              <div
                v-if="message.role === 'assistant' && Array.isArray(message.suggestions) && message.suggestions.length"
                class="mt-3 flex flex-wrap gap-2"
              >
                <button
                  v-for="suggestion in message.suggestions"
                  :key="suggestion"
                  type="button"
                  class="rounded-full border border-slate-600 bg-slate-900 px-3 py-1 text-xs text-slate-200 transition hover:border-sky-500 hover:text-white"
                  @click="askSuggestion(suggestion)"
                >
                  {{ suggestion }}
                </button>
              </div>
            </div>
          </div>

          <div v-if="isTyping" key="typing-indicator" class="flex justify-start">
            <div class="rounded-2xl rounded-bl-md border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-slate-200">
              <div class="flex items-center gap-1.5">
                <span class="h-2 w-2 animate-bounce rounded-full bg-sky-400 [animation-delay:-0.2s]"></span>
                <span class="h-2 w-2 animate-bounce rounded-full bg-sky-400 [animation-delay:-0.1s]"></span>
                <span class="h-2 w-2 animate-bounce rounded-full bg-sky-400"></span>
              </div>
            </div>
          </div>
        </transition-group>

        <div class="border-t border-slate-700 bg-slate-950/60 px-4 py-3">
          <form class="flex items-end gap-2" @submit.prevent="submitQuestion">
            <textarea
              v-model="draft"
              rows="2"
              class="min-h-[44px] flex-1 resize-none rounded-xl border border-slate-700 bg-slate-800 px-3 py-2 text-sm text-white outline-none transition placeholder:text-slate-500 focus:border-sky-500"
              placeholder="Tulis pesan lalu tekan Enter. Shift+Enter untuk baris baru."
              @keydown="handleComposerKeydown"
            ></textarea>
            <button
              type="submit"
              class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-500 disabled:cursor-not-allowed disabled:bg-slate-700 disabled:text-slate-400"
              :disabled="!draft.trim() || isTyping"
            >
              Kirim
            </button>
          </form>
        </div>
      </div>
    </transition>

    <button
      type="button"
      class="group flex items-center gap-3 rounded-full border border-sky-500/40 bg-sky-600 px-4 py-3 text-white shadow-xl transition hover:bg-sky-500"
      @click="toggleOpen"
    >
      <span class="flex h-9 w-9 items-center justify-center rounded-full bg-sky-500/30">
        <svg viewBox="0 0 24 24" class="h-5 w-5 fill-current" aria-hidden="true">
          <path d="M12 3C6.477 3 2 6.91 2 11.733c0 2.613 1.333 4.958 3.452 6.567L5 22l3.67-1.994c1.046.285 2.16.438 3.33.438 5.523 0 10-3.91 10-8.711S17.523 3 12 3zm-4 10.2a1.2 1.2 0 110-2.4 1.2 1.2 0 010 2.4zm4 0a1.2 1.2 0 110-2.4 1.2 1.2 0 010 2.4zm4 0a1.2 1.2 0 110-2.4 1.2 1.2 0 010 2.4z"/>
        </svg>
      </span>
      <span class="hidden text-left sm:block">
        <span class="block text-sm font-semibold">Assistant Help</span>
      </span>
    </button>
  </div>
</template>

<script setup>
import axios from 'axios';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const STORAGE_KEY_PREFIX = 'gmi-help-assistant-chat-v3';

const moduleProfiles = [
  {
    key: 'attendance',
    label: 'Attendance',
    matches: ['gmihr/attendancelog', 'gmihr/absensi', 'gmihr/fingerprint', 'gmihr/thedays', 'gmihr/attendanceapproval', 'attendance-log', 'fingerprint', 'absensi', 'the-days'],
    suggestions: ['Kenapa attendance saya OFF?', 'Cara baca status attendance log'],
  },
  {
    key: 'leave_permission',
    label: 'Leave & Permission',
    matches: ['gmihr/leavepermission', 'leave-permission'],
    suggestions: ['Cara buat leave permission', 'Siapa yang bisa approve leave permission?', 'Kenapa pengajuan saya belum approved?'],
  },
  {
    key: 'overtime',
    label: 'Overtime',
    matches: ['gmihr/overtime', 'overtime'],
    suggestions: ['Cara buat overtime', 'Siapa yang bisa approve overtime?', 'Kenapa overtime saya tidak muncul?'],
  },
  {
    key: 'roster',
    label: 'Roster',
    matches: ['gmihr/roster', '/roster'],
    suggestions: ['Cara upload roster dari awal', 'Apa bedanya pending dan current roster?', 'Cara approve roster'],
  },
  {
    key: 'ticket',
    label: 'Ticket',
    matches: ['gmisl/utility/tickets', '/tickets'],
    suggestions: ['Cara membuat ticket', 'Kenapa ticket tidak bisa di-resolve?', 'Siapa yang bisa close ticket?'],
  },
  {
    key: 'checklist',
    label: 'Checklist',
    matches: ['gmiic/checklist', 'checklist'],
    suggestions: ['Cara isi checklist', 'Bagaimana scan QRCode checklist?', 'Siapa yang bisa approve checklist?'],
  },
  {
    key: 'request_access',
    label: 'Request Access',
    matches: ['gmisl/utility/requestaccess', 'request-access'],
    suggestions: ['Cara buat request access', 'Siapa yang bisa approve request access?', 'Kenapa request access saya ditolak?'],
  },
  {
    key: 'check_inline',
    label: 'Check Inline',
    matches: ['gmisl/utility/checkinline', 'check-inline'],
    suggestions: ['Cara buat check inline', 'Siapa yang bisa update check inline?', 'Bagaimana lihat detail check inline?'],
  },
  {
    key: 'berita_acara',
    label: 'Berita Acara',
    matches: ['gmisl/utility/beritaacara', 'berita-acara'],
    suggestions: ['Cara membuat berita acara', 'Bagaimana print berita acara?', 'Siapa yang bisa hapus berita acara?'],
  },
  {
    key: 'date_code',
    label: 'Date Code',
    matches: ['gmisl/utility/datecode', 'date-code'],
    suggestions: ['Cara pakai date code', 'Bagaimana membaca hasil date code?', 'Apa fungsi modul date code?'],
  },
  {
    key: 'stock_card',
    label: 'Stock Card',
    matches: ['gmisl/utility/stockcard', 'masterdata/stockcard', 'stock-card'],
    suggestions: ['Cara stock in di stock card', 'Cara request item stock card', 'Siapa yang bisa approve request stock card?'],
  },
  {
    key: 'plugging',
    label: 'Plugging',
    matches: ['gmium/plugging', 'plugging'],
    suggestions: ['Cara buat plugging', 'Bagaimana approval plugging?', 'Kenapa plugging saya belum approved?'],
  },
  {
    key: 'electricity',
    label: 'Electricity Meter',
    matches: ['gmium/resourcemonitoring/electricity', 'standard-meter', 'hv-meter'],
    suggestions: ['Cara input meter listrik', 'Bagaimana edit log meter listrik?', 'Bagaimana export data meter listrik?'],
  },
  {
    key: 'water_meter',
    label: 'Water Meter',
    matches: ['gmium/resourcemonitoring/watermeter', 'water-meter'],
    suggestions: ['Cara input water meter', 'Bagaimana edit log water meter?', 'Bagaimana export water meter?'],
  },
  {
    key: 'utility_report',
    label: 'Utility Report',
    matches: ['gmium/utilityreport', 'utility-report'],
    suggestions: ['Apa fungsi utility report?', 'Bagaimana membaca utility report?', 'Data utility report berasal dari mana?'],
  },
  {
    key: 'visitor_form',
    label: 'Visitor Form',
    matches: ['gmivp/visitorform', 'visitor-form'],
    suggestions: ['Cara buat visitor form', 'Siapa yang bisa approve visitor form?', 'Kenapa visitor form saya belum approved?'],
  },
  {
    key: 'exit_permit',
    label: 'Exit Permit',
    matches: ['gmivp/exitpermit', 'exit-permit'],
    suggestions: ['Cara buat exit permit', 'Siapa yang bisa approve exit permit?', 'Bagaimana update status exit permit?'],
  },
  {
    key: 'master_data',
    label: 'Master Data',
    matches: ['masterdata/', 'master-data/', 'masterdata'],
    suggestions: ['Cara tambah master data', 'Bagaimana edit master data?', 'Siapa yang bisa mengelola master data?'],
  },
  {
    key: 'control_panel',
    label: 'Control Panel',
    matches: ['controlpanel/', 'control-panel/', 'controlpanel'],
    suggestions: ['Apa fungsi control panel?', 'Siapa yang bisa mengubah module control?', 'Bagaimana melihat log aktivitas?'],
  },
  {
    key: 'dashboard',
    label: 'Dashboard',
    matches: ['dashboard'],
    suggestions: ['Apa fungsi dashboard?', 'Data dashboard ini dari mana?', 'Kenapa data dashboard berbeda?'],
  },
];

const pageProfiles = [
  {
    key: 'attendance_log',
    matches: ['gmihr/attendancelog/index', 'attendance-log'],
    suggestions: ['Cara baca status attendance log', 'Kenapa status attendance bisa OFF?', 'Kenapa jam masuk dan pulang bisa kebalik?'],
  },
  {
    key: 'fingerprint_index',
    matches: ['gmihr/fingerprint/index', 'fingerprint'],
    suggestions: ['Cara import fingerprint', 'Bagaimana preview data fingerprint?', 'Kenapa data fingerprint belum masuk?'],
  },
  {
    key: 'roster_upload',
    matches: ['gmihr/roster/upload', 'roster/upload'],
    suggestions: ['Cara upload roster dari awal', 'Bagaimana preview roster?', 'Kenapa file roster gagal diproses?'],
  },
  {
    key: 'roster_list',
    matches: ['gmihr/roster/list', 'roster/list'],
    suggestions: ['Apa bedanya pending dan current roster?', 'Cara approve roster', 'Bagaimana filter roster per bulan?'],
  },
  {
    key: 'ticket_index',
    matches: ['gmisl/utility/tickets/index', '/tickets'],
    suggestions: ['Cara membuat ticket', 'Bagaimana filter ticket?', 'Apa arti status ticket?'],
  },
  {
    key: 'ticket_create',
    matches: ['gmisl/utility/tickets/create', '/tickets/create'],
    suggestions: ['Cara membuat ticket secara detail', 'Field apa yang harus diisi saat membuat ticket?', 'Siapa yang menerima ticket setelah dibuat?'],
  },
  {
    key: 'ticket_show',
    matches: ['gmisl/utility/tickets/show', '/tickets/'],
    suggestions: ['Kenapa ticket tidak bisa di-resolve?', 'Siapa yang bisa close ticket?', 'Apa yang bisa dilakukan di detail ticket ini?'],
  },
  {
    key: 'checklist_create',
    matches: ['gmiic/checklist/create', 'checklist/create'],
    suggestions: ['Cara isi checklist', 'Bagaimana scan QRCode checklist?', 'Kenapa QRCode tidak sesuai area?'],
  },
  {
    key: 'checklist_index',
    matches: ['gmiic/checklist/index', 'gmiic/checklist'],
    suggestions: ['Apa fungsi modul checklist?', 'Siapa yang bisa approve checklist?', 'Bagaimana melihat checklist yang sudah dibuat?'],
  },
  {
    key: 'leave_permission_create',
    matches: ['gmihr/leavepermission/create', 'leave-permission/create'],
    suggestions: ['Cara buat leave permission', 'Data apa saja yang perlu diisi?', 'Siapa yang bisa approve leave permission?'],
  },
  {
    key: 'leave_permission_index',
    matches: ['gmihr/leavepermission/index', 'leave-permission'],
    suggestions: ['Kenapa pengajuan saya belum approved?', 'Bagaimana lihat status leave permission?', 'Siapa yang bisa approve leave permission?'],
  },
  {
    key: 'overtime_create',
    matches: ['gmihr/overtime/create', 'overtime/create'],
    suggestions: ['Cara buat overtime', 'Data apa saja yang perlu diisi untuk overtime?', 'Siapa yang bisa approve overtime?'],
  },
  {
    key: 'visitor_form_create',
    matches: ['gmivp/visitorform/create', 'visitor-form/create'],
    suggestions: ['Cara buat visitor form', 'Data tamu apa saja yang perlu diisi?', 'Siapa yang bisa approve visitor form?'],
  },
  {
    key: 'request_access_create',
    matches: ['gmisl/utility/requestaccess/create', 'request-access/create'],
    suggestions: ['Cara buat request access', 'Jenis request access apa saja?', 'Siapa yang bisa approve request access?'],
  },
  {
    key: 'plugging_approval',
    matches: ['gmium/plugging/approval', 'plugging/approval'],
    suggestions: ['Bagaimana approval plugging?', 'Kenapa plugging belum approved?', 'Apa arti status plugging?'],
  },
  {
    key: 'control_panel_logs',
    matches: ['controlpanel/logs', 'control-panel/logs'],
    suggestions: ['Bagaimana melihat log aktivitas?', 'Apa arti data di log?', 'Siapa yang bisa menghapus log?'],
  },
];

const knowledgeTopics = [
  {
    id: 'attendance_overview',
    module: 'attendance',
    keywords: ['attendance', 'absensi', 'attendance log', 'fingerprint', 'scan', 'attendance dengan bahasa sederhana', 'cara pakai attendance'],
    summary: 'Attendance dipakai untuk membaca hasil scan fingerprint lalu membandingkannya dengan roster yang aktif.',
    detail: [
      'Biasanya alurnya seperti ini: data scan masuk dulu, roster periode terkait harus approved dan current, lalu Attendance Log menampilkan hasilnya.',
      'Dari sana user bisa lihat status seperti On Time, Terlambat, OFF, atau kondisi scan yang tidak lengkap.',
      'Kalau hasilnya terasa aneh, titik cek pertama biasanya roster aktif dan scan mentah pada tanggal terkait.',
    ],
    suggestions: ['Cara baca status attendance log', 'Kenapa attendance saya OFF?'],
  },
  {
    id: 'attendance_status',
    module: 'attendance',
    keywords: ['baca status', 'status attendance', 'on time', 'terlambat', 'tidak scan', 'cara baca status attendance log'],
    summary: 'Status di Attendance Log adalah hasil perbandingan antara jadwal kerja dan scan aktual.',
    detail: [
      'On Time berarti scan masuk masih sesuai toleransi jadwal.',
      'Terlambat berarti scan masuk melewati batas yang dianggap tepat waktu.',
      'OFF biasanya berarti hari itu memang bukan jadwal kerja, atau sistem membaca hari itu sebagai hari libur/off berdasarkan roster atau aturan default.',
      'Kalau jam masuk dan pulang terasa kebalik, biasanya perlu cek scan lintas hari atau roster shift malam.',
    ],
    suggestions: ['Kenapa attendance saya OFF?'],
  },
  {
    id: 'attendance_off',
    module: 'attendance',
    keywords: ['off', 'kenapa off', 'status off', 'attendance saya off', 'tidak masuk'],
    summary: 'Status OFF biasanya bukan error tampilan, tapi hasil pembacaan jadwal atau aturan hari tersebut.',
    detail: [
      'Penyebab paling umum adalah hari itu memang OFF di roster.',
      'Kalau tidak ada roster, sistem bisa memakai aturan default tertentu tergantung jenis pegawai atau PIN.',
      'Untuk shift malam, scan pagi hari berikutnya kadang terbaca sebagai checkout hari sebelumnya, jadi perlu dilihat lintas tanggal.',
      'Kalau kamu yakin seharusnya masuk kerja, cocokkan tanggal itu dengan roster aktif dan scan fingerprint mentahnya.',
    ],
    suggestions: ['Cara baca status attendance log'],
  },
  {
    id: 'attendance_team_review',
    module: 'attendance',
    keywords: ['attendance tim', 'attendance aneh', 'hasil attendance aneh', 'attendance bawahan'],
    summary: 'Kalau hasil attendance tim terasa aneh, pengecekan paling aman dimulai dari data jadwal dan scan asli.',
    detail: [
      'Cocokkan dulu roster aktif pada tanggal yang dipermasalahkan.',
      'Lalu cek scan fingerprint mentah untuk memastikan ada scan masuk, pulang, atau scan lintas hari.',
      'Sesudah itu baru lihat apakah status seperti OFF, Terlambat, atau jam kebalik memang sesuai logika shift yang terbaca.',
    ],
    suggestions: ['Cara baca status attendance log', 'Kenapa attendance saya OFF?'],
  },
  {
    id: 'roster_overview',
    module: 'roster',
    keywords: ['roster', 'cara pakai roster', 'upload roster', 'roster dengan bahasa sederhana'],
    summary: 'Roster adalah sumber jadwal kerja yang nanti dipakai Attendance untuk membaca status kehadiran.',
    detail: [
      'Alur umumnya: download template, isi shift, preview untuk validasi, upload, lalu tunggu approval.',
      'Setelah batch roster approved dan menjadi current, barulah roster itu dipakai sistem.',
      'Kalau roster belum current, Attendance bisa tetap membaca batch lama atau aturan default.',
    ],
    suggestions: ['Cara upload roster dari awal', 'Apa bedanya pending dan current roster?', 'Cara approve roster'],
  },
  {
    id: 'roster_upload',
    module: 'roster',
    keywords: ['upload roster', 'cara upload roster', 'template roster', 'preview roster', 'upload roster dari awal'],
    summary: 'Upload roster sebaiknya dilakukan bertahap supaya error cepat ketahuan sebelum dipakai Attendance.',
    detail: [
      'Mulai dari download template roster yang sesuai periode atau departemen.',
      'Isi data shift di file itu, lalu lakukan preview untuk mengecek format dan isi.',
      'Kalau preview aman, baru lanjut upload sebagai batch roster.',
      'Sesudah itu manager atau approver terkait tinggal approve batch tersebut agar bisa aktif.',
    ],
    suggestions: ['Apa bedanya pending dan current roster?', 'Cara approve roster'],
  },
  {
    id: 'roster_status',
    module: 'roster',
    keywords: ['pending', 'current', 'approved', 'pending manager', 'beda pending dan current', 'apa bedanya pending dan current roster'],
    summary: 'Pending, approved, dan current itu bukan hal yang sama.',
    detail: [
      'Pending Manager artinya batch roster masih menunggu persetujuan.',
      'Approved artinya batch itu sudah lolos approval.',
      'Current artinya itulah batch approved yang sedang dipakai aktif oleh sistem untuk periode tersebut.',
      'Jadi batch bisa saja approved, tapi bukan current kalau ada versi lain yang lebih baru dan aktif.',
    ],
    suggestions: ['Cara approve roster', 'Cara upload roster dari awal'],
  },
  {
    id: 'roster_approve',
    module: 'roster',
    keywords: ['approve roster', 'reject roster', 'set current', 'cara approve roster'],
    summary: 'Approve roster biasanya dilakukan oleh role approver setelah isi batch dipastikan benar.',
    detail: [
      'Dari daftar roster, approver buka batch yang menunggu persetujuan lalu review isinya.',
      'Kalau sesuai, batch di-approve dan sistem akan menandainya sebagai approved.',
      'Untuk periode yang sama, batch approved terbaru yang dipilih aktif akan menjadi current.',
      'Kalau ada kesalahan isi, approver bisa reject agar uploader revisi file roster lebih dulu.',
    ],
    suggestions: ['Apa bedanya pending dan current roster?', 'Cara upload roster dari awal'],
  },
  {
    id: 'ticket_overview',
    module: 'ticket',
    keywords: ['ticket', 'tiket', 'cara pakai ticket', 'alur ticket', 'ticket dengan bahasa sederhana'],
    summary: 'Modul Ticket dipakai untuk mencatat permintaan kerja sampai selesai dan dikonfirmasi.',
    detail: [
      'Biasanya alurnya mulai dari requester membuat ticket.',
      'Setelah itu ticket ditangani assignee, status berubah ke In Progress saat pekerjaan mulai dikerjakan.',
      'Kalau pekerjaan selesai, assignee melakukan resolve dengan catatan hasil kerja.',
      'Terakhir requester atau creator meninjau hasilnya lalu close ticket jika sudah sesuai.',
    ],
    suggestions: ['Kenapa ticket tidak bisa di-resolve?', 'Siapa yang bisa close ticket?', 'Alur ticket dari open sampai close'],
  },
  {
    id: 'ticket_create',
    module: 'ticket',
    keywords: ['cara membuat ticket', 'membuat ticket', 'buat ticket', 'create ticket', 'cara buat tiket', 'cara membuat ticket secara detail'],
    summary: 'Untuk membuat ticket, fokusnya ada di pengisian informasi inti ticket dengan jelas sejak awal.',
    detail: [
      'Buka modul Ticket lalu masuk ke halaman create atau tombol tambah ticket.',
      'Pilih atau pastikan department yang sesuai dengan kebutuhan pekerjaan atau permintaan.',
      'Isi judul atau ringkasan masalah dengan singkat tapi jelas supaya mudah dipahami saat ticket dibaca.',
      'Isi deskripsi masalah atau kebutuhan secara lebih detail, termasuk kendala, lokasi, barang, atau konteks kejadian jika memang ada.',
      'Kalau form menyediakan deadline, priority, lampiran, atau foto pendukung, isi bagian itu sesuai kebutuhan supaya assignee lebih cepat memahami kasusnya.',
      'Sesudah semua data dirasa lengkap, simpan atau submit ticket.',
      'Setelah ticket dibuat, ticket biasanya masuk ke alur penanganan: ditinjau, didistribusikan ke assignee, lalu statusnya bergerak sampai resolve dan close.',
    ],
    suggestions: ['Alur ticket dari open sampai close', 'Kenapa ticket tidak bisa di-resolve?', 'Siapa yang bisa close ticket?'],
  },
  {
    id: 'ticket_resolve',
    module: 'ticket',
    keywords: ['resolve', 'resolve ticket', 'tidak bisa resolve', 'kenapa ticket tidak bisa di-resolve', 'resolution notes'],
    summary: 'Resolve ticket biasanya gagal bukan karena tombolnya rusak, tapi karena syarat backend belum terpenuhi.',
    detail: [
      'User yang klik resolve biasanya harus assignee ticket tersebut.',
      'Status ticket juga harus sudah In Progress, bukan masih Open atau status lain.',
      'Kalau ada pending deadline request, proses resolve bisa ikut tertahan.',
      'Resolution notes juga perlu diisi cukup jelas agar proses resolve diterima.',
    ],
    suggestions: ['Siapa yang bisa close ticket?', 'Alur ticket dari open sampai close'],
  },
  {
    id: 'ticket_close',
    module: 'ticket',
    keywords: ['close ticket', 'siapa close', 'creator', 'requester', 'siapa yang bisa close ticket'],
    summary: 'Resolve dan close itu dua tahap yang berbeda.',
    detail: [
      'Assignee menyelesaikan pekerjaan lalu menekan resolve.',
      'Setelah itu creator atau requester meninjau hasilnya.',
      'Kalau hasilnya sudah sesuai, creator/requester yang melakukan close ticket.',
      'Kalau belum sesuai, ticket bisa dikembalikan untuk dikerjakan lagi.',
    ],
    suggestions: ['Kenapa ticket tidak bisa di-resolve?', 'Alur ticket dari open sampai close'],
  },
  {
    id: 'ticket_distribute',
    module: 'ticket',
    keywords: ['distribute', 'bagikan ticket', 'assign ticket', 'cara distribute ticket'],
    summary: 'Distribusi ticket biasanya dilakukan oleh manager department yang berwenang mengatur penugasan.',
    detail: [
      'Sebelum distribute, pastikan ticket memang masuk ke department yang benar.',
      'User yang dipilih biasanya harus masih berada di department yang sama dengan ticket.',
      'Kalau tombol distribute tidak bisa dipakai, biasanya masalahnya ada di role user atau hak kelola department.',
    ],
    suggestions: ['Kenapa ticket tidak bisa di-resolve?', 'Siapa yang bisa close ticket?'],
  },
  {
    id: 'checklist_overview',
    module: 'checklist',
    keywords: ['checklist', 'cara isi checklist', 'scan qrcode checklist', 'template checklist'],
    summary: 'Checklist dipakai untuk mencatat hasil pemeriksaan berdasarkan template yang dipilih.',
    detail: [
      'Biasanya user memilih template dulu, lalu memilih area atau section yang relevan.',
      'Jika template memakai QRCode, area yang discan harus sesuai dengan dropdown atau area aktif.',
      'Sesudah semua pertanyaan diisi, checklist disimpan lalu masuk ke alur review atau approval sesuai template dan role.',
    ],
    suggestions: ['Bagaimana scan QRCode checklist?', 'Siapa yang bisa approve checklist?'],
  },
  {
    id: 'leave_permission_overview',
    module: 'leave_permission',
    keywords: ['leave permission', 'cuti', 'izin', 'cara buat leave permission'],
    summary: 'Leave & Permission dipakai untuk mengajukan cuti atau izin lalu mengikuti alur approval.',
    detail: [
      'User biasanya membuat pengajuan dengan memilih tipe pengajuan, tanggal, dan alasan.',
      'Sesudah disubmit, pengajuan akan masuk ke alur approval sesuai role dan department.',
      'Kalau status belum berubah, biasanya perlu dicek approver yang berwenang atau data pengajuan yang belum lengkap.',
    ],
    suggestions: ['Siapa yang bisa approve leave permission?', 'Kenapa pengajuan saya belum approved?'],
  },
  {
    id: 'overtime_overview',
    module: 'overtime',
    keywords: ['overtime', 'lembur', 'cara buat overtime'],
    summary: 'Overtime dipakai untuk mencatat dan mengajukan pekerjaan lembur sesuai kebutuhan kerja.',
    detail: [
      'Buka modul Overtime lalu masuk ke halaman Create atau tombol tambah lembur.',
      'Isi tanggal lembur sesuai hari pekerjaan lembur dilakukan atau diajukan.',
      'Isi jam mulai dan jam selesai lembur sesuai kebutuhan pada form.',
      'Isi alasan lembur dengan jelas supaya approver paham kenapa pekerjaan itu perlu dilakukan di luar jam normal.',
      'Isi detail pekerjaan atau aktivitas yang dikerjakan saat lembur.',
      'Kalau form menyediakan lampiran, tambahkan file pendukung bila memang diperlukan.',
      'Periksa lagi data yang sudah diisi, lalu submit atau simpan pengajuan lembur.',
      'Sesudah disubmit, overtime akan masuk ke proses review atau approval sesuai role yang berlaku.',
      'Kalau overtime tidak muncul atau belum approved, cek status pengajuan dan pihak approver terkait.',
    ],
    suggestions: ['Siapa yang bisa approve overtime?', 'Kenapa overtime saya tidak muncul?'],
  },
  {
    id: 'overtime_create',
    module: 'overtime',
    keywords: ['langkah membuat overtime', 'cara membuat overtime', 'buat overtime step by step', 'langkah2 overtime', 'membuat overtime'],
    summary: 'Kalau tujuannya membuat overtime, langkahnya sebaiknya diikuti berurutan agar pengajuan tidak tertolak karena data kurang lengkap.',
    detail: [
      'Masuk ke modul Overtime lalu buka halaman Create.',
      'Pilih tanggal lembur yang sesuai.',
      'Isi jam mulai dan jam selesai lembur.',
      'Isi alasan lembur secara singkat tapi jelas.',
      'Isi pekerjaan yang dilakukan atau rencana pekerjaan saat lembur.',
      'Upload lampiran jika form memang menyediakan dan jika ada dokumen pendukung.',
      'Cek kembali semua isi form sebelum disubmit.',
      'Klik simpan atau submit untuk mengirim pengajuan.',
      'Setelah itu buka daftar Overtime untuk melihat status pengajuan apakah masih pending, sudah approved, atau perlu tindak lanjut.',
    ],
    suggestions: ['Siapa yang bisa approve overtime?', 'Kenapa overtime saya tidak muncul?'],
  },
  {
    id: 'visitor_form_overview',
    module: 'visitor_form',
    keywords: ['visitor form', 'tamu', 'cara buat visitor form'],
    summary: 'Visitor Form dipakai untuk mencatat kunjungan tamu dan mengatur alur persetujuannya.',
    detail: [
      'Biasanya form diisi dengan data tamu, tujuan kunjungan, tanggal, dan pihak yang dikunjungi.',
      'Setelah disubmit, status form akan menunggu approval sesuai alur yang berlaku.',
      'Kalau belum approved, cek apakah data tamu, tujuan, atau approver yang dituju sudah benar.',
    ],
    suggestions: ['Siapa yang bisa approve visitor form?', 'Kenapa visitor form saya belum approved?'],
  },
  {
    id: 'exit_permit_overview',
    module: 'exit_permit',
    keywords: ['exit permit', 'cara buat exit permit', 'barang keluar'],
    summary: 'Exit Permit dipakai untuk pengajuan barang atau item yang akan keluar sesuai prosedur.',
    detail: [
      'User biasanya mengisi data barang, tujuan, alasan, dan informasi pendukung lainnya.',
      'Sesudah form dikirim, status akan mengikuti alur approval yang berlaku.',
      'Kalau update status tidak bisa, cek role user dan posisi dokumen di alur approval.',
    ],
    suggestions: ['Siapa yang bisa approve exit permit?', 'Bagaimana update status exit permit?'],
  },
  {
    id: 'stock_card_overview',
    module: 'stock_card',
    keywords: ['stock card', 'stok', 'cara stock in', 'request stock'],
    summary: 'Stock Card dipakai untuk pencatatan stok masuk, permintaan item, dan approval terkait stok.',
    detail: [
      'Di modul ini user bisa melihat pergerakan stok atau membuat request sesuai hak aksesnya.',
      'Untuk proses stock in atau request, data item dan jumlah harus jelas agar pencatatan tidak salah.',
      'Kalau request tidak bisa diproses, cek status request dan hak akses approval pada user yang sedang login.',
    ],
    suggestions: ['Cara stock in di stock card', 'Siapa yang bisa approve request stock card?'],
  },
  {
    id: 'plugging_overview',
    module: 'plugging',
    keywords: ['plugging', 'cara buat plugging', 'approval plugging'],
    summary: 'Plugging dipakai untuk pengajuan dan pemantauan aktivitas plugging beserta approval-nya.',
    detail: [
      'User biasanya membuat data plugging terlebih dahulu lalu mengirimkannya untuk approval.',
      'Status approval akan menentukan apakah plugging sudah bisa dilanjutkan atau masih menunggu.',
      'Kalau belum approved, cek approver, status, dan kelengkapan data yang diinput.',
    ],
    suggestions: ['Bagaimana approval plugging?', 'Kenapa plugging saya belum approved?'],
  },
  {
    id: 'request_access_overview',
    module: 'request_access',
    keywords: ['request access', 'cara buat request access', 'approval request access', 'akses user'],
    summary: 'Request Access dipakai untuk meminta akses modul bagi user existing atau new user.',
    detail: [
      'Di create request access, user memilih jenis request lalu mengisi target user atau data calon user baru.',
      'Setelah itu modul yang diminta dan alasan request harus diisi dengan jelas.',
      'Sesudah submit, request biasanya masuk ke alur review manager lalu diproses oleh IT jika sudah approved.',
    ],
    suggestions: ['Siapa yang bisa approve request access?', 'Kenapa request access saya ditolak?'],
  },
  {
    id: 'check_inline_overview',
    module: 'check_inline',
    keywords: ['check inline', 'cara buat check inline', 'detail check inline'],
    summary: 'Check Inline dipakai untuk mencatat data pemeriksaan inline beserta gambar pendukungnya.',
    detail: [
      'User bisa membuat data baru dengan mengisi informasi inti seperti customer, PO, batch, qty, tanggal, dan gambar bila ada.',
      'Data yang sudah dibuat bisa dilihat lagi pada daftar dan diperbarui dari halaman detail atau edit.',
      'Kalau gambar tidak muncul, biasanya perlu dicek file yang diupload atau data existing image yang dipertahankan saat update.',
    ],
    suggestions: ['Cara buat check inline', 'Bagaimana lihat detail check inline?'],
  },
  {
    id: 'berita_acara_overview',
    module: 'berita_acara',
    keywords: ['berita acara', 'cara membuat berita acara', 'print berita acara', 'pdf berita acara'],
    summary: 'Berita Acara dipakai untuk membuat dokumen kejadian lalu melihat, mencetak, atau mengunduh PDF-nya.',
    detail: [
      'Saat create, user biasanya mengisi tanggal kejadian, tempat, waktu, customer, department, dan kronologi.',
      'Sesudah disimpan, sistem menyiapkan data dokumen yang bisa dibuka di halaman detail.',
      'Dari sana user bisa print atau download PDF, dan user tertentu juga bisa menghapus dokumen tersebut.',
    ],
    suggestions: ['Bagaimana print berita acara?', 'Siapa yang bisa hapus berita acara?'],
  },
  {
    id: 'date_code_overview',
    module: 'date_code',
    keywords: ['date code', 'cara pakai date code', 'fungsi date code'],
    summary: 'Date Code dipakai untuk membantu membaca atau mengolah informasi terkait kode tanggal.',
    detail: [
      'Gunakan modul ini saat ingin memahami hasil date code atau melakukan pengecekan kode yang berhubungan dengan tanggal.',
      'Jika user bingung dengan hasilnya, arahkan ke input yang dipakai dan hasil pembacaan yang muncul pada layar.',
    ],
    suggestions: ['Bagaimana membaca hasil date code?', 'Apa fungsi modul date code?'],
  },
  {
    id: 'electricity_overview',
    module: 'electricity',
    keywords: ['meter listrik', 'electricity', 'standard meter', 'hv meter'],
    summary: 'Modul meter listrik dipakai untuk input, edit, dan export log pembacaan meter listrik.',
    detail: [
      'Ada halaman standard meter dan HV meter yang sama-sama fokus pada pencatatan angka pembacaan.',
      'User biasanya input log baru, memperbarui log yang sudah ada, atau mengunduh data export.',
      'Kalau angka terasa salah, titik cek pertama biasanya log yang dipilih dan nilai meter yang diinput.',
    ],
    suggestions: ['Cara input meter listrik', 'Bagaimana export data meter listrik?'],
  },
  {
    id: 'water_meter_overview',
    module: 'water_meter',
    keywords: ['water meter', 'meter air', 'cara input water meter'],
    summary: 'Water Meter dipakai untuk mencatat dan memperbarui data pembacaan meter air.',
    detail: [
      'User biasanya input pembacaan baru, edit data log, lalu melihat rekap atau export bila tersedia.',
      'Kalau data belum muncul, cek tanggal log, nilai input, dan apakah penyimpanan sudah berhasil.',
    ],
    suggestions: ['Cara input water meter', 'Bagaimana export water meter?'],
  },
  {
    id: 'utility_report_overview',
    module: 'utility_report',
    keywords: ['utility report', 'laporan utility', 'fungsi utility report'],
    summary: 'Utility Report dipakai untuk melihat rangkuman atau laporan data utility dalam satu tempat.',
    detail: [
      'Biasanya user membuka modul ini untuk membaca laporan hasil pencatatan utility dari modul terkait.',
      'Kalau angka berbeda dari yang diharapkan, cek rentang data, sumber log, dan waktu pembaruan laporan.',
    ],
    suggestions: ['Bagaimana membaca utility report?', 'Data utility report berasal dari mana?'],
  },
  {
    id: 'stock_card_detail',
    module: 'stock_card',
    keywords: ['cara request stock', 'approve request stock', 'stock in'],
    summary: 'Di Stock Card, alur utama biasanya stock in, request stock, lalu approval request bila role user mengizinkan.',
    detail: [
      'Stock in dipakai untuk menambah stok item yang ada.',
      'Request stock dipakai saat user ingin meminta item tertentu dengan jumlah dan catatan yang jelas.',
      'Jika request sudah pending, approver yang berwenang bisa meninjau lalu approve selama stok mencukupi.',
    ],
    suggestions: ['Cara stock in di stock card', 'Siapa yang bisa approve request stock card?'],
  },
  {
    id: 'visitor_form_detail',
    module: 'visitor_form',
    keywords: ['siapa yang bisa approve visitor form', 'visitor form approved', 'status visitor form'],
    summary: 'Visitor Form mengikuti alur submit lalu approval sesuai pihak yang berwenang.',
    detail: [
      'Sesudah form dibuat, status akan menunjukkan apakah form masih menunggu, sudah approved, atau perlu tindak lanjut.',
      'Jika approval belum bergerak, cek data tamu, tujuan kunjungan, dan approver yang berkaitan.',
    ],
    suggestions: ['Cara buat visitor form', 'Kenapa visitor form saya belum approved?'],
  },
  {
    id: 'exit_permit_detail',
    module: 'exit_permit',
    keywords: ['siapa yang bisa approve exit permit', 'status exit permit', 'update status exit permit'],
    summary: 'Exit Permit bergerak dari pengajuan lalu mengikuti status dan approval sesuai alur dokumen.',
    detail: [
      'Kalau status tidak berubah, biasanya ada langkah approval yang belum dilakukan atau role user belum sesuai.',
      'Pastikan data barang, tujuan, dan alasan keluar sudah diisi dengan lengkap saat membuat permit.',
    ],
    suggestions: ['Cara buat exit permit', 'Bagaimana update status exit permit?'],
  },
  {
    id: 'dashboard_overview',
    module: 'dashboard',
    keywords: ['dashboard', 'fungsi dashboard', 'data dashboard'],
    summary: 'Dashboard dipakai untuk melihat ringkasan data penting aplikasi dalam satu tampilan.',
    detail: [
      'Biasanya dashboard menampilkan indikator atau angka hasil rekap dari modul-modul terkait.',
      'Kalau data berbeda dari yang diharapkan, cek apakah ada filter, periode, atau pembaruan data yang memengaruhi tampilan.',
    ],
    suggestions: ['Apa fungsi dashboard?', 'Kenapa data dashboard berbeda?'],
  },
  {
    id: 'master_data_overview',
    module: 'master_data',
    keywords: ['master data', 'department', 'employee', 'position', 'customer', 'vehicle type'],
    summary: 'Master Data dipakai untuk mengelola data referensi yang dipakai modul-modul lain.',
    detail: [
      'Biasanya data seperti department, employee, position, customer, dan item referensi lain dikelola di sini.',
      'Perubahan master data bisa berdampak ke modul lain, jadi input dan edit harus dilakukan hati-hati.',
      'Kalau tombol create atau edit tidak tersedia, biasanya itu terkait permission user yang sedang login.',
    ],
    suggestions: ['Cara tambah master data', 'Siapa yang bisa mengelola master data?'],
  },
  {
    id: 'control_panel_overview',
    module: 'control_panel',
    keywords: ['control panel', 'module control', 'access rules', 'logs', 'database backup'],
    summary: 'Control Panel dipakai untuk pengaturan sistem seperti akses modul, access rules, logs, dan backup.',
    detail: [
      'Setiap halaman di control panel punya fungsi berbeda, jadi jawaban perlu menyesuaikan subhalamannya.',
      'Module Control biasanya terkait pengaturan akses modul per user.',
      'Access Rules, Logs, dan Database Backup biasanya hanya relevan untuk user dengan wewenang pengelolaan sistem.',
    ],
    suggestions: ['Siapa yang bisa mengubah module control?', 'Bagaimana melihat log aktivitas?'],
  },
];

const defaultTopicByModule = {
  attendance: 'attendance_overview',
  leave_permission: 'leave_permission_overview',
  overtime: 'overtime_overview',
  roster: 'roster_overview',
  ticket: 'ticket_overview',
  checklist: 'checklist_overview',
  request_access: 'request_access_overview',
  check_inline: 'check_inline_overview',
  berita_acara: 'berita_acara_overview',
  date_code: 'date_code_overview',
  stock_card: 'stock_card_overview',
  plugging: 'plugging_overview',
  electricity: 'electricity_overview',
  water_meter: 'water_meter_overview',
  utility_report: 'utility_report_overview',
  visitor_form: 'visitor_form_overview',
  exit_permit: 'exit_permit_overview',
  master_data: 'master_data_overview',
  control_panel: 'control_panel_overview',
  dashboard: 'dashboard_overview',
};

const moduleActionFallbacks = {
  attendance: [
    'Buka halaman Attendance yang relevan, misalnya Fingerprint, Attendance Log, Absensi, atau The Days.',
    'Kalau sedang import scan, lakukan preview dulu lalu confirm save agar data benar-benar masuk.',
    'Kalau sedang membaca attendance log, cek filter tanggal, pin, nama, department, lalu lihat status yang muncul.',
    'Bandingkan hasil attendance dengan roster aktif dan scan mentah jika ada hasil yang terasa aneh.',
    'Lanjutkan ke approval atau tindak lanjut lain hanya jika role user memang memiliki aksesnya.',
  ],
  leave_permission: [
    'Buka modul Leave & Permission lalu masuk ke halaman create jika ingin membuat pengajuan baru.',
    'Pilih tipe pengajuan, isi tanggal, alasan, dan lampiran bila diperlukan.',
    'Periksa lagi data pengajuan sebelum disubmit.',
    'Setelah submit, buka daftar pengajuan untuk melihat statusnya.',
    'Kalau belum diproses, cek approver terkait dan kelengkapan data pengajuan.',
  ],
  overtime: [
    'Buka modul Overtime lalu masuk ke halaman Create.',
    'Isi tanggal, jam mulai, jam selesai, alasan lembur, dan detail pekerjaan.',
    'Tambahkan lampiran jika form menyediakan dan memang diperlukan.',
    'Submit pengajuan lalu cek kembali di daftar Overtime.',
    'Pantau statusnya apakah masih pending, sudah approved, atau butuh tindak lanjut.',
  ],
  roster: [
    'Masuk ke halaman upload roster atau roster list sesuai kebutuhan.',
    'Kalau membuat batch baru, unduh template lalu isi shift sesuai periode.',
    'Preview file roster untuk memastikan format dan isinya valid.',
    'Upload batch roster lalu tunggu approval.',
    'Cek roster list untuk melihat status approved/current atau untuk approve/reject bila role mengizinkan.',
  ],
  ticket: [
    'Masuk ke modul Ticket lalu pilih apakah ingin melihat daftar ticket, membuat ticket baru, atau membuka detail ticket.',
    'Saat create, isi department, judul, deskripsi, dan data pendukung lain yang tersedia.',
    'Di detail ticket, gunakan aksi yang sesuai seperti comment, update status, resolve, close, atau distribute sesuai role.',
    'Perhatikan status ticket karena beberapa aksi hanya bisa dilakukan pada status tertentu.',
    'Setelah aksi dilakukan, cek kembali detail ticket untuk memastikan perubahan berhasil tersimpan.',
  ],
  checklist: [
    'Buka modul Checklist lalu pilih template atau halaman create jika ingin mengisi checklist baru.',
    'Pilih area atau section yang sesuai dengan template yang digunakan.',
    'Isi pertanyaan checklist satu per satu dan scan QRCode bila template mewajibkannya.',
    'Pastikan QRCode sesuai dengan area aktif sebelum menyimpan.',
    'Submit checklist lalu cek alur approval sesuai template dan role user.',
  ],
  request_access: [
    'Masuk ke Request Access lalu buka halaman create.',
    'Pilih jenis request untuk existing user atau new user.',
    'Isi target user atau data user baru, pilih modul yang diminta, lalu tulis alasan request.',
    'Submit request dan cek statusnya di halaman daftar atau detail.',
    'Jika request sudah approved, proses berikutnya biasanya dilanjutkan oleh IT.',
  ],
  check_inline: [
    'Buka modul Check Inline dan masuk ke halaman create bila ingin membuat data baru.',
    'Isi data inti seperti customer, PO, batch, qty, tanggal, dan unggah gambar bila ada.',
    'Simpan data lalu cek lagi di daftar Check Inline.',
    'Jika perlu revisi, buka detail/edit untuk memperbarui data atau gambar.',
    'Pastikan perubahan tersimpan sebelum kembali ke daftar.',
  ],
  berita_acara: [
    'Masuk ke modul Berita Acara lalu buka halaman create.',
    'Isi tanggal kejadian, tempat, waktu, customer, department, dan kronologi.',
    'Simpan dokumen agar nomor dan data berita acara terbentuk.',
    'Buka halaman detail untuk meninjau hasil dokumen.',
    'Lanjutkan ke print, download PDF, atau hapus dokumen sesuai hak akses.',
  ],
  date_code: [
    'Buka modul Date Code.',
    'Masukkan atau pilih informasi yang ingin dibaca dari kode tanggal.',
    'Perhatikan hasil pembacaan atau konversi yang ditampilkan pada halaman.',
    'Kalau hasilnya membingungkan, cocokan lagi input dan format kode yang dipakai.',
  ],
  stock_card: [
    'Masuk ke Stock Card atau master stock card sesuai tujuanmu.',
    'Kalau mengelola master item, tambah atau edit data barang pada halaman master.',
    'Kalau menambah stok, gunakan form stock in dan isi item, tanggal, serta jumlah.',
    'Kalau meminta stok, buat request item lalu cek status pending/approved.',
    'Pantau histori stok dan saldo item dari kartu stok yang tersedia.',
  ],
  plugging: [
    'Buka modul Plugging lalu pilih create jika ingin membuat data baru.',
    'Isi tanggal, jam, customer, kendaraan, suhu, lokasi, dan data pendukung lain yang ada di form.',
    'Simpan data plugging lalu cek lagi di daftar.',
    'Jika perlu revisi, buka edit untuk memperbarui data.',
    'Untuk approval, buka halaman approval dan lengkapi PIC customer serta signature jika memang diwajibkan.',
  ],
  electricity: [
    'Masuk ke halaman meter listrik yang sesuai, Standard Meter atau HV Meter.',
    'Isi log pembacaan meter pada form input.',
    'Simpan data lalu cek apakah log baru muncul di daftar.',
    'Kalau perlu revisi, gunakan aksi update pada log yang tersedia.',
    'Jika dibutuhkan, gunakan fitur export untuk mengunduh data.',
  ],
  water_meter: [
    'Buka modul Water Meter.',
    'Isi data pembacaan meter air pada form yang tersedia.',
    'Simpan lalu cek hasilnya pada daftar log.',
    'Update log bila ada kesalahan input.',
    'Gunakan export jika ingin mengunduh data log.',
  ],
  utility_report: [
    'Masuk ke Utility Report.',
    'Baca ringkasan data yang tampil pada halaman laporan.',
    'Cocokkan hasil laporan dengan periode atau konteks data yang sedang dilihat.',
    'Jika angka terasa berbeda, cek sumber log dari modul utility yang terkait.',
  ],
  visitor_form: [
    'Buka Visitor Form lalu masuk ke halaman create jika ingin membuat form baru.',
    'Isi data tamu, tujuan kunjungan, tanggal, waktu, dan pihak yang dikunjungi.',
    'Submit form lalu cek statusnya di halaman daftar.',
    'Lanjutkan ke approval atau update status sesuai role yang dimiliki.',
  ],
  exit_permit: [
    'Masuk ke Exit Permit lalu buka halaman create.',
    'Isi data barang, tujuan, alasan keluar, dan informasi pendukung lainnya.',
    'Submit permit lalu cek statusnya di daftar.',
    'Jika role mengizinkan, lanjutkan ke approval atau tindak lanjut dokumen.',
  ],
  master_data: [
    'Buka modul master data yang sesuai seperti Department, Employee, Position, Customer, Vehicle Type, atau lainnya.',
    'Gunakan halaman create untuk menambah data baru atau edit untuk memperbarui data lama.',
    'Isi field referensi dengan benar karena data ini biasanya dipakai modul lain.',
    'Simpan perubahan lalu cek daftar data untuk memastikan hasilnya sudah masuk.',
    'Jika ada aksi khusus seperti resign employee atau face reference photo, lakukan dari halaman yang memang menyediakan aksi itu.',
  ],
  control_panel: [
    'Masuk ke halaman Control Panel yang sesuai seperti Module Control, Access Rules, Logs, atau Database Backup.',
    'Pahami dulu fungsi halaman itu sebelum menjalankan aksi administratif.',
    'Lakukan perubahan atau tindakan seperti save konfigurasi, rollback, clear log, atau start backup hanya jika memang berwenang.',
    'Setelah aksi dilakukan, cek hasilnya di halaman yang sama atau pada data yang terdampak.',
  ],
  dashboard: [
    'Buka Dashboard untuk melihat ringkasan data utama aplikasi.',
    'Perhatikan kartu, indikator, atau widget yang tersedia.',
    'Jika ada angka yang ingin dipahami, cocokkan dengan modul sumber datanya.',
    'Gunakan dashboard sebagai titik awal sebelum masuk ke modul detail yang relevan.',
  ],
};

const page = usePage();
const open = ref(false);
const draft = ref('');
const messages = ref([]);
const messagesContainer = ref(null);
const isTyping = ref(false);
const lastTopicId = ref(null);
const lastProvider = ref('local');

const currentModule = computed(() => {
  const pageName = String(page.component || '').toLowerCase();
  const normalizedPageName = pageName.replace(/\s+/g, '');
  const url = String(page.url || window.location.pathname || '').toLowerCase();
  const combined = `${normalizedPageName} ${url}`;

  const matchedProfile = moduleProfiles.find((profile) => profile.matches.some((match) => combined.includes(String(match).toLowerCase())));
  return matchedProfile?.key || 'general';
});

const currentModuleLabel = computed(() => {
  return moduleProfiles.find((profile) => profile.key === currentModule.value)?.label || 'Aplikasi';
});
const currentPageProfile = computed(() => {
  const pageName = String(page.component || '').toLowerCase().replace(/\s+/g, '');
  const url = String(page.url || window.location.pathname || '').toLowerCase();
  const combined = `${pageName} ${url}`;

  return pageProfiles.find((profile) => profile.matches.some((match) => combined.includes(String(match).toLowerCase()))) || null;
});

const authUser = computed(() => page.props?.auth?.user || null);
const userPermissions = computed(() => Array.isArray(page.props?.auth?.module_permissions) ? page.props.auth.module_permissions : []);
const isAdmin = computed(() => Boolean(page.props?.auth?.is_admin));
const isManager = computed(() => Boolean(authUser.value?.position?.is_manager));
const departmentName = computed(() => String(authUser.value?.department?.name || '').trim());
const roleName = computed(() => String(authUser.value?.position?.name || '').trim());
const storageKey = computed(() => `${STORAGE_KEY_PREFIX}:${authUser.value?.id || 'guest'}`);

const quickSuggestions = computed(() => {
  if (currentPageProfile.value?.suggestions?.length) {
    return currentPageProfile.value.suggestions;
  }

  const baseProfile = moduleProfiles.find((profile) => profile.key === currentModule.value);
  const baseSuggestions = baseProfile?.suggestions || ['Jelaskan modul ini dengan bahasa sederhana', 'Apa fungsi halaman ini?', 'Siapa yang bisa memakai modul ini?'];

  if (currentModule.value === 'attendance') {
    return isManager.value || isAdmin.value
      ? ['Cara baca status attendance log', 'Kenapa attendance tim bisa OFF?', 'Apa yang perlu dicek saat hasil attendance aneh?']
      : baseSuggestions;
  }

  if (currentModule.value === 'roster') {
    return isManager.value || isAdmin.value
      ? ['Cara approve roster', 'Apa bedanya pending dan current roster?', 'Kalau roster belum current apa dampaknya?']
      : baseSuggestions;
  }

  if (currentModule.value === 'ticket') {
    return isManager.value || isAdmin.value
      ? ['Cara distribute ticket', 'Kenapa ticket tidak bisa di-resolve?', 'Siapa yang bisa close ticket?']
      : baseSuggestions;
  }

  return baseSuggestions;
});

function createMessage(role, text, extra = {}) {
  return {
    id: `${role}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
    role,
    text,
    suggestions: extra.suggestions || [],
  };
}

function capitalize(value) {
  const normalized = String(value || '').trim();
  return normalized ? normalized.charAt(0).toUpperCase() + normalized.slice(1) : '';
}

function defaultWelcomeMessage() {
  const loginName = String(authUser.value?.name || 'User').trim() || 'User';

  return createMessage(
    'assistant',
    `Hi ${loginName}\n\nDari mana kita harus mulai?`,
    { suggestions: quickSuggestions.value }
  );
}

function persistMessages() {
  try {
    localStorage.setItem(
      storageKey.value,
      JSON.stringify({
        messages: messages.value.slice(-30),
        lastTopicId: lastTopicId.value,
        lastProvider: lastProvider.value,
      })
    );
  } catch (error) {
    // ignore localStorage issues
  }
}

async function fetchHistoryFromDatabase() {
  const response = await axios.get('/ai-help/history', {
    headers: {
      'X-Skip-Global-Loading': '1',
    },
  });

  return Array.isArray(response.data?.messages) ? response.data.messages : [];
}

function loadMessagesFromStorage() {
  try {
    const raw = localStorage.getItem(storageKey.value);
    const parsed = raw ? JSON.parse(raw) : null;

    if (parsed && Array.isArray(parsed.messages) && parsed.messages.length) {
      messages.value = parsed.messages;
      lastTopicId.value = parsed.lastTopicId || null;
      lastProvider.value = parsed.lastProvider || 'local';
      return;
    }
  } catch (error) {
    // ignore localStorage issues
  }

  messages.value = [defaultWelcomeMessage()];
  lastTopicId.value = null;
  lastProvider.value = 'local';
}

async function loadMessages() {
  try {
    const databaseMessages = await fetchHistoryFromDatabase();
    if (databaseMessages.length) {
      messages.value = databaseMessages;
      lastTopicId.value = null;
      lastProvider.value = databaseMessages.at(-1)?.provider || 'database';
      persistMessages();
      return;
    }
  } catch (error) {
    // fallback to storage if database history cannot be loaded
  }

  loadMessagesFromStorage();
}

function normalizeText(value) {
  return String(value || '').trim().toLowerCase();
}

function getTopicById(topicId) {
  return knowledgeTopics.find((topic) => topic.id === topicId) || null;
}

function isFollowUpQuestion(question) {
  const normalized = normalizeText(question);
  const followUpPhrases = ['kalau begitu', 'kalau gitu', 'lalu', 'terus', 'habis itu', 'setelah itu', 'berarti', 'kalau saya', 'jadi', 'yang bisa', 'siapa', 'kenapa'];

  if (normalized.split(/\s+/).length <= 4) {
    return true;
  }

  return followUpPhrases.some((phrase) => normalized.startsWith(phrase));
}

function isGenericExplainQuestion(question) {
  const normalized = normalizeText(question);
  const phrases = [
    'jelaskan step by step',
    'bisa jelaskan step by step',
    'jelaskan',
    'cara kerja nya',
    'cara kerjanya',
    'mulai dari mana',
    'step by step',
    'alur nya',
    'alurnya',
  ];

  return phrases.some((phrase) => normalized === phrase || normalized.startsWith(phrase));
}

function wantsStepByStep(question) {
  const normalized = normalizeText(question);
  return normalized.includes('step by step')
    || normalized.includes('langkah')
    || normalized.includes('tahapan')
    || normalized.includes('urutannya')
    || normalized.includes('alur');
}

function scoreTopic(topic, question) {
  const normalizedQuestion = normalizeText(question);

  return topic.keywords.reduce((score, keyword) => {
    return normalizedQuestion.includes(normalizeText(keyword)) ? score + 1 : score;
  }, topic.module === currentModule.value ? 0.75 : 0);
}

function findBestTopic(question) {
  const ranked = knowledgeTopics
    .map((topic) => ({ topic, score: scoreTopic(topic, question) }))
    .sort((left, right) => right.score - left.score);

  if (!ranked.length || ranked[0].score < 1) {
    return null;
  }

  return ranked[0].topic;
}

function suggestionsForTopic(topic) {
  return topic?.suggestions?.length ? topic.suggestions : quickSuggestions.value;
}

function joinDetailLines(lines) {
  return lines.map((line) => `- ${line}`).join('\n');
}

function joinDetailSteps(lines) {
  return lines.map((line, index) => `${index + 1}. ${line}`).join('\n');
}

function buildTopicAnswer(topic, usedFollowUpContext = false, stepByStep = false) {
  const moduleLabel = topic.module === 'general' ? 'aplikasi' : capitalize(topic.module);
  const opener = usedFollowUpContext
    ? `Masih nyambung dengan topik ${moduleLabel} tadi, ${topic.summary.toLowerCase()}`
    : topic.summary;
  const body = stepByStep ? joinDetailSteps(topic.detail) : joinDetailLines(topic.detail);
  const closing = stepByStep
    ? 'Kalau mau, saya bisa lanjut jelaskan tiap langkahnya satu per satu juga.'
    : 'Kalau mau, saya bisa bantu lanjut dari sisi yang lebih spesifik juga.';

  return createMessage(
    'assistant',
    `${opener}\n\n${body}\n\n${closing}`,
    { suggestions: suggestionsForTopic(topic) }
  );
}

function fallbackAnswer(question) {
  const moduleLabel = currentModule.value === 'general' ? 'modul aplikasi ini' : currentModuleLabel.value;
  const normalized = normalizeText(question);
  const stepByStep = wantsStepByStep(question);

  if (stepByStep) {
    const fallbackSteps = moduleActionFallbacks[currentModule.value];
    if (Array.isArray(fallbackSteps) && fallbackSteps.length) {
      return createMessage(
        'assistant',
        `Kalau kita jelaskan ${moduleLabel} secara step by step, alurnya biasanya seperti ini:\n\n${joinDetailSteps(fallbackSteps)}\n\nKalau mau, saya bisa lanjut jelaskan salah satu langkahnya lebih detail lagi.`,
        { suggestions: quickSuggestions.value }
      );
    }

    return createMessage(
      'assistant',
      `Kalau kita jelaskan ${moduleLabel} secara step by step, alurnya biasanya seperti ini:\n\n1. buka modul atau halaman ${moduleLabel} yang ingin dipakai\n2. pahami fungsi utama halaman itu dari data, tombol, atau form yang tersedia\n3. isi, pilih, atau cek data yang dibutuhkan sesuai proses modulnya\n4. simpan, submit, atau jalankan aksi utama pada halaman tersebut\n5. periksa hasil akhirnya, termasuk status, detail, approval, atau output lain yang muncul\n\nKalau mau, saya bisa lanjut jelaskan step by step yang lebih spesifik untuk halaman ini.`,
      { suggestions: quickSuggestions.value }
    );
  }

  if (normalized.includes('cara') || normalized.includes('bagaimana') || normalized.includes('kenapa')) {
    return createMessage(
      'assistant',
      `Saya belum yakin menangkap maksudnya secara spesifik, tapi saya bisa bantu dari konteks ${moduleLabel}.\n\nCoba tulis pertanyaannya lebih langsung tentang langkah penggunaan, arti status, approval, atau alasan sebuah aksi tidak bisa dipakai di halaman ini.`,
      { suggestions: quickSuggestions.value }
    );
  }

  return createMessage(
    'assistant',
    `Saya siap bantu untuk pemakaian modul ${moduleLabel}. Kamu bisa tanya alur kerja, arti status, approval, atau alasan sebuah aksi tidak bisa dijalankan.`,
    { suggestions: quickSuggestions.value }
  );
}

function formatAssistantMessage(text) {
  const rawBlocks = String(text || '')
    .split(/\n\s*\n/)
    .map((block) => block.trim())
    .filter(Boolean);

  return rawBlocks.map((block) => {
    const lines = block
      .split('\n')
      .map((line) => line.trim())
      .filter(Boolean);

    const isList = lines.length > 1 && lines.every((line) => line.startsWith('- '));
    const isOrderedList = lines.length > 1 && lines.every((line) => /^\d+\.\s+/.test(line));

    if (isList) {
      return {
        type: 'list',
        items: lines.map((line) => line.replace(/^- /, '').trim()),
      };
    }

    if (isOrderedList) {
      return {
        type: 'ordered-list',
        items: lines.map((line) => line.replace(/^\d+\.\s+/, '').trim()),
      };
    }

    return {
      type: 'paragraph',
      content: lines.join(' '),
    };
  });
}

function generateAnswer(question) {
  const stepByStep = wantsStepByStep(question);
  const explicitTopic = findBestTopic(question);

  if (explicitTopic) {
    lastTopicId.value = explicitTopic.id;
    return buildTopicAnswer(explicitTopic, false, stepByStep);
  }

  const rememberedTopic = getTopicById(lastTopicId.value);
  if (rememberedTopic && isFollowUpQuestion(question)) {
    return buildTopicAnswer(rememberedTopic, true, stepByStep);
  }

  if (isGenericExplainQuestion(question)) {
    const defaultTopicId = defaultTopicByModule[currentModule.value];
    const defaultTopic = getTopicById(defaultTopicId);
    if (defaultTopic) {
      lastTopicId.value = defaultTopic.id;
      return buildTopicAnswer(defaultTopic, false, true);
    }
  }

  return fallbackAnswer(question);
}

function buildPageContext() {
  return {
    component: String(page.component || ''),
    url: String(page.url || window.location.pathname || ''),
    module: currentModule.value,
    module_permissions: userPermissions.value,
  };
}

async function saveMessageToDatabase(message) {
  await axios.post('/ai-help/history', {
    role: message.role,
    text: message.text,
    provider: message.provider || null,
    page: buildPageContext(),
  }, {
    headers: {
      'X-Skip-Global-Loading': '1',
    },
  });
}

async function clearHistoryInDatabase() {
  await axios.delete('/ai-help/history', {
    headers: {
      'X-Skip-Global-Loading': '1',
    },
  });
}

function buildHistoryPayload() {
  return messages.value.slice(-8).map((message) => ({
    role: message.role,
    text: message.text,
  }));
}

async function fetchBackendAnswer(question) {
  const response = await axios.post('/ai-help/chat', {
    message: question,
    page: buildPageContext(),
    history: buildHistoryPayload(),
  }, {
    headers: {
      'X-Skip-Global-Loading': '1',
    },
  });

  return {
    text: String(response.data?.answer || '').trim(),
    provider: String(response.data?.provider || 'openai'),
  };
}

function resolveMessagesElement() {
  const element = messagesContainer.value;
  if (!element) return null;

  if (element instanceof HTMLElement) {
    return element;
  }

  return element.$el instanceof HTMLElement ? element.$el : null;
}

function scrollToBottom() {
  nextTick(() => {
    const element = resolveMessagesElement();
    if (!element) return;

    window.requestAnimationFrame(() => {
      element.scrollTo({
        top: element.scrollHeight,
        behavior: 'smooth',
      });
    });
  });
}

async function pushAssistantReply(question) {
  try {
    const backend = await fetchBackendAnswer(question);
    if (backend.text) {
      lastProvider.value = backend.provider;
      const message = createMessage('assistant', backend.text, {
        suggestions: quickSuggestions.value,
      });
      message.provider = backend.provider;
      messages.value.push(message);
      try {
        await saveMessageToDatabase(message);
      } catch (error) {
        // ignore database sync failures
      }
      persistMessages();
      scrollToBottom();
      return;
    }
  } catch (error) {
    // fallback to local help topics if OpenAI is unavailable
  }

  lastProvider.value = 'local';
  const reply = generateAnswer(question);
  reply.provider = 'local';
  messages.value.push(reply);
  try {
    await saveMessageToDatabase(reply);
  } catch (error) {
    // ignore database sync failures
  }
  persistMessages();
  scrollToBottom();
}

async function sendQuestion(question) {
  const normalized = String(question || '').trim();
  if (!normalized || isTyping.value) return;

  const userMessage = createMessage('user', normalized);
  userMessage.provider = 'user';
  messages.value.push(userMessage);
  draft.value = '';
  try {
    await saveMessageToDatabase(userMessage);
  } catch (error) {
    // ignore database sync failures
  }
  persistMessages();
  scrollToBottom();

  isTyping.value = true;
  await new Promise((resolve) => window.setTimeout(resolve, 320));
  await pushAssistantReply(normalized);
  isTyping.value = false;
}

function submitQuestion() {
  return sendQuestion(draft.value);
}

function handleComposerKeydown(event) {
  if (event.key !== 'Enter' || event.shiftKey) {
    return;
  }

  event.preventDefault();
  submitQuestion();
}

function askSuggestion(suggestion) {
  return sendQuestion(suggestion);
}

function resetChat() {
  messages.value = [defaultWelcomeMessage()];
  lastTopicId.value = null;
  lastProvider.value = 'local';
  draft.value = '';
  isTyping.value = false;
  localStorage.removeItem(storageKey.value);
  clearHistoryInDatabase().catch(() => {});
  persistMessages();
  scrollToBottom();
}

function toggleOpen() {
  open.value = !open.value;
  if (open.value) {
    scrollToBottom();
  }
}

watch(currentModule, () => {
  if (!messages.value.length) {
    messages.value = [defaultWelcomeMessage()];
  } else {
    const lastAssistant = [...messages.value].reverse().find((message) => message.role === 'assistant');
    if (lastAssistant) {
      lastAssistant.suggestions = quickSuggestions.value;
    }
  }

  persistMessages();
});

watch(storageKey, () => {
  loadMessages();
  scrollToBottom();
});

onMounted(() => {
  loadMessages().then(() => {
    scrollToBottom();
  });
  scrollToBottom();
});
</script>
