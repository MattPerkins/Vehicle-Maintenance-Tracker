# Vehicle Maintenance Tracker - Phase 1

SDC480 capstone project built with PHP, MySQL, HTML, and CSS.

## Week 2 features
- Database-backed login using `password_verify()`
- PHP sessions for protected pages
- At least five connected pages with navigation
- Five-table relational MySQL database
- Vehicle, maintenance-history, service-location, dashboard, and account pages
- Prepared statements and HTML escaping for basic security

## Local setup with XAMPP
1. Copy this project folder into `C:\xampp\htdocs\vehicle-maintenance-tracker`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Open `http://localhost/phpmyadmin`.
4. Choose **Import** and import `database/schema_local.sql`.
5. Open `http://localhost/vehicle-maintenance-tracker/`.
6. Log in with:
   - Email: `student@example.com`
   - Password: `Student123!`

## Cloud hosting
1. Create a PHP/MySQL site with your chosen host.
2. Create the host database and import `database/schema_tables_only.sql` using the host's phpMyAdmin.
3. Edit `config/db.php` with the host's database server, database name, username, and password.
4. Upload the project files to the site's public web directory.
5. Test the login and every navigation link from another browser/device.

## GitHub Phase 1
Upload all project files except any private production credentials. If your instructor asks for a tag named `Phase #1`, note that Git tag names cannot contain spaces. If GitHub rejects that exact tag, use `Phase-1` as the tag name and `Phase #1` as the release title, then follow your course guide/instructor preference.

Before making a public repository, do not commit a `config/db.php` file containing real cloud database passwords. Use placeholder credentials in GitHub and keep the live credentials only on the hosting server.
