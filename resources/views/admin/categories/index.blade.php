@extends('layouts.app')

@section('title', 'Kelola Kategori')

@section('content')
    <section class="page-section">
        <div class="container">
            <div class="page-heading heading-row"><div><span class="eyebrow">Master data</span><h1>Kategori masalah</h1><p>Kategori dapat dipilih lebih dari satu pada setiap laporan warga.</p></div><a href="{{ route('admin.categories.create') }}" class="button button-primary">+ Tambah kategori</a></div>
            <div class="admin-grid">
                @forelse($categories as $category)
                    <article class="panel admin-card"><div class="admin-card-top"><span class="category-symbol">{{ strtoupper(substr($category->name, 0, 1)) }}</span><span class="count-pill">{{ $category->reports_count }} laporan</span></div><h2>{{ $category->name }}</h2><p>{{ $category->description ?: 'Belum ada deskripsi kategori.' }}</p><div class="admin-actions"><a href="{{ route('admin.categories.edit', $category) }}" class="button button-ghost">Edit</a><form method="POST" action="{{ route('admin.categories.destroy', $category) }}">@csrf @method('DELETE')<button type="submit" class="button button-danger">Hapus</button></form></div></article>
                @empty
                    <div class="empty-state wide"><span>⌁</span><h3>Belum ada kategori</h3><p>Tambahkan kategori sebelum warga mengirim laporan.</p></div>
                @endforelse
            </div>
            <x-pagination :paginator="$categories" />
        </div>
    </section>
@endsection
