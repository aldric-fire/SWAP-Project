<?php
/**
 * About Page
 *
 * Static information page describing the application features and technologies.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../middleware/rbac.php';
require_login();

$pageTitle = 'About';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - SIAMS</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css?v=<?php echo time(); ?>">
</head>
<body class="has-sidebar">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="top-header">
            <h2>About SIAMS</h2>
            <div class="top-header-actions">
                <div class="header-icons">
                    <button class="icon-btn" title="Info">ℹ️</button>
                </div>
            </div>
        </header>

        <main>
            <div class="container">
                <div class="card">
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <h1 style="color: var(--color-primary); font-size: 2.25rem; margin-bottom: 0.5rem;">🔐 SIAMS</h1>
                        <p style="color: var(--color-text-light); font-size: 1.125rem;">Secure Inventory & Asset Management System</p>
                        <p style="color: var(--color-text-light); font-size: 0.95rem; font-style: italic;">Production-Ready | 100% OWASP Compliant | Enterprise-Grade Security</p>
                    </div>

                    <!-- System Overview -->
                    <section style="margin-bottom: 2.5rem;">
                        <h2 style="color: var(--color-gray-900); border-bottom: 2px solid var(--color-primary); padding-bottom: 0.5rem; margin-bottom: 1rem;">📌 System Overview</h2>
                        <p style="line-height: 1.75; color: var(--color-text);">
                            SIAMS is an enterprise-grade inventory management system featuring 4-role RBAC (Role-Based Access Control), 
                            comprehensive user management, dynamic stock request workflows, real-time analytics, and complete audit logging. 
                            Designed with security-first principles following the OWASP Top 10 security framework.
                        </p>
                    </section>

                    <!-- User Roles -->
                    <section style="margin-bottom: 2.5rem;">
                        <h2 style="color: var(--color-gray-900); border-bottom: 2px solid var(--color-primary); padding-bottom: 0.5rem; margin-bottom: 1rem;">👥 User Roles & Access Control</h2>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                            <div style="background: #eff6ff; border-left: 4px solid #2563eb; padding: 1rem; border-radius: 4px;">
                                <h4 style="margin: 0 0 0.5rem 0; color: #1e40af;">👨‍💼 Admin</h4>
                                <p style="margin: 0; font-size: 0.9rem; color: var(--color-text);">Full system access, user management, complete control</p>
                            </div>
                            <div style="background: #f0fdf4; border-left: 4px solid #059669; padding: 1rem; border-radius: 4px;">
                                <h4 style="margin: 0 0 0.5rem 0; color: #047857;">📋 Manager</h4>
                                <p style="margin: 0; font-size: 0.9rem; color: var(--color-text);">Approve requests, manage inventory, view reports</p>
                            </div>
                            <div style="background: #fef3c7; border-left: 4px solid #d97706; padding: 1rem; border-radius: 4px;">
                                <h4 style="margin: 0 0 0.5rem 0; color: #b45309;">👤 Staff</h4>
                                <p style="margin: 0; font-size: 0.9rem; color: var(--color-text);">Submit stock requests, view inventory</p>
                            </div>
                            <div style="background: #faf5ff; border-left: 4px solid #9333ea; padding: 1rem; border-radius: 4px;">
                                <h4 style="margin: 0 0 0.5rem 0; color: #7e22ce;">📝 Auditor</h4>
                                <p style="margin: 0; font-size: 0.9rem; color: var(--color-text);">Read-only access, audit logs, compliance monitoring</p>
                            </div>
                        </div>
                    </section>

                    <!-- Core Features -->
                    <section style="margin-bottom: 2.5rem;">
                        <h2 style="color: var(--color-gray-900); border-bottom: 2px solid var(--color-primary); padding-bottom: 0.5rem; margin-bottom: 1rem;">✨ Core Features</h2>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; grid-auto-rows: 1fr;">
                            <div style="background: var(--color-gray-50); padding: 1.25rem; border-radius: 6px; border-left: 4px solid var(--color-primary);">
                                <h4 style="color: var(--color-primary); margin: 0 0 0.75rem 0;">📦 Inventory Management</h4>
                                <ul style="line-height: 1.75; color: var(--color-text); margin: 0; padding-left: 1.5rem; font-size: 0.95rem;">
                                    <li>Real-time stock tracking with quantity alerts</li>
                                    <li>Category-based organization</li>
                                    <li>Minimum threshold monitoring</li>
                                    <li>Supplier assignment and tracking</li>
                                    <li>Status management (Available/Unavailable)</li>
                                    <li>Item-specific min/max request validation</li>
                                </ul>
                            </div>
                            <div style="background: var(--color-gray-50); padding: 1.25rem; border-radius: 6px; border-left: 4px solid var(--color-primary);">
                                <h4 style="color: var(--color-primary); margin: 0 0 0.75rem 0;">👥 User Management (Admin)</h4>
                                <ul style="line-height: 1.75; color: var(--color-text); margin: 0; padding-left: 1.5rem; font-size: 0.95rem;">
                                    <li>Create, edit, delete users with RBAC</li>
                                    <li>Username uniqueness validation</li>
                                    <li>Password strength enforcement (8+ characters)</li>
                                    <li>Bcrypt password hashing (cost=10)</li>
                                    <li>Role assignment and status control</li>
                                    <li>Field-level encryption for sensitive data</li>
                                </ul>
                            </div>
                            <div style="background: var(--color-gray-50); padding: 1.25rem; border-radius: 6px; border-left: 4px solid var(--color-primary);">
                                <h4 style="color: var(--color-primary); margin: 0 0 0.75rem 0;">📋 Stock Request Workflow</h4>
                                <ul style="line-height: 1.75; color: var(--color-text); margin: 0; padding-left: 1.5rem; font-size: 0.95rem;">
                                    <li>Project deadline (UTC) submission with strict format validation</li>
                                    <li>Multi-factor priority scoring (4 factors + staff inputs)</li>
                                    <li>Manager approval/rejection with automatic inventory deduction</li>
                                    <li>Request history with decision reversal capability</li>
                                    <li>Real-time status tracking and email notifications</li>
                                    <li>Supplier lead time integration for delivery estimates</li>
                                </ul>
                            </div>
                            <div style="background: var(--color-gray-50); padding: 1.25rem; border-radius: 6px; border-left: 4px solid var(--color-primary);">
                                <h4 style="color: var(--color-primary); margin: 0 0 0.75rem 0;">📊 Reports & Analytics</h4>
                                <ul style="line-height: 1.75; color: var(--color-text); margin: 0; padding-left: 1.5rem; font-size: 0.95rem;">
                                    <li>Inventory summary dashboard</li>
                                    <li>Low stock alerts (Top 10)</li>
                                    <li>Request analytics and trends</li>
                                    <li>Top requesters identification</li>
                                    <li>30-day audit activity breakdown</li>
                                    <li>Export capabilities</li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <!-- Security Features -->
                    <section style="margin-bottom: 2.5rem;">
                        <h2 style="color: var(--color-gray-900); border-bottom: 2px solid var(--color-danger); padding-bottom: 0.5rem; margin-bottom: 1rem;">🔐 Security Implementation</h2>
                        <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; padding: 1.5rem;">
                            <h4 style="color: var(--color-danger); margin-bottom: 1rem;">OWASP Top 10 Compliance</h4>
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead>
                                    <tr style="background: var(--color-danger-bg);">
                                        <th style="text-align: left; padding: 0.75rem; border: 1px solid #fca5a5;">Control</th>
                                        <th style="text-align: left; padding: 0.75rem; border: 1px solid #fca5a5;">Implementation</th>
                                        <th style="text-align: center; padding: 0.75rem; border: 1px solid #fca5a5;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;"><strong>A01: Broken Access Control</strong></td>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;">RBAC with require_login() on all pages</td>
                                        <td style="text-align: center; padding: 0.75rem; border: 1px solid #fca5a5;">✅</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;"><strong>A03: Injection</strong></td>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;">PDO prepared statements on all queries</td>
                                        <td style="text-align: center; padding: 0.75rem; border: 1px solid #fca5a5;">✅</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;"><strong>A05: Security Misconfiguration</strong></td>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;">Secure session config, HttpOnly cookies</td>
                                        <td style="text-align: center; padding: 0.75rem; border: 1px solid #fca5a5;">✅</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;"><strong>A07: XSS</strong></td>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;">htmlspecialchars() with ENT_QUOTES on all output; strip_tags() on input; regex pattern validation</td>
                                        <td style="text-align: center; padding: 0.75rem; border: 1px solid #fca5a5;">✅</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;"><strong>CSRF Protection</strong></td>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;">CSRF tokens on all forms (POST/DELETE)</td>
                                        <td style="text-align: center; padding: 0.75rem; border: 1px solid #fca5a5;">✅</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;"><strong>Password Security</strong></td>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;">Bcrypt hashing (cost=10), strength validation</td>
                                        <td style="text-align: center; padding: 0.75rem; border: 1px solid #fca5a5;">✅</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;"><strong>Audit Logging</strong></td>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;">All actions logged with user ID & timestamp</td>
                                        <td style="text-align: center; padding: 0.75rem; border: 1px solid #fca5a5;">✅</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;"><strong>Session Management</strong></td>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;">15-minute timeout, SameSite=Strict</td>
                                        <td style="text-align: center; padding: 0.75rem; border: 1px solid #fca5a5;">✅</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;"><strong>Input Validation</strong></td>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;">Type checking, range validation, sanitization</td>
                                        <td style="text-align: center; padding: 0.75rem; border: 1px solid #fca5a5;">✅</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;"><strong>Error Handling</strong></td>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;">No SQL/internal errors exposed to users</td>
                                        <td style="text-align: center; padding: 0.75rem; border: 1px solid #fca5a5;">✅</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;"><strong>Transaction Safety</strong></td>
                                        <td style="padding: 0.75rem; border: 1px solid #fca5a5;">Database transactions with rollback on approval failure</td>
                                        <td style="text-align: center; padding: 0.75rem; border: 1px solid #fca5a5;">✅</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div style="margin-top: 1.5rem; display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; align-items: start;">
                            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 6px; padding: 1.25rem;">
                                <h4 style="color: #047857; margin: 0 0 0.75rem 0; display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="font-size: 1.25rem;">🔒</span> Additional Security Measures
                                </h4>
                                <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem; color: var(--color-text); line-height: 1.75;">
                                    <li>Database transaction safety</li>
                                    <li>Foreign key cascade protection</li>
                                    <li>Client-side field encryption (AES-256-GCM)</li>
                                    <li>Server-side encrypted storage</li>
                                </ul>
                            </div>
                            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 1.25rem;">
                                <h4 style="color: #1e40af; margin: 0 0 0.75rem 0; display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="font-size: 1.25rem;">📋</span> Audit Capabilities
                                </h4>
                                <ul style="margin: 0; padding-left: 1.5rem; font-size: 0.9rem; color: var(--color-text); line-height: 1.75;">
                                    <li>All CREATE/UPDATE/DELETE actions logged</li>
                                    <li>User accountability tracking</li>
                                    <li>Timestamp precision logging</li>
                                    <li>30-day activity analytics</li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <!-- Technologies -->
                    <section style="margin-bottom: 2.5rem;">
                        <h2 style="color: var(--color-gray-900); border-bottom: 2px solid var(--color-primary); padding-bottom: 0.5rem; margin-bottom: 1rem;">🛠️ Technologies Used</h2>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                            <div style="background: var(--color-gray-50); padding: 1rem; border-radius: 4px; text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">🐘</div>
                                <h4 style="margin: 0 0 0.25rem 0;">PHP 8+</h4>
                                <p style="margin: 0; font-size: 0.85rem; color: var(--color-text-light);">Backend Logic</p>
                            </div>
                            <div style="background: var(--color-gray-50); padding: 1rem; border-radius: 4px; text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">🗄️</div>
                                <h4 style="margin: 0 0 0.25rem 0;">MySQL</h4>
                                <p style="margin: 0; font-size: 0.85rem; color: var(--color-text-light);">Database (PDO)</p>
                            </div>
                            <div style="background: var(--color-gray-50); padding: 1rem; border-radius: 4px; text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">🌐</div>
                                <h4 style="margin: 0 0 0.25rem 0;">HTML5/CSS3</h4>
                                <p style="margin: 0; font-size: 0.85rem; color: var(--color-text-light);">Modern UI</p>
                            </div>
                            <div style="background: var(--color-gray-50); padding: 1rem; border-radius: 4px; text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">⚡</div>
                                <h4 style="margin: 0 0 0.25rem 0;">JavaScript (ES6)</h4>
                                <p style="margin: 0; font-size: 0.85rem; color: var(--color-text-light);">Client Interaction</p>
                            </div>
                            <div style="background: var(--color-gray-50); padding: 1rem; border-radius: 4px; text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">🔐</div>
                                <h4 style="margin: 0 0 0.25rem 0;">Bcrypt</h4>
                                <p style="margin: 0; font-size: 0.85rem; color: var(--color-text-light);">Password Hashing</p>
                            </div>
                            <div style="background: var(--color-gray-50); padding: 1rem; border-radius: 4px; text-align: center;">
                                <div style="font-size: 2rem; margin-bottom: 0.5rem;">🔑</div>
                                <h4 style="margin: 0 0 0.25rem 0;">AES-256-GCM</h4>
                                <p style="margin: 0; font-size: 0.85rem; color: var(--color-text-light);">Field Encryption</p>
                            </div>
                        </div>
                    </section>

                    <!-- Architecture -->
                    <section style="margin-bottom: 2.5rem;">
                        <h2 style="color: var(--color-gray-900); border-bottom: 2px solid var(--color-primary); padding-bottom: 0.5rem; margin-bottom: 1rem;">🏗️ System Architecture</h2>
                        <div style="background: var(--color-gray-50); padding: 1.5rem; border-radius: 6px; font-family: monospace; font-size: 0.9rem;">
                            <strong>Layered Architecture Pattern:</strong>
                            <ul style="margin: 0.5rem 0 0 1.5rem; line-height: 1.75;">
                                <li><strong>Presentation Layer:</strong> pages/*.php (User interface)</li>
                                <li><strong>Security Layer:</strong> middleware/*.php (RBAC, CSRF)</li>
                                <li><strong>Business Logic:</strong> config/*.php (Data access functions)</li>
                                <li><strong>Data Layer:</strong> MySQL with PDO (Persistence)</li>
                            </ul>
                        </div>
                    </section>

                    <!-- Footer Actions -->
                    <div style="text-align: center; padding-top: 1.5rem; border-top: 2px solid var(--color-border);">
                        <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-primary">Back to Inventory</a>
                    </div>
                </div>
            </div>
        </main>

        <?php include __DIR__ . '/../includes/footer.php'; ?>
    </div>
</body>
</html>
