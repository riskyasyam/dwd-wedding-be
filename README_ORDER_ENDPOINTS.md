# Order API Endpoints - Updated Documentation

## ⚠️ IMPORTANT CHANGES

**Checkout endpoint has been updated:**
- ❌ Old: `POST /api/customer/checkout`
- ✅ New: `POST /api/customer/orders/checkout`

**Payment status endpoint has been updated:**
- ❌ Old: `GET /api/customer/orders/{orderNumber}/payment-status`
- ✅ New: `GET /api/customer/orders/payment-status/{orderNumber}`

---

## 📋 Complete Order API Reference

### Customer Endpoints

#### 1. Get All Orders (Customer)

**Endpoint:**
```http
GET /api/customer/orders
Authorization: Bearer {token}
```

**Query Parameters:**
- `status` (optional): Filter by status (`pending`, `paid`, `failed`, `completed`, `cancelled`)
- `page` (optional): Page number for pagination (default: 1)

**Example:**
```http
GET /api/customer/orders?status=paid
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
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
        "status": "paid",
        "payment_method": "qris",
        "notes": "Pengiriman siang hari",
        "created_at": "2024-12-07T10:00:00.000000Z",
        "updated_at": "2024-12-07T10:05:00.000000Z",
        "items": [
          {
            "id": 1,
            "order_id": 1,
            "decoration_id": 5,
            "type": "Custom",
            "quantity": 1,
            "price": 72000000,
            "created_at": "2024-12-07T10:00:00.000000Z",
            "updated_at": "2024-12-07T10:00:00.000000Z",
            "decoration": {
              "id": 5,
              "name": "PURE ELEGANCE 6",
              "slug": "pure-elegance-6",
              "description": "Premium wedding decoration...",
              "base_price": 72000000,
              "discount_percent": 0,
              "images": [
                {
                  "id": 1,
                  "decoration_id": 5,
                  "image_url": "http://localhost:8000/storage/decorations/image1.jpg",
                  "is_primary": true
                }
              ]
            }
          }
        ]
      }
    ],
    "first_page_url": "http://localhost:8000/api/customer/orders?page=1",
    "from": 1,
    "last_page": 1,
    "last_page_url": "http://localhost:8000/api/customer/orders?page=1",
    "next_page_url": null,
    "path": "http://localhost:8000/api/customer/orders",
    "per_page": 10,
    "prev_page_url": null,
    "to": 1,
    "total": 1
  }
}
```

**Frontend Implementation:**
```javascript
// React/Next.js
const fetchOrders = async (status = null) => {
  try {
    const params = new URLSearchParams();
    if (status) params.append('status', status);
    
    const response = await axios.get(
      `/api/customer/orders?${params.toString()}`,
      {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      }
    );
    
    if (response.data.success) {
      setOrders(response.data.data.data); // Pagination data
      setPagination({
        currentPage: response.data.data.current_page,
        lastPage: response.data.data.last_page,
        total: response.data.data.total
      });
    }
  } catch (error) {
    console.error('Fetch orders error:', error);
  }
};

// Usage
useEffect(() => {
  fetchOrders(); // All orders
  // fetchOrders('paid'); // Only paid orders
}, []);
```

---

#### 2. Get Single Order Detail (Customer)

**Endpoint:**
```http
GET /api/customer/orders/{id}
Authorization: Bearer {token}
```

**Example:**
```http
GET /api/customer/orders/1
```

**Response (200):**
```json
{
  "success": true,
  "data": {
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
    "status": "paid",
    "payment_method": "qris",
    "notes": "Pengiriman siang hari",
    "created_at": "2024-12-07T10:00:00.000000Z",
    "updated_at": "2024-12-07T10:05:00.000000Z",
    "user": {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "email": "john@example.com",
      "phone": "+628123456789"
    },
    "items": [
      {
        "id": 1,
        "order_id": 1,
        "decoration_id": 5,
        "type": "Custom",
        "quantity": 1,
        "price": 72000000,
        "decoration": {
          "id": 5,
          "name": "PURE ELEGANCE 6",
          "base_price": 72000000,
          "images": [...]
        }
      }
    ]
  }
}
```

