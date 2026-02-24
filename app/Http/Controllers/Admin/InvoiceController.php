<?php

namespace App\Http\Controllers\Admin;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('q');
        $rawStatus = $request->query('status');
        $status = is_string($rawStatus) && $rawStatus !== ''
            ? Order::normalizeStatus($rawStatus)
            : null;
        $from = $request->query('from');
        $to = $request->query('to');
        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50], true) ? $perPage : 10;

        $invoices = $this->filteredInvoicesQuery($request)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $tableStats = [
            [
                'value' => number_format(Order::query()->count()),
                'label' => __('All Invoices'),
                'icon' => 'bx-receipt',
            ],
            [
                'value' => number_format(Order::query()->where('status', Order::STATUS_APPROVED)->count()),
                'label' => __('Approved Invoices'),
                'icon' => 'bx-check-circle',
            ],
            [
                'value' => number_format(Order::query()->where('status', Order::STATUS_PENDING)->count()),
                'label' => __('Pending Invoices'),
                'icon' => 'bx-time-five',
            ],
            [
                'value' => money((float) Order::query()->sum('total')),
                'label' => __('Total Invoiced'),
                'icon' => 'bx-wallet',
            ],
        ];

        return view('admin.invoices.index', compact('invoices', 'search', 'status', 'from', 'to', 'perPage', 'tableStats'));
    }

    public function export(Request $request, string $format): Response|StreamedResponse
    {
        $normalizedFormat = Str::lower(trim($format));
        if (!in_array($normalizedFormat, ['csv', 'excel', 'pdf'], true)) {
            abort(404);
        }

        $invoices = $this->filteredInvoicesQuery($request)
            ->latest()
            ->get();

        $exportedAt = now();
        $fileSuffix = $exportedAt->format('Ymd_His');
        $baseName = "invoices_{$fileSuffix}";

        if ($normalizedFormat === 'csv') {
            return $this->streamInvoicesCsv($invoices, "{$baseName}.csv");
        }

        if ($normalizedFormat === 'excel') {
            return response()
                ->view('admin.invoices.exports.excel', [
                    'invoices' => $invoices,
                    'exportedAt' => $exportedAt,
                ])
                ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
                ->header('Content-Disposition', 'attachment; filename="'.$baseName.'.xls"');
        }

        return Pdf::loadView('admin.invoices.exports.pdf', [
            'invoices' => $invoices,
            'exportedAt' => $exportedAt,
        ])->download("{$baseName}.pdf");
    }

    public function print(Order $order): View
    {
        $order->load(['customer', 'items.product', 'payments', 'paymentAllocations.payment']);

        $paidPayment = $order->paymentAllocations
            ->sortByDesc(fn ($allocation) => $allocation->allocated_at ?? $allocation->created_at)
            ->map(fn ($allocation) => $allocation->payment)
            ->filter(fn ($payment) => $payment && $payment->status === 'paid')
            ->first()
            ?? $order->payments->firstWhere('status', 'paid');

        return view('admin.invoices.print', compact('order', 'paidPayment'));
    }

    private function filteredInvoicesQuery(Request $request): Builder
    {
        $search = $request->query('q');
        $rawStatus = $request->query('status');
        $status = is_string($rawStatus) && $rawStatus !== ''
            ? Order::normalizeStatus($rawStatus)
            : null;
        $from = $request->query('from');
        $to = $request->query('to');

        return Order::query()
            ->with([
                'customer',
                'payments',
                'items:id,order_id,qty,price,base_price',
            ])
            ->withSum('paymentAllocations as paid_amount', 'amount')
            ->when($search, function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('order_no', 'like', '%' . $search . '%')
                        ->orWhereHas('customer', function ($customerQuery) use ($search): void {
                            $customerQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($from, fn ($query) => $query->whereDate('created_at', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('created_at', '<=', $to));
    }

    /**
     * @param \Illuminate\Support\Collection<int, \App\Models\Order> $invoices
     */
    private function streamInvoicesCsv($invoices, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($invoices): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                return;
            }

            // UTF-8 BOM for Arabic compatibility in Excel.
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, [
                __('Invoice'),
                __('Customer'),
                __('Amount'),
                __('Discount'),
                __('Status'),
                __('Date'),
            ]);

            foreach ($invoices as $order) {
                fputcsv($output, [
                    (string) $order->order_no,
                    (string) ($order->customer?->name ?? ''),
                    (float) $order->total,
                    (float) $order->items_discount_total,
                    __(ucfirst($order->normalized_status)),
                    (string) optional($order->created_at)->format('Y-m-d'),
                ]);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
