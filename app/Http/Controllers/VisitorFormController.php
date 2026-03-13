<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\User;
use App\Models\VisitorForm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class VisitorFormController extends Controller
{
    use RemembersIndexUrl;

    public function create()
    {
        $hostUsers = User::query()
            ->select('id', 'name')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return Inertia::render('GMIVP/VisitorForm/Create', [
            'hostUsers' => $hostUsers,
        ]);
    }

    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'visitor-form');

        $filters = [
            'search' => trim((string) $request->input('search', '')),
            'date' => trim((string) $request->input('date', '')),
            'status' => trim((string) $request->input('status', '')),
        ];

        $authUser = $request->user();
        $query = VisitorForm::query()->with([
            'user:id,name',
            'hostUser:id,name',
            'securityApprover:id,name',
            'hostApprover:id,name',
            'attachments:id,visitor_form_id,filename,path',
        ]);
        $this->applyFilters($query, $filters);

        $visitors = $query
            ->orderByDesc('visit_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('GMIVP/VisitorForm/Index', [
            'visitors' => $visitors,
            'filters' => $filters,
            'statusOptions' => [
                ['value' => 'Waiting', 'label' => 'Waiting'],
                ['value' => 'Checked In', 'label' => 'Checked In'],
                ['value' => 'Checked Out', 'label' => 'Checked Out'],
                ['value' => 'Cancelled', 'label' => 'Cancelled'],
            ],
            'authUserId' => optional($authUser)->id,
            'isSecurityApprover' => $this->isSecurityUser($authUser),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'visitor_name' => ['required', 'string', 'max:150'],
            'from' => ['nullable', 'string', 'max:150'],
            'identity_no' => ['nullable', 'string', 'max:100'],
            'purpose' => ['required', 'string', 'max:255'],
            'host_user_id' => ['required', 'exists:users,id'],
            'visit_date' => ['required', 'date'],
            'appointment_time' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'image', 'max:5120'],
        ]);

        $hostUser = User::query()->select('id', 'name')->findOrFail((int) $validated['host_user_id']);

        $visitorForm = VisitorForm::create([
            ...$validated,
            'host_name' => $hostUser->name,
            'appointment_time' => !empty($validated['appointment_time']) ? $validated['appointment_time'] . ':00' : null,
            'check_out' => !empty($validated['check_out']) ? $validated['check_out'] . ':00' : null,
            'status' => 'Waiting',
            'approval_status' => 'pending',
            'user_id' => optional($request->user())->id,
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('visitor-forms/' . $visitorForm->id, 'public');
                $visitorForm->attachments()->create([
                    'filename' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        return $this->redirectToRememberedIndex($request, 'visitor-form', 'gmi-visitor-permit.visitor-form.index')
            ->with('success', 'Visitor form berhasil disimpan. Menunggu approval Security dan user yang dituju.');
    }

    public function approve(Request $request, VisitorForm $visitorForm)
    {
        $validated = $request->validate([
            'role' => ['required', 'in:security,host'],
        ]);

        $authUser = $request->user();
        if (!$authUser) {
            abort(403);
        }

        if ($validated['role'] === 'security') {
            if (!$this->isSecurityUser($authUser)) {
                abort(403, 'Hanya user Security yang dapat approve sebagai Security.');
            }

            if (!$visitorForm->security_approved_at) {
                $visitorForm->security_approved_by = $authUser->id;
                $visitorForm->security_approved_at = now();
            }
        }

        if ($validated['role'] === 'host') {
            if ((int) $visitorForm->host_user_id !== (int) $authUser->id) {
                abort(403, 'Hanya user yang dituju yang dapat approve.');
            }

            if (!$visitorForm->host_approved_at) {
                $visitorForm->host_approved_by = $authUser->id;
                $visitorForm->host_approved_at = now();
            }
        }

        $this->syncApprovalStatus($visitorForm);
        $visitorForm->save();

        return redirect()->back()->with('success', 'Approval visitor berhasil disimpan.');
    }

    public function updateStatus(Request $request, VisitorForm $visitorForm)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:Waiting,Checked In,Checked Out,Cancelled'],
        ]);

        if ($validated['status'] !== 'Cancelled' && $visitorForm->approval_status !== 'approved') {
            return redirect()->back()->withErrors([
                'status' => 'Status kunjungan hanya bisa diubah setelah approval Security dan user yang dituju selesai.',
            ]);
        }

        $updateData = ['status' => $validated['status']];

        if ($validated['status'] === 'Checked Out' && !$visitorForm->check_out) {
            $updateData['check_out'] = now()->format('H:i:s');
        }

        $visitorForm->update($updateData);

        return redirect()->back()->with('success', 'Status visitor berhasil diupdate.');
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if ($filters['search'] !== '') {
            $query->where(function ($inner) use ($filters) {
                $inner->where('visitor_name', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('from', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('identity_no', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('purpose', 'like', '%' . $filters['search'] . '%')
                    ->orWhere('host_name', 'like', '%' . $filters['search'] . '%');
            });
        }

        if ($filters['date'] !== '') {
            $query->whereDate('visit_date', $filters['date']);
        }

        if ($filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
    }

    private function syncApprovalStatus(VisitorForm $visitorForm): void
    {
        $securityApproved = !is_null($visitorForm->security_approved_at);
        $hostApproved = !is_null($visitorForm->host_approved_at);

        if ($securityApproved && $hostApproved) {
            $visitorForm->approval_status = 'approved';
            return;
        }

        if ($securityApproved || $hostApproved) {
            $visitorForm->approval_status = 'partially_approved';
            return;
        }

        $visitorForm->approval_status = 'pending';
    }

    private function isSecurityUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $user->loadMissing('department:id,code');
        return strtoupper((string) optional($user->department)->code) === 'SEC';
    }
}
