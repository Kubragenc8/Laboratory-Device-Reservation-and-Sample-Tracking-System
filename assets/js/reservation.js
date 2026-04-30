'use strict';

/**
 * Reservation page AJAX integrations.
 *
 * Çalıştığı sayfa:
 * - public/reserve.php
 *
 * Backend/database tarafına dokunmaz.
 * Mevcut API endpoint'leri ile çalışır:
 * - get-stations.php
 * - get-station-equipment.php
 * - check-availability.php
 * - create-reservation.php
 */

document.addEventListener('DOMContentLoaded', () => {
  const reservationPage = document.querySelector('[data-reservation-page="reserve"]');

  if (!reservationPage || !window.LabAjax) {
    return;
  }

  const selectionForm = document.getElementById('reservationSelectionForm');
  const labSelect = document.getElementById('lab_id');
  const stationSelect = document.getElementById('station_id');
  const stationSelectFeedback = document.getElementById('stationSelectFeedback');

  const selectedStationCard = document.getElementById('selectedStationCard');
  const stationEquipmentPanel = document.getElementById('stationEquipmentPanel');
  const stationEquipmentList = document.getElementById('stationEquipmentList');

  const reservationForm = document.getElementById('reservationForm');
  const reservationLabInput = document.getElementById('reservation_lab_id');
  const reservationStationInput = document.getElementById('reservation_station_id');
  const startTimeInput = document.getElementById('start_time');
  const endTimeInput = document.getElementById('end_time');
  const purposeInput = document.getElementById('purpose');

  const checkAvailabilityButton = document.getElementById('checkAvailabilityButton');
  const createReservationButton = document.getElementById('createReservationButton');
  const availabilityMessage = document.getElementById('availabilityMessage');

  let lastAvailabilityState = null;
  let isSubmittingReservation = false;

  function isPositiveInteger(value) {
    return /^[1-9]\d*$/.test(String(value || ''));
  }

  function valueOf(field) {
    return field ? field.value.trim() : '';
  }

  function setButtonLoading(button, loadingText) {
    if (!button) {
      return;
    }

    if (!button.dataset.originalText) {
      button.dataset.originalText = button.textContent.trim();
    }

    button.disabled = true;
    button.textContent = loadingText;
  }

  function resetButton(button) {
    if (!button) {
      return;
    }

    button.disabled = false;

    if (button.dataset.originalText) {
      button.textContent = button.dataset.originalText;
    }
  }

  function setStationFeedback(type, message) {
    if (!stationSelectFeedback) {
      return;
    }

    stationSelectFeedback.textContent = message;
    stationSelectFeedback.className = `field-feedback field-feedback-${type || 'info'}`;
  }

  function showAvailabilityMessage(type, message, extraHtml = '') {
    if (!availabilityMessage) {
      return;
    }

    const alertClass = type === 'success' ? 'alert-success' : 'alert-error';

    availabilityMessage.style.display = 'block';
    availabilityMessage.className = `alert ${alertClass} reservation-availability-message`;
    availabilityMessage.innerHTML = `
      <div>${window.LabAjax.escapeHtml(message)}</div>
      ${extraHtml}
    `;
  }

  function clearAvailabilityMessage() {
    if (!availabilityMessage) {
      return;
    }

    availabilityMessage.style.display = 'none';
    availabilityMessage.className = 'reservation-availability-message';
    availabilityMessage.innerHTML = '';
  }

  function resetAvailabilityState() {
    lastAvailabilityState = null;
    clearAvailabilityMessage();
  }

  function validateReservationFields() {
    let valid = true;

    const stationId = valueOf(reservationStationInput);
    const startTime = valueOf(startTimeInput);
    const endTime = valueOf(endTimeInput);

    if (!isPositiveInteger(stationId)) {
      valid = false;
      showAvailabilityMessage('error', 'Please select a valid station first.');
    }

    if (!startTimeInput || startTime === '') {
      valid = false;
      window.LabAjax.setFieldState(startTimeInput, 'error', 'Start time is required.');
    } else {
      window.LabAjax.clearFieldState(startTimeInput);
    }

    if (!endTimeInput || endTime === '') {
      valid = false;
      window.LabAjax.setFieldState(endTimeInput, 'error', 'End time is required.');
    } else {
      window.LabAjax.clearFieldState(endTimeInput);
    }

    if (startTime !== '' && endTime !== '') {
      const startDate = new Date(startTime);
      const endDate = new Date(endTime);
      const now = new Date();

      if (Number.isNaN(startDate.getTime()) || Number.isNaN(endDate.getTime())) {
        valid = false;
        showAvailabilityMessage('error', 'Please enter valid start and end times.');
      } else if (endDate <= startDate) {
        valid = false;
        window.LabAjax.setFieldState(endTimeInput, 'error', 'End time must be later than start time.');
        showAvailabilityMessage('error', 'End time must be later than start time.');
      } else if (startDate <= now) {
        valid = false;
        window.LabAjax.setFieldState(startTimeInput, 'error', 'Reservation start time must be in the future.');
        showAvailabilityMessage('error', 'Reservation start time must be in the future.');
      }
    }

    if (purposeInput && valueOf(purposeInput).length > 255) {
      valid = false;
      window.LabAjax.setFieldState(purposeInput, 'error', 'Purpose can be maximum 255 characters.');
    } else if (purposeInput) {
      window.LabAjax.clearFieldState(purposeInput);
    }

    return valid;
  }

  function buildConflictHtml(conflicts) {
    if (!Array.isArray(conflicts) || conflicts.length === 0) {
      return '';
    }

    const rows = conflicts.map((conflict) => {
      const user = conflict.user_full_name || '-';
      const start = conflict.start_time || '-';
      const end = conflict.end_time || '-';
      const status = conflict.status || '-';

      return `
        <tr>
          <td>${window.LabAjax.escapeHtml(user)}</td>
          <td>${window.LabAjax.escapeHtml(start)}</td>
          <td>${window.LabAjax.escapeHtml(end)}</td>
          <td>${window.LabAjax.escapeHtml(status)}</td>
        </tr>
      `;
    }).join('');

    return `
      <div class="table-wrapper" style="margin-top:16px;">
        <table class="table">
          <thead>
            <tr>
              <th>User</th>
              <th>Start</th>
              <th>End</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            ${rows}
          </tbody>
        </table>
      </div>
    `;
  }

  async function loadStationsByLab() {
    if (!labSelect || !stationSelect) {
      return;
    }

    const labId = labSelect.value;

    resetAvailabilityState();

    stationSelect.innerHTML = '<option value="">Select station</option>';
    stationSelect.disabled = true;

    if (!isPositiveInteger(labId)) {
      stationSelect.innerHTML = '<option value="">Select laboratory first</option>';
      setStationFeedback('info', 'Select a laboratory to list available stations.');
      return;
    }

    setStationFeedback('info', 'Loading stations...');

    try {
      const response = await window.LabAjax.get('get-stations.php', {
        lab_id: labId
      });

      const stations = response.data && Array.isArray(response.data.stations)
        ? response.data.stations
        : [];

      stationSelect.innerHTML = '<option value="">Select station</option>';

      stations.forEach((station) => {
        const option = document.createElement('option');

        option.value = station.station_id;
        option.dataset.status = station.status || '';
        option.dataset.code = station.station_code || '';
        option.dataset.name = station.station_name || '';

        option.textContent = `${station.station_code || ''} - ${station.station_name || ''} (${station.status || '-'})`;

        if (station.status !== 'active') {
          option.disabled = true;
        }

        stationSelect.appendChild(option);
      });

      stationSelect.disabled = false;

      if (stations.length === 0) {
        setStationFeedback('info', 'No station found for this laboratory.');
      } else {
        setStationFeedback('success', 'Stations loaded. Select an active station.');
      }
    } catch (error) {
      stationSelect.innerHTML = '<option value="">Stations could not be loaded</option>';
      stationSelect.disabled = false;
      setStationFeedback('error', error.message || 'Stations could not be loaded.');
    }
  }

  function renderEquipmentList(equipment) {
    if (!stationEquipmentList) {
      return;
    }

    if (!Array.isArray(equipment) || equipment.length === 0) {
      stationEquipmentList.innerHTML = `
        <p style="color:var(--color-muted); margin-bottom:0;">
          No equipment is assigned to this station.
        </p>
      `;
      return;
    }

    const items = equipment.map((item) => {
      const name = item.equipment_name || item.type_name || item.name || 'Equipment';
      const assetCode = item.asset_code || '-';
      const brand = item.brand || '-';
      const model = item.model || '-';
      const status = item.status || '-';

      return `
        <div class="reservation-equipment-item">
          <div>
            <strong>${window.LabAjax.escapeHtml(name)}</strong>
            <p style="margin:6px 0 0; color:var(--color-muted);">
              Asset: ${window.LabAjax.escapeHtml(assetCode)}
              · Brand: ${window.LabAjax.escapeHtml(brand)}
              · Model: ${window.LabAjax.escapeHtml(model)}
            </p>
          </div>

          <span class="badge ${status === 'available' ? 'badge-success' : 'badge-warning'}">
            ${window.LabAjax.escapeHtml(status)}
          </span>
        </div>
      `;
    }).join('');

    stationEquipmentList.innerHTML = items;
  }

  async function loadStationEquipment(stationId) {
    if (!stationEquipmentList || !isPositiveInteger(stationId)) {
      return;
    }

    stationEquipmentList.innerHTML = `
      <p style="color:var(--color-muted); margin-bottom:0;">
        Loading station equipment...
      </p>
    `;

    try {
      const response = await window.LabAjax.get('get-station-equipment.php', {
        station_id: stationId
      });

      const equipment = response.data && Array.isArray(response.data.equipment)
        ? response.data.equipment
        : [];

      renderEquipmentList(equipment);
    } catch (error) {
      stationEquipmentList.innerHTML = `
        <p style="color:var(--color-error); margin-bottom:0;">
          ${window.LabAjax.escapeHtml(error.message || 'Station equipment could not be loaded.')}
        </p>
      `;
    }
  }

  async function checkAvailability() {
    if (!reservationForm || !validateReservationFields()) {
      return false;
    }

    const payload = {
      station_id: valueOf(reservationStationInput),
      start_time: valueOf(startTimeInput),
      end_time: valueOf(endTimeInput)
    };

    setButtonLoading(checkAvailabilityButton, 'Checking...');
    resetButton(createReservationButton);

    try {
      const response = await window.LabAjax.post('check-availability.php', payload);
      const data = response.data || {};
      const available = Boolean(data.available);

      lastAvailabilityState = {
        checked: true,
        available,
        station_id: payload.station_id,
        start_time: payload.start_time,
        end_time: payload.end_time
      };

      if (available) {
        showAvailabilityMessage('success', 'This station is available for the selected time interval.');
        window.LabAjax.showToast('Station is available.', 'success');
      } else {
        showAvailabilityMessage(
          'error',
          'This station is not available for the selected time interval.',
          buildConflictHtml(data.conflicts || [])
        );
        window.LabAjax.showToast('Selected time interval is not available.', 'error');
      }

      return available;
    } catch (error) {
      lastAvailabilityState = null;

      const conflicts = error.payload && error.payload.data
        ? error.payload.data.conflicts
        : [];

      showAvailabilityMessage(
        'error',
        error.message || 'Availability check failed.',
        buildConflictHtml(conflicts || [])
      );

      window.LabAjax.showToast(error.message || 'Availability check failed.', 'error');
      return false;
    } finally {
      resetButton(checkAvailabilityButton);
    }
  }

  function isCurrentAvailabilityStillValid() {
    if (!lastAvailabilityState || lastAvailabilityState.available !== true) {
      return false;
    }

    return (
      lastAvailabilityState.station_id === valueOf(reservationStationInput)
      && lastAvailabilityState.start_time === valueOf(startTimeInput)
      && lastAvailabilityState.end_time === valueOf(endTimeInput)
    );
  }

  async function createReservation() {
    if (!reservationForm || !validateReservationFields()) {
      return;
    }

    let available = isCurrentAvailabilityStillValid();

    if (!available) {
      available = await checkAvailability();
    }

    if (!available) {
      return;
    }

    const payload = {
      station_id: valueOf(reservationStationInput),
      start_time: valueOf(startTimeInput),
      end_time: valueOf(endTimeInput),
      purpose: purposeInput ? valueOf(purposeInput) : ''
    };

    setButtonLoading(createReservationButton, 'Creating...');
    setButtonLoading(checkAvailabilityButton, 'Please wait...');

    try {
      const response = await window.LabAjax.post('create-reservation.php', payload);
      const data = response.data || {};
      const reservationId = data.reservation_id || (data.reservation && data.reservation.reservation_id);

      showAvailabilityMessage(
        'success',
        reservationId
          ? `Reservation created successfully. Reservation ID: ${reservationId}`
          : 'Reservation created successfully.',
        `
          <div style="margin-top:16px;">
            <a href="my-reservations.php" class="btn btn-primary">
              Go to My Reservations
            </a>
          </div>
        `
      );

      window.LabAjax.showToast('Reservation created successfully.', 'success');

      reservationForm.reset();

      lastAvailabilityState = null;

      if (reservationId) {
        window.setTimeout(() => {
          window.location.href = 'my-reservations.php';
        }, 1200);
      }
    } catch (error) {
      const conflicts = error.payload && error.payload.data
        ? error.payload.data.conflicts
        : [];

      showAvailabilityMessage(
        'error',
        error.message || 'Reservation could not be created.',
        buildConflictHtml(conflicts || [])
      );

      window.LabAjax.showToast(error.message || 'Reservation could not be created.', 'error');
    } finally {
      resetButton(createReservationButton);
      resetButton(checkAvailabilityButton);
    }
  }

  if (labSelect && stationSelect) {
    labSelect.addEventListener('change', loadStationsByLab);
  }

  if (stationSelect) {
    stationSelect.addEventListener('change', () => {
      resetAvailabilityState();

      const stationId = stationSelect.value;

      if (reservationStationInput && isPositiveInteger(stationId)) {
        reservationStationInput.value = stationId;
      }

      if (stationEquipmentPanel) {
        stationEquipmentPanel.dataset.stationId = stationId;
      }

      if (isPositiveInteger(stationId)) {
        loadStationEquipment(stationId);
      }
    });
  }

  if (selectionForm) {
    selectionForm.addEventListener('submit', (event) => {
      if (!labSelect || !stationSelect) {
        return;
      }

      if (!isPositiveInteger(labSelect.value)) {
        event.preventDefault();
        setStationFeedback('error', 'Please select a laboratory.');
        window.LabAjax.showToast('Please select a laboratory.', 'error');
        return;
      }

      if (!isPositiveInteger(stationSelect.value)) {
        event.preventDefault();
        setStationFeedback('error', 'Please select an active station.');
        window.LabAjax.showToast('Please select an active station.', 'error');
      }
    });
  }

  if (startTimeInput) {
    startTimeInput.addEventListener('change', resetAvailabilityState);
    startTimeInput.addEventListener('input', resetAvailabilityState);
  }

  if (endTimeInput) {
    endTimeInput.addEventListener('change', resetAvailabilityState);
    endTimeInput.addEventListener('input', resetAvailabilityState);
  }

  if (purposeInput) {
    purposeInput.addEventListener('input', () => {
      if (valueOf(purposeInput).length > 255) {
        window.LabAjax.setFieldState(purposeInput, 'error', 'Purpose can be maximum 255 characters.');
      } else {
        window.LabAjax.clearFieldState(purposeInput);
      }
    });
  }

  if (checkAvailabilityButton) {
    checkAvailabilityButton.addEventListener('click', async (event) => {
      event.preventDefault();
      await checkAvailability();
    });
  }

  if (createReservationButton) {
    createReservationButton.addEventListener('click', async (event) => {
      event.preventDefault();

      if (isSubmittingReservation) {
        return;
      }

      isSubmittingReservation = true;

      try {
        await createReservation();
      } finally {
        isSubmittingReservation = false;
      }
    });
  }

  if (reservationForm) {
    reservationForm.addEventListener('submit', (event) => {
      event.preventDefault();
    });
  }

  const initialStationId = stationEquipmentPanel
    ? stationEquipmentPanel.dataset.stationId
    : '';

  if (isPositiveInteger(initialStationId)) {
    loadStationEquipment(initialStationId);
  }
});