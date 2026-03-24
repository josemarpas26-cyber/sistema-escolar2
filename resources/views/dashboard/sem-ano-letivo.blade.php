// criar resources/views/dashboard/sem-ano-letivo.blade.php
@extends('layouts.app')

@section('content')
    <x-access-denied-page
        title="Sem ano letivo ativo"
        message="Contacte a administração para ativar um ano letivo." />
@endsection
