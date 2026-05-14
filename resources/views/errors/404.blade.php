@extends('errors.layout')

@section('code', '404')
@section('code-color-from', '#6366f1')
@section('code-color-to', '#4f46e5')
@section('icon-bg', '#eef2ff')
@section('title', 'Page introuvable')
@section('description', "La page que vous recherchez n'existe pas ou a été déplacée. Vérifiez l'URL ou revenez à l'accueil.")

@section('icon')
<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/><line x1="11" y1="8" x2="11" y2="14"/><line x1="8" y1="11" x2="14" y2="11"/>
</svg>
@endsection

@section('actions')
<a href="{{ url('/dashboard') }}" class="btn-primary">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
    Tableau de bord
</a>
<a href="javascript:history.back()" class="btn-outline">Retour</a>
@endsection