**Frontend Implementation:**
```javascript
const fetchOrderDetail = async (orderId) => {
  try {
    const response = await axios.get(
      `/api/customer/orders/${orderId}`,
      {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      }
    );
    
    if (response.data.success) {
      setOrder(response.data.data);
    }
  } catch (error) {
    console.error('Fetch order detail error:', error);
  }
};
```

---

#### 3. Checkout (Create Order)

**⚠️ ENDPOINT UPDATED**

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
      "total": 64800000,
      "status": "pending",
      "items": [...]
    },
    "snap_token": "66e4fa55-fdac-4ef9-91b5-733b97d1b862",
    "client_key": "SB-Mid-client-OQLH8lL8x5Ltx5Vk"
  }
}
```

**Frontend Implementation:**
```javascript
const handleCheckout = async (formData) => {
  try {
    const response = await axios.post(
      '/api/customer/orders/checkout', // ✅ Updated path
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
      
      // Open Midtrans Snap popup
      window.snap.pay(snap_token, {
        onPending: function(result) {
          router.push(`/order-status/${order.order_number}`);
        },
        onClose: function() {
          router.push(`/order-status/${order.order_number}`);
        }
      });
    }
  } catch (error) {
    console.error('Checkout error:', error);
    alert(error.response?.data?.message || 'Checkout failed');
  }
};
```

---

#### 4. Check Payment Status (Polling)

**⚠️ ENDPOINT UPDATED**

**Endpoint:**
```http
GET /api/customer/orders/payment-status/{orderNumber}
Authorization: Bearer {token}
```

**Example:**
```http
GET /api/customer/orders/payment-status/ORD-1733587200-A1B2C3
```

**Response (200):**
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

**Frontend Implementation:**
```javascript
const checkPaymentStatus = async (orderNumber) => {
  try {
    const response = await axios.get(
      `/api/customer/orders/payment-status/${orderNumber}`, // ✅ Updated path
      {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      }
    );

    if (response.data.success) {
      const data = response.data.data;
      setOrder(data);

      // Check if payment settled
      if (data.transaction_status === 'settlement' || data.order_status === 'paid') {
        clearInterval(pollingInterval);
        router.push(`/order-success/${orderNumber}`);
      }
    }
  } catch (error) {
    console.error('Check status error:', error);
  }
};

