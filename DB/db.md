# DB/connect_DB.php

File [connect_DB.php](/c:/xampp/htdocs/dashboard/DB/connect_DB.php) là lớp khởi tạo kết nối cơ sở dữ liệu dùng chung cho dashboard.

## Mục đích chính

- Tạo kết nối MySQL nguồn và MySQL local fallback.
- Tạo kết nối Oracle cho các màn hình cần truy vấn dữ liệu Oracle.
- Đọc cấu hình từ biến môi trường nhưng vẫn có giá trị mặc định để chạy nhanh trong môi trường nội bộ.
- Bảo vệ các host nhạy cảm bằng cách chặn câu lệnh ghi dữ liệu nếu chưa được cho phép rõ ràng.
- Ghi lại lỗi kết nối gần nhất để dễ debug.

## Các nhóm tính năng

### 1. Đọc cấu hình môi trường

- `env_or_default($key, $default)`
  Dùng để lấy biến môi trường dạng chuỗi, nếu không có thì dùng giá trị mặc định.

- `env_int_or_default($key, $default)`
  Dùng để lấy biến môi trường dạng số nguyên, phù hợp cho port.

Các biến môi trường đang được hỗ trợ:

- MySQL nguồn:
  - `DASHBOARD_SOURCE_DB_HOST`
  - `DASHBOARD_SOURCE_DB_USER`
  - `DASHBOARD_SOURCE_DB_PASS`
  - `DASHBOARD_SOURCE_DB_NAME`
  - `DASHBOARD_SOURCE_DB_PORT`

- MySQL local:
  - `DASHBOARD_LOCAL_DB_HOST`
  - `DASHBOARD_LOCAL_DB_USER`
  - `DASHBOARD_LOCAL_DB_PASS`
  - `DASHBOARD_LOCAL_DB_NAME`
  - `DASHBOARD_LOCAL_DB_PORT`

- Oracle:
  - `DASHBOARD_ORACLE_HOST`
  - `DASHBOARD_ORACLE_PORT`
  - `DASHBOARD_ORACLE_USER`
  - `DASHBOARD_ORACLE_PASS`
  - `DASHBOARD_ORACLE_SERVICE_NAME`

### 2. Kết nối MySQL

- `connect_mysql($host, $user, $pass, $db, $port)`
  Tạo kết nối MySQL đầy đủ tới database cụ thể.

- `connect_mysql_without_db($host, $user, $pass, $port)`
  Tạo kết nối MySQL nhưng chưa chọn database.

Điểm đáng chú ý:

- Có timeout kết nối ngắn để tránh treo lâu.
- Có set `utf8mb4`.
- Có lưu lỗi kết nối cuối cùng vào biến global.

### 3. Kết nối Oracle

- `connect_oracle($user, $pass, $host, $port, $serviceName)`
  Tạo kết nối Oracle với charset `AL32UTF8`.

Chiến lược kết nối hiện tại:

- Thử nhiều kiểu DSN khác nhau:
  - `SERVICE_NAME`
  - `SERVER=DEDICATED + SERVICE_NAME`
  - `SID`
  - `SERVER=SHARED + SID`

- Nếu gặp các lỗi listener kiểu `ORA-12516` hoặc `ORA-12520` thì retry ngắn nhiều lần trước khi bỏ.
- Nếu kết nối thành công và hệ thống hỗ trợ thì set call timeout cho OCI.
- Lưu lỗi Oracle gần nhất bằng `db_last_oracle_connect_error()`.

### 4. Kiểm tra host có reachable không

- `db_is_host_reachable($host, $port, $timeout = 3)`

Hàm này dùng `fsockopen()` để kiểm tra nhanh host/port có mở hay không trước khi thử connect thật.

### 5. Cơ chế fallback kết nối

Luồng MySQL:

1. Thử MySQL nguồn trước.
2. Nếu host nguồn không reachable hoặc connect lỗi thì fallback sang MySQL local.
3. Nếu cả hai đều lỗi thì dừng chương trình bằng `die(...)`.

Luồng Oracle:

1. Kiểm tra host Oracle có reachable không.
2. Nếu có thì thử kết nối Oracle.
3. Nếu không kết nối được thì chỉ log warning, không `die`, để các màn hình không phụ thuộc Oracle vẫn có thể chạy.

### 6. Theo dõi lỗi kết nối

- `db_last_mysql_connect_error()`
  Trả về lỗi MySQL gần nhất.

- `db_last_oracle_connect_error()`
  Trả về lỗi Oracle gần nhất.

Điều này hữu ích khi cần hiển thị hoặc log ra lý do kết nối thất bại.

### 7. Guard bảo vệ host nhạy cảm

Danh sách host bảo vệ:

- `10.64.0.251`
- `10.64.0.56`

Các hàm liên quan:

- `db_is_protected_host($host)`
- `db_allow_protected_host_writes($host)`
- `db_is_protected_host_write_allowed($host)`
- `db_strip_sql_comments($sql)`
- `db_is_write_sql($sql)`
- `db_should_block_sql($host, $sql)`

Mục đích:

- Phát hiện câu lệnh ghi như `INSERT`, `UPDATE`, `DELETE`, `DROP`, `ALTER`, `MERGE`, ...
- Nếu truy cập host bảo vệ mà chưa được cấp quyền ghi thì chặn thao tác.

### 8. Wrapper an toàn cho query/prepare

- `db_mysqli_prepare($conn, $sql)`
- `db_mysqli_query($conn, $sql)`
- `db_oci_parse($conn, $sql)`

Các wrapper này sẽ:

- Xác định host hiện tại.
- Kiểm tra SQL có phải lệnh ghi hay không.
- Nếu vi phạm guard thì trả `false` và ghi log.
- Nếu hợp lệ thì mới chuyển tiếp sang hàm thật của MySQL/Oracle.

## Biến kết nối sau khi file được load

- `$conn`
  Kết nối MySQL đang hoạt động.

- `$mysqlActiveHost`
  Host MySQL hiện tại đang được dùng.

- `$oracle_conn`
  Kết nối Oracle nếu thành công, ngược lại sẽ là `null`.

- `$sourceHost`, `$localHost`, `$oracleHost`
  Các cấu hình host hiện hành.

## Lưu ý khi sửa file này

- Đây là file nền dùng chung cho toàn dashboard nên thay đổi ở đây sẽ ảnh hưởng nhiều module.
- Không nên đổi cứng thông số kết nối nếu chưa kiểm tra biến môi trường.
- Nếu thêm cơ chế retry hoặc fallback mới, cần giữ log rõ ràng để dễ tìm nguyên nhân lỗi thật.
- File nên tiếp tục lưu ở `UTF-8` không BOM.
