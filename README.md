# Grand Horizon Hotel - Employee Directory Microservice (System 2)

A REST API microservice and administrative dashboard for hotel staff management built with PHP, PDO MySQL, and Tailwind CSS. Provides live employee datasets to the Main Reservation System (System 1).

## Features
- **RESTful API**: JSON endpoints (`/get_employees.php`, `/api.php`) for querying personnel by status and department.
- **Personnel Directory & Management**: Register new staff with quick 1-click department autofill presets, edit details, toggle availability status, and remove records.
- **Search & Multi-Filtering**: Real-time filtering by name, role, department, employee code, and status.
- **Interactive API Sandbox**: Live in-browser testing console to inspect JSON responses and network latency.

## File Structure
- `index.php` - Microservice dashboard, directory table, modal forms, and interactive API sandbox.
- `get_employees.php` - JSON API endpoint for staff listing and dropdown integration.
- `api.php` - Full REST API endpoint supporting GET, POST, DELETE.
- `db_config.php` - PDO database configuration.
- `script.js` - Client-side logic for real-time filtering, modals, autofill presets, and API sandbox.
- `style.css` - Custom styling tokens.
