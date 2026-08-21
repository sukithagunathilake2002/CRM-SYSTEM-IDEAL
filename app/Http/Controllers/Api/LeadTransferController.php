<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enquiry;
use App\Models\LeadTransferRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadTransferController extends Controller
{
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
