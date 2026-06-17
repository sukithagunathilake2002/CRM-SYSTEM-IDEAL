@php
    $followupEscalations = $followupEscalations ?? ['buckets' => [], 'total' => 0];
@endphp

<section class="card followup-escalation-card">
    <div class="followup-escalation-head">
        <div>
            <h2>Pending Followup Notifications</h2>
            <p>Escalated by lead owner and pending followup date.</p>
        </div>
        <span>{{ (int) ($followupEscalations['total'] ?? 0) }} pending</span>
    </div>

    <div class="followup-escalation-grid">
        @foreach(($followupEscalations['buckets'] ?? []) as $bucket)
            <article class="followup-escalation-bucket">
                <div class="followup-escalation-bucket-head">
                    <h3>{{ $bucket['title'] }}</h3>
                    <small>{{ $bucket['description'] }}</small>
                </div>

                <div class="followup-escalation-list">
                    @forelse(($bucket['rows'] ?? []) as $row)
                        <div class="followup-escalation-row">
                            <div class="followup-escalation-copy">
                                <strong>{{ $row['owner_name'] }}</strong>
                                <span>{{ $row['count'] }} lead{{ (int) $row['count'] === 1 ? '' : 's' }} | oldest {{ $row['oldest_follow_date_label'] }} | {{ $row['max_pending_days'] }} day{{ (int) $row['max_pending_days'] === 1 ? '' : 's' }}</span>
                                <small>Notify {{ $row['recipient_name'] }} ({{ $row['recipient_role'] }})</small>
                            </div>

                            <div class="followup-escalation-actions">
                                @if(!empty($row['mailto_url']))
                                    <a class="btn-link followup-escalation-btn" href="{{ $row['mailto_url'] }}">Notify</a>
                                @else
                                    <span class="followup-escalation-btn disabled">No Email</span>
                                @endif
                                <a class="btn-link alt followup-escalation-btn" href="{{ $row['epr_url'] }}">Open EPR</a>
                            </div>
                        </div>
                    @empty
                        <p class="followup-escalation-empty">No pending followups in this level.</p>
                    @endforelse
                </div>
            </article>
        @endforeach
    </div>
</section>
