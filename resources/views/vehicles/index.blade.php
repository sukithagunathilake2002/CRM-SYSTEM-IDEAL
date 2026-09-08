@extends('layouts.portal')

@section('bodyClass', 'vehicle-details-page')

@section('content')
<style>
.vehicle-page-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    margin-bottom: 18px;
}

.vehicle-page-head h1 {
    margin: 0 0 6px;
    color: var(--text);
    font-size: 28px;
}

.vehicle-page-head p,
.vehicle-help-text {
    margin: 0;
    color: var(--text-soft);
    font-size: 14px;
}

.vehicle-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
}

.vehicle-btn {
    border: 0;
    border-radius: 8px;
    background: #10225a;
    color: #ffffff;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 38px;
    padding: 9px 14px;
    text-decoration: none;
    font-weight: 700;
    font-size: 13px;
}

.vehicle-btn.secondary {
    background: #ffffff;
    border: 1px solid var(--line);
    color: #10225a;
}

.vehicle-btn.danger {
    background: #dc2626;
}

.vehicle-btn:disabled {
    background: #94a3b8;
    cursor: not-allowed;
}

.vehicle-panel {
    background: var(--surface-card);
    border: 1px solid var(--line);
    border-radius: 8px;
    margin-bottom: 18px;
    padding: 18px;
}

.vehicle-panel h2 {
    color: var(--text);
    font-size: 18px;
    margin: 0 0 14px;
}

.vehicle-form-grid {
    display: grid;
    grid-template-columns: repeat(5, minmax(120px, 1fr));
    gap: 12px;
}

.vehicle-form-grid .vehicle-field-wide {
    grid-column: span 2;
}

.vehicle-field label,
.vehicle-permissions legend {
    color: var(--text-soft);
    display: block;
    font-size: 12px;
    font-weight: 800;
    margin-bottom: 6px;
    text-transform: uppercase;
}

.vehicle-field input {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 8px;
    color: var(--text);
    min-height: 40px;
    padding: 9px 10px;
    width: 100%;
}

.vehicle-field textarea {
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 8px;
    color: var(--text);
    min-height: 40px;
    padding: 9px 10px;
    resize: vertical;
    width: 100%;
}

.vehicle-color-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 6px;
}

.vehicle-color-tags span {
    background: #e0f2fe;
    border: 1px solid #bae6fd;
    border-radius: 999px;
    color: #075985;
    font-size: 11px;
    font-weight: 800;
    padding: 4px 8px;
}

.vehicle-permissions {
    border: 1px solid var(--line);
    border-radius: 8px;
    margin: 14px 0 0;
    padding: 12px;
}

.vehicle-permission-list {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
    gap: 8px;
}

.vehicle-check {
    align-items: flex-start;
    background: var(--surface);
    border: 1px solid var(--line);
    border-radius: 8px;
    color: var(--text);
    display: flex;
    gap: 8px;
    padding: 9px;
}

.vehicle-check input {
    margin-top: 3px;
}

.vehicle-check span {
    display: block;
    font-size: 13px;
    font-weight: 700;
}

.vehicle-check small {
    color: var(--text-soft);
    display: block;
    font-size: 11px;
    margin-top: 2px;
}

.vehicle-list {
    display: grid;
    gap: 12px;
}

.vehicle-row {
    background: var(--surface-card);
    border: 1px solid var(--line);
    border-radius: 8px;
    padding: 14px;
}

.vehicle-row summary {
    align-items: center;
    color: var(--text);
    cursor: pointer;
    display: grid;
    grid-template-columns: minmax(180px, 1fr) repeat(3, minmax(110px, auto));
    gap: 12px;
    list-style: none;
}

.vehicle-row summary::-webkit-details-marker {
    display: none;
}

.vehicle-title strong {
    display: block;
    font-size: 15px;
}

.vehicle-title span,
.vehicle-meta {
    color: var(--text-soft);
    display: block;
    font-size: 12px;
    font-weight: 700;
}

.vehicle-row-body {
    border-top: 1px solid var(--line);
    margin-top: 12px;
    padding-top: 14px;
}

.vehicle-row-actions {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: flex-end;
    margin-top: 12px;
}

