# Fitness Club Management System

This is a web application designed to manage a gym or fitness club. It handles member registrations, trainer profiles, attendance logs, and workout plans.

## How to get it running
1.  Move the project folder into your XAMPP `htdocs` directory.
2.  Open **phpMyAdmin** and create a database called `fitness_club`.
3.  Go to the `sql/` folder in this project and import `schema.sql` first, then import `sample-data.sql`.
4.  Check `config/config.php` to make sure the `BASE_URL` is pointing to the right folder on your localhost.
5.  Open your browser and go to `http://localhost/fitness/public/index.php`.

## Login Information
You can use these accounts to test the system:
- **Admin**: username `admin`, password `admin123`
- **Trainer**: username `trainer_john`, password `trainer123`
- **Member**: username `john_doe`, password `member123`

## Main Features
- **Dashboard**: Shows quick stats for members, trainers, and revenue.
- **Member Management**: Add, edit, and view member profiles and their membership status.
- **Attendance**: Manual check-in system for members coming into the gym.
- **Workouts**: Create workout plans and assign them to specific members.
- **Nutrition**: Log daily meals and see simple nutrition reports.
- **Payments**: Track membership payments and revenue.
- **Profile**: Users can edit their own info or change their passwords.

## Tech Used
- PHP for logic
- MySQL for data
- Standard CSS for the UI
- FontAwesome for icons

## File Structure
- `config/`: Database and site settings
- `public/`: All the main pages users see
- `includes/`: Common files like headers, footers, and auth functions
- `assets/`: Styling and javascript
- `sql/`: Database export files
2. `members` - Member profiles
3. `trainers` - Trainer profiles
4. `memberships` - Membership plans
5. `attendance` - Gym check-ins
6. `workout_plans` - Exercise plans
8. `progress_logs` - Fitness progress
9. `payments` - Payment records

## Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)

### Setup Steps

1. **Clone/Download the project**