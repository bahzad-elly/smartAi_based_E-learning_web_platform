/**
 * Smart AI E-Learning – AJAX Module (Part 9)
 * Handles: Live Search, Like, Bookmark, Comments, Load More, Toasts, Spinners, Live Validation
 */

'use strict';

/* ═══════════════════════════════════════════════════════════
   0.  UTILITIES – CSRF Token Helper
═══════════════════════════════════════════════════════════ */
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf_token"]');
    return meta ? meta.content : '';
}

function buildFormData(obj) {
    const fd = new FormData();
    for (const [k, v] of Object.entries(obj)) fd.append(k, v);
    fd.append('csrf_token', getCsrfToken());
    return fd;
}

/* ═══════════════════════════════════════════════════════════
   1.  TOAST NOTIFICATION SYSTEM
═══════════════════════════════════════════════════════════ */
let toastContainer = null;

function initToastContainer() {
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        document.body.appendChild(toastContainer);
    }
}

/**
 * Show a toast notification
 * @param {string} message
 * @param {'success'|'error'|'info'|'warning'} type
 * @param {number} duration ms
 */
function showToast(message, type = 'success', duration = 3500) {
    initToastContainer();
    const icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle', warning: 'fa-exclamation-triangle' };
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<i class="fas ${icons[type] || icons.info}"></i><span>${message}</span><button class="toast-close" onclick="this.parentElement.remove()"><i class="fas fa-times"></i></button>`;
    toastContainer.appendChild(toast);

    // Animate in
    requestAnimationFrame(() => toast.classList.add('show'));

    // Auto-dismiss
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 350);
    }, duration);
}

/* ═══════════════════════════════════════════════════════════
   2.  GLOBAL LOADING SPINNER
═══════════════════════════════════════════════════════════ */
let spinnerEl = null;

function showSpinner() {
    if (!spinnerEl) {
        spinnerEl = document.createElement('div');
        spinnerEl.id = 'global-spinner';
        spinnerEl.innerHTML = '<div class="spinner-ring"></div>';
        document.body.appendChild(spinnerEl);
    }
    spinnerEl.classList.add('active');
}

function hideSpinner() {
    if (spinnerEl) spinnerEl.classList.remove('active');
}

/* ═══════════════════════════════════════════════════════════
   3.  LIVE SEARCH
═══════════════════════════════════════════════════════════ */
function initLiveSearch() {
    const input    = document.getElementById('live-search-input');
    const dropdown = document.getElementById('live-search-dropdown');
    if (!input || !dropdown) return;

    let debounceTimer;

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const q = input.value.trim();
        if (q.length < 2) {
            dropdown.classList.remove('active');
            return;
        }
        debounceTimer = setTimeout(() => performLiveSearch(q, input, dropdown), 300);
    });

    // Close dropdown on outside click
    document.addEventListener('click', (e) => {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('active');
        }
    });

    // Keyboard navigation
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') dropdown.classList.remove('active');
    });
}

async function performLiveSearch(q, input, dropdown) {
    try {
        const res  = await fetch(`api/live_search.php?q=${encodeURIComponent(q)}`);
        const data = await res.json();

        if (!data.results || data.results.length === 0) {
            dropdown.innerHTML = `<div class="ls-empty"><i class="fas fa-search"></i> No results for "<strong>${escapeHtml(q)}</strong>"</div>`;
            dropdown.classList.add('active');
            return;
        }

        dropdown.innerHTML = data.results.map(item => `
            <a href="${escapeHtml(item.url)}" class="ls-item">
                <img src="${escapeHtml(item.thumb)}" alt="" onerror="this.src='images/default.png'">
                <div class="ls-item-info">
                    <span class="ls-item-title">${escapeHtml(item.title)}</span>
                    <span class="ls-item-meta"><i class="fas ${item.icon}"></i> ${item.type === 'course' ? 'Course' : 'Lesson'} &bull; ${escapeHtml(item.tutor_name)}</span>
                </div>
            </a>
        `).join('') + `<a href="search_course.php" class="ls-view-all" onclick="document.querySelector('[name=search_course]').value='${encodeURIComponent(q)}'">View all results for "<strong>${escapeHtml(q)}</strong>" →</a>`;

        dropdown.classList.add('active');
    } catch (err) {
        console.error('Live search error:', err);
    }
}

/* ═══════════════════════════════════════════════════════════
   4.  AJAX LIKE BUTTON
═══════════════════════════════════════════════════════════ */
function initLikeButton() {
    const btn = document.getElementById('ajax-like-btn');
    if (!btn) return;

    btn.addEventListener('click', async (e) => {
        e.preventDefault();
        const contentId = btn.dataset.contentId;
        btn.classList.add('loading');

        try {
            const res  = await fetch('api/ajax_like.php', { method: 'POST', body: buildFormData({ content_id: contentId }) });
            const data = await res.json();

            if (data.error) {
                if (data.error === 'login_required') {
                    showToast('Please login to like videos.', 'warning');
                } else {
                    showToast(data.error, 'error');
                }
                return;
            }

            // Toggle heart icon
            const icon  = btn.querySelector('i');
            const count = document.getElementById('like-count');
            icon.className = data.liked ? 'fas fa-heart' : 'far fa-heart';
            if (count) count.textContent = data.count + ' likes';
            btn.classList.toggle('liked', data.liked);

            // Heart burst animation
            btn.classList.add('like-burst');
            setTimeout(() => btn.classList.remove('like-burst'), 600);

            showToast(data.message, data.liked ? 'success' : 'info');
        } catch (err) {
            showToast('Something went wrong. Please try again.', 'error');
        } finally {
            btn.classList.remove('loading');
        }
    });
}

/* ═══════════════════════════════════════════════════════════
   5.  AJAX BOOKMARK BUTTON
═══════════════════════════════════════════════════════════ */
function initBookmarkButton() {
    document.querySelectorAll('.ajax-bookmark-btn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const playlistId = btn.dataset.playlistId;

            try {
                const res  = await fetch('api/ajax_bookmark.php', { method: 'POST', body: buildFormData({ playlist_id: playlistId }) });
                const data = await res.json();

                if (data.error) {
                    if (data.error === 'login_required') showToast('Please login to bookmark courses.', 'warning');
                    else showToast(data.error, 'error');
                    return;
                }

                const icon = btn.querySelector('i');
                if (icon) icon.className = data.bookmarked ? 'fas fa-bookmark' : 'far fa-bookmark';
                btn.classList.toggle('bookmarked', data.bookmarked);
                btn.title = data.bookmarked ? 'Remove Bookmark' : 'Add Bookmark';
                showToast(data.message, data.bookmarked ? 'success' : 'info');
            } catch (err) {
                showToast('Something went wrong.', 'error');
            }
        });
    });
}

/* ═══════════════════════════════════════════════════════════
   6.  AJAX COMMENTS
═══════════════════════════════════════════════════════════ */
function initComments() {
    const form     = document.getElementById('ajax-comment-form');
    const list     = document.getElementById('comments-list');
    const textarea = document.getElementById('comment-box');
    if (!form || !list) return;

    // ── Submit new comment ──────────────────────────────────
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const text      = textarea.value.trim();
        const contentId = form.dataset.contentId;

        if (!text) { showToast('Please write a comment first.', 'warning'); return; }

        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';

        try {
            const res  = await fetch('api/ajax_comment.php', { method: 'POST', body: buildFormData({ action: 'add', content_id: contentId, comment_box: text }) });
            const data = await res.json();

            if (data.error) { showToast(data.error, 'error'); return; }

            // Prepend new comment
            const emptyMsg = list.querySelector('.empty');
            if (emptyMsg) emptyMsg.remove();

            const div = document.createElement('div');
            div.className = 'box comment-new';
            div.dataset.commentId = data.comment_id;
            div.innerHTML = buildCommentHTML(data);
            list.insertBefore(div, list.firstChild);

            textarea.value = '';
            showToast(data.message, 'success');
        } catch (err) {
            showToast('Could not post comment. Try again.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Add Comment';
        }
    });

    // ── Delete comment (event delegation) ──────────────────
    list.addEventListener('click', async (e) => {
        const deleteBtn = e.target.closest('.ajax-delete-comment');
        if (!deleteBtn) return;
        e.preventDefault();
        if (!confirm('Delete this comment?')) return;

        const box       = deleteBtn.closest('.box');
        const commentId = box?.dataset.commentId;
        if (!commentId) return;

        try {
            const res  = await fetch('api/ajax_comment.php', { method: 'POST', body: buildFormData({ action: 'delete', comment_id: commentId }) });
            const data = await res.json();

            if (data.error) { showToast(data.error, 'error'); return; }

            box.style.transition = 'opacity .3s, transform .3s';
            box.style.opacity    = '0';
            box.style.transform  = 'translateX(40px)';
            setTimeout(() => {
                box.remove();
                if (list.querySelectorAll('.box').length === 0) {
                    list.innerHTML = '<p class="empty">No comments yet. Be the first!</p>';
                }
            }, 320);
            showToast(data.message, 'info');
        } catch (err) {
            showToast('Could not delete comment.', 'error');
        }
    });

    // ── Edit comment (event delegation) ────────────────────
    list.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.ajax-edit-comment');
        if (!editBtn) return;
        e.preventDefault();
        const box       = editBtn.closest('.box');
        const commentId = box?.dataset.commentId;
        const textEl    = box?.querySelector('.text');
        if (!box || !textEl) return;

        // If already in edit mode, cancel
        if (box.classList.contains('editing')) {
            cancelEditMode(box, textEl);
            return;
        }

        // Enter edit mode
        box.classList.add('editing');
        const original = textEl.textContent;
        const editArea = document.createElement('div');
        editArea.className = 'edit-mode';
        editArea.innerHTML = `
            <textarea class="edit-textarea" maxlength="1000">${original}</textarea>
            <div class="edit-actions">
                <button class="inline-btn save-edit-btn" data-comment-id="${commentId}"><i class="fas fa-save"></i> Save</button>
                <button class="inline-option-btn cancel-edit-btn"><i class="fas fa-times"></i> Cancel</button>
            </div>`;
        textEl.after(editArea);
        editArea.querySelector('textarea').focus();

        // Save
        editArea.querySelector('.save-edit-btn').addEventListener('click', async () => {
            const newText = editArea.querySelector('textarea').value.trim();
            if (!newText) { showToast('Comment cannot be empty.', 'warning'); return; }

            try {
                const res  = await fetch('api/ajax_comment.php', { method: 'POST', body: buildFormData({ action: 'edit', comment_id: commentId, comment_box: newText }) });
                const data = await res.json();
                if (data.error) { showToast(data.error, 'error'); return; }
                textEl.textContent = data.comment_text;
                cancelEditMode(box, textEl);
                showToast(data.message, 'success');
            } catch (err) {
                showToast('Could not update comment.', 'error');
            }
        });

        // Cancel
        editArea.querySelector('.cancel-edit-btn').addEventListener('click', () => cancelEditMode(box, textEl));
    });
}

function cancelEditMode(box, textEl) {
    const editArea = box.querySelector('.edit-mode');
    if (editArea) editArea.remove();
    box.classList.remove('editing');
}

function buildCommentHTML(data) {
    return `
        <div class="user">
            <img src="${escapeHtml(data.user_image)}" alt="" onerror="this.src='images/default.png'">
            <div>
                <h3>${escapeHtml(data.user_name)}</h3>
                <span>${new Date(data.date).toLocaleDateString()}</span>
            </div>
        </div>
        <p class="text">${escapeHtml(data.comment_text)}</p>
        <div class="flex-btn" style="margin-top:1rem;">
            <button class="inline-option-btn ajax-edit-comment"><i class="fas fa-edit"></i> Edit</button>
            <button class="inline-delete-btn ajax-delete-comment"><i class="fas fa-trash"></i> Delete</button>
        </div>`;
}

/* ═══════════════════════════════════════════════════════════
   7.  LOAD MORE COURSES
═══════════════════════════════════════════════════════════ */
function initLoadMore() {
    const btn       = document.getElementById('load-more-btn');
    const container = document.getElementById('courses-box-container');
    if (!btn || !container) return;

    let offset = parseInt(btn.dataset.offset || 6);
    const limit = parseInt(btn.dataset.limit  || 6);

    btn.addEventListener('click', async () => {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

        try {
            const q   = document.getElementById('course-search-filter')?.value || '';
            const url = `api/load_more_courses.php?offset=${offset}&limit=${limit}${q ? '&q=' + encodeURIComponent(q) : ''}`;
            const res = await fetch(url);
            const data = await res.json();

            if (data.error) { showToast('Failed to load courses.', 'error'); return; }

            data.courses.forEach(c => {
                const div = document.createElement('div');
                div.className = 'box';
                div.innerHTML = `
                    <div class="tutor">
                        <img src="${escapeHtml(c.tutor_image)}" alt="" onerror="this.src='images/default.png'">
                        <div><h3>${escapeHtml(c.tutor_name)}</h3><span>${c.date}</span></div>
                    </div>
                    <img src="${escapeHtml(c.thumb)}" class="thumb" alt="${escapeHtml(c.title)}" onerror="this.src='images/default.png'">
                    <h3 class="title">${escapeHtml(c.title)}</h3>
                    <a href="${escapeHtml(c.url)}" class="inline-btn">View Playlist</a>`;
                container.appendChild(div);
            });

            offset += data.courses.length;
            btn.dataset.offset = offset;

            if (!data.has_more) {
                btn.style.display = 'none';
                showToast('All courses loaded!', 'info', 2000);
            } else {
                btn.innerHTML = '<i class="fas fa-plus"></i> Load More Courses';
                btn.disabled  = false;
            }
        } catch (err) {
            showToast('Network error. Please try again.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-plus"></i> Load More Courses';
        }
    });
}

/* ═══════════════════════════════════════════════════════════
   8.  LIVE FORM VALIDATION
═══════════════════════════════════════════════════════════ */
function initLiveValidation() {
    // Email fields
    document.querySelectorAll('input[type="email"]').forEach(field => {
        field.addEventListener('input', () => {
            const valid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value);
            setFieldState(field, valid, valid ? '' : 'Enter a valid email address.');
        });
    });

    // Password fields (min 6 chars)
    document.querySelectorAll('input[type="password"]').forEach(field => {
        field.addEventListener('input', () => {
            const valid = field.value.length >= 6;
            setFieldState(field, valid, valid ? '' : 'Password must be at least 6 characters.');
        });
    });

    // Confirm password
    const confirmPass = document.getElementById('confirm_password') || document.getElementById('cpass');
    const mainPass    = document.getElementById('password') || document.getElementById('pass');
    if (confirmPass && mainPass) {
        confirmPass.addEventListener('input', () => {
            const valid = confirmPass.value === mainPass.value;
            setFieldState(confirmPass, valid, valid ? '' : 'Passwords do not match.');
        });
    }

    // Required fields – show helper on blur
    document.querySelectorAll('input[required]:not([type="password"]):not([type="email"]):not([type="hidden"])').forEach(field => {
        field.addEventListener('blur', () => {
            if (field.value.trim() === '') setFieldState(field, false, 'This field is required.');
            else setFieldState(field, true, '');
        });
    });

    // Textarea required
    document.querySelectorAll('textarea[required]').forEach(field => {
        field.addEventListener('blur', () => {
            if (field.value.trim() === '') setFieldState(field, false, 'Please write something.');
            else setFieldState(field, true, '');
        });
    });
}

function setFieldState(field, valid, errorMsg) {
    field.classList.toggle('field-valid',   valid);
    field.classList.toggle('field-invalid', !valid);

    let hint = field.parentElement.querySelector('.field-hint');
    if (!valid && errorMsg) {
        if (!hint) {
            hint = document.createElement('span');
            hint.className = 'field-hint';
            field.parentElement.appendChild(hint);
        }
        hint.textContent = errorMsg;
        hint.style.display = 'block';
    } else if (hint) {
        hint.style.display = 'none';
    }
}

/* ═══════════════════════════════════════════════════════════
   9.  AJAX CERTIFICATE VERIFICATION
═══════════════════════════════════════════════════════════ */
function initCertVerification() {
    const form    = document.getElementById('ajax-verify-form');
    const input   = document.getElementById('cert-code-input');
    const result  = document.getElementById('verify-result');
    if (!form || !result) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const code = input.value.trim().toUpperCase();
        if (!code) { showToast('Please enter a certificate code.', 'warning'); return; }

        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
        result.innerHTML = '';

        try {
            const res  = await fetch(`api/verify_cert.php?code=${encodeURIComponent(code)}`);
            const data = await res.json();
            renderVerifyResult(data, result);
        } catch (err) {
            result.innerHTML = '<div class="verify-hero invalid"><div class="big-icon">⚠️</div><h2>Network Error</h2><p>Could not reach the verification server. Please try again.</p></div>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-search"></i> Verify Now';
        }
    });

    // Auto-uppercase input
    if (input) {
        input.addEventListener('input', () => { input.value = input.value.toUpperCase(); });
    }
}

function renderVerifyResult(data, container) {
    if (data.valid) {
        container.innerHTML = `
            <div class="verify-hero valid animate-in">
                <div class="big-icon">✅</div>
                <h2>Certificate is Valid!</h2>
                <p>This certificate has been verified as authentic and issued by Smart AI E-Learning Platform.</p>
            </div>
            <div class="valid-badge"><i class="fas fa-check-circle"></i> Verified Authentic Certificate</div>
            <div class="cert-details">
                <h3><i class="fas fa-certificate" style="color:#f39c12;"></i> Certificate Details</h3>
                ${detailRow('fa-user-graduate', 'Student Name', data.student_name)}
                ${detailRow('fa-brain', 'Quiz / Assessment', data.quiz_title)}
                ${detailRow('fa-graduation-cap', 'Course', data.course_title)}
                ${detailRow('fa-calendar-check', 'Date of Issue', data.issued_date)}
                ${detailRow('fa-fingerprint', 'Certificate ID', `<span style="font-family:monospace;">${data.cert_code}</span>`)}
                ${data.score !== null ? detailRow('fa-star', 'Score Achieved', data.score + '%') : ''}
            </div>`;
        showToast('Certificate verified successfully!', 'success');
    } else {
        container.innerHTML = `
            <div class="verify-hero invalid animate-in">
                <div class="big-icon">❌</div>
                <h2>Certificate Not Found</h2>
                <p>${escapeHtml(data.message || 'The code you entered does not match any certificate in our system.')}</p>
            </div>`;
        showToast('Certificate not found.', 'error');
    }
}

function detailRow(icon, label, value) {
    return `<div class="detail-row">
        <div class="icon"><i class="fas ${icon}"></i></div>
        <div class="info"><label>${label}</label><span>${value}</span></div>
    </div>`;
}

/* ═══════════════════════════════════════════════════════════
   10. XSS-SAFE HTML ESCAPE HELPER
═══════════════════════════════════════════════════════════ */
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#x27;');
}

/* ═══════════════════════════════════════════════════════════
   11. INITIALISE ALL MODULES ON DOM READY
═══════════════════════════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
    initLiveSearch();
    initLikeButton();
    initBookmarkButton();
    initComments();
    initLoadMore();
    initLiveValidation();
    initCertVerification();
});
