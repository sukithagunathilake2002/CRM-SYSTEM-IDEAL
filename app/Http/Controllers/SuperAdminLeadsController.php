<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Models\User;
use App\Models\Vehicle;
use App\Support\AllLeadsReport;
use Illuminate\Http\Request;

class SuperAdminLeadsController extends Controller
{
    public function index(Request $request, AllLeadsReport $report)
    {
        $filters = $report->filters($request);
        $query = $report->scope($report->query($filters), $request->user())->orderByDesc('enquiries.created_at')->orderByDesc('enquiries.id');
        $enquiries = (clone $query)->paginate(10);
        if ($enquiries->currentPage() > $enquiries->lastPage()) {
            $enquiries = $query->paginate(10, ['*'], 'page', $enquiries->lastPage());
        }
        $parameters = array_diff_key($filters, ['date_notice' => true]);
        $enquiries->appends($parameters);
        $rows = $enquiries->getCollection()->map(fn ($enquiry) => $report->row($enquiry));
        $summary = $this->summary($filters);
        $exportUrl = route($this->routePrefix().'.export', $parameters);
        if ($request->header('X-All-Leads-Partial') === '1') {
            return response()->json([
                'html' => view('dashboards.all-leads.results', compact('enquiries', 'rows', 'filters', 'summary', 'exportUrl'))->render(),
                'filters' => $filters,
                'url' => route($this->routePrefix(), [...$parameters, 'page' => $enquiries->currentPage()]),
            ]);
        }

        return view('dashboards.all-leads.index', compact('enquiries', 'rows', 'filters', 'summary', 'exportUrl'));
    }

    public function options(Request $request, AllLeadsReport $report)
    {
        $viewer = $request->user();
        $users = User::query();
        if ($viewer->role !== User::ROLE_SUPER_ADMIN) {
            $users->whereIn('id', $viewer->accessibleUserIds());
        }
        $leads = $report->scope(Enquiry::query(), $viewer);
        $models = Vehicle::visibleTo($viewer)->whereNotNull('model')->distinct()->pluck('model');
        // Include model snapshots on older enquiries, even if a master vehicle was renamed.
        foreach ((clone $leads)->whereNotNull('selected_vehicle_models')->distinct()->pluck('selected_vehicle_models') as $items) {
            foreach ((is_array($items) ? $items : []) as $item) {
                if (is_array($item) && ! empty($item['model'])) {
                    $models->push($item['model']);
                }
            }
        }

        return response()->json([
            'area_managers' => (clone $users)->where('role', User::ROLE_AREA_MANAGER)->orderBy('name')->get(['id', 'name'])->map(fn ($u) => ['value' => (string) $u->id, 'label' => $u->name]),
            'consultants' => (clone $users)->where('role', User::ROLE_SALES_CONSULTANT)->orderBy('name')->get(['id', 'name', 'manager_id'])->map(fn ($u) => ['value' => (string) $u->id, 'label' => $u->name, 'area_manager' => (string) $u->manager_id]),
            'vehicle_models' => $models->filter()->unique(fn ($model) => strtolower(trim($model)))->sort()->values()->map(fn ($model) => ['value' => strtolower(trim($model)), 'label' => $model]),
            'lead_sources' => (clone $leads)->whereNotNull('lead_source')->distinct()->orderBy('lead_source')->pluck('lead_source')->filter()->values()->map(fn ($source) => ['value' => strtolower(trim($source)), 'label' => $source]),
        ]);
    }

    public function export(Request $request, AllLeadsReport $report)
    {
        $filters = $report->filters($request);
        $query = $report->scope($report->query($filters), $request->user());
        $summary = $this->summary($filters);
        $total = (clone $query)->count();

        return response()->streamDownload(function () use ($query, $report, $summary, $filters, $total) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            $write = function (array $row) use ($handle) {
                $cells = array_map(function ($value) {
                    $text = (string) $value;

                    return preg_match('/^\s*[=+@-]|^[\t\r\n]/u', $text) ? "'".$text : $text;
                }, $row);
                fputcsv($handle, $cells, ',', '"', '');
            };
            $write(['Ideal Motors CRM - All Leads Report']);
            $write(['Generated At', now('Asia/Colombo')->format('Y-m-d H:i:s').' Asia/Colombo']);
            foreach ($summary as $label => $value) {
                $write([$label, $value]);
            }
            if ($filters['date_notice']) {
                $write(['Date range notice', $filters['date_notice']]);
            }
            $write(['Total Matching Leads', $total]);
            $write([]);
            $write($report->headings());
            $query->chunkById(250, function ($enquiries) use ($write, $report) {
                foreach ($enquiries as $enquiry) {
                    $write(array_values($report->row($enquiry)));
                }
            });
            fclose($handle);
        }, 'all_leads_'.$filters['from_date'].'_to_'.$filters['to_date'].'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function routePrefix(): string
    {
        return auth()->user()->role === User::ROLE_SUPER_ADMIN ? 'dashboard.super_admin.leads' : 'dashboard.admin.leads';
    }

    private function summary(array $filters): array
    {
        $summary = ['From Date' => $filters['from_date'], 'To Date' => $filters['to_date']];
        foreach (['area_managers' => 'Area Manager', 'consultants' => 'Consultant'] as $key => $label) {
            $summary[$label] = ! empty($filters[$key]) ? User::whereIn('id', $filters[$key])->orderBy('name')->pluck('name')->implode(', ') : 'All';
        }
        foreach (['vehicle_models' => 'Vehicle Model', 'lead_sources' => 'Lead Source', ...AllLeadsReport::CHOICES] as $key => $label) {
            if ($key === 'competition') {
                $label = 'Lost to Competitor';
            }
            $summary[$label] = ! empty($filters[$key]) ? implode(', ', array_map('ucfirst', $filters[$key])) : 'All';
        }

        return $summary;
    }
}
