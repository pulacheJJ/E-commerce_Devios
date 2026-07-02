@extends('layouts.auth-admin')

@section('content')
    <div class="auth-page-wrapper">
        <!-- Efectos de fondo sutiles -->
        <div class="ambient-glow"></div>

        <div class="auth-card">
            <!-- Cabecera y Logo Centrado -->
            <div class="auth-header">
                <img src="{{ asset('img/logo-devioz.webp') }}" alt="DEVIOZ" class="auth-logo">
                <h2>Bienvenido de vuelta</h2>
                <p>Inicia sesión en tu espacio de trabajo</p>
            </div>

            <form method="POST" action="{{ route('login.post') }}" id="formLogin" class="auth-form">
                @csrf

                @if ($errors->any())
                    <div class="alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <div class="form-group">
                    <label for="email">Correo Corporativo</label>
                    <div class="input-container">
                        <i class="fas fa-envelope icon-left"></i>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus
                            placeholder="admin@devioz.com">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <div class="input-container">
                        <i class="fas fa-lock icon-left"></i>
                        <input type="password" id="password" name="password" required placeholder="••••••••">
                        <button type="button" class="btn-eye" id="btnTogglePass" tabindex="-1">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-actions">
                    <label class="checkbox-container">
                        <input type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <span class="checkmark"></span>
                        <span class="label-text">Mantener sesión iniciada</span>
                    </label>
                </div>

                <button type="submit" class="btn-primary" id="btnLogin">
                    <span>Ingresar al Sistema</span>
                </button>
            </form>
        </div>
    </div>
@endsection
