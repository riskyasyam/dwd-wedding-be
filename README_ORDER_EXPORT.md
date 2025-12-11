# Order Export to Excel - API Documentation

## Overview
Fitur export order ke Excel untuk admin dashboard, memungkinkan download rekap semua transaksi order dalam format Excel (.xlsx).

---

## Endpoint

### Export Orders to Excel
Export semua order atau filtered orders ke file Excel dengan format lengkap.

```
GET /api/admin/orders/export
```

**Authentication:** Required (Bearer Token)  
**Role:** Admin only

---

## Request

### Headers
```
Authorization: Bearer YOUR_ACCESS_TOKEN
Content-Type: application/json
```

### Query Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `status` | string | No | Filter by order status: `pending`, `paid`, `processing`, `completed`, `cancelled`, `refunded` |
| `search` | string | No | Search by order number, customer name, or email |
| `start_date` | date | No | Filter orders from this date (Format: `YYYY-MM-DD`) |
| `end_date` | date | No | Filter orders until this date (Format: `YYYY-MM-DD`) |
| `user_id` | integer | No | Filter orders by specific user ID |

**Note:** Semua parameter optional. Jika tidak ada parameter, akan export semua orders.

---

## Response

**Content-Type:** `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`

File Excel akan langsung ter-download dengan nama:
```
Orders_Report_YYYY-MM-DD_HHMMSS.xlsx
```

Contoh: `Orders_Report_2025-12-11_210345.xlsx`

---

## Excel File Structure

### Sheet Name
`Orders Report`

### Columns

| Column | Description | Format |
|--------|-------------|--------|
| A | Order Number | Text (ORD-1765461806-E4DA3B) |
| B | Order Date | Date (dd/mm/yyyy) |
| C | Order Time | Time (HH:mm) |
| D | Customer Name | Text (First Name + Last Name) |
| E | Customer Email | Text |
| F | Customer Phone | Text |
| G | Items Count | Number |
| H | Decoration(s) | Text (comma separated if multiple) |
| I | Subtotal | Currency (Rp) |
| J | Discount | Currency (Rp) |
| K | Total Amount | Currency (Rp) |
| L | Status | Text (Pending/Paid/Processing/Completed/Cancelled/Refunded) |
| M | Payment Method | Text |
| N | Snap Token | Text (Midtrans) |
| O | Notes | Text |

### Styling
- **Header Row:** Bold white text on pink background (#E91E8C)
- **Column Widths:** Auto-adjusted for readability
- **Alignment:** Headers centered, data left-aligned

---

## Usage Examples

### 1. Export All Orders

**Request:**
```javascript
// JavaScript/React
const exportAllOrders = async () => {
  try {
    const response = await fetch('http://localhost:8000/api/admin/orders/export', {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    });
    
    // Create blob and download
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `Orders_Report_${new Date().toISOString()}.xlsx`;
    document.body.appendChild(a);
    a.click();
    a.remove();
  } catch (error) {
    console.error('Export failed:', error);
  }
};
```

**cURL:**
```bash
curl -X GET "http://localhost:8000/api/admin/orders/export" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  --output orders.xlsx
```

---

### 2. Export Paid Orders Only

**Request:**
```javascript
const exportPaidOrders = async () => {
  const response = await fetch(
    'http://localhost:8000/api/admin/orders/export?status=paid',
    {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    }
  );
  
  const blob = await response.blob();
  downloadFile(blob, 'Paid_Orders.xlsx');
};
```

**cURL:**
```bash
curl -X GET "http://localhost:8000/api/admin/orders/export?status=paid" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  --output paid_orders.xlsx
```

---

### 3. Export Orders by Date Range

**Request:**
```javascript
const exportOrdersByDateRange = async (startDate, endDate) => {
  const params = new URLSearchParams({
    start_date: startDate,
    end_date: endDate
  });
  
  const response = await fetch(
    `http://localhost:8000/api/admin/orders/export?${params}`,
    {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    }
  );
  
  const blob = await response.blob();
  downloadFile(blob, `Orders_${startDate}_to_${endDate}.xlsx`);
};

