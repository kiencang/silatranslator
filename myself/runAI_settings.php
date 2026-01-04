<?php
// File: myself/runAI_settings.php
// --- Cấu hình ---
$configFilePath = dirname(__DIR__) . '/myself/ai_temp_top_config.php'; // Đường dẫn đến file config, dirname để lấy thư mục cha

// --- Giá trị mặc định, dùng khi người dùng cần khôi phục mặc định, nó cũng là giá trị dự phòng khi file config bị lỗi ---
$defaultConfig = [
    'temperature' => 0.3,
    'topP' => 0.9,
];



// --- Biến lưu trạng thái ---
$message = '';
$messageType = ''; // 'success', 'error', 'info'
$currentConfig = $defaultConfig; // Bắt đầu với mặc định



// --- Đọc cấu hình hiện tại ---
if (file_exists($configFilePath)) {
    try {
        // Tạm thời dùng @ để chặn lỗi include nếu file có syntax error, sẽ bắt bằng Throwable
        @$loadedConfig = include($configFilePath); // Lấy thông tin cấu hình từ file

        if ($loadedConfig === false && !file_exists($configFilePath)) {
             // Trường hợp hiếm: file bị xóa giữa lúc kiểm tra file_exists và include
             throw new \Exception("File cấu hình đã biến mất.");
        }

        if (is_array($loadedConfig)) {
             $currentConfig = array_merge($defaultConfig, $loadedConfig); // $loadedConfig ghi đè lên $defaultConfig
        } else {
             // File tồn tại nhưng không trả về array hợp lệ (có thể trống, return sai kiểu, hoặc syntax error)
             $message = 'Cảnh báo: File cấu hình (`ai_temp_top_config.php`) không hợp lệ hoặc bị lỗi. Sử dụng cài đặt mặc định.';
             $messageType = 'error'; // Dùng error để người dùng chú ý
             // Giữ $currentConfig là $defaultConfig đã gán ban đầu
        }
    } catch (\Throwable $e) { // Bắt cả Error (ví dụ: ParseError) và Exception
        $message = 'Lỗi nghiêm trọng khi đọc file cấu hình: ' . $e->getMessage() . ". Sử dụng cài đặt mặc định.";
        $messageType = 'error';
        // Giữ $currentConfig là $defaultConfig
    }
} else {
     $message = 'Thông báo: File cấu hình (`ai_temp_top_config.php`) không tìm thấy, sử dụng giá trị mặc định. Lưu cài đặt sẽ tạo file mới.';
     $messageType = 'info'; // Dùng kiểu info vì đây không hẳn là lỗi
}



