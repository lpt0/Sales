<?php
    // Dynamic title and comments
    $title = "Sign In";
    $filename = "sign-in.php";
    $date = "October 1st, 2020";
    $description = "Simple sign in page for Lab 1";

    // Include the global header (starting a session and output buffer)
    include("./includes/header.php"); 

    /* Check if the user submitted the login form (i.e. they are trying to 
        login.) */
    if ($_SERVER["REQUEST_METHOD"] == "POST")
    {
        /** Trim the inputted data. 
         * Then attempt to authenticate the passed credentials using the
         * created database function for it.
         */
        $email = trim($_POST["email"]);
        $password = trim($_POST["password"]);
        $user = user_authenticate($email, $password); 

        /** If there is an ID in the returned data, that means:
         * 1. an associative array was returned (not a boolean)
         * 2. the credentials were valid, and the user is signed in.
         * 
         * No ID means bad credentials, and a login error.
         * 
         * As an aside, logging is done from within db.php - as close to the 
         * login as possible, as to keep the timestamp as close to the actual
         * login as possible.
         */
        if (isset($user["id"]))
        {
            /**
             * Clear the password hash from the returned data (so its not 
             * stored in the session), then set the session variable. 
             * Lastly, set the message and message type.
             */
            unset($user["passwordhash"]);
            
            // for lab 4, check if the account is enabled
            if ($user["active"] === 't') // postgres returns 't' to represent true
            {
                // it's enabled, they can login
                $_SESSION = $user;
                setMessage("You successfully logged in. You last logged in at " . $user["lastlogin"] . ".", "alert-success");
                //video 1c shows "You successfully logged in"

                // append log message and update login time
                $currentTimestamp = getCurrentTimestamp();
                appendLog("Sign in success at " . $currentTimestamp . ". User \"" . $user["email"] . "\" sign in.");
                $error = user_update_login_time($currentTimestamp, $user["id"]);

                // if any errors occurred while updating the login time, log it
                if ($error)
                {
                    appendLog("Error updating login time for " . $userInfo["id"] . ": $error");
                }
            }
            // otherwise, the account is diabled
            else
            {
                // tell the user thats its locked
                setMessage("Your account has been disabled. Please contact a system administrator.", "alert-danger");

                // add to the log
                appendLog($user["email"] . " tried to log in, but their account is locked.");
            }
        }
        else
        {
            setMessage("Incorrect username or password - please try again.", "alert-danger");
        }
    }

    /* If the user is already signed in, or was signed in above,
        redirect them to the dashboard. */
    if (isSignedIn())
    {
        //redirect
        redirect("dashboard.php");
    }
?>

<!-- Login form (from template)-->
<form class="form-signin" method="post" action="<?php echo $_SERVER["PHP_SELF"]; ?>">
    <h1 class="h3 mb-3 font-weight-normal">Please sign in</h1>
    <label for="inputEmail" class="sr-only">Email address</label>
    <input name="email" type="email" id="inputEmail" class="form-control" placeholder="Email address" required autofocus>
    <label for="inputPassword" class="sr-only">Password</label>
    <input name="password" type="password" id="inputPassword" class="form-control" placeholder="Password" required>
    <button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
</form>

<?php
    include("./includes/footer.php"); // And include the global footer
?>