<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class ExchangeAssessment
{
    public const VEHICLES = ['KUV 100 NXT', 'KUV 100 NXT K6 AMT'];

    public static function rules(): array
    {
        return [
            'exchange_assessment' => ['nullable', 'array'],
            'exchange_assessment.inspection_date' => ['nullable', 'date'],
            'exchange_assessment.has_finance' => ['nullable', Rule::in(['yes', 'no'])],
            'exchange_assessment.finance_company' => ['nullable', 'required_if:exchange_assessment.has_finance,yes', 'string', 'max:255'],
            'exchange_assessment.leased_value' => ['nullable', 'required_if:exchange_assessment.has_finance,yes', 'numeric', 'min:0', 'max:999999999.99'],
            'exchange_assessment.valuation' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'exchange_assessment.valued_by' => ['nullable', 'string', 'max:255'],
            'exchange_assessment.purchased_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'exchange_assessment.inspected_by' => ['nullable', 'string', 'max:255'],
            'exchange_assessment.items' => ['nullable', 'array', 'max:100'],
            'exchange_assessment.items.*.description' => ['nullable', 'required_with:exchange_assessment.items.*.amount', 'string', 'max:500'],
            'exchange_assessment.items.*.amount' => ['nullable', 'required_with:exchange_assessment.items.*.description', 'numeric', 'min:0', 'max:999999999.99'],
        ];
    }

    public static function sync(\App\Models\Enquiry $enquiry, $source): void
    {
        foreach (['prospectSheet', 'booking', 'delivery'] as $relation) {
            $record = $enquiry->$relation;
            if (!$record || ($record->getTable() === $source->getTable())) continue;
            $record->exchange_assessment = $source->exchange_assessment;
            $record->save();
        }
    }

    public static function resolve(array $validated, ?array $existing, ?string $interested): ?array
    {
        if ($interested !== 'yes') return null;
        if (!array_key_exists('exchange_assessment', $validated)) return $existing;
        $input = $validated['exchange_assessment'] ?? [];
        $result = array_intersect_key($input, array_flip([
            'inspection_date', 'has_finance', 'finance_company', 'leased_value',
            'valuation', 'valued_by', 'purchased_price', 'inspected_by',
        ]));
        if (($result['has_finance'] ?? null) !== 'yes') {
            $result['finance_company'] = null;
            $result['leased_value'] = null;
        }
        foreach (['leased_value', 'valuation', 'purchased_price'] as $field) {
            $result[$field] = isset($result[$field]) ? number_format((float) $result[$field], 2, '.', '') : null;
        }
        $result['items'] = [];
        $totalCents = 0;
        foreach ($input['items'] ?? [] as $item) {
            $description = trim((string) ($item['description'] ?? ''));
            if ($description === '' && ($item['amount'] ?? '') === '') continue;
            $cents = (int) round((float) ($item['amount'] ?? 0) * 100);
            $result['items'][] = ['description' => $description, 'amount' => number_format($cents / 100, 2, '.', '')];
            $totalCents += $cents;
        }
        // Always calculate the total on the server; never trust a submitted total.
        $result['total_price'] = number_format($totalCents / 100, 2, '.', '');
        return $result;
    }
}
