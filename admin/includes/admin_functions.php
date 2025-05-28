<?php 
include_once('../config.php'); // adjust path as needed
// Admin user variables
$admin_id = 0;
$isEditingUser = false;
$username = "";
$role = "";
$email = "";
// general variables
$errors = [];

$username = "";
$email = "";
$role = "";
$admin_id = 0;
$isEditingUser = false;
$errors = array();

/*
|--------------------------------------------------------------------------
| Admin Users Actions Handler
|--------------------------------------------------------------------------
| This section handles CRUD operations for admin users based on user input:
|
| 1. Create Admin  - Triggered by the 'create_admin' POST request.
| 2. Edit Admin    - Triggered by the 'edit-admin' GET request.
| 3. Update Admin  - Triggered by the 'update_admin' POST request.
| 4. Delete Admin  - Triggered by the 'delete-admin' GET request.
|
| Each operation calls the corresponding function with required parameters.
|
| Functions:
| - createAdmin($data): Handles creation of a new admin user.
| - editAdmin($id): Loads the data of an admin for editing.
| - updateAdmin($data): Updates an existing admin user's information.
| - deleteAdmin($id): Deletes an admin user by ID.
*/

if (isset($_POST['create_admin'])) {
        createAdmin($_POST);
}

if (isset($_GET['edit-admin'])) {
        $isEditingUser = true;
        $admin_id = $_GET['edit-admin'];
        editAdmin($admin_id);
}

if (isset($_POST['update_admin'])) {
        updateAdmin($_POST);
}

if (isset($_GET['delete-admin'])) {
        $admin_id = $_GET['delete-admin'];
        deleteAdmin($admin_id);
}
if (isset($_POST['create_adminuser'])) {
    createAdminuser($_POST);
}

/* - - - - - - - - - - - -
-  Admin users functions
- - - - - - - - - - - - -*/
/* * * * * * * * * * * * * * * * * * * * * * *
* - Receives new admin data from form
* - Create new admin user
* - Returns all admin users with their roles 
* * * * * * * * * * * * * * * * * * * * * * */
/**
 * Create a new admin user based on the submitted form data.
 *
 * This function handles the server-side logic for registering a new admin user. 
 * It performs input sanitization, validates required fields, checks for duplicate 
 * usernames and emails, encrypts the password using MD5 (note: not recommended for production), 
 * and inserts the new user into the database.
 *
 * @param array $request_values - An associative array containing form inputs:
 *                                'username', 'email', 'password',
 *                                'passwordConfirmation', and optionally 'role'.
 *
 * Global Variables Used:
 * - $conn: MySQLi connection object
 * - $errors: Array used to store validation error messages
 * - $role, $username, $email: Used to temporarily store form values
 *
 * Validation Checks:
 * - Ensures all required fields are filled
 * - Password and password confirmation must match
 * - Username and email must be unique
 *
 * On Success:
 * - Password is encrypted with MD5
 * - New admin record is inserted into the users table
 * - Success message is stored in the session
 * - Redirects to 'users.php'
 *
 * On Failure:
 * - Error messages are pushed to the $errors array
 * - No database action is taken
 */
function createAdmin($request_values){
        global $conn, $errors, $role, $username, $email;
        $username = esc($request_values['username']);
        $email = esc($request_values['email']);
        $password = esc($request_values['password']);
        $passwordConfirmation = esc($request_values['passwordConfirmation']);

        if(isset($request_values['role'])){
                $role = esc($request_values['role']);
        }
        // form validation: ensure that the form is correctly filled
        if (empty($username)) { array_push($errors, "Uhmm...We gonna need the username"); }
        if (empty($email)) { array_push($errors, "Oops.. Email is missing"); }
        if (empty($role)) { array_push($errors, "Role is required for admin users");}
        if (empty($password)) { array_push($errors, "uh-oh you forgot the password"); }
        if ($password != $passwordConfirmation) { array_push($errors, "The two passwords do not match"); }
        // Ensure that no user is registered twice. 
        // the email and usernames should be unique
        $user_check_query = "SELECT * FROM users WHERE username='$username' 
                                                        OR email='$email' LIMIT 1";
        $result = mysqli_query($conn, $user_check_query);
        $user = mysqli_fetch_assoc($result);
        if ($user) { // if user exists
                if ($user['username'] === $username) {
                  array_push($errors, "Username already exists");
                }

                if ($user['email'] === $email) {
                  array_push($errors, "Email already exists");
                }
        }
        // register user if there are no errors in the form
        if (count($errors) == 0) {
                $password = md5($password);//encrypt the password before saving in the database
                $query = "INSERT INTO users (username, email, role, password, created_at, updated_at) 
                                  VALUES('$username', '$email', '$role', '$password', now(), now())";
                mysqli_query($conn, $query);

                $_SESSION['message'] = "Admin user created successfully";
                header('location: users.php');
                exit(0);
        }
}
/* 
* - Takes admin id as parameter
* - Fetches the admin from database
* - sets admin fields on form for editing
 */
