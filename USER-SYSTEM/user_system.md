# USER-SYSTEM Notes

## Mã hóa bắt buộc

- Tất cả file trong thư mục `USER-SYSTEM` phải lưu bằng `UTF-8` không BOM.
- Không chuyển file qua ANSI, Windows-1252, hoặc công cụ làm biến tiếng Việt thành chuỗi kiểu `Quáº£n lÃ½`.
- Khi sửa text tiếng Việt trong PHP/HTML/JS, luôn kiểm tra lại hiển thị trực tiếp trên web sau khi lưu.

## File chính

- `USER-SYSTEM/user_system.php`
  - Trang chính, xử lý list, detail, save, thông báo.
- `USER-SYSTEM/user_system_sql.php`
  - Chỉ chứa hàm SQL.
- `view/Style_user_system.php`
  - CSS cho giao diện `USER-SYSTEM`.

## Quy ước cho màn Chi tiết cán bộ

- Thuộc tính `giaoviec` dùng combobox `Y` / `N`.
- Khi load dữ liệu:
  - `giaoviec = null` hoặc rỗng thì hiển thị `N`.
  - Có giá trị khác rỗng thì chuẩn hóa về `Y` hoặc `N`.
- Khi insert/update:
  - Luôn bind `giaoviec` cùng với các trường còn lại.
  - Nếu người dùng chọn `N` thì lưu `giaoviec = null`; chỉ khi chọn `Y` mới lưu giá trị `Y`.

## Cách kiểm tra nhanh

1. Mở `USER-SYSTEM/user_system.php`.
2. Kiểm tra tất cả text tiếng Việt hiển thị đúng dấu.
3. Mở chi tiết một cán bộ có `giaoviec = null`, xác nhận form hiện `N`.
4. Thử thêm mới với `Y` và `N`.
5. Thử cập nhật bản ghi hiện có, xác nhận lưu thành công và load lại đúng giá trị.