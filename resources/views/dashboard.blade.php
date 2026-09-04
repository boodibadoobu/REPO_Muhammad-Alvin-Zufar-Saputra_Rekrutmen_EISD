@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <section class="page-section">
        <div class="container">
            <div class="page-heading heading-row">
                <div><span class="eyebrow">Ringkasan aktivitas</span><h1>Halo, {{ auth()->user()->name }}.</h1><p>{{ auth()->user()->hasRole(\App\Enums\UserRole::Warga) ? 'Pantau perkembangan laporan lingkunganmu.' : 'Pantau dan tindak lanjuti laporan warga.' }}</p></div>
                @can('create', \App\Models\Report::class)<a href="{{ route('reports.create') }}" class="button button-primary">+ Buat laporan</a>@endcan
            </div>
            <div class="dashboard-stats">
                @foreach(\App\Enums\ReportStatus::cases() as $status)
                    <article class="stat-card"><span class="badge {{ $status->badgeClass() }}">{{ $status->label() }}</span><strong>{{ $counts[$status->value] }}</strong><small>laporan</small></article>
                @endforeach
            </div>
            <div class="panel">
                <div class="panel-heading"><div><h2>Laporan terbaru</h2><p>Aktivitas paling baru dalam sistem.</p></div><a href="{{ route('reports.index') }}" class="text-link">Lihat semua →</a></div>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Laporan</th>@unless(auth()->user()->hasRole(\App\Enums\UserRole::Warga))<th>Pelapor</th>@endunless<th>Status</th><th>Dibuat</th><th></th></tr></thead>
                        <tbody>
                            @forelse($recentReports as $report)
                                <tr><td><strong>{{ $report->title }}</strong><small>{{ Str::limit($report->address, 55) }}</small></td>@unless(auth()->user()->hasRole(\App\Enums\UserRole::Warga))<td>{{ $report->reporter->name }}</td>@endunless<td><span class="badge {{ $report->status->badgeClass() }}">{{ $report->status->label() }}</span></td><td>{{ $report->created_at->format('d M Y') }}</td><td><a href="{{ route('reports.show', $report) }}" class="table-link">Detail</a></td></tr>
                            @empty
                                <tr><td colspan="5"><div class="empty-inline">Belum ada laporan untuk ditampilkan.</div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
@endsection
