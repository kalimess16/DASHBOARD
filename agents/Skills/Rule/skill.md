# Dashboard Rules

## Nguyên tắc code
- Ưu tiên PHP rõ ràng, ngắn gọn, tách file chính, SQL helper và CSS theo đúng trách nhiệm.
- Luôn escape dữ liệu hiển thị HTML bằng `dashboard_html()` hoặc hàm tương đương.
- Truy vấn MySQL có tham số người dùng phải dùng prepared statement.
- Truy vấn Oracle phải validate tên bảng/bộ lọc trước khi nối vào SQL động.
- Không hard-code thêm thông tin nhạy cảm; ưu tiên biến môi trường hoặc file cấu hình riêng.
- File `.php`, `.css`, `.js`, `.sql`, `.md` phải lưu `UTF-8` không BOM.

## Nguyên tắc giao diện
- Layout chính dùng menu ngang cha - con; `HOME_PAGE/home_page.php` là màn hình mặc định và không đưa vào menu.
- Menu chính cần có khả năng thu gọn/mở lại để tiết kiệm chiều cao làm việc.
- Màu giao diện chính dùng palette mệnh Thủy: xanh đen, navy, xanh dương, cyan và xanh băng; chàm tím chỉ làm điểm nhấn nhẹ cho số 9; màu nóng chỉ dùng cho lỗi/cảnh báo.
- Giao diện tối giản, dễ đọc, không dùng nền trang trí nặng hoặc quá nhiều màu lệch tông.
- Module cũ giữ đường dẫn hiện tại nếu chưa có router mới, tránh làm hỏng iframe/include.

## Nguyên tắc bảo mật
- Giữ chặn IP nội bộ bằng `dashboard_chan_ip_khong_hop_le()` cho các entry quan trọng.
- Session dùng `dashboard_khoi_tao_phien()` để đồng nhất cookie path và thời hạn.
- Không echo trực tiếp giá trị từ request, DB, session nếu chưa escape.
- Không mở file đính kèm bằng đường dẫn raw từ request nếu chưa validate.
- Wrapper DB trong `DB/connect_DB.php` phải tiếp tục chặn SQL ghi trên host bảo vệ nếu chưa được cho phép.

## Nguyên tắc tài liệu và thư mục
- `agents/Skills/skill.md`: tài liệu tập trung của toàn bộ tính năng/module.
- `agents/Skills/Rule/skill.md`: rule code, UI, bảo mật và sắp xếp.
- Không tạo thêm `.md` riêng trong các thư mục chức năng như `HOME_PAGE`, `DGX`, `DB`, `FUNC_SHARE`, `USER-SYSTEM`, `CODE-ORCALE_SQL`.
- Khi cần cập nhật ghi chú module, cập nhật trực tiếp vào `agents/Skills/skill.md`.
- Chỉ giữ tài liệu gốc thật sự cần ở root nếu người dùng còn dùng như bảng yêu cầu; không đặt tài liệu module rải rác.