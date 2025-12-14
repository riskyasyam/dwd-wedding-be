# Down Payment (DP) System - Complete Documentation

## Overview
Sistem pembayaran dengan opsi DP (Down Payment / Uang Muka) yang memungkinkan customer membayar sebagian dulu, kemudian melunasi sisanya kapan saja.

---

## Database Changes

### Decorations Table
Tambahan kolom:
- `minimum_dp_percentage` (integer, default: 30) - Minimum percentage DP untuk decoration tersebut

### Orders Table
Tambahan kolom:
- `payment_type` (enum: 'full', 'dp', default: 'full') - Tipe pembayaran
- `dp_amount` (bigInteger, default: 0) - Jumlah DP yang dibayar
- `remaining_amount` (bigInteger, default: 0) - Sisa yang harus dibayar
- `dp_paid_at` (timestamp, nullable) - Waktu DP dibayar
- `full_paid_at` (timestamp, nullable) - Waktu lunas
- `dp_snap_token` (string, nullable) - Midtrans snap token untuk DP
- `remaining_snap_token` (string, nullable) - Midtrans snap token untuk sisa pembayaran

---

## Order Status Flow

### Status Values:
1. **`pending`** - Order created, belum bayar sama sekali
2. **`dp_paid`** - Sudah bayar DP, belum lunas (NEW!)
3. **`paid`** - Sudah lunas (full payment ATAU DP + remaining)
4. **`processing`** - Sedang diproses
5. **`completed`** - Selesai
6. **`failed`** - Payment failed/expired/cancelled
7. **`cancelled`** - Order dibatalkan

### Payment Flow:

#### Full Payment:
```
pending → (pay full amount) → paid → processing → completed
```

#### DP Payment:
```
pending → (pay DP) → dp_paid → (pay remaining) → paid → processing → completed
```

### Status Update Logic:

**Backend automatically updates status via `checkPaymentStatus()` endpoint:**

1. **Midtrans callback with 'settlement' or 'capture':**
   - If `payment_type = 'dp'` AND `remaining_amount > 0` → status = **'dp_paid'**
   - If `payment_type = 'full'` → status = **'paid'**
   - If order_number ends with '-REMAINING' → status = **'paid'** (pelunasan)

2. **Frontend should poll this endpoint after payment:**
   ```
   GET /api/customer/orders/payment-status/{orderNumber}
   ```

---

## API Endpoints

### 1. Get Decoration with DP Info

**Endpoint:** `GET /api/public/decorations/{id}`

**Response includes:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "PURE ELEGANCE",
    "base_price": 40000000,
    "final_price": 36000000,
    "minimum_dp_percentage": 30,
    "...": "other fields"
  }
}
```

---

### 2. Checkout with Payment Type

**Endpoint:** `POST /api/customer/orders/checkout`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
```

