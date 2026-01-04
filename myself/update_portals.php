<?php
// --- Cấu hình ---
$configFilePath = __DIR__ . DIRECTORY_SEPARATOR . 'link_portal_config.php'; // Đường dẫn đến file config

$errorMessage = ''; // Biến chứa thông báo lỗi
$successMessage = ''; // Biến chứa thông báo thành công
$infoMessage = ''; // Biến chứa thông báo thông tin (VD: không có thay đổi)

$initialPortals = [ // Mảng lưu giá trị ban đầu ĐỌC TỪ FILE
    'PORTAL_ONE' => '',
    'PORTAL_TWO' => '',
    'PORTAL_THREE' => '',
];
$currentPortals = []; // Mảng giá trị HIỂN THỊ trên form (có thể là giá trị mới nhập nếu có lỗi)

// Danh sách khóa
$portalKeys = array_keys($initialPortals); // Lấy danh sách key

// --- Hàm đọc giá trị hiện tại từ file config (không thay đổi) ---
function getCurrentPortalValues($filePath, $keys) {
    $values = array_fill_keys($keys, '');
    if (!file_exists($filePath) || !is_readable($filePath)) {
        return ["Lỗi: Không thể đọc file cấu hình '$filePath'. Vui lòng kiểm tra đường dẫn và quyền đọc.", $values];
    }
    $content = file_get_contents($filePath);
    if ($content === false) {
        return ["Lỗi: Không thể đọc nội dung file cấu hình '$filePath'.", $values];
    }
    foreach ($keys as $key) {
        if (preg_match("/^define\('".preg_quote($key)."',\s*'(.*?)'\);/m", $content, $matches)) {
            $values[$key] = $matches[1];
        }
    }
    return [null, $values];
}

// --- Đọc giá trị hiện tại ban đầu TỪ FILE ---
// Lưu vào $initialPortals để so sánh sau này
// Cũng gán vào $currentPortals để hiển thị ban đầu
list($readError, $initialPortals) = getCurrentPortalValues($configFilePath, $portalKeys);
if ($readError) {
    $errorMessage = $readError; // Hiển thị lỗi đọc file ngay lập tức
}
// $currentPortals sẽ giữ giá trị hiển thị trên form
$currentPortals = $initialPortals; // Ban đầu, giá trị hiển thị = giá trị đọc từ file