// --- Xử lý khi Form được gửi đi (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Xử lý Khôi phục Mặc định ---
    if (isset($_POST['reset_settings'])) {
        $newConfig = $defaultConfig; // Sử dụng trực tiếp mảng default

        // Chuẩn bị nội dung để ghi file (từ giá trị mặc định)
        $configContent = "<?php\n\n// File cấu hình AI (đã khôi phục mặc định)\n\nreturn " . var_export($newConfig, true) . ";\n";

        // Lấy các mặc định để Ghi vào file
        if (file_put_contents($configFilePath, $configContent, LOCK_EX)) {
            $message = 'Cài đặt mặc định đã được khôi phục và lưu thành công!';
            $messageType = 'success';
            $currentConfig = $newConfig; // Cập nhật cấu hình hiện tại trên trang
        } else {
            $message = 'Lỗi: Không thể ghi vào file cấu hình để khôi phục mặc định. Vui lòng kiểm tra quyền ghi.';
            $messageType = 'error';
            // $currentConfig không thay đổi nếu ghi lỗi
        }
        // Không cần làm gì thêm, đi thẳng tới hiển thị form
    }
    // --- Xử lý Lưu Cài Đặt Thông Thường ---
    else {
        // Bắt đầu với cấu hình hiện tại (có thể là từ file hoặc default)
        $newConfig = $currentConfig;
        $validationError = false; // Cờ kiểm tra lỗi validation

        // Validate temperature
        $temp = filter_input(INPUT_POST, 'temperature', FILTER_VALIDATE_FLOAT, [
            'options' => ['min_range' => 0.0, 'max_range' => 1.0]
        ]);

        // Kiểm tra cẩn thận: false là lỗi validate, null là không có input hoặc không phải float
        if ($temp === false || $temp === null) {
             $message = 'Lỗi: Temperature không hợp lệ (phải là số từ 0.0 đến 1.0).';
             $messageType = 'error';
             $validationError = true;
        } else {
            $newConfig['temperature'] = $temp; // Chỉ cập nhật nếu hợp lệ
        }

        // Validate topP (chỉ thực hiện nếu chưa có lỗi trước đó)
        if (!$validationError) {
             $topP = filter_input(INPUT_POST, 'topP', FILTER_VALIDATE_FLOAT, [
                 'options' => ['min_range' => 0.0, 'max_range' => 1.0]
             ]);

             if ($topP === false || $topP === null) {
                 $message = 'Lỗi: TopP không hợp lệ (phải là số từ 0.0 đến 1.0).';
                 $messageType = 'error';
                 $validationError = true;
             } else {
                 $newConfig['topP'] = $topP; // Chỉ cập nhật nếu hợp lệ
             }
        }
        
        // Nếu chưa có cập nhật gì mà nhấn lưu thì cũng thông báo để khách biết
        if ($newConfig['temperature'] == $loadedConfig['temperature'] && $newConfig['topP'] == $loadedConfig['topP']) {
                $message = 'Không có thay đổi nào được phát hiện. Dữ liệu vẫn giữ nguyên.';
                $messageType = 'info';            
        } else { // Khi các giá trị là khác thì tiến hành lưu
            // Chỉ lưu nếu không có lỗi validation
            if (!$validationError) {
                // Chuẩn bị nội dung để ghi vào file PHP
                $configContent = "<?php\n\n// File cấu hình AI \n\nreturn " . var_export($newConfig, true) . ";\n";

                // Ghi vào file
                if (file_put_contents($configFilePath, $configContent, LOCK_EX)) {
                    $message = 'Cài đặt đã được lưu thành công!';
                    $messageType = 'success';
                    $currentConfig = $newConfig; // Cập nhật cấu hình hiện tại để hiển thị
                } else {
                    $message = 'Lỗi: Không thể ghi vào file cấu hình. Vui lòng kiểm tra quyền ghi của thư mục.';
                    $messageType = 'error';
                    // $currentConfig không thay đổi nếu ghi lỗi
                }
            }
        }
        // Nếu có lỗi validation, $message và $messageType đã được đặt, không cần làm gì thêm
    }
} // Kết thúc if ($_SERVER['REQUEST_METHOD'] === 'POST')
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../images/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../images/apple-touch-icon.png">     
    <title>Cài đặt Tham số AI Gemini | silaTranslator</title>
    <link rel="stylesheet" href="../css/shared.css?v=4">
    <style>
        .container { 
            margin: auto; 
            padding: 15px 30px 25px 30px;
        }
        
        label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: bold; 
            color: #555; 
        }
        
        input[type="number"] { 
            width: 100%; 
            padding: 12px; 
            margin-bottom: 20px; 
            border: 1px solid #ccc; 
            border-radius: 4px; 
            box-sizing: border-box; 
            font-size: 1rem; 
            max-width: 150px; 
        }
        
        .form-group { 
            margin-bottom: 25px; 
        }
        
        /* CSS cho phần văn bản hỗ trợ */
        .help-text { 
            font-size: 0.9em; 
            color: #666; 
            margin-top: -15px; 
            margin-bottom: 15px; 
            display: block; 
        }

        /* CSS cho các nút */
        .form-actions {
            display: flex; /* Sử dụng flexbox để sắp xếp các nút */
            gap: 10px; /* Khoảng cách giữa các nút */
            margin-top: 20px;
        }
        
        /* Ẩn hiện thông tin phụ */
        
        #hide-show-container {
            display: none; /* Ẩn box tin tức mặc định */
        }
        
        /* Định dạng cho nút/link kích hoạt */
        .toggle-info-button {
            display: block; /* Chiếm cả dòng để dễ căn giữa */
            width: fit-content; /* Độ rộng vừa với nội dung */
            margin: -10px auto 10px auto; /* Căn giữa và tạo khoảng cách */
            padding: 8px 15px;
            font-size: 0.9em;
            font-weight: bold;
            color: #0066cc;
            background-color: transparent; /* Nền trong suốt */
            border: none; /* Bỏ viền */
            /* border-bottom: 1px dashed #0066cc;  Có thể thêm gạch chân nếu muốn */
            cursor: pointer;
            text-align: center;
            transition: color 0.2s ease;
        }

        .toggle-info-button:hover {
            color: #004c99;
            /* border-bottom-style: solid; */
        } 

        .help-text {width: 600px;}
    </style>
