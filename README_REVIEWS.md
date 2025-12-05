# DWD Wedding Organizer - Review System API Guide

## 📋 Overview

Review system untuk decoration products dengan logic:
- **Customer:** Hanya bisa review jika sudah pernah beli (order status = completed)
- **Admin:** Bisa create fake reviews untuk marketing purposes
- **Auto-update:** Rating & review count decoration otomatis update setiap ada perubahan

---

## 🌐 Public Endpoints (No Authentication)

### Get Reviews for Decoration
```bash
GET /api/public/decorations/{decorationId}/reviews?page=1&per_page=10
```

**Response:**
```json
{
  "success": true,
  "data": {
    "decoration": {
      "id": 1,
      "name": "Elegant Wedding Decoration",
      "rating": 4.5,
      "review_count": 25
    },
    "reviews": {
      "current_page": 1,
      "data": [
        {
          "id": 1,
          "rating": 5,
          "comment": "Amazing decoration! Exactly what we wanted for our wedding. Highly recommended!",
          "posted_at": "2025-11-20",
          "user": {
            "id": 2,
            "name": "John Doe"
          }
        },
        {
          "id": 2,
          "rating": 4,
          "comment": "Beautiful setup, professional team. Worth every penny!",
          "posted_at": "2025-11-18",
          "user": {
            "id": 3,
            "name": "Jane Smith"
          }
        }
      ],
      "per_page": 10,
      "total": 25
    }
  }
}
```

---

## 🔐 Customer Endpoints (Authentication Required)

### 1. Check if Customer Can Review

**Endpoint:**
```bash
GET /api/customer/reviews/can-review/{decorationId}
Authorization: Bearer {customer_token}
```

**Response - Can Review:**
```json
{
  "success": true,
  "can_review": true,
  "message": "You can review this decoration"
}
```

**Response - Already Reviewed:**
```json
{
  "success": true,
  "can_review": false,
  "reason": "already_reviewed",
  "message": "You have already reviewed this decoration"
}
```

**Response - Not Purchased:**
```json
{
  "success": true,
  "can_review": false,
  "reason": "not_purchased",
  "message": "You need to purchase this decoration before reviewing"
}
```

---

### 2. Create Review (Must Have Purchased)

**Endpoint:**
```bash
POST /api/customer/reviews
Authorization: Bearer {customer_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "decoration_id": 1,
  "rating": 5,
  "comment": "Amazing decoration! The team was professional and delivered exactly what we wanted for our wedding. Highly recommended!"
}
```

**Validation Rules:**
- `decoration_id` - Required, must exist in decorations table
- `rating` - Required, integer between 1-5
- `comment` - Required, max 1000 characters

**Success Response:**
```json
{
  "success": true,
  "message": "Review posted successfully",
  "data": {
    "id": 10,
    "user_id": 2,
    "decoration_id": 1,
    "rating": 5,
    "comment": "Amazing decoration! The team was professional...",
    "posted_at": "2025-12-04",
    "user": {
      "id": 2,
      "name": "John Doe"
    }
  }
}
```

**Error Response - Not Purchased:**
```json
{
  "success": false,
  "message": "You can only review decorations you have purchased"
}
```

**Error Response - Already Reviewed:**
```json
{
  "success": false,
  "message": "You have already reviewed this decoration"
}
```

---

### 3. Update Own Review

**Endpoint:**
```bash
PUT /api/customer/reviews/{id}
Authorization: Bearer {customer_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "rating": 4,
  "comment": "Updated comment: Still great, but had minor issues with setup time."
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Review updated successfully",
  "data": {
    "id": 10,
    "rating": 4,
    "comment": "Updated comment: Still great, but had minor issues...",
    "posted_at": "2025-12-04",
    "user": {
      "id": 2,
      "name": "John Doe"
    }
  }
}
```

**Error Response - Not Owner:**
```json
{
  "message": "No query results for model [App\\Models\\Review]"
}
```

---

### 4. Delete Own Review

**Endpoint:**
```bash
DELETE /api/customer/reviews/{id}
Authorization: Bearer {customer_token}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Review deleted successfully"
}
```

---

## 👨‍💼 Admin Endpoints (Full CRUD - Can Create Fake Reviews)

### 1. List All Reviews

