@extends('layouts.app')

@section('title', 'Edit Kategori')

@section('content')
    <section class="page-section"><div class="container narrow-container"><div class="breadcrumb"><a href="{{ route('admin.categories.index') }}">Kategori</a><span>/</span><span>Edit</span></div><div class="page-heading"><span class="eyebrow">Master data</span><h1>Edit kategori</h1><p>Perubahan nama akan langsung terlihat pada seluruh laporan terkait.</p></div><form method="POST" action="{{ route('admin.categories.update', $category) }}" class="form-panel">@csrf @method('PUT') @include('admin.categories._form')<div class="form-actions"><a href="{{ route('admin.categories.index') }}" class="button button-ghost">Batal</a><button type="submit" class="button button-primary">Simpan perubahan</button></div></form></div></section>
@endsection
