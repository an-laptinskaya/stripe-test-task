<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
        @stack('styles')

        <title>Laravel</title>
        
    </head>
    <body class="antialiased">

    <div>
        <div class="navbar bg-body-tertiary">
            <div class="container container-fluid">
                <a class="navbar-brand">Navbar</a>
                @if (Route::has('login'))
                    <div class="d-flex">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="btn btn-outline-primary">{{auth()->user()->name}}</a>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-outline-primary me-2">Log in</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="btn btn-outline-primary">Register</a>
                            @endif
                        @endauth
                    </div>
                @endif
            </div>
        </div>
    
        @yield('content')

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
    </body>
</html>