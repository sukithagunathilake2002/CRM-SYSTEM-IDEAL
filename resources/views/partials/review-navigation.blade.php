<style>
    .lead-review-nav { display: flex; align-items: center; flex-wrap: wrap; gap: 16px; margin: 16px 0; padding: 16px; border: 1px solid #dde1e7; border-radius: 12px; background: #fff; }
    .lead-review-nav__steps { display: flex; flex-wrap: wrap; gap: 8px; }
    .lead-review-nav a, .lead-review-nav button { display: inline-flex; align-items: center; justify-content: center; min-height: 42px; padding: 0 16px; border: 1px solid #d6dae1; border-radius: 8px; background: #f6f7f9; color: #303744; font-size: 14px; font-weight: 600; text-decoration: none; box-sizing: border-box; }
    .lead-review-nav a:hover { background: #e9ecf1; border-color: #a7afbb; }
    .lead-review-nav a.lead-review-nav__next { background: #111827; border-color: #111827; color: #fff; }
    .lead-review-nav a:focus-visible { outline: 3px solid #2563eb; outline-offset: 3px; }
    .lead-review-nav a.lead-review-nav__exit { margin-left: auto; gap: 8px; background: #fff; }
    @media (max-width: 480px) { .lead-review-nav { padding: 12px; gap: 12px; } .lead-review-nav__steps { width: 100%; } .lead-review-nav__steps a, .lead-review-nav__steps button { flex: 1; padding: 0 10px; } }
    .lead-review-nav button:disabled { opacity: .45; cursor: not-allowed; }
</style>
<nav class="lead-review-nav" aria-label="Review steps">
    <div class="lead-review-nav__steps">
        @php
            $reviewLastStep = $reviewRoute === 'delivery.show' ? 6 : 5;
            $reviewCurrentStep = max(1, min($reviewLastStep, (int) $reviewCurrentStep));
            $previousReviewStep = $reviewCurrentStep - 1;
            $nextReviewStep = $reviewCurrentStep + 1;
            if (in_array($reviewRoute, ['booking.show', 'delivery.show'], true) && ($selectedFirstTimeBuyer ?? null) === 'yes') {
                $previousReviewStep = $reviewCurrentStep === 4 ? 2 : $previousReviewStep;
                $nextReviewStep = $reviewCurrentStep === 2 ? 4 : $nextReviewStep;
            }
        @endphp
        @if($reviewCurrentStep > 1)
            <a href="{{ route($reviewRoute, ['enquiry' => $enquiry->id, 'step' => $previousReviewStep]) }}">&larr; Previous</a>
        @else
            <button type="button" disabled>&larr; Previous</button>
        @endif
        @if($reviewCurrentStep < $reviewLastStep)
            <a class="lead-review-nav__next" href="{{ route($reviewRoute, ['enquiry' => $enquiry->id, 'step' => $nextReviewStep]) }}">Next &rarr;</a>
        @else
            <button type="button" disabled>Next &rarr;</button>
        @endif
    </div>
    <a class="lead-review-nav__exit" href="{{ route('enquiries.list') }}"><span aria-hidden="true">&larr;</span> Exit</a>
</nav>
