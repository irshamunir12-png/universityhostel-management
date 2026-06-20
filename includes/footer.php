</div> </div> </main>
    
    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">V 1.0</div>
        <strong><?= $settings['footer_text'] ?></strong>
    </footer>
</div> <script src="<?= BASE_URL ?>assets/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/adminlte.min.js"></script>

<?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin'): ?>
<!-- Floating Quick Actions Button -->
<div class="dropdown position-fixed bottom-0 end-0 mb-4 me-4 bd-mode-toggle d-flex flex-column gap-2" style="z-index: 1050;">
    <!-- Global AI Assistant Trigger -->
    <button class="btn btn-info btn-lg rounded-circle shadow py-3 px-3 border-0 text-white" type="button" data-bs-toggle="modal" data-bs-target="#globalAIModal" title="AI Warden Assistant">
        <i class="bi bi-stars fs-4"></i>
    </button>

    <button class="btn btn-primary btn-lg rounded-circle shadow py-3 px-3 border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Quick Actions">
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

<!-- Global AI Assistant Modal -->
<div class="modal fade" id="globalAIModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white border-0 py-3">
                <h5 class="modal-title fw-bold"><i class="bi bi-stars me-2"></i> AI Assistant</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="mb-3 position-relative">
                    <label class="form-label small fw-bold text-muted text-uppercase">What do you want to write?</label>
                    <input type="text" id="globalAiPrompt" class="form-control border-0 shadow-sm py-3 px-3 rounded-3 ai-suggestion-input" placeholder="e.g. Write a noise warning notice...">
                </div>
                
                <div id="aiResponseContainer" class="p-3 bg-white rounded-3 shadow-sm mb-3 border border-light" style="min-height: 120px; display: none;">
                    <div id="aiLoading" class="text-center py-4" style="display: none;">
                        <div class="spinner-border text-primary spinner-border-sm" role="status"></div>
                        <span class="ms-2 small text-muted fst-italic">AI is thinking...</span>
                    </div>
                    <div id="aiResult" class="text-dark small" style="line-height: 1.6; white-space: pre-wrap;"></div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="button" onclick="generateAIContent()" id="aiGenBtn" class="btn btn-info text-white fw-bold rounded-pill px-4 shadow-sm flex-fill">
                        <i class="bi bi-stars me-1"></i> GENERATE
                    </button>
                    <button type="button" id="aiCopyBtn" onclick="copyAIResult()" class="btn btn-outline-secondary rounded-pill px-3 shadow-sm" style="display: none;">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    async function generateAIContent() {
        const prompt = document.getElementById('globalAiPrompt').value;
        const resultDiv = document.getElementById('aiResult');
        const loader = document.getElementById('aiLoading');
        const container = document.getElementById('aiResponseContainer');
        const copyBtn = document.getElementById('aiCopyBtn');
        const genBtn = document.getElementById('aiGenBtn');

        if(!prompt) return alert("Please type something first!");

        container.style.display = 'block';
        loader.style.display = 'block';
        resultDiv.innerText = "";
        copyBtn.style.display = 'none';
        genBtn.disabled = true;

        try {
            const response = await fetch('<?= BASE_URL ?>core/ai_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ prompt: prompt })
            });
            const data = await response.json();
            loader.style.display = 'none';
            
            if(data.text) {
                typeWriterEffect(resultDiv, data.text, 10);
                copyBtn.style.display = 'block';
            } else {
                resultDiv.innerHTML = `<span class="text-danger">Error: Could not generate content.</span>`;
            }
        } catch (err) {
            loader.style.display = 'none';
            resultDiv.innerHTML = `<span class="text-danger">Network error. Check connection.</span>`;
        } finally {
            genBtn.disabled = false;
        }
    }

    function copyAIResult() {
        const text = document.getElementById('aiResult').innerText;
        navigator.clipboard.writeText(text).then(() => {
            alert("Content copied to clipboard!");
        });
    }
</script>

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