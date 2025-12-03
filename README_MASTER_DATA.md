# DWD Wedding Organizer - Data Master & API Integration Guide

## 📊 Data Master Overview

### 1. **Decorations** (Produk Dekorasi)
**Table:** `decorations` + `decoration_images` + `decoration_free_items`
**Purpose:** Data produk/item dekorasi yang bisa dipilih customer

**Main Table Fields (`decorations`):**
- `id` - Primary key
- `name` - Nama dekorasi
- `slug` - URL-friendly name
- `region` - Wilayah/kota (e.g., "Jakarta", "Bandung")
- `description` - Deskripsi lengkap
- `base_price` - Harga dasar (integer, dalam rupiah)
- `discount_percent` - Persentase diskon (0-100)
- `final_price` - Harga setelah diskon (auto-calculated)
- `discount_start_date` - Tanggal mulai diskon
- `discount_end_date` - Tanggal akhir diskon
- `rating` - Rating produk (0-5, decimal)
- `review_count` - Jumlah review
- `is_deals` - Flag untuk deals/promo spesial

**Images Table Fields (`decoration_images`):**
- `id` - Primary key
- `decoration_id` - Foreign key ke decorations
- `image` - Path/URL gambar (bisa multiple per decoration)

**Free Items Table Fields (`decoration_free_items`):**
- `id` - Primary key
- `decoration_id` - Foreign key ke decorations
- `item_name` - Nama item gratis (e.g., "Cinematic Video", "Foto & Video", "Makeup Artist")
- `description` - Deskripsi item gratis
- `quantity` - Jumlah item yang didapat (default: 1)

**API Endpoints:**
```bash
# List decorations with filters
GET /api/admin/decorations?region=Jakarta&search=elegant&is_deals=true
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Elegant Wedding Decoration",
        "slug": "elegant-wedding-decoration",
        "category": "Modern",
        "region": "Jakarta",
        "description": "Beautiful modern decoration...",
        "base_price": 15000000,
        "discount_percent": 10,
        "final_price": 13500000,
        "discount_start_date": "2025-12-01",
        "discount_end_date": "2025-12-31",
        "rating": 4.5,
        "review_count": 12,
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
            "image": "/storage/decorations/decoration_1_detail1.jpg"
          }
        ]
      }
    ],
    "per_page": 15,
    "total": 50
  }
}

# Create decoration
POST /api/admin/decorations
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Elegant Wedding Decoration",
  "region": "Jakarta",
  "description": "Beautiful modern wedding decoration with flowers and lights",
  "base_price": 15000000,
  "discount_percent": 10,
  "discount_start_date": "2025-12-01",
  "discount_end_date": "2025-12-31",
  "is_deals": true
}

Response:
{
  "success": true,
  "message": "Decoration created successfully",
  "data": {
    "id": 1,
    "name": "Elegant Wedding Decoration",
    "slug": "elegant-wedding-decoration",
    ...
  }
}

# Upload decoration images (multiple images)
POST /api/admin/decorations/{id}/images
Authorization: Bearer {token}
Content-Type: multipart/form-data

FormData:
- images[]: file1.jpg
- images[]: file2.jpg
- images[]: file3.jpg

Response:
{
  "success": true,
  "message": "Images uploaded successfully",
  "data": [
    {
      "id": 1,
      "decoration_id": 1,
      "image": "/storage/decorations/decoration_1_main.jpg"
    },
    {
      "id": 2,
      "decoration_id": 1,
      "image": "/storage/decorations/decoration_1_detail1.jpg"
    }
  ]
}

# Delete single decoration image
DELETE /api/admin/decorations/images/{imageId}
Authorization: Bearer {token}

Response:
{
  "success": true,
  "message": "Image deleted successfully"
}

# Get single decoration
GET /api/admin/decorations/{id}
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Elegant Wedding Decoration",
    "slug": "elegant-wedding-decoration",
    "region": "Jakarta",
    "description": "Beautiful modern wedding decoration",
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
      }
    ],
    "freeItems": [
      {
        "id": 1,
        "decoration_id": 1,
        "item_name": "Cinematic Video",
        "description": "Professional HD cinematic wedding video",
        "quantity": 1
      },
      {
        "id": 2,
        "decoration_id": 1,
        "item_name": "Foto & Video",
        "description": "Full day photo and video documentation",
        "quantity": 1
      },
      {
        "id": 3,
        "decoration_id": 1,
        "item_name": "Makeup Artist",
        "description": "Professional makeup for bride and groom",
        "quantity": 2
      }
    ]
  }
}

# === DECORATION FREE ITEMS MANAGEMENT ===

# List all free items for a decoration
GET /api/admin/decorations/{decorationId}/free-items
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": [
    {
      "id": 1,
      "decoration_id": 1,
      "item_name": "Cinematic Video",
      "description": "Professional HD cinematic wedding video",
      "quantity": 1,
      "created_at": "2025-12-03T10:00:00Z",
      "updated_at": "2025-12-03T10:00:00Z"
    },
    {
      "id": 2,
      "decoration_id": 1,
      "item_name": "Foto & Video",
      "description": "Full day photo and video documentation",
      "quantity": 1,
      "created_at": "2025-12-03T10:00:00Z",
      "updated_at": "2025-12-03T10:00:00Z"
    }
  ]
}

# Add free item to decoration
POST /api/admin/decorations/{decorationId}/free-items
Authorization: Bearer {token}
Content-Type: application/json

{
  "item_name": "Makeup Artist",
  "description": "Professional makeup for bride and groom",
  "quantity": 2
}

Response:
{
  "success": true,
  "message": "Free item created successfully",
  "data": {
    "id": 3,
    "decoration_id": 1,
    "item_name": "Makeup Artist",
    "description": "Professional makeup for bride and groom",
    "quantity": 2,
    "created_at": "2025-12-03T10:00:00Z",
    "updated_at": "2025-12-03T10:00:00Z"
  }
}

# Get single free item
GET /api/admin/decorations/{decorationId}/free-items/{id}
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "id": 1,
    "decoration_id": 1,
    "item_name": "Cinematic Video",
    "description": "Professional HD cinematic wedding video",
    "quantity": 1
  }
}

# Update free item
PUT /api/admin/decorations/{decorationId}/free-items/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "item_name": "Cinematic Video 4K",
  "description": "Professional 4K cinematic wedding video with drone shots",
  "quantity": 1
}

Response:
{
  "success": true,
  "message": "Free item updated successfully",
  "data": {
    "id": 1,
    "decoration_id": 1,
    "item_name": "Cinematic Video 4K",
    "description": "Professional 4K cinematic wedding video with drone shots",
    "quantity": 1
  }
}

# Delete free item
DELETE /api/admin/decorations/{decorationId}/free-items/{id}
Authorization: Bearer {token}

Response:
{
  "success": true,
  "message": "Free item deleted successfully"
}

# Update decoration
PUT /api/admin/decorations/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Updated Decoration Name",
  "region": "Jakarta",
  "description": "Updated description",
  "base_price": 16000000,
  "discount_percent": 15,
  "discount_start_date": "2025-12-01",
  "discount_end_date": "2025-12-31",
  "is_deals": false
}

# Delete decoration (cascade deletes images & free items automatically)
DELETE /api/admin/decorations/{id}
Authorization: Bearer {token}

Response:
{
  "success": true,
  "message": "Decoration deleted successfully"
}
```