// --- Xử lý việc gửi form ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPortals = [];
    $validationErrors = [];
    $allFieldsFilled = true;
    $errorMessage = ''; // Reset lỗi cho mỗi lần submit
    $successMessage = ''; // Reset thành công
    $infoMessage = ''; // Reset thông tin

    // 1. Lấy và kiểm tra dữ liệu đầu vào
    foreach ($portalKeys as $index => $key) {
        $fieldName = 'portal_' . strtolower(str_replace('PORTAL_', '', $key));
        $value = isset($_POST[$fieldName]) ? trim($_POST[$fieldName]) : '';
        $newPortals[$key] = $value; // Lưu giá trị MỚI NHẬP

        if (empty($value)) {
            $allFieldsFilled = false;
            $validationErrors[] = "Link Cổng thông tin " . ($index + 1) . " không được để trống.";
        } elseif (!filter_var($value, FILTER_VALIDATE_URL)) {
            $validationErrors[] = "Link Cổng thông tin " . ($index + 1) . " ('" . htmlspecialchars($value) . "') không phải là một địa chỉ URL hợp lệ.";
        }
    }

    // 2. Kiểm tra tổng hợp các điều kiện validation
    if (!$allFieldsFilled) {
         $errorMessage = "Lỗi: Tất cả các link cổng thông tin đều là bắt buộc. Vui lòng điền đầy đủ.";
    } elseif (!empty($validationErrors)) {
        $errorMessage = "Lỗi: Có một hoặc nhiều URL không hợp lệ. Vui lòng kiểm tra lại các link đã nhập.";
        $errorMessage .= "<br>- " . implode("<br>- ", $validationErrors);
    }

    // 3. Nếu không có lỗi VALIDATION -> Kiểm tra thay đổi và tiến hành cập nhật file
    if (empty($errorMessage)) {
        // *** KIỂM TRA THAY ĐỔI ***
        // So sánh mảng giá trị MỚI NHẬP ($newPortals) với mảng giá trị BAN ĐẦU đọc từ file ($initialPortals)
        if ($newPortals == $initialPortals) {
            $infoMessage = "Không có thay đổi nào được phát hiện. Dữ liệu vẫn giữ nguyên.";
            // Không làm gì cả, không cần ghi file
            // $currentPortals vẫn giữ giá trị $initialPortals (vì không có lỗi và không có thay đổi)
        } else {
            // *** CÓ THAY ĐỔI -> TIẾN HÀNH CẬP NHẬT FILE ***
            if (!file_exists($configFilePath) || !is_readable($configFilePath)) {
                $errorMessage = "Lỗi: Không thể đọc file cấu hình '$configFilePath' để cập nhật.";
                $currentPortals = $newPortals; // Giữ lại giá trị nhập khi có lỗi hệ thống
            } else {
                $content = file_get_contents($configFilePath);
                if ($content === false) {
                     $errorMessage = "Lỗi: Không thể đọc nội dung file cấu hình '$configFilePath' để cập nhật.";
                     $currentPortals = $newPortals; // Giữ lại giá trị nhập
                } else {
                    // Thay thế các dòng define
                    foreach ($newPortals as $key => $value) {
                        $escapedValue = str_replace("'", "\\'", $value);
                        $content = preg_replace(
                            "/^(define\('".preg_quote($key)."',\s*').*?('\);)/m",
                            "$1" . $escapedValue . "$2",
                            $content,
                            1
                        );
                    }

                    // Ghi lại file
                    if (is_writable($configFilePath)) {
                        if (file_put_contents($configFilePath, $content, LOCK_EX) !== false) {
                            $successMessage = "Cập nhật các link cổng thông tin thành công!";
                            // Đọc lại giá trị mới để hiển thị và cập nhật $initialPortals cho lần submit tiếp theo
                            list($readErr, $updatedValues) = getCurrentPortalValues($configFilePath, $portalKeys);
                            if ($readErr) {
                                $successMessage .= " (Lỗi khi đọc lại giá trị mới: $readErr)";
                                // Giữ giá trị vừa nhập thành công để hiển thị
                                $currentPortals = $newPortals;
                                // Không cập nhật initialPortals vì đọc lại lỗi
                            } else {
                                $currentPortals = $updatedValues; // Hiển thị giá trị mới nhất
                                $initialPortals = $updatedValues; // Cập nhật giá trị gốc cho lần so sánh tiếp theo
                            }
                            $errorMessage = ''; // Xóa lỗi nếu trước đó có lỗi đọc ban đầu
                        } else {
                            $errorMessage = "Lỗi: Không thể ghi vào file cấu hình '$configFilePath'. Vui lòng kiểm tra quyền ghi của web server.";
                             $currentPortals = $newPortals; // Giữ giá trị người dùng đã nhập
                        }
                    } else {
                         $errorMessage = "Lỗi: File cấu hình '$configFilePath' không có quyền ghi. Vui lòng kiểm tra quyền ghi của web server.";
                          $currentPortals = $newPortals; // Giữ giá trị người dùng đã nhập
                    }
                }
            }
        } // Kết thúc else (có thay đổi)
    } else {
        // Nếu có lỗi VALIDATION, giữ lại giá trị người dùng đã nhập trên form
        $currentPortals = $newPortals;
    }
} // Kết thúc if POST