function editAdmin($admin_id)
{
        global $conn, $username, $role, $isEditingUser, $admin_id, $email;

        $sql = "SELECT * FROM users WHERE id=$admin_id LIMIT 1";
        $result = mysqli_query($conn, $sql);
        $admin = mysqli_fetch_assoc($result);

        // set form values ($username and $email) on the form to be updated
        $username = $admin['username'];
        $email = $admin['email'];
}

/**
 * Update an existing admin user's details.
 *
 * This function processes form data to update an admin user's information 
 * in the database. It retrieves the admin ID from the form input, 
 * sanitizes the data, encrypts the password, and executes an UPDATE query.
 *
 * @param array $request_values - Associative array containing:
 *                                'admin_id', 'username', 'email', 
 *                                'password', 'passwordConfirmation', and optionally 'role'.
 *
 * Global Variables Used:
 * - $conn: MySQLi connection object
 * - $errors: Array used to track validation errors
 * - $role, $username, $email: Temporarily store form input values
 * - $admin_id: ID of the admin being updated
 * - $isEditingUser: Set to false after update
 *
 * Behavior:
 * - Extracts and sanitizes form values
 * - Checks for role input and assigns if present
 * - If no validation errors:
 *     - Encrypts password using MD5 (note: use `password_hash()` in production)
 *     - Executes an UPDATE query to modify the user record
 *     - Sets a session success message
 *     - Redirects to 'users.php'
 *
 * Notes:
 * - Does not re-validate email/username uniqueness
 * - Relies on pre-populated $errors array for validation status
 * - Password is always re-hashed, regardless of whether it was changed
 */
function updateAdmin($request_values){
        global $conn, $errors, $role, $username, $isEditingUser, $admin_id, $email;
        // get id of the admin to be updated
        $admin_id = $request_values['admin_id'];
        // set edit state to false
        $isEditingUser = false;


        $username = esc($request_values['username']);
        $email = esc($request_values['email']);
        $password = esc($request_values['password']);
        $passwordConfirmation = esc($request_values['passwordConfirmation']);
        if(isset($request_values['role'])){
                $role = $request_values['role'];
        }
        // register user if there are no errors in the form
        if (count($errors) == 0) {
                //encrypt the password (security purposes)
                $password = md5($password);

                $query = "UPDATE users SET username='$username', email='$email', role='$role', password='$password' WHERE id=$admin_id";
                mysqli_query($conn, $query);

                $_SESSION['message'] = "Admin user updated successfully";
                header('location: users.php');
                exit(0);
        }
}
// delete admin user 
function deleteAdmin($admin_id) {
        global $conn;
        $sql = "DELETE FROM users WHERE id=$admin_id";
        if (mysqli_query($conn, $sql)) {
                $_SESSION['message'] = "User successfully deleted";
                header("location: users.php");
                exit(0);
        }
}


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * 
* - Returns all admin users and their corresponding roles
* * * * * * * * * * * * * * * * * * * * * * * * * * * * * */
function getAdminUsers(){
        global $conn, $roles;
        $sql = "SELECT * FROM users WHERE role IS NOT NULL";
        $result = mysqli_query($conn, $sql);
        $users = mysqli_fetch_all($result, MYSQLI_ASSOC);

        return $users;
}
/* * * * * * * * * * * * * * * * * * * * *
* - Escapes form submitted value, hence, preventing SQL injection
* * * * * * * * * * * * * * * * * * * * * */
function esc(String $value){
        // bring the global db connect object into function
        global $conn;
        // remove empty space sorrounding string
        $val = trim($value); 
        $val = mysqli_real_escape_string($conn, $value);
        return $val;
}
// Receives a string like 'Some Sample String'
// and returns 'some-sample-string'
function makeSlug(String $string){
        $string = strtolower($string);
        $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
        return $slug;
}
?>
<?php 
// Admin user variables
// ... varaibles here ...

// Topics variables
$topic_id = 0;
$isEditingTopic = false;
$topic_name = "";

/* - - - - - - - - - - 
-  Admin users actions
- - - - - - - - - - -*/
// ... 

/* - - - - - - - - - - 
-  Topic actions
- - - - - - - - - - -*/
// if user clicks the create topic button
if (isset($_POST['create_topic'])) { createTopic($_POST); }
// if user clicks the Edit topic button
if (isset($_GET['edit-topic'])) {
        $isEditingTopic = true;
        $topic_id = $_GET['edit-topic'];
        editTopic($topic_id);
}
// if user clicks the update topic button
if (isset($_POST['update_topic'])) {
        updateTopic($_POST);
}
// if user clicks the Delete topic button
if (isset($_GET['delete-topic'])) {
        $topic_id = $_GET['delete-topic'];
        deleteTopic($topic_id);
}

