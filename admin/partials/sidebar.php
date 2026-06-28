<?php $current = basename($_SERVER['SCRIPT_NAME'] ?? ''); ?>
<aside class="wp-sidebar">
    <a href="index.php" class="wp-brand"><img src="../assets/images/flexi-feet-logo.png" alt="Flexi Feet"></a>
    <nav>
        <a class="<?= $current === 'index.php' ? 'active' : '' ?>" href="index.php">Dashboard</a>
        <a href="index.php#appointments">Appointments</a>
        <div class="menu-group">
            <span>Posts</span>
            <a class="<?= $current === 'posts.php' ? 'active' : '' ?>" href="posts.php">All Posts</a>
            <a class="<?= $current === 'post-edit.php' ? 'active' : '' ?>" href="post-edit.php">Add New</a>
        </div>
        <a class="<?= $current === 'media.php' ? 'active' : '' ?>" href="media.php">Media</a>
        <a class="<?= $current === 'reels.php' ? 'active' : '' ?>" href="reels.php">Instagram Reels</a>
        <a href="../" target="_blank">View Website</a>
    </nav>
    <a href="logout.php" class="wp-logout">Logout</a>
</aside>
