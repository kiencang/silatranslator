<?php
// File: myself/update_RSS_link.php

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Cấu hình ---
$configFilePath = __DIR__ . '/rss_config.php'; // Đường dẫn đến tương đối đến file config.php
$errorMessage = ''; // Thông báo lỗi
$successMessage = ''; // Thông báo thành công

// --- Giá trị hiện tại ---
$currentRssLink = ''; // Link RSS hiện tại
$currentRssLinkName = ''; // Tên của link RSS hiện tại
$currentCacheDuration = 6; // Giá trị mặc định thời gian cache
$currentTranslateContent = false; // Giá trị mặc định có dịch nội dung RSS hay không
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Hàm kiểm tra tính hợp lệ của RSS feed ---
function isValidRssFeed($url) {
    // Nếu URL rỗng hoặc không phải là định dạng URL hợp lệ
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        // Cho phép URL rỗng (coi như hợp lệ để xóa), nhưng định dạng sai thì không phải feed hợp lệ
        return empty($url);
    }

    // Cấu hình ngữ cảnh stream để fetch URL
    $contextOptions = [
        'http' => [
            'method' => "GET",
            'timeout' => 30, // Timeout 30 giây cho rộng rãi, một số RSS ở xa có thể mất thời gian hơi lâu mới load được
            'ignore_errors' => true, // Bỏ qua lỗi HTTP (ví dụ: 404) để có thể đọc nội dung lỗi nếu có
            'header' => "User-Agent: RSSConfigUpdater/1.0\r\n" // Một số feed yêu cầu User-Agent
        ],
        'ssl' => [
            'verify_peer' => false,       // Không xác thực chứng chỉ SSL của peer
            'verify_peer_name' => false, // Không xác thực tên host của peer
        ],
    ];
    
    $context = stream_context_create($contextOptions);

    // Tắt báo lỗi nội bộ của PHP khi dùng file_get_contents hoặc simplexml_load_string
    $content = @file_get_contents($url, false, $context);

    // Nếu không thể lấy nội dung URL
    if ($content === false) {
        return false;
    }

    // Sử dụng libxml để phân tích XML, tắt báo lỗi của libxml ra ngoài
    libxml_use_internal_errors(true);
    $xml = @simplexml_load_string($content); // Thử phân tích chuỗi nội dung thành XML
    libxml_clear_errors(); // Xóa các lỗi đã được lưu trữ bởi libxml

    // Kiểm tra xem việc phân tích có thành công không VÀ có chứa các thẻ feed phổ biến không
    if ($xml !== false) {
        // Đây là kiểm tra heuristic cơ bản: tìm các thẻ gốc phổ biến của RSS/Atom
        return (isset($xml->channel) || isset($xml->entry) || $xml->getName() === 'rss' || $xml->getName() === 'feed');
    }

    // Phân tích XML thất bại
    return false;
}
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Hàm đọc giá trị hiện tại từ file config ---
function getCurrentValues($filePath, &$rssLink, &$rssLinkName, &$cacheDuration, &$translateContent) {
    // Kiểm tra file tồn tại và quyền đọc
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return "Lỗi: Không thể đọc file cấu hình '$filePath'. Vui lòng kiểm tra đường dẫn và quyền đọc.";
    }

    // Đọc nội dung file
    $content = file_get_contents($filePath);
    if ($content === false) {
        return "Lỗi: Không thể đọc nội dung file cấu hình '$filePath'.";
    }

    // Trích xuất giá trị RSS_LINK // Quan trọng, phải đúng để tải nội dung RSS
    if (preg_match("/^define\('RSS_LINK',\s*'(.*?)'\);/m", $content, $matches)) {
        $rssLink = $matches[1];
    } else {
        $rssLink = ''; // Mặc định là rỗng nếu không tìm thấy
    }

    // Trích xuất giá trị RSS_LINK_NAME // Lấy tên, mục đích hiển thị
    if (preg_match("/^define\('RSS_LINK_NAME',\s*'(.*?)'\);/m", $content, $matches)) {
        $rssLinkName = htmlspecialchars_decode($matches[1], ENT_QUOTES);
    } else {
        $rssLinkName = ''; // Mặc định là rỗng nếu không tìm thấy
    }

    // Trích xuất giá trị RSS_CACHE_DURATION // Tức là thời gian cache, tính theo tiếng, phải là số nguyên
    if (preg_match("/^define\('RSS_CACHE_DURATION',\s*(\d+)\);/m", $content, $matches)) {
        $cacheDuration = (int)$matches[1]; // Ép kiểu thành số nguyên
    } else {
        $cacheDuration = 6; // Giá trị mặc định nếu không tìm thấy
    }

    // Trích xuất giá trị RSS_TRANSLATE_CONTENT // Kiểm tra xem có dịch nội dung RSS hay không, chỉ true hoặc false
    if (preg_match("/^define\('RSS_TRANSLATE_CONTENT',\s*(true|false)\);/m", $content, $matches)) {
        // So sánh chuỗi 'true' hoặc 'false' để xác định giá trị boolean
        $translateContent = (strtolower($matches[1]) === 'true');
    } else {
        $translateContent = false; // Giá trị mặc định nếu không tìm thấy
    }

    return null; // Không có lỗi
}
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- Đọc giá trị hiện tại ban đầu ---
$errorMessage = getCurrentValues($configFilePath, $currentRssLink, $currentRssLinkName, $currentCacheDuration, $currentTranslateContent);

