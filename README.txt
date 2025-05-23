BIMS (Barangay Information Management System)
==============================================

**Requirements:**
- PHP 7.4 or higher
- MySQL/MariaDB
- Web server (Apache, Nginx, XAMPP, etc.)
- [FPDF library](http://www.fpdf.org/) (for PDF export)
- Tailwind CSS (CDN included in HTML)

**Setup Instructions:**
1. Import the `bims_db.sql` file into your MySQL server.
2. Copy all files and folders to your web server directory (e.g., `htdocs/bims` for XAMPP).
3. Download FPDF from http://www.fpdf.org/ and place `fpdf.php` in `lib/fpdf/`.
4. Update `config.php` if your database settings are different.
5. Place your barangay logo as `barangay_logo.png` in the project root (optional).
6. Access the system via your browser (e.g., http://localhost/bims/).

**To Host Online:**
- Upload all files and folders to your web hosting (public_html or similar).
- Import the database using phpMyAdmin or MySQL CLI.
- Update `config.php` with your online database credentials.
- Make sure the `lib/fpdf/` folder and logo are uploaded.

**Features:**
- Secure login for secretary
- Manage individuals and families (CRUD)
- Responsive sidebar and navbar
- Search/filter and modals for records
- Export individuals as PDF
- Generate certificates with logo
- Dashboard and reports
- Profile management (change password)

**Troubleshooting:**
- If you see a missing FPDF error, download and place the library as instructed.
- If you see a database error, check your `config.php` and database import.

**For transfer:**
- Copy the entire folder and database export to the new machine.
- Import the database, update `config.php` if needed, and all features will work.

**For support or updates, contact your developer.**
