<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class VehicleController extends Controller
{
    public function index(): View
    {
        $vehicles = Vehicle::query()
            ->with('permittedHeadsOfSales:id,name,email')
            ->withCount('enquiries')
            ->orderBy('model')
            ->orderBy('engine_type')
            ->orderBy('variant')
            ->get();

        $headsOfSales = User::query()
            ->where('role', User::ROLE_HEAD_OF_SALES)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('vehicles.index', compact('vehicles', 'headsOfSales'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateVehicle($request);

        DB::transaction(function () use ($validated): void {
            $vehicle = Vehicle::query()->create($this->vehiclePayload($validated));
            $vehicle->permittedHeadsOfSales()->sync($validated['head_of_sales_ids'] ?? []);
        });

        return redirect()
            ->route('vehicles.index')
            ->with('success', 'Vehicle added successfully.');
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $validated = $this->validateVehicle($request);

        DB::transaction(function () use ($vehicle, $validated): void {
            $vehicle->update($this->vehiclePayload($validated));
            $vehicle->permittedHeadsOfSales()->sync($validated['head_of_sales_ids'] ?? []);
        });

        return redirect()
            ->route('vehicles.index')
            ->with('success', 'Vehicle details updated successfully.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->loadCount('enquiries');

        if ($vehicle->enquiries_count > 0) {
            return redirect()
                ->route('vehicles.index')
                ->withErrors(['vehicle' => 'This vehicle is linked to existing enquiries and cannot be deleted.']);
        }

        DB::transaction(function () use ($vehicle): void {
            $vehicle->permittedHeadsOfSales()->detach();
            $vehicle->delete();
        });

        return redirect()
            ->route('vehicles.index')
            ->with('success', 'Vehicle deleted successfully.');
    }

    private function validateVehicle(Request $request): array
    {
        return $request->validate([
            'model' => ['required', 'string', 'max:255'],
            'engine_type' => ['required', 'string', 'max:255'],
            'variant' => ['required', 'string', 'max:255'],
            'colors' => ['nullable', 'string', 'max:2000'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'vat_amount' => ['required', 'numeric', 'min:0'],
            'head_of_sales_ids' => ['nullable', 'array'],
            'head_of_sales_ids.*' => [
                'integer',
                Rule::exists('users', 'id')->where(fn($query) => $query->where('role', User::ROLE_HEAD_OF_SALES)),
            ],
        ]);
    }

    private function vehiclePayload(array $validated): array
    {
        return [
            'model' => trim((string) $validated['model']),
            'engine_type' => trim((string) $validated['engine_type']),
            'variant' => trim((string) $validated['variant']),
            'colors' => $this->normalizeColors($validated['colors'] ?? ''),
            'unit_price' => (float) $validated['unit_price'],
            'vat_amount' => (float) $validated['vat_amount'],
        ];
    }

    private function normalizeColors(?string $colors): array
    {
        return collect(preg_split('/[\r\n,]+/', (string) $colors) ?: [])
            ->map(fn($color): string => trim($color))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
