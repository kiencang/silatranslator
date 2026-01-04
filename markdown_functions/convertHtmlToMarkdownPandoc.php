<?php
// --- Cấu hình đường dẫn ---
if (!defined('BASE_PATH')) {
    // Cung cấp giá trị mặc định hoặc báo lỗi nếu BASE_PATH chưa được định nghĩa
    define('BASE_PATH', dirname(__DIR__));
    // Hoặc: trigger_error('BASE_PATH constant is not defined.', E_USER_ERROR);
}

// Đường dẫn tới pandoc.exe (sử dụng hằng số đã cung cấp)
define('PANDOC_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'pandoc.exe');

/**
 * Chuyển đổi nội dung HTML sang Markdown bằng Pandoc, tối ưu cho dịch thuật.
 *
 * @param string $htmlContent Nội dung HTML cần chuyển đổi (có thể là fragment / html không hoàn chỉnh).
 * @param array $extraOptions Mảng các tùy chọn dòng lệnh bổ sung cho Pandoc.
 *                             Ví dụ: ['--markdown-headings=atx', '--wrap=preserve']
 * @return string|false Chuỗi Markdown kết quả nếu thành công, false nếu có lỗi.
 */
function convertHtmlToMarkdownPandoc(string $htmlContent, array $extraOptions = []): string|false
{
    // 1. Kiểm tra sự tồn tại của Pandoc executable
    if (!file_exists(PANDOC_PATH) || !is_executable(PANDOC_PATH)) {
        error_log('Pandoc executable not found or not executable at: ' . PANDOC_PATH);
        return false;
    }

    // 2. Chuẩn bị các tùy chọn Pandoc mặc định tối ưu cho dịch thuật
     // Lựa chọn khác: 'commonmark' (chuẩn hơn), 'markdown' (Pandoc's default), 'gfm' GitHub Flavored Markdown
    $defaultOptions = [
        '-f', 'html',               // Định dạng đầu vào là HTML
        '-t', 'gfm-raw_html',       // Định dạng đầu ra: gfm | GitHub Flavored Markdown (phổ biến, hỗ trợ tốt bảng, code block...) nhưng commonmark chặt và chuẩn hơn có thể phù hợp hơn với API dịch thuật                             
        '--strip-comments',         // Loại bỏ các comment HTML (<!-- ... -->) - làm sạch đầu vào
        '--wrap=none',              // Quan trọng: Không tự động ngắt dòng. Giúp API dịch xử lý các đoạn văn dài tốt hơn.
        '--markdown-headings=atx', // Sử dụng kiểu tiêu đề # H1, ## H2 (thường được ưa chuộng hơn Setext) - gfm/commonmark mặc định dùng cái này rồi
    ];

    // Kết hợp tùy chọn mặc định và tùy chọn bổ sung (ghi đè nếu trùng lặp)
    // Lưu ý: Cách xử lý ghi đè đơn giản, có thể cần logic phức tạp hơn nếu cần kiểm soát chặt chẽ
    $options = array_merge($defaultOptions, $extraOptions);

    // Ghép các options thành một chuỗi để dùng trong command line
    // Sử dụng escapeshellarg cho từng option để tăng bảo mật, mặc dù ở đây các option chủ yếu là cố định
    $optionsString = implode(' ', array_map('escapeshellarg', $options));

    // 3. Xây dựng command line
    // Sử dụng escapeshellcmd cho đường dẫn Pandoc đề phòng có ký tự đặc biệt
    $command = escapeshellcmd(PANDOC_PATH) . ' ' . $optionsString;

    // 4. Sử dụng proc_open để thực thi và giao tiếp qua pipes
    $descriptorSpec = [
        0 => ['pipe', 'r'], // stdin - để gửi HTML vào
        1 => ['pipe', 'w'], // stdout - để nhận Markdown ra
        2 => ['pipe', 'w']  // stderr - để bắt lỗi từ Pandoc
    ];

    $pipes = [];
    $process = proc_open($command, $descriptorSpec, $pipes);

    if (!is_resource($process)) {
        error_log('Failed to open process with command: ' . $command);
        return false;
    }

    // 5. Gửi dữ liệu HTML vào stdin của Pandoc
    fwrite($pipes[0], $htmlContent);
    fclose($pipes[0]); // Đóng stdin để Pandoc biết đã hết input

    // 6. Đọc kết quả Markdown từ stdout
    $markdownOutput = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    // 7. Đọc thông báo lỗi (nếu có) từ stderr
    $errorOutput = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    // 8. Đóng process và kiểm tra exit code
    $exitCode = proc_close($process);

    // 9. Xử lý kết quả
    if ($exitCode === 0) {
        // Thành công
        return $markdownOutput;
    } else {
        // Có lỗi xảy ra
        error_log("Pandoc execution failed with exit code: $exitCode");
        error_log("Pandoc command: $command");
        error_log("Pandoc stderr: " . $errorOutput);
        // Có thể ghi nhận cả $htmlContent nếu cần debug
        // error_log("Pandoc input HTML: " . $htmlContent);
        return false;
    }
}