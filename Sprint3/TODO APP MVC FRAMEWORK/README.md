# PHP MVC Framework

A minimal PHP MVC framework built from scratch, based on the tutorial by Anant Garg. Created by SADIKI ABDELKARIM

---

## Directory Structure

```
php-mvc-framework/
├── .htaccess                        ← Redirects all traffic → /public
├── application/
│   ├── controllers/
│   │   └── itemscontroller.php      ← Items controller (view, viewall, add, delete)
│   ├── models/
│   │   └── item.php                 ← Item model
│   └── views/
│       ├── header.php               ← Global header
│       ├── footer.php               ← Global footer
│       └── items/
│           ├── viewall.php          ← List all todos
│           ├── view.php             ← Single todo item
│           ├── add.php              ← Add success page
│           └── delete.php           ← Delete success page
├── config/
│   └── config.php                   ← DB credentials & environment flag
├── db/
│   └── todo.sql                     ← Schema + seed data
├── library/
│   ├── bootstrap.php                ← Loads config + shared
│   ├── shared.php                   ← Error handling, routing, autoload
│   ├── controller.class.php         ← Base Controller class
│   ├── model.class.php              ← Base Model class
│   ├── sqlquery.class.php           ← MySQL abstraction layer
│   └── template.class.php          ← View renderer
├── public/
│   ├── .htaccess                    ← Routes all requests → index.php
│   └── index.php                    ← Single entry point
├── scripts/                         ← CLI utilities (future)
└── tmp/
    └── logs/                        ← Error logs (when not in dev mode)
```

---

### 1. Database
```bash
mysql -u root -p < db/todo.sql
```

### 2. Configure
Edit `config/config.php`:
```php
define('DB_NAME',     'todo');
define('DB_USER',     'yourusername');
define('DB_PASSWORD', 'yourpassword');
define('DB_HOST',     'localhost');
```
---

## URL Conventions

| URL Pattern                            | Action             |
|----------------------------------------|--------------------|
| `/items/viewall`                       | List all items     |
| `/items/view/{id}/{slug}`              | View single item   |
| `/items/add` (POST)                    | Add new item       |
| `/items/delete/{id}`                   | Delete item        |

---

## Coding Conventions

- MySQL tables: lowercase, plural — e.g. `items`
- Models: singular, capitalized — e.g. `Item`
- Controllers: pluralized + `Controller` suffix — e.g. `ItemsController`
- Views: `views/{controller}/{action}.php` — e.g. `views/items/view.php`
