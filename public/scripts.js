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

// Search functionality
let hasNewLogs = false;

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
    if (!searchInput.value.trim()) {
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
        isExpanding ?
            el.classList.remove('highlight-toggle-display') :
            el.classList.add('highlight-toggle-display');
    });

    iconToggle.forEach(el => {
        isExpanding ?
            el.classList.add('rotate-180') :
            el.classList.remove('rotate-180');
    });
}

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
    initializeTheme();

    // Theme toggle
    document.getElementById('theme-toggle').addEventListener('click', toggleTheme);

    // Search input events
    const searchInput = document.getElementById('search-input');
    searchInput.addEventListener('input', (e) => {
        if (!e.target.value.trim()) {
            triggerSearch();
        }
    });
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

// Expose functions needed by HTML
window.copyJSON = copyJSON;
window.toggleHighlight = toggleHighlight;
window.triggerSearch = triggerSearch;