# Production Deployment & Security Verification Checklist
**OWASP Top 10:2021 Compliant** | **Last Updated:** January 30, 2026

## ✅ Pre-Deployment Verification

### Security Infrastructure
- [ ] Rate limiting implemented
  - [ ] `config/rate_limit.php` exists
  - [ ] `login_attempts` table created (run `rate_limit_table.sql`)
  - [ ] Login page includes rate limit checks
  - [ ] Max 5 attempts per 5 minutes enforced

### Code Review
- [ ] All new files created successfully
  - [ ] `config/supplier_defaults.php` exists
  - [ ] `config/notifications.php` exists
  - [ ] `config/email_config.php` exists
  - [ ] `config/rate_limit.php` exists ✨ NEW
  - [ ] `config/reports.php` exists ✨ NEW
  - [ ] `pages/view_report.php` exists ✨ NEW
  - [ ] `pages/export_report.php` exists ✨ NEW
  - [ ] `pages/approve_request.php` updated
  - [ ] `config/requests.php` enhanced

- [ ] No syntax errors in new files
  - [ ] Run: `php -l auth/login.php` (check syntax)
  - [ ] Run: `php -l config/rate_limit.php`
  - [ ] No fatal errors in PHP logs

- [ ] Documentation complete
  - [ ] `OWASP_2021_COMPLIANCE_AUDIT.md` exists ✨ NEW
  - [ ] `README.md` updated with final features
  - [ ] `SECURITY_SUMMARY.md` updated
  - [ ] `rate_limit_table.sql` exists

### Security Verification (OWASP Top 10:2021)

#### A01: Broken Access Control
- [ ] All pages require authentication (`require_login()`)
- [ ] Role-based access enforced (`require_role(['Admin'])`)
- [ ] No direct object reference vulnerabilities
- [ ] Session validation on every request ✓

#### A02: Cryptographic Failures  
- [ ] Passwords hashed with bcrypt (cost=10) ✓
- [ ] Session IDs regenerated on login ✓
- [ ] HTTPOnly cookies enabled ✓
- [ ] No plaintext sensitive data in logs ✓
- [ ] Database credentials moved to `.env` (production) ⚠️

#### A03: Injection
- [ ] **100% PDO prepared statement coverage** ✓
- [ ] `PDO::ATTR_EMULATE_PREPARES => false` ✓
- [ ] All inputs validated with `filter_var()` ✓
- [ ] All outputs escaped with `htmlspecialchars(ENT_QUOTES, 'UTF-8')` ✓

#### A07: Authentication Failures
- [ ] **Rate limiting active** (5 attempts / 5 minutes) ✨ NEW
- [ ] Password minimum 8 characters ✓
- [ ] Session timeout 15 minutes ✓
- [ ] Timing attack mitigation (dummy hash) ✓
- [ ] Failed login attempts logged ✨ NEW

#### A08: Software and Data Integrity
- [ ] CSRF tokens on all forms ✓
- [ ] Database transactions with rollback ✓
- [ ] No `unserialize()` or `eval()` usage ✓

#### A09: Security Logging
- [ ] Audit logs for all critical actions ✓
- [ ] User login/logout logged ✓
- [ ] Failed login attempts logged ✨ NEW
- [ ] Request approval/rejection logged ✓
- [ ] Logs immutable (INSERT only) ✓

### Database Verification
- [ ] Database connection working
- [ ] All required tables present:
  - [ ] `users`
  - [ ] `suppliers`
  - [ ] `inventory_items`
  - [ ] `stock_requests`
  - [ ] `audit_logs`
  - [ ] `login_attempts` ✨ NEW (run `rate_limit_table.sql`)

- [ ] Schema requirements
  - [ ] Confirmed: Minimal schema changes (only `login_attempts` table) ✓
  - [ ] Confirmed: Backward compatible with existing data ✓

- [ ] Rate limiting table test
  ```sql
  -- Verify table exists
  SHOW TABLES LIKE 'login_attempts';
  
  -- Should return: login_attempts
  ```

### Functionality Testing

#### Supplier Lead Times
- [ ] Run `PHASE1_VERIFICATION.php` → "Supplier Lead Times" test
- [ ] Verify default lead time (should be 7 days)
- [ ] Test with known supplier IDs

#### Priority Calculation
- [ ] Run `PHASE1_VERIFICATION.php` → "Priority Calculation" test
- [ ] Verify scoring increases with:
  - [ ] Higher quantity
  - [ ] Higher urgency (Low < Medium < High)
  - [ ] Higher frequency
  - [ ] Longer lead time

#### Frequency Tracking
- [ ] Run `PHASE1_VERIFICATION.php` → "Frequency Tracking" test
- [ ] Verify frequency query returns correct counts
- [ ] Test with and without 30-day requests

#### Email System
- [ ] Run `PHASE1_VERIFICATION.php` → "Email Notification System" test
- [ ] Verify feature flags are readable
- [ ] Check that email config file loads