// --- Xử lý việc gửi form ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy giá trị đã gửi
    $newRssLink = isset($_POST['rss_link']) ? trim($_POST['rss_link']) : '';
    $newRssLinkName = isset($_POST['rss_link_name']) ? trim($_POST['rss_link_name']) : '';
    // *** MỚI: Lấy giá trị cache và translate ***
    $newCacheDurationInput = isset($_POST['rss_cache_duration']) ? trim($_POST['rss_cache_duration']) : '6'; // Mặc định là chuỗi '6' nếu thiếu
    $newTranslateContentInput = isset($_POST['rss_translate_content']) ? $_POST['rss_translate_content'] : 'false'; // Giá trị là 'true' hoặc 'false' từ select

    // Chuyển đổi giá trị input thành kiểu dữ liệu phù hợp
    $newCacheDuration = filter_var($newCacheDurationInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]); // Validate là số nguyên không âm
    $newTranslateContent = ($newTranslateContentInput === 'true'); // Chuyển 'true'/'false' thành boolean

    // Lấy nội dung để kiểm tra xem có thay đổi nào diễn ra hay không
    // Đảm bảo file config tồn tại và đọc được trước khi include/require
    if (file_exists($configFilePath) && is_readable($configFilePath)) {
         // Include config để lấy giá trị hằng số hiện tại cho việc so sánh
        require_once $configFilePath;

        // Kiểm tra xem có hằng số nào được định nghĩa chưa (đề phòng file config trống hoặc mới)
        $rssLinkDefined = defined('RSS_LINK');
        $rssLinkNameDefined = defined('RSS_LINK_NAME');
        $rssCacheDurationDefined = defined('RSS_CACHE_DURATION');
        $rssTranslateContentDefined = defined('RSS_TRANSLATE_CONTENT');

        // *** CẬP NHẬT: Kiểm tra thay đổi cho cả 4 trường ***
        $hasChanged = (
            ($rssLinkDefined && $newRssLink != RSS_LINK) || (!$rssLinkDefined && !empty($newRssLink)) ||
            ($rssLinkNameDefined && $newRssLinkName != RSS_LINK_NAME) || (!$rssLinkNameDefined && !empty($newRssLinkName)) ||
            ($rssCacheDurationDefined && $newCacheDuration !== RSS_CACHE_DURATION) || (!$rssCacheDurationDefined && $newCacheDuration !== 6) || // So với default nếu chưa defined
            ($rssTranslateContentDefined && $newTranslateContent !== RSS_TRANSLATE_CONTENT) || (!$rssTranslateContentDefined && $newTranslateContent !== false) // So với default nếu chưa defined
        );

    } else {
        // Nếu file config không đọc được, coi như chắc chắn có thay đổi (vì không thể so sánh)
        // Hoặc bạn có thể báo lỗi ngay tại đây
         $errorMessage = "Lỗi: Không thể đọc file cấu hình '$configFilePath' để kiểm tra thay đổi.";
         $hasChanged = false; // Ngăn không cho chạy logic cập nhật nếu không đọc được file
    }


    if ($hasChanged && empty($errorMessage)) { // Chỉ xử lý nếu có thay đổi VÀ chưa có lỗi đọc file config
        // --- BẮT ĐẦU: Validation ---
        $validationErrors = [];

        // 1. Kiểm tra quy tắc: Tên không được đặt khi Link trống
        if (empty($newRssLink) && !empty($newRssLinkName)) {
            $validationErrors[] = "Bạn không thể đặt tên cho Link RSS khi Link RSS đang để trống. Vui lòng nhập Link RSS hoặc xóa Tên gợi nhớ.";
        }

        // 2. Kiểm tra tính hợp lệ của Link RSS (nếu không trống)
        if (!empty($newRssLink)) {
            if (!isValidRssFeed($newRssLink)) {
                $validationErrors[] = "Link RSS được cung cấp ('" . htmlspecialchars($newRssLink) . "') không hợp lệ hoặc không thể truy cập/phân tích dưới dạng RSS/Atom feed.";
            }
        }

        // 3. *** MỚI: Kiểm tra tính hợp lệ của Cache Duration ***
        if ($newCacheDuration === false) { // filter_var trả về false nếu không hợp lệ
             $validationErrors[] = "Thời gian cache phải là một số nguyên không âm (lớn hơn hoặc bằng 0).";
             $newCacheDuration = $currentCacheDuration; // Đặt lại giá trị hiển thị là giá trị hiện tại nếu nhập sai
        }

        // (Không cần validation cho trường boolean vì nó đến từ select box với giá trị cố định 'true'/'false')

        // --- KẾT THÚC: Validation ---

        if (!empty($validationErrors)) {
            $errorMessage = "Lỗi:<br>" . implode("<br>", $validationErrors);
            // Giữ lại giá trị đã gửi để hiển thị lại trên form (kể cả giá trị lỗi)
            $currentRssLink = $newRssLink;
            $currentRssLinkName = $newRssLinkName;
            // Nếu cache duration không hợp lệ, $newCacheDuration đã được đặt lại ở trên
            $currentCacheDuration = ($newCacheDuration === false) ? $currentCacheDuration : $newCacheDuration;
             $currentTranslateContent = $newTranslateContent;
        } else {
            // --- BẮT ĐẦU: Cập nhật file ---
            // Đọc lại nội dung file cấu hình (phòng trường hợp nó đã thay đổi)
            if (!file_exists($configFilePath) || !is_readable($configFilePath)) {
                $errorMessage = "Lỗi: Không thể đọc file cấu hình '$configFilePath' để cập nhật.";
            } else {
                $content = file_get_contents($configFilePath);
                if ($content === false) {
                     $errorMessage = "Lỗi: Không thể đọc nội dung file cấu hình '$configFilePath' để cập nhật.";
                } else {
                    // Chuẩn bị giá trị mới để chèn
                    $escapedNewRssLink = str_replace("'", "\\'", $newRssLink);
                    $escapedNewRssLinkName = str_replace("'", "\\'", $newRssLinkName);
                    // *** MỚI: Giá trị cho cache và translate (không cần escape vì là số và boolean literal) ***
                    $valueCacheDuration = (int)$newCacheDuration; // Đảm bảo là số nguyên
                    $valueTranslateContent = $newTranslateContent ? 'true' : 'false'; // Chuỗi 'true' hoặc 'false'

                    // --- Thay thế hoặc Thêm các dòng define ---

                    // Hàm trợ giúp để thay thế hoặc thêm dòng define
                    function replaceOrAddDefine(&$content, $defineName, $defineValue) {
                        $pattern = "/^(define\('{$defineName}',\s*).*;.*$/m";
                        $replacement = "define('{$defineName}', {$defineValue});"; // Lưu ý: $defineValue cần được quote nếu là string

                        if (preg_match($pattern, $content)) {
                            // Nếu tìm thấy, thay thế
                            $content = preg_replace($pattern, $replacement, $content, 1);
                        } else {
                            // Nếu không tìm thấy, thêm vào cuối file (hoặc vị trí mong muốn)
                            // Đảm bảo có dấu xuống dòng nếu file không kết thúc bằng dấu xuống dòng
                            if (!empty($content) && substr($content, -1) !== "\n") {
                                $content .= "\n";
                            }
                             // Thêm thẻ đóng PHP nếu cần
                             if (strpos(trim($content), '<?php') === 0 && strpos($content, '?>') === false) {
                                 $content = rtrim($content) . "\n?>"; // Xóa khoảng trắng cuối rồi thêm 
                             }
                             // Thêm dòng define trước thẻ đóng PHP (nếu có) hoặc cuối file
                             $closingTagPos = strrpos($content, '?>');
                             if ($closingTagPos !== false) {
                                 $content = substr_replace($content, $replacement . "\n", $closingTagPos, 0);
                             } else {
                                 $content .= $replacement . "\n";
                             }
                        }
                    }

                    // Sử dụng hàm trợ giúp
                    replaceOrAddDefine($content, 'RSS_LINK', "'" . $escapedNewRssLink . "'");
                    replaceOrAddDefine($content, 'RSS_LINK_NAME', "'" . $escapedNewRssLinkName . "'");
                    replaceOrAddDefine($content, 'RSS_CACHE_DURATION', (string)$valueCacheDuration); // Giá trị số không cần dấu nháy
                    replaceOrAddDefine($content, 'RSS_TRANSLATE_CONTENT', $valueTranslateContent); // Giá trị boolean literal không cần dấu nháy

                    // Ghi nội dung đã cập nhật trở lại file
                    if (is_writable($configFilePath)) {
                        if (file_put_contents($configFilePath, $content, LOCK_EX) !== false) {
                            $successMessage = "Cập nhật cấu hình RSS thành công!";
                            // Đọc lại giá trị để hiển thị những giá trị đã cập nhật
                            $readErr = getCurrentValues($configFilePath, $currentRssLink, $currentRssLinkName, $currentCacheDuration, $currentTranslateContent);
                             if (!empty($readErr)) {
                                 $successMessage = "Cập nhật cấu hình RSS thành công! (Nhưng có lỗi khi đọc lại giá trị mới: $readErr)";
                                 // Giữ lại giá trị mà chúng ta *nghĩ* đã ghi vào form
                                 $currentRssLink = $newRssLink;
                                 $currentRssLinkName = $newRssLinkName;
                                 $currentCacheDuration = $newCacheDuration;
                                 $currentTranslateContent = $newTranslateContent;
                             }
                             $errorMessage = ''; // Xóa lỗi cũ nếu cập nhật thành công
                        } else {
                            $errorMessage = "Lỗi: Không thể ghi vào file cấu hình '$configFilePath'. Vui lòng kiểm tra quyền ghi của web server.";
                            // Giữ lại giá trị đã gửi để hiển thị lại
                            $currentRssLink = $newRssLink;
                            $currentRssLinkName = $newRssLinkName;
                            $currentCacheDuration = $newCacheDuration;
                            $currentTranslateContent = $newTranslateContent;
                        }
                    } else {
                         $errorMessage = "Lỗi: File cấu hình '$configFilePath' không có quyền ghi. Vui lòng kiểm tra quyền ghi của web server.";
                         // Giữ lại giá trị đã gửi để hiển thị lại
                         $currentRssLink = $newRssLink;
                         $currentRssLinkName = $newRssLinkName;
                         $currentCacheDuration = $newCacheDuration;
                         $currentTranslateContent = $newTranslateContent;
                    }
                }
            }
            // --- KẾT THÚC: Cập nhật file ---
        }

    } elseif (!$hasChanged && $_SERVER['REQUEST_METHOD'] === 'POST' && empty($errorMessage)) {
        // Chỉ hiển thị thông báo "Không có thay đổi" nếu không có lỗi nào khác xảy ra trước đó
        $errorMessage = "Không có thay đổi nào được phát hiện. Dữ liệu vẫn giữ nguyên.";
    }
}
// E  -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cấu hình RSS | silaTranslator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../images/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../images/apple-touch-icon.png">
    <style>
        body {
            font-family: "Be Vietnam Pro", 'Roboto', sans-serif;
            line-height: 1.6;
            padding: 20px;
            max-width: 600px;
            margin: 20px auto;
            border: 1px solid #ccc;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            margin-top: 10px;
        }
        form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        form input[type="url"],
        form input[type="text"],
        form input[type="number"], /* Thêm style cho number */
        form select {               /* Thêm style cho select */
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 3px;
            box-sizing: border-box; /* Bao gồm padding trong chiều rộng */
            background-color: #fff; /* Nền trắng cho select */
            font-family: inherit; /* Kế thừa font */
            font-size: inherit;   /* Kế thừa cỡ chữ */
        }
        form button {
            background-color: #007bff;
            font-family: "Be Vietnam Pro", 'Roboto', sans-serif;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 1em;
        }
        form button:hover {
            background-color: #0056b3;
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: left; /* Căn lề trái cho dễ đọc lỗi */
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            text-align: center; /* Thành công thì căn giữa */
        }
        .note {
            font-size: 0.9em;
            color: #666;
            margin-top: -10px;
            margin-bottom: 15px;
        }
        /* Căn chỉnh label và input/select trên cùng dòng cho dễ nhìn hơn */
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
             margin-bottom: 5px;
        }
        /* Thêm khoảng cách giữa các nhóm */
        .form-group + .form-group {
            margin-top: 20px;
        }

        /* === Kiểu cho Thanh Bên Trái Cố Định === */
        #sticky-left-sidebar {
            position: fixed; /* Quan trọng: Giữ cố định trên màn hình */
            right: 0;         /* Đặt ở cạnh tráiสุด */
            top: 50%;        /* Bắt đầu từ giữa chiều cao màn hình */
            transform: translateY(-50%); /* Căn giữa theo chiều dọc chính xác */
            width: 55px;     /* Chiều rộng của thanh bên */
            background-color: rgba(200, 200, 200, 0.5); /* Màu nền hơi mờ (tùy chỉnh) */
            padding: 10px 0;  /* Đệm trên dưới, không đệm trái phải */
            border-radius: 0 8px 8px 0; /* Bo tròn góc phải */
            box-shadow: 2px 0px 8px rgba(0, 0, 0, 0.15); /* Đổ bóng nhẹ bên phải */
            z-index: 999;   /* Đảm bảo nó nằm trên các phần tử khác (trừ modal/popup) */
            transition: background-color 0.3s ease; /* Hiệu ứng chuyển màu nền */
        }

        #sticky-left-sidebar:hover {
             background-color: rgba(222, 226, 230, 0.8); /* Đổi màu nền khi di chuột qua */
        }

        #sticky-left-sidebar ul {
            list-style: none; /* Bỏ dấu chấm đầu dòng */
            padding: 0;
            margin: 0;
            display: flex;          /* Sắp xếp các nút theo chiều dọc */
            flex-direction: column; /* Hướng dọc */
            align-items: center;   /* Căn giữa các nút theo chiều ngang */
        }

        #sticky-left-sidebar li {
            margin-bottom: 10px; /* Khoảng cách giữa các nút */
        }

        #sticky-left-sidebar li:last-child {
            margin-bottom: 0; /* Bỏ khoảng cách cho nút cuối cùng */
        }

        #sticky-left-sidebar a {
            display: flex;          /* Sử dụng flexbox để căn giữa icon/text */
            justify-content: center; /* Căn giữa nội dung theo chiều ngang */
            align-items: center;   /* Căn giữa nội dung theo chiều dọc */
            width: 40px;            /* Chiều rộng nút */
            height: 40px;           /* Chiều cao nút */
            background-color: #ffffff; /* Màu nền nút */
            color: #495057;          /* Màu icon/text */
            text-decoration: none;  /* Bỏ gạch chân link */
            font-size: 1.2em;        /* Kích thước icon/text (điều chỉnh nếu dùng icon font) */
            border-radius: 50%;    /* Làm cho nút tròn */
            border: 1px solid #dee2e6; /* Viền nhẹ cho nút */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); /* Bóng đổ nhẹ cho nút */
            transition: all 0.2s ease-in-out; /* Hiệu ứng chuyển đổi mượt mà */
        }

        #sticky-left-sidebar a:hover {
            background-color: #e9ecef; /* Màu nền khi di chuột */
            color: #212529;          /* Màu icon/text khi di chuột */
            transform: scale(1.1);   /* Phóng to nhẹ khi di chuột */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Tăng bóng đổ khi di chuột */
        }

        #sticky-left-sidebar a:active {
            transform: scale(1.05); /* Thu nhỏ lại một chút khi nhấn */
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); /* Giảm bóng đổ khi nhấn */
        }        
    </style>
