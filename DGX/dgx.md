# DGX

## Cap nhat 2026-04-17

- Da fix loi Oracle `ORA-01799` trong `dgx_report_sql()` bang cach bo subquery ra khoi dieu kien `LEFT JOIN I_USER`.
- Bao cao DGX van lay `TEN_GDV` tu `I_USER`, loc theo ngay bao cao thong qua CTE `PARAMS`.

## Cap nhat 2026-04-07

- Bao cao theo yeu cau da bo sung cot `Ma Diem GDX` tren popup va trong file Excel xuat ra.
- SQL custom report da tra them truong `MA_DIEM_GDX` de dong bo giua man hinh va file xuat.

## Cap nhat 2026-04-01

- Trang DGX da bo sung nut `Bao cao theo yeu cau` ben canh `Danh sach diem co dinh`.
- Bo loc `Bao cao theo yeu cau` gom: `Tu ngay`, `Den ngay`, `Nhap ma POS`.
- Neu bo trong ma POS thi lay toan bo POS; neu nhap thi loc theo cac ma POS dang nhap.
- Popup `Bao cao theo yeu cau` khong tu dong tai du lieu khi mo; chi goi du lieu sau khi bam `Bao cao` hoac `Xuat bao cao`.
- Du lieu `Bao cao theo yeu cau` da dung cung logic voi bao cao DGX hien co, mo rong theo khoang ngay va bo sung cot `Ngay giao dich xa`.
- Chuc nang `Xuat bao cao` dung cung bo loc dang xem va xuat file Excel co them cot `Ngay giao dich xa`.
- Da lint: `DGX/dgx.php`, `DGX/dgx_sql.php`, `DGX/dgx_report_layout.php`, `view/Style_dgx.php`.

## Cap nhat 2026-04-02

- Bo loc `Bao cao theo yeu cau` tiep tuc cho phep bo trong ma POS de lay tat ca POS.
- Khi nhap ma POS chi gom so va do dai nho hon 6 ky tu, he thong tu dong them `0` ben trai. Vi du: `3401` -> `003401`.
- SQL `Bao cao theo yeu cau` da tach cot sap xep ngay giao dich xa sang lop ngoai de tranh loi Oracle `ORA-01791`.
- Da lint: `DGX/dgx.php`, `DGX/dgx_sql.php`, `DGX/dgx_report_layout.php`.
- Bao cao theo yeu cau da doi sang cach truy van theo tung ngay trong khoang chon de giam nguy co time out Oracle khi gom nhieu ngay.
- Luong bao cao DGX tu tang timeout OCI rieng len 120 giay cho request nay va tra thong bao huong dan ro hon neu Oracle van qua thoi gian.
- custom_report_list da duoc bo sung helper tra JSON an toan hon de tranh vo response khi du lieu Oracle co ky tu loi ma hoa.
- Popup Bao cao theo yeu cau da doc noi dung loi thuc te tu server thay vi chi hien cau mac dinh "Khong tai duoc du lieu bao cao.".
- Luong tong hop theo ngay nay da duoc reset lai `set_time_limit` truoc moi lan query de tranh bi cong don tong thoi gian va dung o `DGX/dgx.php` line 80.

## Cap nhat tiep 2026-04-02

- `DGX/dgx.php` da nha khoa session som ngay sau khoi tao de request `Bao cao theo yeu cau` va `Xuat bao cao` khong giu session lock lam cham cac tab khac.
- Popup `Bao cao theo yeu cau` da doi trang thai mac dinh sang `San sang tai bao cao`, giu lai ket qua vua tai khi mo lai popup va khong reset ve `Chua tai du lieu` sai ngu canh.
- Popup `Bao cao theo yeu cau` da khoa tam thoi bo loc, nut `Bao cao`, nut `Xuat bao cao` trong luc request dang chay va giu thong bao loi thuc te neu tai/xuat that bai.
- `view/Style_dgx.php` da bo sung style cho trang thai disabled/busy de nguoi dung nhan biet ro popup dang xu ly.
## Cap nhat bo sung 2026-04-02

- DGX/dgx_sql.php da doi logic lay TEN_GDV trong ca dgx_report_sql() va dgx_custom_report_sql() theo code Oracle moi.
- SQL nay uu tien ten theo CHUAN_HOA_TK_INTELLECT.MA_CB, neu khong co thi fallback sang CHUAN_HOA_TK_INTELLECT.USER_INTELLECT, nham hien du ten GDV tren man hinh va file xuat.
- Da lint: DGX/dgx_sql.php.