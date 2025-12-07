# Decoration Advantages & Terms - Frontend Integration Guide

## Overview

Sistem Advantages (Fitur & Keunggulan) dan Terms (Ketentuan & Syarat) untuk setiap dekorasi kini menggunakan **tabel relasi terpisah** yang dapat di-CRUD secara independen. Admin tidak perlu menulis ulang advantages/terms yang sama untuk dekorasi berbeda.

---

## Database Structure

### Decoration Advantages Table
```sql
decoration_advantages
- id (primary key)
- decoration_id (foreign key)
- title (string) - Judul keunggulan
- description (text, nullable) - Deskripsi detail
- order (integer) - Urutan tampilan
```

### Decoration Terms Table
```sql
decoration_terms
- id (primary key)
- decoration_id (foreign key)
- term (text) - Isi syarat & ketentuan
- order (integer) - Urutan tampilan
```

---

## API Endpoints

### **Admin Panel - CRUD Advantages**

#### 1. Get All Advantages for a Decoration
```http
GET /api/admin/decorations/{decorationId}/advantages
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "decoration_id": 1,
      "title": "Setup dan bongkar pasang oleh tim profesional",
      "description": "Tim berpengalaman akan handle semua",
      "order": 1,
      "created_at": "2024-12-07T10:00:00.000000Z",
      "updated_at": "2024-12-07T10:00:00.000000Z"
    },
    {
      "id": 2,
      "decoration_id": 1,
      "title": "Material berkualitas tinggi dan modern",
      "description": null,
      "order": 2,
      "created_at": "2024-12-07T10:05:00.000000Z",
      "updated_at": "2024-12-07T10:05:00.000000Z"
    }
  ]
}
```

#### 2. Create New Advantage
```http
POST /api/admin/decorations/{decorationId}/advantages
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Setup dan bongkar pasang oleh tim profesional",
  "description": "Tim berpengalaman akan handle semua setup",
  "order": 1
}
```

**Validation:**
- `title`: required, string, max 255 characters
- `description`: optional, string
- `order`: optional, integer, default 0

**Response:**
```json
{
  "success": true,
  "message": "Advantage created successfully",
  "data": {
    "id": 1,
    "decoration_id": 1,
    "title": "Setup dan bongkar pasang oleh tim profesional",
    "description": "Tim berpengalaman akan handle semua setup",
    "order": 1,
    "created_at": "2024-12-07T10:00:00.000000Z",
    "updated_at": "2024-12-07T10:00:00.000000Z"
  }
}
```

#### 3. Get Single Advantage
```http
GET /api/admin/decorations/{decorationId}/advantages/{id}
Authorization: Bearer {token}
```

#### 4. Update Advantage
```http
PUT /api/admin/decorations/{decorationId}/advantages/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "title": "Setup oleh tim profesional bersertifikat",
  "description": "Updated description",
  "order": 2
}
```

#### 5. Delete Advantage
```http
DELETE /api/admin/decorations/{decorationId}/advantages/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Advantage deleted successfully"
}
```

---

### **Admin Panel - CRUD Terms**

#### 1. Get All Terms for a Decoration
```http
GET /api/admin/decorations/{decorationId}/terms
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "decoration_id": 1,
      "term": "Booking minimal 2 minggu sebelum tanggal acara",
      "order": 1,
      "created_at": "2024-12-07T10:00:00.000000Z",
      "updated_at": "2024-12-07T10:00:00.000000Z"
    },
    {
      "id": 2,
      "decoration_id": 1,
      "term": "DP 30% dari total harga saat booking dikonfirmasi",
      "order": 2,
      "created_at": "2024-12-07T10:05:00.000000Z",
      "updated_at": "2024-12-07T10:05:00.000000Z"
    }
  ]
}
```

#### 2. Create New Term
```http
POST /api/admin/decorations/{decorationId}/terms
Authorization: Bearer {token}
Content-Type: application/json

{
  "term": "Booking minimal 2 minggu sebelum tanggal acara",
  "order": 1
}
```

**Validation:**
- `term`: required, string
- `order`: optional, integer, default 0

**Response:**
```json
{
  "success": true,
  "message": "Term created successfully",
  "data": {
    "id": 1,
    "decoration_id": 1,
    "term": "Booking minimal 2 minggu sebelum tanggal acara",
    "order": 1,
    "created_at": "2024-12-07T10:00:00.000000Z",
    "updated_at": "2024-12-07T10:00:00.000000Z"
  }
}
```

