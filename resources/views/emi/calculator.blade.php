@extends('layouts.app')

<link rel="stylesheet" href="{{ asset('css/emi.css') }}">


@section('content')
<div class="emi-page">
    <header class="emi-topbar">
        <a href="{{ route('dashboard.main') }}" class="brand-logo-link" aria-label="Go to dashboard">
            <img src="{{ asset('icons/logo.png') }}" alt="Ideal Motors" class="emi-brand-logo">
        </a>
        <div class="top-icons-right"></div>
    </header>

    <div class="emi-shell">
        <div class="emi-layout">
            <section class="emi-form-panel">
                <div class="emi-heading">
                    <h1>EMI Calculator</h1>
                    <p>Estimate your monthly installment in seconds.</p>
                </div>

                <div class="emi-group">
                    <label for="months">Payback Period (Months)</label>
                    <div class="emi-input-wrap">
                        <span class="emi-input-prefix" aria-hidden="true">&#128197;</span>
                        <select id="months">
                            <option value="12" selected>12</option>
                            <option value="24">24</option>
                            <option value="36">36</option>
                            <option value="48">48</option>
                            <option value="60">60</option>
                            <option value="72">72</option>
                            <option value="84">84</option>
                            <option value="96">96</option>
                            <option value="108">108</option>
                            <option value="120">120</option>
                        </select>
                    </div>
                    <small>Select the loan tenure in months</small>
                </div>

                <div class="emi-group">
                    <label for="loan">Loan Amount (Rs)</label>
                    <div class="emi-input-wrap">
                        <span class="emi-input-prefix" aria-hidden="true">Rs</span>
                        <input type="number" id="loan" value="0">
                    </div>
                    <small>Enter the total loan amount</small>
                </div>

                <div class="emi-group">
                    <label for="interest">Interest Rate (%)</label>
                    <div class="emi-input-wrap">
                        <span class="emi-input-prefix" aria-hidden="true">%</span>
                        <input type="number" id="interest" value="0">
                    </div>
                    <small>Enter the annual interest rate</small>
                </div>
            </section>

            <aside class="emi-result-panel">
                <div class="emi-result-card">
                    <span class="emi-result-title">Your Estimate EMI</span>
                    <span class="emi-title-rule" aria-hidden="true"></span>
                    <h2>Rs. <span id="emi">0</span></h2>
                    <small class="emi-result-subtitle">Per month</small>
                    <div class="emi-result-icon">
                        <svg class="emi-estimate-icon" viewBox="0 0 64 64" aria-hidden="true" focusable="false">
                            <rect x="10" y="8" width="31" height="44" rx="5" fill="#ffffff"></rect>
                            <rect x="15" y="14" width="20" height="7" rx="2" fill="#1b4dad"></rect>
                            <rect x="15" y="25" width="6" height="6" rx="1.5" fill="#1b4dad"></rect>
                            <rect x="24" y="25" width="6" height="6" rx="1.5" fill="#1b4dad"></rect>
                            <rect x="33" y="25" width="6" height="6" rx="1.5" fill="#1b4dad"></rect>
                            <rect x="15" y="34" width="6" height="6" rx="1.5" fill="#1b4dad"></rect>
                            <rect x="24" y="34" width="6" height="6" rx="1.5" fill="#1b4dad"></rect>
                            <circle cx="45" cy="45" r="13" fill="#ffffff"></circle>
                            <text x="45" y="50" text-anchor="middle" font-size="14" font-weight="700" fill="#1b4dad">$</text>
                        </svg>
                    </div>
                </div>

                <div class="emi-benefits">
                    <article class="emi-benefit">
                        <img src="{{ asset('icons/quickeasyemi.png') }}" alt="Quick and easy">
                        <h4>Quick & Easy</h4>
                        <p>Real-time calculation</p>
                    </article>
                    <article class="emi-benefit">
                        <img src="{{ asset('icons/100 emi.png') }}" alt="100% secure">
                        <h4>100% Secure</h4>
                        <p>Your data is safe</p>
                    </article>
                    <article class="emi-benefit">
                        <img src="{{ asset('icons/accurateemi.png') }}" alt="Accurate results">
                        <h4>Accurate results</h4>
                        <p>Calculated instantly</p>
                    </article>
                </div>
            </aside>
        </div>

        <button class="emi-btn" type="button" onclick="calculateEMI()">
            Calculate
        </button>
    </div>
</div>

<script>
    function calculateEMI() {
        let P = parseFloat(document.getElementById("loan").value);
        let N = parseInt(document.getElementById("months").value);
        let R = parseFloat(document.getElementById("interest").value) / 12 / 100;
        let EMI = 0;

        if (R > 0) {
            EMI = (P * R * Math.pow(1 + R, N)) / (Math.pow(1 + R, N) - 1);
        } else if (N > 0) {
            EMI = P / N;
        }

        document.getElementById("emi").innerHTML = Math.round(EMI || 0).toLocaleString();
    }
</script>

@endsection
