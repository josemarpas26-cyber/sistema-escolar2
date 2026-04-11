@extends('layouts.app')

@section('page-title', 'Nova Fórmula')

@section('content')
@include('formulas.partials.form', [
    'action' => route('formulas.store'),
    'method' => 'POST',
    'formula' => null,
    'anosLetivos' => $anosLetivos,
])
@endsection
