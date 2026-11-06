"use strict";
const REFRESH_MS = 5000;
document.addEventListener('DOMContentLoaded', () => {
    initAgeConfirm();
    const container = document.getElementById('seat-map');
    if (!container) {
        return;
    }
    const showtimeId = container.dataset.showtimeId || '';
    const state = {
        container,
        showtimeId,
        apiUrl: container.dataset.apiUrl || '/api.php',
        csrfToken: container.dataset.csrf || '',
        interactive: container.dataset.interactive === '1',
        maxSeats: parseInt(container.dataset.maxSeats || '8', 10),
        storageKey: `seat-selection-${showtimeId}`,
        selected: new Set(),
        seatById: new Map(),
    };
    loadSeats(state, true)
        .then(() => {
        window.setInterval(() => updateCountdown(state), 1000);
        window.setInterval(() => loadSeats(state, false).catch(() => undefined), REFRESH_MS);
    })
        .catch(() => {
        container.innerHTML = '<p>Không tải được sơ đồ ghế, vui lòng thử lại.</p>';
    });
});
function loadSeats(state, firstLoad) {
    return fetch(`${state.apiUrl}?action=seats&showtime_id=${encodeURIComponent(state.showtimeId)}`)
        .then((res) => (res.ok ? res.json() : Promise.reject(new Error('Không tải được sơ đồ ghế'))))
        .then((data) => {
        if (data.showtime.sale_closed) {
            state.interactive = false;
        }
        data.seats.forEach((seat) => state.seatById.set(seat.id, seat));
        if (firstLoad) {
            restoreSelection(state);
            reholdRestoredSeats(state);
        }
        else {
            state.selected.forEach((id) => {
                if (!isSelectable(state.seatById.get(id))) {
                    state.selected.delete(id);
                }
            });
            saveSelection(state);
        }
        refreshView(state);
    });
}
function refreshView(state) {
    renderSeats(state);
    updateSummary(state);
    updateCountdown(state);
}
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
function showMessage(text) {
    const message = document.getElementById('seat-message');
    if (message) {
        message.textContent = text;
    }
}
function isSelectable(seat) {
    return !!seat && seat.is_active && !seat.is_aisle && !seat.is_booked
        && (!seat.is_held || seat.held_by_me);
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
    saved.map(Number).forEach((id) => {
        if (state.selected.size < state.maxSeats && isSelectable(state.seatById.get(id))) {
            state.selected.add(id);
        }
    });
    saveSelection(state);
}
function reholdRestoredSeats(state) {
    state.selected.forEach((id) => {
        const seat = state.seatById.get(id);
        if (!seat || seat.held_by_me) {
            return;
        }
        requestHold(state, 'hold_seat', id).then((result) => applyHoldResult(state, id, result));
    });
}
function saveSelection(state) {
    try {
        sessionStorage.setItem(state.storageKey, JSON.stringify(Array.from(state.selected)));
    }
    catch (e) {
    }
}
function renderSeats(state) {
    const rows = new Map();
    state.seatById.forEach((seat) => {
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
            rowEl.appendChild(seat.is_aisle ? createAisle() : createSeat(seat, state));
        });
        grid.appendChild(rowEl);
    });
    state.container.innerHTML = '';
    state.container.appendChild(grid);
}
function createSeat(seat, state) {
    const label = seat.row + seat.number;
    const seatEl = document.createElement('div');
    seatEl.className = 'seat ' + seatTypeClass(seat.type);
    seatEl.textContent = label;
    if (!seat.is_active) {
        seatEl.classList.add('inactive');
        seatEl.title = `${label} - Ngừng sử dụng`;
    }
    else if (seat.is_booked) {
        seatEl.classList.add('booked');
        seatEl.title = `${label} - Đã bán`;
    }
    else if (seat.is_held && !seat.held_by_me) {
        seatEl.classList.add('held');
        seatEl.title = `${label} - Đang được khách khác giữ`;
    }
    else {
        seatEl.title = `${seat.type} - ${formatMoney(seat.price)}`;
        seatEl.classList.toggle('selected', state.selected.has(seat.id));
        if (state.interactive) {
            seatEl.addEventListener('click', () => toggleSeat(seat.id, state));
        }
    }
    return seatEl;
}
function createAisle() {
    const el = document.createElement('div');
    el.className = 'seat aisle';
    el.setAttribute('aria-hidden', 'true');
    return el;
}
function toggleSeat(seatId, state) {
    showMessage('');
    if (state.selected.has(seatId)) {
        state.selected.delete(seatId);
        requestHold(state, 'release_seat', seatId);
    }
    else {
        if (state.selected.size >= state.maxSeats) {
            showMessage(`Mỗi đơn chỉ được chọn tối đa ${state.maxSeats} ghế`);
            return;
        }
        state.selected.add(seatId);
        requestHold(state, 'hold_seat', seatId).then((result) => applyHoldResult(state, seatId, result));
    }
    saveSelection(state);
    refreshView(state);
}
function applyHoldResult(state, seatId, result) {
    const seat = state.seatById.get(seatId);
    if (result.held) {
        if (seat) {
            seat.held_by_me = true;
            seat.hold_expires_at = result.expires_at;
        }
    }
    else {
        state.selected.delete(seatId);
        saveSelection(state);
        showMessage(result.error || 'Ghế vừa được người khác giữ, vui lòng chọn ghế khác.');
    }
    refreshView(state);
}
function requestHold(state, action, seatId) {
    const body = new URLSearchParams({
        showtime_id: state.showtimeId,
        seat_id: String(seatId),
        csrf_token: state.csrfToken,
    });
    return fetch(`${state.apiUrl}?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
    })
        .then((res) => res.json())
        .then((data) => ({
        held: action === 'release_seat' || data.held === true,
        expires_at: data.expires_at || null,
        error: data.error || null,
    }))
        .catch(() => ({ held: false, expires_at: null, error: 'Không giữ được ghế, vui lòng thử lại.' }));
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
function getSelectedHoldExpiry(state) {
    const expiries = Array.from(state.selected)
        .map((id) => { var _a; return ((_a = state.seatById.get(id)) === null || _a === void 0 ? void 0 : _a.hold_expires_at) || null; })
        .filter((expiry) => expiry !== null);
    return expiries.length ? Math.min(...expiries) : null;
}
function updateCountdown(state) {
    const countdown = document.getElementById('hold-countdown');
    if (!countdown)
        return;
    const expiresAt = getSelectedHoldExpiry(state);
    if (expiresAt === null) {
        countdown.textContent = '';
        return;
    }
    const seconds = Math.max(0, expiresAt - Math.floor(Date.now() / 1000));
    if (seconds === 0) {
        countdown.textContent = '';
        state.selected.clear();
        saveSelection(state);
        renderSeats(state);
        updateSummary(state);
        showMessage('Thời gian giữ ghế đã hết, vui lòng chọn lại.');
        return;
    }
    const minutes = Math.floor(seconds / 60);
    countdown.textContent = `Thời gian giữ ghế còn lại: ${minutes}:${String(seconds % 60).padStart(2, '0')}`;
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