// Lấy thông tin 3 đường link (cần require sau khi đã xử lý POST và có thể đã ghi file)
// Kiểm tra xem file config có tồn tại không TRƯỚC KHI require
if (file_exists($configFilePath)) {
    require_once $configFilePath;
} else {
    // Xử lý trường hợp file config không tồn tại khi hiển thị HTML
    // Có thể định nghĩa các hằng số mặc định hoặc hiển thị lỗi khác
    if (empty($errorMessage)) { // Chỉ hiển thị lỗi này nếu chưa có lỗi nào khác
        $errorMessage = "Lỗi nghiêm trọng: File cấu hình '$configFilePath' không tồn tại. Không thể hiển thị sidebar và form.";
    }
    // Định nghĩa hằng số rỗng để tránh lỗi PHP trong HTML sidebar
    if (!defined('PORTAL_ONE')) define('PORTAL_ONE', '#error-no-config');
    if (!defined('PORTAL_TWO')) define('PORTAL_TWO', '#error-no-config');
    if (!defined('PORTAL_THREE')) define('PORTAL_THREE', '#error-no-config');
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật Link Cổng thông tin | silaTranslator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../images/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../images/apple-touch-icon.png">
    <style>
        /* CSS không đổi, thêm kiểu cho .info */
        body { 
            font-family: "Be Vietnam Pro", 'Roboto', sans-serif; 
            line-height: 1.6; 
            padding: 20px; 
            max-width: 700px; 
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
        
        form input[type="url"] { 
            width: 100%; 
            padding: 8px; 
            margin-bottom: 15px; 
            border: 1px solid #ccc; 
            border-radius: 3px; 
            box-sizing: border-box; 
        }
        
        form button { 
            font-family: "Be Vietnam Pro", 'Roboto', sans-serif; 
            background-color: #007bff; 
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
            text-align: left; 
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
            text-align: center; 
        }
        
        /* Thêm kiểu cho thông báo info */
        .info { 
            background-color: #cfe2ff; 
            color: #052c65; 
            border: 1px solid #b6d4fe; 
            text-align: center; 
        }
        
        .note { 
            font-size: 0.9em; 
            color: #666; 
            margin-top: -10px; 
            margin-bottom: 15px; 
        }
        
        .required { 
            color: red; 
            font-weight: bold; 
        }
        
        /* CSS Sidebar không đổi */
        #sticky-left-sidebar { position: fixed; left: 0; top: 40%; transform: translateY(-50%); width: 55px; background-color: rgba(200, 200, 200, 0.5); padding: 10px 0; border-radius: 0 8px 8px 0; box-shadow: 2px 0px 8px rgba(0, 0, 0, 0.15); z-index: 999; transition: background-color 0.3s ease; }
        #sticky-left-sidebar:hover { background-color: rgba(222, 226, 230, 0.8); }
        #sticky-left-sidebar ul { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; align-items: center; }
        #sticky-left-sidebar li { margin-bottom: 10px; }
        #sticky-left-sidebar li:last-child { margin-bottom: 0; }
        #sticky-left-sidebar a { display: flex; justify-content: center; align-items: center; width: 40px; height: 40px; background-color: #ffffff; color: #495057; text-decoration: none; font-size: 1.2em; border-radius: 50%; border: 1px solid #dee2e6; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); transition: all 0.2s ease-in-out; }
        #sticky-left-sidebar a:hover { background-color: #e9ecef; color: #212529; transform: scale(1.1); box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        #sticky-left-sidebar a:active { transform: scale(1.05); box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); }
        @media (max-width: 850px) { #sticky-left-sidebar { display: none; } }
    </style>
</head>
<body>
    <!-- === Thanh Bên Trái Cố Định === -->
    <aside id="sticky-left-sidebar">
        <ul>
            <!-- Sử dụng defined() để kiểm tra hằng số tồn tại trước khi dùng -->
            <li>
                <a href="../index.php" title="Trang dịch bài">
                    <svg width="24" height="24" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
                      <polygon 
                        points="25,2  30,18  48,18  34,30  40,48  25,38  10,48  16,30  2,18  20,18"
                        fill="#777" 
                      />
                    </svg>
                </a>
            </li>
            <li>
                <a href="<?php echo defined('PORTAL_ONE') ? htmlspecialchars(PORTAL_ONE) : '#'; ?>" title="<?php echo defined('PORTAL_ONE') ? htmlspecialchars(PORTAL_ONE) : 'Lỗi config'; ?>" target="_blank">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#6C757D" xmlns="http://www.w3.org/2000/svg">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
                    </svg>
                </a>
            </li>
            <li>
                <a href="<?php echo defined('PORTAL_TWO') ? htmlspecialchars(PORTAL_TWO) : '#'; ?>" title="<?php echo defined('PORTAL_TWO') ? htmlspecialchars(PORTAL_TWO) : 'Lỗi config'; ?>" target="_blank">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#6C757D" xmlns="http://www.w3.org/2000/svg">

                    <polygon points="12,2 13.5,11.2 22,12 13.5,12.8 12,22 10.5,12.8 2,12 10.5,11.2" />
                    
                    <circle cx="16" cy="8" r="1.2"/>

                    <circle cx="16" cy="16" r="1.2"/>

                    <circle cx="8" cy="16" r="1.2"/>

                    <circle cx="8" cy="8" r="1.2"/>
                    </svg>
                </a>
            </li>
            <li>
                <a href="<?php echo defined('PORTAL_THREE') ? htmlspecialchars(PORTAL_THREE) : '#'; ?>" title="<?php echo defined('PORTAL_THREE') ? htmlspecialchars(PORTAL_THREE) : 'Lỗi config'; ?>" target="_blank">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="#6C757D" xmlns="http://www.w3.org/2000/svg">
                    <!-- Mũi tên chính, to, hướng lên trên -->
                    <path d="
                          M12 3   
                          L7 9      
                          L10.5 9   
                          L10.5 21  
                          L13.5 21  
                          L13.5 9   
                          L17 9     
                          L12 3     
                          Z
                          "/>

                    <polygon points="3,11 10,10.5 10,13 3,12.5" />

                    <polygon points="14,10.5 21,11 21,12.5 14,13" />
                    </svg>
                </a>
            </li>
        </ul>
    </aside>
    
    <h1>Cập nhật Link Cổng thông tin</h1>
    <p>Mục này đơn giản chỉ là cập nhật cho 3 đường link cố định cho thanh sidebar bên trái, giúp bạn dễ dàng truy cập các nguồn tin ưa thích.</p>

    <?php if ($errorMessage): ?>
        <div class="message error"><?php echo $errorMessage; ?></div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>

    <?php if ($infoMessage): // Hiển thị thông báo info ?>
        <div class="message info"><?php echo htmlspecialchars($infoMessage); ?></div>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        <?php
        // Sử dụng $currentPortals để hiển thị giá trị trên form
        foreach ($portalKeys as $index => $key):
            $fieldName = 'portal_' . strtolower(str_replace('PORTAL_', '', $key));
            $label = "Link Cổng thông tin " . ($index + 1) . " (" . $key . "):";
            // Lấy giá trị từ $currentPortals để hiển thị (đã bao gồm giá trị mới nếu có lỗi)
            $displayValue = isset($currentPortals[$key]) ? $currentPortals[$key] : '';
        ?>
        <div>
            <label for="<?php echo $fieldName; ?>"><?php echo $label; ?> <span class="required">*</span></label>
            <input type="url" id="<?php echo $fieldName; ?>" name="<?php echo $fieldName; ?>" value="<?php echo htmlspecialchars($displayValue); ?>" required placeholder="https://example-news-portal.com">
            <p class="note">Bắt buộc nhập, phải là một địa chỉ web hợp lệ.</p> <!-- Bỏ ghi chú http/https vì filter_var không bắt buộc -->
        </div>
        <?php endforeach; ?>

        <div>
            <button type="submit">Lưu Thay Đổi</button>
        </div>
    </form>
</body>
</html>