**Request Body:**
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "08123456789",
  "address": "Jl. Example No. 123",
  "city": "Jakarta",
  "district": "Jakarta Selatan",
  "sub_district": "Kebayoran Baru",
  "postal_code": "12345",
  "payment_type": "dp",
  "voucher_code": "DISC10",
  "notes": "Optional notes"
}
```

**Payment Type Options:**
- `"full"` - Bayar penuh
- `"dp"` - Bayar DP saja

**Response (DP Payment):**
```json
{
  "success": true,
  "snap_token": "abc123-midtrans-snap-token",
  "order": {
    "id": 123,
    "order_number": "ORD-1702281234-ABC123",
    "subtotal": 36000000,
    "voucher_discount": 3600000,
    "total": 32400000,
    "payment_type": "dp",
    "dp_amount": 9720000,
    "remaining_amount": 22680000,
    "status": "pending",
    "...": "other fields"
  }
}
```

**DP Calculation:**
- Total setelah diskon: Rp 32.400.000
- Minimum DP (30%): Rp 9.720.000
- Sisa: Rp 22.680.000

---

### 3. Pay Remaining Amount

**Endpoint:** `POST /api/customer/orders/{id}/pay-remaining`

**Headers:**
```
Authorization: Bearer YOUR_TOKEN
```

**Requirements:**
- Order `payment_type` must be `"dp"`
- Order `status` must be `"dp_paid"`
- Order `remaining_amount` > 0

**Response:**
```json
{
  "success": true,
  "snap_token": "xyz789-remaining-payment-token",
  "remaining_amount": 22680000,
  "order": {
    "id": 123,
    "order_number": "ORD-1702281234-ABC123",
    "payment_type": "dp",
    "dp_amount": 9720000,
    "remaining_amount": 22680000,
    "status": "dp_paid",
    "dp_paid_at": "2025-12-14T10:30:00.000000Z",
    "...": "other fields"
  }
}
```

---

### 4. Check Payment Status

**Endpoint:** `GET /api/customer/orders/payment-status/{orderNumber}`

**Response:**
```json
{
  "success": true,
  "order": {
    "id": 123,
    "order_number": "ORD-1702281234-ABC123",
    "status": "dp_paid",
    "payment_type": "dp",
    "total": 32400000,
    "dp_amount": 9720000,
    "remaining_amount": 22680000,
    "dp_paid_at": "2025-12-14T10:30:00.000000Z",
    "full_paid_at": null
  }
}
```

---

## Admin Features

### 1. Set Minimum DP Percentage

**Endpoint:** `PUT /api/admin/decorations/{id}`

**Request Body:**
```json
{
  "name": "PURE ELEGANCE",
  "region": "Jabodetabek",
  "description": "...",
  "base_price": 40000000,
  "discount_percent": 10,
  "is_deals": true,
  "minimum_dp_percentage": 30
}
```

**Validation:**
- `minimum_dp_percentage`: integer, min 10%, max 100%
- Default: 30% if not provided

---

### 2. View Orders by Status

**Endpoint:** `GET /api/admin/orders?status={status}`

**Status Filter Options:**
- `pending` - Belum bayar
- `dp_paid` - Sudah DP, belum lunas ✨ NEW!
- `paid` - Sudah lunas
- `processing` - Sedang diproses
- `completed` - Selesai
- `failed` - Payment failed/expired
- `cancelled` - Dibatalkan

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 123,
        "order_number": "ORD-1702281234-ABC123",
        "user": {
          "id": 1,
          "first_name": "John",
          "last_name": "Doe"
        },
        "total": 32400000,
        "payment_type": "dp",
        "dp_amount": 9720000,
        "remaining_amount": 22680000,
        "status": "dp_paid",
        "dp_paid_at": "2025-12-14T10:30:00.000000Z",
        "full_paid_at": null,
        "created_at": "2025-12-14T10:00:00.000000Z"
      }
    ],
    "per_page": 20,
    "total": 50
  }
}
```

---

### 3. Order Statistics

**Endpoint:** `GET /api/admin/orders/statistics`

**Response includes DP metrics:**
```json
{
  "success": true,
  "data": {
    "total_orders": 150,
    "pending_orders": 10,
    "dp_paid_orders": 15,
    "paid_orders": 80,
    "processing_orders": 20,
    "completed_orders": 20,
    "failed_orders": 3,
    "cancelled_orders": 2,
    "total_revenue": 1500000000,
    "dp_revenue": 150000000,
    "orders_this_month": 25,
    "revenue_this_month": 300000000
  }
}
```

**New fields:**
- `dp_paid_orders`: Count of orders with status 'dp_paid' (belum lunas)
- `processing_orders`: Count of orders being processed
- `dp_revenue`: Total DP amount collected from dp_paid orders

---

### 4. Update Order Status

**Endpoint:** `PUT /api/admin/orders/{id}/status`

**Request Body:**
```json
{
  "status": "dp_paid"
}
```

