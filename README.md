Exam Portal

A web-based Online Examination and Student Test Analysis Platform built using PHP, MySQL, JavaScript, HTML, CSS, and Python Flask.

The platform provides functionality for managing colleges, courses, batches, students, tests, questions, submissions, evaluations, and student performance analysis.

📌 Project Overview

The Exam Portal is designed to simplify the process of conducting online examinations and analysing student performance.

The system supports:

- Admin management
- College management
- Course and batch management
- Student registration and management
- Online tests/examinations
- Multiple question types
- MCQ questions
- Coding questions
- Explanation/descriptive questions
- Student submissions
- Automatic and manual evaluation support
- Test timer
- Tab-switch monitoring
- Guest/QR-based test access
- Student performance analysis
- PCI (Performance/Competency Index) calculation
- Analytics and charts
- Notifications and system error reporting

---

🛠️ Technology Stack

Frontend

- HTML5
- CSS3
- JavaScript
- Bootstrap / CSS-based UI components

Backend

- PHP
- PHP PDO
- MySQL

Database

- MySQL 8.0+
- phpMyAdmin

Local Server

- XAMPP
- Apache
- MySQL

Analysis Service

- Python 3
- Flask
- MySQL Connector
- Gunicorn

Testing

- Playwright
- Node.js
- npm

---

💻 Requirements

Before setting up the project, make sure the following software is installed on your system.

1. XAMPP

Install XAMPP because the project uses PHP, Apache, and MySQL.

XAMPP provides:

- Apache Web Server
- PHP
- MySQL/MariaDB
- phpMyAdmin

Download XAMPP from the official website:

https://www.apachefriends.org/

After installation, open the XAMPP Control Panel.

Start:

Apache
MySQL

Both services should be running before opening the application.

---

2. PHP

PHP is required because the main application is written in PHP.

XAMPP already includes PHP, so a separate PHP installation is normally not required.

Check your PHP version:

php -v

The project uses modern PHP features, so use a recent PHP version compatible with your XAMPP installation.

---

3. MySQL

The application uses MySQL as its database.

XAMPP includes MySQL/MariaDB and phpMyAdmin.

The project database is:

test_platform

The database schema is available at:

sql/schema.sql

---

4. Python

Python is required for the PCI analysis API.

Install Python 3.x and verify:

python --version

or:

python3 --version

The Python service uses the dependencies listed in:

src/python/requirements.txt

Current Python dependencies include:

Flask 3.0.0
mysql-connector-python 8.2.0
gunicorn 21.2.0

---

5. Node.js and npm

Node.js and npm are required only if you want to run the automated Playwright tests.

Check installation:

node -v
npm -v

The project includes:

package.json

with Playwright configured for testing.

---

📥 Installation

Step 1: Clone the Repository

Clone the project using Git:

git clone https://github.com/03Hari-Krishna-Kumar-div/Exam_portel.git

Or download the repository as a ZIP file from GitHub and extract it.

---

📂 Step 2: Move the Project to XAMPP

Copy the project into the XAMPP "htdocs" directory.

For a default Windows XAMPP installation:

C:\xampp\htdocs\

The recommended folder structure for the current configuration is:

C:\xampp\htdocs\test-platform\

The project should look approximately like:

C:\xampp\htdocs\test-platform\
│
├── assets\
├── docs\
├── sql\
├── src\
│   ├── php\
│   │   ├── api\
│   │   ├── config\
│   │   ├── includes\
│   │   └── public\
│   │
│   └── python\
│       ├── analysis\
│       ├── app.py
│       └── requirements.txt
│
├── router.php
├── package.json
└── ...

«Important: The repository is named "Exam_portel", but the current PHP configuration expects the XAMPP folder/path to be "test-platform". If you keep the folder name as "Exam_portel", update the "BASE_URL" value in "src/php/config/db.php".»

---

🗄️ Step 3: Start XAMPP

Open:

XAMPP Control Panel

Start:

Apache
MySQL

Make sure both services show as running.

---

🗃️ Step 4: Create the Database

Open phpMyAdmin in your browser:

http://localhost/phpmyadmin/