</head>
<body>
    <div class="container">
        <h1>Cài đặt Tham số AI Gemini</h1>
        
        <button type="button" id="toggle-info-button" class="toggle-info-button">
            Xem ý nghĩa của phần này ▼ 
        </button>         
        
        <p style="margin-top: 5px;" id="hide-show-container">
            Các tham số dưới đây có ảnh hưởng nhất định đến <strong>chất lượng bản dịch</strong>, nhưng nó không tuyến tính, nghĩa là không phải bạn cứ tăng hoặc giảm các tham số là chất lượng sẽ tăng hay giảm theo (hoặc ngược lại). 
            Dù sao, điều bạn cần nhớ là <a href="setting.php" target="_blank">model của AI</a> (cái bạn cũng có quyền thay đổi) mới là cái có tác động lớn nhất đến chất lượng bản dịch, tiếp đến là prompt (lời nhắc) và system instructions (hướng dẫn cho hệ thống). 
            Hiện <a href="../client_prompt/system_prompt.php" target="_blank">prompt & system instructions</a> cũng có khả năng tùy chỉnh, nhưng bạn nên thận trọng vì tính năng này hơi khó tối ưu (đừng lo, mặc định của hệ thống đã khá tốt rồi).
        </p>
        
        <?php if ($message): // Cách thêm class thông minh để CSS theo kiểu thông báo ?>
            <!-- ==== htmlspecialchars dùng để hiển thị thuần túy văn bản, các ký tự đặc biệt không bị hiểu nhầm là mã lệnh  === -->
            <div class="message <?php echo htmlspecialchars($messageType); ?>">
                <?php echo htmlspecialchars($message); // Xuất thông điệp ra màn hình ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); // Gửi lại chính form để xử lý dữ liệu ?>">
            <!-- Kiểu của cả 2 input đều là number với các giá trị min, max được thiết lập sẵn, tránh mất công validate nhiều, nhưng cái này phụ thuộc trình duyệt, tuy vậy đây là tính năng rất nhiều trình duyệt hỗ trợ. Có cần kiểm tra ở phía server? -->
            <div class="form-group">
                <label for="temperature">Temperature:</label>
                <input type="number" id="temperature" name="temperature" step="0.05" min="0.0" max="1.0" value="<?php echo htmlspecialchars($currentConfig['temperature']); ?>" required>
                <small class="help-text">Kiểm soát tính ngẫu nhiên. Giá trị thấp (gần 0) cho kết quả tập trung và nhất quán hơn, giá trị cao (gần 1) cho kết quả sáng tạo và đa dạng hơn. (Ví dụ: 0.2 - 0.8). Lưu ý: <strong>Hiện ứng dụng chỉ dùng Temperature là tham số chính của AI</strong>. Bạn nên để giá trị này thấp. Mặc định: <strong><?php echo $defaultConfig['temperature']; ?></strong></small>
            </div>

            <div class="form-group">
                <label for="topP">Top-P:</label>
                <input type="number" id="topP" name="topP" step="0.01" min="0.0" max="1.0" value="<?php echo htmlspecialchars($currentConfig['topP']); ?>" required>
                <small class="help-text">Chỉ xem xét các token có xác suất tích lũy đạt đến giá trị này. Giảm giá trị (ví dụ: 0.9) để hạn chế các lựa chọn ít có khả năng hơn. Thường dùng thay thế hoặc cùng với Temperature. (Ví dụ: 0.9 - 1.0). Hiện ứng dụng <strong>KHÔNG còn dùng Top-P để kiểm soát đầu ra của AI</strong>, mà chỉ dùng Temperature.</small>
            </div>

            <div class="form-actions">
                 <button type="submit" name="save_settings">Lưu Cài Đặt</button>
                 <button type="submit" name="reset_settings">Khôi Phục Mặc định</button>
                 <!-- Nút Khôi phục Mặc định mới -->
            </div>
        </form>
        
        <!-- ===================== Nút Tiện Ích Phụ ===================== -->
        <div class="utility-buttons">
            <a href="setting.php" class="utility-button">Chọn Model</a>
            <a href="small_settings.php" class="utility-button">Cài đặt Nhỏ</a>
            <a href="../index.php" class="utility-button">Dịch trang web</a>
            <a href="../translation_PDF_HTML.php" class="utility-button">Dịch PDF</a>
            <a href="model_config.php" class="utility-button">Thêm bớt Model</a>
        </div>
        <!-- ===================== KẾT THÚC Nút Tiện Ích Phụ ===================== -->          
    </div>
    
    <script>
        const toggleButton = document.getElementById('toggle-info-button');
        const newsContainer = document.getElementById('hide-show-container');

        // Kiểm tra xem nút và container có tồn tại không
        if (toggleButton && newsContainer) {
            toggleButton.addEventListener('click', function() {
                // Kiểm tra trạng thái hiện tại của container
                if (newsContainer.style.display === 'none' || newsContainer.style.display === '') {
                    // Nếu đang ẩn, thì hiện ra
                    newsContainer.style.display = 'block';
                    // Đổi nội dung nút/link thành "Ẩn tin tức" và mũi tên lên
                    toggleButton.innerHTML = 'Ẩn giải thích ý nghĩa ▲';
                } else {
                    // Nếu đang hiện, thì ẩn đi
                    newsContainer.style.display = 'none';
                    // Đổi nội dung nút/link trở lại ban đầu
                    toggleButton.innerHTML = 'Xem ý nghĩa của phần này ▼';
                }
            });
        }  
    </script>      
</body>
</html>