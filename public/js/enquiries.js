(function () {
    const results = document.getElementById("eprResults");
    if (!results) return;
    const search = document.getElementById("eprSearch");
    const sort = document.getElementById("eprSortBtn");
    const status = document.getElementById("eprLoadStatus");
    const overlay = document.getElementById("eprFilterOverlay");
    const filterSearch = document.getElementById("eprFilterSearch");
    const groupNames = ["model", "leadSource", "exchange", "followupType", "role"];
    const groupIds = {model: "Model", leadSource: "LeadSource", exchange: "Exchange", followupType: "FollowupType", role: "Role"};
    const dateIds = {inquiryFrom: "filterInquiryFrom", inquiryTo: "filterInquiryTo", dueFrom: "filterDueFrom", dueTo: "filterDueTo"};
    let selections = {};
    let options = null;
    let optionsRequest = null;
    let pending = null;
    let searchTimer;
    let currentUrl = new URL(window.location.href);

    function showStatus(message) {
        status.textContent = message;
        status.hidden = !message;
    }

    function readSelections() {
        groupNames.forEach((group) => {
            selections[group] = new Set(Array.from(currentUrl.searchParams.entries())
                .filter(([key]) => key === group || key.startsWith(`${group}[`))
                .map(([, value]) => value));
        });
        Object.entries(dateIds).forEach(([key, id]) => {
            document.getElementById(id).value = currentUrl.searchParams.get(key) || "";
        });
        filterSearch.value = search.value;
    }

    function setOverlay(open) {
        overlay.classList.toggle("open", open);
        overlay.setAttribute("aria-hidden", open ? "false" : "true");
        document.body.classList.toggle("filter-open", open);
    }

    function closeMenus() {
        results.querySelectorAll(".card-menu.open").forEach(menu => menu.classList.remove("open"));
        results.querySelectorAll(".menu-dot-btn[aria-expanded='true']").forEach(button => button.setAttribute("aria-expanded", "false"));
    }

    window.toggleCardMenu = function (button) {
        const menu = button.closest(".epr-card")?.querySelector(".card-menu");
        if (!menu) return;
        const open = menu.classList.contains("open");
        closeMenus();
        if (!open) {
            menu.classList.add("open");
            button.setAttribute("aria-expanded", "true");
        }
    };

    async function navigate(url, {scroll = false, history = true} = {}) {
        clearTimeout(searchTimer);
        pending?.abort();
        const controller = new AbortController();
        pending = controller;
        results.setAttribute("aria-busy", "true");
        showStatus("Loading enquiries...");
        try {
            const response = await fetch(url, {
                headers: {"X-Enquiry-Partial": "1", "Accept": "application/json"},
                signal: controller.signal,
            });
            if (response.redirected) {
                window.location.assign(response.url);
                return;
            }
            if (!response.ok) {
                const error = await response.json().catch(() => ({}));
                throw new Error(error.message || "Could not load enquiries. Please try again.");
            }
            const data = await response.json();
            if (controller.signal.aborted) return;
            results.innerHTML = data.html;
            currentUrl = new URL(url);
            if (history) window.history.pushState({}, "", currentUrl);
            showStatus("");
            if (scroll) {
                results.scrollIntoView({block: "start", behavior: "instant"});
                results.querySelector('[aria-current="page"]')?.focus({preventScroll: true});
            }
        } catch (error) {
            if (error.name !== "AbortError") showStatus(error.message);
        } finally {
            if (pending === controller) {
                pending = null;
                results.removeAttribute("aria-busy");
            }
        }
    }

    function queryUrl() {
        const url = new URL(currentUrl);
        url.searchParams.delete("page");
        url.searchParams.delete("filter_options");
        if (search.value.trim()) url.searchParams.set("q", search.value.trim());
        else url.searchParams.delete("q");
        url.searchParams.set("sort", sort.dataset.sort || "newest");
        return url;
    }

    results.addEventListener("click", event => {
        const link = event.target.closest(".epr-pagination a");
        if (!link || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
        event.preventDefault();
        navigate(new URL(link.href), {scroll: true});
    });
    search.addEventListener("input", () => {
        clearTimeout(searchTimer);
        pending?.abort();
        searchTimer = setTimeout(() => navigate(queryUrl()), 300);
    });
    search.addEventListener("keydown", event => {
        if (event.key === "Enter") { event.preventDefault(); navigate(queryUrl()); }
    });
    sort.addEventListener("click", () => {
        sort.dataset.sort = sort.dataset.sort === "oldest" ? "newest" : "oldest";
        sort.textContent = sort.dataset.sort === "oldest" ? "Sort: Old" : "Sort: New";
        navigate(queryUrl());
    });
    window.addEventListener("popstate", () => {
        currentUrl = new URL(window.location.href);
        search.value = currentUrl.searchParams.get("q") || "";
        sort.dataset.sort = currentUrl.searchParams.get("sort") === "oldest" ? "oldest" : "newest";
        sort.textContent = sort.dataset.sort === "oldest" ? "Sort: Old" : "Sort: New";
        readSelections();
        navigate(currentUrl, {history: false});
    });

    function renderGroup(group) {
        const list = document.getElementById(`filter${groupIds[group]}Options`);
        if (!list) return;
        list.replaceChildren();
        if (!options) { list.textContent = "Loading options..."; return; }
        const text = (document.getElementById(`filter${groupIds[group]}Search`)?.value || "").trim().toLowerCase();
        const filtered = (options[group] || []).filter(option => `${option.label} ${option.value}`.toLowerCase().includes(text));
        filtered.forEach(option => {
            const label = document.createElement("label");
            label.className = "epr-filter-choice";
            const span = document.createElement("span");
            span.textContent = option.label;
            const input = document.createElement("input");
            input.type = "checkbox";
            input.value = option.value;
            input.dataset.filterGroup = group;
            input.checked = selections[group].has(option.value);
            label.append(span, input);
            list.appendChild(label);
        });
        if (!filtered.length) list.textContent = "No matching options";
    }
    async function openFilters() {
        readSelections();
        setOverlay(true);
        groupNames.forEach(renderGroup);
        if (options) return;
        try {
            if (!optionsRequest) {
                const url = new URL(currentUrl);
                url.searchParams.set("filter_options", "1");
                optionsRequest = fetch(url, {headers: {"Accept": "application/json"}}).then(async response => {
                    if (!response.ok) throw new Error("Could not load filters. Close and reopen Filter to retry.");
                    return response.json();
                });
            }
            options = await optionsRequest;
            groupNames.forEach(renderGroup);
        } catch (error) {
            groupNames.forEach(group => {
                const list = document.getElementById(`filter${groupIds[group]}Options`);
                if (list) list.textContent = error.message;
            });
        } finally { optionsRequest = null; }
    }
    ["eprFilterBtn", "eprMenuFilterBtn"].forEach(id => document.getElementById(id)?.addEventListener("click", openFilters));
    document.getElementById("eprFilterClose").addEventListener("click", () => setOverlay(false));
    overlay.addEventListener("click", event => { if (event.target === overlay) setOverlay(false); });
    overlay.addEventListener("change", event => {
        const input = event.target;
        const group = input.dataset.filterGroup;
        if (!group || !selections[group]) return;
        if (input.checked) selections[group].add(input.value);
        else selections[group].delete(input.value);
    });
    groupNames.forEach(group => document.getElementById(`filter${groupIds[group]}Search`)?.addEventListener("input", () => renderGroup(group)));
    document.querySelectorAll("[data-filter-tab]").forEach(tab => tab.addEventListener("click", () => {
        document.querySelectorAll("[data-filter-tab]").forEach(item => item.classList.toggle("active", item === tab));
        document.querySelectorAll("[data-filter-panel]").forEach(panel => panel.classList.toggle("active", panel.dataset.filterPanel === tab.dataset.filterTab));
    }));
    function clearFilterParameters(url) {
        Array.from(url.searchParams.keys()).forEach(key => {
            if (groupNames.some(group => key === group || key.startsWith(`${group}[`)) || key in dateIds) url.searchParams.delete(key);
        });
    }
    document.getElementById("eprFilterApplyBtn").addEventListener("click", () => {
        search.value = filterSearch.value;
        const url = queryUrl();
        clearFilterParameters(url);
        groupNames.forEach(group => selections[group].forEach(value => url.searchParams.append(`${group}[]`, value)));
        Object.entries(dateIds).forEach(([key, id]) => {
            const value = document.getElementById(id).value;
            if (value) url.searchParams.set(key, value);
        });
        setOverlay(false);
        navigate(url);
    });
    document.getElementById("eprFilterClearBtn").addEventListener("click", () => {
        search.value = "";
        const url = queryUrl();
        clearFilterParameters(url);
        groupNames.forEach(group => {
            selections[group] = new Set();
            const input = document.getElementById(`filter${groupIds[group]}Search`);
            if (input) input.value = "";
        });
        Object.values(dateIds).forEach(id => { document.getElementById(id).value = ""; });
        filterSearch.value = "";
        groupNames.forEach(renderGroup);
        navigate(url);
    });
    document.addEventListener("click", event => {
        if (!event.target.closest(".menu-dot-btn") && !event.target.closest(".card-menu")) closeMenus();
    });
    document.addEventListener("keydown", event => { if (event.key === "Escape") setOverlay(false); });
    readSelections();
})();
