# 🔧 Fix Migration Duplicate Error

## ❌ Error Yang Terjadi

```
SQLSTATE[42S21]: Column already exists: 1060 Duplicate column name 'customer_name'
```

Error ini terjadi karena migration `alter_reviews_table_add_customer_name` mencoba menambahkan kolom yang sudah ada di migration `create_reviews_table`.

## ✅ Yang Sudah Diperbaiki

### File Fixed:
- `database/migrations/2024_12_04_000001_alter_reviews_table_add_customer_name.php`

### Perubahan:
Ditambahkan check `Schema::hasColumn()` untuk mencegah duplicate column:

```php
public function up(): void
{
    Schema::table('reviews', function (Blueprint $table) {
        // Make user_id nullable
        $table->foreignId('user_id')->nullable()->change();
        
        // Add customer_name only if not exists ✅
        if (!Schema::hasColumn('reviews', 'customer_name')) {
            $table->string('customer_name')->nullable()->after('user_id');
        }
    });
}
```

## 🚀 Cara Fresh Migrate di Device Baru

### Step 1: Drop Database & Recreate

Di MySQL/PhpMyAdmin:
```sql
DROP DATABASE IF EXISTS wo_dwd;
CREATE DATABASE wo_dwd;
```

### Step 2: Run Fresh Migration

```bash
cd C:\Users\risky\OneDrive\Dokumen\Project Fastwork\wo_dwd
php artisan migrate:fresh
```

Atau kalau mau dengan seeder:
```bash
php artisan migrate:fresh --seed
```

### Step 3: Verify

Check semua table sudah ada:
```sql
SHOW TABLES;
```

Expected tables:
- users
- decorations
- orders
- order_items
- reviews
- vouchers
- carts
- cart_items
- events
- inspirations
- advertisements
- vendors
- promotions
- faqs
- settings
- dll...

## 📊 Semua Migration Sudah Safe

Semua migration DP-related sudah punya check `Schema::hasColumn()`:

✅ `add_dp_fields_to_decorations_and_orders_tables.php` - Has column checks
✅ `add_snap_token_to_orders_table.php` - Has column checks  
✅ `add_remaining_paid_at_to_orders_table.php` - Has column checks
✅ `alter_reviews_table_add_customer_name.php` - ✅ **FIXED** - Now has column checks

## ⚠️ Kalau Masih Error

Jika masih ada error duplicate column lain, lakukan:

### Option 1: Fresh Migrate (Recommended)
```bash
php artisan migrate:fresh
```

### Option 2: Rollback Specific Migration
```bash
# Rollback 1 migration
php artisan migrate:rollback --step=1

# Atau rollback sampai migration tertentu
php artisan migrate:rollback --step=5
```

### Option 3: Check Migration Status
```bash
php artisan migrate:status
```

Akan tampilkan migration mana yang sudah run dan mana yang belum.

## 🎯 Next Steps

1. ✅ Migration sudah diperbaiki
2. 🔄 Di device baru, run `php artisan migrate:fresh`
3. 🧪 Test create order dengan DP
4. 📊 Verify database schema

---

**Status:** ✅ FIXED  
**Date:** December 16, 2025  
**Solution:** Added `Schema::hasColumn()` checks to prevent duplicate columns
