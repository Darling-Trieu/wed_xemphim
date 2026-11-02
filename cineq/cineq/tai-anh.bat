@echo off
REM Tai anh tu Figma vao thu muc assets (Windows 10/11 co san curl).
REM Link Figma chi con han khoang 7 ngay ke tu 01/11/2026.
cd /d "%~dp0"
if not exist assets mkdir assets
set P=https://www.figma.com/api/mcp/asset/e5a5199e-ce6f-4a29-96cb-1624a9d3952f
curl -f -L -o assets\hero-cinema.png          %P%/d669b.png
curl -f -L -o assets\poster-an-mang.png       %P%/d6d89.png
curl -f -L -o assets\poster-chi-chi-em-em.png %P%/833a1.png
curl -f -L -o assets\poster-shaun.png         %P%/3ebfd.png
curl -f -L -o assets\poster-2.png             %P%/4e418.png
curl -f -L -o assets\poster-3.png             %P%/791a6.png
echo Xong! Mo index.html bang trinh duyet.
pause
