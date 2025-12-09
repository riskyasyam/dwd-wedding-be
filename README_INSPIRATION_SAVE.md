# Inspiration Save/Favorite Feature Documentation

## Overview
Customer dapat menyimpan (save/favorite) inspiration untuk referensi nanti. Setiap inspiration memiliki counter `liked_count` yang menunjukkan berapa banyak user yang menyimpannya.

---

## Features
- ✅ Save/Favorite inspiration
- ✅ Remove dari saved list (unfavorite)
- ✅ View all saved inspirations
- ✅ Toggle like/unlike dengan single endpoint
- ✅ Field `is_saved` untuk menunjukkan status saved
- ✅ Counter `liked_count` untuk popularity

---

## Database Schema

### Inspirations Table
```sql
CREATE TABLE inspirations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    image VARCHAR(255) NOT NULL,
    colors JSON NOT NULL,
    location VARCHAR(255) NOT NULL,
    liked_count INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

### Pivot Table: inspiration_user_saved
```sql
CREATE TABLE inspiration_user_saved (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    inspiration_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    UNIQUE KEY (user_id, inspiration_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (inspiration_id) REFERENCES inspirations(id) ON DELETE CASCADE
);
```

---

## API Endpoints

### 1. Get All Inspirations (with is_saved status)

**Endpoint**: `GET /api/public/inspirations`

**Authentication**: Optional (field `is_saved` hanya muncul jika login)

**Query Parameters**:
- `color` (string): Filter by color
- `location` (string): Filter by location
- `search` (string): Search by title
- `order_by` (string): `created_at` | `liked_count` (default: `created_at`)
- `order_dir` (string): `asc` | `desc` (default: `desc`)

**Response**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "Elegant Garden Wedding",
        "image": "/storage/inspirations/image.jpg",
        "image_url": "http://localhost:8000/storage/inspirations/image.jpg",
        "colors": ["White", "Gold", "Green"],
        "location": "Bali",
        "liked_count": 150,
        "is_saved": true,
        "created_at": "2025-12-09T10:00:00.000000Z",
        "updated_at": "2025-12-09T10:00:00.000000Z"
      },
      {
        "id": 2,
        "title": "Rustic Barn Wedding",
        "image": "/storage/inspirations/image2.jpg",
        "image_url": "http://localhost:8000/storage/inspirations/image2.jpg",
        "colors": ["Brown", "Cream", "Green"],
        "location": "Bandung",
        "liked_count": 85,
        "is_saved": false,
        "created_at": "2025-12-08T10:00:00.000000Z",
        "updated_at": "2025-12-08T10:00:00.000000Z"
      }
    ],
    "total": 50
  }
}
```

**Field `is_saved`**:
- `true`: User sudah save inspiration ini
- `false`: User belum save
- Not present in response: User tidak login (guest)

**Notes**:
- Endpoint ini **PUBLIC** - tidak perlu login untuk browse
- Field `is_saved` hanya muncul jika user login
- Guest users dapat melihat semua inspirations tanpa `is_saved` field

---

### 2. Get Single Inspiration Detail

**Endpoint**: `GET /api/public/inspirations/{id}`

**Authentication**: Optional

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Elegant Garden Wedding",
    "image": "/storage/inspirations/image.jpg",
    "image_url": "http://localhost:8000/storage/inspirations/image.jpg",
    "colors": ["White", "Gold", "Green"],
    "location": "Bali",
    "liked_count": 150,
    "is_saved": true,
    "created_at": "2025-12-09T10:00:00.000000Z",
    "updated_at": "2025-12-09T10:00:00.000000Z"
  }
}
```

---

### 3. Toggle Save/Unsave Inspiration (Like/Unlike)

**Endpoint**: `POST /api/customer/inspirations/{id}/like`

**Authentication**: Required (Bearer Token)

**Request Body**: None

**Response (Save)**:
```json
{
  "success": true,
  "message": "Inspiration saved to your list",
  "data": {
    "is_saved": true,
    "liked_count": 151
  }
}
```

**Response (Unsave)**:
```json
{
  "success": true,
  "message": "Inspiration removed from your saved list",
  "data": {
    "is_saved": false,
    "liked_count": 150
  }
}
```

**Behavior**:
- Jika belum saved → akan save dan increment `liked_count`
- Jika sudah saved → akan unsave dan decrement `liked_count`
- Toggle action dalam single endpoint

---

### 4. Get My Saved Inspirations

**Endpoint**: `GET /api/customer/my-saved-inspirations`

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
        "title": "Elegant Garden Wedding",
        "image": "/storage/inspirations/image.jpg",
        "image_url": "http://localhost:8000/storage/inspirations/image.jpg",
        "colors": ["White", "Gold", "Green"],
        "location": "Bali",
        "liked_count": 150,
        "is_saved": true,
        "created_at": "2025-12-09T10:00:00.000000Z",
        "updated_at": "2025-12-09T10:00:00.000000Z"
      }
    ],
    "total": 5
  }
}
```

