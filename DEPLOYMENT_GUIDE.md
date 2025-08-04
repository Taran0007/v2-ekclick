# Deployment Guide for Ek-Click Delivery System

## Pre-deployment Checklist

1. **Database Setup**
   - Create a new database on your hosting
   - Note down the database credentials:
     - Database Host
     - Database Name
     - Database Username
     - Database Password

2. **Files to Update**
   Edit `config.php` and update the following:
   ```php
   // Production Settings
   define('DB_HOST', 'your_host');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   define('SITE_URL', 'https://your-domain.com');
   ```

3. **Required PHP Extensions**
   Make sure your hosting has these PHP extensions enabled:
   - PDO
   - PDO_MySQL
   - GD (for image processing)
   - mbstring
   - json
   - session

4. **Minimum Requirements**
   - PHP 7.4 or higher
   - MySQL 5.7 or higher
   - HTTPS enabled
   - Minimum 256MB PHP memory limit

## Deployment Steps

1. **Database Setup**
   - Import `database.sql` to your hosting's database
   - You can use phpMyAdmin or your hosting's database management tool

2. **File Upload**
   - Upload all files to your hosting's public directory (usually public_html)
   - Important: Do NOT upload the following files/folders:
     - `.git/`
     - `start-server.bat`
     - Any local configuration files

3. **Directory Permissions**
   Set the following permissions:
   ```bash
   chmod 755 /public_html
   chmod 644 /public_html/*.php
   chmod 755 /public_html/uploads
   chmod 755 /public_html/includes
   ```

4. **Create Upload Directories**
   Create and set permissions for upload directories:
   ```bash
   mkdir -p uploads/products
   mkdir -p uploads/profiles
   chmod -R 755 uploads
   ```

5. **SSL Certificate**
   - Install SSL certificate if not already done
   - Update SITE_URL in config.php to use https://

6. **First-time Setup**
   - Visit: https://your-domain.com/setup.php
   - This will:
     - Check system requirements
     - Verify database connection
     - Create admin account if not exists
     - Set initial configurations

7. **Security Measures**
   After setup:
   - Delete setup.php
   - Delete create_demo_users.php
   - Update admin password
   - Set proper file permissions

## Post-deployment Checklist

1. **Test Critical Features**
   - User registration/login
   - Vendor registration
   - Order placement
   - Payment processing
   - Image uploads
   - Notifications

2. **Verify Security**
   - SSL is working
   - Proper file permissions
   - Security headers are set
   - Error reporting is disabled
   - Debug mode is off

3. **Performance Checks**
   - Enable PHP caching
   - Configure MySQL query cache
   - Enable Gzip compression
   - Set up browser caching

4. **Backup Setup**
   - Configure database backups
   - Set up file backups
   - Test restore procedures

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Verify credentials in config.php
   - Check database host is accessible
   - Verify database user permissions

2. **File Upload Issues**
   - Check directory permissions
   - Verify PHP memory limit
   - Check upload_max_filesize in php.ini

3. **Session Issues**
   - Check session save path permissions
   - Verify session configuration
   - Check for SSL mixed content

### Support Contacts

For technical support:
- Email: support@your-domain.com
- Documentation: https://your-domain.com/docs

## Maintenance

Regular maintenance tasks:
1. Check error logs weekly
2. Monitor disk space
3. Update PHP and MySQL versions
4. Backup database daily
5. Review security settings monthly
