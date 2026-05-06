</main>
<script>
document.addEventListener('click', function (event) {
    const link = event.target.closest('[data-confirm]');
    if (link && !confirm(link.dataset.confirm)) {
        event.preventDefault();
    }
});
document.querySelectorAll('[data-sortable]').forEach(function (table) {
    table.querySelectorAll('.sortable-th').forEach(function (th) {
        th.addEventListener('click', function () {
            const col = Number(th.dataset.col);
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            const asc = th.dataset.asc !== 'true';
            rows.sort(function (a, b) {
                const av = a.children[col]?.textContent.trim() || '';
                const bv = b.children[col]?.textContent.trim() || '';
                return asc ? av.localeCompare(bv, undefined, {numeric: true}) : bv.localeCompare(av, undefined, {numeric: true});
            });
            th.dataset.asc = String(asc);
            rows.forEach(row => table.querySelector('tbody').appendChild(row));
        });
    });
});
const clearBtn = document.getElementById('sqlClearBtn');
if (clearBtn) clearBtn.addEventListener('click', () => { document.getElementById('sql').value = ''; });
const filter = document.getElementById('tableFilter');
if (filter) filter.addEventListener('input', () => {
    const needle = filter.value.toLowerCase();
    document.querySelectorAll('#tablesListTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(needle) ? '' : 'none';
    });
});
</script>
</body>
</html>
