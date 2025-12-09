# Order Review API Documentation

## Overview
Customer dapat memberikan review untuk decoration yang sudah mereka pesan setelah order selesai (status `completed` atau `paid`).

## Features
- ✅ Field `has_reviewed` pada setiap order item
- ✅ Customer hanya bisa review decoration yang sudah mereka pesan
- ✅ Satu customer hanya bisa review satu decoration satu kali
- ✅ Review hanya bisa diberikan untuk order dengan status `completed` atau `paid`

---

## API Endpoints

### 1. Get Orders with Review Status

**Endpoint**: `GET /api/customer/orders`

**Authentication**: Required (Bearer Token)

**Response**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "order_number": "ORD-1234567890-ABC123",
        "status": "completed",
        "total": 32400000,
        "items": [
          {
            "id": 1,
            "decoration_id": 10,
            "type": "custom",
            "quantity": 1,
            "base_price": 36000000,
            "discount": 3600000,
            "price": 32400000,
            "has_reviewed": false,
            "decoration": {
              "id": 10,
              "name": "PURE ELEGANCE 9",
              "images": [...]
            }
          }
        ]
      }
    ]
  }
}
```

**Field `has_reviewed`**:
- `true`: Customer sudah memberikan review untuk decoration ini
- `false`: Customer belum memberikan review

---

### 2. Get Single Order Detail with Review Status

**Endpoint**: `GET /api/customer/orders/{id}`

**Authentication**: Required (Bearer Token)

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "order_number": "ORD-1234567890-ABC123",
    "status": "completed",
    "total": 32400000,
    "items": [
      {
        "id": 1,
        "decoration_id": 10,
        "has_reviewed": false,
        "decoration": {
          "id": 10,
          "name": "PURE ELEGANCE 9"
        }
      }
    ]
  }
}
```

---

### 3. Submit Review for Decoration

**Endpoint**: `POST /api/customer/orders/{orderId}/review`

**Authentication**: Required (Bearer Token)

**Request Body**:
```json
{
  "decoration_id": 10,
  "rating": 5,
  "comment": "Dekorasi sangat bagus dan sesuai ekspektasi! Pelayanan juga memuaskan."
}
```

**Validation Rules**:
- `decoration_id`: required, must exist in decorations table
- `rating`: required, integer, min: 1, max: 5
- `comment`: required, string, max: 1000 characters

**Success Response (201 Created)**:
```json
{
  "success": true,
  "message": "Review submitted successfully",
  "data": {
    "id": 15,
    "user_id": 5,
    "decoration_id": 10,
    "rating": 5,
    "comment": "Dekorasi sangat bagus dan sesuai ekspektasi! Pelayanan juga memuaskan.",
    "posted_at": "2025-12-09",
    "created_at": "2025-12-09T10:30:00.000000Z",
    "updated_at": "2025-12-09T10:30:00.000000Z"
  }
}
```

**Error Responses**:

**400 Bad Request - Already Reviewed**:
```json
{
  "success": false,
  "message": "You have already reviewed this decoration"
}
```

**400 Bad Request - Order Not Completed**:
```json
{
  "success": false,
  "message": "Order not found or not completed"
}
```

**404 Not Found - Decoration Not in Order**:
```json
{
  "success": false,
  "message": "Decoration not found in this order"
}
```

**422 Validation Error**:
```json
{
  "message": "The rating field is required. (and 1 more error)",
  "errors": {
    "rating": [
      "The rating field is required."
    ],
    "comment": [
      "The comment field is required."
    ]
  }
}
```

---

## Frontend Implementation

### React/Next.js Example

#### 1. Display Orders with Review Button

