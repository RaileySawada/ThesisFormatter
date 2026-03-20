<?php
if (!empty($_SESSION['error'])) {
  $toastFile = defined('TOAST') ? TOAST : (__DIR__ . '/ToastMessage.php');
  if (file_exists($toastFile)) require $toastFile;
}
?>
<section id="formatter-app" data-theme="light"
  class="relative min-h-screen overflow-x-hidden transition-colors duration-300"
  style="background: var(--bg-page);">

  <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
    <div class="blob-blue   absolute -top-24 -left-16 h-80 w-80 rounded-full blur-3xl opacity-30" style="background:var(--blob-1)"></div>
    <div class="blob-indigo absolute top-1/3 -right-20 h-96 w-96 rounded-full blur-3xl opacity-25" style="background:var(--blob-2)"></div>
    <div class="blob-sky    absolute bottom-0 left-1/3 h-72 w-72 rounded-full blur-3xl opacity-20" style="background:var(--blob-3)"></div>
  </div>

  <div class="relative mx-auto max-w-7xl px-4 py-4 sm:px-6">
    <header class="mb-4 flex items-center justify-between lg:hidden">
      <div class="flex items-center gap-2">
        <img src="<?= defined('LOGO') ? LOGO : '' ?>" alt="Thesis Formatter" class="h-8 w-8 object-contain rounded-xl">
        <span class="text-[11px] font-bold uppercase tracking-[0.2em]" style="color:var(--accent)">Thesis Formatter</span>
      </div>
      <div class="flex items-center gap-2">
        <button id="mobile-theme-btn" aria-label="Toggle dark mode"
          class="flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold transition"
          style="border-color:var(--border);background:var(--surface);color:var(--text-secondary)">
          <span id="mobile-theme-icon"><i class="fa-solid fa-sun fa-sm" style="color:#f59e0b"></i></span>
          <span id="mobile-theme-label">Light</span>
        </button>
        <button id="open-options-btn"
          class="flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-bold text-white shadow-md transition active:scale-95"
          style="background:var(--accent)">
          <i class="fa-solid fa-sliders text-sm"></i> Options
        </button>
      </div>
    </header>

    <form action="" method="POST" enctype="multipart/form-data" id="main-form"
          class="flex w-full gap-6 items-start">

      
      <aside class="sticky top-0 hidden lg:flex lg:flex-col w-full max-w-[280px] shrink-0 self-start" id="main-sidebar">

        
        <div class="flex items-center gap-3 pb-1">
          <img src="<?= defined('LOGO') ? LOGO : '' ?>" alt="Thesis Formatter" class="h-10 w-10 object-contain shrink-0">
          <div>
            <p class="text-[10px] font-bold uppercase tracking-[0.22em]" style="color:var(--accent)">Thesis</p>
            <p class="text-sm font-bold leading-none" style="color:var(--text-primary)">Formatter</p>
          </div>
        </div>

        <div class="h-px" style="background:var(--border)"></div>

        
        <div class="rounded-2xl border px-4 py-3 transition-colors" style="background:var(--surface-raised);border-color:var(--border)">
          <p class="mb-2.5 text-[10px] font-bold uppercase tracking-[0.18em]" style="color:var(--text-muted)">
            <i class="fa-solid fa-palette mr-1"></i> Appearance
          </p>
          <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
              <span id="desktop-theme-icon"><i class="fa-solid fa-sun fa-lg" style="color:#f59e0b"></i></span>
              <span class="text-sm font-semibold" style="color:var(--text-primary)">Dark Mode</span>
            </div>
            <div id="desktop-toggle" class="toggle-track" role="switch" aria-checked="false" tabindex="0">
              <div class="toggle-thumb"></div>
            </div>
          </div>
        </div>

        
        <div>
          <p class="mb-2.5 text-[10px] font-bold uppercase tracking-[0.18em]" style="color:var(--text-muted)">
            <i class="fa-solid fa-layer-group mr-1"></i> Main Sections
          </p>
          <div class="space-y-2" id="desktop-section-cards">

            
            <div class="section-card section-card--disabled relative flex items-start gap-3 rounded-2xl border px-4 py-3 cursor-not-allowed select-none"
                 data-value="preliminary" title="Coming soon"
                 style="border-color:var(--border);opacity:.45">
              <input type="checkbox" name="sections[]" value="preliminary" class="sr-only section-checkbox" disabled>
              <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-xl" style="background:var(--surface-raised);color:var(--text-muted)">
                <i class="fa-solid fa-file-lines text-xs"></i>
              </span>
              <span class="flex-1 min-w-0">
                <span class="block text-sm font-semibold" style="color:var(--text-muted)">Preliminary</span>
                <span class="block text-xs mt-0.5" style="color:var(--text-muted)">Title page, approval sheet, abstract</span>
              </span>
              <span class="mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide" style="background:var(--surface-raised);color:var(--text-muted)">Soon</span>
            </div>

            
            <div class="section-card section-card--selected relative flex cursor-pointer items-start gap-3 rounded-2xl px-4 py-3 transition-all"
                 data-value="chapters"
                 style="border:2px solid var(--accent);background:var(--accent-subtle)">
              <input type="checkbox" name="sections[]" value="chapters" class="sr-only section-checkbox" checked>
              <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-xl" style="background:var(--accent-subtle-strong);color:var(--accent)">
                <i class="fa-solid fa-book-open text-xs"></i>
              </span>
              <span class="flex-1 min-w-0 pr-1">
                <span class="block text-sm font-semibold leading-snug" style="color:var(--text-primary)">Chapter 1 – References</span>
                <span class="block text-xs mt-0.5" style="color:var(--text-secondary)">Chapters, headings, body text, figures</span>
              </span>
              <span class="section-card__check mt-0.5 shrink-0 flex h-5 w-5 items-center justify-center rounded-full text-white" style="background:var(--accent)">
                <i class="fa-solid fa-check text-[10px]"></i>
              </span>
            </div>

            
            <div class="section-card section-card--disabled relative flex items-start gap-3 rounded-2xl border px-4 py-3 cursor-not-allowed select-none"
                 data-value="appendices" title="Coming soon"
                 style="border-color:var(--border);opacity:.45">
              <input type="checkbox" name="sections[]" value="appendices" class="sr-only section-checkbox" disabled>
              <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-xl" style="background:var(--surface-raised);color:var(--text-muted)">
                <i class="fa-solid fa-paperclip text-xs"></i>
              </span>
              <span class="flex-1 min-w-0">
                <span class="block text-sm font-semibold" style="color:var(--text-muted)">Appendices</span>
                <span class="block text-xs mt-0.5" style="color:var(--text-muted)">Appendix headings, labels, CV</span>
              </span>
              <span class="mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide" style="background:var(--surface-raised);color:var(--text-muted)">Soon</span>
            </div>

          </div>
        </div>

        
        <div>
          <button type="button" id="desktop-rules-toggle"
            class="w-full flex items-center justify-between rounded-xl px-1 py-1.5 transition cursor-pointer">
            <p class="text-[10px] font-bold uppercase tracking-[0.18em]" style="color:var(--text-muted)">
              <i class="fa-solid fa-gear mr-1"></i> Advanced Rules
            </p>
            <i class="fa-solid fa-chevron-down rules-chevron text-xs" style="color:var(--text-muted)"></i>
          </button>
          <div class="rules-body" id="desktop-rules-body">
            <div class="space-y-1.5 pt-2">
              <?php foreach ($rules as [$val, $icon, $label]): ?>
              <label class="tf-rule-row flex items-center gap-3 rounded-xl px-3 py-2.5 cursor-pointer transition-colors"
                     style="background:var(--surface-raised)">
                <input type="checkbox" name="rules[]" value="<?= $val ?>" checked class="sr-only rule-checkbox">
                <span class="rule-toggle-dot flex h-4 w-4 shrink-0 items-center justify-center rounded-full text-white transition" style="background:var(--accent)">
                  <i class="fa-solid fa-check text-[8px]"></i>
                </span>
                <i class="fa-solid <?= $icon ?> text-xs w-3.5 text-center shrink-0" style="color:var(--text-muted)"></i>
                <span class="text-sm" style="color:var(--text-secondary)"><?= $label ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        
        <div class="mt-auto pt-2 border-t" style="border-color:var(--border)">
          <p class="text-[10px] text-center" style="color:var(--text-muted)">
            Developed with <span style="color:#ef4444">anger</span> by
            <span class="font-bold" style="color:var(--text-secondary)">Railey</span> 😤
          </p>
        </div>
      </aside>

      
      <main class="min-w-0 flex-1 space-y-5">

        
        <div class="rounded-3xl border p-5 sm:p-7 transition-colors duration-300"
             style="background:var(--surface);border-color:var(--border);box-shadow:var(--shadow)">

          
          <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
              <p class="text-[10px] font-bold uppercase tracking-[0.22em]" style="color:var(--accent)">
                <img src="<?= defined('LOGO') ? LOGO : '' ?>" alt="" class="inline-block h-4 w-4 object-contain mr-1 align-middle"> Thesis Formatter
              </p>
              <p class="mt-2 text-sm leading-7" style="color:var(--text-secondary)">
                Upload your manuscript and apply formatting rules for chapters, references, figures, tables, and captions.
              </p>
            </div>
            <div class="flex shrink-0 gap-2">
              
              <a href="<?= defined('BASE_URL') ? BASE_URL : '' ?>/public/template/manuscript_template.docx"
                 download
                 class="inline-flex items-center gap-1.5 rounded-2xl border px-4 py-2.5 text-xs font-semibold transition hover:opacity-80"
                 style="border-color:var(--accent);color:var(--accent);background:var(--accent-subtle)">
                <i class="fa-solid fa-download text-xs"></i>
                Template
              </a>
              <button type="button" id="open-preview-btn"
                class="inline-flex items-center gap-1.5 rounded-2xl border px-4 py-2.5 text-xs font-semibold transition hover:opacity-80"
                style="border-color:var(--border);color:var(--text-secondary);background:var(--surface-raised)">
                <i class="fa-solid fa-eye text-xs"></i>
                Preview Rules
              </button>
            </div>
          </div>

          
          <div id="drop-zone" class="drop-zone mt-5 rounded-2xl border-2 border-dashed p-6 sm:p-8 transition-all"
               style="border-color:var(--accent-muted);background:var(--accent-subtle)">
            <div id="upload-prompt">
              <label for="manuscript" class="block cursor-pointer">
                <div class="flex flex-col items-center justify-center text-center">
                  <div class="flex h-14 w-14 items-center justify-center rounded-2xl shadow-sm ring-1 sm:h-16 sm:w-16"
                       style="background:var(--surface);ring-color:var(--border)">
                    <i class="fa-solid fa-cloud-arrow-up text-2xl sm:text-3xl" style="color:var(--accent)"></i>
                  </div>
                  <h3 class="mt-4 text-base font-semibold sm:text-lg" style="color:var(--text-primary)">Upload manuscript</h3>
                  <p class="mt-1.5 text-sm" style="color:var(--text-secondary)">
                    Drag &amp; drop your <span class="font-semibold" style="color:var(--text-primary)">.docx</span> file, or click to browse
                  </p>
                  <p class="mt-1 text-xs" style="color:var(--text-muted)">Microsoft Word Document (.docx)</p>
                </div>
                <input id="manuscript" name="manuscript" type="file" accept=".docx" class="hidden">
              </label>
            </div>

            <div id="file-uploaded-state" class="hidden">
              <div class="flex flex-col items-center gap-4">
                <div class="file-cards-wrap">
                  <div class="file-card fc-p1">
                    <div class="fc-lines">
                      <div class="fc-line" style="width:70%"></div>
                      <div class="fc-line" style="width:90%"></div>
                      <div class="fc-line" style="width:55%"></div>
                    </div>
                  </div>
                  <div class="file-card fc-p2">
                    <div class="fc-lines">
                      <div class="fc-line" style="width:85%"></div>
                      <div class="fc-line" style="width:60%"></div>
                      <div class="fc-line" style="width:75%"></div>
                      <div class="fc-line" style="width:40%"></div>
                    </div>
                  </div>
                  <div class="file-card fc-p3">
                    <i class="fa-solid fa-file-lines fc-doc-icon"></i>
                    <div class="fc-lines" style="margin-top:6px">
                      <div class="fc-line" style="width:80%"></div>
                      <div class="fc-line" style="width:65%"></div>
                    </div>
                    <span id="fan-filename" class="fc-filename"></span>
                  </div>
                </div>
                <div class="text-center">
                  <p class="text-sm font-bold" style="color:var(--text-primary)">
                    <i class="fa-solid fa-circle-check mr-1.5" style="color:#10b981"></i>File ready
                  </p>
                  <p id="file-name-display" class="mt-0.5 text-xs truncate max-w-xs" style="color:var(--text-muted)"></p>
                </div>
                <label for="manuscript" class="cursor-pointer">
                  <span class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-semibold transition hover:opacity-80"
                        style="border-color:var(--border);background:var(--surface);color:var(--text-secondary)">
                    <i class="fa-solid fa-arrow-up-from-bracket text-xs"></i> Change file
                  </span>
                  <input id="manuscript" name="manuscript" type="file" accept=".docx" class="hidden">
                </label>
              </div>
            </div>
          </div>

          
          <div class="mt-4 flex items-center gap-2">
            <span class="text-xs font-medium" style="color:var(--text-muted)">Formatting:</span>
            <span id="selected-section-badge" class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold" style="background:var(--accent-subtle-strong);color:var(--accent)">
              <i class="fa-solid fa-book-open text-[10px]"></i> Chapter 1 – References
            </span>
          </div>

          <div class="mt-3">
            <button id="apply-btn" type="submit" name="action" value="format" disabled
              class="w-full rounded-2xl px-5 py-3.5 text-sm font-bold text-white transition active:scale-[0.98] shadow-md disabled:cursor-not-allowed disabled:opacity-40 disabled:shadow-none disabled:active:scale-100"
              style="background:var(--accent);box-shadow:0 4px 14px var(--accent-glow)">
              <span id="apply-btn-idle"><i class="fa-solid fa-bolt mr-2"></i> Apply Formatting</span>
              <span id="apply-btn-loading" class="hidden items-center justify-center gap-2">
                <svg class="animate-spin h-4 w-4 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Processing…
              </span>
            </button>
          </div>
        </div>

        
        <div class="grid gap-5 xl:grid-cols-2">

          
          <div class="rounded-3xl border p-6 transition-colors duration-300"
               style="background:var(--surface);border-color:var(--border);box-shadow:var(--shadow)">
            <h2 class="text-lg font-bold sm:text-xl" style="color:var(--text-primary)">
              <i class="fa-solid fa-list-check mr-2" style="color:var(--accent)"></i>Formatting Coverage
            </h2>
            <p class="mt-1.5 text-sm" style="color:var(--text-muted)">Normalizes document sections per the master template.</p>
            <div class="mt-4 space-y-2.5">
              <div class="rounded-2xl border p-4 transition-colors" style="background:var(--surface-raised);border-color:var(--border)">
                <h3 class="text-sm font-semibold" style="color:var(--text-secondary)">
                  <i class="fa-solid fa-file-circle-check mr-1.5" style="color:var(--text-muted)"></i>Preliminary Pages
                  <span class="ml-2 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" style="background:var(--surface);color:var(--text-muted)">Soon</span>
                </h3>
                <p class="mt-1 text-xs" style="color:var(--text-muted)">Title page, approval sheet, abstract, acknowledgement.</p>
              </div>
              <div class="rounded-2xl border-2 p-4 transition-colors" style="background:var(--accent-subtle);border-color:var(--accent)">
                <h3 class="text-sm font-semibold" style="color:var(--text-primary)">
                  <i class="fa-solid fa-book-open mr-1.5" style="color:var(--accent)"></i>Chapters and References
                  <span class="ml-2 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" style="background:var(--accent-subtle-strong);color:var(--accent)">Active</span>
                </h3>
                <p class="mt-1 text-xs" style="color:var(--text-secondary)">Chapter titles, headings, body text, figures, tables, captions, legends, references.</p>
              </div>
              <div class="rounded-2xl border p-4 transition-colors" style="background:var(--surface-raised);border-color:var(--border)">
                <h3 class="text-sm font-semibold" style="color:var(--text-secondary)">
                  <i class="fa-solid fa-paperclip mr-1.5" style="color:var(--text-muted)"></i>Appendices
                  <span class="ml-2 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" style="background:var(--surface);color:var(--text-muted)">Soon</span>
                </h3>
                <p class="mt-1 text-xs" style="color:var(--text-muted)">Appendix labels, continuation blocks, CV.</p>
              </div>
            </div>
          </div>

          
          <div class="rounded-3xl p-6 text-white shadow-xl flex flex-col" style="background:var(--status-bg)">
            <p class="text-xs font-bold uppercase tracking-[0.2em]" style="color:var(--accent-light)">
              <i class="fa-solid fa-circle-info mr-1"></i> Current Status
            </p>

            
            <h2 id="status-headline" class="mt-2 text-lg font-bold sm:text-xl">Ready for upload</h2>
            <p id="status-subtext" class="mt-1.5 text-sm leading-6" style="color:rgba(255,255,255,0.65)">
              Select sections on the left, then upload your manuscript.
            </p>

            
            <div class="mt-5 space-y-2.5 flex-1">
              
              <div id="step-section" class="status-step flex items-center gap-3 rounded-2xl px-4 py-3 transition-all duration-300" style="background:rgba(255,255,255,0.07)">
                <span id="step-section-icon" class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold" style="background:rgba(255,255,255,0.15)">1</span>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-semibold">Section selected</p>
                  <p id="step-section-detail" class="text-[11px] mt-0.5" style="color:rgba(255,255,255,0.5)">Choose a scope in the sidebar</p>
                </div>
              </div>

              
              <div id="step-file" class="status-step flex items-center gap-3 rounded-2xl px-4 py-3 transition-all duration-300" style="background:rgba(255,255,255,0.07)">
                <span id="step-file-icon" class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold" style="background:rgba(255,255,255,0.15)">2</span>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-semibold">Manuscript uploaded</p>
                  <p id="step-file-detail" class="text-[11px] mt-0.5 truncate" style="color:rgba(255,255,255,0.5)">No file chosen yet</p>
                </div>
              </div>

              
              <div id="step-rules" class="status-step flex items-center gap-3 rounded-2xl px-4 py-3 transition-all duration-300" style="background:rgba(255,255,255,0.07)">
                <span id="step-rules-icon" class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold" style="background:rgba(255,255,255,0.15)">3</span>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-semibold">Formatting rules</p>
                  <p id="step-rules-detail" class="text-[11px] mt-0.5" style="color:rgba(255,255,255,0.5)">Loading…</p>
                </div>
              </div>

              
              <div id="step-ready" class="status-step flex items-center gap-3 rounded-2xl px-4 py-3 transition-all duration-300" style="background:rgba(255,255,255,0.05);opacity:0.45">
                <span id="step-ready-icon" class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold" style="background:rgba(255,255,255,0.1)">4</span>
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-semibold">Apply formatting</p>
                  <p id="step-ready-detail" class="text-[11px] mt-0.5" style="color:rgba(255,255,255,0.4)">Complete steps above first</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        
        <p class="text-center text-[11px] pb-2 lg:hidden" style="color:var(--text-muted)">
          Developed with <span style="color:#ef4444">anger</span> by <strong style="color:var(--text-secondary)">Railey</strong> 😤
        </p>

      </main>
    </form>
  </div>
