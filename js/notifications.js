/**
 * Smart AI E-Learning – Notifications Module (Part 11)
 * Manages real-time header bell notifications, read actions, and toast dismissals.
 */

'use strict';

(function() {
    const config = window.chatConfig || { role: 'user', csrfToken: '' };
    const prefix = config.endpointPrefix || '';
    const apiEndpoint = `${prefix}api/ajax_notifications.php`;

    let dropdownOpen = false;
    let cachedUnreadCount = 0;

    // Cache elements
    const bellBtn = document.getElementById('notif-bell-btn');
    const badge = document.getElementById('notif-badge');
    const dropdown = document.getElementById('notif-dropdown');
    const listContainer = document.getElementById('notif-list-container');
    const markAllBtn = document.getElementById('notif-mark-all');

    if (!bellBtn || !dropdown) return;

    /* ═══════════════════════════════════════════════════════════
       1. TOGGLE DROPDOWN
       ═══════════════════════════════════════════════════════════ */
    bellBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdownOpen = !dropdownOpen;
        dropdown.classList.toggle('active', dropdownOpen);
        if (dropdownOpen) {
            fetchNotifications();
        }
    });

    document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target) && !bellBtn.contains(e.target)) {
            dropdownOpen = false;
            dropdown.classList.remove('active');
        }
    });

    /* ═══════════════════════════════════════════════════════════
       2. FETCH NOTIFICATIONS
       ═══════════════════════════════════════════════════════════ */
    async function fetchNotifications() {
        try {
            const res = await fetch(`${apiEndpoint}?action=fetch`);
            const data = await res.json();

            if (data.status === 'success') {
                updateBadge(data.unread_count);
                renderNotifications(data.notifications);
            }
        } catch (err) {
            console.error('Fetch notifications failed:', err);
        }
    }

    function updateBadge(count) {
        if (count > 0) {
            badge.textContent = count;
            badge.style.display = 'flex';

            // If new notifications arrived, trigger user-facing custom toast if not already open
            if (count > cachedUnreadCount && cachedUnreadCount !== 0) {
                if (typeof showToast === 'function') {
                    showToast('You have new notifications!', 'info');
                }
            }
        } else {
            badge.style.display = 'none';
        }
        cachedUnreadCount = count;
    }

    function renderNotifications(notifs) {
        if (!notifs || notifs.length === 0) {
            listContainer.innerHTML = `<div class="notif-empty"><i class="far fa-bell-slash"></i> No notifications yet.</div>`;
            return;
        }

        listContainer.innerHTML = notifs.map(n => `
            <div class="notif-item ${n.status === 'unread' ? 'unread' : 'read'}" data-id="${n.id}">
                <div class="notif-content-wrapper">
                    <h4 class="notif-title">${escapeHtml(n.title)}</h4>
                    <p class="notif-msg">${escapeHtml(n.message)}</p>
                    <span class="notif-time">${formatTime(n.created_at)}</span>
                </div>
                ${n.status === 'unread' ? `<button class="notif-item-read-btn" title="Mark as read"><i class="fas fa-check"></i></button>` : ''}
            </div>
        `).join('');

        // Attach single mark-read listeners
        document.querySelectorAll('.notif-item-read-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                const item = btn.closest('.notif-item');
                const id = item.dataset.id;
                await markAsRead(id);
                item.classList.remove('unread');
                item.classList.add('read');
                btn.remove();
            });
        });
    }

    /* ═══════════════════════════════════════════════════════════
       3. MARK READ ACTIONS
       ═══════════════════════════════════════════════════════════ */
    markAllBtn.addEventListener('click', async (e) => {
        e.stopPropagation();
        await markAsRead('all');
        fetchNotifications();
    });

    async function markAsRead(id) {
        const fd = new FormData();
        fd.append('action', 'mark_read');
        fd.append('id', id);
        fd.append('csrf_token', config.csrfToken);

        try {
            const res = await fetch(apiEndpoint, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.status === 'success') {
                if (id === 'all') {
                    cachedUnreadCount = 0;
                    badge.style.display = 'none';
                } else {
                    cachedUnreadCount = Math.max(0, cachedUnreadCount - 1);
                    if (cachedUnreadCount === 0) {
                        badge.style.display = 'none';
                    } else {
                        badge.textContent = cachedUnreadCount;
                    }
                }
            }
        } catch (err) {
            console.error('Mark as read failed:', err);
        }
    }

    function formatTime(tString) {
        const d = new Date(tString.replace(/-/g, '/'));
        return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#x27;');
    }

    // Bootstrap loops
    document.addEventListener('DOMContentLoaded', () => {
        fetchNotifications();
        // Poll every 8 seconds for unread count indicator updates
        setInterval(fetchNotifications, 8000);
    });

})();
