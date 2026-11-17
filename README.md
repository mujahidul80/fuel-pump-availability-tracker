<p align="center">
  <h1 align="center">⛽ Fuel Pump Availability Tracker (তেল দিচ্ছে?)</h1>
  <p align="center">
    <strong>Crowdsourced Real-Time Fuel Availability & Petrol Pump Live Status Monitoring Platform</strong>
    <br>
    <em>কোথায় তেল পাওয়া যাচ্ছে? রিয়েল-টাইম জ্বালানি তথ্য সেবা</em>
  </p>
</p>

<p align="center">
  <a href="https://github.com/mujahidul80/fuel-pump-availability-tracker"><img src="https://img.shields.io/badge/PHP-7.4%20%7C%208.x-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP"></a>
  <a href="https://github.com/mujahidul80/fuel-pump-availability-tracker"><img src="https://img.shields.io/badge/Database-MySQL%20%7C%20SQLite-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="Database"></a>
  <a href="https://vercel.com/new/clone?repository-url=https%3A%2F%2Fgithub.com%2Fmujahidul80%2Ffuel-pump-availability-tracker"><img src="https://img.shields.io/badge/Vercel-Ready%201--Click-000000?style=for-the-badge&logo=vercel&logoColor=white" alt="Vercel Ready"></a>
  <a href="https://github.com/mujahidul80/fuel-pump-availability-tracker/blob/main/LICENSE"><img src="https://img.shields.io/badge/License-MIT-22c55e?style=for-the-badge" alt="License"></a>
  <a href="https://github.com/mujahidul80/fuel-pump-availability-tracker/pulls"><img src="https://img.shields.io/badge/PRs-Welcome-blue?style=for-the-badge" alt="PRs Welcome"></a>
</p>

<p align="center">
  <a href="https://vercel.com/new/clone?repository-url=https%3A%2F%2Fgithub.com%2Fmujahidul80%2Ffuel-pump-availability-tracker">
    <img src="https://vercel.com/button" alt="Deploy with Vercel"/>
  </a>
</p>

---

## 📌 Overview

**Fuel Pump Availability Tracker (তেল দিচ্ছে?)** is a modern, community-driven web application designed to help motorists, drivers, and citizens instantly find which petrol pumps / gas stations currently have active supplies of **Petrol**, **Octane**, and **Diesel**.

Users can check live queues, submit new stations, verify community updates with upvotes/downvotes, and post comments with photographic evidence. Built for maximum reliability with automatic zero-config fallback support for serverless environments (e.g., **Vercel**).

---

## ✨ Key Highlights & Features

- **🟢 Real-Time Fuel Availability Status:** Immediate badge indicators (তেল দিচ্ছে / তেল নেই).
- **⛽ Fuel-Specific Tracking:** Individual toggles for **Petrol (পেট্রোল)**, **Octane (অকটেন)**, and **Diesel (ডিজেল)**.
- **🏷️ Purchase Limits & Rationing:** Displays specific fuel ration limits per bike or car (e.g. `৳৫০০ / বাইক`).
- **👥 Crowdsourced Contributions:** Community members can add new pumps or update current status in seconds.
- **👍 Community Upvote/Downvote System:** Community verification to highlight genuine reports and downvote false alerts.
- **🛡️ Anti-Spam & Rate Limiter:** IP-based daily rate limits for votes and comments to prevent tampering.
- **🖼️ WebP Image Engine:** Auto-converts uploaded user photos to high-performance `.webp` format for rapid mobile loading.
- **⚡ Dual Database Engine (MySQL & SQLite):**
  - Seamlessly runs on standard **MySQL / MariaDB** (cPanel, XAMPP, VPS).
  - Automatically activates **SQLite fallback** in serverless environments (like Vercel) if no remote MySQL host is provided—preventing connection crashes.
- **🎛️ Protected Admin Control Center:** Full dashboard for moderating pumps, approving user comments, resetting counters, and updating SEO settings.
- **📱 Mobile-First Responsive Design:** Clean UI built with custom CSS variables, FontAwesome 6 icons, and Google Fonts (*Hind Siliguri*).
- **🔍 SEO & Social Media Optimized:** Dynamic OpenGraph and Twitter Card tags, `sitemap.xml`, and `robots.txt`.

---

## 🚀 How to Deploy Live on Vercel

You can make this project live on Vercel in just 60 seconds!

### Option A: One-Click Instant Deploy
Click the deploy button below:

[![Deploy with Vercel](https://vercel.com/button)](https://vercel.com/new/clone?repository-url=https%3A%2F%2Fgithub.com%2Fmujahidul80%2Ffuel-pump-availability-tracker)

### Option B: Manual Vercel Deployment
1. Go to **[Vercel Dashboard](https://vercel.com/new)**.
2. Click **"Import"** next to `mujahidul80/fuel-pump-availability-tracker`.
3. *(Optional)* If you want to connect an external Cloud MySQL database (e.g. from TiDB Cloud, Aiven, or Railway), add these **Environment Variables**:
   - `DB_HOST` : `your-mysql-hostname`
   - `DB_PORT` : `3306`
   - `DB_NAME` : `your-database-name`
   - `DB_USER` : `your-database-username`
   - `DB_PASS` : `your-database-password`
   - `ADMIN_PASS` : `your-admin-password`
4. Click **Deploy**.
5. Once deployment completes, your site will be live instantly with a public `*.vercel.app` URL!

> [!TIP]
> Even if you don't provide any MySQL environment variables, the built-in SQLite auto-engine will self-initialize sample pumps so your demo site works immediately without error!

---

## 💻 Local Installation (XAMPP / WAMP / Apache)

### Prerequisites
- PHP 7.4 or 8.x (with PDO and GD extensions enabled)
- MySQL 5.7+ or MariaDB

### 1-Click Interactive Web Setup:
1. Clone the repository into your web root (e.g. `htdocs/Fuel`):
   ```bash
   git clone https://github.com/mujahidul80/fuel-pump-availability-tracker.git
   ```
2. Open in your browser:
   ```
   http://localhost/Fuel/install.php
   ```
3. Enter your database credentials and admin password. The installer will create all tables, insert default settings, and generate `db.php` automatically.

---

## 🔒 Admin Portal

Access your administration dashboard:
```
http://localhost/Fuel/admin/
```
- **Default Password:** `admin` (or the password configured in `db.php` / installation)
- Actions supported:
  - Toggle pump availability on/off with 1 click
  - Moderate and remove reported comments
  - Delete obsolete pump records
  - Adjust daily voting & comment submission limits

---

## 📂 Project Architecture

```
├── .github/
│   ├── ISSUE_TEMPLATE/   # Bug report & feature request templates
│   └── PULL_REQUEST_TEMPLATE.md
├── admin/
│   └── index.php         # Admin control panel & moderation console
├── uploads/              # Media storage directory (.gitkeep)
├── .htaccess             # Apache pretty URLs & security rewrite rules
├── 404.php               # Custom branded 404 error page
├── database.sql          # Complete MySQL database schema export
├── db.php                # Smart hybrid DB connection (MySQL + SQLite fallback)
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
├── style.css             # Mobile-first stylesheet
└── vercel.json           # Vercel serverless runtime configuration
```

---

## 👨‍💻 Author & Copyright

- **Developed By:** [Mujahidul Islam](https://github.com/mujahidul80)
- **GitHub Profile:** [@mujahidul80](https://github.com/mujahidul80)
- **Copyright:** &copy; 2026 Mujahidul Islam. All Rights Reserved.

---

## 📄 License

This project is licensed under the open-source **[MIT License](LICENSE)**. Contributions, stars, and pull requests are warmly welcomed!
