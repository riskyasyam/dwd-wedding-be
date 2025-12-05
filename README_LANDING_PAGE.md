# DWD Wedding Organizer - Landing Page API Guide

## 🌐 Public API Endpoints (Tanpa Authentication)

**Base URL:** `http://localhost:8000/api/public`

Semua endpoint di bawah ini **TIDAK memerlukan token** dan bisa diakses langsung dari landing page.

---

## 📋 Table of Contents
1. [Decorations](#1-decorations)
2. [Events](#2-events)
3. [Advertisements](#3-advertisements)
4. [Testimonials](#4-testimonials)
5. [Inspirations](#5-inspirations)
6. [Vendors](#6-vendors)

---

## 1. Decorations

### List Decorations
```bash
GET /api/public/decorations?region=Jakarta&is_deals=true&page=1&per_page=12
```

**Query Parameters:**
- `region` - Filter by region/kota (Jakarta, Bandung, Bali, dll)
- `search` - Search by name
- `is_deals` - Filter deals/promo (true/false)
- `page` - Page number (default: 1)
- `per_page` - Items per page (default: 15)

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Elegant Wedding Decoration",
        "slug": "elegant-wedding-decoration",
        "region": "Jakarta",
        "description": "Beautiful modern wedding decoration",
        "base_price": 15000000,
        "discount_percent": 10,
        "final_price": 13500000,
        "rating": 4.8,
        "review_count": 25,
        "is_deals": true,
        "images": [
          {
            "id": 1,
            "image": "/storage/decorations/decoration_1.jpg"
          }
        ],
        "freeItems": [
          {
            "id": 1,
            "item_name": "Cinematic Video",
            "description": "Professional HD video",
            "quantity": 1
          }
        ]
      }
    ],
    "per_page": 12,
    "total": 50,
    "last_page": 5
  }
}
```

### Get Single Decoration
```bash
# By ID
GET /api/public/decorations/1

# By Slug (recommended for SEO-friendly URLs)
GET /api/public/decorations/elegant-wedding-decoration
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Elegant Wedding Decoration",
    "slug": "elegant-wedding-decoration",
    "region": "Jakarta",
    "description": "Beautiful modern wedding decoration with flowers and lights",
    "base_price": 15000000,
    "discount_percent": 10,
    "final_price": 13500000,
    "discount_start_date": "2025-12-01",
    "discount_end_date": "2025-12-31",
    "rating": 4.8,
    "review_count": 25,
    "is_deals": true,
    "images": [
      {
        "id": 1,
        "decoration_id": 1,
        "image": "/storage/decorations/decoration_1_main.jpg"
      },
      {
        "id": 2,
        "decoration_id": 1,
        "image": "/storage/decorations/decoration_1_detail.jpg"
      }
    ],
    "freeItems": [
      {
        "id": 1,
        "item_name": "Cinematic Video",
        "description": "Professional HD cinematic wedding video",
        "quantity": 1
      },
      {
        "id": 2,
        "item_name": "Makeup Artist",
        "description": "Professional makeup for bride and groom",
        "quantity": 2
      }
    ]
  }
}
```

---

## 2. Events

### List Events
```bash
GET /api/public/events?page=1&per_page=6
```

**Query Parameters:**
- `search` - Search by title or location
- `page` - Page number
- `per_page` - Items per page

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "Grand Wedding Ceremony",
        "slug": "grand-wedding-ceremony",
        "banner_image": "/storage/events/banner_1.jpg",
        "start_date": "2025-12-15",
        "end_date": "2025-12-15",
        "location": "Hotel Grand Indonesia, Jakarta",
        "short_description": "Beautiful wedding at Grand Ballroom",
        "organizer": "DWD Wedding Organizer",
        "images": [
          {
            "id": 1,
            "image": "/storage/events/event_1_gallery1.jpg"
          }
        ]
      }
    ],
    "per_page": 6,
    "total": 20
  }
}
```

