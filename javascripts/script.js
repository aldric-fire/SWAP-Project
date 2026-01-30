// ========================================
// MAIN JAVASCRIPT FILE
// ========================================
// Handles client-side interactivity and form validation

// ========================================
// DELETE CONFIRMATION
// ========================================

function confirmDelete(itemName) {
    return confirm('Are you sure you want to delete "' + itemName + '"?');
}

// ========================================
// AUTO-HIDE ALERTS
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');

    alerts.forEach(function(alert) {
        // Wait 3 seconds, then fade out over 0.5 seconds
        setTimeout(function() {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';

            // Remove element after fade completes
            setTimeout(function() {
                alert.remove();
            }, 500);
        }, 3000);
    });
});

// ========================================
// FORM VALIDATION
// ========================================

function validateInventoryForm() {
    const itemName = document.getElementById('item_name').value.trim();
    const quantity = document.getElementById('quantity').value;
    const minThreshold = document.getElementById('min_threshold').value;

    if (itemName === '') {
        alert('Please enter an item name');
        return false;
    }

    if (quantity === '' || quantity < 0) {
        alert('Please enter a valid quantity');
        return false;
    }

    if (minThreshold === '' || minThreshold < 0) {
        alert('Please enter a valid minimum threshold');
        return false;
    }

    return true;
}
