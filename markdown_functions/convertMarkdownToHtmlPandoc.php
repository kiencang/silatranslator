<?php

// --- Cấu hình (Giống như trước) ---
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__)); // quay trở lại thư mục cha, phải làm thế vì vị trí của file này
}
if (!defined('PANDOC_PATH')) {
    define('PANDOC_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'pandoc.exe');
}

/**
 * Chuyển đổi nội dung Markdown sang HTML bằng Pandoc.
 *
 * @param string $markdownContent Nội dung Markdown cần chuyển đổi.
 * @param string $inputFormat Định dạng Markdown đầu vào (quan trọng: phải khớp với định dạng đã xuất ra ở bước trước, ví dụ: 'gfm', 'commonmark').
 * @param array $extraOptions Mảng các tùy chọn dòng lệnh bổ sung cho Pandoc.
 *                             Ví dụ: ['--standalone', '--toc']
 * @return string|false Chuỗi HTML kết quả nếu thành công, false nếu có lỗi.
 */
function convertMarkdownToHtmlPandoc(string $markdownContent, string $inputFormat = 'gfm', array $extraOptions = []): string|false
{
    // 1. Kiểm tra Pandoc
    if (!file_exists(PANDOC_PATH) || !is_executable(PANDOC_PATH)) {
        error_log('Pandoc executable not found or not executable at: ' . PANDOC_PATH);
        return false;
    }

    // 2. Chuẩn bị các tùy chọn Pandoc mặc định cho chiều về
    $defaultOptions = [
        '-f', $inputFormat, // Định dạng đầu vào là Markdown (gfm, commonmark, markdown...) // **QUAN TRỌNG**: Phải khớp với định dạng '-t' bạn đã dùng khi chuyển từ HTML sang MD.
        '-t', 'html'       // Định dạng đầu ra: HTML5 (chuẩn hiện đại) // Lựa chọn khác: 'html' (thường là HTML4 transitional)
    ];

    // Kết hợp tùy chọn mặc định và tùy chọn bổ sung
    $options = array_merge($defaultOptions, $extraOptions);
    $optionsString = implode(' ', array_map('escapeshellarg', $options));

    // 3. Xây dựng command line
    $command = escapeshellcmd(PANDOC_PATH) . ' ' . $optionsString;

    // 4. Sử dụng proc_open (Tương tự hàm trước)
    $descriptorSpec = [
        0 => ['pipe', 'r'], // stdin
        1 => ['pipe', 'w'], // stdout
        2 => ['pipe', 'w']  // stderr
    ];

    $pipes = [];
    $process = proc_open($command, $descriptorSpec, $pipes);

    if (!is_resource($process)) {
        error_log('Failed to open process with command: ' . $command);
        return false;
    }

    // 5. Gửi dữ liệu Markdown vào stdin
    fwrite($pipes[0], $markdownContent);
    fclose($pipes[0]);

    // 6. Đọc kết quả HTML từ stdout
    $htmlOutput = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    // 7. Đọc lỗi từ stderr
    $errorOutput = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    // 8. Đóng process và kiểm tra exit code
    $exitCode = proc_close($process);

    // 9. Xử lý kết quả
    if ($exitCode === 0) {
        return $htmlOutput; // Trả về chuỗi HTML
    } else {
        error_log("Pandoc execution failed with exit code: $exitCode");
        error_log("Pandoc command: $command");
        error_log("Pandoc stderr: " . $errorOutput);
        // error_log("Pandoc input Markdown: " . $markdownContent); // Bỏ comment nếu cần debug
        return false;
    }
}
