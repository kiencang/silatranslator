**Nhiệm vụ:** Dịch chính xác và tự nhiên **nội dung văn bản tiếng Anh sang tiếng Việt** trong đoạn mã Markdown dưới đây.

**Yêu cầu BẮT BUỘC (Không được vi phạm):**

1.  **Chỉ dịch các nội dung sau:**
    *   Văn bản thuần túy (plain text) không phải là cú pháp Markdown.
    *   Văn bản mô tả thay thế (alt text) trong cú pháp hình ảnh Markdown (`![alt text](url)`).

2.  **Bảo toàn Cấu trúc và Cú pháp:**
    *   Giữ nguyên 100% thứ tự của các yếu tố (văn bản, cú pháp Markdown).
    *   **Giữ nguyên vị trí chính xác** của tất cả các ký tự cú pháp Markdown so với văn bản mà chúng định dạng. Ví dụ: `**Bold text**` phải được dịch thành `**Văn bản đậm**`, không phải `** Văn bản đậm **` hay `**Văn bản** đậm`.
    *   Đảm bảo cấu trúc tổng thể (thứ tự tiêu đề, danh sách, đoạn văn, khối mã, v.v.) được bảo toàn.
	
3.  **Không dịch các nội dung sau:**
    *   Audio: nghĩa là nếu bạn gặp file audio, bạn không cần nghe file audio đó để dịch.
    *   Video: nghĩa là nếu bạn gặp file video, bạn không cần xem, nghe video đó để dịch.
    *   PDF: nghĩa là nếu bạn gặp file pdf (hoặc các định dạng như .doc, .docx), bạn không cần tải file đó để dịch.	

**Ghi nhớ**: Nhắc lại lần nữa, chỉ dịch văn bản thuần túy (plain text) không phải là cú pháp Markdown.

**Ưu tiên Chất lượng Dịch thuật (Rất quan trọng):**

    *   Áp dụng tất cả các nguyên tắc dịch thuật từ Hướng dẫn Hệ thống (`systemInstruction`).
    *   **ĐẶC BIỆT CHÚ TRỌNG:** Đảm bảo bản dịch tiếng Việt **NGHE TỰ NHIÊN VÀ LƯU LOÁT**, ngay cả khi câu bị ngắt bởi các định dạng Markdown inline (như `**`, `*`, ` `` `, `[]()`). Hãy đọc thầm cả câu hoàn chỉnh để chắc chắn nó mạch lạc và truyền tải đúng ý nghĩa.
    *   **Nguyên tắc Ưu tiên khi Xung đột:**
        1.  **Bảo toàn Cú pháp Markdown (Ưu tiên 1):** Yêu cầu về bảo toàn cú pháp Markdown là **không thể thay đổi**.
        2.  **Chính xác & Ý nghĩa (Ưu tiên 2):** Bản dịch phải truyền tải đúng ý nghĩa gốc.
        3.  **Tiếng Việt tự nhiên (Ưu tiên 3):** Trong giới hạn của hai ưu tiên trên, hãy làm cho bản dịch tự nhiên và trôi chảy nhất có thể.

**Định dạng Output:** **Chỉ trả về đoạn mã Markdown đã được dịch.** Không thêm bất kỳ lời giải thích, ghi chú hay văn bản nào khác vào phần trả lời.

---
**BẮT ĐẦU NỘI DUNG MARKDOWN CẦN DỊCH:**