html.theme-dark .vehicle-btn.secondary {
    background: #0f172a;
    color: #e2e8f0;
}

@media (max-width: 880px) {
    .vehicle-page-head,
    .vehicle-row summary {
        display: block;
    }

    .vehicle-actions {
        margin-top: 12px;
    }

    .vehicle-form-grid {
        grid-template-columns: 1fr;
    }

    .vehicle-form-grid .vehicle-field-wide {
        grid-column: auto;
    }

    .vehicle-meta {
        margin-top: 8px;
    }
}
</style>

<div class="vehicle-page-head">
    <div>
        <h1>Vehicale Details</h1>
        <p>Add vehicles, update pricing, and assign each model to the relevant Head Of Sales hierarchy.</p>
    </div>
    <div class="vehicle-actions">
        <a class="vehicle-btn secondary" href="{{ route('dashboard.super_admin') }}">Back to Dashboard</a>
    </div>
</div>

<section class="vehicle-panel">
    <h2>Add Vehicle</h2>
    <form method="POST" action="{{ route('vehicles.store') }}">
        @csrf
        <div class="vehicle-form-grid">
            <div class="vehicle-field">
                <label for="new_model">Model</label>
                <input id="new_model" type="text" name="model" value="{{ old('model') }}" required>
            </div>
            <div class="vehicle-field">
                <label for="new_engine_type">Engine Type</label>
                <input id="new_engine_type" type="text" name="engine_type" value="{{ old('engine_type') }}" required>
            </div>
            <div class="vehicle-field">
                <label for="new_variant">Variant</label>
                <input id="new_variant" type="text" name="variant" value="{{ old('variant') }}" required>
            </div>
            <div class="vehicle-field vehicle-field-wide">
                <label for="new_colors">Vehicle Colors</label>
                <textarea id="new_colors" name="colors" rows="1" placeholder="White, Black, Silver">{{ old('colors') }}</textarea>
            </div>
            <div class="vehicle-field">
                <label for="new_unit_price">Unit Price</label>
                <input id="new_unit_price" type="number" name="unit_price" value="{{ old('unit_price', '0.00') }}" min="0" step="0.01" required>
            </div>
            <div class="vehicle-field">
                <label for="new_vat_amount">VAT Amount</label>
                <input id="new_vat_amount" type="number" name="vat_amount" value="{{ old('vat_amount', '0.00') }}" min="0" step="0.01" required>
            </div>
        </div>

        <fieldset class="vehicle-permissions">
            <legend>Head Of Sales Permissions</legend>
            @if($headsOfSales->isNotEmpty())
                <div class="vehicle-permission-list">
                    @foreach($headsOfSales as $head)
                        <label class="vehicle-check">
                            <input type="checkbox" name="head_of_sales_ids[]" value="{{ $head->id }}" @checked(in_array((string) $head->id, old('head_of_sales_ids', []), true))>
                            <span>
                                {{ $head->name }}
                                <small>{{ $head->email }}</small>
                            </span>
                        </label>
                    @endforeach
                </div>
            @else
                <p class="vehicle-help-text">No Head Of Sales users are registered yet.</p>
            @endif
        </fieldset>

        <div class="vehicle-row-actions">
            <button class="vehicle-btn" type="submit">Add Vehicle</button>
        </div>
    </form>
</section>