Create a database named:

test_platform

Alternatively, the provided schema already contains:

CREATE DATABASE IF NOT EXISTS test_platform;

So you can import the schema directly.

---

📥 Step 5: Import the Database Schema

In phpMyAdmin:

1. Open "http://localhost/phpmyadmin/"
2. Select the "test_platform" database.
3. Click Import.
4. Select:

sql/schema.sql

5. Click Go.

The schema creates the required tables for:

- Admins
- Colleges
- Courses
- Batches
- Students
- Guest entries
- Tests
- Questions
- Submissions
- Student answers
- Tab-switch logs
- PCI records

The database schema uses MySQL 8.0+ and "utf8mb4".

---

🔐 Default Admin Login

The database schema contains a seeded administrator account.

Email: admin@testplatform.com
Password: admin123

For security, change the default administrator password after the first login.

«Do not use the default password in a production environment.»

---

⚙️ Step 6: Configure the PHP Database Connection

The database configuration file is:

src/php/config/db.php

The default local XAMPP configuration is:

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'test_platform');
define('DB_USER', 'root');
define('DB_PASS', '');

This means the application expects:

Host:     127.0.0.1
Port:     3306
Database: test_platform
Username: root
Password: empty

If your XAMPP MySQL installation uses a password, update:

define('DB_PASS', 'your_password');

---

🌐 Step 7: Configure the Project URL

The current "db.php" configuration uses:

/test-platform/src/php/public

for the Apache/XAMPP environment.

Therefore, if your project is located at:

C:\xampp\htdocs\test-platform

the application can be accessed through:

http://localhost/test-platform/src/php/public/

If you want to use a different project folder name, update the following value in:

src/php/config/db.php

For example:

define('BASE_URL', '/Exam_portel/src/php/public');

if the project is located at:

C:\xampp\htdocs\Exam_portel

Also make sure the corresponding asset path is correct.

---

🚀 Step 8: Run the PHP Application

After starting Apache and MySQL, open:

http://localhost/test-platform/src/php/public/

If you renamed the folder to "Exam_portel" and updated "BASE_URL", use:

http://localhost/Exam_portel/src/php/public/

---

🐍 Step 9: Set Up the Python PCI Analysis API

The project contains a Python Flask service for PCI calculation and analytics.

Python source:

src/python/app.py

Requirements:

src/python/requirements.txt

Navigate to the Python directory:

cd src/python

Create a virtual environment:

Windows

python -m venv venv

Activate it:

venv\Scripts\activate

Linux/macOS

python3 -m venv venv

Activate it:

source venv/bin/activate

---

📦 Step 10: Install Python Dependencies

Run:

pip install -r requirements.txt

The project currently requires:

flask==3.0.0
mysql-connector-python==8.2.0
gunicorn==21.2.0

---

▶️ Step 11: Start the Python API

From:

src/python

run:

python app.py

The Python service is configured to use:

http://127.0.0.1:5000

The service provides endpoints for:

GET  /health
POST /api/pci/calculate
GET  /api/pci/batch/<test_id>
GET  /api/pci/student/<student_id>
GET  /api/charts/test/<test_id>

The "/health" endpoint can be used to verify that the Python service is running.

Open:

http://127.0.0.1:5000/health

Expected response:

{
  "status": "ok",
  "service": "pci-analysis"
}

---

🔗 PHP + Python Communication

The PHP configuration contains the Python API URL:

define('PYTHON_API_URL', 'http://127.0.0.1:5000');

Therefore, when using the analysis functionality, make sure the Python Flask service is running on port:

5000

---

🧪 Automated Testing

The project contains Playwright-based automated tests.

Install Node.js dependencies from the project root:

npm install

Install Playwright browsers:

npx playwright install

Run tests:

npm test

Run tests with the browser visible:

npm run test:headed

View the Playwright test report:

npm run test:report

---

📁 Important Project Directories

