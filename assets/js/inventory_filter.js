/* ============================================================
   ShStorage — inventory_filter.js
   Live client-side filtering for the inventory table.
   No page reload — filters by brand, gender, and stock status.
   ============================================================ */

document.addEventListener('DOMContentLoaded', function () {

  const filterBrand  = document.getElementById('filterBrand');
  const filterGender = document.getElementById('filterGender');
  const filterStock  = document.getElementById('filterStock');
  const filterSearch = document.getElementById('filterSearch');
  const tableBody    = document.getElementById('inventoryBody');

  if (!tableBody) return; // not on inventory page

  function getRows() {
    return Array.from(tableBody.querySelectorAll('tr[data-brand]'));
  }

  function applyFilters() {
    const brand  = filterBrand  ? filterBrand.value.toLowerCase()  : '';
    const gender = filterGender ? filterGender.value.toLowerCase() : '';
    const stock  = filterStock  ? filterStock.value.toLowerCase()  : '';
    const search = filterSearch ? filterSearch.value.toLowerCase() : '';

    let visibleCount = 0;

    getRows().forEach(function (row) {
      const rowBrand  = (row.dataset.brand  || '').toLowerCase();
      const rowGender = (row.dataset.gender || '').toLowerCase();
      const rowStock  = (row.dataset.stock  || '').toLowerCase(); // 'in', 'low', 'out'
      const rowText   = row.textContent.toLowerCase();

      const matchBrand  = !brand  || rowBrand  === brand;
      const matchGender = !gender || rowGender === gender;
      const matchStock  = !stock  || rowStock  === stock;
      const matchSearch = !search || rowText.includes(search);

      const visible = matchBrand && matchGender && matchStock && matchSearch;
      row.style.display = visible ? '' : 'none';
      if (visible) visibleCount++;
    });

    // Show/hide empty state row
    let emptyRow = tableBody.querySelector('.empty-filter-row');
    if (visibleCount === 0) {
      if (!emptyRow) {
        emptyRow = document.createElement('tr');
        emptyRow.className = 'empty-filter-row';
        const cols = tableBody.closest('table').querySelectorAll('thead th').length || 7;
        emptyRow.innerHTML = `<td colspan="${cols}" class="empty-row">No shoes match the selected filters.</td>`;
        tableBody.appendChild(emptyRow);
      }
      emptyRow.style.display = '';
    } else {
      if (emptyRow) emptyRow.style.display = 'none';
    }

    // Update visible count badge if present
    const countBadge = document.getElementById('inventoryCount');
    if (countBadge) countBadge.textContent = visibleCount;
  }

  // Attach listeners
  [filterBrand, filterGender, filterStock, filterSearch].forEach(function (el) {
    if (el) el.addEventListener('change', applyFilters);
    if (el && el.tagName === 'INPUT') el.addEventListener('input', applyFilters);
  });

  // Reset all filters
  const resetBtn = document.getElementById('resetFilters');
  if (resetBtn) {
    resetBtn.addEventListener('click', function () {
      if (filterBrand)  filterBrand.value  = '';
      if (filterGender) filterGender.value = '';
      if (filterStock)  filterStock.value  = '';
      if (filterSearch) filterSearch.value = '';
      applyFilters();
    });
  }

  // Run once on load to respect any pre-selected values
  applyFilters();
});