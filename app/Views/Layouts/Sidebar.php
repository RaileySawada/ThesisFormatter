<aside id="sidebar" class="fixed md:static z-70 w-[280px] md:w-[76px] lg:w-[280px] h-full md:h-auto bg-gradient-to-b from-[#010b31] via-slate-900 to-[#030a25] border-r border-slate-800/50 !flex flex-col shadow-2xl -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out">
  <div class="flex flex-col sticky top-0">
    <button id="close_sidebar" class="absolute top-4 right-4 md:hidden group flex items-center justify-center w-10 h-10 cursor-pointer hover:bg-slate-800/50 rounded-lg transition-all duration-300 z-10">
      <svg width="24" height="16" viewBox="0 0 24 16" fill="none" xmlns="http://www.w3.org/2000/svg" class="text-slate-300 group-hover:text-white transition-colors duration-300">
        <rect x="2.5" y="0.5" width="19" height="15" rx="4" stroke="currentColor" stroke-width="1.5"/>
        <line x1="9" y1="0.5" x2="9" y2="15.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
      </svg>
    </button>
    <div class="flex flex-col items-center pt-6 pb-5 border-b border-slate-700/50 backdrop-blur-sm select-none">
      <div class="relative group">
        <img src="<?= LOGO; ?>"
            alt="Logo"
            draggable="false"
            class="h-14 relative shadow-2xl transform group-hover:scale-110 transition-all duration-300">
      </div>
      <h1 class="block md:hidden lg:block mt-3 text-white font-bold text-lg tracking-tight bg-gradient-to-r from-white to-blue-100 bg-clip-text text-transparent">e-Docs</h1>
      <p class="block md:hidden lg:block text-slate-400 text-xs mt-0.5 font-light">Document Management</p>
    </div>

    <nav class="flex-1 overflow-y-auto px-2.5 py-5 space-y-1 mb-auto scrollbar-thin scrollbar-thumb-slate-700 scrollbar-track-transparent">
      <?php if ($user_role === "ADMIN"): ?>
      <a href="<?= BASE_URL.'/User_Management' ?>" 
        class="group relative flex items-center gap-3 px-3.5 py-3 md:px-2 md:py-2.5 lg:px-3.5 lg:py-3 rounded-xl transition-all duration-300 <?= $page == 'User Management' ? 'bg-gradient-to-r from-blue-600 to-blue-500 shadow-lg shadow-blue-500/30' : 'hover:bg-slate-800/70' ?>">
        <?php if($page == 'User Management'): ?>
          <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-7 bg-white rounded-r-full shadow-lg shadow-white/50"></div>
        <?php endif; ?>
        <div class="relative flex items-center gap-3 flex-1">
          <div class="flex items-center justify-center w-9 h-9 rounded-lg <?= $page == 'User Management' ? 'bg-white/15 shadow-inner' : 'bg-slate-800/80 group-hover:bg-blue-600/20 group-hover:scale-110' ?> transition-all duration-300">
            <i class="fa-solid fa-users-gear <?= $page == 'User Management' ? 'text-white drop-shadow-lg' : 'text-slate-400 group-hover:text-blue-400' ?> text-sm"></i>
          </div>
          <span class="<?= $page == 'User Management' ? 'text-white font-semibold' : 'text-slate-300 group-hover:text-white' ?> block md:hidden lg:block text-sm transition-colors duration-300 select-none">User Management</span>
        </div>
        <?php if ($page == 'User Management'): ?>
          <div class="block md:hidden lg:block">
            <i class="fa-solid fa-circle-dot text-white/80 text-xs relative animate-pulse"></i>
          </div>
        <?php endif; ?>
      </a>
      <?php endif; ?>

      <?php if($user_role == "ADMIN STAFF" || $user_role == "STUDENT" || ($user_role == "FACULTY" && isset($rs_role))): ?>
      <a href="<?= BASE_URL.'/Research' ?>" 
        class="group relative flex items-center gap-3 px-3.5 py-3 md:px-2 md:py-2.5 lg:px-3.5 lg:py-3 rounded-xl transition-all duration-300 <?= $page == 'Research' ? 'bg-gradient-to-r from-blue-600 to-blue-500 shadow-lg shadow-blue-500/30' : 'hover:bg-slate-800/70' ?>">
        <?php if($page == 'Research'): ?>
          <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-7 bg-white rounded-r-full shadow-lg shadow-white/50"></div>
        <?php endif; ?>
        <div class="relative flex items-center gap-3 flex-1">
          <div class="flex items-center justify-center w-9 h-9 rounded-lg <?= $page == 'Research' ? 'bg-white/15 shadow-inner' : 'bg-slate-800/80 group-hover:bg-blue-600/20 group-hover:scale-110' ?> transition-all duration-300">
            <i class="fa-solid fa-microscope <?= $page == 'Research' ? 'text-white drop-shadow-lg' : 'text-slate-400 group-hover:text-blue-400' ?> text-sm"></i>
          </div>
          <span class="<?= $page == 'Research' ? 'text-white font-semibold' : 'text-slate-300 group-hover:text-white' ?> block md:hidden lg:block text-sm transition-colors duration-300 select-none">Research</span>
        </div>
        <?php if ($page == 'Research'): ?>
          <div class="block md:hidden lg:block">
            <i class="fa-solid fa-circle-dot text-white/80 text-xs relative animate-pulse"></i>
          </div>
        <?php endif; ?>
      </a>
      <?php endif; ?>

      <?php if($user_role == "ADMIN STAFF" || $user_role == "FACULTY"): ?>
      <a href="<?= BASE_URL.'/Extension' ?>" 
        class="group relative flex items-center gap-3 px-3.5 py-3 md:px-2 md:py-2.5 lg:px-3.5 lg:py-3 rounded-xl transition-all duration-300 <?= $page == 'Extension' ? 'bg-gradient-to-r from-blue-600 to-blue-500 shadow-lg shadow-blue-500/30' : 'hover:bg-slate-800/70' ?>">
        <?php if($page == 'Extension'): ?>
          <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-7 bg-white rounded-r-full shadow-lg shadow-white/50"></div>
        <?php endif; ?>
        <div class="relative flex items-center gap-3 flex-1">
          <div class="flex items-center justify-center w-9 h-9 rounded-lg <?= $page == 'Extension' ? 'bg-white/15 shadow-inner' : 'bg-slate-800/80 group-hover:bg-blue-600/20 group-hover:scale-110' ?> transition-all duration-300">
            <i class="fa-solid fa-people-roof <?= $page == 'Extension' ? 'text-white drop-shadow-lg' : 'text-slate-400 group-hover:text-blue-400' ?> text-sm"></i>
          </div>
          <span class="<?= $page == 'Extension' ? 'text-white font-semibold' : 'text-slate-300 group-hover:text-white' ?> block md:hidden lg:block text-sm transition-colors duration-300 select-none">Extension</span>
        </div>
        <?php if($page == 'Extension'): ?>
          <div class="block md:hidden lg:block">
            <i class="fa-solid fa-circle-dot text-white/80 text-xs relative animate-pulse"></i>
          </div>
        <?php endif; ?>
      </a>
      <?php endif; ?>

      <?php if($user_role == "ADMIN STAFF" || $user_role == "FACULTY"): ?>
      <a href="<?= BASE_URL.'/Planning' ?>" 
        class="group relative flex items-center gap-3 px-3.5 py-3 md:px-2 md:py-2.5 lg:px-3.5 lg:py-3 rounded-xl transition-all duration-300 <?= $page == 'Planning' ? 'bg-gradient-to-r from-blue-600 to-blue-500 shadow-lg shadow-blue-500/30' : 'hover:bg-slate-800/70' ?>">
        <?php if($page == 'Planning'): ?>
          <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-7 bg-white rounded-r-full shadow-lg shadow-white/50"></div>
        <?php endif; ?>
        <div class="relative flex items-center gap-3 flex-1">
          <div class="flex items-center justify-center w-9 h-9 rounded-lg <?= $page == 'Planning' ? 'bg-white/15 shadow-inner' : 'bg-slate-800/80 group-hover:bg-blue-600/20 group-hover:scale-110' ?> transition-all duration-300">
            <i class="fa-solid fa-clipboard-list <?= $page == 'Planning' ? 'text-white drop-shadow-lg' : 'text-slate-400 group-hover:text-blue-400' ?> text-sm"></i>
          </div>
          <span class="<?= $page == 'Planning' ? 'text-white font-semibold' : 'text-slate-300 group-hover:text-white' ?> block md:hidden lg:block text-sm transition-colors duration-300 select-none">Planning</span>
        </div>
        <?php if($page == 'Planning'): ?>
          <div class="block md:hidden lg:block">
            <i class="fa-solid fa-circle-dot text-white/80 text-xs relative animate-pulse"></i>
          </div>
        <?php endif; ?>
      </a>
      <?php endif; ?>

      <?php if($user_role == "ADMIN STAFF" || $user_role == "ACCREDITOR" || ($user_role == "FACULTY" && isset($qa_role)) || $user_role == "STAFF"): ?>
      <a href="<?= BASE_URL.'/Quality_Assurance' ?>"
        class="group relative flex items-center gap-3 px-3.5 py-3 md:px-2 md:py-2.5 lg:px-3.5 lg:py-3 rounded-xl transition-all duration-300 <?= $page == 'Quality Assurance' ? 'bg-gradient-to-r from-blue-600 to-blue-500 shadow-lg shadow-blue-500/30' : 'hover:bg-slate-800/70' ?>">
        <?php if($page == 'Quality Assurance'): ?>
          <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-7 bg-white rounded-r-full shadow-lg shadow-white/50"></div>
        <?php endif; ?>
        <div class="relative flex items-center gap-3 flex-1">
          <div class="flex items-center justify-center w-9 h-9 rounded-lg <?= $page == 'Quality Assurance' ? 'bg-white/15 shadow-inner' : 'bg-slate-800/80 group-hover:bg-blue-600/20 group-hover:scale-110' ?> transition-all duration-300">
            <i class="fa-solid fa-flask <?= $page == 'Quality Assurance' ? 'text-white drop-shadow-lg' : 'text-slate-400 group-hover:text-blue-400' ?> text-sm"></i>
          </div>
          <span class="<?= $page == 'Quality Assurance' ? 'text-white font-semibold' : 'text-slate-300 group-hover:text-white' ?> block md:hidden lg:block text-sm transition-colors duration-300 select-none">Quality Assurance</span>
        </div>
        <?php if($page == 'Quality Assurance'): ?>
          <div class="block md:hidden lg:block">
            <i class="fa-solid fa-circle-dot text-white/80 text-xs relative animate-pulse"></i>
          </div>
        <?php endif; ?>
      </a>
      <?php endif; ?>

      <?php if($user_role == "ADMIN STAFF" || ($user_role == "FACULTY" && isset($qa_role) && $qa_role == "QA")): ?>
      <a href="<?= $qa_role == "QA" ? BASE_URL.'/Analytics?page=quality_assurance' : BASE_URL.'/Analytics' ?>" 
        class="group relative flex items-center gap-3 px-3.5 py-3 md:px-2 md:py-2.5 lg:px-3.5 lg:py-3 rounded-xl transition-all duration-300 <?= $page == 'Analytics' ? 'bg-gradient-to-r from-blue-600 to-blue-500 shadow-lg shadow-blue-500/30' : 'hover:bg-slate-800/70' ?>">
        <?php if($page == 'Analytics'): ?>
          <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-7 bg-white rounded-r-full shadow-lg shadow-white/50"></div>
        <?php endif; ?>
        <div class="relative flex items-center gap-3 flex-1">
          <div class="flex items-center justify-center w-9 h-9 rounded-lg <?= $page == 'Analytics' ? 'bg-white/15 shadow-inner' : 'bg-slate-800/80 group-hover:bg-blue-600/20 group-hover:scale-110' ?> transition-all duration-300">
            <i class="fa-solid fa-chart-line <?= $page == 'Analytics' ? 'text-white drop-shadow-lg' : 'text-slate-400 group-hover:text-blue-400' ?> text-sm"></i>
          </div>
          <span class="<?= $page == 'Analytics' ? 'text-white font-semibold' : 'text-slate-300 group-hover:text-white' ?> block md:hidden lg:block text-sm transition-colors duration-300 select-none">Analytics</span>
        </div>
        <?php if($page == 'Analytics'): ?>
          <div class="block md:hidden lg:block">
            <i class="fa-solid fa-circle-dot text-white/80 text-xs relative animate-pulse"></i>
          </div>
        <?php endif; ?>
      </a>
      <?php endif; ?>

      <?php //if($user_role == "ADMIN STAFF"): ?>
      <!-- <a href="<? //BASE_URL.'/Decision_Support' ?>" 
        class="group relative flex items-center gap-3 px-3.5 py-3 md:px-2 md:py-2.5 lg:px-3.5 lg:py-3 rounded-xl transition-all duration-300 <? //$page == 'Decision Support' ? 'bg-gradient-to-r from-blue-600 to-blue-500 shadow-lg shadow-blue-500/30' : 'hover:bg-slate-800/70' ?>">
        <?php //if($page == 'Decision Support'): ?>
          <div class="absolute left-0 top-1/2 -translate-y-1/2 w-1 h-7 bg-white rounded-r-full shadow-lg shadow-white/50"></div>
        <?php //endif; ?>
        <div class="relative flex items-center gap-3 flex-1">
          <div class="flex items-center justify-center w-9 h-9 rounded-lg <? //$page == 'Decision Support' ? 'bg-white/15 shadow-inner' : 'bg-slate-800/80 group-hover:bg-blue-600/20 group-hover:scale-110' ?> transition-all duration-300">
            <i class="fa-solid fa-lightbulb <? //$page == 'Decision Support' ? 'text-white drop-shadow-lg' : 'text-slate-400 group-hover:text-blue-400' ?> text-sm"></i>
          </div>
          <span class="<? //$page == 'Decision Support' ? 'text-white font-semibold' : 'text-slate-300 group-hover:text-white' ?> block md:hidden lg:block text-sm transition-colors duration-300 select-none">Decision Support</span>
        </div>
        <?php //if($page == 'Decision Support'): ?>
          <div class="block md:hidden lg:block">
            <i class="fa-solid fa-circle-dot text-white/80 text-xs relative animate-pulse"></i>
          </div>
        <?php //endif; ?>
      </a> -->
      <?php //endif; ?>

      <?php if(isset($rs_role) || $session->getVal('role') == "STUDENT"): ?>
      <div id="semesterBtn"
           class="!mt-4 group relative flex items-center justify-between md:justify-center lg:justify-between gap-2 bg-gradient-to-br from-slate-900/80 via-slate-800/80 to-slate-900/80 backdrop-blur-sm border border-slate-700/70 rounded-xl px-4 py-3.5 md:p-0 lg:px-4 lg:py-3.5 hover:shadow-xl hover:shadow-blue-500/10 hover:border-blue-600/30 transition-all duration-300 cursor-pointer overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-tr from-blue-600/5 to-purple-600/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <div class="flex items-center gap-3 relative z-10">
          <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-blue-600/20 to-blue-500/10 md:bg-transparent lg:bg-gradient-to-br lg:from-blue-600/20 lg:to-blue-500/10 group-hover:from-blue-600/30 group-hover:to-blue-500/20 md:group-hover:from-transparent lg:group-hover:from-blue-600/30 transition-all duration-300 shadow-inner">
            <i class="fa-solid fa-graduation-cap text-blue-400 text-lg group-hover:text-blue-300 transition-colors duration-300"></i>
          </div>
          <div class="block md:hidden lg:block">
            <span class="text-slate-200 font-semibold tracking-wide text-sm group-hover:text-white transition-colors duration-300 select-none"><?= $activeSemester ?></span>
            <p class="text-slate-500 text-xs mt-0.5 select-none">Active Semester</p>
          </div>
        </div>
        <div class="block md:hidden lg:block relative z-10">
          <div class="w-2 h-2 rounded-full bg-green-500 shadow-lg shadow-green-500/50 animate-pulse"></div>
        </div>
      </div>
      <?php endif; ?>
    </nav>
  </div>
</aside>

<div id="sidebar_backdrop" class="fixed inset-0 bg-black/50 z-60 md:hidden opacity-0 pointer-events-none transition-opacity duration-300"></div>