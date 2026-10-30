"use strict";
document.addEventListener('DOMContentLoaded', () => {
    initAgeConfirm();
    const container = document.getElementById('seat-map');
    if (!container) {
        return;
    }
    const showtimeId = container.dataset.showtimeId || '';
    const apiUrl = container.dataset.apiUrl || '/api.php';
    const state = {
        interactive: container.dataset.interactive === '1',
        maxSeats: parseInt(container.dataset.maxSeats || '8', 10),
        storageKey: `seat-selection-${showtimeId}`,
        selected: new Set(),
        seatById: new Map(),
    };
    fetch(`${apiUrl}?action=seats&showtime_id=${encodeURIComponent(showtimeId)}`)
        .then((res) => {
        if (!res.ok) {
            throw new Error('Không tải được sơ đồ ghế');
        }
        return res.json();
    })
        .then((data) => {
        if (data.showtime.sale_closed) {
            state.interactive = false;
        }
        data.seats.forEach((seat) => state.seatById.set(seat.id, seat));
        restoreSelection(state);
        renderSeats(container, data.seats, state);
        updateSummary(state);
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
function isSelectable(seat) {
    return !!seat && seat.is_active && !seat.is_aisle && !seat.is_booked;
}
function restoreSelection(state) {
    if (!state.interactive) {
        return;
    }
    let saved = [];
    try {
        saved = JSON.parse(sessionStorage.getItem(state.storageKey) || '[]');
    }
    catch (e) {
        saved = [];
    }
    if (!Array.isArray(saved)) {
        return;
    }
    saved.forEach((id) => {
        if (state.selected.size < state.maxSeats && isSelectable(state.seatById.get(Number(id)))) {
            state.selected.add(Number(id));
        }
    });
    saveSelection(state);
}
function saveSelection(state) {
    try {
        sessionStorage.setItem(state.storageKey, JSON.stringify(Array.from(state.selected)));
    }
    catch (e) {
    }
}
function renderSeats(container, seats, state) {
    const rows = new Map();
    seats.forEach((seat) => {
        if (!rows.has(seat.row)) {
            rows.set(seat.row, []);
        }
        rows.get(seat.row).push(seat);
    });
    const grid = document.createElement('div');
    grid.className = 'seat-grid';
    Array.from(rows.keys())
        .sort((a, b) => a.length - b.length || a.localeCompare(b))
        .forEach((rowLabel) => {
        const rowEl = document.createElement('div');
        rowEl.className = 'seat-row';
        const label = document.createElement('span');
        label.className = 'seat-row-label';
        label.textContent = rowLabel;
        rowEl.appendChild(label);
        let expected = 1;
        rows.get(rowLabel)
            .sort((a, b) => a.number - b.number)
            .forEach((seat) => {
            for (; expected < seat.number; expected++) {
                rowEl.appendChild(createAisle());
            }
            expected = seat.number + 1;
            if (seat.is_aisle) {
                rowEl.appendChild(createAisle());
                return;
            }
            const seatEl = document.createElement('div');
            seatEl.className = 'seat ' + seatTypeClass(seat.type);
            seatEl.textContent = rowLabel + seat.number;
            if (!seat.is_active) {
                seatEl.classList.add('inactive');
                seatEl.title = `${rowLabel}${seat.number} - Ngừng sử dụng`;
            }
            else if (seat.is_booked) {
                seatEl.classList.add('booked');
                seatEl.title = `${rowLabel}${seat.number} - Đã bán`;
            }
            else {
                seatEl.title = `${seat.type} - ${formatMoney(seat.price)}`;
                if (state.selected.has(seat.id)) {
                    seatEl.classList.add('selected');
                }
                if (state.interactive) {
                    seatEl.addEventListener('click', () => toggleSeat(seat.id, seatEl, state));
                }
            }
            rowEl.appendChild(seatEl);
        });
        grid.appendChild(rowEl);
    });
    container.innerHTML = '';
    container.appendChild(grid);
}
function createAisle() {
    const el = document.createElement('div');
    el.className = 'seat aisle';
    el.setAttribute('aria-hidden', 'true');
    return el;
}
function toggleSeat(seatId, seatEl, state) {
    const message = document.getElementById('seat-message');
    if (state.selected.has(seatId)) {
        state.selected.delete(seatId);
        seatEl.classList.remove('selected');
    }
    else {
        if (state.selected.size >= state.maxSeats) {
            if (message) {
                message.textContent = `Mỗi đơn chỉ được chọn tối đa ${state.maxSeats} ghế`;
            }
            return;
        }
        state.selected.add(seatId);
        seatEl.classList.add('selected');
    }
    if (message) {
        message.textContent = '';
    }
    saveSelection(state);
    updateSummary(state);
}
function updateSummary(state) {
    const input = document.getElementById('seat-ids-input');
    const label = document.getElementById('selected-seats-label');
    const totalEl = document.getElementById('total-price');
    const submitBtn = document.getElementById('submit-booking');
    const ids = Array.from(state.selected);
    if (input) {
        input.value = ids.join(',');
    }
    let total = 0;
    const labels = [];
    ids.forEach((id) => {
        const seat = state.seatById.get(id);
        if (seat) {
            total += seat.price;
            labels.push(seat.row + seat.number);
        }
    });
    if (label) {
        label.textContent = ids.length > 0 ? `Đã chọn ${ids.length} ghế: ${labels.join(', ')}` : 'Chưa chọn ghế nào';
    }
    if (totalEl) {
        totalEl.textContent = formatMoney(total);
    }
    if (submitBtn) {
        submitBtn.disabled = ids.length === 0;
    }
}
function initAgeConfirm() {
    const form = document.getElementById('confirm-form');
    const checkbox = document.getElementById('age-confirmed');
    const message = document.getElementById('age-message');
    if (!form || !checkbox) {
        return;
    }
    form.addEventListener('submit', (event) => {
        if (!checkbox.checked) {
            event.preventDefault();
            if (message) {
                message.textContent = 'Cần xác nhận đúng độ tuổi quy định';
            }
        }
    });
    checkbox.addEventListener('change', () => {
        if (message && checkbox.checked) {
            message.textContent = '';
        }
    });
}
