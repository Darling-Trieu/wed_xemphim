interface SeatDto {
    id: number;
    row: string;
    number: number;
    type: string;
    price: number;
    is_active: boolean;
    is_aisle: boolean;
    is_booked: boolean;
    is_held: boolean;
    held_by_me: boolean;
    hold_expires_at: number | null;
}

interface SeatsResponse {
    showtime: {
        id: number;
        movie: string;
        cinema: string;
        room: string;
        show_date: string;
        start_time: string;
        sale_closed: boolean;
    };
    seats: SeatDto[];
}

interface SeatMapState {
    interactive: boolean;
    maxSeats: number;
    storageKey: string;
    selected: Set<number>;
    seatById: Map<number, SeatDto>;
    holdExpiresAt: number | null;
}

document.addEventListener('DOMContentLoaded', () => {
    initAgeConfirm();

    const container = document.getElementById('seat-map');
    if (!container) {
        return;
    }

    const showtimeId = container.dataset.showtimeId || '';
    const apiUrl = container.dataset.apiUrl || '/api.php';

    const state: SeatMapState = {
        interactive: container.dataset.interactive === '1',
        maxSeats: parseInt(container.dataset.maxSeats || '8', 10),
        storageKey: `seat-selection-${showtimeId}`,
        selected: new Set<number>(),
        seatById: new Map<number, SeatDto>(),
        holdExpiresAt: null,
    };

    fetch(`${apiUrl}?action=seats&showtime_id=${encodeURIComponent(showtimeId)}`)
        .then((res) => {
            if (!res.ok) {
                throw new Error('Không tải được sơ đồ ghế');
            }
            return res.json() as Promise<SeatsResponse>;
        })
        .then((data) => {
            if (data.showtime.sale_closed) {
                state.interactive = false;
            }
            data.seats.forEach((seat) => state.seatById.set(seat.id, seat));
            restoreSelection(state);
            renderSeats(container, data.seats, state);
            updateSummary(state);
            updateCountdown(state);
            window.setInterval(() => updateCountdown(state), 1000);
            window.setInterval(() => refreshSeats(container, apiUrl, state), 5000);
        })
        .catch(() => {
            container.innerHTML = '<p>Không tải được sơ đồ ghế, vui lòng thử lại.</p>';
        });

        function refreshSeats(container: HTMLElement, apiUrl: string, state: SeatMapState): void {
            fetch(`${apiUrl}?action=seats&showtime_id=${encodeURIComponent(state.storageKey.replace('seat-selection-', ''))}`)
                .then((res) => res.ok ? res.json() as Promise<SeatsResponse> : Promise.reject(new Error('Không tải được trạng thái ghế')))
                .then((data) => {
                    data.seats.forEach((seat) => state.seatById.set(seat.id, seat));
                    state.selected.forEach((id) => {
                        if (!isSelectable(state.seatById.get(id))) {
                            state.selected.delete(id);
                        }
                    });
                    state.holdExpiresAt = getSelectedHoldExpiry(state);
                    renderSeats(container, data.seats, state);
                    saveSelection(state);
                    updateSummary(state);
                    updateCountdown(state);
                })
                .catch(() => undefined);
        }
});

function seatTypeClass(type: string): string {
    if (type === 'VIP') return 'type-vip';
    if (type === 'Đôi') return 'type-doi';
    return '';
}

function formatMoney(amount: number): string {
    return amount.toLocaleString('vi-VN') + 'đ';
}

function isSelectable(seat: SeatDto | undefined): boolean {
    return !!seat && seat.is_active && !seat.is_aisle && !seat.is_booked
        && (!seat.is_held || seat.held_by_me);
}

/**
 * Ghế đang chọn chỉ lưu ở trình duyệt (sessionStorage): khách đăng nhập xong hoặc bấm
 * "Quay lại" từ màn hình xác nhận sẽ thấy lại đúng các ghế đã chọn. Ghế đã bị người khác
 * mua trong lúc đó được bỏ ra.
 */
