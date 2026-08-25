// Admin JS
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 4 seconds
    document.querySelectorAll('.alert-admin').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // Confirm delete links
    document.querySelectorAll('.btn-red').forEach(btn => {
        btn.addEventListener('click', function(e) {
            if (!confirm('آیا از حذف این مورد مطمئن هستید؟')) {
                e.preventDefault();
            }
        });
    });

    // Upload area click to trigger file input
    document.querySelectorAll('.upload-area').forEach(area => {
        const fileInput = area.querySelector('input[type="file"]');
        if (fileInput) {
            area.addEventListener('click', function(e) {
                if (e.target !== fileInput) {
                    fileInput.click();
                }
            });
            // Show filename after selection
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const name = this.files[0].name;
                    const hint = area.querySelector('span');
                    if (hint) hint.textContent = name;
                }
            });
        }
    });

    // Auto-generate slug from title
    const titleInputs = document.querySelectorAll('input[name="title"]');
    const slugInputs = document.querySelectorAll('input[name="slug"]');
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
