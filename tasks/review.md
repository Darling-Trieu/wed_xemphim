## Review năm chiều

Correctness: sáu điểm trong báo cáo được xử lý; email không thể thay đổi qua sửa hồ sơ. Backend và HTML áp dụng SĐT bắt buộc, mật khẩu mới 8 ký tự có chữ/số. Giữ dữ liệu không nhạy cảm khi nhập sai.

Readability và architecture: theo MVC hiện có; dùng chung helper kiểm tra mật khẩu giữa đăng ký và đổi mật khẩu. Không thêm dependency hoặc migration.

Security: route đổi mật khẩu yêu cầu đăng nhập, CSRF và mật khẩu cũ. ID lấy từ session; prepared UPDATE kèm hash hiện tại chống ghi đè dựa trên hash cũ. bcrypt cost 12, không trim, giới hạn 72 byte/NUL để tránh cắt ngầm. Không trả lại/lưu log password hoặc hash. Thành công đổi session ID và token CSRF.

Performance: chỉ thêm truy vấn validation cần thiết; bcrypt cost 12 trên thao tác tạo/đổi mật khẩu. Không thêm truy vấn cho mọi request.

Không thấy lỗi chặn bàn giao trong diff sau kiểm thử. Không tự commit hay sửa file JS đã có sẵn.

## Giới hạn

- Chưa kiểm tra trực quan trên trình duyệt/mobile, chưa đo tải 20 người dùng hay thời gian phản hồi NFR.
- Đổi mật khẩu không thu hồi toàn bộ phiên khác; chức năng quản lý phiên, rate limiting và hardening triển khai HTTPS không nằm trong sáu lỗi lần này.
- Mật khẩu seed cũ giữ nguyên để tương thích demo; chính sách mới áp dụng đăng ký/đổi mật khẩu. Không thay đổi CSDL nghiệp vụ movie_booking.