**Frontend Form Fields:**
```typescript
interface DecorationForm {
  name: string;              // Required, max 255
  region: string;            // Required, dropdown atau input
  description: string;       // Required, textarea
  base_price: number;        // Required, min 0, format rupiah
  discount_percent?: number; // Optional, 0-100
  discount_start_date?: string; // Optional, date picker
  discount_end_date?: string;   // Optional, date picker (must be >= start_date)
  is_deals: boolean;         // Checkbox, default false
  images?: File[];           // Multiple file upload
}

// Untuk upload images setelah create
interface ImageUploadForm {
  decoration_id: number;
  images: File[];            // Multiple file input, accept image/*
}

// Untuk manage free items
interface FreeItemForm {
  item_name: string;         // Required, max 255 (e.g., "Cinematic Video", "Makeup Artist")
  description?: string;      // Optional, textarea
  quantity: number;          // Required, min 1 (default 1)
}

// Response decoration lengkap
interface Decoration {
  id: number;
  name: string;
  slug: string;
  region: string;
  description: string;
  base_price: number;
  discount_percent: number;
  final_price: number;
  discount_start_date: string | null;
  discount_end_date: string | null;
  rating: number;
  review_count: number;
  is_deals: boolean;
  images: DecorationImage[];
  freeItems: FreeItem[];
  created_at: string;
  updated_at: string;
}

interface FreeItem {
  id: number;
  decoration_id: number;
  item_name: string;
  description: string;
  quantity: number;
  created_at: string;
  updated_at: string;
}
```

---

### 2. **Vouchers / Promo Codes** (Kode Voucher)
**Table:** `vouchers`
**Purpose:** Promo code system untuk diskon di checkout cart

