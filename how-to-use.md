## 🚀 Database Setup

### Step 1: Create Database Structure
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click "SQL" tab
3. Paste and run `database.sql`
4. Paste and run `rate_limit_table.sql`

### Step 2: Populate Test Data
1. Navigate to: http://localhost/SWAP-Project/create_users.php
   - Creates 4 users (admin, manager_user, staff_user, auditor_user)
   - All passwords: `password123`
2. Navigate to: http://localhost/SWAP-Project/create_sample_data.php
   - Populates inventory, suppliers, and sample requests

### Step 3: Login
Navigate to: http://localhost/SWAP-Project/auth/login.php

### quick troubleshoot, if cannot login, clear cookies, then refresh, then test again.



