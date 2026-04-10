<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Bon de commande #{{ $order->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; background: #fff; }

        .page { padding: 32px 36px; }

        /* Header */
        .header { display: flex; justify-content: space-between; align-items: flex-start; padding-bottom: 20px; border-bottom: 2px solid #e2e8f0; margin-bottom: 24px; }
        .header-left h1 { font-size: 22px; font-weight: 700; color: #0f172a; }
        .header-left p { font-size: 11px; color: #64748b; margin-top: 4px; }
        .header-right { text-align: right; }
        .order-id { font-size: 28px; font-weight: 800; color: #4f46e5; }
        .order-id-label { font-size: 10px; color: #94a3b8; letter-spacing: 0.05em; text-transform: uppercase; }

        /* Badge status */
        .status-badge { display: inline-block; padding: 4px 12px; border-radius: 999px; font-size: 11px; font-weight: 600; margin-top: 8px; }
        .status-draft    { background: #f1f5f9; color: #475569; }
        .status-pending  { background: #fffbeb; color: #b45309; }
        .status-approved { background: #ecfdf5; color: #065f46; }
        .status-rejected { background: #fef2f2; color: #991b1b; }

        /* Section */
        .section { margin-bottom: 22px; }
        .section-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; margin-bottom: 10px; padding-bottom: 4px; border-bottom: 1px solid #e2e8f0; }

        /* Info grid */
        .info-grid { display: table; width: 100%; }
        .info-row { display: table-row; }
        .info-label { display: table-cell; width: 35%; font-size: 11px; color: #64748b; padding: 4px 0; vertical-align: top; }
        .info-value { display: table-cell; font-size: 12px; font-weight: 500; color: #0f172a; padding: 4px 0; vertical-align: top; }

        /* Amount box */
        .amount-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; }
        .amount-box .label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .amount-box .value { font-size: 22px; font-weight: 800; color: #0f172a; margin-top: 2px; }

        /* Description */
        .description-box { background: #f8fafc; border-radius: 6px; padding: 10px 12px; font-size: 12px; line-height: 1.6; color: #334155; white-space: pre-wrap; }

        /* Circuit de validation */
        .timeline { position: relative; padding-left: 20px; }
        .timeline-item { position: relative; margin-bottom: 14px; }
        .timeline-item:last-child { margin-bottom: 0; }
        .timeline-dot { position: absolute; left: -20px; top: 2px; width: 14px; height: 14px; border-radius: 50%; border: 2px solid #e2e8f0; background: #fff; }
        .timeline-dot.done { background: #10b981; border-color: #10b981; }
        .timeline-dot.active { background: #4f46e5; border-color: #4f46e5; }
        .timeline-dot.rejected { background: #ef4444; border-color: #ef4444; }
        .timeline-line { position: absolute; left: -14px; top: 16px; width: 2px; height: calc(100% + 2px); background: #e2e8f0; }
        .timeline-item.done .timeline-line { background: #10b981; }
        .level-name { font-weight: 600; font-size: 12px; color: #0f172a; }
        .level-meta { font-size: 10px; color: #64748b; margin-top: 2px; }
        .level-validator { font-size: 11px; color: #4f46e5; margin-top: 3px; font-weight: 500; }
        .level-comment { font-size: 10px; color: #64748b; font-style: italic; margin-top: 3px; background: #f1f5f9; padding: 4px 8px; border-radius: 4px; }

        /* Historique */
        .log-item { padding: 8px 12px; border-radius: 6px; margin-bottom: 8px; }
        .log-item.approved { background: #ecfdf5; border-left: 3px solid #10b981; }
        .log-item.rejected { background: #fef2f2; border-left: 3px solid #ef4444; }
        .log-header { display: flex; justify-content: space-between; align-items: center; }
        .log-action { font-weight: 700; font-size: 11px; }
        .log-action.approved { color: #065f46; }
        .log-action.rejected { color: #991b1b; }
        .log-level { font-size: 10px; color: #64748b; }
        .log-by { font-size: 11px; color: #334155; margin-top: 2px; }
        .log-comment { font-size: 10px; font-style: italic; color: #64748b; margin-top: 4px; border-top: 1px solid rgba(0,0,0,0.08); padding-top: 4px; }
        .log-date { font-size: 10px; color: #94a3b8; }

        /* Attachments */
        .attachment-item { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 11px; }
        .attachment-item:last-child { border-bottom: none; }

        /* Footer */
        .footer { margin-top: 30px; padding-top: 14px; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .footer-left { font-size: 10px; color: #94a3b8; }
        .footer-right { font-size: 10px; color: #94a3b8; }

        /* Two columns */
        .cols { display: table; width: 100%; border-spacing: 16px 0; }
        .col { display: table-cell; vertical-align: top; }
        .col-left { width: 58%; }
        .col-right { width: 42%; }
    </style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div class="header-left">
            <h1>Bon de Commande</h1>
            <p>{{ $order->boutique?->name ?? 'Aucune boutique' }}@if($order->boutique) &nbsp;&mdash;&nbsp; {{ $order->boutique->code }}@endif</p>
            <div>
                @php
                    $statusLabels = ['draft' => 'Brouillon', 'pending' => 'En attente', 'approved' => 'Approuvée', 'rejected' => 'Refusée'];
                @endphp
                <span class="status-badge status-{{ $order->status }}">
                    {{ $statusLabels[$order->status] ?? $order->status }}
                </span>
            </div>
        </div>
        <div class="header-right">
            <div class="order-id-label">N° Commande</div>
            <div class="order-id">#{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }}</div>
            <div style="font-size:10px;color:#94a3b8;margin-top:4px;">
                Créée le {{ \Carbon\Carbon::parse($order->created_at)->locale('fr')->isoFormat('DD MMMM YYYY') }}
            </div>
        </div>
    </div>

    {{-- Montant + Infos générales --}}
    <div class="cols">
        <div class="col col-left">
            <div class="section">
                <div class="section-title">Détails de la commande</div>
                <div class="amount-box">
                    <div class="label">Montant total</div>
                    <div class="value">{{ number_format($order->amount, 0, ',', ' ') }} XOF</div>
                </div>
                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Demandeur</div>
                        <div class="info-value">{{ $order->user?->name ?? '—' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Boutique</div>
                        <div class="info-value">{{ $order->boutique?->name ?? '—' }}</div>
                    </div>
                    @if($order->submitted_at)
                    <div class="info-row">
                        <div class="info-label">Soumise le</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($order->submitted_at)->locale('fr')->isoFormat('DD MMM YYYY, HH:mm') }}</div>
                    </div>
                    @endif
                    <div class="info-row">
                        <div class="info-label">Statut</div>
                        <div class="info-value">{{ $statusLabels[$order->status] ?? $order->status }}</div>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Description</div>
                <div class="description-box">{{ $order->description }}</div>
            </div>

            @if($order->attachments && $order->attachments->count())
            <div class="section">
                <div class="section-title">Pièces jointes ({{ $order->attachments->count() }})</div>
                @foreach($order->attachments as $attachment)
                <div class="attachment-item">
                    <span>{{ $attachment->file_name }}</span>
                    <span style="color:#94a3b8;">
                        @if($attachment->file_size >= 1048576)
                            {{ round($attachment->file_size / 1048576, 1) }} MB
                        @else
                            {{ round($attachment->file_size / 1024) }} KB
                        @endif
                    </span>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <div class="col col-right">
            {{-- Circuit de validation --}}
            <div class="section">
                <div class="section-title">Circuit de validation</div>
                <div class="timeline">
                    @foreach($levels as $index => $level)
                        @php
                            $log = $order->validationLogs->firstWhere('validation_level_id', $level->id);
                            $isDone = $order->status === 'approved' ||
                                      ($order->status === 'pending' && $order->current_level_order > $level->order);
                            $isActive = $order->status === 'pending' && $order->current_level_order === $level->order;
                            $isRejected = $log && $log->action === 'rejected';
                            $dotClass = $isRejected ? 'rejected' : ($isDone ? 'done' : ($isActive ? 'active' : ''));
                        @endphp
                        <div class="timeline-item {{ $isDone ? 'done' : '' }}">
                            @if(!$loop->last)
                                <div class="timeline-line"></div>
                            @endif
                            <div class="timeline-dot {{ $dotClass }}"></div>
                            <div class="level-name">{{ $level->name }}</div>
                            @if($isActive)
                                <div class="level-meta">En attente de validation</div>
                            @elseif($isDone && !$isRejected)
                                <div class="level-meta" style="color:#10b981;">Validé</div>
                            @elseif($isRejected)
                                <div class="level-meta" style="color:#ef4444;">Refusé</div>
                            @else
                                <div class="level-meta">En attente</div>
                            @endif
                            @if($log)
                                <div class="level-validator">
                                    Par : {{ $log->user?->name ?? '—' }}
                                    &mdash; {{ \Carbon\Carbon::parse($log->created_at)->locale('fr')->isoFormat('DD MMM YYYY, HH:mm') }}
                                </div>
                                @if($log->comment)
                                    <div class="level-comment">"{{ $log->comment }}"</div>
                                @endif
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Historique des actions --}}
            @if($order->validationLogs && $order->validationLogs->count())
            <div class="section">
                <div class="section-title">Historique des validations</div>
                @foreach($order->validationLogs->sortBy('created_at') as $log)
                <div class="log-item {{ $log->action }}">
                    <div class="log-header">
                        <span class="log-action {{ $log->action }}">
                            {{ $log->action === 'approved' ? 'Approuvée' : 'Refusée' }}
                        </span>
                        <span class="log-level">{{ $log->validationLevel?->name }}</span>
                    </div>
                    <div class="log-by">Par {{ $log->user?->name ?? '—' }}</div>
                    <div class="log-date">{{ \Carbon\Carbon::parse($log->created_at)->locale('fr')->isoFormat('DD MMM YYYY [à] HH:mm') }}</div>
                    @if($log->comment)
                        <div class="log-comment">"{{ $log->comment }}"</div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Footer --}}
    <div class="footer">
        <div class="footer-left">
            Généré le {{ \Carbon\Carbon::now()->locale('fr')->isoFormat('DD MMMM YYYY [à] HH:mm') }}
        </div>
        <div class="footer-right">
            Commande #{{ str_pad($order->id, 5, '0', STR_PAD_LEFT) }} &mdash; {{ config('app.name') }}
        </div>
    </div>

</div>
</body>
</html>
