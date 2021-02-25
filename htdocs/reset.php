<?php
    /*
        Haran
        WEBD3201
        November 23, 2020
    */
    $title = "Request a Password Reset";
    $filename = "reset.php";
    $date = "November 23, 2020";
    $description = "Request a password reset, for when the password is forgotten.";

    include "./includes/header.php";

    // signed in users shouldn't be able to access this
    if (isSignedIn())
    {
        setMessage("That page is only for people who are not signed in.", "alert-danger");
        redirect("dashboard.php");
    }

    // there's only one input; no need to make it sticky

    if ($_SERVER["REQUEST_METHOD"] === "POST")
    {
        // use the email from post vars (if it exists), or leave it empty
        $email = isset($_POST["email"]) ? trim($_POST["email"]) : "";

        // if email is empty, display an error
        if ($email === "")
        {
            setMessage("Email cannot be empty. Please try again.", "alert-danger");
        }
        // otherwise, form is valid
        else
        {
            $user_info = select_user_by_email($email);
            if ($user_info !== FALSE) // user exists
            {
                // append this reset request to the password reset log
                $logname = date("Ymd", time()) . "_reset_log.txt";
                appendText($logname, "$email has requested a password reset at " . getCurrentTimestamp());
                // "send" email
                /*mail(
                    $email, // the "To:" field of the email
                    "Password Reset Link",
                    // use the first name from their account details
                    "Hi " . $user_info["firstname"] . ",\n\nA password reset has been requested for your account.\n"
                    . "Here is a link that will allow you to reset your password.\n\n{{ reset link }}\n\nIf you did not "
                    . "request this reset, you may ignore this email."
                );*/
            }
            // tell them it's been sent, and send them back to the login page
            setMessage("A password reset link has been sent to your email, if it exists.", "alert-success");
            redirect("sign-in.php");
        }
    }

    // display the reset form
    echo display_form(
        array(
            array(
                "type" => "email",
                "name" => "email",
                "label" => "Email",
                "value" => "", // doesn't need to be sticky
                "required" => TRUE // this field is required
            )
        )
    );
?>
<?php
include "./includes/footer.php";
?>