(function () {
  const section = document.getElementById("formatter-app");
  const saved = localStorage.getItem("thesis_theme") || "light";
  let isDark = saved === "dark";
  const desktopToggle = document.getElementById("desktop-toggle");
  const desktopIcon = document.getElementById("desktop-theme-icon");
  const mobileSideBtn = document.getElementById("mobile-theme-btn");
  const mobileSideBtnIcon = document.getElementById("mobile-theme-icon");
  const mobileSideBtnLbl = document.getElementById("mobile-theme-label");
  const mobileSheetToggle = document.getElementById("mobile-sheet-toggle");
  const mobileSheetIcon = document.getElementById("mobile-sheet-theme-icon");
  function applyTheme(dark) {
    isDark = dark;
    const theme = dark ? "dark" : "light";
    section.setAttribute("data-theme", theme);
    document.body.setAttribute("data-theme", theme);
    localStorage.setItem("thesis_theme", theme);
    [desktopToggle, mobileSheetToggle].forEach((t) => {
      if (!t) return;
      t.classList.toggle("on", dark);
      t.setAttribute("aria-checked", String(dark));
    });
    const iconHtml = dark
      ? '<i class="fa-solid fa-moon fa-lg text-blue-400"></i>'
      : '<i class="fa-solid fa-sun fa-lg text-amber-500"></i>';
    const iconHtmlSm = dark
      ? '<i class="fa-solid fa-moon fa-sm text-blue-400"></i>'
      : '<i class="fa-solid fa-sun fa-sm text-amber-500"></i>';
    if (desktopIcon) desktopIcon.innerHTML = iconHtml;
    if (mobileSheetIcon) mobileSheetIcon.innerHTML = iconHtml;
    if (mobileSideBtnIcon) mobileSideBtnIcon.innerHTML = iconHtmlSm;
    if (mobileSideBtnLbl)
      mobileSideBtnLbl.textContent = dark ? "Dark" : "Light";
  }
  applyTheme(isDark);
  function toggleTheme() {
    applyTheme(!isDark);
  }
  desktopToggle?.addEventListener("click", toggleTheme);
  desktopToggle?.addEventListener("keydown", (e) => {
    if (e.key === " " || e.key === "Enter") {
      e.preventDefault();
      toggleTheme();
    }
  });
  mobileSideBtn?.addEventListener("click", toggleTheme);
  mobileSheetToggle?.addEventListener("click", toggleTheme);
  mobileSheetToggle?.addEventListener("keydown", (e) => {
    if (e.key === " " || e.key === "Enter") {
      e.preventDefault();
      toggleTheme();
    }
  });
  function syncMobileOptionsToDesktopForm() {
    const pairs = [
      ['input[name="sections_m[]"]', 'input[name="sections[]"]'],
      ['input[name="rules_m[]"]', 'input[name="rules[]"]'],
    ];

    pairs.forEach(([mobileSelector, desktopSelector]) => {
      const mobileInputs = Array.from(
        document.querySelectorAll(mobileSelector),
      );
      const desktopInputs = Array.from(
        document.querySelectorAll(desktopSelector),
      );

      mobileInputs.forEach((mobileInput) => {
        const desktopMatch = desktopInputs.find(
          (d) => d.value === mobileInput.value,
        );
        if (desktopMatch) {
          desktopMatch.checked = mobileInput.checked;
        }
      });
    });
  }
  function initAccordion(btnId, bodyId) {
    const btn = document.getElementById(btnId);
    const body = document.getElementById(bodyId);
    if (!btn || !body) return;
    const chevron = btn.querySelector(".rules-chevron");
    let open = !0;
    btn.addEventListener("click", () => {
      open = !open;
      body.classList.toggle("collapsed", !open);
      chevron?.classList.toggle("rotated", !open);
    });
  }
  initAccordion("desktop-rules-toggle", "desktop-rules-body");
  initAccordion("mobile-rules-toggle", "mobile-rules-body");
  const openOptionsBtn = document.getElementById("open-options-btn");
  const mobileBackdrop = document.getElementById("mobile-modal-backdrop");
  const mobileSheet = document.getElementById("mobile-options-sheet");
  const closeMobileSheet = document.getElementById("close-mobile-sheet");
  const applyAndClose = document.getElementById("apply-and-close");
  function openMobileSheet() {
    mobileBackdrop.classList.remove("hidden");
    mobileSheet.classList.remove("hidden", "closing");
    mobileSheet.classList.add("modal-sheet");
    document.body.style.overflow = "hidden";
  }
  function closeMobileSheetFn() {
    mobileSheet.classList.add("closing");
    setTimeout(() => {
      mobileSheet.classList.add("hidden");
      mobileSheet.classList.remove("closing", "modal-sheet");
      mobileBackdrop.classList.add("hidden");
      document.body.style.overflow = "";
    }, 280);
  }
  openOptionsBtn?.addEventListener("click", openMobileSheet);
  closeMobileSheet?.addEventListener("click", closeMobileSheetFn);
  applyAndClose?.addEventListener("click", () => {
    syncMobileOptionsToDesktopForm();
    closeMobileSheetFn();
  });
  mobileBackdrop?.addEventListener("click", closeMobileSheetFn);
  const openPreviewBtn = document.getElementById("open-preview-btn");
  const previewBackdrop = document.getElementById("preview-backdrop");
  const previewModal = document.getElementById("preview-modal");
  const closePreviewBtn = document.getElementById("close-preview-btn");
  function openPreview() {
    previewBackdrop.classList.remove("hidden");
    previewBackdrop.setAttribute("aria-hidden", "false");
    document.body.style.overflow = "hidden";
  }
  function closePreview() {
    previewModal.classList.add("closing");
    setTimeout(() => {
      previewBackdrop.classList.add("hidden");
      previewModal.classList.remove("closing");
      previewBackdrop.setAttribute("aria-hidden", "true");
      document.body.style.overflow = "";
    }, 280);
  }
  openPreviewBtn?.addEventListener("click", openPreview);
  closePreviewBtn?.addEventListener("click", closePreview);
  previewBackdrop?.addEventListener("click", (e) => {
    if (e.target === previewBackdrop) closePreview();
  });
  const uploadPrompt = document.getElementById("upload-prompt");
  const fileUploadedState = document.getElementById("file-uploaded-state");
  const fileNameDisplay = document.getElementById("file-name-display");
  const fanFilename = document.getElementById("fan-filename");
  document.querySelectorAll("#manuscript").forEach((input) => {
    input.addEventListener("change", () => {
      const file = input.files?.[0];
      if (!file) return;
      document.querySelectorAll("#manuscript").forEach((other) => {
        if (other !== input) {
          const dt = new DataTransfer();
          dt.items.add(file);
          other.files = dt.files;
        }
      });
      showUploadedState(file.name);
    });
  });
  function showUploadedState(name) {
    uploadPrompt.classList.add("hidden");
    fileUploadedState.classList.remove("hidden");
    if (fileNameDisplay) fileNameDisplay.textContent = name;
    if (fanFilename) fanFilename.textContent = name;
    const dz = document.getElementById("drop-zone");
    if (dz) {
      dz.classList.remove("border-dashed", "border-blue-200", "bg-blue-50/50");
      dz.classList.add(
        "border-solid",
        "border-emerald-300",
        "bg-emerald-50/40",
      );
    }
  }
  const dropZone = document.getElementById("drop-zone");
  const anyManuscript = document.querySelector("#manuscript");
  if (dropZone && anyManuscript) {
    dropZone.addEventListener("dragover", (e) => {
      e.preventDefault();
      dropZone.style.borderColor = "#3b82f6";
    });
    dropZone.addEventListener("dragleave", () => {
      dropZone.style.borderColor = "";
    });
    dropZone.addEventListener("drop", (e) => {
      e.preventDefault();
      dropZone.style.borderColor = "";
      const file = e.dataTransfer?.files?.[0];
      if (file?.name.endsWith(".docx")) {
        document.querySelectorAll("#manuscript").forEach((inp) => {
          const dt = new DataTransfer();
          dt.items.add(file);
          inp.files = dt.files;
        });
        showUploadedState(file.name);
      }
    });
  }
})();
