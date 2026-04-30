'use strict';

/**
 * Laboratories page dynamic filtering.
 *
 * Çalıştığı sayfa:
 * - public/labs.php
 *
 * Backend/database bağlantılarına dokunmaz.
 * Sayfadaki mevcut lab kartlarını client-side filtreler.
 */

document.addEventListener('DOMContentLoaded', () => {
  const labsPage = document.querySelector('[data-labs-page="true"]');

  if (!labsPage) {
    return;
  }

  const filterForm = document.getElementById('labsFilterForm');
  const searchInput = document.getElementById('q');
  const facultySelect = document.getElementById('faculty_id');
  const departmentSelect = document.getElementById('department_id');
  const labTypeSelect = document.getElementById('lab_type');
  const clientFilterButton = document.getElementById('labsClientFilterButton');

  const labsGrid = document.getElementById('labsGrid');
  const emptyState = document.getElementById('labsEmptyState');
  const visibleLabsCount = document.getElementById('visibleLabsCount');
  const filterModeBadge = document.getElementById('labsFilterModeBadge');

  const labCards = Array.from(document.querySelectorAll('[data-lab-card="true"]'));
  const allDepartmentOptions = departmentSelect
    ? Array.from(departmentSelect.querySelectorAll('option'))
    : [];

  function normalize(value) {
    return String(value || '')
      .toLowerCase()
      .trim();
  }

  function getSelectedValue(select) {
    return select ? String(select.value || '').trim() : '';
  }

  function updateVisibleCount(count) {
    if (visibleLabsCount) {
      visibleLabsCount.textContent = count;
    }
  }

  function showEmptyState(shouldShow) {
    if (!emptyState) {
      return;
    }

    emptyState.style.display = shouldShow ? 'block' : 'none';
  }

  function setFilterBadge(text, type = 'info') {
    if (!filterModeBadge) {
      return;
    }

    filterModeBadge.textContent = text;
    filterModeBadge.className = `badge badge-${type}`;
  }

  function filterDepartmentsByFaculty() {
    if (!facultySelect || !departmentSelect) {
      return;
    }

    const selectedFacultyId = getSelectedValue(facultySelect);
    const currentDepartmentId = getSelectedValue(departmentSelect);
    let currentDepartmentStillVisible = false;

    departmentSelect.innerHTML = '';

    allDepartmentOptions.forEach((option) => {
      const optionFacultyId = option.dataset.facultyId || '';
      const isDefaultOption = option.value === '';

      const shouldShow =
        isDefaultOption ||
        selectedFacultyId === '' ||
        optionFacultyId === selectedFacultyId;

      if (shouldShow) {
        const clonedOption = option.cloneNode(true);

        if (clonedOption.value === currentDepartmentId) {
          clonedOption.selected = true;
          currentDepartmentStillVisible = true;
        }

        departmentSelect.appendChild(clonedOption);
      }
    });

    if (!currentDepartmentStillVisible) {
      departmentSelect.value = '';
    }
  }

  function cardMatchesFilters(card) {
    const query = normalize(searchInput ? searchInput.value : '');
    const selectedFacultyId = getSelectedValue(facultySelect);
    const selectedDepartmentId = getSelectedValue(departmentSelect);
    const selectedLabType = getSelectedValue(labTypeSelect);

    const searchText = normalize(card.dataset.search);
    const cardFacultyId = String(card.dataset.facultyId || '');
    const cardDepartmentId = String(card.dataset.departmentId || '');
    const cardLabType = String(card.dataset.labType || '');

    const matchesSearch = query === '' || searchText.includes(query);
    const matchesFaculty = selectedFacultyId === '' || cardFacultyId === selectedFacultyId;
    const matchesDepartment = selectedDepartmentId === '' || cardDepartmentId === selectedDepartmentId;
    const matchesLabType = selectedLabType === '' || cardLabType === selectedLabType;

    return matchesSearch && matchesFaculty && matchesDepartment && matchesLabType;
  }

  function applyClientFilters() {
    let visibleCount = 0;

    labCards.forEach((card) => {
      const isVisible = cardMatchesFilters(card);

      card.style.display = isVisible ? '' : 'none';

      if (isVisible) {
        visibleCount += 1;
      }
    });

    updateVisibleCount(visibleCount);
    showEmptyState(visibleCount === 0);

    if (visibleCount === labCards.length) {
      setFilterBadge('All Laboratories', 'info');
    } else if (visibleCount === 0) {
      setFilterBadge('No Result', 'warning');
    } else {
      setFilterBadge(`${visibleCount} Result${visibleCount > 1 ? 's' : ''}`, 'success');
    }
  }

  function clearClientOnlyVisualState() {
    labCards.forEach((card) => {
      card.style.display = '';
    });

    updateVisibleCount(labCards.length);
    showEmptyState(labCards.length === 0);

    if (labCards.length === 0) {
      setFilterBadge('No Result', 'warning');
    } else {
      setFilterBadge('Dynamic Filter Ready', 'info');
    }
  }

  function debounce(callback, delay = 250) {
    let timerId;

    return function (...args) {
      window.clearTimeout(timerId);

      timerId = window.setTimeout(() => {
        callback.apply(this, args);
      }, delay);
    };
  }

  const debouncedFilter = debounce(applyClientFilters, 250);

  if (facultySelect) {
    facultySelect.addEventListener('change', () => {
      filterDepartmentsByFaculty();
      applyClientFilters();
    });
  }

  if (departmentSelect) {
    departmentSelect.addEventListener('change', applyClientFilters);
  }

  if (labTypeSelect) {
    labTypeSelect.addEventListener('change', applyClientFilters);
  }

  if (searchInput) {
    searchInput.addEventListener('input', debouncedFilter);
  }

  if (clientFilterButton) {
    clientFilterButton.addEventListener('click', (event) => {
      event.preventDefault();
      applyClientFilters();
    });
  }

  if (filterForm) {
    filterForm.addEventListener('reset', () => {
      window.setTimeout(() => {
        filterDepartmentsByFaculty();
        clearClientOnlyVisualState();
      }, 0);
    });
  }

  filterDepartmentsByFaculty();
  applyClientFilters();
});