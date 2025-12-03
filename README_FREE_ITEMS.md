# Decoration Free Items - Complete Guide

## 📦 Konsep Free Items

**Free Items** adalah item/layanan **GRATIS** yang didapat customer saat memesan decoration tertentu. Bukan kategori decoration, tapi **bonus/hadiah** yang sudah termasuk dalam paket decoration.

### Contoh Free Items:
- 🎥 **Cinematic Video** - Video sinematik pernikahan profesional
- 📸 **Foto & Video** - Dokumentasi foto dan video seharian penuh
- 💄 **Makeup Artist** - Makeup profesional untuk pengantin
- 🎵 **Live Music** - Hiburan musik live
- 🍰 **Wedding Cake** - Kue pengantin 3 tingkat
- 🚗 **Mobil Pengantin** - Sewa mobil mewah untuk pengantin
- 🎤 **MC Professional** - Pembawa acara berpengalaman
- 📝 **Wedding Planner** - Konsultasi perencana pernikahan

## 🏗️ Database Structure

### Tables
1. **decorations** - Produk dekorasi utama
2. **decoration_free_items** - Item gratis per decoration

### Relationships
```
decorations (1) → (many) decoration_free_items
```

### decoration_free_items Schema
```sql
CREATE TABLE decoration_free_items (
    id BIGINT PRIMARY KEY,
    decoration_id BIGINT,              -- FK ke decorations
    item_name VARCHAR(255) NOT NULL,   -- Nama item gratis
    description TEXT,                  -- Deskripsi detail item
    quantity INT DEFAULT 1,            -- Jumlah item (1 video, 2 makeup artist, dll)
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (decoration_id) REFERENCES decorations(id) ON DELETE CASCADE
);
```

## 🔌 API Endpoints

### Base URL
```
/api/admin/decorations/{decorationId}/free-items
```

### 1. List Free Items
**GET** `/api/admin/decorations/{decorationId}/free-items`

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "decoration_id": 1,
      "item_name": "Cinematic Video",
      "description": "Professional HD cinematic wedding video with drone coverage",
      "quantity": 1,
      "created_at": "2025-12-03T10:00:00Z",
      "updated_at": "2025-12-03T10:00:00Z"
    },
    {
      "id": 2,
      "decoration_id": 1,
      "item_name": "Foto & Video",
      "description": "Full day photo and video documentation by professional photographer",
      "quantity": 1,
      "created_at": "2025-12-03T10:00:00Z",
      "updated_at": "2025-12-03T10:00:00Z"
    },
    {
      "id": 3,
      "decoration_id": 1,
      "item_name": "Makeup Artist",
      "description": "Professional makeup for bride and groom including pre-wedding makeup trial",
      "quantity": 2,
      "created_at": "2025-12-03T10:00:00Z",
      "updated_at": "2025-12-03T10:00:00Z"
    }
  ]
}
```

---

### 2. Add Free Item
**POST** `/api/admin/decorations/{decorationId}/free-items`

**Headers:**
```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "item_name": "Live Music Band",
  "description": "Professional live band performance for 3 hours during wedding reception",
  "quantity": 1
}
```

**Validation Rules:**
- `item_name`: required, string, max 255 characters
- `description`: optional, string
- `quantity`: required, integer, min 1

**Response:**
```json
{
  "success": true,
  "message": "Free item created successfully",
  "data": {
    "id": 4,
    "decoration_id": 1,
    "item_name": "Live Music Band",
    "description": "Professional live band performance for 3 hours during wedding reception",
    "quantity": 1,
    "created_at": "2025-12-03T11:30:00Z",
    "updated_at": "2025-12-03T11:30:00Z"
  }
}
```

---

### 3. Get Single Free Item
**GET** `/api/admin/decorations/{decorationId}/free-items/{id}`

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "decoration_id": 1,
    "item_name": "Cinematic Video",
    "description": "Professional HD cinematic wedding video with drone coverage",
    "quantity": 1,
    "created_at": "2025-12-03T10:00:00Z",
    "updated_at": "2025-12-03T10:00:00Z"
  }
}
```

---

### 4. Update Free Item
**PUT** `/api/admin/decorations/{decorationId}/free-items/{id}`

**Headers:**
```
Authorization: Bearer {admin_token}
Content-Type: application/json
```

**Request Body:**
```json
{
  "item_name": "Cinematic Video 4K",
  "description": "Professional 4K cinematic wedding video with drone coverage and same-day edit",
  "quantity": 1
}
```

