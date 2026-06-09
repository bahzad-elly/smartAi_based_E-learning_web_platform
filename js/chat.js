/**
 * Smart AI E-Learning – Chat JS Module (Part 10)
 * Manages real-time polling updates, scroll lock adjustments, typing indicators, and file uploads.
 */

'use strict';

(function() {
    const config = window.chatConfig || { role: 'user', csrfToken: '' };
    const prefix = config.endpointPrefix || '';
    const apiEndpoint = `${prefix}api/ajax_chat.php`;

    let activeContactId = null;
    let pollInterval = null;
    let statusInterval = null;
    let isTyping = false;
    let typingTimer = null;
    let lastMessageCount = 0;

    // DOM Cache
    const contactsList = document.getElementById('chat-contacts-list');
    const welcomeScreen = document.getElementById('chat-welcome-screen');
    const activeThread = document.getElementById('chat-active-thread');
    const recipientImage = document.getElementById('chat-recipient-image');
    const recipientName = document.getElementById('chat-recipient-name');
    const recipientStatus = document.getElementById('chat-recipient-status');
    const messagesArea = document.getElementById('chat-messages-area');
    const sendForm = document.getElementById('chat-send-form');
    const messageInput = document.getElementById('chat-input-message');
    const attachmentInput = document.getElementById('attachment-input');
    const attachmentPreview = document.getElementById('attachment-preview-container');
    const attachmentName = document.getElementById('attachment-name');
    const removeAttachmentBtn = document.getElementById('remove-attachment-btn');
    const typingIndicator = document.getElementById('typing-indicator');

    /* ═══════════════════════════════════════════════════════════
       1. INITIALIZE CONTACTS LIST
       ═══════════════════════════════════════════════════════════ */
    async function loadContacts() {
        try {
            const res = await fetch(`${apiEndpoint}?action=fetch_contacts`);
            const data = await res.json();

            if (data.status === 'success') {
                if (data.contacts.length === 0) {
                    contactsList.innerHTML = `<div class="ls-empty"><i class="far fa-user"></i> No conversations active.</div>`;
                    return;
                }

                contactsList.innerHTML = data.contacts.map(c => `
                    <div class="chat-contact-item ${activeContactId === c.id ? 'active' : ''}" data-id="${c.id}" data-name="${escapeHtml(c.name)}" data-image="${escapeHtml(c.image || 'default.png')}">
                        <div class="contact-avatar-wrapper">
                            <img src="${prefix}uploaded_files/${escapeHtml(c.image)}" onerror="this.src='${prefix}images/default.png'">
                            <span class="status-indicator ${c.online ? 'online' : 'offline'}"></span>
                        </div>
                        <div class="contact-info">
                            <h4 class="contact-name">${escapeHtml(c.name)}</h4>
                            <p class="contact-last-msg">${escapeHtml(c.last_msg || 'No messages')}</p>
                        </div>
                    </div>
                `).join('');

                // Re-bind click event
                document.querySelectorAll('.chat-contact-item').forEach(item => {
                    item.addEventListener('click', () => {
                        selectContact(item.dataset.id, item.dataset.name, item.dataset.image);
                    });
                });
            }
        } catch (err) {
            console.error('Fetch contacts failed:', err);
        }
    }

    /* ═══════════════════════════════════════════════════════════
       2. SELECT ACTIVE CONVERSATION
       ═══════════════════════════════════════════════════════════ */
    function selectContact(id, name, image) {
        activeContactId = id;
        lastMessageCount = 0;

        // UI highlight adjustments
        document.querySelectorAll('.chat-contact-item').forEach(item => {
            item.classList.toggle('active', item.dataset.id === id);
        });

        // Swap viewport visibility
        welcomeScreen.style.display = 'none';
        activeThread.style.display = 'flex';

        recipientImage.src = `${prefix}uploaded_files/${image}`;
        recipientImage.onerror = () => { recipientImage.src = `${prefix}images/default.png`; };
        recipientName.textContent = name;

        // Reset elements
        messagesArea.innerHTML = '';
        messageInput.value = '';
        resetAttachment();

        // Start real-time loops
        clearInterval(pollInterval);
        clearInterval(statusInterval);

        fetchMessages();
        pollInterval = setInterval(fetchMessages, 2500);

        checkRecipientStatus();
        statusInterval = setInterval(checkRecipientStatus, 3000);
    }

    /* ═══════════════════════════════════════════════════════════
       3. FETCH MESSAGES LOOP
       ═══════════════════════════════════════════════════════════ */
    async function fetchMessages() {
        if (!activeContactId) return;

        try {
            const res = await fetch(`${apiEndpoint}?action=fetch_messages&target_id=${activeContactId}`);
            const data = await res.json();

            if (data.status === 'success') {
                const msgs = data.messages || [];
                
                // Perform fresh render only if count changes
                if (msgs.length !== lastMessageCount) {
                    renderMessages(msgs);
                    lastMessageCount = msgs.length;
                    scrollToBottom();
                }
            }
        } catch (err) {
            console.error('Fetch messages failed:', err);
        }
    }

    function renderMessages(msgs) {
        messagesArea.innerHTML = msgs.map(m => {
            const isMe = (config.role === 'user' && m.sender_type === 'user') || (config.role === 'tutor' && m.sender_type === 'tutor');
            const bubbleClass = isMe ? 'me' : 'them';

            let mediaMarkup = '';
            if (m.attachment) {
                const ext = m.attachment.split('.').pop().toLowerCase();
                const path = `${prefix}uploaded_files/${m.attachment}`;
                if (['png', 'jpg', 'jpeg'].includes(ext)) {
                    mediaMarkup = `<div class="msg-img-attach"><img src="${path}" class="chat-media-preview" onclick="window.open('${path}')"></div>`;
                } else {
                    mediaMarkup = `<div class="msg-file-attach"><a href="${path}" target="_blank"><i class="fas fa-file-arrow-down"></i> ${escapeHtml(m.attachment)}</a></div>`;
                }
            }

            return `
                <div class="message-row ${bubbleClass}">
                    <div class="message-bubble">
                        ${m.message ? `<p class="msg-txt">${escapeHtml(m.message)}</p>` : ''}
                        ${mediaMarkup}
                        <span class="msg-time">${formatTime(m.created_at)}</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    /* ═══════════════════════════════════════════════════════════
       4. SEND MESSAGES
       ═══════════════════════════════════════════════════════════ */
    sendForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const text = messageInput.value.trim();
        const file = attachmentInput.files[0];

        if (!text && !file) return;

        const fd = new FormData();
        fd.append('action', 'send_message');
        fd.append('target_id', activeContactId);
        fd.append('message', text);
        fd.append('csrf_token', config.csrfToken);
        if (file) {
            fd.append('attachment', file);
        }

        messageInput.value = '';
        resetAttachment();
        stopTypingNotification();

        try {
            const res = await fetch(apiEndpoint, { method: 'POST', body: fd });
            const data = await res.json();

            if (data.status === 'success') {
                fetchMessages();
            } else if (data.error) {
                alert(data.error);
            }
        } catch (err) {
            console.error('Send message failed:', err);
        }
    });

    /* ═══════════════════════════════════════════════════════════
       5. RECIPIENT ONLINE STATUS & TYPING INDICATORS
       ═══════════════════════════════════════════════════════════ */
    async function checkRecipientStatus() {
        if (!activeContactId) return;

        try {
            const res = await fetch(`${apiEndpoint}?action=status&target_id=${activeContactId}`);
            const data = await res.json();

            if (data.status === 'success') {
                // Status Light
                if (data.online) {
                    recipientStatus.className = 'status-online';
                    recipientStatus.innerHTML = '<i class="fas fa-circle"></i> Online';
                } else {
                    recipientStatus.className = 'status-offline';
                    recipientStatus.innerHTML = '<i class="fas fa-circle"></i> Offline';
                }

                // Typing Status
                typingIndicator.style.display = data.typing ? 'block' : 'none';
            }
        } catch (err) {
            console.error('Status check failed:', err);
        }
    }

    // Typing Event Triggers
    messageInput.addEventListener('input', () => {
        if (!isTyping) {
            isTyping = true;
            sendTypingNotification(1);
        }
        clearTimeout(typingTimer);
        typingTimer = setTimeout(stopTypingNotification, 3000);
    });

    function stopTypingNotification() {
        isTyping = false;
        sendTypingNotification(0);
    }

    async function sendTypingNotification(state) {
        if (!activeContactId) return;
        const fd = new FormData();
        fd.append('action', 'typing');
        fd.append('target_id', activeContactId);
        fd.append('is_typing', state);
        fd.append('csrf_token', config.csrfToken);

        try {
            await fetch(apiEndpoint, { method: 'POST', body: fd });
        } catch (err) {
            // Silence typing sync errors
        }
    }

    /* ═══════════════════════════════════════════════════════════
       6. UI ATTACHMENTS & UTILS
       ═══════════════════════════════════════════════════════════ */
    attachmentInput.addEventListener('change', () => {
        const file = attachmentInput.files[0];
        if (file) {
            attachmentName.textContent = file.name;
            attachmentPreview.style.display = 'flex';
        }
    });

    removeAttachmentBtn.addEventListener('click', resetAttachment);

    function resetAttachment() {
        attachmentInput.value = '';
        attachmentPreview.style.display = 'none';
    }

    function scrollToBottom() {
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    function formatTime(tString) {
        const d = new Date(tString.replace(/-/g, '/'));
        return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#x27;');
    }

    // Bootstrap DOM actions
    document.addEventListener('DOMContentLoaded', () => {
        loadContacts();
        // Periodically refresh contact list (online status update)
        setInterval(loadContacts, 8000);
    });

})();
