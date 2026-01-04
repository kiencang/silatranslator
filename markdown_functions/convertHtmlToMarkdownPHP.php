<?php

// Use statements cho thư viện và các thành phần cốt lõi
use League\HTMLToMarkdown\HtmlConverter;
use League\HTMLToMarkdown\Converter\TableConverter;
use League\HTMLToMarkdown\Converter\ConverterInterface;
use League\HTMLToMarkdown\ElementInterface;

// --- ĐỊNH NGHĨA CÁC CONVERTER TÙY CHỈNH ---

/**
 * Chuyển đổi thẻ <figure>.
 * Sử dụng getValue() để lấy nội dung con đã được xử lý.
 */
class FigureConverter implements ConverterInterface
{
    // --- Không cần Constructor và thuộc tính $htmlConverter nữa ---

    public function getSupportedTags(): array
    {
        return ['figure'];
    }

    public function convert(ElementInterface $element): string
    {
        // Lấy nội dung con đã được chuyển đổi sang Markdown từ thư viện
        $innerMarkdown = trim($element->getValue()); // <<< THAY ĐỔI CHÍNH
        // Đảm bảo có khoảng trắng xung quanh để tách biệt khối.
        return "\n\n" . $innerMarkdown . "\n\n";
    }
}

/**
 * Chuyển đổi thẻ <figcaption>.
 * Sử dụng getValue() để lấy nội dung con đã được xử lý.
 */
class FigcaptionConverter implements ConverterInterface
{
    // --- Không cần Constructor và thuộc tính $htmlConverter nữa ---

    public function getSupportedTags(): array
    {
        return ['figcaption'];
    }

    public function convert(ElementInterface $element): string
    {
        // Lấy nội dung con đã được chuyển đổi sang Markdown từ thư viện
        $innerMarkdown = trim($element->getValue()); // <<< THAY ĐỔI CHÍNH
        // Đảm bảo nó là một khối riêng biệt.
        return "\n\n" . $innerMarkdown . "\n\n";
    }
}

/**
 * Chuyển đổi thẻ <details>.
 * Sử dụng getValue() để lấy nội dung con đã được xử lý.
 */
class DetailsConverter implements ConverterInterface
{
    // --- Không cần Constructor và thuộc tính $htmlConverter nữa ---

    public function getSupportedTags(): array
    {
        return ['details'];
    }

    public function convert(ElementInterface $element): string
    {
        // Lấy nội dung con đã được chuyển đổi sang Markdown từ thư viện
        // SummaryConverter sẽ xử lý thẻ <summary> bên trong trước đó.
        $innerMarkdown = trim($element->getValue()); // <<< THAY ĐỔI CHÍNH
        // Đảm bảo khối này được tách biệt.
        return "\n\n" . $innerMarkdown . "\n\n";
    }
}

/**
 * Chuyển đổi thẻ <summary>.
 * Sử dụng getValue() để lấy nội dung con đã được xử lý.
 */
class SummaryConverter implements ConverterInterface
{
    // --- Không cần Constructor và thuộc tính $htmlConverter nữa ---

    public function getSupportedTags(): array
    {
        return ['summary'];
    }

    public function convert(ElementInterface $element): string
    {
        // Lấy nội dung con đã được chuyển đổi sang Markdown từ thư viện
        $innerMarkdown = trim($element->getValue()); // <<< THAY ĐỔI CHÍNH
        // Thêm ** để in đậm và \n để tách khỏi nội dung details phía sau.
        return '**' . $innerMarkdown . "**\n";
    }
}

// --- KẾT THÚC ĐỊNH NGHĨA CONVERTER ---


/**
 * Chuyển đổi HTML sang Markdown với cấu hình tối ưu cho việc dịch bằng AI.
 *
 * Hàm này sử dụng thư viện league/html-to-markdown với các tùy chỉnh để:
 * - Loại bỏ các thành phần không cần thiết (scripts, styles, noscript).
 * - Giữ lại cấu trúc ngữ nghĩa quan trọng (tiêu đề, danh sách, nhấn mạnh, link, ảnh).
 * - Hỗ trợ chuyển đổi các thẻ cấu trúc bổ sung sang Markdown thông qua các Converter tùy chỉnh:
 *     - `<table>` thành bảng GFM (Github Flavored Markdown).
 *     - `<figure>` và `<figcaption>` thành khối nội dung (ví dụ: ảnh) và đoạn văn bản chú thích tương ứng, cách nhau bởi dòng trống.
 *     - `<details>` và `<summary>` thành khối nội dung với phần `<summary>` được in đậm (`**text**`) để làm tiêu đề.
 * - Loại bỏ hoàn toàn các thẻ HTML không được hỗ trợ khác mà không có converter tương ứng (do tùy chọn 'strip_tags' => true).
 * - Đảm bảo định dạng Markdown nhất quán (kiểu header ATX, bold `**`, italic `_`, list `-`).
 * - Xử lý ngắt dòng cứng (`<br>`) thành hard break Markdown (hai dấu cách + newline hoặc chỉ newline tùy cấu hình 'hard_break').
 * - Cung cấp khả năng ghi đè các tùy chọn mặc định thông qua tham số `$customOptions`.
 *
 * @param string $htmlContent Nội dung HTML cần chuyển đổi. Nên đảm bảo mã hóa UTF-8.
 * @param array $customOptions Mảng tùy chọn bổ sung hoặc ghi đè lên các tùy chọn mặc định.
 *                             Xem tài liệu của League\HTMLToMarkdown\HtmlConverter và các Converter liên quan
 *                             để biết danh sách đầy đủ các tùy chọn có thể cấu hình.
 * @return string Nội dung Markdown đã được chuyển đổi và tối ưu hóa.
 *                Trả về chuỗi rỗng nếu `$htmlContent` rỗng hoặc chỉ chứa khoảng trắng sau khi trim.
 *
 * @see \League\HTMLToMarkdown\HtmlConverter
 * @see \League\HTMLToMarkdown\Converter\TableConverter
 * @see FigureConverter
 * @see FigcaptionConverter
 * @see DetailsConverter
 * @see SummaryConverter
 */
