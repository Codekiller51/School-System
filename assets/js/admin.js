// Sidebar Toggle
let sidebar = document.querySelector(".sidebar");
let sidebarBtn = document.querySelector(".sidebarBtn");
let screenWidth = window.innerWidth;

// Set initial state based on screen size
if (screenWidth <= 768) {
    sidebar.classList.add("close");
}

sidebarBtn.addEventListener("click", () => {
    sidebar.classList.toggle("close");
});

// Theme Toggle
const themeToggle = document.querySelector('.theme-toggle');
const prefersDarkScheme = window.matchMedia('(prefers-color-scheme: dark)');

// Set initial theme based on system preference
if (prefersDarkScheme.matches) {
    document.body.setAttribute('data-theme', 'dark');
}

if (themeToggle) {
    themeToggle.addEventListener('click', () => {
        const currentTheme = document.body.getAttribute('data-theme');
        if (currentTheme === 'dark') {
            document.body.removeAttribute('data-theme');
        } else {
            document.body.setAttribute('data-theme', 'dark');
        }
    });
}

// Search Functionality
const searchBox = document.querySelector('.search-box');
const searchInput = searchBox?.querySelector('input');
const searchResults = document.createElement('div');
searchResults.classList.add('search-results');

if (searchBox && searchInput) {
    searchBox.appendChild(searchResults);
    
    let searchTimeout;
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        const query = e.target.value.trim();
        
        if (query.length > 2) {
            searchTimeout = setTimeout(() => {
                fetch(`search.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        searchResults.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.classList.add('search-item');
                                div.innerHTML = `
                                    <a href="${item.url}">
                                        <i class='bx ${item.icon}'></i>
                                        <span>${item.title}</span>
                                    </a>
                                `;
                                searchResults.appendChild(div);
                            });
                            searchResults.style.display = 'block';
                        } else {
                            searchResults.style.display = 'none';
                        }
                    })
                    .catch(error => console.error('Search error:', error));
            }, 300);
        } else {
            searchResults.style.display = 'none';
        }
    });
}

// Close search results when clicking outside
document.addEventListener('click', (e) => {
    if (!searchBox?.contains(e.target)) {
        searchResults.style.display = 'none';
    }
});

// Error and Success Messages
function showMessage(message, type = 'success') {
    const messageDiv = document.createElement('div');
    messageDiv.classList.add('alert', `alert-${type}`, 'message-popup');
    messageDiv.textContent = message;
    document.body.appendChild(messageDiv);
    
    setTimeout(() => {
        messageDiv.classList.add('show');
    }, 100);
    
    setTimeout(() => {
        messageDiv.classList.remove('show');
        setTimeout(() => messageDiv.remove(), 300);
    }, 3000);
}

// Handle AJAX errors
function handleAjaxError(error) {
    console.error('AJAX Error:', error);
    showMessage('An error occurred. Please try again.', 'danger');
}

// Format date and time
function formatDateTime(date) {
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Format numbers
function formatNumber(number) {
    return new Intl.NumberFormat('en-US').format(number);
}

// Handle empty states
function showEmptyState(container, message, icon = 'bx-info-circle') {
    container.innerHTML = `
        <div class="empty-state">
            <i class='bx ${icon}'></i>
            <p>${message}</p>
        </div>
    `;
}

// Export functionality
function exportData(data, filename, type = 'csv') {
    let content;
    if (type === 'csv') {
        const headers = Object.keys(data[0]);
        content = [
            headers.join(','),
            ...data.map(row => headers.map(header => `"${row[header]}"`).join(','))
        ].join('\n');
    } else if (type === 'json') {
        content = JSON.stringify(data, null, 2);
    }
    
    const blob = new Blob([content], { type: 'text/plain' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `${filename}.${type}`;
    a.click();
    window.URL.revokeObjectURL(url);
}

// Print functionality
function printData(elementId) {
    const printContents = document.getElementById(elementId).innerHTML;
    const originalContents = document.body.innerHTML;
    
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    
    // Reinitialize event listeners
    location.reload();
}