// Start polling
useEffect(() => {
  const interval = setInterval(() => {
    checkPaymentStatus(orderNumber);
  }, 3000);

  return () => clearInterval(interval);
}, [orderNumber]);
```

---

#### 5. Cancel Order (Customer)

**Endpoint:**
```http
PUT /api/customer/orders/{id}/cancel
Authorization: Bearer {token}
```

**Example:**
```http
PUT /api/customer/orders/1/cancel
```

**Response (200):**
```json
{
  "success": true,
  "message": "Order cancelled successfully",
  "data": {
    "id": 1,
    "order_number": "ORD-1733587200-A1B2C3",
    "status": "cancelled",
    "total": 64800000
  }
}
```

**Error Response (400):**
```json
{
  "success": false,
  "message": "Only pending orders can be cancelled"
}
```

**Frontend Implementation:**
```javascript
const cancelOrder = async (orderId) => {
  if (!confirm('Are you sure you want to cancel this order?')) {
    return;
  }

  try {
    const response = await axios.put(
      `/api/customer/orders/${orderId}/cancel`,
      {},
      {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
      }
    );

    if (response.data.success) {
      alert('Order cancelled successfully');
      fetchOrders(); // Refresh orders list
    }
  } catch (error) {
    alert(error.response?.data?.message || 'Failed to cancel order');
  }
};
```

---

### Admin Endpoints

#### 1. Get All Orders (Admin)

**Endpoint:**
```http
GET /api/admin/orders
Authorization: Bearer {admin_token}
```

**Query Parameters:**
- `status` (optional): Filter by status
- `user_id` (optional): Filter by user
- `search` (optional): Search by order number
- `start_date` (optional): Filter from date (YYYY-MM-DD)
- `end_date` (optional): Filter to date (YYYY-MM-DD)
- `page` (optional): Page number

**Example:**
```http
GET /api/admin/orders?status=paid&page=1
GET /api/admin/orders?search=ORD-1733
GET /api/admin/orders?start_date=2024-12-01&end_date=2024-12-31
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "order_number": "ORD-1733587200-A1B2C3",
        "total": 64800000,
        "status": "paid",
        "payment_method": "qris",
        "created_at": "2024-12-07T10:00:00.000000Z",
        "user": {
          "id": 1,
          "first_name": "John",
          "last_name": "Doe",
          "email": "john@example.com"
        },
        "items": [...]
      }
    ],
    "per_page": 20,
    "total": 1
  }
}
```

**Frontend Implementation:**
```javascript
const fetchAdminOrders = async (filters = {}) => {
  try {
    const params = new URLSearchParams();
    if (filters.status) params.append('status', filters.status);
    if (filters.search) params.append('search', filters.search);
    if (filters.start_date) params.append('start_date', filters.start_date);
    if (filters.end_date) params.append('end_date', filters.end_date);
    
    const response = await axios.get(
      `/api/admin/orders?${params.toString()}`,
      {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('adminToken')}`
        }
      }
    );
    
    if (response.data.success) {
      setOrders(response.data.data.data);
    }
  } catch (error) {
    console.error('Fetch admin orders error:', error);
  }
};
```

---

#### 2. Get Single Order Detail (Admin)

**Endpoint:**
```http
GET /api/admin/orders/{id}
Authorization: Bearer {admin_token}
```

**Response:** Same as customer order detail

---

#### 3. Update Order Status (Admin)

**Endpoint:**
```http
PUT /api/admin/orders/{id}/status
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "status": "completed"
}
```

**Valid Status Values:**
- `pending` - Menunggu pembayaran
- `paid` - Sudah dibayar
- `failed` - Pembayaran gagal
- `completed` - Order selesai (dekorasi sudah dipasang)
- `cancelled` - Order dibatalkan

**Response (200):**
```json
{
  "success": true,
  "message": "Order status updated successfully",
  "data": {
    "id": 1,
    "order_number": "ORD-1733587200-A1B2C3",
    "status": "completed",
    "total": 64800000
  }
}
```

**Frontend Implementation:**
```javascript
const updateOrderStatus = async (orderId, newStatus) => {
  try {
    const response = await axios.put(
      `/api/admin/orders/${orderId}/status`,
      { status: newStatus },
      {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('adminToken')}`,
          'Content-Type': 'application/json'
        }
      }
    );

    if (response.data.success) {
      alert('Order status updated successfully');
      fetchAdminOrders(); // Refresh list
    }
  } catch (error) {
    alert('Failed to update order status');
  }
};

// Status dropdown component
<select onChange={(e) => updateOrderStatus(order.id, e.target.value)}>
  <option value="pending">Pending</option>
  <option value="paid">Paid</option>
  <option value="completed">Completed</option>
  <option value="failed">Failed</option>
  <option value="cancelled">Cancelled</option>
