// Theme handling
const themeStorageKey = 'logViewerTheme';
const selectedFieldsKey = 'logViewerSelectedFields';

// Detect reduced motion preference
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Apply reduced motion class to body for CSS targeting
if (prefersReducedMotion) {
    document.documentElement.classList.add('reduced-motion');
}

// Track whether user is using keyboard or mouse for focus management
let wasKeyboardUsed = false;

// Detect keyboard vs mouse interaction
document.addEventListener('keydown', () => {
    wasKeyboardUsed = true;
}, { capture: true });

document.addEventListener('mousedown', () => {
    wasKeyboardUsed = false;
}, { capture: true });

// Apply focus classes based on input method
document.addEventListener('DOMContentLoaded', () => {
    // For search input - only show focus ring if keyboard was used
    const searchInput = document.getElementById('search-input');
    if (searchInput) {
        searchInput.addEventListener('focus', () => {
            if (wasKeyboardUsed) {
                searchInput.classList.add('show-focus');
            } else {
                searchInput.classList.remove('show-focus');
            }
        });
    }
});

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

// ==========================================================================
// FOCUS MANAGEMENT & ACCESSIBILITY
// ==========================================================================

/**
 * Announce search results count via live region
 * @param {number} count - Number of results found
 * @param {string} searchTerm - The search term used (optional)
 */
function announceResults(count, searchTerm = '') {
    const liveRegion = document.getElementById('live-status');
    if (liveRegion) {
        let message;
        if (count === 0) {
            message = searchTerm
                ? `No log entries found for "${searchTerm}"`
                : 'No log entries found';
        } else if (count === 1) {
            message = searchTerm
                ? `1 log entry found for "${searchTerm}"`
                : '1 log entry found';
        } else {
            message = searchTerm
                ? `${count} log entries found for "${searchTerm}"`
                : `${count} log entries found`;
        }
        liveRegion.textContent = message;
        // Clear after announcement for repeated messages
        setTimeout(() => {
            liveRegion.textContent = '';
        }, 3000);
    }
}

/**
 * Focus management after search results update
 * @param {boolean} keepFocusPosition - Whether to maintain focus position
 */
function manageFocusAfterSearch(keepFocusPosition = false) {
    const searchInput = document.getElementById('search-input');
    if (!searchInput) return;

    // Return focus to search input after content update
    searchInput.focus();
}

/**
 * Handle log entry row keyboard navigation
 * @param {KeyboardEvent} event
 */
function handleRowKeydown(event) {
    const row = event.currentTarget;
    const rows = Array.from(document.querySelectorAll('.row-main[tabindex="0"]'));
    const currentIndex = rows.indexOf(row);

    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            if (currentIndex < rows.length - 1) {
                rows[currentIndex + 1].focus();
            }
            break;
        case 'ArrowUp':
            event.preventDefault();
            if (currentIndex > 0) {
                rows[currentIndex - 1].focus();
            }
            break;
        case 'Home':
            event.preventDefault();
            if (rows.length > 0) {
                rows[0].focus();
            }
            break;
        case 'End':
            event.preventDefault();
            if (rows.length > 0) {
                rows[rows.length - 1].focus();
            }
            break;
        case 'Enter':
        case ' ':
            event.preventDefault();
            // Find the expand button in this row and click it
            const expandButton = row.querySelector('button[aria-label^="Expand"]');
            if (expandButton) {
                expandButton.click();
            }
            break;
    }
}

/**
 * Initialize row keyboard navigation
 */
function initializeRowNavigation() {
    const rows = document.querySelectorAll('.row-main[tabindex="0"]');
    rows.forEach(row => {
        row.addEventListener('keydown', handleRowKeydown);
    });
}

// Observe search results for updates and announce count
function setupSearchResultsObserver() {
    const searchResults = document.getElementById('search-results');
    if (!searchResults) return;

    const observer = new MutationObserver((mutations) => {
        const rows = searchResults.querySelectorAll('.row-main');
        const count = rows.length;
        if (count > 0) {
            announceResults(count);
            initializeRowNavigation();
        }
    });

    observer.observe(searchResults, { childList: true, subtree: true });
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    initializeRowNavigation();
    setupSearchResultsObserver();
});

// Re-initialize after HTMX content swaps
document.body.addEventListener('htmx:afterSwap', () => {
    initializeRowNavigation();
    const rows = document.querySelectorAll('.row-main');
    announceResults(rows.length);
});

// ==========================================================================
// KEYBOARD SHORTCUTS MODAL
// ==========================================================================

/**
 * Open the keyboard shortcuts modal
 */
function openKeyboardShortcutsModal() {
    const modal = document.getElementById('keyboard-shortcuts-modal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        // Focus the close button for accessibility
        const closeBtn = modal.querySelector('.modal-close');
        if (closeBtn) {
            closeBtn.focus();
        }
        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }
}

/**
 * Close the keyboard shortcuts modal
 */
function closeKeyboardShortcutsModal() {
    const modal = document.getElementById('keyboard-shortcuts-modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        // Restore body scroll
        document.body.style.overflow = '';
        // Return focus to the keyboard shortcuts button if it exists
        const helpBtn = document.getElementById('keyboard-help-button');
        if (helpBtn) {
            helpBtn.focus();
        }
    }
}

/**
 * Toggle the keyboard shortcuts modal
 */
function toggleKeyboardShortcutsModal() {
    const modal = document.getElementById('keyboard-shortcuts-modal');
    if (modal && !modal.classList.contains('hidden')) {
        closeKeyboardShortcutsModal();
    } else {
        openKeyboardShortcutsModal();
    }
}

/**
 * Handle global keyboard shortcuts
 * @param {KeyboardEvent} event
 */
function handleGlobalKeydown(event) {
    // Don't trigger if user is typing in an input
    const tagName = event.target.tagName.toLowerCase();
    const isTextInput = tagName === 'input' || tagName === 'textarea' || tagName === 'search';

    // Show shortcuts modal with '?' key (only when not typing)
    if (event.key === '?' && !isTextInput) {
        event.preventDefault();
        toggleKeyboardShortcutsModal();
    }

    // Close modal with Escape
    if (event.key === 'Escape') {
        const modal = document.getElementById('keyboard-shortcuts-modal');
        if (modal && !modal.classList.contains('hidden')) {
            closeKeyboardShortcutsModal();
        }
    }
}

// Initialize keyboard shortcuts modal on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    document.addEventListener('keydown', handleGlobalKeydown);

    // Add keyboard help button to search input container (inside flex container)
    const searchInputContainer = document.querySelector('.search-input-container');
    if (searchInputContainer && !document.getElementById('keyboard-help-button')) {
        const helpBtn = document.createElement('button');
        helpBtn.id = 'keyboard-help-button';
        helpBtn.className = 'keyboard-help-button';
        helpBtn.setAttribute('aria-label', 'Show keyboard shortcuts');
        helpBtn.setAttribute('title', 'Keyboard shortcuts (?)');
        helpBtn.innerHTML = '<span class="i i-question"></span>';
        helpBtn.onclick = toggleKeyboardShortcutsModal;
        searchInputContainer.appendChild(helpBtn);
    }
});