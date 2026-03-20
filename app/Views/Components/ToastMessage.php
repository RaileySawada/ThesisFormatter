<?php if (isset($_SESSION['success'])): ?>
<div class="toast-notification success fixed top-6 right-4 z-[100] w-[calc(100%-2rem)] sm:w-96">
  <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-50 to-green-50 shadow-2xl border border-emerald-200/50 backdrop-blur-sm">
    <div class="absolute inset-0 bg-gradient-to-r from-emerald-400/10 to-green-400/10 pointer-events-none"></div>

    <!-- Progress bar -->
    <div class="absolute bottom-0 left-0 right-0 h-1 bg-emerald-200/30">
      <div class="toast-progress h-full bg-gradient-to-r from-emerald-500 to-green-500 rounded-full"></div>
    </div>

    <div class="relative px-5 py-4 flex items-start gap-4">
      <div class="relative flex-shrink-0">
        <div class="absolute inset-0 bg-emerald-500/20 rounded-full blur-xl animate-pulse"></div>
        <div class="relative w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center shadow-lg">
          <i class="fa-solid fa-check text-white text-lg"></i>
        </div>
      </div>

      <div class="flex-1 pt-1 min-w-0">
        <h4 class="font-semibold text-emerald-900 text-sm mb-0.5 select-none">Success</h4>
        <p class="text-emerald-700 text-sm leading-relaxed select-none"><?= htmlspecialchars($_SESSION['success']) ?></p>
      </div>

      <button type="button" title="Close"
              onclick="dismissToast(this.closest('.toast-notification'))"
              class="flex-shrink-0 w-8 h-8 rounded-lg hover:bg-emerald-200/50 flex items-center justify-center transition-all duration-200 group cursor-pointer">
        <i class="fa-solid fa-xmark text-emerald-600 group-hover:rotate-90 transition-transform duration-200"></i>
      </button>
    </div>
  </div>
</div>
<?php unset($_SESSION['success']); ?>

<?php elseif (isset($_SESSION['error'])): ?>
<div class="toast-notification error fixed top-6 right-4 z-[100] w-[calc(100%-2rem)] sm:w-96">
  <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-rose-50 to-red-50 shadow-2xl border border-rose-200/50 backdrop-blur-sm">
    <div class="absolute inset-0 bg-gradient-to-r from-rose-400/10 to-red-400/10 pointer-events-none"></div>

    <!-- Progress bar -->
    <div class="absolute bottom-0 left-0 right-0 h-1 bg-rose-200/30">
      <div class="toast-progress toast-progress--error h-full bg-gradient-to-r from-rose-500 to-red-500 rounded-full"></div>
    </div>

    <div class="relative px-5 py-4 flex items-start gap-4">
      <div class="relative flex-shrink-0">
        <div class="absolute inset-0 bg-rose-500/20 rounded-full blur-xl animate-pulse"></div>
        <div class="relative w-10 h-10 rounded-full bg-gradient-to-br from-rose-400 to-red-500 flex items-center justify-center shadow-lg">
          <i class="fa-solid fa-triangle-exclamation text-white text-lg"></i>
        </div>
      </div>

      <div class="flex-1 pt-1 min-w-0">
        <h4 class="font-semibold text-rose-900 text-sm mb-0.5 select-none">Error</h4>
        <p class="text-rose-700 text-sm leading-relaxed select-none"><?= htmlspecialchars($_SESSION['error']) ?></p>
      </div>

      <button type="button" title="Close"
              onclick="dismissToast(this.closest('.toast-notification'))"
              class="flex-shrink-0 w-8 h-8 rounded-lg hover:bg-rose-200/50 flex items-center justify-center transition-all duration-200 group cursor-pointer">
        <i class="fa-solid fa-xmark text-rose-600 group-hover:rotate-90 transition-transform duration-200"></i>
      </button>
    </div>
  </div>
</div>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<style>
@keyframes toast-slide-in {
  from { transform: translateX(calc(100% + 1rem)); opacity: 0; }
  to   { transform: translateX(0); opacity: 1; }
}
@keyframes toast-slide-out {
  from { transform: translateX(0); opacity: 1; }
  to   { transform: translateX(calc(100% + 1rem)); opacity: 0; }
}
@keyframes toast-progress {
  from { width: 100%; }
  to   { width: 0%; }
}

.toast-notification {
  animation: toast-slide-in 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.toast-notification.hide {
  animation: toast-slide-out 0.35s cubic-bezier(0.4, 0, 1, 1) forwards;
}
.toast-progress {
  animation: toast-progress 5s linear forwards;
}
.toast-progress--error {
  animation-duration: 7s;
}
</style>

<script>
// Global so inline onclick="dismissToast(...)" always resolves
window.dismissToast = function (el) {
  if (!el) return;
  el.classList.add('hide');
  setTimeout(() => el.remove(), 350);
};

// Auto-dismiss on DOMContentLoaded in case script runs before elements exist
(function () {
  function autoDismiss() {
    const success = document.querySelector('.toast-notification.success');
    if (success) setTimeout(() => window.dismissToast(success), 4700);
    const error = document.querySelector('.toast-notification.error');
    if (error) setTimeout(() => window.dismissToast(error), 7000);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoDismiss);
  } else {
    autoDismiss();
  }
})();
</script>