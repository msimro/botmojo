# BotMojo Deployment Guide

## Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Composer
- SSL certificate
- Sufficient server resources (2+ CPU cores, 4GB+ RAM)

## Production Deployment Steps

1. Clone the repository:
```bash
git clone https://github.com/msimro/botmojo.git
cd botmojo
```

2. Install dependencies:
```bash
composer install --no-dev --optimize-autoloader
```

3. Set up environment:
```bash
cp .env.example .env
# Edit .env with production values
```

4. Configure web server:
```nginx
server {
    listen 443 ssl http2;
    server_name api.botmojo.com;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    root /var/www/botmojo/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Security headers
    add_header X-Frame-Options "DENY";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Content-Security-Policy "default-src 'self'";
}
```

5. Set up database:
```bash
mysql -u root -p < docs/database.sql
```

6. Configure PHP:
```ini
; php.ini optimizations
memory_limit = 256M
max_execution_time = 30
display_errors = Off
log_errors = On
error_log = /var/log/php/error.log
opcache.enable = 1
opcache.memory_consumption = 128
```

7. Set up log rotation:
```conf
# /etc/logrotate.d/botmojo
/var/www/botmojo/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
    sharedscripts
    postrotate
        /usr/lib/php/php8.0-fpm-reopenlogs
    endscript
}
```

8. Set proper permissions:
```bash
chown -R www-data:www-data /var/www/botmojo
chmod -R 755 /var/www/botmojo
chmod -R 770 /var/www/botmojo/logs
chmod -R 770 /var/www/botmojo/cache
```

9. Set up monitoring:
- Configure server monitoring (e.g., New Relic, Datadog)
- Set up error alerting
- Configure uptime monitoring

10. Security considerations:
- Enable firewall
- Configure fail2ban
- Keep system updated
- Regular security audits
- Enable rate limiting

## Maintenance

### Daily Tasks
- Check error logs
- Monitor system resources
- Verify backups

### Weekly Tasks
- Update dependencies
- Review performance metrics
- Clean old cache files

### Monthly Tasks
- Security updates
- Database optimization
- SSL certificate check

## Rollback Plan

1. Create backup:
```bash
mysqldump -u root -p botmojo > backup.sql
tar -czf botmojo-backup.tar.gz /var/www/botmojo
```

2. Restore from backup:
```bash
mysql -u root -p botmojo < backup.sql
rm -rf /var/www/botmojo/*
tar -xzf botmojo-backup.tar.gz -C /var/www/
```

## Support
For production support:
- Email: support@botmojo.com
- Emergency: +1-XXX-XXX-XXXX