**Validation Rules:**
- `item_name`: sometimes required, string, max 255 characters
- `description`: optional, string
- `quantity`: sometimes required, integer, min 1

**Response:**
```json
{
  "success": true,
  "message": "Free item updated successfully",
  "data": {
    "id": 1,
    "decoration_id": 1,
    "item_name": "Cinematic Video 4K",
    "description": "Professional 4K cinematic wedding video with drone coverage and same-day edit",
    "quantity": 1,
    "created_at": "2025-12-03T10:00:00Z",
    "updated_at": "2025-12-03T12:00:00Z"
  }
}
```

---

### 5. Delete Free Item
**DELETE** `/api/admin/decorations/{decorationId}/free-items/{id}`

**Headers:**
```
Authorization: Bearer {admin_token}
```

**Response:**
```json
{
  "success": true,
  "message": "Free item deleted successfully"
}
```

---

## 💻 Frontend Integration

### React + TypeScript Example

#### Type Definitions
```typescript
interface FreeItem {
  id: number;
  decoration_id: number;
  item_name: string;
  description: string;
  quantity: number;
  created_at: string;
  updated_at: string;
}

interface FreeItemForm {
  item_name: string;
  description?: string;
  quantity: number;
}

interface Decoration {
  id: number;
  name: string;
  slug: string;
  region: string;
  description: string;
  base_price: number;
  final_price: number;
  images: DecorationImage[];
  freeItems: FreeItem[];  // ← Included in decoration response
}
```

#### API Service (Axios)
```typescript
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000/api',
  headers: {
    'Content-Type': 'application/json',
  }
});

// Add auth token to all requests
api.interceptors.request.use(config => {
  const token = localStorage.getItem('admin_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Free Items Service
export const freeItemsService = {
  // List all free items for decoration
  list: (decorationId: number) =>
    api.get<{ success: boolean; data: FreeItem[] }>(
      `/admin/decorations/${decorationId}/free-items`
    ),

  // Add new free item
  create: (decorationId: number, data: FreeItemForm) =>
    api.post<{ success: boolean; message: string; data: FreeItem }>(
      `/admin/decorations/${decorationId}/free-items`,
      data
    ),

  // Get single free item
  show: (decorationId: number, id: number) =>
    api.get<{ success: boolean; data: FreeItem }>(
      `/admin/decorations/${decorationId}/free-items/${id}`
    ),

  // Update free item
  update: (decorationId: number, id: number, data: Partial<FreeItemForm>) =>
    api.put<{ success: boolean; message: string; data: FreeItem }>(
      `/admin/decorations/${decorationId}/free-items/${id}`,
      data
    ),

  // Delete free item
  delete: (decorationId: number, id: number) =>
    api.delete<{ success: boolean; message: string }>(
      `/admin/decorations/${decorationId}/free-items/${id}`
    ),
};
```

#### React Component Example
```tsx
import React, { useState, useEffect } from 'react';
import { freeItemsService } from './api/services';

interface Props {
  decorationId: number;
}

export const FreeItemsManager: React.FC<Props> = ({ decorationId }) => {
  const [freeItems, setFreeItems] = useState<FreeItem[]>([]);
  const [loading, setLoading] = useState(false);
  const [formData, setFormData] = useState<FreeItemForm>({
    item_name: '',
    description: '',
    quantity: 1,
  });

  // Load free items
  useEffect(() => {
    loadFreeItems();
  }, [decorationId]);

  const loadFreeItems = async () => {
    try {
      setLoading(true);
      const { data } = await freeItemsService.list(decorationId);
      setFreeItems(data.data);
    } catch (error) {
      console.error('Failed to load free items:', error);
    } finally {
      setLoading(false);
    }
  };

  // Add new free item
  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await freeItemsService.create(decorationId, formData);
      // Reset form
      setFormData({ item_name: '', description: '', quantity: 1 });
      // Reload list
      loadFreeItems();
      alert('Free item added successfully!');
    } catch (error) {
      console.error('Failed to add free item:', error);
      alert('Failed to add free item');
    }
  };

  // Delete free item
  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this free item?')) return;
    
    try {
      await freeItemsService.delete(decorationId, id);
      loadFreeItems();
      alert('Free item deleted successfully!');
    } catch (error) {
      console.error('Failed to delete free item:', error);
      alert('Failed to delete free item');
    }
  };

  return (
    <div className="free-items-manager">
      <h3>Free Items Management</h3>
      
      {/* Add Form */}
      <form onSubmit={handleSubmit} className="mb-4">
        <div className="form-group">
          <label>Item Name*</label>
          <input
            type="text"
            className="form-control"
            placeholder="e.g., Cinematic Video, Makeup Artist"
            value={formData.item_name}
            onChange={e => setFormData({ ...formData, item_name: e.target.value })}
            required
            maxLength={255}
          />
        </div>
        
        <div className="form-group">
          <label>Description</label>
          <textarea
            className="form-control"
            rows={3}
            placeholder="Detail description of the free item"
            value={formData.description}
            onChange={e => setFormData({ ...formData, description: e.target.value })}
          />
        </div>
        
        <div className="form-group">
          <label>Quantity*</label>
          <input
            type="number"
            className="form-control"
            min={1}
            value={formData.quantity}
            onChange={e => setFormData({ ...formData, quantity: parseInt(e.target.value) })}
            required
          />
        </div>
        
        <button type="submit" className="btn btn-primary">
          Add Free Item
        </button>
      </form>

      {/* List */}
      {loading ? (
        <p>Loading...</p>
      ) : (
        <div className="free-items-list">
          {freeItems.length === 0 ? (
            <p>No free items yet. Add your first one!</p>
          ) : (
            <table className="table">
              <thead>
                <tr>
                  <th>Item Name</th>
                  <th>Description</th>
                  <th>Qty</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {freeItems.map(item => (
                  <tr key={item.id}>
                    <td><strong>{item.item_name}</strong></td>
                    <td>{item.description}</td>
                    <td>{item.quantity}</td>
                    <td>
                      <button 
                        className="btn btn-sm btn-danger"
                        onClick={() => handleDelete(item.id)}
                      >
                        Delete
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      )}
    </div>
  );
};
```

