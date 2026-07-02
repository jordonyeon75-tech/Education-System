## README.txt Frontend  

### How to Run the Frontend  

#### Step 1: Download the Project Files  
1. Download the ZIP file of the front-end project.  
2. Extract the ZIP file to your desired location on your computer.  

#### Step 2: Set Up Your Local Server  
1. Ensure you have a local server like XAMPP or WAMP installed.  
2. Move the extracted project folder to your `htdocs` directory:  
   - Path for XAMPP:  
     `C:\xampp\htdocs\edu`  

#### Step 3: Run the Frontend  
1. Start the Apache service in XAMPP or WAMP.  
2. Open your web browser and go to:  
   `http://localhost/edu/login.php`  
3. The system's entry point is the `login.php` page, which integrates with the backend system.  

#### Step 4: Integration with Backend  
1. Ensure the backend system is set up and running on the same server environment.  
2. The frontend communicates with the backend using AJAX requests.  
3. If the backend is hosted on a different server, update the API endpoints in the frontend JavaScript files.  

---

### Folder Structure for Frontend  

edu/
├── css/ # Contains all CSS files for styling and links with main pages.
│ ├── admin_styles.css # Styles for the admin side design.
│ ├── AdminHeader.css # Styles for admin sidebar and header design.
│ ├── LoginStyle.css # Styles for the login page design.
│ ├── StudentHeader.css # Styles for student sidebar design.
│ ├── style.css # Global styles for the system design.
│
├── js/ # Contains all JavaScript files for functionality.
│ ├── datatable_init.js # Scripts for initializing and managing system tables.
│
└── images/ # Contains images used in the project.