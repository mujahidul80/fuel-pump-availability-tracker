# Fuel Pump Availability Tracker (তেল দিচ্ছে?)

A crowdsourced, real-time web application to monitor and track fuel availability across petrol pumps and gas stations. Built with PHP, MySQL, and vanilla JavaScript.

---

## 📌 Overview

**Fuel Pump Availability Tracker** helps commuters, drivers, and transport operators locate filling stations with active supplies of **Petrol**, **Octane**, and **Diesel**. Community members and pump staff can submit updates, verify reports via community votes, and leave comments with situational details and photo proof.

---

## ✨ Key Features

- **Real-Time Fuel Status:** Instant visibility of fuel supply status across registered pumps.
- **Multi-Fuel Tracking:** Separate status indicators for Petrol, Octane, and Diesel.
- **Fuel Rationing / Limit Info:** Displays purchase limits per vehicle (e.g., maximum taka or liter allocations).
- **Crowdsourced Updates:** Community members can submit new pumps or update current status via modal dialogues.
- **Community Verification:** Upvote/downvote mechanism to confirm valid reports.
- **Anti-Spam & Rate Limiting:** Daily IP-based limits for votes and submissions.
- **Image Upload with Auto-Conversion:** Automatic server-side conversion of uploaded photos to WebP format for high-speed page loads.
- **Admin Management Portal:** Dedicated administrative dashboard to manage stations, moderate comments, and configure system settings.
- **Web-Based Installer:** Built-in guided installer (`install.php`) for automatic database configuration.
- **Responsive Mobile-First UI:** Built with modern CSS and optimized for smartphone browsers.

---

## 🛠️ Technology Stack

- **Backend:** PHP (PDO, OOP principles)
- **Database:** MySQL / MariaDB (UTF-8 mb4 encoding)
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla ES6)
- **Icons & Fonts:** FontAwesome 6, Google Fonts (Hind Siliguri)
- **Web Server:** Apache (with `mod_rewrite` enabled via `.htaccess`)

---

## 🚀 Installation & Setup

### Prerequisites
- PHP 7.4 or higher with PDO and GD extensions enabled
- MySQL 5.7+ or MariaDB 10.3+
- Web server: Apache (XAMPP, WampServer, Laragon, or Linux LAMP/LEMP)

### Method 1: Using the Web Installer (Recommended)
1. Clone this repository to your web server root:
   ```bash
   git clone https://github.com/mujahidul80/fuel-pump-availability-tracker.git
   ```
2. Navigate to the project directory in your browser:
   ```
   http://localhost/fuel-pump-availability-tracker/install.php
   ```
3. Follow the on-screen steps to enter your database credentials and set an admin password. The installer will generate the database tables and write the configuration automatically.

### Method 2: Manual Setup
1. Import `database.sql` into your MySQL server via phpMyAdmin or MySQL CLI:
   ```bash
   mysql -u root -p fuel_update < database.sql
   ```
2. Copy `db.sample.php` to `db.php`:
   ```bash
   cp db.sample.php db.php
   ```
3. Edit `db.php` with your database credentials and admin password.
4. Open the project URL in your browser.

---

## 🔒 Admin Dashboard

Access the administrative control center at:
```
http://localhost/fuel-pump-availability-tracker/admin/
```
- **Default Password:** `admin` (or the password configured during installation)
- Features include:
  - Toggle station availability
  - Edit or delete station profiles
  - Moderate comments and reported entries
  - Adjust daily submission limits and IP restriction settings
  - Customize site title, descriptions, and contact links

---

## 📂 Project Structure

```
├── admin/
│   └── index.php         # Admin control panel
├── uploads/              # Uploaded media storage
├── .htaccess             # Pretty URLs and Apache rewrites
├── database.sql          # Database schema export
├── db.php                # Database connection (auto-generated)
├── db.sample.php         # Sample database configuration
├── details.php           # Single pump view & comment thread
├── footer.php            # Global site footer
├── header.php            # Global site header with dynamic SEO meta tags
├── index.php             # Main homepage & listing view
├── install.php           # Interactive web installation wizard
├── script.js             # Client-side interactions & AJAX voting
├── setup.php             # Database migration and setup script
└── style.css             # Main stylesheet
```

---

## 👨‍💻 Author & Copyright

- **Developed By:** [Mujahidul Islam](https://github.com/mujahidul80)
- **GitHub:** [@mujahidul80](https://github.com/mujahidul80)
- **Copyright:** &copy; 2026 Mujahidul Islam. All Rights Reserved.

---

## 📄 License

This project is open-source and available under the [MIT License](LICENSE).
Copyright (c) 2026 Mujahidul Islam.
