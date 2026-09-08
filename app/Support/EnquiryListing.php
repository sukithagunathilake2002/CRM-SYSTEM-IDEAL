<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class EnquiryListing
{
    public function paginate(Builder $query, Request $request): LengthAwarePaginator
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'sort' => ['nullable', 'in:newest,oldest'],
            'page' => ['nullable', 'integer', 'min:1'],
            'inquiryFrom' => ['nullable', 'date_format:Y-m-d'],
            'inquiryTo' => ['nullable', 'date_format:Y-m-d'],
            'dueFrom' => ['nullable', 'date_format:Y-m-d'],
            'dueTo' => ['nullable', 'date_format:Y-m-d'],
            'model' => ['sometimes', 'array', 'max:100'],
            'leadSource' => ['sometimes', 'array', 'max:100'],
            'exchange' => ['sometimes', 'array', 'max:2'],
            'followupType' => ['sometimes', 'array', 'max:100'],
            'role' => ['sometimes', 'array', 'max:100'],
            'model.*' => ['string', 'max:200'],
            'leadSource.*' => ['string', 'max:200'],
            'exchange.*' => ['in:yes,no'],
            'followupType.*' => ['string', 'max:200'],
            'role.*' => ['string', 'max:200'],
        ]);

        foreach (['inquiryFrom' => ['created_at', '>='], 'inquiryTo' => ['created_at', '<='],
            'dueFrom' => ['follow_date', '>='], 'dueTo' => ['follow_date', '<=']] as $key => [$column, $operator]) {
            if ($request->filled($key)) {
                $value = $request->input($key);
                if ($column === 'created_at') {
                    $value .= $operator === '>=' ? ' 00:00:00' : ' 23:59:59';
                }
                $query->where($column, $operator, $value);
            }
        }
        foreach (['leadSource' => 'lead_source', 'followupType' => 'follow_type'] as $key => $column) {
            if ($values = $request->input($key, [])) {
                $query->whereIn($query->getQuery()->raw("LOWER(TRIM($column))"), array_map('strtolower', $values));
            }
        }
        if (count($request->input('exchange', [])) === 1) {
            if ($request->input('exchange.0') === 'yes') {
                $query->where('exchange', 1);
            } else {
                $query->where(fn($q) => $q->whereNull('exchange')->orWhere('exchange', '<>', 1));
            }
        }
        if ($roles = $request->input('role', [])) {
            $query->where(function ($q) use ($roles) {
                $q->whereHas('user', fn($u) => $u->whereIn('role', $roles));
                if (in_array('unassigned', $roles, true)) {
                    $q->orWhereDoesntHave('user');
                }
            });
        }
        if ($models = $request->input('model', [])) {
            $models = array_map('strtolower', $models);
            $query->where(function ($q) use ($models) {
                $q->where(function ($fallback) use ($models) {
                    $fallback->where(function ($empty) {
                        $empty->whereNull('selected_vehicle_models')->orWhere('selected_vehicle_models', '[]');
                    })->whereHas('vehicle', fn($v) => $v->whereIn($v->getQuery()->raw('LOWER(TRIM(model))'), $models));
                });
                foreach ($models as $model) {
                    if ($q->getConnection()->getDriverName() === 'sqlite') {
                        $q->orWhereRaw("EXISTS (SELECT 1 FROM json_each(enquiries.selected_vehicle_models) AS selected_model WHERE LOWER(TRIM(json_extract(selected_model.value, '$.model'))) = ?)", [$model]);
                    } else {
                        $q->orWhereRaw('JSON_CONTAINS(LOWER(enquiries.selected_vehicle_models), ?)', [json_encode(['model' => $model])]);
                    }
                }
            });
        }

        $search = trim((string) $request->input('q', ''));
        $tokens = preg_split('/[^\pL\pN]+/u', mb_strtolower($search), -1, PREG_SPLIT_NO_EMPTY);
        if ($tokens) {
            $query->where(function ($searchQuery) use ($tokens, $search) {
                $searchQuery->where(function ($allTokens) use ($tokens) {
                    foreach ($tokens as $token) {
                        $like = '%'.$token.'%';
                        $allTokens->where(function ($q) use ($like, $token) {
                            $q->where('enquiries.id', $token)
                                ->orWhereRaw('LOWER(lead_source) LIKE ?', [$like])
                                ->orWhereRaw('LOWER(source_of_information) LIKE ?', [$like])
                                ->orWhereRaw('LOWER(follow_type) LIKE ?', [$like])
                                ->orWhere('enquiries.created_at', 'like', $like)
                                ->orWhere('follow_date', 'like', $like)
                                ->orWhereRaw('LOWER(selected_vehicle_models) LIKE ?', [$like])
                                ->orWhereHas('customer', fn($c) => $c->whereRaw('LOWER(name) LIKE ?', [$like])->orWhereRaw('LOWER(title) LIKE ?', [$like])->orWhere('mobile_numbers', 'like', $like))
                                ->orWhereHas('vehicle', fn($v) => $v->whereRaw('LOWER(model) LIKE ?', [$like])->orWhereRaw('LOWER(engine_type) LIKE ?', [$like])->orWhereRaw('LOWER(variant) LIKE ?', [$like]))
                                ->orWhereHas('user', fn($u) => $u->whereRaw('LOWER(name) LIKE ?', [$like])->orWhereRaw('LOWER(role) LIKE ?', [$like]));
                        });
                    }
                });
                $digits = preg_replace('/\D+/', '', $search);
                if (strlen($digits) >= 3) {
                    $searchQuery->orWhereHas('customer', fn($c) => $c->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(mobile_numbers, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') LIKE ?", ['%'.$digits.'%']));
                }
            });
        }

        $direction = $request->input('sort') === 'oldest' ? 'asc' : 'desc';
        $query->orderBy('follow_date', $direction)->orderBy('follow_time')->orderBy('enquiries.id', $direction);
        $page = (int) $request->input('page', 1);
        $enquiries = (clone $query)->paginate(10, ['*'], 'page', $page);
        // A saved link can point past the last page after records are removed or reassigned.
        if ($page > $enquiries->lastPage()) {
            $enquiries = $query->paginate(10, ['*'], 'page', $enquiries->lastPage());
        }
        return $enquiries->appends($request->except(['page', 'filter_options']));
    }

    public function options(Builder $query): array
    {
        // Only requested when the filter dialog opens; do not load customer or booking records.
        $rows = (clone $query)->setEagerLoads([])
            ->with(['vehicle:id,model', 'user:id,role'])
            ->get(['enquiries.id', 'vehicle_id', 'user_id', 'selected_vehicle_models', 'lead_source', 'follow_type']);
        $options = ['model' => [], 'leadSource' => [], 'followupType' => [], 'role' => []];
        foreach ($rows as $row) {
            $models = collect($row->selectedVehicleItems())->pluck('model');
            foreach ($models as $model) {
                $value = strtolower(trim((string) $model));
                if ($value !== '') $options['model'][$value] = ['value' => $value, 'label' => $model];
            }
            foreach (['leadSource' => 'lead_source', 'followupType' => 'follow_type'] as $key => $column) {
                $value = strtolower(trim((string) $row->$column));
                if ($value !== '') $options[$key][$value] = ['value' => $value, 'label' => $row->$column];
            }
            $role = $row->user?->role ?? 'unassigned';
            $options['role'][$role] = ['value' => $role, 'label' => $row->user?->role_label ?? 'Unassigned'];
        }
        foreach ($options as &$group) {
            $group = array_values($group);
            usort($group, fn($a, $b) => strcasecmp($a['label'], $b['label']));
        }
        $options['exchange'] = [['value' => 'yes', 'label' => 'Yes'], ['value' => 'no', 'label' => 'No']];
        return $options;
    }
}
