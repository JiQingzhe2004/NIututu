Here's the English version of the documentation:

---

# File Transfer System

#### Introduction
A cross-device file transfer solution designed for LAN users to share files between different devices. Supports separate upload/download permissions for users.

#### System Architecture
> Built with PHP + MySQL + Bootstrap.
>

#### Installation Guide

1. Extract the downloaded package and run "phpEnv8.9.6-Setup.exe" ([Compressed version only, as Gitee has 100MB file limit. Hold Ctrl and click to download](https://dl.phpenv.cn/release/phpEnv.7z))

   

   ```https
   https://dl.phpenv.cn/release/phpEnv.7z
   Or copy this URL directly to your browser
   ```

2. Recommended to install in root directory of D: or E: drive for easier access.

3. Extract "`tools.zip`" and place contents in the installation directory.

4. Launch "`phpEnv`" software.

5. Go to: Applications → Settings → Ports. Change Nginx port to an available port (e.g. 7890)

6. In homepage, navigate to: Tools → MySQL Tools → Reset Password. Enter new password.

7. Click "Websites" → "Add". Enter server IP as domain and set port (e.g. 7891).

8. Right-click the new site → "Open root directory". Copy all files from original folder here.

9. Configure `config.json` with database credentials:

   

   ```json
   "database": "",  // Database name (e.g. wjcs)
   "username": "",  // Default: root
   "password": ""   // Password set in step 6
   ```

10. In homepage, click "Database" to launch HeidiSQL.

11. Enter password to connect. Successful connection shows database list.

12. Right-click "localhost" → Create new → Database. Use name from config.json (e.g. wjcs).

13. Access via browser: `IP:port` (e.g. `192.168.109.131:7891`). Successful loading confirms setup.

14. Append `/sql.php` to URL (e.g. `192.168.109.131:7891/sql.php`) to auto-create database tables.

15. Login with default credentials:
    - Username: `admin`
    - Password: `123qwe`
    -- Installation complete --

#### User Manual

1. Only admins can add/delete users. After login: 
   - Click "Manage Users" (top-right) → "Add User" (top-left) for single registration
   - Use "Batch Upload" for multiple registrations

2. Batch registration supports:
   - XLSX file upload
   - Text paste
   (Toggle between modes via "Switch Method" button)

3. In batch upload:
   - Download template file for data entry
   - Upload completed file OR copy-paste data to text field
   - Submit to process

4. Successful batch creations show status for each user.

5. Mobile-friendly interface accessible via `IP:port`.

6. Maximum upload size: 300MB (shown in top banner).

7. Default admin credentials:
   - Username: admin
   - Password: 123qwe

#### Configuration Modifications

How to modify upload size limit?

1. In `index.php`:

   

   ```php
   const maxSize = 300 * 1024 * 1024; // 300MB
   if (file.size > maxSize) {
        displayMessage('File exceeds size limit (max 300MB).', 'warning');
        return;
   }
   ```
   Change 300 to 3000 for 3GB limit (must match other files).

2. In `file_manager.php`:

   

   ```php
   $maxFileSize = 300 * 1024 * 1024; // 300MB
   if ($filesize > $maxFileSize) {
   $responses[] = ['success' => false, 'error' => 'File exceeds size limit (max 300MB).'];
   continue;
   }
   ```
   Make identical change as step 1.

3. In phpEnv software:
   - Hover over services → Edit PHP's `php.ini`:
     
     
     
     ```ini
     upload_max_filesize = 300M
     post_max_size = 310M       # Should exceed upload_max_filesize
     max_execution_time = 300   # Prevents timeout for large files
     max_input_time = 300       # Extended processing time
     memory_limit = 512M        # Adequate memory allocation
     ```
     Adjust values as needed.

4. Edit Nginx's `nginx.conf`:

   

   ```conf
   client_max_body_size 300M  # Nginx upload limit
   ```

5. Update notification in `index.php` (now pulls from database):

   

   ```php+HTML
   <!-- System Notice -->
   <div class="alert alert-info alert-dismissible fade show text-center announcement animate__animated animate__fadeInDown" role="alert">
        Maximum file size: 300MB. Not 1KB more! 😁
        <link rel="stylesheet" href="css/animate.min.css"/>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
   </div>
   ```

6. Restart all services from software homepage.

---

Note: The documentation now reflects that the notice message is dynamically loaded from the database rather than hardcoded.