**Fields:**
- `id` - Primary key
- `code` - Kode voucher (unique, uppercase, e.g., "WEDDING2024", "FIRSTORDER")
- `type` - Tipe diskon: `percentage` (persen) atau `fixed` (nominal)
- `discount_value` - Nilai diskon: 10 untuk 10%, atau 500000 untuk Rp 500.000
- `min_purchase` - Minimal total belanja untuk bisa pakai voucher (default: 0)
- `max_discount` - Max potongan (hanya untuk percentage type)
- `usage_limit` - Total berapa kali voucher bisa dipakai (null = unlimited)
- `usage_count` - Sudah dipakai berapa kali
- `usage_per_user` - 1 user bisa pakai berapa kali (default: 1)
- `valid_from` - Tanggal mulai berlaku
- `valid_until` - Tanggal kadaluarsa
- `is_active` - Admin bisa enable/disable voucher
- `description` - Deskripsi voucher

**API Endpoints:**
```bash
# List vouchers (Admin)
GET /api/admin/vouchers?is_active=true&type=percentage&search=WEDDING
Authorization: Bearer {admin_token}

Response:
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "code": "WEDDING2024",
        "type": "percentage",
        "discount_value": 10,
        "min_purchase": 5000000,
        "max_discount": 2000000,
        "usage_limit": 100,
        "usage_count": 25,
        "usage_per_user": 1,
        "valid_from": "2024-12-01",
        "valid_until": "2024-12-31",
        "is_active": true,
        "description": "Diskon 10% untuk pernikahan Desember 2024"
      }
    ]
  }
}

# Create voucher (Admin)
POST /api/admin/vouchers
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "code": "NEWYEAR2025",
  "type": "fixed",
  "discount_value": 1000000,
  "min_purchase": 10000000,
  "usage_limit": 50,
  "usage_per_user": 1,
  "valid_from": "2025-01-01",
  "valid_until": "2025-01-31",
  "description": "Diskon Rp 1 juta untuk tahun baru 2025"
}

Response:
{
  "success": true,
  "message": "Voucher created successfully",
  "data": {
    "id": 2,
    "code": "NEWYEAR2025",
    "type": "fixed",
    "discount_value": 1000000,
    ...
  }
}

# Get single voucher (Admin)
GET /api/admin/vouchers/{id}
Authorization: Bearer {admin_token}

# Update voucher (Admin)
PUT /api/admin/vouchers/{id}
Authorization: Bearer {admin_token}
Content-Type: application/json

{
  "code": "NEWYEAR2025",
  "discount_value": 1500000,
  "is_active": true
}

# Delete voucher (Admin)
DELETE /api/admin/vouchers/{id}
Authorization: Bearer {admin_token}

# Validate voucher (Customer - saat checkout)
POST /api/customer/vouchers/validate
Authorization: Bearer {customer_token}
Content-Type: application/json

{
  "code": "WEDDING2024",
  "cart_total": 15000000
}

Response (Success):
{
  "success": true,
  "message": "Voucher is valid",
  "data": {
    "voucher": {
      "id": 1,
      "code": "WEDDING2024",
      "type": "percentage",
      "discount_value": 10,
      ...
    },
    "discount_amount": 1500000,
    "final_total": 13500000,
    "display_text": "10% OFF (max Rp 2.000.000) - Min purchase Rp 5.000.000"
  }
}

Response (Error - Expired):
{
  "success": false,
  "message": "Voucher has expired"
}

Response (Error - Min Purchase):
{
  "success": false,
  "message": "Minimum purchase of Rp 5.000.000 required"
}

Response (Error - Already Used):
{
  "success": false,
  "message": "You have already used this voucher"
}
```

**Frontend Form Fields (Admin):**
```typescript
interface VoucherForm {
  code: string;                  // Required, will be uppercased, unique
  type: 'percentage' | 'fixed';  // Required, radio button
  discount_value: number;        // Required, min 1
  min_purchase?: number;         // Optional, default 0
  max_discount?: number;         // Optional, only for percentage type
  usage_limit?: number;          // Optional, null = unlimited
  usage_per_user?: number;       // Optional, default 1
  valid_from: string;            // Required, date picker
  valid_until: string;           // Required, date picker (>= valid_from)
  description?: string;          // Optional, textarea
}

// Response voucher lengkap
interface Voucher {
  id: number;
  code: string;
  type: 'percentage' | 'fixed';
  discount_value: number;
  min_purchase: number;
  max_discount: number | null;
  usage_limit: number | null;
  usage_count: number;
  usage_per_user: number;
  valid_from: string;
  valid_until: string;
  is_active: boolean;
  description: string;
  created_at: string;
  updated_at: string;
}

// Customer validate response
interface VoucherValidation {
  voucher: Voucher;
  discount_amount: number;       // Nominal diskon yang didapat
  final_total: number;           // Total setelah diskon
  display_text: string;          // Text untuk display ke user
}
```

