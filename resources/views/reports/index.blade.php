@extends('layouts.app')

@section('title', 'Daftar Laporan')

@section('content')
    <section class="page-section">
        <div class="container">
            <div class="page-heading heading-row">
                <div><span class="eyebrow">{{ auth()->user()->hasRole(\App\Enums\UserRole::Warga) ? 'Laporan saya' : 'Laporan warga' }}</span><h1>Pusat laporan</h1><p>Cari, saring, dan pantau setiap laporan berdasarkan tahap penanganannya.</p></div>
                @can('create', \App\Models\Report::class)<a href="{{ route('reports.create') }}" class="button button-primary">+ Buat laporan</a>@endcan
            </div>

            <form method="GET" action="{{ route('reports.index') }}" class="filter-bar">
                <label class="search-field"><span aria-hidden="true">⌕</span><input type="search" name="search" aria-label="Cari judul atau lokasi laporan" value="{{ request('search') }}" placeholder="Cari judul atau lokasi..."></label>
                <select name="status" aria-label="Filter status"><option value="">Semua status</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>@endforeach</select>
                <select name="category" aria-label="Filter kategori"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }}</option>@endforeach</select>
                <button type="submit" class="button button-dark">Terapkan</button>
                @if(request()->hasAny(['search', 'status', 'category']))<a href="{{ route('reports.index') }}" class="button button-ghost">Reset</a>@endif
            </form>

            <div class="reports-list">
                @forelse($reports as $report)
                    <article class="report-row-card">
                        <a href="{{ route('reports.show', $report) }}" class="report-thumb"><img src="{{ Storage::url($report->photo_path) }}" alt="Foto bukti {{ $report->title }}"></a>
                        <div class="report-row-main">
                            <div class="report-meta"><span class="badge {{ $report->status->badgeClass() }}">{{ $report->status->label() }}</span><span>#{{ str_pad($report->id, 5, '0', STR_PAD_LEFT) }}</span><span>{{ $report->created_at->format('d M Y') }}</span></div>
                            <h2><a href="{{ route('reports.show', $report) }}">{{ $report->title }}</a></h2>
                            <p class="location-line">⌖ {{ Str::limit($report->address, 100) }}</p>
                            <div class="tag-row">@foreach($report->categories as $category)<span>{{ $category->name }}</span>@endforeach</div>
                        </div>
                        <div class="report-row-side">
                            @unless(auth()->user()->hasRole(\App\Enums\UserRole::Warga))<span class="mini-label">Pelapor</span><strong>{{ $report->reporter->name }}</strong>@endunless
                            <a href="{{ route('reports.show', $report) }}" class="text-link">Lihat detail →</a>
                        </div>
                    </article>
                @empty
                    <div class="empty-state"><span>⌁</span><h3>Laporan tidak ditemukan</h3><p>Coba ubah filter atau buat laporan lingkungan baru.</p>@can('create', \App\Models\Report::class)<a href="{{ route('reports.create') }}" class="button button-primary">Buat laporan</a>@endcan</div>
                @endforelse
            </div>
            <x-pagination :paginator="$reports" />
        </div>
    </section>
@endsection
