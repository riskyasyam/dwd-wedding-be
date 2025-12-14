# Testing Polling Manual untuk Remaining Payment

## 🧪 Cara Test di Postman/Browser

### 1. Simulasi Flow Lengkap

#### Step 1: Checkout dengan DP
```http
POST http://localhost:8000/api/customer/orders/checkout
Authorization: Bearer {your_token}
Content-Type: application/json

{
  "payment_type": "dp",
  "first_name": "Test",
  "last_name": "User",
  "email": "test@example.com",
  "phone": "081234567890",
  "address": "Jl. Test No. 123",
  "city": "Jakarta",
  "district": "Jakarta Selatan",
  "sub_district": "Kebayoran Baru",
  "postal_code": "12345",
  "items": [
    {
      "decoration_id": 1,
      "quantity": 1,
      "event_date": "2025-12-20"
    }
  ]
}
```

**Response akan berisi `snap_token` untuk bayar DP**

---

#### Step 2: Bayar DP di Midtrans Snap
- Buka Snap dengan snap_token dari response
- Bayar dengan test card (sandbox)
- Setelah success, frontend harus polling

---

#### Step 3: Polling untuk DP Payment
```http
GET http://localhost:8000/api/customer/orders/payment-status/{order_number}
Authorization: Bearer {your_token}
```

**Expected Response setelah DP dibayar:**
```json
{
  "success": true,
  "data": {
    "order_status": "dp_paid",
    "transaction_status": "settlement",
    "order": {
      "status": "dp_paid",
      "dp_paid_at": "2025-12-14 15:00:00",
      "remaining_amount": 25200000,
      "remaining_paid_at": null,
      "full_paid_at": null
    }
  }
}
```

---

#### Step 4: Bayar Remaining
```http
POST http://localhost:8000/api/customer/orders/{order_id}/pay-remaining
Authorization: Bearer {your_token}
```

**Response akan berisi `remaining_snap_token`**

---

#### Step 5: Bayar di Midtrans Snap
- Buka Snap dengan remaining_snap_token
- Order ID akan format: `ORD-XXX-REMAINING-{timestamp}`
- Bayar dengan test card
- Setelah success, **WAJIB POLLING!**

---

#### Step 6: Polling untuk Remaining Payment ⭐ CRITICAL!
```http
GET http://localhost:8000/api/customer/orders/payment-status/ORD-1765696107-REMAINING-1702546789
Authorization: Bearer {your_token}
```

**IMPORTANT:** Order number harus include suffix `-REMAINING-{timestamp}`!

**Expected Response setelah Remaining dibayar:**
```json
{
  "success": true,
  "data": {
    "order_number": "ORD-1765696107-REMAINING-1702546789",
    "actual_order_number": "ORD-1765696107-167909",
    "is_remaining_payment": true,
    "order_status": "paid",
    "transaction_status": "settlement",
    "order": {
      "status": "paid",
      "remaining_amount": 0,
      "dp_paid_at": "2025-12-14 15:00:00",
      "remaining_paid_at": "2025-12-14 15:10:00", ✅ TERISI!
      "full_paid_at": "2025-12-14 15:10:00" ✅ TERISI!
    }
  }
}
```

---

## 🔍 Check Database

Setelah polling, cek database untuk pastikan data terupdate:

```sql
SELECT 
    order_number,
    status,
    payment_type,
    dp_amount,
    remaining_amount,
    dp_paid_at,
    remaining_paid_at,
    full_paid_at
FROM orders 
WHERE order_number = 'ORD-1765696107-167909';
```

**Expected Result:**
```
order_number           | status | remaining_amount | dp_paid_at          | remaining_paid_at   | full_paid_at
----------------------|--------|------------------|---------------------|---------------------|-------------------
ORD-1765696107-167909 | paid   | 0                | 2025-12-14 15:00:00 | 2025-12-14 15:10:00 | 2025-12-14 15:10:00
```

Semua field harus terisi! ✅

---

## 📊 Check Logs

Monitor logs untuk lihat apakah polling berhasil update:

```powershell
Get-Content storage/logs/laravel.log -Tail 100 | Select-String -Pattern "Remaining payment settled"
```

