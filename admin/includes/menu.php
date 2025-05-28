<!--
|--------------------------------------------------------------------------
| Admin Sidebar Menu
|--------------------------------------------------------------------------
| This section renders a sidebar card with administrative action links.
| It provides quick navigation for the admin to manage different parts of
| the application such as posts, users, and topics.
|
| Elements:
| - A styled container with the class "menu" and a "card" component inside.
| - The "card-header" displays the title "Actions".
| - The "card-content" contains navigation links for:
|     1. Creating new posts
|     2. Managing existing posts
|     3. Managing user accounts
|     4. Managing discussion topics
|
| PHP Usage:
| - Uses `BASE_URL` to generate correct paths to admin routes.
|
| Notes:
| - Ensure `BASE_URL` is defined in the config and points to the root of the project.
| - Styling and layout depend on external CSS for `.menu`, `.card`, etc.
-->
<div class="menu">
        <div class="card">
                <div class="card-header">
                        <h2>Actions</h2>
                </div>
                <div class="card-content">
                        <a href="<?php echo BASE_URL . 'admin/create_post.php' ?>">Create Posts</a>
                        <a href="<?php echo BASE_URL . 'admin/posts.php' ?>">Manage Posts</a>
                        <a href="<?php echo BASE_URL . 'admin/users.php' ?>">Manage Users</a>
                </div>
        </div>
</div>