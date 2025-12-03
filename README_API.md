# DWD Wedding Organizer - Backend API Documentation

## 📋 Overview

Laravel 11 REST API backend untuk sistem wedding organizer DWD (dwdecor.co.id). Backend ini menggunakan Laravel Sanctum untuk autentikasi API dan dirancang untuk bekerja dengan frontend Next.js.

## 🚀 Setup & Installation

### Prerequisites
- PHP 8.2+
- MySQL 8.0+
- Composer
- Node.js (optional, untuk Vite)

### Installation Steps

1. **Clone & Install Dependencies**
```bash
cd wo_dwd
composer install
```

2. **Environment Configuration**
```bash
# Copy .env file (sudah ada)
# Update konfigurasi database di .env:
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wo_dwd
DB_USERNAME=root
DB_PASSWORD=

# Frontend URL untuk CORS
FRONTEND_URL=http://localhost:3000
SANCTUM_STATEFUL_DOMAINS=localhost:3000
```

3. **Generate Application Key** (jika belum)
```bash
php artisan key:generate
```

4. **Run Migrations & Seeders**
```bash
php artisan migrate
php artisan db:seed
```

5. **Start Development Server**
```bash
php artisan serve
# API akan running di: http://127.0.0.1:8000
```

## 🗄️ Database Schema

### Users Table
- `id` - Primary key
- `first_name` - Nama depan user
- `last_name` - Nama belakang user
- `email` - Email (unique)
- `phone` - Nomor telepon
- `password` - Hashed password
- `role` - Enum: 'admin', 'customer'
- `provider` - OAuth provider (google, facebook, null)
- `provider_id` - OAuth provider ID
- `email_verified_at` - Timestamp verifikasi email

### Master Data Tables
- `decoration_categories` - Kategori dekorasi
- `decorations` - Data dekorasi/produk
- `packages` - Paket dekorasi
- `package_items` - Item dalam paket
- `events` - Event/acara
- `galleries` - Galeri foto
- `testimonials` - Testimoni customer
- `settings` - Pengaturan aplikasi

### Transaction Tables
- `orders` - Order customer
- `order_items` - Item dalam order
- `payments` - Pembayaran
- `consultations` - Konsultasi customer
- `reviews` - Review customer

## 🔐 Authentication

### Sanctum Token Authentication

API menggunakan Laravel Sanctum untuk autentikasi. Ada 2 metode autentikasi:

1. **Cookie-based (Recommended untuk SPA)**
   - Untuk Next.js yang berjalan di domain yang sama
   - Menggunakan CSRF token

2. **Token-based**
   - Untuk mobile apps atau third-party
   - Menggunakan Bearer token

### Authentication Flow (Cookie-based for Next.js)

#### 1. Get CSRF Cookie
```bash
GET http://localhost:8000/sanctum/csrf-cookie
```
**Response:** Sets CSRF cookie

#### 2. Register User
```bash
POST http://localhost:8000/api/auth/register
Content-Type: application/json

{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "081234567890",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "role": "customer"
  },
  "token": "1|abc123..."
}
```

#### 3. Login
```bash
POST http://localhost:8000/api/auth/login
Content-Type: application/json

{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:**
```json
{
  "user": {
    "id": 1,
    "first_name": "John",
    "last_name": "Doe",
    "email": "john@example.com",
    "role": "customer"
  },
  "token": "2|xyz789..."
}
```

#### 4. Get Authenticated User
```bash
GET http://localhost:8000/api/auth/user
Authorization: Bearer {token}
```

**Response:**
```json
{
  "id": 1,
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "081234567890",
  "role": "customer",
  "email_verified_at": "2025-12-02T10:00:00.000000Z"
}
```

#### 5. Logout
```bash
POST http://localhost:8000/api/auth/logout
Authorization: Bearer {token}
```

**Response:**
```json
{
  "message": "Logged out successfully"
}
```

## 📍 API Endpoints

### Base URL
```
http://localhost:8000/api
```

### Public Endpoints

#### Health Check
```bash
GET /api/health
```
**Response:**
```json
{
  "status": "ok",
  "timestamp": "2025-12-02T10:00:00.000000Z"
}
```

### Authentication Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/auth/register` | Register user baru | ❌ |
| POST | `/auth/login` | Login user | ❌ |
| POST | `/auth/logout` | Logout user | ✅ |
| GET | `/auth/user` | Get data user yang login | ✅ |

### Protected Endpoints (Requires Authentication)

#### Customer Routes
Prefix: `/api/customer`

