# Customer CSV Importer

A PHP script that imports customer data from a CSV file into a MySQL database using **PDO** and **League CSV**.

This project includes:

- Database creation and user setup via PHP
- Table schema creation
- CSV import with prepared statements and transactions
- Execution time measurement

---

## 🛠 Requirements

- PHP 8.x
- MySQL
- Composer

---

## ⚡ Installation

1. Clone the repository:

```bash
git clone https://github.com/yourusername/customer-csv-importer.git
cd customer-csv-importer
```
1. Install dependencies:
```bash
composer install
```

2. Create a .env file in the project root (you can use example.env for templating):
```bash
DB_HOST=127.0.0.1
DB_NAME=your_db_name
DB_ROOT_USER=root
DB_ROOT_PASS=your_root_password

DB_USER=your_db_user
DB_PASS=your_db_password
```

## 🚀 Usage
1️⃣ Setup Database and Table
```bash
php setup.php
```
2️⃣ Import CSV
```bash
php import.php
```
The script will output:
```bash
Imported X records successfully.
Execution time: Y seconds.
```