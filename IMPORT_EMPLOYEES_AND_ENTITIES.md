# Import Employees and Entities - Guide

This guide explains how to import employees and entities into the Tanseeq Asset Management System.

---

## Quick links

| Action | Where to go |
|--------|-------------|
| **Import Employees** | Employee Master → **Search Employees** → **Import** (or Entity Master → **Import Employees** button) |
| **Import Entities** | Entity Master → **Import Entities** button |

---

## 1. Importing Employees

### Step 1: Prepare your CSV file

Your Excel/CSV must have these columns (exact names or close variants):

| Column | Required | Notes |
|--------|----------|-------|
| **EmployeeID** | Yes | Unique identifier; rows without it are skipped |
| **Name** | No | Employee full name |
| **Designation** | No | Job title |
| **Department Name** or **Department** | No | Defaults to "N/A" if empty |
| **Email** | No | |
| **Phone** | No | |
| **Entity** or **Entity Name** or **Company** | No | Company/entity; use Default Entity if empty |

### Step 2: Save as CSV UTF-8

1. In Excel: **File → Save As**
2. Choose **CSV UTF-8 (Comma delimited) (*.csv)**
3. Save the file

### Step 3: Import

1. Go to **Employee Master** → **Search Employees** → **Import** (or **Entity Master** → **Import Employees**)
2. Options:
   - **Delete all existing employees** – Replaces all employees with the file (use with caution)
   - **Default Entity** – Used when a row has no Entity column or empty Entity
   - **Update Entity Master from Excel** – Adds entities from the file to Entity Master (recommended: keep checked)
3. Select your CSV file
4. Click **Import**

### CSV example

```csv
EmployeeID,Name,Designation,Department Name,Email,Phone,Entity
E001,John Doe,Developer,IT,john@company.com,555-1234,Tanseeq
E002,Jane Smith,Manager,HR,jane@company.com,555-5678,Proscape
```

---

## 2. Importing Entities

### Option A: From CSV file

Use when you have a list of entities in a spreadsheet.

1. Create a CSV with a column named **Entity**, **Entity Name**, or **Company**
2. Go to **Entity Master**
3. Click **Import Entities**
4. In the modal:
   - Check **Replace existing entities** if you want to replace the current list
   - Choose your CSV file
   - Click **Import**

### Option B: From existing employees

Use when employees are already imported and you want entities pulled from their records.

1. Go to **Entity Master**
2. Click **Import Entities**
3. In the modal, under **From Existing Employees**:
   - Check **Replace existing entities** if you want to replace the current list
   - Click **Sync from Employees**

---

## 3. Recommended order

1. **Import entities first** (if you have a CSV)  
   - Or use **Sync from Employees** after importing employees.
2. **Import employees**  
   - Turn on **Update Entity Master from Excel** so entities from the file are added.

---

## 4. Troubleshooting

| Issue | Solution |
|-------|----------|
| "No Entity column found" | Ensure your CSV has a column named Entity, Entity Name, or Company |
| "Could not read file" | Save as CSV UTF-8 in Excel; avoid special characters in the path |
| Rows skipped | Each row must have an EmployeeID; check for duplicate EmployeeIDs |
| Wrong encoding | Use **File → Save As → CSV UTF-8** in Excel |
| Empty Default Entity dropdown | Go to Entity Master and add entities manually, or run **Sync from Employees** |

---

## 5. Seed default entities (optional)

To add default entities (Proscape, bioscape, Tanseeq realty, etc.) without importing:

```bash
php artisan db:seed --class=EntitySeeder
```
