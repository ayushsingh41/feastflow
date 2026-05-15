# 🍽️ FeastFlow — Real-World Food Ordering System
### ApexPlanet Task 4 | Days 37–48 | Full Stack PHP/MySQL Project

![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![XAMPP](https://img.shields.io/badge/XAMPP-Compatible-F37623?logo=apache&logoColor=white)

---

## 📋 Project Overview

FeastFlow is a production-grade food ordering web application built with PHP 8, MySQL, and vanilla JS. It covers everything required in Task 4: authentication, CRUD, admin panel, analytics, search, filters, pagination, dark/light theme, and more.

---

## ✅ Features Implemented

### 🔐 Authentication
- Secure register & login with bcrypt password hashing
- CSRF token protection on all forms
- Account lockout after 5 failed attempts (15-min lockout)
- Password strength meter on register
- Forgot password flow
- Session management with role-based routing

### 👤 Customer Panel
- **Dashboard** — welcome banner, stats, category grid, featured/popular items
- **Browse Menu** — category filter pills, search, sort (popular/rating/price), veg filter, quick view modal
- **Cart** — AJAX add/remove/update, coupon code, live totals, sticky CTA
- **Checkout** — saved address auto-fill, payment method selection (COD/UPI/Card)
- **Orders** — full history, visual status progress tracker, reorder feature
- **Order Detail** — live progress steps, item breakdown, rating system
- **Profile** — avatar upload, personal info edit, password change

### 🛠️ Admin Panel
- **Dashboard** — stats cards, Chart.js revenue line chart, order status donut, top products
- **Products** — full CRUD, image upload, search/filter/pagination, featured & veg flags
- **Categories** — modal-based add/edit/delete with icon & color picker
- **Orders** — status filter pills, date range filter, AJAX inline status update
- **Users** — search, role/status filter, activate/deactivate, order stats
- **Coupons** — create percent/fixed coupons, usage tracker, expiry management
- **Analytics** — monthly revenue bar+line chart, category donut, orders by weekday, payment pie, KPIs

### 🎨 UI/UX
- Dark & Light theme toggle (persisted in localStorage)
- Fonts: Playfair Display + Plus Jakarta Sans
- Fully responsive (mobile, tablet, desktop)
- Animated stat counters, toast notifications, loading indicators
- Category pills, pagination, sticky cart bar

---

## 📁 Folder Structure

```
feastflow/
├── index.php                   → Entry point (redirects to login/dashboard)
├── database.sql                → Full DB schema + seed data
├── README.md
│
├── config/
│   ├── config.php              → App constants & settings
│   └── database.php            → PDO singleton connection
│
├── includes/
│   ├── auth.php                → Login, register, CSRF, session helpers
│   ├── functions.php           → Business logic (products, orders, cart...)
│   ├── header.php              → Topbar + sidebar HTML
│   └── footer.php              → Closing HTML + JS
│
├── auth/
│   ├── login.php               → Login form with demo credentials
│   ├── register.php            → Registration with password strength
│   ├── forgot-password.php     → Password reset form
│   └── logout.php              → Session destroy + redirect
│
├── admin/
│   ├── dashboard.php           → Stats + charts + recent activity
│   ├── products.php            → Products CRUD
│   ├── categories.php          → Categories management
│   ├── orders.php              → Orders management with AJAX status update
│   ├── view-order.php          → Order detail view
│   ├── users.php               → User management
│   ├── coupons.php             → Coupon management
│   └── analytics.php          → Business analytics with 4 charts
│
├── customer/
│   ├── dashboard.php           → Home with categories, featured, recent orders
│   ├── menu.php                → Browse menu with filters
│   ├── cart.php                → Cart with AJAX + coupon
│   ├── checkout.php            → Place order
│   ├── orders.php              → Order history with progress tracker
│   ├── order-detail.php        → Single order with rating
│   └── profile.php             → Edit profile + change password
│
└── assets/
    ├── css/style.css           → Full design system (dark/light)
    ├── js/main.js              → Theme, cart AJAX, counters, toasts
    └── uploads/products/       → Product image uploads
```

---

## 🚀 Setup Instructions (XAMPP)

### Step 1 — Copy Files
```
htdocs/feastflow/   ← paste all files here
```

### Step 2 — Create Database
1. Open `http://localhost/phpmyadmin`
2. Click **New** → name it `feastflow_db` → Create
3. Click **Import** → choose `database.sql` → Go

### Step 3 — Configure (if needed)
Open `config/database.php` and update:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // your MySQL username
define('DB_PASS', '');           // your MySQL password (usually empty for XAMPP)
define('DB_NAME', 'feastflow_db');
```

Open `config/config.php` and verify:
```php
define('APP_URL', 'http://localhost/feastflow');
```

### Step 4 — Run
Visit: `http://localhost/feastflow`

---

## 🔑 Demo Credentials

| Role     | Email                    | Password   |
|----------|--------------------------|------------|
| Admin    | admin@feastflow.com      | Admin@123  |
| Customer | rahul@example.com        | Admin@123  |
| Customer | priya@example.com        | Admin@123  |

---

## 🎟️ Demo Coupons

| Code       | Discount        | Min Order |
|------------|-----------------|-----------|
| FEAST20    | 20% off         | ₹200      |
| NEWUSER50  | ₹50 off         | ₹100      |
| SAVE100    | ₹100 off        | ₹500      |

---

## 🔧 Technologies Used

- **Backend**: PHP 8.1 — PDO, Sessions, bcrypt
- **Database**: MySQL 8 — Foreign keys, indexes, transactions
- **Frontend**: Vanilla JS — AJAX fetch, localStorage theme
- **Charts**: Chart.js 4.4
- **Icons**: Remix Icons 4.2
- **Fonts**: Google Fonts (Playfair Display + Plus Jakarta Sans)
- **Hosting**: XAMPP (local), compatible with InfinityFree / Hostinger

---

## 📦 Deliverables

- [x] Real-World Web Application
- [x] Admin Panel with full CRUD
- [x] User Authentication (Register/Login)
- [x] Search, Filters & Pagination
- [x] Analytics Dashboard with Charts
- [x] Documented GitHub Repo
- [ ] 10-minute Demo Video (record using OBS/Loom)

---

## 👨‍💻 Author

**Tejas** · B.Tech CSE · Sharda University · ApexPlanet Internship Task 4
