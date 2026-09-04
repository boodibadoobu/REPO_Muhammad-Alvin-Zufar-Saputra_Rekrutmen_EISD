@extends('layouts.app')

@section('title', 'Kelola Pengguna')

@section('content')
    <section class="page-section">
        <div class="container">
            <div class="page-heading"><span class="eyebrow">Hak akses</span><h1>Kelola pengguna</h1><p>Atur role warga, petugas, dan admin dengan aman.</p></div>
            <form method="GET" action="{{ route('admin.users.index') }}" class="filter-bar"><label class="search-field"><span>⌕</span><input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..."></label><select name="role"><option value="">Semua role</option>@foreach($roles as $role)<option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>@endforeach</select><button class="button button-dark" type="submit">Terapkan</button>@if(request()->hasAny(['search', 'role']))<a href="{{ route('admin.users.index') }}" class="button button-ghost">Reset</a>@endif</form>
            <div class="panel table-panel"><div class="table-wrap"><table><thead><tr><th>Pengguna</th><th>Role</th><th>Laporan dibuat</th><th>Ditangani</th><th>Bergabung</th><th></th></tr></thead><tbody>
                @forelse($users as $user)
                    <tr><td><div class="user-cell"><span class="avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</span><span><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></span></div></td><td><span class="role-badge role-{{ $user->role->value }}">{{ $user->role->label() }}</span></td><td>{{ $user->reports_count }}</td><td>{{ $user->handled_reports_count }}</td><td>{{ $user->created_at->format('d M Y') }}</td><td><a href="{{ route('admin.users.edit', $user) }}" class="table-link">Kelola</a></td></tr>
                @empty<tr><td colspan="6"><div class="empty-inline">Pengguna tidak ditemukan.</div></td></tr>@endforelse
            </tbody></table></div></div>
            <x-pagination :paginator="$users" />
        </div>
    </section>
@endsection
