# ConstructoHub - Construction Materials Marketplace

construction_final is a construction materials marketplace.

![PHP](https://img.shields.io/badge/PHP-2563EB?style=flat-square) ![MySQL](https://img.shields.io/badge/MySQL-0F172A?style=flat-square)

## Overview

The data model covers `admins`, `bulk_orders`, `chat_messages`, `coupons`, `credit_payments`, `materials`, `notifications`, `orders`, `order_items`, `payments`, `reviews`, `supplier_bids`, and related records.

## Application areas

| Area | Pages in the repository |
| --- | --- |
| Admin | [`admin/admin_home.php`](admin/admin_home.php), [`admin/admin_login.php`](admin/admin_login.php), [`admin/admin_navbar.php`](admin/admin_navbar.php), [`admin/order_details.php`](admin/order_details.php), [`admin/view_material.php`](admin/view_material.php) |
| Customer | [`customer/customer_chat_list.php`](customer/customer_chat_list.php), [`customer/dashboard.php`](customer/dashboard.php), [`customer/feedback.php`](customer/feedback.php), [`customer/generate_invoice.php`](customer/generate_invoice.php), [`customer/material_details.php`](customer/material_details.php) |
| Root Pages | [`login.php`](login.php), [`register.php`](register.php) |
| Retailer | [`retailer/bulk_order.php`](retailer/bulk_order.php), [`retailer/customer_chat_list.php`](retailer/customer_chat_list.php), [`retailer/dashboard.php`](retailer/dashboard.php), [`retailer/feedback.php`](retailer/feedback.php), [`retailer/generate_invoice.php`](retailer/generate_invoice.php) |
| Wholesaler | [`wholesaler/add_coupons.php`](wholesaler/add_coupons.php), [`wholesaler/add_material.php`](wholesaler/add_material.php), [`wholesaler/chat.php`](wholesaler/chat.php), [`wholesaler/credit_payment.php`](wholesaler/credit_payment.php), [`wholesaler/dashboard.php`](wholesaler/dashboard.php) |

## Getting started

### 1. Get the project

```bash
git clone https://github.com/nasirabbas827/construction_final.git
cd construction_final
```

### 2. Configure PHP and the database

1. Use a local PHP server with MySQL or MariaDB, such as XAMPP.
2. Import [`Database/construction_db.sql`](Database/construction_db.sql) into your local database server.
3. Set your local database connection details in [`admin/config.php`](admin/config.php), [`config.php`](config.php), [`customer/config.php`](customer/config.php), [`retailer/config.php`](retailer/config.php), [`wholesaler/config.php`](wholesaler/config.php).
4. Keep the existing asset and upload directory paths intact.

### 3. Start the application

With the database running, start a local development server from the repository root:

```bash
php -S 127.0.0.1:8000
```

Open http://127.0.0.1:8000/index.php. If using Apache/XAMPP instead, place the project in its document root and open the matching localhost folder.

## Repository guide

| Path | Purpose |
| --- | --- |
| [`Database`](Database) | Database scripts |
| [`PHPMailer`](PHPMailer) | Bundled mail integration |
| [`config.php`](config.php) | Application/database configuration |
| [`css`](css) | Stylesheets |
| [`images`](images) | Image assets |
| [`index.php`](index.php) | Web entry page |
| [`uploads`](uploads) | Upload storage |

## Development notes

These setup instructions are based on the repository source. The application has not been executed as part of this documentation update; verify dependencies and configuration in your local environment.
