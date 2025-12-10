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
                   bottom: 20px;
                   right: 20px;
                   min-width: 240px;
                   max-width: 340px;
                   text-align: left;
                   z-index: 10500;
                   box-shadow: 0 3px 10px rgba(0,0,0,0.18);
                   border-radius: 8px;
                   font-size: 14px;
                   padding: 10px 14px;
                 '>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert' style='margin-left:12px;'></button>
            </div>

            <script>
            setTimeout(() => {
                const alertBox = document.getElementById('floating-alert');
                if (alertBox) {
                    alertBox.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                    alertBox.style.opacity = '0';
                    alertBox.style.transform = 'translateY(10px)';
                    setTimeout(() => alertBox.remove(), 400);
                }
            }, 2500);
            </script>
            ";
        }
    }
}
?>
