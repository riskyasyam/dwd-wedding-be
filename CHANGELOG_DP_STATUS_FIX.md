# Changelog - DP Payment Status Fix

**Date:** December 14, 2025  
**Priority:** 🔥 CRITICAL FIX

---

## 🐛 Problems Fixed

### 1. Midtrans Snap Showing Wrong Amount
**Issue:** Ketika user pilih "Bayar DP", Midtrans Snap popup menampilkan nominal FULL bukan nominal DP.

**Root Cause:** Backend mengirim semua item details dari cart ke Midtrans dengan harga penuh, sehingga total tidak match dengan DP amount.

**Solution:**
- Saat `payment_type = "dp"`, hanya kirim 1 item dengan nama "Down Payment (DP)" dan price = dp_amount
- Saat `payment_type = "full"`, kirim detail semua items + discounts
- Snap token disimpan ke field yang sesuai (`dp_snap_token` atau `snap_token`)

**Files Changed:**
- `app/Http/Controllers/Customer/OrderController.php` - Method `checkout()` line 298-356

---

### 2. Order Status Always 'paid' Instead of 'dp_paid'
**Issue:** Setelah customer bayar DP, order status langsung menjadi `'paid'` padahal seharusnya `'dp_paid'`. Akibatnya customer tidak bisa bayar sisa karena validasi error "DP has not been paid yet".

**Root Cause:** 
- Method `checkPaymentStatus()` tidak membedakan status berdasarkan `payment_type`
- Ketika Midtrans callback dengan status "settlement" atau "capture", backend langsung set status = 'paid'

**Solution:**
- Detect apakah ini DP payment atau full payment
- Detect apakah ini remaining payment (suffix `-REMAINING`)
- Set status yang sesuai:
  - DP payment → status = `'dp_paid'`, set `dp_paid_at`
  - Remaining payment → status = `'paid'`, set `full_paid_at`, clear `remaining_amount`
  - Full payment → status = `'paid'`, set `full_paid_at`

**Files Changed:**
- `app/Http/Controllers/Customer/OrderController.php` - Method `checkPaymentStatus()` line 400-460

---

### 3. Missing `minimum_dp_percentage` Column
**Issue:** Error SQL "Column not found: 1054 Unknown column 'minimum_dp_percentage'" ketika update decoration atau load landing page.

**Root Cause:** Migration `2025_12_14_042346_add_dp_fields_to_decorations_and_orders_tables` tidak ter-execute dengan benar.

**Solution:**
- Reset migration record dari database
- Re-run migration untuk menambahkan column `minimum_dp_percentage` ke tabel `decorations`
- Update migration rollback untuk handle column yang belum ada

**Files Changed:**
- `database/migrations/2025_12_14_042346_add_dp_fields_to_decorations_and_orders_tables.php`

---

### 4. Missing 'dp_paid' and 'processing' in Status Enum
**Issue:** Database enum status hanya punya: `pending`, `paid`, `failed`, `completed`, `cancelled`. Tidak ada `dp_paid` dan `processing`.

**Root Cause:** Enum status belum diupdate untuk mendukung DP workflow.

**Solution:**
- Buat migration baru untuk ALTER enum status
- Tambahkan `'dp_paid'` dan `'processing'` ke enum values
- Update existing orders dengan `payment_type = 'dp'` dan `remaining_amount > 0` ke status `'dp_paid'`

**Files Changed:**
- `database/migrations/2025_12_14_055918_alter_orders_status_enum_add_dp_paid.php` (NEW)

---

### 5. Missing `snap_token` Field
**Issue:** Field untuk menyimpan snap token full payment tidak ada di database.

**Solution:**
- Buat migration untuk menambahkan field `snap_token`
- Update Order model untuk include `snap_token` di fillable array

**Files Changed:**
- `database/migrations/2025_12_14_053040_add_snap_token_to_orders_table.php` (NEW)
- `app/Models/Order.php`

---

### 6. Admin OrderController Not Supporting DP Status
**Issue:** Admin controller belum mendukung status `'dp_paid'` dan `'processing'`.

**Solution:**
- Update validation di `updateStatus()` method untuk include `'dp_paid'` dan `'processing'`
- Update `statistics()` method untuk count DP orders dan revenue
- Tambahkan metrics: `dp_paid_orders`, `processing_orders`, `dp_revenue`

**Files Changed:**
- `app/Http/Controllers/Admin/OrderController.php`

---

## 📦 New Migrations

### 1. Add DP Fields (Updated)
**File:** `2025_12_14_042346_add_dp_fields_to_decorations_and_orders_tables.php`

**Decorations Table:**
- `minimum_dp_percentage` (integer, default: 30) - Minimum DP percentage

