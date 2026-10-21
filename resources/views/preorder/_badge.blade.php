@php
    $warnaBadge = [
        'baru' => ['#fef3c7', '#b45309'],
        'diproses' => ['#dbeafe', '#1d4ed8'],
        'siap' => ['#dcfce7', '#15803d'],
        'selesai' => ['#e5e7eb', '#374151'],
        'dibatalkan' => ['#fee2e2', '#b91c1c'],
    ][$status] ?? ['#e5e7eb', '#374151'];
@endphp
<span class="badge" style="background: {{ $warnaBadge[0] }}; color: {{ $warnaBadge[1] }}; font-weight: 700; font-size: 11px; padding: 6px 10px;">{{ \App\Models\PreOrder::LABEL_STATUS[$status] ?? ucfirst($status) }}</span>
