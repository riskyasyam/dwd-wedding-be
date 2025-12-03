# Database Schema - DWD Wedding Organizer

## Overview
Database ini dirancang untuk website wedding organizer dengan fitur pemesanan dekorasi, vendor directory, event management, dan payment gateway Midtrans.

---

## Tables

### 1. users
**Purpose**: Menyimpan data customer dan admin dengan dukungan social login

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PK, Auto Increment | Primary key |
| first_name | VARCHAR | NOT NULL | Nama depan user |
| last_name | VARCHAR | NOT NULL | Nama belakang user |
| email | VARCHAR | UNIQUE, NOT NULL | Email address |
| phone | VARCHAR | NULLABLE | Nomor telepon |
| password | VARCHAR | NOT NULL | Hashed password |
| provider | ENUM | DEFAULT 'local' | Social login provider (local, google, facebook, apple) |
| provider_id | VARCHAR | NULLABLE | ID dari social provider |
| role | ENUM | DEFAULT 'customer' | Role user (customer, admin) |
| email_verified_at | TIMESTAMP | NULLABLE | Timestamp email verification |
| remember_token | VARCHAR | NULLABLE | Laravel remember token |
| created_at | TIMESTAMP | | Created timestamp |
| updated_at | TIMESTAMP | | Updated timestamp |

**Relationships**:
- hasOne: Cart
- hasMany: Order, Review
- belongsToMany: Inspiration (through inspiration_user_saved)

**Default Users**:
- Admin: admin@dwdecor.co.id / password
- Customer: customer@example.com / password

---

### 2. inspirations
**Purpose**: Gallery inspirasi dekorasi yang bisa disave oleh user

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PK, Auto Increment | Primary key |
| title | VARCHAR | NOT NULL | Judul inspirasi |
| image | VARCHAR | NOT NULL | Path gambar |
| category | VARCHAR | NOT NULL | Kategori dekorasi (traditional, modern, minimalist, dll) |
| color | VARCHAR | NOT NULL | Color theme (white, gold, pink, dll) |
| location | VARCHAR | NOT NULL | Lokasi venue |
| liked_count | INT | DEFAULT 0 | Jumlah yang menyimpan |
| created_at | TIMESTAMP | | Created timestamp |
| updated_at | TIMESTAMP | | Updated timestamp |

**Relationships**:
- belongsToMany: User (through inspiration_user_saved)

---

### 3. inspiration_user_saved
**Purpose**: Pivot table untuk user yang save inspiration

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PK, Auto Increment | Primary key |
| user_id | BIGINT | FK, NOT NULL | Foreign key to users |
| inspiration_id | BIGINT | FK, NOT NULL | Foreign key to inspirations |
| created_at | TIMESTAMP | | Created timestamp |
| updated_at | TIMESTAMP | | Updated timestamp |

**Constraints**:
- UNIQUE (user_id, inspiration_id)
- ON DELETE CASCADE

---

### 4. decorations
**Purpose**: Produk dekorasi yang bisa dipesan

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PK, Auto Increment | Primary key |
| name | VARCHAR | NOT NULL | Nama produk dekorasi |
| slug | VARCHAR | UNIQUE, NOT NULL | URL-friendly slug |
| category | VARCHAR | NOT NULL | Kategori (wedding, engagement, birthday, dll) |
| region | VARCHAR | NOT NULL | Region/area (Jakarta, Bandung, dll) |
| description | LONGTEXT | NOT NULL | Deskripsi lengkap produk |
| base_price | BIGINT | NOT NULL | Harga dasar (dalam rupiah) |
| discount_percent | INT | DEFAULT 0 | Persentase diskon |
| final_price | BIGINT | NOT NULL | Harga setelah diskon |
| rating | DECIMAL(2,1) | DEFAULT 0 | Rating rata-rata (0.0 - 5.0) |
| review_count | INT | DEFAULT 0 | Jumlah review |
| is_deals | BOOLEAN | DEFAULT false | Apakah termasuk deals of the week |
| created_at | TIMESTAMP | | Created timestamp |
| updated_at | TIMESTAMP | | Updated timestamp |

**Relationships**:
- hasMany: DecorationImage, Review, CartItem, OrderItem

---

### 5. decoration_images
**Purpose**: Multiple images untuk setiap produk dekorasi (carousel)

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PK, Auto Increment | Primary key |
| decoration_id | BIGINT | FK, NOT NULL | Foreign key to decorations |
| image | VARCHAR | NOT NULL | Path gambar |
| created_at | TIMESTAMP | | Created timestamp |
| updated_at | TIMESTAMP | | Updated timestamp |

**Relationships**:
- belongsTo: Decoration

**Constraints**:
- ON DELETE CASCADE

---

### 6. reviews
**Purpose**: Rating dan review dari user untuk produk dekorasi

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PK, Auto Increment | Primary key |
| user_id | BIGINT | FK, NOT NULL | Foreign key to users |
| decoration_id | BIGINT | FK, NOT NULL | Foreign key to decorations |
| rating | INT | NOT NULL | Rating 1-5 |
| comment | TEXT | NULLABLE | Komentar/review text |
| posted_at | DATE | NOT NULL | Tanggal posting |
| created_at | TIMESTAMP | | Created timestamp |
| updated_at | TIMESTAMP | | Updated timestamp |

