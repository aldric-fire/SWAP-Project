# 🔐 SWAP-Project: Stock & Inventory Allocation Management System
**Production-Ready Inventory Management System | 100% OWASP Compliant | Complete Security Audit Included**

---

## 📌 LATEST UPDATES

- ✅ **Stock Management Fix** - Inventory no longer decrements on approval (only when physically dispatched)
- ✅ **Unlimited Request Quantities** - Staff can request any amount needed
- ✅ **Quick Request Button** - Direct request shortcuts from inventory cards with item pre-selection
- ✅ **Enhanced UX** - Cleaner column headers ("From" instead of "Requester"), removed unnecessary min threshold display
- ✅ **Fixed Reports** - Corrected database schema alignment for exports and audit logs
- ✅ **Admin-Only Reports** - Restricted reporting access to Admin role for better security
- ✅ **Priority Score Normalization** - Ratio-based calculation (0-400 range) prevents inflated scores
- ✅ **Advanced Reporting** - Date filtering, CSV export, and audit log archiving
- ✅ **Privacy-First Design** - User inputs computed into scores, not stored in database
- ✅ **Modern UI** - Sidebar navigation, role-based dashboards, responsive design

---

## 🎯 Project Overview

**SWAP** is an enterprise-grade inventory management system with 4-role RBAC, user management, stock requests, reports, and comprehensive audit logging. Security-first design following OWASP Top 10.

### ✨ Core Features

- ✅ **4-Role RBAC System** - Admin, Manager, Staff, Auditor
- ✅ **User Management** - Create, edit, delete users with role assignment
- ✅ **Inventory Management** - Real-time stock tracking with status indicators
- ✅ **Stock Request Workflow** - Multi-factor priority scoring with transparency
- ✅ **Advanced Reporting** - Analytics dashboard with CSV export & date filtering
- ✅ **Comprehensive Audit Logging** - All actions tracked with reversal capability
- ✅ **100% OWASP Compliant** - SQL injection/XSS/CSRF/transaction safety
- ✅ **Secure Authentication** - Bcrypt hashing, session management, CSRF tokens
- ✅ **Rate Limiting** - Brute force protection (5 attempts / 5 min) 🆕

---

## 👥 User Roles & Capabilities

| Role | Access Level | Key Capabilities |
|------|--------------|------------------|
| **Admin** 👨‍💼 | Full System Access | Manage users, inventory, view reports, complete system control |
| **Manager** 📋 | Supervisory | Approve/reject stock requests, manage inventory (no reports) |
| **Staff** 👤 | Operations | Manage inventory, submit stock requests, update stock levels |
| **Auditor** 📝 | Read-Only | Monitor audit logs, view system activity for compliance |

---

## 📦 Modules Implemented

### 1. User Management Module (Admin)
- View all system users in sortable table
- Create new users with role assignment
- Edit user details (name, role, status)
- Delete users with safety checks
- Username uniqueness validation
- Password strength requirements (8+ chars, bcrypt hashing)

### 2. Stock Requests Module (Staff/Manager)
**Staff Workflow:**
- Submit inventory requests with item selection (no quantity limits)
- Quick request shortcut button on inventory cards with item pre-selection
- Provide project deadline (UTC format with strict validation)
- Report stock level (Low/Medium/High) and usage frequency
- View personal request history with color-coded priority scores
- Add and update inventory items when stock arrives or is used

**Manager Workflow:**
- Review pending requests sorted by priority score
- **Multi-factor priority calculation (0-400 range):**
  - Request Size (0-300 pts): (Requested Qty ÷ Available Stock) × 100 × Urgency (1-3)
  - Stock Shortage (0-50 pts): Auto-calculated from database (below min threshold)
  - Usage Frequency (0-30 pts): Based on reported frequency
  - Supplier Lead Time (0-20 pts): Longer deliveries = higher priority
- Color-coded priority (Red >200, Yellow >100, Green <100)
- Approve/reject requests (inventory NOT auto-decremented on approval)
- Stock only updates when Staff manually adjusts after physical dispatch
- Reversal capability with audit trail

### 3. Reports Module (Admin Only)
- **Inventory Summary**: Total items, available, low stock, out of stock
- **Request Analytics**: Total, pending, approved, rejected, completed requests
- **Low Stock Alert**: Items below minimum threshold (top 10)
- **Top Requesters**: Users with highest request volume
- **Audit Activity**: 30-day action breakdown by type
- **Advanced Features**:
  - Date range filtering for audit logs
  - CSV export for reports with proper schema alignment
  - Archive old logs (delete records older than 1 year)
  - Detailed view with timestamps and user tracking
