@if($workflowSubmitted)
    <style>
        .workflow-status { display:flex; align-items:center; gap:16px; padding:22px 24px; margin:20px 0 16px; border:1px solid #a7dbc7; border-left:5px solid #16845b; border-radius:12px; background:#f0faf5; color:#163e2d; box-shadow:0 3px 12px rgba(22,62,45,.05); }
        .workflow-status__icon { display:flex; align-items:center; justify-content:center; flex:0 0 44px; height:44px; background:#d5f0e2; border-radius:50%; color:#14744e; }
        .workflow-status__copy { flex:1; min-width:0; }
        .workflow-status__title { display:block; margin:0 0 5px; font-size:18px; line-height:1.4; font-weight:750; color:#163e2d; }
        .workflow-status__copy p { margin:0; font-size:14px; line-height:1.6; color:#456456; }
        .workflow-status a.workflow-status__action { display:inline-flex; align-items:center; justify-content:center; gap:9px; flex-shrink:0; min-height:44px; padding:0 20px; border:1px solid #111827; border-radius:8px; background:#111827; color:#fff; font-size:14px; font-weight:700; text-decoration:none; transition:background .15s; }
        .workflow-status a.workflow-status__action:hover { background:#293549; }
        .workflow-status a.workflow-status__action:focus-visible { outline:3px solid #2563eb; outline-offset:3px; }
        .workflow-status--editing { background:#eff6ff; border-color:#b8d3fa; border-left-color:#2563eb; }
        .workflow-status--editing .workflow-status__icon { background:#dbeafe; color:#1d4ed8; }
        .workflow-status--editing .workflow-status__title { color:#173968; }
        .workflow-status--editing .workflow-status__copy p { color:#435f82; }
        @media(max-width:600px) { .workflow-status { flex-wrap:wrap; padding:18px 16px; gap:12px; } .workflow-status__copy { flex-basis:calc(100% - 60px); } .workflow-status a.workflow-status__action { width:100%; box-sizing:border-box; } }
    </style>
    <section class="workflow-status {{ $workflowEditing ? 'workflow-status--editing' : '' }}" role="status" aria-label="{{ ucfirst($workflowType) }} submission status">
        <span class="workflow-status__icon" aria-hidden="true">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6"/></svg>
        </span>
        <div class="workflow-status__copy">
            <strong class="workflow-status__title">{{ ucfirst($workflowType) }} {{ $workflowEditing ? 'â€” editing saved details' : 'already submitted' }}</strong>
            <p>
                @if($workflowEditing)
                    Use Save &amp; Next to continue, or Save &amp; Exit to finish. On the last step, select Save Changes.
                @elseif($workflowOwner)
                    Your {{ $workflowType }} is saved. To make changes, select Edit {{ $workflowType }}.
                @else
                    These details are available for review. Only the enquiry creator can make changes.
                @endif
            </p>
        </div>
        @if($workflowOwner && !$workflowEditing)
            <a class="workflow-status__action" href="{{ route($workflowType . '.show', ['enquiry' => $enquiry->id, 'step' => $currentStep, 'edit' => 1]) }}">
                <svg aria-hidden="true" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m16 4 4 4M4 20l4-1L20 7a2.8 2.8 0 0 0-4-4L4 15z"/></svg>
                Edit {{ $workflowType }}
            </a>
        @elseif($workflowEditing)
            <a class="workflow-status__action" href="{{ route($workflowType . '.show', ['enquiry' => $enquiry->id, 'step' => $currentStep]) }}">Back to saved details</a>
        @endif
    </section>
@endif

