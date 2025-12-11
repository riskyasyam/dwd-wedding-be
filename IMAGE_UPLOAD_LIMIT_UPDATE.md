# Image Upload Size Limit Update

## ✅ Changes Applied

### Increased Upload Limits
**From:** 2 MB (2048 KB)  
**To:** 10 MB (10,240 KB)

---

### Files Updated

#### 1. DecorationController
**File:** `app/Http/Controllers/Admin/DecorationController.php`

**Method:** `uploadImages()`
```php
'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:10240' // Max 10MB per image
```

#### 2. EventController
**File:** `app/Http/Controllers/Admin/EventController.php`

**Method:** `uploadImages()`
```php
'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:10240' // Max 10MB per image
```

#### 3. VendorController
**File:** `app/Http/Controllers/Admin/VendorController.php`

**Method:** `uploadImages()`
```php
'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:10240' // Max 10MB per image
```

#### 4. InspirationController
**File:** `app/Http/Controllers/Admin/InspirationController.php`

**Methods:** `store()` and `update()`
```php
'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240' // Max 10MB
```

---

## Server Configuration Check

✅ **PHP Settings (Already Configured):**
- `upload_max_filesize`: 2G ✅
- `post_max_size`: 2G ✅
- `max_file_uploads`: 20 ✅

Server sudah support upload hingga 2GB, jadi **tidak perlu konfigurasi tambahan**.

---

## Validation Rules

### Current Settings:
- **Max Size:** 10 MB (10,240 KB) per image
- **Formats:** JPEG, PNG, JPG, WebP
- **Multiple Upload:** Up to 20 images per request (server limit)

### Recommended Per Upload:
- **Decorations:** 3-10 images
- **Events:** 4-10 images
- **Vendors:** 6-12 images
- **Inspirations:** 1 image only

---

## Image Guidelines

### ✅ Recommended:
- **Dimensions:** 1920 x 1080 px (Full HD)
- **File Size:** 1-5 MB (after compression)
- **Format:** JPEG or WebP for photos
- **Quality:** High resolution, sharp, good lighting

### ⚠️ Before Upload:
1. Resize to appropriate dimensions
2. Compress with tools (TinyPNG, ImageOptim)
3. Check file size < 10 MB
4. Use descriptive filenames

---

## Documentation

Complete guidelines available in:
📄 **[README_IMAGE_UPLOAD_REQUIREMENTS.md](README_IMAGE_UPLOAD_REQUIREMENTS.md)**

Includes:
- Detailed format requirements
- Dimension guidelines
- Quality standards
- Content guidelines
- Technical requirements
- Error handling
- Best practices
- Frontend implementation
- Troubleshooting

---

## Testing

### Test Upload:
```bash
# Single image
POST /api/admin/decorations/{id}/upload-images
Authorization: Bearer YOUR_TOKEN
Content-Type: multipart/form-data

images[]: file.jpg (max 10MB)

# Multiple images
images[]: file1.jpg
images[]: file2.jpg
images[]: file3.jpg
```

### Expected Response:
```json
{
  "success": true,
  "message": "Images uploaded successfully",
  "data": [
    {
      "id": 1,
      "image": "/storage/decorations/1702281234_abc123.jpg",
      "decoration_id": 1,
      "created_at": "2025-12-11T10:00:00.000000Z",
      "updated_at": "2025-12-11T10:00:00.000000Z"
    }
  ]
}
```

---

## Error Messages

### "The images.0 field must not be greater than 10240 kilobytes"
**Before:** Max 2048 KB (2 MB) ❌  
**Now:** Max 10240 KB (10 MB) ✅

**Solution if still occurs:**
- Compress image before upload
- Use online compression tools
- Reduce image dimensions
- Convert to WebP format

---

## Frontend Display

### Show Requirements to Users:
```html
<div class="upload-info">
  <p><strong>Image Requirements:</strong></p>
  <ul>
    <li>Format: JPEG, PNG, WebP</li>
    <li>Max Size: 10 MB per image</li>
    <li>Recommended: 1920x1080px, 1-3 MB</li>
    <li>Multiple upload: Up to 10 images</li>
  </ul>
</div>
```

### Input Validation:
```jsx
<input 
  type="file" 
  multiple 
  accept="image/jpeg,image/png,image/webp"
  onChange={(e) => {
    const maxSize = 10 * 1024 * 1024; // 10MB
    const files = Array.from(e.target.files);
    
    const oversized = files.filter(f => f.size > maxSize);
    if (oversized.length > 0) {
      alert(`${oversized.length} file(s) exceed 10MB limit`);
      e.target.value = null;
    }
  }}
/>
<p className="text-sm text-gray-500">
  Max 10MB per image. JPEG, PNG, WebP only
</p>
```

---

## Summary

✅ **Updated:** Limit upload dari 2 MB ke 10 MB
✅ **Applied:** Semua controllers (Decoration, Event, Vendor, Inspiration)
✅ **Server Ready:** PHP sudah support upload 2GB
✅ **Documentation:** Complete guidelines tersedia
✅ **No Restart Needed:** Changes effective immediately

🎉 **Sekarang bisa upload image hingga 10 MB!**
