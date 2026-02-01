# Fitness Club Management System
This is a simple but powerful web application I built to help gym owners and staff manage their daily operations. It handles everything from member registrations to tracking workout plans—basically replacing potential piles of paperwork with a clean dashboard.

## What is this?
I created this project to streamline how a typical fitness club operates. Instead of tracking payments or attendance in spreadsheets (or worse, notebooks), this system keeps it all in one place. It's built with PHP and standard web technologies, so it's lightweight and easy to deploy on any standard hosting environment or local XAMPP setup.

## Features I'm Proud Of
Here's what the system can do right now:
- **Dashboard**: A quick-glance view of how the business is doing (active members, monthly revenue, etc.).
- **Staff Access**: Different logins for Admins and Trainers so everyone sees only what they need to see.
- **Member Management**: specific profiles for every member, tracking their personal info and membership status.
- **Attendance**: A manual check-in feature to keep logs of who visited the gym and when.
- **Workout Planning**: Trainers can create custom workout routines and assign them directly to members.
- **Nutrition Tracking**: A space for logging meals to help members stay on top of their diet goals.

## Live Demo
**Access the live application here:**
[https://student.heraldcollege.edu.np/~np03cy4a240041/ASSESMENT/fitness/public/login.php]

## Getting Started (Local Dev)
If you want to run this locally on your machine:

1.  **Clone/Download**: Drop this folder into your `htdocs` directory (for XAMPP).
2.  **Database Setup**:
    - Create a database in phpMyAdmin (e.g., `fitness_club`).
    - Import `sql/schema.sql` first, then `sql/sample-data.sql`.
    - **Important**: Update `config/db.php` with your database name, username, and password.
3.  **Config**:
    - Update `config/config.php`: Set `BASE_URL` to your local path (e.g., `http://localhost/ASSESMENT/fitness`).
4.  **Launch**:
    - Visit the `public/index.php` page in your browser.

## Default Logins
I've set up a few accounts so you don't have to register from scratch while testing:

| Role | Username | Password |
| :--- | :--- | :--- |
| **Admin** | `admin` | `admin123` |
| **Trainer** | `trainer_john` | `trainer123` |
| **Member** | `john_doe` | `member123` |

## Tech Stack
Nothing too fancy, just reliable tools:
- **PHP** (Core logic)
- **MySQL** (Database)
- **HTML5/CSS3** (Frontend interface)
- **JavaScript** (Interactivity)

## Notes
Feel free to poke around the code in the `public` folder to see how the pages are stitched together, or check `includes/functions.php` for the backend logic.

Enjoy! 🏋️‍♂️