<?php

namespace App\Exports;

use App\Models\Order;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class OrdersExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle
{
    protected $filters;
    
    public function __construct($filters = [])
    {
        $this->filters = $filters;
    }

    /**
     * Query orders based on filters
     */
    public function collection()
    {
        $query = Order::with(['user', 'items.decoration'])
            ->orderBy('created_at', 'desc');

        // Apply filters (same as index method)
        if (isset($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        if (isset($this->filters['user_id'])) {
            $query->where('user_id', $this->filters['user_id']);
        }

        if (isset($this->filters['search'])) {
            $query->where(function($q) use ($query) {
                $q->where('order_number', 'like', '%' . $this->filters['search'] . '%')
                  ->orWhereHas('user', function($userQuery) {
                      $userQuery->where('first_name', 'like', '%' . $this->filters['search'] . '%')
                                ->orWhere('last_name', 'like', '%' . $this->filters['search'] . '%')
                                ->orWhere('email', 'like', '%' . $this->filters['search'] . '%');
                  });
            });
        }

        if (isset($this->filters['start_date'])) {
            $query->whereDate('created_at', '>=', $this->filters['start_date']);
        }

        if (isset($this->filters['end_date'])) {
            $query->whereDate('created_at', '<=', $this->filters['end_date']);
        }

        return $query->get();
    }

    /**
     * Define column headings
     */
    public function headings(): array
    {
        return [
            'Order Number',
            'Order Date',
            'Order Time',
            'Customer Name',
            'Customer Email',
            'Customer Phone',
            'Items Count',
            'Decoration(s)',
            'Subtotal',
            'Discount',
            'Total Amount',
            'Status',
            'Payment Method',
            'Snap Token',
            'Notes',
        ];
    }

    /**
     * Map data to columns
     */
    public function map($order): array
    {
        // Get customer name
        $customerName = $order->user 
            ? ($order->user->first_name . ' ' . $order->user->last_name) 
            : 'N/A';
        
        // Get customer email
        $customerEmail = $order->user ? $order->user->email : 'N/A';
        
        // Get customer phone
        $customerPhone = $order->user ? ($order->user->phone ?? 'N/A') : 'N/A';
        
        // Get decoration names
        $decorations = $order->items->map(function($item) {
            return $item->decoration ? $item->decoration->name : 'N/A';
        })->implode(', ');
        
        // Format date and time
        $orderDate = $order->created_at->format('d/m/Y');
        $orderTime = $order->created_at->format('H:i');
        
        // Format currency
        $subtotal = 'Rp ' . number_format($order->subtotal, 0, ',', '.');
        $discount = 'Rp ' . number_format($order->discount ?? 0, 0, ',', '.');
        $total = 'Rp ' . number_format($order->total_amount, 0, ',', '.');
        
        // Status label
        $statusLabel = $this->getStatusLabel($order->status);
        
        return [
            $order->order_number,
            $orderDate,
            $orderTime,
            $customerName,
            $customerEmail,
            $customerPhone,
            $order->items->count(),
            $decorations,
            $subtotal,
            $discount,
            $total,
            $statusLabel,
            $order->payment_method ?? 'N/A',
            $order->snap_token ?? 'N/A',
            $order->notes ?? '-',
        ];
    }

    /**
     * Get status label
     */
    private function getStatusLabel($status)
    {
        $labels = [
            'pending' => 'Pending',
            'paid' => 'Paid',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'refunded' => 'Refunded',
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Apply styles to worksheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style for header row
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E91E8C'], // Pink color
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    /**
     * Define column widths
     */
    public function columnWidths(): array
    {
        return [
            'A' => 25, // Order Number
            'B' => 12, // Order Date
            'C' => 10, // Order Time
            'D' => 25, // Customer Name
            'E' => 30, // Customer Email
            'F' => 15, // Customer Phone
            'G' => 12, // Items Count
            'H' => 40, // Decoration(s)
            'I' => 18, // Subtotal
            'J' => 18, // Discount
            'K' => 18, // Total Amount
            'L' => 12, // Status
            'M' => 15, // Payment Method
            'N' => 30, // Snap Token
            'O' => 30, // Notes
        ];
    }

    /**
     * Set sheet title
     */
    public function title(): string
    {
        return 'Orders Report';
    }
}