</select>
```

---

#### 4. Get Order Statistics (Admin)

**Endpoint:**
```http
GET /api/admin/orders/statistics
Authorization: Bearer {admin_token}
```

**Response (200):**
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

**Frontend Implementation:**
```javascript
const fetchOrderStatistics = async () => {
  try {
    const response = await axios.get(
      '/api/admin/orders/statistics',
      {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('adminToken')}`
        }
      }
    );

    if (response.data.success) {
      setStats(response.data.data);
    }
  } catch (error) {
    console.error('Fetch statistics error:', error);
  }
};

// Display in dashboard
<div className="stats-grid">
  <div className="stat-card">
    <h3>Total Orders</h3>
    <p>{stats.total_orders}</p>
  </div>
  <div className="stat-card">
    <h3>Total Revenue</h3>
    <p>Rp {stats.total_revenue.toLocaleString('id-ID')}</p>
  </div>
  <div className="stat-card">
    <h3>This Month</h3>
    <p>{stats.orders_this_month} orders</p>
    <p>Rp {stats.revenue_this_month.toLocaleString('id-ID')}</p>
  </div>
</div>
```

---

#### 5. Get Recent Orders (Admin)

**Endpoint:**
```http
GET /api/admin/orders/recent/{limit}
Authorization: Bearer {admin_token}
```

**Example:**
```http
GET /api/admin/orders/recent/10
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "order_number": "ORD-1733587200-A1B2C3",
      "total": 64800000,
      "status": "paid",
      "user": {
        "first_name": "John",
        "last_name": "Doe"
      },
      "created_at": "2024-12-07T10:00:00.000000Z"
    }
  ]
}
```

**Frontend Implementation:**
```javascript
const fetchRecentOrders = async (limit = 10) => {
  try {
    const response = await axios.get(
      `/api/admin/orders/recent/${limit}`,
      {
        headers: {
          'Authorization': `Bearer ${localStorage.getItem('adminToken')}`
        }
      }
    );

    if (response.data.success) {
      setRecentOrders(response.data.data);
    }
  } catch (error) {
    console.error('Fetch recent orders error:', error);
  }
};
```

---

## 🔄 Migration Guide

### Update Your Frontend Code

**1. Update Checkout Endpoint:**
```javascript
// ❌ Old
axios.post('/api/customer/checkout', data)

// ✅ New
axios.post('/api/customer/orders/checkout', data)
```

**2. Update Payment Status Endpoint:**
```javascript
// ❌ Old
axios.get(`/api/customer/orders/${orderNumber}/payment-status`)

// ✅ New
axios.get(`/api/customer/orders/payment-status/${orderNumber}`)
```

**3. Ensure Authentication:**
All endpoints require Bearer token in Authorization header:
```javascript
headers: {
  'Authorization': `Bearer ${localStorage.getItem('token')}`
}
```

---

## 📊 Order Status Flow

```
pending → paid → completed
   ↓         ↓
cancelled  failed
```

**Status Descriptions:**
- `pending`: Order dibuat, menunggu pembayaran
- `paid`: Pembayaran berhasil (settlement dari Midtrans)
- `completed`: Order selesai, dekorasi sudah terpasang
- `failed`: Pembayaran gagal/ditolak
- `cancelled`: Order dibatalkan oleh customer (hanya bisa jika status pending)

---

## 🧪 Testing Endpoints

### Postman Collection

**Environment Variables:**
```
base_url: http://localhost:8000
token: {your_bearer_token}
admin_token: {admin_bearer_token}
```

**Test Sequence:**
1. Login customer → Get token
2. Add items to cart → POST /api/customer/cart/add
3. Checkout → POST /api/customer/orders/checkout
4. Open Midtrans popup → Use snap_token
5. Simulate payment in Midtrans dashboard
6. Poll status → GET /api/customer/orders/payment-status/{orderNumber}
7. Verify order → GET /api/customer/orders
8. Admin view → GET /api/admin/orders

---

## 🐛 Troubleshooting

### Orders Not Showing in Dashboard

**Possible Causes:**
1. ✅ **Using wrong endpoint path** - Use updated paths above
2. ✅ **Missing Authorization header** - Add Bearer token
3. ✅ **Token expired** - Re-login to get new token
4. ✅ **Order belongs to different user** - Customer can only see own orders
5. ✅ **Database empty** - Create test order first

**Debug Steps:**
```javascript
// Check if token exists
console.log('Token:', localStorage.getItem('token'));

// Check API response
const response = await axios.get('/api/customer/orders', {
  headers: { 'Authorization': `Bearer ${token}` }
});
console.log('Orders:', response.data);

// Check for errors
if (!response.data.success) {
  console.error('Error:', response.data.message);
}
```

### Order Created But Not Visible

**Check:**
1. Order successfully created (check database `orders` table)
2. `user_id` matches authenticated user
3. Order items created (check `order_items` table)
4. Using correct authentication token
5. Frontend calling correct endpoint

---

## 📞 Support

Jika masih ada masalah:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check browser console untuk error JavaScript
3. Verify token dengan: `GET /api/customer/profile`
4. Test endpoint dengan Postman/Thunder Client

---

**Happy Coding! 🚀**
