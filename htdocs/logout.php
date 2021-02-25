<?php
    /*
        Haran
        WEBD3201
        September 15th, 2020
    */
    $title = "Logout";
    $filename = "logout.php";
    $date = "October 1st, 2020";
    $description = "A simple log out page, that will clear a session; for Lab 1";
    /** Include the header file so the session gets loaded
     * (see documentation: https://www.php.net/manual/en/function.session-start.php)
     * helped a lot - session needs to be read from before it can be destroyed, per docs
     */
    include("./includes/header.php");
    
    // Only log the user out if there *is a user to begin with
    if (session_id() != "" && isSignedIn()) {
        /** Store the email, since it needs to be logged.
         * Then, reset and destroy the session; afterwards, start a new one
         * so a message can be set. 
         * 
         * As a note to self, once a session is destroyed, no session vars 
         * can be set until a new one is started; that's why one needs to be
         * started here.
         */
        $email = $_SESSION["email"];
        
        //note: https://www.php.net/manual/en/function.session-unset.php
        // says not to use session_unset
        session_reset();
        session_destroy();
        session_start();

        setMessage("You have been succesfully signed out.", "alert-success");
        appendLog("User \"" . $email . "\" has logged out at " . getCurrentTimestamp() .".");
    }
    // Send the user back to the login page
    redirect("sign-in.php");

    // and the footer to make it valid HTML (and flush the output buffer)
    include("./includes/footer.php")
?>