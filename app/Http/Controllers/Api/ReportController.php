<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        $period = $request->period ?? 'daily';
        $query = Order::where('payment_status', 'paid')->where('status', '!=', 'cancelled');

        [$current, $previous] = match ($period) {
            'weekly' => [now()->startOfWeek(), now()->subWeek()->startOfWeek()],
            'monthly' => [now()->startOfMonth(), now()->subMonth()->startOfMonth()],
            default => [now()->startOfDay(), now()->subDay()->startOfDay()],
        };

        $groupFormat = match ($period) {
            'weekly' => '%Y-%u',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $currentRevenue = (clone $query)->where('created_at', '>=', $current)->sum('total_price');
        $previousRevenue = (clone $query)->where('created_at', '>=', $previous)->where('created_at', '<', $current)->sum('total_price');
        $currentOrders = (clone $query)->where('created_at', '>=', $current)->count();
        $previousOrders = (clone $query)->where('created_at', '>=', $previous)->where('created_at', '<', $current)->count();

        $chartData = DB::table('orders')
            ->select(DB::raw("DATE_FORMAT(created_at, '$groupFormat') as period"), DB::raw('SUM(total_price) as revenue'), DB::raw('COUNT(*) as orders'))
            ->where('payment_status', 'paid')->where('status', '!=', 'cancelled')
            ->when($period === 'daily', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)))
            ->when($period === 'weekly', fn ($q) => $q->where('created_at', '>=', now()->subWeeks(12)))
            ->when($period === 'monthly', fn ($q) => $q->where('created_at', '>=', now()->subMonths(12)))
            ->groupBy('period')->orderBy('period')->get();

        return response()->json([
            'period' => $period,
            'current_revenue' => $currentRevenue, 'previous_revenue' => $previousRevenue,
            'revenue_change' => $previousRevenue > 0 ? round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1) : 0,
            'current_orders' => $currentOrders,  'previous_orders' => $previousOrders,
            'chart' => $chartData,
        ]);
    }

    public function byCategory(): JsonResponse
    {
        $data = DB::table('order_items')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select('menu_items.category', DB::raw('SUM(order_items.subtotal) as revenue'), DB::raw('SUM(order_items.quantity) as qty_sold'), DB::raw('COUNT(DISTINCT orders.id) as order_count'))
            ->where('orders.payment_status', 'paid')->where('orders.status', '!=', 'cancelled')
            ->groupBy('menu_items.category')->get();

        $total = $data->sum('revenue');
        $result = $data->map(fn ($d) => [...(array) $d, 'percentage' => $total > 0 ? round(($d->revenue / $total) * 100, 1) : 0]);

        return response()->json(['data' => $result, 'total_revenue' => $total]);
    }

    public function topMenu(): JsonResponse
    {
        $data = DB::table('order_items')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select('menu_items.id', 'menu_items.name', 'menu_items.emoji', 'menu_items.category', DB::raw('SUM(order_items.quantity) as qty_sold'), DB::raw('SUM(order_items.subtotal) as revenue'))
            ->where('orders.payment_status', 'paid')->where('orders.status', '!=', 'cancelled')
            ->groupBy('menu_items.id', 'menu_items.name', 'menu_items.emoji', 'menu_items.category')
            ->orderByDesc('qty_sold')->limit(10)->get();

        return response()->json(['data' => $data]);
    }

    public function orders(Request $request): JsonResponse
    {
        $query = Order::with('items')
            ->when($request->from, fn ($q) => $q->whereDate('created_at', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('created_at', '<=', $request->to))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest();

        return response()->json($query->paginate(50));
    }

    public function export(Request $request)
    {
        $from = $request->from ?? now()->startOfMonth()->toDateString();
        $to = $request->to ?? now()->toDateString();
        $type = $request->type ?? 'excel';

        $orders = Order::with('items')->whereDate('created_at', '>=', $from)->whereDate('created_at', '<=', $to)->where('status', '!=', 'cancelled')->get();
        $categoryReport = DB::table('order_items')
            ->join('menu_items', 'order_items.menu_item_id', '=', 'menu_items.id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select('menu_items.category', DB::raw('SUM(order_items.subtotal) as revenue'), DB::raw('SUM(order_items.quantity) as qty'))
            ->whereDate('orders.created_at', '>=', $from)->whereDate('orders.created_at', '<=', $to)->where('orders.status', '!=', 'cancelled')
            ->groupBy('menu_items.category')->get();

        return $type === 'pdf'
            ? $this->exportPdf($orders, $categoryReport, $from, $to)
            : $this->exportXlsx($orders, $categoryReport, $from, $to);
    }

    private function exportXlsx($orders, $categoryReport, $from, $to)
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Laporan Kantin');
        $sheet = $spreadsheet->getActiveSheet();

        $red = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 12], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DA291C']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];
        $dark = ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '333333']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]];
        $yellow = ['font' => ['bold' => true], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC72C']]];
        $border = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]]];

        $totalRevenue = $orders->where('payment_status', 'paid')->sum('total_price');
        $totalOrders = $orders->count();
        $avgOrder = $totalOrders > 0 ? round($totalRevenue / $totalOrders) : 0;

        $row = 1;
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue("A{$row}", 'LAPORAN KANTIN MAS WAWAN');
        $sheet->getStyle("A{$row}")->applyFromArray($red);
        $sheet->getRowDimension($row)->setRowHeight(28);
        $row++;

        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue("A{$row}", "Periode: {$from} s/d {$to}   |   Dicetak: ".now()->format('d/m/Y H:i'));
        $sheet->getStyle("A{$row}")->getFont()->setItalic(true);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row += 2;

        // Summary
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", 'RINGKASAN');
        $sheet->getStyle("A{$row}")->applyFromArray($yellow);
        $row++;
        foreach ([['Total Pendapatan', 'Rp '.number_format($totalRevenue, 0, ',', '.')], ['Total Pesanan', $totalOrders], ['Rata-rata/Pesanan', 'Rp '.number_format($avgOrder, 0, ',', '.')]] as [$l,$v]) {
            $sheet->setCellValue("A{$row}", $l);
            $sheet->setCellValue("B{$row}", $v);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
        }
        $row++;

        // Category
        $sheet->mergeCells("A{$row}:D{$row}");
        $sheet->setCellValue("A{$row}", 'REKAP PER KATEGORI');
        $sheet->getStyle("A{$row}")->applyFromArray($yellow);
        $row++;
        foreach (['Kategori', 'Terjual (qty)', 'Pendapatan', 'Persentase'] as $i => $h) {
            $sheet->setCellValue(chr(65 + $i).$row, $h);
        }
        $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($dark);
        $row++;
        foreach ($categoryReport as $cat) {
            $pct = $totalRevenue > 0 ? round(($cat->revenue / $totalRevenue) * 100, 1) : 0;
            $sheet->setCellValue("A{$row}", ucfirst($cat->category));
            $sheet->setCellValue("B{$row}", $cat->qty);
            $sheet->setCellValue("C{$row}", 'Rp '.number_format($cat->revenue, 0, ',', '.'));
            $sheet->setCellValue("D{$row}", $pct.'%');
            $sheet->getStyle("A{$row}:D{$row}")->applyFromArray($border);
            $row++;
        }
        $row++;

        // Orders
        $sheet->mergeCells("A{$row}:F{$row}");
        $sheet->setCellValue("A{$row}", 'RIWAYAT PESANAN');
        $sheet->getStyle("A{$row}")->applyFromArray($yellow);
        $row++;
        foreach (['No. Pesanan', 'Nama', 'Total', 'Status', 'Pembayaran', 'Tanggal'] as $i => $h) {
            $sheet->setCellValue(chr(65 + $i).$row, $h);
        }
        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($dark);
        $row++;
        foreach ($orders as $i => $o) {
            $sheet->setCellValue("A{$row}", $o->order_number);
            $sheet->setCellValue("B{$row}", $o->customer_name);
            $sheet->setCellValue("C{$row}", 'Rp '.number_format($o->total_price, 0, ',', '.'));
            $sheet->setCellValue("D{$row}", $o->status);
            $sheet->setCellValue("E{$row}", $o->payment_status);
            $sheet->setCellValue("F{$row}", $o->created_at->format('d/m/Y H:i'));
            if ($i % 2 === 0) {
                $sheet->getStyle("A{$row}:F{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9F9F9');
            }
            $sheet->getStyle("A{$row}:F{$row}")->applyFromArray($border);
            $row++;
        }

        foreach (['A' => 22, 'B' => 22, 'C' => 20, 'D' => 14, 'E' => 14, 'F' => 20] as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }

        $filename = "laporan-kantin-{$from}-{$to}.xlsx";
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function exportPdf($orders, $categoryReport, $from, $to)
    {
        $totalRevenue = $orders->where('payment_status', 'paid')->sum('total_price');
        $totalOrders = $orders->count();
        $avgOrder = $totalOrders > 0 ? round($totalRevenue / $totalOrders) : 0;
        $printDate = now()->format('d/m/Y H:i');
        $fmtRevenue = 'Rp '.number_format($totalRevenue, 0, ',', '.');
        $fmtAvg = 'Rp '.number_format($avgOrder, 0, ',', '.');

        $catRows = $categoryReport->map(function ($c) use ($totalRevenue) {
            $pct = $totalRevenue > 0 ? round(($c->revenue / $totalRevenue) * 100, 1) : 0;
            $cat = ucfirst($c->category);
            $qty = number_format($c->qty);
            $rev = 'Rp '.number_format($c->revenue, 0, ',', '.');

            return "<tr><td>{$cat}</td><td style='text-align:right'>{$qty}</td><td style='text-align:right'>{$rev}</td><td style='text-align:right'>{$pct}%</td></tr>";
        })->implode('');

        $orderRows = $orders->take(200)->map(function ($o) {
            $num = $o->order_number;
            $name = htmlspecialchars($o->customer_name);
            $total = 'Rp '.number_format($o->total_price, 0, ',', '.');
            $date = $o->created_at->format('d/m/Y H:i');
            $st = $o->status;

            return "<tr><td>{$num}</td><td>{$name}</td><td style='text-align:right'>{$total}</td><td><span class='badge badge-{$st}'>{$st}</span></td><td>{$date}</td></tr>";
        })->implode('');

        $html = <<<HTML
<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8">
<style>
  * { box-sizing: border-box; }
  body { font-family: Arial, sans-serif; font-size: 12px; color: #222; margin: 0; padding: 20px; }
  .header { display: flex; align-items: center; gap: 16px; border-bottom: 3px solid #DA291C; padding-bottom: 16px; margin-bottom: 20px; }
  .header-logo { font-size: 40px; }
  .header-info h1 { color: #DA291C; font-size: 22px; margin: 0 0 4px; }
  .header-info p { color: #666; font-size: 12px; margin: 0; }
  .summary-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 24px; }
  .summary-card { background: #f9f9f9; border-radius: 8px; padding: 14px 16px; border-left: 4px solid #DA291C; }
  .summary-card .label { font-size: 11px; color: #666; margin-bottom: 4px; }
  .summary-card .value { font-size: 20px; font-weight: bold; color: #DA291C; }
  h2 { font-size: 14px; color: #fff; background: #DA291C; padding: 8px 12px; border-radius: 6px; margin: 0 0 10px; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
  th { background: #333; color: #fff; padding: 9px 10px; text-align: left; font-size: 11px; text-transform: uppercase; }
  td { padding: 8px 10px; border-bottom: 1px solid #eee; font-size: 12px; }
  tr:nth-child(even) td { background: #fafafa; }
  .badge { display:inline-block; padding:2px 8px; border-radius:20px; font-size:10px; font-weight:bold; }
  .badge-pending{background:#fff3cd;color:#856404} .badge-processing{background:#cfe2ff;color:#084298}
  .badge-ready{background:#d1ecf1;color:#0c5460} .badge-completed{background:#d4edda;color:#155724}
  .badge-cancelled{background:#f8d7da;color:#721c24}
  .footer { text-align:center; color:#999; font-size:10px; margin-top:24px; border-top:1px solid #eee; padding-top:12px; }
</style></head><body>
<div class="header">
  <div class="header-logo">&#x1F354;</div>
  <div class="header-info"><h1>Kantin Mas Wawan</h1><p>Laporan Periode: {$from} s/d {$to} &nbsp;|&nbsp; Dicetak: {$printDate}</p></div>
</div>
<div class="summary-grid">
  <div class="summary-card"><div class="label">Total Pendapatan</div><div class="value">{$fmtRevenue}</div></div>
  <div class="summary-card"><div class="label">Total Pesanan</div><div class="value">{$totalOrders}</div></div>
  <div class="summary-card"><div class="label">Rata-rata / Pesanan</div><div class="value">{$fmtAvg}</div></div>
</div>
<h2>Rekap per Kategori</h2>
<table><thead><tr><th>Kategori</th><th style="text-align:right">Terjual</th><th style="text-align:right">Pendapatan</th><th style="text-align:right">%</th></tr></thead><tbody>{$catRows}</tbody></table>
<h2>Riwayat Pesanan</h2>
<table><thead><tr><th>No. Pesanan</th><th>Nama</th><th style="text-align:right">Total</th><th>Status</th><th>Tanggal</th></tr></thead><tbody>{$orderRows}</tbody></table>
<div class="footer">Laporan otomatis dibuat oleh Sistem Kantin Mas Wawan &nbsp;|&nbsp; {$printDate}</div>
<script>window.onload=function(){window.print();}</script>
</body></html>
HTML;

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