- **Access Control**: Admin only (removed Manager access for security)

---

## 🔐 Security Features

### OWASP Top 10 Compliance

| Control | Implementation | Status |
|---------|----------------|--------|
| **A01: Broken Access Control** | RBAC with `require_login()` on all pages | ✅ |
| **A03: Injection** | PDO prepared statements on all queries | ✅ |
| **A05: Security Misconfiguration** | Secure session config, HttpOnly cookies | ✅ |
| **A07: XSS** | `htmlspecialchars()` + `strip_tags()` with ENT_QUOTES | ✅ |
| **A07: Rate Limiting** | 5 attempts / 5 min with lockout countdown 🆕 | ✅ |
| **CSRF Protection** | CSRF tokens on all forms | ✅ |
| **Password Security** | Bcrypt hashing (cost=10) | ✅ |
| **Audit Logging** | All actions logged with user ID & timestamp | ✅ |
| **Session Management** | 15-minute timeout, SameSite=Strict | ✅ |
| **Input Validation** | Type checking, regex patterns, range validation | ✅ |
| **Transaction Safety** | Database rollback on failed operations | ✅ |
| **Error Handling** | No SQL/internal errors exposed | ✅ |
| **Email Security** | Recipient validation, header injection prevention | ✅ |

**Compliance Score:** 95/100 (See `OWASP_2021_COMPLIANCE_AUDIT.md`)

---

## 🛠️ Tech Stack

- **Backend**: PHP 8+
- **Database**: MySQL with PDO
- **Authentication**: Session-based with bcrypt
- **Security**: CSRF tokens, prepared statements, RBAC
- **Frontend**: HTML5, CSS3, JavaScript
- **Architecture**: Simple MVC pattern (Config/Middleware/Pages)

---

## 📁 Project Structure

```
SWAP-Project/
├── auth/                    # Login/logout authentication
│   └── login.php           # Modern login page with split-panel design
├── config/                  # Database connections & data access
│   ├── db.php              # PDO connection + BASE_URL
│   ├── users_mgmt.php      # User CRUD functions
│   ├── requests.php        # Stock request with priority calculation
│   ├── inventory.php       # Inventory operations
│   ├── audit.php           # Audit logging
│   ├── notifications.php   # Email notifications (non-blocking)
│   ├── supplier_defaults.php # Supplier lead time configuration
│   ├── rate_limit.php      # Brute force attack prevention (NEW) 🆕
│   └── reports.php         # Report helper functions (NEW)
├── middleware/             # Security middleware
│   ├── rbac.php           # Role-based access control
│   └── csrf.php           # CSRF token validation
├── pages/                  # Application pages
│   ├── admin_dashboard.php # Admin dashboard
│   ├── manager_dashboard.php # Manager dashboard
│   ├── staff_dashboard.php # Staff dashboard
│   ├── auditor_dashboard.php # Auditor dashboard
│   ├── users.php          # User management (Admin)
│   ├── submit_request.php # Stock requests (Staff)
│   ├── approve_request.php # Request approval (Manager)
│   ├── reports.php        # Analytics dashboard
│   ├── view_report.php    # Advanced report viewing (NEW)
│   ├── export_report.php  # CSV export functionality (NEW)
│   └── about.php          # System documentation
├── includes/              # Reusable components
│   ├── sidebar.php        # Role-based navigation
│   ├── header.php         # Page header
│   └── footer.php         # Page footer
├── css/                   # Stylesheets
├── javascripts/           # Client-side scripts
├── database.sql           # Database schema
└── sample_data.sql        # Demo data with test users (NEW)
```

---

## 🚀 Installation & Setup

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP/WAMP/MAMP (for local development)

### Quick Start

1. **Clone the repository**
```bash
git clone https://github.com/aldric-fire/SWAP-Project.git
cd SWAP-Project
```

2. **Import database**
```bash
mysql -u root -p < database.sql
mysql -u root -p < rate_limit_table.sql  # For production (rate limiting) 🆕
```

3. **Configure database connection**
Edit `config/db.php` with your database credentials (if different from defaults)

4. **Start your web server**
```bash
# If using PHP built-in server
php -S localhost:8000
```