/* - - - - - - - - - - 
-  Topics functions
- - - - - - - - - - -*/
// get all topics from DB
function getAllTopics() {
        global $conn;
        $sql = "SELECT * FROM topics";
        $result = mysqli_query($conn, $sql);
        $topics = mysqli_fetch_all($result, MYSQLI_ASSOC);
        return $topics;
}
/**
 * Create a new topic in the database.
 *
 * This function handles the creation of a new topic. It sanitizes the input, 
 * generates a URL-friendly slug from the topic name, validates the input, 
 * and inserts the topic into the database if there are no validation errors.
 *
 * @param array $request_values - Associative array containing:
 *                                'topic_name' => The name of the topic to be created.
 *
 * Global Variables Used:
 * - $conn: MySQLi connection object.
 * - $errors: Array for collecting validation error messages.
 * - $topic_name: Temporarily stores the sanitized topic name.
 *
 * Function Behavior:
 * - Sanitizes the input topic name using `esc()`.
 * - Creates a slug using `makeSlug()` (e.g., "Life Advice" → "life-advice").
 * - Validates that the topic name is not empty.
 * - Checks if the topic already exists based on its slug.
 * - If valid, inserts the new topic into the `topics` table.
 * - Sets a session success message and redirects to `topics.php`.
 *
 * Notes:
 * - Assumes the `slug` field in the `topics` table is unique.
 * - Prevents duplicate topics by checking the slug.
 * - No handling for database errors beyond duplicate check.
 */
function createTopic($request_values){
        global $conn, $errors, $topic_name;
        $topic_name = esc($request_values['topic_name']);
        // create slug: if topic is "Life Advice", return "life-advice" as slug
        $topic_slug = makeSlug($topic_name);
        // validate form
        if (empty($topic_name)) { 
                array_push($errors, "Topic name required"); 
        }
        // Ensure that no topic is saved twice. 
        $topic_check_query = "SELECT * FROM topics WHERE slug='$topic_slug' LIMIT 1";
        $result = mysqli_query($conn, $topic_check_query);
        if (mysqli_num_rows($result) > 0) { // if topic exists
                array_push($errors, "Topic already exists");
        }
        // register topic if there are no errors in the form
        if (count($errors) == 0) {
                $query = "INSERT INTO topics (name, slug) 
                                  VALUES('$topic_name', '$topic_slug')";
                mysqli_query($conn, $query);

                $_SESSION['message'] = "Topic created successfully";
                header('location: topics.php');
                exit(0);
        }
}
/* * * * * * * * * * * * * * * * * * * * *
* - Takes topic id as parameter
* - Fetches the topic from database
* - sets topic fields on form for editing
* * * * * * * * * * * * * * * * * * * * * */
function editTopic($topic_id) {
        global $conn, $topic_name, $isEditingTopic, $topic_id;
        $sql = "SELECT * FROM topics WHERE id=$topic_id LIMIT 1";
        $result = mysqli_query($conn, $sql);
        $topic = mysqli_fetch_assoc($result);
        // set form values ($topic_name) on the form to be updated
        $topic_name = $topic['name'];
}
function updateTopic($request_values) {
        global $conn, $errors, $topic_name, $topic_id;
        $topic_name = esc($request_values['topic_name']);
        $topic_id = esc($request_values['topic_id']);
        // create slug: if topic is "Life Advice", return "life-advice" as slug
        $topic_slug = makeSlug($topic_name);
        // validate form
        if (empty($topic_name)) { 
                array_push($errors, "Topic name required"); 
        }
        // register topic if there are no errors in the form
        if (count($errors) == 0) {
                $query = "UPDATE topics SET name='$topic_name', slug='$topic_slug' WHERE id=$topic_id";
                mysqli_query($conn, $query);

                $_SESSION['message'] = "Topic updated successfully";
                header('location: topics.php');
                exit(0);
        }
}
// delete topic 
function deleteTopic($topic_id) {
        global $conn;
        $sql = "DELETE FROM topics WHERE id=$topic_id";
        if (mysqli_query($conn, $sql)) {
                $_SESSION['message'] = "Topic successfully deleted";
                header("location: topics.php");
                exit(0);
        }
}
function createAdminuser($request_values) {
    global $conn, $errors, $username, $email, $role;

    $username = esc($request_values['username']);
    $email = esc($request_values['email']);
    $password = esc($request_values['password']);
    $passwordConfirmation = esc($request_values['passwordConfirmation']);
    $role = esc($request_values['role']);

    // Validation
    if (empty($username)) { array_push($errors, "Username is required"); }
    if (empty($email)) { array_push($errors, "Email is required"); }
    if (empty($password)) { array_push($errors, "Password is required"); }
    if ($password != $passwordConfirmation) {
        array_push($errors, "Passwords do not match");
    }

    // If no errors, save admin to DB
    if (count($errors) == 0) {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, email, password, role, created_at) 
                  VALUES('$username', '$email', '$password', '$role', now())";
        mysqli_query($conn, $query);

        $_SESSION['message'] = "Admin user created successfully";
        header("location: users.php");
        exit(0);
    }
}
function getAdminuser() {
    global $conn;

    $sql = "SELECT * FROM users WHERE role='Admin' OR role='Author'";
    $result = mysqli_query($conn, $sql);
    $users = mysqli_fetch_all($result, MYSQLI_ASSOC);

    return $users;
}





