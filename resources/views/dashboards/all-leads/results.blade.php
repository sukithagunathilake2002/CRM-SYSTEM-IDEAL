@if($filters['date_notice'])<p class="all-leads-notice" role="status">{{ $filters['date_notice'] }}</p>@endif
<div class="all-leads-results-bar">
    <p><strong>{{ number_format($enquiries->total()) }}</strong> matching leads <span>&middot; {{ $filters['from_date'] }} to {{ $filters['to_date'] }}</span></p>
    <a class="all-leads-button" href="{{ $exportUrl }}">Download CSV</a>
</div>
<div class="all-leads-filter-summary" aria-label="Applied filters">
    @foreach($summary as $label => $value)
        @if(!in_array($label, ['From Date', 'To Date']) && $value !== 'All')<span><strong>{{ $label }}:</strong> {{ $value }}</span>@endif
    @endforeach
</div>
<p class="all-leads-report-note">The CSV includes every matching lead, across all pages. Competition means lost to a competitor. Unrecorded answers are shown separately from “No”.</p>
<div class="all-leads-table-wrap" tabindex="0" role="region" aria-label="All leads results">
    <table class="all-leads-table">
        <thead><tr>
            <th>Lead / Inquiry Date</th><th>Customer</th><th>Area Manager / Consultant</th><th>Vehicle Model</th><th>Lead Source / Status</th>
            <th>Test Drive Given</th><th>First Time Buyer</th><th>Interested in Exchange</th><th>Lost to Competitor</th><th>Action</th>
        </tr></thead>
        <tbody>
            @forelse($rows as $row)
                <tr data-lead-id="{{ $row['id'] }}">
                    <td><strong>#{{ $row['id'] }}</strong><small>{{ $row['date'] }}</small></td>
                    <td><strong>{{ $row['customer'] }}</strong><small>{{ $row['phone'] }}</small><small>{{ $row['district'] }}</small></td>
                    <td><strong>{{ $row['area_manager'] }}</strong><small>{{ $row['consultant'] }}</small><small>Created by: {{ $row['owner'] }}</small></td>
                    <td>{{ $row['vehicle_model'] ?: 'Not recorded' }}</td>
                    <td>{{ $row['lead_source'] ?: 'Not recorded' }}<small>{{ $row['lead_status'] }} &middot; {{ $row['lead_result'] }}</small></td>
                    @foreach(['test_drive', 'first_buyer', 'interested_exchange', 'competition'] as $key)
                        <td><span class="all-leads-answer {{ $row[$key] === 'Yes' ? 'yes' : '' }}">{{ $row[$key] }}</span>
                            @if($key === 'competition' && $row[$key] === 'Yes')<small>{{ trim($row['competition_brand'].' '.$row['competition_model']) }}</small>@endif
                        </td>
                    @endforeach
                    <td><a href="{{ route('followup.show', $row['id']) }}">View Lead</a></td>
                </tr>
            @empty
                <tr><td colspan="10" class="all-leads-empty">No leads match these filters. Change or clear filters to see more results.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@include('enquiries.partials.pagination')
