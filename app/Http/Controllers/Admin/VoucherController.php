<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    /**
     * Display a listing of vouchers.
     */
    public function index(Request $request)
    {
        $query = Voucher::query();

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Search by code
        if ($request->has('search')) {
            $query->where('code', 'like', '%' . $request->search . '%');
        }

        $vouchers = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $vouchers
        ]);
    }

    /**
     * Store a newly created voucher.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:255|unique:vouchers,code',
            'type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|integer|min:1',
            'min_purchase' => 'nullable|integer|min:0',
            'max_discount' => 'nullable|integer|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_user' => 'nullable|integer|min:1',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:valid_from',
            'description' => 'nullable|string',
        ]);

        // Uppercase code
        $code = strtoupper($request->code);

        $voucher = Voucher::create([
            'code' => $code,
            'type' => $request->type,
            'discount_value' => $request->discount_value,
            'min_purchase' => $request->min_purchase ?? 0,
            'max_discount' => $request->max_discount,
            'usage_limit' => $request->usage_limit,
            'usage_count' => 0,
            'usage_per_user' => $request->usage_per_user ?? 1,
            'valid_from' => $request->valid_from,
            'valid_until' => $request->valid_until,
            'is_active' => true,
            'description' => $request->description,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Voucher created successfully',
            'data' => $voucher
        ], 201);
    }

    /**
     * Display the specified voucher.
     */
    public function show($id)
    {
        $voucher = Voucher::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $voucher
        ]);
    }

    /**
     * Update the specified voucher.
     */
    public function update(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);

        $request->validate([
            'code' => 'sometimes|required|string|max:255|unique:vouchers,code,' . $id,
            'type' => 'sometimes|required|in:percentage,fixed',
            'discount_value' => 'sometimes|required|integer|min:1',
            'min_purchase' => 'nullable|integer|min:0',
            'max_discount' => 'nullable|integer|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_user' => 'nullable|integer|min:1',
            'valid_from' => 'sometimes|required|date',
            'valid_until' => 'sometimes|required|date|after_or_equal:valid_from',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $data = $request->all();
        
        // Uppercase code if provided
        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        $voucher->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Voucher updated successfully',
            'data' => $voucher
        ]);
    }

    /**
     * Remove the specified voucher.
     */
    public function destroy($id)
    {
        $voucher = Voucher::findOrFail($id);
        $voucher->delete();

        return response()->json([
            'success' => true,
            'message' => 'Voucher deleted successfully'
        ]);
    }

    /**
     * Validate voucher code (for customer use)
     */
    public function validate(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'cart_total' => 'required|integer|min:0',
        ]);

        $voucher = Voucher::where('code', strtoupper($request->code))->first();

        if (!$voucher) {
            return response()->json([
                'success' => false,
                'message' => 'Voucher not found'
            ], 404);
        }

        $userId = auth()->id();
        $validation = $voucher->canBeUsedBy($userId, $request->cart_total);

        if (!$validation['valid']) {
            return response()->json([
                'success' => false,
                'message' => $validation['message']
            ], 400);
        }

        $discountAmount = $voucher->calculateDiscount($request->cart_total);

        return response()->json([
            'success' => true,
            'message' => 'Voucher is valid',
            'data' => [
                'voucher' => $voucher,
                'discount_amount' => $discountAmount,
                'final_total' => $request->cart_total - $discountAmount,
                'display_text' => $voucher->getDisplayText()
            ]
        ]);
    }
}
