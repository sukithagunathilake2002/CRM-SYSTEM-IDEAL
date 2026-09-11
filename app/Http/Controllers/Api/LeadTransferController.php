<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\LeadTransferRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeadTransferController extends Controller
{
    public function requestData(Request $request): JsonResponse
    {
        $consultant = $request->user();
        abort_unless($consultant?->role === User::ROLE_SALES_CONSULTANT, 403);

        $managerId = (int) ($consultant->manager_id ?? 0);
        $targetConsultants = User::query()
            ->where('role', User::ROLE_SALES_CONSULTANT)
            ->where('manager_id', $managerId)
            ->where('id', '!=', $consultant->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'manager_id']);

        $leads = Enquiry::query()
            ->with(['customer:id,title,name', 'vehicle:id,model,variant'])
            ->where('user_id', $consultant->id)
            ->latest()
            ->get(['id', 'user_id', 'customer_id', 'vehicle_id', 'follow_type', 'follow_date', 'created_at']);

        $requests = LeadTransferRequest::query()
            ->with(['enquiry.customer:id,title,name', 'toUser:id,name,email', 'decider:id,name'])
            ->where('requested_by', $consultant->id)
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'leads' => $leads,
            'target_consultants' => $targetConsultants,
            'requests' => $requests,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $consultant = $request->user();
        abort_unless($consultant?->role === User::ROLE_SALES_CONSULTANT, 403);

        $validated = $request->validate([
            'enquiry_id' => ['required', 'integer', Rule::exists('enquiries', 'id')],
            'to_user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if (empty($consultant->manager_id)) {
            return response()->json(['message' => 'You must be assigned to an Area Manager before requesting a transfer.'], 422);
        }

        $lead = Enquiry::query()->find((int) $validated['enquiry_id']);
        if (!$lead || (int) $lead->user_id !== (int) $consultant->id) {
            return response()->json(['message' => 'Please select one of your own leads.'], 422);
        }

        $target = User::query()->find((int) $validated['to_user_id']);
        if (!$target
            || $target->role !== User::ROLE_SALES_CONSULTANT
            || (int) $target->id === (int) $consultant->id
            || (int) $target->manager_id !== (int) $consultant->manager_id) {
            return response()->json(['message' => 'Please select a Sales Consultant under your Area Manager.'], 422);
        }

        $hasPendingRequest = LeadTransferRequest::query()
            ->where('enquiry_id', $lead->id)
            ->where('status', LeadTransferRequest::STATUS_PENDING)
            ->exists();
        if ($hasPendingRequest) {
            return response()->json(['message' => 'This lead already has a pending transfer request.'], 422);
        }

        $transferRequest = LeadTransferRequest::query()->create([
            'enquiry_id' => $lead->id,
            'from_user_id' => $consultant->id,
            'to_user_id' => $target->id,
            'area_manager_id' => $consultant->manager_id,
            'requested_by' => $consultant->id,
            'status' => LeadTransferRequest::STATUS_PENDING,
            'reason' => $validated['reason'],
        ]);

        return response()->json([
            'message' => 'Lead transfer request sent to your Area Manager for approval.',
            'request' => $transferRequest,
        ], 201);
    }

    public function approvals(Request $request): JsonResponse
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

        return response()->json($requests);
    }

    public function approve(Request $request, LeadTransferRequest $transferRequest): JsonResponse
    {
        $areaManager = $request->user();
        abort_unless($areaManager?->role === User::ROLE_AREA_MANAGER, 403);
        abort_unless((int) $transferRequest->area_manager_id === (int) $areaManager->id, 403);

        if ($transferRequest->status !== LeadTransferRequest::STATUS_PENDING) {
            return response()->json(['message' => 'This transfer request has already been decided.'], 422);
        }

        DB::transaction(function () use ($transferRequest, $areaManager): void {
            $lead = Enquiry::query()->whereKey($transferRequest->enquiry_id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $lead->user_id === (int) $transferRequest->from_user_id, 422,
                'This lead is no longer assigned to the requesting consultant.');

            $target = User::query()->findOrFail((int) $transferRequest->to_user_id);
            abort_unless($target->role === User::ROLE_SALES_CONSULTANT
                && (int) $target->manager_id === (int) $areaManager->id, 422,
                'The target consultant is no longer under your hierarchy.');

            $lead->update(['user_id' => (int) $transferRequest->to_user_id]);
            $transferRequest->update([
                'status' => LeadTransferRequest::STATUS_APPROVED,
                'decided_by' => $areaManager->id,
                'decided_at' => now(),
            ]);
        });

        return response()->json(['message' => 'Lead transfer approved and completed.']);
    }

    public function reject(Request $request, LeadTransferRequest $transferRequest): JsonResponse
    {
        $areaManager = $request->user();
        abort_unless($areaManager?->role === User::ROLE_AREA_MANAGER, 403);
        abort_unless((int) $transferRequest->area_manager_id === (int) $areaManager->id, 403);
        $validated = $request->validate(['decision_note' => ['nullable', 'string', 'max:1000']]);

        if ($transferRequest->status !== LeadTransferRequest::STATUS_PENDING) {
            return response()->json(['message' => 'This transfer request has already been decided.'], 422);
        }

        $transferRequest->update([
            'status' => LeadTransferRequest::STATUS_REJECTED,
            'decision_note' => $validated['decision_note'] ?? null,
            'decided_by' => $areaManager->id,
            'decided_at' => now(),
        ]);

        return response()->json(['message' => 'Lead transfer request rejected.']);
    }
}
