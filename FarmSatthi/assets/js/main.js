/**
 * Farm Management System - Main JavaScript
 */

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (mobileMenuToggle && navMenu) {
        mobileMenuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
        });
    }
    
    // Auto-hide flash messages after 5 seconds
    const flashMessage = document.getElementById('flash-message');
    if (flashMessage) {
        setTimeout(function() {
            flashMessage.style.display = 'none';
        }, 5000);
    }
    
    // Form validation
    const forms = document.querySelectorAll('.data-form, .login-form');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#dc3545';
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
        
        // Remove error styling on input
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(function(input) {
            input.addEventListener('input', function() {
                this.style.borderColor = '';
            });
        });
    });
    
    // Confirm delete actions
    const deleteLinks = document.querySelectorAll('.btn-delete');
    deleteLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
    
    // Number input validation
    const numberInputs = document.querySelectorAll('input[type="number"]');
    numberInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            if (this.value < 0 && this.min === '0') {
                this.value = 0;
            }
        });
    });
    
    // Date input - set max date to today for past dates
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(function(input) {
        if (input.name === 'purchase_date' || input.name === 'hire_date' || input.name === 'planting_date') {
            // These can be in the past, set max to today
            const today = new Date().toISOString().split('T')[0];
            if (!input.max) {
                input.max = today;
            }
        }
    });
    
    // Table row highlighting
    const tableRows = document.querySelectorAll('.data-table tbody tr');
    tableRows.forEach(function(row) {
        row.addEventListener('click', function(e) {
            // Don't highlight if clicking on action buttons
            if (!e.target.closest('.actions')) {
                this.style.backgroundColor = '#e8f5e9';
                setTimeout(() => {
                    this.style.backgroundColor = '';
                }, 300);
            }
        });
    });
    
    // Search input auto-focus
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput && !document.querySelector('.login-page')) {
        // Don't auto-focus on mobile
        if (window.innerWidth > 768) {
            searchInput.focus();
        }
    }
    
    // Password strength indicator (for registration)
    const passwordInput = document.querySelector('input[name="password"]');
    const confirmPasswordInput = document.querySelector('input[name="confirm_password"]');
    
    if (passwordInput && confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value && this.value !== passwordInput.value) {
                this.setCustomValidity('Passwords do not match');
                this.style.borderColor = '#dc3545';
            } else {
                this.setCustomValidity('');
                this.style.borderColor = '';
            }
        });
        
        passwordInput.addEventListener('input', function() {
            if (confirmPasswordInput.value) {
                confirmPasswordInput.dispatchEvent(new Event('input'));
            }
        });
    }
    
    // Smooth scroll for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
    
    // Print functionality for reports
    if (window.location.pathname.includes('reports')) {
        const printButton = document.createElement('button');
        printButton.textContent = '🖨️ Print Report';
        printButton.className = 'btn btn-secondary';
        printButton.style.marginLeft = '1rem';
        
        const moduleHeader = document.querySelector('.module-header');
        if (moduleHeader) {
            printButton.addEventListener('click', function() {
                window.print();
            });
            moduleHeader.appendChild(printButton);
        }
    }
    
    // Auto-calculate for inventory reorder alerts
    const quantityInput = document.querySelector('input[name="quantity"]');
    const reorderInput = document.querySelector('input[name="reorder_level"]');
    
    if (quantityInput && reorderInput) {
        function checkReorderLevel() {
            const quantity = parseFloat(quantityInput.value) || 0;
            const reorder = parseFloat(reorderInput.value) || 0;
            
            if (quantity <= reorder && quantity > 0) {
                quantityInput.style.borderColor = '#ffc107';
                quantityInput.style.backgroundColor = '#fff3cd';
            } else {
                quantityInput.style.borderColor = '';
                quantityInput.style.backgroundColor = '';
            }
        }
        
        quantityInput.addEventListener('input', checkReorderLevel);
        reorderInput.addEventListener('input', checkReorderLevel);
        checkReorderLevel();
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('input[name="search"]');
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
        
        // Escape to close mobile menu
        if (e.key === 'Escape' && navMenu) {
            navMenu.classList.remove('active');
        }
    });
    
    // Loading state for forms
    const submitButtons = document.querySelectorAll('button[type="submit"]');
    submitButtons.forEach(function(button) {
        button.closest('form')?.addEventListener('submit', function() {
            button.disabled = true;
            button.textContent = 'Processing...';
            
            // Re-enable after 3 seconds in case of error
            setTimeout(function() {
                button.disabled = false;
                button.textContent = button.getAttribute('data-original-text') || 'Submit';
            }, 3000);
        });
        
        // Store original text
        button.setAttribute('data-original-text', button.textContent);
    });
});

// Utility function to format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// Utility function to format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    }).format(date);
}

// Export for use in other scripts
window.FarmMS = {
    formatCurrency: formatCurrency,
    formatDate: formatDate
};
