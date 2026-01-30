 
 
 

let currentUser = null;
let allMessages = [];
let currentFilter = 'all';

 
 
 

async function initDashboard() {
    try {
         
        const cachedUser = getFromCache(CACHE_KEYS.USER);
        if (cachedUser) {
            currentUser = cachedUser;
            updateUserInfo();
        }

         
        const response = await fetch('check_session.php');
        const auth = await response.json();

        if (!auth.success || !auth.user || !auth.user.username) {
            clearCache();
            if (!window.location.pathname.includes('login.html')) {
                window.location.replace('login.html');
            }
            return;
        }

        currentUser = auth.user;
        saveToCache(CACHE_KEYS.USER, currentUser);
        updateUserInfo();

         
        await loadMessages();
        await loadStats();

        setupEventListeners();
    } catch (error) {
        console.error('Dashboard init failed:', error);
        if (!window.location.pathname.includes('login.html')) {
            window.location.replace('login.html');
        }
    }
}

 
 
 

function updateUserInfo() {
    if (!currentUser || !currentUser.username) {
        return;
    }

    const userNameEl = document.getElementById('userName');
    const userAvatarEl = document.getElementById('userAvatar');
    const shareLinkEl = document.getElementById('shareLink');

    if (userNameEl) {
        userNameEl.textContent = currentUser.username;
    }

    if (userAvatarEl && currentUser.username) {
        userAvatarEl.textContent = currentUser.username.charAt(0).toUpperCase();
    }

    if (shareLinkEl && currentUser.shareLink) {
        shareLinkEl.value = currentUser.shareLink;
    }
}

 
 
 

async function loadMessages(forceRefresh = false) {
    const container = document.getElementById('messagesList');

     
    if (!forceRefresh) {
        const cachedMessages = getFromCache(CACHE_KEYS.MESSAGES);
        if (cachedMessages) {
            allMessages = cachedMessages;
            renderMessages(allMessages);
            updateMessageCount(allMessages.length);
             
            fetchMessagesInBackground();
            return;
        }
    }

    try {
        const response = await fetch('get_messages.php');
        const data = await response.json();

        if (data.success) {
            allMessages = data.messages;
            saveToCache(CACHE_KEYS.MESSAGES, allMessages);
            renderMessages(allMessages);
            updateMessageCount(allMessages.length);
        }
    } catch (error) {
        console.error('Failed to load messages:', error);
    }
}

async function fetchMessagesInBackground() {
    try {
        const response = await fetch('get_messages.php');
        const data = await response.json();

        if (data.success) {
            allMessages = data.messages;
            saveToCache(CACHE_KEYS.MESSAGES, allMessages);
             
            const currentCount = document.getElementById('messageCount')?.textContent;
            if (parseInt(currentCount) !== allMessages.length) {
                renderMessages(allMessages);
                updateMessageCount(allMessages.length);
            }
        }
    } catch (error) { }
}

function renderMessages(messages) {
    const container = document.getElementById('messagesList');
    if (!container) return;

    if (messages.length === 0) {
        container.innerHTML = `
            <div class="no-messages">
                <div class="no-messages-icon">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="opacity: 0.3;">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                </div>
                <h3>Aucun message</h3>
                <p>Partagez votre lien pour recevoir des messages anonymes!</p>
            </div>
        `;
        return;
    }

    container.innerHTML = messages.map(msg => `
        <div class="message-card" data-id="${msg.id}">
            <div class="message-content">${escapeHtml(msg.content)}</div>
            <div class="message-time">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
                ${msg.formattedDate} (${msg.relativeTime})
            </div>
        </div>
    `).join('');
}

function updateMessageCount(count) {
    const countEl = document.getElementById('messageCount');
    if (countEl) {
        countEl.textContent = count;
    }
}

 
 
 

async function loadStats(forceRefresh = false) {
     
    if (!forceRefresh) {
        const cachedStats = getFromCache(CACHE_KEYS.STATS);
        if (cachedStats) {
            updateStatsCards(cachedStats);
            updateFilterCounts(cachedStats);
            return;
        }
    }

    try {
        const response = await fetch('get_stats.php');
        const data = await response.json();

        if (data.success) {
            saveToCache(CACHE_KEYS.STATS, data.stats);
            updateStatsCards(data.stats);
            updateFilterCounts(data.stats);
        }
    } catch (error) {
        console.error('Failed to load stats:', error);
    }
}

function updateStatsCards(stats) {
    const mapping = {
        'statTotal': 'total',
        'statToday': 'today',
        'statWeek': 'this_week',
        'statMonth': 'this_month'
    };

    for (const [elementId, statKey] of Object.entries(mapping)) {
        const el = document.getElementById(elementId);
        if (el) {
            el.textContent = stats[statKey] || 0;
        }
    }
}