**Expected Log Entry:**
```
[2025-12-14 15:10:00] local.INFO: Remaining payment settled via manual_check 
{
  "order_number": "ORD-1765696107-167909",
  "status": "paid",
  "remaining_amount": 0,
  "remaining_paid_at": "2025-12-14 15:10:00",
  "full_paid_at": "2025-12-14 15:10:00"
}
```

---

## ⚠️ Common Issues

### Issue 1: `remaining_paid_at` masih NULL
**Cause:** Frontend tidak polling atau polling dengan order number yang salah

**Solution:** 
- Pastikan frontend polling dengan order number yang LENGKAP (include `-REMAINING-{timestamp}`)
- Contoh: `ORD-XXX-REMAINING-1702546789` (BENAR)
- Bukan: `ORD-XXX` (SALAH)

---

### Issue 2: Polling return "pending" padahal sudah bayar
**Cause:** Midtrans settlement butuh waktu (biasanya instant di sandbox, tapi bisa delay)

**Solution:**
- Polling beberapa kali dengan delay 2-3 detik
- Biasanya maksimal 3-5 kali polling sudah settlement

---

### Issue 3: Order tidak ditemukan saat polling
**Cause:** Order number salah atau typo

**Solution:**
- Double check order number dari response `pay-remaining`
- Pastikan format: `ORD-{timestamp1}-{timestamp2}-REMAINING-{timestamp3}`

---

## 🎯 Frontend Integration Example

```javascript
// Saat customer klik "Bayar Sisa"
async function payRemaining(orderId) {
  try {
    // Request snap token
    const response = await fetch(`/api/customer/orders/${orderId}/pay-remaining`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
    
    const data = await response.json();
    
    if (data.success) {
      const snapToken = data.snap_token;
      const remainingOrderNumber = data.remaining_order_number; // Save this!
      
      // Open Midtrans Snap
      snap.pay(snapToken, {
        onSuccess: async function(result) {
          console.log('✅ Payment success:', result);
          
          // CRITICAL: Poll untuk update database
          await pollPaymentStatus(remainingOrderNumber);
        },
        
        onPending: function(result) {
          console.log('⏳ Payment pending:', result);
        },
        
        onError: function(result) {
          console.log('❌ Payment error:', result);
          alert('Pembayaran gagal!');
        },
        
        onClose: function() {
          console.log('Snap closed');
        }
      });
    }
    
  } catch (error) {
    console.error('Error:', error);
  }
}

// Polling function
async function pollPaymentStatus(orderNumber) {
  let attempts = 0;
  const maxAttempts = 10;
  const delayMs = 2000;
  
  const poll = async () => {
    try {
      const response = await fetch(
        `/api/customer/orders/payment-status/${orderNumber}`,
        {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        }
      );
      
      const data = await response.json();
      
      if (data.success) {
        console.log('Poll result:', data);
        
        if (data.data.order_status === 'paid') {
          // ✅ Order sudah lunas!
          console.log('✅ Order lunas!');
          console.log('Remaining paid at:', data.data.order.remaining_paid_at);
          console.log('Full paid at:', data.data.order.full_paid_at);
          
          // Refresh order list
          await fetchOrders();
          
          // Show success message
          alert('Pelunasan berhasil! Order sudah lunas. ✅');
          
          return; // Stop polling
        }
        
        // Belum paid, poll lagi
        attempts++;
        if (attempts < maxAttempts) {
          console.log(`⏳ Belum lunas, polling lagi... (${attempts}/${maxAttempts})`);
          setTimeout(poll, delayMs);
        } else {
          console.log('⚠️ Max polling attempts reached');
          alert('Mohon refresh halaman untuk melihat status terbaru');
        }
      }
      
    } catch (error) {
      console.error('Error polling:', error);
      
      attempts++;
      if (attempts < maxAttempts) {
        setTimeout(poll, delayMs);
      }
    }
  };
  
  // Start polling
  poll();
}
```

---

## ✅ Success Criteria

Polling berhasil jika:

1. ✅ Response `order_status` = `"paid"`
2. ✅ Response `remaining_paid_at` terisi dengan timestamp
3. ✅ Response `full_paid_at` terisi dengan timestamp
4. ✅ Response `remaining_amount` = `0`
5. ✅ Database order status = `"paid"`
6. ✅ Log menunjukkan "Remaining payment settled via manual_check"

---

**Date:** December 14, 2025  
**Method:** Manual Polling  
**Status:** Ready for Testing