### Get Single Event
```bash
# By ID
GET /api/public/events/1

# By Slug (recommended for SEO-friendly URLs)
GET /api/public/events/grand-wedding-ceremony
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Grand Wedding Ceremony",
    "slug": "grand-wedding-ceremony",
    "banner_image": "/storage/events/banner_1.jpg",
    "start_date": "2025-12-15",
    "end_date": "2025-12-15",
    "location": "Hotel Grand Indonesia, Jakarta",
    "short_description": "Beautiful wedding at Grand Ballroom",
    "full_description": "Complete description with full details...",
    "organizer": "DWD Wedding Organizer",
    "images": [
      {
        "id": 1,
        "event_id": 1,
        "image": "/storage/events/event_1_gallery1.jpg"
      },
      {
        "id": 2,
        "event_id": 1,
        "image": "/storage/events/event_1_gallery2.jpg"
      }
    ]
  }
}
```

---

## 3. Advertisements

### Get Active Advertisements (untuk Banner/Carousel)
```bash
GET /api/public/advertisements
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "Wedding Fair 2024",
      "image": "/storage/advertisements/banner_1.jpg",
      "description": "Join our biggest wedding fair in Bali",
      "link_url": "https://dwdwedding.com/wedding-fair",
      "order": 0
    },
    {
      "id": 2,
      "title": "Grand Wedding Ceremony",
      "image": "/storage/advertisements/banner_2.jpg",
      "description": null,
      "link_url": null,
      "order": 1
    }
  ]
}
```

**Note:** Hanya menampilkan iklan yang:
- `is_active = true`
- Dalam rentang tanggal (start_date - end_date)
- Sorted by `order` ASC

---

## 4. Testimonials

### List Testimonials
```bash
GET /api/public/testimonials?rating=5&is_featured=true&page=1&per_page=6
```

**Query Parameters:**
- `rating` - Filter by rating (1-5)
- `is_featured` - Filter featured testimonials (true/false)
- `page` - Page number
- `per_page` - Items per page

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "content": "Amazing service! The decoration was perfect!",
        "rating": 5,
        "is_featured": true,
        "created_at": "2025-11-20T10:00:00Z",
        "user": {
          "id": 2,
          "name": "John Doe",
          "email": "john@example.com"
        }
      }
    ],
    "per_page": 6,
    "total": 15
  }
}
```

---

## 5. Inspirations

### List Inspirations
```bash
GET /api/public/inspirations?color=Pink&location=Bali&search=romantic&order_by=liked_count&page=1&per_page=12
```

**Query Parameters:**
- `category` - Filter by category (Modern, Traditional, Rustic, dll)
- `color` - Filter by color (cari yang mengandung warna ini)
- `location` - Filter by location
- `search` - Search by title
- `order_by` - Sort by: `liked_count` (popular) atau `created_at` (newest)
- `order_dir` - Sort direction: `desc` atau `asc`
- `page` - Page number
- `per_page` - Items per page

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "Romantic Pink Garden Wedding",
        "image": "/storage/inspirations/inspiration_1.jpg",
        "category": "Modern",
        "colors": ["Pink", "Gold"],
        "location": "Bali",
        "liked_count": 125,
        "created_at": "2025-12-01T10:00:00Z"
      }
    ],
    "per_page": 12,
    "total": 50
  }
}
```

### Get Single Inspiration
```bash
GET /api/public/inspirations/{id}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Romantic Pink Garden Wedding",
    "image": "/storage/inspirations/inspiration_1.jpg",
    "category": "Modern",
    "colors": ["Pink", "Gold"],
    "location": "Bali",
    "liked_count": 125,
    "created_at": "2025-12-01T10:00:00Z",
    "updated_at": "2025-12-01T10:00:00Z"
  }
}
```

---

## 6. Vendors

### List Vendors
```bash
GET /api/public/vendors?category=Fotografi&search=premium&page=1&per_page=12
```

**Query Parameters:**
- `category` - Filter by category: Fotografi, Videografi, Make up / Hair & Hijab, Attire, Entertainment (Musik)
- `search` - Search by name
- `page` - Page number
- `per_page` - Items per page

**Response:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Premium Photography Service",
        "slug": "premium-photography-service",
        "category": "Fotografi",
        "email": "info@premiumphotography.com",
        "phone": "+6281234567890",
        "address": "Jl. Sudirman No. 123, Jakarta",
        "description": "Professional photography service",
        "rating": 4.8,
        "images": [
          {
            "id": 1,
            "image": "/storage/vendors/vendor_1_portfolio1.jpg"
          }
        ]
      }
    ],
    "per_page": 12,
    "total": 30
  }
}
```

### Get Single Vendor
```bash
# By ID
GET /api/public/vendors/1

