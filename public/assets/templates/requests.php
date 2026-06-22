<?php
// Requests page - shows pending requests and sent requests
session_start();
$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser) { header('Location: /login.php'); exit; }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Chat Requests - SwiftChat</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <meta name="csrf-token" content="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
</head>
<body>
    <?php include 'navigation.php'; ?>
    
    <div class="container">
        <h2>Chat Requests</h2>
        
        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('pending')">Pending (Incoming)</button>
            <button class="tab-btn" onclick="showTab('sent')">Sent</button>
            <button class="tab-btn" onclick="showTab('blocked')">Blocked Users</button>
        </div>
        
        <!-- Pending Requests -->
        <div id="pending-tab" class="tab-content">
            <h3>Requests from others</h3>
            <div id="pending-requests"></div>
        </div>
        
        <!-- Sent Requests -->
        <div id="sent-tab" class="tab-content" style="display:none;">
            <h3>Your sent requests</h3>
            <div id="sent-requests"></div>
        </div>
        
        <!-- Blocked Users -->
        <div id="blocked-tab" class="tab-content" style="display:none;">
            <h3>Blocked users</h3>
            <div id="blocked-users"></div>
        </div>
    </div>
    
    <script src="/assets/js/requests.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            RequestSystem.renderPendingRequests('pending-requests');
        });
        
        function showTab(tab) {
            document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById(tab + '-tab').style.display = 'block';
            event.target.classList.add('active');
            
            if (tab === 'pending') RequestSystem.renderPendingRequests('pending-requests');
            if (tab === 'sent') loadSentRequests();
            if (tab === 'blocked') loadBlockedUsers();
        }
        
        async function loadSentRequests() {
            const data = await RequestSystem.getSentRequests();
            const container = document.getElementById('sent-requests');
            if (data.requests.length === 0) {
                container.innerHTML = '<p>No sent requests.</p>';
                return;
            }
            container.innerHTML = data.requests.map(r => `
                <div class="request-card">
                    <strong>${r.to_username}</strong>
                    <span class="badge badge-${r.status}">${r.status}</span>
                    <small>${timeAgo(r.created_at)}</small>
                </div>
            `).join('');
        }
        
        async function loadBlockedUsers() {
            const data = await RequestSystem.getBlockedUsers();
            const container = document.getElementById('blocked-users');
            if (data.blocked_users.length === 0) {
                container.innerHTML = '<p>No blocked users.</p>';
                return;
            }
            container.innerHTML = data.blocked_users.map(b => `
                <div class="request-card">
                    <strong>${b.blocked_username}</strong>
                    <button onclick="unblockUser(${b.blocked_id})">Unblock</button>
                </div>
            `).join('');
        }
        
        async function unblockUser(userId) {
            const result = await RequestSystem.unblockUser(userId);
            alert(result.message);
            loadBlockedUsers();
        }
    </script>
</body>
</html>