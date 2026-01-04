<?php
// File: myself/small_settings.php

// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- KHAI BÁO BIẾN VÀ ĐƯỜNG DẪN ---
define('CONFIG_FILE', __DIR__ . DIRECTORY_SEPARATOR . 'config.php'); // Đường dẫn đến file config.php
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// Kiểm tra xem máy tính của người dùng có phải là Windows 64bit hay không
$is64bit_os_wmic = false; // Gán cờ lúc đầu là false

// Kiểm tra xem có phải Windows không trước khi chạy lệnh wmic
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    // Sử dụng WMIC (Windows Management Instrumentation Command-line)
    // Lệnh này trả về trực tiếp "64-bit" hoặc "32-bit"
    // @ dùng để ẩn lỗi nếu lệnh không chạy được
    $output = @shell_exec('wmic os get OSArchitecture 2>&1'); 
    
    if ($output) {
        // Tìm chuỗi "64-bit" trong kết quả trả về
        if (stripos($output, '64-bit') !== false) {
            $is64bit_os_wmic = true; // Nếu tìm thấy nghĩa là Win 64 bit
        }
    } 
}
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// Dùng để kiểm tra xem máy tính của người dùng có sử dụng trình duyệt Chrome hoặc Firefox hay không?
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'functions' . DIRECTORY_SEPARATOR . 'isBrowserInstalledWindows.php'; 
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- GIÁ TRỊ MẶC ĐỊNH ---
// Lưu các giá trị mặc định vào một mảng để dễ quản lý và sử dụng khi reset (khôi phục lại các giá trị mặc định khi người dùng có yêu cầu)
// Quá trình điều chỉnh hệ thống có thể không như ý, khi đó khôi phục lại mặc định cung cấp các tùy chọn nhìn chung là tốt
// Người dùng luôn có thể điều chỉnh cấu hình, sau đó khôi phục lại nếu cần, điều đó giúp việc khám phá cấu hình được thoải mái hơn
$default_settings = [
    'CURL_TIMEOUT_FETCH' => 120, // time out cho việc chờ lấy nội dung html
    'CURL_TIMEOUT_API' => 1000, // time out cho việc tương tác với API AI, thời gian này cao là vì việc dịch có thể rất tốn thời gian, đặc biệt với nội dung dài & mô hình cao hoặc cấu hình phức tạp để có chất lượng dịch tốt hơn
    'MAX_INPUT_TOKENS_ALLOWED' => 64000, // Số lượng token tối đa đầu vào, vì là tác vụ dịch nên token đầu ra thường cao hơn 20, 30% token đầu vào
    'RECENT_TRANSLATIONS_NUMBER' => 10, // Hiển thị số lượng bài dịch gần đây, tối đa 10 để không làm giao diện trông kỳ quái
    'REMOVE_INLINE_HTML_TAGS' => false, // Có xóa các thẻ inline HTML hay không
    'USE_ADVANCED_READABILITY' => false, // Có sử dụng READABILITY nguyên bản từ Mozilla để làm phương pháp dự phòng hay không?
    'ONLY_ADVANCED_READABILITY' => true, // Có sử dụng READABILITY nguyên bản từ Mozilla làm phương pháp chính
    'USE_HTML_PURIFIER' => true, // Có sử dụng thư viện lọc & sửa HTML là PURIFIER hay không, thường là có
    'FETCH_HTML_WITH_PANTHER' => true, // Có sử dụng chương trình dự phòng lấy nội dung thông qua Panther hay không, mô phỏng thao tác duyệt web của người dùng
    'ONLY_FETCH_HTML_WITH_PANTHER' => false, // Ưu tiên Panther là trình lấy nội dung chính
    'USER_PREFERRED_BROWSER_PANTHER' => 'chrome', // Sử dụng trình duyệt nào // Mặc định nên để Chrome
    'USE_USER_PROMPT_SYSTEM' => false, // Có sử dụng prompt & SI tùy chỉnh của người dùng hay không
    'USE_HTML_TO_MARKDOWN_PANDOC' => false, // Có sử dụng Pandoc để chuyển đổi qua lại giữa HTML và Markdown hay không, mặc định dùng thư viện hoạt động khá ổn của PHP
    'ALL_HTML_TAGS' => false, // Có sử dụng kiểu dịch lấy toàn ộ thẻ HTML hay không?
    'SEARCH_ENGINE_GEMINI' => false, // Có bổ sung công cụ tìm kiếm vào chức năng phản hồi của Gemini không? Thường có ích cho các sự kiện gần đây
    'LEFT_SIDEBAR' => false, // Có hiển thị sidebar bên trái hay không
    'MULTI_LINGUAL' => false, // Có dịch đa ngữ hay không   
];
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// Biến hiện tại, khởi tạo bằng giá trị mặc định // Biến hiện tại dùng để hiển thị cấu hình ra giao diện người dùng
// Khởi tạo các giá trị mặc định phòng lỗi, bình thường giá trị này được lấy từ file lưu cấu hình config.php
$current_timeout_fetch = $default_settings['CURL_TIMEOUT_FETCH'];
$current_timeout_api = $default_settings['CURL_TIMEOUT_API'];
$current_max_tokens = $default_settings['MAX_INPUT_TOKENS_ALLOWED'];
$current_recent_translations_number = $default_settings['RECENT_TRANSLATIONS_NUMBER'];
$current_remove_inline = $default_settings['REMOVE_INLINE_HTML_TAGS'];
$current_use_advanced_readability = $default_settings['USE_ADVANCED_READABILITY'];
$current_only_advanced_readability = $default_settings['ONLY_ADVANCED_READABILITY'];
$current_use_html_purifier = $default_settings['USE_HTML_PURIFIER'];
$current_use_symfony_panther = $default_settings['FETCH_HTML_WITH_PANTHER'];
$current_only_use_symfony_panther = $default_settings['ONLY_FETCH_HTML_WITH_PANTHER'];
$current_use_preferred_browser_panther = $default_settings['USER_PREFERRED_BROWSER_PANTHER'];
$current_user_prompt_system = $default_settings['USE_USER_PROMPT_SYSTEM'];
$current_use_html_to_markdown_pandoc = $default_settings['USE_HTML_TO_MARKDOWN_PANDOC'];
$current_all_html_tags = $default_settings['ALL_HTML_TAGS'];
$current_search_engine_gemini = $default_settings['SEARCH_ENGINE_GEMINI'];
$current_left_sidebar = $default_settings['LEFT_SIDEBAR'];
$current_multi_lingual = $default_settings['MULTI_LINGUAL'];

$message = ''; // Thông điệp
$error = ''; // Thông báo lỗi
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- HÀM HỖ TRỢ ĐỌC GIÁ TRỊ DEFINE ---
/**
 * Đọc giá trị của một hằng số define() từ nội dung file bằng regex.
 * @param string $content Nội dung file config.
 * @param string $constantName Tên hằng số cần tìm.
 * @param string $type Kiểu dữ liệu mong đợi ('int', 'bool').
 * @param mixed $default Giá trị mặc định nếu không tìm thấy.
 * @return mixed Giá trị của hằng số hoặc giá trị mặc định.
 */
