 
 
 

const API_BASE = '/ananymous';

 
 
 

const CACHE_KEYS = {
    USER: 'sayit_user',
    MESSAGES: 'sayit_messages',
    STATS: 'sayit_stats',
    LAST_FETCH: 'sayit_last_fetch'
};

 
const CACHE_DURATION = 5 * 60 * 1000;

 
function saveToCache(key, data) {
    try {
        localStorage.setItem(key, JSON.stringify({
            data: data,
            timestamp: Date.now()
        }));
    } catch (e) {
        console.warn('localStorage not available:', e);
    }
}

 
function getFromCache(key, maxAge = CACHE_DURATION) {
    try {
        const cached = localStorage.getItem(key);
        if (!cached) return null;

        const { data, timestamp } = JSON.parse(cached);

         
        if (Date.now() - timestamp > maxAge) {
            localStorage.removeItem(key);
            return null;
        }

        return data;
    } catch (e) {
        return null;
    }
}

 
function clearCache() {
    Object.values(CACHE_KEYS).forEach(key => {
        try {
            localStorage.removeItem(key);
        } catch (e) { }
    });
}

 
 
 

 
async function register(username, email, password, confirmPassword) {
    try {
        const response = await fetch(`${API_BASE}/register.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, email, password, confirmPassword })
        });

        const data = await response.json();

        if (data.success && data.user) {
            saveToCache(CACHE_KEYS.USER, data.user);
        }

        return data;
    } catch (error) {
        return { success: false, message: 'Erreur de connexion au serveur' };
    }
}

 
async function login(username, password) {
    try {
        const response = await fetch(`${API_BASE}/login.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ username, password })
        });

        const data = await response.json();

        if (data.success && data.user) {
            saveToCache(CACHE_KEYS.USER, data.user);
        }

        return data;
    } catch (error) {
        return { success: false, message: 'Erreur de connexion au serveur' };
    }
}

 
async function logout() {
    try {
        const response = await fetch(`${API_BASE}/logout.php`);
        const data = await response.json();
        clearCache();
        return data;
    } catch (error) {
        clearCache();
        return { success: false, message: 'Erreur lors de la déconnexion' };
    }
}

 
async function checkAuth() {
     
    const cachedUser = getFromCache(CACHE_KEYS.USER, 60000);  
    if (cachedUser) {
        return { success: true, user: cachedUser };
    }

    try {
        const response = await fetch(`${API_BASE}/check_session.php`);
        const data = await response.json();

        if (data.success && data.user) {
            saveToCache(CACHE_KEYS.USER, data.user);
        }

        return data;
    } catch (error) {
        return { success: false, message: 'Erreur de vérification' };
    }
}

 
async function redirectIfLoggedIn() {
    const auth = await checkAuth();
    if (auth.success) {
        window.location.replace('dashboard.html');
    }
}

 
async function redirectIfNotLoggedIn() {
    const auth = await checkAuth();
    if (!auth.success) {
        window.location.replace('login.html');
        return null;
    }
    return auth;
}

 
 
 

function validateUsername(username) {
    if (!username || username.length < 3) {
        return { valid: false, message: 'Le nom d\'utilisateur doit contenir au moins 3 caractères' };
    }
    if (username.length > 50) {
        return { valid: false, message: 'Le nom d\'utilisateur ne peut pas dépasser 50 caractères' };
    }
    if (!/^[a-zA-Z0-9_]+$/.test(username)) {
        return { valid: false, message: 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres et underscores' };
    }
    return { valid: true };
}

function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email || !emailRegex.test(email)) {
        return { valid: false, message: 'Veuillez entrer une adresse email valide' };
    }
    return { valid: true };
}

function validatePassword(password) {
    if (!password || password.length < 10) {
        return { valid: false, message: 'Le mot de passe doit contenir au moins 10 caractères' };
    }
    return { valid: true };
}

 
 
 

function showAlert(container, type, message) {
    const iconSVG = type === 'success'
        ? '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
        : '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>';

    container.innerHTML = `
        <div class="alert alert-${type}">
            ${iconSVG}
            <span>${message}</span>
        </div>
    `;
}

function setButtonLoading(button, loading) {
    if (loading) {
        button.disabled = true;
        button.dataset.originalHtml = button.innerHTML;
        button.innerHTML = '<div class="spinner"></div> Chargement...';
    } else {
        button.disabled = false;
        button.innerHTML = button.dataset.originalHtml || button.innerHTML;
    }
}
