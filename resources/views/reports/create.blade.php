@extends('layouts.app')

@section('title', 'Buat Laporan')

@section('content')
    <section class="page-section">
        <div class="container narrow-container">
            <div class="breadcrumb"><a href="{{ route('reports.index') }}">Laporan</a><span>/</span><span>Buat laporan</span></div>
            <div class="page-heading"><span class="eyebrow">Suaramu penting</span><h1>Buat laporan lingkungan</h1><p>Isi informasi sejelas mungkin agar petugas dapat memverifikasi dan menindaklanjuti laporan.</p></div>
            <form method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data" class="form-panel">
                @csrf
                @include('reports._form')
                <div class="form-actions"><a href="{{ route('reports.index') }}" class="button button-ghost">Batal</a><button type="submit" class="button button-primary">Kirim laporan</button></div>
            </form>
        </div>
    </section>
@endsection