function convertHtmlToMarkdownPhp(string $htmlContent, array $customOptions = []): string
{
    if (empty(trim($htmlContent))) {
        return '';
    }

    // Giữ các tùy chọn cơ bản
    // --- Mảng tùy chọn mặc định cho HtmlConverter ---
    // Tham khảo tài liệu league/html-to-markdown để biết thêm chi tiết.
    $defaultOptions = [
        /**
         * Loại bỏ hoàn toàn các thẻ HTML được liệt kê và TOÀN BỘ nội dung bên trong chúng.
         * Danh sách các thẻ được phân tách bằng dấu cách.
         * Hữu ích để loại bỏ các phần tử không mong muốn như script, style trước khi xử lý.
         */
        'remove_nodes'      => 'script style noscript',

        /**
         * Xử lý các thẻ HTML KHÔNG có Converter tương ứng (không được hỗ trợ).
         * - true: Loại bỏ thẻ nhưng GIỮ LẠI nội dung văn bản bên trong thẻ đó.
         *         (Lưu ý: Sẽ không ảnh hưởng đến table, figure, figcaption, details, summary
         *          vì chúng ĐÃ CÓ Converter được thêm vào).
         * - false: Giữ nguyên cả thẻ không được hỗ trợ và nội dung của nó trong output.
         */
        'strip_tags'        => true,

        /**
         * Cách chuyển đổi thẻ <br>.
         * - true: Chuyển đổi thành một dấu ngắt dòng thực sự (`\n`) - theo kiểu GFM.
         * - false: Chuyển đổi thành hai dấu cách + dấu ngắt dòng (`  \n`) - theo kiểu Markdown truyền thống.
         */
        'hard_break'        => true,

        /**
         * Sử dụng cú pháp link tự động (autolink) <http://example.com> khi có thể.
         * - true: Sử dụng <url> nếu văn bản link giống hệt URL.
         * - false: Luôn sử dụng cú pháp đầy đủ [text](url), ngay cả khi text giống url.
         *          (false thường cho kết quả nhất quán hơn).
         */
        'use_autolinks'     => false,

        /**
         * Kiểu định dạng cho thẻ tiêu đề H1 và H2.
         * - 'atx': Sử dụng dấu thăng (# H1, ## H2).
         * - 'setext': Sử dụng gạch dưới (H1\n===, H2\n---).
         * (H3 trở xuống luôn dùng 'atx').
         */
        'header_style'      => 'atx',

        /**
         * Ký tự sử dụng cho thẻ in đậm (<strong>, <b>).
         * - '**': Sử dụng dấu sao kép (**bold**).
         * - '__': Sử dụng dấu gạch dưới kép (__bold__).
         */
        'bold_style'        => '**',

        /**
         * Ký tự sử dụng cho thẻ in nghiêng (<em>, <i>).
         * - '_': Sử dụng dấu gạch dưới đơn (_italic_).
         * - '*': Sử dụng dấu sao đơn (*italic*).
         */
        'italic_style'      => '_',

        /**
         * Ký tự đánh dấu cho các mục trong danh sách không có thứ tự (<ul><li>).
         * Các lựa chọn phổ biến: '-', '*', '+'.
         */
        'list_item_style'   => '-',

        /**
         * Ẩn các lỗi/cảnh báo PHP do trình phân tích cú pháp DOMDocument tạo ra khi gặp HTML không hợp lệ.
         * - true: Ẩn lỗi (hữu ích khi xử lý HTML từ nguồn không đáng tin cậy).
         * - false: Hiển thị lỗi (hữu ích khi debug HTML đầu vào).
         */
        'suppress_errors'   => true,
    ];

    $finalOptions = array_merge($defaultOptions, $customOptions);

    // Khởi tạo Converter chính với các tùy chọn
    $converter = new HtmlConverter($finalOptions);

    // Lấy môi trường để thêm các converter
    $environment = $converter->getEnvironment();

    // Thêm bộ chuyển đổi BẢNG
    $environment->addConverter(new TableConverter());

    // <<< THÊM CÁC BỘ CHUYỂN ĐỔI TÙY CHỈNH (KHÔNG CẦN INJECT $converter nữa) >>>
    $environment->addConverter(new FigureConverter());    // <<< Không inject
    $environment->addConverter(new FigcaptionConverter()); // <<< Không inject
    $environment->addConverter(new DetailsConverter());   // <<< Không inject
    $environment->addConverter(new SummaryConverter());   // <<< Không inject
    // <<< KẾT THÚC THÊM BỘ CHUYỂN ĐỔI TÙY CHỈNH >>>

    // Thực hiện chuyển đổi
    $markdownOutput = $converter->convert($htmlContent);

    // Làm sạch output cuối cùng
    $markdownOutput = trim($markdownOutput);
    $markdownOutput = preg_replace("/\n{3,}/", "\n\n", $markdownOutput);

    return $markdownOutput;
}