<?php
// Mẫu cấu hình CSDL cho hosting (InfinityFree...).
// Sao chép thành database.local.php (cùng thư mục) rồi điền thông tin trong
// Control Panel -> MySQL Databases. File database.local.php không đưa lên git.
return [
    'host'   => 'sqlXXX.infinityfree.com',   // "MySQL Hostname"
    'port'   => '3306',
    'dbname' => 'if0_XXXXXXXX_movie',        // "MySQL DB Name"
    'user'   => 'if0_XXXXXXXX',              // "MySQL Username"
    'pass'   => 'mat-khau-hosting',          // mật khẩu vPanel / MySQL
];
