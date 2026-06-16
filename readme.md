# 🏨 University Hostel - Modern Management System

A full-fledged, professional AI-integrated Web Application designed for University Hostel Operations. Built with PHP 8 (PDO), MySQL, and AdminLTE 4.

> **⚠️ Note:** Is project ko setup karne ke liye sirf root folder mein maujood `database.sql` zaroori hai. Baaki tamaam `.sql` files aur `core/run_migrations.php` ko folder se delete kar diya gaya hai taake project clean rahe.

## ✨ Project Introduction

The **University Hostel** is a cutting-edge, AI-powered management system developed to streamline and automate the complex operations of university hostels. Moving beyond traditional manual processes, this system offers a centralized, secure, and intelligent platform for managing students, rooms, fees, attendance, gate security, and assets. With its intuitive interface and advanced features like an AI Warden Assistant and real-time analytics, it aims to enhance efficiency, transparency, and overall student experience.

## 📸 Screenshots
Create a folder at `assets/img/screenshots/` and place your images there. Then update the links below:

*(Please add your project screenshots here. For example:)*

*   **Dashboard Overview:**
    ![Dashboard Screenshot](assets/img/screenshots/dashboard.png)
*   **Fee Management:**
    ![Fee Management Screenshot](assets/img/screenshots/fee.png)
*   **AI Assistant in Action:**
    ![AI Assistant Screenshot](assets/img/screenshots/ai.png)
*   **Gate Management:**
    ![Gate Management Screenshot](assets/img/screenshots/gate.png)

## ⚙️ Installation & Setup

Follow these steps to get the Residence Hostel ERP running on your local machine:

### Prerequisites
*   **XAMPP / WAMP / MAMP**: A local server environment (Apache, MySQL, PHP).
*   **Git**: For cloning the repository.

### 1. Clone the Repository
If you are setting it up manually, just ensure the folder name is `residential` inside `htdocs`.
```bash
git clone [YOUR_GITHUB_REPOSITORY_URL] residential
cd residential
```

### 2. Database Setup
*   Open your web browser and go to `http://localhost/phpmyadmin`.
*   Create a new database named **`universal_db`**.
*   Import the `database.sql` file located in the project root into the `universal_db` database. This will create all necessary tables and populate initial data.

### 3. Configure the Application
*   Open `c:\xampp\htdocs\residential\core\config.php` in a text editor.
    ```php
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'universal_db'); // Ensure this matches your database name
    define('DB_USER', 'root');         // Your MySQL username
    define('DB_PASS', '');             // Leave empty for default XAMPP
    define('BASE_URL', 'http://localhost/residential/');
    ```

### 4. Run the Application
*   Start Apache and MySQL services from your XAMPP/WAMP/MAMP control panel.
*   Open your web browser and navigate to `http://localhost/residential/`.

## 🔑 Default Credentials
For quick testing and initial setup, you can use the following credentials:

| Role          | Email             | Password | Registration No. |
| :------------ | :---------------- | :------- | :--------------- |
| **Super Admin** | `admin@hostel.com`   | `123456` | *Administrator Access* |
| **Warden**      | `warden@hostel.com`  | `123456` | *Staff Management*     |
| **Student**     | `student@hostel.com` | `123456` | `ST-2024-001`          |

---

## 🚀 Key Features

### 🛠 Core Management
*   **User & Role Management**: Comprehensive CRUD for students, wardens, and super admins with dynamic role-based access control.
*   **Room Management**: Define buildings, blocks, room types (student, staff, office), capacity, and washroom types.
*   **Room Allocation**: Assign students to specific rooms and beds, with real-time occupancy tracking.
*   **Student Registration**: Streamlined process for new student onboarding.

### 🔒 Advanced Security & Gate Control
*   **Gate Management**: Real-time logging of student IN/OUT movements, with automated curfew violation detection.
*   **Visitor Management**: Track visitor details, purpose, and associated students.
*   **Digital Student ID**: QR-code based student identification for quick verification.

### 💰 Financial & Administrative
*   **Fee Management**: Assign, track, and manage student fees with statuses like Unpaid, Partial, Paid, Pending Verification, and Rejected. Supports payment receipt uploads.
*   **Complaint Management**: A robust ticketing system for students to lodge complaints, with priority levels and admin resolution workflow.
*   **Leave Management**: Workflow for students to apply for leaves and for wardens to approve/reject them.
*   **Announcements**: Digital notice board for official communications with expiry dates.

