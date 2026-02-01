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

## Getting Started (Local Dev)
If you want to run this on your machine to test it out, here's the quickest way:

1.  **Clone/Download**: Drop this whole folder into your `htdocs` directory (if you're using XAMPP).
2.  **Database Setup**:
    - Open up phpMyAdmin and create a new database named `fitness_club`.
    - Import the files from the `sql/` folder. Start with `schema.sql` to build the tables, then `sample-data.sql` if you want some dummy users to play with.
3.  **Config**:
    - Check `config/config.php`.
    - Make sure `BASE_URL` matches your local folder path (e.g., `http://localhost/ASSESMENT/fitness`).
4.  **Launch**:
    - Visit `http://localhost/ASSESMENT/fitness/public/index.php` in your browser.

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