---

## 🎯 Use Cases

### Admin Panel
1. **Saat membuat decoration baru:**
   - Admin buat decoration dulu (POST /decorations)
   - Lalu tambahkan free items satu per satu (POST /decorations/{id}/free-items)

2. **Saat edit decoration:**
   - Edit data decoration (PUT /decorations/{id})
   - Manage free items secara terpisah (CRUD di /free-items)

3. **Saat hapus decoration:**
   - Free items akan terhapus otomatis (CASCADE DELETE)

### Customer Website
1. **Halaman detail decoration:**
   - Tampilkan decoration dengan `freeItems` array
   - Render badge/tag untuk setiap free item
   - Contoh: "✓ Gratis Cinematic Video", "✓ Gratis Makeup Artist (2 orang)"

2. **Halaman listing decoration:**
   - Bisa filter/sort by jumlah free items terbanyak
   - Tampilkan preview "Bonus: 5 free items included!"

---

## ✅ Best Practices

1. **Naming Convention:**
   - Gunakan nama yang jelas dan konsisten
   - Contoh: "Cinematic Video", bukan "Video" saja
   - Hindari singkatan yang ambigu

2. **Description:**
   - Jelaskan detail item (durasi, kualitas, dll)
   - Contoh: "Professional makeup for bride and groom **including** pre-wedding makeup trial"

3. **Quantity:**
   - Default quantity = 1
   - Gunakan quantity > 1 jika memang ada multiple items
   - Contoh: 2 makeup artists, 3 photographers

4. **Cascade Delete:**
   - Free items akan otomatis terhapus saat decoration dihapus
   - Tidak perlu manual delete satu per satu

5. **Validation:**
   - Selalu validasi `decoration_id` exists sebelum add free item
   - Cek duplikasi item_name dalam satu decoration

---

## 🚨 Common Issues & Solutions

### Issue: "Decoration not found"
**Solution:** Pastikan `decorationId` valid dan decoration exists di database

### Issue: "Free item not found"
**Solution:** 
- Pastikan `id` free item valid
- Pastikan free item belongs to decoration yang benar (check `decoration_id`)

### Issue: Free items tidak muncul di GET /decorations/{id}
**Solution:** Pastikan controller sudah include relationship:
```php
$decoration = Decoration::with('images', 'freeItems')->findOrFail($id);
```

---

## 📝 Summary

| Aspect | Detail |
|--------|--------|
| **Purpose** | Manage bonus/free items untuk setiap decoration |
| **Relationship** | 1 decoration → many free items (one-to-many) |
| **CRUD Operations** | Full CRUD (Create, Read, Update, Delete) |
| **Auth Required** | Yes (admin only) |
| **Cascade Delete** | Yes (delete decoration = delete free items) |
| **Frontend Display** | Show as badges/tags di decoration detail |

---

**🎉 Ready to use! Dokumentasi lengkap untuk team frontend.**
