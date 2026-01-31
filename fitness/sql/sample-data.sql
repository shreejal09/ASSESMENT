-- Sample Data for Fitness Club Management System
-- Run this AFTER creating the tables

-- Insert sample users with CORRECT password hashes
-- Passwords: admin123, trainer123, member123

INSERT INTO users (username, email, password_hash, role, status) VALUES
('admin', 'admin@fitnessclub.com', '$2y$10$FSQ4e4tpzHyWuwmS1wBy.OA4apMYKqxYDshbK2RWZFJJB7zeEarqO', 'admin', 'active'),
('trainer_john', 'john.trainer@fitnessclub.com', '$2y$10$6EwbH/XLMJbnc7cSX7Bn7.7P68Xj58sDAPaDZItJjphfpsRVkMWKu', 'trainer', 'active'),
('trainer_sarah', 'sarah.trainer@fitnessclub.com', '$2y$10$6EwbH/XLMJbnc7cSX7Bn7.7P68Xj58sDAPaDZItJjphfpsRVkMWKu', 'trainer', 'active'),
('john_doe', 'john.doe@email.com', '$2y$10$wXWGI.xzuVAsTUdvnda5a.hiNxjx37lbp8h5uJe1.1WRGeiKECZS6', 'member', 'active'),
('lisa_smith', 'lisa.smith@email.com', '$2y$10$wXWGI.xzuVAsTUdvnda5a.hiNxjx37lbp8h5uJe1.1WRGeiKECZS6', 'member', 'active'),
('david_wilson', 'david.wilson@email.com', '$2y$10$wXWGI.xzuVAsTUdvnda5a.hiNxjx37lbp8h5uJe1.1WRGeiKECZS6', 'member', 'active'),
('emily_brown', 'emily.brown@email.com', '$2y$10$wXWGI.xzuVAsTUdvnda5a.hiNxjx37lbp8h5uJe1.1WRGeiKECZS6', 'member', 'active');

-- Insert sample members
INSERT INTO members (user_id, first_name, last_name, email, phone, date_of_birth, gender, address, emergency_contact, join_date, status, fitness_goals, height_cm, weight_kg) VALUES
(4, 'John', 'Doe', 'john.doe@email.com', '(555) 123-4567', '1990-05-15', 'Male', '123 Main St, Cityville', 'Jane Doe (555) 987-6543', '2024-01-15', 'Active', 'Lose 10kg, build muscle', 180.5, 85.2),
(5, 'Lisa', 'Smith', 'lisa.smith@email.com', '(555) 234-5678', '1992-08-22', 'Female', '456 Oak Ave, Townsville', 'John Smith (555) 876-5432', '2024-02-20', 'Active', 'Improve endurance, tone muscles', 165.0, 62.5),
(6, 'David', 'Wilson', 'david.wilson@email.com', '(555) 345-6789', '1985-11-30', 'Male', '789 Pine Rd, Villageton', 'Sarah Wilson (555) 765-4321', '2024-03-10', 'Active', 'Build strength, increase flexibility', 175.0, 78.0),
(7, 'Emily', 'Brown', 'emily.brown@email.com', '(555) 456-7890', '1995-03-18', 'Female', '321 Maple Dr, Hamlet City', 'Robert Brown (555) 654-3210', '2024-04-05', 'Inactive', 'Weight loss, improve cardio', 170.0, 70.0);

-- Insert sample trainers
INSERT INTO trainers (user_id, full_name, email, phone, specialization, certification, experience_years, hourly_rate, availability, bio) VALUES
(2, 'John Trainer', 'john.trainer@fitnessclub.com', '(555) 111-2222', 'Strength Training, Bodybuilding', 'NASM Certified, CPR Certified', 8, 60.00, 'Full-time', 'Specialized in strength training and bodybuilding with 8 years of experience. Helped numerous clients achieve their fitness goals.'),
(3, 'Sarah Coach', 'sarah.trainer@fitnessclub.com', '(555) 222-3333', 'Yoga, Pilates, Flexibility', 'RYT 500, ACE Certified', 5, 50.00, 'Part-time', 'Yoga and flexibility expert focused on holistic wellness and injury prevention.');

