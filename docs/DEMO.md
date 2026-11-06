# Kịch bản demo – Nhóm 4: Hệ thống đặt vé xem phim

Kịch bản chỉ dùng dữ liệu seed, không phụ thuộc dữ liệu nhập tay lúc chấm.

## 0. Chuẩn bị (5 phút trước buổi demo)

1. Laragon → Start All.
2. Import lại dữ liệu mẫu (suất chiếu được sinh theo ngày chạy, gồm hôm nay và 7 ngày tới):
   ```
   mysql -u root --default-character-set=utf8mb4 < database/schema.sql
   ```
3. Mở 2 trình duyệt khác nhau (hoặc 1 cửa sổ thường + 1 cửa sổ ẩn danh) để demo 2 phiên.
4. (Tuỳ chọn) chạy kiểm thử tự động để chiếu kết quả: `php tests/run_tests.php` → `TỔNG: 170/170 kiểm tra đạt`.

| Vai trò | Tài khoản | Mật khẩu |
|---|---|---|
| Admin | admin@cinema.com | admin123 |
| Khách hàng 1 | user@example.com (hoặc SĐT 0911111111) | user123 |
| Khách hàng 2 | ngoc.tran@example.com | user123 |

Dữ liệu seed đạt tiêu chí nghiệm thu: 10 phim (có T13, T16, T18), 4 phòng có sơ đồ ghế (Thường / VIP / Đôi, lối đi, ghế ngừng sử dụng), khoảng 190 suất trong 7 ngày, 11 booking.

## 1. Luồng 1 – Admin tạo phòng / ghế → tạo phim → tạo suất hợp lệ

1. Đăng nhập **admin** → trang Dashboard (thống kê).
2. **Loại ghế**: xem Thường 0đ / VIP +10.000đ / Đôi +60.000đ; thêm thử "Sweetbox" +30.000đ.
3. **Phòng chiếu → + Thêm phòng chiếu**: CineStar Quận 1, "Phòng Demo", Deluxe, 5 hàng × 8 ghế → Tạo.
4. Ở trang **Sơ đồ ghế** vừa mở: hàng E chọn "Cả hàng → Đôi", hàng C–D "Cả hàng → VIP", cột 4 của vài hàng đặt "Lối đi", 1 ghế "Ngừng sử dụng" → Lưu.
5. **Phim → + Thêm phim**: nhập tên, thời lượng 94, nhãn T13, trạng thái Đang chiếu → Lưu.
   - Thử link trailer `javascript:alert(1)` → bị từ chối.
6. **Suất chiếu → + Thêm suất chiếu**: phim vừa tạo, Phòng Demo, ngày mai 19:00, giá 75000 → Lưu (giờ chiếm phòng 19:00–20:49).
7. **Chống trùng suất**: thêm suất khác cùng phòng ngày mai **20:48** → báo *"Phòng đã có suất trùng thời gian: … 19:00 – 20:49"*; đổi thành **20:49** → hợp lệ.

## 2. Luồng 2 – User chọn phim → suất → ghế → xác nhận → nhận vé

1. Trình duyệt 1, **chưa đăng nhập**: Trang chủ → chọn một phim T16/T18 → **Đặt ngay** → chọn suất (lịch chiếu hiện số ghế trống; suất sắp chiếu < 15 phút hiện mờ "Đã đóng bán").
2. Sơ đồ ghế: chú thích màu + giá từng loại; chọn 2 ghế (1 VIP + 1 Đôi) → đồng hồ giữ ghế 5:00 chạy, tạm tính cập nhật. Thử chọn ghế thứ 9 → *"Mỗi đơn chỉ được chọn tối đa 8 ghế"*.
3. Bấm **Đăng nhập để đặt vé** → đăng nhập `user@example.com` → quay lại đúng suất, **2 ghế vẫn được chọn và giữ**.
4. **Đặt vé** → màn hình xác nhận: phim, suất, phòng, giá từng ghế, tổng. Bấm Xác nhận khi chưa tích ô độ tuổi → *"Cần xác nhận đúng độ tuổi quy định"*; tích rồi Xác nhận.
5. Trang thành công: mã đơn `VE…`, thanh toán mô phỏng Thành công (và email xác nhận nếu đã cấu hình sendmail).
6. **Vé của tôi** → đơn mới nhất ở đầu → Chi tiết: ghế, loại ghế, giá từng ghế, thời điểm đặt.
7. **Snapshot**: admin đổi tên phim / giá suất / phụ thu VIP → user mở lại chi tiết vé → thông tin vẫn như lúc đặt.

## 3. Luồng 3 – Hai phiên cùng chọn một ghế → chỉ một phiên thành công

1. Trình duyệt 1 (user@example.com) và trình duyệt 2 (ngoc.tran@example.com) mở **cùng một suất**.
2. Trình duyệt 1 chọn ghế **D5** → trong ≤ 5 giây, trình duyệt 2 thấy D5 chuyển màu "Đang được giữ", bấm vào không chọn được.
3. Trình duyệt 1 xác nhận đặt vé → trình duyệt 2 thấy D5 thành "Đã bán".
4. Chứng minh ở mức CSDL (không phụ thuộc giao diện): chạy `php tests/run_tests.php`, xem **Ca 1**: 2 request HTTP song song và 10 tiến trình cùng đặt 1 ghế → đúng 1 đơn thành công, các phiên còn lại nhận *"Ghế … đã có người đặt"*, nhờ ràng buộc `UNIQUE(showtime_id, seat_id)` trên bảng `showtime_seats`.

## 4. Các điểm phụ (nếu còn thời gian)

| Nội dung | Thao tác | Kết quả mong đợi |
|---|---|---|
| Hủy đơn | Admin → Đơn đặt vé → Hủy vé, nhập lý do | Đơn Đã hủy, thanh toán Đã hoàn, ghế trống lại; hủy đơn suất đã chiếu bị chặn |
| Không xóa cứng | Xóa phim / phòng đã có suất | Chỉ chuyển Ngừng chiếu / Ngừng hoạt động |
| Đánh giá phim | user@example.com → phim "Shaun the Sheep" (có suất đã chiếu xong) → đánh giá; đánh giá lại | Lần 2 cập nhật bản cũ; phim chưa xem không có form đánh giá |
| Thống kê | Dashboard → chọn Từ / Đến ngày, lọc phim / rạp | Chỉ tính đơn Đã xác nhận; Đến < Từ → báo lỗi |
| Phân quyền | User gõ `/admin` | 403 |
| Khóa tài khoản | Admin → Khách hàng → Khóa 1 tài khoản đang đăng nhập ở trình duyệt khác | Trình duyệt kia bị đăng xuất ở thao tác kế tiếp; admin không tự khóa được mình |
| Nhật ký | Admin → Nhật ký hệ thống | Có log đăng nhập và mọi thao tác ở trên |
