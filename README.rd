*** FOLLOW THESE STEPS EXACTLY TO RUN YOUR PROJECT ***

GOAL: You will run a local server (XAMPP) to host your PHP project and your MySQL database. You will use "phpMyAdmin" to show your database tables.

STEP 1: Install XAMPP

Go to this website: https://www.apachefriends.org/index.html

Download and install XAMPP. It's a single, free program.

Open the "XAMPP Control Panel".

Click "Start" next to Apache.

Click "Start" next to MySQL.
(If it's green, you are good.)

STEP 2: Create Your Database (Demo Part 1)

Open your web browser (Chrome, Firefox).

Go to this URL: http://localhost/phpmyadmin/

This is phpMyAdmin. This is what you will show your professor to demo the database contents.

Click on the "Databases" tab at the top.

Under "Create database", type the name: ctms_db

Click "Create".

Your new ctms_db database will appear on the left. Click on it.

Click the "Import" tab at the top.

Click "Choose File" and select the database.sql file you downloaded from me.

Scroll down and click "Go".

DONE! It will run the SQL script. You will see all 8 tables (TEAM, PLAYER, MATCH, UMPIRE, TEAM_MANAGEMENT, CAPTAIN, PLAYS, UMPIRED_BY) on the left, full of data.

STEP 3: Run Your Web Project

Find your XAMPP installation folder. (Usually C:\xampp)

Open the folder named htdocs.

Create a new folder inside htdocs named ctms.

Put your project files (index.php, style.css) into this C:\xampp\htdocs\ctms folder.

Open your web browser.

Go to this URL: http://localhost/ctms/

Your project will be running.

YOUR DEMO PLAN:

Show your professor the running project at http://localhost/ctms/.

Show the "Points Table", "All Players", "Matches", and the new "Umpires" and "Team Staff" tables.

Use the "Add New Player" form to add a new player (e.g., "Test Player").

Go to the http://localhost/phpmyadmin/ tab.

Show them all 8 tables, clicking each one. Point out that the tables TEAM_MANAGEMENT and CAPTAIN are exactly as specified in the relational model.

Click on the PLAYER table.

Show them the "Test Player" you just added inside the database table.

This proves your project is 100% matching the report and fully functional.

Good luck!