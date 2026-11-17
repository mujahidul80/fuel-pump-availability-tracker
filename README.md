<p align="center">
  <h1 align="center">⛽ Fuel Pump Availability Tracker (তেল দিচ্ছে?)</h1>
  <p align="center">
    <strong>Crowdsourced Real-Time Fuel Availability & Petrol Pump Live Status Monitoring Platform</strong>
    <br>
    <em>কোথায় তেল পাওয়া যাচ্ছে? চুয়াডাঙ্গা জ্বালানি তথ্য সেবা</em>
  </p>
</p>

<p align="center">
  <a href="https://github.com/mujahidul80/fuel-pump-availability-tracker"><img src="https://img.shields.io/badge/PHP-7.4%20%7C%208.x-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP"></a>
  <a href="https://github.com/mujahidul80/fuel-pump-availability-tracker"><img src="https://img.shields.io/badge/Database-MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="Database"></a>
  <a href="https://wa.me/8801571432927"><img src="https://img.shields.io/badge/WhatsApp-Chat%20Now-25D366?style=for-the-badge&logo=whatsapp&logoColor=white" alt="WhatsApp"></a>
  <a href="https://github.com/mujahidul80/fuel-pump-availability-tracker/blob/main/LICENSE"><img src="https://img.shields.io/badge/License-MIT-22c55e?style=for-the-badge" alt="License"></a>
  <a href="https://github.com/mujahidul80/fuel-pump-availability-tracker/pulls"><img src="https://img.shields.io/badge/PRs-Welcome-blue?style=for-the-badge" alt="PRs Welcome"></a>
</p>

---

## 📌 Overview

**Fuel Pump Availability Tracker (তেল দিচ্ছে?)** is a modern, community-driven web application designed to help motorists, drivers, and citizens instantly find which petrol pumps / gas stations currently have active supplies of **Petrol**, **Octane**, and **Diesel**.

Users can check live queues, submit new stations, verify community updates with upvotes/downvotes, and post comments with photographic evidence. Built for maximum reliability across Apache, cPanel, VPS, and cloud environments.

---

## ✨ Key Highlights & Features

- **🟢 Real-Time Fuel Availability Status:** Immediate badge indicators (তেল দিচ্ছে / তেল নেই).
- **⛽ Fuel-Specific Tracking:** Individual toggles for **Petrol (পেট্রোল)**, **Octane (অকটেন)**, and **Diesel (ডিজেল)**.
- **🏷️ Purchase Limits & Rationing:** Displays specific fuel ration limits per bike or car (e.g. `৳৫০০ / বাইক`).
- **👥 Crowdsourced Contributions:** Community members can add new pumps or update current status in seconds.
- **👍 Community Upvote/Downvote System:** Community verification to highlight genuine reports and downvote false alerts.
- **🛡️ Anti-Spam & Rate Limiter:** IP-based daily rate limits for votes and comments to prevent tampering.
- **🖼️ WebP Image Engine:** Auto-converts uploaded user photos to high-performance `.webp` format for rapid mobile loading.
- **🎛️ Protected Admin Control Center:** Full dashboard for moderating pumps, approving user comments, resetting counters, and updating SEO settings.
- **📱 Mobile-First Responsive Design:** Clean UI built with custom CSS variables, FontAwesome 6 icons, and Google Fonts (*Hind Siliguri*).
- **🔍 SEO & Social Media Optimized:** Dynamic OpenGraph and Twitter Card tags, `sitemap.xml`, and `robots.txt`.

---

## 💻 Installation & Setup

### Prerequisites
- **PHP**: Version 7.4 or 8.x (with PDO and GD extensions enabled)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Web Server**: Apache with `mod_rewrite` enabled (XAMPP, WAMP, Laragon, cPanel, or VPS)

### Method 1: Interactive 1-Click Web Installer (Recommended)
1. Clone the repository into your web server root directory:
   ```bash
   git clone https://github.com/mujahidul80/fuel-pump-availability-tracker.git
   ```
2. Place the folder inside `htdocs` (e.g., `C:/xampp/htdocs/Fuel`).
3. Open in your browser:
   ```text
   http://localhost/Fuel/install.php
   ```
4. Enter your database credentials and set an admin password. The installer will create all tables, insert default settings, and generate `db.php` automatically.

### Method 2: Manual Setup
1. Import `database.sql` into your MySQL database via phpMyAdmin or MySQL CLI:
   ```bash
   mysql -u root -p fuel_update < database.sql
   ```
2. Copy `db.sample.php` to `db.php`:
   ```bash
   cp db.sample.php db.php
   ```
3. Update `db.php` with your database credentials.

---

## 🔒 Admin Dashboard

Access the administrative control center at:
```text
http://localhost/Fuel/admin/
```
- **Default Password:** `admin` (or the password configured during installation)
- Actions supported:
  - Toggle pump availability on/off with 1 click
  - Moderate and remove reported comments
  - Delete obsolete pump records
  - Adjust daily voting & comment submission limits
  - Customize site name, contact details, and banner text

---

## 📂 Project Architecture

```text
fuel-pump-availability-tracker/
├── .github/
│   ├── ISSUE_TEMPLATE/   # Bug report & feature request templates
│   └── PULL_REQUEST_TEMPLATE.md
├── admin/
│   └── index.php         # Admin control panel & moderation console
├── public/               # Static assets (CSS, JS, robots.txt)
├── uploads/              # Uploaded media storage (.gitkeep)
├── .htaccess             # Apache pretty URLs & security rewrite rules
├── 404.php               # Custom branded 404 error page
├── database.sql          # Complete MySQL database schema & Chuadanga seed data
├── db.php                # Database connection configuration
├── db.sample.php         # Template database configuration
├── details.php           # Single pump view & community comment thread
├── footer.php            # Dynamic global footer with copyright attribution
├── header.php            # Dynamic header with OpenGraph meta tags
├── index.php             # Homepage with live list & pump submission modal
├── install.php           # 1-Click interactive web installation wizard
├── robots.txt            # Search engine crawler instructions
├── script.js             # Client-side AJAX voting & modal scripts
├── setup.php             # Database migration and setup script
├── sitemap.php           # Dynamic XML sitemap generator
└── style.css             # Mobile-first stylesheet
```

---

## 👤 Author & Support

**Developed by Mujahidul Islam**
- 💬 **WhatsApp:** [Chat on WhatsApp](https://wa.me/8801571432927) (`+8801571432927`)
- 🔗 **GitHub Profile:** [@mujahidul80](https://github.com/mujahidul80)
- 🌐 **Website / Blog:** [mujahidulib.blogspot.com](https://mujahidulib.blogspot.com)
- 📂 **Repository:** [mujahidul80/fuel-pump-availability-tracker](https://github.com/mujahidul80/fuel-pump-availability-tracker)

---

## 📄 License

This project is released under the open-source **[MIT License](LICENSE)**.
&copy; 2026 Mujahidul Islam. All Rights Reserved.
