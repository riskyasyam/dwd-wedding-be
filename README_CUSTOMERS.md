# Customer Name Handling Documentation

## Database Structure

### Users Table
The `users` table stores names as separate fields:
```sql
first_name VARCHAR(255)
last_name VARCHAR(255)
```

This normalized structure allows for:
- Proper name sorting
- Formal communication (Dear Mr. {last_name})
- International name formats
- Individual field updates

## Backend Implementation

### User Model Accessor

The `User` model provides a virtual `name` attribute via Laravel accessor:

```php
// app/Models/User.php

protected $appends = ['name'];

public function getNameAttribute()
{
    return trim("{$this->first_name} {$this->last_name}");
}
```

### Field Definitions
- **first_name**: User's first name (given name)
- **last_name**: User's last name (family name)
- **name**: Virtual attribute (full name) - auto-generated from first_name + last_name

## API Responses

### JSON Output
When retrieving user data, the response includes all fields:

```json
{
  "id": 1,
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "role": "customer",
  "name": "John Doe"
}
```

### Notes:
- `name` is READ-ONLY (virtual attribute)
- Updates must use `first_name` and `last_name` fields
- `name` automatically appears in JSON responses
- Search queries should target `first_name` and `last_name` separately

## Frontend Usage

### Display Full Name
```javascript
// Simply use the name field
<p>Welcome, {user.name}!</p>
```

### Display Name Parts
```javascript
// For forms or detailed views
<input value={user.first_name} />
<input value={user.last_name} />
```

### Update Name
```javascript
// PUT /api/admin/customers/:id
fetch('/api/admin/customers/1', {
  method: 'PUT',
  body: JSON.stringify({
    first_name: 'John',
    last_name: 'Doe'
  })
})
```

## Admin Customer Management

### API Endpoints

#### Get Customers List
```http
GET /api/admin/customers
```

**Query Parameters:**
- `search` - Search by first_name, last_name, or email
- `page` - Pagination page number

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "first_name": "John",
      "last_name": "Doe",
      "name": "John Doe",
      "email": "john@example.com",
      "phone": "08123456789",
      "role": "customer",
      "email_verified_at": "2024-01-15T10:30:00.000000Z"
    }
  ],
  "current_page": 1,
  "total": 50
}
```

#### Get Customer Detail
```http
GET /api/admin/customers/:id
```

**Response:**
```json
{
  "id": 1,
  "first_name": "John",
  "last_name": "Doe",
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "08123456789",
  "role": "customer",
  "orders_count": 5,
  "reviews_count": 3,
  "saved_inspirations_count": 10
}
```

#### Update Customer
```http
PUT /api/admin/customers/:id
```

**Request Body:**
```json
{
  "first_name": "Jane",
  "last_name": "Smith",
  "email": "jane@example.com"
}
```

**Notes:**
- Cannot update `name` directly (it's virtual)
- Must send `first_name` and/or `last_name`
- `name` will auto-update in response

#### Delete Customer
```http
DELETE /api/admin/customers/:id
```

#### Get Customer Statistics
```http
GET /api/admin/customers/statistics
```

**Response:**
```json
{
  "total_customers": 250,
  "verified_customers": 200,
  "new_this_month": 15
}
```

## Search Implementation

### Backend Search Logic
The search functionality queries multiple fields:

```php
$customers = User::where('role', 'customer')
    ->when($request->search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', '%' . $search . '%')
              ->orWhere('last_name', 'like', '%' . $search . '%')
              ->orWhere('email', 'like', '%' . $search . '%');
        });
    })
    ->paginate(15);
```

### Frontend Search
```javascript
// Search by any part of name or email
fetch('/api/admin/customers?search=john')
fetch('/api/admin/customers?search=doe')
fetch('/api/admin/customers?search=john@example.com')
```

## Common Patterns

### Table Display
```javascript
// Admin customer table
<table>
  <thead>
    <tr>
      <th>Name</th>
      <th>Email</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
    {customers.map(customer => (
      <tr key={customer.id}>
        <td>{customer.name}</td>
        <td>{customer.email}</td>
        <td>...</td>
      </tr>
    ))}
  </tbody>
</table>
```

### Edit Form
```javascript
// Customer edit form
<form onSubmit={handleUpdate}>
  <input
    name="first_name"
    value={customer.first_name}
    onChange={handleChange}
    placeholder="First Name"
  />
  <input
    name="last_name"
    value={customer.last_name}
    onChange={handleChange}
    placeholder="Last Name"
  />
  <input
    name="email"
    value={customer.email}
    onChange={handleChange}
    placeholder="Email"
  />
</form>
```

### Review Display
```javascript
// Show reviewer name in review card
<div className="review">
  <h4>{review.user.name}</h4>
  <p>{review.comment}</p>
</div>
```

## Validation Rules

### Create User (Registration)
```php
'first_name' => 'required|string|max:255',
'last_name' => 'required|string|max:255',
'email' => 'required|email|unique:users',
'password' => 'required|min:8'
```

### Update User
```php
'first_name' => 'sometimes|required|string|max:255',
'last_name' => 'sometimes|required|string|max:255',
'email' => 'sometimes|required|email|unique:users,email,' . $userId
```

## Testing

### Example Test Data
```php
// Create test customer
User::create([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'password' => Hash::make('password'),
    'role' => 'customer'
]);

// Access name
$user = User::first();
echo $user->name; // "John Doe"
```

### Testing Search
```bash
# Search by first name
curl -H "Authorization: Bearer {token}" \
  "http://localhost:8000/api/admin/customers?search=john"

# Search by last name
curl -H "Authorization: Bearer {token}" \
  "http://localhost:8000/api/admin/customers?search=doe"

# Search by email
curl -H "Authorization: Bearer {token}" \
  "http://localhost:8000/api/admin/customers?search=john@example.com"
```

## Best Practices

1. **Display**: Always use `user.name` for display purposes
2. **Updates**: Always send `first_name` and `last_name` separately
3. **Search**: Backend handles searching across both name fields
4. **Validation**: Validate `first_name` and `last_name`, never `name`
5. **Forms**: Show separate inputs for first and last name
6. **Read-only**: Never try to update the `name` field directly

## Related Documentation
- README_REVIEWS.md - Review system uses user.name
- README_MASTER_DATA.md - Master data CRUD operations
- README_LANDING_PAGE.md - Public API endpoints
