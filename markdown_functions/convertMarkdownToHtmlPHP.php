<?php

// --- Use statements cho hàm thứ hai (convertMarkdownToHtmlAdvanced) ---
use League\CommonMark\Environment\Environment as CommonMarkEnvironment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\Attributes\AttributesExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\MarkdownConverter;
use League\CommonMark\Exception\MissingDependencyException;
// --- Hết phần use ---

/**
 * Chuyển đổi Markdown sang HTML với cấu hình nâng cao và bảo mật.
 *
 * @param string $markdown Chuỗi Markdown cần chuyển đổi.
 * @param array $options Các tùy chọn cấu hình:
 *   - 'use_gfm' (bool): Bật GitHub Flavored Markdown (bảng, gạch ngang, etc.). Mặc định: true.
 *   - 'use_attributes' (bool): Bật extension cho phép thêm thuộc tính HTML ({#id .class}). Mặc định: true.
 *   - 'use_footnotes' (bool): Bật extension chú thích cuối trang ([^1]). Mặc định: true.
 *   - 'use_external_links' (bool): Bật extension xử lý link ngoài (thêm rel="noopener noreferrer"). Mặc định: true.
 *   - 'html_input' (string): Cách xử lý HTML thô trong Markdown ('strip', 'allow', 'escape'). Mặc định: 'escape'.
 *                               'strip': Loại bỏ thẻ HTML.
 *                               'allow': Cho phép thẻ HTML (CẨN THẬN VỚI XSS NẾU INPUT KHÔNG ĐÁNG TIN CẬY).
 *                               'escape': Chuyển mã HTML thành thực thể (hiển thị dạng text). An toàn nhất.
 *   - 'allow_unsafe_links' (bool): Cho phép các URL không an toàn (javascript:, data:, etc.). Mặc định: false (AN TOÀN).
 *   - 'external_link_config' (array): Cấu hình riêng cho ExternalLinkExtension.
 *                                     Ví dụ: ['internal_hosts' => ['example.com'], 'open_in_new_window' => true]
 *   - 'environment_config' (array): Mảng cấu hình bổ sung cho Environment của CommonMark.
 *
 * @return string Chuỗi HTML đã được chuyển đổi.
 *
 * @throws InvalidArgumentException Nếu cấu hình không hợp lệ.
 * @throws MissingDependencyException Nếu một extension được yêu cầu nhưng chưa cài đặt.
 */
// --- Bước 3: Hàm convertMarkdownToHtmlAdvanced (GIỮ NGUYÊN) ---
function convertMarkdownToHtmlPhp(string $markdown, array $options = []): string
{
     $defaults = [
        'use_gfm'            => true,
        'use_attributes'     => true,
        'use_footnotes'      => true,
        'use_external_links' => true,
        'html_input'         => 'escape', // Giữ lại cài đặt an toàn này
        'allow_unsafe_links' => false,
        'external_link_config' => [
            'internal_hosts'     => [],
            'open_in_new_window' => true, // Link mở ra tab mới
            'html_class'         => 'external-link',
            'nofollow'           => 'external',
            'noopener'           => 'external',
            'noreferrer'         => 'external',
        ],
        'environment_config' => [],
    ];
    $config = array_merge($defaults, $options);

    if (!in_array($config['html_input'], ['strip', 'allow', 'escape'])) {
        throw new \InvalidArgumentException("Giá trị 'html_input' không hợp lệ.");
    }

    $environmentConfig = array_merge(
        [
            'html_input'         => $config['html_input'],
            'allow_unsafe_links' => $config['allow_unsafe_links'],
        ],
        $config['environment_config']
    );

    if ($config['use_external_links']) {
        $environmentConfig['external_link'] = $config['external_link_config'];
    }

    // Sử dụng alias 'CommonMarkEnvironment'
    $environment = new CommonMarkEnvironment($environmentConfig);
    $environment->addExtension(new CommonMarkCoreExtension());

    if ($config['use_gfm']) {
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
    }
    if ($config['use_attributes']) {
        if (!class_exists(AttributesExtension::class)) throw new MissingDependencyException("Cài đặt: composer require league/commonmark-ext-attributes");
        $environment->addExtension(new AttributesExtension());
    }
    if ($config['use_footnotes']) {
         if (!class_exists(FootnoteExtension::class)) throw new MissingDependencyException("Cài đặt: composer require league/commonmark-ext-footnotes");
        $environment->addExtension(new FootnoteExtension());
    }
    if ($config['use_external_links']) {
         if (!class_exists(ExternalLinkExtension::class)) throw new MissingDependencyException("Cài đặt: composer require league/commonmark-ext-external-link");
        $environment->addExtension(new ExternalLinkExtension());
    }

    $converter = new MarkdownConverter($environment);
    $html = $converter->convert($markdown)->getContent();

    return $html;
}