<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<style>
    @font-face { font-family: 'DejaVu Sans'; src: url('{{ storage_path("fonts/DejaVuSans.ttf") }}'); }
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #111; margin: 20px; }
    h1  { font-size: 13px; text-align: center; margin-bottom: 2px; }
    h2  { font-size: 11px; text-align: center; margin-bottom: 12px; color: #444; font-weight: normal; }
    .meta { font-size: 9px; color: #555; margin-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    th, td { border: 1px solid #ccc; padding: 4px 6px; }
    th { background: #f0f0f0; font-weight: bold; text-align: center; }
    td.num { text-align: right; }
    td.center { text-align: center; }
    .total { font-weight: bold; background: #f9f9f9; }
    .summary { margin-top: 12px; }
    .footer { margin-top: 20px; font-size: 9px; color: #666; text-align: right; }
</style>
</head>
<body>
<h1>SỔ THEO DÕI DOANH THU VÀ THUẾ KHOÁN (S2-a HKD)</h1>
<h2>Thông tư 152/2025/TT-BTC</h2>
<div class="meta">
    <strong>Hộ/cá nhân KD:</strong> {{ $profile->full_name }} &nbsp;&nbsp;
    <strong>MST:</strong> {{ $profile->tax_code ?? '—' }} &nbsp;&nbsp;
    <strong>Kỳ:</strong> {{ $period }}
</div>

<table>
    <thead>
        <tr>
            <th style="width:5%">STT</th>
            <th style="width:10%">Ngày</th>
            <th style="width:12%">Số CT</th>
            <th style="width:20%">Người mua</th>
            <th>Diễn giải</th>
            <th style="width:13%">Doanh thu</th>
            <th style="width:10%">VAT</th>
            <th style="width:10%">TNCN</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data['entries'] as $i => $e)
        <tr>
            <td class="center">{{ $i + 1 }}</td>
            <td class="center">{{ $e['date'] }}</td>
            <td class="center">{{ $e['document_no'] ?? '' }}</td>
            <td>{{ $e['buyer_name'] ?? '' }}</td>
            <td>{{ $e['description'] }}</td>
            <td class="num">{{ number_format($e['amount']) }}</td>
            <td class="num">{{ number_format($e['vat_amount'] ?? 0) }}</td>
            <td class="num">{{ number_format($e['pit_amount'] ?? 0) }}</td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center">Không có dữ liệu</td></tr>
        @endforelse
        <tr class="total">
            <td colspan="5" style="text-align:right">Cộng</td>
            <td class="num">{{ number_format($data['total_amount']) }}</td>
            <td class="num">{{ number_format($data['total_vat']) }}</td>
            <td class="num">{{ number_format($data['total_pit']) }}</td>
        </tr>
    </tbody>
</table>

<div class="footer">In ngày {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