**Relationships**:
- belongsTo: User, Decoration

**Constraints**:
- ON DELETE CASCADE

---

### 7. carts
**Purpose**: Shopping cart untuk setiap user

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PK, Auto Increment | Primary key |
| user_id | BIGINT | FK, NOT NULL | Foreign key to users |
| created_at | TIMESTAMP | | Created timestamp |
| updated_at | TIMESTAMP | | Updated timestamp |

**Relationships**:
- belongsTo: User
- hasMany: CartItem

**Constraints**:
- ON DELETE CASCADE

---

### 8. cart_items
**Purpose**: Item-item dalam shopping cart

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PK, Auto Increment | Primary key |
| cart_id | BIGINT | FK, NOT NULL | Foreign key to carts |
| decoration_id | BIGINT | FK, NOT NULL | Foreign key to decorations |
| type | ENUM | DEFAULT 'custom' | Tipe pemesanan (custom, random) |
| quantity | INT | DEFAULT 1 | Jumlah item |
| price | BIGINT | NOT NULL | Harga per item (snapshot saat add to cart) |
| created_at | TIMESTAMP | | Created timestamp |
| updated_at | TIMESTAMP | | Updated timestamp |

**Relationships**:
- belongsTo: Cart, Decoration

**Constraints**:
- ON DELETE CASCADE

---

### 9. orders
**Purpose**: Order yang sudah di-checkout

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PK, Auto Increment | Primary key |
| user_id | BIGINT | FK, NOT NULL | Foreign key to users |
| order_number | VARCHAR | UNIQUE, NOT NULL | Nomor order unik |
| subtotal | BIGINT | NOT NULL | Total sebelum diskon dan ongkir |
| discount | BIGINT | DEFAULT 0 | Jumlah diskon |
| delivery_fee | BIGINT | DEFAULT 0 | Biaya pengiriman/setup |
| total | BIGINT | NOT NULL | Total akhir yang harus dibayar |
| status | ENUM | DEFAULT 'pending' | Status order (pending, paid, failed, completed, cancelled) |
| payment_method | VARCHAR | NULLABLE | Metode pembayaran (Midtrans) |
| created_at | TIMESTAMP | | Created timestamp |
| updated_at | TIMESTAMP | | Updated timestamp |

**Relationships**:
- belongsTo: User
- hasMany: OrderItem

**Constraints**:
- ON DELETE CASCADE

**Status Flow**:
1. pending → Menunggu pembayaran
2. paid → Sudah dibayar
3. failed → Pembayaran gagal
4. completed → Order selesai
5. cancelled → Order dibatalkan

---

### 10. order_items
**Purpose**: Item-item dalam setiap order

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PK, Auto Increment | Primary key |
| order_id | BIGINT | FK, NOT NULL | Foreign key to orders |
| decoration_id | BIGINT | FK, NOT NULL | Foreign key to decorations |
| type | ENUM | DEFAULT 'custom' | Tipe pemesanan (custom, random) |
| quantity | INT | DEFAULT 1 | Jumlah item |
| price | BIGINT | NOT NULL | Harga per item (snapshot saat order) |
| created_at | TIMESTAMP | | Created timestamp |
| updated_at | TIMESTAMP | | Updated timestamp |

**Relationships**:
- belongsTo: Order, Decoration

**Constraints**:
- ON DELETE CASCADE

---

### 11. vendors
**Purpose**: Partner vendor untuk kebutuhan wedding

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PK, Auto Increment | Primary key |
| name | VARCHAR | NOT NULL | Nama vendor |
| category | ENUM | NOT NULL | Kategori (photography, videography, makeup, attire, music) |
| rating | DECIMAL(2,1) | DEFAULT 0 | Rating rata-rata (0.0 - 5.0) |
| review_count | INT | DEFAULT 0 | Jumlah review |
| image | VARCHAR | NULLABLE | Gambar profil vendor |
| description | TEXT | NULLABLE | Deskripsi vendor |
| created_at | TIMESTAMP | | Created timestamp |
| updated_at | TIMESTAMP | | Updated timestamp |

**Relationships**:
- hasMany: VendorImage

**Categories**:
- photography: Layanan fotografi
- videography: Layanan videografi
- makeup: Makeup artist
- attire: Baju pengantin & kebaya
- music: Band & musik entertainment

---

### 12. vendor_images
**Purpose**: Gallery images untuk setiap vendor

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PK, Auto Increment | Primary key |
| vendor_id | BIGINT | FK, NOT NULL | Foreign key to vendors |
| image | VARCHAR | NOT NULL | Path gambar portfolio |
| created_at | TIMESTAMP | | Created timestamp |
| updated_at | TIMESTAMP | | Updated timestamp |

**Relationships**:
- belongsTo: Vendor

**Constraints**:
- ON DELETE CASCADE

---

