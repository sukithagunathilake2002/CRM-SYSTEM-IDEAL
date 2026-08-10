(function () {
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
    const filterDueFromInput = document.getElementById("filterDueFrom");
    const filterDueToInput = document.getElementById("filterDueTo");

    const optionGroups = {
        model: {
            cardKey: "model",
            pairGetter: getUniqueModelPairs,
            list: document.getElementById("filterModelOptions"),
            search: document.getElementById("filterModelSearch"),
        },
        leadSource: {
            cardKey: "leadSource",
            list: document.getElementById("filterLeadSourceOptions"),
            search: document.getElementById("filterLeadSourceSearch"),
        },
        exchange: {
            staticOptions: [
                { value: "yes", label: "Yes" },
                { value: "no", label: "No" },
            ],
            list: document.getElementById("filterExchangeOptions"),
        },
        followupType: {
            cardKey: "followType",
            list: document.getElementById("filterFollowupTypeOptions"),
            search: document.getElementById("filterFollowupTypeSearch"),
        },
        role: {
            pairGetter: getUniqueOwnerRolePairs,
            list: document.getElementById("filterRoleOptions"),
            search: document.getElementById("filterRoleSearch"),
        },
    };

    if (!list) {
        return;
    }

    const activeFilters = {
        inquiryFrom: "",
        inquiryTo: "",
        dueFrom: "",
        dueTo: "",
        model: new Set(),
        leadSource: new Set(),
        exchange: new Set(),
        followupType: new Set(),
        role: new Set(),
    };

    function getCards() {
        return Array.from(list.querySelectorAll(".epr-card"));
    }

    function normalizeValue(value) {
        return (value || "").toString().trim().toLowerCase();
    }

    function normalizeSearch(value) {
        return normalizeValue(value)
            .replace(/[^a-z0-9]+/g, " ")
            .replace(/\s+/g, " ")
            .trim();
    }

    function digitsOnly(value) {
        return (value || "").toString().replace(/\D+/g, "");
    }

    function formatLabel(value) {
        return value
            .replace(/[_-]+/g, " ")
            .split(/\s+/)
            .filter(Boolean)
            .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
            .join(" ");
    }

    function optionId(groupName, value) {
        return `filter-${groupName}-${normalizeSearch(value).replace(/\s+/g, "-") || "item"}`;
    }

    function getUniqueCardData(key) {
        return Array.from(
            new Set(
                getCards()
                    .map((card) => normalizeValue(card.dataset[key]))
                    .filter(Boolean),
            ),
        )
            .map((value) => ({ value, label: formatLabel(value) }))
            .sort((a, b) => a.label.localeCompare(b.label));
    }

    function getUniqueModelPairs() {
        const values = new Set();
        getCards().forEach((card) => {
            String(card.dataset.models || card.dataset.model || "")
                .split("|")
                .map(normalizeValue)
                .filter(Boolean)
                .forEach((value) => values.add(value));
        });

        return Array.from(values)
            .map((value) => ({ value, label: formatLabel(value) }))
            .sort((a, b) => a.label.localeCompare(b.label));
    }

    function getUniqueOwnerRolePairs() {
        const lookup = new Map();
        getCards().forEach((card) => {
            const value = normalizeValue(card.dataset.ownerRole);
            const label = (card.dataset.ownerRoleLabel || "").trim();
            if (!value || lookup.has(value)) return;
            lookup.set(value, label || formatLabel(value));
        });

        return Array.from(lookup.entries())
            .map(([value, label]) => ({ value, label }))
            .sort((a, b) => a.label.localeCompare(b.label));
    }

    function groupOptions(groupName) {
        const group = optionGroups[groupName];
        if (!group) return [];
        if (Array.isArray(group.staticOptions)) return group.staticOptions;
        if (typeof group.pairGetter === "function") return group.pairGetter();
        return getUniqueCardData(group.cardKey);
    }

    function renderOptionGroup(groupName) {
        const group = optionGroups[groupName];
        if (!group?.list) return;

        const query = normalizeSearch(group.search?.value || "");
        const selected = activeFilters[groupName];
        const options = groupOptions(groupName).filter((option) => {
            if (!query) return true;
            return normalizeSearch(`${option.label} ${option.value}`).includes(query);
        });

        group.list.innerHTML = "";

        if (options.length === 0) {
            const empty = document.createElement("p");
            empty.className = "epr-filter-empty";
            empty.textContent = "No matching options";
            group.list.appendChild(empty);
            return;
        }

        options.forEach((option) => {
            const id = optionId(groupName, option.value);
            const label = document.createElement("label");
            label.className = "epr-filter-choice";
            label.htmlFor = id;

            const text = document.createElement("span");
            text.textContent = option.label;

            const input = document.createElement("input");
            input.type = "checkbox";
            input.id = id;
            input.value = option.value;
            input.checked = selected.has(option.value);
            input.dataset.filterGroup = groupName;

            label.append(text, input);
            group.list.appendChild(label);
        });
    }

    function renderAllOptionGroups() {
        Object.keys(optionGroups).forEach(renderOptionGroup);
    }

    function dateInRange(dateValue, fromValue, toValue) {
        if (!fromValue && !toValue) return true;
        if (!dateValue) return false;

        const dateMs = Date.parse(`${dateValue}T00:00:00`);
        if (!Number.isFinite(dateMs)) return false;

        if (fromValue) {
            const fromMs = Date.parse(`${fromValue}T00:00:00`);
            if (Number.isFinite(fromMs) && dateMs < fromMs) return false;
        }

        if (toValue) {
            const toMs = Date.parse(`${toValue}T23:59:59`);
            if (Number.isFinite(toMs) && dateMs > toMs) return false;
        }

        return true;
    }

    function setOverlayOpen(open) {
        if (!filterOverlay) return;
        filterOverlay.classList.toggle("open", open);
        filterOverlay.setAttribute("aria-hidden", open ? "false" : "true");
        document.body.classList.toggle("filter-open", open);
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
        if (filterSearchInput && searchInput) {
            filterSearchInput.value = searchInput.value;
        }
        renderAllOptionGroups();
        setOverlayOpen(true);
    }

    function activateFilterTab(tabName) {
        filterTabs.forEach((tab) => {
            tab.classList.toggle("active", tab.dataset.filterTab === tabName);
        });

        filterPanels.forEach((panel) => {
            panel.classList.toggle("active", panel.dataset.filterPanel === tabName);
        });
    }

    function clearActiveFilters() {
        activeFilters.inquiryFrom = "";
        activeFilters.inquiryTo = "";
        activeFilters.dueFrom = "";
        activeFilters.dueTo = "";
        Object.keys(optionGroups).forEach((groupName) => activeFilters[groupName]?.clear());
    }

    function resetFilterInputs() {
        [filterInquiryFromInput, filterInquiryToInput, filterDueFromInput, filterDueToInput].forEach((input) => {
            if (input) input.value = "";
        });

        Object.values(optionGroups).forEach((group) => {
            if (group.search) group.search.value = "";
        });
    }

    function collectFiltersFromForm() {
        activeFilters.inquiryFrom = filterInquiryFromInput?.value || "";
        activeFilters.inquiryTo = filterInquiryToInput?.value || "";
        activeFilters.dueFrom = filterDueFromInput?.value || "";
        activeFilters.dueTo = filterDueToInput?.value || "";
    }

    function setFilterValue(groupName, value, checked) {
        const selected = activeFilters[groupName];
        if (!selected) return;

        if (checked) {
            selected.add(value);
        } else {
            selected.delete(value);
        }
    }

    function matchSelected(selectedSet, value) {
        return selectedSet.size === 0 || selectedSet.has(normalizeValue(value)) || selectedSet.has(String(value || "").trim());
    }

    function matchSelectedAny(selectedSet, value) {
        if (selectedSet.size === 0) return true;
        return String(value || "")
            .split("|")
            .map(normalizeValue)
            .some((item) => selectedSet.has(item));
    }

    function matchesSearch(card, query) {
        const normalizedQuery = normalizeSearch(query);
        if (!normalizedQuery) return true;

        const searchable = normalizeSearch([
            card.dataset.search,
            card.dataset.name,
            card.dataset.phone,
            card.dataset.vehicle,
            card.dataset.model,
            card.dataset.leadSource,
            card.dataset.followType,
            card.dataset.ownerNameLabel,
            card.dataset.ownerRoleLabel,
        ].join(" "));

        const tokens = normalizedQuery.split(" ").filter(Boolean);
        const textMatch = tokens.every((token) => searchable.includes(token));
        const digitQuery = digitsOnly(query);
        const phoneMatch = digitQuery.length >= 3 && digitsOnly(card.dataset.phone).includes(digitQuery);

        return textMatch || phoneMatch;
    }

    function applySearchAndSort() {
        const query = searchInput?.value || "";
        const sortMode = sortBtn?.dataset.sort || "newest";
        const cards = getCards();

        cards.forEach((card) => {
            const isVisible =
                matchesSearch(card, query) &&
                matchSelectedAny(activeFilters.model, card.dataset.models || card.dataset.model) &&
                matchSelected(activeFilters.leadSource, card.dataset.leadSource) &&
                matchSelected(activeFilters.exchange, card.dataset.exchange) &&
                matchSelected(activeFilters.followupType, card.dataset.followType) &&
                matchSelected(activeFilters.role, card.dataset.ownerRole) &&
                dateInRange(card.dataset.inquiryDate, activeFilters.inquiryFrom, activeFilters.inquiryTo) &&
                dateInRange(card.dataset.followDate, activeFilters.dueFrom, activeFilters.dueTo);

            card.style.display = isVisible ? "" : "none";
        });

        cards
            .filter((card) => card.style.display !== "none")
            .sort((a, b) => {
                const aDate = Number(a.dataset.date || 0);
                const bDate = Number(b.dataset.date || 0);
                return sortMode === "newest" ? bDate - aDate : aDate - bDate;
            })
            .forEach((card) => list.appendChild(card));
    }

    window.toggleCardMenu = function (button) {
        const card = button.closest(".epr-card");
        const menu = card?.querySelector(".card-menu");
        if (!menu) return;

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
    filterCloseBtn?.addEventListener("click", () => setOverlayOpen(false));

    filterOverlay?.addEventListener("click", function (event) {
        if (event.target === filterOverlay) setOverlayOpen(false);
    });

    filterOverlay?.addEventListener("change", function (event) {
        const input = event.target;
        if (!(input instanceof HTMLInputElement) || input.type !== "checkbox") return;
        setFilterValue(input.dataset.filterGroup, input.value, input.checked);
        renderOptionGroup(input.dataset.filterGroup);
    });

    filterTabs.forEach((tab) => {
        tab.addEventListener("click", function () {
            activateFilterTab(tab.dataset.filterTab);
        });
    });

    Object.keys(optionGroups).forEach((groupName) => {
        optionGroups[groupName].search?.addEventListener("input", () => renderOptionGroup(groupName));
    });

    filterApplyBtn?.addEventListener("click", function () {
        collectFiltersFromForm();

        if (searchInput && filterSearchInput) {
            searchInput.value = filterSearchInput.value;
        }

        applySearchAndSort();
        setOverlayOpen(false);
    });

    filterClearBtn?.addEventListener("click", function () {
        resetFilterInputs();
        clearActiveFilters();

        if (searchInput) searchInput.value = "";
        if (filterSearchInput) filterSearchInput.value = "";

        renderAllOptionGroups();
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
            setOverlayOpen(false);
        }
    });

    renderAllOptionGroups();
    activateFilterTab("inquiry_period");
    applySearchAndSort();
})();
