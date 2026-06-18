<?php

namespace App\Http\Controllers;

use App\Models\Enquiry;
use App\Models\LeadTransferRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeadTransferRequestController extends Controller
{
    public function create(Request $request): View
    {
        $consultant = $request->user();
        abort_unless($consultant?->role === User::ROLE_SALES_CONSULTANT, 403);

        $managerId = (int) ($consultant->manager_id ?? 0);
        $targetConsultants = User::query()
            ->where('role', User::ROLE_SALES_CONSULTANT)
            ->where('manager_id', $managerId)
            ->where('id', '!=', $consultant->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $leads = Enquiry::query()
            ->with(['customer:id,title,name', 'vehicle:id,model,variant'])
            ->where('user_id', $consultant->id)
            ->latest()
            ->get(['id', 'customer_id', 'vehicle_id', 'follow_type', 'follow_date', 'created_at']);

        $requests = LeadTransferRequest::query()
            ->with(['enquiry.customer:id,title,name', 'toUser:id,name,email', 'decider:id,name'])
            ->where('requested_by', $consultant->id)
            ->latest()
            ->limit(20)
            ->get();
        $selectedLeadIdInput = (string) $request->query('enquiry_id', '');
        $selectedLeadId = ctype_digit($selectedLeadIdInput) && $leads->contains('id', (int) $selectedLeadIdInput)
            ? (int) $selectedLeadIdInput
            : null;

        return view('lead-transfer.request', [
            'leads' => $leads,
            'targetConsultants' => $targetConsultants,
            'requests' => $requests,
            'selectedLeadId' => $selectedLeadId,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $consultant = $request->user();
        abort_unless($consultant?->role === User::ROLE_SALES_CONSULTANT, 403);

        $validated = $request->validate([
            'enquiry_id' => ['required', 'integer', Rule::exists('enquiries', 'id')],
            'to_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if (empty($consultant->manager_id)) {
            return back()
                ->withErrors(['enquiry_id' => 'You must be assigned to an Area Manager before requesting a transfer.'])
                ->withInput();
        }

        $lead = Enquiry::query()->find((int) $validated['enquiry_id']);
        if (!$lead || (int) $lead->user_id !== (int) $consultant->id) {
            return back()
                ->withErrors(['enquiry_id' => 'Please select one of your own leads.'])
                ->withInput();
        }

        $targetConsultant = User::query()->find((int) $validated['to_user_id']);
        if (!$targetConsultant
            || $targetConsultant->role !== User::ROLE_SALES_CONSULTANT
            || (int) $targetConsultant->id === (int) $consultant->id
            || (int) $targetConsultant->manager_id !== (int) $consultant->manager_id) {
            return back()
                ->withErrors(['to_user_id' => 'Please select a Sales Consultant under your Area Manager.'])
                ->withInput();
        }

        $hasPendingRequest = LeadTransferRequest::query()
            ->where('enquiry_id', $lead->id)
            ->where('status', LeadTransferRequest::STATUS_PENDING)
            ->exists();

        if ($hasPendingRequest) {
            return back()
                ->withErrors(['enquiry_id' => 'This lead already has a pending transfer request.'])
                ->withInput();
        }

        LeadTransferRequest::query()->create([
            'enquiry_id' => $lead->id,
            'from_user_id' => $consultant->id,
            'to_user_id' => $targetConsultant->id,
            'area_manager_id' => $consultant->manager_id,
            'requested_by' => $consultant->id,
            'status' => LeadTransferRequest::STATUS_PENDING,
            'reason' => $validated['reason'],
        ]);

        return redirect()
            ->route('lead_transfer.request.create')
            ->with('success', 'Lead transfer request sent to your Area Manager for approval.');
    }

    public function approvals(Request $request): View
    {
        $areaManager = $request->user();
        abort_unless($areaManager?->role === User::ROLE_AREA_MANAGER, 403);

        $requests = LeadTransferRequest::query()
            ->with([
                'enquiry.customer:id,title,name',
                'enquiry.vehicle:id,model,variant',
                'fromUser:id,name,email',
                'toUser:id,name,email',
                'requester:id,name,email',
                'decider:id,name',
            ])
            ->where('area_manager_id', $areaManager->id)
            ->latest()
            ->get();

        return view('lead-transfer.approvals', [
            'requests' => $requests,
        ]);
    }

    public function approve(Request $request, LeadTransferRequest $transferRequest): RedirectResponse
    {
        $areaManager = $request->user();
        abort_unless($areaManager?->role === User::ROLE_AREA_MANAGER, 403);
        abort_unless((int) $transferRequest->area_manager_id === (int) $areaManager->id, 403);

        if ($transferRequest->status !== LeadTransferRequest::STATUS_PENDING) {
            return back()->withErrors(['transfer_request' => 'This transfer request has already been decided.']);
        }

        return DB::transaction(function () use ($transferRequest, $areaManager): RedirectResponse {
            $lead = Enquiry::query()
                ->where('id', $transferRequest->enquiry_id)
                ->lockForUpdate()
                ->first();

            if (!$lead || (int) $lead->user_id !== (int) $transferRequest->from_user_id) {
                return back()->withErrors(['transfer_request' => 'This lead is no longer assigned to the requesting consultant.']);
            }

            $targetConsultant = User::query()->find((int) $transferRequest->to_user_id);
            if (!$targetConsultant
                || $targetConsultant->role !== User::ROLE_SALES_CONSULTANT
                || (int) $targetConsultant->manager_id !== (int) $areaManager->id) {
                return back()->withErrors(['transfer_request' => 'The target consultant is no longer under your hierarchy.']);
            }

            $lead->update([
                'user_id' => (int) $transferRequest->to_user_id,
            ]);

            $transferRequest->update([
                'status' => LeadTransferRequest::STATUS_APPROVED,
                'decided_by' => $areaManager->id,
                'decided_at' => now(),
            ]);

            return redirect()
                ->route('lead_transfer.approvals')
                ->with('success', 'Lead transfer approved and completed.');
        });
    }

    public function reject(Request $request, LeadTransferRequest $transferRequest): RedirectResponse
    {
        $areaManager = $request->user();
        abort_unless($areaManager?->role === User::ROLE_AREA_MANAGER, 403);
        abort_unless((int) $transferRequest->area_manager_id === (int) $areaManager->id, 403);

        $validated = $request->validate([
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($transferRequest->status !== LeadTransferRequest::STATUS_PENDING) {
            return back()->withErrors(['transfer_request' => 'This transfer request has already been decided.']);
        }

        $transferRequest->update([
            'status' => LeadTransferRequest::STATUS_REJECTED,
            'decision_note' => $validated['decision_note'] ?? null,
            'decided_by' => $areaManager->id,
            'decided_at' => now(),
        ]);

        return redirect()
            ->route('lead_transfer.approvals')
            ->with('success', 'Lead transfer request rejected.');
    }
}
