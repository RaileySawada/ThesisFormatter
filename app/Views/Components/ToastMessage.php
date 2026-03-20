<?php if (isset($_SESSION['success'])): ?>
<div class="tf-toast tf-toast--success" role="alert" aria-live="polite">
  <div class="tf-toast-icon">
    <i class="fa-solid fa-check"></i>
  </div>
  <div class="tf-toast-body">
    <p class="tf-toast-title">Success</p>
    <p class="tf-toast-msg"><?= htmlspecialchars($_SESSION['success']) ?></p>
  </div>
  <button class="tf-toast-close" onclick="dismissToast(this.closest('.tf-toast'))" title="Close">
    <i class="fa-solid fa-xmark"></i>
  </button>
  <div class="tf-toast-bar"></div>
</div>
<?php unset($_SESSION['success']); ?>

<?php elseif (isset($_SESSION['error'])): ?>
<div class="tf-toast tf-toast--error" role="alert" aria-live="assertive">
  <div class="tf-toast-icon">
    <i class="fa-solid fa-triangle-exclamation"></i>
  </div>
  <div class="tf-toast-body">
    <p class="tf-toast-title">Error</p>
    <p class="tf-toast-msg"><?= htmlspecialchars($_SESSION['error']) ?></p>
  </div>
  <button class="tf-toast-close" onclick="dismissToast(this.closest('.tf-toast'))" title="Close">
    <i class="fa-solid fa-xmark"></i>
  </button>
  <div class="tf-toast-bar tf-toast-bar--error"></div>
</div>
<?php unset($_SESSION['error']); ?>
<?php endif; ?>

<style>
.tf-toast {
  position: fixed;
  top: 24px;
  right: 16px;
  z-index: 100;
  width: calc(100% - 2rem);
  max-width: 384px;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 14px 18px 14px;
  border-radius: 16px;
  border: 1px solid var(--border);
  background: var(--surface);
  box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.06);
  overflow: hidden;
  animation: tf-slide-in 0.38s cubic-bezier(0.22,1,0.36,1) forwards;
}
body[data-theme="dark"] .tf-toast {
  box-shadow: 0 8px 32px rgba(0,0,0,0.45), 0 2px 8px rgba(0,0,0,0.3);
}
.tf-toast.hide {
  animation: tf-slide-out 0.3s cubic-bezier(0.4,0,1,1) forwards;
}
.tf-toast-icon {
  flex-shrink: 0;
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
}
.tf-toast--success .tf-toast-icon {
  background: rgba(16,185,129,0.12);
  color: #10b981;
}
body[data-theme="dark"] .tf-toast--success .tf-toast-icon {
  background: rgba(16,185,129,0.15);
  color: #34d399;
}
.tf-toast--error .tf-toast-icon {
  background: rgba(239,68,68,0.1);
  color: #ef4444;
}
body[data-theme="dark"] .tf-toast--error .tf-toast-icon {
  background: rgba(239,68,68,0.15);
  color: #f87171;
}
.tf-toast-body {
  flex: 1;
  min-width: 0;
  padding-top: 2px;
}
.tf-toast-title {
  font-size: 0.8125rem;
  font-weight: 700;
  line-height: 1;
  margin: 0 0 4px 0;
  color: var(--text-primary);
}
.tf-toast-msg {
  font-size: 0.8rem;
  line-height: 1.5;
  margin: 0;
  color: var(--text-secondary);
}
.tf-toast-close {
  flex-shrink: 0;
  width: 28px;
  height: 28px;
  border-radius: 8px;
  border: none;
  background: transparent;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 0.8rem;
  transition: background 0.18s, color 0.18s;
  margin-top: 1px;
}
.tf-toast-close:hover {
  background: var(--surface-raised);
  color: var(--text-secondary);
}
.tf-toast-bar {
  position: absolute;
  bottom: 0;
  left: 0;
  height: 3px;
  border-radius: 0 2px 2px 0;
  background: #10b981;
  animation: tf-progress 5s linear forwards;
}
.tf-toast-bar--error {
  background: #ef4444;
  animation-duration: 7s;
}
body[data-theme="dark"] .tf-toast-bar        { background: #34d399; }
body[data-theme="dark"] .tf-toast-bar--error { background: #f87171; }

.tf-toast--success { border-color: rgba(16,185,129,0.3); }
.tf-toast--error   { border-color: rgba(239,68,68,0.25); }
body[data-theme="dark"] .tf-toast--success { border-color: rgba(52,211,153,0.2); }
body[data-theme="dark"] .tf-toast--error   { border-color: rgba(248,113,113,0.2); }

@keyframes tf-slide-in  { from { transform: translateX(calc(100% + 1rem)); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
@keyframes tf-slide-out { from { transform: translateX(0); opacity: 1; } to { transform: translateX(calc(100% + 1rem)); opacity: 0; } }
@keyframes tf-progress  { from { width: 100%; } to { width: 0%; } }
</style>

<script>
window.dismissToast = function (el) {
  if (!el) return;
  el.classList.add('hide');
  setTimeout(() => el.remove(), 320);
};
(function () {
  function autoDismiss() {
    const s = document.querySelector('.tf-toast.tf-toast--success');
    if (s) setTimeout(() => window.dismissToast(s), 4700);
    const e = document.querySelector('.tf-toast.tf-toast--error');
    if (e) setTimeout(() => window.dismissToast(e), 7000);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoDismiss);
  } else {
    autoDismiss();
  }
})();
</script>