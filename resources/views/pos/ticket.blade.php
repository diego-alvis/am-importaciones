<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Venta #{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: monospace; }
        body { background: #f0f0f0; display: flex; justify-content: center; padding: 20px; }
        .ticket { width: 80mm; background: #fff; padding: 5mm; color: #000; font-size: 12px; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        .mb-2 { margin-bottom: 5px; }
        .mb-4 { margin-bottom: 15px; }
        .divider { border-top: 1px dashed #000; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 2px 0; vertical-align: top; }
        .text-right { text-align: right; }
        .totals-table td { padding-top: 5px; }
        @media print {
            body { background: none; padding: 0; display: block; }
            .ticket { width: 100%; padding: 0; box-shadow: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="no-print text-center mb-4">
            <button onclick="window.print()" style="padding: 10px; background: #000; color: #fff; border: none; cursor: pointer; border-radius: 5px; width: 100%;">Imprimir Ticket</button>
        </div>

        <div class="text-center mb-4">
            <h2 class="font-bold">A Y M IMPORTACIONES</h2>
            <p>Sede: {{ $sale->cashSession->stand->name ?? 'Principal' }}</p>
            <p>RUC: 20000000000</p>
            <p>Ticket: #{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</p>
            <p>Fecha: {{ $sale->created_at->format('d/m/Y H:i:s') }}</p>
            <p>Atendido por: {{ $sale->user->name }}</p>
        </div>

        <div class="divider"></div>

        <table>
            <thead>
                <tr>
                    <th style="width: 15%">Cant</th>
                    <th style="width: 55%">Producto</th>
                    <th class="text-right" style="width: 30%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td>
                        {{ $item->product->name }}
                        @if($item->device_model)
                        <br><small>Mod: {{ $item->device_model }}</small>
                        @endif
                    </td>
                    <td class="text-right">S/ {{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="divider"></div>

        <table class="totals-table font-bold">
            <tr>
                <td>TOTAL A PAGAR:</td>
                <td class="text-right">S/ {{ number_format($sale->total_amount, 2) }}</td>
            </tr>
        </table>

        <div class="divider"></div>

        <div class="mb-4">
            <p class="font-bold mb-2">MÉTODOS DE PAGO:</p>
            <table style="width: 100%">
                @foreach($sale->payments as $payment)
                <tr>
                    <td style="text-transform: uppercase;">- {{ $payment->payment_method }}</td>
                    <td class="text-right">S/ {{ number_format($payment->amount, 2) }}</td>
                </tr>
                @endforeach
            </table>
        </div>

        <div class="text-center mb-4">
            <p>¡Gracias por su compra!</p>
            <p>Conserve este ticket para cualquier reclamo o garantía.</p>
        </div>
        
        <div class="divider"></div>
        <div class="text-center">
            <p style="font-size: 10px;">Sistema POS desarrollado por Diego</p>
        </div>
    </div>
    
    <script>
        // Imprime automáticamente al cargar la ventana
        window.onload = function() { window.print(); }
    </script>
</body>
</html>