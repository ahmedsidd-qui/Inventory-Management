/**
 * JavaScript Utilities
 * Form validation and interactive features
 */

// Confirm before deletion
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Format currency input
function formatCurrencyInput(input) {
    let value = input.value.replace(/[^0-9.]/g, '');
    input.value = value;
}

// Calculate total amount
function calculateTotal(quantity, price, totalElement) {
    const qty = parseFloat(quantity) || 0;
    const prc = parseFloat(price) || 0;
    const total = qty * prc;

    if (totalElement) {
        totalElement.textContent = 'PKR ' + total.toFixed(2);
    }

    return total;
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');

    for (let input of inputs) {
        if (!input.value.trim()) {
            alert('Please fill in all required fields');
            input.focus();
            return false;
        }
    }

    return true;
}



// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {


    // Mobile Navigation Toggle
    const navToggle = document.getElementById('navToggle');
    const navLinks = document.querySelector('.nav-links');

    if (navToggle && navLinks) {
        navToggle.addEventListener('click', function () {
            navLinks.classList.toggle('active');
            // Change icon based on state
            if (navLinks.classList.contains('active')) {
                navToggle.textContent = '✕'; // Close icon
            } else {
                navToggle.textContent = '☰'; // Hamburger icon
            }
        });
    }

    // Add loading state to buttons
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function (e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing...';
            }
        });
    });
});
