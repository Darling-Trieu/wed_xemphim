"use strict";
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('seat-map');
    if (!container) {
        return;
    }
    const showtimeId = container.dataset.showtimeId;
    const interactive = container.dataset.interactive === '1';
    const apiUrl = container.dataset.apiUrl || '/api.php';
    const selected = new Set();
    const priceById = new Map();
    fetch(`${apiUrl}?action=seats&showtime_id=${encodeURIComponent(showtimeId || '')}`)
        .then((res) => {
        if (!res.ok) {
            throw new Error('Không tải được sơ đồ ghế');
        }
        return res.json();
    })
        .then((data) => {
        renderSeats(container, data.seats, interactive, selected, priceById);
    })
        .catch(() => {
        container.innerHTML = '<p>Không tải được sơ đồ ghế, vui lòng thử lại.</p>';
    });
});
function seatTypeClass(type) {
    if (type === 'VIP')
        return 'type-vip';
    if (type === 'Đôi')
        return 'type-doi';
    return '';
}
function formatMoney(amount) {
    return amount.toLocaleString('vi-VN') + 'đ';
}
function renderSeats(container, seats, interactive, selected, priceById) {
    const rows = new Map();
    seats.forEach((seat) => {
        priceById.set(seat.id, seat.price);
        if (!rows.has(seat.row)) {
            rows.set(seat.row, []);
        }
        rows.get(seat.row).push(seat);
    });
    const grid = document.createElement('div');
    grid.className = 'seat-grid';
    Array.from(rows.keys())
        .sort()
        .forEach((rowLabel) => {
        const rowEl = document.createElement('div');
        rowEl.className = 'seat-row';
        const label = document.createElement('span');
        label.className = 'seat-row-label';
        label.textContent = rowLabel;
        rowEl.appendChild(label);
        rows.get(rowLabel)
            .sort((a, b) => a.number - b.number)
            .forEach((seat) => {
            const seatEl = document.createElement('div');
            seatEl.className = 'seat ' + seatTypeClass(seat.type);
            seatEl.textContent = rowLabel + seat.number;
            seatEl.title = `${seat.type} - ${formatMoney(seat.price)}`;
            if (!seat.is_active) {
                seatEl.classList.add('inactive');
            }
            else if (seat.is_booked) {
                seatEl.classList.add('booked');
            }
            else if (interactive) {
                seatEl.addEventListener('click', () => toggleSeat(seat.id, seatEl, selected, priceById));
            }
            rowEl.appendChild(seatEl);
        });
        grid.appendChild(rowEl);
    });
    container.innerHTML = '';
    container.appendChild(grid);
}
function toggleSeat(seatId, seatEl, selected, priceById) {
    if (selected.has(seatId)) {
        selected.delete(seatId);
        seatEl.classList.remove('selected');
    }
    else {
        selected.add(seatId);
        seatEl.classList.add('selected');
    }
    updateSummary(selected, priceById);
}
function updateSummary(selected, priceById) {
    const input = document.getElementById('seat-ids-input');
    const label = document.getElementById('selected-seats-label');
    const totalEl = document.getElementById('total-price');
    const submitBtn = document.getElementById('submit-booking');
    if (input) {
        input.value = Array.from(selected).join(',');
    }
    let total = 0;
    selected.forEach((id) => {
        total += priceById.get(id) || 0;
    });
    if (label) {
        label.textContent = selected.size > 0 ? `Đã chọn ${selected.size} ghế` : 'Chưa chọn ghế nào';
    }
    if (totalEl) {
        totalEl.textContent = formatMoney(total);
    }
    if (submitBtn) {
        submitBtn.disabled = selected.size === 0;
    }
}
