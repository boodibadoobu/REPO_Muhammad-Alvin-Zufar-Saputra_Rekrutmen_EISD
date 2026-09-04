@extends('layouts.app')

@section('title', 'Tambah Kategori')

@section('content')
    <section class="page-section"><div class="container narrow-container"><div class="breadcrumb"><a href="{{ route('admin.categories.index') }}">Kategori</a><span>/</span><span>Tambah</span></div><div class="page-heading"><span class="eyebrow">Master data</span><h1>Tambah kategori</h1><p>Buat kategori yang mudah dipahami warga saat melapor.</p></div><form method="POST" action="{{ route('admin.categories.store') }}" class="form-panel">@csrf @include('admin.categories._form')<div class="form-actions"><a href="{{ route('admin.categories.index') }}" class="button button-ghost">Batal</a><button type="submit" class="button button-primary">Simpan kategori</button></div></form></div></section>
@endsection
