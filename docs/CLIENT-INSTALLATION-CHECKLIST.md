# Bethel Asset Tracker — Client PC Installation & Update Checklist

**Client:** Bethel Baptist Hospital, Inc., Fortich St., Sumpong, Malaybalay City, Bukidnon 8700  
**Application:** Bethel Asset Tracker (`com.bethelhospital.assettracker`)  
**Installer File:** `nativephp\electron\dist\Bethel Asset Tracker-1.0.0-setup.exe`

---

## 1. Pre-Installation Check on the Hospital Windows PC
- [ ] Windows 10 or Windows 11 (64-bit / x64).
- [ ] No internet connection, XAMPP, PHP, or database server is required — everything is bundled inside the `.exe` installer.
- [ ] Connect the hospital's printer (laser/inkjet/sticker printer) and USB barcode/QR scanner.

## 2. Installing the Application
- [ ] Copy `Bethel Asset Tracker-1.0.0-setup.exe` onto the hospital PC via USB flash drive.
- [ ] Double-click `Bethel Asset Tracker-1.0.0-setup.exe`.
- [ ] If Windows SmartScreen appears ("Windows protected your PC"), click **More info** &rarr; **Run anyway**.
- [ ] Wait for the installer to finish and launch **Bethel Asset Tracker** from the Desktop shortcut or Start Menu.

## 3. Where Data Is Stored (Update-Safe Storage)
- **Active SQLite Database:**
  ```text
  %APPDATA%\bethel-asset-tracker\database\database.sqlite
  (e.g. C:\Users\<WindowsUser>\AppData\Roaming\bethel-asset-tracker\database\database.sqlite)
  ```
- **Default Automatic & Manual Backup Folder:**
  ```text
  C:\Users\<WindowsUser>\Documents\BethelBackups
  ```
- **Why Updates Never Erase Data:**
  1. `config/nativephp.php` excludes `database/*.sqlite*` from the build bundle (`cleanup_exclude_files`), so a new `.exe` installer never contains a blank database file.
  2. `nsis.delete_app_data_on_uninstall` is `false`, so `%APPDATA%\bethel-asset-tracker` is never deleted when upgrading.
  3. On startup, the app automatically creates a `pre-migration-*.sqlite` backup before running any new additive migrations.

## 4. First-Time Login & Password Change
- [ ] On the Sign In screen, sign in with the seeded administrator account:
  - **Email:** `admin@hospital.local`
  - **Temporary Password:** `password123`
- [ ] The system will immediately prompt you to set a new permanent password (minimum 8 characters).
- [ ] Record the new administrator password in a secure place at the hospital.

## 5. Initial Hospital Configuration (Settings Page)
- [ ] Open **Settings** in the left sidebar.
- [ ] Verify **Hospital Name** (`Bethel Baptist Hospital, Inc.`) and **Hospital Address** (`Fortich St., Sumpong, Malaybalay City, Bukidnon 8700`).
- [ ] Upload the official hospital logo (`.png` or `.jpg`) — it will be automatically embedded as base64 in all printed property tags.
- [ ] Confirm **Asset No. Prefix** (default `BBH`) and **Tag Width / Height** (default `10 cm × 6 cm`).
- [ ] Confirm **Backup Folder** (defaults to `Documents\BethelBackups`, or click **Choose Backup Folder…** to select an external/secondary drive folder).
- [ ] Click **Backup Now** once to verify a `.sqlite` backup file is created in the backup folder.

## 6. Importing the Hospital's Existing Excel Asset List
- [ ] Open **Reports** in the left sidebar.
- [ ] Click **Download Sample Template** to see the exact column names (`asset_no`, `asset_name`, `model`, `serial_no`, `date_acquired`, `department`, `location`, `status`, `notes`).
- [ ] Copy the hospital's existing spreadsheet rows into the template (or ensure the hospital spreadsheet has matching column headings). Note: `date_acquired` and `serial_no` may be left blank; `asset_no` may be filled with existing paper tag numbers (e.g. `BBHIND-S300002315`) or left blank to auto-generate.
- [ ] Upload the file and click **Preview Import**.
- [ ] Review the preview table and click **Confirm & Import**.

## 7. Printing Property Tags & Testing the USB Scanner
- [ ] Open any asset and click **Print Tag**.
- [ ] Click **Print Now** (`window.print()`) and print one test tag.
- [ ] Verify the hospital logo, header, bordered table fields, and QR code print crisply at `10 cm × 6 cm`.
- [ ] Open **Scan** in the left sidebar, scan the printed QR code with the USB barcode/QR scanner, and confirm it opens the matching asset page automatically.

## 8. Delivering Future Software Updates
1. Bump `'version'` in `config/nativephp.php` (e.g., `'1.0.1'`).
2. Add only **additive** migrations (new tables or nullable columns — never `migrate:fresh` or `dropIfExists` in production workflow).
3. Run `npm run build` and `php artisan native:build win x64 --no-interaction`.
4. Send the new `Bethel Asset Tracker-<version>-setup.exe` to the client. Running the new installer updates the app binaries while keeping `%APPDATA%\bethel-asset-tracker\database\database.sqlite` and `Documents\BethelBackups` intact.
