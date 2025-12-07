# Decoration FAQs - Frontend Integration Guide

## Overview

Sistem FAQ (Frequently Asked Questions) untuk setiap dekorasi sekarang menggunakan **tabel relasi** yang terhubung dengan `decoration_id`. Admin dapat menambah, edit, dan hapus FAQ untuk setiap dekorasi secara terpisah.

---

## Database Structure

### FAQs Table
```sql
faqs
- id (primary key)
- decoration_id (foreign key)
- question (text) - Pertanyaan FAQ
- answer (text) - Jawaban FAQ
- order (integer) - Urutan tampilan
- created_at
- updated_at
```

---

## API Endpoints

### **Admin Panel - CRUD FAQs**

#### 1. Get All FAQs for a Decoration
```http
GET /api/admin/decorations/{decorationId}/faqs
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
      "question": "Apakah harga sudah termasuk setup dan bongkar?",
      "answer": "Ya, harga sudah termasuk setup 3-4 jam sebelum acara dimulai dan bongkar pasang setelah acara selesai (maksimal +3 jam).",
      "order": 1,
      "created_at": "2024-12-07T10:00:00.000000Z",
      "updated_at": "2024-12-07T10:00:00.000000Z"
    },
    {
      "id": 2,
      "decoration_id": 1,
      "question": "Berapa lama waktu setup dekorasi?",
      "answer": "Waktu setup berkisar 3-4 jam sebelum acara dimulai, tergantung kompleksitas desain.",
      "order": 2,
      "created_at": "2024-12-07T10:05:00.000000Z",
      "updated_at": "2024-12-07T10:05:00.000000Z"
    }
  ]
}
```

#### 2. Create New FAQ
```http
POST /api/admin/decorations/{decorationId}/faqs
Authorization: Bearer {token}
Content-Type: application/json

{
  "question": "Apakah bisa custom tema dekorasi?",
  "answer": "Ya, kami menerima custom tema sesuai keinginan Anda dengan konsultasi gratis bersama wedding decorator.",
  "order": 1
}
```

**Validation:**
- `question`: required, string
- `answer`: required, string
- `order`: optional, integer, default 0

**Response:**
```json
{
  "success": true,
  "message": "FAQ created successfully",
  "data": {
    "id": 3,
    "decoration_id": 1,
    "question": "Apakah bisa custom tema dekorasi?",
    "answer": "Ya, kami menerima custom tema sesuai keinginan Anda dengan konsultasi gratis bersama wedding decorator.",
    "order": 1,
    "created_at": "2024-12-07T11:00:00.000000Z",
    "updated_at": "2024-12-07T11:00:00.000000Z"
  }
}
```

#### 3. Get Single FAQ
```http
GET /api/admin/decorations/{decorationId}/faqs/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "decoration_id": 1,
    "question": "Apakah harga sudah termasuk setup dan bongkar?",
    "answer": "Ya, harga sudah termasuk setup dan bongkar.",
    "order": 1,
    "created_at": "2024-12-07T10:00:00.000000Z",
    "updated_at": "2024-12-07T10:00:00.000000Z"
  }
}
```

#### 4. Update FAQ
```http
PUT /api/admin/decorations/{decorationId}/faqs/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "question": "Apakah harga sudah termasuk setup, bongkar dan transport?",
  "answer": "Ya, harga sudah all-in termasuk setup, bongkar dan transport dalam kota.",
  "order": 1
}
```

**Response:**
```json
{
  "success": true,
  "message": "FAQ updated successfully",
  "data": {
    "id": 1,
    "decoration_id": 1,
    "question": "Apakah harga sudah termasuk setup, bongkar dan transport?",
    "answer": "Ya, harga sudah all-in termasuk setup, bongkar dan transport dalam kota.",
    "order": 1,
    "created_at": "2024-12-07T10:00:00.000000Z",
    "updated_at": "2024-12-07T11:30:00.000000Z"
  }
}
```

#### 5. Delete FAQ
```http
DELETE /api/admin/decorations/{decorationId}/faqs/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "message": "FAQ deleted successfully"
}
```

---

## Landing Page - Get Decoration with FAQs

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

