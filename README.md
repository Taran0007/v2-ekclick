# DeliverEase - Multi-Vendor Delivery Service

A PHP-based multi-vendor delivery service platform that can be deployed on any shared hosting with PHP and MySQL support.

## Features

- **Multi-Role System**: Admin, Vendor, Delivery Personnel, and Customer dashboards
- **Vendor Management**: Vendors can manage their shops, products, and orders
- **Order Management**: Complete order lifecycle from placement to delivery
- **Real-time Updates**: Track orders and delivery status
- **Product Catalog**: Browse products by category and vendor
- **User Authentication**: Secure login system with role-based access
- **Responsive Design**: Works on desktop and mobile devices

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled (for Apache)

## Installation

### 1. Upload Files
Upload all files from the `php-deliverease` folder to your web hosting directory (e.g., `public_html` or `www`).

### 2. Create Database
1. Log into your hosting control panel (cPanel, Plesk, etc.)
2. Create a new MySQL database named `deliverease`
3. Create a database user and grant all privileges to the database
4. Note down the database credentials

### 3. Import Database Schema
1. Open phpMyAdmin from your hosting control panel
2. Select the `deliverease` database
3. Click on "Import" tab
4. Choose the `database.sql` file and import it

### 4. Configure Database Connection
Edit the `config.php` file and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'deliverease');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
```

### 5. Update Site URL
In `config.php`, update the site URL to match your domain:

```php
define('SITE_URL', 'https://yourdomain.com');
```

### 6. Set Up Demo Data
1. Navigate to `https://yourdomain.com/setup.php`
2. Click "Initialize Demo Data" to add sample users, vendors, and products
3. Delete or rename `setup.php` after completion for security

### 7. Create Upload Directories
Ensure the following directories exist and are writable (755 permissions):
- `uploads/`
- `uploads/products/`
- `uploads/profiles/`

## Demo Credentials

After running the setup, you can login with these credentials:

### Admin
- Username: `admin`
- Password: `admin123`

### Vendors
- Username: `vendor1` or `vendor2`
- Password: `vendor123`

### Delivery Personnel
- Username: `delivery1` or `delivery2`
- Password: `delivery123`

### Customers
- Username: `customer1` or `customer2`
- Password: `customer123`

## File Structure

```
php-deliverease/
├── admin/              # Admin dashboard files
├── vendor/             # Vendor dashboard files
├── delivery/           # Delivery personnel dashboard files
├── customer/           # Customer dashboard files
├── includes/           # Shared PHP includes (header, footer)
├── api/                # API endpoints for AJAX calls
├── uploads/            # User uploaded files
├── config.php          # Main configuration file
├── database.sql        # Database schema
├── index.php           # Homepage
├── login.php           # Login page
├── register.php        # Registration page
├── dashboard.php       # Dashboard router
└── setup.php           # Setup script
```

## Security Considerations

1. **Change Default Passwords**: After setup, change all demo account passwords
2. **Remove Setup File**: Delete or rename `setup.php` after initial setup
3. **Secure Uploads**: Configure `.htaccess` to prevent PHP execution in uploads folder
4. **SSL Certificate**: Use HTTPS for production deployment
5. **Regular Backups**: Set up automated database and file backups

## Customization

### Changing Colors
Edit the CSS variables in the style sections of each file:
```css
:root {
    --primary-color: #FF6B6B;
    --secondary-color: #4ECDC4;
    --dark-color: #2C3E50;
    --light-bg: #F8F9FA;
}
```

### Adding Payment Gateway
To add payment integration:
1. Create payment processing files in the `api/` directory
2. Update order placement logic to include payment verification
3. Add payment status tracking in the orders table

### Email Notifications
To add email notifications:
1. Configure PHP mail settings in `config.php`
2. Add email sending functions for order updates
3. Create email templates for different notifications

## Troubleshooting

### Database Connection Error
- Verify database credentials in `config.php`
- Ensure database user has all privileges
- Check if database server is running

### 404 Errors
- Ensure `.htaccess` file is present (for Apache)
- Check if mod_rewrite is enabled
- Verify file permissions (644 for files, 755 for directories)

### Session Issues
- Ensure PHP sessions are enabled
- Check session save path permissions
- Clear browser cookies and cache

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review server error logs
3. Ensure all requirements are met
4. Contact your hosting provider for server-specific issues

## License

This project is provided as-is for educational and commercial use.

---

**Note**: This is a basic implementation. For production use, consider adding:
- Input validation and sanitization
- CSRF protection
- Rate limiting
- Advanced error handling
- Automated testing
- Performance optimization
- Scalability improvements
