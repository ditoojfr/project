# Project E-DESLAY (PHP - Plain)
Files provided are ready to run on a local server (XAMPP, WAMP) or hosting supporting PHP & MySQL.

## Setup
1. Import the provided `project.sql` into your MySQL server (phpMyAdmin).
2. Run this SQL to add `saran` table if not present:
```sql
CREATE TABLE `saran` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `nama` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `isi_saran` TEXT NOT NULL,
  `tanggal` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```
3. Place the `project/` folder in your `htdocs` or web root.
4. Open `http://localhost/project/` to view the public site.
5. Login for admin: `http://localhost/project/login.php`
   - username: admin
   - password: admin

Notes:
- Images are stored in the database as BLOB per your request.
- Pages are responsive and basic JS slider included.