**Notes**:
- Semua item di list ini `is_saved: true`
- Hanya menampilkan inspiration yang di-save oleh user

---

### 5. Remove from Saved List (Unfavorite)

**Endpoint**: `DELETE /api/customer/inspirations/{id}/saved`

**Authentication**: Required (Bearer Token)

**Response (Success)**:
```json
{
  "success": true,
  "message": "Inspiration removed from your saved list",
  "data": {
    "is_saved": false,
    "liked_count": 150
  }
}
```

**Error Response (Not Saved)**:
```json
{
  "success": false,
  "message": "Inspiration is not in your saved list"
}
```

**Notes**:
- Alternative dari toggle endpoint
- Spesifik untuk remove action
- Lebih semantic untuk UI "Remove" button

---

## Frontend Implementation

### React/Next.js Example

#### 1. Inspiration Card with Save Button

```javascript
import { useState } from 'react';
import { Heart } from 'lucide-react';
import axios from 'axios';

const InspirationCard = ({ inspiration: initialInspiration }) => {
  const [inspiration, setInspiration] = useState(initialInspiration);
  const [loading, setLoading] = useState(false);

  const handleToggleSave = async () => {
    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      
      // Check if user is logged in
      if (!token) {
        alert('Please login to save inspirations');
        // Redirect to login page
        window.location.href = '/login';
        return;
      }
      
      const response = await axios.post(
        `${process.env.NEXT_PUBLIC_API_URL}/api/customer/inspirations/${inspiration.id}/like`,
        {},
        {
          headers: { Authorization: `Bearer ${token}` }
        }
      );

      if (response.data.success) {
        setInspiration({
          ...inspiration,
          is_saved: response.data.data.is_saved,
          liked_count: response.data.data.liked_count
        });
      }
    } catch (error) {
      console.error('Failed to toggle save:', error);
      alert('Failed to save inspiration');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="inspiration-card">
      <div className="image-container">
        <img 
          src={inspiration.image_url} 
          alt={inspiration.title}
          className="w-full h-64 object-cover"
        />
        
        {/* Save Button Overlay */}
        <button
          onClick={handleToggleSave}
          disabled={loading}
          className={`absolute top-4 right-4 p-2 rounded-full ${
            inspiration.is_saved 
              ? 'bg-red-500 text-white' 
              : 'bg-white text-gray-600'
          }`}
          title={inspiration.is_saved ? 'Remove from saved' : 'Save inspiration'}
        >
          <Heart 
            size={24} 
            fill={inspiration.is_saved ? 'currentColor' : 'none'}
          />
        </button>
      </div>

      <div className="card-body p-4">
        <h3 className="text-xl font-semibold">{inspiration.title}</h3>
        
        <div className="colors flex gap-2 my-2">
          {inspiration.colors.map((color, idx) => (
            <span 
              key={idx}
              className="px-3 py-1 bg-gray-100 rounded-full text-sm"
            >
              {color}
            </span>
          ))}
        </div>

        <div className="footer flex justify-between items-center">
          <span className="text-gray-600">{inspiration.location}</span>
          <div className="flex items-center gap-1">
            <Heart size={16} />
            <span>{inspiration.liked_count}</span>
          </div>
        </div>
      </div>
    </div>
  );
};

export default InspirationCard;
```

#### 2. Inspirations Gallery Page

