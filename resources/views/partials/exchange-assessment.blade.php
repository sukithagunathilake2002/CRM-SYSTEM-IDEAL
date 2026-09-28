@php
    $assessment = old('exchange_assessment', $exchangeRecord?->exchange_assessment ?? $exchangeFallback?->exchange_assessment ?? []);
    $assessment = is_array($assessment) ? $assessment : [];
    $assessmentRows = $assessment['items'] ?? [];
    if (empty($assessmentRows)) $assessmentRows = [['description' => '', 'amount' => '']];
    $assessmentVehicle = old('exchange_vehicle_model', $exchangeRecord?->exchange_vehicle_model ?: $exchangeFallback?->exchange_vehicle_model);
@endphp
<div class="exchange-assessment" data-exchange-assessment>
    <div class="exchange-assessment-grid">
        <label>Select Vehicle
            <select name="exchange_vehicle_model" id="exchangeAssessmentVehicle">
                <option value="">Select Vehicle</option>
                @foreach(\App\Support\ExchangeAssessment::VEHICLES as $vehicle)
                    <option value="{{ $vehicle }}" @selected($assessmentVehicle === $vehicle)>{{ $vehicle }}</option>
                @endforeach
            </select>
        </label>
        <label>Inspection Date
            <input type="date" name="exchange_assessment[inspection_date]" value="{{ $assessment['inspection_date'] ?? '' }}">
        </label>
        <label>Finance
            <select name="exchange_assessment[has_finance]" data-exchange-finance>
                <option value="">Select Yes or No</option>
                <option value="yes" @selected(($assessment['has_finance'] ?? '') === 'yes')>Yes</option>
                <option value="no" @selected(($assessment['has_finance'] ?? '') === 'no')>No</option>
            </select>
        </label>
        <label data-exchange-finance-field>Name of Finance Company
            <input type="text" maxlength="255" name="exchange_assessment[finance_company]" value="{{ $assessment['finance_company'] ?? '' }}">
        </label>
        <label data-exchange-finance-field>Leased Value (Rs)
            <input type="number" min="0" max="999999999.99" step="0.01" name="exchange_assessment[leased_value]" value="{{ $assessment['leased_value'] ?? '' }}">
        </label>
        <label>Valuation (Rs)
            <input type="number" min="0" max="999999999.99" step="0.01" name="exchange_assessment[valuation]" value="{{ $assessment['valuation'] ?? '' }}">
        </label>
        <label>Valued By
            <input type="text" maxlength="255" name="exchange_assessment[valued_by]" value="{{ $assessment['valued_by'] ?? '' }}">
        </label>
        <label>Purchased Price (Rs)
            <input type="number" min="0" max="999999999.99" step="0.01" name="exchange_assessment[purchased_price]" value="{{ $assessment['purchased_price'] ?? $exchangeRecord?->exchange_purchase_value ?? $exchangeFallback?->exchange_purchase_value ?? '' }}">
        </label>
        <label>Inspected By
            <input type="text" maxlength="255" name="exchange_assessment[inspected_by]" value="{{ $assessment['inspected_by'] ?? '' }}">
        </label>
    </div>
    <h4>Description and Amount</h4>
    <div class="exchange-assessment-item-head" aria-hidden="true">
        <span>Description</span>
        <span>Amount (Rs)</span>
        <span></span>
    </div>
    <div data-exchange-items>
        @foreach($assessmentRows as $index => $item)
            <div class="exchange-assessment-item" data-exchange-item>
                <label><input aria-label="Description" type="text" maxlength="500" name="exchange_assessment[items][{{ $index }}][description]" value="{{ $item['description'] ?? '' }}" data-item-description></label>
                <label><input aria-label="Amount (Rs)" type="number" min="0" max="999999999.99" step="0.01" name="exchange_assessment[items][{{ $index }}][amount]" value="{{ $item['amount'] ?? '' }}" data-item-amount></label>
                <button type="button" data-remove-exchange-item>Remove</button>
            </div>
        @endforeach
    </div>
    <button type="button" data-add-exchange-item>Add Description</button>
    <p><strong>Total Price (Rs): <output data-exchange-total>{{ $assessment['total_price'] ?? '0.00' }}</output></strong></p>
    <p>Total Price is the sum of the description amounts.</p>
    @foreach($errors->getMessages() as $field => $messages)
        @if(str_starts_with($field, 'exchange_assessment.') || $field === 'exchange_vehicle_model')
            @foreach($messages as $message)<p role="alert">{{ $message }}</p>@endforeach
        @endif
    @endforeach
</div>
@once
<style>
.exchange-assessment { width:100%; margin:16px 0; }
.exchange-assessment-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
.exchange-assessment label { display:flex; flex-direction:column; gap:6px; font-size:14px; font-weight:600; }
.exchange-assessment input,.exchange-assessment select { width:100%; min-width:0; border:1px solid #cbd5e1; border-radius:8px; padding:10px 12px; background:white; color:#172033; box-sizing:border-box; }
/* Form-level styles previously reduced select height and clipped its text. */
.exchange-assessment select {
    height:44px !important;
    min-height:44px !important;
    padding:0 12px !important;
    font-size:16px !important;
    line-height:1.4 !important;
    appearance:auto;
    -webkit-appearance:menulist;
}
.exchange-assessment input { min-height:44px !important; font-size:16px !important; line-height:1.4 !important; }
.exchange-assessment-item-head,.exchange-assessment-item { display:grid; grid-template-columns:minmax(0,2fr) minmax(0,1fr) 88px; gap:12px; align-items:end; }
.exchange-assessment-item-head { margin:16px 0 8px; font-size:14px; font-weight:700; color:#172033; }
.exchange-assessment-item { margin:8px 0; }
.exchange-assessment-item label { min-width:0; }
.exchange-assessment button { padding:9px 12px; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer; }
.exchange-assessment [hidden] { display:none !important; }
@media(max-width:600px) {
    .exchange-assessment-grid { grid-template-columns:1fr; }
    .exchange-assessment-item-head,.exchange-assessment-item { grid-template-columns:minmax(0,2fr) minmax(0,1fr); gap:8px; }
    .exchange-assessment-item-head > span:last-child { display:none; }
    .exchange-assessment-item button { grid-column:2; justify-self:end; }
    .exchange-assessment-item { margin-bottom:16px; }
}
</style>
<script src="{{ asset('js/exchange-assessment.js') }}" defer></script>
@endonce