**Allowed status values:**
- `pending`
- `dp_paid` ✨ NEW!
- `paid`
- `processing` ✨ NEW!
- `completed`
- `failed`
- `cancelled`

---

### 5. Export Orders (Updated)

**Endpoint:** `GET /api/admin/orders/export?status={status}`

Excel file now includes new columns:
- Payment Type (Full/DP)
- DP Amount
- Remaining Amount
- DP Paid Date
- Full Paid Date

---

## Frontend Implementation

### 1. Display DP Information

```typescript
interface Decoration {
  id: number;
  name: string;
  base_price: number;
  final_price: number;
  minimum_dp_percentage: number; // NEW!
}

const DecorationCard = ({ decoration }: { decoration: Decoration }) => {
  const dpAmount = Math.ceil(decoration.final_price * decoration.minimum_dp_percentage / 100);
  
  return (
    <div>
      <h3>{decoration.name}</h3>
      <p>Harga: Rp {decoration.final_price.toLocaleString()}</p>
      <p>Minimal DP ({decoration.minimum_dp_percentage}%): 
         Rp {dpAmount.toLocaleString()}
      </p>
    </div>
  );
};
```

---

### 2. Checkout with Payment Type Selection

```typescript
const CheckoutPage = () => {
  const [paymentType, setPaymentType] = useState<'full' | 'dp'>('full');
  const [total, setTotal] = useState(32400000);
  const [minimumDp, setMinimumDp] = useState(30);
  
  const dpAmount = Math.ceil(total * minimumDp / 100);
  const remainingAmount = total - dpAmount;

  const handleCheckout = async () => {
    const response = await api.post('/customer/orders/checkout', {
      // ... personal details
      payment_type: paymentType,
      // ... other fields
    });

    if (response.data.success) {
      // Open Midtrans Snap
      snap.pay(response.data.snap_token, {
        onSuccess: (result) => {
          console.log('Payment success:', result);
          router.push(`/orders/${response.data.order.id}`);
        },
        onPending: (result) => {
          console.log('Payment pending:', result);
        },
        onError: (result) => {
          console.error('Payment error:', result);
        },
        onClose: () => {
          console.log('Payment popup closed');
        }
      });
    }
  };

  return (
    <div>
      <h2>Pilih Tipe Pembayaran</h2>
      
      <label>
        <input
          type="radio"
          value="full"
          checked={paymentType === 'full'}
          onChange={(e) => setPaymentType(e.target.value as 'full' | 'dp')}
        />
        Bayar Penuh - Rp {total.toLocaleString()}
      </label>

      <label>
        <input
          type="radio"
          value="dp"
          checked={paymentType === 'dp'}
          onChange={(e) => setPaymentType(e.target.value as 'full' | 'dp')}
        />
        Bayar DP ({minimumDp}%) - Rp {dpAmount.toLocaleString()}
        <small>Sisa: Rp {remainingAmount.toLocaleString()}</small>
      </label>

      <button onClick={handleCheckout}>Checkout</button>
    </div>
  );
};
```

---

### 3. Pay Remaining Amount

```typescript
const OrderDetailPage = ({ orderId }: { orderId: number }) => {
  const [order, setOrder] = useState<Order | null>(null);

  const handlePayRemaining = async () => {
    try {
      const response = await api.post(`/customer/orders/${orderId}/pay-remaining`);
      
      if (response.data.success) {
        // Open Midtrans Snap for remaining payment
        snap.pay(response.data.snap_token, {
          onSuccess: (result) => {
            console.log('Remaining payment success:', result);
            // Reload order status
            fetchOrder();
          },
          onPending: (result) => {
            console.log('Payment pending:', result);
          },
          onError: (result) => {
            console.error('Payment error:', result);
          }
        });
      }
    } catch (error) {
      console.error('Failed to pay remaining:', error);
    }
  };

  if (!order) return <div>Loading...</div>;

  return (
    <div>
      <h2>Order #{order.order_number}</h2>
      <p>Status: {order.status}</p>
      <p>Total: Rp {order.total.toLocaleString()}</p>
      
      {order.payment_type === 'dp' && (
        <div className="dp-info">
          <h3>Informasi DP</h3>
          <p>DP Dibayar: Rp {order.dp_amount.toLocaleString()}</p>
          <p>Sisa: Rp {order.remaining_amount.toLocaleString()}</p>
          
          {order.status === 'dp_paid' && order.remaining_amount > 0 && (
            <button onClick={handlePayRemaining}>
              Bayar Sisa (Rp {order.remaining_amount.toLocaleString()})
            </button>
          )}
        </div>
      )}
    </div>
  );
};
```