**Endpoint:**
```bash
GET /api/admin/reviews?decoration_id=1&rating=5&search=amazing&page=1
Authorization: Bearer {admin_token}
```

**Query Parameters:**
- `decoration_id` - Filter by decoration ID
- `rating` - Filter by rating (1-5)
- `search` - Search in comment text
- `page` - Page number

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "rating": 5,
        "comment": "Amazing decoration!",
        "posted_at": "2025-11-20",
        "user": {
          "id": 2,
          "name": "John Doe"
        },
        "decoration": {
          "id": 1,
          "name": "Elegant Wedding Decoration"
        }
      }
    ],
    "per_page": 15,
    "total": 50
  }
}
```

---

### 2. Create Fake Review (Admin Only)

**Endpoint:**
```bash
POST /api/admin/reviews
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "user_id": 5,
  "decoration_id": 1,
  "rating": 5,
  "comment": "Fake review for marketing purposes. Beautiful decoration setup!",
  "posted_at": "2025-11-15"
}
```

**Validation Rules:**
- `user_id` - Required, must exist in users table
- `decoration_id` - Required, must exist in decorations table
- `rating` - Required, integer between 1-5
- `comment` - Required, max 1000 characters
- `posted_at` - Optional, date format (default: now)

**Success Response:**
```json
{
  "success": true,
  "message": "Review created successfully",
  "data": {
    "id": 15,
    "user_id": 5,
    "decoration_id": 1,
    "rating": 5,
    "comment": "Fake review for marketing purposes...",
    "posted_at": "2025-11-15",
    "user": {
      "id": 5,
      "name": "Marketing User"
    },
    "decoration": {
      "id": 1,
      "name": "Elegant Wedding Decoration"
    }
  }
}
```

**Note:** Admin tidak perlu validasi pembelian, bisa create review untuk user mana pun.

---

### 3. Get Single Review

**Endpoint:**
```bash
GET /api/admin/reviews/{id}
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 2,
    "decoration_id": 1,
    "rating": 5,
    "comment": "Amazing decoration!",
    "posted_at": "2025-11-20",
    "user": {
      "id": 2,
      "name": "John Doe"
    },
    "decoration": {
      "id": 1,
      "name": "Elegant Wedding Decoration"
    }
  }
}
```

---

### 4. Update Review (Admin)

**Endpoint:**
```bash
PUT /api/admin/reviews/{id}
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "rating": 4,
  "comment": "Updated comment by admin",
  "posted_at": "2025-11-22"
}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Review updated successfully",
  "data": {
    "id": 1,
    "rating": 4,
    "comment": "Updated comment by admin",
    "posted_at": "2025-11-22",
    "user": {
      "id": 2,
      "name": "John Doe"
    },
    "decoration": {
      "id": 1,
      "name": "Elegant Wedding Decoration"
    }
  }
}
```

---

### 5. Delete Review (Admin)

**Endpoint:**
```bash
DELETE /api/admin/reviews/{id}
Authorization: Bearer {admin_token}
```

**Success Response:**
```json
{
  "success": true,
  "message": "Review deleted successfully"
}
```

**Note:** Setelah delete, rating dan review_count decoration otomatis update.

---

## 🔄 Auto-Update Features

### Decoration Rating & Review Count

Setiap kali ada perubahan review (create, update, delete), sistem otomatis:

1. **Calculate Average Rating:**
   ```sql
   AVG(rating) FROM reviews WHERE decoration_id = X
   ```
   
2. **Count Total Reviews:**
   ```sql
   COUNT(*) FROM reviews WHERE decoration_id = X
   ```

3. **Update Decoration:**
   ```php
   $decoration->update([
       'rating' => 4.5,        // Rounded to 1 decimal
       'review_count' => 25
   ]);
   ```

**Example:**
- Decoration memiliki 10 reviews dengan rating: 5, 5, 4, 5, 3, 4, 5, 4, 5, 5
- Average = 4.5
- Review count = 10
- Decoration akan otomatis update: `rating = 4.5`, `review_count = 10`

---

## 🎯 Frontend Integration Examples

### 1. Display Reviews on Decoration Detail Page

```typescript
// components/DecorationReviews.tsx
import { useState, useEffect } from 'react';
import axios from 'axios';

