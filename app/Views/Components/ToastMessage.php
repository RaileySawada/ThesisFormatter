<?php if (isset($_SESSION['success'])): ?>
<div class="toast-notification success fixed top-6 right-0 z-[100] w-[90%] sm:w-96 animate-slide-in flex flex-col px-4 sm:px-6 w-screen sm:w-content">
  <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-emerald-50 to-green-50 shadow-2xl border border-emerald-200/50 backdrop-blur-sm">
    <div class="absolute inset-0 bg-gradient-to-r from-emerald-400/10 to-green-400/10"></div>
    
    <div class="absolute bottom-0 left-0 right-0 h-1 bg-emerald-200/30">
      <div class="toast-progress h-full bg-gradient-to-r from-emerald-500 to-green-500 rounded-full shadow-lg shadow-emerald-500/50"></div>
    </div>
    
    <div class="relative px-5 py-4 flex items-start gap-4">
      <div class="relative flex-shrink-0">
        <div class="absolute inset-0 bg-emerald-500/20 rounded-full blur-xl animate-pulse"></div>
        <div class="relative w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-green-500 flex items-center justify-center shadow-lg">
          <i class="fa-solid fa-check text-white text-lg"></i>
        </div>
      </div>
      
      <div class="flex-1 pt-1">
        <h4 class="font-semibold text-emerald-900 text-sm mb-0.5 select-none">Success</h4>
        <p class="text-emerald-700 text-sm leading-relaxed select-none"><?= htmlspecialchars($session->getVal('success')); ?></p>
      </div>
      
      <button type="button" title="Close" onclick="this.closest('.success').style.animation='slide-out 0.3s forwards'" 
              class="flex-shrink-0 w-8 h-8 rounded-lg hover:bg-emerald-200/50 flex items-center justify-center transition-all duration-200 group cursor-pointer">
        <i class="fa-solid fa-xmark text-emerald-600 group-hover:rotate-90 transition-transform duration-200"></i>
      </button>
    </div>
  </div>
</div>
<?php
  if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
  }
?>
<?php elseif (isset($_SESSION['error'])): ?>
<div class="toast-notification error fixed top-6 right-0 z-[100] w-[90%] sm:w-96 animate-slide-in flex flex-col px-4 sm:px-6 w-screen sm:w-content">
  <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-rose-50 to-red-50 shadow-2xl border border-rose-200/50 backdrop-blur-sm">
    <div class="absolute inset-0 bg-gradient-to-r from-rose-400/10 to-red-400/10"></div>
    
    <div class="relative px-5 py-4 flex items-center gap-4">
      <div class="relative flex-shrink-0">
        <div class="absolute inset-0 bg-rose-500/20 rounded-full blur-xl animate-pulse"></div>
        <div class="relative w-10 h-10 rounded-full bg-gradient-to-br from-rose-400 to-red-500 flex items-center justify-center shadow-lg">
          <i class="fa-solid fa-xmark text-white text-lg"></i>
        </div>
      </div>
      
      <div class="flex-1 pt-1">
        <h4 class="font-semibold text-rose-900 text-sm mb-0.5 select-none">Error</h4>
        <p class="text-rose-700 text-sm leading-relaxed select-none"><?= htmlspecialchars($session->getVal('error')); ?></p>
      </div>
      
      <button onclick="this.closest('.toast-notification').style.animation='slide-out 0.3s forwards'" 
              class="flex-shrink-0 w-8 h-8 rounded-lg hover:bg-rose-200/50 flex items-center justify-center transition-all duration-200 group cursor-pointer">
        <i class="fa-solid fa-xmark text-rose-600 group-hover:rotate-90 transition-transform duration-200"></i>
      </button>
    </div>
  </div>
</div>
<?php
  if (isset($_SESSION['error'])) {
    unset($_SESSION['error']);
  }
endif;
?>

<style>
@keyframes slide-in {
  from {
    transform: translateX(400px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

@keyframes slide-out {
  to {
    transform: translateX(400px);
    opacity: 0;
  }
}

.toast-notification {
  animation: slide-in 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}

.toast-notification.hide {
  animation: slide-out 0.3s ease forwards;
}

.toast-progress {
  animation: progress 5s linear forwards;
}

@keyframes progress {
  from { width: 100%; }
  to { width: 0%; }
}
</style>

<script>
const toast = document.querySelector('.toast-notification.success');

if (toast) {
  setTimeout(() => {
    toast.classList.add('hide');
  }, 4700);

  setTimeout(() => {
    toast.remove();
  }, 5200);
}
</script>