
@if ($errors->any())
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                html: `
                    <ul class="text-left list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                `,
                confirmButtonText: 'Okay',
                confirmButtonColor: '#284B53', // matches resort-primary
            });
        });
    </script>
@endif
