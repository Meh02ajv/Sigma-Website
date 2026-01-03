// Messagerie avec AJAX Polling - Sans WebSocket
const currentUserId = parseInt(document.body.dataset.userId);
let selectedUserId = null;
let isSending = false;
const displayedMessages = new Set();
let isMobile = window.innerWidth <= 768;
let pollingInterval = null;
let unreadPollingInterval = null;
let lastMessageId = 0;
const POLLING_INTERVAL = 2000; // 2 secondes
const UNREAD_POLLING_INTERVAL = 5000; // 5 secondes

// Initialiser la messagerie avec AJAX polling
function initializeMessaging() {
    const statusElement = document.getElementById('connection-status');
    statusElement.style.display = 'block';
    statusElement.innerHTML = '<i class="fas fa-check-circle"></i> Prêt';
    statusElement.className = 'connection-status connected';
    
    setTimeout(() => {
        statusElement.style.display = 'none';
    }, 2000);
    
    // Charger les indicateurs non lus
    loadUnreadIndicators();
    
    // Démarrer le polling pour les indicateurs non lus
    startUnreadPolling();
}

// Démarrer le polling pour les nouveaux messages
function startMessagePolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }
    
    pollingInterval = setInterval(() => {
        if (selectedUserId) {
            pollNewMessages();
        }
    }, POLLING_INTERVAL);
}

// Arrêter le polling des messages
function stopMessagePolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
}

