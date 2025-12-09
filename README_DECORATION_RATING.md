# Decoration Rating & Review Count Documentation

## Overview
Sistem rating dan review count untuk decoration dihitung secara **real-time** dari semua customer reviews. Rating dan review count tidak disimpan di database, melainkan dikalkulasi setiap kali data decoration diambil.

---

## Implementation

### 1. Model Relationship

**File**: `app/Models/Decoration.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Decoration extends Model
{
    // ... other code ...

    /**
     * Get ALL reviews for the decoration
     * Includes both customer reviews (with user_id) and fake reviews (without user_id)
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
        // IMPORTANT: NO filter - returns ALL reviews
    }
}
```

**Notes**:
- Method `reviews()` mengembalikan **SEMUA** reviews (customer + fake reviews)
- **JANGAN** filter `whereNotNull('user_id')` karena akan exclude fake reviews
- Fake reviews digunakan untuk initial rating sebelum ada customer yang review

---

### 2. Controller Implementation

**File**: `app/Http/Controllers/Admin/DecorationController.php`

#### Method: `index()` - Get All Decorations

```php
public function index(Request $request)
{
    $query = Decoration::with('images', 'freeItems', 'advantages', 'terms', 'faqs');

    // Filters...
    if ($request->has('region')) {
        $query->where('region', $request->region);
    }

    $decorations = $query->orderBy('created_at', 'desc')->paginate(15);

    // Calculate rating and review count from ALL customer reviews
    $decorations->getCollection()->transform(function ($decoration) {
        // Average rating dari semua reviews (customer + fake)
        $decoration->rating = round($decoration->reviews()->avg('rating') ?? 0, 1);
        
        // Total jumlah reviews (customer + fake)
        $decoration->review_count = $decoration->reviews()->count();
        
        return $decoration;
    });

    return response()->json([
        'success' => true,
        'data' => $decorations
    ]);
}
```

#### Method: `show()` - Get Single Decoration

```php
public function show($identifier)
{
    // Find by ID or slug
    if (is_numeric($identifier)) {
        $decoration = Decoration::with('images', 'freeItems', 'advantages', 'terms', 'faqs')
            ->findOrFail($identifier);
    } else {
        $decoration = Decoration::with('images', 'freeItems', 'advantages', 'terms', 'faqs')
            ->where('slug', $identifier)
            ->firstOrFail();
    }

    // Calculate rating and review count from ALL reviews
    $decoration->rating = round($decoration->reviews()->avg('rating') ?? 0, 1);
    $decoration->review_count = $decoration->reviews()->count();

    return response()->json([
        'success' => true,
        'data' => $decoration
    ]);
}
```

---

## API Response Examples

### GET /api/public/decorations

**Response**:
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 10,
        "name": "PURE ELEGANCE 9",
        "slug": "pure-elegance-9",
        "region": "Jakarta",
        "description": "Dekorasi elegan...",
        "base_price": 36000000,
        "discount_percent": 10,
        "final_price": 32400000,
        "rating": 4.5,
        "review_count": 12,
        "images": [...],
        "freeItems": [...],
        "advantages": [...],
        "terms": [...],
        "faqs": [...]
      }
    ],
    "total": 25
  }
}
```

### GET /api/public/decorations/{id}

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 10,
    "name": "PURE ELEGANCE 9",
    "slug": "pure-elegance-9",
    "region": "Jakarta",
    "description": "Dekorasi elegan untuk pernikahan impian Anda",
    "base_price": 36000000,
    "discount_percent": 10,
    "final_price": 32400000,
    "discount_start_date": "2025-12-01",
    "discount_end_date": "2025-12-31",
    "rating": 4.5,
    "review_count": 12,
    "is_deals": true,
    "images": [
      {
        "id": 1,
        "image_path": "decorations/image.jpg"
      }
    ],
    "free_items": [
      {
        "id": 1,
        "item_name": "Free Photobooth"
      }
    ],
    "advantages": [...],
    "terms": [...],
    "faqs": [...]
  }
}
```

---

## Rating Calculation Logic

### Formula
```php
// Average rating from ALL reviews
$rating = round($decoration->reviews()->avg('rating') ?? 0, 1);

// Total review count
$review_count = $decoration->reviews()->count();
```

### Examples

#### Example 1: With Reviews
```
Reviews:
- Customer A: 5 stars
- Customer B: 4 stars
- Fake Review: 5 stars

Rating = (5 + 4 + 5) / 3 = 4.7
Review Count = 3
```

#### Example 2: No Reviews
```
Reviews: None

Rating = 0
Review Count = 0
```