```javascript
import { useState, useEffect } from 'react';
import axios from 'axios';

const MyOrders = () => {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchOrders();
  }, []);

  const fetchOrders = async () => {
    try {
      const token = localStorage.getItem('token');
      const response = await axios.get(
        `${process.env.NEXT_PUBLIC_API_URL}/api/customer/orders`,
        {
          headers: { Authorization: `Bearer ${token}` }
        }
      );
      setOrders(response.data.data.data);
    } catch (error) {
      console.error('Failed to fetch orders:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div>
      <h1>My Orders</h1>
      {orders.map(order => (
        <div key={order.id} className="order-card">
          <h3>Order #{order.order_number}</h3>
          <p>Status: {order.status}</p>
          <p>Total: Rp {order.total.toLocaleString('id-ID')}</p>
          
          <div className="order-items">
            {order.items.map(item => (
              <div key={item.id} className="item">
                <p>{item.decoration.name}</p>
                <p>Rp {item.price.toLocaleString('id-ID')}</p>
                
                {/* Show review button only if not reviewed and order completed */}
                {(order.status === 'completed' || order.status === 'paid') && !item.has_reviewed && (
                  <button onClick={() => openReviewModal(order.id, item.decoration_id)}>
                    Write a Review
                  </button>
                )}
                
                {item.has_reviewed && (
                  <span className="text-green-600">✓ Reviewed</span>
                )}
              </div>
            ))}
          </div>
        </div>
      ))}
    </div>
  );
};
```

#### 2. Review Modal/Form Component

```javascript
import { useState } from 'react';
import axios from 'axios';

const ReviewModal = ({ orderId, decorationId, onClose, onSuccess }) => {
  const [rating, setRating] = useState(5);
  const [comment, setComment] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!comment.trim()) {
      setError('Comment is required');
      return;
    }

    try {
      setLoading(true);
      setError(null);
      
      const token = localStorage.getItem('token');
      const response = await axios.post(
        `${process.env.NEXT_PUBLIC_API_URL}/api/customer/orders/${orderId}/review`,
        {
          decoration_id: decorationId,
          rating: rating,
          comment: comment
        },
        {
          headers: { Authorization: `Bearer ${token}` }
        }
      );

      if (response.data.success) {
        alert('Review submitted successfully!');
        onSuccess();
        onClose();
      }
    } catch (err) {
      console.error('Failed to submit review:', err);
      setError(err.response?.data?.message || 'Failed to submit review');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="modal">
      <div className="modal-content">
        <h2>Write a Review</h2>
        
        <form onSubmit={handleSubmit}>
          {/* Rating Stars */}
          <div className="rating">
            <label>Rating:</label>
            <div className="stars">
              {[1, 2, 3, 4, 5].map(star => (
                <button
                  key={star}
                  type="button"
                  onClick={() => setRating(star)}
                  className={star <= rating ? 'star-filled' : 'star-empty'}
                >
                  ★
                </button>
              ))}
            </div>
          </div>

          {/* Comment */}
          <div className="form-group">
            <label>Your Review:</label>
            <textarea
              value={comment}
              onChange={(e) => setComment(e.target.value)}
              placeholder="Share your experience with this decoration..."
              maxLength={1000}
              rows={5}
              required
            />
            <small>{comment.length}/1000 characters</small>
          </div>

          {error && (
            <div className="error-message">{error}</div>
          )}

          <div className="buttons">
            <button type="button" onClick={onClose} disabled={loading}>
              Cancel
            </button>
            <button type="submit" disabled={loading}>
              {loading ? 'Submitting...' : 'Submit Review'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default ReviewModal;
```

#### 3. Complete Implementation with State Management

