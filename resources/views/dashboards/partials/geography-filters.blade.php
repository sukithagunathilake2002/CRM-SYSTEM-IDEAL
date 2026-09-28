<section class="card" id="geographyFilters">
    <h2>District and Province Filters</h2>
    <p>Filter both lead overviews below. Dates refer to when the enquiry was created.</p>
    <p role="status"><strong>{{ number_format(array_sum(array_column($analytics['by_district'] ?? [], 'leads'))) }} matching leads</strong>
        @if($analytics['has_active_filters'] ?? false)
            — filters applied to both overviews.
        @else
            — no filters applied.
        @endif
    </p>
    <form method="GET" action="{{ url()->current() }}#geographyFilters" class="geography-filter-form">
        <label>
            From Date
            <input type="date" name="from_date" value="{{ $analytics['filters']['from_date'] }}">
        </label>
        <label>
            To Date
            <input type="date" name="to_date" value="{{ $analytics['filters']['to_date'] }}">
        </label>
        <label>
            Owner Role
            <select name="owner_role">
                <option value="">All roles</option>
                @foreach($analytics['filter_options']['owner_roles'] as $option)
                    <option value="{{ $option['value'] }}" @selected(($analytics['filters']['owner_role'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </label>
        <label>
            District
            <select name="district">
                <option value="">All districts</option>
                @foreach(($analytics['filter_options']['districts'] ?? []) as $option)
                    <option value="{{ $option['value'] }}" @selected(($analytics['filters']['district'] ?? '') === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </label>
        <label>
            User
            <select name="user_id" id="geographyUserSelect">
                <option value="">Select owner role first</option>
                @foreach($analytics['filter_options']['users'] as $option)
                    <option
                        value="{{ $option['id'] }}"
                        data-role-key="{{ $option['role_key'] }}"
                        @selected((string) $analytics['filters']['user_id'] === (string) $option['id'])
                    >
                        {{ $option['name'] }} ({{ $option['role'] }})
                    </option>
                @endforeach
            </select>
        </label>
        <label>
            User Scope
            <select name="user_scope">
                @foreach($analytics['filter_options']['user_scopes'] as $option)
                    <option value="{{ $option['value'] }}" @selected(($analytics['filters']['user_scope'] ?? 'hierarchy') === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Lead Result
            <select name="lead_result">
                <option value="">All</option>
                @foreach($analytics['filter_options']['lead_results'] as $option)
                    <option value="{{ $option['value'] }}" @selected($analytics['filters']['lead_result'] === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Lead Temperature
            <select name="lead_temperature">
                <option value="">All</option>
                @foreach($analytics['filter_options']['lead_temperatures'] as $option)
                    <option value="{{ $option['value'] }}" @selected($analytics['filters']['lead_temperature'] === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Followup Type
            <select name="follow_type">
                <option value="">All</option>
                @foreach($analytics['filter_options']['follow_types'] as $option)
                    <option value="{{ $option['value'] }}" @selected($analytics['filters']['follow_type'] === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </label>
        <label>
            Followup Status
            <select name="followup_status">
                <option value="">All</option>
                @foreach($analytics['filter_options']['followup_statuses'] as $option)
                    <option value="{{ $option['value'] }}" @selected($analytics['filters']['followup_status'] === $option['value'])>{{ $option['label'] }}</option>
                @endforeach
            </select>
        </label>

        <div class="analytics-filter-actions">
            <button type="submit" class="btn-primary analytics-filter-btn">Apply Filters</button>
            <a href="{{ url()->current() }}#geographyFilters" class="btn-link alt analytics-filter-btn analytics-filter-reset">Reset</a>
        </div>
    </form>
</section>
<style>
.geography-filter-form { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; }
.geography-filter-form label { display:flex; flex-direction:column; gap:6px; font-weight:600; font-size:13px; }
.geography-filter-form input,.geography-filter-form select { width:100%; min-width:0; padding:10px; border:1px solid #cbd5e1; border-radius:8px; background:#fff; color:#111827; }
.geography-filter-form .analytics-filter-actions { grid-column:1/-1; display:flex; gap:10px; }
@media(max-width:800px) { .geography-filter-form { grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:480px) { .geography-filter-form { grid-template-columns:1fr; } }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.geography-filter-form');
    const role = form.elements.owner_role;
    const user = form.elements.user_id;
    const options = Array.from(user.options).slice(1);
    const updateUsers = () => {
        const selected = user.value;
        const available = options.filter(option => option.dataset.roleKey === role.value);
        user.replaceChildren(new Option(role.value ? 'All users in this role' : 'Select owner role first', ''), ...available);
        user.value = available.some(option => option.value === selected) ? selected : '';
        user.disabled = !role.value;
    };
    role.addEventListener('change', updateUsers);
    updateUsers();
});
</script>