// Polling pour les nouveaux messages d'une conversation
async function pollNewMessages() {
    if (!selectedUserId) return;
    
    try {
        const response = await fetch(`get_new_messages.php?recipient_id=${selectedUserId}&last_message_id=${lastMessageId}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        if (data.success && data.messages && data.messages.length > 0) {
            data.messages.forEach(message => {
                const isCurrentConversation = 
                    (message.sender_id === currentUserId && message.recipient_id === parseInt(selectedUserId)) ||
                    (message.sender_id === parseInt(selectedUserId) && message.recipient_id === currentUserId);
                
                if (isCurrentConversation && !displayedMessages.has(message.message_id)) {
                    displayMessage(message);
                    displayedMessages.add(message.message_id);
                    lastMessageId = Math.max(lastMessageId, message.message_id);
                    
                    // Marquer comme lu si c'est un message reçu
                    if (message.sender_id !== currentUserId) {
                        markAsRead(selectedUserId);
                        // Déplacer le contact en haut de la liste
                        moveContactToTop(selectedUserId);
                    }
                }
            });
        }
    } catch (error) {
        console.error('Error polling messages:', error);
    }
}

// Démarrer le polling pour les indicateurs non lus
function startUnreadPolling() {
    if (unreadPollingInterval) {
        clearInterval(unreadPollingInterval);
    }
    
    unreadPollingInterval = setInterval(loadUnreadIndicators, UNREAD_POLLING_INTERVAL);
}

// Afficher un message dans le chat
function displayMessage(message) {
    const messagesDiv = document.getElementById('chat-messages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${message.sender_id === currentUserId ? 'sent' : 'received'}`;
    messageDiv.dataset.messageId = message.message_id;
    
    // Contenu du message (échappé automatiquement avec textContent)
    const paragraph = document.createElement('p');
    paragraph.textContent = message.content;
    messageDiv.appendChild(paragraph);
    
    // Timestamp
    const timestamp = document.createElement('span');
    timestamp.className = 'timestamp';
    timestamp.textContent = formatTime(message.sent_at);
    messageDiv.appendChild(timestamp);
    
    messagesDiv.appendChild(messageDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
    hidePlaceholderMessages();
}

// Échapper le HTML pour éviter les XSS
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Formater l'heure
function formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'});
}

// Afficher une erreur
function displayError(errorMsg) {
    const messagesDiv = document.getElementById('chat-messages');
    messagesDiv.innerHTML = '';
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle"></i>
        <p>${errorMsg}</p>
    `;
    messagesDiv.appendChild(errorDiv);
    hidePlaceholderMessages();
}

// Afficher un message d'information
function displayInfo(infoMsg) {
    const messagesDiv = document.getElementById('chat-messages');
    messagesDiv.innerHTML = '';
    const infoDiv = document.createElement('div');
    infoDiv.className = 'info-message';
    infoDiv.innerHTML = `
        <i class="fas fa-info-circle"></i>
        <p>${infoMsg}</p>
    `;
    messagesDiv.appendChild(infoDiv);
    hidePlaceholderMessages();
}

// Cacher les messages de placeholder
function hidePlaceholderMessages() {
    const noSelection = document.querySelector('.no-selection');
    if (noSelection) noSelection.classList.add('hidden');
    const errorMessage = document.querySelector('.error-message');
    if (errorMessage) errorMessage.classList.add('hidden');
    const infoMessage = document.querySelector('.info-message');
    if (infoMessage) infoMessage.classList.add('hidden');
}

// Réinitialiser le chat
function resetChat() {
    selectedUserId = null;
    stopMessagePolling();
    lastMessageId = 0;
    
    document.getElementById('chat-title').textContent = 'Sélectionnez un utilisateur pour commencer à discuter';
    document.getElementById('chat-input').classList.add('hidden');
    const messagesDiv = document.getElementById('chat-messages');
    messagesDiv.innerHTML = '';
    const noSelection = document.createElement('div');
    noSelection.className = 'no-selection';
    noSelection.innerHTML = `
        <i class="fas fa-comments"></i>
        <p>Sélectionnez un contact pour commencer la conversation</p>
    `;
    messagesDiv.appendChild(noSelection);
    document.querySelectorAll('.user-card').forEach(c => c.classList.remove('active'));
    displayedMessages.clear();
    
    // Réinitialiser le textarea
    const messageInput = document.getElementById('message-input');
    messageInput.value = '';
    messageInput.style.height = '';
    
    // Sur mobile, afficher à nouveau la liste des utilisateurs
    if (isMobile) {
        document.getElementById('user-list').classList.remove('hidden-mobile');
    }
}

// Afficher le chat
function showChat() {
    if (isMobile) {
        document.getElementById('user-list').classList.add('hidden-mobile');
    }
    document.getElementById('chat-input').classList.remove('hidden');
    
    // Démarrer le polling des nouveaux messages
    startMessagePolling();
    
    // Focus sur le champ de saisie
    setTimeout(() => {
        if (window.innerWidth > 768) {
            document.getElementById('message-input').focus();
        }
    }, 100);
}

// Charger les messages d'une conversation
async function loadMessages(recipientId) {
    try {
        const response = await fetch(`get_messages.php?recipient_id=${recipientId}`);
        if (!response.ok) {
            const text = await response.text();
            throw new Error(`HTTP error! status: ${response.status}, response: ${text}`);
        }
        const messages = await response.json();
        if (messages.error) {
            throw new Error(messages.error);
        }
        const messagesDiv = document.getElementById('chat-messages');
        messagesDiv.innerHTML = '';
        displayedMessages.clear();
        lastMessageId = 0;
        
        if (messages.length === 0) {
            displayInfo('Aucun message. Commencez la conversation !');
        } else {
            // Afficher les messages dans l'ordre chronologique
            messages.reverse().forEach(message => {
                const isValidMessage = 
                    (message.sender_id === currentUserId && message.recipient_id === parseInt(recipientId)) ||
                    (message.sender_id === parseInt(recipientId) && message.recipient_id === currentUserId);
                if (isValidMessage && !displayedMessages.has(message.message_id)) {
                    displayMessage(message);
                    displayedMessages.add(message.message_id);
                    lastMessageId = Math.max(lastMessageId, message.message_id);
                }
            });
            markAsRead(recipientId);
        }
        loadUnreadIndicators();
    } catch (error) {
        console.error('Error loading messages:', error);
        displayError('Erreur lors du chargement des messages: ' + error.message);
    }
}

// Marquer les messages comme lus
async function markAsRead(recipientId) {
    try {
        const response = await fetch('mark_messages_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `recipient_id=${recipientId}&csrf_token=${document.body.dataset.csrfToken}`
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const result = await response.json();
        if (result.success) {
            console.log(`Messages marked as read for recipient ${recipientId}`);
            loadUnreadIndicators();
        }
    } catch (error) {
        console.error('Error marking messages as read:', error);
    }
}

// Charger les indicateurs de messages non lus
async function loadUnreadIndicators() {
    try {
        const response = await fetch('get_unread_counts.php');
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const unreadCounts = await response.json();
        if (unreadCounts.error) {
            throw new Error(unreadCounts.error);
        }
        document.querySelectorAll('.user-card').forEach(card => {
            const userId = parseInt(card.dataset.id);
            card.classList.remove('unread');
            if (unreadCounts[userId] && unreadCounts[userId] > 0) {
                card.classList.add('unread');
            }
        });
    } catch (error) {
        console.error('Error loading unread indicators:', error);
    }
}

// Déplacer un contact en haut de la liste
function moveContactToTop(userId) {
    const userListHeader = document.querySelector('.user-list-header');
    const card = document.querySelector(`.user-card[data-id="${userId}"]`);
    
    if (!card || !userListHeader) return;
    
    // Vérifier si la carte n'est pas déjà en première position
    const firstCard = userListHeader.nextElementSibling;
    if (firstCard === card) return;
    
    // Ajouter classe d'animation
    card.classList.add('moving-to-top');
    
    // Retirer et réinsérer en haut
    card.remove();
    userListHeader.insertAdjacentElement('afterend', card);
    
    // Retirer la classe d'animation après l'animation
    setTimeout(() => {
        card.classList.remove('moving-to-top');
    }, 500);
}

// Définir l'utilisateur actif
function setActiveUser(card) {
    document.querySelectorAll('.user-card').forEach(c => c.classList.remove('active'));
    card.classList.add('active');
}

// Envoyer un message
async function sendMessage() {
    if (isSending) return;
    isSending = true;
    
    const input = document.getElementById('message-input');
    const content = input.value.trim();
    
    if (!content) {
        isSending = false;
        return;
    }
    
    if (!selectedUserId) {
        displayError('Veuillez sélectionner un utilisateur.');
        isSending = false;
        return;
    }
    
    const sendButton = document.getElementById('send-message');
    const originalHTML = sendButton.innerHTML;
    sendButton.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
    sendButton.disabled = true;
    
    try {
        const response = await fetch('send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                recipient_id: parseInt(selectedUserId),
                content: content
            })
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.success && data.message) {
            input.value = '';
            input.style.height = '';
            
            if (!displayedMessages.has(data.message.message_id)) {
                displayMessage(data.message);
                displayedMessages.add(data.message.message_id);
                lastMessageId = Math.max(lastMessageId, data.message.message_id);
            }
            
            // Déplacer le contact en haut de la liste
            moveContactToTop(selectedUserId);
        } else {
            throw new Error(data.error || 'Erreur lors de l\'envoi du message');
        }
    } catch (error) {
        console.error('Error sending message:', error);
        displayError('Erreur lors de l\'envoi du message: ' + error.message);
    } finally {
        sendButton.innerHTML = originalHTML;
        sendButton.disabled = false;
        isSending = false;
    }
}

// Gérer la sélection d'un utilisateur
function setupUserCardListeners() {
    document.querySelectorAll('.user-card').forEach(card => {
        // Click event
        card.addEventListener('click', () => {
            handleUserSelection(card);
        });
        
        // Keyboard navigation
        card.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                handleUserSelection(card);
            }
        });
    });
}

function handleUserSelection(card) {
    const userId = parseInt(card.dataset.id);
    if (selectedUserId === userId) {
        // Fermer la conversation si on clique sur le même utilisateur (sauf sur mobile)
        if (!isMobile) {
            resetChat();
        }
    } else {
        // Ouvrir une nouvelle conversation
        selectedUserId = userId;
        document.getElementById('chat-title').innerHTML = `<i class="fas fa-user"></i> ${card.dataset.name}`;
        setActiveUser(card);
        showChat();
        loadMessages(selectedUserId);
    }
}

// Bouton de retour sur mobile
// Les event listeners sont maintenant dans DOMContentLoaded

// Empêcher le zoom sur double tap (iOS)
let resizeTimeout;
window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        const wasMobile = isMobile;
        isMobile = window.innerWidth <= 768;
        
        if (wasMobile !== isMobile) {
            if (!isMobile && selectedUserId) {
                // Afficher à nouveau la liste sur desktop
                document.getElementById('user-list').classList.remove('hidden-mobile');
            }
        }
    }, 250);
});

// Empêcher le zoom sur double tap (iOS)
let lastTouchEnd = 0;
document.addEventListener('touchend', function(event) {
    const now = Date.now();
    if (now - lastTouchEnd <= 300) {
        event.preventDefault();
    }
    lastTouchEnd = now;
}, false);

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    initializeMessaging();
    setupUserCardListeners();
    
    // Bouton de retour sur mobile
    const backButton = document.getElementById('back-button');
    if (backButton) {
        backButton.addEventListener('click', resetChat);
    }
    
    // Gestion de l'envoi de message
    const sendButton = document.getElementById('send-message');
    if (sendButton) {
        sendButton.addEventListener('click', sendMessage);
    }
    
    // Gestion de la textarea
    const messageInput = document.getElementById('message-input');
    
    if (messageInput) {
        messageInput.addEventListener('input', function() {
            // Ajustement automatique de la hauteur
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            if (this.scrollHeight > 120) {
                this.style.overflowY = 'auto';
            } else {
                this.style.overflowY = 'hidden';
            }
        });
        
        messageInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }
    
    // Cacher la liste des utilisateurs sur mobile si une conversation est ouverte
    if (isMobile && selectedUserId) {
        document.getElementById('user-list').classList.add('hidden-mobile');
    }
});

// Nettoyer avant de quitter la page
window.addEventListener('beforeunload', () => {
    stopMessagePolling();
    if (unreadPollingInterval) {
        clearInterval(unreadPollingInterval);
    }
});
