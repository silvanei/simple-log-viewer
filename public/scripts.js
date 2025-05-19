// Theme handling
const themeStorageKey = 'logViewerTheme';

function initializeTheme() {
    const savedTheme = localStorage.getItem(themeStorageKey);
    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    const initialTheme = savedTheme || systemTheme;

    document.documentElement.classList.add(initialTheme);
}

function toggleTheme() {
    const isDark = document.documentElement.classList.contains('dark');
    const newTheme = isDark ? 'light' : 'dark';

    document.documentElement.classList.remove(isDark ? 'dark' : 'light');
    document.documentElement.classList.add(newTheme);
    localStorage.setItem(themeStorageKey, newTheme);
}

let hasNewLogs = false;
let isLogsPaused = false;

document.addEventListener('DOMContentLoaded', () => {
    const pauseButton = document.getElementById('pause-button');
    if (pauseButton) {
        pauseButton.classList.toggle('paused', isLogsPaused);
    }
});

function togglePauseLogs() {
    const pauseButton = document.getElementById('pause-button');
    const playIcon = pauseButton.querySelector('.play-icon');
    const pauseIcon = pauseButton.querySelector('.pause-icon');
    
    isLogsPaused = !isLogsPaused;
    pauseButton.classList.toggle('paused', isLogsPaused);
    
    playIcon.classList.toggle('hidden', !isLogsPaused);
    pauseIcon.classList.toggle('hidden', isLogsPaused);
    
    if (!isLogsPaused) {
        if (hasNewLogs) {
            triggerSearch();
        }
        updateNotificationDot(false);
    }
}

function triggerSearch() {
    const searchInput = document.getElementById('search-input');
    htmx.trigger(searchInput, 'search-trigger');
    updateNotificationDot(false);
    hasNewLogs = false;
}

function updateNotificationDot(show) {
    const dot = document.querySelector('.notification-dot');
    if (show) {
        dot.classList.remove('hidden');
    } else {
        dot.classList.add('hidden');
    }
}

function handleNewLogs(searchInput) {
    if (!isLogsPaused) {
        triggerSearch();
    } else {
        hasNewLogs = true;
        updateNotificationDot(true);
    }
}

// Copy functionality
async function copyJSON(event) {
    const btn = event.currentTarget;
    const logContent = btn.closest('.log-content');
    const encodedData = logContent?.dataset?.json;

    if (!encodedData) {
        showFeedback(btn, 'No data to copy!', false);
        return;
    }

    try {
        const decoded = atob(encodedData);
        if (navigator.clipboard) {
            await navigator.clipboard.writeText(decoded);
        } else {
            const textArea = document.createElement('textarea');
            textArea.value = decoded;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
        }
        showFeedback(btn, 'Copied!', true);
    } catch (error) {
        showFeedback(btn, 'Copy failed!', false);
        console.error('Copy error:', error);
    }
}

function showFeedback(button, text, success) {
    const originalHTML = button.innerHTML;
    const color = success ? 'var(--text)' : 'var(--error)';

    button.innerHTML = `<span style="color:${color}">${text}</span>`;
    button.style.pointerEvents = 'none';

    setTimeout(() => {
        button.innerHTML = originalHTML;
        button.style.pointerEvents = 'auto';
    }, 1000);
}

// Highlight functionality
function toggleHighlight(event) {
    const btn = event.currentTarget;
    const isExpanding = !btn.classList.contains('expanded');
    const logContent = btn.closest('.log-content');
    const highlights = Element.prototype.querySelectorAll.call(logContent, '.highlight-toggle');
    const iconToggle = Element.prototype.querySelectorAll.call(logContent, '.icon-toggle');

    btn.classList.toggle('expanded');
    btn.querySelector('span').textContent = isExpanding ? 'Collapse All' : 'Expand All';

    highlights.forEach(el => {
        el.classList.toggle('highlight-toggle-display', !isExpanding);
    });

    iconToggle.forEach(el => {
        el.classList.toggle('rotate-180', isExpanding);
    });
}

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    initializeTheme();

    // Theme toggle
    document.getElementById('theme-toggle').addEventListener('click', toggleTheme);

    // Search input events
    const searchInput = document.getElementById('search-input');
    const pauseButton = document.getElementById('pause-button');
    
    if (pauseButton) {
        pauseButton.addEventListener('click', togglePauseLogs);
    }

    // Só atualize a busca quando pressionar Enter
    searchInput.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') {
            triggerSearch();
        }
    });

    // SSE events
    const searchResults = document.getElementById('search-results');
    searchResults.addEventListener('htmx:sseBeforeMessage', (evt) => {
        evt.preventDefault();
        handleNewLogs(searchInput);
    });
    searchResults.addEventListener('htmx:sseOpen', () => {
        handleNewLogs(searchInput);
    });
});

// Clear logs functionality
async function clearLogs() {
    try {
        const response = await fetch('/api/logs/clear', {
            method: 'POST',
        });
        
        if (response.ok) {
            triggerSearch(); // Refresh the logs view
        } else {
            console.error('Failed to clear logs');
        }
    } catch (error) {
        console.error('Error clearing logs:', error);
    }
}

// Expose functions needed by HTML
window.copyJSON = copyJSON;
window.toggleHighlight = toggleHighlight;
window.triggerSearch = triggerSearch;
window.clearLogs = clearLogs;