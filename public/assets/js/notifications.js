/**
 * SwiftChat Notifications - Frontend
 */

const NotificationSystem = {
    // Get unread count and update badge
    async updateBadge() {
        try {
            const response = await fetch('/api/notifications.php?action=count', {
                headers: { 'Authorization': 'Bearer ' + getAuthToken() }
            });
            const data = await response.json();
            
            const badge = document.getElementById('notification-badge');
            if (badge) {
                badge.textContent = data.count || '';
                badge.style.display = data.count > 0 ? 'inline' : 'none';
            }
        } catch (error) {
            console.error('Notification badge update failed:', error);
        }
    },

    // Get unread notifications
    async getUnread() {
        try {
            const response = await fetch('/api/notifications.php?action=unread', {
                headers: { 'Authorization': 'Bearer ' + getAuthToken() }
            });
            return await response.json();
        } catch (error) {
            return { notifications: [], unread_count: 0 };
        }
    },

    // Mark as read
    async markAsRead(id) {
        await fetch(`/api/notifications.php?action=read&id=${id}`, {
            headers: { 'Authorization': 'Bearer ' + getAuthToken() }
        });
        this.updateBadge();
    },

    // Mark all as read
    async markAllAsRead() {
        await fetch('/api/notifications.php?action=read-all', {
            method: 'PUT',
            headers: { 'Authorization': 'Bearer ' + getAuthToken() }
        });
        this.updateBadge();
    },

    // Poll for new notifications
    startPolling(intervalMs = 10000) {
        setInterval(() => this.updateBadge(), intervalMs);
    }
};