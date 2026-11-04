<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoja de Despacho #TR-{{ str_pad($transfer->id, 5, '0', STR_PAD_LEFT) }} - A&M Importaciones</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; margin: 0; padding: 24px; color: #1e293b; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #e2e8f0; padding-bottom: 16px; margin-bottom: 20px; }
        .logo { font-size: 20px; font-weight: 900; letter-spacing: -0.5px; }
        .logo span { color: #2563eb; }
        .meta-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 24px; font-size: 13px; }
        .meta-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; }
        .meta-title { font-size: 11px; text-transform: uppercase; font-weight: bold; color: #64748b; margin-bottom: 4px; }
        .meta-value { font-weight: 700; color: #0f172a; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; font-size: 13px; }
        th { background: #f1f5f9; text-align: left; padding: 10px 12px; font-weight: 700; border-bottom: 2px solid #cbd5e1; font-size: 12px; text-transform: uppercase; color: #475569; }
        td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; }
        .check-box { width: 18px; height: 18px; border: 2px solid #94a3b8; border-radius: 4px; display: inline-block; vertical-align: middle; }
        .signatures { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 50px; }
        .sig-box { border-top: 1px dashed #94a3b8; padding-top: 8px; text-align: center; font-size: 12px; color: #64748b; font-weight: 600; }
        .no-print { margin-bottom: 20px; }
        .btn-print { background: #2563eb; color: white; border: none; padding: 10px 18px; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">🖨️ Imprimir Hoja de Picking / Despacho</button>
        <button onclick="window.close()" style="margin-left: 8px; padding: 10px 16px; border-radius: 8px; border: 1px solid #cbd5e1; background: white; cursor: pointer; font-weight: 600;">Cerrar</button>
    </div>

    <div class="header">
        <div>
            <div class="logo">A&M <span>IMPORTACIONES</span></div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Hoja de Picking y Despacho de Mercadería</div>
        </div>
        <div style="text-align: right;">
            <div style="font-size: 18px; font-weight: 900; color: #2563eb;">#TR-{{ str_pad($transfer->id, 5, '0', STR_PAD_LEFT) }}</div>
            <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Fecha: {{ $transfer->created_at->format('d/m/Y h:i A') }}</div>
        </div>
    </div>

    <div class="meta-grid">
        <div class="meta-card">
            <div class="meta-title">Origen (Despacha)</div>
            <div class="meta-value">{{ $transfer->fromLocation->name }}</div>
            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                Despachado por: {{ $transfer->dispatchedBy->name ?? 'En proceso de alistamiento' }}
            </div>
        </div>
        <div class="meta-card">
            <div class="meta-title">Destino (Recibe)</div>
            <div class="meta-value">{{ $transfer->toLocation->name }}</div>
            <div style="font-size: 11px; color: #64748b; margin-top: 2px;">
                Solicitado por: {{ $transfer->requestedBy->name ?? 'Traslado Directo' }}
            </div>
        </div>
    </div>

    @if($transfer->notes)
    <div style="background: #fefce8; border: 1px solid #fef08a; padding: 10px 12px; border-radius: 8px; font-size: 12px; color: #854d0e; margin-bottom: 20px;">
        <strong>Observaciones / Notas del Pedido:</strong> {{ $transfer->notes }}
    </div>
    @endif

    <table>
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;">✓</th>
                <th style="width: 120px;">SKU</th>
                <th>Descripción del Producto</th>
                <th>Categoría</th>
                <th style="width: 90px; text-align: center;">Cant. Pedida</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transfer->items as $item)
            <tr>
                <td style="text-align: center;"><span class="check-box"></span></td>
                <td style="font-family: monospace; font-weight: bold; color: #2563eb;">{{ $item->product->sku ?? '-' }}</td>
                <td style="font-weight: 600;">{{ $item->product->name ?? 'Producto' }}</td>
                <td style="color: #64748b; font-size: 12px;">{{ $item->product->category->name ?? 'General' }}</td>
                <td style="text-align: center; font-weight: 900; font-size: 14px;">{{ $item->quantity }} und</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="font-size: 12px; color: #64748b; margin-bottom: 30px;">
        Total de artículos: <strong>{{ $transfer->items->count() }}</strong> &bull; Total de unidades: <strong>{{ $transfer->items->sum('quantity') }} und</strong>
    </div>

    <div class="signatures">
        <div class="sig-box">
            Alistado y Despachado por (Almacén Central)
        </div>
        <div class="sig-box">
            Revisado y Conforme (Tienda Destino)
        </div>
    </div>
</body>
</html>

