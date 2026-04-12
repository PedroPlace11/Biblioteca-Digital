<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <title>Fatura #{{ $encomenda->id }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #111827;
            font-size: 11px;
            line-height: 1.28;
        }
        .page {
            padding: 18px 24px 30px;
        }
        .clear { clear: both; }
        .header {
            width: 100%;
            margin-bottom: 12px;
        }
        .brand-wrap {
            float: left;
            width: 52%;
        }
        .brand {
            font-size: 34px;
            font-weight: 700;
            line-height: 1;
            color: #0f172a;
            letter-spacing: -0.02em;
        }
        .brand-logo {
            max-width: 220px;
            max-height: 58px;
            width: auto;
            height: auto;
            display: block;
        }
        .brand-sub {
            margin-top: 4px;
            font-size: 10px;
            color: #6b7280;
        }
        .status-wrap {
            float: right;
            width: 46%;
        }
        .status-box {
            border: 1px solid #aeb9c5;
            background: #edf3f7;
            padding: 8px 10px;
        }
        .status-title {
            font-weight: 700;
            margin-bottom: 4px;
        }
        .status-row {
            margin: 2px 0;
            font-size: 10px;
        }
        .line {
            border-top: 1px solid #cfd6dd;
            margin: 10px 0;
        }
        .addresses {
            margin-top: 6px;
            margin-bottom: 10px;
        }
        .address-col {
            width: 49%;
            float: left;
        }
        .address-col.right {
            float: right;
        }
        .address-title {
            font-size: 10px;
            color: #374151;
            margin-bottom: 5px;
            font-weight: 700;
        }
        .address-text {
            font-size: 10px;
            color: #111827;
            line-height: 1.35;
        }
        .meta {
            border-top: 1px solid #cfd6dd;
            border-bottom: 1px solid #cfd6dd;
            padding: 7px 0;
            margin-bottom: 10px;
        }
        .meta-col {
            width: 33%;
            float: left;
            padding-right: 8px;
        }
        .meta-title {
            font-size: 10px;
            color: #374151;
            margin-bottom: 4px;
            font-weight: 700;
        }
        .meta-text {
            font-size: 10px;
        }
        .invoice-title {
            font-size: 11px;
            font-weight: 700;
            margin: 0 0 5px;
            color: #1f2937;
        }
        .items {
            width: 100%;
            border-collapse: collapse;
        }
        .items th {
            text-align: left;
            font-size: 9px;
            color: #374151;
            border-top: 1px solid #cfd6dd;
            border-bottom: 1px solid #cfd6dd;
            padding: 5px 6px;
            font-weight: 700;
        }
        .items td {
            border-bottom: 1px solid #e5e7eb;
            padding: 7px 6px;
            vertical-align: top;
            font-size: 10px;
        }
        .text-right { text-align: right; }
        .totals {
            width: 45%;
            margin-left: auto;
            margin-top: 8px;
            border-collapse: collapse;
        }
        .totals td {
            border: none;
            padding: 3px 0;
            font-size: 10px;
        }
        .totals-sep td {
            border-top: 1px solid #cfd6dd;
            padding-top: 6px;
        }
        .total-final {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
        }
        .footer {
            position: fixed;
            left: 24px;
            right: 24px;
            bottom: 10px;
            color: #6b7280;
            font-size: 10px;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    @php
        // Carrega logo local e converte para base64 para compatibilidade de renderização em PDF.
        $logoPath = public_path('images/logo/inovcorp.png');
        $logoSrc = file_exists($logoPath)
            ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
            : null;
    @endphp

    <div class="page">
        <div class="header">
            <div class="brand-wrap">
                {{-- Exibe logotipo da marca; fallback textual quando ficheiro não existir. --}}
                @if ($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Logotipo Inovcorp" class="brand-logo">
                @else
                    <div class="brand">biblioteca.pt</div>
                @endif
                <div class="brand-sub">Fatura eletrónica</div>
            </div>
            <div class="status-wrap">
                <div class="status-box">
                    {{-- Quadro com estado financeiro e metadados principais da fatura. --}}
                    <div class="status-title">Pago</div>
                    <div class="status-row">N.º de referência de pagamento: {{ $encomenda->stripe_payment_intent_id ?? ('ENCOMENDA-' . $encomenda->id) }}</div>
                    <div class="status-row">Vendido por Sistema Biblioteca</div>
                    <div class="line"></div>
                    <div class="status-row">Data da fatura: {{ now()->format('d/m/Y H:i') }}</div>
                    <div class="status-row">Número da fatura: FT-{{ str_pad((string) $encomenda->id, 6, '0', STR_PAD_LEFT) }}</div>
                    <div class="status-row">Total: {{ number_format($total, 2, ',', '.') }} &euro;</div>
                </div>
            </div>
            <div class="clear"></div>
        </div>

        <div class="addresses">
            <div class="address-col">
                {{-- Bloco de faturação com dados fiscais do cliente. --}}
                <div class="address-title">Direção de faturação</div>
                <div class="address-text">
                    <div><strong>{{ $encomenda->fatura_nome ?: ($encomenda->user?->name ?? 'Cliente') }}</strong></div>
                    <div>NIF: {{ $encomenda->fatura_com_nif ? ($encomenda->fatura_nif ?? '-') : '-' }}</div>
                    <div>{{ $encomenda->morada_linha_1 }}</div>
                    @if (!empty($encomenda->morada_linha_2))
                        <div>{{ $encomenda->morada_linha_2 }}</div>
                    @endif
                    <div>{{ $encomenda->codigo_postal }} {{ $encomenda->cidade }} ({{ $encomenda->pais }})</div>
                </div>
            </div>
            <div class="address-col right">
                {{-- Bloco de envio usado para entrega física da encomenda. --}}
                <div class="address-title">Direção de envio</div>
                <div class="address-text">
                    <div><strong>{{ $encomenda->nome_destinatario }}</strong></div>
                    <div>{{ $encomenda->morada_linha_1 }}</div>
                    @if (!empty($encomenda->morada_linha_2))
                        <div>{{ $encomenda->morada_linha_2 }}</div>
                    @endif
                    <div>{{ $encomenda->codigo_postal }} {{ $encomenda->cidade }} ({{ $encomenda->pais }})</div>
                    <div>Telemóvel: {{ $encomenda->telemovel_destinatario }}</div>
                </div>
            </div>
            <div class="clear"></div>
        </div>

        <div class="meta">
            <div class="meta-col">
                {{-- Metadados do pedido associado à fatura. --}}
                <div class="meta-title">Informação do pedido</div>
                <div class="meta-text">Data do pedido: {{ $encomenda->created_at?->format('d/m/Y H:i') }}</div>
                <div class="meta-text">Número do pedido: #{{ $encomenda->id }}</div>
            </div>
            <div class="meta-col">
                <div class="meta-title">Cliente</div>
                <div class="meta-text">{{ $encomenda->user?->name ?? '-' }}</div>
                <div class="meta-text">{{ $encomenda->user?->email ?? '-' }}</div>
            </div>
            <div class="meta-col">
                <div class="meta-title">Vendido por</div>
                <div class="meta-text">Sistema Biblioteca</div>
                <div class="meta-text">Portugal</div>
            </div>
            <div class="clear"></div>
        </div>

        <p class="invoice-title">Detalhes da fatura</p>
        <table class="items">
                <thead>
                    <tr>
                        <th>Descrição</th>
                        <th class="text-right">Cant.</th>
                        <th class="text-right">P. Unitário</th>
                        <th class="text-right">IVA %</th>
                        <th class="text-right">IVA incluído</th>
                        <th class="text-right">Preço total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($encomenda->itens as $item)
                        @php
                            // Decompõe subtotal em base tributável e componente de IVA a 6%.
                            $itemSubtotal = (float) $item->subtotal;
                            $itemSemIva = $itemSubtotal / 1.06;
                            $itemIva = $itemSubtotal - $itemSemIva;
                        @endphp
                        <tr>
                            <td>{{ $item->livro_nome }}</td>
                            <td class="text-right">{{ (int) $item->quantidade }}</td>
                            <td class="text-right">{{ number_format((float) $item->preco_unitario, 2, ',', '.') }} &euro;</td>
                            <td class="text-right">6%</td>
                            <td class="text-right">{{ number_format($itemIva, 2, ',', '.') }} &euro;</td>
                            <td class="text-right">{{ number_format($itemSubtotal, 2, ',', '.') }} &euro;</td>
                        </tr>
                    @endforeach

                    <tr>
                        {{-- Linha de portes com IVA a 0% no documento fiscal atual. --}}
                        <td>Envio</td>
                        <td class="text-right">1</td>
                        <td class="text-right">{{ number_format($portes, 2, ',', '.') }} &euro;</td>
                        <td class="text-right">0%</td>
                        <td class="text-right">0,00 &euro;</td>
                        <td class="text-right">{{ number_format($portes, 2, ',', '.') }} &euro;</td>
                    </tr>

                    @if ($descontoValor > 0)
                        {{-- Linha opcional de desconto promocional aplicado à encomenda. --}}
                        <tr>
                            <td>Desconto promocional</td>
                            <td class="text-right">1</td>
                            <td class="text-right">-{{ number_format($descontoValor, 2, ',', '.') }} &euro;</td>
                            <td class="text-right">0%</td>
                            <td class="text-right">0,00 &euro;</td>
                            <td class="text-right">-{{ number_format($descontoValor, 2, ',', '.') }} &euro;</td>
                        </tr>
                    @endif
                </tbody>
        </table>

        <table class="totals">
                <tr class="totals-sep">
                    {{-- Resumo final com base, imposto, portes e total liquidado. --}}
                    <td>Valor sem IVA</td>
                    <td class="text-right">{{ number_format($valorSemIva, 2, ',', '.') }} &euro;</td>
                </tr>
                <tr>
                    <td>IVA (6%)</td>
                    <td class="text-right">{{ number_format($valorIva, 2, ',', '.') }} &euro;</td>
                </tr>
                <tr>
                    <td>Portes</td>
                    <td class="text-right">{{ number_format($portes, 2, ',', '.') }} &euro;</td>
                </tr>
                @if ($descontoValor > 0)
                    <tr>
                        <td>Desconto</td>
                        <td class="text-right">-{{ number_format($descontoValor, 2, ',', '.') }} &euro;</td>
                    </tr>
                @endif
                <tr>
                    <td class="total-final">Total</td>
                    <td class="text-right total-final">{{ number_format($total, 2, ',', '.') }} &euro;</td>
                </tr>
        </table>

        <div class="footer">
            Fatura gerada automaticamente por Sistema Biblioteca | Encomenda #{{ $encomenda->id }} | Página 1 de 1
        </div>
    </div>
</body>
</html>
