<?php
$currentUser = $_SESSION['user'] ?? null;
$unreadCount = $_SESSION['unread_requests'] ?? 0;
?>
<nav class="navbar">
    <div class="nav-brand">
        <a href="/">⚡ SwiftChat</a>
    </div>
    <div class="nav-links">
        <a href="/chat.php">Chat</a>
        <a href="/requests.php">
            Requests
            <?php if ($unreadCount > 0): ?>
                <span class="badge"><?php echo $unreadCount; ?></span>
            <?php endif; ?>
        </a>
        <span id="notification-badge" class="badge" style="display:none;"></span>
        <?php if ($currentUser): ?>
            <a href="/profile.php"><?php echo htmlspecialchars($currentUser['username']); ?></a>
            <a href="/api/auth.php?action=logout">Logout</a>
        <?php else: ?>
            <a href="/login.php">Login</a>
        <?php endif; ?>
    </div>
</nav>