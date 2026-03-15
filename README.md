# BlueRoute Freight

<div align="center">

```
██████╗ ██╗     ██╗   ██╗███████╗██████╗  ██████╗ ██╗   ██╗████████╗███████╗
██╔══██╗██║     ██║   ██║██╔════╝██╔══██╗██╔═══██╗██║   ██║╚══██╔══╝██╔════╝
██████╔╝██║     ██║   ██║█████╗  ██████╔╝██║   ██║██║   ██║   ██║   █████╗
██╔══██╗██║     ██║   ██║██╔══╝  ██╔══██╗██║   ██║██║   ██║   ██║   ██╔══╝
██████╔╝███████╗╚██████╔╝███████╗██║  ██║╚██████╔╝╚██████╔╝   ██║   ███████╗
╚═════╝ ╚══════╝ ╚═════╝ ╚══════╝╚═╝  ╚═╝ ╚═════╝  ╚═════╝    ╚═╝   ╚══════╝
```

**Security · Shipping · Storage**

[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![License](https://img.shields.io/badge/License-MIT-gold?style=flat-square)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Active-10b981?style=flat-square)]()

*A full-stack shipment management and secure freight tracking platform built with PHP & MySQL.*

</div>

---

## Overview

BlueRoute Freight is a web-based shipping and security logistics platform. It provides a dual-portal system — a **client-facing dashboard** for tracking personal shipments and a **comprehensive admin panel** for managing the full shipment lifecycle, vault storage records, users, and activity logs.

The platform is designed for security-grade freight companies that require end-to-end visibility, controlled access, and real-time tracking updates.

---

## Features

### Client Portal
- Secure login with role-based access control
- Personal shipment dashboard with status overview
- Live package tracking with step-by-step timeline
- BlueRoute Locker address for package forwarding
- Profile management

### Admin Panel
- Full shipment CRUD with auto-generated tracking numbers (`BLR-YYYYMMDD-XXXXX`)
- Granular tracking timeline editor (per-step done/active flags)
- Vault storage records management
- User management with role and status controls
- Activity log with type-categorised entries
- CSV export for shipment data
- Live status breakdown and reporting

### System
- CSRF protection on all POST forms
- Rate limiting on login (10 attempts / 15 min per IP)
- Session-based auth with role enforcement on every protected page and API endpoint
- Password hashing via `password_hash()` / `password_verify()`
- All user input sanitised through `htmlspecialchars` + `strip_tags`

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.1+ |
| Database | MySQL 8.0+ / MariaDB |
| Frontend | Vanilla HTML, CSS, JavaScript |
| Fonts | Google Fonts (DM Sans, DM Serif Display, Syne, Outfit) |
| Icons | Font Awesome 6.5 |
| Auth | PHP Sessions + CSRF tokens |

No frameworks. No build tools. No dependencies to install.

---

## Project Structure

```
blueroute/
│
├── api/
│   ├── shipments.php       # Shipment CRUD API
│   ├── vaults.php          # Vault records API
│   ├── users.php           # User management API
│   ├── activity.php        # Activity log API
│   └── track.php           # Public tracking lookup
│
├── includes/
│   ├── db.php              # PDO database connection
│   ├── helpers.php         # Shared utility functions
│   └── security.php        # CSRF + rate limiting
│
├── admin.php               # Admin dashboard (role-gated)
├── client-dashboard.php    # Client portal (role-gated)
├── login.php               # Login page + auth API
├── logout.php              # Session destruction
├── signup.php              # New user registration
├── tracking.php            # Public shipment tracker
└── index.html              # Landing / marketing page
```

---

## Getting Started

### Requirements

- PHP 8.1 or higher
- MySQL 8.0 or MariaDB 10.6+
- A web server (Apache / Nginx) or PHP's built-in server for local development

### Installation

**1. Clone the repository**
```bash
git clone https://github.com/yourusername/blueroute-freight.git
cd blueroute-freight
```

**2. Create the database**
```sql
CREATE DATABASE blueroute CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**3. Import the schema**
```bash
mysql -u root -p blueroute < schema.sql
```

**4. Configure the database connection**

Edit `includes/db.php` with your credentials:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'blueroute');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');
```

**5. Create your first admin account**

Register via `signup.php`, then update the role directly in the database:
```sql
UPDATE users SET role = 'admin' WHERE email = 'your@email.com';
```

**6. Run locally**
```bash
php -S localhost:8000
```

Then open [http://localhost:8000](http://localhost:8000).

---

## Database Schema (Key Tables)

```sql
users
  id, first_name, last_name, username, email, password_hash,
  phone, country, state, city, address, zip_code,
  role (admin|client), status (active|suspended),
  last_login, login_attempts, created_at

shipments
  id, tracking_number, client_name, client_email,
  origin, destination, service_type, status,
  current_location, weight_kg, description, notes,
  eta, created_at, updated_at

tracking_events
  id, shipment_id (FK), event_title, location,
  event_time, is_done, is_active, sort_order

vaults
  id, reference, client_name, facility, contents,
  status, last_audit, next_audit, notes, created_at

activity_log
  id, type, message, created_at
```

---

## API Reference

All write endpoints (`POST`, `PUT`, `DELETE`) require an active admin session.

### Shipments — `api/shipments.php`

| Method | Params | Description |
|---|---|---|
| `GET` | — | List all shipments |
| `GET` | `?id=` | Single shipment with tracking timeline |
| `POST` | JSON body | Create shipment (tracking number auto-generated) |
| `PUT` | `?id=` + JSON body | Update shipment and/or timeline |
| `DELETE` | `?id=` | Delete shipment and cascade events |

### Users — `api/users.php`

| Method | Params | Description |
|---|---|---|
| `GET` | — | List all users (no password hashes) |
| `GET` | `?id=` | Single user |
| `DELETE` | `?id=` | Delete user (cannot delete self) |
| `PATCH` | `?id=` + JSON body | Update `role` or `status` |

### Vaults — `api/vaults.php`

| Method | Description |
|---|---|
| `GET` | List all vault records |
| `POST` | Create vault record |
| `PUT` | Update vault record |
| `DELETE` | Delete vault record |

---

## Roles & Access

| Area | `client` | `admin` |
|---|---|---|
| Login | ✅ | ✅ |
| Client Dashboard | ✅ | ✗ (redirected to admin) |
| Admin Panel | ✗ (redirected to login) | ✅ |
| Public Tracking | ✅ | ✅ |
| API write endpoints | ✗ 403 | ✅ |

---

## Tracking Number Format

Tracking numbers are auto-generated on shipment creation:

```
BLR-YYYYMMDD-XXXXX
│    │         └── 5 random alphanumeric chars (no I, O, 0, 1)
│    └──────────── creation date
└───────────────── BlueRoute prefix

Example: BLR-20260315-A3F7K
```

Uniqueness is guaranteed by a collision-check loop against the database before insertion.

---

## Security Notes

- Sessions are regenerated on login (`session_regenerate_id(true)`)
- CSRF tokens are validated on all state-changing requests
- Passwords are never stored in plain text
- The `password_hash` field is excluded from all API responses
- The `?user_id=` GET override was intentionally removed from the client dashboard — users can only view their own data
- All API endpoints verify session role server-side regardless of client input

---

## License

MIT — see [LICENSE](LICENSE) for details.

---

<div align="center">

Built with care for the logistics industry.

*BlueRoute Security & Shipping — All rights reserved © 2026*

</div>
