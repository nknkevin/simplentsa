<?php
/**
 * Admin Panel - User Management
 */
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once '../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .admin-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 1rem;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .back-btn {
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .card h2 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .user-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .user-table th,
        .user-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .user-table th {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .user-table tr:last-child td {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-active {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success-color);
        }
        
        .status-inactive {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }
        
        .action-btns {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-small {
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
        }
        
        @media (max-width: 640px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .user-table {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>User Management</h1>
            <a href="../dashboard.php" class="back-btn">← Back to Dashboard</a>
        </div>
        
        <!-- Create User Form -->
        <div class="card">
            <h2>Create New User</h2>
            <form id="create-user-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="new-username">Username</label>
                        <input type="text" id="new-username" class="form-control" placeholder="Enter username" required>
                    </div>
                    <div class="form-group">
                        <label for="new-password">Password</label>
                        <input type="password" id="new-password" class="form-control" placeholder="Enter password" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="new-role">Role</label>
                        <select id="new-role" class="form-control">
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="form-group" style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">Create User</button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Change Password Form -->
        <div class="card">
            <h2>Change User Password</h2>
            <form id="change-password-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="change-user-id">Select User</label>
                        <select id="change-user-id" class="form-control" required>
                            <option value="">Choose a user...</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="new-user-password">New Password</label>
                        <input type="password" id="new-user-password" class="form-control" placeholder="Enter new password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>
        </div>
        
        <!-- Users List -->
        <div class="card">
            <h2>All Users</h2>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="users-list">
                    <tr>
                        <td colspan="6" class="text-center">Loading...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        // Load users on page load
        $(document).ready(function() {
            loadUsers();
        });
        
        function loadUsers() {
            $.ajax({
                url: '../api/admin/users.php?action=list',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        let html = '';
                        response.data.forEach(function(user) {
                            const statusClass = user.active == 1 ? 'status-active' : 'status-inactive';
                            const statusText = user.active == 1 ? 'Active' : 'Inactive';
                            html += `
                                <tr>
                                    <td>${user.id}</td>
                                    <td>${escapeHtml(user.username)}</td>
                                    <td>${user.role}</td>
                                    <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                                    <td>${user.created_at}</td>
                                    <td class="action-btns">
                                        <button class="btn btn-secondary btn-small" onclick="toggleUserStatus(${user.id})">Toggle Status</button>
                                    </td>
                                </tr>
                            `;
                        });
                        $('#users-list').html(html);
                        
                        // Populate user dropdown for password change
                        let options = '<option value="">Choose a user...</option>';
                        response.data.forEach(function(user) {
                            options += `<option value="${user.id}">${escapeHtml(user.username)} (${user.role})</option>`;
                        });
                        $('#change-user-id').html(options);
                    } else {
                        $('#users-list').html('<tr><td colspan="6" class="text-center">' + response.message + '</td></tr>');
                    }
                },
                error: function() {
                    $('#users-list').html('<tr><td colspan="6" class="text-center">Error loading users</td></tr>');
                }
            });
        }
        
        // Create new user
        $('#create-user-form').on('submit', function(e) {
            e.preventDefault();
            
            const data = {
                username: $('#new-username').val(),
                password: $('#new-password').val(),
                role: $('#new-role').val()
            };
            
            $.ajax({
                url: '../api/admin/users.php?action=create',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('User created successfully!');
                        $('#create-user-form')[0].reset();
                        loadUsers();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error creating user');
                }
            });
        });
        
        // Change password
        $('#change-password-form').on('submit', function(e) {
            e.preventDefault();
            
            const data = {
                user_id: $('#change-user-id').val(),
                new_password: $('#new-user-password').val()
            };
            
            $.ajax({
                url: '../api/admin/users.php?action=change_password',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Password changed successfully!');
                        $('#change-password-form')[0].reset();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error changing password');
                }
            });
        });
        
        // Toggle user status
        function toggleUserStatus(userId) {
            if (!confirm('Are you sure you want to toggle this user\'s status?')) {
                return;
            }
            
            $.ajax({
                url: '../api/admin/users.php?action=toggle_active',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ user_id: userId }),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        loadUsers();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error updating user status');
                }
            });
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
