@extends('layouts.app')

<link rel="stylesheet" href="{{ asset('css/emi.css') }}">


@section('content')

<div class="emi-container">

    <h3 class="emi-title">EMI Calculator</h3>

    <div class="emi-card">

        <div class="emi-group">
            <label>Payback Period (Months)</label>
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
            <label>Loan Amount (Rs)</label>
            <input type="number" id="loan" value="0">
        </div>

        <div class="emi-group">
            <label>Interest Rate (%)</label>
            <input type="number" id="interest" value="0">
        </div>

        <div class="emi-result">
            <span>You Pay</span>
            <h2>Rs. <span id="emi">0</span></h2>
            <small>per month</small>
        </div>

        <button class="emi-btn" onclick="calculateEMI()">CALCULATE</button>

    </div>

</div>

<script>
    function calculateEMI() {
        let P = parseFloat(document.getElementById("loan").value);
        let N = parseInt(document.getElementById("months").value);
        let R = parseFloat(document.getElementById("interest").value) / 12 / 100;

        let EMI = (P * R * Math.pow(1 + R, N)) / (Math.pow(1 + R, N) - 1);

        document.getElementById("emi").innerHTML = Math.round(EMI);

    }
</script>

@endsection