@extends('layouts.app')

@section('title', 'Edit Laporan')

@section('content')
    <section class="page-section">
        <div class="container narrow-container">
            <div class="breadcrumb"><a href="{{ route('reports.show', $report) }}">Detail laporan</a><span>/</span><span>Edit</span></div>
            <div class="page-heading"><span class="eyebrow">Perbarui informasi</span><h1>Edit laporan</h1><p>Laporan hanya dapat diedit selama masih berstatus diajukan.</p></div>
            <form method="POST" action="{{ route('reports.update', $report) }}" enctype="multipart/form-data" class="form-panel">
                @csrf
                @method('PUT')
                @include('reports._form')
                <div class="form-actions"><a href="{{ route('reports.show', $report) }}" class="button button-ghost">Batal</a><button type="submit" class="button button-primary">Simpan perubahan</button></div>
            </form>
        </div>
    </section>
@endsection
