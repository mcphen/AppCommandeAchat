@extends('errors.layout')

@section('code', '403')
@section('code-color-from', '#f59e0b')
@section('code-color-to', '#d97706')
@section('icon-bg', '#fef3c7')
@section('title', 'Accès refusé')
@section('description', "Vous n'avez pas les permissions nécessaires pour accéder à cette ressource. Si vous pensez qu'il s'agit d'une erreur, contactez votre administrateur.")

@section('icon')
<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
</svg>
@endsection

@section('actions')
<a href="{{ url('/dashboard') }}" class="btn-primary">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
    Tableau de bord
</a>
<a href="javascript:history.back()" class="btn-outline">Retour</a>
@endsection
