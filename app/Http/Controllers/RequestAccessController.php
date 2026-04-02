<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RemembersIndexUrl;
use App\Models\RequestAccess;
use App\Models\User;
use App\Models\Department;
use App\Models\ModulePermission;
use App\Models\ActivityLog;
use App\Support\AccessRuleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class RequestAccessController extends Controller
{
    use RemembersIndexUrl;

    private const ACCESS_MODULE = 'request_access';

    protected function accessRules(): AccessRuleService
    {
        return app(AccessRuleService::class);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->rememberIndexUrl($request, 'request-access');

        $query = RequestAccess::query()->with(['user', 'targetDepartment', 'creator', 'reviewer', 'processor']);

        // Filters
        $search = $request->input('search');
        $status = $request->input('status');
        $type = $request->input('type');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                  ->orWhere('target_user_name', 'like', "%{$search}%")
                  ->orWhere('target_user_email', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Filter status - only apply if status is not empty
        if (!empty($status)) {
            $query->where('status', $status);
        }

        // Filter type - only apply if type is not empty
        if (!empty($type)) {
            $query->where('type', $type);
        }

        $currentUserId = Auth::id();
        $currentUser = User::find($currentUserId);

        // Role-based visibility:
        // - Regular users see only their own requests
        // - Managers see requests from their department
        // - IT department sees all requests (to process)
        // - Admins see all requests
        
        $isManager = $this->isDepartmentManager($currentUserId);
        $isAdmin = $currentUser && $currentUser->isAdmin();
        $canViewAll = $this->canViewAllRequests($currentUser);

        if (!$isAdmin && !$isManager && !$canViewAll) {
            // Regular user - see only their own requests
            $query->where('created_by', $currentUserId);
        } elseif ($isManager && !$isAdmin && !$canViewAll) {
            // Manager - see requests from their department
            $managedDeptIds = $this->getManagedDepartmentIds($currentUserId);
            $query->whereHas('creator', function ($q) use ($managedDeptIds) {
                $q->whereIn('department_id', $managedDeptIds);
            });
        }
        // Admin and IT see all - no additional filter

        $requests = $query
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('GMISL/Utility/RequestAccess/Index', [
            'requests' => $requests,
            'filters' => $request->only(['search', 'status', 'type', 'page']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $currentUser = Auth::user();
        
        // Get available modules from config
        $modules = config('modules', []);
        
        // Get all users for "existing user" selection (for managers/HR)
        $isManager = $this->isDepartmentManager($currentUser->id);
        $isAdmin = $currentUser->is_admin;
        
        // Get departments
        $departments = Department::active()->select('id', 'name', 'code')->get();

        // If manager/admin, they can request for new users (Flow 2)
        $canRequestNewUser = $this->canCreateNewUser($currentUser);

        // Get users for existing user request:
        // - Admin: all active users
        // - Manager: users in managed departments (OPS manager includes INV/RSC/ADL)
        // - Regular: users in their own department
        $usersQuery = User::select('id', 'name', 'email', 'department_id')
            ->where('status', 'active');

        if ($isAdmin || $this->canViewAllRequests($currentUser)) {
            // no extra filter
        } elseif ($isManager) {
            $managedDeptIds = $this->getManagedDepartmentIds($currentUser->id);
            $usersQuery->whereIn('department_id', $managedDeptIds);
        } else {
            $usersQuery->where('department_id', $currentUser->department_id);
        }

        $users = $usersQuery
            ->orderBy('name')
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'department_id' => $user->department_id,
                ];
            });

        return Inertia::render('GMISL/Utility/RequestAccess/Create', [
            'modules' => $modules,
            'departments' => $departments,
            'users' => $users,
            'canRequestNewUser' => $canRequestNewUser,
            'currentUser' => [
                'id' => $currentUser->id,
                'name' => $currentUser->name,
                'department_id' => $currentUser->department_id,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:existing_user,new_user',
            'user_id' => 'required_if:type,existing_user|nullable|exists:users,id',
            'target_user_name' => 'required_if:type,new_user|nullable|string|max:255',
            'target_user_email' => 'required_if:type,new_user|nullable|email|max:255',
            'target_department_id' => 'required_if:type,new_user|nullable|exists:departments,id',
            'module_keys' => 'required|array|min:1',
            'module_keys.*' => 'required|string|max:255',
            'reason' => 'required|string|min:10',
        ]);

        $currentUserId = Auth::id();

        // Create request
        $requestAccess = RequestAccess::create([
            'request_number' => RequestAccess::generateRequestNumber(),
            'type' => $data['type'],
            'user_id' => $data['type'] === 'existing_user' ? $data['user_id'] : null,
            'target_user_name' => $data['type'] === 'new_user' ? $data['target_user_name'] : null,
            'target_user_email' => $data['type'] === 'new_user' ? $data['target_user_email'] : null,
            'target_department_id' => $data['type'] === 'new_user' ? $data['target_department_id'] : null,
            'module_keys' => $data['module_keys'],
            'reason' => $data['reason'],
            'status' => 'pending',
            'created_by' => $currentUserId,
        ]);

        // Activity Log for Create
        $this->logActivity(
            'request_accesses',
            $requestAccess->id,
            'created',
            null,
            $data,
            'Created request access: ' . $requestAccess->request_number
        );

        return $this->redirectToRememberedIndex($request, 'request-access', 'request-access.index')
            ->with('success', 'Request submitted successfully. Waiting for manager approval.');
    }

    /**
     * Display the specified resource.
     */
    public function show(RequestAccess $requestAccess)
    {
        $currentUserId = Auth::id();
        $currentUser = User::find($currentUserId);
        
        $isAdmin = $currentUser && $currentUser->isAdmin();
        $canReview = $this->canReviewRequest($currentUserId, $requestAccess);
        $canProcess = $this->canProcessRequest($currentUserId, $requestAccess);

        // Load relationships
        $requestAccess->load(['user', 'targetDepartment', 'creator', 'reviewer', 'processor']);

        // Get available modules for the form (if needed)
        $modules = config('modules', []);
        
        // Get departments
        $departments = Department::active()->select('id', 'name', 'code')->get();

        return Inertia::render('GMISL/Utility/RequestAccess/Show', [
            'request' => $requestAccess,
            'authUser' => [
                'id' => $currentUserId,
                'name' => $currentUser->name,
                'email' => $currentUser->email,
            ],
            'canReview' => $canReview,
            'canProcess' => $canProcess,
            'isAdmin' => $isAdmin,
            'modules' => $modules,
            'departments' => $departments,
        ]);
    }

    /**
     * Manager approves the request.
     */
    public function approve(Request $request, RequestAccess $requestAccess)
    {
        $currentUserId = Auth::id();

        // Check if user can review
        if (!$this->canReviewRequest($currentUserId, $requestAccess)) {
            return redirect()->back()->withErrors(['error' => 'You are not authorized to approve this request.']);
        }

        $data = $request->validate([
            'review_notes' => 'nullable|string',
        ]);

        $oldData = $requestAccess->toArray();
        $requestAccess->update([
            'status' => 'approved',
            'reviewed_by' => $currentUserId,
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'],
        ]);

        // Activity Log for Approve
        $this->logActivity(
            'request_accesses',
            $requestAccess->id,
            'approved',
            $oldData,
            $requestAccess->toArray(),
            'Approved request access: ' . $requestAccess->request_number
        );

        return redirect()->back()->with('success', 'Request approved. Waiting for IT processing.');
    }

    /**
     * Manager rejects the request.
     */
    public function reject(Request $request, RequestAccess $requestAccess)
    {
        $currentUserId = Auth::id();
        $canReview = $this->canReviewRequest($currentUserId, $requestAccess);
        $canProcess = $this->canProcessRequest($currentUserId, $requestAccess);

        if (!$canReview && !$canProcess) {
            return redirect()->back()->withErrors(['error' => 'You are not authorized to reject this request.']);
        }

        $data = $request->validate([
            'review_notes' => 'required|string|min:5',
        ]);

        $oldData = $requestAccess->toArray();
        $requestAccess->update([
            'status' => 'rejected',
            'reviewed_by' => $currentUserId,
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'],
        ]);

        // Activity Log for Reject
        $this->logActivity(
            'request_accesses',
            $requestAccess->id,
            'rejected',
            $oldData,
            $requestAccess->toArray(),
            'Rejected request access: ' . $requestAccess->request_number
        );

        return redirect()->back()->with('success', 'Request rejected.');
    }

    /**
     * IT processes the approved request.
     */
    public function process(Request $request, RequestAccess $requestAccess)
    {
        $currentUserId = Auth::id();

        // Check if user can process
        if (!$this->canProcessRequest($currentUserId, $requestAccess)) {
            return redirect()->back()->withErrors(['error' => 'You are not authorized to process this request.']);
        }

        // Only process approved requests
        if ($requestAccess->status !== 'approved') {
            return redirect()->back()->withErrors(['error' => 'Only approved requests can be processed.']);
        }

        $data = $request->validate([
            'processing_notes' => 'nullable|string',
        ]);

        try {
            $moduleKeys = $this->normalizeModuleKeys($requestAccess->module_keys ?? []);
            
            if ($requestAccess->type === 'existing_user') {
                // Flow 1: Assign permission to existing user
                $user = User::find($requestAccess->user_id);
                if (!$user) {
                    return redirect()->back()->withErrors(['error' => 'User not found.']);
                }

                // Create module permissions for each module key
                foreach ($moduleKeys as $moduleKey) {
                    ModulePermission::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'module_key' => $moduleKey,
                        ],
                        []
                    );
                }

                $message = 'Permissions assigned to existing user.';
            } else {
                // Flow 2: Create new user and assign permission
                // Check if email already exists
                if (User::where('email', $requestAccess->target_user_email)->exists()) {
                    return redirect()->back()->withErrors(['error' => 'A user with this email already exists.']);
                }

                // Create new user
                $newUser = User::create([
                    'name' => $requestAccess->target_user_name,
                    'email' => $requestAccess->target_user_email,
                    'account' => strtolower(str_replace(' ', '', $requestAccess->target_user_name)) . rand(100, 999),
                    'password' => Hash::make(Str::random(12)),
                    'status' => 'active',
                    'department_id' => $requestAccess->target_department_id,
                    'user_created' => 'system',
                ]);

                // Create module permissions for each module key
                foreach ($moduleKeys as $moduleKey) {
                    ModulePermission::updateOrCreate(
                        [
                            'user_id' => $newUser->id,
                            'module_key' => $moduleKey,
                        ],
                        []
                    );
                }

                // TODO: Send activation email (can be implemented later)
                // For now, just mark as processed
                
                $message = 'New user created and permissions assigned.';
            }

            $oldData = $requestAccess->toArray();
            // Update request status
            $requestAccess->update([
                'status' => 'processed',
                'processed_by' => $currentUserId,
                'processed_at' => now(),
                'processing_notes' => $data['processing_notes'],
            ]);

            // Activity Log for Process
            $this->logActivity(
                'request_accesses',
                $requestAccess->id,
                'processed',
                $oldData,
                $requestAccess->toArray(),
                'Processed request access: ' . $requestAccess->request_number
            );

            return redirect()->back()->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Error processing request: ' . $e->getMessage()]);
        }
    }

    /**
     * Check if user is a manager of any department
     */
    protected function isDepartmentManager($userId)
    {
        return $this->accessRules()->isManager($userId);
    }

    /**
     * Get department IDs that user manages
     */
    protected function getManagedDepartmentIds($userId)
    {
        return $this->accessRules()->visibleDepartmentIds($userId, self::ACCESS_MODULE, 'review');
    }

    protected function canViewAllRequests($user): bool
    {
        return $this->accessRules()->canViewAllDepartments($user, self::ACCESS_MODULE, 'view_list');
    }

    protected function canCreateNewUser($user): bool
    {
        return $this->accessRules()->allows($user, self::ACCESS_MODULE, 'create_new_user');
    }

    protected function canReviewRequest($userId, RequestAccess $requestAccess): bool
    {
        $targetDepartmentId = (int) optional($requestAccess->creator)->department_id;
        if ($targetDepartmentId <= 0 && $requestAccess->created_by) {
            $targetDepartmentId = (int) User::where('id', $requestAccess->created_by)->value('department_id');
        }

        return $this->accessRules()->canAccessDepartment($userId, self::ACCESS_MODULE, 'review', $targetDepartmentId);
    }

    protected function canProcessRequest($userId, RequestAccess $requestAccess): bool
    {
        if ($requestAccess->status !== 'approved') {
            return false;
        }

        return $this->accessRules()->allows($userId, self::ACCESS_MODULE, 'process');
    }

    protected function normalizeModuleKeys(array $keys): array
    {
        return collect($keys)
            ->filter(fn($key) => is_string($key))
            ->map(fn($key) => trim($key))
            ->filter(fn($key) => $key !== '')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Get list of users for manager to select (for existing user requests)
     */
    public function getUsers()
    {
        $currentUserId = Auth::id();
        $isManager = $this->isDepartmentManager($currentUserId);
        $isAdmin = Auth::user()->isAdmin();

        $query = User::select('id', 'name', 'email', 'department_id');

        if ($isManager && !$isAdmin) {
            $managedDeptIds = $this->getManagedDepartmentIds($currentUserId);
            $query->whereIn('department_id', $managedDeptIds);
        }

        $users = $query->where('status', 'active')->get();

        return response()->json(['users' => $users]);
    }

    /**
     * Helper function to log activity
     */
    private function logActivity($tableName, $recordId, $action, $oldValues, $newValues, $description)
    {
        $user = Auth::user();
        
        ActivityLog::create([
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'ip_address' => request()->ip(),
            'created_date' => now(),
            'description' => $description,
        ]);
    }
}
