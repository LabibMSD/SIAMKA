<?php


function validate_image($file) {
    $allowed_types = ['image/jpeg', 'image/png'];
    $max_size = 2 * 1024 * 1024; // 2MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return "Terjadi kesalahan saat upload file.";
    }

    if (!in_array($file['type'], $allowed_types)) {
        return "Format file tidak valid (hanya JPG/PNG).";
    }

    if ($file['size'] > $max_size) {
        return "Ukuran file maksimal 2MB.";
    }

    return true; // valid
}

function upload_image($file, $upload_dir = '../assets/uploads/assets/') {
    $validate = validate_image($file);
    if ($validate !== true) return $validate;

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $new_name = uniqid('AST_') . '.' . $file_ext;
    $file_path = $upload_dir . $new_name;

    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        return $new_name;
    } else {
        return "Gagal mengunggah file.";
    }
}

function delete_image($filename, $upload_dir = '../assets/uploads/assets/') {
    $file_path = $upload_dir . $filename;
    if (file_exists($file_path)) {
        unlink($file_path);
        return true;
    }
    return false;
}
?>
