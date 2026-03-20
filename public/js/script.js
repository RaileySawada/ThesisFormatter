(function () {
  "use strict";
  const app = document.getElementById("formatter-app");
  const saved = localStorage.getItem("thesis_theme") || "light";
  let isDark = saved === "dark";
  const desktopToggle = document.getElementById("desktop-toggle");
  const desktopIcon = document.getElementById("desktop-theme-icon");
  const mobileSideBtn = document.getElementById("mobile-theme-btn");
  const mobileSideBtnIcon = document.getElementById("mobile-theme-icon");
  const mobileSideBtnLbl = document.getElementById("mobile-theme-label");
  function applyTheme(dark) {
    isDark = dark;
    app?.setAttribute("data-theme", dark ? "dark" : "light");
    document.body.setAttribute("data-theme", dark ? "dark" : "light");
    localStorage.setItem("thesis_theme", dark ? "dark" : "light");
    desktopToggle?.classList.toggle("on", dark);
    desktopToggle?.setAttribute("aria-checked", String(dark));
    const moon = '<i class="fa-solid fa-moon fa-lg" style="color:#60a5fa"></i>';
    const sun = '<i class="fa-solid fa-sun fa-lg" style="color:#f59e0b"></i>';
    const moonSm =
      '<i class="fa-solid fa-moon fa-sm" style="color:#60a5fa"></i>';
    const sunSm = '<i class="fa-solid fa-sun fa-sm" style="color:#f59e0b"></i>';
    if (desktopIcon) desktopIcon.innerHTML = dark ? moon : sun;
    if (mobileSideBtnIcon) mobileSideBtnIcon.innerHTML = dark ? moonSm : sunSm;
    if (mobileSideBtnLbl)
      mobileSideBtnLbl.textContent = dark ? "Dark" : "Light";
  }
  applyTheme(isDark);
  desktopToggle?.addEventListener("click", () => applyTheme(!isDark));
  desktopToggle?.addEventListener("keydown", (e) => {
    if (e.key === " " || e.key === "Enter") {
      e.preventDefault();
      applyTheme(!isDark);
    }
  });
  mobileSideBtn?.addEventListener("click", () => applyTheme(!isDark));
  function initSectionCards(containerSel) {
    const container = document.querySelector(containerSel);
    if (!container) return;
    container
      .querySelectorAll(".section-card:not(.section-card--disabled)")
      .forEach((card) => {
        card.addEventListener("click", () => {
          const chk = card.querySelector(".section-checkbox");
          if (!chk || chk.disabled) return;
          if (card.classList.contains("section-card--selected")) {
            const selected = container.querySelectorAll(
              ".section-card--selected",
            );
            if (selected.length <= 1) {
              card.style.outline = "2px solid #ef4444";
              setTimeout(() => (card.style.outline = ""), 600);
              return;
            }
          }
          chk.checked = !chk.checked;
          setCardState(card, chk.checked);
          updateSelectedBadge();
          updateStatusPanel();
          syncCards(card.dataset.value, chk.checked);
        });
      });
  }
  function setCardState(card, selected) {
    card.classList.toggle("section-card--selected", selected);
    if (selected) {
      card.style.border = "2px solid var(--accent)";
      card.style.background = "var(--accent-subtle)";
    } else {
      card.style.border = "1px solid var(--border)";
      card.style.background = "";
    }
  }
  function syncCards(value, checked) {
    ["#desktop-section-cards", "#mobile-section-cards"].forEach((sel) => {
      const c = document.querySelector(sel);
      const card = c?.querySelector(`.section-card[data-value="${value}"]`);
      const chk = card?.querySelector(".section-checkbox");
      if (!chk || chk.disabled) return;
      chk.checked = checked;
      setCardState(card, checked);
    });
  }
  initSectionCards("#desktop-section-cards");
  initSectionCards("#mobile-section-cards");
  const labels = {
    preliminary: "Preliminary",
    chapters: "Chapter 1 – References",
    appendices: "Appendices",
  };
  const icons = {
    preliminary: "fa-file-lines",
    chapters: "fa-book-open",
    appendices: "fa-paperclip",
  };
  function updateSelectedBadge() {
    const badge = document.getElementById("selected-section-badge");
    if (!badge) return;
    const selected = [
      ...document.querySelectorAll(
        "#desktop-section-cards .section-checkbox:checked:not(:disabled)",
      ),
    ].map((c) => c.value);
    if (!selected.length) {
      badge.innerHTML =
        '<i class="fa-solid fa-triangle-exclamation text-[10px]"></i> None selected';
      badge.style.background = "rgba(239,68,68,.15)";
      badge.style.color = "#ef4444";
    } else {
      badge.innerHTML = `<i class="fa-solid ${icons[selected[0]] || "fa-layer-group"} text-[10px]"></i> ${selected.map((v) => labels[v] || v).join(", ")}`;
      badge.style.background = "var(--accent-subtle-strong)";
      badge.style.color = "var(--accent)";
    }
  }
  updateSelectedBadge();
  document.querySelectorAll(".rule-checkbox").forEach((chk) => {
    const dot = chk.closest("label")?.querySelector(".rule-toggle-dot");
    if (!dot) return;
    const update = () => {
      dot.style.background = chk.checked ? "var(--accent)" : "var(--border)";
      dot.querySelector("i").style.opacity = chk.checked ? "1" : "0";
    };
    chk.addEventListener("change", () => {
      update();
      updateStatusPanel();
    });
    update();
  });
  function initAccordion(btnId, bodyId) {
    const btn = document.getElementById(btnId);
    const body = document.getElementById(bodyId);
    if (!btn || !body) return;
    const chev = btn.querySelector(".rules-chevron");
    let open = !0;
    btn.addEventListener("click", () => {
      open = !open;
      body.classList.toggle("collapsed", !open);
      chev?.classList.toggle("rotated", !open);
    });
  }
  initAccordion("desktop-rules-toggle", "desktop-rules-body");
  initAccordion("mobile-rules-toggle", "mobile-rules-body");
  const backdrop = document.getElementById("mobile-modal-backdrop");
  const sheet = document.getElementById("mobile-options-sheet");
  const closeSheetBtn = document.getElementById("close-mobile-sheet");
  const applyClose = document.getElementById("apply-and-close");
  const openBtn = document.getElementById("open-options-btn");
  function openSheet() {
    backdrop?.classList.remove("hidden");
    sheet?.classList.remove("hidden", "closing");
    document.body.style.overflow = "hidden";
  }
  function closeSheet() {
    sheet?.classList.add("closing");
    setTimeout(() => {
      sheet?.classList.add("hidden");
      sheet?.classList.remove("closing");
      backdrop?.classList.add("hidden");
      document.body.style.overflow = "";
    }, 280);
  }
  openBtn?.addEventListener("click", openSheet);
  closeSheetBtn?.addEventListener("click", closeSheet);
  backdrop?.addEventListener("click", closeSheet);
  applyClose?.addEventListener("click", () => {
    document.querySelectorAll('input[name="rules_m[]"]').forEach((m) => {
      const d = document.querySelector(
        `input[name="rules[]"][value="${m.value}"]`,
      );
      if (d) d.checked = m.checked;
    });
    closeSheet();
  });
  const prevBackdrop = document.getElementById("preview-backdrop");
  const prevModal = document.getElementById("preview-modal");
  const openPrev = document.getElementById("open-preview-btn");
  const closePrev = document.getElementById("close-preview-btn");
  openPrev?.addEventListener("click", () => {
    prevBackdrop?.classList.remove("hidden");
    document.body.style.overflow = "hidden";
  });
  function closePreview() {
    prevModal?.classList.add("closing");
    setTimeout(() => {
      prevBackdrop?.classList.add("hidden");
      prevModal?.classList.remove("closing");
      document.body.style.overflow = "";
    }, 280);
  }
  closePrev?.addEventListener("click", closePreview);
  prevBackdrop?.addEventListener("click", (e) => {
    if (e.target === prevBackdrop) closePreview();
  });
  const uploadPrompt = document.getElementById("upload-prompt");
  const uploadedState = document.getElementById("file-uploaded-state");
  const fileNameDisplay = document.getElementById("file-name-display");
  const fanFilename = document.getElementById("fan-filename");
  let currentFileName = null;
  function showUploaded(name) {
    currentFileName = name;
    uploadPrompt?.classList.add("hidden");
    uploadedState?.classList.remove("hidden");
    if (fileNameDisplay) fileNameDisplay.textContent = name;
    if (fanFilename) fanFilename.textContent = name;
    const dz = document.getElementById("drop-zone");
    if (dz) {
      dz.style.borderColor = "#10b981";
      dz.style.background = "rgba(16,185,129,.06)";
    }
    updateStatusPanel();
    updateApplyBtn();
  }
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
      showUploaded(file.name);
    });
  });
  const dz = document.getElementById("drop-zone");
  if (dz) {
    dz.addEventListener("dragover", (e) => {
      e.preventDefault();
      dz.classList.add("dragover");
    });
    dz.addEventListener("dragleave", () => dz.classList.remove("dragover"));
    dz.addEventListener("drop", (e) => {
      e.preventDefault();
      dz.classList.remove("dragover");
      const file = e.dataTransfer?.files?.[0];
      if (file?.name.endsWith(".docx")) {
        document.querySelectorAll("#manuscript").forEach((inp) => {
          const dt = new DataTransfer();
          dt.items.add(file);
          inp.files = dt.files;
        });
        showUploaded(file.name);
      }
    });
  }
  document.getElementById("main-form")?.addEventListener("submit", (e) => {
    const anyChecked = document.querySelector(
      "#desktop-section-cards .section-checkbox:checked:not(:disabled)",
    );
    if (!anyChecked) {
      e.preventDefault();
      const badge = document.getElementById("selected-section-badge");
      badge?.scrollIntoView({ behavior: "smooth", block: "center" });
      badge?.animate([{ opacity: 1 }, { opacity: 0.3 }, { opacity: 1 }], {
        duration: 600,
        iterations: 2,
      });
      return;
    }
    const btn = document.getElementById("apply-btn");
    const idle = document.getElementById("apply-btn-idle");
    const loading = document.getElementById("apply-btn-loading");
    if (btn && idle && loading) {
      idle.classList.add("hidden");
      loading.classList.remove("hidden");
      loading.classList.add("inline-flex");
      btn.style.cursor = "wait";
      btn.style.pointerEvents = "none";
    }
    startDownloadPoller();
  });
  function stepDone(stepEl, iconEl, detail) {
    stepEl?.classList.remove("status-step--active", "status-step--inactive");
    stepEl?.classList.add("status-step--done");
    if (iconEl)
      iconEl.innerHTML = '<i class="fa-solid fa-check text-[10px]"></i>';
    if (iconEl) {
      iconEl.style.background = "#3b82f6";
      iconEl.style.color = "#fff";
    }
    if (detail) detail.style.color = "rgba(255,255,255,0.6)";
  }
  function stepActive(stepEl, iconEl) {
    stepEl?.classList.remove("status-step--done", "status-step--inactive");
    stepEl?.classList.add("status-step--active");
    if (iconEl) iconEl.style.background = "rgba(255,255,255,0.25)";
    if (iconEl) iconEl.style.color = "#fff";
  }
  function stepInactive(stepEl, iconEl) {
    stepEl?.classList.remove("status-step--done", "status-step--active");
    stepEl?.classList.add("status-step--inactive");
    if (iconEl) iconEl.style.background = "rgba(255,255,255,0.1)";
  }
  function updateStatusPanel() {
    const elSection = document.getElementById("step-section");
    const elSectionIcon = document.getElementById("step-section-icon");
    const elSectionDet = document.getElementById("step-section-detail");
    const elFile = document.getElementById("step-file");
    const elFileIcon = document.getElementById("step-file-icon");
    const elFileDet = document.getElementById("step-file-detail");
    const elRules = document.getElementById("step-rules");
    const elRulesIcon = document.getElementById("step-rules-icon");
    const elRulesDet = document.getElementById("step-rules-detail");
    const elReady = document.getElementById("step-ready");
    const elReadyIcon = document.getElementById("step-ready-icon");
    const elReadyDet = document.getElementById("step-ready-detail");
    const headline = document.getElementById("status-headline");
    const subtext = document.getElementById("status-subtext");
    const selectedSections = [
      ...document.querySelectorAll(
        "#desktop-section-cards .section-checkbox:checked:not(:disabled)",
      ),
    ].map((c) => c.value);
    const sectionDone = selectedSections.length > 0;
    const fileDone = !!currentFileName;
    const totalRules = document.querySelectorAll(
      "#desktop-rules-body .rule-checkbox",
    ).length;
    const activeRules = document.querySelectorAll(
      "#desktop-rules-body .rule-checkbox:checked",
    ).length;
    const rulesDone = activeRules > 0;
    const allDone = sectionDone && fileDone && rulesDone;
    if (sectionDone) {
      stepDone(elSection, elSectionIcon, elSectionDet);
      if (elSectionDet) {
        elSectionDet.textContent = selectedSections
          .map((v) => labels[v] || v)
          .join(", ");
      }
    } else {
      stepActive(elSection, elSectionIcon);
      if (elSectionDet)
        elSectionDet.textContent = "Choose a scope in the sidebar";
    }
    if (fileDone) {
      stepDone(elFile, elFileIcon, elFileDet);
      if (elFileDet) elFileDet.textContent = currentFileName;
    } else if (sectionDone) {
      stepActive(elFile, elFileIcon);
      if (elFileDet) {
        elFileDet.textContent = "No file chosen yet";
        elFileDet.style.color = "rgba(255,255,255,0.5)";
      }
    } else {
      stepInactive(elFile, elFileIcon);
    }
    if (rulesDone) {
      stepDone(elRules, elRulesIcon, elRulesDet);
      if (elRulesDet)
        elRulesDet.textContent = `${activeRules} of ${totalRules} rules active`;
    } else {
      stepActive(elRules, elRulesIcon);
      if (elRulesDet) {
        elRulesDet.textContent = "No rules enabled";
        elRulesDet.style.color = "rgba(255,255,255,0.5)";
      }
    }
    if (allDone) {
      stepDone(elReady, elReadyIcon, elReadyDet);
      elReady?.classList.remove("status-step--inactive");
      elReady?.style && (elReady.style.opacity = "1");
      if (elReadyDet) elReadyDet.textContent = "Hit 'Apply Formatting' to run";
      if (headline) headline.textContent = "Ready to format!";
      if (subtext) {
        subtext.textContent =
          "All set — click Apply Formatting below to process your manuscript.";
      }
    } else if (fileDone) {
      stepActive(elReady, elReadyIcon);
      stepInactive(elReady, elReadyIcon);
      if (headline) headline.textContent = "Almost there…";
      if (subtext) {
        subtext.textContent = "Check that all steps above are complete.";
      }
    } else {
      stepInactive(elReady, elReadyIcon);
      if (elReadyDet) {
        elReadyDet.textContent = "Complete steps above first";
        elReadyDet.style.color = "rgba(255,255,255,0.4)";
      }
      if (headline) headline.textContent = "Ready for upload";
      if (subtext) {
        subtext.textContent =
          "Select sections on the left, then upload your manuscript.";
      }
    }
  }
  updateStatusPanel();
  (function resetSpinner() {
    const btn = document.getElementById("apply-btn");
    const idle = document.getElementById("apply-btn-idle");
    const loading = document.getElementById("apply-btn-loading");
    if (!btn || !idle || !loading) return;
    idle.classList.remove("hidden");
    loading.classList.add("hidden");
    loading.classList.remove("inline-flex");
    btn.style.cursor = "";
    btn.style.pointerEvents = "";
  })();
  window.addEventListener("pageshow", (e) => {
    if (e.persisted) {
      const btn = document.getElementById("apply-btn");
      const idle = document.getElementById("apply-btn-idle");
      const loading = document.getElementById("apply-btn-loading");
      if (btn && idle && loading) {
        idle.classList.remove("hidden");
        loading.classList.add("hidden");
        loading.classList.remove("inline-flex");
        btn.style.cursor = "";
        btn.style.pointerEvents = "";
      }
      updateApplyBtn();
    }
  });
  function updateApplyBtn() {
    const btn = document.getElementById("apply-btn");
    if (!btn) return;
    const hasFile = !!currentFileName;
    btn.disabled = !hasFile;
  }
  updateApplyBtn();
  function getCookie(name) {
    return (
      document.cookie
        .split("; ")
        .find((r) => r.startsWith(name + "="))
        ?.split("=")[1] ?? null
    );
  }
  function eraseCookie(name) {
    document.cookie = name + "=; Max-Age=0; path=/";
  }
  function resetBtn() {
    const btn = document.getElementById("apply-btn");
    const idle = document.getElementById("apply-btn-idle");
    const loading = document.getElementById("apply-btn-loading");
    if (!btn || !idle || !loading) return;
    idle.classList.remove("hidden");
    loading.classList.add("hidden");
    loading.classList.remove("inline-flex");
    btn.style.cursor = "";
    btn.style.pointerEvents = "";
    updateApplyBtn();
  }
  function showSuccessToast(msg) {
    document.querySelectorAll(".toast-notification").forEach((t) => t.remove());
    const toast = document.createElement("div");
    toast.className =
      "toast-notification success fixed top-6 right-4 z-[100] w-[calc(100%-2rem)] sm:w-96";
    toast.innerHTML = `
      <div class="relative overflow-hidden rounded-2xl shadow-2xl border" style="background:#f0fdf4;border-color:#bbf7d0">
        <div class="absolute bottom-0 left-0 right-0 h-1" style="background:#d1fae5">
          <div class="toast-progress h-full rounded-full" style="background:linear-gradient(to right,#10b981,#34d399)"></div>
        </div>
        <div class="relative px-5 py-4 flex items-start gap-4">
          <div class="relative flex-shrink-0">
            <div class="w-10 h-10 rounded-full flex items-center justify-center shadow-lg" style="background:linear-gradient(135deg,#34d399,#10b981)">
              <i class="fa-solid fa-check text-white text-lg"></i>
            </div>
          </div>
          <div class="flex-1 pt-1 min-w-0">
            <h4 class="font-semibold text-sm mb-0.5 select-none" style="color:#064e3b">Success</h4>
            <p class="text-sm leading-relaxed select-none" style="color:#065f46">${msg}</p>
          </div>
          <button type="button" onclick="this.closest('.toast-notification').classList.add('hide');setTimeout(()=>this.closest('.toast-notification')?.remove(),350)"
                  class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center transition-all duration-200 group cursor-pointer"
                  style="hover:background:#d1fae5">
            <i class="fa-solid fa-xmark group-hover:rotate-90 transition-transform duration-200" style="color:#059669"></i>
          </button>
        </div>
      </div>`;
    document.body.appendChild(toast);
    if (!document.getElementById("tf-toast-style")) {
      const s = document.createElement("style");
      s.id = "tf-toast-style";
      s.textContent = `
        @keyframes toast-slide-in{from{transform:translateX(calc(100% + 1rem));opacity:0}to{transform:translateX(0);opacity:1}}
        @keyframes toast-slide-out{from{transform:translateX(0);opacity:1}to{transform:translateX(calc(100% + 1rem));opacity:0}}
        @keyframes toast-progress{from{width:100%}to{width:0%}}
        .toast-notification{animation:toast-slide-in .4s cubic-bezier(.16,1,.3,1) forwards}
        .toast-notification.hide{animation:toast-slide-out .35s cubic-bezier(.4,0,1,1) forwards}
        .toast-progress{animation:toast-progress 5s linear forwards}`;
      document.head.appendChild(s);
    }
    setTimeout(() => toast.classList.add("hide"), 4700);
    setTimeout(() => toast.remove(), 5100);
  }
  function startDownloadPoller() {
    eraseCookie("tf_download_ready");
    const interval = setInterval(() => {
      if (getCookie("tf_download_ready")) {
        clearInterval(interval);
        eraseCookie("tf_download_ready");
        resetBtn();
        showSuccessToast(
          "Formatting applied successfully. Your file is downloading.",
        );
      }
    }, 300);
    setTimeout(() => {
      clearInterval(interval);
      resetBtn();
    }, 300000);
  }
})();
