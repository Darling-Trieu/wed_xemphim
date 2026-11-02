#!/bin/sh
# Tải ảnh từ Figma vào thư mục assets (macOS / Linux).
# Link Figma chỉ còn hạn khoảng 7 ngày kể từ 01/11/2026.
cd "$(dirname "$0")" || exit 1
mkdir -p assets
P=https://www.figma.com/api/mcp/asset/e5a5199e-ce6f-4a29-96cb-1624a9d3952f
curl -f -L -o assets/hero-cinema.png          "$P/d669b.png"
curl -f -L -o assets/poster-an-mang.png       "$P/d6d89.png"
curl -f -L -o assets/poster-chi-chi-em-em.png "$P/833a1.png"
curl -f -L -o assets/poster-shaun.png         "$P/3ebfd.png"
curl -f -L -o assets/poster-2.png             "$P/4e418.png"
curl -f -L -o assets/poster-3.png             "$P/791a6.png"
echo "Xong! Mở index.html bằng trình duyệt."