**Frontend Usage (Customer Checkout):**
```typescript
// 1. User input voucher code
const [voucherCode, setVoucherCode] = useState('');
const [appliedVoucher, setAppliedVoucher] = useState(null);

// 2. Validate voucher
const validateVoucher = async () => {
  try {
    const { data } = await axios.post('/api/customer/vouchers/validate', {
      code: voucherCode,
      cart_total: cartTotal
    });
    
    setAppliedVoucher(data.data);
    alert(`Voucher applied! You save Rp ${data.data.discount_amount.toLocaleString()}`);
  } catch (error) {
    alert(error.response.data.message);
  }
};

// 3. Display discount in cart summary
<div>
  <p>Subtotal: Rp {cartTotal.toLocaleString()}</p>
  {appliedVoucher && (
    <p className="text-green-600">
      Voucher ({appliedVoucher.voucher.code}): 
      - Rp {appliedVoucher.discount_amount.toLocaleString()}
    </p>
  )}
  <p><strong>Total: Rp {appliedVoucher ? appliedVoucher.final_total.toLocaleString() : cartTotal.toLocaleString()}</strong></p>
</div>

// 4. Send voucher code when creating order
const createOrder = async () => {
  await axios.post('/api/orders', {
    items: cartItems,
    voucher_code: appliedVoucher?.voucher.code, // Include voucher code
    total: appliedVoucher?.final_total || cartTotal
  });
};
```

**Contoh Voucher:**
- `WEDDING2024` - Diskon 10% max Rp 2 juta, min belanja Rp 5 juta
- `FIRSTORDER` - Diskon Rp 500 ribu untuk pembelian pertama
- `NEWYEAR2025` - Diskon Rp 1 juta, min belanja Rp 10 juta
- `FLASH50` - Diskon 50%, max Rp 5 juta, limited 20 orang saja
- `FREESHIP` - Gratis ongkir (bisa fixed discount sesuai delivery fee)

---

### 3. **Events** (Event/Acara)
**Table:** `events` + `event_images`
**Purpose:** Event showcase, inspirasi acara pernikahan

**Main Table Fields (`events`):**
- `id` - Primary key
- `title` - Judul event
- `slug` - URL-friendly title
- `banner_image` - Banner utama event
- `start_date` - Tanggal mulai event
- `end_date` - Tanggal akhir event
- `location` - Lokasi event
- `short_description` - Deskripsi singkat
- `full_description` - Deskripsi lengkap
- `organizer` - Nama penyelenggara

**Images Table Fields (`event_images`):**
- `id` - Primary key
- `event_id` - Foreign key ke events
- `image` - Path/URL gambar galeri event

**API Endpoints:**
```bash
# List events with filters
GET /api/admin/events?search=wedding&start_date=2025-01-01&end_date=2025-12-31
Authorization: Bearer {token}

Response:
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
        "full_description": "Complete description...",
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
    ]
  }
}

# Create event
POST /api/admin/events
Authorization: Bearer {token}
Content-Type: multipart/form-data

FormData:
- title: "Grand Wedding Ceremony"
- start_date: "2025-12-15"
- end_date: "2025-12-15"
- location: "Hotel Grand Indonesia, Jakarta"
- short_description: "Beautiful wedding ceremony"
- full_description: "Complete description..."
- organizer: "DWD Wedding Organizer"
- banner_image: file (single banner image)

Response:
{
  "success": true,
  "message": "Event created successfully",
  "data": {
    "id": 1,
    "title": "Grand Wedding Ceremony",
    "banner_image": "/storage/events/banner_1.jpg",
    ...
  }
}

# Upload event gallery images
POST /api/admin/events/{id}/images
Authorization: Bearer {token}
Content-Type: multipart/form-data

FormData:
- images[]: file1.jpg
- images[]: file2.jpg
- images[]: file3.jpg

Response:
{
  "success": true,
  "message": "Images uploaded successfully",
  "data": [
    {
      "id": 1,
      "event_id": 1,
      "image": "/storage/events/event_1_gallery1.jpg"
    }
  ]
}

# Delete event gallery image
DELETE /api/admin/events/images/{imageId}
Authorization: Bearer {token}

# Get single event
GET /api/admin/events/{id}

# Update event
PUT /api/admin/events/{id}

# Delete event
DELETE /api/admin/events/{id}
```

