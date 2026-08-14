# Addis Ababa Land Banking System

A runnable PHP + MySQL + JavaScript MVP for a hierarchical Land Banking System:

- City → 11 Sub-Cities → Woredas
- Land Bank Accounts
- Deposit / Withdraw transactions
- GPS latitude/longitude capture
- Google Maps visualization
- Woreda → Sub-City → City approval workflow
- Digital-signature text capture
- City/Sub-City/Woreda dashboards
- Filters by Sub-City, Woreda, transaction type and status
- Photo/document path fields
- Audit log

## Requirements

- XAMPP (Apache + MySQL)
- PHP 8+
- MySQL 5.7+/8+
- A Google Maps JavaScript API key for the map
- Browser location permission for GPS

## Installation

1. Copy this folder into:
   `C:\xampp\htdocs\land_banking_system`

2. Start Apache and MySQL in XAMPP.

3. Open phpMyAdmin and import:
   `database/schema.sql`

4. Edit:
   `config/config.php`
   and set your database credentials and Google Maps API key.

5. Open:
   `http://localhost/land_banking_system/`

## Default login

Username: `admin`
Password: `admin123`

Change this password before production use.

## Important

This is an operational MVP foundation. Before production deployment, add:
- HTTPS
- stronger password policy / MFA
- real PKI or legally approved digital signatures if required
- file upload validation and antivirus scanning
- database backups
- role/permission hardening
- CSRF protection on every state-changing endpoint
- server-side coordinate validation
- proper land parcel polygons and PostGIS for advanced GIS
- Google Maps billing/API restrictions
