# Customer API – Laravel

REST API built with Laravel for managing customer data imported from CSV into MySQL.  
The API follows a **JSON:API-inspired** response structure and supports filtering, sorting, pagination, and Basic Authentication.

---


## 🗃️ Data Model

The `Customer` model (`app/Models/Customer.php`) maps to the `customers` table with the following fields:

| Field               | Description                  |
|---------------------|------------------------------|
| `customer_id`       | Unique customer identifier   |
| `first_name`        | First name                   |
| `last_name`         | Last name                    |
| `company`           | Company name                 |
| `city`              | City                         |
| `country`           | Country                      |
| `phone_1`           | Primary phone number         |
| `phone_2`           | Secondary phone number       |
| `email`             | Email address                |
| `subscription_date` | Date of subscription         |
| `website`           | Website URL                  |

> Timestamps (`created_at` / `updated_at`) are disabled on this model.

---

## 🚀 Installation

### 1. Clone the repository

```bash
git clone https://github.com/your-username/customer-api.git
cd customer-api
```

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

```bash
cp .env.example .env
```

Update the database configuration in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=test
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 4. Generate application key

```bash
php artisan key:generate
```

### 5. Start the server

```bash
php artisan serve
```

The server will be available at `http://127.0.0.1:8000`.

---

## 🔐 Authentication

This API uses **Basic Authentication**.

Create a user via Tinker:

```bash
php artisan tinker
```

```php
\App\Models\User::create([
    'name'     => 'admin',
    'email'    => 'admin@example.com',
    'password' => bcrypt('password'),
]);
```

Pass these credentials with every request (see Curl examples below).

---

## 📡 Endpoint

```
GET /api/users
```

---

## 🔎 Filtering

Filtering uses `LIKE` matching and is supported on the following fields: `first_name`, `email`.

```
/api/users?filter[first_name]=Eladio
/api/users?filter[email]=example.com
/api/users?filter[first_name]=Eladio&filter[email]=example.com
```

> **Note:** Filtering on fields other than `first_name` and `email` is silently ignored for security reasons.

---

## 🔄 Sorting

Sorting is supported on the following fields: `first_name`, `email`. Accepted directions are `asc` and `desc`.

```
/api/users?sort[first_name]=asc
/api/users?sort[email]=desc
/api/users?sort[first_name]=asc&sort[email]=desc
```

> Any value other than `desc` is treated as `asc` by default.

---

## 📄 Pagination

```
/api/users?page[number]=1&page[size]=10
```

| Parameter      | Default | Description               |
|----------------|---------|---------------------------|
| `page[number]` | `1`     | Page number to retrieve   |
| `page[size]`   | `15`    | Number of results per page |



---

## 🧾 Response Structure

Every response follows this structure:

```json
{
    "data": [
        {
            "customer_id": 1,
            "first_name": "Eladio",
            "last_name": "Smith",
            "company": "Acme Corp",
            "city": "Paris",
            "country": "France",
            "phone_1": "+33600000000",
            "phone_2": null,
            "email": "eladio@example.com",
            "subscription_date": "2023-01-15",
            "website": "https://example.com"
        }
    ],
    "links": {
        "first": "http://127.0.0.1:8000/api/users?page=1",
        "last":  "http://127.0.0.1:8000/api/users?page=5",
        "prev":  null,
        "next":  "http://127.0.0.1:8000/api/users?page=2"
    },
    "meta": {
        "current_page": 1,
        "from":         1,
        "last_page":    5,
        "path":         "http://127.0.0.1:8000/api/users",
        "per_page":     10,
        "to":           10,
        "total":        50
    }
}
```

---

## 🏷️ Custom Response Header

Every response includes:

```
x-api-version: v1
```

---

## 🧪 Testing with Curl

Basic request with authentication:

```bash
curl -u admin@example.com:password \
  "http://127.0.0.1:8000/api/users?filter[first_name]=kim"
```


---

## 🛠️ Tech Stack

| Layer      | Technology  |
|------------|-------------|
| Language   | PHP 8+      |
| Framework  | Laravel 10+ |
| Database   | MySQL       |
| ORM        | Eloquent    |

---

## ✅ Features Implemented

- [x] `GET /api/users` endpoint via `CustomerController`
- [x] Filtering with `LIKE` on `first_name` and `email`
- [x] Multi-field sorting on `first_name` and `email`
- [x] Custom pagination via `page[number]` and `page[size]`
- [x] JSON:API-style response (`data` / `links` / `meta`)
- [x] Basic Authentication
- [x] Custom `x-api-version: v1` response header
- [x] Timestamps disabled on the `Customer` model

---

## 📄 License

This project is open-source and available under the [MIT License](LICENSE).