#### 3. Get Single Term
```http
GET /api/admin/decorations/{decorationId}/terms/{id}
Authorization: Bearer {token}
```

#### 4. Update Term
```http
PUT /api/admin/decorations/{decorationId}/terms/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "term": "Booking minimal 3 minggu sebelum tanggal acara",
  "order": 2
}
```

#### 5. Delete Term
```http
DELETE /api/admin/decorations/{decorationId}/terms/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "Term deleted successfully"
}
```

---

## Landing Page - Get Decoration with Advantages & Terms

### **Public Endpoint (No Auth Required)**

```http
GET /api/public/decorations/{id}
// or using slug
GET /api/public/decorations/{slug}
```

**Example:**
```http
GET /api/public/decorations/pure-elegance-9
```

**Response includes advantages and terms:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "PURE ELEGANCE 9",
    "slug": "pure-elegance-9",
    "region": "Jakarta",
    "description": "Dekorasi mewah dengan konsep modern minimalis",
    "base_price": 40000000,
    "discount_percent": 10,
    "final_price": 36000000,
    "rating": 4.8,
    "review_count": 15,
    "is_deals": true,
    "images": [
      {
        "id": 1,
        "image": "/storage/decorations/123.jpg",
        "image_url": "http://localhost:8000/storage/decorations/123.jpg"
      }
    ],
    "advantages": [
      {
        "id": 1,
        "title": "Setup dan bongkar pasang oleh tim profesional",
        "description": "Tim berpengalaman akan handle semua",
        "order": 1
      },
      {
        "id": 2,
        "title": "Material berkualitas tinggi dan modern",
        "description": null,
        "order": 2
      },
      {
        "id": 3,
        "title": "Desain dapat dikustomisasi sesuai tema acara",
        "description": null,
        "order": 3
      }
    ],
    "terms": [
      {
        "id": 1,
        "term": "Booking minimal 2 minggu sebelum tanggal acara",
        "order": 1
      },
      {
        "id": 2,
        "term": "DP 30% dari total harga saat booking dikonfirmasi",
        "order": 2
      },
      {
        "id": 3,
        "term": "Pelunasan maksimal H-3 sebelum acara",
        "order": 3
      }
    ],
    "free_items": [...]
  }
}
```

---

## Frontend Implementation

### **Landing Page - Decoration Detail**

#### Display Advantages (Fitur & Keunggulan)

```jsx
// React/Next.js Example
function DecorationAdvantages({ decoration }) {
  return (
    <section className="advantages-section">
      <h2>Fitur & Keunggulan</h2>
      <div className="advantages-list">
        {decoration.advantages.map((advantage) => (
          <div key={advantage.id} className="advantage-item">
            <span className="checkmark">✓</span>
            <div>
              <h4>{advantage.title}</h4>
              {advantage.description && (
                <p className="description">{advantage.description}</p>
              )}
            </div>
          </div>
        ))}
      </div>
    </section>
  );
}

// CSS Example
.advantages-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.advantage-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.checkmark {
  color: #22c55e;
  font-size: 20px;
  font-weight: bold;
}
```

#### Display Terms (Ketentuan & Syarat)

```jsx
function DecorationTerms({ decoration }) {
  return (
    <section className="terms-section">
      <h2>Ketentuan & Syarat</h2>
      <div className="terms-box">
        <ul className="terms-list">
          {decoration.terms.map((term) => (
            <li key={term.id}>• {term.term}</li>
          ))}
        </ul>
      </div>
    </section>
  );
}

// CSS Example
.terms-box {
  background-color: #fef3c7;
  border-radius: 8px;
  padding: 24px;
}

