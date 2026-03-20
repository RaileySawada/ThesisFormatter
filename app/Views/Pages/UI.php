<section id="formatter-app" data-theme="light" class="relative min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 p-4 sm:p-6 overflow-x-hidden">
  <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
    <div class="blob-blue   absolute -top-24 -left-16 h-72 w-72 rounded-full bg-blue-200/30 blur-3xl"></div>
    <div class="blob-indigo absolute top-1/3 -right-20 h-80 w-80 rounded-full bg-indigo-200/30 blur-3xl"></div>
    <div class="blob-sky    absolute bottom-0 left-1/3 h-64 w-64 rounded-full bg-sky-200/20 blur-3xl"></div>
  </div>

  <header class="relative mb-4 flex items-center justify-between lg:hidden">
    <div>
      <p class="tf-label text-[10px] font-bold uppercase tracking-[0.22em] text-blue-700">
        <i class="fa-solid fa-graduation-cap mr-1"></i>Thesis Formatter
      </p>
    </div>
    <div class="flex items-center gap-2">
      <button id="mobile-theme-btn" aria-label="Toggle dark mode"
        class="tf-topbar-theme-btn flex items-center gap-2 rounded-xl border border-slate-200 bg-white/90 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm">
        <span id="mobile-theme-icon" class="theme-icon-wrap"><i class="fa-solid fa-sun fa-sm text-amber-500"></i></span>
        <span id="mobile-theme-label">Light</span>
      </button>
      <button id="open-options-btn" aria-label="Open options"
        class="flex items-center gap-1.5 rounded-xl bg-blue-600 px-3 py-2 text-xs font-bold text-white shadow-md active:scale-95 transition-transform">
        <i class="fa-solid fa-sliders text-sm"></i> Options
      </button>
    </div>
  </header>

  <form action="" method="POST" enctype="multipart/form-data" class="relative mx-auto flex w-full max-w-7xl gap-6 items-start">
    <aside class="tf-card sticky hidden lg:flex lg:flex-col w-full max-w-[295px] shrink-0
                  rounded-3xl border border-slate-200/70 bg-white/95 p-5
                  shadow-xl shadow-slate-200/40 backdrop-blur gap-4">
      <div>
        <p class="tf-label text-xs font-semibold uppercase tracking-[0.2em] text-blue-700">
          <i class="fa-solid fa-wand-magic-sparkles mr-1"></i> Formatter Options
        </p>
        <h2 class="tf-heading mt-1.5 text-2xl font-bold text-slate-800">Formatting Scope</h2>
        <p class="tf-subtext mt-1.5 text-sm leading-6 text-slate-500">
          Select which sections of the manuscript will be aligned to the master thesis template.
        </p>
      </div>

      <div class="tf-appearance-box rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
        <p class="tf-muted mb-2 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
          <i class="fa-solid fa-palette mr-1"></i> Appearance
        </p>
        <div class="flex items-center justify-between gap-4">
          <div class="flex items-center gap-2">
            <span id="desktop-theme-icon" class="theme-icon-wrap"><i class="fa-solid fa-sun fa-lg text-amber-500"></i></span>
            <p class="tf-heading text-sm font-semibold text-slate-800">Dark Mode</p>
          </div>
          <div id="desktop-toggle" class="toggle-track" role="switch" aria-checked="false" tabindex="0">
            <div class="toggle-thumb"></div>
          </div>
        </div>
      </div>

      <div>
        <p class="tf-muted mb-2.5 text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
          <i class="fa-solid fa-layer-group mr-1"></i> Main Sections
        </p>
        <div class="space-y-2">
          <label class="tf-section-label flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 px-4 py-3 transition hover:border-blue-300 hover:bg-blue-50/60">
            <input type="checkbox" name="sections[]" value="preliminary" class="mt-0.5 h-4 w-4 rounded">
            <span>
              <span class="tf-heading block text-sm font-semibold text-slate-800">Preliminary</span>
              <span class="tf-subtext block text-xs text-slate-500">Title page, approval sheet, abstract, TOC</span>
            </span>
          </label>
          <label class="tf-section-label flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 px-4 py-3 transition hover:border-blue-300 hover:bg-blue-50/60">
            <input type="checkbox" name="sections[]" value="chapters" class="mt-0.5 h-4 w-4 rounded">
            <span>
              <span class="tf-heading block text-sm font-semibold text-slate-800">Chapter 1 – References</span>
              <span class="tf-subtext block text-xs text-slate-500">Chapters, headings, body text, figures, captions</span>
            </span>
          </label>
          <label class="tf-section-label flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 px-4 py-3 transition hover:border-blue-300 hover:bg-blue-50/60">
            <input type="checkbox" name="sections[]" value="appendices" class="mt-0.5 h-4 w-4 rounded">
            <span>
              <span class="tf-heading block text-sm font-semibold text-slate-800">Appendices</span>
              <span class="tf-subtext block text-xs text-slate-500">Appendix headings, continuation labels, CV</span>
            </span>
          </label>
        </div>
      </div>

      <div>
        <button type="button" id="desktop-rules-toggle"
          class="tf-rules-header w-full flex items-center justify-between rounded-xl px-1 py-1.5 transition hover:bg-slate-50 cursor-pointer">
          <p class="tf-muted text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">
            <i class="fa-solid fa-gear mr-1"></i> Advanced Rules
          </p>
          <i class="fa-solid fa-chevron-down rules-chevron tf-muted text-xs text-slate-400"></i>
        </button>
        <div class="rules-body" id="desktop-rules-body">
          <div>
            <div class="space-y-1.5 pt-2">
              <?php foreach ($rules as [$val, $icon, $label]): ?>
              <label class="tf-rule-row flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-2.5">
                <input type="checkbox" name="rules[]" value="<?= $val ?>" checked class="h-4 w-4 rounded">
                <i class="fa-solid <?= $icon ?> text-xs text-slate-400 w-3.5 text-center shrink-0"></i>
                <span class="text-sm text-slate-700"><?= $label ?></span>
              </label>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>
    </aside>

    <main class="min-w-0 flex-1 space-y-6">
      <div class="tf-card rounded-3xl border border-slate-200/70 bg-white/95 p-5 shadow-xl shadow-slate-200/40 backdrop-blur sm:p-8">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
          <div class="max-w-2xl">
            <p class="tf-label text-[10px] font-semibold uppercase tracking-[0.22em] text-blue-700 sm:text-xs">
              <i class="fa-solid fa-graduation-cap mr-1"></i> Thesis Formatter
            </p>
            <p class="tf-body-text mt-3 text-sm leading-7 text-slate-600 sm:text-base">
              Upload a manuscript and apply the selected formatting rules for preliminary pages, chapters, references, figures, tables, captions, and appendices.
            </p>
          </div>
          <button type="button" id="open-preview-btn"
            class="tf-preview-btn inline-flex shrink-0 items-center justify-center gap-2 rounded-2xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
            <i class="fa-solid fa-eye text-sm opacity-70"></i>
            Preview Template Rules
          </button>
        </div>

        <div id="drop-zone" class="drop-zone mt-6 rounded-3xl border-2 border-dashed border-blue-200 bg-blue-50/50 p-6 sm:p-8 transition">
          <div id="upload-prompt">
            <label for="manuscript" class="block cursor-pointer">
              <div class="flex flex-col items-center justify-center text-center">
                <div class="tf-upload-icon-box flex h-14 w-14 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-slate-200 sm:h-16 sm:w-16">
                  <i class="fa-solid fa-cloud-arrow-up text-2xl text-blue-600 sm:text-3xl"></i>
                </div>
                <h3 class="tf-heading mt-4 text-base font-semibold text-slate-800 sm:text-lg">Upload manuscript</h3>
                <p class="tf-subtext mt-2 text-sm text-slate-600">
                  Drag &amp; drop your <span class="font-semibold text-slate-800">.docx</span> file, or click to browse.
                </p>
                <p class="tf-muted mt-1 text-xs text-slate-500">Microsoft Word Document (.docx)</p>
              </div>
              <input id="manuscript" name="manuscript" type="file" accept=".docx" class="hidden">
            </label>
          </div>

          <div id="file-uploaded-state" class="hidden">
            <div class="flex flex-col items-center gap-4">
              <div class="file-cards-wrap" title="Hover to inspect">
                <div class="file-card">
                  <i class="fa-solid fa-file-word text-2xl text-blue-500"></i>
                  <span class="tf-muted text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Document</span>
                </div>

                <div class="file-card">
                  <i class="fa-solid fa-file-lines text-2xl text-indigo-500"></i>
                  <span id="fan-filename" class="text-[10px] font-bold text-slate-600 px-2 text-center leading-tight max-w-full truncate" style="max-width:100px"></span>
                </div>
                
                <div class="file-card">
                  <i class="fa-solid fa-circle-check text-2xl text-emerald-500"></i>
                  <span class="tf-muted text-[10px] font-semibold text-slate-400 uppercase tracking-wider">Ready</span>
                </div>
              </div>

              <div class="text-center">
                <p class="tf-heading text-sm font-bold text-slate-800">
                  <i class="fa-solid fa-circle-check text-emerald-500 mr-1.5"></i>File ready
                </p>
                <p id="file-name-display" class="tf-subtext mt-0.5 text-xs text-slate-500 truncate max-w-xs"></p>
              </div>

              <label for="manuscript" class="cursor-pointer">
                <span class="inline-flex items-center gap-1.5 rounded-xl border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 transition">
                  <i class="fa-solid fa-arrow-up-from-bracket text-xs"></i> Change file
                </span>
                <input id="manuscript" name="manuscript" type="file" accept=".docx" class="hidden">
              </label>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <button type="submit" name="action" value="format"
            class="w-full rounded-2xl bg-blue-600 px-5 py-3.5 text-sm font-bold text-white
                   hover:bg-blue-700 active:scale-[0.98] transition shadow-md shadow-blue-600/20">
            <i class="fa-solid fa-bolt mr-2"></i> Apply Formatting
          </button>
        </div>
      </div>

      <div class="grid gap-6 xl:grid-cols-2">
        <div class="tf-card rounded-3xl border border-slate-200/70 bg-white/95 p-6 shadow-xl shadow-slate-200/40 backdrop-blur">
          <h2 class="tf-heading text-lg font-bold text-slate-900 sm:text-xl">
            <i class="fa-solid fa-list-check mr-2 text-blue-600"></i>Formatting Coverage
          </h2>
          <p class="tf-subtext mt-2 text-sm text-slate-500">
            The system normalizes selected document parts according to the master DOCX template.
          </p>
          <div class="mt-5 space-y-3">
            <div class="tf-coverage-item rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
              <h3 class="tf-heading text-sm font-semibold text-slate-800">
                <i class="fa-solid fa-file-circle-check mr-1.5 text-blue-500"></i>Preliminary Pages
              </h3>
              <p class="tf-subtext mt-1 text-sm text-slate-500">Title page, approval sheet, abstract, acknowledgement, and front matter layout.</p>
            </div>
            <div class="tf-coverage-item rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
              <h3 class="tf-heading text-sm font-semibold text-slate-800">
                <i class="fa-solid fa-book-open mr-1.5 text-blue-500"></i>Chapters and References
              </h3>
              <p class="tf-subtext mt-1 text-sm text-slate-500">Chapter titles, headings, body text, figures, tables, captions, legends, and references.</p>
            </div>
            <div class="tf-coverage-item rounded-2xl bg-slate-50 p-4 ring-1 ring-slate-200">
              <h3 class="tf-heading text-sm font-semibold text-slate-800">
                <i class="fa-solid fa-paperclip mr-1.5 text-blue-500"></i>Appendices
              </h3>
              <p class="tf-subtext mt-1 text-sm text-slate-500">Appendix labels, continuation blocks, user manual, and curriculum vitae.</p>
            </div>
          </div>
        </div>

        <div class="tf-status-card rounded-3xl border border-slate-700 bg-slate-900 p-6 text-white shadow-xl">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">
            <i class="fa-solid fa-circle-info mr-1"></i> Current Status
          </p>
          <h2 class="mt-2 text-lg font-bold sm:text-xl">Ready for upload</h2>
          <p class="mt-3 text-sm leading-6 text-slate-300">
            No document has been processed yet. Upload a file and choose the formatting scope from the options panel.
          </p>
          <div class="mt-6 grid grid-cols-2 gap-3">
            <div class="rounded-2xl bg-white/10 p-4">
              <p class="text-xs uppercase tracking-[0.15em] text-slate-400">
                <i class="fa-solid fa-file-word mr-1"></i> Accepted File
              </p>
              <p class="mt-2 text-sm font-semibold">.DOCX</p>
            </div>
            <div class="rounded-2xl bg-white/10 p-4">
              <p class="text-xs uppercase tracking-[0.15em] text-slate-400">
                <i class="fa-solid fa-microchip mr-1"></i> Mode
              </p>
              <p class="mt-2 text-sm font-semibold">Template Matching</p>
            </div>
          </div>
        </div>
      </div>
    </main>
  </form>
