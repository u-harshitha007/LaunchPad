# LaunchPad

**Track Skills. Build Projects. Launch Careers.**

LaunchPad is a student career operating system that helps you manage skills, projects, certifications, internships, and career growth — all from a premium dark dashboard.

![Tech Stack](https://img.shields.io/badge/HTML5-E34F26?style=flat&logo=html5&logoColor=white)
![Tech Stack](https://img.shields.io/badge/CSS3-1572B6?style=flat&logo=css3&logoColor=white)
![Tech Stack](https://img.shields.io/badge/JavaScript-F7DF1E?style=flat&logo=javascript&logoColor=black)
![Tech Stack](https://img.shields.io/badge/PHP-777BB4?style=flat&logo=php&logoColor=white)
![Tech Stack](https://img.shields.io/badge/MySQL-4479A1?style=flat&logo=mysql&logoColor=white)

## Features

- **Authentication** — Register, login, logout with password hashing and PHP sessions
- **Dashboard** — Career progress ring, stat cards, charts, and activity feed
- **Skills** — Track categories, proficiency levels, and progress bars
- **Projects** — Portfolio with technologies, GitHub links, and status tracking
- **Certifications** — Store credentials with PDF/image upload support
- **Internships** — Application pipeline tracker with status and notes
- **Profile** — Personal info, college details, about section, and photo upload

## Requirements

- PHP 8.0+ (with `mysqli` and `fileinfo` extensions)
- MySQL 8.0+
- A local web server (XAMPP, WAMP, Laragon, or PHP built-in server)

## Setup Instructions

### 1. Clone the repository

```bash
git clone https://github.com/u-harshitha007/LaunchPad.git
cd LaunchPad
```

### 2. Configure the database

1. Start MySQL (via XAMPP Control Panel, Windows Services, or your preferred method).
2. Import the schema:

```bash
mysql -u root -p < database/launchpad.sql
```

Or use phpMyAdmin: create database `launchpad`, then import `database/launchpad.sql`.

### 3. Configure database credentials

Edit `includes/db.php` if your MySQL credentials differ from the defaults:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');      // Your MySQL password
define('DB_NAME', 'launchpad');
```

### 4. Set upload permissions

Ensure the `uploads/` directory is writable by the web server:

```bash
# Linux/macOS
chmod -R 755 uploads/
```

On Windows with XAMPP, this is usually automatic.

### 5. Run the application

**Option A — XAMPP / WAMP / Laragon**

Copy or symlink the project into your web root (`htdocs`, `www`, etc.) and visit:

```
http://localhost/LaunchPad/
```

**Option B — PHP built-in server**

```bash
php -S localhost:8000
```

Then open `http://localhost:8000` in your browser.

### 6. Create your account

Visit the app, click **Create Account**, register, and start tracking your career journey.

## Project Structure

```
LaunchPad/
├── index.php              # Entry point (redirects to login/dashboard)
├── login.php              # User login
├── register.php           # User registration
├── dashboard.php          # Main dashboard
├── skills.php             # Skills CRUD
├── projects.php           # Projects CRUD
├── certifications.php     # Certifications CRUD + file upload
├── internships.php        # Internship tracker
├── profile.php            # Profile management
├── logout.php             # Session destroy
├── assets/
│   ├── css/
│   │   ├── style.css      # Global + auth styles
│   │   └── dashboard.css  # Dashboard layout + components
│   └── js/
│       └── main.js        # Sidebar, modals, charts
├── uploads/               # User-uploaded files
├── includes/
│   ├── db.php             # Database connection
│   ├── auth.php           # Authentication helpers
│   ├── functions.php      # Shared utilities
│   ├── layout.php         # Dashboard page wrapper
│   └── sidebar.php        # Navigation sidebar
└── database/
    └── launchpad.sql      # MySQL schema
```

## Database Schema

| Table            | Description                          |
|------------------|--------------------------------------|
| `users`          | Student accounts and profile data    |
| `skills`         | Skills with category and progress    |
| `projects`       | Portfolio projects                   |
| `certifications` | Credentials with optional file proof |
| `internships`    | Application tracking                 |

All child tables use `FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE`.

## Design System

| Token            | Value     | Usage                |
|------------------|-----------|----------------------|
| Background       | `#0B0F14` | Deep charcoal base   |
| Cards            | `#151A21` | Elevated surfaces    |
| Emerald accent   | `#10B981` | Success states       |
| Blue highlight   | `#60A5FA` | Interactive elements |
| Typography       | Inter     | Clean, modern text   |

## Security Notes

- Passwords are hashed with `password_hash()` (bcrypt)
- All database queries use prepared statements
- User input is escaped with `htmlspecialchars()` on output
- File uploads are validated by MIME type and size
- Session-based authentication protects all dashboard pages

## License

This project is open source for educational purposes.
