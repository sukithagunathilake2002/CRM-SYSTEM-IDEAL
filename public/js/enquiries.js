z(function () {
    const list = document.getElementById("eprList");
    const searchInput = document.getElementById("eprSearch");
    const sortBtn = document.getElementById("eprSortBtn");
    const filterBtn = document.getElementById("eprFilterBtn");
    const menuFilterBtn = document.getElementById("eprMenuFilterBtn");
    const filterOverlay = document.getElementById("eprFilterOverlay");
    const filterCloseBtn = document.getElementById("eprFilterClose");
    const filterApplyBtn = document.getElementById("eprFilterApplyBtn");
    const filterClearBtn = document.getElementById("eprFilterClearBtn");
    const filterSearchInput = document.getElementById("eprFilterSearch");
    const filterTabs = Array.from(document.querySelectorAll("[data-filter-tab]"));
    const filterPanels = Array.from(document.querySelectorAll("[data-filter-panel]"));

    const filterInquiryFromInput = document.getElementById("filterInquiryFrom");
    const filterInquiryToInput = document.getElementById("filterInquiryTo");
    const filterModelSelect = document.getElementById("filterModel");
    const filterLeadSourceSelect = document.getElementById("filterLeadSource");
    const filterExchangeSelect = document.getElementById("filterExchange");
    const filterDueFromInput = document.getElementById("filterDueFrom");
    const filterDueToInput = document.getElementById("filterDueTo");
    const filterFollowupTypeSelect = document.getElementById("filterFollowupType");
    const filterRoleSelect = document.getElementById("filterRole");
    const filterAssignedUserSelect = document.getElementById("filterAssignedUser");

    if (!list) {
        return;
    }

    const activeFilters = {
        inquiryFrom: "",
        inquiryTo: "",
        model: "",
        leadSource: "",
        exchange: "",
        dueFrom: "",
        dueTo: "",
        followupType: "",
        role: "",
        assignedUserId: "",
    };

    function getCards() {
        return Array.from(list.querySelectorAll(".epr-card"));
    }

    function normalizeValue(value) {
        return (value || "").toString().trim().toLowerCase();
    }

    function formatLabel(value) {
        return value
            .replace(/[_-]+/g, " ")
            .split(/\s+/)
            .filter(Boolean)
            .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
            .join(" ");
    }

    function setSelectOptions(selectEl, values, defaultLabel) {
        if (!selectEl) {
            return;
        }

        selectEl.innerHTML = `<option value="">${defaultLabel}</option>`;

        values.forEach((value) => {
            const option = document.createElement("option");
            option.value = value;
            option.textContent = formatLabel(value);
            selectEl.appendChild(option);
        });
    }

    function getUniqueCardData(key) {
        return Array.from(
            new Set(
                getCards()
                    .map((card) => normalizeValue(card.dataset[key]))
                    .filter(Boolean),
            ),
        ).sort((a, b) => a.localeCompare(b));
    }

    function setSelectOptionsFromPairs(selectEl, pairs, defaultLabel) {
        if (!selectEl) {
            return;
        }

        selectEl.innerHTML = `<option value="">${defaultLabel}</option>`;

        pairs.forEach(({ value, label }) => {
            const option = document.createElement("option");
            option.value = value;
            option.textContent = label;
            selectEl.appendChild(option);
        });
    }

    function getUniqueOwnerRolePairs() {
        const lookup = new Map();
        getCards().forEach((card) => {
            const value = normalizeValue(card.dataset.ownerRole);
            const label = (card.dataset.ownerRoleLabel || "").trim();
            if (!value || lookup.has(value)) {
                return;
            }
            lookup.set(value, label || formatLabel(value));
        });

        return Array.from(lookup.entries())
            .map(([value, label]) => ({ value, label }))
            .sort((a, b) => a.label.localeCompare(b.label));
    }

    function getUniqueOwnerUserPairs() {
        const lookup = new Map();
        getCards().forEach((card) => {
            const value = (card.dataset.ownerId || "").trim();
            const label = (card.dataset.ownerNameLabel || "").trim() || formatLabel(card.dataset.ownerName || "");
            if (!value || lookup.has(value)) {
                return;
            }
            lookup.set(value, label || "Unknown");
        });

        return Array.from(lookup.entries())
            .map(([value, label]) => ({ value, label }))
            .sort((a, b) => a.label.localeCompare(b.label));
    }

    function populateFilterOptions() {
        setSelectOptions(filterModelSelect, getUniqueCardData("model"), "All Models");
        setSelectOptions(filterLeadSourceSelect, getUniqueCardData("leadSource"), "All Lead Sources");
        setSelectOptions(filterFollowupTypeSelect, getUniqueCardData("followType"), "All Followup Types");
        setSelectOptionsFromPairs(filterRoleSelect, getUniqueOwnerRolePairs(), "All Roles");
        setSelectOptionsFromPairs(filterAssignedUserSelect, getUniqueOwnerUserPairs(), "All Users");
    }

    function dateInRange(dateValue, fromValue, toValue) {
        if (!fromValue && !toValue) {
            return true;
        }

        if (!dateValue) {
            return false;
        }

        const dateMs = Date.parse(`${dateValue}T00:00:00`);
        if (!Number.isFinite(dateMs)) {
            return false;
        }

        if (fromValue) {
            const fromMs = Date.parse(`${fromValue}T00:00:00`);
            if (Number.isFinite(fromMs) && dateMs < fromMs) {
                return false;
            }
        }

        if (toValue) {
            const toMs = Date.parse(`${toValue}T23:59:59`);
            if (Number.isFinite(toMs) && dateMs > toMs) {
                return false;
            }
        }

        return true;
    }

    function closeAllMenus() {
        document.querySelectorAll(".card-menu.open").forEach((menu) => {
            menu.classList.remove("open");
        });
        document.querySelectorAll(".menu-dot-btn[aria-expanded='true']").forEach((btn) => {
            btn.setAttribute("aria-expanded", "false");
        });
    }

    function openFilterOverlay() {
        if (!filterOverlay) {
            return;
        }

        if (filterSearchInput && searchInput) {
            filterSearchInput.value = searchInput.value;
        }

        filterOverlay.classList.add("open");
        filterOverlay.setAttribute("aria-hidden", "false");
        document.body.classList.add("filter-open");
    }

    function closeFilterOverlay() {
        if (!filterOverlay) {
            return;
        }

        filterOverlay.classList.remove("open");
        filterOverlay.setAttribute("aria-hidden", "true");
        document.body.classList.remove("filter-open");
    }

    function activateFilterTab(tabName) {
        filterTabs.forEach((tab) => {
            tab.classList.toggle("active", tab.dataset.filterTab === tabName);
        });

        filterPanels.forEach((panel) => {
            panel.classList.toggle("active", panel.dataset.filterPanel === tabName);
        });
    }

    function resetFilterInputs() {
        if (filterInquiryFromInput) filterInquiryFromInput.value = "";
        if (filterInquiryToInput) filterInquiryToInput.value = "";
        if (filterModelSelect) filterModelSelect.value = "";
        if (filterLeadSourceSelect) filterLeadSourceSelect.value = "";
        if (filterExchangeSelect) filterExchangeSelect.value = "";
        if (filterDueFromInput) filterDueFromInput.value = "";
        if (filterDueToInput) filterDueToInput.value = "";
        if (filterFollowupTypeSelect) filterFollowupTypeSelect.value = "";
        if (filterRoleSelect) filterRoleSelect.value = "";
        if (filterAssignedUserSelect) filterAssignedUserSelect.value = "";
    }

    function clearActiveFilters() {
        activeFilters.inquiryFrom = "";
        activeFilters.inquiryTo = "";
        activeFilters.model = "";
        activeFilters.leadSource = "";
        activeFilters.exchange = "";
        activeFilters.dueFrom = "";
        activeFilters.dueTo = "";
        activeFilters.followupType = "";
        activeFilters.role = "";
        activeFilters.assignedUserId = "";
    }

    function collectFiltersFromForm() {
        activeFilters.inquiryFrom = filterInquiryFromInput?.value || "";
        activeFilters.inquiryTo = filterInquiryToInput?.value || "";
        activeFilters.model = normalizeValue(filterModelSelect?.value);
        activeFilters.leadSource = normalizeValue(filterLeadSourceSelect?.value);
        activeFilters.exchange = normalizeValue(filterExchangeSelect?.value);
        activeFilters.dueFrom = filterDueFromInput?.value || "";
        activeFilters.dueTo = filterDueToInput?.value || "";
        activeFilters.followupType = normalizeValue(filterFollowupTypeSelect?.value);
        activeFilters.role = normalizeValue(filterRoleSelect?.value);
        activeFilters.assignedUserId = (filterAssignedUserSelect?.value || "").trim();
    }

    function applySearchAndSort() {
        const query = (searchInput?.value || "").trim().toLowerCase();
        const sortMode = sortBtn?.dataset.sort || "newest";
        const cards = getCards();

        cards.forEach((card) => {
            const text = [card.dataset.name, card.dataset.phone, card.dataset.vehicle].join(" ");
            const isTextMatch = !query || text.includes(query);
            const isModelMatch = !activeFilters.model || normalizeValue(card.dataset.model) === activeFilters.model;
            const isLeadSourceMatch = !activeFilters.leadSource || normalizeValue(card.dataset.leadSource) === activeFilters.leadSource;
            const isExchangeMatch = !activeFilters.exchange || normalizeValue(card.dataset.exchange) === activeFilters.exchange;
            const isFollowTypeMatch = !activeFilters.followupType || normalizeValue(card.dataset.followType) === activeFilters.followupType;
            const isRoleMatch = !activeFilters.role || normalizeValue(card.dataset.ownerRole) === activeFilters.role;
            const isAssignedUserMatch = !activeFilters.assignedUserId || (card.dataset.ownerId || "").trim() === activeFilters.assignedUserId;
            const isInquiryDateMatch = dateInRange(card.dataset.inquiryDate, activeFilters.inquiryFrom, activeFilters.inquiryTo);
            const isDueDateMatch = dateInRange(card.dataset.followDate, activeFilters.dueFrom, activeFilters.dueTo);

            const isVisible =
                isTextMatch &&
                isModelMatch &&
                isLeadSourceMatch &&
                isExchangeMatch &&
                isFollowTypeMatch &&
                isRoleMatch &&
                isAssignedUserMatch &&
                isInquiryDateMatch &&
                isDueDateMatch;

            card.style.display = isVisible ? "" : "none";
        });

        const visibleCards = cards.filter((card) => card.style.display !== "none");
        visibleCards
            .sort((a, b) => {
                const aDate = Number(a.dataset.date || 0);
                const bDate = Number(b.dataset.date || 0);
                return sortMode === "newest" ? bDate - aDate : aDate - bDate;
            })
            .forEach((card) => list.appendChild(card));
    }

    window.toggleCardMenu = function (button) {
        const card = button.closest(".epr-card");
        if (!card) {
            return;
        }

        const menu = card.querySelector(".card-menu");
        if (!menu) {
            return;
        }

        const isOpen = menu.classList.contains("open");
        closeAllMenus();

        if (!isOpen) {
            menu.classList.add("open");
            button.setAttribute("aria-expanded", "true");
        }
    };

    searchInput?.addEventListener("input", applySearchAndSort);

    filterBtn?.addEventListener("click", openFilterOverlay);
    menuFilterBtn?.addEventListener("click", openFilterOverlay);
    filterCloseBtn?.addEventListener("click", closeFilterOverlay);

    filterOverlay?.addEventListener("click", function (event) {
        if (event.target === filterOverlay) {
            closeFilterOverlay();
        }
    });

    filterTabs.forEach((tab) => {
        tab.addEventListener("click", function () {
            activateFilterTab(tab.dataset.filterTab);
        });
    });

    filterApplyBtn?.addEventListener("click", function () {
        collectFiltersFromForm();

        if (searchInput && filterSearchInput) {
            searchInput.value = filterSearchInput.value;
        }

        applySearchAndSort();
        closeFilterOverlay();
    });

    filterClearBtn?.addEventListener("click", function () {
        resetFilterInputs();
        clearActiveFilters();

        if (searchInput) {
            searchInput.value = "";
        }
        if (filterSearchInput) {
            filterSearchInput.value = "";
        }

        applySearchAndSort();
    });

    sortBtn?.addEventListener("click", function () {
        const current = sortBtn.dataset.sort || "newest";
        sortBtn.dataset.sort = current === "newest" ? "oldest" : "newest";
        sortBtn.textContent = sortBtn.dataset.sort === "newest" ? "Sort: New" : "Sort: Old";
        applySearchAndSort();
    });

    document.addEventListener("click", function (event) {
        if (!event.target.closest(".menu-dot-btn") && !event.target.closest(".card-menu")) {
            closeAllMenus();
        }
    });

    document.addEventListener("keydown", function (event) {
        if (event.key === "Escape") {
            closeFilterOverlay();
        }
    });

    populateFilterOptions();
    activateFilterTab("inquiry_period");
    applySearchAndSort();
})();
