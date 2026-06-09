{{-- resources/views/layouts/app.blade.php -- MODIFIED FOR BOOTSTRAP --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
        
        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        
        <style>
            body {
                background-color: #f8f9fa;
            }
            .navbar-brand {
                font-size: 1.5rem;
                font-weight: 600;
            }
            .nav-link.active {
                font-weight: 600;
            }
            .card {
                border: none;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                border-radius: 8px;
            }
            .btn {
                border-radius: 6px;
                font-weight: 500;
            }
            .table {
                background: white;
            }
            /* Ensure navbar collapse works properly */
            @media (min-width: 992px) {
                .navbar-collapse {
                    display: flex !important;
                }
            }
            /* Make sure nav links are visible */
            .navbar-nav .nav-link {
                color: rgba(255, 255, 255, 0.85) !important;
            }
            .navbar-nav .nav-link:hover {
                color: rgba(255, 255, 255, 1) !important;
            }
            /* Fix pagination styling for Bootstrap */
            .pagination {
                margin-bottom: 0;
            }
            .pagination .page-link {
                padding: 0.375rem 0.75rem;
                font-size: 0.875rem;
                line-height: 1.5;
                color: #0d6efd;
                background-color: #fff;
                border: 1px solid #dee2e6;
            }
            .pagination .page-link:hover {
                z-index: 2;
                color: #0a58ca;
                background-color: #e9ecef;
                border-color: #dee2e6;
            }
            .pagination .page-item.active .page-link {
                z-index: 3;
                color: #fff;
                background-color: #0d6efd;
                border-color: #0d6efd;
            }
            .pagination .page-item.disabled .page-link {
                color: #6c757d;
                pointer-events: none;
                background-color: #fff;
                border-color: #dee2e6;
            }
            /* Ensure pagination arrows are properly sized */
            .pagination .page-link {
                min-width: 38px;
                text-align: center;
            }
            /* Loading overlay */
            #loading-overlay {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.65);
                display: none;
                align-items: center;
                justify-content: center;
                flex-direction: column;
                z-index: 2000;
            }
            #loading-overlay.show {
                display: flex;
            }
            .loading-brain {
                width: 72px;
                height: 72px;
                border-radius: 9999px;
                background: linear-gradient(135deg, #4f46e5, #0ea5e9);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 36px;
                color: #fff;
                box-shadow: 0 10px 30px rgba(0,0,0,0.25);
                animation: brain-spin 1.1s linear infinite;
            }
            @keyframes brain-spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        </style>

    </head>
    <body>
        <div id="app"> {{-- Common practice to wrap in an ID --}}
            {{-- IMPORTANT: Replace Breeze navigation with a Bootstrap Navbar --}}
            @include('layouts.partials.navbar-bootstrap') {{-- CREATE THIS FILE with Bootstrap navbar code --}}


            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-light border-bottom mb-4"> {{-- Basic Bootstrap header styling --}}
                    <div class="container py-3"> {{-- Use Bootstrap container --}}
                        {{ $header }} {{-- This will render the H2 from your pages --}}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main class="py-4"> {{-- Add some padding --}}
                 <div class="container"> {{-- Wrap main content in a container --}}
                    @yield('content')
                 </div>
            </main>
        </div>

        <div id="loading-overlay" aria-live="polite" aria-busy="true" role="status">
            <div class="loading-brain" aria-hidden="true">🧠</div>
            <div id="loading-overlay-message" class="text-white mt-3 fw-semibold">Analyzing resume...</div>
        </div>

        <!-- Bootstrap JS Bundle (includes Popper) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

        <script>
            (function () {
                const overlay = document.getElementById('loading-overlay');
                const messageEl = document.getElementById('loading-overlay-message');
                const defaultMessage = messageEl ? messageEl.textContent : '';
                if (!overlay) return;

                const showOverlay = (message) => {
                    if (messageEl) {
                        messageEl.textContent = (message && message.trim()) ? message.trim() : defaultMessage;
                    }
                    overlay.classList.add('show');
                };
                const hideOverlay = () => overlay.classList.remove('show');

                // Expose helpers in case inline handlers ever need them
                window.__showLoadingOverlay = showOverlay;
                window.__hideLoadingOverlay = hideOverlay;

                // Capture clicks on any element marked with data-loading-overlay (event delegation).
                // Reads optional data-loading-message for a context-specific status string.
                document.addEventListener('click', (event) => {
                    const target = event.target.closest('[data-loading-overlay]');
                    if (!target) return;
                    if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                    showOverlay(target.dataset.loadingMessage);
                }, true);

                // Also handle form submissions with the attribute
                document.addEventListener('submit', (event) => {
                    if (event.target?.hasAttribute('data-loading-overlay')) {
                        showOverlay(event.target.dataset.loadingMessage);
                    }
                }, true);

                // Hide overlay when the page finishes loading (in case it was shown early)
                window.addEventListener('pageshow', hideOverlay);
            })();
        </script>

        @stack('scripts')

    </body>
</html>
