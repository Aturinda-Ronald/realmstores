<?php
require_once '../config.php';
require_once 'includes/functions.php';

requireLogin();

// Fetch active sessions
// Order by last updated descending
$stmt = $pdo->query("
    SELECT cs.*, 
    (SELECT COUNT(*) FROM chat_messages cm WHERE cm.session_id = cs.id AND cm.sender_type = 'user' AND cm.is_read = 0) as unread_count,
    (SELECT message FROM chat_messages cm WHERE cm.session_id = cs.id ORDER BY id DESC LIMIT 1) as last_message
    FROM chat_sessions cs 
    ORDER BY cs.updated_at DESC
");
$sessions = $stmt->fetchAll();

// If specific session selected
$currentSessionId = isset($_GET['session']) ? (int)$_GET['session'] : 0;
$currentSession = null;
if ($currentSessionId) {
    $stmt = $pdo->prepare("SELECT * FROM chat_sessions WHERE id = ?");
    $stmt->execute([$currentSessionId]);
    $currentSession = $stmt->fetch();
}

include 'includes/header.php';
?>

<style>
/* Modern Chat Layout */
.chat-layout { display: flex; height: calc(100vh - 130px); border: 1px solid #e0e0e0; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05); font-family: 'Inter', sans-serif; }
.chat-sidebar { width: 320px; border-right: 1px solid #f0f0f0; background: #fff; display: flex; flex-direction: column; }
.chat-main { flex: 1; display: flex; flex-direction: column; background: #f9fafb; position: relative; }

/* Sidebar */
.sidebar-header { padding: 20px; border-bottom: 1px solid #f0f0f0; background: #fff; }
.sidebar-header h3 { margin: 0; font-size: 18px; color: #1a1a1a; font-weight: 700; }
.session-list { flex: 1; overflow-y: auto; }
.session-item { padding: 15px 20px; border-bottom: 1px solid #f5f5f5; cursor: pointer; transition: all 0.2s; position: relative; }
.session-item:hover { background: #f8f9fa; }
.session-item.active { background: #f0f7ff; border-left: 4px solid #007bff; }
.session-top { display: flex; justify-content: space-between; margin-bottom: 6px; }
.session-name { font-weight: 600; font-size: 14px; color: #333; }
.session-time { font-size: 11px; color: #999; }
.session-preview { font-size: 13px; color: #666; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 90%; }
.session-badge { background: #ff3b30; color: white; padding: 2px 8px; border-radius: 12px; font-size: 10px; font-weight: bold; position: absolute; right: 15px; top: 35px; box-shadow: 0 2px 4px rgba(255,59,48,0.3); }

/* Chat Area */
.chat-header { padding: 15px 25px; border-bottom: 1px solid #e0e0e0; background: #fff; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02); z-index: 10; }
.user-meta h4 { margin: 0; font-size: 16px; color: #1a1a1a; }
.user-meta span { font-size: 12px; color: #666; display: flex; align-items: center; gap: 5px; margin-top: 2px; }
.status-dot { width: 8px; height: 8px; background: #28a745; border-radius: 50%; display: inline-block; }

.chat-messages { flex: 1; padding: 25px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; scroll-behavior: smooth; }
.message-wrapper { display: flex; flex-direction: column; max-width: 70%; position: relative; }
.message-wrapper.user { align-self: flex-start; align-items: flex-start; }
.message-wrapper.admin { align-self: flex-end; align-items: flex-end; }

.message-bubble { padding: 12px 18px; border-radius: 18px; font-size: 14px; line-height: 1.5; position: relative; word-wrap: break-word; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
.message-wrapper.user .message-bubble { background: #fff; color: #333; border-bottom-left-radius: 4px; border: 1px solid #f0f0f0; }
.message-wrapper.admin .message-bubble { background: #007bff; color: white; border-bottom-right-radius: 4px; }
.message-time { font-size: 11px; color: #adb5bd; margin-top: 5px; padding: 0 4px; }

.chat-input-area { padding: 20px; border-top: 1px solid #e0e0e0; background: #fff; display: flex; gap: 15px; align-items: flex-end; }
#messageInput { flex: 1; border: 1px solid #e0e0e0; border-radius: 24px; padding: 12px 20px; font-size: 14px; resize: none; outline: none; transition: border-color 0.2s; background: #f8f9fa; max-height: 100px; }
#messageInput:focus { border-color: #007bff; background: #fff; }
.send-btn { background: #007bff; color: white; border: none; width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.1s, background 0.2s; flex-shrink: 0; }
.send-btn:hover { background: #0056b3; transform: scale(1.05); }
.send-btn:active { transform: scale(0.95); }

/* Scrollbar */
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }
::-webkit-scrollbar-thumb:hover { background: #bbb; }

.empty-state { text-align: center; color: #999; margin: auto; }

/* Mobile optimization */
@media (max-width: 768px) {
    .chat-layout { height: calc(100vh - 70px); border: none; border-radius: 0; }
    .chat-sidebar { width: 100%; border-right: none; }
    .chat-main { width: 100%; }
    
    /* Toggle visibility based on state */
    .chat-layout.chat-active .chat-sidebar { display: none; }
    .chat-layout:not(.chat-active) .chat-main { display: none; }
    
    .sidebar-header h3 { font-size: 16px; }
    .chat-header { padding: 10px 15px; }
    .back-btn { display: inline-flex !important; margin-right: 10px; }
}

.back-btn { display: none; background: none; border: none; cursor: pointer; color: #333; padding: 5px; align-items: center; justify-content: center; }
</style>

<div class="chat-layout <?php echo $currentSession ? 'chat-active' : ''; ?>">
    <!-- Sidebar -->
    <div class="chat-sidebar">
        <div class="sidebar-header">
            <h3>Active Chats</h3>
        </div>
        <div class="session-list" id="sessionList">
            <!-- Populated by JS -->
            <div style="padding:20px; text-align:center; color:#999;">Loading...</div>
        </div>
    </div>

    <!-- Main Chat Area -->
    <div class="chat-main">
        <?php if ($currentSession): ?>
            <div class="chat-header">
                <div style="display: flex; align-items: center;">
                    <a href="support.php" class="back-btn">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                    </a>
                    <div class="user-meta">
                        <h4><?php echo htmlspecialchars($currentSession['name']); ?></h4>
                        <span>
                            <span class="status-dot"></span> 
                            <?php echo htmlspecialchars($currentSession['email']); ?> 
                            <?php if($currentSession['phone']) echo ' • '.htmlspecialchars($currentSession['phone']); ?>
                        </span>
                    </div>
                </div>
                <a href="users.php?search=<?php echo urlencode($currentSession['email']); ?>" class="btn-secondary" style="font-size: 12px; padding: 6px 14px; border-radius: 20px;">View User</a>
            </div>

            <div class="chat-messages" id="chatMessages">
                <!-- Messages -->
            </div>

            <div class="chat-input-area">
                <textarea id="messageInput" rows="1" placeholder="Type your message..."></textarea>
                <button onclick="sendMessage()" class="send-btn">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                </button>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#e0e0e0" stroke-width="1.5">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
                <h3>Select a conversation</h3>
                <p>Choose an active chat from the sidebar to start messaging.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
const currentSessionId = <?php echo $currentSessionId; ?>;
const sessionListDiv = document.getElementById('sessionList');

// --- Sidebar Polling ---
function fetchSessions() {
    fetch('<?php echo BASE_URL; ?>/api/support/sessions.php')
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            renderSessions(data.sessions);
        }
    });
}

function renderSessions(sessions) {
    if (sessions.length === 0) {
        sessionListDiv.innerHTML = '<div style="padding:20px; text-align:center; color:#999;">No chats yet</div>';
        return;
    }
    
    sessionListDiv.innerHTML = sessions.map(session => {
        const isActive = session.id == currentSessionId ? 'active' : '';
        const unreadDate = new Date(session.updated_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        const badge = session.unread_count > 0 ? `<div class="session-badge">${session.unread_count}</div>` : '';
        
        return `
        <div onclick="window.location.href='?session=${session.id}'" class="session-item ${isActive}">
            <div class="session-top">
                <span class="session-name">${escapeHtml(session.name)}</span>
                <span class="session-time">${unreadDate}</span>
            </div>
            <div class="session-preview">${escapeHtml(session.last_message || 'Start chatting...')}</div>
            ${badge}
        </div>
        `;
    }).join('');
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Initial session load
fetchSessions();
setInterval(fetchSessions, 5000); // Poll list every 5s

// --- Main Chat Logic (if session active) ---
<?php if ($currentSession): ?>
let lastMessageId = 0;
const messagesDiv = document.getElementById('chatMessages');
const messageInput = document.getElementById('messageInput');

function fetchMessages() {
    fetch(`<?php echo BASE_URL; ?>/api/support/sync.php?session_id=${currentSessionId}&last_id=${lastMessageId}&context=admin`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.messages.length > 0) {
                data.messages.forEach(appendMessage);
            }
        });
}

function appendMessage(msg) {
    // Check if exists
    if (document.getElementById('msg-'+msg.id)) return;

    const div = document.createElement('div');
    div.id = 'msg-'+msg.id;
    div.className = `message-wrapper ${msg.sender_type}`;
    const time = new Date(msg.created_at).toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
    
    div.innerHTML = `
        <div class="message-bubble">${msg.message.replace(/\n/g, '<br>')}</div>
        <span class="message-time">${time}</span>
    `;
    messagesDiv.appendChild(div);
    scrollToBottom();
    lastMessageId = Math.max(lastMessageId, msg.id);
}

function scrollToBottom() {
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

function sendMessage() {
    const text = messageInput.value.trim();
    if (!text) return;
    
    messageInput.value = '';
    
    const formData = new FormData();
    formData.append('session_id', currentSessionId);
    formData.append('message', text);
    formData.append('sender', 'admin');

    fetch(`<?php echo BASE_URL; ?>/api/support/send.php`, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            fetchMessages(); // Sync
        }
    });
}

// Typing Logic
let typingTimer;
messageInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
    
    if (typingTimer) clearTimeout(typingTimer);
    
    // Throttled update
    if (!window.lastTypingTime || Date.now() - window.lastTypingTime > 2000) {
        window.lastTypingTime = Date.now();
        fetch('<?php echo BASE_URL; ?>/api/support/typing.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `session_id=${currentSessionId}&role=admin`
        });
    }
    
    typingTimer = setTimeout(() => {
        fetch('<?php echo BASE_URL; ?>/api/support/typing.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `session_id=${currentSessionId}&role=admin&status=stopped`
        });
        window.lastTypingTime = 0;
    }, 1000);
});

messageInput.addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

function fetchMessages() {
    fetch(`<?php echo BASE_URL; ?>/api/support/sync.php?session_id=${currentSessionId}&last_id=${lastMessageId}&context=admin`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (data.messages.length > 0) {
                    data.messages.forEach(appendMessage);
                    updateTypingIndicator(false); // Hide if msg received
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
            indicator.className = 'message-wrapper user';
            indicator.innerHTML = `
                <div class="message-bubble" style="background: #f1f0f0; border: none;">
                    <span class="typing-dots">
                        <span class="dot"></span><span class="dot"></span><span class="dot"></span>
                    </span>
                </div>`;
            messagesDiv.appendChild(indicator);
            scrollToBottom();
            
            // Add Styles if needed
            if (!document.getElementById('typing-style-admin')) {
                const style = document.createElement('style');
                style.id = 'typing-style-admin';
                style.textContent = `
                    .typing-dots { display: inline-flex; gap: 4px; padding: 5px 0; }
                    .typing-dots .dot { width: 6px; height: 6px; background: #999; border-radius: 50%; animation: typing 1.4s infinite ease-in-out both; }
                    .typing-dots .dot:nth-child(1) { animation-delay: -0.32s; }
                    .typing-dots .dot:nth-child(2) { animation-delay: -0.16s; }
                    @keyframes typing { 
                        0%, 80%, 100% { transform: scale(0); } 
                        40% { transform: scale(1); } 
                    }
                `;
                document.head.appendChild(style);
            }
        }
    } else if (indicator) {
        indicator.remove();
    }
}

// Initial Load
fetchMessages();
setInterval(fetchMessages, 3000); // Poll messages every 3s
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>
