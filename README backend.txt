## How to Run the Project

## Step 1: Download the Project Files  
1. Download the ZIP file of the project.  
2. Extract the ZIP file to your desired location on your computer.

---

## Step 2: Download and Import the Database 
1. Open phpMyAdmin in your web browser:  
   URL: `http://localhost/phpmyadmin`  
2. Create a new database named `edu`.  
3. Import the provided SQL file into the `edu` database:  
   - Go to the Import tab in phpMyAdmin.  
   - Select the SQL file and click Go.

---

## Step 3: Set Up Your Local Server
1. Ensure you have XAMPP or WAMP installed on your computer.  
2. Move the extracted project folder to your htdocs directory:  
   - Path for XAMPP:  
     `C:\xampp\htdocs\edu`

---

## Step 4: Run the Project
1. Start the Apache and MySQL services in XAMPP or WAMP.  
2. Open your web browser and go to:  
   `http://localhost/edu/common/login.php`  
3. The entry point for the project is `login.php`.

---

## Step 5: Login Credentials  

| Role    | Username | Password       |
|-------------------------------------|
| Admin   | jordon   | 2Ub3jkF@       |
| Teacher | mathan   | GlWf1zX@       |
| Student | keith    | @fuBTXS5       |

---------------------------------------------------------

## Technologies Used  

- PHP – Backend development  
- MySQL – Database management  
- AJAX – Asynchronous data handling  
- JSON – Data format  

---------------------------------------------------------

## Folder Structure 

edu/  
├── admin/  
│   ├── classroom_management.php/    # Create, update, and delete classroom venues and times.  
│   ├── course_management.php/       # Create, update, and delete courses.  
│   ├── enrollment_management.php/   # View receipts, approve enrollments, and send approval emails to students. 
│   ├── notice_management.php/       # Create, update, and delete notices.
│   └── user_management.php/         # Create, update, and delete users.  
│  
├── common/  
│   ├── login.php/                   # Secure login with account lock after 3 incorrect attempts.  
│   ├── forgot_password.php/         # Reset password by entering registered email.  
│   ├── profile.php/                 # Update personal details (password, profile pic, etc.).  
│   └── timetable.php/               # View today's class schedule (time and venue).  
│  
├── config/  
│   ├── db_config.php/               # Database configuration settings.   
│   ├── smtp_config.php/             # SMTP configuration for sending email.  
│ 
├── lib/  
│   ├── authlib.php/                 # Contains functions for managing user authentication and session handling.  
│   ├── compresslib.php/             # Manages file compression and optimization for uploads.
│   ├── courselib.php/               # Contains functions for managing courses.
│   ├── userlib.php/                 # Contains functions for managing users. 
│  
├── teacher/  
│   ├── attendance.php/              # Update student attendance status by course.  
│   └── course_material.php/         # Upload PDF or Word documents as course materials.  
│  
└── student/  
    ├── enroll.php/                  # Enroll in available courses.  
    ├── course.php/                  # View enrolled courses and details.  
    └── stu_materials.php/           # Access course materials uploaded by teachers.  