function getDefinedValue(string $content, string $constantName, string $type, $default = null) {
    $pattern = "/define\(\s*'" . preg_quote($constantName, '/') . "'\s*,\s*(.*?)\s*\);/i";
    
    if (preg_match($pattern, $content, $matches)) {
        $value = trim($matches[1]);
        if ($type === 'int') {
            $value = trim($value, "'\"");
            return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['default' => $default]]);
            
        } elseif ($type === 'bool') {
            if (strcasecmp($value, 'true') === 0) {
                return true;
            } elseif (strcasecmp($value, 'false') === 0) {
                return false;
            }
            // Nếu giá trị là 1 hoặc 0 (đôi khi người dùng sửa file config thủ công)
             elseif ($value === '1') return true;
             elseif ($value === '0') return false;
             
        } elseif ($type === 'string') {
            // Loại bỏ dấu nháy đơn hoặc kép ở đầu và cuối chuỗi
            return trim($value, "'\""); 
        }
        
        return $value;
    }
    
    return $default;
}
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- HÀM HỖ TRỢ CẬP NHẬT GIÁ TRỊ DEFINE ---
/**
 * Cập nhật giá trị của một hằng số define() trong nội dung file bằng regex.
 * @param string $content Nội dung file config.
 * @param string $constantName Tên hằng số cần cập nhật.
 * @param mixed $newValue Giá trị mới.
 * @param string $type Kiểu dữ liệu ('int', 'bool').
 * @return string Nội dung file đã được cập nhật.
 */
function updateDefineValue(string $content, string $constantName, $newValue, string $type): string {
    $pattern = "/(define\(\s*'" . preg_quote($constantName, '/') . "'\s*,\s*)(.*?)\s*(\);)/i";

    $replacementValue = '';
    if ($type === 'int') {
        $replacementValue = (int)$newValue;
    } elseif ($type === 'bool') {
        $replacementValue = $newValue ? 'true' : 'false';
    } elseif ($type === 'string') { 
        // Đảm bảo giá trị là chuỗi, escape dấu nháy đơn, và bọc trong dấu nháy đơn
        $replacementValue = "'" . addcslashes((string) $newValue, "'") . "'";
    } else {
         $replacementValue = "'" . addslashes((string)$newValue) . "'";
    }

    $newContent = preg_replace($pattern, '${1}' . $replacementValue . '${3}', $content, 1, $count);

    if ($count === 0) {
        // Nếu không tìm thấy define, có thể thêm vào cuối file (tùy chọn)
        // Ví dụ: $newContent .= "\ndefine('" . $constantName . "', " . $replacementValue . ");";
        // Hiện tại chỉ trả về nội dung gốc nếu không cập nhật được
        return $content;
    }
    return $newContent;
}
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- ĐỌC CÀI ĐẶT HIỆN TẠI TỪ config.php ---
if (file_exists(CONFIG_FILE)) {
    $config_content = file_get_contents(CONFIG_FILE);

    // Lấy từng giá trị hiện tại, nếu không đọc được thì dùng giá trị mặc định đã gán ở trên
    $current_timeout_fetch = getDefinedValue($config_content, 'CURL_TIMEOUT_FETCH', 'int', $current_timeout_fetch);
    $current_timeout_api = getDefinedValue($config_content, 'CURL_TIMEOUT_API', 'int', $current_timeout_api);
    $current_max_tokens = getDefinedValue($config_content, 'MAX_INPUT_TOKENS_ALLOWED', 'int', $current_max_tokens);
    $current_recent_translations_number = getDefinedValue($config_content, 'RECENT_TRANSLATIONS_NUMBER', 'int', $current_recent_translations_number);
    $current_remove_inline = getDefinedValue($config_content, 'REMOVE_INLINE_HTML_TAGS', 'bool', $current_remove_inline);
    $current_use_advanced_readability  = getDefinedValue($config_content, 'USE_ADVANCED_READABILITY', 'bool', $current_use_advanced_readability);
    $current_only_advanced_readability  = getDefinedValue($config_content, 'ONLY_ADVANCED_READABILITY', 'bool', $current_only_advanced_readability);
    $current_use_html_purifier  = getDefinedValue($config_content, 'USE_HTML_PURIFIER', 'bool', $current_use_html_purifier);
    $current_use_symfony_panther = getDefinedValue($config_content, 'FETCH_HTML_WITH_PANTHER', 'bool', $current_use_symfony_panther);
    $current_only_use_symfony_panther = getDefinedValue($config_content, 'ONLY_FETCH_HTML_WITH_PANTHER', 'bool', $current_only_use_symfony_panther);
    $current_use_preferred_browser_panther = getDefinedValue($config_content, 'USER_PREFERRED_BROWSER_PANTHER', 'string', $current_use_preferred_browser_panther); 
    $current_user_prompt_system = getDefinedValue($config_content, 'USE_USER_PROMPT_SYSTEM', 'bool', $current_user_prompt_system);
    $current_use_html_to_markdown_pandoc = getDefinedValue($config_content, 'USE_HTML_TO_MARKDOWN_PANDOC', 'bool', $current_use_html_to_markdown_pandoc);
    $current_all_html_tags = getDefinedValue($config_content, 'ALL_HTML_TAGS', 'bool', $current_all_html_tags);
    $current_search_engine_gemini = getDefinedValue($config_content, 'SEARCH_ENGINE_GEMINI', 'bool', $current_search_engine_gemini);
    $current_left_sidebar = getDefinedValue($config_content, 'LEFT_SIDEBAR', 'bool', $current_left_sidebar);
    $current_multi_lingual = getDefinedValue($config_content, 'MULTI_LINGUAL', 'bool', $current_multi_lingual);
} else {
    $error = "Lỗi: File config.php không tồn tại.";
    // Dù file không tồn tại, form vẫn hiển thị giá trị mặc định đã gán ở trên
}
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------