```javascript
import { useState, useEffect } from 'react';
import axios from 'axios';
import InspirationCard from './InspirationCard';

const InspirationsPage = () => {
  const [inspirations, setInspirations] = useState([]);
  const [loading, setLoading] = useState(true);
  const [filters, setFilters] = useState({
    color: '',
    location: '',
    search: '',
    order_by: 'created_at',
    order_dir: 'desc'
  });

  useEffect(() => {
    fetchInspirations();
  }, [filters]);

  const fetchInspirations = async () => {
    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      
      const params = new URLSearchParams();
      Object.keys(filters).forEach(key => {
        if (filters[key]) params.append(key, filters[key]);
      });

      const response = await axios.get(
        `${process.env.NEXT_PUBLIC_API_URL}/api/public/inspirations?${params}`,
        {
          headers: token ? { Authorization: `Bearer ${token}` } : {}
        }
      );

      setInspirations(response.data.data.data);
    } catch (error) {
      console.error('Failed to fetch inspirations:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="inspirations-page">
      <h1>Wedding Inspirations</h1>

      {/* Filters */}
      <div className="filters mb-6">
        <input
          type="text"
          placeholder="Search..."
          value={filters.search}
          onChange={(e) => setFilters({...filters, search: e.target.value})}
          className="border px-4 py-2 rounded"
        />

        <select
          value={filters.order_by}
          onChange={(e) => setFilters({...filters, order_by: e.target.value})}
          className="border px-4 py-2 rounded ml-2"
        >
          <option value="created_at">Newest</option>
          <option value="liked_count">Most Popular</option>
        </select>
      </div>

      {/* Gallery */}
      {loading ? (
        <div>Loading...</div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          {inspirations.map(inspiration => (
            <InspirationCard 
              key={inspiration.id} 
              inspiration={inspiration}
            />
          ))}
        </div>
      )}
    </div>
  );
};

export default InspirationsPage;
```

#### 3. My Saved Inspirations Page

```javascript
import { useState, useEffect } from 'react';
import axios from 'axios';

const MySavedInspirations = () => {
  const [savedInspirations, setSavedInspirations] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchSavedInspirations();
  }, []);

  const fetchSavedInspirations = async () => {
    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      
      const response = await axios.get(
        `${process.env.NEXT_PUBLIC_API_URL}/api/customer/my-saved-inspirations`,
        {
          headers: { Authorization: `Bearer ${token}` }
        }
      );

      setSavedInspirations(response.data.data.data);
    } catch (error) {
      console.error('Failed to fetch saved inspirations:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleRemoveSaved = async (id) => {
    try {
      const token = localStorage.getItem('token');
      
      const response = await axios.delete(
        `${process.env.NEXT_PUBLIC_API_URL}/api/customer/inspirations/${id}/saved`,
        {
          headers: { Authorization: `Bearer ${token}` }
        }
      );

      if (response.data.success) {
        // Remove from list
        setSavedInspirations(
          savedInspirations.filter(item => item.id !== id)
        );
        alert('Removed from saved list');
      }
    } catch (error) {
      console.error('Failed to remove:', error);
      alert('Failed to remove inspiration');
    }
  };

  if (loading) return <div>Loading...</div>;

  if (savedInspirations.length === 0) {
    return (
      <div className="text-center py-12">
        <h2>No Saved Inspirations Yet</h2>
        <p>Start browsing and save your favorite wedding inspirations!</p>
      </div>
    );
  }

  return (
    <div className="saved-inspirations-page">
      <h1>My Saved Inspirations ({savedInspirations.length})</h1>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
        {savedInspirations.map(inspiration => (
          <div key={inspiration.id} className="inspiration-card">
            <div className="image-container relative">
              <img 
                src={inspiration.image_url} 
                alt={inspiration.title}
                className="w-full h-64 object-cover"
              />
              
              {/* Remove Button */}
              <button
                onClick={() => handleRemoveSaved(inspiration.id)}
                className="absolute top-4 right-4 bg-red-500 text-white p-2 rounded-full"
              >
                ✕
              </button>
            </div>

            <div className="p-4">
              <h3 className="text-xl font-semibold">{inspiration.title}</h3>
              
              <div className="colors flex gap-2 my-2">
                {inspiration.colors.map((color, idx) => (
                  <span 
                    key={idx}
                    className="px-3 py-1 bg-gray-100 rounded-full text-sm"
                  >
                    {color}
                  </span>
                ))}
              </div>

              <p className="text-gray-600">{inspiration.location}</p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

export default MySavedInspirations;
```

#### 4. Toggle Save with Optimistic UI Update

