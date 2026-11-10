# Careshell Medical CRM

Careshell is a responsive PHP and MySQL clinic workspace. It runs locally with XAMPP and includes patient records, appointment scheduling, shared reminders, team discussions and private messages, staff roles, and an administrator activity log.

## Run locally with XAMPP

1. Copy the project folder into `D:\xampp\htdocs\careshell` (or your XAMPP `htdocs` folder).
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Check `config.php`. The defaults are MySQL at `127.0.0.1:3306`, database `medicalcrm`, user `root`, and an empty password. Update these values if your local MySQL setup differs.
4. Open [http://localhost/careshell/install.php](http://localhost/careshell/install.php).
5. Create the first administrator account with a strong password of at least 12 characters. The setup page creates the database tables and account.
6. Sign in at [http://localhost/careshell/login.php](http://localhost/careshell/login.php).

The MySQL account needs permission to create the `medicalcrm` database. If it does not, create a database named `medicalcrm` in phpMyAdmin, import `database.sql`, then visit the setup page again.

## Move records from the original desktop app

Make a backup of `legacy\medCRM.db` first. Complete the web setup, then run this command from the project directory:

```powershell
D:\xampp\php\php.exe migrate_legacy.php
```

Or pass the path to another SQLite database:

```powershell
D:\xampp\php\php.exe migrate_legacy.php "D:\backups\medCRM.db"
```

The importer moves patient demographics and notes, appointments, reminders, forum discussions, and their replies. It can be run again without duplicating imported records. It intentionally does not migrate PIN credentials, local sessions, images, or private messages; staff should receive new web accounts and passwords. The XAMPP PHP installation must have `pdo_sqlite` enabled for the importer.

The preserved legacy database is blocked from direct web access by `legacy\.htaccess`; the command-line importer can still read it.

## Roles

| Role | Access |
| --- | --- |
| Administrator | All records, staff accounts, roles, and activity history |
| Doctor | Patient records, appointments, and reminders |
| Nurse | Patient records and reminders; read-only appointment schedule |
| Receptionist | Patient records, appointments, and reminders |

Passwords are stored as PHP password hashes. Mutations use prepared SQL statements and CSRF tokens, and the activity log records sign-ins and record changes. Administrators can deactivate staff accounts from **Team access**.

## Notes

- The interface is built with PHP, MySQL, CSS, and vanilla JavaScript; there are no Composer or npm dependencies.
- Staff can post team-wide discussions and send private messages to active team accounts.
- Keep this installation on a trusted local network. This starter is not a certified electronic health record or a substitute for a privacy/security compliance review. Configure HTTPS, least-privilege MySQL credentials, backups, and your applicable health-data safeguards before handling real patient information.
- `database.sql` defines the MySQL schema. `config.php` supports `MEDCRM_DB_HOST`, `MEDCRM_DB_PORT`, `MEDCRM_DB_NAME`, `MEDCRM_DB_USER`, and `MEDCRM_DB_PASSWORD` environment variables.
