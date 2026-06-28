# Flexi Feet Sdn Bhd - Premium Website & CRM

A self-contained, high-performance website and CRM built for Flexi Feet Sdn Bhd. Designed with Apple-standard aesthetics and optimized for Hostinger shared hosting.

## Features

- **Apple Standard UI**: High-end minimalist design, glassmorphism, and smooth scroll animations.
- **Dynamic Content**: Integrated YouTube Shorts ("Flexi Stories") scrolling section.
- **Self-Contained CRM**: A built-in admin dashboard to manage appointments stored in a local JSON database.
- **Blog CMS**: Admin-managed posts with draft/published states and public blog pages.
- **SMTP Notifications**: Booking requests can email the site owner and send the customer a confirmation via Hostinger SMTP.
- **3D Scanning Section**: Highlights the advanced Italian foot scanning technology.
- **Comprehensive Foot Care**: Detailed sections for 10 common diabetic and orthopaedic foot conditions.
- **Hostinger Ready**: Runs on PHP/JS/CSS with zero external dependencies or build steps.

## Deployment

1. Upload all files in this directory to your Hostinger `public_html` folder.
2. Ensure the `storage/` folder is writable by the server (usually `755` permission).
3. Copy `includes/config.local.example.php` to `includes/config.local.php` on the server and add the real SMTP password.
4. Access your site at `yourdomain.com`.

## Admin Access

- **Login URL**: `yourdomain.com/admin/login.php`
- **Default Username**: `admin`
- **Default Password**: `FlexiFeet@2026`

*Note: For security, it is highly recommended to update the admin credentials in `includes/config.php` before going live.*

## Local Testing

If you have PHP installed locally, you can run:
```bash
php -S localhost:8000
```
Then visit `http://localhost:8000` in your browser.

## Technical Details

- **Frontend**: Vanilla JavaScript & Modern CSS (Grid, Flexbox, Backdrop-filter).
- **Backend**: PHP 7.4+.
- **Database**: `storage/appointments.json` and `storage/blog-posts.json` (Atomic file-locked JSON storage).
- **Icons**: Custom embedded SVG icons for zero external requests.
- **Fonts**: 'Inter' imported via Google Fonts for premium typography.