---

### 4. Admin Order Management

```typescript
const AdminOrdersPage = () => {
  const [statusFilter, setStatusFilter] = useState('');
  const [orders, setOrders] = useState<Order[]>([]);

  const statusOptions = [
    { value: '', label: 'All Status' },
    { value: 'pending', label: 'Pending' },
    { value: 'dp_paid', label: 'Belum Lunas / DP' },
    { value: 'paid', label: 'Paid / Lunas' },
    { value: 'processing', label: 'Processing' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
  ];

  const fetchOrders = async () => {
    const response = await api.get('/admin/orders', {
      params: { status: statusFilter }
    });
    setOrders(response.data.data.data);
  };

  return (
    <div>
      <h1>Order Management</h1>
      
      <select value={statusFilter} onChange={(e) => setStatusFilter(e.target.value)}>
        {statusOptions.map(opt => (
          <option key={opt.value} value={opt.value}>{opt.label}</option>
        ))}
      </select>

      <table>
        <thead>
          <tr>
            <th>Order Number</th>
            <th>Customer</th>
            <th>Total</th>
            <th>Payment Type</th>
            <th>DP Amount</th>
            <th>Remaining</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          {orders.map(order => (
            <tr key={order.id}>
              <td>{order.order_number}</td>
              <td>{order.user.first_name} {order.user.last_name}</td>
              <td>Rp {order.total.toLocaleString()}</td>
              <td>{order.payment_type === 'dp' ? 'DP' : 'Full'}</td>
              <td>
                {order.payment_type === 'dp' 
                  ? `Rp ${order.dp_amount.toLocaleString()}`
                  : '-'
                }
              </td>
              <td>
                {order.payment_type === 'dp' 
                  ? `Rp ${order.remaining_amount.toLocaleString()}`
                  : '-'
                }
              </td>
              <td>
                <span className={`badge badge-${order.status}`}>
                  {getStatusLabel(order.status)}
                </span>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

const getStatusLabel = (status: string) => {
  const labels = {
    'pending': 'Pending',
    'dp_paid': 'Belum Lunas / DP',
    'paid': 'Paid / Lunas',
    'processing': 'Processing',
    'completed': 'Completed',
    'cancelled': 'Cancelled',
  };
  return labels[status] || status;
};
```

---

## Validation Rules

### Minimum DP Percentage:
- **Minimum:** 10%
- **Maximum:** 100%
- **Default:** 30%
- **Admin dapat custom per decoration**

### Payment Flow Rules:
1. ✅ User dapat pilih `full` atau `dp` saat checkout
2. ✅ Jika pilih `dp`, sistem otomatis hitung DP amount berdasarkan `minimum_dp_percentage`
3. ✅ Midtrans snap token untuk DP menggunakan `dp_amount`, bukan `total`
4. ✅ Setelah DP paid, status order jadi `dp_paid`
5. ✅ User bisa bayar remaining kapan saja dengan endpoint `/pay-remaining`
6. ✅ Setelah remaining paid, status jadi `paid` (lunas)

---

## Business Logic

### DP Amount Calculation:
```typescript
// If cart has multiple decorations, use highest minimum_dp_percentage
const minDpPercentage = Math.max(
  ...cartItems.map(item => item.decoration.minimum_dp_percentage)
);

const dpAmount = Math.ceil(total * minDpPercentage / 100);
const remainingAmount = total - dpAmount;
```

