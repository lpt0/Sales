<?php
    /*
        Haran
        WEBD3201
        October 5th, 2020
    */
    $title = "Salespeople";
    $filename = "salespeople.php";
    $date = "October 5th, 2020";
    $description = "Create and view salespeople";

    include "./includes/header.php";

    // Require the user to sign in to access this page
    requiresSignIn();

    /* Need to be an admin to access this
        The reason I'm using a different error mesasage is to not allude to what this page is;
        that could be a security issue in itself, as it may be more targeted by someone *unfamiliar* with
        the software (like someone running a fuzzer) 
        Extra signed in check is unneccessary, but including it to be safe */
    if (isSignedIn() && get_user_type() != ADMIN)
    {
        // kick the user back to the dashboard with an appropriate message
        setMessage("You are not permitted to view that page.", "alert-danger");
        redirect("dashboard.php");
        /* reason for dashboard instead of sign in, is because they're already signed in
            - sending the user to the sign in page will just redirect them to the dashboard */
    }

    // Initialize variables that may need to be sticky
    $firstName = "";
    $lastName = "";
    $email = "";
    $extension = "";
    
    // form was submitted
    if ($_SERVER["REQUEST_METHOD"] == "POST")
    {
        // check if the "active" key is set in the post vars
        #region Account active state change
        if (isset($_POST["active"]))
        {
            // iterate through that array
            foreach ($_POST["active"] as $user_id => $state)
            {
                /* state is either Active (TRUE) or Inactive (FALSE)
                    check if the new desired state is active or not.
                    need to use strings since Postgres does not understand 
                    PHP's TRUE/FALSE */
                $is_active = $state === "Active" ? "true" : "false";

                // make sure that the user id being changed is a salesperson (since this could be faked)
                $user_data = user_select($user_id);

                // make sure the user exists (false was not returned)
                if ($user_data != FALSE)
                {
                    // update their account state
                    $error = user_update_active($user_id, $is_active);

                    // if there was an error, display it and log it
                    if ($error !== "")
                    {
                        appendErrorMessage($error);
                        appendLog("Error setting $user_id's state to $state at " . getCurrentTimestamp() . ": $error");
                    }
                    // otherwise, say that it was a success
                    else
                    {
                        setMessage("Successfully set user to be $state!", "alert-success");
                        // log the success
                        appendLog("Successfully set $user_id's state to $state at " . getCurrentTimestamp());

                    }
                }
                // false was returned, display an error
                else
                {
                    appendErrorMessage("Could not change $user_id to $state - user does not exist.");
                    // add to log
                    appendLog("$user_id could not be changed to $state - user does not exist.");
                }
            }
        }
        #endregion

        // otherwise, the POST request was made to add a salesperson
        #region Add salesperson validation
        else
        {
            // Populate the variables with the POST'ed values, so they are sticky (and show up again in the form)
            // check if they are set before accessing them, just in case; if they aren't set, leave them blank
            $firstName = isset($_POST["first_name"]) ? trim($_POST["first_name"]) : "";
            $lastName = isset($_POST["last_name"]) ? ($_POST["last_name"]) : "";
            $email = isset($_POST["email"]) ? trim($_POST["email"]) : "";
            $extension = isset($_POST["extension"]) ? trim($_POST["extension"]) : "";
            $password = isset($_POST["password"]) ? $_POST["password"] : ""; // don't trim this; let the user have spaces at the beginning and end
            
            // none of them should be empty - use a function from functions.php to validate
            /* for the first and last name, and email, if the vars are not empty,
                check if they are within the character limits */
            if (!checkIfEmpty($firstName, "First name")) checkLength($firstName, "First name", 255);
            if (!checkIfEmpty($lastName, "Last name")) checkLength($lastName, "Last name", 255);
            if (!checkIfEmpty($email, "Email")) checkLength($email, "Email", 320);
            checkIfEmpty($extension, "Extension");
            checkIfEmpty($password, "Password");
            
            /* only check for this error if the string isn't empty
                is the extension not an integer? */
            if ($extension !== "" && gettype(filter_var($extension, FILTER_VALIDATE_INT)) !== "integer")
            {
                // append an error message saying that it must be a number
                appendErrorMessage("Extension must be a positive, whole number.");
                
                // clear the invalid extension number
                $extension = "";
            }
            // double check to make sure it's not a negative number
            elseif ($extension <= -1)
            {
                // append an error message saying that it must be a number
                appendErrorMessage("Extension must be a positive, whole number.");
                
                // clear the invalid extension number
                $extension = "";
            }
            // if it's a valid integer
            elseif (gettype(filter_var($extension, FILTER_VALIDATE_INT)) === "integer")
            {
                // cast it to an integer, to be safe
                $extension = intval($extension);
            }

            // validate the email address using filter_var (if its not empty)
            if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL))
            {
                // again, *append* an error message, saying that it needs to be a valid email
                appendErrorMessage("Email is invalid.");
                
                // clear invalid email
                $email = "";
            }

            /* make sure that:
                - the first name is not an empty string
                - the last name is not an empty string
                - the extension is an integer
                - the extension is greater than, or equal to, 0
                - the password is not an empty string
                - the email is not an empty string */
            if ($firstName !== "" && $lastName !== "" 
                // make SURE it's an integer
                && gettype($extension) === "integer" && $extension >= 0
                && $password !== ""
                && $email !== "")
            {
                // try to add - if it comes back with a non-empty string, there's an error
                $error = create_salesperson($firstName, $lastName, $email, $extension, $password);
                
                if ($error) // an error occurred?
                {
                    // check if its a duplicate key error
                    if (preg_match("/duplicate/", $error))
                    {
                        setMessage("There is already an account with this email, please use a different one.", "alert-danger");
                        
                        // clear the invalid email
                        $email = "";
                    }
                    else // otherwise just output what the error was, verbatim
                        setMessage("An error occurred - " . $error . "; please try again.", "alert-danger");
                }
                else // if $result is empty, that means record inserted successfully
                {
                    // success message
                    setMessage("Succesfully added " . $firstName . " " . $lastName . " to the database!", "alert-success");
                    // clear variables
                    $firstName = "";
                    $lastName = "";
                    $email = "";
                    $extension = "";
                }
            }
            else
            {
                appendErrorMessage("Please fix the above error(s) and try again.");
            }
        }
        #endregion
    }

    #region Add salesperson form
    // Display the form, with all fields being required
    echo display_form(
        array(
            array(
                "type" => "text",
                "name" => "first_name",
                "value" => $firstName,
                "label" => "First Name",
                "required" => TRUE
            ),
            array(
                "type" => "text",
                "name" => "last_name",
                "value" => $lastName,
                "label" => "Last Name",
                "required" => TRUE
            ),
            array(
                "type" => "email",
                "name" => "email",
                "value" => $email,
                "label" => "Email",
                "required" => TRUE
            ),
            array(
                "type" => "password",
                "name" => "password",
                "value" => "", // password should never be sticky
                "label" => "Password",
                "required" => TRUE
            ),
            array(
                "type" => "number",
                "name" => "extension",
                "value" => $extension,
                "label" => "Extension",
                "required" => TRUE
            )
        )
    );
    #endregion
?>
</div>
<h2>Salespeople</h2>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap pb-2 mb-3 border-bottom">
<!-- Table of salespeople -->
<?php
    // this one is easier since only admins may view salespeople
    $page = 1;
    
    if (isset($_GET["page"]) && is_numeric($_GET["page"]))
    {
        $page = $_GET["page"];
    }
    $salespeople = select_all_salespeople($page, ROWS_PER_PAGE);
    $salespeople_count = count_salespeople();
    // output the table
    echo display_table(
        array(
            "id" => "ID",
            "email" => "Email",
            "firstname" => "First name",
            "lastname" => "Last name",
            "created" => "Created at",
            "lastlogin" => "Last login",
            "phoneextension" => "Extension",
            "active" => "Is Active?"
        ),
        $salespeople,
        $salespeople_count,
        $page,
        ROWS_PER_PAGE,
        // array for displaying the radio button
        array(
            "active" => array(
                TRUE => "Active",
                FALSE => "Inactive",
                /* the ID should be used as the radio button group name, 
                    instead of the row index number (within the table) */
                "index" => "id"
            )
        )
    );
?>

<?php
    include "./includes/footer.php";
?>