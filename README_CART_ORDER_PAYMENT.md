# Cart, Orders & Payment - Frontend Integration Guide

## Overview

Sistem **Cart (Keranjang Belanja)**, **Checkout**, dan **Payment Gateway Midtrans** untuk DWD Wedding Organizer. User dapat menambah dekorasi ke cart, checkout, dan membayar menggunakan Midtrans dengan custom UI di frontend. Sistem ini menggunakan **manual polling** untuk mengecek status pembayaran tanpa webhook/callback.

⚠️ **IMPORTANT DOCUMENTATION:**
- 📖 **Payment Flow & Sandbox Testing:** [README_PAYMENT_INTEGRATION.md](./README_PAYMENT_INTEGRATION.md)
- 📖 **Complete Order API Endpoints:** [README_ORDER_ENDPOINTS.md](./README_ORDER_ENDPOINTS.md)

---

## Table of Contents

1. [Database Structure](#database-structure)
2. [Midtrans Setup](#midtrans-setup)
3. [Cart API Endpoints](#cart-api-endpoints)
4. [Orders & Checkout API](#orders--checkout-api)
5. [Admin Order Management](#admin-order-management)
6. [Frontend Implementation](#frontend-implementation)
7. [Payment Flow Diagram](#payment-flow-diagram)
8. [Manual Polling Strategy](#manual-polling-strategy)
9. [Complete Payment Integration](#complete-payment-integration)

---

## Database Structure

### Carts Table
```sql
carts
- id (primary key)
- user_id (foreign key) - One cart per user
- created_at
- updated_at
```

### Cart Items Table
```sql
cart_items
- id (primary key)
- cart_id (foreign key)
- decoration_id (foreign key)
- type (enum: custom, random)
- quantity (integer)
- price (bigint) - Snapshot price saat add to cart
- created_at
- updated_at
```

### Orders Table
```sql
orders
- id (primary key)
- user_id (foreign key)
- order_number (unique) - Format: ORD-{timestamp}-{hash}
- subtotal (bigint)
- voucher_code (string, nullable)
- voucher_discount (bigint, default 0)
- discount (bigint, default 0) - Diskon dari decoration
- delivery_fee (bigint, default 0)
- total (bigint)
- status (enum: pending, paid, failed, completed, cancelled)
- payment_method (string, nullable)
- created_at
- updated_at
```

### Order Items Table
```sql
order_items
- id (primary key)
- order_id (foreign key)
- decoration_id (foreign key)
- type (enum: custom, random)
- quantity (integer)
- price (bigint) - Snapshot price saat order
- created_at
- updated_at
```

---

## Midtrans Setup

### 1. Register Midtrans Account

1. Daftar di [Midtrans](https://midtrans.com/)
2. Login ke [Dashboard Sandbox](https://dashboard.sandbox.midtrans.com/)
3. Ambil credentials:
   - **Server Key**: Settings → Access Keys → Server Key
   - **Client Key**: Settings → Access Keys → Client Key

### 2. Configure .env

```env
# Midtrans Payment Gateway
MIDTRANS_SERVER_KEY=SB-Mid-server-xxxxxxxxxxxxx
MIDTRANS_CLIENT_KEY=SB-Mid-client-xxxxxxxxxxxxx
MIDTRANS_IS_PRODUCTION=false
MIDTRANS_IS_SANITIZED=true
MIDTRANS_IS_3DS=true
```

⚠️ **IMPORTANT**: 
- Untuk production, set `MIDTRANS_IS_PRODUCTION=true`
- Ganti credentials dengan production keys
- **Tidak perlu setup webhook/callback** karena menggunakan manual polling

### 3. Test Cards (Sandbox)

| Card Number | Bank | Status |
|------------|------|--------|
| 4811 1111 1111 1114 | BNI | Success |
| 5211 1111 1111 1117 | Mandiri | Success |
| 4111 1111 1111 1111 | Visa | Success |

---

## Cart API Endpoints

### **Customer Routes** (Auth Required)

#### 1. Get Cart
```http
GET /api/customer/cart
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "cart": {
      "id": 1,
      "user_id": 1,
      "items": [
        {
          "id": 1,
          "cart_id": 1,
          "decoration_id": 1,
          "type": "custom",
          "quantity": 1,
          "price": 36000000,
          "decoration": {
            "id": 1,
            "name": "PURE ELEGANCE 9",
            "slug": "pure-elegance-9",
            "base_price": 40000000,
            "discount_percent": 10,
            "final_price": 36000000,
            "images": [...]
          }
        }
      ]
    },
    "subtotal": 36000000,
    "item_count": 1
  }
}
```

#### 2. Add Item to Cart
```http
POST /api/customer/cart/add
Authorization: Bearer {token}
Content-Type: application/json

{
  "decoration_id": 1,
  "type": "custom",
  "quantity": 1
}
```

**Validation:**
- `decoration_id`: required, exists in decorations table
- `type`: required, enum (custom, random)
- `quantity`: required, integer, min 1

**Response:**
```json
{
  "success": true,
  "message": "Item added to cart successfully",
  "data": {
    "id": 1,
    "cart_id": 1,
    "decoration_id": 1,
    "type": "custom",
    "quantity": 1,
    "price": 36000000,
    "decoration": {...}
  }
}
```

#### 3. Update Cart Item Quantity
```http
PUT /api/customer/cart/items/{itemId}
Authorization: Bearer {token}
Content-Type: application/json

{
  "quantity": 2
}
```

**Response:**
```json
{
  "success": true,
  "message": "Cart item updated successfully",
  "data": {
    "id": 1,
    "quantity": 2,
    "price": 36000000
  }
}
```

#### 4. Remove Item from Cart
```http
DELETE /api/customer/cart/items/{itemId}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Item removed from cart successfully"
}
```

#### 5. Clear Cart
```http
DELETE /api/customer/cart/clear
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Cart cleared successfully"
}
```

---

## Orders & Checkout API

### **Customer Routes** (Auth Required)

#### 1. Get All My Orders
```http
GET /api/customer/orders?status=paid
Authorization: Bearer {token}
```

**Query Parameters:**
- `status` (optional): pending, paid, failed, completed, cancelled

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 1,
        "order_number": "ORD-1733587200-A1B2C3",
        "subtotal": 40000000,
        "voucher_code": "WELCOME20",
        "voucher_discount": 4000000,
        "discount": 4000000,
        "delivery_fee": 0,
        "total": 32000000,
        "status": "paid",
        "payment_method": "bank_transfer",
        "created_at": "2024-12-07T10:00:00.000000Z",
        "items": [
          {
            "id": 1,
            "decoration_id": 1,
            "type": "custom",
            "quantity": 1,
            "price": 40000000,
            "decoration": {...}
          }
        ]
      }
    ],
    "per_page": 10,
    "total": 1
  }
}
```

#### 2. Get Single Order Detail
```http
GET /api/customer/orders/{id}
Authorization: Bearer {token}
```

**Response:** Same as order item in list above.

#### 3. Checkout (Create Order + Get Midtrans Token)
```http
POST /api/customer/checkout
Authorization: Bearer {token}
Content-Type: application/json

{
  "first_name": "Nama",
  "last_name": "Depan",
  "email": "customer@example.com",
  "phone": "081234567890",
  "address": "Jl. Contoh No. 123, RT 01/RW 02",
  "city": "Jakarta",
  "district": "Cempaka Putih",
  "sub_district": "Cempaka Putih Timur",
  "postal_code": "10510",
  "notes": "DP Fanny",
  "voucher_code": "WELCOME20"
}
```

**Validation:**
- `first_name`: required, string, max 255
- `last_name`: required, string, max 255
- `email`: required, email, max 255
- `phone`: required, string, max 20
- `address`: required, string (text)
- `city`: required, string, max 255
- `district`: required, string, max 255 (Kelurahan)
- `sub_district`: required, string, max 255 (Kecamatan)
- `postal_code`: required, string, max 10
- `notes`: optional, string (text)
- `voucher_code`: optional, must exist in vouchers table

**Process:**
1. Validate cart not empty
2. Calculate subtotal from cart items
3. Apply decoration discounts
4. Validate and apply voucher (if provided)
5. Create order with pending status
6. Generate Midtrans Snap Token
7. Clear cart after successful order creation
8. Return order data + snap_token

**Response:**
```json
{
  "success": true,
  "message": "Order created successfully",
  "data": {
    "order": {
      "id": 1,
      "order_number": "ORD-1733587200-A1B2C3",
      "first_name": "Nama",
      "last_name": "Depan",
      "email": "customer@example.com",
      "phone": "081234567890",
      "address": "Jl. Contoh No. 123, RT 01/RW 02",
      "city": "Jakarta",
      "district": "Cempaka Putih",
      "sub_district": "Cempaka Putih Timur",
      "postal_code": "10510",
      "subtotal": 40000000,
      "voucher_code": "WELCOME20",
      "voucher_discount": 4000000,
      "discount": 4000000,
      "delivery_fee": 0,
      "total": 32000000,
      "status": "pending",
      "notes": "DP Fanny",
      "items": [...]
    },
    "snap_token": "66e4fa55-fdac-4ef9-91b5-733b97d1b862",
    "client_key": "SB-Mid-client-xxxxxxxxxxxxx"
  }
}
```

**Error Responses:**

**Cart Empty:**
```json
{
  "success": false,
  "message": "Cart is empty"
}
```

**Voucher Invalid:**
```json
{
  "success": false,
  "message": "Voucher sudah expired"
}
```

#### 4. Check Payment Status (Manual Polling)
```http
GET /api/customer/orders/{orderNumber}/payment-status
Authorization: Bearer {token}
```

**Example:**
```http
GET /api/customer/orders/ORD-1733587200-A1B2C3/payment-status
```

**Process:**
1. Query Midtrans API untuk status transaksi
2. Update order status di database
3. Return status terbaru

**Response:**
```json
{
  "success": true,
  "data": {
    "order_number": "ORD-1733587200-A1B2C3",
    "order_status": "paid",
    "transaction_status": "settlement",
    "payment_type": "bank_transfer",
    "transaction_time": "2024-12-07 10:15:00",
    "gross_amount": "32000000"
  }
}
```

**Status Mapping:**
| Midtrans Status | Order Status |
|----------------|--------------|
| settlement | paid |
| capture (fraud_status=accept) | paid |
| pending | pending |
| deny / expire / cancel | failed |

#### 5. Cancel Order
```http
PUT /api/customer/orders/{id}/cancel
Authorization: Bearer {token}
```

**Note:** Only pending orders can be cancelled.

**Response:**
```json
{
  "success": true,
  "message": "Order cancelled successfully",
  "data": {
    "id": 1,
    "status": "cancelled"
  }
}
```

---

## Admin Order Management

### **Admin Routes** (Auth + Role Admin Required)

#### 1. Get All Orders
```http
GET /api/admin/orders?status=paid&user_id=1&start_date=2024-12-01
Authorization: Bearer {admin_token}
```

**Query Parameters:**
- `status`: pending, paid, failed, completed, cancelled
- `user_id`: Filter by specific user
- `search`: Search by order_number
- `start_date`: Filter from date (YYYY-MM-DD)
- `end_date`: Filter to date (YYYY-MM-DD)

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "order_number": "ORD-1733587200-A1B2C3",
        "subtotal": 40000000,
        "total": 32000000,
        "status": "paid",
        "user": {
          "id": 1,
          "name": "John Doe",
          "email": "john@example.com"
        },
        "items": [...]
      }
    ],
    "per_page": 20,
    "total": 15
  }
}
```

#### 2. Get Single Order Detail
```http
GET /api/admin/orders/{id}
Authorization: Bearer {admin_token}
```

**Response:** Same as order in list above.

#### 3. Update Order Status
```http
PUT /api/admin/orders/{id}/status
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "status": "completed"
}
```

**Validation:**
- `status`: required, enum (pending, paid, failed, completed, cancelled)

**Response:**
```json
{
  "success": true,
  "message": "Order status updated successfully",
  "data": {
    "id": 1,
    "status": "completed"
  }
}
```

#### 4. Get Order Statistics
```http
GET /api/admin/orders/statistics
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "total_orders": 150,
    "pending_orders": 5,
    "paid_orders": 100,
    "completed_orders": 40,
    "failed_orders": 3,
    "cancelled_orders": 2,
    "total_revenue": 5000000000,
    "orders_this_month": 25,
    "revenue_this_month": 800000000
  }
}
```

#### 5. Get Recent Orders
```http
GET /api/admin/orders/recent/10
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "order_number": "ORD-1733587200-A1B2C3",
      "total": 32000000,
      "status": "paid",
      "user": {...},
      "created_at": "2024-12-07T10:00:00.000000Z"
    }
  ]
}
```

---

## Frontend Implementation

### **1. Cart Page Component**

#### Validate Voucher in Cart

Before implementing the cart page, you need to be able to validate voucher codes when users click "Apply" button.

**API Endpoint:**
```http
POST /api/customer/checkout/validate-voucher
Authorization: Bearer {token}
Content-Type: application/json

{
  "code": "WEDDING2025",
  "cart_total": 72000000
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Voucher is valid",
  "data": {
    "voucher": {
      "id": 1,
      "code": "WEDDING2025",
      "type": "percentage",
      "discount_value": 10,
      "min_purchase": 50000000,
      "max_discount": 10000000,
      "usage_limit": 100,
      "usage_per_user": 1,
      "valid_from": "2025-01-01T00:00:00.000000Z",
      "valid_until": "2025-12-31T23:59:59.000000Z",
      "is_active": true,
      "description": "Diskon 10% untuk paket Wedding hingga Rp 10 juta"
    },
    "discount_amount": 7200000,
    "final_total": 64800000,
    "display_text": "Diskon 10% (Max Rp 10,000,000)"
  }
}
```

**Error Response - Voucher Not Found (404):**
```json
{
  "success": false,
  "message": "Voucher not found"
}
```

**Error Response - Invalid Voucher (400):**
```json
{
  "success": false,
  "message": "Voucher has expired"
}
```

**Other Possible Error Messages:**
- "Voucher is not active yet"
- "Voucher has expired"
- "Voucher usage limit reached"
- "You have already used this voucher"
- "Minimum purchase amount is Rp {amount}"

**React/Next.js Implementation:**
```jsx
import { useState } from 'react';
import axios from 'axios';

function VoucherInput({ cartTotal, onVoucherApplied }) {
  const [voucherCode, setVoucherCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [appliedVoucher, setAppliedVoucher] = useState(null);

  const validateVoucher = async () => {
    if (!voucherCode.trim()) {
      setError('Please enter voucher code');
      return;
    }

    setLoading(true);
    setError('');

    try {
      const response = await axios.post(
        '/api/customer/checkout/validate-voucher',
        {
          code: voucherCode.toUpperCase(),
          cart_total: cartTotal
        },
        {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        }
      );

      if (response.data.success) {
        const { voucher, discount_amount, final_total, display_text } = response.data.data;
        
        setAppliedVoucher({
          code: voucher.code,
          discount_amount,
          final_total,
          display_text
        });
        
        // Callback to parent component
        onVoucherApplied({
          code: voucher.code,
          discount_amount,
          final_total
        });
        
        setError('');
      }
    } catch (err) {
      if (err.response?.data?.message) {
        setError(err.response.data.message);
      } else {
        setError('Failed to validate voucher');
      }
      setAppliedVoucher(null);
      onVoucherApplied(null);
    } finally {
      setLoading(false);
    }
  };

  const removeVoucher = () => {
    setVoucherCode('');
    setAppliedVoucher(null);
    setError('');
    onVoucherApplied(null);
  };

  return (
    <div className="voucher-section">
      <h3>Voucher Discount</h3>
      
      {!appliedVoucher ? (
        <div className="voucher-input-group">
          <input
            type="text"
            value={voucherCode}
            onChange={(e) => setVoucherCode(e.target.value.toUpperCase())}
            placeholder="Enter voucher code"
            disabled={loading}
            className="voucher-input"
          />
          <button
            onClick={validateVoucher}
            disabled={loading || !voucherCode.trim()}
            className="apply-button"
          >
            {loading ? 'Validating...' : 'Apply'}
          </button>
        </div>
      ) : (
        <div className="applied-voucher">
          <div className="voucher-info">
            <span className="voucher-code">✓ {appliedVoucher.code}</span>
            <span className="voucher-discount">
              {appliedVoucher.display_text}
            </span>
            <span className="discount-amount">
              - Rp {appliedVoucher.discount_amount.toLocaleString('id-ID')}
            </span>
          </div>
          <button onClick={removeVoucher} className="remove-button">
            Remove
          </button>
        </div>
      )}
      
      {error && <p className="error-message">{error}</p>}
    </div>
  );
}

export default VoucherInput;
```

**CSS Styles:**
```css
.voucher-section {
  margin: 20px 0;
  padding: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background-color: #f9fafb;
}

.voucher-section h3 {
  font-size: 16px;
  font-weight: 600;
  margin-bottom: 12px;
  color: #111827;
}

.voucher-input-group {
  display: flex;
  gap: 8px;
}

.voucher-input {
  flex: 1;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
  text-transform: uppercase;
}

.voucher-input:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.apply-button {
  padding: 10px 24px;
  background-color: #3b82f6;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.apply-button:hover:not(:disabled) {
  background-color: #2563eb;
}

.apply-button:disabled {
  background-color: #9ca3af;
  cursor: not-allowed;
}

.applied-voucher {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px;
  background-color: #dcfce7;
  border: 1px solid #86efac;
  border-radius: 6px;
}

.voucher-info {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.voucher-code {
  font-weight: 600;
  color: #15803d;
  font-size: 14px;
}

.voucher-discount {
  font-size: 12px;
  color: #166534;
}

.discount-amount {
  font-weight: 600;
  color: #15803d;
  font-size: 14px;
}

.remove-button {
  padding: 6px 12px;
  background-color: #ef4444;
  color: white;
  border: none;
  border-radius: 4px;
  font-size: 12px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.remove-button:hover {
  background-color: #dc2626;
}

.error-message {
  margin-top: 8px;
  color: #dc2626;
  font-size: 13px;
  font-weight: 500;
}
```

**Usage in Cart Page:**
```jsx
function CartPage() {
  const [cartItems, setCartItems] = useState([]);
  const [subtotal, setSubtotal] = useState(0);
  const [voucherData, setVoucherData] = useState(null);

  useEffect(() => {
    // Calculate subtotal from cart items
    const total = cartItems.reduce((sum, item) => sum + item.subtotal, 0);
    setSubtotal(total);
  }, [cartItems]);

  const handleVoucherApplied = (voucher) => {
    setVoucherData(voucher);
  };

  const finalTotal = voucherData 
    ? voucherData.final_total 
    : subtotal;

  return (
    <div className="cart-page">
      {/* Cart Items List */}
      <div className="cart-items">
        {/* ... cart items rendering ... */}
      </div>

      {/* Order Summary */}
      <div className="order-summary">
        <h2>Order Summary</h2>
        
        <div className="summary-row">
          <span>Subtotal:</span>
          <span>Rp {subtotal.toLocaleString('id-ID')}</span>
        </div>

        {/* Voucher Input Component */}
        <VoucherInput 
          cartTotal={subtotal}
          onVoucherApplied={handleVoucherApplied}
        />

        {voucherData && (
          <div className="summary-row discount">
            <span>Voucher Discount:</span>
            <span className="discount-value">
              - Rp {voucherData.discount_amount.toLocaleString('id-ID')}
            </span>
          </div>
        )}

        <div className="summary-row total">
          <span>Total:</span>
          <span>Rp {finalTotal.toLocaleString('id-ID')}</span>
        </div>

        <button 
          onClick={() => proceedToCheckout(voucherData?.code)}
          className="checkout-button"
        >
          Proceed to Checkout
        </button>
      </div>
    </div>
  );
}
```

**Notes:**
- Voucher validation requires authentication (Bearer token)
- Always send `cart_total` as integer (in Rupiah, no decimals)
- Voucher code will be automatically converted to uppercase
- Validation checks: active status, date range, usage limits, min purchase
- The same endpoint can be used for both cart page and checkout page
- Store applied voucher code to pass to checkout API later

---

### **2. Full Cart Page Implementation**

```jsx
// React/Next.js Example
import { useState, useEffect } from 'react';
import { useAuth } from '@/hooks/useAuth';

function CartPage() {
  const { token } = useAuth();
  const [cart, setCart] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchCart();
  }, []);

  const fetchCart = async () => {
    const res = await fetch('/api/customer/cart', {
      headers: { Authorization: `Bearer ${token}` }
    });
    const data = await res.json();
    setCart(data.data);
    setLoading(false);
  };

  const updateQuantity = async (itemId, quantity) => {
    await fetch(`/api/customer/cart/items/${itemId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`
      },
      body: JSON.stringify({ quantity })
    });
    fetchCart();
  };

  const removeItem = async (itemId) => {
    await fetch(`/api/customer/cart/items/${itemId}`, {
      method: 'DELETE',
      headers: { Authorization: `Bearer ${token}` }
    });
    fetchCart();
  };

  if (loading) return <div>Loading...</div>;

  return (
    <div className="cart-page">
      <h1>Your Cart</h1>
      
      {cart.cart.items.length === 0 ? (
        <p>Your cart is empty</p>
      ) : (
        <>
          {cart.cart.items.map(item => (
            <div key={item.id} className="cart-item">
              <img 
                src={item.decoration.images[0]?.image_url} 
                alt={item.decoration.name}
              />
              <div className="item-details">
                <h3>{item.decoration.name}</h3>
                <p>Type: {item.type}</p>
                <p>Price: Rp {item.price.toLocaleString()}</p>
              </div>
              
              <div className="quantity-control">
                <button onClick={() => updateQuantity(item.id, item.quantity - 1)}>
                  -
                </button>
                <span>{item.quantity}</span>
                <button onClick={() => updateQuantity(item.id, item.quantity + 1)}>
                  +
                </button>
              </div>
              
              <button onClick={() => removeItem(item.id)}>
                Remove
              </button>
            </div>
          ))}
          
          <div className="cart-summary">
            <h2>Order Summary</h2>
            <p>Subtotal: Rp {cart.subtotal.toLocaleString()}</p>
            <p>Items: {cart.item_count}</p>
            
            <button onClick={() => window.location.href = '/checkout'}>
              Go to Payment
            </button>
          </div>
        </>
      )}
    </div>
  );
}
```

### **2. Add to Cart from Decoration Detail**

```jsx
function DecorationDetail({ decoration }) {
  const { token } = useAuth();
  const [quantity, setQuantity] = useState(1);
  const [type, setType] = useState('custom');

  const addToCart = async () => {
    const res = await fetch('/api/customer/cart/add', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`
      },
      body: JSON.stringify({
        decoration_id: decoration.id,
        type: type,
        quantity: quantity
      })
    });
    
    const data = await res.json();
    if (data.success) {
      alert('Added to cart!');
      window.location.href = '/cart';
    }
  };

  return (
    <div className="decoration-detail">
      {/* ... decoration info ... */}
      
      <div className="order-section">
        <h3>Pilih Paket</h3>
        <div className="quantity-selector">
          <button onClick={() => setQuantity(q => Math.max(1, q - 1))}>-</button>
          <input value={quantity} readOnly />
          <button onClick={() => setQuantity(q => q + 1)}>+</button>
        </div>
        
        <button onClick={addToCart} className="btn-add-to-cart">
          Add to Cart
        </button>
      </div>
    </div>
  );
}
```

### **3. Checkout Page with Midtrans**

```jsx
import { useState, useEffect } from 'react';
import { useAuth } from '@/hooks/useAuth';

function CheckoutPage() {
  const { token, user } = useAuth();
  const [formData, setFormData] = useState({
    first_name: user?.first_name || '',
    last_name: user?.last_name || '',
    email: user?.email || '',
    phone: user?.phone || '',
    address: '',
    city: '',
    district: '',
    sub_district: '',
    postal_code: '',
    notes: '',
    voucher_code: ''
  });
  const [processing, setProcessing] = useState(false);

  useEffect(() => {
    // Load Midtrans Snap script
    const script = document.createElement('script');
    script.src = 'https://app.sandbox.midtrans.com/snap/snap.js';
    script.setAttribute('data-client-key', 'YOUR_CLIENT_KEY'); // Will be from API response
    document.body.appendChild(script);

    return () => {
      document.body.removeChild(script);
    };
  }, []);

  const handleCheckout = async () => {
    setProcessing(true);

    // Validate form
    if (!formData.first_name || !formData.last_name || !formData.email || !formData.phone ||
        !formData.address || !formData.city || !formData.district || 
        !formData.sub_district || !formData.postal_code) {
      alert('Please fill all required fields');
      setProcessing(false);
      return;
    }

    try {
      const res = await fetch('/api/customer/checkout', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`
        },
        body: JSON.stringify(formData)
      });

      const data = await res.json();

      if (!data.success) {
        alert(data.message);
        setProcessing(false);
        return;
      }

      // Open Midtrans Snap popup
      window.snap.pay(data.data.snap_token, {
        onSuccess: function(result) {
          console.log('Payment success:', result);
          // Start polling payment status
          startPolling(data.data.order.order_number);
        },
        onPending: function(result) {
          console.log('Payment pending:', result);
          // Start polling payment status
          startPolling(data.data.order.order_number);
        },
        onError: function(result) {
          console.log('Payment error:', result);
          alert('Payment failed. Please try again.');
          setProcessing(false);
        },
        onClose: function() {
          console.log('Customer closed the popup without finishing payment');
          setProcessing(false);
        }
      });

    } catch (error) {
      console.error('Checkout error:', error);
      alert('Checkout failed. Please try again.');
      setProcessing(false);
    }
  };

  const startPolling = (orderNumber) => {
    // Redirect to order status page with polling
    window.location.href = `/orders/${orderNumber}/status`;
  };

  return (
    <div className="checkout-page">
      <h1>Complete Your Order</h1>
      
      {/* Personal Details */}
      <section className="personal-details">
        <h2>Personal Details</h2>
        <div className="form-grid">
          <input
            type="text"
            placeholder="Nama Depan"
            value={formData.first_name}
            onChange={(e) => setFormData({...formData, first_name: e.target.value})}
            required
          />
          <input
            type="text"
            placeholder="Nama Belakang"
            value={formData.last_name}
            onChange={(e) => setFormData({...formData, last_name: e.target.value})}
            required
          />
          <input
            type="email"
            placeholder="Email"
            value={formData.email}
            onChange={(e) => setFormData({...formData, email: e.target.value})}
            required
          />
          <input
            type="tel"
            placeholder="No. HP"
            value={formData.phone}
            onChange={(e) => setFormData({...formData, phone: e.target.value})}
            required
          />
        </div>
      </section>

      {/* Shipping Address */}
      <section className="shipping-address">
        <h2>Shipping Address</h2>
        <textarea
          placeholder="Alamat Lengkap"
          value={formData.address}
          onChange={(e) => setFormData({...formData, address: e.target.value})}
          required
        />
        <div className="form-grid">
          <input
            type="text"
            placeholder="Kota"
            value={formData.city}
            onChange={(e) => setFormData({...formData, city: e.target.value})}
            required
          />
          <input
            type="text"
            placeholder="Kelurahan"
            value={formData.district}
            onChange={(e) => setFormData({...formData, district: e.target.value})}
            required
          />
          <input
            type="text"
            placeholder="Kecamatan"
            value={formData.sub_district}
            onChange={(e) => setFormData({...formData, sub_district: e.target.value})}
            required
          />
          <input
            type="text"
            placeholder="Kode Pos"
            value={formData.postal_code}
            onChange={(e) => setFormData({...formData, postal_code: e.target.value})}
            required
          />
        </div>
        <textarea
          placeholder="Notes (opsional)"
          value={formData.notes}
          onChange={(e) => setFormData({...formData, notes: e.target.value})}
        />
      </section>

      {/* Voucher */}
      <section className="voucher-section">
        <h2>Promo Code</h2>
        <input
          type="text"
          placeholder="Enter voucher code (optional)"
          value={formData.voucher_code}
          onChange={(e) => setFormData({...formData, voucher_code: e.target.value.toUpperCase()})}
        />
      </section>

      <button 
        onClick={handleCheckout} 
        disabled={processing}
        className="btn-checkout"
      >
        {processing ? 'Processing...' : 'Complete Purchase'}
      </button>
    </div>
  );
}
```

### **4. Order Status Page with Manual Polling**

```jsx
import { useState, useEffect } from 'react';
import { useRouter } from 'next/router';
import { useAuth } from '@/hooks/useAuth';

function OrderStatusPage() {
  const router = useRouter();
  const { orderNumber } = router.query;
  const { token } = useAuth();
  const [status, setStatus] = useState(null);
  const [polling, setPolling] = useState(true);

  useEffect(() => {
    if (!orderNumber) return;

    let interval;
    
    const checkStatus = async () => {
      try {
        const res = await fetch(
          `/api/customer/orders/${orderNumber}/payment-status`,
          {
            headers: { Authorization: `Bearer ${token}` }
          }
        );
        const data = await res.json();
        
        if (data.success) {
          setStatus(data.data);
          
          // Stop polling if payment is settled (paid or failed)
          if (['paid', 'failed', 'cancelled'].includes(data.data.order_status)) {
            setPolling(false);
            clearInterval(interval);
          }
        }
      } catch (error) {
        console.error('Error checking status:', error);
      }
    };

    // Initial check
    checkStatus();

    // Poll every 3 seconds
    if (polling) {
      interval = setInterval(checkStatus, 3000);
    }

    return () => {
      if (interval) clearInterval(interval);
    };
  }, [orderNumber, polling, token]);

  if (!status) {
    return <div>Checking payment status...</div>;
  }

  return (
    <div className="order-status-page">
      <h1>Payment Status</h1>
      
      <div className={`status-card status-${status.order_status}`}>
        {status.order_status === 'paid' && (
          <>
            <div className="icon-success">✓</div>
            <h2>Payment Successful!</h2>
            <p>Your order has been confirmed.</p>
            <p>Order Number: {status.order_number}</p>
            <p>Payment Method: {status.payment_type}</p>
            <button onClick={() => router.push('/orders')}>
              View My Orders
            </button>
          </>
        )}
        
        {status.order_status === 'pending' && (
          <>
            <div className="icon-pending">⏳</div>
            <h2>Payment Pending</h2>
            <p>Waiting for payment confirmation...</p>
            <p className="polling-indicator">
              Checking status automatically...
            </p>
          </>
        )}
        
        {status.order_status === 'failed' && (
          <>
            <div className="icon-failed">✗</div>
            <h2>Payment Failed</h2>
            <p>Your payment could not be processed.</p>
            <button onClick={() => router.push('/cart')}>
              Try Again
            </button>
          </>
        )}
      </div>
    </div>
  );
}
```

---

## Payment Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                    CUSTOMER PAYMENT FLOW                         │
└─────────────────────────────────────────────────────────────────┘

1. Browse Decorations
   └─> Add to Cart (POST /api/customer/cart/add)
       └─> Cart Page (GET /api/customer/cart)

2. Checkout
   └─> Apply Voucher (optional)
   └─> Click "Proceed to Payment"
       └─> POST /api/customer/checkout
           ├─> Validate cart
           ├─> Calculate total
           ├─> Create order (status: pending)
           ├─> Generate Midtrans Snap Token
           └─> Return: snap_token + client_key

3. Midtrans Payment Popup
   └─> window.snap.pay(snap_token)
       ├─> onSuccess → Redirect to status page
       ├─> onPending → Redirect to status page
       ├─> onError → Show error
       └─> onClose → User cancelled

4. Order Status Page (Manual Polling)
   └─> Poll every 3 seconds:
       GET /api/customer/orders/{orderNumber}/payment-status
       ├─> Query Midtrans API
       ├─> Update order status
       └─> Return latest status
   
   └─> Stop polling when:
       ├─> status = "paid" → Show success
       ├─> status = "failed" → Show error
       └─> status = "cancelled" → Show cancelled

5. Order Confirmation
   └─> View order details (GET /api/customer/orders/{id})
```

---

## Manual Polling Strategy

### Why Manual Polling Instead of Webhook?

User requested **no webhook/callback setup** karena:
- Tidak perlu konfigurasi di dashboard Midtrans
- Lebih simple untuk development
- Cocok untuk small-scale apps
- Frontend full control atas status checking

### Polling Implementation Best Practices

#### 1. Polling Interval
```javascript
// Recommended intervals:
const INITIAL_INTERVAL = 2000;  // 2 seconds for first 30 seconds
const NORMAL_INTERVAL = 5000;   // 5 seconds after 30 seconds
const MAX_ATTEMPTS = 60;        // Stop after 5 minutes

let attempts = 0;
let interval;

const startPolling = () => {
  const pollInterval = attempts < 15 ? INITIAL_INTERVAL : NORMAL_INTERVAL;
  
  interval = setInterval(async () => {
    attempts++;
    
    if (attempts >= MAX_ATTEMPTS) {
      clearInterval(interval);
      alert('Payment status check timeout. Please check your orders page.');
      return;
    }
    
    await checkPaymentStatus();
  }, pollInterval);
};
```

#### 2. Stop Conditions
```javascript
const checkPaymentStatus = async () => {
  const data = await fetchStatus();
  
  // Stop polling on final states
  const FINAL_STATES = ['paid', 'failed', 'cancelled', 'completed'];
  
  if (FINAL_STATES.includes(data.order_status)) {
    clearInterval(interval);
    handleFinalStatus(data.order_status);
  }
};
```

#### 3. Error Handling
```javascript
const checkPaymentStatus = async () => {
  try {
    const res = await fetch(`/api/customer/orders/${orderNumber}/payment-status`);
    
    if (!res.ok) {
      throw new Error('Failed to check status');
    }
    
    const data = await res.json();
    updateUI(data);
    
  } catch (error) {
    console.error('Polling error:', error);
    // Continue polling despite errors
    // Don't stop interval - transient errors are OK
  }
};
```

#### 4. User Experience Tips
- Show loading spinner during pending
- Display "Checking payment status..." message
- Add "Refresh Status" button for manual check
- Auto-redirect to orders page after success
- Provide "Need Help?" support link

---

## Admin Panel - Order Management UI

### **Orders List Page**

```jsx
function AdminOrdersPage() {
  const [orders, setOrders] = useState([]);
  const [filters, setFilters] = useState({
    status: '',
    search: '',
    start_date: '',
    end_date: ''
  });

  useEffect(() => {
    fetchOrders();
  }, [filters]);

  const fetchOrders = async () => {
    const params = new URLSearchParams(filters);
    const res = await fetch(`/api/admin/orders?${params}`, {
      headers: { Authorization: `Bearer ${adminToken}` }
    });
    const data = await res.json();
    setOrders(data.data.data);
  };

  const updateStatus = async (orderId, newStatus) => {
    await fetch(`/api/admin/orders/${orderId}/status`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${adminToken}`
      },
      body: JSON.stringify({ status: newStatus })
    });
    fetchOrders();
  };

  return (
    <div className="admin-orders">
      <h1>Order Management</h1>
      
      {/* Filters */}
      <div className="filters">
        <select 
          value={filters.status}
          onChange={(e) => setFilters({...filters, status: e.target.value})}
        >
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="paid">Paid</option>
          <option value="completed">Completed</option>
          <option value="failed">Failed</option>
          <option value="cancelled">Cancelled</option>
        </select>
        
        <input
          type="text"
          placeholder="Search order number"
          value={filters.search}
          onChange={(e) => setFilters({...filters, search: e.target.value})}
        />
      </div>

      {/* Orders Table */}
      <table className="orders-table">
        <thead>
          <tr>
            <th>Order Number</th>
            <th>Customer</th>
            <th>Total</th>
            <th>Status</th>
            <th>Payment Method</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {orders.map(order => (
            <tr key={order.id}>
              <td>{order.order_number}</td>
              <td>{order.user.name}</td>
              <td>Rp {order.total.toLocaleString()}</td>
              <td>
                <span className={`badge badge-${order.status}`}>
                  {order.status}
                </span>
              </td>
              <td>{order.payment_method || '-'}</td>
              <td>{new Date(order.created_at).toLocaleDateString()}</td>
              <td>
                <select
                  value={order.status}
                  onChange={(e) => updateStatus(order.id, e.target.value)}
                >
                  <option value="pending">Pending</option>
                  <option value="paid">Paid</option>
                  <option value="completed">Completed</option>
                  <option value="failed">Failed</option>
                  <option value="cancelled">Cancelled</option>
                </select>
                
                <button onClick={() => window.location.href = `/admin/orders/${order.id}`}>
                  View Details
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
```

### **Dashboard Statistics Widget**

```jsx
function OrderStatisticsWidget() {
  const [stats, setStats] = useState(null);

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    const res = await fetch('/api/admin/orders/statistics', {
      headers: { Authorization: `Bearer ${adminToken}` }
    });
    const data = await res.json();
    setStats(data.data);
  };

  if (!stats) return <div>Loading...</div>;

  return (
    <div className="order-stats-grid">
      <div className="stat-card">
        <h3>Total Orders</h3>
        <p className="stat-number">{stats.total_orders}</p>
      </div>
      
      <div className="stat-card">
        <h3>Pending</h3>
        <p className="stat-number">{stats.pending_orders}</p>
      </div>
      
      <div className="stat-card">
        <h3>Total Revenue</h3>
        <p className="stat-number">
          Rp {stats.total_revenue.toLocaleString()}
        </p>
      </div>
      
      <div className="stat-card">
        <h3>This Month</h3>
        <p className="stat-number">{stats.orders_this_month} orders</p>
        <p className="stat-sub">
          Rp {stats.revenue_this_month.toLocaleString()}
        </p>
      </div>
    </div>
  );
}
```

---

## CSS Examples

### Checkout Page Styling
```css
.checkout-page {
  max-width: 800px;
  margin: 0 auto;
  padding: 40px 20px;
}

.checkout-page section {
  background: white;
  padding: 24px;
  border-radius: 8px;
  margin-bottom: 24px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.checkout-page h2 {
  font-size: 18px;
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 16px;
}

.form-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 16px;
}

@media (max-width: 640px) {
  .form-grid {
    grid-template-columns: 1fr;
  }
}

.checkout-page input,
.checkout-page textarea {
  width: 100%;
  padding: 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.checkout-page input:focus,
.checkout-page textarea:focus {
  outline: none;
  border-color: #3b82f6;
  ring: 2px solid rgba(59, 130, 246, 0.2);
}

.checkout-page textarea {
  resize: vertical;
  min-height: 80px;
}

.btn-checkout {
  width: 100%;
  padding: 16px;
  background: #000;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}

.btn-checkout:hover:not(:disabled) {
  background: #333;
}

.btn-checkout:disabled {
  background: #9ca3af;
  cursor: not-allowed;
}
```

### Cart Page Styling
```css
.cart-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 40px 20px;
}

.cart-item {
  display: flex;
  gap: 20px;
  padding: 20px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  margin-bottom: 16px;
  align-items: center;
}

.cart-item img {
  width: 120px;
  height: 120px;
  object-fit: cover;
  border-radius: 8px;
}

.quantity-control {
  display: flex;
  gap: 10px;
  align-items: center;
}

.quantity-control button {
  width: 32px;
  height: 32px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  background: white;
  cursor: pointer;
}

.cart-summary {
  background: #f9fafb;
  padding: 24px;
  border-radius: 8px;
  margin-top: 24px;
}

.btn-add-to-cart {
  width: 100%;
  padding: 16px;
  background: #000;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
}

.btn-add-to-cart:hover {
  background: #333;
}
```

### Order Status Styling
```css
.order-status-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.status-card {
  background: white;
  padding: 48px;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  text-align: center;
  max-width: 500px;
}

.icon-success {
  width: 80px;
  height: 80px;
  background: #22c55e;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 48px;
  margin: 0 auto 24px;
}

.icon-pending {
  width: 80px;
  height: 80px;
  background: #f59e0b;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 48px;
  margin: 0 auto 24px;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.5; }
}

.polling-indicator {
  color: #6b7280;
  font-size: 14px;
  margin-top: 16px;
}
```

---

## Complete Payment Integration

### 🎯 Payment Flow dengan QRIS dan Virtual Account

Untuk **dokumentasi lengkap** mengenai:
- ✅ Alur pembayaran QRIS, VA, GoPay, ShopeePay, Credit Card
- ✅ Cara simulate payment di Midtrans Sandbox Dashboard
- ✅ Implementation guide Midtrans Snap popup
- ✅ Status polling logic dan error handling
- ✅ Step-by-step testing guide
- ✅ Production deployment checklist

**📖 Baca:** [**README_PAYMENT_INTEGRATION.md**](./README_PAYMENT_INTEGRATION.md)

### Quick Reference

**Checkout Response:**
```json
{
  "success": true,
  "data": {
    "order": { ... },
    "snap_token": "66e4fa55-fdac-4ef9-91b5-733b97d1b862",
    "client_key": "SB-Mid-client-abc123xyz"
  }
}
```

**Open Payment Popup:**
```javascript
window.snap.pay(snap_token, {
  onPending: function(result) {
    // Redirect ke order status page untuk polling
    router.push(`/order-status/${orderNumber}`);
  },
  onClose: function() {
    // User tutup popup, tetap redirect ke polling page
    router.push(`/order-status/${orderNumber}`);
  }
});
```

**Polling Status:**
```javascript
// Poll setiap 3 detik
const interval = setInterval(async () => {
  const status = await checkPaymentStatus(orderNumber);
  
  if (status.transaction_status === 'settlement') {
    clearInterval(interval);
    router.push(`/order-success/${orderNumber}`);
  }
}, 3000);
```

**Simulate Payment di Dashboard:**
```
1. https://dashboard.sandbox.midtrans.com/
2. Transactions → Find order by order_number
3. Click "Simulate Payment"
4. Pilih "Success"
5. Submit → Status jadi "settlement"
6. Frontend polling akan detect dan redirect ke success page
```

---

## Summary

**✅ System Features:**
- Cart management (add, update, remove, clear)
- Checkout with voucher support
- Midtrans payment integration (Snap)
- Manual polling untuk payment status (no webhook)
- Admin order management
- Order statistics & reports

**Frontend Tasks:**
1. Cart page: Display items, update quantity, remove items
2. Decoration detail: Add to cart button
3. Checkout page: Apply voucher, proceed to payment
4. Midtrans integration: Load Snap.js, handle payment popup
5. Order status page: Manual polling every 3 seconds
6. Admin orders: List, filter, update status, view statistics

**Testing Checklist:**
- [ ] Add decoration to cart
- [ ] Update cart item quantity
- [ ] Remove cart item
- [ ] Apply valid voucher at checkout
- [ ] Checkout creates order with pending status
- [ ] Midtrans Snap popup opens
- [ ] Complete payment with test card
- [ ] Status page polls and updates automatically
- [ ] Order status changes to "paid" after settlement
- [ ] Admin can view and manage all orders
