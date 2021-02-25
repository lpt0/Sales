<?php
    $title = "Change Password";
    $filename = "change-password.php";
    $date = "October 26th, 2020";
    $description = "Change your account password";
    include "./includes/header.php";

    // Require the user to sign in to access this page
    requiresSignIn();

    // Get the inputs if the page was POST'ed to
    if ($_SERVER["REQUEST_METHOD"] == "POST")
    {
        $newPassword = $_POST["password"];
        $confirmPassword = $_POST["confirm"];


        // password cannot be empty
        if ($newPassword === "" || $confirmPassword === "")
        {
            setMessage("Password cannot be empty. Please try again.", "alert-danger");
        }
        /* Determine if the password is invalid
            First, if has to be at least MIN_PASSWORD_LENGTH characters in 
            length (defined in constants) */
        elseif (strlen($newPassword) < MIN_PASSWORD_LENGTH)
        {
            setMessage("Password must be at least " . MIN_PASSWORD_LENGTH . " characters. Please try again.", "alert-danger");
        }
        // if the password and confirmation don't match, the user entered it incorrectly
        elseif ($newPassword !== $confirmPassword)
        {
            setMessage("The re-typed password does not match the new password. Please try again.", "alert-danger");
        }
        // the password is valid
        else
        {
            /* call the function from db.php to hash and update the password,
                for the current logged on user */
            $error = user_update_password($_SESSION["id"], $newPassword);
            echo "<script>alert(\"" . $newPassword . "\")</script>";

            // display the error if there was one
            if ($error)
            {
              setMessage($error . ". Please try again", "alert-danger");
            }
            // otherwise send them back to the dashboard
            else
            {
              redirect("dashboard.php");
              setMessage("Your password was updated successfully!", "alert-success");
            }
        }
        
    }

    // Display the form with the password reset
    echo display_form(
        array(
            array(
                "type" => "password",
                "name" => "password",
                "value" => "",
                "label" => "New Password",
                "required" => TRUE,
            ),
            array(
                "type" => "password",
                "name" => "confirm",
                "value" => "",
                "label" => "Re-type password",
                "required" => TRUE
            )
        )
    )
?>

<?php
include "./includes/footer.php";
?>