<div class="vehicle-list">
    @forelse($vehicles as $vehicle)
        @php
            $assignedHeadIds = $vehicle->permittedHeadsOfSales->pluck('id')->map(fn($id) => (int) $id)->all();
            $vehicleLabel = trim($vehicle->model . ' / ' . $vehicle->engine_type . ' / ' . $vehicle->variant);
            $vehicleColors = collect($vehicle->colors ?? [])->map(fn($color) => trim((string) $color))->filter()->values()->all();
        @endphp
        <details class="vehicle-row">
            <summary>
                <span class="vehicle-title">
                    <strong>{{ $vehicle->model }}</strong>
                    <span>{{ $vehicle->engine_type }} / {{ $vehicle->variant }}</span>
                    @if(!empty($vehicleColors))
                        <span class="vehicle-color-tags">
                            @foreach($vehicleColors as $color)
                                <span>{{ $color }}</span>
                            @endforeach
                        </span>
                    @endif
                </span>
                <span class="vehicle-meta">Unit: {{ number_format((float) $vehicle->unit_price, 2) }}</span>
                <span class="vehicle-meta">VAT: {{ number_format((float) $vehicle->vat_amount, 2) }}</span>
                <span class="vehicle-meta">{{ count($assignedHeadIds) }} Head Of Sales</span>
            </summary>

            <div class="vehicle-row-body">
                <form method="POST" action="{{ route('vehicles.update', $vehicle) }}">
                    @csrf
                    @method('PUT')
                    <div class="vehicle-form-grid">
                        <div class="vehicle-field">
                            <label for="model_{{ $vehicle->id }}">Model</label>
                            <input id="model_{{ $vehicle->id }}" type="text" name="model" value="{{ old('model_' . $vehicle->id, $vehicle->model) }}" required>
                        </div>
                        <div class="vehicle-field">
                            <label for="engine_{{ $vehicle->id }}">Engine Type</label>
                            <input id="engine_{{ $vehicle->id }}" type="text" name="engine_type" value="{{ old('engine_type_' . $vehicle->id, $vehicle->engine_type) }}" required>
                        </div>
                        <div class="vehicle-field">
                            <label for="variant_{{ $vehicle->id }}">Variant</label>
                            <input id="variant_{{ $vehicle->id }}" type="text" name="variant" value="{{ old('variant_' . $vehicle->id, $vehicle->variant) }}" required>
                        </div>
                        <div class="vehicle-field vehicle-field-wide">
                            <label for="colors_{{ $vehicle->id }}">Vehicle Colors</label>
                            <textarea id="colors_{{ $vehicle->id }}" name="colors" rows="1" placeholder="White, Black, Silver">{{ old('colors_' . $vehicle->id, implode(', ', $vehicleColors)) }}</textarea>
                        </div>
                        <div class="vehicle-field">
                            <label for="unit_price_{{ $vehicle->id }}">Unit Price</label>
                            <input id="unit_price_{{ $vehicle->id }}" type="number" name="unit_price" value="{{ old('unit_price_' . $vehicle->id, $vehicle->unit_price) }}" min="0" step="0.01" required>
                        </div>
                        <div class="vehicle-field">
                            <label for="vat_amount_{{ $vehicle->id }}">VAT Amount</label>
                            <input id="vat_amount_{{ $vehicle->id }}" type="number" name="vat_amount" value="{{ old('vat_amount_' . $vehicle->id, $vehicle->vat_amount) }}" min="0" step="0.01" required>
                        </div>
                    </div>

                    <fieldset class="vehicle-permissions">
                        <legend>Head Of Sales Permissions</legend>
                        @if($headsOfSales->isNotEmpty())
                            <div class="vehicle-permission-list">
                                @foreach($headsOfSales as $head)
                                    <label class="vehicle-check">
                                        <input type="checkbox" name="head_of_sales_ids[]" value="{{ $head->id }}" @checked(in_array((int) $head->id, $assignedHeadIds, true))>
                                        <span>
                                            {{ $head->name }}
                                            <small>{{ $head->email }}</small>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <p class="vehicle-help-text">No Head Of Sales users are registered yet.</p>
                        @endif
                    </fieldset>

                    <div class="vehicle-row-actions">
                        <span class="vehicle-help-text">{{ $vehicle->enquiries_count }} linked enquiries</span>
                        <button class="vehicle-btn" type="submit">Update Details</button>
                    </div>
                </form>

                <form method="POST" action="{{ route('vehicles.destroy', $vehicle) }}" class="vehicle-row-actions" onsubmit="return confirm('Delete {{ addslashes($vehicleLabel) }}?');">
                    @csrf
                    @method('DELETE')
                    <button class="vehicle-btn danger" type="submit" @disabled($vehicle->enquiries_count > 0)>Delete</button>
                </form>
            </div>
        </details>
    @empty
        <section class="vehicle-panel">
            <p class="vehicle-help-text">No vehicles found. Add the first vehicle above.</p>
        </section>
    @endforelse
</div>
@endsection