-- Insert sample memberships (using relative dates to ensure they are Active today)
INSERT INTO memberships (member_id, plan_name, plan_type, price, start_date, expiry_date, payment_status, payment_method, auto_renew) VALUES
(1, 'Premium', 'Monthly', 79.99, DATE_SUB(CURRENT_DATE, INTERVAL 15 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 15 DAY), 'Paid', 'Credit Card', TRUE),
(2, 'Standard', 'Monthly', 59.99, DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 20 DAY), 'Paid', 'Debit Card', TRUE),
(3, 'Student', 'Monthly', 39.99, DATE_SUB(CURRENT_DATE, INTERVAL 5 DAY), DATE_ADD(CURRENT_DATE, INTERVAL 25 DAY), 'Pending', 'Cash', FALSE),
(1, 'Premium', 'Monthly', 79.99, DATE_SUB(CURRENT_DATE, INTERVAL 45 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 15 DAY), 'Paid', 'Credit Card', TRUE),
(2, 'Standard', 'Monthly', 59.99, DATE_SUB(CURRENT_DATE, INTERVAL 40 DAY), DATE_SUB(CURRENT_DATE, INTERVAL 10 DAY), 'Overdue', 'Debit Card', TRUE);

-- Insert sample attendance records (using relative dates)
INSERT INTO attendance (member_id, check_in, check_out, workout_type, trainer_id, duration_minutes, notes) VALUES
(1, CONCAT(CURRENT_DATE, ' 08:30:00'), CONCAT(CURRENT_DATE, ' 10:00:00'), 'Weight Training', 1, 90, 'Focused on chest and triceps'),
(2, CONCAT(DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY), ' 09:00:00'), CONCAT(DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY), ' 10:30:00'), 'Yoga', 2, 90, 'Beginner yoga session'),
(3, CONCAT(DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY), ' 17:00:00'), CONCAT(DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY), ' 18:30:00'), 'Cardio', 1, 90, 'Treadmill and cycling'),
(1, CONCAT(DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), ' 08:00:00'), CONCAT(DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), ' 09:30:00'), 'Weight Training', 1, 90, 'Leg day'),
(2, CONCAT(DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), ' 18:00:00'), CONCAT(DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY), ' 19:00:00'), 'Pilates', 2, 60, 'Core strengthening'),
(1, CONCAT(DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), ' 07:30:00'), CONCAT(DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), ' 08:45:00'), 'Cardio', NULL, 75, 'Morning run'),
(3, CONCAT(DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), ' 16:00:00'), CONCAT(DATE_SUB(CURRENT_DATE, INTERVAL 3 DAY), ' 17:30:00'), 'Strength Training', 1, 90, 'Upper body focus');

-- Insert sample workout plans
INSERT INTO workout_plans (plan_name, description, difficulty_level, duration_weeks, target_area, equipment_needed, created_by, is_active) VALUES
('Beginner Strength Program', '4-week strength program for beginners focusing on fundamental exercises', 'Beginner', 4, 'Full Body', 'Barbell, Dumbbells, Bench', 1, TRUE),
('Intermediate Bodybuilding', '8-week bodybuilding program for intermediate lifters', 'Intermediate', 8, 'Muscle Hypertrophy', 'Full gym equipment', 2, TRUE),
('Yoga for Flexibility', '6-week yoga program to improve flexibility and relaxation', 'Beginner', 6, 'Flexibility, Balance', 'Yoga mat', 3, TRUE),
('Fat Loss HIIT', '4-week high-intensity interval training program for fat loss', 'Intermediate', 4, 'Full Body', 'None required', 1, TRUE),
('Advanced Powerlifting', '12-week powerlifting program for advanced athletes', 'Advanced', 12, 'Strength', 'Barbell, Power Rack', 2, TRUE);