// Usage
exportOrdersByDateRange('2025-12-01', '2025-12-31');
```

**cURL:**
```bash
curl -X GET "http://localhost:8000/api/admin/orders/export?start_date=2025-12-01&end_date=2025-12-31" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  --output orders_december.xlsx
```

---

### 4. Export with Search Filter

**Request:**
```javascript
const exportSearchResults = async (searchTerm) => {
  const params = new URLSearchParams({
    search: searchTerm
  });
  
  const response = await fetch(
    `http://localhost:8000/api/admin/orders/export?${params}`,
    {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    }
  );
  
  const blob = await response.blob();
  downloadFile(blob, `Search_Results_${searchTerm}.xlsx`);
};

// Search by order number
exportSearchResults('ORD-1765461806');

// Search by customer name
exportSearchResults('John Doe');
```

**cURL:**
```bash
# Search by order number
curl -X GET "http://localhost:8000/api/admin/orders/export?search=ORD-1765461806" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  --output search_results.xlsx

# Search by customer name
curl -X GET "http://localhost:8000/api/admin/orders/export?search=John%20Doe" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  --output search_results.xlsx
```

---

### 5. Export with Multiple Filters

**Request:**
```javascript
const exportFiltered = async () => {
  const params = new URLSearchParams({
    status: 'paid',
    start_date: '2025-12-01',
    end_date: '2025-12-31',
    search: 'John'
  });
  
  const response = await fetch(
    `http://localhost:8000/api/admin/orders/export?${params}`,
    {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
      },
    }
  );
  
  const blob = await response.blob();
  downloadFile(blob, 'Filtered_Orders.xlsx');
};
```

---

## Frontend Implementation

### React Component Example

```typescript
import { useState } from 'react';
import api from '@/lib/axios';

interface ExportFilters {
  status?: string;
  search?: string;
  start_date?: string;
  end_date?: string;
}

const OrderExportButton = ({ filters }: { filters: ExportFilters }) => {
  const [isExporting, setIsExporting] = useState(false);

  const handleExport = async () => {
    setIsExporting(true);
    
    try {
      const response = await api.get('/admin/orders/export', {
        params: filters,
        responseType: 'blob', // Important for file download
      });

      // Create download link
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      
      // Generate filename with current date
      const filename = `Orders_Report_${new Date().toISOString().split('T')[0]}.xlsx`;
      link.setAttribute('download', filename);
      
      // Trigger download
      document.body.appendChild(link);
      link.click();
      
      // Cleanup
      link.remove();
      window.URL.revokeObjectURL(url);
      
      console.log('Export successful!');
    } catch (error: any) {
      console.error('Export failed:', error);
      alert('Failed to export orders. Please try again.');
    } finally {
      setIsExporting(false);
    }
  };

  return (
    <button
      onClick={handleExport}
      disabled={isExporting}
      className="bg-pink-600 text-white px-4 py-2 rounded hover:bg-pink-700 disabled:bg-gray-400"
    >
      {isExporting ? 'Exporting...' : 'Export to Excel'}
    </button>
  );
};

export default OrderExportButton;
```

### Usage in Order Management Page

```typescript
'use client';

import { useState } from 'react';
import OrderExportButton from '@/components/OrderExportButton';

