# Payment Integration Guide - Midtrans Snap

Dokumentasi lengkap integrasi pembayaran menggunakan Midtrans Snap (Sandbox Mode) dengan metode QRIS, Virtual Account, dan lainnya.

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Payment Flow](#payment-flow)
3. [Backend API](#backend-api)
4. [Frontend Implementation](#frontend-implementation)
5. [Sandbox Testing](#sandbox-testing)
6. [Payment Methods](#payment-methods)
7. [Status Polling](#status-polling)
8. [Error Handling](#error-handling)

---

## 🎯 Overview

### Midtrans Snap Integration
- **Mode**: Sandbox (Testing)
- **UI**: Custom frontend dengan Snap Popup
- **Payment Methods**: QRIS, Virtual Account, Credit Card, GoPay, ShopeePay, dll.
- **Status Update**: Manual Polling (No Webhook)

### Key Features
✅ Checkout dari cart dengan voucher discount  
✅ Generate Snap Token untuk payment popup  
✅ Display payment popup dengan berbagai metode pembayaran  
✅ Polling status payment secara manual setiap 3 detik  
✅ Update order status otomatis (pending → paid → completed)  

---

## 🔄 Payment Flow

### Complete User Journey

```
1. User di Cart Page
   ↓
2. Click "Proceed to Checkout"
   ↓
3. Fill Personal Details & Shipping Address
   ↓
4. Apply Voucher (optional)
   ↓
5. Click "Pay Now"
   ↓
6. POST /api/customer/orders/checkout
   ↓ Response: snap_token, client_key, order
   ↓
7. Load Midtrans Snap.js
   ↓
8. Call snap.pay(snap_token, {...})
   ↓
9. Midtrans Popup Muncul
   │
   ├─ User Pilih QRIS
   │  ↓ QR Code Muncul (Fake QR - Sandbox)
   │  ↓ User "Scan" (simulate di dashboard)
   │  ↓ Go to Midtrans Dashboard
   │  ↓ Transactions → Find Order → Simulate Payment
   │
   ├─ User Pilih Virtual Account
   │  ↓ VA Number Muncul (Fake - Sandbox)
   │  ↓ Copy VA Number
   │  ↓ Go to Midtrans Dashboard
   │  ↓ Transactions → Find Order → Simulate Payment
   │
   └─ User Pilih GoPay/ShopeePay/dll.
      ↓ Deeplink/QR muncul
      ↓ Simulate di dashboard
   ↓
10. Setelah simulate payment success
    ↓
11. Frontend Polling Status (setiap 3 detik)
    GET /api/customer/orders/payment-status/{order_number}
    ↓
12. Cek transaction_status dari Midtrans
    │
    ├─ "pending" → Tetap polling
    ├─ "settlement" → Order status = "paid"
    └─ "deny/cancel/expire" → Order status = "failed"
    ↓
13. Redirect ke Order Success Page
    ↓
14. User lihat order details, status: "paid"
```

**❌ JANGAN:**
- Langsung redirect ke success page setelah checkout
- Skip payment popup
- Tidak polling status

**✅ HARUS:**
- Tampilkan payment popup setelah checkout
- User pilih metode pembayaran di popup
- Polling status sampai settlement
- Baru redirect ke success setelah status = "paid"

---

## 🔌 Backend API

### 1. Checkout Order

**Endpoint:**
```http
POST /api/customer/orders/checkout
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "+628123456789",
  "address": "Jl. Sudirman No. 123",
  "city": "Jakarta",
  "district": "Tanah Abang",
  "sub_district": "Petamburan",
  "postal_code": "10260",
  "notes": "Pengiriman siang hari",
  "voucher_code": "WEDDING2025"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "order": {
      "id": 1,
      "order_number": "ORD-1733587200-A1B2C3",
      "user_id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "phone": "+628123456789",
      "address": "Jl. Sudirman No. 123",
      "city": "Jakarta",
      "district": "Tanah Abang",
      "sub_district": "Petamburan",
      "postal_code": "10260",
      "subtotal": 72000000,
      "voucher_code": "WEDDING2025",
      "voucher_discount": 7200000,
      "discount": 0,
      "delivery_fee": 0,
      "total": 64800000,
      "status": "pending",
      "notes": "Pengiriman siang hari",
      "created_at": "2024-12-07T10:00:00.000000Z",
      "items": [
        {
          "id": 1,
          "decoration_id": 5,
          "type": "Custom",
          "quantity": 1,
          "price": 72000000,
          "decoration": {
            "id": 5,
            "name": "PURE ELEGANCE 6",
            "base_price": 72000000
          }
        }
      ]
    },
    "snap_token": "66e4fa55-fdac-4ef9-91b5-733b97d1b862",
    "client_key": "SB-Mid-client-abc123xyz"
  }
}
```

**Error Responses:**

```json
// Cart kosong
{
  "success": false,
  "message": "Cart is empty"
}

// Voucher invalid
{
  "success": false,
  "message": "Voucher sudah expired"
}

// Midtrans error
{
  "success": false,
  "message": "Failed to create payment: ..."
}
```

---

### 2. Check Payment Status (Polling)

**Endpoint:**
```http
GET /api/customer/orders/payment-status/{order_number}
Authorization: Bearer {token}
```

**Example:**
```http
GET /api/customer/orders/payment-status/ORD-1733587200-A1B2C3
```

**Response - Pending:**
```json
{
  "success": true,
  "data": {
    "order_number": "ORD-1733587200-A1B2C3",
    "order_status": "pending",
    "transaction_status": "pending",
    "payment_type": "qris",
    "transaction_time": "2024-12-07 10:00:00",
    "gross_amount": "64800000"
  }
}
```

**Response - Settlement (Paid):**
```json
{
  "success": true,
  "data": {
    "order_number": "ORD-1733587200-A1B2C3",
    "order_status": "paid",
    "transaction_status": "settlement",
    "payment_type": "qris",
    "transaction_time": "2024-12-07 10:00:00",
    "gross_amount": "64800000"
  }
}
```

**Transaction Status dari Midtrans:**
- `pending` - Menunggu pembayaran
- `settlement` - Pembayaran berhasil (VA, QRIS, E-wallet)
- `capture` - Pembayaran berhasil (Credit Card)
- `deny` - Ditolak
- `cancel` - Dibatalkan user
- `expire` - Expired (tidak dibayar dalam waktu yang ditentukan)

---

## 💻 Frontend Implementation

### 1. Install Midtrans Snap

**Option A: CDN (Recommended)**

Tambahkan di `public/index.html` atau `app/layout.tsx`:

```html
<!-- Sandbox -->
<script src="https://app.sandbox.midtrans.com/snap/snap.js" 
        data-client-key="SB-Mid-client-abc123xyz"></script>

<!-- Production (nanti) -->
<!-- <script src="https://app.midtrans.com/snap/snap.js" 
        data-client-key="Mid-client-production"></script> -->
```

**Option B: NPM Package**

```bash
npm install midtrans-client
```

---

### 2. Checkout Component

```jsx
// pages/checkout.jsx or app/checkout/page.jsx
'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import axios from 'axios';

export default function CheckoutPage() {
  const router = useRouter();
  const [loading, setLoading] = useState(false);
  const [cartItems, setCartItems] = useState([]);
  const [formData, setFormData] = useState({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    address: '',
    city: '',
    district: '',
    sub_district: '',
    postal_code: '',
    notes: '',
    voucher_code: ''
  });

  // Load cart data
  useEffect(() => {
    loadCart();
  }, []);

  const loadCart = async () => {
    try {
      const response = await axios.get('/api/customer/cart', {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      });
      
      if (response.data.success) {
        setCartItems(response.data.data.items || []);
      }
    } catch (error) {
      console.error('Load cart error:', error);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      // 1. Call checkout API
      const response = await axios.post(
        '/api/customer/orders/checkout',
        formData,
        {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json'
          }
        }
      );

      if (response.data.success) {
        const { snap_token, order } = response.data.data;
        
        // 2. Open Midtrans Snap Popup
        window.snap.pay(snap_token, {
          onSuccess: function(result) {
            console.log('Payment success:', result);
            // ❌ JANGAN langsung redirect di sini
            // Biarkan polling yang handle redirect
          },
          onPending: function(result) {
            console.log('Payment pending:', result);
            // Redirect ke halaman waiting payment
            router.push(`/order-status/${order.order_number}`);
          },
          onError: function(result) {
            console.error('Payment error:', result);
            alert('Payment failed. Please try again.');
            setLoading(false);
          },
          onClose: function() {
            console.log('Payment popup closed');
            // Redirect ke halaman waiting payment
            router.push(`/order-status/${order.order_number}`);
          }
        });
      }
    } catch (error) {
      console.error('Checkout error:', error);
      alert(error.response?.data?.message || 'Checkout failed');
      setLoading(false);
    }
  };

  return (
    <div className="checkout-page">
      <h1>Checkout</h1>
      
      <form onSubmit={handleSubmit}>
        {/* Personal Details */}
        <section className="personal-details">
          <h2>Personal Details</h2>
          <div className="form-row">
            <input
              type="text"
              name="first_name"
              placeholder="First Name"
              value={formData.first_name}
              onChange={(e) => setFormData({...formData, first_name: e.target.value})}
              required
            />
            <input
              type="text"
              name="last_name"
              placeholder="Last Name"
              value={formData.last_name}
              onChange={(e) => setFormData({...formData, last_name: e.target.value})}
              required
            />
          </div>
          <input
            type="email"
            name="email"
            placeholder="Email"
            value={formData.email}
            onChange={(e) => setFormData({...formData, email: e.target.value})}
            required
          />
          <input
            type="tel"
            name="phone"
            placeholder="Phone Number"
            value={formData.phone}
            onChange={(e) => setFormData({...formData, phone: e.target.value})}
            required
          />
        </section>

        {/* Shipping Address */}
        <section className="shipping-address">
          <h2>Shipping Address</h2>
          <textarea
            name="address"
            placeholder="Street Address"
            value={formData.address}
            onChange={(e) => setFormData({...formData, address: e.target.value})}
            required
          />
          <div className="form-row">
            <input
              type="text"
              name="city"
              placeholder="City"
              value={formData.city}
              onChange={(e) => setFormData({...formData, city: e.target.value})}
              required
            />
            <input
              type="text"
              name="district"
              placeholder="District"
              value={formData.district}
              onChange={(e) => setFormData({...formData, district: e.target.value})}
              required
            />
          </div>
          <div className="form-row">
            <input
              type="text"
              name="sub_district"
              placeholder="Sub District"
              value={formData.sub_district}
              onChange={(e) => setFormData({...formData, sub_district: e.target.value})}
              required
            />
            <input
              type="text"
              name="postal_code"
              placeholder="Postal Code"
              value={formData.postal_code}
              onChange={(e) => setFormData({...formData, postal_code: e.target.value})}
              required
            />
          </div>
        </section>

        {/* Voucher */}
        <section className="voucher">
          <h2>Voucher Code (Optional)</h2>
          <input
            type="text"
            name="voucher_code"
            placeholder="Enter voucher code"
            value={formData.voucher_code}
            onChange={(e) => setFormData({...formData, voucher_code: e.target.value.toUpperCase()})}
          />
        </section>

        {/* Notes */}
        <section className="notes">
          <h2>Notes (Optional)</h2>
          <textarea
            name="notes"
            placeholder="Delivery instructions, special requests, etc."
            value={formData.notes}
            onChange={(e) => setFormData({...formData, notes: e.target.value})}
          />
        </section>

        {/* Submit */}
        <button 
          type="submit" 
          disabled={loading}
          className="pay-button"
        >
          {loading ? 'Processing...' : 'Pay Now'}
        </button>
      </form>
    </div>
  );
}
```

---

### 3. Order Status Page (Polling)

```jsx
// pages/order-status/[orderNumber].jsx
'use client';

import { useState, useEffect, useRef } from 'react';
import { useRouter, useParams } from 'next/navigation';
import axios from 'axios';

export default function OrderStatusPage() {
  const router = useRouter();
  const params = useParams();
  const orderNumber = params.orderNumber;
  
  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(true);
  const [pollingCount, setPollingCount] = useState(0);
  const intervalRef = useRef(null);

  useEffect(() => {
    if (!orderNumber) return;

    // Start polling immediately
    checkPaymentStatus();
    
    // Poll every 3 seconds
    intervalRef.current = setInterval(() => {
      checkPaymentStatus();
    }, 3000);

    // Cleanup on unmount
    return () => {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
      }
    };
  }, [orderNumber]);

  const checkPaymentStatus = async () => {
    try {
      const response = await axios.get(
        `/api/customer/orders/payment-status/${orderNumber}`,
        {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        }
      );

      if (response.data.success) {
        const data = response.data.data;
        setOrder(data);
        setPollingCount(prev => prev + 1);
        setLoading(false);

        // Check if payment is settled
        if (data.transaction_status === 'settlement' || data.order_status === 'paid') {
          // Stop polling
          if (intervalRef.current) {
            clearInterval(intervalRef.current);
          }
          
          // Redirect to success page after 2 seconds
          setTimeout(() => {
            router.push(`/order-success/${orderNumber}`);
          }, 2000);
        }

        // Check if payment failed
        if (['deny', 'cancel', 'expire'].includes(data.transaction_status)) {
          // Stop polling
          if (intervalRef.current) {
            clearInterval(intervalRef.current);
          }
          
          // Show failed state
          alert('Payment failed or cancelled');
        }

        // Stop polling after 5 minutes (100 attempts)
        if (pollingCount >= 100) {
          if (intervalRef.current) {
            clearInterval(intervalRef.current);
          }
          alert('Payment timeout. Please check your order status later.');
        }
      }
    } catch (error) {
      console.error('Check status error:', error);
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="loading-container">
        <div className="spinner"></div>
        <p>Checking payment status...</p>
      </div>
    );
  }

  return (
    <div className="order-status-page">
      <div className="status-card">
        <h1>Order Status</h1>
        
        <div className="order-info">
          <p><strong>Order Number:</strong> {order?.order_number}</p>
          <p><strong>Total:</strong> Rp {parseInt(order?.gross_amount || 0).toLocaleString('id-ID')}</p>
        </div>

        {/* Status Indicator */}
        {order?.transaction_status === 'pending' && (
          <div className="status-pending">
            <div className="spinner"></div>
            <h2>⏳ Waiting for Payment</h2>
            <p>Please complete your payment</p>
            <p className="payment-method">Payment Method: {order?.payment_type?.toUpperCase()}</p>
            <p className="polling-info">Checking status... ({pollingCount} attempts)</p>
          </div>
        )}

        {order?.transaction_status === 'settlement' && (
          <div className="status-success">
            <div className="checkmark">✓</div>
            <h2>Payment Successful!</h2>
            <p>Redirecting to order details...</p>
          </div>
        )}

        {['deny', 'cancel', 'expire'].includes(order?.transaction_status) && (
          <div className="status-failed">
            <div className="cross">✗</div>
            <h2>Payment Failed</h2>
            <p>Status: {order?.transaction_status}</p>
            <button onClick={() => router.push('/orders')}>
              View My Orders
            </button>
          </div>
        )}
      </div>

      <style jsx>{`
        .loading-container {
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          min-height: 400px;
        }

        .spinner {
          width: 40px;
          height: 40px;
          border: 4px solid #f3f3f3;
          border-top: 4px solid #3b82f6;
          border-radius: 50%;
          animation: spin 1s linear infinite;
        }

        @keyframes spin {
          0% { transform: rotate(0deg); }
          100% { transform: rotate(360deg); }
        }

        .order-status-page {
          max-width: 600px;
          margin: 0 auto;
          padding: 20px;
        }

        .status-card {
          background: white;
          border-radius: 12px;
          padding: 32px;
          box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .order-info {
          margin: 20px 0;
          padding: 16px;
          background: #f9fafb;
          border-radius: 8px;
        }

        .status-pending, .status-success, .status-failed {
          text-align: center;
          padding: 32px;
        }

        .status-pending h2 { color: #f59e0b; }
        .status-success h2 { color: #10b981; }
        .status-failed h2 { color: #ef4444; }

        .checkmark, .cross {
          width: 80px;
          height: 80px;
          margin: 0 auto 20px;
          background: #10b981;
          color: white;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 48px;
        }

        .cross {
          background: #ef4444;
        }

        .polling-info {
          margin-top: 12px;
          font-size: 14px;
          color: #6b7280;
        }

        .payment-method {
          margin-top: 8px;
          font-weight: 600;
          color: #3b82f6;
          text-transform: uppercase;
        }
      `}</style>
    </div>
  );
}
```

---

## 🧪 Sandbox Testing

### Akses Midtrans Dashboard

**URL:** https://dashboard.sandbox.midtrans.com/  
**Login:** Gunakan akun Midtrans Sandbox Anda

---

### Testing QRIS Payment

**Step-by-step:**

1. **User melakukan checkout**
   - Isi form personal details dan shipping address
   - Click "Pay Now"
   - Backend generate snap_token

2. **Midtrans popup muncul**
   - Popup menampilkan berbagai metode pembayaran
   - User pilih **"QRIS"**

3. **QR Code ditampilkan**
   - QR Code palsu muncul (sandbox mode)
   - Instruksi untuk scan dengan mobile banking
   - ⚠️ JANGAN tutup popup dulu

4. **Simulate payment di dashboard**
   ```
   a. Buka tab baru → https://dashboard.sandbox.midtrans.com/
   b. Login ke dashboard
   c. Menu: Transactions → Transaction List
   d. Cari order berdasarkan order_number (misal: ORD-1733587200-A1B2C3)
   e. Click order tersebut
   f. Click tombol "Simulate Payment"
   g. Pilih "Success" (settlement)
   h. Click "Submit"
   ```

5. **Status berubah**
   - Transaction status: `pending` → `settlement`
   - Order status: `pending` → `paid`

6. **Frontend polling mendeteksi**
   - Polling endpoint setiap 3 detik mendeteksi status `settlement`
   - Tampilan berubah menjadi "Payment Successful!"
   - Auto redirect ke order success page

---

### Testing Virtual Account Payment

**Step-by-step:**

1. **User pilih Virtual Account**
   - Setelah popup muncul, pilih bank (BCA, BNI, Mandiri, Permata, dll.)
   - Contoh: Pilih **"BCA Virtual Account"**

2. **VA Number ditampilkan**
   ```
   VA Number: 70012345678901
   Bank: BCA
   Amount: Rp 64.800.000
   
   Expired: 24 hours
   ```
   - Copy VA number
   - ⚠️ Ini fake VA number (sandbox)

3. **Simulate payment di dashboard**
   ```
   a. Buka https://dashboard.sandbox.midtrans.com/
   b. Transactions → Transaction List
   c. Cari order dengan order_number
   d. Click order
   e. Click "Simulate Payment"
   f. Pilih "Success"
   g. Submit
   ```

4. **Status update**
   - Polling mendeteksi `settlement`
   - Order status menjadi `paid`
   - Redirect ke success page

---

### Testing GoPay / ShopeePay

**Step-by-step:**

1. **User pilih GoPay/ShopeePay**
   - Popup menampilkan QR code atau deeplink
   - Sandbox mode: Tidak bisa real scan

2. **Simulate payment di dashboard**
   - Sama seperti QRIS/VA
   - Buka dashboard → Transactions
   - Find order → Simulate Payment → Success

3. **Status update**
   - Polling deteksi settlement
   - Order berhasil dibayar

---

### Testing Credit Card

**Use Midtrans Test Cards:**

```
Card Number: 4811 1111 1111 1114
CVV: 123
Exp Date: 01/25 (atau bulan/tahun di masa depan)
OTP: 112233
```

**Flow:**
1. User pilih Credit Card
2. Input test card number
3. Input CVV dan expiry
4. Submit
5. OTP page muncul → Input 112233
6. Payment success
7. Status langsung `settlement` (tidak perlu simulate)

---

### Simulate Payment Failure

**Di dashboard:**
1. Find transaction
2. Click "Simulate Payment"
3. Pilih **"Deny"** atau **"Expire"**
4. Submit

**Result:**
- Transaction status: `deny` atau `expire`
- Order status: `failed`
- Frontend polling akan mendeteksi dan tampilkan failed state

---

## 💳 Payment Methods Support

### Tersedia di Sandbox:

| Method | Code | Description |
|--------|------|-------------|
| **QRIS** | `qris` | QR Code untuk semua bank/e-wallet |
| **GoPay** | `gopay` | E-wallet GoPay |
| **ShopeePay** | `shopeepay` | E-wallet ShopeePay |
| **BCA VA** | `bca_va` | Virtual Account BCA |
| **BNI VA** | `bni_va` | Virtual Account BNI |
| **BRI VA** | `bri_va` | Virtual Account BRI |
| **Mandiri Bill** | `echannel` | Mandiri Bill Payment |
| **Permata VA** | `permata_va` | Virtual Account Permata |
| **Credit Card** | `credit_card` | Visa/Mastercard/JCB |
| **Akulaku** | `akulaku` | Paylater Akulaku |
| **Kredivo** | `kredivo` | Paylater Kredivo |

---

## 🔄 Status Polling

### Polling Strategy

**Interval:** 3 seconds  
**Max Attempts:** 100 (5 minutes total)  
**Timeout:** Stop after 5 minutes or when status settled

**Polling Logic:**
```javascript
// Start polling after payment popup closed
const pollingInterval = setInterval(async () => {
  const status = await checkPaymentStatus(orderNumber);
  
  if (status.transaction_status === 'settlement') {
    // Payment success
    clearInterval(pollingInterval);
    redirectToSuccess();
  } else if (['deny', 'cancel', 'expire'].includes(status.transaction_status)) {
    // Payment failed
    clearInterval(pollingInterval);
    showFailedState();
  } else if (pollingCount > 100) {
    // Timeout
    clearInterval(pollingInterval);
    showTimeoutMessage();
  }
}, 3000);
```

**Best Practices:**
- ✅ Start polling immediately after popup closed
- ✅ Stop polling when settlement/failed detected
- ✅ Show loading indicator during polling
- ✅ Display attempt count to user
- ✅ Handle network errors gracefully
- ✅ Cleanup interval on component unmount

---

## ⚠️ Error Handling

### Common Errors and Solutions

#### 1. "Cart is empty"
**Cause:** User mencoba checkout dengan cart kosong  
**Solution:** Redirect ke cart page, minta user add items dulu

#### 2. "Voucher sudah expired"
**Cause:** Voucher code tidak valid  
**Solution:** Remove voucher, checkout tanpa voucher

#### 3. "Failed to create payment"
**Cause:** Midtrans API error (server_key salah, network issue)  
**Solution:** 
- Check Midtrans credentials di `.env`
- Check network connection
- Check Midtrans status page

#### 4. "snap is not defined"
**Cause:** Snap.js belum load  
**Solution:** 
- Pastikan script Snap.js ada di HTML
- Load snap.js sebelum call `window.snap.pay()`
- Add error handling:
```javascript
if (typeof window.snap === 'undefined') {
  alert('Payment system not loaded. Please refresh the page.');
  return;
}
```

#### 5. Payment popup tidak muncul
**Cause:** snap_token invalid atau expired  
**Solution:**
- Check response dari checkout API
- Verify snap_token ada di response
- Try checkout ulang untuk generate token baru

#### 6. Polling timeout (5 minutes)
**Cause:** User tidak complete payment  
**Solution:**
- Show message: "Payment pending. Check your order status later."
- Redirect ke orders page
- User bisa cek manual di "My Orders"

---

## 📱 Mobile Responsiveness

### Snap Popup di Mobile

**Midtrans Snap automatically responsive:**
- Desktop: Modal popup overlay
- Mobile: Full screen overlay
- Mobile browser: Otomatis adjust ke screen size

**Deep Link Support:**
- GoPay: `gojek://` deeplink
- ShopeePay: `shopeepay://` deeplink
- Auto redirect ke app jika installed

---

## 🚀 Production Checklist

### Before Going Live:

- [ ] Ganti `MIDTRANS_IS_PRODUCTION=true` di `.env`
- [ ] Update server_key dan client_key dengan production keys
- [ ] Ganti snap.js URL ke production:
  ```html
  <script src="https://app.midtrans.com/snap/snap.js" 
          data-client-key="{production_client_key}"></script>
  ```
- [ ] Test dengan real bank account (small amount)
- [ ] Setup webhook untuk backup status update (optional)
- [ ] Monitor transactions di production dashboard
- [ ] Setup notification email untuk failed payments
- [ ] Add transaction logs untuk debugging

---

## 📞 Support

**Midtrans Documentation:**
- https://docs.midtrans.com/
- https://docs.midtrans.com/en/snap/overview

**Sandbox Dashboard:**
- https://dashboard.sandbox.midtrans.com/

**Production Dashboard:**
- https://dashboard.midtrans.com/

**Contact Midtrans:**
- Email: support@midtrans.com
- Live Chat: Available di dashboard

---

## 📝 Summary

### Key Points to Remember:

1. **Checkout Flow:**
   - User checkout → Generate snap_token → Open popup → User pay → Polling status → Success

2. **Payment Popup:**
   - HARUS ditampilkan setelah checkout
   - User pilih metode pembayaran di popup
   - JANGAN skip popup

3. **Sandbox Testing:**
   - QR Code/VA adalah fake (tidak bisa real scan)
   - Harus simulate payment di dashboard
   - Login dashboard → Transactions → Simulate Payment

4. **Status Polling:**
   - Polling setiap 3 detik
   - Cek transaction_status dari Midtrans
   - `settlement` = payment success
   - Stop polling setelah settlement/failed

5. **Error Handling:**
   - Handle popup close
   - Handle network errors
   - Handle timeout (5 minutes)
   - Show clear error messages

---

**Happy Coding! 🎉**

Jika ada pertanyaan atau error, refer ke dokumentasi ini atau contact Midtrans support.