```javascript
import { useState, useEffect } from 'react';
import axios from 'axios';
import ReviewModal from './ReviewModal';

const OrdersPage = () => {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [showReviewModal, setShowReviewModal] = useState(false);
  const [selectedOrder, setSelectedOrder] = useState(null);
  const [selectedDecoration, setSelectedDecoration] = useState(null);

  useEffect(() => {
    fetchOrders();
  }, []);

  const fetchOrders = async () => {
    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      const response = await axios.get(
        `${process.env.NEXT_PUBLIC_API_URL}/api/customer/orders`,
        {
          headers: { Authorization: `Bearer ${token}` }
        }
      );
      setOrders(response.data.data.data);
    } catch (error) {
      console.error('Failed to fetch orders:', error);
    } finally {
      setLoading(false);
    }
  };

  const openReviewModal = (orderId, decorationId) => {
    setSelectedOrder(orderId);
    setSelectedDecoration(decorationId);
    setShowReviewModal(true);
  };

  const closeReviewModal = () => {
    setShowReviewModal(false);
    setSelectedOrder(null);
    setSelectedDecoration(null);
  };

  const handleReviewSuccess = () => {
    // Refresh orders to update has_reviewed status
    fetchOrders();
  };

  if (loading) return <div>Loading...</div>;

  return (
    <div className="orders-page">
      <h1>My Orders</h1>
      
      {orders.length === 0 ? (
        <p>No orders yet</p>
      ) : (
        <div className="orders-list">
          {orders.map(order => (
            <div key={order.id} className="order-card">
              <div className="order-header">
                <h3>Order #{order.order_number}</h3>
                <span className={`status-badge status-${order.status}`}>
                  {order.status}
                </span>
              </div>
              
              <div className="order-info">
                <p>Date: {new Date(order.created_at).toLocaleDateString('id-ID')}</p>
                <p>Total: Rp {order.total.toLocaleString('id-ID')}</p>
              </div>

              <div className="order-items">
                {order.items.map(item => (
                  <div key={item.id} className="order-item">
                    <div className="item-image">
                      {item.decoration.images?.[0] && (
                        <img 
                          src={`${process.env.NEXT_PUBLIC_API_URL}/storage/${item.decoration.images[0].image_path}`}
                          alt={item.decoration.name}
                        />
                      )}
                    </div>
                    
                    <div className="item-details">
                      <h4>{item.decoration.name}</h4>
                      <p>Type: {item.type}</p>
                      <p>Quantity: {item.quantity}</p>
                      <p>Price: Rp {item.price.toLocaleString('id-ID')}</p>
                    </div>

                    <div className="item-actions">
                      {(order.status === 'completed' || order.status === 'paid') && (
                        <>
                          {item.has_reviewed ? (
                            <div className="reviewed-badge">
                              <span>✓ Reviewed</span>
                            </div>
                          ) : (
                            <button 
                              className="btn-review"
                              onClick={() => openReviewModal(order.id, item.decoration_id)}
                            >
                              Write a Review
                            </button>
                          )}
                        </>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          ))}
        </div>
      )}

      {showReviewModal && (
        <ReviewModal
          orderId={selectedOrder}
          decorationId={selectedDecoration}
          onClose={closeReviewModal}
          onSuccess={handleReviewSuccess}
        />
      )}
    </div>
  );
};

export default OrdersPage;
```

---

## Business Rules

### When Customer Can Review
✅ Order status must be `completed` or `paid`
✅ Decoration must be in the customer's order
✅ Customer has not reviewed this decoration before

### When Customer Cannot Review
❌ Order status is `pending`, `failed`, or `cancelled`
❌ Decoration is not in the customer's order
❌ Customer has already reviewed this decoration

---

## Database Schema

### Reviews Table
```sql
CREATE TABLE reviews (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,
    customer_name VARCHAR(255) NULL,
    decoration_id BIGINT UNSIGNED NOT NULL,
    rating INT NOT NULL,
    comment TEXT NOT NULL,
    posted_at DATE NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (decoration_id) REFERENCES decorations(id) ON DELETE CASCADE
);
```

**Notes**:
- `user_id` can be NULL for fake/admin-created reviews
- `customer_name` is used for fake reviews (when `user_id` is NULL)
- Real customer reviews will have `user_id` and NULL `customer_name`

---

## Testing

### Test Scenarios

1. **Happy Path - Submit Review**
   - Login as customer
   - Complete an order
   - Submit review with rating 5 and comment
   - Verify review is saved
   - Verify `has_reviewed` becomes `true`

2. **Cannot Review Same Decoration Twice**
   - Submit review for decoration A
   - Try to submit review again for decoration A
   - Should return error: "You have already reviewed this decoration"

3. **Cannot Review Pending Order**
   - Create order (status: pending)
   - Try to submit review
   - Should return error: "Order not found or not completed"

4. **Cannot Review Decoration Not in Order**
   - Complete order with decoration A
   - Try to submit review for decoration B
   - Should return error: "Decoration not found in this order"

5. **Validation Errors**
   - Submit review without rating
   - Submit review without comment
   - Submit review with rating > 5
   - Submit review with comment > 1000 chars

---

## Notes
- Review hanya bisa diberikan untuk order yang sudah `completed` atau `paid`
- Satu customer hanya bisa review satu decoration satu kali (meskipun order berkali-kali)
- Rating range: 1-5 (bintang)
- Comment max length: 1000 characters
- Field `has_reviewed` di-generate real-time saat fetch orders