#### Example 3: Mixed Reviews
```
Reviews:
- Customer A: 5 stars
- Customer B: 3 stars
- Customer C: 4 stars
- Fake Review 1: 5 stars
- Fake Review 2: 4 stars

Rating = (5 + 3 + 4 + 5 + 4) / 5 = 4.2
Review Count = 5
```

---

## Database Schema

### Reviews Table
```sql
CREATE TABLE reviews (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NULL,           -- NULL = fake review, NOT NULL = customer review
    customer_name VARCHAR(255) NULL,        -- For fake reviews
    decoration_id BIGINT UNSIGNED NOT NULL,
    rating INT NOT NULL,                    -- 1-5
    comment TEXT NOT NULL,
    posted_at DATE NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (decoration_id) REFERENCES decorations(id) ON DELETE CASCADE
);
```

**Review Types**:
1. **Customer Review**: `user_id` NOT NULL, `customer_name` NULL
2. **Fake Review**: `user_id` NULL, `customer_name` NOT NULL

---

## Frontend Implementation

### React/Next.js Example

#### Display Decoration List with Rating

```javascript
import { Star } from 'lucide-react'; // or any star icon

const DecorationCard = ({ decoration }) => {
  const renderStars = (rating) => {
    const stars = [];
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 !== 0;

    // Full stars
    for (let i = 0; i < fullStars; i++) {
      stars.push(
        <Star key={`full-${i}`} className="fill-yellow-400 text-yellow-400" size={16} />
      );
    }

    // Half star
    if (hasHalfStar) {
      stars.push(
        <Star key="half" className="fill-yellow-400 text-yellow-400 opacity-50" size={16} />
      );
    }

    // Empty stars
    const emptyStars = 5 - Math.ceil(rating);
    for (let i = 0; i < emptyStars; i++) {
      stars.push(
        <Star key={`empty-${i}`} className="text-gray-300" size={16} />
      );
    }

    return stars;
  };

  return (
    <div className="decoration-card">
      <img src={decoration.images[0]?.image_path} alt={decoration.name} />
      
      <div className="card-body">
        <h3>{decoration.name}</h3>
        
        <div className="rating-section">
          <div className="stars">
            {renderStars(decoration.rating)}
          </div>
          <span className="rating-text">
            {decoration.rating.toFixed(1)}
          </span>
          <span className="review-count">
            ({decoration.review_count} reviews)
          </span>
        </div>

        <div className="price-section">
          {decoration.discount_percent > 0 ? (
            <>
              <span className="original-price">
                Rp {decoration.base_price.toLocaleString('id-ID')}
              </span>
              <span className="final-price">
                Rp {decoration.final_price.toLocaleString('id-ID')}
              </span>
              <span className="discount-badge">
                -{decoration.discount_percent}%
              </span>
            </>
          ) : (
            <span className="final-price">
              Rp {decoration.base_price.toLocaleString('id-ID')}
            </span>
          )}
        </div>
      </div>
    </div>
  );
};
```

#### Display Decoration Detail with Rating

```javascript
const DecorationDetail = ({ decoration }) => {
  const getRatingText = (count) => {
    if (count === 0) return 'No reviews yet';
    if (count === 1) return '1 review';
    return `${count} reviews`;
  };

  return (
    <div className="decoration-detail">
      <h1>{decoration.name}</h1>
      
      <div className="rating-overview">
        <div className="rating-score">
          <span className="score">{decoration.rating.toFixed(1)}</span>
          <span className="max-score">/ 5.0</span>
        </div>
        
        <div className="rating-stars">
          {[1, 2, 3, 4, 5].map(star => (
            <Star
              key={star}
              className={star <= decoration.rating ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300'}
              size={24}
            />
          ))}
        </div>
        
        <p className="review-count">
          {getRatingText(decoration.review_count)}
        </p>
      </div>

      {/* Rest of decoration details */}
      <div className="description">
        <p>{decoration.description}</p>
      </div>

      {/* Price */}
      <div className="price">
        <span className="final-price">
          Rp {decoration.final_price.toLocaleString('id-ID')}
        </span>
        {decoration.discount_percent > 0 && (
          <>
            <span className="original-price">
              Rp {decoration.base_price.toLocaleString('id-ID')}
            </span>
            <span className="discount">-{decoration.discount_percent}%</span>
          </>
        )}
      </div>
    </div>
  );
};
```

#### Filter/Sort by Rating