### 🤖 AI & Automation
*   **AI Warden Assistant**: An integrated AI (powered by OpenRouter API) to assist wardens in drafting formal notices, warnings, emails, and other administrative content.
*   **Smart Suggestions**: AI provides context-aware suggestions for content generation.

### 📊 Reporting & Analytics
*   **Interactive Dashboard**: A high-level overview of hostel operations with key metrics and real-time charts (revenue, occupancy, complaints).
*   **Detailed Reports**: Generate and export PDF reports for fee collection, attendance, room occupancy, and complaints.
*   **Attendance Tracking**: Daily attendance marking with integration for approved leaves.

### 📦 Inventory & Assets
*   **Inventory Management**: Track consumable items (e.g., cleaning supplies, bulbs).
*   **Asset Tracking**: Register and manage high-value assets (furniture, electronics) with unique asset tags.
*   **Asset Allocation**: Allocate assets to students or rooms, tracking their condition upon issue and return.

### 🎓 Student Portal
*   **Personalized Dashboard**: Students can view their room details, fee status, payment history, and lodge complaints.
*   **Digital ID**: Access to their unique QR-code based digital ID.
*   **Notice Board**: View all official announcements.

## 💻 Tech Stack

*   **Backend**: PHP 8 (with PDO for secure database interactions)
*   **Database**: MySQL
*   **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5, AdminLTE 4
*   **Charting**: Chart.js
*   **AI Integration**: OpenRouter API (Mistral, Gemini, Llama models)
*   **PDF Generation**: html2pdf.js

---

## 🚀 Future Enhancements

### 1. 📲 QR Code Integration (High Priority)
*   Integrate the **Digital ID QR** with the **Gate Management** module. The warden should be able to scan a student's phone to automatically log them "IN" or "OUT".

### 2. 📧 Automated Notifications
*   Integrate an Email/SMS API. When a student's fee is overdue or an announcement is made, the system should send an auto-alert.

### 3. 📊 Advanced Analytics
*   Add a "Performance Report" for the Mess (wastage tracking) and a "Financial Forecast" chart on the dashboard.

### 4. 🗂 Bulk Data Import
*   Add an "Import from Excel" feature in **Manage Users** to quickly onboard hundreds of students at the start of a semester.

### 5. 🛡 Security Hardening
*   Implement **Two-Factor Authentication (2FA)** for Admin logins and **File Upload Validation** (MIME check) for receipts to prevent malicious scripts.

### 6. 📅 Event Calendar
*   A module to manage hostel events, sports weeks, or maintenance schedules visible on the dashboard.

---

## 📂 Project Structure

