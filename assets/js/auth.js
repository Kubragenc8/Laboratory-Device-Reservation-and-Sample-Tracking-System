'use strict';

/**
 * Login/Register validation + AJAX integrations.
 *
 * Çalıştığı sayfalar:
 * - public/login.php
 * - public/register.php
 *
 * Backend validation korunur.
 * Bu dosya sadece kullanıcı tarafında hızlı kontrol ve AJAX feedback sağlar.
 */

document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('form[method="POST"]');

  if (!form || !window.LabAjax) {
    return;
  }

  const firstNameInput = document.getElementById('first_name');
  const lastNameInput = document.getElementById('last_name');
  const emailInput = document.getElementById('email');
  const phoneInput = document.getElementById('phone');
  const studentNoInput = document.getElementById('student_no');
  const facultySelect = document.getElementById('faculty_id');
  const departmentSelect = document.getElementById('department_id');
  const classYearSelect = document.getElementById('class_year');
  const passwordInput = document.getElementById('password');
  const confirmPasswordInput = document.getElementById('confirm_password');

  const isRegisterPage = Boolean(studentNoInput && confirmPasswordInput);
  let emailAvailable = null;
  let isSubmittingAfterAsyncCheck = false;

  form.setAttribute('novalidate', 'novalidate');

  function valueOf(field) {
    return field ? field.value.trim() : '';
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  function isPositiveInteger(value) {
    return /^[1-9]\d*$/.test(String(value || ''));
  }

  function validateRequired(field, message) {
    if (!field) {
      return true;
    }

    if (valueOf(field) === '') {
      window.LabAjax.setFieldState(field, 'error', message);
      return false;
    }

    window.LabAjax.clearFieldState(field);
    return true;
  }

  function validateEmailField() {
    if (!emailInput) {
      return true;
    }

    const email = valueOf(emailInput);

    if (email === '') {
      emailAvailable = null;
      window.LabAjax.setFieldState(emailInput, 'error', 'Email is required.');
      return false;
    }

    if (!isValidEmail(email)) {
      emailAvailable = null;
      window.LabAjax.setFieldState(emailInput, 'error', 'Email format is invalid.');
      return false;
    }

    if (!isRegisterPage) {
      window.LabAjax.clearFieldState(emailInput);
    }

    return true;
  }

  function validatePasswordField() {
    if (!passwordInput) {
      return true;
    }

    const password = passwordInput.value;

    if (password === '') {
      window.LabAjax.setFieldState(passwordInput, 'error', 'Password is required.');
      return false;
    }

    if (password.length < 6) {
      window.LabAjax.setFieldState(passwordInput, 'error', 'Password must be at least 6 characters.');
      return false;
    }

    window.LabAjax.clearFieldState(passwordInput);
    return true;
  }

  function validateConfirmPasswordField() {
    if (!confirmPasswordInput) {
      return true;
    }

    if (confirmPasswordInput.value === '') {
      window.LabAjax.setFieldState(confirmPasswordInput, 'error', 'Password confirmation is required.');
      return false;
    }

    if (passwordInput && confirmPasswordInput.value !== passwordInput.value) {
      window.LabAjax.setFieldState(confirmPasswordInput, 'error', 'Passwords do not match.');
      return false;
    }

    window.LabAjax.setFieldState(confirmPasswordInput, 'success', 'Passwords match.');
    return true;
  }

  function validateRegisterFields() {
    let valid = true;

    valid = validateRequired(firstNameInput, 'First name is required.') && valid;
    valid = validateRequired(lastNameInput, 'Last name is required.') && valid;

    if (phoneInput && valueOf(phoneInput).length > 30) {
      window.LabAjax.setFieldState(phoneInput, 'error', 'Phone can be maximum 30 characters.');
      valid = false;
    } else if (phoneInput) {
      window.LabAjax.clearFieldState(phoneInput);
    }

    if (studentNoInput) {
      const studentNo = valueOf(studentNoInput);

      if (studentNo === '') {
        window.LabAjax.setFieldState(studentNoInput, 'error', 'Student number is required.');
        valid = false;
      } else if (studentNo.length < 3 || studentNo.length > 20) {
        window.LabAjax.setFieldState(studentNoInput, 'error', 'Student number must be between 3 and 20 characters.');
        valid = false;
      } else {
        window.LabAjax.clearFieldState(studentNoInput);
      }
    }

    if (facultySelect && !isPositiveInteger(facultySelect.value)) {
      window.LabAjax.setFieldState(facultySelect, 'error', 'Faculty selection is required.');
      valid = false;
    } else if (facultySelect) {
      window.LabAjax.clearFieldState(facultySelect);
    }

    if (departmentSelect && !isPositiveInteger(departmentSelect.value)) {
      window.LabAjax.setFieldState(departmentSelect, 'error', 'Department selection is required.');
      valid = false;
    } else if (departmentSelect) {
      window.LabAjax.clearFieldState(departmentSelect);
    }

    if (classYearSelect && !/^[1-6]$/.test(classYearSelect.value)) {
      window.LabAjax.setFieldState(classYearSelect, 'error', 'Class year must be between 1 and 6.');
      valid = false;
    } else if (classYearSelect) {
      window.LabAjax.clearFieldState(classYearSelect);
    }

    valid = validateConfirmPasswordField() && valid;

    return valid;
  }

  async function checkEmailAvailability() {
    if (!isRegisterPage || !emailInput || !validateEmailField()) {
      return !isRegisterPage;
    }

    const email = valueOf(emailInput);

    window.LabAjax.setFieldState(emailInput, 'info', 'Checking email...');

    try {
      const response = await window.LabAjax.get('check-email.php', { email });
      const exists = Boolean(response.data && response.data.exists);

      emailAvailable = !exists;

      if (exists) {
        window.LabAjax.setFieldState(emailInput, 'error', 'This email is already registered.');
        return false;
      }

      window.LabAjax.setFieldState(emailInput, 'success', 'This email is available.');
      return true;
    } catch (error) {
      emailAvailable = null;
      window.LabAjax.setFieldState(emailInput, 'error', error.message || 'Email check failed.');
      return false;
    }
  }

  async function loadDepartmentsByFaculty() {
    if (!facultySelect || !departmentSelect) {
      return;
    }

    const facultyId = facultySelect.value;

    departmentSelect.innerHTML = '<option value="">Select department</option>';

    if (!isPositiveInteger(facultyId)) {
      window.LabAjax.setFieldState(facultySelect, 'error', 'Faculty selection is required.');
      return;
    }

    departmentSelect.disabled = true;
    window.LabAjax.setFieldState(departmentSelect, 'info', 'Loading departments...');

    try {
      const response = await window.LabAjax.get('get-departments.php', {
        faculty_id: facultyId
      });

      const departments = response.data && Array.isArray(response.data.departments)
        ? response.data.departments
        : [];

      departments.forEach((department) => {
        const option = document.createElement('option');
        option.value = department.department_id;
        option.textContent = department.department_name;
        departmentSelect.appendChild(option);
      });

      departmentSelect.disabled = false;
      window.LabAjax.clearFieldState(facultySelect);

      if (departments.length === 0) {
        window.LabAjax.setFieldState(departmentSelect, 'info', 'No active department found for this faculty.');
      } else {
        window.LabAjax.clearFieldState(departmentSelect);
      }
    } catch (error) {
      departmentSelect.disabled = false;
      window.LabAjax.setFieldState(departmentSelect, 'error', error.message || 'Departments could not be loaded.');
    }
  }

  function addPasswordToggle(input) {
    if (!input || input.dataset.toggleReady === '1') {
      return;
    }

    input.dataset.toggleReady = '1';

    const wrapper = document.createElement('div');
    wrapper.className = 'password-input-wrapper';

    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'auth-password-toggle';
    button.setAttribute('aria-label', 'Show password');
    button.textContent = 'Show';

    button.addEventListener('click', () => {
      const isHidden = input.type === 'password';

      input.type = isHidden ? 'text' : 'password';
      button.textContent = isHidden ? 'Hide' : 'Show';
      button.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });

    wrapper.appendChild(button);
  }

  if (emailInput && isRegisterPage) {
    emailInput.addEventListener('input', () => {
      emailAvailable = null;
    });

    emailInput.addEventListener(
      'blur',
      window.LabAjax.debounce(checkEmailAvailability, 250)
    );
  }

  if (facultySelect && departmentSelect) {
    facultySelect.addEventListener('change', loadDepartmentsByFaculty);
  }

  if (passwordInput) {
    passwordInput.addEventListener('input', () => {
      validatePasswordField();

      if (confirmPasswordInput && confirmPasswordInput.value !== '') {
        validateConfirmPasswordField();
      }
    });

    addPasswordToggle(passwordInput);
  }

  if (confirmPasswordInput) {
    confirmPasswordInput.addEventListener('input', validateConfirmPasswordField);
    addPasswordToggle(confirmPasswordInput);
  }

  form.addEventListener('submit', async (event) => {
    if (isSubmittingAfterAsyncCheck) {
      return;
    }

    let valid = true;

    valid = validateEmailField() && valid;
    valid = validatePasswordField() && valid;

    if (isRegisterPage) {
      valid = validateRegisterFields() && valid;
    }

    if (!valid) {
      event.preventDefault();
      window.LabAjax.showToast('Please fix the highlighted fields.', 'error');
      return;
    }

    if (isRegisterPage) {
      event.preventDefault();

      const emailOk = emailAvailable === true
        ? true
        : await checkEmailAvailability();

      if (!emailOk) {
        window.LabAjax.showToast('Please use an available email address.', 'error');
        return;
      }

      isSubmittingAfterAsyncCheck = true;
      form.submit();
    }
  });
});