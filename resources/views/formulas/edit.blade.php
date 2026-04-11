@extends('layouts.app')

@section('page-title', 'Editar Fórmula')

@section('content')
@include('formulas.partials.form', [
    'action' => route('formulas.update', $formula),
    'method' => 'PUT',
    'formula' => $formula,
    'anosLetivos' => collect([$formula->anoLetivo]),
])
@endsection
