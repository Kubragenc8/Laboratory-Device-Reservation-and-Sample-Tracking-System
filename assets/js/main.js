document.addEventListener("DOMContentLoaded", () => {
  initializeAlerts();
  initializeMobileMenu();
});

/* ===== ALERT CLOSE ===== */
function initializeAlerts() {
  const alerts = document.querySelectorAll("[data-alert-close]");

  alerts.forEach(button => {
    button.addEventListener("click", () => {
      const parent = button.closest(".alert");
      if (parent) {
        parent.style.opacity = "0";
        setTimeout(() => {
          parent.remove();
        }, 200);
      }
    });
  });
}

/* ===== MOBILE MENU ===== */
function initializeMobileMenu() {
  const toggle = document.querySelector("[data-mobile-toggle]");
  const menu = document.querySelector("[data-mobile-menu]");

  if (!toggle || !menu) return;

  toggle.addEventListener("click", () => {
    menu.classList.toggle("active");
  });
}

/* ===== CONFIRM ACTION ===== */
function confirmAction(message = "Bu işlemi yapmak istediğine emin misin?") {
  return window.confirm(message);
}

/* ===== HELPERS ===== */
function showElement(element) {
  if (element) element.style.display = "";
}

function hideElement(element) {
  if (element) element.style.display = "none";
}

function toggleElement(element) {
  if (!element) return;

  if (
    element.style.display === "none" ||
    window.getComputedStyle(element).display === "none"
  ) {
    showElement(element);
  } else {
    hideElement(element);
  }
}