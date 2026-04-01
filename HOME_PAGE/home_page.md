# HOME_PAGE Notes

## Phạm vi chính

- Màn hình tổng hợp dư nợ theo `POS / Xã`.
- File xử lý chính: `HOME_PAGE/home_page.php`.
- File SQL: `HOME_PAGE/home_page_sql.php`.
- File giao diện: `view/Style_home_page.php`.

## Quy ước đang dùng

- Giữ hiển thị tiếng Việt UTF-8.
- `home_page.php` cần xuất `Content-Type` theo `charset=UTF-8`.
- Font mặc định của màn hình là `Times New Roman`.
- Layout ưu tiên mở rộng sát hai bên, không bó hẹp phần nội dung chính.
- Cụm tóm tắt ngày báo cáo / tổng bản ghi / tổng KH nằm ngang ngay dưới tiêu đề hero; khối nguồn vốn nằm ở cột trái phía dưới dưới dạng chip chọn gọn để làm bộ lọc nhanh.
- Phần chương trình vay đang hiển thị dưới dạng donut chart ở khối trên bên phải, không dùng danh sách thanh ngang như trước.

## Hành vi hiện tại cần giữ

- `MAPOS` mặc định là `3400`.
- Ngày báo cáo mặc định khi mở màn hình là ngày hiện tại trừ `1` ngày.
- Bộ lọc nguồn vốn dùng tham số `source` với các giá trị `ALL`, `TW`, `DP`, hiển thị theo dạng chọn có dấu tích.
- Click vào nguồn vốn sẽ nạp lại toàn bộ số liệu theo đúng nguồn đang chọn.
- Có nút `Tải lại toàn nguồn` để quay về dữ liệu tổng hợp đầy đủ.
- Danh sách tổng hợp đang tách theo `POS` và `Xã`.
- Click vào từng dòng mở modal chi tiết theo chương trình vay.
- Với `POS`, tiêu đề modal ưu tiên lấy tên `PGD`; mã `POS` hiển thị ở dòng phụ.
- Dữ liệu chi tiết đang group theo đơn vị và chương trình vay ở tầng SQL, không dựng giả ở frontend.

## Lưu ý khi sửa tiếp

- Ưu tiên sửa trong đúng 3 file liên quan nêu trên.
- Nếu chỉnh CSS, kiểm tra lại desktop và mobile.
- Nếu đổi text giao diện, giữ nguyên tiếng Việt có dấu.
- Nếu đổi truy vấn, kiểm tra lại các tổng số ở thẻ thống kê, danh sách `POS / Xã`, donut chart, và modal chi tiết.