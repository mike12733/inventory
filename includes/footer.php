    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-graduation-cap me-2"></i>LNHS Documents Portal</h5>
                    <p class="mb-0">Making document requests easier for students and alumni.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        <i class="fas fa-clock me-1"></i>
                        Processing Hours: 8:00 AM - 5:00 PM, Monday - Friday
                    </p>
                    <p class="mb-0">
                        <i class="fas fa-envelope me-1"></i>
                        Email: admin@lnhs.edu.ph
                    </p>
                </div>
            </div>
            <hr class="my-3">
            <div class="text-center">
                <small>&copy; <?php echo date('Y'); ?> LNHS Documents Request Portal. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Load notifications on dropdown open
        document.getElementById('notificationDropdown')?.addEventListener('click', function() {
            loadNotifications();
        });

        function loadNotifications() {
            fetch('ajax/get-notifications.php')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('notifications-list');
                    if (data.success && data.notifications.length > 0) {
                        container.innerHTML = data.notifications.map(notification => `
                            <li>
                                <a class="dropdown-item ${!notification.is_read ? 'fw-bold' : ''}" href="#" onclick="markAsRead(${notification.id})">
                                    <div class="small text-muted">${notification.created_at}</div>
                                    <div>${notification.title}</div>
                                    <div class="small">${notification.message.substring(0, 50)}...</div>
                                </a>
                            </li>
                        `).join('');
                    } else {
                        container.innerHTML = '<li><span class="dropdown-item-text text-muted">No notifications</span></li>';
                    }
                })
                .catch(error => console.error('Error loading notifications:', error));
        }

        function markAsRead(notificationId) {
            fetch('ajax/mark-notification-read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: notificationId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update notification badge
                    const badge = document.querySelector('.notification-badge');
                    if (badge) {
                        const count = parseInt(badge.textContent) - 1;
                        if (count <= 0) {
                            badge.remove();
                        } else {
                            badge.textContent = count;
                        }
                    }
                }
            });
        }

        // Auto-dismiss alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('.needs-validation');
            forms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                });
            });
        });

        // File upload preview
        function previewFile(input, previewId) {
            const file = input.files[0];
            const preview = document.getElementById(previewId);
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (file.type.startsWith('image/')) {
                        preview.innerHTML = `<img src="${e.target.result}" class="img-thumbnail" style="max-width: 200px;">`;
                    } else {
                        preview.innerHTML = `<div class="alert alert-info"><i class="fas fa-file me-2"></i>${file.name}</div>`;
                    }
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        }

        // Status update with confirmation
        function updateStatus(requestId, newStatus, confirmMessage) {
            if (confirm(confirmMessage || 'Are you sure you want to update this status?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'admin-dashboard.php';
                
                const requestIdInput = document.createElement('input');
                requestIdInput.type = 'hidden';
                requestIdInput.name = 'request_id';
                requestIdInput.value = requestId;
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'new_status';
                statusInput.value = newStatus;
                
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'update_status';
                
                form.appendChild(requestIdInput);
                form.appendChild(statusInput);
                form.appendChild(actionInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Search functionality
        function searchTable(inputId, tableId) {
            const input = document.getElementById(inputId);
            const table = document.getElementById(tableId);
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

            input.addEventListener('keyup', function() {
                const filter = this.value.toLowerCase();
                
                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    const cells = row.getElementsByTagName('td');
                    let found = false;
                    
                    for (let j = 0; j < cells.length; j++) {
                        if (cells[j].textContent.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                    
                    row.style.display = found ? '' : 'none';
                }
            });
        }
    </script>
</body>
</html>