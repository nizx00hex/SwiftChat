/**
 * SwiftChat Request System - Frontend
 */

const RequestSystem = {
    // Send a chat request
    async sendRequest(username, message = '') {
        try {
            const response = await fetch('/api/requests.php?action=send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCSRFToken(),
                    'Authorization': 'Bearer ' + getAuthToken()
                },
                body: JSON.stringify({ username, message })
            });
            return await response.json();
        } catch (error) {
            console.error('Send request failed:', error);
            return { success: false, message: 'Network error' };
        }
    },

    // Accept a request
    async acceptRequest(requestId) {
        try {
            const response = await fetch('/api/requests.php?action=accept', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCSRFToken(),
                    'Authorization': 'Bearer ' + getAuthToken()
                },
                body: JSON.stringify({ request_id: requestId })
            });
            return await response.json();
        } catch (error) {
            console.error('Accept failed:', error);
            return { success: false, message: 'Network error' };
        }
    },

    // Reject a request
    async rejectRequest(requestId) {
        try {
            const response = await fetch('/api/requests.php?action=reject', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCSRFToken(),
                    'Authorization': 'Bearer ' + getAuthToken()
                },
                body: JSON.stringify({ request_id: requestId })
            });
            return await response.json();
        } catch (error) {
            console.error('Reject failed:', error);
            return { success: false, message: 'Network error' };
        }
    },

    // Block a user
    async blockUser(userId, reason = '') {
        try {
            const response = await fetch('/api/requests.php?action=block', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCSRFToken(),
                    'Authorization': 'Bearer ' + getAuthToken()
                },
                body: JSON.stringify({ user_id: userId, reason })
            });
            return await response.json();
        } catch (error) {
            console.error('Block failed:', error);
            return { success: false, message: 'Network error' };
        }
    },

    // Unblock a user
    async unblockUser(userId) {
        try {
            const response = await fetch('/api/requests.php?action=unblock', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': getCSRFToken(),
                    'Authorization': 'Bearer ' + getAuthToken()
                },
                body: JSON.stringify({ user_id: userId })
            });
            return await response.json();
        } catch (error) {
            console.error('Unblock failed:', error);
            return { success: false, message: 'Network error' };
        }
    },

    // Get pending requests
    async getPendingRequests() {
        try {
            const response = await fetch('/api/requests.php?action=pending', {
                headers: { 'Authorization': 'Bearer ' + getAuthToken() }
            });
            return await response.json();
        } catch (error) {
            console.error('Get pending failed:', error);
            return { success: false, requests: [] };
        }
    },

    // Get sent requests
    async getSentRequests() {
        try {
            const response = await fetch('/api/requests.php?action=sent', {
                headers: { 'Authorization': 'Bearer ' + getAuthToken() }
            });
            return await response.json();
        } catch (error) {
            console.error('Get sent failed:', error);
            return { success: false, requests: [] };
        }
    },

    // Get blocked users
    async getBlockedUsers() {
        try {
            const response = await fetch('/api/requests.php?action=blocked', {
                headers: { 'Authorization': 'Bearer ' + getAuthToken() }
            });
            return await response.json();
        } catch (error) {
            console.error('Get blocked failed:', error);
            return { success: false, blocked_users: [] };
        }
    },

    // Check if can message
    async canMessage(userId) {
        try {
            const response = await fetch(`/api/requests.php?action=check&user_id=${userId}`, {
                headers: { 'Authorization': 'Bearer ' + getAuthToken() }
            });
            return await response.json();
        } catch (error) {
            console.error('Check failed:', error);
            return { can_message: false };
        }
    },

    // Render pending requests UI
    async renderPendingRequests(containerId) {
        const container = document.getElementById(containerId);
        const data = await this.getPendingRequests();
        
        if (data.requests.length === 0) {
            container.innerHTML = '<p style="color:#7f8c8d;text-align:center;">No pending requests</p>';
            return;
        }
        
        container.innerHTML = data.requests.map(req => `
            <div class="request-card" id="request-${req.id}">
                <div class="request-info">
                    <strong>${escapeHTML(req.from_username)}</strong>
                    ${req.request_message ? `<p>"${escapeHTML(req.request_message)}"</p>` : ''}
                    <small>${timeAgo(req.created_at)}</small>
                </div>
                <div class="request-actions">
                    <button onclick="RequestSystem.handleAccept(${req.id})" class="btn-accept">✅ Accept</button>
                    <button onclick="RequestSystem.handleReject(${req.id})" class="btn-reject">❌ Reject</button>
                </div>
            </div>
        `).join('');
    },

    async handleAccept(requestId) {
        const result = await this.acceptRequest(requestId);
        if (result.success) {
            document.getElementById(`request-${requestId}`).remove();
            alert('Request accepted! You can now chat.');
            location.reload();
        } else {
            alert(result.message);
        }
    },

    async handleReject(requestId) {
        if (!confirm('Reject this request?')) return;
        const result = await this.rejectRequest(requestId);
        if (result.success) {
            document.getElementById(`request-${requestId}`).remove();
            alert('Request rejected.');
        } else {
            alert(result.message);
        }
    }
};

// Helper functions
function getAuthToken() {
    return localStorage.getItem('auth_token') || '';
}

function getCSRFToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function escapeHTML(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function timeAgo(dateString) {
    const seconds = Math.floor((new Date() - new Date(dateString)) / 1000);
    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + 'm ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + 'h ago';
    return Math.floor(seconds / 86400) + 'd ago';
}