# By Slug (recommended for SEO-friendly URLs)
GET /api/public/vendors/premium-photography-service
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Premium Photography Service",
    "slug": "premium-photography-service",
    "category": "Fotografi",
    "email": "info@premiumphotography.com",
    "phone": "+6281234567890",
    "address": "Jl. Sudirman No. 123, Jakarta",
    "description": "Professional photography service with 10+ years experience",
    "rating": 4.8,
    "images": [
      {
        "id": 1,
        "vendor_id": 1,
        "image": "/storage/vendors/vendor_1_portfolio1.jpg"
      },
      {
        "id": 2,
        "vendor_id": 1,
        "image": "/storage/vendors/vendor_1_portfolio2.jpg"
      }
    ]
  }
}
```

---

## 🎨 Frontend Integration Examples

### 1. Homepage - Hero Banner Carousel

```typescript
// components/HeroBanner.tsx
import { useState, useEffect } from 'react';
import axios from 'axios';

interface Advertisement {
  id: number;
  title: string;
  image: string;
  description: string | null;
  link_url: string | null;
}

export default function HeroBanner() {
  const [banners, setBanners] = useState<Advertisement[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchBanners = async () => {
      try {
        const { data } = await axios.get('http://localhost:8000/api/public/advertisements');
        setBanners(data.data);
      } catch (error) {
        console.error('Failed to fetch banners:', error);
      } finally {
        setLoading(false);
      }
    };
    fetchBanners();
  }, []);

  if (loading) return <div>Loading...</div>;

  return (
    <div className="carousel">
      {banners.map(banner => (
        <div key={banner.id} className="carousel-item">
          {banner.link_url ? (
            <a href={banner.link_url} target="_blank">
              <img src={`http://localhost:8000${banner.image}`} alt={banner.title} />
            </a>
          ) : (
            <img src={`http://localhost:8000${banner.image}`} alt={banner.title} />
          )}
        </div>
      ))}
    </div>
  );
}
```

### 2. Decorations Section - Featured Deals

```typescript
// components/FeaturedDecorations.tsx
import { useState, useEffect } from 'react';
import axios from 'axios';

export default function FeaturedDecorations() {
  const [decorations, setDecorations] = useState([]);

  useEffect(() => {
    const fetchDecorations = async () => {
      try {
        const { data } = await axios.get('http://localhost:8000/api/public/decorations', {
          params: {
            is_deals: true,
            per_page: 6
          }
        });
        setDecorations(data.data.data);
      } catch (error) {
        console.error('Failed to fetch decorations:', error);
      }
    };
    fetchDecorations();
  }, []);

  return (
    <section className="featured-decorations">
      <h2>Special Deals</h2>
      <div className="grid grid-cols-3 gap-6">
        {decorations.map(deco => (
          <div key={deco.id} className="decoration-card">
            <img src={`http://localhost:8000${deco.images[0]?.image}`} alt={deco.name} />
            <h3>{deco.name}</h3>
            <p className="location">{deco.region}</p>
            <div className="price">
              {deco.discount_percent > 0 && (
                <span className="old-price">Rp {deco.base_price.toLocaleString()}</span>
              )}
              <span className="final-price">Rp {deco.final_price.toLocaleString()}</span>
            </div>
            <div className="rating">
              ⭐ {deco.rating} ({deco.review_count} reviews)
            </div>
          </div>
        ))}
      </div>
    </section>
  );
}
```

### 3. Testimonials Section

```typescript
// components/TestimonialsSection.tsx
import { useState, useEffect } from 'react';
import axios from 'axios';

export default function TestimonialsSection() {
  const [testimonials, setTestimonials] = useState([]);

  useEffect(() => {
    const fetchTestimonials = async () => {
      try {
        const { data } = await axios.get('http://localhost:8000/api/public/testimonials', {
          params: {
            is_featured: true,
            per_page: 6
          }
        });
        setTestimonials(data.data.data);
      } catch (error) {
        console.error('Failed to fetch testimonials:', error);
      }
    };
    fetchTestimonials();
  }, []);

  return (
    <section className="testimonials">
      <h2>What Our Clients Say</h2>
      <div className="grid grid-cols-3 gap-6">
        {testimonials.map(testimonial => (
          <div key={testimonial.id} className="testimonial-card">
            <div className="stars">
              {'⭐'.repeat(testimonial.rating)}
            </div>
            <p className="content">{testimonial.content}</p>
            <p className="author">- {testimonial.user.name}</p>
          </div>
        ))}
      </div>
    </section>
  );
}
```

### 4. Inspirations Gallery

```typescript
// pages/inspirations.tsx
import { useState, useEffect } from 'react';
import axios from 'axios';

