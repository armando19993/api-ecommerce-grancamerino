<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();
        
        // Filtro por rol
        if ($request->has('is_admin')) {
            $query->where('is_admin', $request->boolean('is_admin'));
        }
        
        // Búsqueda por nombre o email
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Ordenamiento
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        $allowedSortFields = ['name', 'email', 'created_at', 'updated_at'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        // Paginación
        $perPage = $request->input('per_page', 20);
        $perPage = min($perPage, 100);
        
        $users = $query->paginate($perPage);
        
        return response()->json([
            'status' => 'success',
            'data' => $users
        ]);
    }

    /**
     * Display the specified user
     *
     * @param User $user
     * @return JsonResponse
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $user
        ]);
    }

    /**
     * Store a newly created user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'is_admin' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_admin' => $request->boolean('is_admin', false),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    /**
     * Update the specified user
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)
            ],
            'password' => 'sometimes|nullable|string|min:8|confirmed',
            'is_admin' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['name', 'email', 'is_admin']);
        
        // Solo actualizar password si se proporciona
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully',
            'data' => $user->fresh()
        ]);
    }

    /**
     * Remove the specified user
     *
     * @param User $user
     * @return JsonResponse
     */
    public function destroy(User $user): JsonResponse
    {
        // Prevenir que el usuario se elimine a sí mismo
        if (auth()->id() === $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot delete your own account'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
    }

    /**
     * Toggle admin status
     *
     * @param User $user
     * @return JsonResponse
     */
    public function toggleAdmin(User $user): JsonResponse
    {
        // Prevenir que el usuario se quite sus propios permisos de admin
        if (auth()->id() === $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'You cannot change your own admin status'
            ], 403);
        }

        $user->update([
            'is_admin' => !$user->is_admin
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Admin status updated successfully',
            'data' => $user->fresh()
        ]);
    }

    /**
     * Get user statistics
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_users' => User::count(),
            'total_admins' => User::where('is_admin', true)->count(),
            'total_customers' => User::where('is_admin', false)->count(),
            'recent_users' => User::where('created_at', '>=', now()->subDays(30))->count(),
            'users_with_orders' => User::has('orders')->count(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $stats
        ]);
    }

    /**
     * Get user's orders
     *
     * @param User $user
     * @return JsonResponse
     */
    public function orders(User $user): JsonResponse
    {
        $orders = $user->orders()
            ->with(['items.product', 'items.productVariant.size'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    /**
     * Get user's addresses
     *
     * @param User $user
     * @return JsonResponse
     */
    public function addresses(User $user): JsonResponse
    {
        $addresses = $user->addresses()
            ->orderBy('is_primary', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $addresses
        ]);
    }

    /**
     * Get user's favorites
     *
     * @param User $user
     * @return JsonResponse
     */
    public function favorites(User $user): JsonResponse
    {
        $favorites = $user->favorites()
            ->with(['category', 'team', 'images', 'variants.size'])
            ->orderBy('favorites.created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'status' => 'success',
            'data' => $favorites
        ]);
    }

    /**
     * Bulk delete users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'required|uuid|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Prevenir que el usuario se elimine a sí mismo
        $userIds = array_filter($request->user_ids, function($id) {
            return $id !== auth()->id();
        });

        if (empty($userIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete your own account'
            ], 403);
        }

        $deletedCount = User::whereIn('id', $userIds)->delete();

        return response()->json([
            'status' => 'success',
            'message' => "{$deletedCount} users deleted successfully",
            'data' => [
                'deleted_count' => $deletedCount,
                'requested_count' => count($request->user_ids),
                'skipped_count' => count($request->user_ids) - $deletedCount
            ]
        ]);
    }

    /**
     * Update user password (admin can change any user's password)
     *
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function updatePassword(Request $request, User $user): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password updated successfully'
        ]);
    }
}
