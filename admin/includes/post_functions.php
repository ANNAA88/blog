<?php 
// Post variables
$post_id = 0;
$isEditingPost = false;
$published = 1;
$title = "";
$post_slug = "";
$body = "";
$featured_image = "";
$post_topic = "";

/* - - - - - - - - - - 
-  Post functions
- - - - - - - - - - -*/
/**
 * Retrieve all posts from the database based on the user's role.
 *
 * This function fetches posts from the database. Admin users retrieve all posts, 
 * while Author users only retrieve posts they have created. After fetching, it 
 * adds the author's name to each post using `getPostAuthorById()`.
 *
 * @return array $final_posts - An array of associative arrays, each representing a post 
 *                               with an additional 'author' field.
 *
 * Global Variables Used:
 * - $conn: MySQLi connection object.
 * - $_SESSION['user']: Used to determine the role and ID of the currently logged-in user.
 *
 * Behavior:
 * - Admins: `SELECT * FROM posts`
 * - Authors: `SELECT * FROM posts WHERE user_id = [author's ID]`
 * - Adds an 'author' key to each post using the `getPostAuthorById()` function.
 *
 * Dependencies:
 * - `getPostAuthorById($user_id)`: Should return the author's name or display name.
 *
 * Notes:
 * - Assumes the `user_id` column exists in the `posts` table.
 * - Make sure user session is initialized and contains role and ID before calling this.
 * - No error handling for failed SQL queries; consider checking `mysqli_error()` in production.
 */
function getAllPosts()
{
        global $conn;
        
        // Admin can view all posts
        // Author can only view their posts
        if ($_SESSION['user']['role'] == "Admin") {
                $sql = "SELECT * FROM posts";
        } elseif ($_SESSION['user']['role'] == "Author") {
                $user_id = $_SESSION['user']['id'];
                $sql = "SELECT * FROM posts WHERE user_id=$user_id";
        }
        $result = mysqli_query($conn, $sql);
       
        $posts = mysqli_fetch_all($result, MYSQLI_ASSOC);
      
        $final_posts = array();
        foreach ($posts as $post) {
                $post['author'] = getPostAuthorById($post['user_id']);
                array_push($final_posts, $post);
        }
        return $final_posts;
}
// get the author/username of a post
function getPostAuthorById($user_id)
{
        global $conn;
        $sql = "SELECT username FROM users WHERE id=$user_id";
        $result = mysqli_query($conn, $sql);
        if ($result) {
                // return username
                return mysqli_fetch_assoc($result)['username'];
        } else {
                return null;
        }
}
/* - - - - - - - - - - 
-  Post actions
- - - - - - - - - - -*/
// if user clicks the create post button
if (isset($_POST['create_post'])) { createPost($_POST); }
// if user clicks the Edit post button
if (isset($_GET['edit-post'])) {
        $isEditingPost = true;
        $post_id = $_GET['edit-post'];
        editPost($post_id);
}
// if user clicks the update post button
if (isset($_POST['update_post'])) {
        updatePost($_POST);
}
// if user clicks the Delete post button
if (isset($_GET['delete-post'])) {
        $post_id = $_GET['delete-post'];
        deletePost($post_id);
}

/* - - - - - - - - - - 
-  Post functions
- - - - - - - - - - -*/
/**
 * Creates a new blog post with the provided request values.
 *
 * This function processes user-submitted data for creating a blog post. It validates the inputs,
 * checks for duplicate post titles (slugs), handles the image upload, inserts the post into the database,
 * and links the post to its selected topic.
 *
 * @param array $request_values Associative array containing the post form inputs:
 *                              - 'title': The title of the post.
 *                              - 'body': The content of the post.
 *                              - 'topic_id': The ID of the topic the post belongs to (optional).
 *                              - 'publish': Whether the post is published (1) or draft (0) (optional).
 *                              - 'featured_image': The uploaded featured image (from $_FILES).
 *
 * Global Variables:
 * - $conn (mysqli): The database connection.
 * - $errors (array): Array to collect validation or processing errors.
 * - $title, $featured_image, $topic_id, $body, $published: Used to store input values for reuse.
 *
 * Side Effects:
 * - Updates the $errors array if validation fails.
 * - Stores the image file in the ../static/images/ directory.
 * - Inserts data into `posts` and `post_topic` tables if validation passes.
 * - Sets a success message in $_SESSION and redirects to posts.php.
 */
