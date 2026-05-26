(function () {
    const form = document.getElementById('prospectForm');
    if (!form) {
        return;
    }

    const steps = Array.from(document.querySelectorAll('.prospect-step'));
    const stepButtons = Array.from(document.querySelectorAll('[data-step-button]'));
    const activeStepInput = document.getElementById('active_step');
    const exitAfterSaveInput = document.getElementById('exit_after_save');
    const backBtn = document.getElementById('backBtn');
    const nextBtn = document.getElementById('nextBtn');
    const saveExitBtn = document.getElementById('saveExitBtn');

    const interestedEditToggle = document.getElementById('toggleInterestedVehicleEdit');
    const interestedEditFields = document.getElementById('interestedVehicleEditFields');
    const interestedModelSelect = document.getElementById('interested_model');
    const interestedEngineSelect = document.getElementById('interested_engine');
    const interestedVariantSelect = document.getElementById('interested_variant');
    const sourceInfoSelect = document.getElementById('source_of_information');

    const exchangeImageToggle = document.getElementById('addExchangeImages');
    const exchangeImageFields = document.getElementById('exchangeImageFields');
    const addMoreExchangeImagesBtn = document.getElementById('addMoreExchangeImagesBtn');
    const extraExchangeImagesContainer = document.getElementById('extraExchangeImagesContainer');

    const exchangeExpectedPriceInput = document.querySelector('input[name="exchange_expected_price"]');
    const exchangeQuotedPriceInput = document.querySelector('input[name="exchange_quoted_price"]');
    const exchangeDifferenceInput = document.querySelector('input[name="exchange_price_difference"]');
    const offerEditCheckbox = document.getElementById('allowOfferEdit');
    const offerUnitPriceInput = document.getElementById('offer_unit_price');
    const offerUnitDiscountInput = document.getElementById('offer_unit_price_discount');
    const offerUnitFreeInput = document.getElementById('offer_unit_price_free');
    const offerVatAmountInput = document.getElementById('offer_vat_amount');
    const offerVatDiscountInput = document.getElementById('offer_vat_discount');
    const offerVatFreeInput = document.getElementById('offer_vat_free');
    const offerTotalCostInput = document.getElementById('offer_total_cost');
    const offerTotalDiscountInput = document.getElementById('offer_total_discount');
    const offerFinalPriceInput = document.getElementById('offer_final_price');
    const offerTotalCostDisplay = document.getElementById('offerTotalCostDisplay');
    const offerTotalDiscountDisplay = document.getElementById('offerTotalDiscountDisplay');
    const offerFinalPriceDisplay = document.getElementById('offerFinalPriceDisplay');
    const offerSummaryModal = document.getElementById('offerSummaryModal');
    const summaryLooksGoodBtn = document.getElementById('summaryLooksGoodBtn');
    const summaryModalCloseBtn = document.getElementById('summaryModalCloseBtn');
    const summaryInterestedVehicle = document.getElementById('summaryInterestedVehicle');
    const summaryVatCost = document.getElementById('summaryVatCost');
    const summaryVatOffer = document.getElementById('summaryVatOffer');
    const summaryVatPayable = document.getElementById('summaryVatPayable');
    const summaryUnitCost = document.getElementById('summaryUnitCost');
    const summaryUnitOffer = document.getElementById('summaryUnitOffer');
    const summaryUnitPayable = document.getElementById('summaryUnitPayable');
    const summaryTotalCost = document.getElementById('summaryTotalCost');
    const summaryTotalOffer = document.getElementById('summaryTotalOffer');
    const summaryFinalPrice = document.getElementById('summaryFinalPrice');
    const mobileNumbersInput = form.querySelector('input[name="mobile_numbers"]');
    const addContactNumberBtn = document.getElementById('addContactNumberBtn');
    const customerRemarkPreset = document.getElementById('customerRemarkPreset');
    const rescheduleFollowupToggle = document.getElementById('rescheduleFollowupToggle');
    const rescheduleFields = document.getElementById('rescheduleFields');
    const exchangePreviewObjectUrls = new WeakMap();

    let currentStep = parseInt(form.dataset.initialStep || '1', 10);
    if (Number.isNaN(currentStep) || currentStep < 1 || currentStep > 5) {
        currentStep = 1;
    }

    function updateStepper() {
        steps.forEach((stepEl) => {
            const stepNo = parseInt(stepEl.dataset.step, 10);
            stepEl.classList.toggle('active', stepNo === currentStep);
        });

        stepButtons.forEach((btn) => {
            const stepNo = parseInt(btn.dataset.stepButton, 10);
            btn.classList.toggle('active', stepNo === currentStep);
            btn.classList.toggle('complete', stepNo < currentStep);
        });

        activeStepInput.value = currentStep;
        nextBtn.textContent = currentStep === 5 ? 'Submit' : 'Next';
    }

    function selectedValue(name) {
        const selected = document.querySelector(`input[name="${name}"]:checked`);
        return selected ? selected.value : '';
    }

    function updateConditionals() {
        document.querySelectorAll('[data-conditional]').forEach((block) => {
            const fieldName = block.dataset.conditional;
            const expectedValue = block.dataset.value;
            const currentValue = selectedValue(fieldName);
            block.style.display = currentValue === expectedValue ? 'block' : 'none';
        });
    }

    function updateCompetitionModels() {
        const brandSelect = document.getElementById('competition_brand');
        const modelSelect = document.getElementById('competition_model');
        if (!brandSelect || !modelSelect) {
            return;
        }

        const map = window.PROSPECT_COMPETITION_MAP || {};
        const models = map[brandSelect.value] || [];
        const selectedFromServer = modelSelect.dataset.selectedModel || '';
        const activeModel = modelSelect.value || selectedFromServer;

        modelSelect.innerHTML = '<option value="">Select Model</option>';

        models.forEach((model) => {
            const option = document.createElement('option');
            option.value = model;
            option.textContent = model.toUpperCase();
            if (model === activeModel) {
                option.selected = true;
            }
            modelSelect.appendChild(option);
        });

        modelSelect.dataset.selectedModel = '';
    }

    function updateSourceInformationOptions() {
        if (!sourceInfoSelect) {
            return;
        }

        const selectedLeadSource = selectedValue('lead_source');
        const sourceMap = window.PROSPECT_SOURCE_INFO_MAP || {};
        const sourceOptions = sourceMap[selectedLeadSource] || [];
        const selectedFromServer = sourceInfoSelect.dataset.selectedSourceInfo || sourceInfoSelect.value;

        sourceInfoSelect.innerHTML = '<option value="">Select Source of Information</option>';

        sourceOptions.forEach((sourceOption) => {
            const option = document.createElement('option');
            option.value = sourceOption;
            option.textContent = sourceOption;
            if (sourceOption === selectedFromServer) {
                option.selected = true;
            }
            sourceInfoSelect.appendChild(option);
        });

        sourceInfoSelect.dataset.selectedSourceInfo = '';
        sourceInfoSelect.disabled = selectedLeadSource === '';
    }

    function setPersonalEditable(isEditable) {
        document.querySelectorAll('.lockable').forEach((input) => {
            input.readOnly = !isEditable;
        });

        document.querySelectorAll('.lockable-select').forEach((select) => {
            select.disabled = !isEditable;
        });

        document.querySelectorAll('.lockable-choice').forEach((choice) => {
            choice.disabled = !isEditable;
        });

        if (addContactNumberBtn) {
            addContactNumberBtn.disabled = !isEditable;
        }
    }

    function setSelectOptions(selectEl, placeholder, options, selectedValueLocal) {
        if (!selectEl) {
            return;
        }

        selectEl.innerHTML = `<option value="">${placeholder}</option>`;

        options.forEach((optionValue) => {
            const option = document.createElement('option');
            option.value = optionValue;
            option.textContent = optionValue;
            if (optionValue === selectedValueLocal) {
                option.selected = true;
            }
            selectEl.appendChild(option);
        });
    }

    async function loadInterestedVariants(selectedVariant = '') {
        if (!interestedModelSelect || !interestedEngineSelect || !interestedVariantSelect) {
            return;
        }

        const model = interestedModelSelect.value;
        const engine = interestedEngineSelect.value;

        if (!model || !engine) {
            setSelectOptions(interestedVariantSelect, 'Select Variant', [], '');
            return;
        }

        try {
            const response = await fetch(`/get-variants/${encodeURIComponent(model)}/${encodeURIComponent(engine)}`);
            const data = await response.json();
            const variants = data.map((item) => item.variant).filter(Boolean);
            setSelectOptions(interestedVariantSelect, 'Select Variant', variants, selectedVariant);
        } catch (error) {
            console.error('Failed to load variants', error);
            setSelectOptions(interestedVariantSelect, 'Select Variant', [], '');
        }
    }

    async function loadInterestedEngines(selectedEngine = '', selectedVariant = '') {
        if (!interestedModelSelect || !interestedEngineSelect || !interestedVariantSelect) {
            return;
        }

        const model = interestedModelSelect.value;
        if (!model) {
            setSelectOptions(interestedEngineSelect, 'Select Engine Type', [], '');
            setSelectOptions(interestedVariantSelect, 'Select Variant', [], '');
            return;
        }

        try {
            const response = await fetch(`/get-engines/${encodeURIComponent(model)}`);
            const data = await response.json();
            const engines = data.map((item) => item.engine_type).filter(Boolean);
            setSelectOptions(interestedEngineSelect, 'Select Engine Type', engines, selectedEngine);
            await loadInterestedVariants(selectedVariant);
        } catch (error) {
            console.error('Failed to load engines', error);
            setSelectOptions(interestedEngineSelect, 'Select Engine Type', [], '');
            setSelectOptions(interestedVariantSelect, 'Select Variant', [], '');
        }
    }

    async function syncInterestedVehicleSelectionFromServerData() {
        if (!interestedModelSelect || !interestedEngineSelect || !interestedVariantSelect) {
            return;
        }

        const selectedModel = interestedModelSelect.dataset.selectedModel || interestedModelSelect.value;
        const selectedEngine = interestedEngineSelect.dataset.selectedEngine || '';
        const selectedVariant = interestedVariantSelect.dataset.selectedVariant || '';

        if (selectedModel) {
            interestedModelSelect.value = selectedModel;
        }

        await loadInterestedEngines(selectedEngine, selectedVariant);

        interestedEngineSelect.dataset.selectedEngine = '';
        interestedVariantSelect.dataset.selectedVariant = '';
    }

    function setInterestedVehicleEditEnabled(isEnabled) {
        if (!interestedEditFields) {
            return;
        }

        interestedEditFields.style.display = isEnabled ? 'grid' : 'none';

        interestedEditFields.querySelectorAll('select').forEach((selectEl) => {
            selectEl.disabled = !isEnabled;
        });
    }

    function updateRescheduleVisibility() {
        if (!rescheduleFollowupToggle || !rescheduleFields) {
            return;
        }

        rescheduleFields.style.display = rescheduleFollowupToggle.checked ? 'block' : 'none';
    }
    function updateExchangeImageVisibility() {
        if (!exchangeImageToggle || !exchangeImageFields) {
            return;
        }

        const interestedExchange = selectedValue('interested_in_exchange') === 'yes';

        if (!interestedExchange) {
            exchangeImageToggle.checked = false;
            exchangeImageToggle.disabled = true;
            exchangeImageFields.style.display = 'none';
            return;
        }

        exchangeImageToggle.disabled = false;
        exchangeImageFields.style.display = exchangeImageToggle.checked ? 'block' : 'none';
    }

    function updateExchangeDifference() {
        if (!exchangeExpectedPriceInput || !exchangeQuotedPriceInput || !exchangeDifferenceInput) {
            return;
        }

        const expectedPrice = parseFloat(exchangeExpectedPriceInput.value);
        const quotedPrice = parseFloat(exchangeQuotedPriceInput.value);

        if (Number.isFinite(expectedPrice) && Number.isFinite(quotedPrice)) {
            exchangeDifferenceInput.value = (expectedPrice - quotedPrice).toFixed(2);
        } else {
            exchangeDifferenceInput.value = '';
        }
    }

    function isOfferEditable() {
        return offerEditCheckbox ? offerEditCheckbox.checked : true;
    }
    function toNonNegativeNumber(value) {
        const parsed = parseFloat(value);
        return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
    }

    function setOfferDisplayValue(element, value) {
        if (element) {
            element.textContent = value.toFixed(2);
        }
    }

    function formatCompactInputNumber(value) {
        if (!Number.isFinite(value)) {
            return '0';
        }

        const rounded = Math.round(value * 100) / 100;
        if (Math.abs(rounded) < 0.005) {
            return '0';
        }

        if (Number.isInteger(rounded)) {
            return String(rounded);
        }

        return rounded.toFixed(2).replace(/\.?0+$/, '');
    }

    function formatSummaryNumber(value) {
        if (!Number.isFinite(value)) {
            return '0';
        }

        const rounded = Math.round(value * 100) / 100;
        return rounded.toLocaleString('en-US', {
            minimumFractionDigits: rounded % 1 === 0 ? 0 : 2,
            maximumFractionDigits: 2,
        });
    }

    function updateOfferTotals() {
        if (!offerUnitPriceInput || !offerVatAmountInput || !offerTotalCostInput || !offerTotalDiscountInput || !offerFinalPriceInput) {
            return;
        }

        const unitPrice = toNonNegativeNumber(offerUnitPriceInput.value);
        const vatAmount = toNonNegativeNumber(offerVatAmountInput.value);

        const isUnitFree = !!offerUnitFreeInput?.checked;
        const isVatFree = !!offerVatFreeInput?.checked;
        const offerEditable = isOfferEditable();

        if (offerUnitFreeInput) {
            offerUnitFreeInput.disabled = !offerEditable;
        }

        if (offerVatFreeInput) {
            offerVatFreeInput.disabled = !offerEditable;
        }

        let unitDiscount = toNonNegativeNumber(offerUnitDiscountInput?.value ?? 0);
        let vatDiscount = toNonNegativeNumber(offerVatDiscountInput?.value ?? 0);

        if (isUnitFree) {
            unitDiscount = unitPrice;
            if (offerUnitDiscountInput) {
                offerUnitDiscountInput.value = formatCompactInputNumber(unitPrice);
                offerUnitDiscountInput.readOnly = true;
            }
        } else if (offerUnitDiscountInput) {
            if (unitDiscount > unitPrice) {
                unitDiscount = unitPrice;
                offerUnitDiscountInput.value = formatCompactInputNumber(unitDiscount);
            }
            offerUnitDiscountInput.readOnly = !isOfferEditable();
        }

        if (isVatFree) {
            vatDiscount = vatAmount;
            if (offerVatDiscountInput) {
                offerVatDiscountInput.value = formatCompactInputNumber(vatAmount);
                offerVatDiscountInput.readOnly = true;
            }
        } else if (offerVatDiscountInput) {
            if (vatDiscount > vatAmount) {
                vatDiscount = vatAmount;
                offerVatDiscountInput.value = formatCompactInputNumber(vatDiscount);
            }
            offerVatDiscountInput.readOnly = !isOfferEditable();
        }

        if (offerUnitDiscountInput) {
            offerUnitDiscountInput.value = formatCompactInputNumber(unitDiscount);
        }

        if (offerVatDiscountInput) {
            offerVatDiscountInput.value = formatCompactInputNumber(vatDiscount);
        }

        const totalCost = unitPrice + vatAmount;
        const totalDiscount = unitDiscount + vatDiscount;
        const finalPrice = Math.max(0, totalCost - totalDiscount);

        offerTotalCostInput.value = totalCost.toFixed(2);
        offerTotalDiscountInput.value = totalDiscount.toFixed(2);
        offerFinalPriceInput.value = finalPrice.toFixed(2);

        setOfferDisplayValue(offerTotalCostDisplay, totalCost);
        setOfferDisplayValue(offerTotalDiscountDisplay, totalDiscount);
        setOfferDisplayValue(offerFinalPriceDisplay, finalPrice);
    }

    function openOfferSummaryModal() {
        if (!offerSummaryModal) {
            return;
        }

        offerSummaryModal.classList.add('active');
        document.body.classList.add('modal-open');
    }

    function closeOfferSummaryModal() {
        if (!offerSummaryModal) {
            return;
        }

        offerSummaryModal.classList.remove('active');
        document.body.classList.remove('modal-open');
    }

    function updateOfferSummaryModal() {
        const interestedVehicleLabel = document.getElementById('offerInterestedVehicleLabel');
        if (summaryInterestedVehicle && interestedVehicleLabel) {
            summaryInterestedVehicle.textContent = interestedVehicleLabel.textContent.trim();
        }

        const unitCost = toNonNegativeNumber(offerUnitPriceInput?.value);
        const vatCost = toNonNegativeNumber(offerVatAmountInput?.value);

        let unitOffer = toNonNegativeNumber(offerUnitDiscountInput?.value);
        let vatOffer = toNonNegativeNumber(offerVatDiscountInput?.value);

        if (offerUnitFreeInput?.checked) {
            unitOffer = unitCost;
        }
        if (offerVatFreeInput?.checked) {
            vatOffer = vatCost;
        }

        unitOffer = Math.min(unitOffer, unitCost);
        vatOffer = Math.min(vatOffer, vatCost);

        const unitPayable = Math.max(0, unitCost - unitOffer);
        const vatPayable = Math.max(0, vatCost - vatOffer);
        const isVatFreeSummary = !!offerVatFreeInput?.checked || (vatCost > 0 && Math.abs(vatCost - vatOffer) < 0.005);
        const isUnitFreeSummary = !!offerUnitFreeInput?.checked || (unitCost > 0 && Math.abs(unitCost - unitOffer) < 0.005);
        const totalCost = toNonNegativeNumber(offerTotalCostInput?.value);
        const totalOffer = toNonNegativeNumber(offerTotalDiscountInput?.value);
        const finalPrice = toNonNegativeNumber(offerFinalPriceInput?.value);

        if (summaryVatCost) summaryVatCost.textContent = isVatFreeSummary ? 'Free' : formatSummaryNumber(vatCost);
        if (summaryVatOffer) summaryVatOffer.textContent = isVatFreeSummary ? 'Free' : formatSummaryNumber(vatOffer);
        if (summaryVatPayable) summaryVatPayable.textContent = formatSummaryNumber(vatPayable);

        if (summaryUnitCost) summaryUnitCost.textContent = formatSummaryNumber(unitCost);
        if (summaryUnitOffer) summaryUnitOffer.textContent = isUnitFreeSummary ? 'Free' : formatSummaryNumber(unitOffer);
        if (summaryUnitPayable) summaryUnitPayable.textContent = formatSummaryNumber(unitPayable);

        if (summaryTotalCost) summaryTotalCost.textContent = formatSummaryNumber(totalCost);
        if (summaryTotalOffer) summaryTotalOffer.textContent = formatSummaryNumber(totalOffer);
        if (summaryFinalPrice) summaryFinalPrice.textContent = formatSummaryNumber(finalPrice);
    }

    function addExtraExchangeImageRow() {
        if (!extraExchangeImagesContainer) {
            return;
        }

        const tileCount = extraExchangeImagesContainer.querySelectorAll('.extra-image-row').length;
        const nextPictureNo = tileCount + 3;

        const row = document.createElement('div');
        row.className = 'extra-image-row';
        row.innerHTML = `
            <label class="exchange-upload-tile exchange-upload-tile-extra" data-upload-tile>
                <span class="exchange-upload-text">Car picture ${nextPictureNo}</span>
                <img class="exchange-upload-preview" alt="Car picture ${nextPictureNo} preview" hidden>
                <button type="button" class="extra-image-remove-top" aria-label="Remove image slot">-</button>
                <input type="file" name="extra_exchange_images[]" accept="image/*">
            </label>
        `;

        extraExchangeImagesContainer.appendChild(row);

        const newInput = row.querySelector('input[type="file"]');
        if (newInput) {
            bindExchangeUploadPreview(newInput);
        }

        renumberExtraExchangeRows();
    }

    function renumberExtraExchangeRows() {
        if (!extraExchangeImagesContainer) {
            return;
        }

        const rows = Array.from(extraExchangeImagesContainer.querySelectorAll('.extra-image-row'));
        rows.forEach((row, index) => {
            const pictureNo = index + 3;
            const textEl = row.querySelector('.exchange-upload-text');
            const previewEl = row.querySelector('.exchange-upload-preview');
            if (textEl) {
                textEl.textContent = `Car picture ${pictureNo}`;
            }
            if (previewEl) {
                previewEl.alt = `Car picture ${pictureNo} preview`;
            }
        });
    }

    function removeExtraExchangeImageRow(buttonEl) {
        if (!extraExchangeImagesContainer || !buttonEl) {
            return;
        }

        const row = buttonEl.closest('.extra-image-row');
        if (!row) {
            return;
        }

        const fileInput = row.querySelector('input[type="file"]');
        if (fileInput) {
            const previousObjectUrl = exchangePreviewObjectUrls.get(fileInput);
            if (previousObjectUrl) {
                URL.revokeObjectURL(previousObjectUrl);
                exchangePreviewObjectUrls.delete(fileInput);
            }
        }

        row.remove();
        renumberExtraExchangeRows();
    }

    function applyExchangePreviewToTile(inputEl, sourceUrl) {
        const tile = inputEl.closest('[data-upload-tile]');
        if (!tile) {
            return;
        }

        const previewEl = tile.querySelector('.exchange-upload-preview');
        const textEl = tile.querySelector('.exchange-upload-text');

        if (!previewEl) {
            return;
        }

        if (!sourceUrl) {
            previewEl.hidden = true;
            previewEl.removeAttribute('src');
            tile.classList.remove('has-preview');
            if (textEl) {
                textEl.hidden = false;
            }
            return;
        }

        previewEl.src = sourceUrl;
        previewEl.hidden = false;
        tile.classList.add('has-preview');
        if (textEl) {
            textEl.hidden = true;
        }
    }

    function bindExchangeUploadPreview(inputEl) {
        if (!inputEl) {
            return;
        }

        const existingSrc = String(inputEl.dataset.existingSrc || '').trim();
        if (existingSrc !== '') {
            applyExchangePreviewToTile(inputEl, existingSrc);
        }

        inputEl.addEventListener('change', () => {
            const previousObjectUrl = exchangePreviewObjectUrls.get(inputEl);
            if (previousObjectUrl) {
                URL.revokeObjectURL(previousObjectUrl);
                exchangePreviewObjectUrls.delete(inputEl);
            }

            const file = inputEl.files && inputEl.files[0] ? inputEl.files[0] : null;
            if (!file) {
                const fallbackExisting = String(inputEl.dataset.existingSrc || '').trim();
                applyExchangePreviewToTile(inputEl, fallbackExisting);
                return;
            }

            const objectUrl = URL.createObjectURL(file);
            exchangePreviewObjectUrls.set(inputEl, objectUrl);
            applyExchangePreviewToTile(inputEl, objectUrl);
        });
    }

    stepButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            closeOfferSummaryModal();
            currentStep = parseInt(btn.dataset.stepButton, 10);
            updateStepper();
            updateConditionals();
            updateExchangeImageVisibility();
        });
    });

    document.querySelectorAll('input[type="radio"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            updateConditionals();
            updateSourceInformationOptions();
            updateExchangeImageVisibility();
        });
    });

    const brandSelect = document.getElementById('competition_brand');
    if (brandSelect) {
        brandSelect.addEventListener('change', updateCompetitionModels);
    }

    if (interestedModelSelect) {
        interestedModelSelect.addEventListener('change', () => {
            loadInterestedEngines();
        });
    }

    if (interestedEngineSelect) {
        interestedEngineSelect.addEventListener('change', () => {
            loadInterestedVariants();
        });
    }

    if (interestedEditToggle) {
        interestedEditToggle.addEventListener('change', (event) => {
            const enabled = event.target.checked;
            setInterestedVehicleEditEnabled(enabled);

            if (enabled) {
                syncInterestedVehicleSelectionFromServerData();
            }
        });

        setInterestedVehicleEditEnabled(interestedEditToggle.checked);
    }

    if (exchangeImageToggle) {
        exchangeImageToggle.addEventListener('change', updateExchangeImageVisibility);
    }
    if (rescheduleFollowupToggle) {
        rescheduleFollowupToggle.addEventListener('change', updateRescheduleVisibility);
    }

    if (exchangeExpectedPriceInput) {
        exchangeExpectedPriceInput.addEventListener('input', updateExchangeDifference);
    }
    if (exchangeQuotedPriceInput) {
        exchangeQuotedPriceInput.addEventListener('input', updateExchangeDifference);
    }

    if (addMoreExchangeImagesBtn) {
        addMoreExchangeImagesBtn.addEventListener('click', addExtraExchangeImageRow);
    }

    if (extraExchangeImagesContainer) {
        extraExchangeImagesContainer.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const removeButton = target.closest('.extra-image-remove-top');
            if (removeButton) {
                event.preventDefault();
                event.stopPropagation();
                removeExtraExchangeImageRow(removeButton);
            }
        });
    }

    if (offerUnitDiscountInput) {
        offerUnitDiscountInput.addEventListener('input', updateOfferTotals);
    }

    if (offerVatDiscountInput) {
        offerVatDiscountInput.addEventListener('input', updateOfferTotals);
    }

    if (offerUnitFreeInput) {
        offerUnitFreeInput.addEventListener('change', () => {
            if (!offerUnitFreeInput.checked && offerUnitDiscountInput) {
                offerUnitDiscountInput.value = '0';
            }

            updateOfferTotals();
        });
    }

    if (offerVatFreeInput) {
        offerVatFreeInput.addEventListener('change', () => {
            if (!offerVatFreeInput.checked && offerVatDiscountInput) {
                offerVatDiscountInput.value = '0';
            }

            updateOfferTotals();
        });
    }

    if (offerEditCheckbox) {
        offerEditCheckbox.addEventListener('change', updateOfferTotals);
    }

    if (backBtn) {
        backBtn.addEventListener('click', () => {
            closeOfferSummaryModal();

            if (currentStep > 1) {
                currentStep -= 1;
                updateStepper();
                updateConditionals();
                updateExchangeImageVisibility();
                return;
            }

            window.location.href = '/epr';
        });
    }
    nextBtn.addEventListener('click', () => {
        if (currentStep === 4 && offerSummaryModal) {
            updateOfferTotals();
            updateOfferSummaryModal();
            openOfferSummaryModal();
            return;
        }

        exitAfterSaveInput.value = '0';
        form.requestSubmit();
    });

    saveExitBtn.addEventListener('click', () => {
        closeOfferSummaryModal();
        exitAfterSaveInput.value = '1';
        form.requestSubmit();
    });

    if (summaryLooksGoodBtn) {
        summaryLooksGoodBtn.addEventListener('click', () => {
            closeOfferSummaryModal();
            exitAfterSaveInput.value = '0';
            form.requestSubmit();
        });
    }

    if (summaryModalCloseBtn) {
        summaryModalCloseBtn.addEventListener('click', () => {
            closeOfferSummaryModal();
        });
    }

    if (offerSummaryModal) {
        offerSummaryModal.addEventListener('click', (event) => {
            if (event.target === offerSummaryModal) {
                closeOfferSummaryModal();
            }
        });
    }

    if (addContactNumberBtn && mobileNumbersInput) {
        addContactNumberBtn.addEventListener('click', () => {
            const currentValue = mobileNumbersInput.value.trim();
            mobileNumbersInput.value = currentValue ? `${currentValue}, ` : '';
            mobileNumbersInput.focus();
            mobileNumbersInput.setSelectionRange(mobileNumbersInput.value.length, mobileNumbersInput.value.length);
        });
    }

    if (customerRemarkPreset && !customerRemarkPreset.value) {
        const firstTemplateOption = Array.from(customerRemarkPreset.options).find((option) => option.value);
        if (firstTemplateOption) {
            customerRemarkPreset.value = firstTemplateOption.value;
        }
    }

    const personalEditCheckbox = document.getElementById('allowPersonalEdit');
    if (personalEditCheckbox) {
        personalEditCheckbox.addEventListener('change', (event) => {
            setPersonalEditable(event.target.checked);
        });

        const hasMandatoryPersonalData = selectedValue('customer_type') && selectedValue('profession');
        if (hasMandatoryPersonalData) {
            setPersonalEditable(false);
        } else {
            personalEditCheckbox.checked = true;
            setPersonalEditable(true);
        }
    }

    form.addEventListener('submit', () => {
        document.querySelectorAll('.lockable-select, .lockable-choice').forEach((field) => {
            field.disabled = false;
        });

        if (interestedEditToggle && interestedEditToggle.checked) {
            interestedEditFields?.querySelectorAll('select').forEach((selectEl) => {
                selectEl.disabled = false;
            });
        }

        if (sourceInfoSelect && !sourceInfoSelect.disabled) {
            sourceInfoSelect.disabled = false;
        }
    });

    updateStepper();
    updateConditionals();
    updateCompetitionModels();
    updateSourceInformationOptions();
    syncInterestedVehicleSelectionFromServerData();
    updateExchangeImageVisibility();
    updateRescheduleVisibility();
    updateExchangeDifference();
    updateOfferTotals();

    form.querySelectorAll('#exchangeImageFields input[type="file"]').forEach((inputEl) => {
        bindExchangeUploadPreview(inputEl);
    });

    renumberExtraExchangeRows();
})();