function restoreSelection(state: SeatMapState): void {
    if (!state.interactive) {
        return;
    }
    let saved: number[] = [];
    try {
        saved = JSON.parse(sessionStorage.getItem(state.storageKey) || '[]');
    } catch (e) {
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

function saveSelection(state: SeatMapState): void {
    try {
        sessionStorage.setItem(state.storageKey, JSON.stringify(Array.from(state.selected)));
    } catch (e) {
        // Trình duyệt chặn storage: vẫn chọn ghế bình thường, chỉ không khôi phục được
    }
}

function renderSeats(container: HTMLElement, seats: SeatDto[], state: SeatMapState): void {
    const rows = new Map<string, SeatDto[]>();
    seats.forEach((seat) => {
        if (!rows.has(seat.row)) {
            rows.set(seat.row, []);
        }
        rows.get(seat.row)!.push(seat);
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
            rows.get(rowLabel)!
                .sort((a, b) => a.number - b.number)
                .forEach((seat) => {
                    // Cột bị thiếu trong lưới cũng hiển thị như lối đi để các hàng thẳng cột
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
                    } else if (seat.is_booked) {
                        seatEl.classList.add('booked');
                        seatEl.title = `${rowLabel}${seat.number} - Đã bán`;
                    } else if (seat.is_held && !seat.held_by_me) {
                        seatEl.classList.add('held');
                        seatEl.title = `${rowLabel}${seat.number} - Đang được khách khác giữ`;
                    } else {
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

function createAisle(): HTMLElement {
    const el = document.createElement('div');
    el.className = 'seat aisle';
    el.setAttribute('aria-hidden', 'true');
    return el;
}

function toggleSeat(seatId: number, seatEl: HTMLElement, state: SeatMapState): void {
    const message = document.getElementById('seat-message');

    if (state.selected.has(seatId)) {
        state.selected.delete(seatId);
        seatEl.classList.remove('selected');
        updateSeatHold('release_seat', seatId, state);
    } else {
        if (state.selected.size >= state.maxSeats) {
            if (message) {
                message.textContent = `Mỗi đơn chỉ được chọn tối đa ${state.maxSeats} ghế`;
            }
            return;
        }
        state.selected.add(seatId);
        seatEl.classList.add('selected');
        updateSeatHold('hold_seat', seatId, state).then((result) => {
            if (result.held) {
                const seat = state.seatById.get(seatId);
                if (seat) {
                    seat.hold_expires_at = result.expires_at;
                }
                state.holdExpiresAt = getSelectedHoldExpiry(state);
                updateCountdown(state);
                return;
            }
            state.selected.delete(seatId);
            seatEl.classList.remove('selected');
            seatEl.classList.add('held');
            seatEl.title = 'Đang được khách khác giữ';
            updateSummary(state);
            if (message) {
                message.textContent = 'Ghế vừa được người khác giữ, vui lòng chọn ghế khác.';
            }
        });
    }

    if (message) {
        message.textContent = '';
    }
    saveSelection(state);
    updateSummary(state);
    updateCountdown(state);
}

function updateSeatHold(action: string, seatId: number, state: SeatMapState): Promise<{held: boolean; expires_at: number | null}> {
    const formData = new URLSearchParams({
        showtime_id: state.storageKey.replace('seat-selection-', ''),
        seat_id: String(seatId),
    });
    return fetch(`${document.getElementById('seat-map')?.getAttribute('data-api-url') || '/api.php'}?action=${action}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString(),
    }).then((res) => res.ok ? res.json() : Promise.reject(new Error('Không giữ được ghế')))
        .then((data: { held?: boolean; expires_at?: number | null }) => ({
            held: action === 'release_seat' || data.held === true,
            expires_at: data.expires_at || null,
        }))
        .catch(() => ({ held: false, expires_at: null }));
}

function updateSummary(state: SeatMapState): void {
    const input = document.getElementById('seat-ids-input') as HTMLInputElement | null;
    const label = document.getElementById('selected-seats-label');
    const totalEl = document.getElementById('total-price');
    const submitBtn = document.getElementById('submit-booking') as HTMLButtonElement | null;

    const ids = Array.from(state.selected);
    if (input) {
        input.value = ids.join(',');
    }

    let total = 0;
    const labels: string[] = [];
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

function getSelectedHoldExpiry(state: SeatMapState): number | null {
    const expiries = Array.from(state.selected)
        .map((id) => state.seatById.get(id)?.hold_expires_at || null)
        .filter((expiry): expiry is number => expiry !== null);
    return expiries.length ? Math.min(...expiries) : null;
}

function updateCountdown(state: SeatMapState): void {
    const countdown = document.getElementById('hold-countdown');
    if (!countdown) return;

    state.holdExpiresAt = getSelectedHoldExpiry(state);
    if (state.holdExpiresAt === null) {
        countdown.textContent = '';
        return;
    }

    const seconds = state.holdExpiresAt
        ? Math.max(0, state.holdExpiresAt - Math.floor(Date.now() / 1000))
        : 0;
    if (seconds === 0) {
        countdown.textContent = '';
        if (state.selected.size > 0) {
            state.selected.clear();
            saveSelection(state);
            updateSummary(state);
            renderSeats(
                document.getElementById('seat-map') as HTMLElement,
                Array.from(state.seatById.values()),
                state
            );
            const message = document.getElementById('seat-message');
            if (message) {
                message.textContent = 'Thời gian giữ ghế đã hết, vui lòng chọn lại.';
            }
        }
        return;
    }

    const minutes = Math.floor(seconds / 60);
    countdown.textContent = `Thời gian giữ ghế còn lại: ${minutes}:${String(seconds % 60).padStart(2, '0')}`;
}

/** Màn hình xác nhận: phim T13/T16/T18 phải tích ô xác nhận độ tuổi (server cũng kiểm tra lại). */
function initAgeConfirm(): void {
    const form = document.getElementById('confirm-form') as HTMLFormElement | null;
    const checkbox = document.getElementById('age-confirmed') as HTMLInputElement | null;
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