**Frontend Form Fields:**
```typescript
interface EventForm {
  title: string;              // Required, max 255
  start_date: string;         // Required, date picker
  end_date: string;           // Required, date picker (must be >= start_date)
  location: string;           // Required, max 255
  short_description: string;  // Required, textarea, max length
  full_description: string;   // Required, rich text editor
  organizer?: string;         // Optional, max 255
  banner_image: File;         // Required, single file upload
  gallery_images?: File[];    // Optional, multiple file upload
}
```

---

### 4. **Galleries** (Galeri Foto)
**Table:** `galleries`
**Purpose:** Portfolio foto dekorasi yang sudah dikerjakan
**Fields:**
- `id` - Primary key
- `title` - Judul foto
- `category` - Kategori galeri
- `image_url` - URL gambar
- `description` - Deskripsi foto

**API Endpoints:**
```bash
# List galleries
GET /api/admin/galleries?category=Wedding
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "title": "Modern Wedding Setup",
        "category": "Wedding",
        "image_url": "https://example.com/gallery1.jpg",
        "description": "Modern decoration setup at Grand Ballroom"
      }
    ],
    "per_page": 20
  }
}

# Create gallery item
POST /api/admin/galleries
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Modern Wedding Setup",
  "category": "Wedding",
  "image_url": "https://example.com/gallery1.jpg",
  "description": "Modern decoration setup"
}

# Get single gallery
GET /api/admin/galleries/{id}

# Update gallery
PUT /api/admin/galleries/{id}

# Delete gallery
DELETE /api/admin/galleries/{id}
```

**Frontend Form Fields:**
```typescript
interface GalleryForm {
  title: string;        // Required, max 255
  category: string;     // Required, max 255
  image_url: string;    // Required, URL or file upload
  description?: string; // Optional, textarea
}
```

---

### 5. **Testimonials** (Testimoni)
**Table:** `testimonials`
**Purpose:** Review dan testimoni dari customer
**Fields:**
- `id` - Primary key
- `user_id` - Foreign key ke users
- `content` - Isi testimoni
- `rating` - Rating (1-5)
- `is_featured` - Flag untuk testimoni unggulan

**API Endpoints:**
```bash
# List testimonials
GET /api/admin/testimonials?rating=5&is_featured=true
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "user_id": 2,
        "content": "Amazing service! The decoration was perfect!",
        "rating": 5,
        "is_featured": true,
        "user": {
          "id": 2,
          "first_name": "John",
          "last_name": "Doe",
          "email": "john@example.com"
        }
      }
    ]
  }
}

# Create testimonial
POST /api/admin/testimonials
Authorization: Bearer {token}
Content-Type: application/json

{
  "user_id": 2,
  "content": "Amazing service! The decoration was perfect!",
  "rating": 5,
  "is_featured": true
}

# Get single testimonial
GET /api/admin/testimonials/{id}

# Update testimonial
PUT /api/admin/testimonials/{id}

# Delete testimonial
DELETE /api/admin/testimonials/{id}
```

**Frontend Form Fields:**
```typescript
interface TestimonialForm {
  user_id: number;      // Required, dropdown dari users dengan role=customer
  content: string;      // Required, textarea
  rating: number;       // Required, 1-5, star rating component
  is_featured: boolean; // Checkbox, default false
}
```

---

### 6. **Inspirations** (Inspirasi Dekorasi)
**Table:** `inspirations` + `inspiration_user_saved`
**Purpose:** Galeri inspirasi dekorasi untuk customer, bisa di-save/like

**Main Table Fields (`inspirations`):**
- `id` - Primary key
- `title` - Judul inspirasi
- `image` - Path/URL gambar inspirasi (single image)
- `category` - Kategori tema (Modern, Traditional, Rustic, dll)
- `colors` - Array warna dominan (contoh: ["Pink", "Gold"], ["White", "Blue"])
- `location` - Lokasi foto diambil
- `liked_count` - Jumlah user yang save/like inspirasi ini

**Pivot Table (`inspiration_user_saved`):**
- `user_id` - Foreign key ke users
- `inspiration_id` - Foreign key ke inspirations

