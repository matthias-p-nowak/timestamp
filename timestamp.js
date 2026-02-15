const UI_CONFIG = {
  ids: {
    overlay: "dayEditor",
    pairGrid: "pairGrid",
    addPairBtn: "addPairBtn",
    cancelButton: "editorCancel",
    saveButton: "editorSave",
    dayDateInput: "editorDayDate",
    editorErrors: "editorErrors",
    breakHint: "breakHint",
    calendarDateInput: "calendarDateInput",
    calendarAddBtn: "calendarAddBtn"
  },
  selectors: {
    dayRows: ".day-row",
    missingDayButtons: ".missing-day-btn",
    timePairs: ".time-pair",
    pairRows: ".pair-row",
    inInput: 'input[name="pair_in[]"]',
    outInput: 'input[name="pair_out[]"]'
  },
  classes: {
    open: "is-open",
    invalid: "is-invalid",
    pairRow: "pair-row",
    pairInput: "pair-input"
  },
  input: {
    inName: "pair_in[]",
    outName: "pair_out[]",
    timeStepSeconds: "60",
    checkInPlaceholder: "Check in",
    checkOutPlaceholder: "Check out"
  },
  messages: {
    missingCheckIn: "Each non-empty row needs a check-in time.",
    invalidTimeFormat: "Use time as HH:mm, 745, or 1712.",
    invalidCheckOut: "Check-out has invalid time format.",
    checkOutBeforeCheckIn: "Check-out cannot be earlier than check-in."
  },
  breakWindow: {
    startMinutes: (11 * 60) + 30,
    endMinutes: 12 * 60
  },
  dateWindow: {
    weeks: 8
  },
  media: {
    coarsePointer: "(pointer: coarse)"
  }
};

const overlay = document.getElementById(UI_CONFIG.ids.overlay);
const pairGrid = document.getElementById(UI_CONFIG.ids.pairGrid);
const addPairBtn = document.getElementById(UI_CONFIG.ids.addPairBtn);
const cancelButton = document.getElementById(UI_CONFIG.ids.cancelButton);
const saveButton = document.getElementById(UI_CONFIG.ids.saveButton);
const dayDateInput = document.getElementById(UI_CONFIG.ids.dayDateInput);
const editorForm = overlay.querySelector("form");
const editorErrors = document.getElementById(UI_CONFIG.ids.editorErrors);
const breakHint = document.getElementById(UI_CONFIG.ids.breakHint);
const calendarDateInput = document.getElementById(UI_CONFIG.ids.calendarDateInput);
const calendarAddBtn = document.getElementById(UI_CONFIG.ids.calendarAddBtn);
const useNativeTimeInput = typeof window.matchMedia === "function" &&
  window.matchMedia(UI_CONFIG.media.coarsePointer).matches;
const BREAK_START_MINUTES = UI_CONFIG.breakWindow.startMinutes;
const BREAK_END_MINUTES = UI_CONFIG.breakWindow.endMinutes;

function parseCompactTime(value) {
  const raw = value.trim();
  if (raw === "") {
    return null;
  }

  const fullMatch = raw.match(/^(\d{2}):(\d{2})$/);
  if (fullMatch) {
    const hours = Number(fullMatch[1]);
    const minutes = Number(fullMatch[2]);
    if (hours >= 0 && hours <= 23 && minutes >= 0 && minutes <= 59) {
      return { formatted: `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}`, totalMinutes: (hours * 60) + minutes };
    }
    return null;
  }

  if (!/^\d{3,4}$/.test(raw)) {
    return null;
  }

  const normalized = raw.length === 3 ? `0${raw}` : raw;
  const hours = Number(normalized.slice(0, 2));
  const minutes = Number(normalized.slice(2, 4));
  if (hours < 0 || hours > 23 || minutes < 0 || minutes > 59) {
    return null;
  }

  return { formatted: `${String(hours).padStart(2, "0")}:${String(minutes).padStart(2, "0")}`, totalMinutes: (hours * 60) + minutes };
}

function addPairInputRow(inValue, outValue) {
  const row = document.createElement("div");
  row.className = UI_CONFIG.classes.pairRow;

  const inInput = document.createElement("input");
  inInput.className = UI_CONFIG.classes.pairInput;
  if (useNativeTimeInput) {
    inInput.type = "time";
    inInput.step = UI_CONFIG.input.timeStepSeconds;
  } else {
    inInput.type = "text";
    inInput.placeholder = UI_CONFIG.input.checkInPlaceholder;
  }
  inInput.name = UI_CONFIG.input.inName;
  inInput.value = inValue || "";

  const outInput = document.createElement("input");
  outInput.className = UI_CONFIG.classes.pairInput;
  if (useNativeTimeInput) {
    outInput.type = "time";
    outInput.step = UI_CONFIG.input.timeStepSeconds;
  } else {
    outInput.type = "text";
    outInput.placeholder = UI_CONFIG.input.checkOutPlaceholder;
  }
  outInput.name = UI_CONFIG.input.outName;
  outInput.value = outValue || "";

  inInput.addEventListener("input", validateEditorRows);
  outInput.addEventListener("input", validateEditorRows);

  row.appendChild(inInput);
  row.appendChild(outInput);
  pairGrid.appendChild(row);
}

function setInputValidity(input, isValid) {
  input.classList.toggle(UI_CONFIG.classes.invalid, !isValid);
}

