/**
 * Fitness Club Management System - Main JavaScript
 * Handles AJAX requests, form validation, and UI interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    initTooltips();
    
    // Initialize form validation
    initFormValidation();
    
    // Initialize AJAX search
    initAjaxSearch();
    
    // Initialize membership validation
    initMembershipValidation();
    
    // Auto-hide alerts after 5 seconds
    autoHideAlerts();
    
    // Initialize password toggle
    initPasswordToggle();
});

/**
 * Initialize tooltips for elements with title attribute
 */
function initTooltips() {
    const tooltips = document.querySelectorAll('[title]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.title;
            tooltip.style.position = 'absolute';
            tooltip.style.background = 'rgba(0, 0, 0, 0.8)';
            tooltip.style.color = 'white';
            tooltip.style.padding = '5px 10px';
            tooltip.style.borderRadius = '4px';
            tooltip.style.fontSize = '0.8rem';
            tooltip.style.zIndex = '1000';
            tooltip.style.maxWidth = '200px';
            tooltip.style.wordWrap = 'break-word';
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
                this._tooltip = null;
            }
        });
    });
}

/**
 * Initialize form validation for required fields
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    markFieldInvalid(field, 'This field is required');
                } else {
                    markFieldValid(field);
                    
                    // Additional validation for specific field types
                    if (field.type === 'email') {
                        if (!isValidEmail(field.value)) {
                            isValid = false;
                            markFieldInvalid(field, 'Please enter a valid email address');
                        }
                    }
                    
                    if (field.type === 'password' && field.id === 'password') {
                        if (field.value.length < 6) {
                            isValid = false;
                            markFieldInvalid(field, 'Password must be at least 6 characters');
                        }
                    }
                    
                    if (field.type === 'number' && field.min) {
                        if (parseFloat(field.value) < parseFloat(field.min)) {
                            isValid = false;
                            markFieldInvalid(field, `Minimum value is ${field.min}`);
                        }
                    }
                    
                    if (field.type === 'number' && field.max) {
                        if (parseFloat(field.value) > parseFloat(field.max)) {
                            isValid = false;
                            markFieldInvalid(field, `Maximum value is ${field.max}`);
                        }
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showToast('Please fix the errors in the form', 'error');
            }
        });
    });
}

/**
 * Mark a field as invalid
 */
function markFieldInvalid(field, message) {
    field.style.borderColor = '#e74c3c';
    field.style.boxShadow = '0 0 0 3px rgba(231, 76, 60, 0.1)';
    
    // Remove existing error message
    const existingError = field.parentElement.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
    
    // Add error message
    const error = document.createElement('div');
    error.className = 'field-error';
    error.style.color = '#e74c3c';
    error.style.fontSize = '0.85rem';
    error.style.marginTop = '5px';
    error.textContent = message;
    
    field.parentElement.appendChild(error);
}

/**
 * Mark a field as valid
 */
function markFieldValid(field) {
    field.style.borderColor = '#27ae60';
    field.style.boxShadow = '0 0 0 3px rgba(39, 174, 96, 0.1)';
    
    // Remove existing error message
    const existingError = field.parentElement.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

/**
 * Validate email format
 */
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * Initialize AJAX search functionality
 */
function initAjaxSearch() {
    const searchInput = document.getElementById('search');
    const searchForm = document.querySelector('.filter-form');
    
    if (searchInput && searchForm) {
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            
            if (this.value.length >= 2) {
                searchTimeout = setTimeout(() => {
                    performAjaxSearch(this.value);
                }, 300);
            }
        });
        
        // Prevent form submission on Enter in search field
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchForm.submit();
            }
        });
    }
    
    // Check if we're on a page that needs live search
    const liveSearchInput = document.getElementById('live-search');
    if (liveSearchInput) {
        initLiveSearch(liveSearchInput);
    }
}

/**
 * Perform AJAX search
 */