interface Review {
  id: number;
  rating: number;
  comment: string;
  posted_at: string;
  user: {
    id: number;
    name: string;
  };
}

export default function DecorationReviews({ decorationId }: { decorationId: number }) {
  const [reviews, setReviews] = useState<Review[]>([]);
  const [decorationInfo, setDecorationInfo] = useState<any>(null);

  useEffect(() => {
    const fetchReviews = async () => {
      try {
        const { data } = await axios.get(
          `http://localhost:8000/api/public/decorations/${decorationId}/reviews`
        );
        setDecorationInfo(data.data.decoration);
        setReviews(data.data.reviews.data);
      } catch (error) {
        console.error('Failed to fetch reviews:', error);
      }
    };
    fetchReviews();
  }, [decorationId]);

  return (
    <div className="reviews-section">
      {/* Rating Summary */}
      <div className="rating-summary">
        <div className="rating-number">{decorationInfo?.rating}</div>
        <div className="stars">
          {'⭐'.repeat(Math.round(decorationInfo?.rating || 0))}
        </div>
        <p>{decorationInfo?.review_count} reviews</p>
      </div>

      {/* Reviews List */}
      <div className="reviews-list">
        {reviews.map(review => (
          <div key={review.id} className="review-card">
            <div className="review-header">
              <div className="user-info">
                <strong>{review.user.name}</strong>
                <span className="date">{new Date(review.posted_at).toLocaleDateString()}</span>
              </div>
              <div className="rating">
                {'⭐'.repeat(review.rating)}
              </div>
            </div>
            <p className="comment">{review.comment}</p>
          </div>
        ))}
      </div>
    </div>
  );
}
```

---

### 2. Customer Write Review Form

```typescript
// components/WriteReview.tsx
import { useState, useEffect } from 'react';
import axios from 'axios';

export default function WriteReview({ decorationId }: { decorationId: number }) {
  const [canReview, setCanReview] = useState<boolean | null>(null);
  const [reason, setReason] = useState('');
  const [rating, setRating] = useState(5);
  const [comment, setComment] = useState('');
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    checkCanReview();
  }, [decorationId]);

  const checkCanReview = async () => {
    try {
      const { data } = await axios.get(
        `http://localhost:8000/api/customer/reviews/can-review/${decorationId}`,
        {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`
          }
        }
      );
      setCanReview(data.can_review);
      if (!data.can_review) {
        setReason(data.message);
      }
    } catch (error) {
      console.error('Failed to check review status:', error);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);

    try {
      const { data } = await axios.post(
        'http://localhost:8000/api/customer/reviews',
        {
          decoration_id: decorationId,
          rating,
          comment
        },
        {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('token')}`
          }
        }
      );

      alert('Review posted successfully!');
      window.location.reload(); // Refresh to show new review
    } catch (error: any) {
      alert(error.response?.data?.message || 'Failed to post review');
    } finally {
      setSubmitting(false);
    }
  };

  if (canReview === null) {
    return <div>Loading...</div>;
  }

  if (!canReview) {
    return (
      <div className="cannot-review-message">
        <p>{reason}</p>
      </div>
    );
  }

  return (
    <form onSubmit={handleSubmit} className="review-form">
      <h3>Write a Review</h3>
      
      <div className="rating-input">
        <label>Rating:</label>
        <div className="stars-input">
          {[1, 2, 3, 4, 5].map(star => (
            <button
              key={star}
              type="button"
              onClick={() => setRating(star)}
              className={star <= rating ? 'active' : ''}
            >
              ⭐
            </button>
          ))}
        </div>
      </div>

      <div className="comment-input">
        <label>Your Review:</label>
        <textarea
          value={comment}
          onChange={(e) => setComment(e.target.value)}
          placeholder="Share your experience with this decoration..."
          rows={5}
          maxLength={1000}
          required
        />
        <small>{comment.length}/1000 characters</small>
      </div>

      <button type="submit" disabled={submitting}>
        {submitting ? 'Posting...' : 'Post Review'}
      </button>
    </form>
  );
}
```

---

### 3. Admin Create Fake Review

```typescript
// admin/components/CreateFakeReview.tsx
import { useState } from 'react';
import axios from 'axios';

