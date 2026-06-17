/**
 * Smart AI E-Learning – AI Chatbot Script (Part 12)
 * Manages floating window states, suggestions, typing indicators, and markdown formatting.
 */

'use strict';

(function() {
    // DOM Elements
    const toggleBtn = document.getElementById('chatbot-toggle-btn');
    const closeBtn = document.getElementById('chatbot-close-btn');
    const container = document.getElementById('chatbot-container');
    const messagesArea = document.getElementById('chatbot-messages-area');
    const sendForm = document.getElementById('chatbot-send-form');
    const messageInput = document.getElementById('chatbot-message-input');
    const suggestions = document.getElementById('chatbot-suggestions');

    if (!toggleBtn || !container || !messagesArea) return;

    // Toggle Chatbot Window Open/Close
    toggleBtn.addEventListener('click', () => {
        const isActive = container.classList.toggle('active');
        toggleBtn.classList.toggle('active', isActive);
        if (isActive) {
            messageInput.focus();
            scrollToBottom();
        }
    });

    closeBtn.addEventListener('click', () => {
        container.classList.remove('active');
        toggleBtn.classList.remove('active');
    });

    // Suggestion pills clicks
    suggestions.addEventListener('click', (e) => {
        const pill = e.target.closest('.chatbot-suggestion-pill');
        if (pill) {
            const question = pill.dataset.question;
            sendMessage(question);
        }
    });

    // Form submit
    sendForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const text = messageInput.value.trim();
        if (!text) return;
        
        sendMessage(text);
        messageInput.value = '';
    });

    // Main send message flow
    async function sendMessage(text) {
        // 1. Append user bubble
        appendBubble(text, 'user');
        scrollToBottom();

        // 2. Append typing indicator
        const typingId = appendTypingIndicator();
        scrollToBottom();

        // 3. Make fetch query
        try {
            const response = await fetch('api/index.php/chatbot', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ message: text })
            });
            const data = await response.json();

            // Remove typing indicator
            removeTypingIndicator(typingId);

            if (data.status === 'success') {
                appendBubble(data.reply, 'bot');
            } else {
                appendBubble("Sorry, I encountered an error connecting to the brain. Please try again later.", 'bot');
            }
        } catch (err) {
            removeTypingIndicator(typingId);
            appendBubble("Network issue detected. Please check your internet connection.", 'bot');
            console.error('Chatbot API error:', err);
        }
        scrollToBottom();
    }

    // Helper: Append a message bubble
    function appendBubble(content, sender) {
        const bubble = document.createElement('div');
        bubble.className = `chatbot-bubble ${sender}`;
        
        if (sender === 'bot') {
            // Render markdown bold and links
            bubble.innerHTML = parseMarkdown(content);
        } else {
            bubble.textContent = content;
        }
        messagesArea.appendChild(bubble);
    }

    // Helper: Typing indicator
    function appendTypingIndicator() {
        const indicator = document.createElement('div');
        indicator.className = 'chatbot-bubble bot typing-indicator';
        indicator.id = 'typing-' + Math.random().toString(36).substr(2, 9);
        indicator.innerHTML = `
            <div class="typing-dots" style="display:flex; gap: 4px; padding: 4px 0;">
                <span style="width:6px; height:6px; background:#999; border-radius:50%; animation: bounce 1.4s infinite ease-in-out both;"></span>
                <span style="width:6px; height:6px; background:#999; border-radius:50%; animation: bounce 1.4s infinite ease-in-out both; animation-delay: 0.16s;"></span>
                <span style="width:6px; height:6px; background:#999; border-radius:50%; animation: bounce 1.4s infinite ease-in-out both; animation-delay: 0.32s;"></span>
            </div>
            <style>
                @keyframes bounce {
                    0%, 80%, 100% { transform: scale(0); }
                    40% { transform: scale(1); }
                }
            </style>
        `;
        messagesArea.appendChild(indicator);
        return indicator.id;
    }

    function removeTypingIndicator(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    function scrollToBottom() {
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    // Basic markdown parser for premium formatting
    function parseMarkdown(text) {
        if (!text) return '';
        let html = escapeHtml(text);
        // Bold markdown: **text**
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        // Links markdown: [text](url)
        html = html.replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" style="color:var(--main-color); text-decoration:underline; font-weight:600;">$1</a>');
        // New lines to br
        html = html.replace(/\n/g, '<br>');
        return html;
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#x27;');
    }

})();
