# Universal Hostel ERP - Modern Management System

A full-fledged, professional Desktop-style Web Application designed for University Hostel Operations. Built with PHP (PDO), MySQL, and AdminLTE 4.

## 📂 Project Structure & Page Functionality

### 🛠 System Administration
*   **Manage Roles (`manage_roles.php`)**: Create and define system roles (Super Admin, Warden, Student, etc.).
*   **Manage Pages (`manage_pages.php`)**: Dynamic sidebar manager to control menu items, icons, and hierarchy.
*   **Access Matrix (`manage_access.php`)**: A permissions grid to toggle page access for different roles in real-time.
*   **System Settings (`manage_settings.php`)**: Global configuration for system name, currency, and hostel curfew times.

### 🏢 Hostel Operations
*   **Student Registration (`student_registration.php`)**: Dedicated enrollment form for adding new residents to the system.
*   **Manage Users (`manage_users.php`)**: Centralized CRUD for Students and Staff members with profile tracking.
*   **Manage Rooms (`manage_rooms.php`)**: Define buildings, room types (Student/Office), and capacity limits.
*   **Allocate Rooms (`allocate_rooms.php`)**: Assign students to specific beds with automated occupancy tracking.
*   **Mark Attendance (`mark_attendance.php`)**: Daily check-ins with leave status integration and Excel export.
*   **Gate Management (`gate_management.php`)**: IN/OUT logs with real-time curfew alerts and "Currently Outside" monitoring.
*   **Manage Mess (`manage_mess.php`)**: Weekly food menu management using a tagging system for breakfast, lunch, and dinner.

### 💰 Finance & Helpdesk
*   **Manage Fees (`manage_fees.php`)**: Assign fees, track partial payments, and monitor overdue balances.
*   **Manage Complaints (`manage_complaints.php`)**: Admin ticket system to resolve student issues with priority levels.
*   **Manage Leaves (`manage_leaves.php`)**: Professional workflow for approving or rejecting student leave applications.
*   **Manage Announcements (`manage_announcements.php`)**: Create digital notices with expiry dates and live layout previews.

### 📦 Inventory & Assets
*   **Inventory Stock (`manage_inventory.php`)**: Track consumable items like furniture, bulbs, or plumbing supplies.
*   **Trackable Assets (`manage_assets.php`)**: Register high-value equipment (Laptops, Chairs) with unique Tag IDs.
*   **Asset Allocation (`allocate_assets.php`)**: Issue specific assets to students or rooms and track return conditions.

### 🎓 Student Portal
*   **My Room (`my_room.php`)**: View current allocation details and roommate info.
*   **My Fees (`my_fees.php`)**: View due fees and upload payment receipts for admin verification.
*   **My Complaints (`my_complaints.php`)**: Lodge issues and track resolution status.
*   **Notice Board (`announcements.php`)**: Read official university announcements.
*   **Profile (`profile.php`)**: Manage personal details, change password, and view "Digital Student ID" (QR Code).

---

## 🔍 Missing Features (Identified Gaps)

1.  **Guardian Information**: The registration form tracks the student but lacks "Emergency Contact" or "Parent/Guardian" details which are critical for hostels.
2.  **Fee Ledger**: Currently, fees are per-entry. A "Total Ledger" or "Account Statement" for a student is missing.
3.  **Automatic Fee Generation**: There is no logic to automatically generate monthly "Mess Charges" or "Rent" for all active students.
4.  **Asset Audit Trail**: While we track current allocations, we don't have a history log of "Who used this chair before?" to track damages over time.
5.  **Leave Attachment**: Students cannot upload "Doctor's Notes" or "Permissions" when applying for leave.

---

## 🚀 Recommended Enhancements

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
**Developed By:** Universal Team  
**Theme:** Modern Desktop ERP (Navy/Green)