function updateFilterCounts(stats) {
    const filters = document.querySelectorAll('.filter-item');
    filters.forEach(filter => {
        const filterType = filter.dataset.filter;
        const countEl = filter.querySelector('.count');
        if (countEl && stats[filterType] !== undefined) {
            countEl.textContent = stats[filterType];
        }
    });
}

 
 
 

async function filterMessages(filter) {
    currentFilter = filter;

     
    document.querySelectorAll('.filter-item').forEach(item => {
        item.classList.toggle('active', item.dataset.filter === filter);
    });

    document.querySelectorAll('.stat-card').forEach(card => {
        card.classList.toggle('active', card.dataset.filter === filter);
    });

     
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    const thisWeekStart = new Date(today);
    thisWeekStart.setDate(today.getDate() - today.getDay() + 1);

    const lastWeekStart = new Date(thisWeekStart);
    lastWeekStart.setDate(lastWeekStart.getDate() - 7);
    const lastWeekEnd = new Date(thisWeekStart);
    lastWeekEnd.setSeconds(lastWeekEnd.getSeconds() - 1);

    const thisMonthStart = new Date(now.getFullYear(), now.getMonth(), 1);
    const lastMonthStart = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    const lastMonthEnd = new Date(now.getFullYear(), now.getMonth(), 0);

    const twoMonthsAgoStart = new Date(now.getFullYear(), now.getMonth() - 2, 1);
    const twoMonthsAgoEnd = new Date(now.getFullYear(), now.getMonth() - 1, 0);

    let filtered = allMessages;

    switch (filter) {
        case 'today':
            filtered = allMessages.filter(m => new Date(m.receivedAt) >= today);
            break;
        case 'yesterday':
            filtered = allMessages.filter(m => {
                const d = new Date(m.receivedAt);
                return d >= yesterday && d < today;
            });
            break;
        case 'this_week':
            filtered = allMessages.filter(m => new Date(m.receivedAt) >= thisWeekStart);
            break;
        case 'last_week':
            filtered = allMessages.filter(m => {
                const d = new Date(m.receivedAt);
                return d >= lastWeekStart && d <= lastWeekEnd;
            });
            break;
        case 'this_month':
            filtered = allMessages.filter(m => new Date(m.receivedAt) >= thisMonthStart);
            break;
        case 'last_month':
            filtered = allMessages.filter(m => {
                const d = new Date(m.receivedAt);
                return d >= lastMonthStart && d <= lastMonthEnd;
            });
            break;
        case 'two_months_ago':
            filtered = allMessages.filter(m => {
                const d = new Date(m.receivedAt);
                return d >= twoMonthsAgoStart && d <= twoMonthsAgoEnd;
            });
            break;
        default:
            filtered = allMessages;
    }

    renderMessages(filtered);
    updateMessageCount(filtered.length);
}

 
 
 

function setupEventListeners() {
     
    const copyBtn = document.getElementById('copyLinkBtn');
    if (copyBtn) {
        copyBtn.addEventListener('click', copyShareLink);
    }

     
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }

     
    document.querySelectorAll('.filter-item').forEach(item => {
        item.addEventListener('click', () => filterMessages(item.dataset.filter));
    });

     
    document.querySelectorAll('.stat-card').forEach(card => {
        card.addEventListener('click', () => {
            const filter = card.dataset.filter;
            if (filter) filterMessages(filter);
        });
    });

     
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', () => {
            loadMessages(true);
            loadStats(true);
        });
    }
}

 
 
 

async function copyShareLink() {
    const shareLinkEl = document.getElementById('shareLink');
    const copyBtn = document.getElementById('copyLinkBtn');

    const originalHTML = copyBtn.innerHTML;
    const copiedHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 4px;"><polyline points="20 6 9 17 4 12"/></svg>Copié!`;

    try {
        await navigator.clipboard.writeText(shareLinkEl.value);
        copyBtn.innerHTML = copiedHTML;
        setTimeout(() => { copyBtn.innerHTML = originalHTML; }, 2000);
    } catch (error) {
        shareLinkEl.select();
        document.execCommand('copy');
        copyBtn.innerHTML = copiedHTML;
        setTimeout(() => { copyBtn.innerHTML = originalHTML; }, 2000);
    }
}

async function handleLogout() {
    const result = await logout();
    if (result.success) {
        window.location.href = 'index.html';
    }
}

 
 
 

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

 
document.addEventListener('DOMContentLoaded', initDashboard);
