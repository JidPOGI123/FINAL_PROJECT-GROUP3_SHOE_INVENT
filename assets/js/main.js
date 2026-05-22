/* ============================================================
   ShStorage — main.js
   Shared utilities: sidebar toggle, alerts, modal helpers
   ============================================================ */

/* ---- Sidebar toggle (mobile) ---- */
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  if (sidebar) sidebar.classList.toggle('open');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function (e) {
  const sidebar = document.getElementById('sidebar');
  const toggle  = document.getElementById('sidebarToggle');
  if (!sidebar || !toggle) return;
  if (window.innerWidth > 768) return;
  if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
    sidebar.classList.remove('open');
  }
});

/* ---- Auto-dismiss alerts after 5s ---- */
document.addEventListener('DOMContentLoaded', function () {
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(function (alert) {
    setTimeout(function () {
      alert.style.transition = 'opacity .4s ease';
      alert.style.opacity = '0';
      setTimeout(function () { alert.remove(); }, 400);
    }, 5000);
  });
});

/* ---- Modal helper ---- */
function toggleModal(id) {
  const modal = document.getElementById(id);
  if (modal) modal.classList.toggle('active');
}

/* ---- Image preview ---- */
function previewImage(input, previewId) {
  const preview = document.getElementById(previewId || 'imgPreview');
  if (!preview) return;
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      preview.src = e.target.result;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(input.files[0]);
  }
}

/* ---- Confirm delete helper ---- */
function confirmDelete(message) {
  return confirm(message || 'Are you sure you want to delete this?');
}