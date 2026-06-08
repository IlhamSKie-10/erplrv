<div style="font-family:inherit;">

    {{-- ── Header info pesanan ──────────────────────────────────── --}}
    <div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;">
        <div style="
            background-color:#3b82f6;color:#fff;
            padding:6px 14px;border-radius:9999px;
            font-size:0.9rem;font-weight:700;letter-spacing:0.03em;">
            {{ $record->order?->order_code ?? '-' }}
        </div>
        <span style="font-size:0.8rem;color:#6b7280;">
            Dibuat: {{ $record->order?->created_at?->format('d/m/Y H:i') ?? '-' }}
        </span>
    </div>

    {{-- ── Deskripsi produk ────────────────────────────────────── --}}
    <div style="margin-bottom:16px;">
        <p style="font-size:0.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;
                  letter-spacing:0.08em;margin-bottom:6px;">
            Kalimat Produk
        </p>
        <div style="
            padding:12px 14px;
            background-color:rgba(59,130,246,0.06);
            border-left:3px solid #3b82f6;
            border-radius:0 6px 6px 0;
            font-size:0.875rem;line-height:1.6;
            color:inherit;
            white-space:pre-wrap;">{{ $record->order?->product_sentence ?? '-' }}</div>
    </div>

    {{-- ── Deadline ─────────────────────────────────────────────── --}}
    @if($record->order?->deadline_at)
    <div style="margin-bottom:16px;">
        <p style="font-size:0.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;
                  letter-spacing:0.08em;margin-bottom:6px;">
            Deadline
        </p>
        <div style="display:inline-block;padding:4px 12px;border-radius:9999px;font-size:0.82rem;font-weight:600;
            background-color:#fef3c7;color:#92400e;">
            📅 {{ $record->order->deadline_at->format('d/m/Y') }}
        </div>
    </div>
    @endif

    <hr style="border:none;border-top:1px solid rgba(156,163,175,0.25);margin:16px 0;">

    {{-- ── Catatan Admin (CS) ───────────────────────────────────── --}}
    <div>
        <p style="font-size:0.75rem;font-weight:600;color:#9ca3af;text-transform:uppercase;
                  letter-spacing:0.08em;margin-bottom:8px;">
            📝 Catatan Admin / CS
        </p>

        @if($record->order?->admin_notes)
            <div style="
                padding:14px 16px;
                border-radius:8px;
                border:1px solid rgba(156,163,175,0.3);
                font-size:0.875rem;line-height:1.7;
                white-space:pre-wrap;
                background-color:rgba(243,244,246,0.5);
                color:inherit;">{{ $record->order->admin_notes }}</div>
        @else
            <div style="
                padding:14px 16px;
                border-radius:8px;
                border:1px dashed rgba(156,163,175,0.4);
                font-size:0.875rem;
                color:#9ca3af;
                text-align:center;">
                Tidak ada catatan dari CS.
            </div>
        @endif
    </div>
</div>
