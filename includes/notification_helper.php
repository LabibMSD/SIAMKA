<?php
/**
 * Notification/Flash Message Helper
 */

// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Set notification
 */
if (!function_exists('set_notification')) {
    function set_notification($type, $message) {
        $_SESSION["notification"] = [
            "type" => $type,  // success, error, warning, info
            "message" => $message
        ];
    }
}

/**
 * Get and clear notification
 */
if (!function_exists('get_notification')) {
    function get_notification() {
        if (isset($_SESSION["notification"])) {
            $notification = $_SESSION["notification"];
            unset($_SESSION["notification"]);
            return $notification;
        }
        return null;
    }
}

/**
 * Display notification HTML
 */
if (!function_exists('display_notification')) {
    function display_notification() {
        $notif = get_notification();
        if ($notif) {
            $type = $notif["type"];
            $message = htmlspecialchars($notif["message"]);

            $class_map = [
                "success" => "alert-success",
                "error"   => "alert-danger",
                "warning" => "alert-warning",
                "info"    => "alert-info"
            ];
            
            $class = $class_map[$type] ?? "alert-info";

            echo "
            <div id='floating-alert' class='alert {$class} alert-dismissible fade show' 
                 role='alert'
                 style='
                   position: fixed;
                   top: 10%;
                   left: 50%;
                   transform: translate(-50%, -50%);
                   min-width: 300px;
                   text-align: center;
                   z-index: 9999;
                   box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                   border-radius: 10px;
                   font-size: 16px;
                 '>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>

            <script>
            setTimeout(() => {
                const alertBox = document.getElementById('floating-alert');
                if (alertBox) {
                    alertBox.style.transition = 'opacity 0.5s ease';
                    alertBox.style.opacity = '0';
                    setTimeout(() => alertBox.remove(), 500);
                }
            }, 2000);
            </script>
            ";
        }
    }
}
?>
