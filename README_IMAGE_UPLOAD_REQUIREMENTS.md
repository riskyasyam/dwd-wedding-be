# Image Upload Requirements & Guidelines

## 📸 Syarat & Ketentuan Upload Gambar

### 1. Format File yang Diterima
✅ **Supported Formats:**
- JPEG (.jpeg, .jpg)
- PNG (.png)
- WebP (.webp)

❌ **Not Supported:**
- GIF, BMP, SVG, TIFF, dan format lainnya

---

### 2. Ukuran File

#### Limit Size:
- **Maximum:** 10 MB (10,240 KB) per image
- **Recommended:** 1-3 MB untuk optimal loading
- **Minimum:** 100 KB untuk kualitas yang baik

#### Multiple Upload:
- **Decoration Images:** Bisa upload multiple images sekaligus
- **Event Images:** Bisa upload multiple images sekaligus
- **Vendor Images:** Bisa upload multiple images sekaligus
- **Inspiration Image:** Single image only

---

### 3. Dimensi Gambar

#### Recommended Dimensions:

**Decoration Images:**
- Minimum: 800 x 600 px
- Recommended: 1920 x 1080 px (Full HD)
- Aspect Ratio: 16:9 atau 4:3
- Multiple images untuk showcase berbagai angle

**Event Images:**
- Minimum: 1200 x 800 px
- Recommended: 1920 x 1080 px
- Aspect Ratio: 16:9 untuk banner/hero
- Event photo harus jelas dan menarik

**Vendor Portfolio:**
- Minimum: 800 x 800 px
- Recommended: 1200 x 1200 px atau 1920 x 1080 px
- Aspect Ratio: 1:1 (square) atau 16:9
- Showcase hasil karya vendor

**Inspiration:**
- Minimum: 800 x 800 px
- Recommended: 1080 x 1080 px (Instagram size)
- Aspect Ratio: 1:1 atau 4:5
- High quality inspirational content

---

### 4. Kualitas Gambar

#### ✅ Good Quality:
- Resolusi tinggi (minimal 72 DPI, recommended 150 DPI)
- Lighting yang baik
- Focus/tajam, tidak blur
- Warna natural dan tidak over-saturated
- Komposisi yang baik

#### ❌ Bad Quality:
- Gambar blur atau pixelated
- Terlalu gelap atau terlalu terang
- Watermark dari website lain
- Screenshot dari aplikasi lain
- Gambar dengan teks berlebihan

---

### 5. Content Guidelines

#### ✅ Diperbolehkan:
- Foto hasil dekorasi asli
- Foto venue/event yang sudah dijalankan
- Portfolio karya vendor
- Inspirasi dekorasi wedding
- Behind the scenes (optional)

#### ❌ Tidak Diperbolehkan:
- Gambar copyrighted tanpa izin
- Stock photos generik
- Gambar yang menyesatkan
- Konten SARA atau tidak pantas
- Gambar yang mengandung informasi pribadi orang lain tanpa consent

---

### 6. Technical Requirements

#### File Naming:
- System akan auto-rename dengan format: `timestamp_uniqueid.extension`
- Contoh: `1702281234_abc123def.jpg`
- Original filename tidak disimpan

#### Storage Location:
```
/storage/app/public/decorations/  - Decoration images
/storage/app/public/events/        - Event images
/storage/app/public/vendors/       - Vendor images
/storage/app/public/inspirations/  - Inspiration images
```

#### Access URL:
```
http://yourdomain.com/storage/decorations/filename.jpg
http://yourdomain.com/storage/events/filename.jpg
http://yourdomain.com/storage/vendors/filename.jpg
http://yourdomain.com/storage/inspirations/filename.jpg
```

---

### 7. Image Optimization (Automatic)

Jika ImageOptimizer sudah diimplementasikan:

✅ **Auto Processing:**
- Resize ke max 1920px width (maintain aspect ratio)
- Compress dengan 80% quality
- Generate thumbnail 300px (70% quality)
- Convert ke WebP format (optional)

✅ **Benefits:**
- File size berkurang 70-90%
- Faster loading time
- Better SEO performance
- Mobile-friendly

---

### 8. Upload Limits

#### Per Request:
- **Decorations:** Max 10 images per upload
- **Events:** Max 10 images per upload
- **Vendors:** Max 10 images per upload
- **Inspirations:** 1 image only

#### Per Entity:
- **Decoration:** Unlimited images (recommended 3-8 images)
- **Event:** Unlimited images (recommended 4-10 images)
- **Vendor:** Unlimited images (recommended 6-12 portfolio)
- **Inspiration:** 1 image only

#### Total Upload Size:
- Max 100 MB per request (10 images x 10 MB)
- Server `post_max_size` must be >= 100M
- Server `upload_max_filesize` must be >= 10M

---

### 9. Error Messages

#### Common Errors:

**"The images.0 field must not be greater than 10240 kilobytes"**
- Solusi: Compress image sebelum upload atau gunakan file < 10MB

