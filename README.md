# Bastion for Craft CMS 5

![Screenshot](./docs/images/bastion_banner.png)

Bastion is an all-in-one security plugin for Craft CMS 5. It provides a security dashboard with automated scanning, IP-based access control, Content Security Policy management, bot defence, dependency auditing, and update reminders — all from within your control panel.

## Features

- **Security Dashboard & Scanner**: Run comprehensive security scans covering 20+ checks — critical CMS/plugin updates, HTTPS enforcement, dev mode status, file/folder permissions, PHP version support, security headers, admin username validation, search engine indexing status, and more. Results are stored and displayed with pass/warning/fail indicators.

- **SSL Certificate Monitoring**: Fetch SSL/TLS security assessments via SSL Labs API, including certificate grade, protocol support, cipher strength, and expiration dates. Receive automated email reminders at 7 days and 24 hours before certificate expiration.

- **Domain Expiration Monitoring**: Track domain registration expiration dates using RDAP lookups. Automated email reminders at 30 days and 7 days before expiration. Supports IDN domains and automatically detects local/development environments.

- **Disk Space Monitoring**: Monitor server disk usage in real time with configurable threshold alerts. Receive email notifications when disk usage exceeds your defined percentage limit.

- **IP Restrictions**: Restrict access to both the front-end and the control panel independently by IP address. Supports single IPs, CIDR ranges, and IP ranges (IPv4 and IPv6). Choose between redirecting blocked visitors or rendering a custom Twig template.

- **Content Security Policy (CSP)**: Build and manage 24 CSP directives directly from the control panel. Deploy via HTTP header, meta tag, or report-only mode. Includes nonce generation for inline scripts/styles and SEOmatic compatibility. One-click default policy setup available.

- **Security Headers**: Configure additional HTTP security headers (Referrer-Policy, Strict-Transport-Security, X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, CORS) from a single settings page.

- **Bot Defence**: Automatically block unwanted bots at the server level via .htaccess rules. Supports Apache, LiteSpeed, and OpenLiteSpeed with Cloudflare detection. Automatic .htaccess backup management.

- **Dependency Audit**: Scan your Composer dependencies against the Packagist security advisories API. View vulnerable packages with severity levels (critical, high, medium, low) and detailed advisory information.

- **Updates Reminder**: Receive scheduled email notifications when Craft CMS or plugin updates are available. Configurable frequency (daily, weekly, bi-weekly, monthly), send day, and recipient list with a self-healing background job.

## Requirements

- Craft CMS 5.0.0 or later
- PHP 8.2 or later

## Installation

You can install this plugin from the Plugin Store or with Composer.

#### From the Plugin Store

Go to the Plugin Store in your project's Control Panel and search for "Bastion". Then click on the "Install" button in its modal window.

#### With Composer

Open your terminal and run the following commands:

```bash
# go to the project directory
cd /path/to/my-project.test

# tell Composer to load the plugin
composer require convergine/craft-bastion

# tell Craft to install the plugin
./craft plugin/install craft-bastion
```

## Support

For any issues or questions, you can reach us by email info@convergine.com or by opening an issue on [GitHub](https://github.com/convergine/craft-bastion/issues).