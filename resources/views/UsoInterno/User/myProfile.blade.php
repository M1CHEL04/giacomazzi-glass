@extends('layouts.app-interno')
@section('title', 'Mi Perfil - Giacomazzi Glass')
@section('page-title', 'Mi perfil')
@section('subhead', 'Gestiona tu información personal')
@section('content')
@php
$fullName = trim(session('user_name', 'Usuario'));
$nameParts = preg_split('/\s+/', $fullName);
$userName = $nameParts[0] ?? 'Usuario';
$userEmail = session('user_email', 'correo@ejemplo.com');
$userInitial = mb_substr($userName, 0, 1);
@endphp

<div class="row g-4">
    <div class="col-12 col-lg-7">
        <div class="p-4 p-lg-5 border rounded-4 bg-white shadow-sm">
            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="internal-user-avatar" aria-hidden="true">{{ $userInitial }}</span>
                <div>
                    <div class="fw-semibold text-uppercase small text-success-emphasis">Cuenta</div>
                    <h2 class="h4 fw-bold mb-1">{{ $fullName }}</h2>
                    <div class="text-secondary">{{ $userEmail }}</div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Nombre</label>
                    <div class="form-control bg-light">{{ $fullName }}</div>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">Correo electronico</label>
                    <div class="form-control bg-light">{{ $userEmail }}</div>
                </div>
            </div>

            <div class="mt-4 d-flex flex-wrap gap-2 justify-content-end">
                <a href="{{ route('change-password-view') }}" class="btn btn-sm btn-success fw-semibold">
                    Cambiar contrasena
                </a>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-5">
        <div class="p-4 p-lg-5 border rounded-4 bg-white shadow-sm h-100">
            <div class="fw-semibold text-uppercase small text-success-emphasis mb-2">Seguridad</div>
            <h3 class="h5 fw-bold mb-3">Tu acceso protegido</h3>
            <p class="text-secondary mb-4">
                Mantén tu cuenta segura actualizando tu contrasena con regularidad.
            </p>
            <div class="d-flex align-items-center gap-3">
                <div class="internal-brand-mark">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <div>
                    <div class="fw-semibold">Recomendacion</div>
                    <div class="text-secondary small">Usa una contrasena unica y segura.</div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection