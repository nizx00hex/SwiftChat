/**
 * SwiftChat WebSocket Client
 * Real-time messaging and notifications
 */

class SwiftWebSocket {
    constructor() {
        this.ws = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 10;
        this.messageHandlers = [];
        this.notificationHandlers = [];
    }

    connect() {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const host = window.location.hostname;
        const port = '8080';
        const token = getAuthToken();

        this.ws = new WebSocket(`${protocol}//${host}:${port}`);

        this.ws.onopen = () => {
            console.log('WebSocket connected');
            this.reconnectAttempts = 0;
            // Authenticate
            this.send({
                type: 'auth',
                token: token
            });
        };

        this.ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleMessage(data);
        };

        this.ws.onclose = () => {
            console.log('WebSocket disconnected');
            this.reconnect();
        };

        this.ws.onerror = (error) => {
            console.error('WebSocket error:', error);
        };
    }

    handleMessage(data) {
        switch (data.type) {
            case 'message':
                this.messageHandlers.forEach(handler => handler(data));
                break;
            case 'notification':
                this.notificationHandlers.forEach(handler => handler(data));
                NotificationSystem.updateBadge();
                break;
            case 'request_update':
                // Refresh requests if on requests page
                if (typeof RequestSystem !== 'undefined') {
                    RequestSystem.renderPendingRequests('pending-requests');
                }
                break;
        }
    }

    send(data) {
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify(data));
        }
    }

    onMessage(handler) {
        this.messageHandlers.push(handler);
    }

    onNotification(handler) {
        this.notificationHandlers.push(handler);
    }

    reconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            const delay = Math.min(1000 * Math.pow(2, this.reconnectAttempts), 30000);
            setTimeout(() => this.connect(), delay);
        }
    }

    disconnect() {
        if (this.ws) {
            this.ws.close();
        }
    }
}

// Initialize WebSocket
const wsClient = new SwiftWebSocket();
wsClient.connect();

// Handle incoming messages
wsClient.onMessage((data) => {
    if (typeof addMessageToChat === 'function') {
        addMessageToChat(data);
    }
});

// Handle notifications
wsClient.onNotification((data) => {
    if (data.title) {
        showToast(data.title, data.message);
    }
});