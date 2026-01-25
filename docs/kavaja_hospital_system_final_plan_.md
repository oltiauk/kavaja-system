# Kavaja Hospital System - Final Plan

## Overview

A web-based hospital management system for digitizing patient records. Staff access via computers and phones. Hospitalized patients can view their records by scanning a QR code.

---

## Technology

- **Backend:** Laravel 11 with Filament (admin panel)
- **Patient Portal:** Custom Laravel page (for QR code access)
- **Database:** MySQL
- **File Storage:** Local storage (or cloud if hospital server isn't suitable)
- **Hosting:** Web-based (hospital server if capable, otherwise cloud)
- **Branding:** Hospital logo and colors throughout the system

---

## User Roles

| Role | Can Do |
|------|--------|
| **Admin** | Create/delete user accounts, assign roles, view monthly reports, full system access |
| **Administration** | Register patients, create visits/hospitalizations, update basic patient info |
| **Staff** | Add medical details, upload documents, add diagnosis/treatment, discharge patients |

---

## Patient Information

### Basic Info (Administration enters):
- Name
- Surname
- Date of birth
- Gender
- Phone number
- National ID number
- Place of residency
- Emergency contact
- Health insurance number

### Medical Info (Staff enters - for hospitalizations only):
- Blood type
- Height
- Weight
- Allergies
- Smoking status (smoker, non-smoker, former smoker)
- Alcohol use
- Drug use history
- Pacemaker or implants
- Previous bad reactions to anesthesia
- Current medications

---

## Patient Encounters

### Visit (Outpatient)
- Quick appointment, patient leaves same day
- Administration enters: basic info + main complaint + doctor
- Staff adds: diagnosis, treatment, documents (if any)
- No QR code generated
- No discharge paper

### Hospitalization (Inpatient)
- Patient stays in hospital
- Administration enters: basic info + main complaint + doctor
- Patient appears in Staff queue
- Staff completes: medical info + diagnosis + treatment + surgical notes + documents
- When ready to discharge:
  1. Doctor writes discharge paper on their computer (PDF or Word)
  2. Staff uploads discharge paper to system (separate "Upload Discharge Paper" button)
  3. System adds QR code to top right corner
  4. System keeps both versions (original + with QR code)
  5. Staff prints the version with QR code
  6. Staff clicks "Discharge Patient"
  7. Patient leaves with printed discharge paper
- Only one discharge paper allowed per hospitalization (new upload replaces old, old is deleted)

---

## System Flows

### New Patient - Visit
1. Patient arrives
2. Administration selects "New Patient" → "Visit"
3. Administration enters basic info, complaint, doctor
4. Patient sees doctor
5. Staff adds diagnosis, treatment, documents (if any)
6. Patient leaves (no QR code, no discharge paper)

### New Patient - Hospitalization
1. Patient arrives
2. Administration selects "New Patient" → "Hospitalization"
3. Administration enters basic info, complaint, doctor
4. Patient appears in Staff queue
5. Staff completes medical info
6. Staff adds diagnosis, treatment, surgical notes, documents over time
7. Doctor writes discharge paper (PDF or Word)
8. Staff clicks "Upload Discharge Paper" button
9. System adds QR code to top right corner (keeps both versions)
10. Staff prints version with QR code
11. Staff clicks "Discharge Patient"
12. Patient leaves with printed discharge paper

### Returning Patient
1. Administration searches patient by name
2. Patient found - basic info already saved
3. Administration updates any changed info (if needed)
4. Administration creates new Visit or Hospitalization
5. Flow continues as above

### Convert Visit to Hospitalization
1. Doctor decides patient needs hospitalization
2. Staff clicks "Convert to Hospitalization"
3. Patient moves to Hospitalizations section
4. Patient appears in Staff queue
5. Staff completes medical details

---

## Staff Queue

Staff sees a list of hospitalized patients showing:
- Patient name
- Admission date/time
- Status (Needs medical info / Complete)
- Click to open and add information

---

## Document Uploads

Two separate upload buttons:
- **"Upload Document"** - for diagnostic images, reports, and other files (no QR code added)
- **"Upload Discharge Paper"** - specifically for discharge paper (system adds QR code to top right corner)

### Diagnostic Files
- Staff uploads images (photos from phone) or documents (PDF, Word, etc.)
- System automatically records upload date/time
- Can upload image only, report only, or both
- Files linked to the specific visit or hospitalization

### Discharge Paper
- Doctor writes on personal computer (PDF or Word)
- Staff uploads via "Upload Discharge Paper" button
- System adds QR code to top right corner
- System saves both versions (original + with QR code)
- Only one discharge paper per hospitalization
- New upload replaces old one (old is deleted completely)

---

## Patient QR Portal

1. Patient scans QR code on discharge paper
2. System asks for date of birth (security)
3. Patient enters DOB
4. Patient sees:
   - Their general information
   - Full history (visits + hospitalizations, most recent first)
   - Diagnostic images and reports
5. Patient CANNOT see:
   - Surgical/operative notes
6. QR code works forever
7. Page matches hospital branding (logo + colors)

---

## Audit Log

- System automatically tracks all changes
- Records: who made the change, what changed, when
- Based on logged-in user account
- Tracks modifications only (not views)

---

## Monthly Reports (Admin Only)

Admin can view statistics inside the system:
- Number of patients per day/week/month
- Most common diagnoses
- Number of hospitalizations
- Number of surgeries
- Which doctors see the most patients
- Busiest days of the week

---

## Role Permissions

| Feature | Admin | Administration | Staff | Patient (QR) |
|---------|-------|----------------|-------|--------------|
| Create user accounts | ✓ | ✗ | ✗ | ✗ |
| Delete user accounts | ✓ | ✗ | ✗ | ✗ |
| View monthly reports | ✓ | ✗ | ✗ | ✗ |
| Register new patients | ✓ | ✓ | ✗ | ✗ |
| Create visit/hospitalization | ✓ | ✓ | ✗ | ✗ |
| View staff queue | ✓ | ✗ | ✓ | ✗ |
| Add medical details | ✓ | ✗ | ✓ | ✗ |
| Upload documents | ✓ | ✗ | ✓ | ✗ |
| Upload discharge paper | ✓ | ✗ | ✓ | ✗ |
| Add surgical notes | ✓ | ✗ | ✓ | ✗ |
| Discharge patient | ✓ | ✗ | ✓ | ✗ |
| View patient records | ✓ | ✓ | ✓ | ✓ (own only) |
| View surgical notes | ✓ | ✓ | ✓ | ✗ |

---

## Patient Record View

### Top Section - General Information
All basic patient info that stays the same:
- Name, Surname, DOB, Gender
- Phone, National ID, Residency
- Emergency contact, Health insurance
- (For hospitalized: Blood type, Height, Weight, Allergies, Smoking status, Alcohol use, Drug use, Pacemaker/implants, Anesthesia reactions, Current medications)

### History Section - Timeline
All visits and hospitalizations together:
- Most recent at the top
- Shows date, type (visit/hospitalization), complaint, doctor, diagnosis
- Click to see full details including documents

---

## Summary

- **3 roles:** Admin, Administration, Staff
- **2 encounter types:** Visit, Hospitalization
- **QR access:** Hospitalized patients only, requires DOB, works forever
- **Discharge paper:** Doctor writes it (PDF/Word), staff uploads, system adds QR code to top right corner
- **Built with:** Laravel 11 + Filament + MySQL
- **Accessible on:** Computers and phones (web browser)
- **Branding:** Hospital logo and colors in system and patient portal