```javascript
const handleToggleSaveOptimistic = async (inspirationId) => {
  // Optimistic update
  setInspirations(prev => 
    prev.map(item => 
      item.id === inspirationId 
        ? {
            ...item,
            is_saved: !item.is_saved,
            liked_count: item.is_saved 
              ? item.liked_count - 1 
              : item.liked_count + 1
          }
        : item
    )
  );

  try {
    const token = localStorage.getItem('token');
    await axios.post(
      `${process.env.NEXT_PUBLIC_API_URL}/api/customer/inspirations/${inspirationId}/like`,
      {},
      { headers: { Authorization: `Bearer ${token}` } }
    );
  } catch (error) {
    // Revert on error
    setInspirations(prev => 
      prev.map(item => 
        item.id === inspirationId 
          ? {
              ...item,
              is_saved: !item.is_saved,
              liked_count: item.is_saved 
                ? item.liked_count + 1 
                : item.liked_count - 1
            }
          : item
      )
    );
    console.error('Failed to toggle save:', error);
  }
};
```

---

## Business Rules

### Saving Inspiration
✅ User harus login untuk save inspiration
✅ User bisa save unlimited inspirations
✅ Satu user hanya bisa save satu inspiration sekali (unique constraint)
✅ Save inspiration akan increment `liked_count`

### Unsaving Inspiration
✅ User bisa unsave dengan 2 cara:
   1. Toggle via `POST /inspirations/{id}/like` (jika sudah saved)
   2. Direct remove via `DELETE /inspirations/{id}/saved`
✅ Unsave akan decrement `liked_count`

### Viewing
✅ Semua orang bisa lihat inspirations (public)
✅ Field `is_saved` hanya muncul jika user login
✅ `liked_count` visible untuk semua

---

## Testing

### Test Scenarios

1. **Save Inspiration**
   - Login as customer
   - Call `POST /inspirations/1/like`
   - Verify `is_saved: true`
   - Verify `liked_count` incremented
   - Check pivot table has record

2. **Unsave via Toggle**
   - Save inspiration first
   - Call `POST /inspirations/1/like` again
   - Verify `is_saved: false`
   - Verify `liked_count` decremented

3. **Unsave via DELETE**
   - Save inspiration first
   - Call `DELETE /inspirations/1/saved`
   - Verify removed from saved list
   - Verify `liked_count` decremented

4. **View Saved List**
   - Save multiple inspirations
   - Call `GET /my-saved-inspirations`
   - Verify only saved items returned
   - All items have `is_saved: true`

5. **Duplicate Save Prevention**
   - Save inspiration
   - Try to save again directly in database
   - Should fail with unique constraint error

6. **Filter & Sort**
   - Test filter by color
   - Test filter by location
   - Test sort by popularity (`liked_count`)
   - Test sort by newest

---

## Performance Considerations

### N+1 Query Issue
When fetching many inspirations with `is_saved` status:

```php
// Instead of checking in loop (N+1 queries)
$inspirations->getCollection()->transform(function ($inspiration) use ($user) {
    $inspiration->is_saved = $inspiration->savedByUsers()
        ->where('user_id', $user->id)
        ->exists();
    return $inspiration;
});

// Better: Eager load with whereHas
$inspirations = Inspiration::with(['savedByUsers' => function($query) use ($user) {
    $query->where('user_id', $user->id);
}])->paginate(15);

// Then check in memory
$inspirations->getCollection()->transform(function ($inspiration) {
    $inspiration->is_saved = $inspiration->savedByUsers->isNotEmpty();
    return $inspiration;
});
```

---

## Notes

- Fitur save/favorite menggunakan pivot table `inspiration_user_saved`
- Counter `liked_count` di-update real-time saat save/unsave
- Field `is_saved` tidak disimpan di database (calculated on-the-fly)
- Authentication optional untuk browsing, required untuk save
- Toggle endpoint lebih convenient untuk UI (single button)
- DELETE endpoint lebih semantic untuk "Remove" action

---

## Summary

✅ **Browsing (Public - No Login)**:
- `GET /api/public/inspirations` → Browse semua inspirations
- `GET /api/public/inspirations/{id}` → Detail inspiration
- Guest users bisa lihat semua inspirations
- Field `is_saved` tidak muncul untuk guest

✅ **Save Actions (Requires Login)**:
1. **Save/Unsave** → `POST /api/customer/inspirations/{id}/like`
2. **Remove** → `DELETE /api/customer/inspirations/{id}/saved`
3. **View Saved** → `GET /api/customer/my-saved-inspirations`

✅ **Key Fields**:
- `is_saved` (boolean) → Only for logged-in users
- `liked_count` (integer) → Visible for everyone

✅ **UI Flow**:
1. User browse inspirations tanpa login
2. User klik heart button → check if logged in
3. If not logged in → show login prompt
4. If logged in → toggle save/unsave
5. Show saved count for all users
