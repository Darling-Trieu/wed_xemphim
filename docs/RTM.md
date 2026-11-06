# Ma trận truy vết yêu cầu (RTM) – Nhóm 4: Hệ thống đặt vé xem phim

- Kiểm thử tự động: `php tests/run_tests.php` (MySQL của Laragon đang chạy). Script tự tạo CSDL riêng
  `movie_booking_test` từ `database/schema.sql`, bật 2 server `php -S` và chạy toàn bộ ca, không đụng CSDL thật.
- Lần chạy gần nhất: **06/11/2026 – 170/170 kiểm tra Đạt** (25 ca kiểm thử).
- Mỗi ca gồm nhiều kiểm tra nhỏ; ca chỉ Đạt khi mọi kiểm tra của ca đều Đạt.

## 1. Danh sách ca kiểm thử

| Mã | Ca kiểm thử (tên section trong `tests/run_tests.php`) | Nhóm kiểm thử bắt buộc | Số kiểm tra | Kết quả |
|---|---|---|---|---|
| TC-01 | Ca 1 – Hai phiên đặt cùng 1 ghế gần như đồng thời (2 request HTTP song song + 10 tiến trình cùng lúc) | Cùng ghế cùng suất từ 2 phiên | 6 | Đạt |
| TC-02 | Ca 2 – Sửa giá trong request từ client | Tính giá theo loại ghế | 3 | Đạt |
| TC-03 | Ca 3 – Gửi 9 ghế, ghế phòng khác, ghế ngừng sử dụng, 8 ghế vẫn đặt được | Số lượng ghế biên | 4 | Đạt |
| TC-04 | Ca 4 – Đặt suất còn 14 phút / 16 phút nữa chiếu | Suất đã qua / đóng bán | 2 | Đạt |
| TC-05 | Ca 5 – Phim T18 không tích xác nhận độ tuổi (cả gọi thẳng request) | Quy tắc nghiệp vụ | 6 | Đạt |
| TC-06 | Ca 6 – Trùng lịch biên: A 19:00 (94') chiếm phòng tới 20:49; B 20:48 bị chặn, 20:49 hợp lệ; sửa không trùng chính nó | Trùng suất cùng phòng | 13 | Đạt |
| TC-07 | Ca 7 – Admin hủy đơn: bắt buộc lý do, thanh toán → Đã hoàn, ghế trống lại, chặn hủy suất đã chiếu | Quản trị đơn | 7 | Đạt |
| TC-08 | Ca 8 – Sửa phim / giờ / giá / loại ghế sau khi đặt, "Vé của tôi" vẫn hiện thông tin cũ | Snapshot vé | 3 | Đạt |
| TC-09 | Ca 9 – Xem đơn người khác (IDOR), User mở trang / API admin | Quyền Guest/User/Admin | 5 | Đạt |
| TC-10 | Ca 10 – Tài khoản bị khóa, admin không tự khóa mình, tìm tài khoản | Quyền Guest/User/Admin | 6 | Đạt |
| TC-11 | UC05 – Đăng nhập bằng email hoặc SĐT, thông báo lỗi, log, đăng xuất | Tài khoản | 8 | Đạt |
| TC-12 | Ca 12 – Xóa phim / phòng / suất / rạp đã có vé → không xóa cứng | Toàn vẹn dữ liệu | 9 | Đạt |
| TC-13 | Ca 13 – Thống kê: chỉ tính đơn Đã xác nhận, Từ ngày > Đến ngày báo lỗi, khoảng rỗng | Thống kê | 5 | Đạt |
| TC-14 | Quyền sở hữu ghế giữ tạm: CSRF, tối đa 8 ghế / phiên, đăng xuất nhả ghế, khách chọn ghế → đăng nhập → đặt được | Giữ ghế / ghế đã đặt | 10 | Đạt |
| TC-15 | Ca 15 – Form / API không có CSRF token bị từ chối, trailer `javascript:` bị chặn | Bảo mật | 4 | Đạt |
| TC-16 | Ca 16 – Guest bấm Đặt vé → đăng nhập → quay lại đúng suất | Quyền Guest/User | 4 | Đạt |
| TC-17 | Ca 17 – Phim Ngừng chiếu / phòng Ngừng hoạt động → suất không bán được, ẩn khỏi lịch | Quy tắc nghiệp vụ | 6 | Đạt |
| TC-18 | Ca 18 – Chỉ người đã xem xong phim mới đánh giá được, nhận xét ≤ 500 ký tự | Đánh giá phim | 4 | Đạt |
| TC-19 | Ca 19 – Dữ liệu nhập sai → báo lỗi rõ ràng, không "Lỗi hệ thống" | Validate | 8 | Đạt |
| TC-20 | Ca 20 – Loại ghế & phụ thu, sửa phòng, ghế Ngừng sử dụng / Lối đi | Quản lý phòng / ghế | 12 | Đạt |
| TC-21 | Ca 21 – Tra cứu suất: số ghế trống, suất đóng bán hiện mờ, giới hạn 7 ngày, kết quả rỗng | Tra cứu | 4 | Đạt |
| TC-22 | Lỗi CSDL không lộ chi tiết SQL | Bảo mật | 1 | Đạt |
| TC-23 | UC04 – Validation và quyền đăng ký | Tài khoản | 17 | Đạt |
| TC-24 | UC06 – Validation và giữ dữ liệu hồ sơ | Tài khoản | 7 | Đạt |
| TC-25 | UC06 – Đổi mật khẩu an toàn | Tài khoản | 16 | Đạt |
| TC-M1 | Thủ công: đặt vé xong nhận email xác nhận (cần cấu hình `sendmail` của Laragon) | Thông báo | – | Kiểm tra khi demo |

## 2. Yêu cầu chức năng → ca kiểm thử

| Yêu cầu | Mức | Nội dung | Ca kiểm thử | Kết quả |
|---|---|---|---|---|
| G4-FR01 | MUST | Xem phim đang chiếu / sắp chiếu và chi tiết phim | TC-12, TC-17 | Đạt |
| G4-FR02 | MUST | Tra cứu suất theo phim, rạp / phòng, ngày | TC-21, TC-17 | Đạt |
| G4-FR03 | MUST | Đăng ký, đăng nhập, quản lý tài khoản | TC-11, TC-23, TC-24, TC-25, TC-10 | Đạt |
| G4-FR04 | MUST | Sơ đồ ghế: trống / đang chọn / đang giữ / đã bán | TC-14, TC-03, TC-20 | Đạt |
| G4-FR05 | MUST | Chọn ghế, tạo đơn, kiểm tra lại ghế ngay trước khi xác nhận | TC-01, TC-14, TC-04, TC-16 | Đạt |
| G4-FR06 | MUST | Tổng tiền theo loại ghế / giá suất, lưu snapshot giá | TC-02, TC-08, TC-20 | Đạt |
| G4-FR07 | MUST | Mã booking và lịch sử vé | TC-02, TC-08, TC-09 | Đạt |
| G4-FR08 | MUST | Admin quản lý phim và trạng thái | TC-12, TC-17, TC-19, TC-15 | Đạt |
| G4-FR09 | MUST | Admin quản lý rạp / phòng / sơ đồ ghế / loại ghế | TC-20, TC-12 | Đạt |
| G4-FR10 | MUST | Tạo / sửa suất, chặn trùng giờ cùng phòng (thời lượng + 15' dọn phòng) | TC-06 | Đạt |
| G4-FR11 | MUST | Admin theo dõi đơn / vé, trạng thái thanh toán | TC-07 | Đạt |
| G4-FR12 | SHOULD | Dashboard vé / doanh thu theo phim / rạp / khoảng thời gian | TC-13 | Đạt |
| G4-FR13 | SHOULD | Email xác nhận vé; đánh giá phim sau suất chiếu | TC-18, TC-M1 | Đạt (email kiểm tra thủ công) |
| G4-FR14 | COULD | Giữ ghế tạm theo timeout (5 phút) | TC-14, TC-01 | Đạt |

## 3. Quy tắc nghiệp vụ → ca kiểm thử

| Quy tắc | Ca kiểm thử | Kết quả |
|---|---|---|
| Cùng Seat + Showtime chỉ bán cho tối đa 1 đơn hợp lệ (`UNIQUE(showtime_id, seat_id)`) | TC-01, TC-07, TC-14 | Đạt |
| Suất cùng phòng không chồng lấn; chiếm phòng = thời lượng phim + 15 phút dọn phòng | TC-06 | Đạt |
| Không đặt suất đã bắt đầu / đóng bán (trước 15 phút) hoặc ghế không khả dụng | TC-04, TC-03, TC-17, TC-21 | Đạt |
| Lịch sử vé giữ thông tin phim / suất / ghế / giá lúc đặt | TC-08, TC-20 | Đạt |
| 1–8 ghế / đơn | TC-03, TC-14 | Đạt |
| Phim T13/T16/T18 bắt buộc xác nhận độ tuổi | TC-05 | Đạt |
| Không xóa cứng dữ liệu đã có vé | TC-12, TC-20 | Đạt |

## 4. Yêu cầu phi chức năng → ca kiểm thử

| NFR | Ca kiểm thử | Kết quả |
|---|---|---|
| Phân quyền Guest / User / Admin, chặn IDOR | TC-09, TC-10, TC-16 | Đạt |
| CSRF cho mọi form / API thay đổi dữ liệu, chống XSS qua link | TC-15, TC-14 | Đạt |
| Lỗi CSDL không lộ SQL, báo "Lỗi hệ thống, vui lòng thử lại" | TC-22, TC-19 | Đạt |
| Toàn vẹn dữ liệu: transaction đặt vé / hủy đơn, không đơn "mồ côi" khi rollback | TC-01, TC-07 | Đạt |
| Log đăng nhập và thao tác admin | TC-11, TC-07, TC-10, TC-06, TC-12 | Đạt |