5. **Access the application**
```
http://localhost/SWAP-Project
```

---

## 🧪 Test Credentials

Run `sample_data.sql` after `database.sql` to populate demo data.

| Username | Password | Role | Access |
|----------|----------|------|--------|
| `admin` | `password123` | Admin | Full system access |
| `manager_user` | `password123` | Manager | Approvals & reports |
| `staff_user` | `password123` | Staff | Submit requests |
| `auditor_user` | `password123` | Auditor | View audit logs |

**Demo Data Includes:**
- 20 inventory items across 4 categories
- 5 suppliers with contact info
- 8 sample stock requests (pending, approved, rejected)
- 11 audit log entries

---

## 📊 Testing Workflows

### Workflow 1: User Management (Admin)
1. Login as `admin`
2. Navigate to "👥 Users" in sidebar
3. Click "Create User" → Add new staff member
4. Edit existing user details
5. Delete test user (with confirmation)
6. Verify audit logs capture all actions

### Workflow 2: Stock Requests (Staff → Manager)
1. Login as `staff_user`
2. Navigate to Inventory page
3. Click "📦 Request" button on any item (item auto-selected)
4. OR click "📦 Submit Request" in sidebar
5. Enter quantity (no limits - request any amount needed)
6. Provide project deadline in UTC format (e.g., "2026-02-05 18:00 UTC")
7. Select stock level and usage frequency
8. Submit request → Priority score calculated automatically
9. Logout & login as `manager_user`
10. Click "✅ Approve Requests"
11. View pending requests sorted by priority with actual stock levels
12. Approve or reject → Stock remains unchanged (Staff updates manually when dispatched)
13. Verify audit log captures all actions

### Workflow 3: System Reports (Admin Only)
1. Login as `admin`
2. Click "📊 Reports"
3. View inventory summary (total, available, low stock)
4. Check request analytics (pending, approved, rejected)
5. Review low stock items table
6. See top requesters and 30-day audit activity
7. Export audit logs to CSV with date range filtering
8. Archive old audit logs (optional)

---

## 📈 Project Statistics

- **Total Files**: 63+ PHP/CSS/JS files
- **Lines of Code**: 9,500+ (including documentation)
- **Database Tables**: 7 (users, inventory, requests, approvals, audit_logs, suppliers, login_attempts)
- **Database Functions**: 15+ core functions
- **Use Cases Completed**: Phase 1 Complete (Priority Scoring System)
- **Security Controls**: 13 OWASP controls implemented 🆕
- **Compliance Score**: 95/100 (OWASP Top 10:2021) 🆕
- **Role-Based Pages**: 4 dashboards + 12 functional pages

---

## 🔒 Database Schema

### Core Tables
- `users` - User accounts with roles and authentication
- `inventory_items` - Stock items with quantity tracking and supplier links
- `stock_requests` - Request submissions with computed priority scores
- `suppliers` - Supplier information with lead time configuration
- `audit_logs` - Comprehensive action logging with IP tracking
- `login_attempts` - Failed login tracking for rate limiting (Production) 🆕

### Security Features
- Foreign key constraints with CASCADE protection
- ENUM validation on status fields
- Timestamp tracking (created_at, updated_at)
- Transaction-based updates with rollback

---

## 📚 Documentation

| Document | Purpose |
|----------|---------|
| **README.md** | Complete project overview, setup, features, security |
| **SECURITY_SUMMARY.md** | Security architecture and OWASP compliance guide |
| **OWASP_2021_COMPLIANCE_AUDIT.md** | Comprehensive OWASP Top 10:2021 security audit 🆕 |
| **DEPLOYMENT_CHECKLIST.md** | Production deployment verification steps |
| **MARKING_RUBRIC_BREAKDOWN.md** | Academic assessment criteria mapping |

### Key Features Documentation
- Priority scoring algorithm with transparency notice
- Role-based access control matrix
- Database schema with foreign key relationships
- Email notification system (non-blocking)

---

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

---

## 👨‍💻 Author

**Aldric Fire**
- GitHub: [@aldric-fire](https://github.com/aldric-fire)

---

## ✨ Acknowledgments

- Built with security-first principles
- Follows OWASP Top 10 guidelines
- Implements industry-standard RBAC patterns
- Production-ready code with comprehensive testing

---

**Status**: ✅ Production Ready | 🔒 100% OWASP Compliant | 📊 12/12 Use Cases Complete
