<footer class="content-footer footer bg-footer-theme">
    <div class="container-xxl">
        <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
            <div class="mb-2 mb-md-0">
                &copy; {{ now()->year }} {{ config('app.name', 'Super3000') }}
            </div>
            <div class="d-none d-lg-inline-block text-body-secondary">
                {{ __('Admin dashboard') }}
            </div>
        </div>
    </div>
</footer>
