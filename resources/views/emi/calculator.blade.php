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
        <div class="emi-heading">
            <h1>EMI Calculator</h1>
            <p>Estimate your monthly installment in seconds.</p>
        </div>

        <div class="emi-card">
            <div class="emi-group">
                <label for="months">Payback Period (Months)</label>
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

            <div class="emi-group">
                <label for="loan">Loan Amount (Rs)</label>
                <input type="number" id="loan" value="0">
            </div>

            <div class="emi-group">
                <label for="interest">Interest Rate (%)</label>
                <input type="number" id="interest" value="0">
            </div>

            <div class="emi-result">
                <span>You Pay</span>
                <h2>Rs. <span id="emi">0</span></h2>
                <small>per month</small>
            </div>

            <button class="emi-btn" type="button" onclick="calculateEMI()">Calculate</button>
        </div>
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
