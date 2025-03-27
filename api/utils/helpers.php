<?php
function generateRandomPassword($length = 12) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
    return substr(str_shuffle(str_repeat($characters, ceil($length/strlen($characters)))), 0, $length);
}

function jsonResponse($status, $message, $code = 200) {
    http_response_code($code);
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}