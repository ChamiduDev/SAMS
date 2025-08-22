# Smart Academic Management System (SAMS)

SAMS is a comprehensive, web-based application designed to manage the academic operations of an educational institution. It provides a centralized platform for students, teachers, and administrators to manage courses, subjects, attendance, exams, fees, and more.

## Features

*   **Student Management:** Manage student information, including personal details, course enrollment, and academic performance.
*   **Course and Subject Management:** Create and manage courses and subjects, and assign subjects to courses.
*   **Attendance Tracking:** Record and monitor student attendance.
*   **Exam and Grade Management:** Schedule exams, record exam results, and manage the grading system.
*   **Fee Management:** Manage fee structures, assign fees to students, and track payments.
*   **Role-Based Access Control:** A flexible role and permission system to control access to different features.
*   **Notifications:** A notification system to keep users informed about important events and updates.
*   **Dashboard:** A comprehensive dashboard that provides an overview of the system for different user roles.

## Database Schema

The application uses a MySQL database with the following main tables:

*   `students`: Stores student information.
*   `courses`: Stores course information.
*   `subjects`: Stores subject information.
*   `attendance`: Stores student attendance records.
*   `exams`: Stores exam information.
*   `exam_results`: Stores student exam results.
*   `fees`: Stores student fee information.
*   `users`: Stores user accounts.
*   `roles`: Stores user roles (e.g., admin, teacher, student).
*   `permissions`: Stores permissions for different actions.
*   `role_permissions`: Maps roles to permissions.

## Installation

1.  **Clone the repository:**
    ```bash
    git clone https://github.com/ChamiduDev/SAMS.git
    ```
2.  **Import the database:**
    *   Create a new MySQL database named `sams_db`.
    *   Import the `sams_db.sql` file into the `sams_db` database.
3.  **Configure the application:**
    *   Open the `config/database.php` file and update the database credentials if necessary.
4.  **Run the application:**
    *   Place the project files in your web server's document root (e.g., `htdocs` for XAMPP).
    *   Open your web browser and navigate to `http://localhost/`.

## Usage

1.  **Login:**
    *   Access the application in your web browser.
    *   You will be redirected to the login page.
    *   Use the following default credentials to log in as an administrator:
        *   **Username:** SuperAdmin
        *   **Password:** admin123
2.  **Dashboard:**
    *   After logging in, you will be redirected to the dashboard.
    *   The dashboard provides an overview of the system and quick access to different features.
3.  **Manage Students, Courses, etc.:**
    *   Use the sidebar navigation to access the different modules of the application.
    *   You can add, edit, delete, and view information depending on your user role and permissions.

## Roles and Permissions

The application has a role-based access control (RBAC) system that allows you to control what users can see and do. There are three default roles:

*   **Admin:** Has full access to all features of the application.
*   **Teacher:** Can manage courses, subjects, attendance, and exams for their assigned students.
*   **Student:** Can view their own academic information, including courses, subjects, attendance, and exam results.

You can create new roles and assign specific permissions to them in the "Roles" section of the application.
