<?php
// Ensure session started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$chatSessionId = $_SESSION['chat_session_id'] ?? 0;
?>
<div id="chat-widget" class="chat-widget">
    <!-- Chat Button -->
    <button id="chat-toggle" class="chat-toggle">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
        </svg>
    </button>

    <!-- Chat Box -->
    <div id="chat-box" class="chat-box">
        <div class="chat-header">
            <h3>Live Support</h3>
            <div style="display:flex; align-items:center; gap:10px;">
                <button id="chat-end" class="chat-close" style="font-size: 14px; font-weight: normal; display:none;" title="End Chat">End</button>
                <button id="chat-close" class="chat-close">&times;</button>
            </div>
        </div>

        <!-- Pre-Chat Form -->
        <div id="chat-form" class="chat-body" style="<?php echo $chatSessionId ? 'display:none;' : ''; ?>">
            <p style="margin-bottom: 20px; color: #666; font-size: 14px;">Please fill out the form below to start chatting with us.</p>
            <div class="form-group">
                <input type="text" id="chat-name" placeholder="Your Name" class="chat-input" required>
            </div>
            <div class="form-group">
                <input type="email" id="chat-email" placeholder="Your Email" class="chat-input" required>
            </div>
            <div class="form-group">
                <input type="tel" id="chat-phone" placeholder="Phone (Optional)" class="chat-input">
            </div>
            <button id="chat-start-btn" class="chat-btn">Start Chat</button>
        </div>

        <!-- Chat Conversation -->
        <div id="chat-conversation" class="chat-conversation" style="<?php echo $chatSessionId ? 'display:flex;' : 'display:none;'; ?>">
            <div id="chat-messages-list" class="chat-messages-list">
                <!-- Messages go here -->
                <div class="system-message">Welcome! How can we help you?</div>
            </div>
            <div class="chat-input-area">
                <textarea id="chat-message-input" placeholder="Type a message..." rows="1"></textarea>
                <button id="chat-send-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="22" y1="2" x2="11" y2="13"></line>
                        <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.chat-widget { position: fixed; bottom: 20px; right: 20px; z-index: 10000; font-family: 'Segoe UI', sans-serif; }