-- Insert sample workout exercises for plan 1 (Beginner Strength)
INSERT INTO workout_exercises (workout_plan_id, exercise_name, sets, reps, rest_seconds, instructions, muscle_group, day_number) VALUES
(1, 'Barbell Squats', 3, '8-10', 90, 'Keep back straight, descend until thighs parallel', 'Legs', 1),
(1, 'Bench Press', 3, '8-10', 90, 'Lower bar to chest, press up explosively', 'Chest', 1),
(1, 'Bent Over Rows', 3, '8-10', 90, 'Bend at waist, pull bar to lower chest', 'Back', 1),
(1, 'Overhead Press', 3, '8-10', 90, 'Press weight overhead, keep core tight', 'Shoulders', 2),
(1, 'Lat Pulldowns', 3, '10-12', 90, 'Pull bar to upper chest, squeeze lats', 'Back', 2),
(1, 'Leg Press', 3, '10-12', 90, 'Control descent, push through heels', 'Legs', 2),
(1, 'Bicep Curls', 3, '10-12', 60, 'Keep elbows close to body', 'Arms', 3),
(1, 'Tricep Extensions', 3, '10-12', 60, 'Keep elbows stationary', 'Arms', 3),
(1, 'Plank', 3, '30-60 sec', 45, 'Keep body straight, engage core', 'Core', 3);

-- Insert sample workout exercises for plan 2 (Intermediate Bodybuilding)
INSERT INTO workout_exercises (workout_plan_id, exercise_name, sets, reps, rest_seconds, instructions, muscle_group, day_number) VALUES
(2, 'Incline Dumbbell Press', 4, '8-10', 75, '30 degree incline, control descent', 'Chest', 1),
(2, 'Flat Barbell Press', 4, '8-10', 75, 'Focus on explosive movement', 'Chest', 1),
(2, 'Cable Flyes', 3, '12-15', 60, 'Keep slight bend in elbows', 'Chest', 1),
(2, 'Pull-ups', 4, '6-10', 90, 'Full range of motion', 'Back', 2),
(2, 'T-bar Rows', 4, '8-10', 75, 'Squeeze shoulder blades', 'Back', 2),
(2, 'Seated Rows', 3, '10-12', 60, 'Focus on contraction', 'Back', 2);

-- Insert sample workout exercises for plan 3 (Yoga)
INSERT INTO workout_exercises (workout_plan_id, exercise_name, sets, reps, rest_seconds, instructions, muscle_group, day_number) VALUES
(3, 'Sun Salutation', 3, '5 rounds', 30, 'Flow through poses, focus on breath', 'Full Body', 1),
(3, 'Warrior I', 3, '30 seconds each side', 15, 'Hold pose, engage core', 'Legs', 1),
(3, 'Warrior II', 3, '30 seconds each side', 15, 'Keep hips open', 'Legs', 1),
(3, 'Downward Dog', 3, '1 minute', 30, 'Press hips up, heels down', 'Full Body', 1),
(3, 'Tree Pose', 3, '30 seconds each side', 15, 'Focus on balance', 'Legs, Balance', 2),
(3, 'Bridge Pose', 3, '30 seconds', 15, 'Lift hips, engage glutes', 'Back, Glutes', 2);

-- Insert sample member workout assignments
INSERT INTO member_workouts (member_id, workout_plan_id, assigned_by, start_date, end_date, progress_notes, status) VALUES
(1, 2, 2, '2024-01-01', '2024-02-26', 'Doing well, increasing weights weekly', 'Active'),
(2, 3, 3, '2024-01-01', '2024-02-12', 'Improving flexibility, attending regularly', 'Active'),
(3, 1, 2, '2024-01-01', '2024-01-29', 'Learning proper form, making progress', 'Completed'),
(1, 4, 2, '2024-02-01', '2024-03-01', 'Starting HIIT program', 'Active'),
(2, 1, 3, '2024-02-01', '2024-03-01', 'Beginner strength training', 'Active');