function validateEditorRows() {
  const rows = Array.from(pairGrid.querySelectorAll(UI_CONFIG.selectors.pairRows));
  let firstError = "";
  let hasError = false;
  let hasOverlapWithBreakWindow = false;

  rows.forEach((row) => {
    const inInput = row.querySelector(UI_CONFIG.selectors.inInput);
    const outInput = row.querySelector(UI_CONFIG.selectors.outInput);
    const inValue = inInput.value.trim();
    const outValue = outInput.value.trim();
    let rowValid = true;

    if (inValue === "" && outValue === "") {
      setInputValidity(inInput, true);
      setInputValidity(outInput, true);
      return;
    }

    const inParsed = parseCompactTime(inValue);
    const outParsed = outValue === "" ? null : parseCompactTime(outValue);

    if (inValue === "") {
        rowValid = false;
        if (!firstError) {
          firstError = UI_CONFIG.messages.missingCheckIn;
        }
      } else if (inParsed === null) {
        rowValid = false;
        if (!firstError) {
          firstError = UI_CONFIG.messages.invalidTimeFormat;
        }
      }

    if (outValue !== "" && outParsed === null) {
      rowValid = false;
      if (!firstError) {
        firstError = UI_CONFIG.messages.invalidCheckOut;
      }
    }

    if (inParsed !== null && outParsed !== null && outParsed.totalMinutes < inParsed.totalMinutes) {
      rowValid = false;
      if (!firstError) {
        firstError = UI_CONFIG.messages.checkOutBeforeCheckIn;
      }
    }

    if (rowValid && inParsed !== null && outParsed !== null) {
      if (inParsed.totalMinutes < BREAK_END_MINUTES && outParsed.totalMinutes > BREAK_START_MINUTES) {
        hasOverlapWithBreakWindow = true;
      }
    }

    setInputValidity(inInput, rowValid || inParsed !== null);
    setInputValidity(outInput, rowValid || outValue === "" || outParsed !== null);

    if (!rowValid) {
      hasError = true;
    }
  });

  editorErrors.textContent = hasError ? firstError : "";
  breakHint.hidden = !hasOverlapWithBreakWindow;
  saveButton.disabled = hasError;
  saveButton.setAttribute("aria-disabled", hasError ? "true" : "false");
  return !hasError;
}

function fillModalFromRow(row) {
  pairGrid.innerHTML = "";
  const pairs = Array.from(row.querySelectorAll(UI_CONFIG.selectors.timePairs)).map((item) => item.textContent.trim());

  if (pairs.length === 0) {
    addPairInputRow("", "");
    return;
  }

  pairs.forEach((text) => {
    const split = text.split("-");
    const inValue = split[0] ? split[0].trim() : "";
    const outValue = split[1] ? split[1].trim() : "";
    addPairInputRow(inValue, outValue);
  });
}

function openModal(row) {
  fillModalFromRow(row);
  dayDateInput.value = row.dataset.date || "";
  overlay.classList.add(UI_CONFIG.classes.open);
  overlay.setAttribute("aria-hidden", "false");
  validateEditorRows();
}

function openModalForDate(dateValue) {
  pairGrid.innerHTML = "";
  addPairInputRow("", "");
  dayDateInput.value = dateValue;
  overlay.classList.add(UI_CONFIG.classes.open);
  overlay.setAttribute("aria-hidden", "false");
  validateEditorRows();
}

function setDatePickerRangeToLast8Weeks() {
  if (!calendarDateInput) {
    return;
  }
  if (calendarDateInput.min && calendarDateInput.max) {
    return;
  }

  const today = new Date();
  const maxDate = new Date(today.getFullYear(), today.getMonth(), today.getDate());
  const weekDay = (maxDate.getDay() + 6) % 7;
  const mondayThisWeek = new Date(maxDate);
  mondayThisWeek.setDate(maxDate.getDate() - weekDay);
  const minDate = new Date(mondayThisWeek);
  minDate.setDate(mondayThisWeek.getDate() - ((UI_CONFIG.dateWindow.weeks - 1) * 7));

  const toIsoDate = (value) => {
    const year = value.getFullYear();
    const month = String(value.getMonth() + 1).padStart(2, "0");
    const day = String(value.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  };

  calendarDateInput.min = toIsoDate(minDate);
  calendarDateInput.max = toIsoDate(maxDate);
}

function closeModal() {
  overlay.classList.remove(UI_CONFIG.classes.open);
  overlay.setAttribute("aria-hidden", "true");
}

document.querySelectorAll(UI_CONFIG.selectors.dayRows).forEach((row) => {
  row.addEventListener("click", () => {
    openModal(row);
  });

  row.addEventListener("keydown", (event) => {
    if (event.key === "Enter" || event.key === " ") {
      event.preventDefault();
      openModal(row);
    }
  });
});

document.querySelectorAll(UI_CONFIG.selectors.missingDayButtons).forEach((button) => {
  button.addEventListener("click", () => {
    openModal(button);
  });
});

setDatePickerRangeToLast8Weeks();

calendarAddBtn.addEventListener("click", () => {
  if (typeof calendarDateInput.showPicker === "function") {
    calendarDateInput.showPicker();
    return;
  }
  calendarDateInput.click();
});

calendarDateInput.addEventListener("change", () => {
  const selectedDate = calendarDateInput.value;
  if (!selectedDate) {
    return;
  }
  if ((calendarDateInput.min && selectedDate < calendarDateInput.min) ||
      (calendarDateInput.max && selectedDate > calendarDateInput.max)) {
    calendarDateInput.value = "";
    return;
  }

  openModalForDate(selectedDate);
  calendarDateInput.value = "";
});

cancelButton.addEventListener("click", closeModal);

addPairBtn.addEventListener("click", () => {
  addPairInputRow("", "");
  validateEditorRows();
});

overlay.addEventListener("click", (event) => {
  if (event.target === overlay) {
    closeModal();
  }
});

editorForm.addEventListener("submit", (event) => {
  if (!validateEditorRows()) {
    event.preventDefault();
  }
});
