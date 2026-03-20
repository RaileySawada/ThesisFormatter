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
    document.documentElement.setAttribute(
      "data-theme",
      dark ? "dark" : "light",
    );
    document.documentElement.style.setProperty(
      "--sb-thumb",
      dark ? "#4a5568" : "#a0aec0",
    );
    document.documentElement.style.setProperty(
      "--sb-thumb-hover",
      dark ? "#718096" : "#718096",
    );
    document.documentElement.style.background = dark ? "#0d1117" : "#f0f4ff";
    document.body.style.background = dark ? "#0d1117" : "#f0f4ff";
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
    sheet?.classList.remove("closing");
    sheet?.classList.add("is-open");
    backdrop?.setAttribute("data-open", "");
    document.body.style.overflow = "hidden";
  }
  function closeSheet() {
    sheet?.classList.add("closing");
    sheet?.classList.remove("is-open");
    setTimeout(() => {
      sheet?.classList.remove("closing");
      backdrop?.removeAttribute("data-open");
      document.body.style.overflow = "";
    }, 320);
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
  const closePrevFooter = document.getElementById("close-preview-footer-btn");
  openPrev?.addEventListener("click", () => {
    prevModal?.classList.remove("closing");
    prevBackdrop?.setAttribute("data-open", "");
    document.body.style.overflow = "hidden";
  });
  function closePreview() {
    prevModal?.classList.add("closing");
    setTimeout(() => {
      prevModal?.classList.remove("closing");
      prevBackdrop?.removeAttribute("data-open");
      document.body.style.overflow = "";
    }, 340);
  }
  closePrev?.addEventListener("click", closePreview);
  closePrevFooter?.addEventListener("click", closePreview);
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
    const wrap = document.querySelector(".file-cards-wrap");
    if (wrap) {
      wrap.classList.remove("dealt");
      void wrap.offsetWidth;
      wrap.classList.add("dealt");
      const cards = wrap.querySelectorAll(".file-card");
      const longest = 0.18 + 0.45;
      setTimeout(
        () => {
          const transforms = {
            "fc-p1":
              "translate(-50%,-50%) rotate(-6deg) translateX(-18px) translateY(6px)",
            "fc-p2":
              "translate(-50%,-50%) rotate(4deg) translateX(14px) translateY(10px)",
            "fc-p3": "translate(-50%,-50%) rotate(-1deg) translateY(0px)",
          };
          cards.forEach((c) => {
            const cls = ["fc-p1", "fc-p2", "fc-p3"].find((k) =>
              c.classList.contains(k),
            );
            if (cls) c.style.transform = transforms[cls];
            c.style.animation = "none";
          });
          wrap.classList.remove("dealt");
        },
        (longest + 0.05) * 1000,
      );
    }
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
    document.querySelectorAll(".tf-toast").forEach((t) => t.remove());
    const toast = document.createElement("div");
    toast.className = "tf-toast tf-toast--success";
    toast.setAttribute("role", "alert");
    toast.innerHTML = `
      <div class="tf-toast-icon"><i class="fa-solid fa-check"></i></div>
      <div class="tf-toast-body">
        <p class="tf-toast-title">Success</p>
        <p class="tf-toast-msg">${msg}</p>
      </div>
      <button class="tf-toast-close" onclick="dismissToast(this.closest('.tf-toast'))" title="Close">
        <i class="fa-solid fa-xmark"></i>
      </button>
      <div class="tf-toast-bar"></div>`;
    document.body.appendChild(toast);
    setTimeout(() => window.dismissToast(toast), 4700);
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
