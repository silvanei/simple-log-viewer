// Theme handling
const themeStorageKey = 'logViewerTheme';
const selectedFieldsKey = 'logViewerSelectedFields';

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
let selectedFields = [];

// Field Management Functions
function loadSelectedFields() {
  const saved = localStorage.getItem(selectedFieldsKey);
  selectedFields = saved ? JSON.parse(saved) : [];
  createSelectedFieldsInput();
}

function saveSelectedFields() {
  localStorage.setItem(selectedFieldsKey, JSON.stringify(selectedFields));
  createSelectedFieldsInput();
}

function isFieldSelected(fieldName) {
    return selectedFields.includes(fieldName);
}

function createSelectedFieldsInput() {
  const searchContainer = document.getElementById('search-container-id');
  const existingFields = searchContainer.querySelectorAll('.fields');
  existingFields.forEach(el => el.remove());

  selectedFields.forEach(fieldName => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'fields[]'
    input.className = 'fields';
    input.value = fieldName;
    searchContainer.appendChild(input);
  });
}

function toggleField(event, fieldName) {
    event.stopPropagation();
    const isCurrentlySelected = isFieldSelected(fieldName);
    if (isCurrentlySelected) {
        selectedFields = selectedFields.filter(f => f !== fieldName);
    } else {
        selectedFields.push(fieldName);
    }

  saveSelectedFields();
  triggerSearch();
}


function initializeFieldManagement() {
    loadSelectedFields();
}

document.addEventListener('DOMContentLoaded', () => {
    const pauseButton = document.getElementById('pause-button');
    if (pauseButton) {
        pauseButton.classList.toggle('paused', isLogsPaused);
        pauseButton.setAttribute('aria-pressed', isLogsPaused.toString());
        pauseButton.setAttribute('aria-label', isLogsPaused ? 'Resume' : 'Pause');
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

    pauseButton.setAttribute('aria-pressed', isLogsPaused.toString());
    pauseButton.setAttribute('aria-label', isLogsPaused ? 'Resume' : 'Pause');

    announceStatus(isLogsPaused ? 'Logs paused' : 'Logs resumed');

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
        announceStatus('New logs available');
    } else {
        dot.classList.add('hidden');
    }
}

function announceStatus(message) {
    const liveRegion = document.getElementById('live-status');
    if (liveRegion) {
        liveRegion.textContent = message;
        // Clear after announcement for repeated messages
        setTimeout(() => {
            liveRegion.textContent = '';
        }, 1000);
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

function toggleLogEntry(button) {
    const logContent = button.closest('.row-main').nextElementSibling;
    const isExpanded = button.getAttribute('aria-expanded') === 'true';

    button.setAttribute('aria-expanded', (!isExpanded).toString());
    button.classList.toggle('rotate-180');
    logContent.classList.toggle('collapsed');
}

// Event Listeners
document.addEventListener('DOMContentLoaded', () => {
  initializeTheme();
  initializeFieldManagement();

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
window.toggleHighlight = toggleHighlight;
window.toggleLogEntry = toggleLogEntry;
window.triggerSearch = triggerSearch;
window.clearLogs = clearLogs;