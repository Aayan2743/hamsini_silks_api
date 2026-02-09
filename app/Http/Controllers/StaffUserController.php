<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Validator;

class StaffUserController extends Controller
{
    /**
     * List Staff (pagination + search)
     */
    public function index(Request $request)
    {
        $search  = $request->get('search');
        $perPage = $request->get('per_page', 10);

        $query = User::where('role', 'employee');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%");
            });
        }

        $users = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $users->items(),
            'meta'    => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'total'        => $users->total(),
            ],
        ]);
    }

    /**
     * Create Staff
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'phone'    => 'required|string|unique:users,phone',
            'password' => 'required|min:6',
            // 'role'     => 'required|in:employee,employeer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error'   => $validator->errors()->first(),
            ], 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            'role'     => 'employee',

        ]);

        return response()->json([
            'success' => true,
            'message' => 'Staff created successfully',
            'data'    => $user,
        ], 201);
    }

    /**
     * Show Staff
     */
    public function show($id)
    {
        $user = User::where('role', 'employee')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $user,
        ]);
    }

    /**
     * Update Staff
     */
    public function update(Request $request, $id)
    {
        $user = User::where('role', 'employee')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone' => [
                'required',
                Rule::unique('users', 'phone')->ignore($user->id),
            ],
            'role'  => 'required|in:employee,employeer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error'   => $validator->errors()->first(),
            ], 422);
        }

        $user->update($request->only('name', 'email', 'phone', 'role'));

        return response()->json([
            'success' => true,
            'message' => 'Staff updated successfully',
            'data'    => $user,
        ]);
    }

    /**
     * Soft Delete Staff
     */
    public function destroy($id)
    {
        $user = User::where('role', 'employee')->findOrFail($id);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Staff deleted successfully',
        ]);
    }
}
