<?php
// Lấy chuỗi User-Agent
$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

// Kiểm tra xem chuỗi User-Agent có rỗng không
if (!empty($userAgent)) {
    $os = "Unknown OS";

    // Kiểm tra các từ khóa phổ biến cho các hệ điều hành
    if (preg_match('/windows|win32/i', $userAgent)) {
        $os = 'Windows';
    } elseif (preg_match('/macintosh|mac os x|mac_powerpc/i', $userAgent)) {
        $os = 'Mac OS';
    } elseif (preg_match('/linux/i', $userAgent)) {
        // Cẩn thận: Android cũng dựa trên Linux
        if (preg_match('/android/i', $userAgent)) {
             $os = 'Android';
        } else {
             $os = 'Linux';
        }
    } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
        $os = 'iOS';
    } elseif (preg_match('/android/i', $userAgent)) {
        $os = 'Android'; // Kiểm tra lại phòng trường hợp Linux không khớp trước
    } elseif (preg_match('/blackberry/i', $userAgent)) {
        $os = 'BlackBerry';
    } elseif (preg_match('/webos/i', $userAgent)) {
        $os = 'Mobile'; // Hoặc WebOS cụ thể
    }

    echo "User Agent: " . htmlspecialchars($userAgent) . "<br>";
    echo "Detected OS: " . $os . "<br>";
    echo "Nếu bạn vẫn chưa chắc chắn, hãy lên mạng search truy vấn 'cách kiểm tra hệ điều hành là phiên bản Windows 64 bit hay Windows 32 bit'";

} else {
    echo "User Agent string is not available.";
}
?>