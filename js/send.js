 
 
 

let recipientUsername = null;

 
async function initSendPage() {
     
    const urlParams = new URLSearchParams(window.location.search);
    recipientUsername = urlParams.get('u');

    if (!recipientUsername) {
        showError('Aucun destinataire spécifié');
        return;
    }

     
    updateRecipientInfo();

     
    setupEventListeners();
}

 
function updateRecipientInfo() {
    const avatarEl = document.getElementById('recipientAvatar');
    const nameEl = document.getElementById('recipientName');

    if (avatarEl) {
        avatarEl.textContent = recipientUsername.charAt(0).toUpperCase();
    }

    if (nameEl) {
        nameEl.textContent = `@${recipientUsername}`;
    }
}

 
function setupEventListeners() {
    const form = document.getElementById('sendForm');
    const messageInput = document.getElementById('messageInput');
    const charCountEl = document.getElementById('charCount');

    if (form) {
        form.addEventListener('submit', handleSendMessage);
    }

    if (messageInput && charCountEl) {
        messageInput.addEventListener('input', () => {
            const count = messageInput.value.length;
            charCountEl.textContent = `${count}/5000`;

            if (count > 5000) {
                charCountEl.style.color = '#ef4444';
            } else {
                charCountEl.style.color = '';
            }
        });
    }
}

 
async function handleSendMessage(e) {
    e.preventDefault();

    const messageInput = document.getElementById('messageInput');
    const submitBtn = document.getElementById('submitBtn');
    const message = messageInput.value.trim();

     
    if (!message) {
        showAlert('error', 'Veuillez écrire un message');
        return;
    }

    if (message.length > 5000) {
        showAlert('error', 'Le message est trop long (maximum 5000 caractères)');
        return;
    }

     
    setButtonLoading(submitBtn, true);

    try {
        const response = await fetch('send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                username: recipientUsername,
                message: message
            })
        });

        const data = await response.json();

        if (data.success) {
            showSuccess();
        } else {
            showAlert('error', data.message);
        }
    } catch (error) {
        console.error('Send failed:', error);
        showAlert('error', 'Erreur de connexion au serveur');
    } finally {
        setButtonLoading(submitBtn, false);
    }
}

 
function showSuccess() {
    const formContainer = document.getElementById('formContainer');
    const successContainer = document.getElementById('successContainer');

    if (formContainer) {
        formContainer.classList.add('hidden');
    }

    if (successContainer) {
        successContainer.classList.remove('hidden');
    }
}

 
function showError(message) {
    const formContainer = document.getElementById('formContainer');

    if (formContainer) {
        formContainer.innerHTML = `
            <div class="alert alert-error">
                <span>✕</span>
                <span>${message}</span>
            </div>
            <div class="text-center mt-3">
                <a href="index.html" class="btn btn-primary">Retour à l'accueil</a>
            </div>
        `;
    }
}

 
function showAlert(type, message) {
    const container = document.getElementById('alertContainer');
    if (!container) return;

    container.innerHTML = `
        <div class="alert alert-${type}">
            <span>${type === 'success' ? '✓' : type === 'error' ? '✕' : 'ℹ'}</span>
            <span>${message}</span>
        </div>
    `;

    setTimeout(() => {
        container.innerHTML = '';
    }, 5000);
}

 
function setButtonLoading(button, loading) {
    if (!button) return;

    if (loading) {
        button.disabled = true;
        button.dataset.originalText = button.innerHTML;
        button.innerHTML = '<span class="spinner"></span> Envoi en cours...';
    } else {
        button.disabled = false;
        button.innerHTML = button.dataset.originalText || button.innerHTML;
    }
}

 
document.addEventListener('DOMContentLoaded', initSendPage);
