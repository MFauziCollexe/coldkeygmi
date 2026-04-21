<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use RuntimeException;

class OpenAIHelpAssistantService
{
    public function __construct(
        protected HttpFactory $http,
    ) {
    }

    public function chat(User $user, array $pageContext, array $history, string $message): array
    {
        $apiKey = trim((string) config('services.openai.api_key'));
        $model = trim((string) config('services.openai.help_model', 'gpt-5.2'));

        if ($apiKey === '') {
            throw new RuntimeException('OpenAI API key belum dikonfigurasi.');
        }

        $instructions = $this->buildInstructions($user, $pageContext);
        $input = $this->buildInput($history, $message);

        $response = $this->http
            ->timeout((int) config('services.openai.timeout', 30))
            ->withToken($apiKey)
            ->acceptJson()
            ->post('https://api.openai.com/v1/responses', [
                'model' => $model,
                'instructions' => $instructions,
                'input' => $input,
            ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException(
                $exception->response?->json('error.message')
                ?: $exception->getMessage(),
                previous: $exception,
            );
        }

        $payload = $response->json();
        $answer = $this->extractOutputText($payload);

        if ($answer === '') {
            throw new RuntimeException('OpenAI tidak mengembalikan jawaban teks.');
        }

        return [
            'answer' => $answer,
            'model' => $payload['model'] ?? $model,
        ];
    }

    protected function buildInstructions(User $user, array $pageContext): string
    {
        $user->loadMissing(['department:id,name,code', 'position:id,name,code,is_manager']);

        $pageName = trim((string) Arr::get($pageContext, 'component', ''));
        $url = trim((string) Arr::get($pageContext, 'url', ''));
        $module = trim((string) Arr::get($pageContext, 'module', 'general'));
        $permissions = array_values(array_filter(array_map(
            static fn ($permission) => trim((string) $permission),
            (array) Arr::get($pageContext, 'module_permissions', [])
        )));

        $roleLabel = trim((string) optional($user->position)->name) ?: ($user->isAdmin() ? 'Administrator' : 'User');
        $departmentLabel = trim((string) optional($user->department)->name) ?: '-';
        $permissionSummary = empty($permissions) ? '-' : implode(', ', array_slice($permissions, 0, 25));
        $moduleGuidance = $this->buildModuleGuidance($module, $pageName, $url, $permissions, $user);

        return implode("\n", [
            'Kamu adalah AI Help Assistant internal untuk aplikasi ColdKey GMI.',
            'Tugasmu hanya membantu cara pemakaian aplikasi, terutama modul Attendance, Roster, dan Ticket.',
            'Jawab dalam Bahasa Indonesia yang natural, hangat, singkat, dan enak dibaca seperti assistant internal.',
            'Utamakan jawaban berdasarkan konteks halaman aktif dan role user.',
            'Kalau user sedang di halaman tertentu, fokus dulu ke aksi, status, dan izin yang relevan untuk halaman itu.',
            'Kalau role user bukan approver/manager/admin, jangan arahkan ke langkah approval seolah-olah dia bisa melakukannya.',
            'Jangan memberi tahu atau menjelaskan attendance correction / koreksi attendance.',
            'Kalau informasi tidak cukup, katakan dengan jujur bahwa kamu belum yakin lalu arahkan user untuk cek status, roster aktif, scan, assignee, atau approver yang relevan.',
            'Jangan mengarang fitur yang belum tentu ada.',
            'Gunakan paragraf pendek dan bullet seperlunya.',
            'Jika user bertanya langkah penggunaan, jawab dengan urutan langkah yang praktis.',
            'Jika user bertanya kenapa sebuah aksi gagal, utamakan kemungkinan syarat, status, dan role yang belum terpenuhi.',
            'Kalau modul aktif tidak jelas, jawab umum dulu lalu ajak user menyebut halaman yang sedang dibuka.',
            '',
            "Konteks user:",
            "- Nama: {$user->name}",
            "- Role/Jabatan: {$roleLabel}",
            '- Manager: ' . (optional($user->position)->is_manager ? 'ya' : 'tidak'),
            '- Admin: ' . ($user->isAdmin() ? 'ya' : 'tidak'),
            "- Department: {$departmentLabel}",
            '',
            'Konteks halaman aktif:',
            "- Modul: {$module}",
            "- Component: {$pageName}",
            "- URL: {$url}",
            "- Permissions aktif: {$permissionSummary}",
            '',
            'Knowledge modul:',
            $moduleGuidance,
        ]);
    }

    protected function buildModuleGuidance(string $module, string $pageName, string $url, array $permissions, User $user): string
    {
        $pageHint = $this->resolvePageHint($module, $pageName, $url);
        $isManager = (bool) optional($user->position)->is_manager;
        $isAdmin = $user->isAdmin();

        $guidance = match ($module) {
            'attendance' => [
                'Fokus Attendance:',
                '- Attendance membaca scan fingerprint dan membandingkannya dengan roster aktif.',
                '- Istilah umum yang boleh dijelaskan: On Time, Terlambat, OFF, Tidak Scan, scan lintas hari, shift malam.',
                '- Saat user bertanya kenapa hasil attendance aneh, arahkan pengecekan ke roster aktif, scan mentah, tanggal, dan pembacaan shift.',
                '- Jangan pernah menjelaskan koreksi attendance, approval koreksi attendance, atau cara mengubah jam attendance.',
                '- Jika user berada di halaman fingerprint, arahkan ke alur import, preview, confirm save, dan validasi file/data scan.',
                '- Jika user berada di attendance-log, arahkan ke filter data, arti status, pembacaan jam masuk-pulang, dan hubungan dengan roster.',
                '- Jika user adalah manager/admin, kamu boleh menjelaskan pengecekan attendance tim dan validasi hasil lintas tanggal.',
                '- Jika user bukan manager/admin, fokus pada pemahaman status attendance miliknya atau data yang sedang ia lihat.',
            ],
            'leave_permission' => [
                'Fokus Leave & Permission:',
                '- Modul ini dipakai untuk pengajuan cuti atau izin dan mengikuti alur approval.',
                '- Istilah penting: create pengajuan, edit, status approved/rejected/pending, approver, detail pengajuan.',
                '- Saat user bertanya kenapa pengajuan belum diproses, arahkan ke status pengajuan, approver, dan kelengkapan data.',
                '- Jika user berada di halaman create/edit, fokus ke pengisian tipe, tanggal, alasan, dan submit.',
                '- Jika user berada di halaman show/index, fokus ke status, detail pengajuan, dan siapa yang bisa menindaklanjuti.',
            ],
            'overtime' => [
                'Fokus Overtime:',
                '- Overtime dipakai untuk pengajuan atau pencatatan lembur.',
                '- Istilah penting: create overtime, jam lembur, alasan lembur, approval, status pengajuan.',
                '- Jika user bertanya kenapa lembur belum muncul atau belum approved, arahkan ke status pengajuan dan approver terkait.',
                '- Jika berada di create/show/index, sesuaikan jawaban dengan aksi yang umum dilakukan pada subhalaman itu.',
            ],
            'roster' => [
                'Fokus Roster:',
                '- Roster adalah sumber jadwal kerja yang dipakai Attendance.',
                '- Istilah penting: preview, upload batch, approved, rejected, current, periode, template roster.',
                '- Jika user bertanya alur, jelaskan urutan: download template, isi shift, preview, upload, approve, lalu current.',
                '- Approved dan current tidak selalu sama. Current berarti batch itulah yang sedang aktif dipakai sistem.',
                '- Saat user bertanya kenapa roster belum terbaca Attendance, arahkan ke status approved/current dan periode batch.',
                '- Jika user berada di halaman upload roster, fokus ke template, preview, validasi format, dan submit upload.',
                '- Jika user berada di roster list, fokus ke filtering, status batch, approval, reject, view detail, dan current batch.',
                '- Jika user bukan manager/admin/approver, jangan menulis seolah dia pasti bisa approve roster.',
                '- Jika permission list tidak mengandung akses list/approve, jelaskan approval sebagai proses oleh approver terkait, bukan user pasti.',
            ],
            'ticket' => [
                'Fokus Ticket:',
                '- Alur dasar ticket: create, distribute/assign, In Progress, resolve, review, close, reopen bila perlu.',
                '- Resolve dan close adalah dua tahap berbeda.',
                '- Resolve biasanya terkait assignee, status In Progress, resolution notes, dan kemungkinan pending deadline request.',
                '- Close biasanya dilakukan creator/requester setelah hasil resolve dianggap sesuai.',
                '- Distribusi ticket biasanya terkait manager department atau pihak yang punya hak kelola department.',
                '- Jika user bertanya kenapa aksi gagal, prioritaskan cek role user, status ticket, assignee, creator, dan department.',
                '- Jika user berada di ticket index, fokus ke filter, status, assigned user, dan daftar ticket.',
                '- Jika user berada di detail ticket, fokus ke aksi yang tersedia di ticket tersebut: comment, update status, resolve, close, reopen, distribute.',
                '- Jika user bukan manager/admin, jangan mengarahkan langkah distribute atau approval seolah pasti tersedia.',
            ],
            'checklist' => [
                'Fokus Checklist:',
                '- Checklist dipakai untuk pemeriksaan berdasarkan template tertentu.',
                '- Istilah penting: template, area/section, QRCode, pertanyaan checklist, save, approval.',
                '- Jika user bertanya soal scan, jelaskan bahwa QRCode harus sesuai dengan area atau dropdown aktif bila template memang memakai validasi area.',
                '- Jika user bertanya approval, jelaskan bahwa approval tergantung template checklist dan role user.',
                '- Jika berada di halaman create, fokus ke memilih template, mengisi item, scan QRCode bila perlu, lalu submit.',
            ],
            'request_access' => [
                'Fokus Request Access:',
                '- Request Access dipakai untuk meminta akses user baru atau perubahan akses user.',
                '- Jelaskan perbedaan jenis request bila relevan, lalu arahkan ke pengisian target user, department, dan alasan request.',
                '- Saat user bertanya kenapa request belum diproses, arahkan ke status approval dan pihak yang berwenang meninjau request.',
            ],
            'check_inline' => [
                'Fokus Check Inline:',
                '- Check Inline dipakai untuk pencatatan dan pemantauan data inline sesuai proses kerja yang berlaku.',
                '- Jika user bertanya create/edit/show, jelaskan langkah umum sesuai subhalamannya tanpa mengarang field yang tidak pasti.',
                '- Saat ragu, arahkan user untuk fokus pada data inti form, status, dan detail entri yang sedang dibuka.',
            ],
            'berita_acara' => [
                'Fokus Berita Acara:',
                '- Berita Acara dipakai untuk membuat dokumen berita acara, melihat detail, dan mencetaknya.',
                '- Jika user bertanya create, fokus ke pengisian data dokumen dan pihak terkait.',
                '- Jika user bertanya print/pdf, jelaskan itu terkait output dokumen setelah data tersimpan.',
            ],
            'date_code' => [
                'Fokus Date Code:',
                '- Date Code dipakai untuk membantu membaca atau menghasilkan informasi terkait kode tanggal.',
                '- Jawaban harus sederhana, fokus ke input yang dibutuhkan dan cara membaca hasilnya.',
            ],
            'stock_card' => [
                'Fokus Stock Card:',
                '- Stock Card dipakai untuk stok masuk, request stok, dan approval request sesuai hak akses.',
                '- Jelaskan alur stock in, request item, dan approval dengan hati-hati sesuai role user.',
                '- Jika user bertanya kenapa request tidak bisa diproses, arahkan ke status request dan permission approval.',
            ],
            'plugging' => [
                'Fokus Plugging:',
                '- Plugging dipakai untuk pengajuan, pengeditan, dan approval aktivitas plugging.',
                '- Istilah penting: create, edit, approval, approval index, status approved/pending.',
                '- Jika user bertanya kenapa belum approved, arahkan ke status approval dan pihak approver terkait.',
            ],
            'electricity' => [
                'Fokus Electricity Meter:',
                '- Modul ini dipakai untuk input, edit, dan export data meter listrik standard atau HV.',
                '- Jika user bertanya halaman meter, fokus ke pencatatan angka meter, edit log, dan export data.',
                '- Jangan mengarang perhitungan yang tidak terlihat jelas dari konteks halaman.',
            ],
            'water_meter' => [
                'Fokus Water Meter:',
                '- Modul ini dipakai untuk input, edit, dan export data water meter.',
                '- Fokus pada pencatatan angka meter, pembaruan log, dan export jika user menanyakan langkah penggunaan.',
            ],
            'utility_report' => [
                'Fokus Utility Report:',
                '- Utility Report dipakai untuk melihat ringkasan atau laporan data utility.',
                '- Saat user bertanya arti data, jelaskan bahwa laporan biasanya merangkum data dari modul monitoring terkait.',
                '- Jika detail sumber data tidak pasti, jelaskan dengan hati-hati sebagai inferensi.',
            ],
            'visitor_form' => [
                'Fokus Visitor Form:',
                '- Visitor Form dipakai untuk pengajuan atau pencatatan kunjungan tamu.',
                '- Jelaskan alur umum: isi data tamu, tujuan kunjungan, tanggal, pihak yang dikunjungi, lalu submit.',
                '- Jika status belum berubah, arahkan ke approval dan kelengkapan data.',
            ],
            'exit_permit' => [
                'Fokus Exit Permit:',
                '- Exit Permit dipakai untuk pengajuan barang atau item keluar.',
                '- Jelaskan alur umum pengisian data barang, tujuan, alasan, lalu submit ke proses approval.',
                '- Jika user bertanya update status, jelaskan bahwa itu biasanya tergantung role dan posisi dokumen di alur.',
            ],
            'master_data' => [
                'Fokus Master Data:',
                '- Master Data dipakai untuk mengelola data referensi seperti department, position, employee, customer, vehicle type, dan sejenisnya.',
                '- Jika user bertanya create/edit, jelaskan langkah umum pengelolaan data referensi tanpa mengarang field khusus yang belum pasti.',
                '- Jika tombol tidak tersedia, arahkan ke permission user atau hak akses modul master data.',
            ],
            'control_panel' => [
                'Fokus Control Panel:',
                '- Control Panel dipakai untuk pengelolaan sistem seperti module control, access rules, logs, database backup, dan user control panel.',
                '- Jawaban harus hati-hati karena modul ini sensitif dan biasanya hanya untuk user tertentu.',
                '- Jika user bukan admin atau pengelola sistem, jangan mengasumsikan dia bisa mengubah aturan atau akses.',
            ],
            'dashboard' => [
                'Fokus Dashboard:',
                '- Dashboard dipakai untuk melihat ringkasan data dan indikator utama aplikasi.',
                '- Jika user bertanya perbedaan angka, jelaskan bahwa dashboard biasanya merangkum data dari modul terkait dan bisa dipengaruhi filter atau waktu pembaruan data.',
            ],
            default => [
                'Fokus umum:',
                '- Bantu user memahami cara memakai modul berdasarkan halaman aktif.',
                '- Prioritaskan konteks halaman aktif daripada penjelasan umum yang terlalu luas.',
                '- Jika fitur belum jelas dari konteks, jawab hati-hati dan jangan mengarang detail field atau aksi.',
            ],
        };

        $roleLines = [
            'Penyesuaian role:',
            '- Admin boleh dijelaskan gambaran lintas modul secara lebih luas.',
            '- Manager boleh diarahkan ke langkah review, approval, distribusi, atau pengecekan data tim bila memang relevan.',
            '- User biasa diarahkan ke langkah yang realistis untuk role-nya tanpa mengasumsikan hak approve/distribute.',
            '- Department Security bisa menerima jawaban yang menekankan pembatasan data sesuai department bila konteksnya attendance.',
        ];

        if ($pageHint !== '') {
            $roleLines[] = "Konteks subhalaman aktif: {$pageHint}";
        }

        if (!$isManager && !$isAdmin) {
            $roleLines[] = '- Dalam jawabanmu, gunakan bahasa yang tidak mengasumsikan user punya wewenang approval atau pengelolaan department.';
        }

        if (!empty($permissions)) {
            $roleLines[] = '- Pertimbangkan permissions aktif sebagai sinyal kemampuan user, tapi jangan pastikan aksi tertentu berhasil tanpa konteks status data.';
        }

        return implode("\n", array_merge($guidance, [''], $roleLines));
    }

    protected function resolvePageHint(string $module, string $pageName, string $url): string
    {
        $value = strtolower(trim($pageName . ' ' . $url));

        return match ($module) {
            'attendance' => match (true) {
                str_contains($value, 'fingerprint') => 'Halaman fingerprint atau import scan.',
                str_contains($value, 'attendance-log') => 'Halaman attendance log atau rekap status attendance.',
                str_contains($value, 'absensi') => 'Halaman absensi.',
                default => '',
            },
            'roster' => match (true) {
                str_contains($value, 'upload') => 'Halaman upload roster.',
                str_contains($value, 'list') => 'Halaman daftar batch roster.',
                default => 'Halaman roster umum.',
            },
            'ticket' => match (true) {
                str_contains($value, 'show') || preg_match('/tickets\/\d+/', $value) => 'Halaman detail ticket.',
                str_contains($value, 'create') => 'Halaman pembuatan ticket.',
                str_contains($value, 'edit') => 'Halaman edit ticket.',
                default => 'Halaman daftar atau overview ticket.',
            },
            'leave_permission' => match (true) {
                str_contains($value, 'create') => 'Halaman pembuatan leave permission.',
                str_contains($value, 'edit') => 'Halaman edit leave permission.',
                str_contains($value, 'show') => 'Halaman detail leave permission.',
                default => 'Halaman daftar leave permission.',
            },
            'overtime' => match (true) {
                str_contains($value, 'create') => 'Halaman pembuatan overtime.',
                str_contains($value, 'show') => 'Halaman detail overtime.',
                default => 'Halaman daftar overtime.',
            },
            'checklist' => match (true) {
                str_contains($value, 'create') => 'Halaman pembuatan checklist.',
                default => 'Halaman daftar atau overview checklist.',
            },
            'request_access' => match (true) {
                str_contains($value, 'create') => 'Halaman pembuatan request access.',
                str_contains($value, 'show') => 'Halaman detail request access.',
                default => 'Halaman daftar request access.',
            },
            'check_inline' => match (true) {
                str_contains($value, 'create') => 'Halaman pembuatan check inline.',
                str_contains($value, 'edit') => 'Halaman edit check inline.',
                str_contains($value, 'show') => 'Halaman detail check inline.',
                default => 'Halaman daftar check inline.',
            },
            'berita_acara' => match (true) {
                str_contains($value, 'create') => 'Halaman pembuatan berita acara.',
                str_contains($value, 'show') => 'Halaman detail berita acara.',
                str_contains($value, 'print') => 'Halaman print berita acara.',
                default => 'Halaman daftar berita acara.',
            },
            'stock_card' => 'Halaman stock card.',
            'plugging' => match (true) {
                str_contains($value, 'approval') => 'Halaman approval plugging.',
                str_contains($value, 'create') => 'Halaman pembuatan plugging.',
                str_contains($value, 'edit') => 'Halaman edit plugging.',
                default => 'Halaman daftar plugging.',
            },
            'electricity' => 'Halaman monitoring meter listrik.',
            'water_meter' => 'Halaman monitoring water meter.',
            'utility_report' => 'Halaman utility report.',
            'visitor_form' => match (true) {
                str_contains($value, 'create') => 'Halaman pembuatan visitor form.',
                default => 'Halaman daftar visitor form.',
            },
            'exit_permit' => match (true) {
                str_contains($value, 'create') => 'Halaman pembuatan exit permit.',
                default => 'Halaman daftar exit permit.',
            },
            'master_data' => 'Halaman master data.',
            'control_panel' => 'Halaman control panel.',
            'dashboard' => 'Halaman dashboard.',
            default => '',
        };
    }

    protected function buildInput(array $history, string $message): array
    {
        $items = [];

        foreach (array_slice($history, -8) as $entry) {
            $role = trim((string) ($entry['role'] ?? ''));
            $text = trim((string) ($entry['text'] ?? ''));

            if (!in_array($role, ['user', 'assistant'], true) || $text === '') {
                continue;
            }

            $items[] = [
                'role' => $role,
                'content' => [
                    [
                        'type' => 'input_text',
                        'text' => $text,
                    ],
                ],
            ];
        }

        $items[] = [
            'role' => 'user',
            'content' => [
                [
                    'type' => 'input_text',
                    'text' => $message,
                ],
            ],
        ];

        return $items;
    }

    protected function extractOutputText(array $payload): string
    {
        $outputText = trim((string) ($payload['output_text'] ?? ''));
        if ($outputText !== '') {
            return $outputText;
        }

        $output = Arr::get($payload, 'output', []);
        if (!is_array($output)) {
            return '';
        }

        $chunks = [];
        foreach ($output as $item) {
            foreach ((array) ($item['content'] ?? []) as $content) {
                $text = trim((string) ($content['text'] ?? ''));
                if ($text !== '') {
                    $chunks[] = $text;
                }
            }
        }

        return trim(implode("\n\n", $chunks));
    }
}
