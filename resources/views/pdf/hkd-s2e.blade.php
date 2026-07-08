<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<style>
    @font-face { font-family: 'DejaVu Sans'; src: url('{{ storage_path("fonts/DejaVuSans.ttf") }}'); }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #111; margin: 20px; }
    h1  { font-size: 13px; text-align: center; margin-bottom: 2px; }
    h2  { font-size: 11px; text-align: center; margin-bottom: 12px; color: #444; font-weight: normal; }
    .meta { font-size: 9px; color: #555; margin-bottom: 8px; }
    .acct-header { font-weight: bold; font-size: 10px; margin: 10px 0 4px; background: #e8e8e8; padding: 3px 6px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
    th, td { border: 1px solid #ccc; padding: 3px 5px; }
    th { background: #f0f0f0; font-weight: bold; text-align: center; }
    td.num { text-align: right; }
    td.center { text-align: center; }
    .total { font-weight: bold; background: #f9f9f9; }
    .footer { margin-top: 20px; font-size: 9px; color: #666; text-align: right; }
</style>
</head>
<body>
<h1>SỔ QUỸ TIỀN MẶT / NGÂN HÀNG (S2-e HKD)</h1>
<h2>Thông tư 152/2025/TT-BTC</h2>
<div class="meta">
    <strong>Hộ/cá nhân KD:</strong> {{ $profile->full_name }} &nbsp;&nbsp;
    <strong>MST:</strong> {{ $profile->tax_code ?? '—' }} &nbsp;&nbsp;
    <strong>Kỳ:</strong> {{ $period }}
</div>

@foreach($data['accounts'] as $acct)
<div class="acct-header">Tài khoản: {{ $acct['name'] }} ({{ $acct['type_label'] }}) — Số dư đầu kỳ: {{ number_format($acct['opening_balance']) }} ₫</div>
<table>
    <thead>
        <tr>
            <th style="width:10%">Ngày</th>
            <th style="width:12%">Số CT</th>
            <th>Diễn giải</th>
            <th style="width:8%">Loại</th>
            <th style="width:13%">Thu</th>
            <th style="width:13%">Chi</th>
            <th style="width:13%">Số dư</th>
        </tr>
    </thead>
    <tbody>
        @php $balance = $acct['opening_balance']; @endphp
        <tr>
            <td colspan="6" style="text-align:right;font-weight:bold">Số dư đầu kỳ</td>
            <td class="num">{{ number_format($balance) }}</td>
        </tr>
        @foreach($acct['transactions'] as $t)
        @php
            if ($t['trans_type'] === 'receipt') { $balance += $t['amount']; }
            else { $balance -= $t['amount']; }
        @endphp
        <tr>
            <td class="center">{{ $t['date'] }}</td>
            <td class="center">{{ $t['document_no'] ?? '' }}</td>
            <td>{{ $t['description'] }}</td>
            <td class="center">{{ $t['trans_type'] === 'receipt' ? 'Thu' : 'Chi' }}</td>
            <td class="num">{{ $t['trans_type'] === 'receipt' ? number_format($t['amount']) : '' }}</td>
            <td class="num">{{ $t['trans_type'] === 'payment' ? number_format($t['amount']) : '' }}</td>
            <td class="num">{{ number_format($balance) }}</td>
        </tr>
        @endforeach
        <tr class="total">
            <td colspan="4" style="text-align:right">Cộng / Số dư cuối kỳ</td>
            <td class="num">{{ number_format($acct['total_receipts']) }}</td>
            <td class="num">{{ number_format($acct['total_payments']) }}</td>
            <td class="num">{{ number_format($acct['closing_balance']) }}</td>
        </tr>
    </tbody>
</table>
@endforeach

@if(empty($data['accounts']))
<p style="text-align:center;color:#999">Không có tài khoản quỹ kỳ này.</p>
@endif

<div class="footer">In ngày {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
