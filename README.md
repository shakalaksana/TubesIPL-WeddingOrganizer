# Wedding Organizer (Dark & Fun Theme) 💍✨

A modern, responsive Wedding Organizer web application built with PHP native and Bootstrap 5.3. This project features a "Dark & Fun" theme with neon accents, glassmorphism effects, and smooth animations.

## 🚀 Features

- **User Interface**: 
    - Full Dark Mode with Neon Accents (`#0f172a` bg, `#d946ef` primary).
    - Glassmorphism Cards.
    - Responsive Animations (Float, Pop-in, Fade-in).
    - Google Font 'Outfit'.
- **Customer Features**:
    - Registration & Login.
    - Wedding Package Booking Form (`sewa_wedding.php`).
    - Order History & Status Tracking (`my_sewa.php`).
    - Profile Management (`customer_profile.php`).
- **Admin Features**:
    - Dashboard with Statistics & Charts (`dashboard.php`).
    - Order Management (Confirm Payments, Edit/Delete Orders) (`admin_pesanan.php`).
    - Customer Management (`admin_pelanggan.php`).
    - Secure Authentication (Hashed Passwords).

## 🛠️ Tech Stack

- **Backend**: Native PHP (No Framework).
- **Frontend**: HTML5, CSS3, Bootstrap 5.3.
- **Database**: MySQL.
- **Styling**: Custom `assets/theme.css`.

## 📦 Installation & Setup

1.  **Clone the Repository**
    ```bash
    git clone https://github.com/shakalaksana/TUBES-IPL.git
    cd TUBES-IPL
    ```

2.  **Database Setup**
    - Open **XAMPP Control Panel** and start **Apache** and **MySQL**.
    - Open `http://localhost/phpmyadmin`.
    - Create a new database named `WeddingOrganizer`.
    - Import the `database.sql` file located in the root directory.

3.  **Configuration**
    - Check `config.php` and match your database credentials:
      ```php
      $db_host = 'localhost';
      $db_user = 'root';
      $db_pass = ''; // Your MySQL password
      $db_name = 'WeddingOrganizer';
      ```

4.  **Run the Project**
    - Copy the project folder to `htdocs` (if using XAMPP).
    - Access via browser: `http://localhost/WeddingOrganizer`.
    - **OR** run with PHP built-in server:
      ```bash
      php -S localhost:8000
      ```
      Then open `http://localhost:8000`.

## 👤 Default Admin Account

- **Username**: `admin`
- **Password**: `admin123`

---