export default function InspirationsPage() {
  const [inspirations, setInspirations] = useState([]);
  const [filters, setFilters] = useState({
    color: '',
    location: '',
    order_by: 'liked_count'
  });

  useEffect(() => {
    const fetchInspirations = async () => {
      try {
        const { data } = await axios.get('http://localhost:8000/api/public/inspirations', {
          params: filters
        });
        setInspirations(data.data.data);
      } catch (error) {
        console.error('Failed to fetch inspirations:', error);
      }
    };
    fetchInspirations();
  }, [filters]);

  return (
    <div className="inspirations-page">
      <div className="filters">
        <select onChange={(e) => setFilters({...filters, color: e.target.value})}>
          <option value="">All Colors</option>
          <option value="Pink">Pink</option>
          <option value="Gold">Gold</option>
          <option value="White">White</option>
        </select>
        
        <select onChange={(e) => setFilters({...filters, order_by: e.target.value})}>
          <option value="liked_count">Most Popular</option>
          <option value="created_at">Newest</option>
        </select>
      </div>

      <div className="masonry-grid">
        {inspirations.map(item => (
          <div key={item.id} className="inspiration-card">
            <img src={`http://localhost:8000${item.image}`} alt={item.title} />
            <div className="overlay">
              <h3>{item.title}</h3>
              <div className="tags">
                {item.colors.map(color => (
                  <span key={color} className="badge">{color}</span>
                ))}
              </div>
              <p>❤️ {item.liked_count} likes</p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
```

### 5. Events Section

```typescript
// components/UpcomingEvents.tsx
import { useState, useEffect } from 'react';
import axios from 'axios';

export default function UpcomingEvents() {
  const [events, setEvents] = useState([]);

  useEffect(() => {
    const fetchEvents = async () => {
      try {
        const { data } = await axios.get('http://localhost:8000/api/public/events', {
          params: { per_page: 3 }
        });
        setEvents(data.data.data);
      } catch (error) {
        console.error('Failed to fetch events:', error);
      }
    };
    fetchEvents();
  }, []);

  return (
    <section className="upcoming-events">
      <h2>Upcoming Events</h2>
      <div className="events-grid">
        {events.map(event => (
          <div key={event.id} className="event-card">
            <img src={`http://localhost:8000${event.banner_image}`} alt={event.title} />
            <div className="event-info">
              <h3>{event.title}</h3>
              <p className="date">📅 {event.start_date}</p>
              <p className="location">📍 {event.location}</p>
              <p className="description">{event.short_description}</p>
            </div>
          </div>
        ))}
      </div>
    </section>
  );
}
```

---

## 🚀 Quick Start

### 1. Setup Axios Instance

```typescript
// lib/api.ts
import axios from 'axios';

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
});

export default api;
```

### 2. Usage in Components

```typescript
import api from '@/lib/api';

// Fetch decorations
const { data } = await api.get('/public/decorations');

// Fetch with filters
const { data } = await api.get('/public/decorations', {
  params: {
    region: 'Jakarta',
    is_deals: true,
    per_page: 12
  }
});
```

---

## ⚠️ Error Handling

```typescript
try {
  const { data } = await api.get('/public/decorations');
  setDecorations(data.data.data);
} catch (error) {
  if (error.response) {
    // Server responded with error
    console.error('Error:', error.response.data.message);
  } else if (error.request) {
    // No response from server
    console.error('Network error: Server not responding');
  } else {
    // Other errors
    console.error('Error:', error.message);
  }
}
```

---

## 📝 Notes

1. **Semua endpoint PUBLIC tidak perlu token** - langsung bisa diakses
2. **Base URL:** Ganti `http://localhost:8000` dengan domain production
3. **Image Path:** Semua path image sudah include `/storage/`, tinggal concat dengan base URL
4. **Pagination:** Gunakan `per_page` untuk control jumlah data per halaman
5. **CORS:** Sudah di-setup untuk allow all origins di development

---

**Last Updated:** December 4, 2025