// -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
// --- XỬ LÝ FORM KHI SUBMIT (METHOD POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {

    // Kiểm tra xem nút nào được nhấn
    $is_reset_action = isset($_POST['reset_settings']); // Nút khôi phục lại các tùy chọn
    $is_save_action = isset($_POST['save_settings']); // Nút lưu các cài đặt

    // --- XỬ LÝ RESET VỀ MẶC ĐỊNH ---
    if ($is_reset_action) {
        if (!file_exists(CONFIG_FILE)) { // Kiểm tra sự tồn tại của file config.php
            $error = "Lỗi: File config.php không tồn tại để reset.";
        } 
        
        elseif (!is_writable(CONFIG_FILE)) { // Kiểm tra khả năng ghi của file
            $error = "Lỗi: File config.php không có quyền ghi. Vui lòng kiểm tra quyền truy cập file trên server.";
        } 
        
        else {
            $config_content_to_update = file_get_contents(CONFIG_FILE); // Đọc lại nội dung

            // Cập nhật từng giá trị về mặc định, hầu hết là int & bool, chỉ có một giá trị là string
            $config_content_to_update = updateDefineValue($config_content_to_update, 'CURL_TIMEOUT_FETCH', $default_settings['CURL_TIMEOUT_FETCH'], 'int');
            $config_content_to_update = updateDefineValue($config_content_to_update, 'CURL_TIMEOUT_API', $default_settings['CURL_TIMEOUT_API'], 'int');
            $config_content_to_update = updateDefineValue($config_content_to_update, 'MAX_INPUT_TOKENS_ALLOWED', $default_settings['MAX_INPUT_TOKENS_ALLOWED'], 'int');
            $config_content_to_update = updateDefineValue($config_content_to_update, 'RECENT_TRANSLATIONS_NUMBER', $default_settings['RECENT_TRANSLATIONS_NUMBER'], 'int');
            $config_content_to_update = updateDefineValue($config_content_to_update, 'REMOVE_INLINE_HTML_TAGS', $default_settings['REMOVE_INLINE_HTML_TAGS'], 'bool');
            $config_content_to_update = updateDefineValue($config_content_to_update, 'USE_ADVANCED_READABILITY', $default_settings['USE_ADVANCED_READABILITY'], 'bool');
            $config_content_to_update = updateDefineValue($config_content_to_update, 'ONLY_ADVANCED_READABILITY', $default_settings['ONLY_ADVANCED_READABILITY'], 'bool');
            $config_content_to_update = updateDefineValue($config_content_to_update, 'USE_HTML_PURIFIER', $default_settings['USE_HTML_PURIFIER'], 'bool');
            $config_content_to_update = updateDefineValue($config_content_to_update, 'FETCH_HTML_WITH_PANTHER', $default_settings['FETCH_HTML_WITH_PANTHER'], 'bool');
            $config_content_to_update = updateDefineValue($config_content_to_update, 'ONLY_FETCH_HTML_WITH_PANTHER', $default_settings['ONLY_FETCH_HTML_WITH_PANTHER'], 'bool');
            $config_content_to_update = updateDefineValue($config_content_to_update, 'USER_PREFERRED_BROWSER_PANTHER', $default_settings['USER_PREFERRED_BROWSER_PANTHER'], 'string');
            $config_content_to_update = updateDefineValue($config_content_to_update, 'USE_USER_PROMPT_SYSTEM', $default_settings['USE_USER_PROMPT_SYSTEM'], 'bool');
            $config_content_to_update = updateDefineValue($config_content_to_update, 'USE_HTML_TO_MARKDOWN_PANDOC', $default_settings['USE_HTML_TO_MARKDOWN_PANDOC'], 'bool');
            $config_content_to_update = updateDefineValue($config_content_to_update, 'ALL_HTML_TAGS', $default_settings['ALL_HTML_TAGS'], 'bool');
            $config_content_to_update = updateDefineValue($config_content_to_update, 'SEARCH_ENGINE_GEMINI', $default_settings['SEARCH_ENGINE_GEMINI'], 'bool');
            $config_content_to_update = updateDefineValue($config_content_to_update, 'LEFT_SIDEBAR', $default_settings['LEFT_SIDEBAR'], 'bool');
            $config_content_to_update = updateDefineValue($config_content_to_update, 'MULTI_LINGUAL', $default_settings['MULTI_LINGUAL'], 'bool');

            // Ghi nội dung đã cập nhật vào file config.php
            if (file_put_contents(CONFIG_FILE, $config_content_to_update) !== false) {
                $message = 'Cài đặt Nhỏ đã được khôi phục về mặc định thành công!';
                
                // Cập nhật lại biến hiển thị trên form sau khi reset thành công
                $current_timeout_fetch = $default_settings['CURL_TIMEOUT_FETCH'];
                $current_timeout_api = $default_settings['CURL_TIMEOUT_API'];
                $current_max_tokens = $default_settings['MAX_INPUT_TOKENS_ALLOWED'];
                $current_recent_translations_number = $default_settings['RECENT_TRANSLATIONS_NUMBER'];
                $current_remove_inline = $default_settings['REMOVE_INLINE_HTML_TAGS'];
                $current_use_advanced_readability = $default_settings['USE_ADVANCED_READABILITY'];
                $current_only_advanced_readability = $default_settings['ONLY_ADVANCED_READABILITY'];
                $current_use_html_purifier = $default_settings['USE_HTML_PURIFIER'];
                $current_use_symfony_panther = $default_settings['FETCH_HTML_WITH_PANTHER'];
                $current_only_use_symfony_panther = $default_settings['ONLY_FETCH_HTML_WITH_PANTHER'];
                $current_use_preferred_browser_panther = $default_settings['USER_PREFERRED_BROWSER_PANTHER'];
                $current_user_prompt_system = $default_settings['USE_USER_PROMPT_SYSTEM'];
                $current_use_html_to_markdown_pandoc = $default_settings['USE_HTML_TO_MARKDOWN_PANDOC'];
                $current_all_html_tags = $default_settings['ALL_HTML_TAGS']; 
                $current_search_engine_gemini = $default_settings['SEARCH_ENGINE_GEMINI'];
                $current_left_sidebar = $default_settings['LEFT_SIDEBAR'];
                $current_multi_lingual = $default_settings['MULTI_LINGUAL'];                               
            } 
            
            else {
                $error = "Lỗi: Không thể ghi vào file config.php để reset.";
            }
        }
    }
    
    // --- XỬ LÝ LƯU CÀI ĐẶT MỚI ---
    elseif ($is_save_action) {
        // Lấy dữ liệu từ form
        $new_timeout_fetch = filter_input(INPUT_POST, 'timeout_fetch', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $new_timeout_api = filter_input(INPUT_POST, 'timeout_api', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $new_max_tokens = filter_input(INPUT_POST, 'max_tokens', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        $new_recent_translations_number = filter_input(INPUT_POST, 'recent_translations_number', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $new_remove_inline = isset($_POST['remove_inline']);
        $new_use_advanced_readability = isset($_POST['use_advanced_readability']);
        $new_only_advanced_readability = isset($_POST['only_advanced_readability']);
        $new_use_html_purifier = isset($_POST['use_html_purifier']);
        $new_use_symfony_panther = isset($_POST['use_symfony_panther']);
        $new_only_use_symfony_panther = isset($_POST['only_use_symfony_panther']);
        $new_user_prompt_system = isset($_POST['user_prompt_system']);
        $new_use_html_to_markdown_pandoc = isset($_POST['use_html_to_markdown_pandoc']);
        $new_all_html_tags = isset($_POST['all_html_tags']);
        $new_search_engine_gemini = isset($_POST['search_engine_gemini']);
        $new_left_sidebar = isset($_POST['left_sidebar']);
        $new_multi_lingual = isset($_POST['multi_lingual']);
        
        // *** SỬA ĐỔI Ở ĐÂY: Lấy giá trị radio button ***
        $new_use_preferred_browser_panther = $default_settings['USER_PREFERRED_BROWSER_PANTHER']; // Mặc định phòng trường hợp lỗi
        
        if (isset($_POST['use_preferred_browser_panther'])) {
            $submitted_browser = $_POST['use_preferred_browser_panther'];
            // Chỉ chấp nhận 'chrome' hoặc 'firefox'
            if ($submitted_browser === 'chrome' || $submitted_browser === 'firefox') {
                $new_use_preferred_browser_panther = $submitted_browser;
            }
        }

        // (Tùy chọn) Logic reset nếu Panther bị tắt
        if (!$new_use_symfony_panther) {
            $new_use_preferred_browser_panther = $default_settings['USER_PREFERRED_BROWSER_PANTHER'];
        }

        // --- VALIDATION DỮ LIỆU ---
        $validation_passed = true; // Cờ kiểm tra validation, mặc định là true
        
        if ($new_timeout_fetch === false || $new_timeout_api === false || $new_max_tokens === false || $new_recent_translations_number === false) {
             $error = "Lỗi: Giá trị Timeout hoặc Max Tokens, Số lượng bài mới dịch phải là số nguyên hợp lệ.";
             $validation_passed = false; // Chuyển cờ
        }  
        
        // *** THÊM LOGIC VALIDATION MỚI CHO PANTHER VÀ TRÌNH DUYỆT ***
        elseif ($new_use_symfony_panther && ($new_use_preferred_browser_panther !== 'chrome' && $new_use_preferred_browser_panther !== 'firefox')) {
            $error = "Lỗi: Bạn đã chọn sử dụng Symfony Panther nhưng chưa chọn trình duyệt (Chrome hoặc Firefox). Vui lòng chọn một trình duyệt hoặc bỏ chọn tùy chọn Panther.";
            $validation_passed = false; // Đánh dấu validation thất bại
        }
        
        elseif (!file_exists(CONFIG_FILE)) {
            $error = "Lỗi: File config.php không tồn tại để cập nhật.";
            $validation_passed = false;
        } 
        
        elseif (!is_writable(CONFIG_FILE)) {
            $error = "Lỗi: File config.php không có quyền ghi. Vui lòng kiểm tra quyền truy cập file trên server.";
            $validation_passed = false;
        }
        
        if ($validation_passed) { // Chỉ thực hiện nếu không có lỗi validation nào
            // --- CẬP NHẬT FILE config.php ---
            $config_content_to_update = file_get_contents(CONFIG_FILE); // Đọc lại nội dung mới nhất
            
            // Kiểm tra xem có bất kỳ sự thay đổi nào không thì mới thực hiện lệnh ghi dữ liệu mới
            // Phải có ít nhất một sự thay đổi thì mới thực hiện lệnh ghi
            if 
            (
                $current_timeout_fetch !== $new_timeout_fetch ||
                $current_timeout_api !== $new_timeout_api ||
                $current_max_tokens !== $new_max_tokens ||
                $current_recent_translations_number !== $new_recent_translations_number ||
                $current_remove_inline !== $new_remove_inline ||
                $current_use_advanced_readability !== $new_use_advanced_readability ||
                $current_only_advanced_readability !== $new_only_advanced_readability ||
                $current_use_html_purifier  !== $new_use_html_purifier ||
                $current_use_symfony_panther !== $new_use_symfony_panther ||
                $current_only_use_symfony_panther !== $new_only_use_symfony_panther ||    
                $current_use_preferred_browser_panther !== $new_use_preferred_browser_panther || 
                $current_user_prompt_system !== $new_user_prompt_system ||
                $current_use_html_to_markdown_pandoc !== $new_use_html_to_markdown_pandoc ||
                $current_all_html_tags !== $new_all_html_tags ||
                $current_search_engine_gemini !== $new_search_engine_gemini ||
                $current_left_sidebar !== $new_left_sidebar ||
                $current_multi_lingual !== $new_multi_lingual                      
            )    
          
            {          
                // Cập nhật từng giá trị khi có sự khác biệt thông tin 
                $config_content_to_update = updateDefineValue($config_content_to_update, 'CURL_TIMEOUT_FETCH', $new_timeout_fetch, 'int');
                $config_content_to_update = updateDefineValue($config_content_to_update, 'CURL_TIMEOUT_API', $new_timeout_api, 'int');
                $config_content_to_update = updateDefineValue($config_content_to_update, 'MAX_INPUT_TOKENS_ALLOWED', $new_max_tokens, 'int');
                $config_content_to_update = updateDefineValue($config_content_to_update, 'RECENT_TRANSLATIONS_NUMBER', $new_recent_translations_number, 'int');
                $config_content_to_update = updateDefineValue($config_content_to_update, 'REMOVE_INLINE_HTML_TAGS', $new_remove_inline, 'bool');
                $config_content_to_update = updateDefineValue($config_content_to_update, 'USE_ADVANCED_READABILITY', $new_use_advanced_readability, 'bool');
                $config_content_to_update = updateDefineValue($config_content_to_update, 'ONLY_ADVANCED_READABILITY', $new_only_advanced_readability, 'bool');
                $config_content_to_update = updateDefineValue($config_content_to_update, 'USE_HTML_PURIFIER', $new_use_html_purifier, 'bool');
                $config_content_to_update = updateDefineValue($config_content_to_update, 'FETCH_HTML_WITH_PANTHER', $new_use_symfony_panther, 'bool');
                $config_content_to_update = updateDefineValue($config_content_to_update, 'ONLY_FETCH_HTML_WITH_PANTHER', $new_only_use_symfony_panther, 'bool');
                $config_content_to_update = updateDefineValue($config_content_to_update, 'USER_PREFERRED_BROWSER_PANTHER', $new_use_preferred_browser_panther, 'string');
                $config_content_to_update = updateDefineValue($config_content_to_update, 'USE_USER_PROMPT_SYSTEM', $new_user_prompt_system, 'bool');
                $config_content_to_update = updateDefineValue($config_content_to_update, 'USE_HTML_TO_MARKDOWN_PANDOC', $new_use_html_to_markdown_pandoc, 'bool');
                $config_content_to_update = updateDefineValue($config_content_to_update, 'ALL_HTML_TAGS', $new_all_html_tags, 'bool');
                $config_content_to_update = updateDefineValue($config_content_to_update, 'SEARCH_ENGINE_GEMINI', $new_search_engine_gemini, 'bool');
                $config_content_to_update = updateDefineValue($config_content_to_update, 'LEFT_SIDEBAR', $new_left_sidebar, 'bool');
                $config_content_to_update = updateDefineValue($config_content_to_update, 'MULTI_LINGUAL', $new_multi_lingual, 'bool');                 

                // Ghi nội dung đã cập nhật vào file config.php
                if (file_put_contents(CONFIG_FILE, $config_content_to_update) !== false) {
                    $message = 'Cài đặt Nhỏ đã được lưu thành công!';

                    // Xóa cache opcode (rất quan trọng) // Để nó không lưu vào cache những giá trị cũ
                    if (function_exists('opcache_invalidate')) {
                        opcache_invalidate(CONFIG_FILE, true);
                    } elseif (function_exists('apc_compile_file')) {
                        @apc_compile_file(CONFIG_FILE); // Thử với APC cũ hơn
                    } 

                    // Cập nhật lại biến hiển thị trên form sau khi lưu thành công
                    $current_timeout_fetch = $new_timeout_fetch;
                    $current_timeout_api = $new_timeout_api;
                    $current_max_tokens = $new_max_tokens;
                    $current_recent_translations_number = $new_recent_translations_number;
                    $current_remove_inline = $new_remove_inline;
                    $current_use_advanced_readability = $new_use_advanced_readability;
                    $current_only_advanced_readability = $new_only_advanced_readability;
                    $current_use_html_purifier = $new_use_html_purifier;
                    $current_use_symfony_panther = $new_use_symfony_panther;
                    $current_only_use_symfony_panther = $new_only_use_symfony_panther;
                    $current_use_preferred_browser_panther = $new_use_preferred_browser_panther;
                    $current_user_prompt_system = $new_user_prompt_system;
                    $current_use_html_to_markdown_pandoc = $new_use_html_to_markdown_pandoc;
                    $current_all_html_tags = $new_all_html_tags;
                    $current_search_engine_gemini = $new_search_engine_gemini;
                    $current_left_sidebar = $new_left_sidebar;
                    $current_multi_lingual = $new_multi_lingual;                   
                } 

                else {
                    $error = "Lỗi: Không thể ghi vào file config.php.";
                }
            } 
            
            else {
                // Nếu không có bất cứ sự khác biệt nào thì không cần thực hiện ghi dữ liệu, và thông báo cho người dùng biết điều đó
                $message = 'Bạn chưa thực hiện bất cứ thay đổi nào. Các cài đặt vẫn giữ nguyên.';
            }
        }
    }
}
// E -----------------------------------------------------------------------------------------------------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt Nhỏ | silaTranslator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="../images/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../images/favicon-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../images/apple-touch-icon.png">
    <link rel="stylesheet" href="../css/shared.css?v=2">
    <link rel="stylesheet" href="../css/small_settings.css?v=3">
    <style>
        .checkbox-wrapper {
            display: flex;
            align-items: center; /* Quan trọng nhất */
            gap: 5px; /* Khoảng cách giữa checkbox và label */
            margin-bottom: 5px; /* Ví dụ khoảng cách với <p> bên dưới */
        }
        
        .checkbox-wrapper input[type="checkbox"] {
            position: relative;
            top: -2px; /* Điều chỉnh cho đến khi thẳng hàng */
        }
        
        .radio-group {
          display: flex;
          align-items: center; /* Căn chỉnh theo chiều dọc */
          margin-left: 10px;
        }

        .radio-group input[type="radio"] {
            position: relative;
            top: -3px; /* Điều chỉnh cho đến khi thẳng hàng */
        }        
    </style>
</head>
<body>
    <!-- === Thanh Bên Trái Cố Định === -->
    <aside id="sticky-left-sidebar">
        <ul>
            <li>
                <a href="../index.php" title="Trang dịch web">
                    <svg width="24" height="24" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
                    <polygon 
                        points="25,2  30,18  48,18  34,30  40,48  25,38  10,48  16,30  2,18  20,18"
                        fill="#777" 
                        />
                    </svg>
                </a>
            </li>
            <li>
                <a href="../search.php" title="Tìm kiếm (từ khóa tiếng Việt chuyển thành từ khóa tiếng Anh)">
                    <svg width="24" height="24" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">

                      <circle 
                        cx="13" cy="13"
                        r="8"              
                        fill="#fff"    
                        stroke="currentColor" 
                        stroke-width="2"   
                      />

                      <line 
                        x1="18.3" y1="18.3" 
                        x2="25" y2="25"   
                        stroke="currentColor" 
                        stroke-width="3"   
                        stroke-linecap="round" 
                      />
                    </svg>
                </a>
            </li>
            <li>
                <a href="is_remove_ads_content.php" title="Xóa một số nội dung quảng cáo còn sót lại">
                    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                        <title>Xóa các thông tin quảng cáo còn lại trong bài</title>
                        <desc>Một biểu tượng SVG đơn sắc cho tấm bia bắn cung, sử dụng nét vẽ.</desc>

                        <!-- Tâm bia (bullseye) - tô đầy -->
                        <circle cx="50" cy="50" r="10" fill="#777" stroke="none" />

                        <!-- Các vòng bia (strokes) -->
                        <circle cx="50" cy="50" r="20" stroke-width="6" />
                        <circle cx="50" cy="50" r="32" stroke-width="6" />
                        <circle cx="50" cy="50" r="45" stroke-width="7" />
                    </svg>
                </a>
            </li>
            <li>
                <a href="update_RSS_link.php" title="Điều chỉnh dịch vụ RSS">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         width="24" height="24"
                         viewBox="0 0 24 24"
                         fill="none"
                         stroke="currentColor"
                         stroke-width="2"
                         stroke-linecap="round"
                         stroke-linejoin="round"
                         aria-labelledby="rss-feed-title-compact">

                        <!-- Điểm gốc (hơi dịch vào trong) -->
                        <circle cx="6" cy="18" r="1.5" fill="#555"></circle>

                        <!-- Các cung tròn đồng tâm với điểm gốc mới -->
                        <path d="M6 11a7 7 0 0 1 7 7"></path>  <!-- Cung nhỏ -->
                        <path d="M6 6a12 12 0 0 1 12 12"></path> <!-- Cung giữa -->
                        <path d="M6 1a17 17 0 0 1 17 17"></path> <!-- Cung lớn -->
                    </svg>
                </a>
            </li>            
        </ul>     
    </aside>
    <!-- === Kết Thúc Thanh Bên Trái === -->  
    
    <div class="container">
        <h1>Cài đặt Nhỏ</h1>
        
        <p style="margin-top: -15px;">Các mặc định dưới đây thường giúp ứng dụng hoạt động tốt cho đa số người dùng trong đa số trường hợp.
            Đừng điều chỉnh nếu bạn chưa hiểu rõ ý nghĩa của cài đặt.
            Tuy nhiên nếu bạn hiểu, việc điều chỉnh có <em>khả năng</em> (tức là không có lời hứa hẹn chắc chắn 100%) sẽ cho kết quả ưng ý hơn. Các tính năng nâng cao thường yêu cầu thời gian thực thi lâu hơn. 
            Sử dụng nút <strong>Khôi Phục Mặc Định</strong> ở dưới cùng nếu các điều chỉnh khiến chương trình bị lỗi.
        </p>
        
        <p>
            <?php // Thông báo cho người dùng biết họ có đang dùng Windows 64-bit hay không?
                if ($is64bit_os_wmic) {
                    echo "Chương trình kiểm tra thấy rằng máy tính của bạn đang dùng Windows 64-bit, và có khả năng bật các tùy chọn nâng cao phù hợp với hệ điều hành này (vui lòng kiểm tra thêm bằng phương pháp khác để chắc chắn máy bạn đang dùng Windows 64-bit).";
                } else {
                    echo "Chương trình kiểm tra thấy rằng máy tính của bạn KHÔNG dùng Windows 64-bit, và các tùy chọn liên quan đến yêu cầu hệ điều hành Windows 64-bit không thích hợp để bật.";
                }
            ?>
        </p>

        <?php if ($error): ?>
            <div class="message error"><?php echo nl2br(htmlspecialchars($error)); ?></div>
        <?php endif; ?>

        <?php if ($message && !$error): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (!file_exists(CONFIG_FILE) && empty($error) && $_SERVER['REQUEST_METHOD'] !== 'POST'): // Chỉ hiển thị lỗi này ban đầu nếu file không tồn tại ?>
             <div class="message error">Lỗi: File config.php không tồn tại. Không thể đọc hoặc lưu cài đặt. Form hiển thị giá trị mặc định.</div>
        <?php endif; ?>

        <?php // Luôn hiển thị form trừ khi có lỗi nghiêm trọng ban đầu về việc file không tồn tại ?>
        <?php if (empty($error) || ($error && strpos($error, 'config.php không tồn tại') === false) || $_SERVER['REQUEST_METHOD'] !== 'POST' ): ?>
        
        <form action="small_settings.php" method="post">

            <div class="setting-group">
                <label for="timeout_fetch">Timeout tải HTML (giây):</label>
                <input type="number" id="timeout_fetch" name="timeout_fetch" value="<?php echo htmlspecialchars($current_timeout_fetch); ?>" min="60" max="180" required>
                <p>Thời gian tối đa chờ đợi tải nội dung HTML. Một số website ở xa vị trí người dùng có thể cần nhiều thời gian hơn để lấy nội dung. Tối thiểu 60 giây. Tối đa 180 giây. Mặc định: <?php echo $default_settings['CURL_TIMEOUT_FETCH']; ?> giây.</p>
            </div>

            <div class="setting-group">
                <label for="timeout_api">Timeout gọi API (giây):</label>
                <input type="number" id="timeout_api" name="timeout_api" value="<?php echo htmlspecialchars($current_timeout_api); ?>" min="500" max="1200" required>
                <p><strong>Thời gian tối đa</strong> chờ đợi phản hồi từ API. Lưu ý là API cho nhiệm vụ dịch thuật mất khá nhiều thời gian để xử lý, nhất là với văn bản dài hoặc/và nội dung phức tạp, cài đặt dịch nâng cao. Tối thiểu 500 giây, tối đa 1200 giây. Mặc định: <?php echo $default_settings['CURL_TIMEOUT_API']; ?> giây.</p>
            </div>

            <div class="setting-group">
                <label for="max_tokens">Giới hạn Token đầu vào:</label>
                <input type="number" id="max_tokens" name="max_tokens" value="<?php echo htmlspecialchars($current_max_tokens); ?>" min="5000" max="100000" required>
                <p>Số lượng token tối đa cho nội dung gửi đến API. Ngưỡng cao nhất được phép là 100 ngàn token (nhưng bạn nên để mặc định thì tốt hơn, bắt buộc mới tăng ngưỡng này). Đối với nội dung quá dài ứng dụng sẽ chia đôi nó ra để gửi lần lượt đến API, điều đó có thể làm suy giảm chất lượng dịch vì AI bị mất cái nhìn toàn cảnh đối với nội dung. Tuy nhiên ứng dụng cố gắng khắc phục điều này bằng cách chia tách ở phần thích hợp, thay vì tách giữa dòng. 
                     Ngưỡng bắt đầu chia tách của ứng dụng là văn bản từ 20 ngàn từ đổ lên, đây là điều rất hiếm khi xảy ra với đa số nội dung trên web, vì hầu hết các bài viết dưới 10 ngàn từ. Ý là trong hầu hết trường hợp việc dịch sẽ diễn ra trọn vẹn, đầy đủ toàn văn.
                     Mặc định: <?php echo $default_settings['MAX_INPUT_TOKENS_ALLOWED']; ?> tokens.</p>
            </div>

            <div class="setting-group">
                <label for="recent_translations_number">Hiển thị số lượng bài mới dịch:</label>
                <input type="number" id="recent_translations_number" name="recent_translations_number" value="<?php echo htmlspecialchars($current_recent_translations_number); ?>" min="1" max="10" required>
                <p>Số lượng hiển thị các bài dịch gần đây ở trang dịch web hoặc dịch PDF. Mặc định: <?php echo $default_settings['RECENT_TRANSLATIONS_NUMBER']; ?>.</p>
            </div>
            
            <div class="setting-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="use_symfony_panther" name="use_symfony_panther" value="1" <?php if ($current_use_symfony_panther) echo ' checked'; ?>>
                    <label for="use_symfony_panther" class="checkbox-label">Dự phòng với Symfony/Panther để giảm tình trạng bị chặn truy cập</label>
                </div>
                <p>Đa số các website không gây khó khăn trong việc lấy nội dung thông qua phương pháp mặc định, nhưng cũng có một số đáng kể các website chặn.</p>
                <p>Tính năng này khi bật sẽ giúp tăng tối đa khả năng lấy được nội dung, nó sẽ tạo một lớp dự phòng nếu phương pháp truyền thống không lấy được.</p>
                <p>Chương trình sẽ dự phòng dùng trình duyệt Firefox hoặc Chrome để giả lập người dùng thực truy cập trang (chỉ trên hệ điều hành Windows 64-bit) để tự động lấy nội dung, qua đó hạn chế tối đa các website chặn. Mặc định: <?php echo $default_settings['FETCH_HTML_WITH_PANTHER'] ? 'Bật' : 'Tắt'; ?>.</p>
             
                    <!-- === KHỐI CHỌN TRÌNH DUYỆT - THÊM ID VÀ STYLE DISPLAY NONE === -->
                    <div class="browser-choice" id="panther_browser_options" style="display: none;">
                        <label>Chọn trình duyệt:</label>
                            <!-- Giả sử Chrome là mặc định nếu Panther được bật -->
                            <div class="radio-group">
                                <input type="radio" id="browser_firefox" name="use_preferred_browser_panther" value="firefox" <?php if ($current_use_preferred_browser_panther == "firefox") echo ' checked'; ?>>
                                <label for="browser_firefox" class="radio-label"><strong>Firefox</strong></label>
                            </div>
                            
                            <div class="radio-group"  style="margin-top: 5px;">
                                <input type="radio" id="browser_chrome" name="use_preferred_browser_panther" value="chrome" <?php if ($current_use_preferred_browser_panther == "chrome") echo ' checked'; ?>>
                                <label for="browser_chrome" class="radio-label"><strong>Chrome</strong></label>
                            </div>
                            <p>Chọn trình duyệt bạn muốn Panther sử dụng (yêu cầu đã cài đặt trình duyệt tương ứng). Bạn không cần cài cả 2 trình duyệt, bạn chỉ cần có một trong hai là dùng được tính năng này.</p>
                        <p>
                        <?php 
                            if (!$is64bit_os_wmic) {
                                echo "Máy tính của bạn KHÔNG phải Windows 64 bit nên phần tùy chọn này không phù hợp.";
                            } else {
                                if (!isBrowserInstalledWindows('chrome')) {
                                   echo "Hệ điều hành của bạn phù hợp, nhưng có vẻ bạn chưa cài trình duyệt Chrome.<br>";
                                } else {
                                    echo "Kiểm tra nhanh: Có vẻ máy tính của bạn đã cài trình duyệt Chrome.<br>";
                                }
                                
                                if (!isBrowserInstalledWindows('firefox')) {
                                   echo "Hệ điều hành của bạn phù hợp, nhưng có vẻ bạn chưa cài trình duyệt Firefox.";
                                } else {
                                    echo "Kiểm tra nhanh: Có vẻ máy tính của bạn đã cài trình duyệt Firefox.";
                                }                                
                            }
                        ?>
                        </p>
                     </div>
                     <!-- === KẾT THÚC KHỐI CHỌN TRÌNH DUYỆT === -->                 
            </div>
            
            <div class="setting-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="only_use_symfony_panther" name="only_use_symfony_panther" value="1" <?php if ($current_only_use_symfony_panther) echo ' checked'; ?>>
                    <label for="only_use_symfony_panther" class="checkbox-label">Để Symfony/Panther là chương trình chính để lấy nội dung</label>
                </div>
                <p>Symfony/Panther không còn là phương thức dự phòng, mà là phương thức chính để lấy nội dung trên website, bạn cần có sẵn trình duyệt Chrome để sử dụng phương thức này. Mặc định: <?php echo $default_settings['ONLY_FETCH_HTML_WITH_PANTHER'] ? 'Bật' : 'Tắt'; ?>.</p>
            </div>
            
            <div class="setting-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="use_advanced_readability" name="use_advanced_readability" value="1" <?php if ($current_use_advanced_readability) echo ' checked'; ?>>
                    <label for="use_advanced_readability" class="checkbox-label">Dự phòng với bộ lọc nội dung nâng cao của ReadabilityJS</label>
                </div>    
                <p>Bật để dự phòng phương thức mặc định lỗi thì chuyển sang phiên bản ReadabilityJS của Mozilla. Mặc định: <?php echo $default_settings['USE_ADVANCED_READABILITY'] ? 'Bật' : 'Tắt'; ?>.</p>
            </div>
            
            <div class="setting-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="only_advanced_readability" name="only_advanced_readability" value="1" <?php if ($current_only_advanced_readability) echo ' checked'; ?>>
                    <label for="only_advanced_readability" class="checkbox-label">Chỉ sử dụng bộ lọc nội dung nâng cao của ReadabilityJS</label>
                </div>    
                <p>Chỉ dùng ReadabilityJS của Mozilla, nó sẽ thành phương pháp chính, không còn là dự phòng nữa. Phương pháp mặc định của hệ thống có chất lượng tốt và nhanh, tuy nhiên với các nội dung phức tạp ReadabilityJS của Mozilla xử lý tốt hơn (nhưng sẽ tốn thời gian hơn). Tóm lại, nếu bạn có yêu cầu cao thì nên bật tùy chọn này. Mặc định: <?php echo $default_settings['ONLY_ADVANCED_READABILITY'] ? 'Bật' : 'Tắt'; ?>.</p>
            </div>              
            
            <div class="setting-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="use_html_purifier" name="use_html_purifier" value="1" <?php if ($current_use_html_purifier) echo ' checked'; ?>>
                    <label for="use_html_purifier" class="checkbox-label">Sử dụng bộ lọc HTMLPurifier</label>
                </div>    
                <p>Bộ lọc HTML trước khi xử lý, dùng để tăng độ sạch, hạn chế lỗi nội dung & tăng cường bảo mật. Mặc định: <?php echo $default_settings['USE_HTML_PURIFIER'] ? 'Bật' : 'Tắt'; ?>.</p>
            </div>  
            
            <div class="setting-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="use_html_to_markdown_pandoc" name="use_html_to_markdown_pandoc" value="1" <?php if ($current_use_html_to_markdown_pandoc) echo ' checked'; ?>>
                    <label for="use_html_to_markdown_pandoc" class="checkbox-label">Sử dụng bộ chuyển đổi HTML sang Markdown của Pandoc</label>
                </div>    
                <p>Mặc định sử dụng thư viện PHP (league/html-to-markdown) để chuyển đổi, nó có chất lượng tốt, nhưng Pandoc có thể sẽ xử lý tốt hơn với các nội dung HTML rất phức tạp. Tuy nhiên yêu cầu tương thích hệ điều hành, nó chỉ chạy được trên Windows 64-bit. Mặc định: <?php echo $default_settings['USE_HTML_TO_MARKDOWN_PANDOC'] ? 'Bật' : 'Tắt'; ?>.</p>
            </div>             
            
            <div class="setting-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="remove_inline" name="remove_inline" value="1" <?php if ($current_remove_inline) echo ' checked'; ?>>
                    <label for="remove_inline" class="checkbox-label">Loại bỏ các thẻ HTML inline [<code>'a', 'strong', 'b', 'em', 'i', 'span', 'u', 'mark'</code>]</label>
                </div>    
                <p>Chỉ áp dụng khi dùng kiểu dịch *Giữ nguyên định dạng* (Vì *Chỉ dịch văn bản* mặc định loại bỏ tất cả các thẻ này rồi). Có công dụng làm sạch thêm một chút cho nội dung cần dịch, nhưng văn bản sẽ mất định dạng (ví dụ không còn in đậm, in nghiêng, các đường link). Ảnh, bảng biểu nếu có vẫn được giữ nguyên. Có khả năng tiết kiệm tương đối nhiều chi phí token đầu vào & đầu ra trên các trang giàu định dạng kiểu này (ví dụ wikipedia.org). Nhìn chung Tắt sẽ tốt hơn trong đa số trường hợp. Mặc định: <?php echo $default_settings['REMOVE_INLINE_HTML_TAGS'] ? 'Bật' : 'Tắt'; ?>.</p>
            </div>            
            
            <div class="setting-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="all_html_tags" name="all_html_tags" value="1" <?php if ($current_all_html_tags) echo ' checked'; ?>>
                    <label for="all_html_tags" class="checkbox-label">Giữ lại toàn bộ thẻ HTML</label>
                </div> 
                <p>Trong phương pháp dịch *Giữ nguyên định dạng* ứng dụng mặc định dùng kiểu Markdown, đây là cách tốt nhất để cân bằng định dạng và chất lượng dịch. Tuy nhiên nếu bạn muốn ưu tiên định dạng hơn, hãy bật tùy chọn giữ lại toàn bộ định dạng (thẻ HTML) ở đây, trông nó sẽ giống website gốc nhất có thể, nhưng chất lượng dịch có thể bị giảm đi & tốn token hơn đáng kể. Nhìn chung nên Tắt để chất lượng dịch được tốt nhất có thể. Mặc định: <?php echo $default_settings['ALL_HTML_TAGS'] ? 'Bật' : 'Tắt'; ?>.</p>
            </div>
                
            <div class="setting-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="left_sidebar" name="left_sidebar" value="1" <?php if ($current_left_sidebar) echo ' checked'; ?>>
                    <label for="left_sidebar" class="checkbox-label">Bật sidebar trái trên trang dịch web</label>
                </div> 
                <p>Tính năng cho phép bạn truy cập nhanh một số trang web thường truy cập. Nó là dạng sibar bên trái trang dịch web, gồm 3 website mà bạn có thể tùy biến (thay đổi theo ý muốn). Mặc định: <?php echo $default_settings['LEFT_SIDEBAR'] ? 'Bật' : 'Tắt'; ?>.</p>
            </div>

            <div class="setting-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="multi_lingual" name="multi_lingual" value="1" <?php if ($current_multi_lingual) echo ' checked'; ?>>
                    <label for="multi_lingual" class="checkbox-label">Bật tùy chọn dịch sang tiếng Việt cho các ngôn ngữ khác (Trung, Nhật, Hàn)</label>
                </div> 
                <p>Bật tính năng này cho phép bạn thêm tùy chọn dịch từ tiếng Trung, Nhật, Hàn sang tiếng Việt. Khi bật, bạn sẽ thấy biểu tượng hình trái đất bên sidebar phải ở <a href="../index.php">trang dịch web</a>, click vào để thay đổi ngôn ngữ muốn dịch. Cần lưu ý là khả năng dịch sang tiếng Việt của những ngôn ngữ này không được tốt như tiếng Anh. Mặc định: <?php echo $default_settings['MULTI_LINGUAL'] ? 'Bật' : 'Tắt'; ?>.</p>
            </div>                
            
            <div class="setting-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="search_engine_gemini" name="search_engine_gemini" value="1" <?php if ($current_search_engine_gemini) echo ' checked'; ?>>
                    <label for="search_engine_gemini" class="checkbox-label">Bật tính năng Google Search cho AI</label>
                </div>
                <p>Nhìn chung AI vẫn cho chất lượng dịch rất cao mà không cần đến Google Search tích hợp vào. Tính năng này sẽ chỉ hữu ích nếu AI cần biết những sự kiện rất gần thời điểm hiện tại. Không nên bật tính năng này với những bài viết rất dài. Lý tưởng nhất chỉ nên bật với các bài có tính thời sự rất cao & có độ dài dưới 5000 từ. Về bản chất, tính năng này là RAG (Retrieval-Augmented Generation / Tạo Tăng Cường Truy Xuất), một dạng bổ sung thêm dữ liệu để có phản hồi tốt hơn so với chỉ dùng dữ liệu đã được đào tạo trước đó. Nhưng RAG khá tốn tài nguyên, và với dịch thuật không phải lúc nào nó cũng cần thiết. Mặc định: <?php echo $default_settings['SEARCH_ENGINE_GEMINI'] ? 'Bật' : 'Tắt'; ?>.</p>
            </div>             
            
            <div class="setting-group">
                <div class="checkbox-wrapper">
                    <input type="checkbox" id="user_prompt_system" name="user_prompt_system" value="1" <?php if ($current_user_prompt_system) echo ' checked'; ?>>
                    <label for="user_prompt_system" class="checkbox-label">Sử dụng prompt & systemInstructions của bạn thay vì mặc định hệ thống</label>
                </div>
                <p><span style="font-size: 2em;">⚠️</span> Sử dụng <a href="../client_prompt/system_prompt.php" target="_blank">prompt & systemInstructions tùy chỉnh</a> (do bạn tự viết) thay vì mẫu có sẵn của ứng dụng. Tính năng này chỉ phát huy tác dụng khi bạn đã up prompt & system Instructions của riêng bạn lên. Mặc định: <?php echo $default_settings['USE_USER_PROMPT_SYSTEM'] ? 'Bật' : 'Tắt'; ?>.</p>
            </div>            

            <div class="button-group">
                <button type="submit" name="save_settings">Lưu Cài Đặt Nhỏ</button>
                <button type="submit" name="reset_settings">Khôi Phục Mặc Định</button>
            </div>
        </form>
             
        <?php endif; ?>
         
        <!-- ===================== Nút Tiện Ích Phụ ===================== -->
        <div class="utility-buttons">
            <a href="setting.php" class="utility-button">Chọn Model</a>
            <a href="runAI_settings.php" class="utility-button">Chỉnh tham số API</a>
            <a href="../index.php" class="utility-button">Dịch trang web</a>
            <a href="../translation_PDF_HTML.php" class="utility-button">Dịch PDF</a>
            <a href="model_config.php" class="utility-button">Thêm bớt Model</a>
        </div>
        <!-- ===================== KẾT THÚC Nút Tiện Ích Phụ ===================== -->   
        
    </div> <!-- === Hết container === -->
    
    <!-- === ĐOẠN MÃ JAVASCRIPT === -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lấy tham chiếu đến các phần tử
            const pantherCheckbox = document.getElementById('use_symfony_panther');
            const browserOptionsDiv = document.getElementById('panther_browser_options');

            // Hàm để cập nhật hiển thị dựa trên trạng thái checkbox
            function toggleBrowserOptions() {
                // Kiểm tra xem các phần tử có tồn tại không trước khi truy cập thuộc tính
                if (pantherCheckbox && browserOptionsDiv) {
                    if (pantherCheckbox.checked) {
                        browserOptionsDiv.style.display = 'block'; // Hiển thị khối
                    } else {
                        browserOptionsDiv.style.display = 'none';  // Ẩn khối
                    }
                }
            }

            // Kiểm tra trạng thái ban đầu khi tải trang
            // (quan trọng nếu checkbox có thể được check sẵn từ PHP)
            toggleBrowserOptions();

            // Thêm listener cho sự kiện thay đổi trên checkbox
            // Kiểm tra pantherCheckbox tồn tại trước khi thêm listener
            if (pantherCheckbox) {
                pantherCheckbox.addEventListener('change', toggleBrowserOptions);
            }
        });
    </script>
    <!-- === KẾT THÚC ĐOẠN MÃ JAVASCRIPT === -->  

    <!-- === CHỌN CHECKBOX NÀY THÌ TẮT CHECKBOX KIA, LIÊN QUAN ĐẾN BỘ LỌC READABILITY === -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lấy tham chiếu đến hai checkbox bằng ID của chúng
            const useAdvancedCheckbox = document.getElementById('use_advanced_readability');
            const onlyAdvancedCheckbox = document.getElementById('only_advanced_readability');

            // Hàm xử lý khi một trong hai checkbox thay đổi trạng thái
            function handleCheckboxChange(changedCheckbox, otherCheckbox) {
                // Nếu checkbox hiện tại đang được chọn
                if (changedCheckbox.checked) {
                    // Bỏ chọn checkbox còn lại
                    otherCheckbox.checked = false;
                }
                // Lưu ý: Nếu checkbox hiện tại bị bỏ chọn, chúng ta không làm gì
                // với checkbox còn lại, cho phép cả hai đều không được chọn.
            }


            if (useAdvancedCheckbox && onlyAdvancedCheckbox) {
                // Thêm lắng nghe sự kiện 'change' cho checkbox thứ nhất
                useAdvancedCheckbox.addEventListener('change', function() {
                    handleCheckboxChange(useAdvancedCheckbox, onlyAdvancedCheckbox);
                });

                // Thêm lắng nghe sự kiện 'change' cho checkbox thứ hai
                onlyAdvancedCheckbox.addEventListener('change', function() {
                    handleCheckboxChange(onlyAdvancedCheckbox, useAdvancedCheckbox);
                });
            } else {
                console.error("Không tìm thấy một hoặc cả hai checkbox. Vui lòng kiểm tra lại ID.");
            }
        });
    </script>
    <!-- === KẾT THÚC ĐOẠN MÃ JAVASCRIPT === --> 
    
    <!-- === CHỌN CHECKBOX NÀY THÌ TẮT CHECKBOX KIA, LIÊN QUAN CHƯƠNG TRÌNH LẤY NỘI DUNG CỦA PANTHER === -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Lấy tham chiếu đến hai checkbox bằng ID của chúng
            const useAdvancedCheckboxSP = document.getElementById('use_symfony_panther');
            const onlyAdvancedCheckboxSP = document.getElementById('only_use_symfony_panther');

            // Hàm xử lý khi một trong hai checkbox thay đổi trạng thái
            function handleCheckboxChangeSP(changedCheckbox, otherCheckbox) {
                // Nếu checkbox hiện tại đang được chọn
                if (changedCheckbox.checked) {
                    // Bỏ chọn checkbox còn lại
                    otherCheckbox.checked = false;
                }
                // Lưu ý: Nếu checkbox hiện tại bị bỏ chọn, chúng ta không làm gì
                // với checkbox còn lại, cho phép cả hai đều không được chọn.
            }


            if (useAdvancedCheckboxSP && onlyAdvancedCheckboxSP) {
                // Thêm lắng nghe sự kiện 'change' cho checkbox thứ nhất
                useAdvancedCheckboxSP.addEventListener('change', function() {
                    handleCheckboxChangeSP(useAdvancedCheckboxSP, onlyAdvancedCheckboxSP);
                });

                // Thêm lắng nghe sự kiện 'change' cho checkbox thứ hai
                onlyAdvancedCheckboxSP.addEventListener('change', function() {
                    handleCheckboxChangeSP(onlyAdvancedCheckboxSP, useAdvancedCheckboxSP);
                });
            } else {
                console.error("Không tìm thấy một hoặc cả hai checkbox. Vui lòng kiểm tra lại ID.");
            }
        });
    </script>
    <!-- === KẾT THÚC ĐOẠN MÃ JAVASCRIPT === -->     
    
</body>
</html>