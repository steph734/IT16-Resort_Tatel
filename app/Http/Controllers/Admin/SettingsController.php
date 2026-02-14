<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Package;
use App\Models\Amenity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\Audit_Log;

class SettingsController extends Controller
{
    /**
     * Display the settings page
     */
    public function index()
    {
        // Show different user lists depending on who is viewing settings:
        // - owner: see admins, staff, and owner (so owner can see themselves)
        // - admin/staff: see only admin and staff (do not see owner accounts)
        $viewerRole = Auth::user()->role ?? '';

        if ($viewerRole === 'owner') {
            $users = User::whereIn('role', ['admin', 'staff', 'owner'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $users = User::whereIn('role', ['admin', 'staff'])
                ->orderBy('created_at', 'desc')
                ->get();
        }
        $packages = Package::orderBy('created_at', 'desc')->get();
        $amenities = Amenity::orderBy('display_order', 'asc')->orderBy('name', 'asc')->get();
        
        return view('admin.settings', compact('users', 'packages', 'amenities'));
    }

    /**
     * Display the accounts page
     */
    public function accounts()
    {
        $viewerRole = Auth::user()->role ?? '';

        if ($viewerRole === 'owner') {
            $users = User::whereIn('role', ['admin', 'staff', 'owner'])
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            $users = User::whereIn('role', ['admin', 'staff'])
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        return view('admin.accounts', compact('users'));
    }

    /**
     * Display the list management page
     */
    public function listManagement()
    {
        $packages = Package::orderBy('created_at', 'desc')->get();
        $amenities = Amenity::orderBy('display_order', 'asc')->orderBy('name', 'asc')->get();
        
        return view('admin.list-management', compact('packages', 'amenities'));
    }

    /**
     * Store a new account
     */
    public function storeAccount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'gender' => 'required|in:Male,Female',
            'address' => 'required|string|max:500',
            'role' => 'required|in:admin,staff',
            'email' => 'required|email|max:255|unique:users,email',
            // Require exactly 12 characters for passwords
            'password' => 'required|string|min:12|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'middle_name' => $request->middle_name,
                'gender' => $request->gender,
                'address' => $request->address,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'status' => 'active'
            ]);

            // Audit log: account created
            try {
                Audit_Log::create([
                    'user_id' => Auth::user()->user_id ?? null,
                    'action' => 'Create Account',
                    'description' => 'Created account ' . ($user->user_id ?? 'unknown') . ' (' . ($user->email ?? 'no-email') . ') role: ' . ($user->role ?? 'n/a'),
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Exception $e) {
                // don't break main flow if logging fails
            }

            return response()->json([
                'success' => true,
                'message' => 'Account created successfully!',
                'user' => [
                    'user_id' => $user->user_id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'middle_name' => $user->middle_name,
                    'name' => $user->name,
                    'gender' => $user->gender,
                    'address' => $user->address,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'created_at' => $user->created_at->format('M d, Y') . ' at ' . $user->created_at->format('g:i A')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create account. Please try again.'
            ], 500);
        }
    }

    /**
     * Update an existing account
     */
    public function updateAccount(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'gender' => 'required|in:Male,Female',
            'address' => 'required|string|max:500',
            'email' => 'required|email|max:255|unique:users,email,' . $userId . ',user_id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::where('user_id', $userId)->firstOrFail();
            $user->update([
                'name' => $request->first_name . ' ' . $request->last_name,
                'middle_name' => $request->middle_name,
                'gender' => $request->gender,
                'address' => $request->address,
                'email' => $request->email,
            ]);

            // Audit log: account updated
            try {
                Audit_Log::create([
                    'user_id' => Auth::user()->user_id ?? null,
                    'action' => 'Update Account',
                    'description' => 'Updated account ' . ($user->user_id ?? $userId) . ' (' . ($user->email ?? 'no-email') . ')',
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Exception $e) {
                // ignore logging errors
            }

            return response()->json([
                'success' => true,
                'message' => 'Account updated successfully!',
                'user' => [
                    'user_id' => $user->user_id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'created_at' => $user->created_at->format('M d, Y') . ' at ' . $user->created_at->format('g:i A')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update account. Please try again.'
            ], 500);
        }
    }

    /**
     * Update account status
     */
   public function updateAccountStatus(Request $request, $userId)
{
    // Validate the requested status
    $validator = Validator::make($request->all(), [
        'status' => 'required|in:active,disabled',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid status value. Must be "active" or "disabled".',
        ], 422);
    }

    try {
        // Retrieve user using the custom 'user_id' column
        $user = User::where('user_id', $userId)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Account not found.',
            ], 404);
        }

        $newStatus = $request->status;

        // Safety check: Prevent disabling the last active admin/owner
        if ($newStatus === 'disabled' && in_array($user->role, ['admin', 'owner'])) {
            $activeAdmins = User::whereIn('role', ['admin', 'owner'])
                ->where('status', 'active')
                ->where('user_id', '!=', $userId)
                ->count();

            if ($activeAdmins === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot disable the last active admin account. At least one active admin (or owner) must remain.',
                ], 403);
            }
        }

        // Perform the status update
        $user->status = $newStatus;
        $user->save();

        // Audit log: account status changed
        try {
            Audit_Log::create([
                'user_id' => Auth::user()->user_id ?? null,
                'action' => 'Update Account Status',
                'description' => 'Changed status of ' . $user->user_id . ' to ' . $newStatus,
                'ip_address' => $request->ip(),
            ]);
        } catch (\Exception $e) {
            // ignore logging errors
        }

        // Dynamic success message based on action
        $action = $newStatus === 'active' ? 'activated' : 'disabled';

        return response()->json([
            'success' => true,
            'message' => "Account {$action} successfully!",
            'status' => $newStatus,
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'An unexpected error occurred. Please try again later.',
        ], 500);
    }
}

    /**
     * Delete an amenity
     */
    public function deleteAmenity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $amenity = Amenity::where('name', $request->name)->first();
            
            if (!$amenity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Amenity not found.'
                ], 404);
            }

            // Prevent deletion of default amenities
            if ($amenity->is_default) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete default amenities.'
                ], 403);
            }

            $amenity->delete();

            // Audit log: amenity deleted
            try {
                Audit_Log::create([
                    'user_id' => Auth::user()->user_id ?? null,
                    'action' => 'Delete Amenity',
                    'description' => 'Deleted amenity: ' . $amenity->name,
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Exception $e) {
                // ignore logging errors
            }

            return response()->json([
                'success' => true,
                'message' => 'Amenity deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete amenity. Please try again.'
            ], 500);
        }
    }

    /**
     * Add a new custom amenity
     */
    public function addAmenity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100|unique:amenities,name'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('name')
            ], 422);
        }

        try {
            // Get the highest display_order and add 1
            $maxOrder = Amenity::max('display_order') ?? 0;
            
            $amenity = Amenity::create([
                'name' => $request->name,
                'is_default' => false,
                'display_order' => $maxOrder + 1
            ]);

            // Audit log: amenity added
            try {
                Audit_Log::create([
                    'user_id' => Auth::user()->user_id ?? null,
                    'action' => 'Add Amenity',
                    'description' => 'Added amenity: ' . $amenity->name,
                    'ip_address' => $request->ip(),
                ]);
            } catch (\Exception $e) {
                // ignore logging errors
            }

            return response()->json([
                'success' => true,
                'message' => 'Amenity added successfully!',
                'amenity' => $amenity
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add amenity. Please try again.'
            ], 500);
        }
    }
}
