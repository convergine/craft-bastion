/**
 * Dependency Audit JavaScript
 */
(function() {
    'use strict';

    const DependencyAudit = {
        /**
         * Initialize
         */
        init() {
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents() {
            // Run Scan button
            const runScanBtn = document.querySelector('.btn-scan');
            if (runScanBtn) {
                runScanBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.runScan(runScanBtn);
                });
            }
        },

        /**
         * Run dependency scan
         */
        async runScan(button) {
            const originalText = button.innerHTML;

            // Disable button and show loading state
            button.disabled = true;
            button.innerHTML = `
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="spin">
                    <path d="M21 12a9 9 0 11-6.219-8.56"/>
                </svg>
                Scanning...
            `;

            try {
                // Call the scan endpoint
                const response = await fetch('/admin/actions/craft-bastion/base/dependency-audit-scan', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': Craft.csrfTokenValue
                    }
                });

                const data = await response.json();
console.log(data);
                if (data.success) {
                    // Reload the page to show new results
                    window.location.reload();
                } else {
                    this.showError('Scan failed: ' + (data.data.error || 'Unknown error'));
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            } catch (error) {
                console.error('Scan error:', error);
                this.showError('Failed to run scan. Please try again.');
                button.disabled = false;
                button.innerHTML = originalText;
            }
        },

        /**
         * Show error message
         */
        showError(message) {
            Craft.cp.displayError(message);
        }
    };

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => DependencyAudit.init());
    } else {
        DependencyAudit.init();
    }

})();
