# Upper Crest Booking Engine Setup

This booking engine uses PHP and MySQL.

## 1. Create MySQL database and user

Example:

```sql
CREATE DATABASE uppercrest_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'uppercrest_user'@'localhost' IDENTIFIED BY 'CHANGE_THIS_PASSWORD';
GRANT ALL PRIVILEGES ON uppercrest_booking.* TO 'uppercrest_user'@'localhost';
FLUSH PRIVILEGES;
```

## 2. Configure the website

```bash
cd ~/uppercrest
cp booking-admin-config.example.php booking-admin-config.php
nano booking-admin-config.php
```

Set the MySQL values, `BOOKING_ADMIN_USERNAME`, `BOOKING_TIMEZONE`, and a strong admin password.

Optional rate plan override:

```php
define('BOOKING_RACK_RATE', 4500);
define('BOOKING_RATE_PLAN', array(
    30 => 1500,
    15 => 2000,
    10 => 2500,
    7 => 3000,
    2 => 3250,
    1 => 3500,
));
```

Then copy to Apache root:

```bash
sudo cp -a ~/uppercrest/. /var/www/html/
```

## 3. Create the tables

Open:

```text
https://theuppercrest.in/booking-admin.php
```

Login and click:

```text
Create / Update MySQL Tables
```

## 4. Manage dates

In the dashboard, use Open / Close Dates:

- Open = available unless a confirmed reservation overlaps.
- Closed = blocked even if there is no reservation.
- Narration / Note = reason such as maintenance, owner blocked, phone booking.
- Rate = nightly rate used by the API.
- Default rates are tiered by stay length: 1 night INR 3500, 2+ nights INR 3250, 7+ nights INR 3000, 10+ nights INR 2500, 15+ nights INR 2000, 30+ nights INR 1500 per night.
- Calendar day rates override the tier rate for that specific date.

Confirmed reservations also block dates.

## 5. Website API endpoints

Availability:

```text
https://theuppercrest.in/booking-engine-api.php?action=availability&check_in=2026-08-01&check_out=2026-08-02
```

Blocked date feed:

```text
https://theuppercrest.in/booking-engine-api.php?action=booked-dates
```

Google Hotel Ads test JSON:

```text
https://theuppercrest.in/booking-engine-api.php?action=hotel-ads&check_in=2026-08-01&nights=1
```

Google Hotel Ads test XML:

```text
https://theuppercrest.in/booking-engine-api.php?action=hotel-ads-xml&check_in=2026-08-01&nights=1
```

## 6. Google Hotel Ads note

These endpoints expose live availability and prices from your MySQL engine. Actual Google Hotel Ads or Free Booking Links integration still requires a Google Hotel Center account, property matching, landing page setup, and Google certification/price accuracy checks.

## 7. Google Vacation Rentals XML listing feed

Public XML listing feed:

```text
https://theuppercrest.in/google-vacation-rentals-feed.xml
```

Google Vacation Rentals onboarding asks for XML list feeds to be shared as ZIP files with your Google Technical Account Manager. After deployment, create a ZIP on the server if Google asks for a hosted ZIP:

```bash
cd /var/www/html
zip -j google-vacation-rentals-feed.zip google-vacation-rentals-feed.xml
```

Then share:

```text
https://theuppercrest.in/google-vacation-rentals-feed.zip
```