.chat-toggle { width: 60px; height: 60px; border-radius: 50%; background: #c53940; color: white; border: none; cursor: pointer; box-shadow: 0 1px 0 rgba(0, 0, 0, .16), 0 1px 2px rgba(0, 0, 0, .26); transition: transform 0.2s; display: flex; align-items: center; justify-content: center; }
.chat-toggle:hover { transform: scale(1.1); }
.chat-box { position: absolute; bottom: 80px; right: 0; width: 350px; height: 450px; background: white; border-radius: 12px; box-shadow: 0 5px 25px rgba(0,0,0,0.2); display: none; flex-direction: column; overflow: hidden; opacity: 0; transition: opacity 0.3s; pointer-events: none; }
.chat-widget.active .chat-box { display: flex; opacity: 1; pointer-events: auto; }
.chat-header { background: #c53940; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0; }
.chat-header h3 { margin: 0; font-size: 16px; }
.chat-close { background: none; border: none; color: white; font-size: 24px; cursor: pointer; padding: 0 5px; }
.chat-body { padding: 20px; flex: 1; overflow-y: auto; }
.chat-input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; margin-bottom: 10px; box-sizing: border-box; }
.chat-btn { width: 100%; padding: 10px; background: #c53940; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
.chat-conversation { flex: 1; display: flex; flex-direction: column; overflow: hidden; min-height: 0; }
.chat-messages-list { flex: 1; padding: 15px; overflow-y: auto; background: #f9f9f9; display: flex; flex-direction: column; gap: 10px; min-height: 0; }
.chat-input-area { padding: 10px; background: white; border-top: 1px solid #eee; display: flex; gap: 10px; align-items: center; flex-shrink: 0; }
#chat-message-input { flex: 1; border: 1px solid #ddd; border-radius: 20px; padding: 8px 15px; resize: none; outline: none; }
#chat-send-btn { background: #c53940; color: white; border: none; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; }
.chat-msg { padding: 8px 12px; border-radius: 12px; max-width: 80%; font-size: 14px; line-height: 1.4; word-wrap: break-word; }
.chat-msg.user { align-self: flex-end; background: #c53940; color: white; border-bottom-right-radius: 2px; }
.chat-msg.admin { align-self: flex-start; background: #e0e0e0; color: #333; border-bottom-left-radius: 2px; }
.system-message { text-align: center; font-size: 12px; color: #999; margin: 10px 0; }
@media (max-width: 480px) {
    .chat-box { 
        position: absolute;
        width: 320px;
        max-width: 90vw;
        height: 450px; /* Start medium/tall to show content */
        /* Dynamic height constraint: */
        position: fixed;
        bottom: 90px;
        right: 20px;
        left: auto;
        
        /* Safer max-height to ensure header stays visible */
        max-height: 70vh; 
        
        border-radius: 12px;
        z-index: 10001;
        display: none;
        flex-direction: column;
    }
    .chat-widget.active .chat-box {
        display: flex;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const widget = document.getElementById('chat-widget');
    const toggleBtn = document.getElementById('chat-toggle');
    const closeBtn = document.getElementById('chat-close');
    const endBtn = document.getElementById('chat-end');
    const box = document.getElementById('chat-box');
    const startBtn = document.getElementById('chat-start-btn');
    const sendBtn = document.getElementById('chat-send-btn');
    const msgInput = document.getElementById('chat-message-input');
    const msgsList = document.getElementById('chat-messages-list');
    
    let sessionId = <?php echo $chatSessionId; ?>;
    let lastMsgId = 0;
    let pollInterval = null;

    // --- Drag Logic ---
    let isDragging = false;
    let currentX;
    let currentY;
    let initialX;
    let initialY;
    let xOffset = 0;
    let yOffset = 0;

    toggleBtn.addEventListener('mousedown', dragStart);
    toggleBtn.addEventListener('touchstart', dragStart, {passive: false});

    document.addEventListener('mousemove', drag);
    document.addEventListener('touchmove', drag, {passive: false});

    document.addEventListener('mouseup', dragEnd);
    document.addEventListener('touchend', dragEnd);

    // Prevent click if dragged
    let wasDragged = false;
    
    toggleBtn.addEventListener('click', (e) => {
        if (wasDragged) {
            wasDragged = false;
            return;
        }
        toggleWidget();
    });

    function dragStart(e) {
        if (e.type === "touchstart") {
            initialX = e.touches[0].clientX - xOffset;
            initialY = e.touches[0].clientY - yOffset;
        } else {
            initialX = e.clientX - xOffset;
            initialY = e.clientY - yOffset;
        }

        if (e.target === toggleBtn || toggleBtn.contains(e.target)) {
            isDragging = true;
        }
    }

    function drag(e) {
        if (isDragging) {
            e.preventDefault();
            
            if (e.type === "touchmove") {
                currentX = e.touches[0].clientX - initialX;
                currentY = e.touches[0].clientY - initialY;
            } else {
                currentX = e.clientX - initialX;
                currentY = e.clientY - initialY;
            }

            xOffset = currentX;
            yOffset = currentY;

            // Only consider it a drag if moved more than 3 pixels
            if (Math.abs(currentX) > 3 || Math.abs(currentY) > 3) {
                 setTranslate(currentX, currentY, widget);
                 wasDragged = true;
            }
        }
    }

    function setTranslate(xPos, yPos, el) {
        el.style.transform = "translate3d(" + xPos + "px, " + yPos + "px, 0)";
    }

    function dragEnd(e) {
        initialX = currentX;
        initialY = currentY;
        isDragging = false;
        // Reset wasDragged after a short delay to allow click event to check it
        setTimeout(() => { if(!isDragging) wasDragged = false; }, 100);
    }
    
// Check for active session on load
    const currentSessionId = '<?php echo isset($_SESSION['chat_session_id']) ? $_SESSION['chat_session_id'] : ''; ?>';
    
    // Restore state from localStorage
    const isOpen = localStorage.getItem('chat_open') === 'true';
    if (isOpen) {
        widget.classList.add('active');
        if (currentSessionId) startPolling();
    } else if (currentSessionId) {
        // Just poll for unread, don't open
        // (Optional: Add badge logic here later)
        startPolling();
    }

    function toggleWidget() {
        widget.classList.toggle('active');
        const isActive = widget.classList.contains('active');
        localStorage.setItem('chat_open', isActive);
        
        if (isActive) {
            if (sessionId) startPolling();
        }
    }

    closeBtn.addEventListener('click', () => {
        widget.classList.remove('active');
        localStorage.setItem('chat_open', 'false');
    });

    startBtn.addEventListener('click', () => {
        const name = document.getElementById('chat-name').value;
        const email = document.getElementById('chat-email').value;
        const phone = document.getElementById('chat-phone').value;

        if (!name || !email) {
            alert('Name and Email are required');
            return;
        }

        const formData = new FormData();
        formData.append('name', name);
        formData.append('email', email);
        formData.append('phone', phone);
        
    <?php if (isset($_SESSION['user_id'])): ?>
        formData.append('user_id', <?php echo $_SESSION['user_id']; ?>);
        <?php endif; ?>

        fetch('<?php echo BASE_URL; ?>/api/support/start.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                sessionId = data.session_id;
                document.getElementById('chat-form').style.display = 'none';
                document.getElementById('chat-conversation').style.display = 'flex';
                document.getElementById('chat-end').style.display = 'block'; // Show end button
                startPolling();
            } else {
                alert(data.message || 'Error starting chat');
            }
        });
    });

    // End Chat Logic
    endBtn.addEventListener('click', () => {
        if (!confirm('Are you sure you want to end this chat? History will be cleared.')) return;
        
        fetch('<?php echo BASE_URL; ?>/api/support/end.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `session_id=${sessionId}`
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                sessionId = null;
                localStorage.removeItem('chat_open');
                location.reload(); // Simple reload to reset state
            }
        });
    });

    function appendMsg(msg) {
        // Avoid dupes
        if (document.getElementById('msg-'+msg.id)) return;
        
        const div = document.createElement('div');
        div.id = 'msg-'+msg.id;
        div.className = `chat-msg ${msg.sender_type}`;
        div.innerText = msg.message;
        msgsList.appendChild(div);
        msgsList.scrollTop = msgsList.scrollHeight;
        lastMsgId = Math.max(lastMsgId, msg.id);
    }

    function sendMsg() {
        const text = msgInput.value.trim();
        if (!text) return;
        
        // Optimistic UI
        // Actually simpler to just wait for sync or confirm
        msgInput.value = '';
        
        const formData = new FormData();
        formData.append('session_id', sessionId);
        formData.append('message', text);
        formData.append('sender', 'user');

        fetch('<?php echo BASE_URL; ?>/api/support/send.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                syncMessages();
            }
        });
    }

    sendBtn.addEventListener('click', sendMsg);
    msgInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMsg();
        }
    });

    // Typing Logic
    let typingTimer;
    msgInput.addEventListener('input', () => {
        if (typingTimer) clearTimeout(typingTimer);
        
        // Throttled update
        if (!window.lastTypingTime || Date.now() - window.lastTypingTime > 2000) {
            window.lastTypingTime = Date.now();
            fetch('<?php echo BASE_URL; ?>/api/support/typing.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `session_id=${sessionId}&role=user`
            });
        }
        
        typingTimer = setTimeout(() => {
            fetch('<?php echo BASE_URL; ?>/api/support/typing.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `session_id=${sessionId}&role=user&status=stopped`
            });
            window.lastTypingTime = 0; // Reset throttle
        }, 1000);
    });

    // Handle Typing Indicator in Sync
    function syncMessages() {
        if (!sessionId) return;
        fetch(`<?php echo BASE_URL; ?>/api/support/sync.php?session_id=${sessionId}&last_id=${lastMsgId}&context=user`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.messages.length > 0) {
                    data.messages.forEach(appendMsg);
                    // Hide typing indicator if message received
                    updateTypingIndicator(false);
                }
                updateTypingIndicator(data.typing);
            }
        });
    }

    function updateTypingIndicator(isTyping) {
        let indicator = document.getElementById('typing-indicator');
        if (isTyping) {
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.id = 'typing-indicator';
                indicator.className = 'chat-msg admin typing-indicator';
                indicator.innerHTML = '<span class="dot"></span><span class="dot"></span><span class="dot"></span>';
                msgsList.appendChild(indicator);
                msgsList.scrollTop = msgsList.scrollHeight;
            }
        } else if (indicator) {
            indicator.remove();
        }
    }

    // CSS for Typing
    if (!document.getElementById('typing-style')) {
        const style = document.createElement('style');
        style.id = 'typing-style';
        style.textContent = `
            .typing-indicator { align-self: flex-start; background: #e0e0e0; padding: 10px 15px; border-radius: 20px; border-bottom-left-radius: 2px; display: flex; gap: 4px; width: fit-content; }
            .typing-indicator .dot { width: 8px; height: 8px; background: #999; border-radius: 50%; animation: typing 1.4s infinite ease-in-out both; }
            .typing-indicator .dot:nth-child(1) { animation-delay: -0.32s; }
            .typing-indicator .dot:nth-child(2) { animation-delay: -0.16s; }
            @keyframes typing { 
                0%, 80%, 100% { transform: scale(0); } 
                40% { transform: scale(1); } 
            }
        `;
        document.head.appendChild(style);
    }

    function startPolling() {
        if (pollInterval) clearInterval(pollInterval);
        syncMessages(); // Initial
        pollInterval = setInterval(syncMessages, 3000);
    }

    // Auto-start polling if session exists
    if (sessionId) {
        document.getElementById('chat-end').style.display = 'block';
        startPolling();
    }
});
</script>