export default function OrderManagement() {
  const [filters, setFilters] = useState({
    status: '',
    search: '',
    start_date: '',
    end_date: '',
  });

  return (
    <div>
      <h1>Order Management</h1>
      
      {/* Filters */}
      <div className="filters">
        <select 
          value={filters.status} 
          onChange={(e) => setFilters({...filters, status: e.target.value})}
        >
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="paid">Paid</option>
          <option value="completed">Completed</option>
        </select>
        
        <input
          type="text"
          placeholder="Search..."
          value={filters.search}
          onChange={(e) => setFilters({...filters, search: e.target.value})}
        />
        
        <input
          type="date"
          value={filters.start_date}
          onChange={(e) => setFilters({...filters, start_date: e.target.value})}
        />
        
        <input
          type="date"
          value={filters.end_date}
          onChange={(e) => setFilters({...filters, end_date: e.target.value})}
        />
      </div>
      
      {/* Export Button */}
      <OrderExportButton filters={filters} />
      
      {/* Orders Table */}
      {/* ... */}
    </div>
  );
}
```

---

## Helper Function

```typescript
// utils/downloadFile.ts
export const downloadFile = (blob: Blob, filename: string) => {
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.URL.revokeObjectURL(url);
};
```

---

## Error Handling

### Common Errors

#### 401 Unauthorized
```json
{
  "message": "Unauthenticated."
}
```
**Solution:** Check if token is valid and not expired.

#### 403 Forbidden
```json
{
  "message": "This action is unauthorized."
}
```
**Solution:** Ensure user has admin role.

#### 500 Internal Server Error
**Solution:** Check backend logs, verify database connection.

### Frontend Error Handling

```typescript
const handleExport = async () => {
  try {
    const response = await api.get('/admin/orders/export', {
      params: filters,
      responseType: 'blob',
    });
    
    // Check if response is valid
    if (response.data.size === 0) {
      throw new Error('No data to export');
    }
    
    downloadFile(response.data, generateFilename());
    
    // Success notification
    toast.success('Orders exported successfully!');
  } catch (error: any) {
    if (error.response?.status === 401) {
      toast.error('Session expired. Please login again.');
      router.push('/login');
    } else if (error.response?.status === 403) {
      toast.error('You do not have permission to export orders.');
    } else {
      toast.error('Failed to export orders. Please try again.');
    }
    console.error('Export error:', error);
  }
};
```

---

## Filter Combinations

### Valid Filter Combinations

| Status | Search | Date Range | Result |
|--------|--------|------------|--------|
| All | - | - | Export all orders |
| Paid | - | - | Export only paid orders |
| - | "John" | - | Orders from customers named John |
| - | - | Dec 1-31 | Orders in December |
| Paid | "John" | Dec 1-31 | Paid orders from John in December |

---

## Performance Notes

- **Small Dataset (< 1000 orders):** Export < 2 seconds
- **Medium Dataset (1000-10000 orders):** Export 3-10 seconds
- **Large Dataset (> 10000 orders):** Export 10-30 seconds

**Recommendation:** 
- For large datasets, consider adding loading indicator
- Implement pagination if exporting > 50,000 records

---

## Excel File Features

✅ **Formatted Headers:** Bold, colored, centered  
✅ **Auto-sized Columns:** Optimal width for readability  
✅ **Currency Formatting:** Proper Rupiah format  
✅ **Date Formatting:** dd/mm/yyyy format  
✅ **Multiple Items Support:** Comma-separated decoration names  
✅ **UTF-8 Support:** Indonesian characters supported  

---

## Testing

### Test Export Functionality

```bash
# Test with no filters
curl -X GET "http://localhost:8000/api/admin/orders/export" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  --output test_all.xlsx

# Test with status filter
curl -X GET "http://localhost:8000/api/admin/orders/export?status=paid" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  --output test_paid.xlsx

# Test with date range
curl -X GET "http://localhost:8000/api/admin/orders/export?start_date=2025-12-01&end_date=2025-12-11" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  --output test_december.xlsx

# Verify file is valid Excel
file test_all.xlsx
# Should output: Microsoft Excel 2007+
```

---

## Summary

✅ **Endpoint:** `GET /api/admin/orders/export`  
✅ **Format:** Excel (.xlsx)  
✅ **Filters:** Status, Search, Date Range  
✅ **File Size:** Optimized, ~100KB per 1000 orders  
✅ **Encoding:** UTF-8 (supports Indonesian)  
✅ **Styling:** Professional format with headers  

**Use Case:** Rekap order untuk laporan, accounting, atau analisis data.