**Orders Table:**
- `payment_type` (enum: 'full', 'dp', default: 'full')
- `dp_amount` (bigInteger, default: 0)
- `remaining_amount` (bigInteger, default: 0)
- `dp_paid_at` (timestamp, nullable)
- `full_paid_at` (timestamp, nullable)
- `snap_token` (string, nullable) - NEW!
- `dp_snap_token` (string, nullable)
- `remaining_snap_token` (string, nullable)

### 2. Add Snap Token Field
**File:** `2025_12_14_053040_add_snap_token_to_orders_table.php`

**Orders Table:**
- `snap_token` (string, nullable) - For full payment snap token

### 3. Alter Status Enum
**File:** `2025_12_14_055918_alter_orders_status_enum_add_dp_paid.php`

**Orders Table:**
- Update status enum from: `['pending', 'paid', 'failed', 'completed', 'cancelled']`
- To: `['pending', 'dp_paid', 'paid', 'processing', 'failed', 'completed', 'cancelled']`

---

## 🔄 Status Flow (Updated)

### Before Fix (WRONG ❌):
```
Customer checkout DP → Midtrans payment → Status = 'paid' → Customer cannot pay remaining
```

### After Fix (CORRECT ✅):

#### Full Payment Flow:
```
pending → (pay full amount) → paid → processing → completed
```

#### DP Payment Flow:
```
pending 
  ↓ (customer pay DP)
dp_paid (DP paid, remaining > 0)
  ↓ (customer pay remaining)
paid (fully paid)
  ↓ (admin process)
processing
  ↓ (admin complete)
completed
```

---

## 🔧 Backend Logic Changes

### checkPaymentStatus() Method

**Before:**
```php
if ($transactionStatus == 'settlement') {
    $order->status = 'paid';  // ❌ Always paid
    $order->payment_method = $status->payment_type;
}
```

**After:**
```php
if ($transactionStatus == 'settlement') {
    // Determine status based on payment type
    if ($isRemainingPayment) {
        // This is remaining payment - set to paid
        $order->status = 'paid';
        $order->full_paid_at = now();
        $order->remaining_amount = 0;
    } else if ($order->payment_type === 'dp' && $order->remaining_amount > 0) {
        // This is DP payment - set to dp_paid
        $order->status = 'dp_paid';
        $order->dp_paid_at = now();
    } else {
        // This is full payment
        $order->status = 'paid';
        $order->full_paid_at = now();
    }
    $order->payment_method = $status->payment_type;
}
```

### checkout() Method

**Before:**
```php
$params['item_details'] = $cart->items->map(function ($item) {
    return [
        'id' => $item->decoration_id,
        'price' => (int) $item->price,  // ❌ Always full price
        'quantity' => $item->quantity,
        'name' => $item->decoration->name,
    ];
})->toArray();
```

**After:**
```php
// For DP payment, use single item with DP amount
if ($paymentType === 'dp') {
    $params['item_details'] = [
        [
            'id' => $orderNumber,
            'price' => (int) $dpAmount,  // ✅ DP amount only
            'quantity' => 1,
            'name' => "Down Payment (DP) - Order #{$orderNumber}",
        ]
    ];
} else {
    // Full payment - show detailed items + discounts
    $params['item_details'] = $cart->items->map(/* ... */)->toArray();
    // Add voucher discount, delivery fee, etc.
}
```

---

## 📊 Admin Statistics (Updated)

### New Response Fields:

```json
{
  "success": true,
  "data": {
    "total_orders": 150,
    "pending_orders": 10,
    "dp_paid_orders": 15,        // ✨ NEW - Count of DP paid orders
    "paid_orders": 80,
    "processing_orders": 20,     // ✨ NEW - Count of processing orders
    "completed_orders": 20,
    "failed_orders": 3,
    "cancelled_orders": 2,
    "total_revenue": 1500000000,
    "dp_revenue": 150000000,     // ✨ NEW - Total DP amount collected
    "orders_this_month": 25,
    "revenue_this_month": 300000000
  }
}
```

---

## 🎯 Status Definitions

| Status | Label | Meaning | Action |
|--------|-------|---------|--------|
| `pending` | Pending | Order created, waiting for payment | Customer must pay |
| `dp_paid` | Belum Lunas / DP | DP paid, waiting for remaining | Customer can pay remaining |
| `paid` | Paid / Lunas | Fully paid | Admin can process |
| `processing` | Processing | Admin is processing order | Wait for completion |
| `completed` | Completed | Order completed and delivered | Done |
| `failed` | Failed | Payment failed or cancelled | Cannot continue |
| `cancelled` | Cancelled | Order cancelled | Done |

---

## 🧪 Testing Checklist

### Customer Flow:
- [x] Create order with `payment_type: "dp"`
- [x] Pay DP via Midtrans → Snap shows DP amount (not full)
- [x] After payment → Order status = `'dp_paid'` (not 'paid')
- [x] "Bayar Sisa" button appears in customer dashboard
- [x] Click "Bayar Sisa" → Snap shows remaining amount
- [x] After remaining payment → Status = `'paid'`, remaining_amount = 0
- [x] Test full payment → Snap shows full amount, status directly to 'paid'

