// ===== ASSIGNED DEVICES JAVASCRIPT =====

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize assigned devices functionality
    initializeAssignedDevices();
    
    // Search functionality
    initializeSearch();
    
    // Filter functionality
    initializeFilters();
    
    // Print functionality
    initializePrint();
    
    // Back button functionality
    initializeBackButton();
    
    // Update button functionality
    initializeUpdateButton();
});

// Initialize main functionality
function initializeAssignedDevices() {
    console.log('Assigned Devices page initialized');
    
    // Add any initialization code here
    setupTableInteractions();
}

// Search functionality
function initializeSearch() {
    const searchInput = document.querySelector('input[placeholder="Search"]');
    
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            filterTable(searchTerm);
        });
    }
}

// Filter table rows based on search term
function filterTable(searchTerm) {
    const table = document.querySelector('.devices-table');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Initialize filters (date range, etc.)
function initializeFilters() {
    const applyBtn = document.querySelector('.btn-primary');
    const clearBtn = document.querySelector('.btn-outline-secondary');
    
    if (applyBtn) {
        applyBtn.addEventListener('click', function() {
            applyFilters();
        });
    }
    
    if (clearBtn) {
        clearBtn.addEventListener('click', function() {
            clearFilters();
        });
    }
}

// Apply filters function
function applyFilters() {
    const dateInputs = document.querySelectorAll('input[type="text"][placeholder="dd/mm/yyyy"]');
    
    // Get filter values
    const startDate = dateInputs[0] ? dateInputs[0].value : '';
    const endDate = dateInputs[1] ? dateInputs[1].value : '';
    
    console.log('Applying filters:', { startDate, endDate });
    
    // Add your filter logic here
    // This would typically involve AJAX requests to filter data
    
    // Show feedback
    showFilterFeedback('Filters applied successfully!');
}

// Clear all filters
function clearFilters() {
    // Clear search
    const searchInput = document.querySelector('input[placeholder="Search"]');
    if (searchInput) {
        searchInput.value = '';
    }
    
    // Clear date inputs
    const dateInputs = document.querySelectorAll('input[type="text"][placeholder="dd/mm/yyyy"]');
    dateInputs.forEach(input => {
        input.value = '';
    });
    
    // Reset table display
    const rows = document.querySelectorAll('.devices-table tbody tr');
    rows.forEach(row => {
        row.style.display = '';
    });
    
    console.log('Filters cleared');
    showFilterFeedback('Filters cleared!');
}

// Show filter feedback
function showFilterFeedback(message) {
    // Create temporary feedback element
    const feedback = document.createElement('div');
    feedback.className = 'alert alert-info alert-dismissible fade show';
    feedback.style.position = 'fixed';
    feedback.style.top = '20px';
    feedback.style.right = '20px';
    feedback.style.zIndex = '9999';
    feedback.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(feedback);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        if (feedback.parentNode) {
            feedback.parentNode.removeChild(feedback);
        }
    }, 3000);
}

// Initialize print functionality
function initializePrint() {
    const printBtn = document.querySelector('.btn-outline-dark');
    
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            printAssignedDevices();
        });
    }
}

// Print assigned devices
function printAssignedDevices() {
    // Hide controls during print
    const controls = document.querySelector('.row.mb-4');
    if (controls) {
        controls.style.display = 'none';
    }
    
    // Print the page
    window.print();
    
    // Restore controls after print
    setTimeout(() => {
        if (controls) {
            controls.style.display = '';
        }
    }, 1000);
}

// Print form function (called by onclick)
function printForm() {
    window.print();
}

// Initialize back button
function initializeBackButton() {
    const backBtn = document.querySelector('.btn-secondary');
    
    if (backBtn) {
        backBtn.addEventListener('click', function(e) {
            e.preventDefault();
            goBack();
        });
    }
}

// Go back to assignments page
function goBack() {
    // Check if there's a previous page in history
    if (window.history.length > 1) {
        window.history.back();
    } else {
        // Fallback to assignments page
        window.location.href = 'assignments.php';
    }
}

// Initialize update button
function initializeUpdateButton() {
    const updateBtn = document.querySelector('.btn-success');
    
    if (updateBtn) {
        updateBtn.addEventListener('click', function() {
            updateAssignedDevices();
        });
    }
}

// Update assigned devices
function updateAssignedDevices() {
    console.log('Update functionality would be implemented here');
    
    // Show confirmation
    if (confirm('Are you sure you want to update the assigned devices?')) {
        // Add your update logic here
        // This would typically involve form submission or AJAX request
        
        showFilterFeedback('Assigned devices updated successfully!');
    }
}

// Setup table interactions
function setupTableInteractions() {
    const table = document.querySelector('.devices-table');
    
    if (table) {
        // Add hover effects or click handlers if needed
        const rows = table.querySelectorAll('tbody tr');
        
        rows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#f8f9fa';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    }
}

// Utility function to format dates
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
}


