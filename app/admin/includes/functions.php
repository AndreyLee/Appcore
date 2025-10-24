<?php
function display_toast($message, $type = 'info') {
    if ($message) {
        $icon = ($type === 'sucesso' || $type === 'success') ? 'success' : 'error';
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: '{$icon}',
                    title: '" . addslashes(htmlspecialchars($message)) . "',
                    showConfirmButton: false,
                    showCloseButton: true,
                    timer: 5000,
                    timerProgressBar: true,
                    didOpen: (toast) => {
                        toast.addEventListener('mouseenter', Swal.stopTimer)
                        toast.addEventListener('mouseleave', Swal.resumeTimer)
                    }
                });
            });
        </script>";
    }
}
?>