**API Endpoints:**
```bash
# List inspirations (Customer - public browsing)
# color parameter: cari inspirasi yang mengandung warna tertentu (misal color=Pink akan match ["Pink", "Gold"])
GET /api/customer/inspirations?category=Modern&color=Pink&search=romantic&order_by=liked_count
Authorization: Bearer {token}

Response:
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
    "per_page": 15,
    "total": 50
  }
}

# Get single inspiration (Customer)
GET /api/customer/inspirations/{id}
Authorization: Bearer {token}

Response:
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

# Like/Save inspiration (Customer)
POST /api/customer/inspirations/{id}/like
Authorization: Bearer {token}

Response (Like):
{
  "success": true,
  "message": "Inspiration saved to your list",
  "data": {
    "is_liked": true,
    "liked_count": 126
  }
}

Response (Unlike):
{
  "success": true,
  "message": "Inspiration removed from your saved list",
  "data": {
    "is_liked": false,
    "liked_count": 125
  }
}

# Get my saved inspirations (Customer)
GET /api/customer/my-saved-inspirations
Authorization: Bearer {token}

Response:
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
        "pivot": {
          "user_id": 2,
          "inspiration_id": 1
        }
      }
    ]
  }
}

# === ADMIN ONLY ===

# List inspirations (Admin)
GET /api/admin/inspirations?category=Modern&color=Pink&search=romantic&order_by=liked_count&order_dir=desc
Authorization: Bearer {admin_token}

# Create inspiration (Admin)
POST /api/admin/inspirations
Authorization: Bearer {admin_token}
Content-Type: multipart/form-data

FormData:
- title: "Elegant White Beach Wedding"
- image: file.jpg
- category: "Modern"
- colors[]: "White"
- colors[]: "Blue"
- location: "Lombok"

Response:
{
  "success": true,
  "message": "Inspiration created successfully",
  "data": {
    "id": 2,
    "title": "Elegant White Beach Wedding",
    "image": "/storage/inspirations/1733234567_abc123.jpg",
    "category": "Modern",
    "colors": ["White", "Blue"],
    "location": "Lombok",
    "liked_count": 0,
    "created_at": "2025-12-03T12:00:00Z",
    "updated_at": "2025-12-03T12:00:00Z"
  }
}

# Get single inspiration (Admin)
GET /api/admin/inspirations/{id}
Authorization: Bearer {admin_token}

# Update inspiration (Admin)
PUT /api/admin/inspirations/{id}
Authorization: Bearer {admin_token}
Content-Type: multipart/form-data

FormData:
- title: "Updated Title"
- image: new_file.jpg (optional - akan replace image lama)
- category: "Traditional"
- colors[]: "Gold"
- colors[]: "Red"
- location: "Yogyakarta"

Response:
{
  "success": true,
  "message": "Inspiration updated successfully",
  "data": {
    "id": 2,
    "title": "Updated Title",
    "image": "/storage/inspirations/1733234890_def456.jpg",
    "category": "Traditional",
    "colors": ["Gold", "Red"],
    "location": "Yogyakarta",
    "liked_count": 5,
    "created_at": "2025-12-03T12:00:00Z",
    "updated_at": "2025-12-03T12:30:00Z"
  }
}

# Delete inspiration (Admin - cascade delete saved records)
DELETE /api/admin/inspirations/{id}
Authorization: Bearer {admin_token}

Response:
{
  "success": true,
  "message": "Inspiration deleted successfully"
}
```

**Frontend Form Fields (Admin):**
```typescript
interface InspirationForm {
  title: string;       // Required, max 255
  image: File;         // Required on create, optional on update
  category: string;    // Required, dropdown atau input (Modern, Traditional, Rustic, Minimalist, dll)
  colors: string[];    // Required, array of colors (misal: ["Pink", "Gold", "White"])
  location: string;    // Required, max 255 (Jakarta, Bali, Yogyakarta, dll)
}

// Response inspiration
interface Inspiration {
  id: number;
  title: string;
  image: string;
  category: string;
  colors: string[];    // Array of colors
  location: string;
  liked_count: number;
  created_at: string;
  updated_at: string;
}

// Customer like response
interface LikeResponse {
  is_liked: boolean;
  liked_count: number;
}
```