function performAjaxSearch(query) {
    const searchResults = document.getElementById('search-results');
    if (!searchResults) return;
    
    searchResults.innerHTML = '<div class="ajax-loading"></div> Searching...';
    
    fetch(`../ajax/search-members.php?q=${encodeURIComponent(query)}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success && data.results.length > 0) {
            displaySearchResults(data.results);
        } else {
            searchResults.innerHTML = '<div class="no-results">No members found</div>';
        }
    })
    .catch(error => {
        console.error('Search error:', error);
        searchResults.innerHTML = '<div class="error">Search failed. Please try again.</div>';
    });
}

/**
 * Display search results
 */
function displaySearchResults(results) {
    const searchResults = document.getElementById('search-results');
    if (!searchResults) return;
    
    let html = '<div class="search-results-list">';
    
    results.forEach(member => {
        html += `
            <div class="search-result-item" data-member-id="${member.id}">
                <div class="result-header">
                    <strong>${member.name}</strong>
                    <span class="status-badge ${member.status_class}">${member.status}</span>
                </div>
                <div class="result-details">
                    <div>Email: ${member.email}</div>
                    <div>Phone: ${member.phone}</div>
                    <div>Joined: ${member.join_date} • Visits: ${member.visits}</div>
                </div>
                <div class="result-actions">
                    <a href="../members/view.php?id=${member.id}" class="btn btn-sm btn-info">View</a>
                    <a href="../members/edit.php?id=${member.id}" class="btn btn-sm btn-warning">Edit</a>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    searchResults.innerHTML = html;
}

/**
 * Initialize live search for member lookup
 */
function initLiveSearch(inputElement) {
    let timeout;
    const resultsContainer = document.createElement('div');
    resultsContainer.className = 'live-search-results';
    resultsContainer.style.display = 'none';
    inputElement.parentElement.appendChild(resultsContainer);
    
    inputElement.addEventListener('input', function() {
        clearTimeout(timeout);
        
        if (this.value.length >= 2) {
            timeout = setTimeout(() => {
                fetchLiveSearchResults(this.value, resultsContainer);
            }, 300);
        } else {
            resultsContainer.style.display = 'none';
        }
    });
    
    // Hide results when clicking outside
    document.addEventListener('click', function(e) {
        if (!inputElement.contains(e.target) && !resultsContainer.contains(e.target)) {
            resultsContainer.style.display = 'none';
        }
    });
}

/**
 * Fetch live search results
 */
function fetchLiveSearchResults(query, container) {
    fetch(`../ajax/search-members.php?q=${encodeURIComponent(query)}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.results.length > 0) {
            let html = '<ul>';
            data.results.forEach(member => {
                html += `
                    <li data-member-id="${member.id}" 
                        data-member-name="${member.name}"
                        data-member-email="${member.email}">
                        ${member.name} <small>(${member.email})</small>
                    </li>
                `;
            });
            html += '</ul>';
            container.innerHTML = html;
            container.style.display = 'block';
            
            // Add click handlers
            container.querySelectorAll('li').forEach(item => {
                item.addEventListener('click', function() {
                    const memberId = this.getAttribute('data-member-id');
                    const memberName = this.getAttribute('data-member-name');
                    const memberEmail = this.getAttribute('data-member-email');
                    
                    // Fill the input field with selected member
                    const input = container.previousElementSibling;
                    input.value = memberName;
                    input.setAttribute('data-member-id', memberId);
                    
                    // Trigger change event
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                    
                    container.style.display = 'none';
                });
            });
        } else {
            container.style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Live search error:', error);
        container.style.display = 'none';
    });
}

/**
 * Initialize membership validation
 */
function initMembershipValidation() {
    const validateBtn = document.getElementById('validate-membership');
    const checkinBtn = document.getElementById('checkin-member');
    
    if (validateBtn) {
        validateBtn.addEventListener('click', function() {
            const memberId = document.getElementById('member_id').value;
            if (!memberId) {
                showToast('Please select a member first', 'warning');
                return;
            }
            validateMembership(memberId, 'check');
        });
    }
    
    if (checkinBtn) {
        checkinBtn.addEventListener('click', function() {
            const memberId = document.getElementById('member_id').value;
            if (!memberId) {
                showToast('Please select a member first', 'warning');
                return;
            }
            validateMembership(memberId, 'checkin');
        });
    }
}

/**
 * Validate membership via AJAX
 */
function validateMembership(memberId, action) {
    const resultDiv = document.getElementById('validation-result');
    if (!resultDiv) return;
    
    resultDiv.innerHTML = '<div class="ajax-loading"></div> Validating...';
    resultDiv.className = 'validation-pending';
    
    const formData = new FormData();
    formData.append('member_id', memberId);
    formData.append('action', action);
    
    fetch('../ajax/validate-membership.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.className = 'validation-success';
            displayValidationSuccess(data, action);
        } else {
            resultDiv.className = 'validation-error';
            displayValidationError(data);
        }
    })
    .catch(error => {
        console.error('Validation error:', error);
        resultDiv.className = 'validation-error';
        resultDiv.innerHTML = `
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                Network error. Please try again.
            </div>
        `;
    });
}

/**
 * Display validation success
 */
function displayValidationSuccess(data, action) {
    const resultDiv = document.getElementById('validation-result');
    
    let html = `
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <strong>${action === 'checkin' ? 'Check-in Successful!' : 'Membership Valid!'}</strong>
        </div>
        
        <div class="validation-details">
            <div class="detail-section">
                <h4><i class="fas fa-user"></i> Member Details</h4>
                <p><strong>Name:</strong> ${data.member.name}</p>
                <p><strong>Status:</strong> 
                    <span class="status-badge success">${data.member.status}</span>
                </p>
            </div>
            
            <div class="detail-section">
                <h4><i class="fas fa-id-card"></i> Membership Details</h4>
                <p><strong>Plan:</strong> ${data.membership.plan_name} (${data.membership.plan_type})</p>
                <p><strong>Expiry:</strong> ${data.membership.expiry_date}</p>
                <p><strong>Status:</strong> 
                    <span class="status-badge ${data.membership.status}">
                        ${data.membership.days_left} days left
                    </span>
                </p>
                <p><strong>Payment:</strong> 
                    <span class="status-badge success">${data.membership.payment_status}</span>
                </p>
            </div>
    `;
    
    if (action === 'checkin' && data.checkin_time) {
        html += `
            <div class="detail-section">
                <h4><i class="fas fa-clock"></i> Check-in Details</h4>
                <p><strong>Time:</strong> ${formatDateTime(data.checkin_time)}</p>
                <p><strong>Attendance ID:</strong> #${data.attendance_id}</p>
            </div>
        `;
    }
    
    html += '</div>';
    resultDiv.innerHTML = html;
}

/**
 * Display validation error
 */
function displayValidationError(data) {
    const resultDiv = document.getElementById('validation-result');
    
    let html = `
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <strong>Validation Failed</strong>
            <p>${data.message}</p>
        </div>
    `;
    
    if (data.member_name) {
        html += `
            <div class="validation-details">
                <div class="detail-section">
                    <h4><i class="fas fa-user"></i> Member Details</h4>
                    <p><strong>Name:</strong> ${data.member_name}</p>
                    <p><strong>Status:</strong> 
                        <span class="status-badge ${data.member_status === 'Active' ? 'success' : 'error'}">
                            ${data.member_status}
                        </span>
                    </p>
                </div>
            </div>
        `;
    }
    
    resultDiv.innerHTML = html;
}

/**
 * Format date time for display
 */
function formatDateTime(dateTimeString) {
    const date = new Date(dateTimeString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
}

/**
 * Auto hide alerts after 5 seconds
 */
function autoHideAlerts() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 500);
        }, 5000);
    });
}

/**
 * Initialize password toggle visibility
 */
function initPasswordToggle() {
    const passwordToggles = document.querySelectorAll('.password-toggle');
    
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const input = this.previousElementSibling;
            if (input.type === 'password') {
                input.type = 'text';
                this.innerHTML = '<i class="fas fa-eye-slash"></i>';
            } else {
                input.type = 'password';
                this.innerHTML = '<i class="fas fa-eye"></i>';
            }
        });
    });
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.toast');
    existingToasts.forEach(toast => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    });
    
    // Create new toast
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.padding = '15px 20px';
    toast.style.borderRadius = '4px';
    toast.style.color = 'white';
    toast.style.fontWeight = '500';
    toast.style.zIndex = '9999';
    toast.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
    toast.style.transform = 'translateX(150%)';
    toast.style.transition = 'transform 0.3s ease';
    
    // Set background color based on type
    switch(type) {
        case 'success':
            toast.style.backgroundColor = '#27ae60';
            break;
        case 'error':
            toast.style.backgroundColor = '#e74c3c';
            break;
        case 'warning':
            toast.style.backgroundColor = '#f39c12';
            break;
        default:
            toast.style.backgroundColor = '#3498db';
    }
    
    toast.textContent = message;
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 10);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        toast.style.transform = 'translateX(150%)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 5000);
    
    // Allow manual dismiss on click
    toast.addEventListener('click', function() {
        this.style.transform = 'translateX(150%)';
        setTimeout(() => {
            if (this.parentNode) {
                this.parentNode.removeChild(this);
            }
        }, 300);
    });
}

/**
 * Format currency
 */
function formatCurrency(amount) {
    return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

/**
 * Calculate age from date of birth
 */
function calculateAge(dateString) {
    const today = new Date();
    const birthDate = new Date(dateString);
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    
    return age;
}

/**
 * Confirm action with custom message
 */
function confirmAction(message) {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.right = '0';
        overlay.style.bottom = '0';
        overlay.style.backgroundColor = 'rgba(0,0,0,0.5)';
        overlay.style.zIndex = '9998';
        overlay.style.display = 'flex';
        overlay.style.justifyContent = 'center';
        overlay.style.alignItems = 'center';
        
        const modal = document.createElement('div');
        modal.style.background = 'white';
        modal.style.padding = '30px';
        modal.style.borderRadius = '8px';
        modal.style.maxWidth = '400px';
        modal.style.width = '90%';
        modal.style.boxShadow = '0 10px 30px rgba(0,0,0,0.3)';
        
        modal.innerHTML = `
            <h3 style="margin-bottom: 15px; color: #2c3e50;">Confirm Action</h3>
            <p style="margin-bottom: 25px; color: #666;">${message}</p>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button id="confirmCancel" style="padding: 8px 16px; background: #95a5a6; color: white; border: none; border-radius: 4px; cursor: pointer;">Cancel</button>
                <button id="confirmOk" style="padding: 8px 16px; background: #e74c3c; color: white; border: none; border-radius: 4px; cursor: pointer;">Confirm</button>
            </div>
        `;
        
        overlay.appendChild(modal);
        document.body.appendChild(overlay);
        
        document.getElementById('confirmCancel').addEventListener('click', () => {
            document.body.removeChild(overlay);
            resolve(false);
        });
        
        document.getElementById('confirmOk').addEventListener('click', () => {
            document.body.removeChild(overlay);
            resolve(true);
        });
    });
}

// Export functions for use in other scripts
window.FitnessClub = {
    showToast,
    confirmAction,
    formatCurrency,
    calculateAge,
    validateMembership
};