</head>
<body>
    <!-- === Thanh Bên Trái Cố Định === -->
    <aside id="sticky-left-sidebar">
        <ul>
            <li>
                <a href="../index.php" title="Dịch trang web">
                    <svg width="24" height="24" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
                        <polygon 
                            points="25,2  30,18  48,18  34,30  40,48  25,38  10,48  16,30  2,18  20,18"
                            fill="#777" 
                        />
                    </svg>
                </a>
            </li>
            <li><a href="small_settings.php" title="Xóa một số nội dung quảng cáo còn sót lại">
                    <svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                      <title>Cài đặt Nhỏ</title>
                      <circle cx="10" cy="10" r="4" fill="currentColor"/>
                    </svg>
                </a>
            </li>            
        </ul>     
    </aside>
    <!-- === Kết Thúc Thanh Bên Trái === -->
    
    <h1>Cập nhật Cấu hình RSS</h1>
    <p>Sử dụng trang này để thiết lập nguồn cấp RSS bạn muốn theo dõi, thời gian làm mới dữ liệu và tùy chọn dịch tự động.</p>
    <!-- Bỏ phần giới thiệu RSS cũ nếu thấy không cần thiết -->
    <!-- <p>RSS feed (Really Simple Syndication) là một định dạng web feed...</p> -->
    <!-- <p>Lợi ích chính là giúp bạn theo dõi trang web yêu thích...</p> -->

    <?php if ($errorMessage): ?>
        <div class="message error"><?php echo $errorMessage; // Cho phép HTML trong lỗi validation ?></div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <div class="form-group">
            <label for="rss_link">Link RSS:</label>
            <input type="url" id="rss_link" name="rss_link" value="<?php echo htmlspecialchars($currentRssLink); ?>" placeholder="Để trống nếu không sử dụng">
            <p class="note">Nhập URL đầy đủ của RSS feed (ví dụ: <strong>https://news.google.com/rss?hl=en-US&gl=US&ceid=US:en</strong>). Để trống trường này nếu muốn tắt tính năng RSS.</p>
        </div>

        <div class="form-group">
            <label for="rss_link_name">Tên gợi nhớ cho Link RSS:</label>
            <input type="text" id="rss_link_name" name="rss_link_name" value="<?php echo htmlspecialchars($currentRssLinkName); ?>" placeholder="Để trống nếu không cần">
             <p class="note">Tên hiển thị để bạn dễ nhận biết (ví dụ: <strong>Google News</strong>). Không bắt buộc.</p>
        </div>

        <!-- *** MỚI: Trường Cache Duration *** -->
        <div class="form-group">
            <label for="rss_cache_duration">Thời gian cache RSS (giờ):</label>
            <input type="number" id="rss_cache_duration" name="rss_cache_duration" value="<?php echo htmlspecialchars($currentCacheDuration); ?>" min="0" max="72" required>
             <p class="note">Số giờ ứng dụng sẽ lưu trữ dữ liệu RSS trước khi tải lại từ nguồn. Mặc định là <strong>6</strong> giờ. Thời gian cache tối đa <strong>72</strong> tiếng. Nhập <strong>0</strong> để luôn tải mới (không khuyến khích).</p>
        </div>

        <!-- *** MỚI: Trường Translate Content *** -->
        <div class="form-group">
             <label for="rss_translate_content">Dịch nội dung RSS sang tiếng Việt?</label>
             <select id="rss_translate_content" name="rss_translate_content">
                 <option value="false" <?php echo !$currentTranslateContent ? 'selected' : ''; ?>>Không</option>
                 <option value="true" <?php echo $currentTranslateContent ? 'selected' : ''; ?>>Có (Tiêu đề và mô tả)</option>
             </select>
             <p class="note">Chọn 'Có' nếu bạn muốn ứng dụng tự động dịch tiêu đề và mô tả ngắn của tin RSS sang tiếng Việt. Mặc định là <strong>Không</strong>.</p>
             <p class="note"><span style="font-size: 2em;">⚠</span>️ Nếu bạn để 'Có', lần đầu tải <a href="../index.php">trang dịch web</a> nó sẽ mất một chút thời gian để dịch. Và hết thời gian cache RSS nó mới dịch lại. Hệ thống sử dụng API với mô hình tương tự kiểu dịch truy vấn của bạn để dịch thông tin RSS này.</p>
        </div>

        <div>
            <button type="submit">Lưu Thay Đổi</button>
        </div>
    </form>

</body>
</html>