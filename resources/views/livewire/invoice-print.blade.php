<div class="space-y-6">
    @if($invoice)
    <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-8 mx-auto" id="invoice-print" style="max-width: 100%;">
        @if($invoice->status === 'paid')
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 -rotate-30 text-[10rem] text-emerald-500/10 border-8 border-emerald-500/10 p-8 rounded-2xl pointer-events-none select-none font-bold">LUNAS</div>
        @endif

        <div class="flex justify-between items-start border-b border-[var(--color-border)] pb-6 mb-8">
            <div>
                @if($invoice->company?->logo_path)
                    <img src="{{ asset('storage/'.$invoice->company->logo_path) }}" class="h-16 mb-3 rounded">
                @endif
                <h2 class="text-2xl font-bold text-[var(--text-primary)]">{{ $invoice->company?->name ?? 'N/A' }}</h2>
                <p class="text-sm text-[var(--text-tertiary)]">
                    {{ $invoice->company?->address }}<br>
                    Email: {{ $invoice->company?->email }} | Telp: {{ $invoice->company?->phone }}
                </p>
            </div>
            <div class="text-right">
                <h1 class="text-4xl font-light text-[var(--text-tertiary)] tracking-widest">{{ $invoice->status === 'paid' ? 'BUKTI BAYAR' : 'INVOICE' }}</h1>
                <h3 class="text-lg font-bold mt-2 text-[var(--text-primary)]">{{ $invoice->inv_number }}</h3>
                <p class="text-sm text-[var(--text-tertiary)]">Tanggal: {{ $invoice->date_issue->format('d F Y') }}</p>
                @if($invoice->status !== 'paid')
                    <p class="text-sm text-[var(--red-600)]">Jatuh Tempo: {{ $invoice->date_due?->format('d F Y') ?? '-' }}</p>
                @endif
            </div>
        </div>

        <div class="mb-8">
            <p class="text-xs text-[var(--text-tertiary)] uppercase font-bold mb-1">Kepada Yth:</p>
            <h4 class="font-bold text-[var(--text-primary)]">{{ $invoice->client_name }}</h4>
            <p class="text-sm text-[var(--text-tertiary)]">{{ $invoice->client_address }}</p>
        </div>

        <table class="w-full border-collapse mb-8">
            <thead>
                <tr class="bg-[#343a40] text-white">
                    <th class="p-2 text-left text-sm">Deskripsi Layanan / Produk</th>
                    <th class="p-2 text-center text-sm w-24">Qty</th>
                    <th class="p-2 text-right text-sm w-40">Harga Satuan</th>
                    <th class="p-2 text-right text-sm w-40">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr class="border-b border-[var(--color-border)]">
                    <td class="p-2 text-sm text-[var(--text-primary)]">{{ $item->description }}</td>
                    <td class="p-2 text-center text-sm text-[var(--text-primary)]">{{ $item->qty }}</td>
                    <td class="p-2 text-right text-sm text-[var(--text-primary)]">Rp {{ number_format((float)$item->price, 0, ',', '.') }}</td>
                    <td class="p-2 text-right text-sm text-[var(--text-primary)]">Rp {{ number_format((float)$item->total, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t-2 border-[var(--text-primary)]">
                    <td colspan="3" class="p-2 text-right text-sm font-bold">Subtotal</td>
                    <td class="p-2 text-right text-sm font-bold">Rp {{ number_format((float)($invoice->grand_total - $invoice->tax_amount), 0, ',', '.') }}</td>
                </tr>
                @if($invoice->tax_rate > 0)
                <tr>
                    <td colspan="3" class="p-2 text-right text-sm">Pajak ({{ $invoice->tax_rate }}%)</td>
                    <td class="p-2 text-right text-sm">Rp {{ number_format((float)$invoice->tax_amount, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="bg-[var(--color-bg)]">
                    <td colspan="3" class="p-2 text-right text-lg font-bold">TOTAL TAGIHAN</td>
                    <td class="p-2 text-right text-lg font-bold">Rp {{ number_format((float)$invoice->grand_total, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        @if($payments->count() > 0)
        <div class="mb-8">
            <h4 class="font-bold border-b border-[var(--color-border)] pb-2 mb-3 text-[var(--text-primary)]">Riwayat Pembayaran</h4>
            <table class="text-sm border border-[var(--color-border)] w-auto">
                <thead class="bg-[var(--color-bg)]">
                    <tr><th class="px-3 py-1 text-left">Tanggal</th><th class="px-3 py-1 text-left">Keterangan</th><th class="px-3 py-1 text-right">Jumlah</th></tr>
                </thead>
                <tbody>
                    @foreach($payments as $pay)
                    <tr class="border-t border-[var(--color-border)]">
                        <td class="px-3 py-1">{{ $pay->payment_date->format('d M Y') }}</td>
                        <td class="px-3 py-1">{{ $pay->note }}</td>
                        <td class="px-3 py-1 text-right">Rp {{ number_format((float)$pay->amount, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="flex justify-end mt-8 mb-12">
            <div class="w-72">
                <table class="w-full text-sm border border-[var(--color-border)]">
                    <tr><td class="p-2 font-bold">Total Tagihan</td><td class="p-2 text-right">Rp {{ number_format((float)$invoice->grand_total, 0, ',', '.') }}</td></tr>
                    <tr class="text-[var(--emerald-600)]"><td class="p-2">Sudah Dibayar</td><td class="p-2 text-right">- Rp {{ number_format((float)$invoice->total_paid, 0, ',', '.') }}</td></tr>
                    <tr class="bg-[var(--color-bg)] border-t-2 border-[var(--text-primary)]">
                        <td class="p-2 font-bold text-lg text-[var(--red-600)]">SISA TAGIHAN</td>
                        <td class="p-2 text-right font-bold text-lg text-[var(--red-600)]">Rp {{ number_format((float)$invoice->remaining, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @if($invoice->status !== 'paid')
        <div class="p-4 bg-[var(--color-bg)] border-l-4 border-[var(--indigo-600)] rounded mb-8">
            <h4 class="font-bold text-[var(--text-primary)]">Metode Pembayaran:</h4>
            <p class="text-xs text-[var(--text-tertiary)] mb-1">Silakan transfer ke:</p>
            <ul class="text-sm font-bold text-[var(--text-primary)]">
                <li>{{ $invoice->company?->bank_name }}</li>
                <li>No. Rek: {{ $invoice->company?->acc_number }}</li>
                <li>A.N: {{ $invoice->company?->acc_name }}</li>
            </ul>
            <p class="text-xs text-[var(--text-tertiary)] mt-3">* Cantumkan <strong>No. Invoice</strong> pada berita transfer.<br>* Konfirmasi via WhatsApp: <strong>{{ $invoice->company?->phone }}</strong></p>
        </div>
        @else
        <div class="p-4 bg-[var(--emerald-50)] border-l-4 border-[var(--emerald-600)] rounded mb-8 text-[var(--emerald-700)]">
            <h4 class="font-bold">Terima Kasih!</h4>
            <p class="text-sm">Pembayaran telah kami terima lunas. Dokumen ini sah sebagai bukti pembayaran.</p>
        </div>
        @endif

        <div class="flex justify-between mt-12">
            <div></div>
            <div class="text-center">
                <p class="mb-4 text-sm text-[var(--text-tertiary)]">Hormat Kami,</p>
                @if($invoice->company?->signature_path)
                    <img src="{{ asset('storage/'.$invoice->company->signature_path) }}" class="h-24 mx-auto mb-2">
                @else
                    <div class="h-24"></div>
                @endif
                <p class="font-bold border-t border-[var(--color-border)] inline-block pt-2 px-8 text-sm text-[var(--text-primary)]">( {{ $invoice->company?->pic_name ?? $invoice->company?->name ?? '' }} )</p>
            </div>
        </div>
    </div>

    <div class="flex justify-center gap-3 no-print">
        <button onclick="window.print()" class="btn-primary">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak Dokumen
        </button>
        <a href="{{ route('invoices') }}" class="btn-secondary">Kembali</a>
    </div>
    @endif
</div>
