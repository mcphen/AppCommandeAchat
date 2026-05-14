@extends('errors.layout')

@section('code', '500')
@section('code-color-from', '#ef4444')
@section('code-color-to', '#dc2626')
@section('icon-bg', '#fef2f2')
@section('title', 'Erreur serveur')
@section('description', "Une erreur interne s'est produite. Notre équipe a été notifiée. Réessayez dans quelques instants.")

@section('icon')
<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
</svg>
@endsection

@section('actions')
<a href="{{ url('/dashboard') }}" class="btn-primary">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
    Tableau de bord
</a>
<a href="javascript:location.reload()" class="btn-outline">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
    Réessayer
</a>
@endsection
