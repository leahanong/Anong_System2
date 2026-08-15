/**
 * Microservice System Interactive JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    initEmployeeFilters();
    initModalHandlers();
});

/**
 * 1-Click Autofill presets for quick staff registration
 * @param {string} department
 * @param {string} target 'inline', 'modal', or 'auto'
 */
function fillPreset(department, target = 'auto') {
    const isModalOpen = !document.getElementById('add-staff-modal')?.classList.contains('hidden');
    const isModal = target === 'modal' || (target === 'auto' && isModalOpen);
    const prefix = isModal ? 'modal_emp_' : 'emp_';

    const nameInput = document.getElementById(prefix + 'name');
    const deptSelect = document.getElementById(prefix + 'department');
    const roleInput = document.getElementById(prefix + 'role');
    const emailInput = document.getElementById(prefix + 'email');
    const phoneInput = document.getElementById(prefix + 'phone');
    const statusSelect = document.getElementById(prefix + 'status');

    const randomSuffix = Math.floor(100 + Math.random() * 900);

    const presets = {
        'Front Desk': {
            name: `Carlos Rivera ${randomSuffix}`,
            dept: 'Front Desk',
            role: 'Front Desk Specialist',
            email: `carlos.r${randomSuffix}@grandhotel.com`,
            phone: '+63 917 555 ' + randomSuffix
        },
        'Concierge': {
            name: `Isabella Cruz ${randomSuffix}`,
            dept: 'Concierge',
            role: 'Senior Concierge',
            email: `isabella.c${randomSuffix}@grandhotel.com`,
            phone: '+63 918 555 ' + randomSuffix
        },
        'Housekeeping': {
            name: `Mateo Gomez ${randomSuffix}`,
            dept: 'Housekeeping',
            role: 'Floor Attendant Lead',
            email: `mateo.g${randomSuffix}@grandhotel.com`,
            phone: '+63 919 555 ' + randomSuffix
        },
        'Guest Services': {
            name: `Amara Santos ${randomSuffix}`,
            dept: 'Guest Services',
            role: 'VIP Guest Host',
            email: `amara.s${randomSuffix}@grandhotel.com`,
            phone: '+63 920 555 ' + randomSuffix
        },
        'Food & Beverage': {
            name: `Lucas Tan ${randomSuffix}`,
            dept: 'Food & Beverage',
            role: 'Banquet Supervisor',
            email: `lucas.t${randomSuffix}@grandhotel.com`,
            phone: '+63 921 555 ' + randomSuffix
        },
        'Maintenance': {
            name: `Rodrigo Ramos ${randomSuffix}`,
            dept: 'Maintenance',
            role: 'Chief Engineer Tech',
            email: `rodrigo.r${randomSuffix}@grandhotel.com`,
            phone: '+63 922 555 ' + randomSuffix
        },
        'Security': {
            name: `Viktor Alcantara ${randomSuffix}`,
            dept: 'Security',
            role: 'Security Specialist',
            email: `viktor.a${randomSuffix}@grandhotel.com`,
            phone: '+63 923 555 ' + randomSuffix
        }
    };

    const data = presets[department] || presets['Front Desk'];

    if (nameInput) nameInput.value = data.name;
    if (deptSelect) deptSelect.value = data.dept;
    if (roleInput) roleInput.value = data.role;
    if (emailInput) emailInput.value = data.email;
    if (phoneInput) phoneInput.value = data.phone;
    if (statusSelect) statusSelect.value = 'Active';

    if (nameInput) nameInput.focus();
}

/**
 * Modal Handling: Open & Close Add Modal
 */
function openAddModal() {
    const modal = document.getElementById('add-staff-modal');
    if (!modal) return;
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    setTimeout(() => {
        const nameInput = document.getElementById('modal_emp_name');
        if (nameInput) nameInput.focus();
    }, 100);
}

function closeAddModal() {
    const modal = document.getElementById('add-staff-modal');
    if (!modal) return;
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

/**
 * Modal Handling: Open & Close Edit Modal
 */
function openEditModal(emp) {
    const modal = document.getElementById('edit-staff-modal');
    if (!modal) return;

    document.getElementById('edit_id').value = emp.id || '';
    document.getElementById('edit_code_display').textContent = emp.employee_code || '';
    document.getElementById('edit_name').value = emp.name || '';
    document.getElementById('edit_department').value = emp.department || 'Front Desk';
    document.getElementById('edit_role').value = emp.role || '';
    document.getElementById('edit_email').value = emp.email || '';
    document.getElementById('edit_phone').value = emp.phone || '';
    document.getElementById('edit_status').value = emp.status || 'Active';

    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    setTimeout(() => {
        const nameInput = document.getElementById('edit_name');
        if (nameInput) nameInput.focus();
    }, 100);
}

function closeEditModal() {
    const modal = document.getElementById('edit-staff-modal');
    if (!modal) return;
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

/**
 * Initialize ESC key and Backdrop Click modal closing
 */
function initModalHandlers() {
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeAddModal();
            closeEditModal();
        }
    });

    ['add-staff-modal', 'edit-staff-modal'].forEach(id => {
        const modal = document.getElementById(id);
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            });
        }
    });
}

/**
 * Real-time filter & search for Employee Directory table
 */