.terms-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.terms-list li {
  padding: 8px 0;
  color: #92400e;
}
```

---

### **Admin Panel - CRUD Interface**

#### Advantages Management

```jsx
// Admin Decoration Detail Page
function DecorationAdvantagesManager({ decorationId }) {
  const [advantages, setAdvantages] = useState([]);
  const [isAdding, setIsAdding] = useState(false);
  const [formData, setFormData] = useState({
    title: '',
    description: '',
    order: 0
  });

  // Fetch advantages
  useEffect(() => {
    fetchAdvantages();
  }, [decorationId]);

  const fetchAdvantages = async () => {
    const res = await fetch(
      `/api/admin/decorations/${decorationId}/advantages`,
      {
        headers: {
          Authorization: `Bearer ${token}`
        }
      }
    );
    const data = await res.json();
    setAdvantages(data.data);
  };

  // Create advantage
  const handleCreate = async (e) => {
    e.preventDefault();
    const res = await fetch(
      `/api/admin/decorations/${decorationId}/advantages`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`
        },
        body: JSON.stringify(formData)
      }
    );
    
    if (res.ok) {
      fetchAdvantages();
      setIsAdding(false);
      setFormData({ title: '', description: '', order: 0 });
    }
  };

  // Update advantage
  const handleUpdate = async (id, updatedData) => {
    const res = await fetch(
      `/api/admin/decorations/${decorationId}/advantages/${id}`,
      {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`
        },
        body: JSON.stringify(updatedData)
      }
    );
    
    if (res.ok) {
      fetchAdvantages();
    }
  };

  // Delete advantage
  const handleDelete = async (id) => {
    if (confirm('Hapus advantage ini?')) {
      const res = await fetch(
        `/api/admin/decorations/${decorationId}/advantages/${id}`,
        {
          method: 'DELETE',
          headers: {
            Authorization: `Bearer ${token}`
          }
        }
      );
      
      if (res.ok) {
        fetchAdvantages();
      }
    }
  };

  return (
    <div className="advantages-manager">
      <div className="header">
        <h3>Fitur & Keunggulan</h3>
        <button onClick={() => setIsAdding(true)}>
          + Tambah Advantage
        </button>
      </div>

      {/* Create Form */}
      {isAdding && (
        <form onSubmit={handleCreate} className="advantage-form">
          <input
            type="text"
            placeholder="Title"
            value={formData.title}
            onChange={(e) => setFormData({...formData, title: e.target.value})}
            required
          />
          <textarea
            placeholder="Description (optional)"
            value={formData.description}
            onChange={(e) => setFormData({...formData, description: e.target.value})}
          />
          <input
            type="number"
            placeholder="Order"
            value={formData.order}
            onChange={(e) => setFormData({...formData, order: parseInt(e.target.value)})}
          />
          <div className="form-actions">
            <button type="submit">Simpan</button>
            <button type="button" onClick={() => setIsAdding(false)}>
              Batal
            </button>
          </div>
        </form>
      )}

      {/* List */}
      <div className="advantages-list">
        {advantages.map((advantage) => (
          <div key={advantage.id} className="advantage-card">
            <div className="advantage-content">
              <h4>{advantage.title}</h4>
              {advantage.description && <p>{advantage.description}</p>}
              <span className="order">Order: {advantage.order}</span>
            </div>
            <div className="actions">
              <button onClick={() => handleEdit(advantage)}>Edit</button>
              <button onClick={() => handleDelete(advantage.id)}>Delete</button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
```

#### Terms Management

```jsx
// Similar structure for Terms
function DecorationTermsManager({ decorationId }) {
  const [terms, setTerms] = useState([]);
  const [isAdding, setIsAdding] = useState(false);
  const [formData, setFormData] = useState({
    term: '',
    order: 0
  });

  // Fetch terms
  useEffect(() => {
    fetchTerms();
  }, [decorationId]);

  const fetchTerms = async () => {
    const res = await fetch(
      `/api/admin/decorations/${decorationId}/terms`,
      {
        headers: {
          Authorization: `Bearer ${token}`
        }
      }
    );
    const data = await res.json();
    setTerms(data.data);
  };

  // Create term
  const handleCreate = async (e) => {
    e.preventDefault();
    const res = await fetch(
      `/api/admin/decorations/${decorationId}/terms`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`
        },
        body: JSON.stringify(formData)
      }
    );
    
    if (res.ok) {
      fetchTerms();
      setIsAdding(false);
      setFormData({ term: '', order: 0 });
    }
  };

  // Update & Delete similar to advantages...

  return (
    <div className="terms-manager">
      <div className="header">
        <h3>Ketentuan & Syarat</h3>
        <button onClick={() => setIsAdding(true)}>
          + Tambah Term
        </button>
      </div>

      {/* Create Form */}
      {isAdding && (
        <form onSubmit={handleCreate} className="term-form">
          <textarea
            placeholder="Ketentuan atau syarat"
            value={formData.term}
            onChange={(e) => setFormData({...formData, term: e.target.value})}
            required
          />
          <input
            type="number"
            placeholder="Order"
            value={formData.order}
            onChange={(e) => setFormData({...formData, order: parseInt(e.target.value)})}
          />
          <div className="form-actions">
            <button type="submit">Simpan</button>
            <button type="button" onClick={() => setIsAdding(false)}>
              Batal
            </button>
          </div>
        </form>
      )}

      {/* List */}
      <div className="terms-list">
        {terms.map((term) => (
          <div key={term.id} className="term-card">
            <div className="term-content">
              <p>{term.term}</p>
              <span className="order">Order: {term.order}</span>
            </div>
            <div className="actions">
              <button onClick={() => handleEdit(term)}>Edit</button>
              <button onClick={() => handleDelete(term.id)}>Delete</button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
```

---

## Admin Panel UI/UX Recommendations

### Navigation Structure
```
Dashboard
└── Decorations
    ├── List All Decorations
    └── Decoration Detail
        ├── Basic Info (Edit)
        ├── Images (Upload/Delete)
        ├── Free Items (CRUD)
        ├── **Advantages (CRUD)** ← NEW
        └── **Terms (CRUD)** ← NEW
```

### Layout Suggestion

**Decoration Detail Page:**
```
┌─────────────────────────────────────────┐
│ Decoration: PURE ELEGANCE 9             │
│ [Edit Basic Info]                       │
├─────────────────────────────────────────┤
│ Images                                  │
│ [Upload] [Delete]                       │
├─────────────────────────────────────────┤
│ Free Items                              │
│ [+ Add] [Edit] [Delete]                 │
├─────────────────────────────────────────┤
│ **Fitur & Keunggulan**           [+ Add]│
│ ┌─────────────────────────────────────┐ │
│ │ ✓ Setup oleh tim profesional       │ │
│ │   [Edit] [Delete]                   │ │
│ ├─────────────────────────────────────┤ │
│ │ ✓ Material berkualitas tinggi       │ │
│ │   [Edit] [Delete]                   │ │
│ └─────────────────────────────────────┘ │
├─────────────────────────────────────────┤
│ **Ketentuan & Syarat**           [+ Add]│
│ ┌─────────────────────────────────────┐ │
│ │ • Booking minimal 2 minggu          │ │
│ │   [Edit] [Delete]                   │ │
│ ├─────────────────────────────────────┤ │
│ │ • DP 30% saat booking               │ │
│ │   [Edit] [Delete]                   │ │
│ └─────────────────────────────────────┘ │
└─────────────────────────────────────────┘
```

---

## Best Practices

### 1. **Order Management**
- Use `order` field untuk mengatur urutan tampilan
- Nilai lebih kecil tampil lebih dulu
- Gunakan increment 10 (10, 20, 30) untuk flexibility

### 2. **Validation**
- Title maksimal 255 karakter
- Description boleh kosong
- Term harus diisi (required)

### 3. **UX Tips**
- Tampilkan advantages dengan checkmark hijau (✓)
- Tampilkan terms dengan bullet point (•)
- Gunakan background berbeda untuk terms section (kuning muda)
- Allow drag & drop untuk reorder advantages/terms

### 4. **Performance**
- Advantages dan terms sudah auto-load saat GET decoration
- No need extra API call untuk landing page
- Admin panel: lazy load advantages/terms tabs

---

## Migration & Data

Tabel sudah dibuat dengan migration. Data advantages/terms:
- Linked to specific decoration via `decoration_id`
- Ordered by `order` field ascending
- Can be reused: just change `decoration_id` to link to different decoration

---

## Error Handling

```javascript
// Example error handling
try {
  const res = await fetch('/api/admin/decorations/1/advantages', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify(formData)
  });
  
  const data = await res.json();
  
  if (!res.ok) {
    // Handle validation errors
    if (data.errors) {
      console.error('Validation errors:', data.errors);
    }
    throw new Error(data.message || 'Failed to create advantage');
  }
  
  // Success
  console.log('Advantage created:', data.data);
} catch (error) {
  console.error('Error:', error);
  alert('Terjadi kesalahan: ' + error.message);
}
```

---

## Summary

**✅ Advantages & Terms sekarang:**
- Tabel terpisah dengan relasi ke decorations
- CRUD lengkap via API
- Auto-load saat GET decoration detail
- Bisa di-reorder dengan field `order`
- Admin friendly: tambah sekali, reuse berkali-kali

**Frontend Tasks:**
1. Landing page: Display advantages & terms di decoration detail
2. Admin panel: Tambah menu CRUD untuk advantages & terms
3. UI: Checkmarks untuk advantages, bullets untuk terms
4. UX: Allow reordering, drag & drop (optional)
