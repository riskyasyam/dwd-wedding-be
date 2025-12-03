<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request)
    {
        $query = User::where('role', 'customer');

        // Search by name or email
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // Filter by email verification status
        if ($request->has('verified')) {
            if ($request->verified == 'true' || $request->verified == '1') {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $customers = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    /**
     * Display the specified customer.
     */
    public function show($id)
    {
        $customer = User::where('role', 'customer')->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }

    /**
     * Update the specified customer.
     */
    public function update(Request $request, $id)
    {
        $customer = User::where('role', 'customer')->findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
        ]);

        $customer->update($request->only(['name', 'email']));

        return response()->json([
            'success' => true,
            'message' => 'Customer updated successfully',
            'data' => $customer
        ]);
    }

    /**
     * Remove the specified customer.
     */
    public function destroy($id)
    {
        $customer = User::where('role', 'customer')->findOrFail($id);
        $customer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Customer deleted successfully'
        ]);
    }

    /**
     * Get customer statistics.
     */
    public function statistics()
    {
        $totalCustomers = User::where('role', 'customer')->count();
        $verifiedCustomers = User::where('role', 'customer')->whereNotNull('email_verified_at')->count();
        $unverifiedCustomers = User::where('role', 'customer')->whereNull('email_verified_at')->count();
        $newCustomersThisMonth = User::where('role', 'customer')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_customers' => $totalCustomers,
                'verified_customers' => $verifiedCustomers,
                'unverified_customers' => $unverifiedCustomers,
                'new_customers_this_month' => $newCustomersThisMonth,
            ]
        ]);
    }
}
