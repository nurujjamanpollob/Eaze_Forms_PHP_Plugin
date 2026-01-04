-- EazeWebIT Contact Form Service Schema

CREATE TABLE IF NOT EXISTS roles (
                                     id INTEGER PRIMARY KEY AUTOINCREMENT,
                                     role_name TEXT NOT NULL UNIQUE,
                                     role_description TEXT,
                                     level INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS users (
                                     id INTEGER PRIMARY KEY AUTOINCREMENT,
                                     username TEXT NOT NULL UNIQUE,
                                     email TEXT NOT NULL UNIQUE,
                                     password TEXT NOT NULL,
                                     created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                     locked INTEGER DEFAULT 0,
                                     role TEXT DEFAULT 'user',
                                     "2_factor_code" TEXT,
                                     "2_factor_master" TEXT,
                                     is_verified INTEGER DEFAULT 0,
                                     FOREIGN KEY (role) REFERENCES roles(role_name)
);

CREATE TABLE IF NOT EXISTS submissions (
                                           id INTEGER PRIMARY KEY AUTOINCREMENT,
                                           submission_id INTEGER NOT NULL,
                                           field_key TEXT NOT NULL,
                                           field_value TEXT,
                                           field_type TEXT NOT NULL,
                                           created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_submission_id ON submissions (submission_id);

CREATE TABLE IF NOT EXISTS logs (
                                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                                    submission_id INTEGER,
                                    action TEXT NOT NULL,
                                    performed_by INTEGER,
                                    details TEXT,
                                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                                    FOREIGN KEY (performed_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS statuses (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        status TEXT NOT NULL UNIQUE COLLATE NOCASE,
                                        description TEXT,
                                        color TEXT DEFAULT 'sky'
);

CREATE TABLE IF NOT EXISTS settings (
                                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                                        "Key" TEXT NOT NULL UNIQUE,
                                        "Value" TEXT
);

CREATE TABLE IF NOT EXISTS security_log (
                                            id INTEGER PRIMARY KEY AUTOINCREMENT,
                                            incident_details TEXT NOT NULL,
                                            type TEXT NOT NULL,
                                            extra_data TEXT,
                                            ip_address TEXT,
                                            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_security_created_at ON security_log (created_at);
CREATE INDEX IF NOT EXISTS idx_security_ip ON security_log (ip_address);

-- Initial Data
INSERT OR IGNORE INTO roles (role_name, role_description, level) VALUES ('admin', 'System Administrator', 100);
INSERT OR IGNORE INTO roles (role_name, role_description, level) VALUES ('user', 'Standard User', 10);

INSERT OR IGNORE INTO statuses (status, description, color) VALUES ('pending', 'Submission received and awaiting review', 'yellow');
INSERT OR IGNORE INTO statuses (status, description, color) VALUES ('sent', 'Response sent to user', 'green');
INSERT OR IGNORE INTO statuses (status, description, color) VALUES ('error', 'An error occurred during processing', 'red');

INSERT OR IGNORE INTO settings ("Key", "Value") VALUES ('upload_limit', '10');
INSERT OR IGNORE INTO settings ("Key", "Value") VALUES ('footer_text', '© 2026 Eaze Web IT (https://eazewebit.com). All rights reserved.');
INSERT OR IGNORE INTO settings ("Key", "Value") VALUES ('admin_logo_url', '/ecfs/public/assets/logo.png');
INSERT OR IGNORE INTO settings ("Key", "Value") VALUES ('default_status', 'pending');

-- SMTP and Notification Settings
INSERT OR IGNORE INTO settings ("Key", "Value") VALUES ('smtp_host', '');
INSERT OR IGNORE INTO settings ("Key", "Value") VALUES ('smtp_port', '587');
INSERT OR IGNORE INTO settings ("Key", "Value") VALUES ('smtp_user', '');
INSERT OR IGNORE INTO settings ("Key", "Value") VALUES ('smtp_pass', '');
INSERT OR IGNORE INTO settings ("Key", "Value") VALUES ('smtp_from_name', 'EazeWebIT Notifications');
INSERT OR IGNORE INTO settings ("Key", "Value") VALUES ('smtp_from_email', 'noreply@example.com');
INSERT OR IGNORE INTO settings ("Key", "Value") VALUES ('admin_recipient_email', '');
INSERT OR IGNORE INTO settings ("Key", "Value") VALUES ('enable_confirmation_email', '0');
INSERT OR IGNORE INTO settings ("Key", "Value") VALUES ('enable_admin_notification', '0');

-- Pagination and Email Templates
INSERT OR IGNORE INTO settings ("Key", "Value") VALUES ('dashboard_pagination_limit', '6');
INSERT OR IGNORE INTO settings ("Key", "Value") VALUES ('manage_submissions_pagination_limit', '10');
INSERT OR IGNORE INTO settings ("Key", "Value") VALUES ('user_email_template', '<h2>Thank you for your submission!</h2><p>We have received your form submission (Reference ID: #{{submission_id}}).</p><p>This is an automated confirmation. We will review your submission shortly.</p><br><hr><p><small>{{footer_text}}</small></p>');
INSERT OR IGNORE INTO settings ("Key", "Value") VALUES ('admin_email_template', '<h2>New Submission Received</h2><p><strong>ID:</strong> {{submission_id}}</p><p><strong>Submitted By:</strong> {{submitted_by}}</p><h3>Form Data:</h3>{{form_data}}');