export default function CreateFakeReview() {
  const [formData, setFormData] = useState({
    user_id: '',
    decoration_id: '',
    rating: 5,
    comment: '',
    posted_at: new Date().toISOString().split('T')[0]
  });

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    try {
      const { data } = await axios.post(
        'http://localhost:8000/api/admin/reviews',
        formData,
        {
          headers: {
            Authorization: `Bearer ${localStorage.getItem('admin_token')}`
          }
        }
      );

      alert('Fake review created successfully!');
      // Reset form or redirect
    } catch (error: any) {
      alert(error.response?.data?.message || 'Failed to create review');
    }
  };

  return (
    <form onSubmit={handleSubmit} className="admin-review-form">
      <h3>Create Fake Review (Admin)</h3>

      <div>
        <label>User ID:</label>
        <input
          type="number"
          value={formData.user_id}
          onChange={(e) => setFormData({...formData, user_id: e.target.value})}
          required
        />
      </div>

      <div>
        <label>Decoration ID:</label>
        <input
          type="number"
          value={formData.decoration_id}
          onChange={(e) => setFormData({...formData, decoration_id: e.target.value})}
          required
        />
      </div>

      <div>
        <label>Rating:</label>
        <select
          value={formData.rating}
          onChange={(e) => setFormData({...formData, rating: parseInt(e.target.value)})}
        >
          <option value={5}>5 Stars</option>
          <option value={4}>4 Stars</option>
          <option value={3}>3 Stars</option>
          <option value={2}>2 Stars</option>
          <option value={1}>1 Star</option>
        </select>
      </div>

      <div>
        <label>Comment:</label>
        <textarea
          value={formData.comment}
          onChange={(e) => setFormData({...formData, comment: e.target.value})}
          rows={5}
          required
        />
      </div>

      <div>
        <label>Posted Date:</label>
        <input
          type="date"
          value={formData.posted_at}
          onChange={(e) => setFormData({...formData, posted_at: e.target.value})}
        />
      </div>

      <button type="submit">Create Fake Review</button>
    </form>
  );
}
```

---

## 📊 Use Cases

### Customer Flow:
1. Customer membeli decoration (order status = completed)
2. Customer buka decoration detail page
3. System check: `GET /api/customer/reviews/can-review/{id}`
4. Jika `can_review = true`, tampilkan form review
5. Customer submit review: `POST /api/customer/reviews`
6. Review muncul di decoration page
7. Customer bisa edit/delete review sendiri

### Admin Flow:
1. Admin ingin boost rating decoration baru
2. Admin create fake review: `POST /api/admin/reviews`
3. Pilih user mana saja (tidak perlu validasi pembelian)
4. Set rating, comment, dan posted_date
5. Review langsung muncul di public page
6. Admin bisa edit/delete review kapan saja

### Public Visitor:
1. Visitor buka decoration detail page (tanpa login)
2. Load reviews: `GET /api/public/decorations/{id}/reviews`
3. Display rating summary + list reviews
4. Sort by newest/highest rating

---

## 🔒 Security & Validation

### Customer Protection:
- ✅ Hanya bisa review decoration yang sudah dibeli
- ✅ 1 user hanya 1 review per decoration
- ✅ Hanya bisa edit/delete review sendiri
- ✅ Comment max 1000 characters
- ✅ Rating harus 1-5

### Admin Flexibility:
- ✅ Bebas create review untuk user mana pun
- ✅ Tidak perlu validasi pembelian
- ✅ Bisa set custom posted_date
- ✅ Full CRUD untuk semua review

### Auto-Update:
- ✅ Rating decoration auto-calculate setiap ada perubahan
- ✅ Review count auto-update
- ✅ Rounded to 1 decimal (4.5, 4.8, dll)

---

## 📝 Database Schema

```sql
CREATE TABLE reviews (
  id BIGINT PRIMARY KEY AUTO_INCREMENT,
  user_id BIGINT NOT NULL,
  decoration_id BIGINT NOT NULL,
  rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment TEXT NOT NULL,
  posted_at DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (decoration_id) REFERENCES decorations(id) ON DELETE CASCADE
);

-- Index for faster queries
CREATE INDEX idx_decoration_reviews ON reviews(decoration_id);
CREATE INDEX idx_user_reviews ON reviews(user_id);
```

---

**Last Updated:** December 4, 2025  
**Base URL:** `http://localhost:8000/api`
