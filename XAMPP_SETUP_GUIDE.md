# Step-by-Step Guide: Running DeliverEase with XAMPP

## 1. Install and Start XAMPP

1. **Download XAMPP** from https://www.apachefriends.org/
2. **Install XAMPP** (default location: `C:\xampp`)
3. **Open XAMPP Control Panel** (Run as Administrator)
4. **Start Apache and MySQL** by clicking their "Start" buttons
   - Apache should show port 80, 443
   - MySQL should show port 3306

## 2. Copy Project Files

1. **Navigate to XAMPP's htdocs folder**: `C:\xampp\htdocs\`
2. **Copy the entire `php-deliverease` folder** into htdocs
   - Your path will be: `C:\xampp\htdocs\php-deliverease\`

## 3. Create Database

1. **Open phpMyAdmin**: http://localhost/phpmyadmin
2. **Click "New"** on the left sidebar
3. **Database name**: `deliverease`
4. **Collation**: `utf8mb4_general_ci`
5. **Click "Create"**

## 4. Import Database Schema

1. **Select `deliverease` database** from left sidebar
2. **Click "Import"** tab
3. **Choose File**: Browse to `C:\xampp\htdocs\php-deliverease\database.sql`
4. **Click "Go"** at the bottom
5. You should see "Import has been successfully finished"

## 5. Update Database Configuration

1. **Open** `C:\xampp\htdocs\php-deliverease\config.php` in your text editor
2. **Verify these settings** (usually no changes needed for XAMPP):
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'deliverease');
   define('DB_USER', 'root');
   define('DB_PASS', '');  // XAMPP default is empty password
   ```
3. **Update the site URL**:
   ```php
   define('SITE_URL', 'http://localhost/php-deliverease');
   ```

## 6. Initialize Demo Data

1. **Open browser**: http://localhost/php-deliverease/setup.php
2. **Click "Initialize Demo Data"** button
3. You should see "Setup completed successfully!"
4. **Important**: Delete or rename `setup.php` after this step for security

## 7. Access the Website

1. **Homepage**: http://localhost/php-deliverease/
2. **Login page**: http://localhost/php-deliverease/login.php
3. **Use demo credentials**:
   - Admin: `admin` / `admin123`
   - Vendor: `vendor1` / `vendor123`
   - Delivery: `delivery1` / `delivery123`
   - Customer: `customer1` / `customer123`

## 8. Live Editing with VSCode

1. **Open VSCode**
2. **File → Open Folder**: Navigate to `C:\xampp\htdocs\php-deliverease`
3. **Install recommended extensions**:
   - PHP Intelephense
   - PHP Debug
   - Live Server (for CSS/JS preview)

### Making Live Changes:

1. **Edit any PHP file** in VSCode
2. **Save the file** (Ctrl+S)
3. **Refresh your browser** to see changes immediately
4. **No compilation needed** - PHP is interpreted on-the-fly!

### Common Files to Edit:

- **Homepage**: `index.php`
- **Styling**: Edit CSS in the `<style>` sections of each file
- **Colors**: Search for `:root` CSS variables to change color scheme
- **Dashboards**: 
  - Admin: `admin/index.php`
  - Vendor: `vendor/index.php`
  - Delivery: `delivery/index.php`
  - Customer: `customer/index.php`

## 9. Debugging Tips

### Enable PHP Error Display:
1. Open `C:\xampp\php\php.ini`
2. Find and set:
   ```ini
   display_errors = On
   error_reporting = E_ALL
   ```
3. Restart Apache in XAMPP

### Check Error Logs:
- **PHP errors**: `C:\xampp\apache\logs\error.log`
- **MySQL errors**: Check phpMyAdmin or enable in `config.php`

## 10. Common Issues & Solutions

### "Access Denied" Database Error:
- Check username/password in `config.php`
- Default XAMPP MySQL user is `root` with no password

### "404 Not Found" Errors:
- Ensure you're accessing: `http://localhost/php-deliverease/`
- Check if Apache is running in XAMPP

### Session Errors:
- Make sure `C:\xampp\tmp` exists and is writable
- Clear browser cookies and try again

### Changes Not Showing:
- Hard refresh browser: Ctrl+F5
- Clear browser cache
- Check if you saved the file

## Quick Development Workflow

1. **Keep XAMPP Control Panel open** with Apache & MySQL running
2. **Open project in VSCode**: `C:\xampp\htdocs\php-deliverease`
3. **Open website in browser**: http://localhost/php-deliverease/
4. **Make changes in VSCode** → Save → Refresh browser
5. **Use browser DevTools** (F12) to debug JavaScript/CSS

## Useful XAMPP Locations

- **Project files**: `C:\xampp\htdocs\php-deliverease\`
- **PHP config**: `C:\xampp\php\php.ini`
- **Apache config**: `C:\xampp\apache\conf\httpd.conf`
- **MySQL data**: `C:\xampp\mysql\data\`
- **Error logs**: `C:\xampp\apache\logs\`

## Making It Accessible on Network

To access from other devices on your network:
1. Find your IP: Open CMD and type `ipconfig`
2. Look for IPv4 Address (e.g., 192.168.1.100)
3. Access from other devices: `http://192.168.1.100/php-deliverease/`

---

**Pro Tip**: Install the "PHP Server" extension in VSCode for quick right-click → "PHP Server: Serve Project" option!
