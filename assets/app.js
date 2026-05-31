// 🔒 Escapar HTML para seguridad
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

//  Feed (index.php)
const feedContainer = document.getElementById('feed');
if (feedContainer) {
    async function loadPosts() {
        const res = await fetch('api/fetch_posts.php');
        const posts = await res.json();
        
        if (posts.length === 0) {
            feedContainer.innerHTML = '<div style="text-align:center; padding:30px; color:#888;">No hay posts aún. ¡Sé el primero!</div>';
            return;
        }

        feedContainer.innerHTML = posts.map(p => `
            <div class="post">
                <strong>@${escapeHtml(p.username)}</strong>
                <p>${escapeHtml(p.content)}</p>
                <small>${new Date(p.created_at).toLocaleString()}</small>
            </div>
        `).join('');
    }

    // Crear post
    const createForm = document.getElementById('createForm');
    if (createForm) {
        createForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const content = document.getElementById('content').value;
            const res = await fetch('api/create_post.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ content })
            });
            const data = await res.json();
            if (data.success) {
                document.getElementById('content').value = '';
                loadPosts();
            } else {
                alert('Error: ' + data.error);
            }
        });
    }

    loadPosts();
    setInterval(loadPosts, 3000); // Auto-refresh feed
}

//  Chat (chat.php)
// Recibimos 'myUserId' como parámetro extra
function initChat(roomId, myUserId) {
    const msgContainer = document.getElementById('messages');
    const chatForm = document.getElementById('chatForm');
    const msgInput = document.getElementById('msgInput');

    async function loadMessages() {
        const res = await fetch(`api/fetch_messages.php?room_id=${roomId}`);
        const messages = await res.json();
        
        // Detectar si estamos cerca del final para hacer scroll automático
        const shouldScroll = msgContainer.scrollTop + msgContainer.clientHeight >= msgContainer.scrollHeight - 50;
        
        msgContainer.innerHTML = messages.map(m => `
            <!-- Usamos la variable 'myUserId' de JS en lugar de PHP -->
            <div class="msg ${m.user_id == myUserId ? 'mine' : ''}">
                <div class="user">@${escapeHtml(m.username)}</div>
                <div class="text">${escapeHtml(m.content)}</div>
                <div class="time">${new Date(m.created_at).toLocaleTimeString()}</div>
            </div>
        `).join('');

        if (shouldScroll) msgContainer.scrollTop = msgContainer.scrollHeight;
    }

    if (chatForm) {
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const content = msgInput.value;
            if (!content.trim()) return;

            const res = await fetch('api/send_message.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ room_id: roomId, content })
            });

            const data = await res.json();
            if (data.success) {
                msgInput.value = '';
                loadMessages();
            }
        });
    }

    loadMessages();
    setInterval(loadMessages, 2000); // Actualizar chat cada 2s
}
// 💬 Chat Privado
function initPrivateChat(receiverId, myUserId) {
    const msgContainer = document.getElementById('messages');
    const chatForm = document.getElementById('privateChatForm');
    const msgInput = document.getElementById('privateMsgInput');

    async function loadMessages() {
        const res = await fetch(`api/fetch_private_msgs.php?user_id=${receiverId}`);
        const messages = await res.json();
        
        const shouldScroll = msgContainer.scrollTop + msgContainer.clientHeight >= msgContainer.scrollHeight - 50;
        
        msgContainer.innerHTML = messages.map(m => `
            <div class="msg ${m.sender_id == myUserId ? 'mine' : ''}">
                <div class="user">@${escapeHtml(m.username)}</div>
                <div class="text">${escapeHtml(m.content)}</div>
                <div class="time">${new Date(m.created_at).toLocaleTimeString()}</div>
            </div>
        `).join('');

        if (shouldScroll) msgContainer.scrollTop = msgContainer.scrollHeight;
    }

    if (chatForm) {
        chatForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const content = msgInput.value;
            if (!content.trim()) return;

            const res = await fetch('api/send_private_msg.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ receiver_id: receiverId, content })
            });

            const data = await res.json();
            if (data.success) {
                msgInput.value = '';
                loadMessages();
            }
        });
    }

    loadMessages();
    setInterval(loadMessages, 2000);
}