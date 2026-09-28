<?php

namespace App\Support;

use App\Models\Enquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkflowSubmission
{
    public static function save(Request $request, Enquiry $enquiry, string $type, callable $save)
    {
        abort_unless($request->user()?->id && (int) $enquiry->user_id === (int) $request->user()->id,
            403, 'Only the enquiry creator can submit or edit this record.');

        return DB::transaction(function () use ($request, $enquiry, $type, $save) {
            // Serialize submissions for the same enquiry, including first-time creation.
            $locked = Enquiry::query()->lockForUpdate()->findOrFail($enquiry->id);
            $record = $locked->$type;
            $submitted = $type === 'booking'
                ? $record?->booking_completed_at !== null
                : $record?->submitted_at !== null;
            $action = $request->input('action_type') ?: $request->input('action_type_fallback');
            if ($submitted && (!$request->boolean('edit') || !in_array($action, ['save', 'save_exit', 'next', 'save_next', 'exit'], true))) {
                $message = ucfirst($type) . ' already submitted. Use Edit to make changes and save.';
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json(['message' => $message, 'already_submitted' => true], 409);
                }
                return redirect()->route($type . '.show', $enquiry->id)->with('error', $message);
            }

            $response = $save($locked);
            if ($submitted && $request->boolean('edit') && $response instanceof \Illuminate\Http\RedirectResponse
                && $action !== 'save_exit') {
                $url = $response->getTargetUrl();
                $response->setTargetUrl($url . (str_contains($url, '?') ? '&' : '?') . 'edit=1');
            }
            return $response;
        });
    }
}
