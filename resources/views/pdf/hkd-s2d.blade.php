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
    .item-header { font-weight: bold; font-size: 10px; margin: 10px 0 4px; background: #e8e8e8; padding: 3px 6px; }
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
<h1>SỔ THEO DÕI HÀNG TỒN KHO — BÌNH QUÂN GIA QUYỀN (S2-d HKD)</h1>
<h2>Thông tư 152/2025/TT-BTC</h2>
<div class="meta">
    <strong>Hộ/cá nhân KD:</strong> {{ $profile->full_name }} &nbsp;&nbsp;
    <strong>MST:</strong> {{ $profile->tax_code ?? '—' }} &nbsp;&nbsp;
    <strong>Kỳ:</strong> {{ $period }}
</div>

@foreach($data['items'] as $item)
<div class="item-header">Mặt hàng: {{ $item['name'] }} ({{ $item['unit'] }})</div>
<table>
    <thead>
        <tr>
            <th style="width:10%">Ngày</th>
            <th>Diễn giải</th>
            <th style="width:8%">Loại</th>
            <th style="width:8%">SL nhập</th>
            <th style="width:10%">Đ.giá nhập</th>
            <th style="width:8%">SL xuất</th>
            <th style="width:10%">Đ.giá BQ</th>
            <th style="width:10%">TT xuất</th>
            <th style="width:8%">SL tồn</th>
            <th style="width:10%">GT tồn</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="center">—</td>
            <td>Tồn đầu kỳ</td>
            <td class="center">—</td>
            <td class="num">{{ $item['opening_qty'] }}</td>
            <td class="num">{{ number_format($item['opening_cost']) }}</td>
            <td></td><td></td><td></td>
            <td class="num">{{ $item['opening_qty'] }}</td>
            <td class="num">{{ number_format($item['opening_value']) }}</td>
        </tr>
        @foreach($item['transactions'] as $t)
        <tr>
            <td class="center">{{ $t['date'] }}</td>
            <td>{{ $t['description'] }}</td>
            <td class="center">{{ $t['trans_type'] === 'import' ? 'Nhập' : 'Xuất' }}</td>
            <td class="num">{{ $t['trans_type'] === 'import' ? $t['quantity'] : '' }}</td>
            <td class="num">{{ $t['trans_type'] === 'import' ? number_format($t['unit_cost']) : '' }}</td>
            <td class="num">{{ $t['trans_type'] === 'export' ? $t['quantity'] : '' }}</td>
            <td class="num">{{ number_format($t['avg_cost']) }}</td>
            <td class="num">{{ $t['trans_type'] === 'export' ? number_format($t['amount']) : '' }}</td>
            <td class="num">{{ $t['running_qty'] }}</td>
            <td class="num">{{ number_format($t['running_value']) }}</td>
        </tr>
        @endforeach
        <tr class="total">
            <td colspan="2">Tồn cuối kỳ</td>
            <td></td>
            <td class="num">{{ $item['total_import_qty'] }}</td>
            <td></td>
            <td class="num">{{ $item['total_export_qty'] }}</td>
            <td></td>
            <td class="num">{{ number_format($item['total_export_amount']) }}</td>
            <td class="num">{{ $item['closing_qty'] }}</td>
            <td class="num">{{ number_format($item['closing_value']) }}</td>
        </tr>
    </tbody>
</table>
@endforeach

@if(empty($data['items']))
<p style="text-align:center;color:#999">Không có dữ liệu hàng tồn kho kỳ này.</p>
@endif

<div class="footer">In ngày {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
