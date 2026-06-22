<?php
/**
 * Email template: New Chat Request notification
 * Variables: $username, $fromUser, $message, $acceptLink
 */
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #3498db; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; }
        .btn { 
            display: inline-block; padding: 12px 24px; 
            background: #2ecc71; color: white; text-decoration: none; 
            border-radius: 5px; margin: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header"><h1>New Chat Request!</h1></div>
        <div class="content">
            <h2>Hello <?php echo htmlspecialchars($username); ?>!</h2>
            <p><strong><?php echo htmlspecialchars($fromUser); ?></strong> wants to chat with you!</p>
            <?php if ($message): ?>
                <p>Message: "<?php echo htmlspecialchars($message); ?>"</p>
            <?php endif; ?>
            <p><a href="<?php echo $acceptLink; ?>" class="btn">View Request</a></p>
        </div>
    </div>
</body>
</html>