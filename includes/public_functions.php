<?php 
/**
 * Get All Published Posts
 *
 * Fetches all posts from the database where the `published` column is set to `true`.
 * For each post, the associated topic is also retrieved and added to the result.
 *
 * Returns:
 * - An array of associative arrays, where each element represents a published post
 *   including its topic data.
 *
 * Process:
 * 1. Queries the `posts` table for all rows where `published=true`.
 * 2. Fetches the result as an associative array using `mysqli_fetch_all`.
 * 3. Iterates over each post and appends its associated topic using `getPostTopic($post_id)`.
 * 4. Collects enriched post data into `$final_posts` and returns it.
 *
 * Dependencies:
 * - Relies on the global `$conn` object (MySQLi connection).
 * - Uses a helper function `getPostTopic($post_id)` to fetch topic information.
 *
 * Example Use Case:
 * - Used on the public blog page to display only posts that are approved/published.
 */
function getPublishedPosts() {
        // use global $conn object in function
        global $conn;
        $sql = "SELECT * FROM posts WHERE published=true";
        $result = mysqli_query($conn, $sql);
        // fetch all posts as an associative array called $posts
        $posts = mysqli_fetch_all($result, MYSQLI_ASSOC);

        $final_posts = array();
        foreach ($posts as $post) {
                $post['topic'] = getPostTopic($post['id']); 
                array_push($final_posts, $post);
        }
        return $final_posts;
}
/* * * * * * * * * * * * * * *
* Receives a post id and
* Returns topic of the post
* * * * * * * * * * * * * * */
function getPostTopic($post_id){
        global $conn;
        $sql = "SELECT * FROM topics WHERE id=
                        (SELECT topic_id FROM post_topic WHERE post_id=$post_id) LIMIT 1";
        $result = mysqli_query($conn, $sql);
        $topic = mysqli_fetch_assoc($result);
        return $topic;
}
/* * * * * * * * * * * * * * * *
* Returns all posts under a topic
* * * * * * * * * * * * * * * * */
/**
 * Get All Published Posts By Topic
 *
 * Retrieves all published posts that are associated with a specific topic.
 * Each returned post includes additional topic information.
 *
 * Parameters:
 * - int $topic_id: The ID of the topic used to filter the posts.
 *
 * Returns:
 * - array: A list of associative arrays, where each array represents a published post
 *   with its corresponding topic data.
 *
 * SQL Logic:
 * - The main query selects posts from the `posts` table where the post ID exists in the
 *   `post_topic` table for the given `$topic_id`.
 * - `GROUP BY` and `HAVING COUNT(1) = 1` ensures only posts uniquely tied to that topic are selected
 *   (i.e., posts that belong to exactly one topic).
 *
 * Process:
 * 1. Executes a nested query to retrieve post IDs associated with the given topic ID.
 * 2. Fetches the full post data for matching IDs.
 * 3. For each post, calls `getPostTopic($post_id)` to attach topic details.
 * 4. Assembles and returns a list of these enriched posts.
 *
 * Dependencies:
 * - Uses the global `$conn` object (MySQLi connection).
 * - Depends on the `getPostTopic($post_id)` function to append topic info.
 *
 * Notes:
 * - This function does not explicitly check `published = true`, so it assumes
 *   either only published posts are stored in the topic relationship or such a check
 *   is unnecessary for this context. Consider adding a `AND ps.published = true` clause
 *   for clarity and correctness.
 *
 * Example Use Case:
 * - Displaying posts under a specific category/topic on the frontend.
 */
function getPublishedPostsByTopic($topic_id) {
        global $conn;
        $sql = "SELECT * FROM posts ps 
                        WHERE ps.id IN 
                        (SELECT pt.post_id FROM post_topic pt 
                                WHERE pt.topic_id=$topic_id GROUP BY pt.post_id 
                                HAVING COUNT(1) = 1)";
        $result = mysqli_query($conn, $sql);
        // fetch all posts as an associative array called $posts
        $posts = mysqli_fetch_all($result, MYSQLI_ASSOC);

        $final_posts = array();
        foreach ($posts as $post) {
                $post['topic'] = getPostTopic($post['id']); 
                array_push($final_posts, $post);
        }
        return $final_posts;
}
/* * * * * * * * * * * * * * * *
* Returns topic name by topic id
* * * * * * * * * * * * * * * * */
function getTopicNameById($id)
{
        global $conn;
        $sql = "SELECT name FROM topics WHERE id=$id";
        $result = mysqli_query($conn, $sql);
        $topic = mysqli_fetch_assoc($result);
        return $topic['name'];
}
/* * * * * * * * * * * * * * *
* Returns a single post
* * * * * * * * * * * * * * */
/**
 * Get a Single Published Post by Slug
 *
 * Retrieves a single published post from the database based on its slug.
 * Also fetches and attaches the topic associated with the post.
 *
 * Parameters:
 * - string $slug: (Currently unused) Expected to represent the post's slug, but the function instead
 *   uses `$_GET['post-slug']` directly from the URL query parameters.
 *
 * Returns:
 * - array|null: An associative array containing post details and its related topic if found and published,
 *   or null if no matching post is found.
 *
 * Behavior:
 * 1. Reads the `post-slug` directly from the global `$_GET` array.
 * 2. Queries the `posts` table to find a post with the matching slug that is also published.
 * 3. If a post is found, appends the topic info using `getPostTopic($post['id'])`.
 * 4. Returns the resulting post array.
 *
 * Dependencies:
 * - Uses the global `$conn` object (MySQLi connection).
 * - Uses `getPostTopic($post_id)` to enrich the result with topic data.
 *
 * Caveats:
 * - The function accepts `$slug` as a parameter but does not use it. 
 *   Instead, it directly accesses `$_GET['post-slug']`, which reduces reusability and testability.
 *   Consider replacing `$_GET['post-slug']` with the `$slug` parameter for better function design.
 *
 * Security:
 * - The current implementation does not sanitize the `$_GET` input before inserting it into the SQL query,
 *   which poses a risk of SQL injection. Use parameterized queries or sanitize the input with `mysqli_real_escape_string()`
 *   or a proper escape function.
 *
 * Example Use Case:
 * - Displaying a single blog post on the frontend based on the slug from the URL.
 */
function getPost($slug){
        global $conn;
        // Get single post slug
        $post_slug = $_GET['post-slug'];
        $sql = "SELECT * FROM posts WHERE slug='$post_slug' AND published=true";
        $result = mysqli_query($conn, $sql);

        // fetch query results as associative array.
        $post = mysqli_fetch_assoc($result);
        if ($post) {
                // get the topic to which this post belongs
                $post['topic'] = getPostTopic($post['id']);
        }
        return $post;
}
/* * * * * * * * * * * *
*  Returns all topics
* * * * * * * * * * * * */
function getAllTopics()
{
        global $conn;
        $sql = "SELECT * FROM topics";
        $result = mysqli_query($conn, $sql);
        $topics = mysqli_fetch_all($result, MYSQLI_ASSOC);
        return $topics;
}
?>