Exam_portel/
│
├── assets/
│   └── css/
│
├── docs/
│
├── sql/
│   ├── schema.sql
│   ├── migration_college_wizard.sql
│   ├── migration_notifications.sql
│   ├── migration_otp.sql
│   ├── migration_softdelete.sql
│   ├── migration_stream_categories.sql
│   ├── migration_students_updated_at.sql
│   ├── migration_unverified.sql
│   └── seed_test_data.php
│
├── src/
│   ├── php/
│   │   ├── api/
│   │   ├── config/
│   │   │   ├── db.php
│   │   │   └── mail.php
│   │   ├── includes/
│   │   └── public/
│   │
│   └── python/
│       ├── analysis/
│       ├── app.py
│       └── requirements.txt
│
├── tests/
│
├── router.php
├── package.json
└── ...

---

🗄️ Database Migrations

Additional SQL migration files are available inside:

sql/

These include migrations for functionality such as:

- College wizard
- Notifications
- OTP
- Soft delete
- Stream categories
- Student timestamps
- Unverified users

If you are setting up a fresh installation, start with:

sql/schema.sql

Then apply additional migration files when required by the version of the application you are using.

---

🔧 Troubleshooting

Apache is not starting

If Apache does not start, another application may already be using port "80" or "443".

Check the XAMPP Control Panel and Apache configuration.

You can also configure Apache to use another port if necessary.

---

MySQL is not starting

Make sure another MySQL/MariaDB service is not already running on port:

3306

Check your XAMPP MySQL configuration.

---

Database Connection Error

If you see a database connection error, verify:

Host: 127.0.0.1
Port: 3306
Database: test_platform
Username: root
Password: your configured password

Check:

src/php/config/db.php

---

404 Error

If the application displays a 404 error, check:

1. Apache is running.
2. The project is inside "xampp/htdocs".
3. The project folder name is correct.
4. The URL matches the folder name.
5. "BASE_URL" in "src/php/config/db.php" matches the project path.

For the default configuration:

C:\xampp\htdocs\test-platform

use:

http://localhost/test-platform/src/php/public/

---

Python API Not Working

Check whether the Flask server is running:

python src/python/app.py

Then open:

http://127.0.0.1:5000/health

If the service does not start, reinstall the dependencies:

cd src/python
pip install -r requirements.txt

---

MySQL Connector Error in Python

Install the required connector:

pip install mysql-connector-python==8.2.0

---

Playwright Tests Not Working

Install dependencies:

npm install

Then install browsers:

npx playwright install

Run:

npm test

---

🔒 Security Notes

This project is intended primarily for development and educational/testing purposes.

Before deploying to production:

- Change the default admin password.
- Do not use an empty MySQL password.
- Use environment variables for sensitive credentials.
- Do not commit passwords or API secrets.
- Configure HTTPS.
- Restrict database access.
- Review PHP error handling.
- Configure secure session cookies.
- Validate and sanitize all user input.
- Use prepared statements for database queries.
- Configure the production Python server properly.

---

📝 Quick Setup Summary

For a quick local setup using XAMPP:

1. Install XAMPP

Install XAMPP and start:

Apache
MySQL

2. Copy the project

Place the project inside:

C:\xampp\htdocs\

Recommended:

C:\xampp\htdocs\test-platform\

3. Create the database

Open:

http://localhost/phpmyadmin/

Create/import:

test_platform

Import:

sql/schema.sql

4. Check database configuration

Open:

src/php/config/db.php

Make sure the local configuration is:

DB_HOST = 127.0.0.1
DB_PORT = 3306
DB_NAME = test_platform
DB_USER = root
DB_PASS = empty

5. Open the application

http://localhost/test-platform/src/php/public/

6. Set up Python analysis

cd src/python
python -m venv venv
venv\Scripts\activate
pip install -r requirements.txt
python app.py

Python API:

http://127.0.0.1:5000

7. Optional: Run automated tests

From the project root:

npm install
npx playwright install
npm test

---

👤 Default Admin Account

Email: admin@testplatform.com
Password: admin123

Change this password after the first login.

---

📚 Repository

GitHub Repository:

https://github.com/03Hari-Krishna-Kumar-div/Exam_portel

---

📄 License

This project is currently maintained as a development/academic project.

Refer to the repository for the latest source code, documentation, and project updates.