**TODO:** Implementasi endpoints berikut:
- `GET /decorations` - List dekorasi
- `GET /decorations/{id}` - Detail dekorasi
- `GET /packages` - List paket
- `GET /packages/{id}` - Detail paket
- `POST /orders` - Create order
- `GET /orders` - List order user
- `GET /orders/{id}` - Detail order
- `POST /consultations` - Request konsultasi
- `POST /reviews` - Submit review

#### Admin Routes
Prefix: `/api/admin`
Requires: `role:admin` middleware

**TODO:** Implementasi endpoints berikut:
- `GET /dashboard` - Dashboard data
- CRUD untuk semua master data:
  - Decorations
  - Packages
  - Events
  - Galleries
  - Testimonials
  - Settings
- Manajemen orders & payments
- Manajemen consultations

## 🔧 Development

### Seeded Users

Setelah run `php artisan db:seed`, tersedia 2 user untuk testing:

**Admin:**
```
Email: admin@dwdecor.co.id
Password: Admin123!
Role: admin
```

**Customer:**
```
Email: customer@example.com
Password: Customer123!
Role: customer
```

### Testing API dengan Postman/Thunder Client

1. **Import Environment Variables:**
```json
{
  "base_url": "http://localhost:8000/api",
  "token": ""
}
```

2. **Login & Save Token:**
   - Login dengan endpoint `/auth/login`
   - Copy token dari response
   - Set di environment variable atau Authorization header

3. **Make Authenticated Requests:**
```
Authorization: Bearer {your_token_here}
```

### CORS Configuration

CORS sudah dikonfigurasi di `config/cors.php`:
- Allowed origins: Frontend URL dari `.env` (default: `http://localhost:3000`)
- Allowed methods: Semua
- Supports credentials: Yes

## 🛠️ Next.js Integration

### Axios Setup Example

```javascript
// lib/axios.js
import axios from 'axios';

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000/api',
  withCredentials: true,
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

export default api;
```

### Usage Example

```javascript
// pages/api/auth/login.js
import api from '@/lib/axios';

export async function login(email, password) {
  // Get CSRF cookie first
  await api.get('http://localhost:8000/sanctum/csrf-cookie');
  
  // Login
  const response = await api.post('/auth/login', {
    email,
    password
  });
  
  // Save token
  localStorage.setItem('token', response.data.token);
  
  return response.data;
}

export async function getUser() {
  const response = await api.get('/auth/user');
  return response.data;
}
```

## 📦 Role-Based Access Control

Aplikasi menggunakan role-based access dengan 2 roles:

### Customer Role
- Akses: Browse decorations & packages
- Actions: Order, consultation, review
- Cannot access admin routes

### Admin Role  
- Akses: Full admin dashboard
- Actions: CRUD semua data, manage orders, manage users
- Middleware: `role:admin`

### Checking Role in API

```php
// In controller
if (auth()->user()->role !== 'admin') {
    return response()->json(['message' => 'Unauthorized'], 403);
}

// In route (using middleware)
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Admin only routes
});
```

## 🐛 Troubleshooting

### CORS Errors
- Pastikan `FRONTEND_URL` di `.env` sesuai dengan Next.js URL
- Check `SANCTUM_STATEFUL_DOMAINS` sudah include domain frontend
- Clear browser cache & cookies

### 401 Unauthorized
- Token expired atau invalid
- User belum login
- Check Authorization header format: `Bearer {token}`

### 403 Forbidden
- User tidak punya permission (role mismatch)
- Check user role di database

### 419 CSRF Token Mismatch
- Get CSRF cookie terlebih dahulu dari `/sanctum/csrf-cookie`
- Pastikan `withCredentials: true` di axios config

## 📝 TODO: Development Roadmap

### Phase 1: Customer API ✅
- [x] Authentication (register, login, logout)
- [ ] Decoration listing & detail
- [ ] Package listing & detail
- [ ] Shopping cart
- [ ] Order creation
- [ ] Payment integration
- [ ] Consultation request
- [ ] Review system

### Phase 2: Admin API ✅
- [ ] Dashboard statistics
- [ ] CRUD Decorations
- [ ] CRUD Packages
- [ ] CRUD Events
- [ ] CRUD Galleries
- [ ] Order management
- [ ] Payment verification
- [ ] User management
- [ ] Settings management

### Phase 3: Advanced Features
- [ ] File upload (images)
- [ ] Email notifications
- [ ] WhatsApp integration
- [ ] Payment gateway (Midtrans)
- [ ] Real-time notifications
- [ ] Export reports (PDF/Excel)

## 📞 Support

Untuk pertanyaan atau issue, silakan buat issue di repository atau kontak developer.

---

**Last Updated:** December 2, 2025  
**Laravel Version:** 11.x  
**PHP Version:** 8.2+
