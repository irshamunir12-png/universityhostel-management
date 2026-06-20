<?php
require_once '../../includes/header.php';

// --- Auto-Register Page in Sidebar (Self-Healing Logic) ---
$pageUrl = 'dashboards/super_admin/user_manual.php';
$checkPage = $pdo->prepare("SELECT id FROM sys_pages WHERE page_url = ?");
$checkPage->execute([$pageUrl]);
$pageEntry = $checkPage->fetch();

if (!$pageEntry) {
    // Agar menu mein nahi hai, to add karo
    $pdo->prepare("INSERT INTO sys_pages (page_name, page_url, icon_class, parent_id, sort_order) VALUES (?, ?, ?, ?, ?)")
        ->execute(['User Manual', $pageUrl, 'bi bi-book-half', 0, 100]);
    $newPageId = $pdo->lastInsertId();
    // Super Admin ko access do
    $pdo->prepare("INSERT IGNORE INTO role_access (role_key, page_id) VALUES ('super_admin', ?)")->execute([$newPageId]);
}
?>

<style>
    .manual-section { background: white; border-radius: 15px; padding: 30px; margin-bottom: 40px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); border: 1px solid #eee; }
    .manual-nav { position: sticky; top: 20px; }
    .manual-img { 
        width: 100%; border-radius: 12px; margin: 25px 0; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 1px solid #ddd;
        transition: transform 0.3s ease;
    }
    .manual-img:hover { transform: scale(1.02); }
    .step-badge { background: #198754; color: white; border-radius: 50%; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 12px; font-size: 0.9rem; }
    h2 { color: #198754; font-weight: 800; border-left: 5px solid #198754; padding-left: 15px; margin-bottom: 25px; }
    h4 { font-weight: 700; margin-top: 20px; color: #333; }
    code { background: #f1f3f5; color: #d63384; padding: 2px 5px; border-radius: 4px; }
</style>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation for Manual -->
        <div class="col-md-3 d-none d-md-block">
            <div class="card manual-nav border-0 shadow-sm">
                <div class="card-header bg-dark text-white fw-bold">User Manual Index</div>
                <div class="list-group list-group-flush">
                    <a href="#intro" class="list-group-item list-group-item-action">Introduction</a>
                    <a href="#gate" class="list-group-item list-group-item-action">Gate Management</a>
                    <a href="#finance" class="list-group-item list-group-item-action">Finance & Fees</a>
                    <a href="#inventory" class="list-group-item list-group-item-action">Inventory & Assets</a>
                    <a href="#reports" class="list-group-item list-group-item-action">Generating Reports</a>
                    <a href="#ai" class="list-group-item list-group-item-action">AI Assistant</a>
                </div>
            </div>
        </div>

        <!-- Manual Content -->
        <div class="col-md-9">
            <div id="intro" class="manual-section">
                <h2>1. Introduction</h2>
                <p>Welcome to the <strong>Residential  Hostel ERP</strong>. This system is designed to streamline hostel operations, including student tracking, financial management, and asset control.</p>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle-fill me-2"></i> This manual provides step-by-step instructions for <strong>Super Admins</strong> and <strong>Wardens</strong>.
                </div>
            </div>

            <div id="gate" class="manual-section">
                <h2>2. Gate Management</h2>
                <p>The Gate Management module tracks student entry and exit in real-time. It features an automated curfew alert system.</p>
                
                <h4>How to Log Student Movement:</h4>
                <ul class="list-unstyled">
                    <li><span class="step-badge">1</span> Search for the student using their Name or Registration Number in the search bar.</li>
                    <li><span class="step-badge">2</span> Click the <span class="badge bg-warning text-dark">OUT</span> button when a student leaves.</li>
                    <li><span class="step-badge">3</span> Click the <span class="badge bg-success">IN</span> button when they return.</li>
                </ul>

                <img src="<?= BASE_URL ?>assets/img/gate.png" class="manual-img" alt="Gate Management Interface">

                <div class="alert alert-warning">
                    <strong>Note:</strong> If a student returns after the set Curfew Time (e.g., 10:00 PM), the system will automatically mark the entry as <span class="text-danger fw-bold">LATE</span>.
                </div>
            </div>

            <div id="finance" class="manual-section">
                <h2>3. Fee Management</h2>
                <p>Track all financial transactions, including monthly hostel fees and mess charges.</p>
                
                <h4>Assigning Fees:</h4>
                <p>Use the <strong>Fee Management</strong> dashboard to create new fee records. Statuses include <code>Paid</code>, <code>Partial</code>, and <code>Unpaid</code>.</p>

                <img src="<?= BASE_URL ?>assets/img/fee.png" class="manual-img" alt="Fee Management Interface">
            </div>

            <div id="inventory" class="manual-section">
                <h2>4. Inventory & Asset Tracking</h2>
                <p>Differentiate between consumable items (General Stock) and high-value items (Trackable Assets).</p>
                
                <h4>Allocating Assets:</h4>
                <p>Go to the <strong>Allocations</strong> tab to assign a specific asset (like a Laptop or Chair) to a student or a specific room.</p>

                <img src="<?= BASE_URL ?>assets/img/assets.png" class="manual-img" alt="Asset Allocation Interface">
            </div>

            <div id="reports" class="manual-section">
                <h2>5. Generating PDF Reports</h2>
                <p>Official reports can be exported as professional PDF documents for record-keeping.</p>
                
                <h4>Steps to Export:</h4>
                <ul class="list-unstyled">
                    <li><span class="step-badge">1</span> Go to the <strong>Reports</strong> section.</li>
                    <li><span class="step-badge">2</span> Select the Date Range for Attendance or view the Fee Summary.</li>
                    <li><span class="step-badge">3</span> Click the <span class="btn btn-sm btn-outline-success">EXPORT PDF</span> button on the specific module.</li>
                </ul>

                <img src="<?= BASE_URL ?>assets/img/reports.png" class="manual-img" alt="PDF Reports Interface">
            </div>

            <div id="ai" class="manual-section">
                <h2>6. AI Warden Assistant</h2>
                <p>The AI Warden Assistant helps you draft professional notices and warnings instantly.</p>
                
                <h4>Usage:</h4>
                <p>Open the AI Modal, type your requirement (e.g., "Electricity maintenance notice for 2 hours"), and click <strong>Generate</strong>.</p>

                <img src="<?= BASE_URL ?>assets/img/ai.png" class="manual-img" alt="AI Assistant Interface">
            </div>

        </div>
    </div>
</div>

<script>
// Smooth scrolling for index links
document.querySelectorAll('.manual-nav a').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        document.querySelector(this.getAttribute('href')).scrollIntoView({
            behavior: 'smooth'
        });
        
        // Update active class
        document.querySelectorAll('.manual-nav a').forEach(a => a.classList.remove('active'));
        this.classList.add('active');
    });
});

// Update clock if available in header
if(typeof updateClockApp === 'function') {
    updateClockApp();
}
</script>

<?php require_once '../../includes/footer.php'; ?>