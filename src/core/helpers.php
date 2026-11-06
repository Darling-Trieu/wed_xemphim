<?php
// Hàm định dạng dùng chung cho view / email (NFR hiển thị: tiền 75.000đ, ngày dd/mm/yyyy).

/** 75000 -> "75.000đ" */
function money($amount): string
{
    return number_format((float) $amount, 0, ',', '.') . 'đ';
}
