<footer class="main-footer">
    <div class="footer-left">
        Copyright &copy; {{ date('Y') }} <div class="bullet"></div>
    </div>
    <div class="footer-right">

    </div>
</footer>
</div>
</div>

<!-- General JS Scripts -->
<script src="{{ asset('assets/admin/modules/jquery.min.js') }}"></script>
<script src="{{ asset('assets/admin/modules/popper.js') }}"></script>
<script src="{{ asset('assets/admin/modules/tooltip.js') }}"></script>
<script src="{{ asset('assets/admin/modules/bootstrap/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/admin/modules/nicescroll/jquery.nicescroll.min.js') }}"></script>
<script src="{{ asset('assets/admin/modules/moment.min.js') }}"></script>
<script src="{{ asset('assets/admin/js/stisla.js') }}"></script>

<!-- JS Libraies -->
<script src="{{ asset('assets/admin/modules/simple-weather/jquery.simpleWeather.min.js') }}"></script>
<script src="{{ asset('assets/admin/modules/chart.min.js') }}"></script>
<script src="{{ asset('assets/admin/modules/jqvmap/dist/jquery.vmap.min.js') }}"></script>
<script src="{{ asset('assets/admin/modules/jqvmap/dist/maps/jquery.vmap.world.js') }}"></script>
<script src="{{ asset('assets/admin/modules/summernote/summernote-bs4.js') }}"></script>
<script src="{{ asset('assets/admin/modules/chocolat/dist/js/jquery.chocolat.min.js') }}"></script>
<script src="{{ asset('assets/admin/modules/select2/dist/js/select2.min.js') }}"></script>

<!-- SweetAlert2 -->
<script src="{{ asset('assets/front/js/sweetalert2@11.js') }}"></script>

{!! ToastMagic::scripts() !!}

@stack('scripts')

<!-- Template JS File -->
<script src="{{ asset('assets/admin/js/scripts.js') }}"></script>
<script src="{{ asset('assets/admin/js/custom.js') }}"></script>
</body>

</html>