---

## 🚀 Deployment Steps

### Step 1: Pre-Deployment Backup
- [ ] Backup database (mysqldump)
- [ ] Backup current code (git commit)
- [ ] Document current version

### Step 2: Deploy New Files
Copy to production:
```bash
cp config/supplier_defaults.php → /path/to/SWAP-Project/config/
cp config/notifications.php → /path/to/SWAP-Project/config/
cp config/email_config.php → /path/to/SWAP-Project/config/
```

### Step 3: Deploy Updated Files
Copy to production:
```bash
cp config/requests.php → /path/to/SWAP-Project/config/
cp pages/approve_request.php → /path/to/SWAP-Project/pages/
```

### Step 4: Deploy Documentation
Copy to production:
```bash
cp PHASE1_IMPLEMENTATION.md → /path/to/SWAP-Project/
cp PHASE1_QUICK_REFERENCE.md → /path/to/SWAP-Project/
cp PHASE1_VERIFICATION.php → /path/to/SWAP-Project/
cp PHASE1_SUMMARY.md → /path/to/SWAP-Project/
```

### Step 5: Verify Deployment
- [ ] All files in place (check file list below)
- [ ] No file permission issues
- [ ] Run `PHASE1_VERIFICATION.php` to validate

### Step 6: Configuration
- [ ] Configure suppliers in `config/supplier_defaults.php`
  - [ ] Add suppliers to `$SUPPLIER_LEAD_TIMES` array
  - [ ] Example: `1 => 7` (Supplier ID 1: 7 days)

- [ ] Configure email in `config/email_config.php`
  - [ ] Set `ENABLE_EMAIL_NOTIFICATIONS` = true/false
  - [ ] Set `ENABLE_APPROVAL_EMAILS` = true/false
  - [ ] Set `ENABLE_REJECTION_EMAILS` = true/false
  - [ ] Set `ENABLE_LOW_STOCK_ALERTS` = true/false

---

## 📋 File Checklist (After Deployment)

### New Files (Should Exist)
```
☐ SWAP-Project/
  ├── config/
  │   ├── supplier_defaults.php       (43 bytes)
  │   ├── notifications.php           (5+ KB)
  │   └── email_config.php            (1 KB)
  ├── PHASE1_IMPLEMENTATION.md        (15+ KB)
  ├── PHASE1_QUICK_REFERENCE.md       (10+ KB)
  ├── PHASE1_VERIFICATION.php         (8+ KB)
  └── PHASE1_SUMMARY.md               (12+ KB)
```

### Modified Files (Should Be Updated)
```
☐ SWAP-Project/
  ├── config/
  │   └── requests.php                (Updated with 5 new functions)
  └── pages/
      └── approve_request.php         (Updated with email hooks)
```

### Verification
```bash
# In SWAP-Project directory:
ls -la config/supplier_defaults.php
ls -la config/notifications.php
ls -la config/email_config.php
ls -la PHASE1_*.md PHASE1_*.php

# All should exist and have recent timestamps
```

---

## 🧪 Post-Deployment Testing

### Test 1: Verification Suite
1. Open browser: `http://localhost/SWAP-Project/PHASE1_VERIFICATION.php`
2. Verify all 8 tests pass:
   - ☐ Database Connection
   - ☐ Supplier Lead Times
   - ☐ Delivery Date Calculation
   - ☐ Frequency Tracking
   - ☐ Priority Calculation
   - ☐ Email System
   - ☐ Database Schema
   - ☐ Security Features

### Test 2: Submit Request Workflow
1. Login as Staff user
2. Navigate to "Submit Stock Request"
3. Fill out form:
   - Select an item
   - Enter quantity: 5
   - Select urgency: Low
   - Submit

4. Verify:
   - ☐ Request submitted successfully
   - ☐ No errors in logs
   - ☐ Priority calculated (check database)

### Test 3: Manager Approval Workflow
1. Login as Manager user
2. Navigate to "Approve Stock Requests"
3. Verify table has new columns:
   - ☐ Stock Level (current / min)
   - ☐ Frequency (30d)
   - ☐ Exp. Delivery

4. Click "Approve" on a request
5. Verify:
   - ☐ Request marked as Approved
   - ☐ No errors in logs
   - ☐ (Optional) Email sent to staff (if enabled)

### Test 4: Priority Scoring Validation
1. Submit multiple requests with different parameters
2. Check priority scores increase correctly:
   - ☐ Higher quantity → Higher priority
   - ☐ Higher urgency → Higher priority
   - ☐ Below minimum stock → Higher priority

### Test 5: Email Notifications (If Enabled)
1. Configure `config/email_config.php`:
   - Set `ENABLE_APPROVAL_EMAILS = true`
   - Set `ENABLE_REJECTION_EMAILS = true`

2. Submit a request as staff
3. Approve as manager
4. Check for email:
   - ☐ Email arrives (or check mailserver logs)
   - ☐ Content is correct (item name, quantity, etc.)
   - ☐ No sensitive data exposed