**Response includes FAQs:**
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
    "images": [...],
    "advantages": [...],
    "terms": [...],
    "faqs": [
      {
        "id": 1,
        "question": "Apakah harga sudah termasuk setup dan bongkar?",
        "answer": "Ya, harga sudah termasuk setup 3-4 jam sebelum acara dimulai dan bongkar pasang setelah acara selesai (maksimal +3 jam).",
        "order": 1
      },
      {
        "id": 2,
        "question": "Berapa lama waktu setup dekorasi?",
        "answer": "Waktu setup berkisar 3-4 jam sebelum acara dimulai, tergantung kompleksitas desain.",
        "order": 2
      },
      {
        "id": 3,
        "question": "Apakah bisa custom tema dekorasi?",
        "answer": "Ya, kami menerima custom tema sesuai keinginan Anda dengan konsultasi gratis.",
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

#### Display FAQs Section

```jsx
// React/Next.js Example
import { useState } from 'react';

function DecorationFAQs({ decoration }) {
  const [openIndex, setOpenIndex] = useState(null);

  const toggleFAQ = (index) => {
    setOpenIndex(openIndex === index ? null : index);
  };

  return (
    <section className="faqs-section">
      <h2>Pertanyaan yang Sering Diajukan (FAQ)</h2>
      <div className="faqs-list">
        {decoration.faqs.map((faq, index) => (
          <div 
            key={faq.id} 
            className={`faq-item ${openIndex === index ? 'active' : ''}`}
          >
            <button 
              className="faq-question"
              onClick={() => toggleFAQ(index)}
            >
              <span>{faq.question}</span>
              <span className="icon">{openIndex === index ? '−' : '+'}</span>
            </button>
            {openIndex === index && (
              <div className="faq-answer">
                <p>{faq.answer}</p>
              </div>
            )}
          </div>
        ))}
      </div>
    </section>
  );
}

// CSS Example (Accordion Style)
.faqs-section {
  margin: 40px 0;
}

.faqs-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.faq-item {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  overflow: hidden;
}

.faq-question {
  width: 100%;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  background: #f9fafb;
  border: none;
  cursor: pointer;
  text-align: left;
  font-size: 16px;
  font-weight: 600;
  transition: background 0.2s;
}

.faq-question:hover {
  background: #f3f4f6;
}

.faq-item.active .faq-question {
  background: #e5e7eb;
}

.faq-question .icon {
  font-size: 24px;
  color: #6b7280;
}

.faq-answer {
  padding: 16px 20px;
  background: white;
  animation: slideDown 0.3s ease;
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
```

---

### **Admin Panel - CRUD Interface**

#### FAQs Management Component

```jsx
// Admin Decoration Detail Page
function DecorationFAQsManager({ decorationId }) {
  const [faqs, setFaqs] = useState([]);
  const [isAdding, setIsAdding] = useState(false);
  const [editingId, setEditingId] = useState(null);
  const [formData, setFormData] = useState({
    question: '',
    answer: '',
    order: 0
  });

  // Fetch FAQs
  useEffect(() => {
    fetchFAQs();
  }, [decorationId]);

  const fetchFAQs = async () => {
    const res = await fetch(
      `/api/admin/decorations/${decorationId}/faqs`,
      {
        headers: {
          Authorization: `Bearer ${token}`
        }
      }
    );
    const data = await res.json();
    setFaqs(data.data);
  };

  // Create FAQ
  const handleCreate = async (e) => {
    e.preventDefault();
    const res = await fetch(
      `/api/admin/decorations/${decorationId}/faqs`,
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
      fetchFAQs();
      setIsAdding(false);
      setFormData({ question: '', answer: '', order: 0 });
      alert('FAQ berhasil ditambahkan!');
    }
  };

  // Update FAQ
  const handleUpdate = async (e) => {
    e.preventDefault();
    const res = await fetch(
      `/api/admin/decorations/${decorationId}/faqs/${editingId}`,
      {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`
        },
        body: JSON.stringify(formData)
      }
    );
    
    if (res.ok) {
      fetchFAQs();
      setEditingId(null);
      setFormData({ question: '', answer: '', order: 0 });
      alert('FAQ berhasil diupdate!');
    }
  };

  // Delete FAQ
  const handleDelete = async (id) => {
    if (confirm('Hapus FAQ ini?')) {
      const res = await fetch(
        `/api/admin/decorations/${decorationId}/faqs/${id}`,
        {
          method: 'DELETE',
          headers: {
            Authorization: `Bearer ${token}`
          }
        }
      );
      
      if (res.ok) {
        fetchFAQs();
        alert('FAQ berhasil dihapus!');
      }
    }
  };

  // Edit handler
  const handleEdit = (faq) => {
    setEditingId(faq.id);
    setFormData({
      question: faq.question,
      answer: faq.answer,
      order: faq.order
    });
    setIsAdding(false);
  };

  // Cancel handler
  const handleCancel = () => {
    setIsAdding(false);
    setEditingId(null);
    setFormData({ question: '', answer: '', order: 0 });
  };

  return (
    <div className="faqs-manager">
      <div className="header">
        <h3>FAQ - Pertanyaan yang Sering Diajukan</h3>
        {!isAdding && !editingId && (
          <button onClick={() => setIsAdding(true)} className="btn-primary">
            + Tambah FAQ
          </button>
        )}
      </div>

      {/* Create/Edit Form */}
      {(isAdding || editingId) && (
        <form 
          onSubmit={editingId ? handleUpdate : handleCreate} 
          className="faq-form"
        >
          <div className="form-group">
            <label>Pertanyaan *</label>
            <input
              type="text"
              placeholder="Contoh: Apakah harga sudah termasuk setup?"
              value={formData.question}
              onChange={(e) => setFormData({...formData, question: e.target.value})}
              required
            />
          </div>
          
          <div className="form-group">
            <label>Jawaban *</label>
            <textarea
              rows="4"
              placeholder="Jawaban detail untuk pertanyaan di atas"
              value={formData.answer}
              onChange={(e) => setFormData({...formData, answer: e.target.value})}
              required
            />
          </div>
          
          <div className="form-group">
            <label>Urutan</label>
            <input
              type="number"
              placeholder="0"
              value={formData.order}
              onChange={(e) => setFormData({...formData, order: parseInt(e.target.value)})}
            />
            <small className="help-text">
              Nilai lebih kecil akan tampil lebih dulu
            </small>
          </div>
          
          <div className="form-actions">
            <button type="submit" className="btn-success">
              {editingId ? 'Update FAQ' : 'Simpan FAQ'}
            </button>
            <button type="button" onClick={handleCancel} className="btn-secondary">
              Batal
            </button>
          </div>
        </form>
      )}

      {/* FAQs List */}
      <div className="faqs-list-admin">
        {faqs.length === 0 ? (
          <p className="empty-state">Belum ada FAQ untuk dekorasi ini.</p>
        ) : (
          faqs.map((faq) => (
            <div key={faq.id} className="faq-card">
              <div className="faq-content">
                <div className="faq-header">
                  <h4 className="question">Q: {faq.question}</h4>
                  <span className="order-badge">Order: {faq.order}</span>
                </div>
                <p className="answer">A: {faq.answer}</p>
              </div>
              <div className="actions">
                <button 
                  onClick={() => handleEdit(faq)}
                  className="btn-edit"
                >
                  Edit
                </button>
                <button 
                  onClick={() => handleDelete(faq.id)}
                  className="btn-delete"
                >
                  Delete
                </button>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
}

// CSS Example
.faqs-manager {
  background: white;
  border-radius: 8px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
}

.faq-form {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 20px;
  margin-bottom: 24px;
}

.form-group {
  margin-bottom: 16px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #374151;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 14px;
}

.help-text {
  display: block;
  margin-top: 4px;
  font-size: 12px;
  color: #6b7280;
}

.form-actions {
  display: flex;
  gap: 12px;
  margin-top: 20px;
}

.btn-primary, .btn-success, .btn-secondary {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-success {
  background: #22c55e;
  color: white;
}

.btn-secondary {
  background: #e5e7eb;
  color: #374151;
}

.faqs-list-admin {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.faq-card {
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 16px;
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
}

.faq-content {
  flex: 1;
}

.faq-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.question {
  font-size: 16px;
  font-weight: 600;
  color: #1f2937;
  margin: 0;
}

.order-badge {
  background: #dbeafe;
  color: #1e40af;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
}

.answer {
  color: #6b7280;
  margin: 0;
  line-height: 1.6;
}

.actions {
  display: flex;
  gap: 8px;
}

.btn-edit, .btn-delete {
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-edit {
  background: #fef3c7;
  color: #92400e;
}

.btn-delete {
  background: #fee2e2;
  color: #991b1b;
}

.empty-state {
  text-align: center;
  color: #9ca3af;
  padding: 40px 20px;
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
        ├── Advantages (CRUD)
        ├── Terms (CRUD)
        └── **FAQs (CRUD)** ← NEW
```

### Layout in Decoration Detail Page

```
┌─────────────────────────────────────────┐
│ **FAQ - Pertanyaan yang Sering Diajukan**│
│                              [+ Tambah]  │
├─────────────────────────────────────────┤
│ ┌─────────────────────────────────────┐ │
│ │ Q: Apakah harga sudah termasuk      │ │
│ │    setup dan bongkar?               │ │
│ │ A: Ya, harga sudah all-in...        │ │
│ │                 Order: 1            │ │
│ │                 [Edit] [Delete]     │ │
│ ├─────────────────────────────────────┤ │
│ │ Q: Berapa lama waktu setup?         │ │
│ │ A: Waktu setup berkisar 3-4 jam...  │ │
│ │                 Order: 2            │ │
│ │                 [Edit] [Delete]     │ │
│ └─────────────────────────────────────┘ │
└─────────────────────────────────────────┘
```

---

## Best Practices

### 1. **FAQ Writing Tips**
- Question: Tulis dari perspektif customer (gunakan "Apakah...", "Berapa...", "Bagaimana...")
- Answer: Jawab langsung, jelas, dan singkat (max 2-3 kalimat)
- Order: FAQ paling penting/umum diberi order terkecil

### 2. **Common FAQ Topics**
- Harga & Payment (DP, pelunasan, metode bayar)
- Setup & Logistics (waktu setup, transport, bongkar)
- Customization (custom tema, warna, ukuran)
- Booking (minimal booking, reschedule, pembatalan)
- Service (konsultasi, garansi, komplain)

### 3. **UX Guidelines**
- Gunakan accordion untuk save space
- Highlight keyword dalam answer (bold/color)
- Max 5-8 FAQ per decoration (paling relevan)
- Mobile-friendly: touchable area min 44x44px

### 4. **Performance**
- FAQs auto-load dengan decoration detail (no extra API call)
- Admin: lazy load FAQ tab jika terlalu banyak data
- Use pagination jika FAQ > 20 items

---

## Example FAQ Content

```javascript
const exampleFAQs = [
  {
    question: "Apakah harga sudah termasuk setup dan bongkar?",
    answer: "Ya, harga sudah termasuk setup 3-4 jam sebelum acara dimulai dan bongkar pasang setelah acara selesai (maksimal +3 jam).",
    order: 1
  },
  {
    question: "Berapa minimal booking sebelum hari H?",
    answer: "Booking minimal 2 minggu sebelum tanggal acara untuk memastikan ketersediaan tim dan material.",
    order: 2
  },
  {
    question: "Apakah bisa custom tema dan warna dekorasi?",
    answer: "Ya, kami menerima custom tema sesuai keinginan dengan konsultasi gratis bersama wedding decorator kami.",
    order: 3
  },
  {
    question: "Bagaimana sistem pembayaran?",
    answer: "DP 30% saat booking dikonfirmasi, pelunasan maksimal H-3 sebelum acara. Kami terima transfer bank dan e-wallet.",
    order: 4
  },
  {
    question: "Apakah ada biaya transport?",
    answer: "Untuk area Jakarta dan sekitar sudah termasuk dalam harga. Area luar Jakarta dikenakan biaya tambahan sesuai jarak.",
    order: 5
  }
];
```

---

## Error Handling

```javascript
// Example error handling
try {
  const res = await fetch(
    `/api/admin/decorations/${decorationId}/faqs`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`
      },
      body: JSON.stringify(formData)
    }
  );
  
  const data = await res.json();
  
  if (!res.ok) {
    // Handle validation errors
    if (data.errors) {
      const errorMessages = Object.values(data.errors).flat().join('\n');
      alert('Validasi error:\n' + errorMessages);
      return;
    }
    throw new Error(data.message || 'Failed to create FAQ');
  }
  
  // Success
  alert('FAQ berhasil ditambahkan!');
  fetchFAQs();
} catch (error) {
  console.error('Error:', error);
  alert('Terjadi kesalahan: ' + error.message);
}
```

---

## Summary

**✅ FAQs sekarang:**
- Terhubung dengan decoration via `decoration_id`
- CRUD lengkap via API admin
- Auto-load di decoration detail (landing page & admin)
- Accordion UI untuk landing page
- Order management untuk urutan tampilan

**Frontend Tasks:**
1. Landing page: Tampilkan FAQs dengan accordion di decoration detail
2. Admin panel: Tambah tab/section FAQ dengan CRUD interface
3. UI: Accordion style dengan + / - icon
4. UX: Mobile-friendly, easy to read
5. Validation: Required fields untuk question & answer
