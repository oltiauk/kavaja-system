# Kavaja Hospital System - Technical Plan

## Table of Contents

1. [Overview](#overview)
2. [Technology Stack](#technology-stack)
3. [Database Schema](#database-schema)
4. [User Roles & Permissions](#user-roles--permissions)
5. [Application Structure](#application-structure)
6. [Screens & User Interface](#screens--user-interface)
7. [Detailed User Flows](#detailed-user-flows)
8. [API Endpoints](#api-endpoints)
9. [File Handling](#file-handling)
10. [QR Code System](#qr-code-system)
11. [Audit Logging](#audit-logging)
12. [Monthly Reports](#monthly-reports)
13. [Security](#security)
14. [Testing Checklist](#testing-checklist)

---

## Overview

Kavaja Hospital System is a web-based hospital management system for digitizing patient records with the following core features:

- Patient registration and record management
- Two encounter types: Visits (outpatient) and Hospitalizations (inpatient)
- Staff queue for hospitalized patients
- Document uploads (diagnostic images, reports, discharge papers)
- QR code generation for patient access (hospitalizations only)
- Patient portal with DOB verification
- Audit logging for all modifications
- Monthly statistics reports for admin

---

## Technology Stack

| Component | Technology |
|-----------|------------|
| Backend Framework | Laravel 11 |
| Admin Panel | Filament 3.x |
| Database | MySQL 8.x |
| PHP Version | 8.2+ |
| File Storage | Local filesystem (storage/app) |
| QR Code Generation | simple-qrcode or bacon/bacon-qr-code |
| PDF Manipulation | setasign/fpdi + setasign/fpdf or dompdf |
| Word Document Manipulation | phpoffice/phpword |
| Authentication | Laravel built-in + Filament auth |
| Frontend (Patient Portal) | Blade templates + Tailwind CSS |

### Required Packages

```bash
composer require filament/filament:"^3.0"
composer require simplesoftwareio/simple-qrcode
composer require setasign/fpdi
composer require setasign/fpdf
composer require phpoffice/phpword
composer require intervention/image
```

---

## Database Schema

### Table: users

Staff accounts for the system.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| name | VARCHAR(255) | NOT NULL | Full name |
| email | VARCHAR(255) | NOT NULL, UNIQUE | Login email |
| password | VARCHAR(255) | NOT NULL | Hashed password |
| role | ENUM('admin', 'administration', 'staff') | NOT NULL | User role |
| created_at | TIMESTAMP | NULL | Record creation time |
| updated_at | TIMESTAMP | NULL | Record update time |

**Indexes:** email (unique)

---

### Table: patients

Core patient information.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| first_name | VARCHAR(255) | NOT NULL | Patient first name |
| last_name | VARCHAR(255) | NOT NULL | Patient last name |
| date_of_birth | DATE | NOT NULL | Date of birth (used for QR verification) |
| gender | ENUM('male', 'female', 'other') | NOT NULL | Gender |
| phone_number | VARCHAR(50) | NULL | Contact phone |
| national_id | VARCHAR(100) | NULL, UNIQUE | National ID number |
| residency | VARCHAR(255) | NULL | Place of residency |
| emergency_contact_name | VARCHAR(255) | NULL | Emergency contact name |
| emergency_contact_phone | VARCHAR(50) | NULL | Emergency contact phone |
| emergency_contact_relationship | VARCHAR(100) | NULL | Relationship to patient |
| health_insurance_number | VARCHAR(100) | NULL | Insurance number |
| created_at | TIMESTAMP | NULL | Record creation time |
| updated_at | TIMESTAMP | NULL | Record update time |
| created_by | BIGINT UNSIGNED | FOREIGN KEY (users.id), NULL | Who created the record |

**Indexes:** 
- national_id (unique)
- last_name, first_name (for search)
- date_of_birth

---

### Table: patient_medical_info

Medical information for hospitalized patients (separate table for flexibility).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| patient_id | BIGINT UNSIGNED | FOREIGN KEY (patients.id), UNIQUE | Link to patient |
| blood_type | ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'unknown') | NULL | Blood type |
| height_cm | DECIMAL(5,2) | NULL | Height in centimeters |
| weight_kg | DECIMAL(5,2) | NULL | Weight in kilograms |
| allergies | TEXT | NULL | List of allergies |
| smoking_status | ENUM('smoker', 'non_smoker', 'former_smoker') | NULL | Smoking status |
| alcohol_use | VARCHAR(255) | NULL | Alcohol use description |
| drug_use_history | TEXT | NULL | Drug use history |
| pacemaker_implants | TEXT | NULL | Pacemaker or implants description |
| anesthesia_reactions | TEXT | NULL | Previous bad reactions to anesthesia |
| current_medications | TEXT | NULL | Current medications |
| created_at | TIMESTAMP | NULL | Record creation time |
| updated_at | TIMESTAMP | NULL | Record update time |
| updated_by | BIGINT UNSIGNED | FOREIGN KEY (users.id), NULL | Who last updated |

**Indexes:** patient_id (unique)

---

### Table: encounters

Patient visits and hospitalizations.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| patient_id | BIGINT UNSIGNED | FOREIGN KEY (patients.id) | Link to patient |
| type | ENUM('visit', 'hospitalization') | NOT NULL | Encounter type |
| status | ENUM('active', 'discharged') | NOT NULL, DEFAULT 'active' | Current status |
| main_complaint | TEXT | NOT NULL | Reason for visit |
| doctor_name | VARCHAR(255) | NOT NULL | Assigned doctor |
| diagnosis | TEXT | NULL | Diagnosis (added later) |
| treatment | TEXT | NULL | Treatment given |
| surgical_notes | TEXT | NULL | Surgical/operative notes (hidden from patients) |
| admission_date | DATETIME | NOT NULL | When encounter started |
| discharge_date | DATETIME | NULL | When patient discharged |
| medical_info_complete | BOOLEAN | DEFAULT FALSE | Whether staff completed medical info |
| created_at | TIMESTAMP | NULL | Record creation time |
| updated_at | TIMESTAMP | NULL | Record update time |
| created_by | BIGINT UNSIGNED | FOREIGN KEY (users.id), NULL | Who created |
| updated_by | BIGINT UNSIGNED | FOREIGN KEY (users.id), NULL | Who last updated |

**Indexes:**
- patient_id
- type
- status
- admission_date DESC
- (type, status) - for staff queue

---

### Table: documents

Uploaded diagnostic files and reports.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| encounter_id | BIGINT UNSIGNED | FOREIGN KEY (encounters.id) | Link to encounter |
| patient_id | BIGINT UNSIGNED | FOREIGN KEY (patients.id) | Link to patient |
| type | ENUM('diagnostic_image', 'report', 'other') | NOT NULL | Document type |
| original_filename | VARCHAR(255) | NOT NULL | Original uploaded filename |
| stored_filename | VARCHAR(255) | NOT NULL | Stored filename (UUID) |
| file_path | VARCHAR(500) | NOT NULL | Full path to file |
| mime_type | VARCHAR(100) | NOT NULL | File MIME type |
| file_size | BIGINT UNSIGNED | NOT NULL | File size in bytes |
| created_at | TIMESTAMP | NULL | Upload time |
| uploaded_by | BIGINT UNSIGNED | FOREIGN KEY (users.id), NULL | Who uploaded |

**Indexes:**
- encounter_id
- patient_id
- created_at DESC

---

### Table: discharge_papers

Discharge papers with QR codes (hospitalizations only).

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| encounter_id | BIGINT UNSIGNED | FOREIGN KEY (encounters.id), UNIQUE | Link to hospitalization |
| patient_id | BIGINT UNSIGNED | FOREIGN KEY (patients.id) | Link to patient |
| original_file_path | VARCHAR(500) | NOT NULL | Path to original upload |
| original_filename | VARCHAR(255) | NOT NULL | Original filename |
| qr_file_path | VARCHAR(500) | NOT NULL | Path to file with QR code |
| qr_token | VARCHAR(100) | NOT NULL, UNIQUE | Unique token for QR code |
| mime_type | VARCHAR(100) | NOT NULL | File type (pdf or docx) |
| created_at | TIMESTAMP | NULL | Creation time |
| updated_at | TIMESTAMP | NULL | Update time |
| uploaded_by | BIGINT UNSIGNED | FOREIGN KEY (users.id), NULL | Who uploaded |

**Indexes:**
- encounter_id (unique)
- patient_id
- qr_token (unique)

---

### Table: audit_logs

Track all modifications to records.

| Column | Type | Constraints | Description |
|--------|------|-------------|-------------|
| id | BIGINT UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Unique identifier |
| user_id | BIGINT UNSIGNED | FOREIGN KEY (users.id), NULL | Who made the change |
| user_name | VARCHAR(255) | NOT NULL | User name (stored for history) |
| action | ENUM('create', 'update', 'delete') | NOT NULL | Type of action |
| model_type | VARCHAR(255) | NOT NULL | Model class name |
| model_id | BIGINT UNSIGNED | NOT NULL | ID of affected record |
| old_values | JSON | NULL | Previous values |
| new_values | JSON | NULL | New values |
| ip_address | VARCHAR(45) | NULL | User IP address |
| user_agent | VARCHAR(500) | NULL | Browser user agent |
| created_at | TIMESTAMP | NOT NULL | When action occurred |

**Indexes:**
- user_id
- model_type, model_id
- created_at DESC

---

### Entity Relationship Diagram

```
users (1) ─────────────── (M) audit_logs
  │
  │ created_by/updated_by
  ▼
patients (1) ─────────── (1) patient_medical_info
  │
  │ patient_id
  ▼
encounters (1) ─────────── (M) documents
  │
  │ encounter_id
  ▼
discharge_papers (1 per hospitalization)
```

---

## User Roles & Permissions

### Role: Admin

**Can do:**
- Everything Administration can do
- Everything Staff can do
- Create user accounts
- Delete user accounts
- Assign roles to users
- View monthly reports/statistics

**Cannot do:**
- N/A (full access)

---

### Role: Administration

**Can do:**
- Register new patients
- Update patient basic information
- Create new visits
- Create new hospitalizations
- Search patients by name
- View patient records
- View surgical notes

**Cannot do:**
- Add/update medical info for hospitalizations
- Upload documents
- Upload discharge papers
- Discharge patients
- Create/delete user accounts
- View monthly reports

---

### Role: Staff

**Can do:**
- View staff queue (hospitalized patients)
- Complete medical info for hospitalized patients
- Add diagnosis, treatment, surgical notes
- Upload documents (diagnostic images, reports)
- Upload discharge papers
- Discharge patients
- Convert visit to hospitalization
- View patient records
- View surgical notes

**Cannot do:**
- Register new patients
- Create new visits/hospitalizations
- Update patient basic information
- Create/delete user accounts
- View monthly reports

---

### Permission Matrix

| Feature | Admin | Administration | Staff |
|---------|:-----:|:--------------:|:-----:|
| **User Management** |
| Create users | ✓ | ✗ | ✗ |
| Delete users | ✓ | ✗ | ✗ |
| View users | ✓ | ✗ | ✗ |
| **Patient Management** |
| Register patient | ✓ | ✓ | ✗ |
| Update basic info | ✓ | ✓ | ✗ |
| Search patients | ✓ | ✓ | ✓ |
| View patient | ✓ | ✓ | ✓ |
| **Encounters** |
| Create visit | ✓ | ✓ | ✗ |
| Create hospitalization | ✓ | ✓ | ✗ |
| View encounters | ✓ | ✓ | ✓ |
| Convert to hospitalization | ✓ | ✗ | ✓ |
| **Medical Info** |
| Complete medical info | ✓ | ✗ | ✓ |
| Add diagnosis/treatment | ✓ | ✗ | ✓ |
| Add surgical notes | ✓ | ✗ | ✓ |
| View surgical notes | ✓ | ✓ | ✓ |
| **Documents** |
| Upload documents | ✓ | ✗ | ✓ |
| View documents | ✓ | ✓ | ✓ |
| Upload discharge paper | ✓ | ✗ | ✓ |
| **Queue & Discharge** |
| View staff queue | ✓ | ✗ | ✓ |
| Discharge patient | ✓ | ✗ | ✓ |
| **Reports** |
| View monthly reports | ✓ | ✗ | ✗ |

---

## Application Structure

### Directory Structure

```
kavaja-hospital/
├── app/
│   ├── Filament/
│   │   ├── Resources/
│   │   │   ├── UserResource.php
│   │   │   ├── PatientResource.php
│   │   │   ├── EncounterResource.php
│   │   │   └── DocumentResource.php
│   │   ├── Pages/
│   │   │   ├── Dashboard.php
│   │   │   ├── StaffQueue.php
│   │   │   └── MonthlyReports.php
│   │   └── Widgets/
│   │       ├── StatsOverview.php
│   │       └── RecentAdmissions.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── PatientPortalController.php
│   │   └── Middleware/
│   │       └── CheckRole.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Patient.php
│   │   ├── PatientMedicalInfo.php
│   │   ├── Encounter.php
│   │   ├── Document.php
│   │   ├── DischargePaper.php
│   │   └── AuditLog.php
│   ├── Observers/
│   │   ├── PatientObserver.php
│   │   ├── EncounterObserver.php
│   │   └── DocumentObserver.php
│   ├── Policies/
│   │   ├── UserPolicy.php
│   │   ├── PatientPolicy.php
│   │   ├── EncounterPolicy.php
│   │   └── DocumentPolicy.php
│   └── Services/
│       ├── QrCodeService.php
│       ├── DischargePaperService.php
│       ├── AuditService.php
│       └── ReportService.php
├── database/
│   ├── migrations/
│   │   ├── 0001_create_users_table.php
│   │   ├── 0002_create_patients_table.php
│   │   ├── 0003_create_patient_medical_info_table.php
│   │   ├── 0004_create_encounters_table.php
│   │   ├── 0005_create_documents_table.php
│   │   ├── 0006_create_discharge_papers_table.php
│   │   └── 0007_create_audit_logs_table.php
│   └── seeders/
│       └── AdminUserSeeder.php
├── resources/
│   └── views/
│       └── patient-portal/
│           ├── verify.blade.php
│           ├── records.blade.php
│           └── layouts/
│               └── portal.blade.php
├── routes/
│   ├── web.php
│   └── api.php
└── storage/
    └── app/
        ├── documents/
        │   └── {patient_id}/
        └── discharge-papers/
            ├── originals/
            └── with-qr/
```

---

### Models

#### User.php

```php
class User extends Authenticatable
{
    protected $fillable = ['name', 'email', 'password', 'role'];
    
    protected $casts = [
        'role' => 'string', // 'admin', 'administration', 'staff'
    ];
    
    public function isAdmin(): bool
    public function isAdministration(): bool
    public function isStaff(): bool
    public function canManageUsers(): bool
    public function canRegisterPatients(): bool
    public function canManageMedicalInfo(): bool
    public function canViewReports(): bool
}
```

#### Patient.php

```php
class Patient extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'date_of_birth', 'gender',
        'phone_number', 'national_id', 'residency',
        'emergency_contact_name', 'emergency_contact_phone',
        'emergency_contact_relationship', 'health_insurance_number',
        'created_by'
    ];
    
    protected $casts = [
        'date_of_birth' => 'date',
    ];
    
    // Relationships
    public function medicalInfo(): HasOne
    public function encounters(): HasMany
    public function documents(): HasMany
    public function createdBy(): BelongsTo
    
    // Scopes
    public function scopeSearch($query, $name)
    
    // Accessors
    public function getFullNameAttribute(): string
    public function getAgeAttribute(): int
}
```

#### Encounter.php

```php
class Encounter extends Model
{
    protected $fillable = [
        'patient_id', 'type', 'status', 'main_complaint',
        'doctor_name', 'diagnosis', 'treatment', 'surgical_notes',
        'admission_date', 'discharge_date', 'medical_info_complete',
        'created_by', 'updated_by'
    ];
    
    protected $casts = [
        'admission_date' => 'datetime',
        'discharge_date' => 'datetime',
        'medical_info_complete' => 'boolean',
    ];
    
    // Relationships
    public function patient(): BelongsTo
    public function documents(): HasMany
    public function dischargePaper(): HasOne
    
    // Scopes
    public function scopeVisits($query)
    public function scopeHospitalizations($query)
    public function scopeActive($query)
    public function scopeNeedingMedicalInfo($query)
    
    // Methods
    public function isHospitalization(): bool
    public function isActive(): bool
    public function canBeConverted(): bool
    public function convertToHospitalization(): void
    public function discharge(): void
}
```

---

## Screens & User Interface

### 1. Login Screen

**URL:** `/admin/login`

**Elements:**
- Hospital logo
- Email input field
- Password input field
- Login button
- "Forgot password" link (optional)

**Validation:**
- Email: required, valid email format
- Password: required

---

### 2. Dashboard (All Roles)

**URL:** `/admin`

**Elements for Admin:**
- Welcome message with user name
- Quick stats cards:
  - Total patients
  - Active hospitalizations
  - Visits today
  - Discharges today
- Recent activity list
- Navigation to all sections

**Elements for Administration:**
- Welcome message
- Quick stats:
  - Patients registered today
  - Visits created today
  - Hospitalizations created today
- Quick action buttons:
  - "Register New Patient"
  - "Search Patient"

**Elements for Staff:**
- Welcome message
- Staff queue summary:
  - Patients needing medical info
  - Patients ready for discharge
- Quick link to Staff Queue

---

### 3. User Management (Admin Only)

**URL:** `/admin/users`

**List View Elements:**
- Table with columns: Name, Email, Role, Created Date
- Search by name/email
- Filter by role
- "Create User" button
- Edit/Delete actions per row

**Create/Edit Form Elements:**
- Name input (required)
- Email input (required, unique)
- Password input (required for create, optional for edit)
- Role dropdown: Admin, Administration, Staff
- Save/Cancel buttons

---

### 4. Patient List

**URL:** `/admin/patients`

**Elements:**
- Search box (search by name)
- Table with columns:
  - Name (last, first)
  - Date of Birth
  - Phone Number
  - National ID
  - Last Visit Date
- "Register New Patient" button (Admin, Administration only)
- Click row to view patient details

---

### 5. Patient Registration Form (Admin, Administration)

**URL:** `/admin/patients/create`

**Form Sections:**

**Section 1: Basic Information**
- First Name (required)
- Last Name (required)
- Date of Birth (required, date picker)
- Gender (required, dropdown: Male, Female, Other)
- Phone Number
- National ID (unique validation)
- Place of Residency

**Section 2: Emergency Contact**
- Contact Name
- Contact Phone
- Relationship to Patient

**Section 3: Insurance**
- Health Insurance Number

**Actions:**
- Save & Create Visit
- Save & Create Hospitalization
- Save Only
- Cancel

---

### 6. Patient Detail View

**URL:** `/admin/patients/{id}`

**Layout:**

**Top Section - Patient Header**
- Full name (large)
- Date of Birth / Age
- Gender
- Edit button (Admin, Administration)

**Left Column - General Information**
- Phone Number
- National ID
- Residency
- Emergency Contact (name, phone, relationship)
- Health Insurance Number

**Right Column - Medical Information (if exists)**
- Blood Type
- Height / Weight
- Allergies (highlighted if present)
- Smoking Status
- Alcohol Use
- Drug Use History
- Pacemaker/Implants
- Anesthesia Reactions
- Current Medications

**Bottom Section - History Timeline**
- Tabs: "All" | "Visits" | "Hospitalizations"
- List sorted by date (newest first)
- Each entry shows:
  - Date
  - Type badge (Visit / Hospitalization)
  - Status badge (Active / Discharged)
  - Main Complaint
  - Doctor Name
  - Diagnosis (if added)
- Click to expand/view full details

**Action Buttons:**
- "New Visit" (Admin, Administration)
- "New Hospitalization" (Admin, Administration)

---

### 7. Create Encounter Form

**URL:** `/admin/patients/{id}/encounters/create`

**Form Elements:**
- Encounter Type (radio: Visit / Hospitalization)
- Main Complaint (textarea, required)
- Doctor Name (text input, required)
- Admission Date (datetime, defaults to now)

**Actions:**
- Create (redirects based on type)
- Cancel

---

### 8. Encounter Detail View

**URL:** `/admin/encounters/{id}`

**Header:**
- Patient name (link to patient)
- Encounter type badge
- Status badge
- Admission date
- Discharge date (if discharged)

**Section: Visit/Hospitalization Details**
- Main Complaint
- Doctor Name
- Diagnosis (editable by Staff)
- Treatment (editable by Staff)

**Section: Surgical Notes (Staff can edit, visible to all staff roles)**
- Textarea for surgical/operative notes
- Warning: "Not visible to patients"

**Section: Medical Information (Hospitalizations only, Staff can edit)**
- All medical info fields
- "Mark as Complete" checkbox

**Section: Documents**
- List of uploaded documents
- Each shows: filename, type, upload date, uploaded by
- Download/View button
- "Upload Document" button (Staff)

**Section: Discharge Paper (Hospitalizations only)**
- If not uploaded: "Upload Discharge Paper" button
- If uploaded: 
  - View original
  - View/Download with QR code
  - "Replace Discharge Paper" button

**Actions (Staff):**
- "Convert to Hospitalization" (if visit)
- "Discharge Patient" (if hospitalization, active, has discharge paper)

---

### 9. Staff Queue (Admin, Staff)

**URL:** `/admin/staff-queue`

**Elements:**
- Page title: "Hospitalized Patients"
- Filter tabs: "Needs Medical Info" | "Ready for Discharge" | "All Active"
- Table with columns:
  - Patient Name
  - Admission Date/Time
  - Doctor
  - Main Complaint
  - Medical Info Status (Complete / Incomplete)
  - Discharge Paper Status (Uploaded / Not Uploaded)
- Click row to open encounter detail
- Sort by admission date (oldest first by default)

---

### 10. Monthly Reports (Admin Only)

**URL:** `/admin/reports`

**Elements:**

**Date Range Selector:**
- Month/Year picker
- "Generate Report" button

**Statistics Cards:**
- Total Patients (new registrations this month)
- Total Visits
- Total Hospitalizations
- Total Surgeries (encounters with surgical notes)
- Total Discharges

**Charts/Tables:**

**Patients per Day (Bar Chart)**
- X-axis: Days of month
- Y-axis: Count
- Separate bars for visits vs hospitalizations

**Most Common Diagnoses (Table)**
- Diagnosis text
- Count
- Top 10

**Doctors by Patient Count (Table)**
- Doctor name
- Visit count
- Hospitalization count
- Total

**Busiest Days of Week (Table)**
- Day name (Monday, Tuesday, etc.)
- Average patient count

---

### 11. Patient Portal - QR Verification

**URL:** `/patient/{token}`

**Elements:**
- Hospital logo
- Hospital name (styled with brand colors)
- Message: "Enter your date of birth to view your records"
- Date of Birth input (date picker)
- "View Records" button
- Error message area (if DOB doesn't match)

**Branding:**
- Hospital logo at top
- Hospital colors for buttons/accents

---

### 12. Patient Portal - Records View

**URL:** `/patient/{token}/records` (after verification)

**Elements:**

**Header:**
- Hospital logo
- Patient name
- "Your Medical Records"

**Section: Personal Information**
- Name, DOB, Gender
- Contact info
- Emergency contact
- Insurance number

**Section: Medical Information (if exists)**
- Blood type, Height, Weight
- Allergies (highlighted)
- Other medical details
- **NOT SHOWN:** (nothing indicates surgical notes exist)

**Section: Visit History**
- List of all encounters (newest first)
- Each shows:
  - Date
  - Type (Visit / Hospitalization)
  - Complaint
  - Doctor
  - Diagnosis
  - Treatment
  - **NOT SHOWN:** Surgical notes

**Section: Documents**
- List of diagnostic images and reports
- View/Download buttons
- **NOT SHOWN:** Discharge papers (they already have physical copy)

**Footer:**
- Hospital contact information

---

## Detailed User Flows

### Flow 1: New Patient - Visit

```
1. Administration logs in
2. Clicks "Register New Patient" or searches (patient not found)
3. Fills patient registration form:
   - Basic info (name, DOB, gender, etc.)
   - Emergency contact
   - Health insurance
4. Clicks "Save & Create Visit"
5. System creates patient record
6. System shows "Create Encounter" form with "Visit" pre-selected
7. Administration enters:
   - Main complaint
   - Doctor name
8. Clicks "Create"
9. System creates encounter (type=visit, status=active)
10. Patient goes to see doctor
11. [Later] Staff opens the encounter
12. Staff adds:
    - Diagnosis
    - Treatment
    - Documents (optional)
13. Clicks "Save"
14. Patient leaves (no discharge paper, no QR code)
```

### Flow 2: New Patient - Hospitalization

```
1. Administration logs in
2. Clicks "Register New Patient"
3. Fills patient registration form
4. Clicks "Save & Create Hospitalization"
5. System creates patient record
6. System shows "Create Encounter" form with "Hospitalization" pre-selected
7. Administration enters:
   - Main complaint
   - Doctor name
8. Clicks "Create"
9. System creates encounter (type=hospitalization, status=active)
10. Patient appears in Staff Queue (status: "Needs Medical Info")

11. Staff opens Staff Queue
12. Clicks on the patient
13. Staff completes medical information:
    - Blood type, Height, Weight
    - Allergies, Smoking status, etc.
14. Marks "Medical Info Complete"
15. Clicks "Save"

16. [During stay] Staff adds:
    - Diagnosis
    - Treatment
    - Surgical notes (if surgery performed)
    - Documents (X-rays, reports)

17. [Ready to discharge] Doctor writes discharge paper on their computer
18. Staff opens the encounter
19. Clicks "Upload Discharge Paper"
20. Selects PDF or Word file
21. System:
    - Saves original file
    - Generates unique QR token
    - Adds QR code to top-right corner
    - Saves modified file
22. Staff downloads/prints version with QR code
23. Staff clicks "Discharge Patient"
24. System:
    - Sets status to "discharged"
    - Sets discharge_date to now
25. Patient receives printed discharge paper and leaves
```

### Flow 3: Returning Patient

```
1. Administration logs in
2. Searches patient by name
3. Patient found in results
4. Clicks to open patient record
5. Reviews current information
6. Updates if needed (phone, insurance, etc.)
7. Clicks "New Visit" or "New Hospitalization"
8. Enters main complaint and doctor
9. Creates encounter
10. [Flow continues as Flow 1 or Flow 2]
```

### Flow 4: Convert Visit to Hospitalization

```
1. Patient came for visit but doctor decides hospitalization needed
2. Staff opens the visit encounter
3. Clicks "Convert to Hospitalization"
4. System confirms action
5. System changes encounter type to "hospitalization"
6. Encounter appears in Staff Queue
7. Staff completes medical information
8. [Flow continues as hospitalization]
```

### Flow 5: Patient Views Records via QR

```
1. Patient has discharge paper with QR code
2. Scans QR code with phone camera
3. Browser opens: /patient/{token}
4. Patient sees verification page
5. Enters date of birth
6. Clicks "View Records"
7. System validates DOB matches patient record
8. If match: redirects to /patient/{token}/records
9. If no match: shows error "Date of birth does not match"

10. Patient views their records:
    - Personal information
    - Medical information
    - All encounters (visits + hospitalizations)
    - All documents
    - [Cannot see surgical notes]
```

### Flow 6: Replace Discharge Paper

```
1. Doctor made error in discharge paper
2. Doctor writes new discharge paper
3. Staff opens the encounter
4. Clicks "Replace Discharge Paper"
5. System warns: "This will replace the existing discharge paper"
6. Staff confirms
7. Staff uploads new file
8. System:
    - Deletes old original file
    - Deletes old QR version file
    - Saves new original
    - Adds same QR code (same token)
    - Saves new QR version
9. Staff prints new version
```

---

## API Endpoints

### Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /admin/login | Staff login |
| POST | /admin/logout | Staff logout |

### Patients (Admin, Administration)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /admin/patients | List patients |
| GET | /admin/patients/search?q={name} | Search patients |
| GET | /admin/patients/{id} | View patient |
| POST | /admin/patients | Create patient |
| PUT | /admin/patients/{id} | Update patient basic info |

### Patient Medical Info (Admin, Staff)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /admin/patients/{id}/medical-info | Get medical info |
| PUT | /admin/patients/{id}/medical-info | Update medical info |

### Encounters

| Method | Endpoint | Description | Roles |
|--------|----------|-------------|-------|
| GET | /admin/encounters | List encounters | All |
| GET | /admin/encounters/{id} | View encounter | All |
| POST | /admin/patients/{id}/encounters | Create encounter | Admin, Administration |
| PUT | /admin/encounters/{id} | Update encounter | Admin, Staff |
| POST | /admin/encounters/{id}/convert | Convert to hospitalization | Admin, Staff |
| POST | /admin/encounters/{id}/discharge | Discharge patient | Admin, Staff |

### Staff Queue (Admin, Staff)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /admin/staff-queue | List active hospitalizations |
| GET | /admin/staff-queue?filter=needs-info | Filter by status |

### Documents (Admin, Staff)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /admin/encounters/{id}/documents | List documents |
| POST | /admin/encounters/{id}/documents | Upload document |
| GET | /admin/documents/{id}/download | Download document |
| DELETE | /admin/documents/{id} | Delete document |

### Discharge Papers (Admin, Staff)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /admin/encounters/{id}/discharge-paper | Upload discharge paper |
| GET | /admin/encounters/{id}/discharge-paper/original | Download original |
| GET | /admin/encounters/{id}/discharge-paper/with-qr | Download with QR |
| DELETE | /admin/encounters/{id}/discharge-paper | Delete (for replacement) |

### Patient Portal (Public)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /patient/{token} | Show verification page |
| POST | /patient/{token}/verify | Verify DOB |
| GET | /patient/{token}/records | View records (after verification) |
| GET | /patient/{token}/documents/{id} | Download document |

### Reports (Admin Only)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /admin/reports | Reports dashboard |
| GET | /admin/reports/data?month={m}&year={y} | Get report data |

### Users (Admin Only)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /admin/users | List users |
| POST | /admin/users | Create user |
| PUT | /admin/users/{id} | Update user |
| DELETE | /admin/users/{id} | Delete user |

---

## File Handling

### Storage Structure

```
storage/app/
├── documents/
│   └── {patient_id}/
│       └── {encounter_id}/
│           └── {uuid}.{ext}
└── discharge-papers/
    └── {patient_id}/
        └── {encounter_id}/
            ├── original.{ext}
            └── with-qr.{ext}
```

### Upload Document Flow

```php
// 1. Validate file
$request->validate([
    'file' => 'required|file|max:20480|mimes:jpg,jpeg,png,gif,pdf,doc,docx'
]);

// 2. Generate unique filename
$uuid = Str::uuid();
$extension = $file->getClientOriginalExtension();
$storedFilename = "{$uuid}.{$extension}";

// 3. Store file
$path = "documents/{$patient_id}/{$encounter_id}";
$file->storeAs($path, $storedFilename);

// 4. Create database record
Document::create([
    'encounter_id' => $encounter_id,
    'patient_id' => $patient_id,
    'type' => $this->determineType($file),
    'original_filename' => $file->getClientOriginalName(),
    'stored_filename' => $storedFilename,
    'file_path' => "{$path}/{$storedFilename}",
    'mime_type' => $file->getMimeType(),
    'file_size' => $file->getSize(),
    'uploaded_by' => auth()->id(),
]);
```

### Upload Discharge Paper Flow

```php
// 1. Validate file (PDF or Word only)
$request->validate([
    'file' => 'required|file|max:20480|mimes:pdf,doc,docx'
]);

// 2. Check if discharge paper already exists
$existing = DischargePaper::where('encounter_id', $encounter_id)->first();
if ($existing) {
    // Delete old files
    Storage::delete($existing->original_file_path);
    Storage::delete($existing->qr_file_path);
    $existing->delete();
}

// 3. Store original
$path = "discharge-papers/{$patient_id}/{$encounter_id}";
$extension = $file->getClientOriginalExtension();
$originalPath = "{$path}/original.{$extension}";
$file->storeAs($path, "original.{$extension}");

// 4. Generate QR token (or reuse existing)
$token = $existing?->qr_token ?? Str::random(64);

// 5. Generate QR code image
$qrCodeService = app(QrCodeService::class);
$qrImage = $qrCodeService->generate(url("/patient/{$token}"));

// 6. Add QR to document
$dischargePaperService = app(DischargePaperService::class);
$qrPath = "{$path}/with-qr.{$extension}";
$dischargePaperService->addQrCode($originalPath, $qrPath, $qrImage);

// 7. Create database record
DischargePaper::create([
    'encounter_id' => $encounter_id,
    'patient_id' => $patient_id,
    'original_file_path' => $originalPath,
    'original_filename' => $file->getClientOriginalName(),
    'qr_file_path' => $qrPath,
    'qr_token' => $token,
    'mime_type' => $file->getMimeType(),
    'uploaded_by' => auth()->id(),
]);
```

---

## QR Code System

### QR Code Service

```php
class QrCodeService
{
    public function generate(string $url): string
    {
        // Returns QR code as PNG image data
        return QrCode::format('png')
            ->size(150)
            ->margin(1)
            ->generate($url);
    }
}
```

### Discharge Paper Service

```php
class DischargePaperService
{
    public function addQrCode(string $inputPath, string $outputPath, string $qrImage): void
    {
        $extension = pathinfo($inputPath, PATHINFO_EXTENSION);
        
        if (in_array($extension, ['pdf'])) {
            $this->addQrToPdf($inputPath, $outputPath, $qrImage);
        } elseif (in_array($extension, ['doc', 'docx'])) {
            $this->addQrToWord($inputPath, $outputPath, $qrImage);
        }
    }
    
    private function addQrToPdf(string $inputPath, string $outputPath, string $qrImage): void
    {
        // Use FPDI to open existing PDF
        $pdf = new \setasign\Fpdi\Fpdi();
        
        // Get page count
        $pageCount = $pdf->setSourceFile(storage_path("app/{$inputPath}"));
        
        // Process each page
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $size = $pdf->getTemplateSize($templateId);
            
            $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
            
            // Add QR code only to first page, top-right corner
            if ($pageNo === 1) {
                // Save QR image temporarily
                $tempQr = tempnam(sys_get_temp_dir(), 'qr') . '.png';
                file_put_contents($tempQr, $qrImage);
                
                // Position: top-right with 10mm margin
                $qrSize = 25; // 25mm
                $x = $size['width'] - $qrSize - 10;
                $y = 10;
                
                $pdf->Image($tempQr, $x, $y, $qrSize, $qrSize);
                
                unlink($tempQr);
            }
        }
        
        $pdf->Output('F', storage_path("app/{$outputPath}"));
    }
    
    private function addQrToWord(string $inputPath, string $outputPath, string $qrImage): void
    {
        // Use PHPWord to open existing document
        $phpWord = \PhpOffice\PhpWord\IOFactory::load(storage_path("app/{$inputPath}"));
        
        // Save QR image temporarily
        $tempQr = tempnam(sys_get_temp_dir(), 'qr') . '.png';
        file_put_contents($tempQr, $qrImage);
        
        // Get first section
        $sections = $phpWord->getSections();
        $section = $sections[0];
        
        // Add header with QR code positioned right
        $header = $section->addHeader();
        $header->addImage($tempQr, [
            'width' => 70,
            'height' => 70,
            'positioning' => 'absolute',
            'posHorizontal' => 'right',
            'posVertical' => 'top',
        ]);
        
        // Save modified document
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save(storage_path("app/{$outputPath}"));
        
        unlink($tempQr);
    }
}
```

### QR Code URL Structure

```
https://hospital-domain.com/patient/{token}

Where {token} is a 64-character random string stored in discharge_papers.qr_token
```

---

## Audit Logging

### Automatic Logging via Observers

```php
// app/Observers/BaseObserver.php
abstract class BaseObserver
{
    protected function logAction(Model $model, string $action): void
    {
        $oldValues = $action === 'update' ? $model->getOriginal() : null;
        $newValues = $action === 'delete' ? null : $model->getAttributes();
        
        // Remove sensitive/unnecessary fields
        $excludeFields = ['password', 'remember_token', 'updated_at'];
        
        if ($oldValues) {
            $oldValues = array_diff_key($oldValues, array_flip($excludeFields));
        }
        if ($newValues) {
            $newValues = array_diff_key($newValues, array_flip($excludeFields));
        }
        
        AuditLog::create([
            'user_id' => auth()->id(),
            'user_name' => auth()->user()?->name ?? 'System',
            'action' => $action,
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

// app/Observers/PatientObserver.php
class PatientObserver extends BaseObserver
{
    public function created(Patient $patient): void
    {
        $this->logAction($patient, 'create');
    }
    
    public function updated(Patient $patient): void
    {
        $this->logAction($patient, 'update');
    }
    
    public function deleted(Patient $patient): void
    {
        $this->logAction($patient, 'delete');
    }
}
```

### What Gets Logged

| Model | Actions Logged |
|-------|----------------|
| Patient | create, update, delete |
| PatientMedicalInfo | create, update |
| Encounter | create, update (including discharge) |
| Document | create, delete |
| DischargePaper | create, update (replace), delete |
| User | create, update, delete |

### What Does NOT Get Logged

- Viewing records (reads)
- Login/logout
- Failed login attempts

---

## Monthly Reports

### Data Calculations

```php
class ReportService
{
    public function generateMonthlyReport(int $month, int $year): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        
        return [
            'period' => [
                'month' => $month,
                'year' => $year,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            
            'totals' => [
                'new_patients' => Patient::whereBetween('created_at', [$startDate, $endDate])->count(),
                'total_visits' => Encounter::where('type', 'visit')
                    ->whereBetween('admission_date', [$startDate, $endDate])->count(),
                'total_hospitalizations' => Encounter::where('type', 'hospitalization')
                    ->whereBetween('admission_date', [$startDate, $endDate])->count(),
                'total_surgeries' => Encounter::whereNotNull('surgical_notes')
                    ->whereBetween('admission_date', [$startDate, $endDate])->count(),
                'total_discharges' => Encounter::where('status', 'discharged')
                    ->whereBetween('discharge_date', [$startDate, $endDate])->count(),
            ],
            
            'patients_per_day' => $this->getPatientsPerDay($startDate, $endDate),
            'common_diagnoses' => $this->getCommonDiagnoses($startDate, $endDate),
            'doctors_by_patients' => $this->getDoctorsByPatients($startDate, $endDate),
            'busiest_days' => $this->getBusiestDays($startDate, $endDate),
        ];
    }
    
    private function getPatientsPerDay($start, $end): array
    {
        return Encounter::selectRaw('DATE(admission_date) as date, type, COUNT(*) as count')
            ->whereBetween('admission_date', [$start, $end])
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->toArray();
    }
    
    private function getCommonDiagnoses($start, $end): array
    {
        return Encounter::selectRaw('diagnosis, COUNT(*) as count')
            ->whereNotNull('diagnosis')
            ->whereBetween('admission_date', [$start, $end])
            ->groupBy('diagnosis')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();
    }
    
    private function getDoctorsByPatients($start, $end): array
    {
        return Encounter::selectRaw('doctor_name, type, COUNT(*) as count')
            ->whereBetween('admission_date', [$start, $end])
            ->groupBy('doctor_name', 'type')
            ->orderByDesc('count')
            ->get()
            ->groupBy('doctor_name')
            ->toArray();
    }
    
    private function getBusiestDays($start, $end): array
    {
        return Encounter::selectRaw('DAYNAME(admission_date) as day_name, COUNT(*) as count')
            ->whereBetween('admission_date', [$start, $end])
            ->groupBy('day_name')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }
}
```

---

## Security

### Authentication

- Laravel built-in authentication with Filament
- Session-based authentication
- Password hashing with bcrypt
- CSRF protection on all forms

### Authorization

- Role-based access control via Policies
- Middleware checks on all routes
- Filament resource policies

### File Security

- Files stored outside public directory
- Signed URLs for downloads (optional)
- File type validation on upload
- File size limits (20MB max)

### Patient Portal Security

- QR token is 64-character random string
- DOB verification required
- Session-based access after verification
- No direct access to files without verification

### Data Protection

- Surgical notes hidden from patients
- Audit log for accountability
- No bulk data export for non-admin

---

## Testing Checklist

### User Management Tests

- [ ] Admin can create new user with each role
- [ ] Admin can edit user details
- [ ] Admin can delete user
- [ ] Deleted user cannot login
- [ ] Administration cannot access user management
- [ ] Staff cannot access user management

### Patient Registration Tests

- [ ] Administration can register new patient with all fields
- [ ] Required fields are validated
- [ ] National ID must be unique
- [ ] Date of birth accepts valid dates
- [ ] Staff cannot register patients
- [ ] Patient appears in search after creation

### Patient Search Tests

- [ ] Search by first name works
- [ ] Search by last name works
- [ ] Search by full name works
- [ ] Partial name search works
- [ ] Search is case-insensitive
- [ ] No results shows appropriate message

### Visit Flow Tests

- [ ] Administration can create visit for new patient
- [ ] Administration can create visit for existing patient
- [ ] Visit appears in patient history
- [ ] Staff can add diagnosis to visit
- [ ] Staff can add treatment to visit
- [ ] Staff can upload documents to visit
- [ ] Visit does not appear in staff queue
- [ ] No discharge paper option for visits
- [ ] No QR code generated for visits

### Hospitalization Flow Tests

- [ ] Administration can create hospitalization
- [ ] Hospitalization appears in staff queue
- [ ] Status shows "Needs Medical Info" initially
- [ ] Staff can complete medical information
- [ ] Staff can mark medical info as complete
- [ ] Staff can add diagnosis and treatment
- [ ] Staff can add surgical notes
- [ ] Staff can upload documents
- [ ] Staff can upload discharge paper
- [ ] QR code is added to discharge paper
- [ ] Both original and QR version are saved
- [ ] Staff can replace discharge paper
- [ ] Old discharge paper is deleted on replace
- [ ] Staff can discharge patient
- [ ] Cannot discharge without discharge paper
- [ ] Discharged patient removed from staff queue
- [ ] Discharge date is recorded

### Convert Visit to Hospitalization Tests

- [ ] Staff can convert active visit
- [ ] Converted encounter changes type
- [ ] Converted encounter appears in staff queue
- [ ] Previous visit data is preserved
- [ ] Medical info can be added after conversion

### Document Upload Tests

- [ ] Staff can upload JPG images
- [ ] Staff can upload PNG images
- [ ] Staff can upload PDF files
- [ ] Staff can upload Word documents
- [ ] Invalid file types are rejected
- [ ] Files over 20MB are rejected
- [ ] Original filename is preserved
- [ ] Document appears in encounter list
- [ ] Document can be downloaded
- [ ] Document can be viewed

### Discharge Paper Tests

- [ ] Only PDF and Word files accepted
- [ ] QR code appears in top-right corner
- [ ] QR code contains correct URL
- [ ] Original file is preserved
- [ ] QR version can be downloaded
- [ ] Replace deletes old files
- [ ] Same QR token used after replace

### Patient Portal Tests

- [ ] QR code URL loads verification page
- [ ] Hospital branding is displayed
- [ ] Correct DOB grants access
- [ ] Incorrect DOB shows error
- [ ] Multiple incorrect attempts allowed
- [ ] Patient info is displayed correctly
- [ ] Visit history is displayed
- [ ] Hospitalization history is displayed
- [ ] Documents can be downloaded
- [ ] Surgical notes are NOT visible
- [ ] Discharge paper is NOT in document list

### Staff Queue Tests

- [ ] Only hospitalizations appear
- [ ] Only active hospitalizations appear
- [ ] Filter by "Needs Medical Info" works
- [ ] Filter by "Ready for Discharge" works
- [ ] Discharged patients do not appear
- [ ] Clicking row opens encounter

### Monthly Reports Tests

- [ ] Only admin can access reports
- [ ] Month/year selection works
- [ ] Patient count is accurate
- [ ] Visit count is accurate
- [ ] Hospitalization count is accurate
- [ ] Surgery count is accurate
- [ ] Discharge count is accurate
- [ ] Patients per day chart works
- [ ] Common diagnoses list is accurate
- [ ] Doctors by patients is accurate
- [ ] Busiest days calculation is correct

### Audit Log Tests

- [ ] Patient creation is logged
- [ ] Patient update is logged
- [ ] Encounter creation is logged
- [ ] Encounter update is logged
- [ ] Document upload is logged
- [ ] Discharge paper upload is logged
- [ ] User creation is logged
- [ ] User deletion is logged
- [ ] Correct user name is recorded
- [ ] Old and new values are recorded

### Role Permission Tests

- [ ] Admin has full access
- [ ] Administration can only register/search patients
- [ ] Administration can create encounters
- [ ] Administration cannot upload documents
- [ ] Administration cannot discharge patients
- [ ] Staff can access staff queue
- [ ] Staff cannot register patients
- [ ] Staff cannot create encounters
- [ ] Staff can manage medical info
- [ ] Staff can upload documents
- [ ] Staff can discharge patients

### Security Tests

- [ ] Unauthenticated users redirected to login
- [ ] Wrong password rejected
- [ ] Session expires after inactivity
- [ ] CSRF token required on forms
- [ ] Invalid QR token shows error
- [ ] Direct file access blocked
- [ ] SQL injection prevented
- [ ] XSS prevented in all inputs

### Mobile Responsiveness Tests

- [ ] Login page works on mobile
- [ ] Dashboard readable on mobile
- [ ] Patient list scrollable on mobile
- [ ] Forms usable on mobile
- [ ] Staff queue usable on mobile
- [ ] Patient portal works on mobile

---

## Implementation Order

### Phase 1: Foundation (Week 1)

1. Laravel project setup
2. Filament installation
3. Database migrations
4. Models with relationships
5. User authentication
6. Admin seeder

### Phase 2: Patient Management (Week 2)

1. Patient resource (CRUD)
2. Patient search
3. Patient medical info
4. Role-based permissions

### Phase 3: Encounters (Week 3)

1. Encounter resource
2. Visit flow
3. Hospitalization flow
4. Staff queue page
5. Convert to hospitalization

### Phase 4: Documents (Week 4)

1. Document upload
2. File storage
3. Document listing
4. Download functionality

### Phase 5: QR & Discharge (Week 5)

1. QR code service
2. Discharge paper upload
3. PDF QR injection
4. Word QR injection
5. Discharge flow

### Phase 6: Patient Portal (Week 6)

1. Portal routes
2. Verification page
3. Records view
4. Document access
5. Branding/styling

### Phase 7: Reports & Polish (Week 7)

1. Audit logging
2. Monthly reports
3. Report dashboard
4. Testing
5. Bug fixes

---

## Notes for Developer

1. **Use Filament Resources** for Patient, Encounter, Document, User management
2. **Use Filament Pages** for Staff Queue and Monthly Reports
3. **Blade templates** for Patient Portal (not Filament)
4. **Observers** for automatic audit logging
5. **Policies** for authorization
6. **Services** for QR and discharge paper logic
7. **Test each role** thoroughly after implementation
8. **Mobile-first** for staff queue and patient portal

---

## Hospital Branding

The following should be customizable:

- Hospital logo (uploaded to storage)
- Primary color (for buttons, headers)
- Secondary color (for accents)
- Hospital name
- Hospital contact information (for patient portal footer)

Store in config or database settings table.
