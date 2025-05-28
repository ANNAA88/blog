<?php
include 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<h2><?php echo $_SESSION['username']; ?>'s Blog</h2>

<a href="create_post.php">Create New Post</a>

<?php while ($row = $result->fetch_assoc()): ?>
    <div>
        <h3><?php echo htmlspecialchars($row['title']); ?></h3>
        <p><?php echo nl2br(htmlspecialchars($row['content'])); ?></p>
        <small>Posted on: <?php echo $row['created_at']; ?></small>
    </div>
    <hr>
<?php endwhile; ?>