```javascript
const DecorationList = () => {
  const [decorations, setDecorations] = useState([]);
  const [sortBy, setSortBy] = useState('newest');

  const sortDecorations = (items, sortType) => {
    const sorted = [...items];
    
    switch(sortType) {
      case 'rating-high':
        return sorted.sort((a, b) => b.rating - a.rating);
      case 'rating-low':
        return sorted.sort((a, b) => a.rating - b.rating);
      case 'most-reviewed':
        return sorted.sort((a, b) => b.review_count - a.review_count);
      case 'price-low':
        return sorted.sort((a, b) => a.final_price - b.final_price);
      case 'price-high':
        return sorted.sort((a, b) => b.final_price - a.final_price);
      default:
        return sorted;
    }
  };

  const sortedDecorations = sortDecorations(decorations, sortBy);

  return (
    <div>
      <div className="sort-controls">
        <label>Sort by:</label>
        <select value={sortBy} onChange={(e) => setSortBy(e.target.value)}>
          <option value="newest">Newest</option>
          <option value="rating-high">Rating: High to Low</option>
          <option value="rating-low">Rating: Low to High</option>
          <option value="most-reviewed">Most Reviewed</option>
          <option value="price-low">Price: Low to High</option>
          <option value="price-high">Price: High to Low</option>
        </select>
      </div>

      <div className="decoration-grid">
        {sortedDecorations.map(decoration => (
          <DecorationCard key={decoration.id} decoration={decoration} />
        ))}
      </div>
    </div>
  );
};
```

---

## Important Notes

### ✅ DO's
- **DO** calculate rating real-time from `reviews()` relationship
- **DO** include ALL reviews (customer + fake) in calculation
- **DO** use `round()` to format rating to 1 decimal place
- **DO** handle null/empty reviews with `?? 0`
- **DO** display both rating number and star visualization

### ❌ DON'Ts
- **DON'T** store rating in database (calculate real-time)
- **DON'T** filter `whereNotNull('user_id')` - includes fake reviews
- **DON'T** use static rating values
- **DON'T** forget to handle zero reviews case

---

## Performance Considerations

### N+1 Query Problem
When fetching multiple decorations, calculating rating can cause N+1 queries.

**Solution: Eager Load with Aggregates**
```php
$decorations = Decoration::with('images')
    ->withAvg('reviews', 'rating')
    ->withCount('reviews')
    ->paginate(15);

// Access with:
// $decoration->reviews_avg_rating
// $decoration->reviews_count
```

### Recommended for Large Datasets
```php
public function index(Request $request)
{
    $decorations = Decoration::with('images', 'freeItems')
        ->withAvg('reviews', 'rating')
        ->withCount('reviews')
        ->orderBy('created_at', 'desc')
        ->paginate(15);

    // Format the aggregated data
    $decorations->getCollection()->transform(function ($decoration) {
        $decoration->rating = round($decoration->reviews_avg_rating ?? 0, 1);
        $decoration->review_count = $decoration->reviews_count ?? 0;
        
        // Remove aggregate attributes
        unset($decoration->reviews_avg_rating);
        unset($decoration->reviews_count);
        
        return $decoration;
    });

    return response()->json([
        'success' => true,
        'data' => $decorations
    ]);
}
```

---

## Testing

### Test Cases

1. **Decoration with No Reviews**
   - Expected: `rating: 0`, `review_count: 0`

2. **Decoration with Customer Reviews Only**
   - Add 3 customer reviews (4, 5, 5)
   - Expected: `rating: 4.7`, `review_count: 3`

3. **Decoration with Mixed Reviews**
   - Add 2 customer reviews (5, 4)
   - Add 2 fake reviews (5, 4)
   - Expected: `rating: 4.5`, `review_count: 4`

4. **Decoration with Low Ratings**
   - Add reviews (1, 2, 3)
   - Expected: `rating: 2.0`, `review_count: 3`

---

## Migration Notes

If you have existing `rating` and `review_count` columns in `decorations` table, you can:

### Option 1: Keep Columns (Recommended)
Keep the columns for caching purposes, but override with calculated values in controller.

### Option 2: Remove Columns
```php
Schema::table('decorations', function (Blueprint $table) {
    $table->dropColumn(['rating', 'review_count']);
});
```

---

## Summary

- ✅ Rating dihitung real-time dari **SEMUA** reviews
- ✅ Review count = total jumlah reviews (customer + fake)
- ✅ Format: `rating: 4.5` (1 decimal), `review_count: 12`
- ✅ Relationship `reviews()` returns ALL reviews (no filter)
- ✅ Frontend dapat sort/filter berdasarkan rating
- ✅ Performa optimal dengan `withAvg()` dan `withCount()`
