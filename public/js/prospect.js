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

    const interestedEditFields = document.getElementById('interestedVehicleEditFields');
    const interestedModelSelect = document.getElementById('interested_model');
    const interestedEngineSelect = document.getElementById('interested_engine');
    const interestedVariantSelect = document.getElementById('interested_variant');
    const sourceInfoSelect = document.getElementById('source_of_information');

    const exchangeImageToggle = document.getElementById('addExchangeImages');
    const exchangeImageFields = document.getElementById('exchangeImageFields');
    const addMoreExchangeImagesBtn = document.getElementById('addMoreExchangeImagesBtn');
    const extraExchangeImagesContainer = document.getElementById('extraExchangeImagesContainer');
    const exchangeBrandSelect = document.getElementById('exchange_vehicle_brand');
    const exchangeModelSelect = document.getElementById('exchange_vehicle_model');

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
    const prospectOfferSummaryPanel = document.getElementById('prospectOfferSummaryPanel');
    const prospectOfferEditGroup = document.getElementById('prospectOfferEditGroup');
    const offerRemarksToggle = document.getElementById('offerRemarksToggle');
    const offerRemarksText = document.getElementById('offerRemarksText');
    const prospectOfferSummaryVatCost = document.getElementById('prospectOfferSummaryVatCost');
    const prospectOfferSummaryVatOffer = document.getElementById('prospectOfferSummaryVatOffer');
    const prospectOfferSummaryVatPayable = document.getElementById('prospectOfferSummaryVatPayable');
    const prospectOfferSummaryUnitCost = document.getElementById('prospectOfferSummaryUnitCost');
    const prospectOfferSummaryUnitOffer = document.getElementById('prospectOfferSummaryUnitOffer');
    const prospectOfferSummaryUnitPayable = document.getElementById('prospectOfferSummaryUnitPayable');
    const prospectOfferSummaryTotalCost = document.getElementById('prospectOfferSummaryTotalCost');
    const prospectOfferSummaryTotalOffer = document.getElementById('prospectOfferSummaryTotalOffer');
    const prospectOfferSummaryFinalPrice = document.getElementById('prospectOfferSummaryFinalPrice');
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
    const prospectContactList = document.getElementById('prospectContactList');
    const customerRemarkPreset = document.getElementById('customerRemarkPreset');
    const rescheduleFollowupToggle = document.getElementById('rescheduleFollowupToggle');
    const rescheduleFields = document.getElementById('rescheduleFields');
    const personalStep = document.querySelector('.personal-step');
    const stepEditToggles = Array.from(document.querySelectorAll('[data-step-edit-toggle]'));
    const summaryFields = {};
    const exchangePreviewObjectUrls = new WeakMap();

    document.querySelectorAll('[data-summary-field]').forEach((field) => {
        const key = field.dataset.summaryField;
        if (!summaryFields[key]) {
            summaryFields[key] = [];
        }
        summaryFields[key].push(field);
    });

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
        form.closest('.prospect-page')?.setAttribute('data-current-step', String(currentStep));
        nextBtn.textContent = currentStep === 5 ? 'Submit' : 'Save & Next';
        updateProspectSummary();
    }

    function selectedValue(name) {
        const selected = document.querySelector(`input[name="${name}"]:checked`);
        return selected ? selected.value : '';
    }

    function fieldValue(selector) {
        const field = form.querySelector(selector);
        return field ? String(field.value || '').trim() : '';
    }

    function selectedText(selectEl) {
        if (!selectEl) {
            return '';
        }

        const option = selectEl.options[selectEl.selectedIndex];
        return option && option.value ? option.textContent.trim() : '';
    }

    function displayChoice(value) {
        return String(value || '')
            .replace(/[_-]+/g, ' ')
            .split(/\s+/)
            .filter(Boolean)
            .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
            .join(' ');
    }

    function moneyValue(value) {
        const parsed = parseFloat(value);
        if (!Number.isFinite(parsed)) {
            return '';
        }

        return parsed.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    }

    function setSummaryField(key, value) {
        const fields = summaryFields[key];
        if (!fields) {
            return;
        }

        const normalized = String(value || '').trim();
        fields.forEach((field) => {
            field.textContent = normalized || 'N/A';

            const row = field.closest('.summary-row');
            if (row && row.classList.contains('summary-detail-row')) {
                row.classList.toggle('summary-empty', normalized === '' || normalized === 'N/A');
            }
        });
    }

    function updateProspectSummary() {
        const title = fieldValue('select[name="title"]');
        const name = fieldValue('input[name="name"]');
        const mobileNumbers = fieldValue('input[name="mobile_numbers"]');
        const dateOfBirth = fieldValue('input[name="date_of_birth"]');
        const location = fieldValue('input[name="location"]');
        const district = fieldValue('select[name="district"]');
        const customerType = selectedValue('customer_type');
        const profession = selectedValue('profession');

        const selectedInterestedModel = selectedText(interestedModelSelect);
        const selectedInterestedEngine = selectedText(interestedEngineSelect);
        const selectedInterestedVariant = selectedText(interestedVariantSelect);
        const editedVehicle = [selectedInterestedModel, selectedInterestedEngine, selectedInterestedVariant].filter(Boolean).join(' ');
        const currentVehicle = editedVehicle || document.querySelector('.buying-step .vehicle-pill')?.textContent?.replace(/\s*\/\s*/g, ' ').trim() || '';

        const leadSource = selectedValue('lead_source');
        const sourceInfo = sourceInfoSelect?.value || '';
        const quoteTaken = selectedValue('quote_taken');
        const quoteDate = fieldValue('input[name="quote_date"]');
        const testDriveGiven = selectedValue('test_drive_given');
        const testDriveDate = fieldValue('input[name="test_drive_date"]');
        const testDriveToWhom = fieldValue('input[name="test_drive_to_whom"]');
        const testDriveReason = fieldValue('input[name="test_drive_not_given_reason"]');
        const competitionInterest = selectedValue('interested_in_competition');
        const competitionBrand = selectedText(document.getElementById('competition_brand'));
        const competitionModel = selectedText(document.getElementById('competition_model'));
        const firstTimeBuyer = selectedValue('first_time_buyer');
        const existingVehicleDetails = [
            fieldValue('input[name="existing_vehicle_brand"]'),
            fieldValue('input[name="existing_vehicle_model"]'),
            fieldValue('input[name="existing_vehicle_year"]'),
        ].filter(Boolean).join(' ');

        const exchangeInterest = selectedValue('interested_in_exchange');
        const exchangeBrand = selectedText(exchangeBrandSelect);
        const exchangeModel = selectedText(exchangeModelSelect);
        const exchangeYear = fieldValue('select[name="exchange_manufacture_year"]');
        const exchangeOwnership = fieldValue('select[name="exchange_ownership"]');
        const exchangeInsuranceValidity = fieldValue('input[name="exchange_insurance_validity"]');
        const exchangeRegNo = fieldValue('input[name="exchange_registration_no"]');
        const exchangeMileageKm = fieldValue('input[name="exchange_mileage_km"]');
        const exchangeExpected = fieldValue('input[name="exchange_expected_price"]');
        const exchangeQuoted = fieldValue('input[name="exchange_quoted_price"]');

        const rescheduled = !!rescheduleFollowupToggle?.checked;
        const followType = selectedValue('follow_type');
        const followDate = fieldValue('input[name="follow_date"]');
        const followTime = fieldValue('input[name="follow_time"]');
        const rescheduleReason = fieldValue('textarea[name="reschedule_reason"]');

        setSummaryField('name', [title, name].filter(Boolean).join(' '));
        setSummaryField('interested_in', currentVehicle);
        setSummaryField('mobile_numbers', mobileNumbers);
        setSummaryField('date_of_birth', dateOfBirth);
        setSummaryField('location', [location, district].filter(Boolean).join(', '));
        setSummaryField('customer_type', displayChoice(customerType));
        setSummaryField('profession', displayChoice(profession));
        setSummaryField('interested_vehicle_color', fieldValue('select[name="interested_vehicle_color"]'));
        setSummaryField('lead_source', [leadSource, sourceInfo].filter(Boolean).join(' - '));
        setSummaryField('quote_taken', quoteTaken ? [displayChoice(quoteTaken), quoteDate ? `on ${quoteDate}` : ''].filter(Boolean).join(' ') : '');
        setSummaryField(
            'test_drive_given',
            testDriveGiven === 'yes'
                ? ['Yes', testDriveDate ? `on ${testDriveDate}` : '', testDriveToWhom ? `to ${testDriveToWhom}` : ''].filter(Boolean).join(' ')
                : testDriveGiven === 'no'
                    ? ['No', testDriveReason].filter(Boolean).join(' - ')
                    : ''
        );
        setSummaryField('purchase_mode', displayChoice(selectedValue('purchase_mode')));
        setSummaryField(
            'competition',
            competitionInterest === 'yes'
                ? ['Yes', [competitionBrand, competitionModel].filter(Boolean).join(' ')].filter(Boolean).join(' - ')
                : displayChoice(competitionInterest)
        );
        setSummaryField(
            'first_time_buyer',
            firstTimeBuyer === 'yes'
                ? 'Yes'
                : firstTimeBuyer === 'no'
                    ? ['No', existingVehicleDetails || 'Vehicle Details'].filter(Boolean).join(' - ')
                    : ''
        );
        setSummaryField('exchange_mobile', mobileNumbers);
        setSummaryField('interested_in_exchange', displayChoice(exchangeInterest));
        setSummaryField('exchange_vehicle', [exchangeBrand, exchangeModel].filter(Boolean).join(' '));
        setSummaryField('exchange_year', exchangeYear);
        setSummaryField('exchange_ownership', exchangeOwnership);
        setSummaryField('exchange_insurance_validity', exchangeInsuranceValidity);
        setSummaryField('exchange_registration_no', exchangeRegNo);
        setSummaryField(
            'exchange_price',
            exchangeExpected || exchangeQuoted
                ? ['Expected: ' + (exchangeExpected || 'N/A'), 'Quoted: ' + (exchangeQuoted || 'N/A')].join(' / ')
                : ''
        );
        setSummaryField('exchange_mileage_km', exchangeMileageKm);
        setSummaryField('offer_unit_price', moneyValue(offerUnitPriceInput?.value));
        setSummaryField('offer_vat_amount', moneyValue(offerVatAmountInput?.value));
        setSummaryField('offer_total_cost', moneyValue(offerTotalCostInput?.value));
        setSummaryField('offer_total_discount', moneyValue(offerTotalDiscountInput?.value));
        setSummaryField('offer_final_price', moneyValue(offerFinalPriceInput?.value));
        setSummaryField('offer_remark', fieldValue('textarea[name="offer_remark"]'));
        setSummaryField('reschedule_followup', rescheduled ? 'Yes' : 'No');
        setSummaryField('followup_plan', rescheduled ? [followType, followDate ? `on ${followDate}` : '', followTime ? `at ${followTime}` : ''].filter(Boolean).join(' ') : '');
        setSummaryField('reschedule_reason', rescheduled ? rescheduleReason : '');
        setSummaryField('lead_status', displayChoice(selectedValue('lead_status')));
        setSummaryField('customer_remark', fieldValue('textarea[name="customer_remark"]'));
    }

    function updateConditionals() {
        document.querySelectorAll('[data-conditional]').forEach((block) => {
            const fieldName = block.dataset.conditional;
            const expectedValue = block.dataset.value;
            const currentValue = selectedValue(fieldName);
            const expectedValues = String(expectedValue || '').split(',').map((value) => value.trim());
            block.style.display = expectedValues.includes(currentValue) ? '' : 'none';
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
        updateProspectSummary();
    }

    function updateExchangeModels() {
        if (!exchangeBrandSelect || !exchangeModelSelect) {
            return;
        }

        const map = window.PROSPECT_COMPETITION_MAP || {};
        const models = map[exchangeBrandSelect.value] || [];
        const selectedFromServer = exchangeModelSelect.dataset.selectedModel || '';
        const activeModel = exchangeModelSelect.value || selectedFromServer;

        exchangeModelSelect.innerHTML = '<option value="">Select Model</option>';

        models.forEach((model) => {
            const option = document.createElement('option');
            option.value = model;
            option.textContent = model.toUpperCase();
            if (model === activeModel) {
                option.selected = true;
            }
            exchangeModelSelect.appendChild(option);
        });

        exchangeModelSelect.dataset.selectedModel = '';
        updateProspectSummary();
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
        if (personalStep) {
            personalStep.classList.toggle('personal-collapsed', !isEditable);
        }

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

        document.querySelectorAll('.contact-remove-btn').forEach((button) => {
            button.disabled = !isEditable;
        });
    }

    function syncProspectMobileNumbers() {
        if (!mobileNumbersInput || !prospectContactList) {
            return;
        }

        const numbers = Array.from(prospectContactList.querySelectorAll('.prospect-mobile-input'))
            .map((input) => input.value.trim())
            .filter(Boolean);
        mobileNumbersInput.value = numbers.join(', ');
        updateProspectSummary();
    }

    function addProspectMobileInput(value = '') {
        if (!prospectContactList) {
            return;
        }

        const row = document.createElement('div');
        row.className = 'contact-input-wrap';
        row.innerHTML = `
            <input class="lockable prospect-mobile-input" type="text" value="">
            <button type="button" class="contact-remove-btn" aria-label="Remove contact">&times;</button>
        `;

        const input = row.querySelector('.prospect-mobile-input');
        const removeButton = row.querySelector('.contact-remove-btn');
        if (input) {
            input.value = value;
            input.readOnly = false;
            input.addEventListener('input', syncProspectMobileNumbers);
        }
        if (removeButton) {
            removeButton.disabled = addContactNumberBtn ? addContactNumberBtn.disabled : false;
        }

        prospectContactList.appendChild(row);
        syncProspectMobileNumbers();
        input?.focus();
    }

    function setStepEditable(toggle, isEditable) {
        const step = toggle.closest('.step-collapsible');
        if (!step) {
            return;
        }

        toggle.checked = isEditable;
        step.classList.toggle('step-collapsed', !isEditable);

        if (step.classList.contains('personal-step')) {
            setPersonalEditable(isEditable);
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
            updateProspectSummary();
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
            updateProspectSummary();
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

    function updateRescheduleVisibility(shouldFocusDate = false) {
        if (!rescheduleFollowupToggle || !rescheduleFields) {
            return;
        }

        const isRescheduling = rescheduleFollowupToggle.checked;
        rescheduleFields.style.display = isRescheduling ? 'block' : 'none';

        rescheduleFields
            .querySelectorAll('input, textarea, select')
            .forEach((field) => {
                field.disabled = !isRescheduling;
                if (isRescheduling && 'readOnly' in field) {
                    field.readOnly = false;
                }
            });

        rescheduleFields
            .querySelectorAll('input[name="follow_date"], input[name="follow_time"], textarea[name="reschedule_reason"]')
            .forEach((field) => {
                field.required = isRescheduling;
            });

        if (isRescheduling && shouldFocusDate) {
            rescheduleFields.querySelector('input[name="follow_date"]')?.focus();
        }
    }

    function bindPlanSchedulePickers() {
        document
            .querySelectorAll('.plan-schedule-input-wrap input[type="date"], .plan-schedule-input-wrap input[type="time"]')
            .forEach((input) => {
                input.addEventListener('click', () => {
                    if (input.disabled || typeof input.showPicker !== 'function') {
                        return;
                    }

                    try {
                        input.showPicker();
                    } catch (error) {
                        input.focus();
                    }
                });
            });
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

        updateProspectSummary();
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

    function setOfferSummaryDisplayValue(element, value) {
        if (element) {
            element.textContent = formatSummaryNumber(value);
        }
    }

    function updateOfferViewMode() {
        const offerEditable = isOfferEditable();
        prospectOfferEditGroup?.classList.toggle('offer-hidden', !offerEditable);
        prospectOfferSummaryPanel?.classList.toggle('offer-hidden', offerEditable);
    }

    function updateOfferRemarksVisibility() {
        offerRemarksText?.classList.toggle('offer-hidden', !offerRemarksToggle?.checked);
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
        const unitPayable = Math.max(0, unitPrice - unitDiscount);
        const vatPayable = Math.max(0, vatAmount - vatDiscount);

        offerTotalCostInput.value = totalCost.toFixed(2);
        offerTotalDiscountInput.value = totalDiscount.toFixed(2);
        offerFinalPriceInput.value = finalPrice.toFixed(2);

        setOfferDisplayValue(offerTotalCostDisplay, totalCost);
        setOfferDisplayValue(offerTotalDiscountDisplay, totalDiscount);
        setOfferDisplayValue(offerFinalPriceDisplay, finalPrice);

        setOfferSummaryDisplayValue(prospectOfferSummaryVatCost, vatAmount);
        setOfferSummaryDisplayValue(prospectOfferSummaryVatOffer, vatDiscount);
        setOfferSummaryDisplayValue(prospectOfferSummaryVatPayable, vatPayable);
        setOfferSummaryDisplayValue(prospectOfferSummaryUnitCost, unitPrice);
        setOfferSummaryDisplayValue(prospectOfferSummaryUnitOffer, unitDiscount);
        setOfferSummaryDisplayValue(prospectOfferSummaryUnitPayable, unitPayable);
        setOfferSummaryDisplayValue(prospectOfferSummaryTotalCost, totalCost);
        setOfferSummaryDisplayValue(prospectOfferSummaryTotalOffer, totalDiscount);
        setOfferSummaryDisplayValue(prospectOfferSummaryFinalPrice, finalPrice);

        updateOfferViewMode();
        updateProspectSummary();
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
            <div class="exchange-upload-actions">
                <button type="button" data-exchange-upload-action="choose">Add</button>
                <button type="button" data-exchange-upload-action="view" disabled>View</button>
                <button type="button" data-exchange-upload-action="remove" disabled>Remove</button>
            </div>
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
            updateExchangeUploadActions(inputEl, false);
            return;
        }

        previewEl.src = sourceUrl;
        previewEl.hidden = false;
        tile.classList.add('has-preview');
        if (textEl) {
            textEl.hidden = true;
        }
        updateExchangeUploadActions(inputEl, true);
    }

    function getExchangeUploadShell(inputEl) {
        return inputEl.closest('.exchange-upload-field, .extra-image-row');
    }

    function updateExchangeUploadActions(inputEl, hasImage) {
        const shell = getExchangeUploadShell(inputEl);
        if (!shell) {
            return;
        }

        shell.querySelectorAll('[data-exchange-upload-action="view"], [data-exchange-upload-action="remove"]').forEach((button) => {
            button.disabled = !hasImage;
        });
    }

    function getExchangeUploadSource(inputEl) {
        const objectUrl = exchangePreviewObjectUrls.get(inputEl);
        if (objectUrl) {
            return objectUrl;
        }

        const tile = inputEl.closest('[data-upload-tile]');
        const previewEl = tile?.querySelector('.exchange-upload-preview');
        if (previewEl && !previewEl.hidden && previewEl.src) {
            return previewEl.src;
        }

        return String(inputEl.dataset.existingSrc || '').trim();
    }

    function clearExchangeUpload(inputEl) {
        const previousObjectUrl = exchangePreviewObjectUrls.get(inputEl);
        if (previousObjectUrl) {
            URL.revokeObjectURL(previousObjectUrl);
            exchangePreviewObjectUrls.delete(inputEl);
        }

        inputEl.value = '';

        const removeInput = getExchangeUploadShell(inputEl)?.querySelector('[data-exchange-remove-input]');
        if (removeInput && inputEl.dataset.existingSrc) {
            removeInput.value = '1';
            inputEl.dataset.existingSrc = '';
        }

        applyExchangePreviewToTile(inputEl, '');
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

            const removeInput = getExchangeUploadShell(inputEl)?.querySelector('[data-exchange-remove-input]');
            if (removeInput) {
                removeInput.value = '0';
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
            updateProspectSummary();
        });
    });

    form.querySelectorAll('input, select, textarea').forEach((field) => {
        field.addEventListener('input', updateProspectSummary);
        field.addEventListener('change', updateProspectSummary);
    });

    const brandSelect = document.getElementById('competition_brand');
    if (brandSelect) {
        brandSelect.addEventListener('change', updateCompetitionModels);
    }

    if (exchangeBrandSelect) {
        exchangeBrandSelect.addEventListener('change', updateExchangeModels);
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

    setInterestedVehicleEditEnabled(true);

    if (exchangeImageToggle) {
        exchangeImageToggle.addEventListener('change', updateExchangeImageVisibility);
    }
    if (rescheduleFollowupToggle) {
        rescheduleFollowupToggle.addEventListener('change', () => updateRescheduleVisibility(true));
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

    if (exchangeImageFields) {
        exchangeImageFields.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const uploadAction = target.closest('[data-exchange-upload-action]');
            if (uploadAction) {
                event.preventDefault();
                event.stopPropagation();

                const shell = uploadAction.closest('.exchange-upload-field, .extra-image-row');
                const inputEl = shell?.querySelector('input[type="file"]');
                if (!inputEl) {
                    return;
                }

                const action = uploadAction.dataset.exchangeUploadAction;
                if (action === 'choose') {
                    inputEl.click();
                } else if (action === 'view') {
                    const source = getExchangeUploadSource(inputEl);
                    if (source) {
                        window.open(source, '_blank', 'noopener');
                    }
                } else if (action === 'remove') {
                    clearExchangeUpload(inputEl);
                }
                return;
            }

            const existingExtraAction = target.closest('[data-existing-extra-action]');
            if (existingExtraAction) {
                event.preventDefault();
                event.stopPropagation();

                const item = existingExtraAction.closest('[data-existing-extra-image]');
                if (!item) {
                    return;
                }

                if (existingExtraAction.dataset.existingExtraAction === 'view') {
                    const source = existingExtraAction.dataset.imageSrc || item.querySelector('img')?.src;
                    if (source) {
                        window.open(source, '_blank', 'noopener');
                    }
                    return;
                }

                const removeCheckbox = item.querySelector('input[name="remove_extra_exchange_images[]"]');
                if (removeCheckbox) {
                    removeCheckbox.checked = true;
                }
                item.classList.add('exchange-existing-preview-removed');
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
        offerEditCheckbox.addEventListener('change', () => {
            updateOfferTotals();
            updateOfferViewMode();
        });
    }

    if (offerRemarksToggle) {
        offerRemarksToggle.addEventListener('change', updateOfferRemarksVisibility);
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
            addProspectMobileInput();
        });
    }

    if (prospectContactList) {
        prospectContactList.querySelectorAll('.prospect-mobile-input').forEach((input) => {
            input.addEventListener('input', syncProspectMobileNumbers);
        });

        prospectContactList.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            const removeButton = target.closest('.contact-remove-btn');
            if (!removeButton || removeButton.disabled) {
                return;
            }

            const rows = prospectContactList.querySelectorAll('.contact-input-wrap');
            if (rows.length <= 1) {
                const input = removeButton.closest('.contact-input-wrap')?.querySelector('.prospect-mobile-input');
                if (input) {
                    input.value = '';
                }
            } else {
                removeButton.closest('.contact-input-wrap')?.remove();
            }

            syncProspectMobileNumbers();
        });

        syncProspectMobileNumbers();
    }

    if (customerRemarkPreset && customerRemarkPreset.tagName === 'SELECT' && !customerRemarkPreset.value) {
        const firstTemplateOption = Array.from(customerRemarkPreset.options).find((option) => option.value);
        if (firstTemplateOption) {
            customerRemarkPreset.value = firstTemplateOption.value;
        }
    }

    stepEditToggles.forEach((toggle) => {
        toggle.addEventListener('change', (event) => {
            setStepEditable(event.target, event.target.checked);
            updateConditionals();
            updateProspectSummary();
        });

        setStepEditable(toggle, toggle.checked);
    });

    form.addEventListener('submit', () => {
        document.querySelectorAll('.lockable-select, .lockable-choice').forEach((field) => {
            field.disabled = false;
        });

        if (sourceInfoSelect && !sourceInfoSelect.disabled) {
            sourceInfoSelect.disabled = false;
        }
    });

    updateStepper();
    updateConditionals();
    updateCompetitionModels();
    updateExchangeModels();
    updateSourceInformationOptions();
    syncInterestedVehicleSelectionFromServerData();
    updateExchangeImageVisibility();
    updateRescheduleVisibility();
    bindPlanSchedulePickers();
    updateExchangeDifference();
    updateOfferTotals();
    updateOfferRemarksVisibility();

    form.querySelectorAll('#exchangeImageFields input[type="file"]').forEach((inputEl) => {
        bindExchangeUploadPreview(inputEl);
    });

    renumberExtraExchangeRows();
    updateProspectSummary();
})();