function initEmployeeFilters() {
    const searchInput = document.getElementById('employee-search');
    const deptFilter = document.getElementById('department-filter');
    const statusFilter = document.getElementById('status-filter-emp');
    const table = document.getElementById('employee-table');
    const countBadge = document.getElementById('filtered-count');
    const resetBtn = document.getElementById('reset-filters-btn');

    if (!table) return;
    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    const dataRows = Array.from(tbody.querySelectorAll('tr[data-department]'));
    const totalRowsCount = dataRows.length;

    // Create or find no-results placeholder row
    let noResultsRow = tbody.querySelector('.no-results-row');
    if (!noResultsRow) {
        noResultsRow = document.createElement('tr');
        noResultsRow.className = 'no-results-row hidden';
        noResultsRow.innerHTML = `
            <td colspan="7" class="text-center py-12 text-slate-400">
                <div class="flex flex-col items-center justify-center gap-2">
                    <span class="text-3xl">🔍</span>
                    <p class="font-medium text-slate-300">No personnel match your search criteria.</p>
                    <button type="button" onclick="resetFilters()" class="text-xs text-blue-400 hover:text-blue-300 underline font-semibold mt-1">
                        Clear all search and filters
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(noResultsRow);
    }

    function filterRows() {
        const query = (searchInput ? searchInput.value : '').toLowerCase().trim();
        const selectedDept = deptFilter ? deptFilter.value : 'all';
        const selectedStatus = statusFilter ? statusFilter.value : 'all';

        let visibleCount = 0;

        dataRows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            const rowDept = row.getAttribute('data-department') || '';
            const rowStatus = row.getAttribute('data-status') || '';

            const matchesQuery = !query || rowText.includes(query);
            const matchesDept = selectedDept === 'all' || rowDept === selectedDept;
            const matchesStatus = selectedStatus === 'all' || rowStatus === selectedStatus;

            const isVisible = matchesQuery && matchesDept && matchesStatus;
            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        // Toggle no-results row
        if (dataRows.length > 0) {
            if (visibleCount === 0) {
                noResultsRow.classList.remove('hidden');
            } else {
                noResultsRow.classList.add('hidden');
            }
        }

        // Update count badge
        if (countBadge) {
            if (visibleCount === totalRowsCount) {
                countBadge.textContent = totalRowsCount;
            } else {
                countBadge.textContent = `${visibleCount} / ${totalRowsCount}`;
            }
        }

        // Toggle reset button visibility if filters are active
        if (resetBtn) {
            const isFiltered = query !== '' || selectedDept !== 'all' || selectedStatus !== 'all';
            resetBtn.style.display = isFiltered ? 'inline-flex' : 'none';
        }
    }

    window.resetFilters = function() {
        if (searchInput) searchInput.value = '';
        if (deptFilter) deptFilter.value = 'all';
        if (statusFilter) statusFilter.value = 'all';
        filterRows();
    };

    if (searchInput) searchInput.addEventListener('input', filterRows);
    if (deptFilter) deptFilter.addEventListener('change', filterRows);
    if (statusFilter) statusFilter.addEventListener('change', filterRows);
    if (resetBtn) resetBtn.addEventListener('click', window.resetFilters);

    // Initial run
    filterRows();
}

/**
 * Interactive API endpoint test runner
 */
async function testEndpoint(endpoint) {
    const statusEl = document.getElementById('response-status');
    const timeEl = document.getElementById('response-time');
    const viewerEl = document.getElementById('json-viewer');

    if (!viewerEl) return;

    statusEl.textContent = `Fetching ${endpoint}...`;
    statusEl.style.color = '#93c5fd';
    const startTime = performance.now();

    try {
        const response = await fetch(endpoint);
        const duration = Math.round(performance.now() - startTime);
        const data = await response.json();

        statusEl.textContent = `Status: ${response.status} ${response.statusText || 'OK'}`;
        statusEl.style.color = response.ok ? '#6ee7b7' : '#f87171';
        timeEl.textContent = `• Latency: ${duration} ms`;

        viewerEl.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
        statusEl.textContent = 'Status: Fetch Failed';
        statusEl.style.color = '#f87171';
        viewerEl.textContent = `Error: ${err.message}\nMake sure Microservice container is running on Port 81.`;
    }
}

function copyConsoleOutput() {
    const viewerEl = document.getElementById('json-viewer');
    if (!viewerEl) return;
    navigator.clipboard.writeText(viewerEl.textContent).then(() => {
        const copyBtn = document.getElementById('copy-json-btn');
        if (copyBtn) {
            const originalText = copyBtn.innerHTML;
            copyBtn.innerHTML = '<span>✓ Copied!</span>';
            setTimeout(() => { copyBtn.innerHTML = originalText; }, 2000);
        } else {
            alert('JSON copied to clipboard!');
        }
    }).catch(() => {
        alert('Failed to copy to clipboard.');
    });
}

function clearConsole() {
    const statusEl = document.getElementById('response-status');
    const timeEl = document.getElementById('response-time');
    const viewerEl = document.getElementById('json-viewer');
    if (statusEl) statusEl.textContent = 'Status: Ready';
    if (timeEl) timeEl.textContent = '• Latency: --';
    if (viewerEl) viewerEl.textContent = '// Click any endpoint test button above to preview live JSON response...';
}
