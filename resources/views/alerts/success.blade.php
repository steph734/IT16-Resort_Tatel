@if (session('success'))
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                html: `{{ session('success') }}`,
                confirmButtonText: 'Great',
                confirmButtonColor: '#284B53', // matches resort-primary
            });
        });
    </script>
@endif
