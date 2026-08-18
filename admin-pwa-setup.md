# Upper Crest Admin App

This project includes a lightweight Progressive Web App (PWA) launcher for the booking admin dashboard.

## Files

- `admin-app.html` - mobile app launcher page
- `manifest.json` - app name, icon, install behavior, and shortcuts
- `admin-service-worker.js` - caches static app assets
- `js/admin-app.js` - install prompt and notification-check helper
- `admin-summary.php` - logged-in admin-only reservation summary endpoint

## How to Install on Phone

Open this URL on the phone:

```text
https://theuppercrest.in/admin-app.html
```

For local server testing:

```text
http://192.168.31.250/uppercrest/admin-app.html
```

### iPhone

1. Open the URL in Safari.
2. Tap Share.
3. Tap Add to Home Screen.
4. Open the new Upper Crest icon.

### Android

1. Open the URL in Chrome.
2. Tap the menu.
3. Tap Add to Home screen or Install app.
4. Open the new Upper Crest icon.

## Notifications

The `Enable Booking Alerts` button asks browser permission and checks for pending reservations every 60 seconds while the app page is open.

Full background push notifications need HTTPS and a push service configuration. Android supports this broadly; iPhone supports it for installed home-screen web apps on HTTPS.

## About manifest.json

`manifest.json` tells the phone browser how the site should behave like an app. It controls:

- App name: `name` and `short_name`
- App icon: `icons`
- First page after opening the app: `start_url`
- App display mode: `display: standalone`
- Browser/app theme color: `theme_color`
- Quick actions: `shortcuts`