function createPost($request_values)
{
    global $conn, $errors, $title, $featured_image, $topic_id, $body, $published;

    $title = esc($request_values['title']);
    $body = htmlentities(esc($request_values['body']));
    $featured_image = ''; // Default empty image

    if (isset($request_values['topic_id'])) {
        $topic_id = esc($request_values['topic_id']);
    }
    if (isset($request_values['publish'])) {
        $published = esc($request_values['publish']);
    }

    $post_slug = makeSlug($title);

    // Validation
    if (empty($title)) array_push($errors, "Post title is required");
    if (empty($body)) array_push($errors, "Post body is required");
    if (empty($topic_id)) array_push($errors, "Post topic is required");

    // Handle optional image
    if (!empty($_FILES['featured_image']['name'])) {
        $featured_image = $_FILES['featured_image']['name'];
        $target = "../static/images/" . basename($featured_image);
        if (!move_uploaded_file($_FILES['featured_image']['tmp_name'], $target)) {
            array_push($errors, "Failed to upload image. Please check file settings for your server");
        }
    }

    // Check for duplicate slug
    $post_check_query = "SELECT * FROM posts WHERE slug='$post_slug' LIMIT 1";
    $result = mysqli_query($conn, $post_check_query);
    if (mysqli_num_rows($result) > 0) {
        array_push($errors, "A post already exists with that title.");
    }

    if (count($errors) == 0) {
        $query = "INSERT INTO posts (user_id, title, slug, image, body, published, created_at, updated_at) 
                  VALUES(1, '$title', '$post_slug', '$featured_image', '$body', $published, now(), now())";
        if (mysqli_query($conn, $query)) {
            $inserted_post_id = mysqli_insert_id($conn);
            $sql = "INSERT INTO post_topic (post_id, topic_id) VALUES($inserted_post_id, $topic_id)";
            mysqli_query($conn, $sql);

            $_SESSION['message'] = "Post created successfully";
            header('location: posts.php');
            exit(0);
        }
    }
}

        /* * * * * * * * * * * * * * * * * * * * *
        * - Takes post id as parameter
        * - Fetches the post from database
        * - sets post fields on form for editing
        * * * * * * * * * * * * * * * * * * * * * */
        function editPost($role_id)
        {
                global $conn, $title, $post_slug, $body, $published, $isEditingPost, $post_id;
                $sql = "SELECT * FROM posts WHERE id=$role_id LIMIT 1";
                $result = mysqli_query($conn, $sql);
                $post = mysqli_fetch_assoc($result);
                // set form values on the form to be updated
                    $post_id = $role_id;
    $title = $post['title'];
    $body = $post['body'];
    $published = $post['published'];
    $featured_image = $post['image'];

    // Get topic from post_topic table
    $topic_query = mysqli_query($conn, "SELECT topic_id FROM post_topic WHERE post_id=$role_id LIMIT 1");
    if ($topic = mysqli_fetch_assoc($topic_query)) {
        $topic_id = $topic['topic_id'];
    }

    $isEditingPost = true;
}
/**
 * Updates an existing blog post with the provided request values.
 *
 * This function processes form inputs to update a blog post's content, title, image,
 * topic association, and published status. It validates input, handles optional
 * featured image uploads, updates the post in the `posts` table, and maintains
 * the post-topic relationship in the `post_topic` table.
 *
 * @param array $request_values Associative array containing:
 *                              - 'title': Updated post title.
 *                              - 'body': Updated post content.
 *                              - 'post_id': ID of the post to be updated.
 *                              - 'topic_id': (Optional) ID of the new topic.
 *                              - 'featured_image': (Optional) New featured image file (via $_FILES).
 *
 * Global Variables:
 * - $conn (mysqli): Database connection.
 * - $errors (array): Stores error messages if validation fails.
 * - $post_id, $title, $featured_image, $topic_id, $body, $published: Post properties.
 *
 * Side Effects:
 * - Updates data in the `posts` table.
 * - Updates `post_topic` association.
 * - Handles featured image file upload.
 * - Redirects to posts.php on success with a success message in the session.
 */
       function updatePost($request_values)
{
    global $conn, $errors, $post_id, $title, $featured_image, $topic_id, $body, $published;

    $title = esc($request_values['title']);
    $body = esc($request_values['body']);
    $post_id = esc($request_values['post_id']);

    if (isset($request_values['topic_id'])) {
        $topic_id = esc($request_values['topic_id']);
    }

    $post_slug = makeSlug($title);

    if (empty($title)) array_push($errors, "Post title is required");
    if (empty($body)) array_push($errors, "Post body is required");

    $update_image_query = ""; // Blank by default

    // Handle optional image
    if (!empty($_FILES['featured_image']['name'])) {
        $featured_image = $_FILES['featured_image']['name'];
        $target = "../static/images/" . basename($featured_image);
        if (!move_uploaded_file($_FILES['featured_image']['tmp_name'], $target)) {
            array_push($errors, "Failed to upload image. Please check file settings for your server");
        } else {
            $update_image_query = ", image='$featured_image'";
        }
    }

    if (count($errors) == 0) {
        $query = "UPDATE posts SET title='$title', views=0, body='$body', published=$published $update_image_query, updated_at=now() WHERE id=$post_id";
        if (mysqli_query($conn, $query)) {
            if (!empty($topic_id)) {
    // Check if topic_id exists
    $check_topic = mysqli_query($conn, "SELECT id FROM topics WHERE id=$topic_id LIMIT 1");
    if (mysqli_num_rows($check_topic) > 0) {
        // Clear old topic relationship
        mysqli_query($conn, "DELETE FROM post_topic WHERE post_id=$post_id");

        // Insert new topic relationship
        $sql = "INSERT INTO post_topic (post_id, topic_id) VALUES($post_id, $topic_id)";
        mysqli_query($conn, $sql);
    } else {
        array_push($errors, "Selected topic does not exist.");
    }
}


            $_SESSION['message'] = "Post updated successfully";
            header('location: posts.php');
            exit(0);
        }
    }
}
 
        // if user clicks the publish post button
