@extends('layouts.app')

@section('title', 'Detail Laporan')

@section('content')
    <section class="page-section">
        <div class="container">
            <div class="breadcrumb"><a href="{{ route('reports.index') }}">Laporan</a><span>/</span><span>#{{ str_pad($report->id, 5, '0', STR_PAD_LEFT) }}</span></div>
            <div class="detail-heading">
                <div><div class="report-meta"><span class="badge {{ $report->status->badgeClass() }}">{{ $report->status->label() }}</span><span>Dibuat {{ $report->created_at->format('d M Y, H:i') }}</span></div><h1>{{ $report->title }}</h1><p class="location-line">⌖ {{ $report->address }}</p></div>
                <div class="detail-actions">@can('update', $report)<a href="{{ route('reports.edit', $report) }}" class="button button-ghost">Edit</a>@endcan @can('delete', $report)<form method="POST" action="{{ route('reports.destroy', $report) }}" onsubmit="return confirm('Hapus laporan ini?')">@csrf @method('DELETE')<button class="button button-danger" type="submit">Hapus</button></form>@endcan</div>
            </div>

            <div class="detail-grid">
                <div class="detail-main">
                    <img class="report-photo" src="{{ Storage::url($report->photo_path) }}" alt="Foto bukti {{ $report->title }}">
                    <article class="panel prose-panel"><h2>Deskripsi laporan</h2><p>{{ $report->description }}</p><div class="tag-row">@foreach($report->categories as $category)<span>{{ $category->name }}</span>@endforeach</div></article>
                    @if($report->latitude && $report->longitude)
                        <article class="panel map-panel"><div class="panel-heading"><div><h2>Titik lokasi laporan</h2><p>Koordinat {{ $report->latitude }}, {{ $report->longitude }}</p></div></div><div class="location-map location-map-display" data-location-display data-latitude="{{ $report->latitude }}" data-longitude="{{ $report->longitude }}" aria-label="Peta lokasi laporan"></div></article>
                    @endif
                    @if($report->resolution_photo_path)
                        <article class="panel resolution-panel"><div class="panel-heading"><div><span class="eyebrow">Tiket ditutup</span><h2>Bukti penyelesaian</h2><p>Dokumentasi hasil penanganan oleh petugas.</p></div><span class="badge badge-success">Selesai</span></div><img class="resolution-photo" src="{{ Storage::url($report->resolution_photo_path) }}" alt="Foto bukti penyelesaian {{ $report->title }}"></article>
                    @endif
                    <article class="panel"><div class="panel-heading"><div><h2>Progres penanganan</h2><p>Status diperbarui oleh petugas pada setiap tahap.</p></div></div>
                        <ol class="timeline">
                            <li class="done"><span></span><div><strong>Laporan diajukan</strong><small>{{ $report->created_at->format('d M Y, H:i') }}</small></div></li>
                            <li @class(['done' => $report->verified_at || in_array($report->status, [\App\Enums\ReportStatus::Diproses, \App\Enums\ReportStatus::Selesai])])><span></span><div><strong>Diverifikasi petugas</strong><small>{{ $report->verified_at?->format('d M Y, H:i') ?? 'Menunggu verifikasi' }}</small></div></li>
                            <li @class(['done' => $report->processed_at || $report->status === \App\Enums\ReportStatus::Selesai])><span></span><div><strong>Sedang diproses</strong><small>{{ $report->processed_at?->format('d M Y, H:i') ?? 'Belum dimulai' }}</small></div></li>
                            <li @class(['done' => $report->resolved_at])><span></span><div><strong>Penanganan selesai</strong><small>{{ $report->resolved_at?->format('d M Y, H:i') ?? 'Belum selesai' }}</small></div></li>
                        </ol>
                        @if($report->status === \App\Enums\ReportStatus::Ditolak)<div class="rejection-note"><strong>Laporan ditolak</strong><p>{{ $report->officer_note }}</p><small>{{ $report->rejected_at?->format('d M Y, H:i') }}</small></div>@elseif($report->officer_note)<div class="officer-note"><strong>Catatan petugas</strong><p>{{ $report->officer_note }}</p></div>@endif
                    </article>
                </div>

                <aside class="detail-sidebar">
                    <article class="panel info-card"><h2>Informasi laporan</h2><dl><div><dt>ID laporan</dt><dd>#{{ str_pad($report->id, 5, '0', STR_PAD_LEFT) }}</dd></div><div><dt>Pelapor</dt><dd>{{ $report->reporter->name }}</dd></div><div><dt>Petugas</dt><dd>{{ $report->officer?->name ?? 'Belum ditugaskan' }}</dd></div><div><dt>Status</dt><dd><span class="badge {{ $report->status->badgeClass() }}">{{ $report->status->label() }}</span></dd></div></dl></article>

                    @can('updateStatus', $report)
                        <article class="panel action-card">
                            <h2>Tindak lanjut laporan</h2>
                            @if(count($nextStatuses))
                                <p>Pilih tahap berikutnya sesuai hasil pemeriksaan.</p>
                                <form method="POST" action="{{ route('reports.status.update', $report) }}" enctype="multipart/form-data" class="form-stack compact-form">
                                    @csrf
                                    @method('PATCH')
                                    <label class="field"><span>Status berikutnya</span><select name="status" required>@foreach($nextStatuses as $status)<option value="{{ $status->value }}">{{ $status->label() }}</option>@endforeach</select></label>
                                    <label class="field"><span>Catatan petugas</span><textarea name="officer_note" rows="4" maxlength="1000" placeholder="Wajib diisi jika laporan ditolak">{{ old('officer_note', $report->officer_note) }}</textarea></label>
                                    @if(collect($nextStatuses)->contains(\App\Enums\ReportStatus::Selesai))
                                        <label class="field"><span>Foto bukti penyelesaian</span><span class="upload-box"><b>✓</b><span><strong>Unggah hasil penanganan</strong><small>Wajib untuk menutup tiket · maksimal 2 MB · 8 megapiksel</small></span><input type="file" name="resolution_photo" accept="image/jpeg,image/png,image/webp" required></span></label>
                                    @endif
                                    <button type="submit" class="button button-primary button-block">Perbarui status</button>
                                </form>
                            @else
                                <div class="terminal-status">✓ Tidak ada tindak lanjut berikutnya untuk status ini.</div>
                            @endif
                        </article>
                    @endcan
                </aside>
            </div>
        </div>
    </section>
@endsection
