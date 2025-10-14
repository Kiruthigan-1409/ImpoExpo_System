document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.getElementById('deliveryTableBody');
    const qInput = document.getElementById('searchInput');
    const statusSel = document.getElementById('statusFilter');
    const driverSel = document.getElementById('driverFilter');
    const fromDateEl = document.getElementById('fromDate');
    const toDateEl = document.getElementById('toDate');

    const rowsPerSel = document.getElementById('rowsPerPage');
    const infoEl = document.getElementById('dtInfo');
    const pagerEl = document.getElementById('dtPagination');
    const statsGrid = document.getElementById('statsGrid');

    if (!tbody || !qInput) return;

    const getCell = (row, idx) => (row.cells[idx]?.textContent || '').trim();
    const toYMD = (str) => str ? new Date(str).toISOString().slice(0, 10) : '';
    const isDate = (s) => /^\d{4}-\d{2}-\d{2}$/.test(s) || !isNaN(Date.parse(s));
    const toNum = (s) => Number((s + '').replace(/[^\d.-]/g, ''));

    const debounce = (fn, delay = 150) => { let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); }; };

    const matchSearch = (row) => {
        const q = qInput.value.trim().toLowerCase();
        if (!q) return true;
        const terms = q.split(/\s+/);
        const haystack = [0, 1, 2, 6, 7].map(i => getCell(row, i).toLowerCase()).join(' ');
        return terms.every(t => haystack.includes(t));
    };

    const matchStatus = (row) => {
        const v = statusSel?.value || '';
        if (!v) return true;
        const cell = getCell(row, 8);
        return cell.toLowerCase().includes(v.toLowerCase());
    };

    const matchDriver = (row) => {
        const v = driverSel?.value || '';
        if (!v) return true;
        const cell = getCell(row, 6);
        return cell.toLowerCase() === v.toLowerCase();
    };

    const matchDateRange = (row) => {
        const cellDate = toYMD(getCell(row, 4));
        if (!cellDate) return true;
        const from = toYMD(fromDateEl?.value || '');
        const to = toYMD(toDateEl?.value || '');
        if (from && cellDate < from) return false;
        if (to && cellDate > to) return false;
        return true;
    };

    const applyFilters = () => {
        Array.from(tbody.rows).forEach(row => {
            const visible = matchSearch(row) && matchStatus(row) && matchDriver(row) && matchDateRange(row);
            row.style.display = visible ? '' : 'none';
        });
        dt.render();
        refreshStats();
    };
    
    const dt = {
        page: 1,
        pageSize: parseInt(rowsPerSel.value, 10) || 25,
        sortCol: null,
        sortDir: 'asc',
        headers: Array.from(document.querySelectorAll('.delivery-table thead th')),

        get visibleRows() { return Array.from(tbody.rows).filter(r => r.style.display !== 'none'); },

        sortBy(idx) {
            if (idx === 9) return;
            if (this.sortCol === idx) {
                this.sortDir = (this.sortDir === 'asc') ? 'desc' : 'asc';
            } else {
                this.sortCol = idx; this.sortDir = 'asc';
            }
            const rows = Array.from(tbody.rows);
            const dir = this.sortDir === 'asc' ? 1 : -1;

            rows.sort((a, b) => {
                const av = getCell(a, idx), bv = getCell(b, idx);
                const anum = toNum(av), bnum = toNum(bv);
                if (!isNaN(anum) && !isNaN(bnum) && (av !== '' && bv !== '')) return (anum - bnum) * dir;
                if (isDate(av) && isDate(bv)) return (new Date(av) - new Date(bv)) * dir;
                return av.localeCompare(bv, undefined, { sensitivity: 'base' }) * dir;
            });

            rows.forEach(r => tbody.appendChild(r));
            this.page = 1;
            this.updateSortUI();
            applyFilters();
        },

        updateSortUI() {
            this.headers.forEach((th, i) => {
                th.classList.remove('sort-asc', 'sort-desc', 'sortable');
                if (i !== 9) th.classList.add('sortable');
                if (i === this.sortCol) th.classList.add(this.sortDir === 'asc' ? 'sort-asc' : 'sort-desc');
            });
        },

        render() {
            const rows = this.visibleRows;
            const total = rows.length;
            const pages = Math.max(1, Math.ceil(total / this.pageSize));
            if (this.page > pages) this.page = pages;

            Array.from(tbody.rows).forEach(r => r.classList.add('dt-hidden'));
            const start = (this.page - 1) * this.pageSize;
            const end = start + this.pageSize;
            rows.forEach((r, i) => {
                if (i >= start && i < end) r.classList.remove('dt-hidden');
            });

            const from = total ? start + 1 : 0;
            const to = Math.min(end, total);
            infoEl.textContent = `Showing ${from}–${to} of ${total}`;

            pagerEl.innerHTML = '';
            const addBtn = (label, page, disabled = false, active = false) => {
                const btn = document.createElement('button');
                btn.className = 'dt-page-btn' + (active ? ' active' : '');
                btn.textContent = label;
                btn.disabled = disabled;
                btn.addEventListener('click', () => { this.page = page; this.render(); });
                pagerEl.appendChild(btn);
            };
            addBtn('«', 1, this.page === 1);
            addBtn('‹', Math.max(1, this.page - 1), this.page === 1);

            const windowSize = 5;
            const startPage = Math.max(1, this.page - Math.floor(windowSize / 2));
            const endPage = Math.min(pages, startPage + windowSize - 1);
            for (let p = startPage; p <= endPage; p++) addBtn(String(p), p, false, p === this.page);

            addBtn('›', Math.min(pages, this.page + 1), this.page === pages);
            addBtn('»', pages, this.page === pages);
        }
    };

    dt.headers.forEach((th, idx) => {
        if (idx === 9) return;
        th.classList.add('sortable');
        th.addEventListener('click', () => dt.sortBy(idx));
    });

    rowsPerSel.addEventListener('change', () => {
        dt.pageSize = parseInt(rowsPerSel.value, 10) || 25;
        dt.page = 1;
        dt.render();
    });

    const observer = new MutationObserver(() => {
        dt.updateSortUI();
        applyFilters();
    });
    observer.observe(tbody, { childList: true, subtree: false });

    function normalizeStatus(s) { return s.toLowerCase().replace(/[\s_-]/g, ''); }
    function statCard(label, value, icon) {
        return `
          <div class="stat-card">
            <div class="stat-row top">
              <div class="icon"><i class="fa-solid ${icon}"></i></div>
            </div>
            <div class="value">${value}</div>
            <div class="label">${label}</div>
          </div>
        `;
    }
    function refreshStats() {
        const visible = dt.visibleRows;
        const total = visible.length;
        const counts = { pending: 0, intransit: 0, delivered: 0, failed: 0, overdue: 0, returned: 0 };
        visible.forEach(row => {
            const s = normalizeStatus(getCell(row, 8));
            if (counts[s] !== undefined) counts[s]++;
        });
        statsGrid.innerHTML = `
          ${statCard('Total Deliveries', total, 'fa-boxes-stacked')}
          ${statCard('Pending', counts.pending, 'fa-hourglass-half')}
          ${statCard('In Transit', counts.intransit, 'fa-truck')}
          ${statCard('Delivered', counts.delivered, 'fa-circle-check')}
          ${statCard('Failed', counts.failed, 'fa-circle-xmark')}
          ${statCard('Overdue', counts.overdue, 'fa-clock')}
        `;
    }

    const onChange = debounce(() => { applyFilters(); }, 120);
    qInput.addEventListener('input', onChange);
    statusSel?.addEventListener('change', onChange);
    driverSel?.addEventListener('change', onChange);
    fromDateEl?.addEventListener('change', onChange);
    toDateEl?.addEventListener('change', onChange);

    applyFilters();
    dt.render();
    refreshStats();
});