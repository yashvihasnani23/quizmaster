# QuizMaster

A full-stack online quiz platform where users take timed quizzes, create their own,
and compete on leaderboards. Includes an admin panel for managing content and users.

**Live demo:** [your link] | **Demo login:** [see below]

![Home](screenshots/home.png)

## Features

**Users**
- Register and log in (bcrypt password hashing)
- Browse quizzes and filter by category
- Timed quizzes with auto-submit when time runs out
- Server-side scoring with a question-by-question answer review
- Personal attempt history and average score
- Global and per-quiz leaderboards (best attempt per user)
- Create, edit, publish/unpublish, and delete your own quizzes

**Admin**
- Stats dashboard (users, quizzes, attempts, average score, popular quizzes)
- Manage categories, all quizzes, and users (change roles, delete)

## Tech Stack

PHP 8 (PDO) | MySQL | HTML, CSS, JavaScript | Bootstrap 5 | Apache (XAMPP)

## Security

- Prepared statements on every query (SQL injection)
- Output escaping with `htmlspecialchars` (XSS)
- CSRF tokens on all POST requests
- Passwords hashed with `password_hash` / `password_verify`
- Login rate limiting (5 failed attempts per email in 15 minutes)
- Session hardening: HttpOnly and SameSite cookies, ID regeneration on login
- Role checks on every admin page; ownership checks on every edit and delete
- Quiz answers never sent to the browser; scoring and timing done on the server

## Database

Six main tables: `users`, `categories`, `quizzes`, `questions`, `attempts`,
`attempt_answers`, plus `login_attempts` for rate limiting.

![Schema](screenshots/schema.png)  <!-- phpMyAdmin > Designer view screenshot -->

## Setup (local)

1. Install [XAMPP](https://www.apachefriends.org) and start Apache and MySQL.
2. Clone this repo into `C:\xampp\htdocs\quizmaster`.
3. In phpMyAdmin, create a database named `quizmaster` and import `database.sql`.
4. Copy `includes/config.sample.php` to `includes/config.php` and adjust it if needed.
5. Open `http://localhost/quizmaster/`, register an account, then make it an admin:
   `UPDATE users SET role='admin' WHERE email='you@example.com';`
6. Assign the sample quizzes to your account: `UPDATE quizzes SET user_id = <your user id>;`

## Screenshots

| Quizzes | Taking a quiz | Result |
|---|---|---|
| ![](screenshots/quizzes.png) | ![](screenshots/quiz.png) | ![](screenshots/result.png) |

## Known limitations / future work

- Email verification and password reset
- Logout via GET link (should be POST)
- Question randomization and quiz time-per-question
- Automated tests

## Author

Yashvi Hasnani 
