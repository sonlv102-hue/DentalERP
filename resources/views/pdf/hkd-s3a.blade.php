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
    .paid { color: #166534; }
    .unpaid { color: #92400e; }
    .footer { margin-top: 20px; font-size: 9px; color: #666; text-align: right; }
</style>
</head>
<body>
<h1>SỔ THEO DÕI THUẾ, PHÍ PHẢI NỘP (S3-a HKD)</h1>
<h2>Thông tư 152/2025/TT-BTC</h2>
<div class="meta">
    <strong>Hộ/cá nhân KD:</strong> {{ $profile->full_name }} &nbsp;&nbsp;
    <strong>MST:</strong> {{ $profile->tax_code ?? '—' }} &nbsp;&nbsp;
    <strong>Địa chỉ:</strong> {{ $profile->address }} &nbsp;&nbsp;
    <strong>Kỳ:</strong> {{ $period }}
</div>

<table>
    <thead>
        <tr>
            <th style="width:5%">STT</th>
            <th style="width:20%">Loại thuế/phí</th>
            <th style="width:13%">Căn cứ tính</th>
            <th style="width:8%">Tỷ lệ</th>
            <th style="width:13%">Số phải nộp</th>
            <th style="width:10%">Hạn nộp</th>
            <th style="width:10%">Ngày nộp</th>
            <th style="width:13%">Đã nộp</th>
            <th style="width:8%">Trạng thái</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data['entries'] as $i => $e)
        <tr>
            <td class="center">{{ $i + 1 }}</td>
            <td>{{ $e['tax_type'] }}</td>
            <td class="num">{{ number_format($e['taxable_amount']) }}</td>
            <td class="center">{{ number_format($e['tax_rate'] * 100, 1) }}%</td>
            <td class="num">{{ number_format($e['tax_amount']) }}</td>
            <td class="center">{{ $e['due_date'] ?? '—' }}</td>
            <td class="center">{{ $e['paid_date'] ?? '—' }}</td>
            <td class="num">{{ number_format($e['paid_amount']) }}</td>
            <td class="center {{ $e['is_paid'] ? 'paid' : 'unpaid' }}">{{ $e['is_paid'] ? 'Đã nộp' : 'Chưa nộp' }}</td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center">Không có dữ liệu</td></tr>
        @endforelse
        <tr class="total">
            <td colspan="4" style="text-align:right">Tổng cộng</td>
            <td class="num">{{ number_format($data['total_tax']) }}</td>
            <td colspan="2"></td>
            <td class="num">{{ number_format($data['total_paid']) }}</td>
            <td></td>
        </tr>
    </tbody>
</table>

<div class="footer">In ngày {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
