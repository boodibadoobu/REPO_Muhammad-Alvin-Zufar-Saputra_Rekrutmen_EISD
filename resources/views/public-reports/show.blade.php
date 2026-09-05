@extends('layouts.app')

@section('title', 'Detail Laporan Publik')

@section('content')
    <section class="page-section">
        <div class="container">
            <div class="breadcrumb"><a href="{{ route('public-reports.index') }}">Laporan publik</a><span>/</span><span>#{{ str_pad($report->id, 5, '0', STR_PAD_LEFT) }}</span></div>
            <div class="detail-heading">
                <div><div class="report-meta"><span class="badge {{ $report->status->badgeClass() }}">{{ $report->status->label() }}</span><span>Dibuat {{ $report->created_at->format('d M Y, H:i') }}</span></div><h1>{{ $report->title }}</h1><p class="location-line">⌖ {{ $report->address }}</p></div>
                <a href="{{ route('public-reports.index') }}" class="button button-ghost">← Kembali ke peta</a>
            </div>

            <div class="detail-grid">
                <div class="detail-main">
                    <img class="report-photo" src="{{ Storage::url($report->photo_path) }}" alt="Foto bukti {{ $report->title }}">
                    <article class="panel prose-panel"><h2>Deskripsi laporan</h2><p>{{ $report->description }}</p><div class="tag-row">@foreach($report->categories as $category)<span>{{ $category->name }}</span>@endforeach</div></article>

                    @if($report->latitude && $report->longitude)
                        <article class="panel map-panel"><div class="panel-heading"><div><h2>Titik lokasi laporan</h2><p>Lokasi yang dipilih pelapor saat laporan dikirim.</p></div></div><div class="location-map location-map-display" data-location-display data-latitude="{{ $report->latitude }}" data-longitude="{{ $report->longitude }}" aria-label="Peta lokasi laporan"></div></article>
                    @endif

                    @if($report->resolution_photo_path)
                        <article class="panel resolution-panel"><div class="panel-heading"><div><span class="eyebrow">Tiket ditutup</span><h2>Bukti penyelesaian</h2><p>Dokumentasi hasil penanganan oleh petugas.</p></div><span class="badge badge-success">Tuntas</span></div><img class="resolution-photo" src="{{ Storage::url($report->resolution_photo_path) }}" alt="Foto bukti penyelesaian {{ $report->title }}"></article>
                    @endif

                    <article class="panel">
                        <div class="panel-heading"><div><h2>Progres penanganan</h2><p>Riwayat status yang dapat dipantau secara terbuka.</p></div></div>
                        <ol class="timeline">
                            <li class="done"><span></span><div><strong>Laporan diajukan</strong><small>{{ $report->created_at->format('d M Y, H:i') }}</small></div></li>
                            <li class="done"><span></span><div><strong>Diverifikasi petugas</strong><small>{{ $report->verified_at?->format('d M Y, H:i') ?? 'Terverifikasi' }}</small></div></li>
                            <li @class(['done' => $report->processed_at || $report->status === \App\Enums\ReportStatus::Selesai])><span></span><div><strong>Sedang diproses</strong><small>{{ $report->processed_at?->format('d M Y, H:i') ?? 'Belum dimulai' }}</small></div></li>
                            <li @class(['done' => $report->resolved_at])><span></span><div><strong>Penanganan selesai</strong><small>{{ $report->resolved_at?->format('d M Y, H:i') ?? 'Belum selesai' }}</small></div></li>
                        </ol>
                        @if($report->officer_note)<div class="officer-note"><strong>Catatan penanganan</strong><p>{{ $report->officer_note }}</p></div>@endif
                    </article>
                </div>

                <aside class="detail-sidebar">
                    <article class="panel info-card">
                        <h2>Informasi publik</h2>
                        <dl>
                            <div><dt>ID laporan</dt><dd>#{{ str_pad($report->id, 5, '0', STR_PAD_LEFT) }}</dd></div>
                            <div><dt>Pelapor</dt><dd>Identitas dilindungi</dd></div>
                            <div><dt>Penanganan</dt><dd>{{ $report->officer ? 'Petugas LaporKita' : 'Menunggu petugas' }}</dd></div>
                            <div><dt>Status</dt><dd><span class="badge {{ $report->status->badgeClass() }}">{{ $report->status->label() }}</span></dd></div>
                            <div><dt>Diperbarui</dt><dd>{{ $report->updated_at->format('d M Y, H:i') }}</dd></div>
                        </dl>
                    </article>
                    <article class="info-box privacy-box"><strong>Privasi pelapor dijaga</strong><p>Nama, email, dan informasi akun warga tidak ditampilkan pada halaman publik.</p></article>
                </aside>
            </div>
        </div>
    </section>
@endsection
