// Bethel Hospital Asset Tracker - Global UI Enhancements

document.addEventListener('DOMContentLoaded', () => {
    initSidebarToggle();
    initGlobalSearch();
});

/**
 * Collapsible Sidebar (248px expanded <-> 72px collapsed)
 * Remembers state in localStorage across page reloads.
 */
function initSidebarToggle() {
    const sidebar = document.getElementById('app-sidebar');
    const toggleBtn = document.getElementById('sidebar-toggle-btn');
    const collapseText = document.querySelectorAll('.sidebar-expanded-text');
    const compactIcons = document.querySelectorAll('.sidebar-compact-icon');
    
    if (!sidebar || !toggleBtn) return;

    function applyState(collapsed) {
        if (collapsed) {
            sidebar.classList.remove('w-[248px]');
            sidebar.classList.add('w-[72px]');
            sidebar.setAttribute('data-collapsed', 'true');
            collapseText.forEach(el => el.classList.add('hidden'));
            compactIcons.forEach(el => el.classList.remove('hidden'));
            toggleBtn.setAttribute('title', 'Expand sidebar');
        } else {
            sidebar.classList.remove('w-[72px]');
            sidebar.classList.add('w-[248px]');
            sidebar.setAttribute('data-collapsed', 'false');
            collapseText.forEach(el => el.classList.remove('hidden'));
            compactIcons.forEach(el => el.classList.add('hidden'));
            toggleBtn.setAttribute('title', 'Collapse sidebar');
        }
    }

    // Read stored preference
    const isStoredCollapsed = localStorage.getItem('bethel_sidebar_collapsed') === 'true';
    applyState(isStoredCollapsed);

    toggleBtn.addEventListener('click', () => {
        const currentlyCollapsed = sidebar.getAttribute('data-collapsed') === 'true';
        const nextState = !currentlyCollapsed;
        localStorage.setItem('bethel_sidebar_collapsed', String(nextState));
        applyState(nextState);
    });
}

/**
 * Global Asset Search with Ctrl+K / / shortcut and instant live dropdown
 */
function initGlobalSearch() {
    const searchInput = document.getElementById('global-search-input');
    const dropdown = document.getElementById('global-search-dropdown');
    const resultsContainer = document.getElementById('global-search-results');
    const loadingIndicator = document.getElementById('global-search-loading');
    const emptyNotice = document.getElementById('global-search-empty');

    if (!searchInput || !dropdown) return;

    let debounceTimer = null;
    let selectedIndex = -1;
    let currentResults = [];

    // Keyboard shortcut to focus search: Ctrl+K or '/'
    document.addEventListener('keydown', (e) => {
        const tag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
        const isEditing = tag === 'input' || tag === 'textarea' || tag === 'select';

        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        } else if (e.key === '/' && !isEditing) {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
    });

    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            dropdown.classList.add('hidden');
            searchInput.blur();
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            navigateResults(1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            navigateResults(-1);
        } else if (e.key === 'Enter') {
            if (selectedIndex >= 0 && currentResults[selectedIndex]) {
                e.preventDefault();
                window.location.href = `/assets/${currentResults[selectedIndex].id}`;
            }
        }
    });

    searchInput.addEventListener('input', () => {
        const query = searchInput.value.trim();
        clearTimeout(debounceTimer);

        if (query.length < 1) {
            dropdown.classList.add('hidden');
            resultsContainer.innerHTML = '';
            currentResults = [];
            selectedIndex = -1;
            return;
        }

        loadingIndicator?.classList.remove('hidden');
        emptyNotice?.classList.add('hidden');
        dropdown.classList.remove('hidden');

        debounceTimer = setTimeout(async () => {
            try {
                const res = await fetch(`/api/assets/quick-search?q=${encodeURIComponent(query)}`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error('Search failed');
                const data = await res.json();
                renderResults(data, query);
            } catch (err) {
                console.error(err);
            } finally {
                loadingIndicator?.classList.add('hidden');
            }
        }, 150);
    });

    searchInput.addEventListener('focus', () => {
        if (searchInput.value.trim().length > 0 && currentResults.length > 0) {
            dropdown.classList.remove('hidden');
        }
    });

    // Close when clicking outside
    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });

    function renderResults(results, query) {
        currentResults = results;
        selectedIndex = -1;
        resultsContainer.innerHTML = '';

        if (results.length === 0) {
            emptyNotice?.classList.remove('hidden');
            return;
        }

        emptyNotice?.classList.add('hidden');

        results.forEach((asset, idx) => {
            const item = document.createElement('a');
            item.href = `/assets/${asset.id}`;
            item.className = 'flex items-center justify-between px-3.5 py-2.5 hover:bg-slate-50 transition-colors border-b border-slate-100 last:border-b-0 cursor-pointer search-result-item';
            item.setAttribute('data-index', idx);

            const badgeBg = asset.status === 'In Use' ? 'bg-emerald-50 text-emerald-800 border-emerald-200' :
                            asset.status === 'Under Repair' ? 'bg-amber-50 text-amber-800 border-amber-200' :
                            asset.status === 'Stored' ? 'bg-slate-100 text-slate-700 border-slate-200' :
                            'bg-neutral-100 text-neutral-600 border-neutral-200';

            item.innerHTML = `
                <div class="min-w-0 flex-1 pr-3">
                    <div class="flex items-center gap-2">
                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-800 border border-slate-200 font-mono text-xs font-semibold">${escapeHtml(asset.asset_no)}</span>
                        <span class="text-xs font-semibold text-slate-900 truncate">${escapeHtml(asset.asset_name)}</span>
                    </div>
                    <div class="text-[11px] text-slate-500 mt-0.5 truncate">
                        ${asset.department ? escapeHtml(asset.department) : 'No dept'} &bull; ${asset.location ? escapeHtml(asset.location) : 'No location'}
                    </div>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border ${badgeBg} shrink-0">
                    ${escapeHtml(asset.status)}
                </span>
            `;

            item.addEventListener('mouseenter', () => highlightResult(idx));
            resultsContainer.appendChild(item);
        });
    }

    function navigateResults(dir) {
        if (currentResults.length === 0) return;
        selectedIndex += dir;
        if (selectedIndex < 0) selectedIndex = currentResults.length - 1;
        if (selectedIndex >= currentResults.length) selectedIndex = 0;
        highlightResult(selectedIndex);
    }

    function highlightResult(idx) {
        selectedIndex = idx;
        const items = resultsContainer.querySelectorAll('.search-result-item');
        items.forEach((item, i) => {
            if (i === idx) {
                item.classList.add('bg-slate-100/90');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('bg-slate-100/90');
            }
        });
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}