### Example Calculation:
```
Decoration A: base_price Rp 40.000.000, minimum_dp 30%
Discount 10%: final_price Rp 36.000.000
Voucher DISC10 (10%): -Rp 3.600.000
Total: Rp 32.400.000

If payment_type = "dp":
- DP Amount (30%): Rp 9.720.000
- Remaining: Rp 22.680.000

User pays Rp 9.720.000 via Midtrans
Status: pending → dp_paid

Later, user pays Rp 22.680.000
Status: dp_paid → paid
```

---

## Testing

### 1. Create Decoration with DP
```bash
POST /api/admin/decorations
{
  "name": "Test Decoration",
  "base_price": 10000000,
  "minimum_dp_percentage": 30,
  ...
}
```

### 2. Checkout with DP
```bash
POST /api/customer/orders/checkout
{
  "payment_type": "dp",
  ...
}

# Response will include:
# - snap_token (for DP payment)
# - dp_amount
# - remaining_amount
```

### 3. Check Order Status
```bash
GET /api/customer/orders/payment-status/ORD-XXX
```

### 4. Pay Remaining
```bash
POST /api/customer/orders/123/pay-remaining

# Response will include:
# - snap_token (for remaining payment)
# - remaining_amount
```

---

## Error Handling

### Common Errors:

#### 1. Cannot Pay Remaining (Wrong Payment Type)
```json
{
  "success": false,
  "message": "Order is not a DP payment"
}
```
**Solution:** Order must have `payment_type = "dp"`

#### 2. DP Not Paid Yet
```json
{
  "success": false,
  "message": "DP has not been paid yet"
}
```
**Solution:** Pay DP first before paying remaining

#### 3. No Remaining Amount
```json
{
  "success": false,
  "message": "No remaining amount to pay"
}
```
**Solution:** Order already fully paid

---

## Database Schema Updates

### Decorations Table:
```sql
ALTER TABLE decorations 
ADD COLUMN minimum_dp_percentage INT DEFAULT 30 
COMMENT 'Minimum DP percentage (default 30%)';
```

### Orders Table:
```sql
ALTER TABLE orders 
ADD COLUMN payment_type ENUM('full', 'dp') DEFAULT 'full' 
COMMENT 'Payment type: full payment or DP',
ADD COLUMN dp_amount BIGINT DEFAULT 0 
COMMENT 'DP amount paid',
ADD COLUMN remaining_amount BIGINT DEFAULT 0 
COMMENT 'Remaining amount to be paid',
ADD COLUMN dp_paid_at TIMESTAMP NULL 
COMMENT 'When DP was paid',
ADD COLUMN full_paid_at TIMESTAMP NULL 
COMMENT 'When fully paid',
ADD COLUMN dp_snap_token VARCHAR(255) NULL 
COMMENT 'Midtrans snap token for DP payment',
ADD COLUMN remaining_snap_token VARCHAR(255) NULL 
COMMENT 'Midtrans snap token for remaining payment';
```

---

## Migration Command

```bash
php artisan migrate
```

Migration file: `2025_12_14_042346_add_dp_fields_to_decorations_and_orders_tables.php`

---

## Summary

✅ **Database:** Added DP fields to decorations and orders
✅ **API:** Checkout with payment type, pay remaining endpoint
✅ **Status:** New `dp_paid` status for orders with DP
✅ **Admin:** Can set minimum DP percentage per decoration
✅ **Customer:** Can choose full or DP payment, pay remaining anytime
✅ **Midtrans:** Integrated for both DP and remaining payments

**New Status Flow:**
- `pending` → Belum bayar
- `dp_paid` → Sudah DP, belum lunas (NEW!)
- `paid` → Sudah lunas

**Payment Options:**
- `full` → Bayar penuh langsung
- `dp` → Bayar DP dulu, sisa kapan saja