---

## 🔍 Troubleshooting

### Issue: "Call to undefined function"
**Solution:**
- Verify `config/supplier_defaults.php` exists
- Verify `config/notifications.php` exists
- Check PHP error logs for include path issues

### Issue: "Email not sending"
**Solution:**
- Check `config/email_config.php` → `ENABLE_EMAIL_NOTIFICATIONS`
- Verify server has mail() function working
- Check error_log for "Email notification error:" messages
- Note: This doesn't block request approval

### Issue: "Frequency shows 0 for all items"
**Solution:**
- Normal if no requests in last 30 days
- Submit test request, wait, then check again
- Verify `stock_requests` table has data with `created_at` timestamps

### Issue: "Wrong priority scores"
**Solution:**
- Check supplier lead time in `config/supplier_defaults.php`
- Verify item `min_threshold` in database
- Verify `quantity` parameter is integer
- Run `PHASE1_VERIFICATION.php` → "Priority Calculation" test

### Issue: "Manager dashboard shows old columns"
**Solution:**
- Clear browser cache (Ctrl+Shift+Del or Cmd+Shift+Del)
- Verify `pages/approve_request.php` was updated
- Check file modification timestamp (should be recent)

---

## 📊 Performance Monitoring

### Queries to Monitor
```sql
-- Check frequency query performance
SELECT item_id, COUNT(*) as freq 
FROM stock_requests 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY item_id;

-- Should execute in <100ms with proper indexing
```

### Database Indexing (Already Present)
- ☐ Primary key on `stock_requests.request_id`
- ☐ Foreign key on `stock_requests.item_id`
- ☐ Consider adding index on `stock_requests.created_at` if not present

### Error Log Monitoring
Monitor `/path/to/error_log` for:
- ☐ Email notification errors (safe to ignore if not critical)
- ☐ Database connection errors (require attention)
- ☐ Syntax errors (require immediate fix)

---

## 🎯 Success Criteria

✅ **Phase 1 Deployment is Successful When:**

1. **All Files Present**
   - ☐ 3 new config files created
   - ☐ 2 files updated with new functions
   - ☐ 4 documentation files created

2. **All Tests Pass**
   - ☐ `PHASE1_VERIFICATION.php` shows 8/8 tests passing
   - ☐ No fatal errors in PHP logs
   - ☐ Database connection verified

3. **Workflows Functional**
   - ☐ Staff can submit requests
   - ☐ Manager dashboard displays correctly
   - ☐ Priority scores calculated
   - ☐ Frequency badges show correctly

4. **Security Maintained**
   - ☐ All queries use prepared statements
   - ☐ All output properly escaped
   - ☐ RBAC enforcement working
   - ☐ No sensitive data exposure

5. **No Regressions**
   - ☐ Existing features still work
   - ☐ No new errors introduced
   - ☐ Database integrity maintained
   - ☐ Session management unchanged

---

## 📝 Rollback Plan (If Needed)

### Quick Rollback (If Critical Issue)
1. Restore database backup (no data changes, just code)
2. Delete new config files:
   - `config/supplier_defaults.php`
   - `config/notifications.php`
   - `config/email_config.php`
3. Restore previous versions of:
   - `config/requests.php`
   - `pages/approve_request.php`
4. Restart web server

### Rollback Steps
```bash
# Backup current state
cp config/requests.php config/requests.php.phase1
cp pages/approve_request.php pages/approve_request.php.phase1

# Restore previous versions
git checkout config/requests.php
git checkout pages/approve_request.php

# Remove new files
rm config/supplier_defaults.php
rm config/notifications.php
rm config/email_config.php
```

### Verification After Rollback
- ☐ System loads without errors
- ☐ Requests can still be submitted
- ☐ Manager approval still works
- ☐ No data lost

---

## 📞 Support Contacts

**For Deployment Help:**
- Review [PHASE1_IMPLEMENTATION.md](PHASE1_IMPLEMENTATION.md) - Full technical details
- Review [PHASE1_QUICK_REFERENCE.md](PHASE1_QUICK_REFERENCE.md) - Configuration guide
- Run [PHASE1_VERIFICATION.php](PHASE1_VERIFICATION.php) - Automated testing

**For Security Questions:**
- See [SECURITY_AUDIT_REPORT.md](SECURITY_AUDIT_REPORT.md)
- All code reviewed for OWASP Top 10

---

## ✨ Final Checklist

Before marking Phase 1 as "Live":

- [ ] All deployment steps completed
- [ ] All verification tests passed
- [ ] Post-deployment testing successful
- [ ] Documentation accessible to team
- [ ] Configuration documented
- [ ] Backup created
- [ ] Team trained on new features
- [ ] Monitoring in place
- [ ] Rollback plan documented
- [ ] Go/No-go decision made

---

**Deployment Status:** ☐ Ready  
**Last Updated:** 2024  
**Version:** 1.0