if (isset($_GET['publish']) || isset($_GET['unpublish'])) {
        $message = "";
        if (isset($_GET['publish'])) {
                $message = "Post published successfully";
                $post_id = $_GET['publish'];
        } else if (isset($_GET['unpublish'])) {
                $message = "Post successfully unpublished";
                $post_id = $_GET['unpublish'];
        }
        togglePublishPost($post_id, $message);
}
// delete blog post
        function deletePost($post_id)
        {
                global $conn;
                $sql = "DELETE FROM posts WHERE id=$post_id";
                if (mysqli_query($conn, $sql)) {
                        $_SESSION['message'] = "Post successfully deleted";
                        header("location: posts.php");
                        exit(0);
                }
        }
/**
 * Toggles the published status of a blog post.
 *
 * This function inverts the `published` field of a specific post in the `posts` table.
 * If the post is currently published (`1`), it will be unpublished (`0`), and vice versa.
 * After the update, it sets a success message in the session and redirects to the posts management page.
 *
 * @param int $post_id The ID of the post to toggle.
 * @param string $message The success message to display after the update.
 *
 * Global Variables:
 * - $conn (mysqli): The database connection.
 *
 * Side Effects:
 * - Updates the `published` field in the `posts` table.
 * - Sets a session message.
 * - Redirects to posts.php and halts further script execution.
 */
function togglePublishPost($post_id, $message)
{
        global $conn;
        $sql = "UPDATE posts SET published=!published WHERE id=$post_id";
        
        if (mysqli_query($conn, $sql)) {
                $_SESSION['message'] = $message;
                header("location: posts.php");
                exit(0);
        }
}

?>