```text
universal/
├── assets/               # CSS, JS, and Images (Bootstrap, AdminLTE, Avatars)
├── core/                 # Backend Logic (Auth, Session, AI Handler, DB Connection)
│   ├── ai_handler.php    # OpenRouter AI API Logic
│   ├── auth.php          # Login/Registration Logic
│   ├── config.php        # Database & URL Configuration
│   └── db.php            # PDO Connection Instance
├── dashboards/           # Role-based Modules
│   └── super_admin/      # Admin-only management pages
├── includes/             # Reusable UI Components (Header, Footer, Sidebar)
├── database.sql          # SINGLE Database Schema File
├── index.php             # Main Intelligence Dashboard
├── login.php             # Secure Login Portal
└── git-helper.bat        # Quick Backup Utility
### 🛠 Core Management
*   **User & Role Management**: Comprehensive CRUD for students, wardens, and super admins with dynamic role-based access control.
*   **Room Management**: Define buildings, blocks, room types (student, staff, office), capacity, and washroom types.
*   **Room Allocation**: Assign students to specific rooms and beds, with real-time occupancy tracking.
*   **Student Registration**: Streamlined process for new student onboarding.

### 🔒 Advanced Security & Gate Control
*   **Gate Management**: Real-time logging of student IN/OUT movements, with automated curfew violation detection.
*   **Visitor Management**: Track visitor details, purpose, and associated students.
*   **Digital Student ID**: QR-code based student identification for quick verification.

### 💰 Financial & Administrative
*   **Fee Management**: Assign, track, and manage student fees with statuses like Unpaid, Partial, Paid, Pending Verification, and Rejected. Supports payment receipt uploads.
*   **Complaint Management**: A robust ticketing system for students to lodge complaints, with priority levels and admin resolution workflow.
*   **Leave Management**: Workflow for students to apply for leaves and for wardens to approve/reject them.
*   **Announcements**: Digital notice board for official communications with expiry dates.

### 🤖 AI & Automation
*   **AI Warden Assistant**: An integrated AI (powered by OpenRouter API) to assist wardens in drafting formal notices, warnings, emails, and other administrative content.
*   **Smart Suggestions**: AI provides context-aware suggestions for content generation.

### 📊 Reporting & Analytics
*   **Interactive Dashboard**: A high-level overview of hostel operations with key metrics and real-time charts (revenue, occupancy, complaints).
*   **Detailed Reports**: Generate and export PDF reports for fee collection, attendance, room occupancy, and complaints.
*   **Attendance Tracking**: Daily attendance marking with integration for approved leaves.

### 📦 Inventory & Assets
*   **Inventory Management**: Track consumable items (e.g., cleaning supplies, bulbs).
*   **Asset Tracking**: Register and manage high-value assets (furniture, electronics) with unique asset tags.
*   **Asset Allocation**: Allocate assets to students or rooms, tracking their condition upon issue and return.

### 🎓 Student Portal
*   **Personalized Dashboard**: Students can view their room details, fee status, payment history, and lodge complaints.
*   **Digital ID**: Access to their unique QR-code based digital ID.
*   **Notice Board**: View all official announcements.

## 💻 Tech Stack

*   **Backend**: PHP 8 (with PDO for secure database interactions)
*   **Database**: MySQL
*   **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5, AdminLTE 4
*   **Charting**: Chart.js
*   **AI Integration**: OpenRouter API (Mistral, Gemini, Llama models)
*   **PDF Generation**: html2pdf.js

## ⚙️ Installation & Setup

Follow these steps to get the Residence Hostel ERP running on your local machine:

### Prerequisites
*   **XAMPP / WAMP / MAMP**: A local server environment (Apache, MySQL, PHP).
*   **Git**: For cloning the repository.

### 1. Clone the Repository
```bash
git clone [YOUR_GITHUB_REPOSITORY_URL] universal
cd universal
```

### 2. Database Setup
*   Open your web browser and go to `http://localhost/phpmyadmin`.
*   Create a new database named `universal_db`.
*   Import the `database.sql` file located in the project root into the `universal_db` database. This will create all necessary tables and populate initial data.

### 3. Configure the Application
*   Open `c:\xampp\htdocs\universal\core\config.php` in a text editor.
*   Update the `DB_NAME`, `DB_USER`, `DB_PASS`, and `BASE_URL` constants if they differ from your setup.
    ```php
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'universal_db'); // Ensure this matches your database name
    define('DB_USER', 'root');         // Your MySQL username
    define('DB_PASS', '123456');       // Your MySQL password (empty string '' if no password)
    define('BASE_URL', 'http://localhost/universal/'); // Ensure this matches your project URL
    ```

### 4. Run the Application
*   Start Apache and MySQL services from your XAMPP/WAMP/MAMP control panel.
*   Open your web browser and navigate to `http://localhost/universal/`.

## 🔑 Default Credentials

For quick testing and initial setup, you can use the following credentials:

| Role          | Email             | Password | Registration No. |
| :------------ | :---------------- | :------- | :--------------- |
| **Super Admin** | `admin@hostel.com`   | `123456` | *Administrator Access* |
| **Warden**      | `warden@hostel.com`  | `123456` | *Staff Management*     |
| **Student**     | `student@hostel.com` | `123456` | `ST-2024-001`          |

---

## 🚀 Future Enhancements

### 1. 📲 QR Code Integration (High Priority)
*   Integrate the **Digital ID QR** with the **Gate Management** module. The warden should be able to scan a student's phone to automatically log them "IN" or "OUT".

### 2. 📧 Automated Notifications
*   Integrate an Email/SMS API. When a student's fee is overdue or an announcement is made, the system should send an auto-alert.

### 3. 📊 Advanced Analytics
*   Add a "Performance Report" for the Mess (wastage tracking) and a "Financial Forecast" chart on the dashboard.

### 4. 🗂 Bulk Data Import
*   Add an "Import from Excel" feature in **Manage Users** to quickly onboard hundreds of students at the start of a semester.

### 5. 🛡 Security Hardening
*   Implement **Two-Factor Authentication (2FA)** for Admin logins and **File Upload Validation** (MIME check) for receipts to prevent malicious scripts.

### 6. 📅 Event Calendar
*   A module to manage hostel events, sports weeks, or maintenance schedules visible on the dashboard.

---
**Current Version:** 1.5 (Stable)  
**Developed By:** [Your Team Name]  
**Theme:** Modern Desktop ERP (Navy/Green)