</section>


<div id="mobile-modal-backdrop" aria-hidden="true"></div>


<div id="mobile-options-sheet" role="dialog" aria-modal="true"
  style="background:var(--surface)">
  <div class="px-5 pt-4 pb-2 shrink-0">
    <div class="mx-auto mb-3 h-1 w-10 rounded-full" style="background:var(--border)"></div>
    <div class="flex items-center justify-between">
      <div>
        <p class="text-[10px] font-bold uppercase tracking-[0.2em]" style="color:var(--accent)">
          <i class="fa-solid fa-sliders mr-1"></i> Options
        </p>
        <h2 class="text-xl font-bold mt-0.5" style="color:var(--text-primary)">Formatting Scope</h2>
      </div>
      <button id="close-mobile-sheet"
        class="flex h-8 w-8 items-center justify-center rounded-full transition"
        style="background:var(--surface-raised);color:var(--text-secondary)">
        <i class="fa-solid fa-xmark text-sm"></i>
      </button>
    </div>
    <p class="mt-1 text-sm" style="color:var(--text-muted)">Select sections and rules to apply.</p>
  </div>

  <div class="flex-1 overflow-y-auto px-5 pb-4 space-y-4">
    <div>
      <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.18em]" style="color:var(--text-muted)">
        <i class="fa-solid fa-layer-group mr-1"></i> Main Sections
      </p>
      <div class="space-y-2" id="mobile-section-cards">

        <div class="section-card section-card--disabled relative flex items-start gap-3 rounded-2xl border px-4 py-3 cursor-not-allowed select-none"
             data-value="preliminary" style="border-color:var(--border);opacity:.45">
          <input type="checkbox" name="sections_m[]" value="preliminary" class="sr-only section-checkbox" disabled>
          <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-xl" style="background:var(--surface-raised);color:var(--text-muted)"><i class="fa-solid fa-file-lines text-xs"></i></span>
          <span class="flex-1 min-w-0">
            <span class="block text-sm font-semibold" style="color:var(--text-muted)">Preliminary</span>
            <span class="block text-xs" style="color:var(--text-muted)">Title page, approval sheet, TOC</span>
          </span>
          <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" style="background:var(--surface-raised);color:var(--text-muted)">Soon</span>
        </div>

        <div class="section-card section-card--selected relative flex cursor-pointer items-start gap-3 rounded-2xl px-4 py-3 transition-all"
             data-value="chapters" style="border:2px solid var(--accent);background:var(--accent-subtle)">
          <input type="checkbox" name="sections_m[]" value="chapters" class="sr-only section-checkbox" checked>
          <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-xl" style="background:var(--accent-subtle-strong);color:var(--accent)"><i class="fa-solid fa-book-open text-xs"></i></span>
          <span class="flex-1 min-w-0 pr-1">
            <span class="block text-sm font-semibold leading-snug" style="color:var(--text-primary)">Chapter 1 – References</span>
            <span class="block text-xs mt-0.5" style="color:var(--text-secondary)">Chapters, headings, body text, figures</span>
          </span>
          <span class="section-card__check mt-0.5 shrink-0 flex h-5 w-5 items-center justify-center rounded-full text-white" style="background:var(--accent)">
            <i class="fa-solid fa-check text-[10px]"></i>
          </span>
        </div>

        <div class="section-card section-card--disabled relative flex items-start gap-3 rounded-2xl border px-4 py-3 cursor-not-allowed select-none"
             data-value="appendices" style="border-color:var(--border);opacity:.45">
          <input type="checkbox" name="sections_m[]" value="appendices" class="sr-only section-checkbox" disabled>
          <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-xl" style="background:var(--surface-raised);color:var(--text-muted)"><i class="fa-solid fa-paperclip text-xs"></i></span>
          <span class="flex-1 min-w-0">
            <span class="block text-sm font-semibold" style="color:var(--text-muted)">Appendices</span>
            <span class="block text-xs" style="color:var(--text-muted)">Appendix headings, labels, CV</span>
          </span>
          <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" style="background:var(--surface-raised);color:var(--text-muted)">Soon</span>
        </div>

      </div>
    </div>

    <div>
      <button type="button" id="mobile-rules-toggle"
        class="w-full flex items-center justify-between rounded-xl px-1 py-1.5 cursor-pointer">
        <p class="text-[10px] font-bold uppercase tracking-[0.18em]" style="color:var(--text-muted)">
          <i class="fa-solid fa-gear mr-1"></i> Advanced Rules
        </p>
        <i class="fa-solid fa-chevron-down rules-chevron text-xs" style="color:var(--text-muted)"></i>
      </button>
      <div class="rules-body" id="mobile-rules-body">
        <div class="space-y-2 pt-2">
          <?php foreach ($rules as [$val, $icon, $label]): ?>
          <label class="tf-rule-row flex items-center gap-3 rounded-xl border px-3 py-3 cursor-pointer transition-colors"
                 style="background:var(--surface-raised);border-color:var(--border)">
            <input type="checkbox" name="rules_m[]" value="<?= $val ?>" checked class="sr-only rule-checkbox">
            <span class="rule-toggle-dot flex h-4 w-4 shrink-0 items-center justify-center rounded-full text-white transition" style="background:var(--accent)">
              <i class="fa-solid fa-check text-[8px]"></i>
            </span>
            <i class="fa-solid <?= $icon ?> text-xs w-3.5 text-center shrink-0" style="color:var(--text-muted)"></i>
            <span class="text-sm font-medium" style="color:var(--text-secondary)"><?= $label ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="shrink-0 border-t px-5 py-4" style="border-color:var(--border);background:var(--surface)">
    <button type="button" id="apply-and-close"
      class="w-full rounded-2xl py-3.5 text-sm font-bold text-white transition active:scale-[0.98]"
      style="background:var(--accent)">
      <i class="fa-solid fa-check mr-2"></i> Apply &amp; Close
    </button>
  </div>