### 13. promotions
**Purpose**: Banner promosi dan deals

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PK, Auto Increment | Primary key |
| title | VARCHAR | NOT NULL | Judul promo |
| banner_image | VARCHAR | NOT NULL | Gambar banner promo |
| discount_percent | INT | NOT NULL | Persentase diskon |
| start_date | DATE | NOT NULL | Tanggal mulai promo |
| end_date | DATE | NOT NULL | Tanggal berakhir promo |
| created_at | TIMESTAMP | | Created timestamp |
| updated_at | TIMESTAMP | | Updated timestamp |

---

### 14. settings
**Purpose**: Konfigurasi website (key-value store)

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PK, Auto Increment | Primary key |
| key | VARCHAR | UNIQUE, NOT NULL | Key setting |
| value | TEXT | NULLABLE | Value setting |
| created_at | TIMESTAMP | | Created timestamp |
| updated_at | TIMESTAMP | | Updated timestamp |

**Default Settings**:
- company_name: Nama perusahaan
- company_tagline: Tagline
- address: Alamat lengkap
- phone: Nomor telepon
- email: Email contact
- whatsapp_link: Link WhatsApp
- social_facebook: URL Facebook
- social_instagram: URL Instagram
- social_tiktok: URL TikTok
- social_youtube: URL YouTube
- about_us: Deskripsi perusahaan
- business_hours: Jam operasional

---

### 15. faqs
**Purpose**: Frequently Asked Questions

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PK, Auto Increment | Primary key |
| question | TEXT | NOT NULL | Pertanyaan |
| answer | TEXT | NOT NULL | Jawaban |
| created_at | TIMESTAMP | | Created timestamp |
| updated_at | TIMESTAMP | | Updated timestamp |

---

### 16. events
**Purpose**: Event wedding (wedding fair, exhibition, dll)

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PK, Auto Increment | Primary key |
| title | VARCHAR | NOT NULL | Judul event |
| slug | VARCHAR | UNIQUE, NOT NULL | URL-friendly slug |
| banner_image | VARCHAR | NOT NULL | Gambar banner event |
| start_date | DATE | NOT NULL | Tanggal mulai event |
| end_date | DATE | NOT NULL | Tanggal berakhir event |
| location | VARCHAR | NOT NULL | Lokasi event |
| short_description | TEXT | NOT NULL | Deskripsi singkat (untuk card) |
| full_description | LONGTEXT | NOT NULL | Deskripsi lengkap (detail page) |
| organizer | VARCHAR | NULLABLE | Nama penyelenggara |
| created_at | TIMESTAMP | | Created timestamp |
| updated_at | TIMESTAMP | | Updated timestamp |

**Relationships**:
- hasMany: EventImage

---

### 17. event_images
**Purpose**: Gallery images untuk setiap event

| Column | Type | Attributes | Description |
|--------|------|------------|-------------|
| id | BIGINT | PK, Auto Increment | Primary key |
| event_id | BIGINT | FK, NOT NULL | Foreign key to events |
| image | VARCHAR | NOT NULL | Path gambar event |
| created_at | TIMESTAMP | | Created timestamp |
| updated_at | TIMESTAMP | | Updated timestamp |

**Relationships**:
- belongsTo: Event

**Constraints**:
- ON DELETE CASCADE

---

## Laravel Default Tables

### password_reset_tokens
Laravel default table untuk reset password functionality.

### sessions
Laravel default table untuk session management.

### cache
Laravel default table untuk cache storage.

### jobs
Laravel default table untuk queue jobs.

---

## Indexes

Untuk optimasi query, berikut index yang direkomendasikan:

```sql
-- Users
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_provider ON users(provider, provider_id);

-- Decorations
CREATE INDEX idx_decorations_category ON decorations(category);
CREATE INDEX idx_decorations_region ON decorations(region);
CREATE INDEX idx_decorations_is_deals ON decorations(is_deals);

-- Orders
CREATE INDEX idx_orders_user_id ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_order_number ON orders(order_number);

-- Reviews
CREATE INDEX idx_reviews_decoration_id ON reviews(decoration_id);
CREATE INDEX idx_reviews_user_id ON reviews(user_id);

-- Vendors
CREATE INDEX idx_vendors_category ON vendors(category);

-- Events
CREATE INDEX idx_events_start_date ON events(start_date);
CREATE INDEX idx_events_end_date ON events(end_date);
```

---

## Notes

1. Semua foreign key menggunakan `ON DELETE CASCADE` untuk menjaga data integrity
2. Price fields menggunakan BIGINT untuk menyimpan nilai dalam rupiah (tanpa desimal)
3. Rating menggunakan DECIMAL(2,1) untuk nilai 0.0 - 5.0
4. ENUM fields untuk membatasi nilai yang valid dan meningkatkan performa
5. Slug fields untuk SEO-friendly URLs
6. Timestamps (created_at, updated_at) otomatis di-handle oleh Laravel

---

## Migration Commands

```bash
# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Reset database
php artisan migrate:fresh

# Run with seeder
php artisan migrate:fresh --seed
```