### Admin Dashboard:
- [x] Filter orders by status `'dp_paid'` works
- [x] Statistics show `dp_paid_orders` count
- [x] Statistics show `dp_revenue` total
- [x] Can update order status to `'dp_paid'` or `'processing'`

### Landing Page:
- [x] Decorations load without SQL error
- [x] `minimum_dp_percentage` displayed correctly
- [x] Admin can set/update minimum DP percentage (10-100%)

---

## 📝 Migration Commands

To apply all fixes:

```bash
# Run all pending migrations
php artisan migrate

# If needed, reset migration state for DP fields:
php artisan tinker
DB::table('migrations')->where('migration', '2025_12_14_042346_add_dp_fields_to_decorations_and_orders_tables')->delete();
exit

php artisan migrate
```

Fix existing DP orders:

```bash
php artisan tinker
DB::table('orders')->where('payment_type', 'dp')->where('status', 'paid')->where('remaining_amount', '>', 0)->update(['status' => 'dp_paid']);
exit
```

---

## 🚀 Deployment Notes

1. **Backup database** before running migrations
2. Run migrations in sequence:
   - `2025_12_14_042346_add_dp_fields_to_decorations_and_orders_tables.php`
   - `2025_12_14_053040_add_snap_token_to_orders_table.php`
   - `2025_12_14_055918_alter_orders_status_enum_add_dp_paid.php`
3. Fix existing orders with DP payment (see command above)
4. Test payment flow thoroughly before going live
5. Monitor Midtrans callback logs for any issues

---

## 📚 Related Documentation

- [README_DP_PAYMENT.md](README_DP_PAYMENT.md) - Complete DP payment system documentation
- [BACKEND_DP_FIX_REQUIRED.md](../wo_dwd_fe/BACKEND_DP_FIX_REQUIRED.md) - Original bug report (amount issue)
- [BACKEND_DP_STATUS_FIX.md](../wo_dwd_fe/BACKEND_DP_STATUS_FIX.md) - Original bug report (status issue)

---

## 🔄 Additional Fix - Remaining Payment Order ID

### 7. Remaining Payment Duplicate Order ID (December 14, 2025)

**Issue:** Saat customer bayar sisa (remaining payment), Midtrans menolak request dengan error:
```
transaction_details.order_id sudah digunakan
```

**Root Cause:** Backend menggunakan order_id yang sama untuk DP payment dan remaining payment. Midtrans tidak mengizinkan duplicate order_id.

**Solution:**
- ✅ Method `payRemaining()` sudah menggunakan suffix `-REMAINING` pada order_id
- ✅ Method `checkPaymentStatus()` sudah detect suffix `-REMAINING` dan handle accordingly
- ✅ Added comprehensive logging untuk debug payment flow
- ✅ Enhanced error handling dengan detailed error messages
- ✅ Updated response format to include complete order information

**Files Enhanced:**
- `app/Http/Controllers/Customer/OrderController.php`
  - `payRemaining()` - Added detailed logging
  - `checkPaymentStatus()` - Added logging for all payment scenarios
  - Enhanced error responses with trace information
  - Complete order data in API responses

**Logging Added:**
```php
// DP Payment
Log::info('DP payment settled', [
    'order_number' => ...,
    'status' => 'dp_paid',
    'dp_amount' => ...,
    'remaining_amount' => ...
]);

// Remaining Payment
Log::info('Remaining payment settled', [
    'order_number' => ...,
    'midtrans_order_id' => ...,
    'status' => 'paid',
    'remaining_amount' => 0
]);

// Errors
Log::error('Error checking payment status', [
    'order_number' => ...,
    'error' => ...,
    'trace' => ...
]);
```

**Testing:**
- [x] DP payment creates order with suffix-less order_id
- [x] Remaining payment creates Midtrans transaction with `-REMAINING` suffix
- [x] Callback handler properly detects and processes remaining payment
- [x] Order status updated correctly: dp_paid → paid
- [x] Remaining amount cleared after remaining payment
- [x] Logs show detailed payment flow for debugging

---

## ✅ Summary

All critical issues with DP payment system have been resolved:

1. ✅ Midtrans Snap now shows correct DP amount (not full)
2. ✅ Order status correctly set to `'dp_paid'` after DP payment
3. ✅ Customer can pay remaining amount without errors
4. ✅ Remaining payment uses unique order_id (with `-REMAINING` suffix)
5. ✅ Payment callback handler properly detects and processes remaining payment
6. ✅ Admin dashboard supports DP status filtering and statistics
7. ✅ Database schema complete with all required fields
8. ✅ Landing page loads without SQL errors
9. ✅ Comprehensive logging for payment flow debugging

**Status:** Ready for Production ✨

**Monitoring:** Check logs with:
```bash
tail -f storage/logs/laravel.log | grep -E "payment|midtrans|DP|remaining"
```
