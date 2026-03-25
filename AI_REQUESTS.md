# AI Request Board

File này là nơi nhập yêu cầu để Codex xử lý.

## Cách dùng

Khi muốn mình làm việc theo file này, bạn chỉ cần nhắn một câu như:

`Hãy làm theo AI_REQUESTS.md`

## Quy tắc Codex phải làm trước

1. Đọc các file `.md` ở thư mục gốc trước.
2. Nếu thư mục đang được yêu cầu có file `.md` riêng, đọc tiếp các file đó trước khi sửa code.
3. Bỏ qua `vendor/` nếu yêu cầu không liên quan đến thư viện.
4. Chỉ xử lý các mục có `Trạng thái: TODO`.
5. Ưu tiên mục có `Ưu tiên: Cao`.
6. Sau khi làm xong, cập nhật lại trạng thái trong phần trả lời cho người dùng.

## Cách bạn nhập yêu cầu

- Mỗi yêu cầu đặt vào đúng thư mục liên quan.
- Nếu có nhiều việc, bạn tạo nhiều khối trong cùng một thư mục.
- Nếu có thư mục mới, bạn tự thêm một mục mới theo đúng mẫu bên dưới.
- Nếu việc liên quan file gốc ở ngoài các thư mục con, dùng mục `ROOT`.

## Mẫu chung

```md
### Task
- Trạng thái: TODO
- Ưu tiên: Cao / Trung bình / Thấp
- File liên quan:
- Mục tiêu:
- Yêu cầu chi tiết:
- Không được đụng vào:
- Kết quả mong muốn:
- Cách kiểm tra:
- Ghi chú thêm:
```

---

## ROOT

### Task
- Trạng thái:
- Ưu tiên:
- File liên quan:
- Mục tiêu:
- Yêu cầu chi tiết:
- Không được đụng vào:
- Kết quả mong muốn:
- Cách kiểm tra:
- Ghi chú thêm:

## CODE-ORCALE_SQL

### Task
- Trạng thái:
- Ưu tiên:
- File liên quan:
- Mục tiêu:
- Yêu cầu chi tiết:
- Không được đụng vào:
- Kết quả mong muốn:
- Cách kiểm tra:
- Ghi chú thêm:

## DB

### Task
- Trạng thái: 
- Ưu tiên: 
- File liên quan: CONNECT_DB.PHP
- Mục tiêu: xử lý lỗi: Chua ket noi duoc Oracle. Kiem tra OCI8 va thong tin ket noi trong DB/connect_DB.php.
- Yêu cầu chi tiết: khắc phục lỗi Chua ket noi duoc Oracle. Kiem tra OCI8 va thong tin ket noi trong DB/connect_DB.php.
- Không được đụng vào: các file không liên quan
- Kết quả mong muốn: có thể kết nối được
- Cách kiểm tra:
- Ghi chú thêm:

## DGX

### Task
- Trạng thái: 
- Ưu tiên: 
- File liên quan: DGX.PHP, dgx_report_layout
- Mục tiêu: KHẮC PHỤC LỖI TIẾNG VIỆT, định dạng số liệu khi xuất ra báo cáo
- Yêu cầu chi tiết: sửa lỗi tiếng việt hiển trị trên web, khi xuất ra dữ liệu các số liệu nên định dạng: 1.000.000 đôi với giá trị triền tệ và 1,000 đối với các số liệu khác
- Không được đụng vào: các thư mục khác
- Kết quả mong muốn: xử lý triệt để các lỗi trên và lưu vào md tránh trường hợp bị sai font chữ
- Cách kiểm tra: vào DGX xem nội dung bên trong, nhắn "báo cáo", "Xuất excel", file báo cáo sẽ thiết lập lại định đạng số và tiền tệ
- Ghi chú thêm:

## HOME_PAGE

### Task
- Trạng thái:
- Ưu tiên:
- File liên quan:
- Mục tiêu:
- Yêu cầu chi tiết:
- Không được đụng vào:
- Kết quả mong muốn:
- Cách kiểm tra:
- Ghi chú thêm:

## USER-SYSTEM

### Task
- Trạng thái: TODO
- Ưu tiên: Cao
- File liên quan: user_system_sql, user_system.php, layout_insert.php, user_system.md
- Mục tiêu: thêm điều kiện về tiêu chí giaoviec
- Yêu cầu chi tiết: Phần giao việc nếu chọn "N" thì insert hoặc updte thì set giaoviec = null
- Không được đụng vào: các thư mục khác
- Kết quả mong muốn: có thể load, inser, update được các thông tin chi tiết cán bộ
- Cách kiểm tra: 
- Ghi chú thêm: 

## VB_IOT

### Task
- Trạng thái:
- Ưu tiên:
- File liên quan:
- Mục tiêu:
- Yêu cầu chi tiết:
- Không được đụng vào:
- Kết quả mong muốn:
- Cách kiểm tra:
- Ghi chú thêm:

## view

### Task
- Trạng thái:
- Ưu tiên:
- File liên quan:
- Mục tiêu:
- Yêu cầu chi tiết:
- Không được đụng vào:
- Kết quả mong muốn:
- Cách kiểm tra:
- Ghi chú thêm:

## Mẫu thêm thư mục mới

Sao chép khối này khi bạn tạo thư mục mới:

```md
## TEN_THU_MUC_MOI

### Task
- Trạng thái: TODO
- Ưu tiên: Trung bình
- File liên quan:
- Mục tiêu:
- Yêu cầu chi tiết:
- Không được đụng vào:
- Kết quả mong muốn:
- Cách kiểm tra:
- Ghi chú thêm:
```

## Ví dụ nhanh

```md
## DGX

### Task
- Trạng thái: TODO
- Ưu tiên: Cao
- File liên quan: DGX/dgx.php, DGX/dgx_sql.php
- Mục tiêu: Thêm bộ lọc theo ngày cho màn hình DGX
- Yêu cầu chi tiết: Thêm 2 ô từ ngày/đến ngày, lọc dữ liệu khi bấm tìm kiếm, giữ lại giá trị sau khi submit
- Không được đụng vào: Xuất Excel hiện tại
- Kết quả mong muốn: Người dùng lọc được dữ liệu theo khoảng ngày ngay trên màn hình DGX
- Cách kiểm tra: Mở DGX, nhập khoảng ngày, bấm tìm kiếm, kiểm tra dữ liệu thay đổi đúng
- Ghi chú thêm: Nếu cần sửa CSS thì cập nhật ở view/Style_dgx.php
```