</section>

<div id="mobile-modal-backdrop" class="modal-backdrop fixed inset-0 z-50 bg-black/50 hidden" aria-hidden="true"></div>

<div id="mobile-options-sheet" role="dialog" aria-modal="true" aria-label="Formatter Options"
  class="tf-modal-bg fixed bottom-0 left-0 right-0 z-50 hidden rounded-t-[28px] bg-white shadow-2xl max-h-[92dvh] flex flex-col">
  <div class="px-5 pt-4 pb-2 shrink-0">
    <div class="handle-bar"></div>
    <div class="flex items-center justify-between">
      <div>
        <p class="tf-label text-[10px] font-bold uppercase tracking-[0.2em] text-blue-700">
          <i class="fa-solid fa-sliders mr-1"></i> Formatter Options
        </p>
        <h2 class="tf-heading text-xl font-bold text-slate-800 mt-0.5">Formatting Scope</h2>
      </div>
      <button id="close-mobile-sheet" aria-label="Close"
        class="tf-modal-close-btn flex h-8 w-8 items-center justify-center rounded-full bg-slate-100 text-slate-500 active:scale-95 transition">
        <i class="fa-solid fa-xmark text-sm"></i>
      </button>
    </div>
    <p class="tf-subtext mt-1 text-sm text-slate-500">Select sections and rules to apply.</p>
  </div>

  <div class="modal-scroll flex-1 overflow-y-auto px-5 pb-4 space-y-4">
    <div>
      <p class="tf-muted mb-2 text-xs font-bold uppercase tracking-[0.18em] text-slate-400">
        <i class="fa-solid fa-layer-group mr-1"></i> Main Sections
      </p>
      <div class="space-y-2">
        <label class="tf-section-label flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition">
          <input type="checkbox" name="sections_m[]" value="preliminary" class="mt-0.5 h-4 w-4 rounded">
          <span>
            <span class="tf-heading block text-sm font-semibold text-slate-800">Preliminary</span>
            <span class="tf-subtext block text-xs text-slate-500">Title page, approval sheet, abstract, TOC</span>
          </span>
        </label>
        <label class="tf-section-label flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition">
          <input type="checkbox" name="sections_m[]" value="chapters" class="mt-0.5 h-4 w-4 rounded">
          <span>
            <span class="tf-heading block text-sm font-semibold text-slate-800">Chapter 1 – References</span>
            <span class="tf-subtext block text-xs text-slate-500">Chapters, headings, body text, figures, captions</span>
          </span>
        </label>
        <label class="tf-section-label flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition">
          <input type="checkbox" name="sections_m[]" value="appendices" class="mt-0.5 h-4 w-4 rounded">
          <span>
            <span class="tf-heading block text-sm font-semibold text-slate-800">Appendices</span>
            <span class="tf-subtext block text-xs text-slate-500">Appendix headings, continuation labels, CV</span>
          </span>
        </label>
      </div>
    </div>

    <div>
      <button type="button" id="mobile-rules-toggle"
        class="tf-rules-header w-full flex items-center justify-between rounded-xl px-1 py-1.5 transition hover:bg-slate-50 cursor-pointer">
        <p class="tf-muted text-xs font-bold uppercase tracking-[0.18em] text-slate-400">
          <i class="fa-solid fa-gear mr-1"></i> Advanced Rules
        </p>
        <i class="fa-solid fa-chevron-down rules-chevron tf-muted text-xs text-slate-400"></i>
      </button>
      <div class="rules-body" id="mobile-rules-body">
        <div>
          <div class="space-y-2 pt-2">
            <?php foreach ($rules as [$val, $icon, $label]): ?>
            <label class="tf-rule-row flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 transition">
              <input type="checkbox" name="rules_m[]" value="<?= $val ?>" checked class="h-4 w-4 rounded">
              <i class="fa-solid <?= $icon ?> text-xs text-slate-400 w-3.5 text-center shrink-0"></i>
              <span class="text-sm font-medium text-slate-700"><?= $label ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="tf-modal-footer shrink-0 border-t border-slate-200 px-5 py-4 bg-white">
    <button type="button" id="apply-and-close"
      class="w-full rounded-2xl bg-blue-600 py-3.5 text-sm font-bold text-white hover:bg-blue-700 active:scale-[0.98] transition">
      <i class="fa-solid fa-check mr-2"></i> Apply &amp; Close
    </button>
  </div>