-- Insert sample nutrition logs
INSERT INTO nutrition_logs (member_id, meal_type, food_name, calories, protein_g, carbs_g, fat_g, serving_size, log_date, notes) VALUES
(1, 'Breakfast', 'Oatmeal with berries', 350, 12, 60, 5, '1 cup', '2024-01-15', 'Added almonds for extra protein'),
(1, 'Lunch', 'Grilled chicken salad', 450, 35, 20, 25, 'Large bowl', '2024-01-15', 'Light dressing'),
(1, 'Post-workout', 'Protein shake', 200, 30, 10, 5, '1 scoop', '2024-01-15', 'Whey protein with water'),
(2, 'Breakfast', 'Greek yogurt with honey', 250, 20, 25, 8, '1 cup', '2024-01-15', 'Fresh fruits added'),
(2, 'Lunch', 'Quinoa bowl with vegetables', 400, 15, 65, 12, 'Medium bowl', '2024-01-15', 'Mixed vegetables'),
(3, 'Breakfast', 'Egg white omelette', 180, 25, 3, 8, '3 eggs', '2024-01-15', 'With spinach and mushrooms'),
(1, 'Dinner', 'Salmon with roasted vegetables', 500, 40, 30, 25, 'Medium plate', '2024-01-15', 'Baked salmon'),
(2, 'Snack', 'Apple with peanut butter', 200, 5, 25, 10, '1 apple, 1 tbsp', '2024-01-15', 'Healthy snack'),
(1, 'Pre-workout', 'Banana and coffee', 150, 2, 35, 1, '1 banana', '2024-01-16', 'Quick energy boost');

-- Insert sample progress logs
INSERT INTO progress_logs (member_id, weight_kg, body_fat_percentage, chest_cm, waist_cm, hips_cm, biceps_cm, thighs_cm, notes, logged_by) VALUES
(1, 85.2, 18.5, 105.0, 88.0, 102.0, 35.5, 58.0, 'Initial measurements', 2),
(2, 62.5, 25.0, 92.0, 74.0, 98.0, 28.0, 52.0, 'Starting point', 3),
(3, 78.0, 20.0, 100.0, 85.0, 100.0, 33.0, 56.0, 'Initial assessment', 2),
(1, 84.5, 18.0, 106.0, 87.0, 101.5, 36.0, 58.5, '2 weeks progress - losing fat, gaining muscle', 2),
(2, 62.0, 24.5, 92.5, 73.5, 97.5, 28.5, 52.5, '1 month progress - improved tone', 3),
(3, 77.5, 19.5, 101.0, 84.0, 99.5, 33.5, 57.0, '3 weeks progress', 2);

-- Insert sample payments
INSERT INTO payments (membership_id, amount, payment_date, payment_method, transaction_id, status, processed_by, notes) VALUES
(1, 79.99, '2024-01-01', 'Credit Card', 'TXN001', 'Completed', 1, 'Monthly membership fee'),
(2, 59.99, '2024-01-01', 'Debit Card', 'TXN002', 'Completed', 1, 'Standard plan payment'),
(3, 39.99, '2024-01-01', 'Cash', 'TXN003', 'Pending', NULL, 'Student plan - pending'),
(4, 79.99, '2024-02-01', 'Credit Card', 'TXN004', 'Completed', 1, 'Auto-renewal payment'),
(5, 59.99, '2024-02-01', 'Debit Card', 'TXN005', 'Overdue', NULL, 'Payment overdue - reminder sent');

-- Insert sample CSRF tokens
INSERT INTO csrf_tokens (token, user_id, expires_at) VALUES
('demo_csrf_token_123456', 1, '2024-12-31 23:59:59'),
('demo_csrf_token_789012', 2, '2024-12-31 23:59:59'),
('demo_csrf_token_345678', 4, '2024-12-31 23:59:59');

-- Insert sample audit logs
INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent) VALUES
(1, 'user_login', 'users', 1, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(1, 'member_added', 'members', 1, '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(2, 'attendance_checkin', 'attendance', 1, '192.168.1.101', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'),
(3, 'workout_assigned', 'member_workouts', 1, '192.168.1.102', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36'),
(4, 'nutrition_logged', 'nutrition_logs', 1, '192.168.1.103', 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/537.36');