**"The images.0 must be an image"**
- Solusi: Gunakan format JPEG, PNG, atau WebP

**"The images.0 must be a file of type: jpeg, png, jpg, webp"**
- Solusi: Convert file ke format yang didukung

**"Maximum file size exceeded"**
- Solusi: Resize atau compress image terlebih dahulu

**"Unable to upload image"**
- Solusi: Check server storage permission dan disk space

---

### 10. Best Practices

#### Before Upload:
1. ✅ Resize image ke dimensi yang sesuai (jangan upload 4K jika tidak perlu)
2. ✅ Compress dengan tools seperti TinyPNG, ImageOptim, atau Photoshop
3. ✅ Gunakan format WebP jika browser support
4. ✅ Crop/remove unnecessary parts
5. ✅ Check image orientation (portrait/landscape)

#### Naming Convention:
1. ✅ Gunakan nama file yang descriptive sebelum upload
2. ✅ Contoh: `wedding-ballroom-jakarta-01.jpg` bukan `IMG_1234.jpg`
3. ✅ Helps untuk tracking dan management

#### Multiple Images:
1. ✅ Upload image dari berbagai angle
2. ✅ Include detail shots dan overall view
3. ✅ Gunakan consistent lighting/color grading
4. ✅ Sequence order: hero image first, details after

---

### 11. Server Configuration

#### Required PHP Settings:
```ini
upload_max_filesize = 10M
post_max_size = 100M
max_file_uploads = 20
memory_limit = 256M
max_execution_time = 300
```

#### Laravel Configuration:
File: `config/filesystems.php`
```php
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
],
```

#### Create Storage Link:
```bash
php artisan storage:link
```

---

### 12. Validation Rules Summary

#### Decoration Images:
```php
'images' => 'required|array',
'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:10240'
```

#### Event Images:
```php
'images' => 'required|array',
'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:10240'
```

#### Vendor Images:
```php
'images' => 'required|array',
'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:10240'
```

#### Inspiration Image:
```php
'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240'
```

---

### 13. Testing Upload

#### Test Single Image:
```bash
curl -X POST http://localhost:8000/api/admin/decorations/1/upload-images \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "images[]=@/path/to/image1.jpg"
```

#### Test Multiple Images:
```bash
curl -X POST http://localhost:8000/api/admin/decorations/1/upload-images \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "images[]=@/path/to/image1.jpg" \
  -F "images[]=@/path/to/image2.jpg" \
  -F "images[]=@/path/to/image3.jpg"
```

#### Check Image URL:
```
http://localhost:8000/storage/decorations/1702281234_abc123.jpg
```

---

### 14. Frontend Implementation

#### Image Preview Before Upload:
```javascript
const handleImageChange = (e) => {
  const files = Array.from(e.target.files);
  
  // Validate size
  const maxSize = 10 * 1024 * 1024; // 10MB in bytes
  const invalidFiles = files.filter(file => file.size > maxSize);
  
  if (invalidFiles.length > 0) {
    alert('Some images exceed 10MB limit. Please compress them.');
    return;
  }
  
  // Validate format
  const validFormats = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
  const invalidFormats = files.filter(file => !validFormats.includes(file.type));
  
  if (invalidFormats.length > 0) {
    alert('Only JPEG, PNG, and WebP formats are allowed.');
    return;
  }
  
  // Create previews
  const previews = files.map(file => URL.createObjectURL(file));
  setPreviews(previews);
};
```

#### Display Helper Text:
```jsx
<input 
  type="file" 
  multiple 
  accept="image/jpeg,image/png,image/webp"
  onChange={handleImageChange}
/>
<p className="text-sm text-gray-500 mt-1">
  Max 10MB per image. Formats: JPEG, PNG, WebP. Recommended: 1920x1080px
</p>
```

---

### 15. Troubleshooting

#### Image Not Showing After Upload:
1. Check storage link: `php artisan storage:link`
2. Verify APP_URL in `.env`
3. Check file permissions: `chmod 755 storage/app/public`
4. Clear cache: `php artisan cache:clear`

#### Upload Always Fails:
1. Check PHP `upload_max_filesize` and `post_max_size`
2. Check Laravel logs: `storage/logs/laravel.log`
3. Verify disk space available
4. Check file permissions on storage directory

#### Slow Upload:
1. Compress images before upload
2. Use WebP format
3. Increase `max_execution_time` in php.ini
4. Use chunked upload for large files
5. Implement client-side compression

---

## Summary

✅ **Max File Size:** 10 MB (10,240 KB)
✅ **Formats:** JPEG, PNG, WebP
✅ **Dimensions:** Min 800x600, Recommended 1920x1080
✅ **Quality:** High resolution, good lighting, sharp focus
✅ **Multiple Upload:** Up to 10 images per request
✅ **Auto Optimization:** Resize, compress, thumbnail generation

📝 **Note:** Pastikan server configuration sudah sesuai untuk support 10MB upload.
