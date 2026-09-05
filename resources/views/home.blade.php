@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
    <section class="hero">
        <div class="container hero-grid">
            <div class="hero-copy">
                <span class="eyebrow">Warga bergerak · Kota berbenah</span>
                <h1>Lingkungan layak dimulai dari <em>satu laporan.</em></h1>
                <p>Laporkan jalan rusak, drainase bermasalah, sanitasi buruk, dan kondisi permukiman yang membutuhkan perhatian. Pantau prosesnya secara transparan sampai selesai.</p>
                <div class="hero-actions">
                    @auth
                        <a href="{{ route('reports.create') }}" class="button button-primary button-large">Buat laporan</a>
                        <a href="{{ route('dashboard') }}" class="button button-light button-large">Buka dashboard</a>
                    @else
                        <a href="{{ route('register') }}" class="button button-primary button-large">Mulai melapor</a>
                        <a href="{{ route('public-reports.index') }}" class="button button-light button-large">Lihat laporan publik</a>
                    @endauth
                </div>
                <div class="trust-row">
                    <span><b>01</b> Mudah dilaporkan</span>
                    <span><b>02</b> Terverifikasi</span>
                    <span><b>03</b> Terpantau</span>
                </div>
            </div>

            <div class="hero-visual" aria-label="Ilustrasi proses pelaporan">
                <div class="sdg-card">
                    <span>11</span>
                    <div><strong>Sustainable Cities</strong><small>& Communities</small></div>
                </div>
                <div class="map-card">
                    <div class="map-lines"></div>
                    <span class="map-pin pin-one">!</span>
                    <span class="map-pin pin-two">✓</span>
                    <div class="map-report-card">
                        <span class="status-dot"></span>
                        <div><small>Status laporan</small><strong>Sedang diproses</strong></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="stats-strip">
        <div class="container stats-grid">
            <div><strong>{{ number_format($totalReports) }}</strong><span>Laporan warga</span></div>
            <div><strong>{{ number_format($resolvedReports) }}</strong><span>Masalah selesai</span></div>
            <div><strong>5</strong><span>Tahap transparan</span></div>
            <div><strong>SDG 11</strong><span>Kota berkelanjutan</span></div>
        </div>
    </section>

    <section class="section" id="cara-kerja">
        <div class="container">
            <div class="section-heading">
                <span class="eyebrow">Alur yang jelas</span>
                <h2>Dari laporan menuju perubahan nyata</h2>
                <p>Setiap laporan melewati tahapan yang dapat dipantau oleh warga.</p>
            </div>
            <div class="steps-grid">
                <article class="step-card"><span>01</span><div class="step-icon">↗</div><h3>Kirim laporan</h3><p>Isi lokasi, uraian, kategori masalah, dan unggah foto bukti.</p></article>
                <article class="step-card"><span>02</span><div class="step-icon">◎</div><h3>Petugas verifikasi</h3><p>Petugas memeriksa kelengkapan dan validitas laporan warga.</p></article>
                <article class="step-card"><span>03</span><div class="step-icon">✓</div><h3>Pantau penanganan</h3><p>Status bergerak dari diverifikasi, diproses, hingga selesai.</p></article>
            </div>
        </div>
    </section>

    <section class="section section-soft">
        <div class="container">
            <div class="section-heading heading-row">
                <div><span class="eyebrow">Dampak bersama</span><h2>Laporan yang sedang ditangani</h2></div>
                <a href="{{ route('public-reports.index') }}" class="text-link">Lihat semua laporan publik →</a>
            </div>
            <div class="report-preview-grid">
                @forelse($recentReports as $report)
                    <article class="preview-card">
                        <div class="preview-photo" style="background-image: url('{{ Storage::url($report->photo_path) }}')"></div>
                        <div class="preview-body">
                            <span class="badge {{ $report->status->badgeClass() }}">{{ $report->status->label() }}</span>
                            <h3>{{ $report->title }}</h3>
                            <p>{{ Str::limit($report->description, 110) }}</p>
                            <div class="tag-row">@foreach($report->categories as $category)<span>{{ $category->name }}</span>@endforeach</div>
                            <a href="{{ route('public-reports.show', $report) }}" class="text-link preview-link">Lihat perkembangan →</a>
                        </div>
                    </article>
                @empty
                    <div class="empty-state wide"><span>⌁</span><h3>Belum ada laporan publik</h3><p>Jadilah warga pertama yang membantu lingkunganmu.</p></div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="cta-section">
        <div class="container cta-card">
            <div><span class="eyebrow eyebrow-light">Jangan biarkan masalah berlalu</span><h2>Suaramu bisa mengubah lingkungan.</h2><p>Sampaikan kondisi yang kamu temui dan bantu petugas menentukan prioritas penanganan.</p></div>
            <a href="{{ auth()->check() ? (auth()->user()->hasRole(\App\Enums\UserRole::Warga) ? route('reports.create') : route('dashboard')) : route('register') }}" class="button button-light button-large">{{ auth()->check() && ! auth()->user()->hasRole(\App\Enums\UserRole::Warga) ? 'Buka dashboard →' : 'Laporkan sekarang →' }}</a>
        </div>
    </section>
@endsection
