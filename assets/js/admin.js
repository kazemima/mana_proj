// ========== User Modal Functions ==========
function openUserModal(userData) {
    var modal = document.getElementById('userModal');
    var form = document.getElementById('userForm');
    var title = document.getElementById('userModalTitle');
    var submitText = document.getElementById('userSubmitText');
    var hint = document.getElementById('passwordHint');
    var passwordError = document.getElementById('passwordError');

    form.reset();
    passwordError.style.display = 'none';

    if (userData) {
        title.textContent = 'ویرایش کاربر';
        submitText.textContent = 'بروزرسانی';
        hint.textContent = '(خالی بگذارید تا تغییر نکند)';
        document.getElementById('user_id').value = userData.id;
        document.getElementById('user_username').value = userData.username || '';
        document.getElementById('user_name').value = userData.name || '';
        document.getElementById('user_email').value = userData.email || '';
        document.getElementById('user_role').value = userData.role || 'admin';
    } else {
        title.textContent = 'افزودن کاربر جدید';
        submitText.textContent = 'افزودن';
        hint.textContent = '*';
        document.getElementById('user_id').value = '';
    }

    modal.classList.add('active');
}

function closeUserModal() {
    document.getElementById('userModal').classList.remove('active');
}

function submitUserForm() {
    var password = document.getElementById('user_password').value;
    var passwordConfirm = document.getElementById('user_password_confirm').value;
    var userId = document.getElementById('user_id').value;
    var passwordError = document.getElementById('passwordError');

    if (!userId && !password) {
        passwordError.textContent = 'رمز عبور الزامی است.';
        passwordError.style.display = 'block';
        return;
    }

    if (password && password !== passwordConfirm) {
        passwordError.textContent = 'رمز عبور و تکرار آن مطابقت ندارند.';
        passwordError.style.display = 'block';
        return;
    }

    passwordError.style.display = 'none';
    document.getElementById('userForm').submit();
}

// Admin JS
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 4 seconds
    document.querySelectorAll('.alert-admin').forEach(function(alert) {
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() { alert.remove(); }, 500);
        }, 4000);
    });

    // Confirm delete links
    document.querySelectorAll('.btn-red').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            if (!confirm('آیا از حذف این مورد مطمئن هستید؟')) {
                e.preventDefault();
            }
        });
    });

    // Handle edit button clicks
    document.querySelectorAll('.btn-edit-user').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var userData = JSON.parse(atob(this.getAttribute('data-user')));
            openUserModal(userData);
        });
    });

    // Close modal on overlay click
    document.querySelectorAll('.menu-modal-overlay').forEach(function(overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === overlay) {
                overlay.classList.remove('active');
            }
        });
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.menu-modal-overlay.active').forEach(function(m) {
                m.classList.remove('active');
            });
        }
    });

    // Upload area click to trigger file input
    document.querySelectorAll('.upload-area').forEach(function(area) {
        var fileInput = area.querySelector('input[type="file"]');
        if (fileInput) {
            area.addEventListener('click', function(e) {
                if (e.target !== fileInput) {
                    fileInput.click();
                }
            });
            // Show filename after selection
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    var name = this.files[0].name;
                    var hint = area.querySelector('span');
                    if (hint) hint.textContent = name;
                }
            });
        }
    });

    // Auto-generate slug from title
    var titleInputs = document.querySelectorAll('input[name="title"]');
    var slugInputs = document.querySelectorAll('input[name="slug"]');
    if (titleInputs.length > 0 && slugInputs.length > 0) {
        titleInputs[0].addEventListener('input', function() {
            if (!slugInputs[0].value || slugInputs[0].dataset.auto === 'true') {
                slugInputs[0].value = this.value
                    .toLowerCase()
                    .replace(/[^\w\u0600-\u06FF\s-]/g, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
                slugInputs[0].dataset.auto = 'true';
            }
        });
        slugInputs[0].addEventListener('input', function() {
            this.dataset.auto = 'false';
        });
    }
});
