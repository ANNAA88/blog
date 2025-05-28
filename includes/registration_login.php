/**
 * User Authentication Script (Register & Login)
 *
 * Handles user registration and login operations, including form validation,
 * input sanitization, session management, and conditional redirection.
 *
 * Sections:
 * 1. Variable Declaration
 * 2. User Registration Logic
 * 3. User Login Logic
 * 4. Utility Functions:
 *    - esc(): Escapes user input for safe SQL use.
 *    - getUserById(): Retrieves user details by user ID.
 *
 * Global Variables Used:
 * - $conn: MySQLi connection object.
 * - $_POST: To handle form data.
 * - $_SESSION: To store session-related user state and messages.
 *
 * ------------------------------------------------------------
 * User Registration Flow:
 * - Triggered when `$_POST['reg_user']` is set.
 * - Validates input fields: username, email, password, password match.
 * - Checks for existing users by username or email.
 * - If no errors:
 *     - Encrypts password using `md5()`.
 *     - Inserts user into database.
 *     - Logs user in by setting session.
 *     - Redirects based on role (Admin/Author => admin panel; otherwise, public site).
 *
 * Security Notes:
 * - Password is hashed with `md5()`, which is outdated and insecure.
 *   Use `password_hash()` and `password_verify()` instead for better security.
 *
 * ------------------------------------------------------------
 * User Login Flow:
 * - Triggered when `$_POST['login_btn']` is set.
 * - Validates presence of username and password.
 * - Hashes password with `md5()` and checks for match in DB.
 * - If credentials match:
 *     - Sets session for the user.
 *     - Redirects based on role.
 * - Otherwise, an error is added: "Wrong credentials".
 *
 * ------------------------------------------------------------
 * Function: esc(string $value)
 * - Escapes form input using `mysqli_real_escape_string` and trims spaces.
 * - Prevents SQL injection.
 *
 * Function: getUserById(int $id)
 * - Fetches a single user from the `users` table by ID.
 * - Returns an associative array containing user details.
 *
 * Recommendations:
 * - Replace `md5()` with `password_hash()` and `password_verify()` for password security.
 * - Add rate-limiting or CAPTCHA to prevent brute-force attacks.
 * - Consider using prepared statements for additional protection.
 */
<?php 
        // variable declaration
        $username = "";
        $email    = "";
        $errors = array(); 

        // REGISTER USER
        if (isset($_POST['reg_user'])) {
                // receive all input values from the form
                $username = esc($_POST['username']);
                $email = esc($_POST['email']);
                $password_1 = esc($_POST['password_1']);
                $password_2 = esc($_POST['password_2']);

                // form validation: ensure that the form is correctly filled
                if (empty($username)) {  array_push($errors, "Uhmm...We gonna need your username"); }
                if (empty($email)) { array_push($errors, "Oops.. Email is missing"); }
                if (empty($password_1)) { array_push($errors, "uh-oh you forgot the password"); }
                if ($password_1 != $password_2) { array_push($errors, "The two passwords do not match");}

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
                        $password = md5($password_1);//encrypt the password before saving in the database
                        $query = "INSERT INTO users (username, email, password, created_at, updated_at) 
                                          VALUES('$username', '$email', '$password', now(), now())";
                        mysqli_query($conn, $query);

                        // get id of created user
                        $reg_user_id = mysqli_insert_id($conn); 

                        // put logged in user into session array
                        $_SESSION['user'] = getUserById($reg_user_id);

                        // if user is admin, redirect to admin area
                        if ( in_array($_SESSION['user']['role'], ["Admin", "Author"])) {
                                $_SESSION['message'] = "You are now logged in";
                                // redirect to admin area
                                header('location: ' . BASE_URL . 'admin/dashboard.php');
                                exit(0);
                        } else {
                                $_SESSION['message'] = "You are now logged in";
                                // redirect to public area
                                header('location: index.php');                          
                                exit(0);
                        }
                }
        }

        // LOG USER IN
        if (isset($_POST['login_btn'])) {
                $username = esc($_POST['username']);
                $password = esc($_POST['password']);

                if (empty($username)) { array_push($errors, "Username required"); }
                if (empty($password)) { array_push($errors, "Password required"); }
                if (empty($errors)) {
                        $password = md5($password); // encrypt password
                        $sql = "SELECT * FROM users WHERE username='$username' and password='$password' LIMIT 1";

                        $result = mysqli_query($conn, $sql);
                        if (mysqli_num_rows($result) > 0) {
                                // get id of created user
                                $reg_user_id = mysqli_fetch_assoc($result)['id']; 

                                // put logged in user into session array
                                $_SESSION['user'] = getUserById($reg_user_id); 

                                // if user is admin, redirect to admin area
                                if ( in_array($_SESSION['user']['role'], ["Admin", "Author"])) {
                                        $_SESSION['message'] = "You are now logged in";
                                        // redirect to admin area
                                        header('location: ' . BASE_URL . '/admin/dashboard.php');
                                        exit(0);
                                } else {
                                        $_SESSION['message'] = "You are now logged in";
                                        // redirect to public area
                                        header('location: index.php');                          
                                        exit(0);
                                }
                        } else {
                                array_push($errors, 'Wrong credentials');
                        }
                }
        }
        // escape value from form
        function esc(String $value)
        {       
                // bring the global db connect object into function
                global $conn;

                $val = trim($value); // remove empty space sorrounding string
                $val = mysqli_real_escape_string($conn, $value);

                return $val;
        }
        // Get user info from user id
        function getUserById($id)
        {
                global $conn;
                $sql = "SELECT * FROM users WHERE id=$id LIMIT 1";

                $result = mysqli_query($conn, $sql);
                $user = mysqli_fetch_assoc($result);

                // returns user in an array format: 
                // ['id'=>1 'username' => 'Awa', 'email'=>'a@a.com', 'password'=> 'mypass']
                return $user; 
        }
?>