**Frontend Usage (Customer):**
```typescript
// 1. Browse inspirations dengan filter
const [inspirations, setInspirations] = useState<Inspiration[]>([]);
const [filters, setFilters] = useState({
  category: '',
  color: '',
  search: '',
  order_by: 'liked_count', // popular first
});

const loadInspirations = async () => {
  const { data } = await axios.get('/api/customer/inspirations', {
    params: filters
  });
  setInspirations(data.data.data);
};

// 2. Toggle like/save inspiration
const toggleLike = async (inspirationId: number) => {
  try {
    const { data } = await axios.post(
      `/api/customer/inspirations/${inspirationId}/like`
    );
    
    // Update UI dengan is_liked & liked_count baru
    alert(data.message);
    loadInspirations(); // Refresh list
  } catch (error) {
    console.error('Failed to toggle like:', error);
  }
};

// 3. Display inspiration card
<div className="inspiration-card">
  <img src={inspiration.image} alt={inspiration.title} />
  <h3>{inspiration.title}</h3>
  <div className="tags">
    <span className="badge">{inspiration.category}</span>
    {inspiration.colors.map((color, i) => (
      <span key={i} className="badge">{color}</span>
    ))}
    <span className="badge">{inspiration.location}</span>
  </div>
  <button onClick={() => toggleLike(inspiration.id)}>
    ❤️ {inspiration.liked_count} likes
  </button>
</div>

// 4. My saved inspirations page
const MySavedInspirations = () => {
  const [saved, setSaved] = useState<Inspiration[]>([]);
  
  useEffect(() => {
    const loadSaved = async () => {
      const { data } = await axios.get('/api/customer/my-saved-inspirations');
      setSaved(data.data.data);
    };
    loadSaved();
  }, []);
  
  return (
    <div>
      <h2>My Saved Inspirations ({saved.length})</h2>
      {saved.map(item => (
        <InspirationCard key={item.id} inspiration={item} />
      ))}
    </div>
  );
};
```

**Use Cases:**
1. **Customer browsing:** Cari inspirasi tema/warna untuk pernikahan
2. **Save/Like:** Bookmark inspirasi favorit untuk referensi
3. **Filter:** Cari by kategori (Modern/Traditional), warna, lokasi
4. **Sort:** Urutkan by popular (liked_count) atau terbaru (created_at)
5. **My Collection:** Lihat semua inspirasi yang sudah di-save

---

### 7. **Vendors** (Vendor Partner)
**Table:** `vendors` + `vendor_images`
**Purpose:** Data vendor partner (catering, photographer, dll)

**Main Table Fields (`vendors`):**
- `id` - Primary key
- `name` - Nama vendor
- `slug` - URL-friendly name
- `category` - Kategori vendor (Catering, Photographer, dll)
- `email` - Email vendor
- `phone` - Nomor telepon
- `address` - Alamat vendor
- `description` - Deskripsi vendor
- `rating` - Rating vendor (0-5)

**Images Table Fields (`vendor_images`):**
- `id` - Primary key
- `vendor_id` - Foreign key ke vendors
- `image` - Path/URL gambar portfolio vendor

**API Endpoints:**
```bash
# List vendors
GET /api/admin/vendors?category=Catering&search=premium
Authorization: Bearer {token}

Response:
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "name": "Premium Catering Service",
        "slug": "premium-catering-service",
        "category": "Catering",
        "email": "info@premiumcatering.com",
        "phone": "+6281234567890",
        "address": "Jl. Sudirman No. 123, Jakarta",
        "description": "Professional catering service",
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
    ]
  }
}

# Create vendor
POST /api/admin/vendors
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Premium Catering Service",
  "category": "Catering",
  "email": "info@premiumcatering.com",
  "phone": "+6281234567890",
  "address": "Jl. Sudirman No. 123, Jakarta",
  "description": "Professional catering service",
  "rating": 4.8
}

# Upload vendor portfolio images
POST /api/admin/vendors/{id}/images
Authorization: Bearer {token}
Content-Type: multipart/form-data

FormData:
- images[]: file1.jpg
- images[]: file2.jpg
- images[]: file3.jpg

Response:
{
  "success": true,
  "message": "Images uploaded successfully",
  "data": [
    {
      "id": 1,
      "vendor_id": 1,
      "image": "/storage/vendors/vendor_1_portfolio1.jpg"
    }
  ]
}

# Delete vendor image
DELETE /api/admin/vendors/images/{imageId}
Authorization: Bearer {token}

# Get single vendor
GET /api/admin/vendors/{id}

# Update vendor
PUT /api/admin/vendors/{id}

# Delete vendor
DELETE /api/admin/vendors/{id}
```

**Frontend Form Fields:**
```typescript
interface VendorForm {
  name: string;         // Required, max 255
  category: string;     // Required, dropdown (Catering, Photographer, Music, etc)
  email: string;        // Required, email format, unique
  phone: string;        // Required, max 20
  address?: string;     // Optional, textarea
  description?: string; // Optional, textarea
  rating?: number;      // Optional, 0-5, decimal
  images?: File[];      // Multiple file upload for portfolio
}
```

---

## 📸 Image Upload Guide

### File Upload Pattern

Semua image upload menggunakan **multipart/form-data**:

```typescript
// Upload decoration images
const formData = new FormData();
files.forEach(file => {
  formData.append('images[]', file);
});

await api.post(`/admin/decorations/${decorationId}/images`, formData, {
  headers: {
    'Content-Type': 'multipart/form-data'
  }
});
```

### Image Management Workflow

1. **Create entity** (decoration/event/vendor) → Get ID
2. **Upload images** using entity ID
3. **Display images** in list/detail view
4. **Delete images** individually if needed

### Storage Structure
```
storage/
├── decorations/
│   ├── decoration_1_main.jpg
│   ├── decoration_1_detail1.jpg
│   └── decoration_2_main.jpg
├── events/
│   ├── banner_1.jpg
│   ├── event_1_gallery1.jpg
│   └── event_1_gallery2.jpg
└── vendors/
    ├── vendor_1_portfolio1.jpg
    └── vendor_1_portfolio2.jpg
```

---

## 🎯 Data Transaksi (Non-Master)

### 1. **Orders** (Pesanan)
**Table:** `orders` + `order_items`
**Purpose:** Data pesanan customer
**Status:** Need to implement controller
**Fields:**
- Order info: `order_number`, `user_id`, `total_amount`, `status`, `payment_status`
- Dates: `order_date`, `event_date`
- Customer info: `customer_name`, `customer_phone`, `customer_email`
- Event info: `event_location`, `notes`

### 2. **Payments** (Pembayaran)
**Table:** `payments`
**Purpose:** Data pembayaran order
**Status:** Need to implement controller
**Fields:**
- `order_id`, `amount`, `payment_method`, `payment_status`
- `payment_date`, `payment_proof`, `notes`

### 3. **Consultations** (Konsultasi)
**Table:** `consultations`
**Purpose:** Request konsultasi dari customer
**Status:** Need to implement controller
**Fields:**
- `user_id`, `name`, `phone`, `email`
- `preferred_date`, `message`, `status`

### 4. **Reviews** (Review Produk)
**Table:** `reviews`
**Purpose:** Review produk dari customer
**Status:** Need to implement controller
**Fields:**
- `user_id`, `decoration_id`, `rating`, `comment`

---

## 🔧 Frontend Integration Guide

### Setup Axios Instance

```typescript
// lib/axios.ts
import axios from 'axios';

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  }
});

// Add token to requests
api.interceptors.request.use(config => {
  const token = localStorage.getItem('token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Handle errors
api.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

### Generic CRUD Service

```typescript
// services/crudService.ts
import api from '@/lib/axios';

export class CRUDService<T> {
  constructor(private endpoint: string) {}

  async getAll(params?: any) {
    const response = await api.get(this.endpoint, { params });
    return response.data;
  }

  async getById(id: number) {
    const response = await api.get(`${this.endpoint}/${id}`);
    return response.data;
  }

  async create(data: Partial<T>) {
    const response = await api.post(this.endpoint, data);
    return response.data;
  }

  async update(id: number, data: Partial<T>) {
    const response = await api.put(`${this.endpoint}/${id}`, data);
    return response.data;
  }

  async delete(id: number) {
    const response = await api.delete(`${this.endpoint}/${id}`);
    return response.data;
  }
}

// Usage
export const decorationService = new CRUDService('/admin/decorations');
export const packageService = new CRUDService('/admin/packages');
export const eventService = new CRUDService('/admin/events');
// ... etc
```

### React Query Hooks Example

```typescript
// hooks/useDecorations.ts
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { decorationService } from '@/services/crudService';

export function useDecorations(filters?: any) {
  return useQuery({
    queryKey: ['decorations', filters],
    queryFn: () => decorationService.getAll(filters)
  });
}

export function useCreateDecoration() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: (data: any) => decorationService.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['decorations'] });
    }
  });
}

export function useUpdateDecoration() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: ({ id, data }: any) => decorationService.update(id, data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['decorations'] });
    }
  });
}

export function useDeleteDecoration() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: (id: number) => decorationService.delete(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['decorations'] });
    }
  });
}
```

---

## 📋 Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### Pagination Response
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [ ... ],
    "first_page_url": "...",
    "last_page": 10,
    "per_page": 15,
    "total": 150
  }
}
```

---

## 🔐 Authentication

Semua endpoint membutuhkan Bearer token kecuali:
- `/api/auth/register`
- `/api/auth/login`
- `/api/health`

Header yang diperlukan:
```
Authorization: Bearer {your_token_here}
Content-Type: application/json
Accept: application/json
```

---

**Last Updated:** December 2, 2025  
**Base URL:** `http://localhost:8000/api`
