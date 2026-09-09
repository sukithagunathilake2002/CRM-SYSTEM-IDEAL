(function () {
    const dialog = document.getElementById("allLeadsFilters");
    if (!dialog) return;
    const form = document.getElementById("allLeadsFilterForm");
    const groups = Array.from(dialog.querySelectorAll("[data-group]"));
    const error = document.getElementById("allLeadsFilterError");
    const submit = document.getElementById("allLeadsSubmit");
    const optionsStatus = document.getElementById("allLeadsOptionsStatus");
    const retry = document.getElementById("allLeadsRetry");
    const results = document.getElementById("allLeadsResults");
    const loadStatus = document.getElementById("allLeadsLoadStatus");
    let applied = JSON.parse(document.getElementById("allLeadsInitialSelection").textContent);
    let options = null;
    let loadingOptions = false;
    let pending = null;

    function closeDropdowns(except) {
        groups.forEach(group => { if (group !== except) group.open = false; });
    }
    function showError(message) {
        error.textContent = message;
        error.hidden = !message;
    }
    function updateCount(group) {
        const checked = group.querySelectorAll('input[type="checkbox"]:checked');
        group.querySelector("[data-selection-count]").textContent = checked.length ? `${checked.length} selected` : "None selected";
    }
    function updateConsultants(clearUnavailable = false) {
        const areaGroup = groups.find(group => group.dataset.group === "area_managers");
        const areas = Array.from(areaGroup.querySelectorAll('input:checked')).map(input => input.value);
        const consultants = groups.find(group => group.dataset.group === "consultants");
        const search = consultants.querySelector("[data-option-search]").value.toLowerCase();
        consultants.querySelectorAll("[data-option]").forEach(label => {
            const input = label.querySelector("input");
            const available = !areas.length || areas.includes(label.dataset.areaManager);
            label.hidden = !available || !label.textContent.toLowerCase().includes(search);
            if (clearUnavailable && !available) input.checked = false;
        });
        updateCount(consultants);
    }
    function syncSelection() {
        form.elements.from_date.value = applied.from_date || dialog.dataset.defaultFrom;
        form.elements.to_date.value = applied.to_date || dialog.dataset.defaultTo;
        groups.forEach(group => {
            const values = applied[group.dataset.group];
            const selected = Array.isArray(values) ? values.map(String) : [];
            group.querySelectorAll('input[type="checkbox"]').forEach(input => { input.checked = selected.includes(input.value); });
            group.querySelector("[data-option-search]").value = "";
            group.querySelectorAll("[data-option]").forEach(label => { label.hidden = false; });
            updateCount(group);
        });
        updateConsultants();
    }
    function renderOptions() {
        const yesNo = [{value: "yes", label: "Yes"}, {value: "no", label: "No"}];
        groups.forEach(group => {
            const key = group.dataset.group;
            const list = group.querySelector("[data-options]");
            list.replaceChildren();
            (options[key] || yesNo).forEach(option => {
                const label = document.createElement("label");
                label.dataset.option = "";
                if (option.area_manager !== undefined) label.dataset.areaManager = option.area_manager;
                const input = document.createElement("input");
                input.type = "checkbox";
                input.name = `${key}[]`;
                input.value = option.value;
                const text = document.createElement("span");
                text.textContent = option.label;
                label.append(input, text);
                list.appendChild(label);
            });
            if (!list.children.length) list.textContent = "No options available";
        });
        syncSelection();
    }
    async function loadOptions() {
        if (options || loadingOptions) return;
        loadingOptions = true;
        retry.hidden = true;
        optionsStatus.hidden = false;
        optionsStatus.textContent = "Loading filter options...";
        submit.disabled = true;
        try {
            const response = await fetch(dialog.dataset.optionsUrl, {headers: {Accept: "application/json"}});
            if (response.redirected) { window.location.assign(response.url); return; }
            if (!response.ok) throw new Error("Could not load filters. Please retry.");
            options = await response.json();
            renderOptions();
            submit.disabled = false;
            optionsStatus.hidden = true;
        } catch (failure) {
            optionsStatus.textContent = failure.message;
            retry.hidden = false;
        } finally { loadingOptions = false; }
    }
    function openFilters() {
        closeDropdowns();
        if (options) syncSelection();
        dialog.showModal();
        loadOptions();
    }
    document.querySelectorAll("[data-all-leads-open]").forEach(button => button.addEventListener("click", openFilters));
    dialog.querySelector("[data-all-leads-close]").addEventListener("click", () => dialog.close());
    dialog.addEventListener("click", event => {
        if (event.target === dialog) {
            const bounds = dialog.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
        }
    });
    retry.addEventListener("click", loadOptions);
    groups.forEach(group => {
        group.addEventListener("toggle", () => { if (group.open) closeDropdowns(group); });
        group.addEventListener("change", () => {
            updateCount(group);
            if (group.dataset.group === "area_managers") updateConsultants(true);
        });
        group.querySelector("[data-option-search]").addEventListener("input", event => {
            if (group.dataset.group === "consultants") { updateConsultants(); return; }
            const search = event.target.value.trim().toLowerCase();
            group.querySelectorAll("[data-option]").forEach(label => { label.hidden = !label.textContent.toLowerCase().includes(search); });
        });
    });
    document.addEventListener("click", event => { if (!event.target.closest(".all-leads-select")) closeDropdowns(); });
    document.getElementById("allLeadsClear").addEventListener("click", () => {
        form.elements.from_date.value = dialog.dataset.defaultFrom;
        form.elements.to_date.value = dialog.dataset.defaultTo;
        groups.forEach(group => {
            group.querySelectorAll('input[type="checkbox"]').forEach(input => { input.checked = false; });
            group.querySelector("[data-option-search]").value = "";
            group.querySelectorAll("[data-option]").forEach(label => { label.hidden = false; });
            updateCount(group);
        });
        showError("");
        closeDropdowns();
    });

    async function navigate(url, pushHistory = true) {
        pending?.abort();
        const controller = new AbortController();
        pending = controller;
        results.setAttribute("aria-busy", "true");
        loadStatus.textContent = "Loading leads...";
        loadStatus.hidden = false;
        submit.disabled = true;
        try {
            const response = await fetch(url, {headers: {Accept: "application/json", "X-All-Leads-Partial": "1"}, signal: controller.signal});
            if (response.redirected) { window.location.assign(response.url); return; }
            const data = await response.json().catch(() => { throw new Error("Could not load leads. Please try again."); });
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(" ") || data.message || "Could not load leads. Please try again.");
            if (controller.signal.aborted) return;
            results.innerHTML = data.html;
            applied = data.filters;
            if (pushHistory) window.history.pushState({}, "", data.url);
            dialog.close();
            showError("");
            loadStatus.hidden = true;
            results.scrollIntoView({block: "start", behavior: "instant"});
        } catch (failure) {
            if (failure.name !== "AbortError") {
                loadStatus.textContent = failure.message;
                if (dialog.open) showError(failure.message);
            }
        } finally {
            if (pending === controller) {
                results.removeAttribute("aria-busy");
                submit.disabled = !options;
                pending = null;
            }
        }
    }
    form.addEventListener("submit", event => {
        const from = form.elements.from_date.value;
        const to = form.elements.to_date.value;
        if (!!from !== !!to || (from && to < from)) {
            event.preventDefault();
            showError("Select both dates, with To Date on or after From Date, or leave both dates empty.");
            return;
        }
        if (!options) { event.preventDefault(); return; }
        if (!results) return;
        event.preventDefault();
        const url = new URL(form.action);
        url.search = new URLSearchParams(new FormData(form)).toString();
        navigate(url);
    });
    results?.addEventListener("click", event => {
        const link = event.target.closest(".epr-pagination a");
        if (!link || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        navigate(link.href);
    });
    if (results) window.addEventListener("popstate", () => navigate(window.location.href, false));
    if (dialog.dataset.hasErrors === "1") openFilters();
})();
