@extends('layouts.app')

@section('title', 'Laporan Publik')

@section('content')
    <section class="public-hero">
        <div class="container public-hero-grid">
            <div>
                <span class="eyebrow eyebrow-light">Transparansi untuk semua</span>
                <h1>Pantau laporan yang telah diverifikasi.</h1>
                <p>Informasi penanganan dapat diakses publik tanpa menampilkan identitas pelapor. Klik titik pada peta atau buka detail untuk melihat perkembangannya.</p>
            </div>
            <div class="public-summary">
                @foreach($statuses as $status)
                    <a href="{{ route('public-reports.index', ['status' => $status->value]) }}">
                        <span class="badge {{ $status->badgeClass() }}">{{ $status->label() }}</span>
                        <strong>{{ number_format($counts[$status->value]) }}</strong>
                        <small>laporan</small>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="page-section public-page">
        <div class="container">
            @if($mapReports->isNotEmpty())
                <article class="panel public-map-panel">
                    <div class="panel-heading"><div><h2>Peta laporan terverifikasi</h2><p>Menampilkan maksimal 200 titik yang sesuai dengan filter aktif.</p></div><span class="map-legend"><i></i> Titik laporan</span></div>
                    <div class="location-map public-report-map" data-public-map aria-label="Peta laporan publik"></div>
                    <script type="application/json" data-map-markers>@json($mapReports)</script>
                </article>
            @endif

            <div class="section-heading public-list-heading">
                <span class="eyebrow">Data terbuka</span>
                <h2>Daftar laporan</h2>
                <p>Hanya laporan berstatus diverifikasi, diproses, atau selesai yang ditampilkan.</p>
            </div>

            <form method="GET" action="{{ route('public-reports.index') }}" class="filter-bar">
                <label class="search-field"><span aria-hidden="true">⌕</span><input type="search" name="search" aria-label="Cari judul atau lokasi laporan" value="{{ request('search') }}" placeholder="Cari judul atau lokasi..."></label>
                <select name="status" aria-label="Filter status"><option value="">Semua status publik</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select>
                <select name="category" aria-label="Filter kategori"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select>
                <button type="submit" class="button button-dark">Terapkan</button>
                @if(request()->hasAny(['search', 'status', 'category']))<a href="{{ route('public-reports.index') }}" class="button button-ghost">Reset</a>@endif
            </form>

            <div class="public-report-grid">
                @forelse($reports as $report)
                    <article class="public-report-card">
                        <a href="{{ route('public-reports.show', $report) }}" class="public-report-photo">
                            <img src="{{ Storage::url($report->photo_path) }}" alt="Foto bukti {{ $report->title }}">
                            <span class="badge {{ $report->status->badgeClass() }}">{{ $report->status->label() }}</span>
                        </a>
                        <div class="public-report-body">
                            <div class="report-meta"><span>#{{ str_pad($report->id, 5, '0', STR_PAD_LEFT) }}</span><span>{{ $report->created_at->format('d M Y') }}</span>@if($report->resolution_photo_path)<span class="proof-label">✓ Bukti selesai</span>@endif</div>
                            <h2><a href="{{ route('public-reports.show', $report) }}">{{ $report->title }}</a></h2>
                            <p class="location-line">⌖ {{ Str::limit($report->address, 90) }}</p>
                            <p>{{ Str::limit($report->description, 125) }}</p>
                            <div class="tag-row">@foreach($report->categories as $category)<span>{{ $category->name }}</span>@endforeach</div>
                            <a href="{{ route('public-reports.show', $report) }}" class="text-link">Lihat detail dan progres →</a>
                        </div>
                    </article>
                @empty
                    <div class="empty-state wide"><span>⌁</span><h3>Laporan publik tidak ditemukan</h3><p>Coba ubah kata kunci atau filter yang digunakan.</p></div>
                @endforelse
            </div>
            <x-pagination :paginator="$reports" />
        </div>
    </section>
@endsection
