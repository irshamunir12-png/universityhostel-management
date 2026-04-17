</div> </div> </main>
    
    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">V 1.0</div>
        <strong><?= $settings['footer_text'] ?></strong>
    </footer>
</div> <script src="<?= BASE_URL ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/adminlte.min.js"></script>

<?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
<!-- Floating Quick Actions Button -->
<div class="dropdown position-fixed bottom-0 end-0 mb-4 me-4 bd-mode-toggle" style="z-index: 1050;">
    <button class="btn btn-primary btn-lg rounded-circle shadow py-3 px-3" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Quick Actions">
        <i class="bi bi-plus-lg fs-4"></i>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow mb-2">
        <li><h6 class="dropdown-header">Quick Actions</h6></li>
        <li><a class="dropdown-item" href="<?= BASE_URL ?>dashboards/super_admin/manage_users.php"><i class="bi bi-person-plus me-2"></i> Add User</a></li>
        <li><a class="dropdown-item" href="<?= BASE_URL ?>dashboards/super_admin/manage_rooms.php"><i class="bi bi-building-add me-2"></i> Add Room</a></li>
        <li><a class="dropdown-item" href="<?= BASE_URL ?>dashboards/super_admin/manage_complaints.php"><i class="bi bi-ticket-detailed me-2"></i> Check Complaints</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="<?= BASE_URL ?>dashboards/super_admin/reports.php"><i class="bi bi-graph-up me-2"></i> View Reports</a></li>
    </ul>
</div>
<?php endif; ?>

<script>
    // Theme Toggle Logic with Persistence
    document.addEventListener("DOMContentLoaded", function() {
        const toggleButton = document.getElementById('theme-toggle');
        const themeIcon = document.getElementById('theme-icon');
        const htmlElement = document.documentElement;

        // 1. Function to update Icon
        function updateIcon(theme) {
            if (theme === 'dark') {
                themeIcon.classList.remove('bi-sun-fill');
                themeIcon.classList.add('bi-moon-fill');
            } else {
                themeIcon.classList.remove('bi-moon-fill');
                themeIcon.classList.add('bi-sun-fill');
            }
        }

        // 2. Initialize Icon on Load
        const currentTheme = localStorage.getItem('theme') || 'light';
        updateIcon(currentTheme);

        // 3. Handle Click
        toggleButton.addEventListener('click', () => {
            const current = htmlElement.getAttribute('data-bs-theme');
            const newTheme = current === 'dark' ? 'light' : 'dark';
            
            // Apply
            htmlElement.setAttribute('data-bs-theme', newTheme);
            localStorage.setItem('theme', newTheme); // SAVE IT
            updateIcon(newTheme);
        });

        // Font Size Switcher Logic
        const fontSizeSlider = document.getElementById('fontSizeSlider');
        const fontSizeLabel = document.getElementById('fontSizeLabel');

        // 1. Function to apply font size
        function applyFontSize(size) {
            htmlElement.style.fontSize = size + '%';
            localStorage.setItem('fontSize', size);
            if (fontSizeLabel) {
                fontSizeLabel.textContent = size + '%';
            }
        }

        // 2. Initialize on load
        const currentSize = localStorage.getItem('fontSize');
        if (currentSize) {
            applyFontSize(currentSize);
            if (fontSizeSlider) {
                fontSizeSlider.value = currentSize;
            }
        }

        // 3. Handle click
        if (fontSizeSlider) {
            fontSizeSlider.addEventListener('input', function() {
                applyFontSize(this.value);
            });
        }
    });
</script>
</body>
</html>