</div>


<div id="preview-backdrop" aria-hidden="true">
  <div id="preview-modal"
    style="background:var(--surface)">

    
    <div class="px-5 pt-4 pb-2 shrink-0">
      <div class="mx-auto mb-3 h-1 w-10 rounded-full" style="background:var(--border)"></div>
      <div class="flex items-center justify-between">
        <div>
          <p class="text-[10px] font-bold uppercase tracking-[0.2em]" style="color:var(--accent)">
            <i class="fa-solid fa-scroll mr-1"></i> Template Rules
          </p>
          <h2 class="text-xl font-bold mt-0.5" style="color:var(--text-primary)">Formatting Guide</h2>
        </div>
        <button id="close-preview-btn"
          class="flex h-8 w-8 items-center justify-center rounded-full transition"
          style="background:var(--surface-raised);color:var(--text-secondary)">
          <i class="fa-solid fa-xmark text-sm"></i>
        </button>
      </div>
      <p class="mt-1 text-sm" style="color:var(--text-muted)">Rules applied to your manuscript.</p>
    </div>

    
    <div class="flex-1 overflow-y-auto px-5 pb-4 space-y-2.5 mt-2">
      <?php
        $preview_rules = [
          ['fa-font',          'Font & Size',  'Garamond — 14pt titles, 13pt headings, 12pt body, 11pt references.'],
          ['fa-text-height',   'Line Spacing', 'Double-spaced body text; single-spaced captions and references.'],
          ['fa-hashtag',       'Pagination',   'Chapter labels trigger page breaks automatically.'],
          ['fa-indent',        'Indentation',  'First-line indent 1.27 cm for all body paragraphs.'],
          ['fa-image',         'Captions',     'Centered figure captions; left-aligned table captions — both end with a period.'],
          ['fa-table',         'Tables',       'Title above; double outer borders; header row separator; auto full-width.'],
          ['fa-book-bookmark', 'References',   'Garamond 11pt, 1.0× spacing, hanging indent 1.27 cm.'],
        ];
        foreach ($preview_rules as [$icon, $title, $desc]):
      ?>
      <div class="rounded-2xl border p-4" style="background:var(--surface-raised);border-color:var(--border)">
        <div class="flex items-center gap-2.5 mb-1">
          <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-xl" style="background:var(--accent-subtle-strong)">
            <i class="fa-solid <?= $icon ?> text-xs" style="color:var(--accent)"></i>
          </span>
          <h3 class="text-sm font-semibold" style="color:var(--text-primary)"><?= $title ?></h3>
        </div>
        <p class="text-xs mt-1 pl-[2.375rem]" style="color:var(--text-muted)"><?= $desc ?></p>
      </div>
      <?php endforeach; ?>
    </div>

    
    <div class="shrink-0 border-t px-5 py-4" style="border-color:var(--border);background:var(--surface)">
      <button type="button" id="close-preview-footer-btn"
        class="w-full rounded-2xl py-3.5 text-sm font-bold transition active:scale-[0.98]"
        style="background:var(--surface-raised);color:var(--text-secondary)">
        <i class="fa-solid fa-xmark mr-2"></i> Close
      </button>
    </div>
  </div>
</div>