</div>

<div id="preview-backdrop" class="fixed inset-0 z-50 hidden bg-black/50 flex items-end sm:items-center justify-center" aria-hidden="true">
  <div id="preview-modal"
    class="tf-modal-bg modal-sheet w-full sm:max-w-lg rounded-t-[28px] sm:rounded-3xl bg-white shadow-2xl p-6 max-h-[80dvh] flex flex-col">
    <div class="flex items-center justify-between mb-4 shrink-0">
      <h2 class="tf-heading text-lg font-bold text-slate-800">
        <i class="fa-solid fa-scroll mr-2 text-blue-600"></i>Template Rules Preview
      </h2>
      <button id="close-preview-btn"
        class="tf-modal-close-btn h-8 w-8 flex items-center justify-center rounded-full bg-slate-100 text-slate-500">
        <i class="fa-solid fa-xmark text-sm"></i>
      </button>
    </div>
    <div class="modal-scroll flex-1 overflow-y-auto space-y-3 px-1 pb-1">
      <?php
        $preview_rules = [
          ['fa-font',           'Font & Size',  'Garamond, 12pt throughout all sections.'],
          ['fa-text-height',    'Line Spacing', 'Double-spaced body text; single-spaced captions.'],
          ['fa-hashtag',        'Pagination',   'Roman numerals for preliminary; Arabic for chapters.'],
          ['fa-indent',         'Indentation',  'First-line indent 0.5-inch for all body paragraphs.'],
          ['fa-image',          'Captions',     'Bold label (Figure 1.) followed by descriptive text.'],
          ['fa-table',          'Tables',       'Title above table; notes below; no vertical borders.'],
          ['fa-book-bookmark',  'References',   'IEEE hanging indent 1.27-cm.'],
        ];
        foreach ($preview_rules as [$icon, $title, $desc]):
      ?>
      <div class="tf-preview-item rounded-2xl bg-slate-50 p-4 border border-slate-200">
        <div class="flex items-center gap-2 mb-1">
          <i class="fa-solid <?= $icon ?> text-sm text-blue-500 w-4 text-center shrink-0"></i>
          <h3 class="text-sm font-semibold text-slate-800"><?= $title ?></h3>
        </div>
        <p class="text-xs text-slate-500 pl-6"><?= $desc ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>