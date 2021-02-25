<?php
    /*
        Haran
        WEBD3201
        October 5th, 2020
    */
    $title = "Clients";
    $filename = "clients.php";
    $date = "October 5th, 2020";
    $description = "Create and view clients";

    include "./includes/header.php";

    // Require the user to sign in to access this page
    requiresSignIn();

    // Only admins and salespeople can access this page (the extra signed in check is unnecessary, but better to be safe)
    if (isSignedIn() && get_user_type() != ADMIN && get_user_type() != SALESPERSON)
    {
        // kick the user back to the dashboard with an appropriate message
        setMessage("You are not permitted to view that page.", "alert-danger");
        redirect("dashboard.php");
        /* reason for dashboard instead of sign in, is because they're already signed in
            - sending the user to the sign in page will just redirect them to the dashboard */
    }

    // Initialize sticky vars (for clients, all inputs are sticky)
    $firstName = "";
    $lastName = "";
    $email = "";
    $phoneNumber = "";
    $extension = NULL; // this is optional

    // used to store the path to the uploaded file, after moving
    $logo_path = "";

    // If the logged in user is a salesperson, just use their id; otherwise, 0
    $salespersonId = (get_user_type() == SALESPERSON ? $_SESSION["id"] : 0);

    #region Form validation & database insertion
    if ($_SERVER["REQUEST_METHOD"] == "POST")
    {
        // Populate the variables with the POST'ed values, so they are sticky (and show up again in the form)
        $firstName = trim($_POST["first_name"]);
        $lastName = trim($_POST["last_name"]);
        $email = trim($_POST["email"]);
        $phoneNumber = trim($_POST["phone_number"]);
        $extension = trim($_POST["extension"]);
        
        // if the extension string is empty, just make it null
        if ($extension === "") $extension = NULL;
        
        /* Only allow the salesperson id to be set if its 0 (meaning, 
        not set and not logged in as a salesperson) */
        if ($salespersonId == 0) $salespersonId = trim($_POST["salesperson_id"]);

        /* except for extension, none of the fields should be blank
            for the character fields, which have a max length in the database,
            also check if the input does not have more characters than it can take,
            if the original string is not empty */
        if (!checkIfEmpty($firstName, "First name")) checkLength($firstName, "First name", 255);
        if (!checkIfEmpty($lastName, "Last name")) checkLength($lastName, "Last name", 255);
        if (!checkIfEmpty($email, "Email")) checkLength($email, "Email", 320);
        if (!checkIfEmpty($phoneNumber, "Phone number")) checkLength($phoneNumber, "Phone number", 10);

        // salesperson cannot be left as 0 (unset)
        if ($salespersonId == 0)
            appendErrorMessage("Please select a salesperson from the list.", "alert-danger");

        
        /* phone number must have ten digits (and that's ten *digits*, not any ol' characters)
            since its ten characters exactly, all digits, that's a simple regular expression 
            only run this check if it's not blank */
        if ($phoneNumber !== "" && !preg_match("/[0-9]{10}/", $phoneNumber))
        {
            appendErrorMessage("Phone number must have ten digits (numbers). A valid example is '1234567890'.");

            // clear the invalid phone number
            $phoneNumber = "";
        }

        // only validate the extension if there was one entered
        if ($extension !== NULL)
        {
            /* check if the entered extension is parseable as an integer
                if its not, there's a problem
                filter_var is used instead of is_numeric, because it NEEDS to be an integer 
                use strict checking (triple equal) since NULL can be equal to 0, or "" */
            if ((gettype(filter_var($extension, FILTER_VALIDATE_INT)) !== "integer"))
            {
                // append an error message saying that it must be a number
                appendErrorMessage("Extension must be a positive, whole number.");
                
                // clear the invalid extension number
                $extension = "";
            }
            // again, double check to make sure it's not a negative number
            elseif ($extension <= -1)
            {
                // append an error message saying that it must be a number
                appendErrorMessage("Extension must be a positive, whole number.");
                
                // clear the invalid extension number
                $extension = "";
            }
            // otherwise, if it is an integer
            elseif (gettype(filter_var($extension, FILTER_VALIDATE_INT)) === "integer")
            {
                // cast it to an integer, to be safe
                $extension = intval($extension);
            }
        }

        // if the email is not blank, validate the email address using filter_var
        if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            // again, *append* an error message, saying that it needs to be a valid email
            appendErrorMessage("Email is invalid.");

            // clear the invalid email
            $email = "";
        }

        // logo needs to be set
        if (!isset($_FILES["logo_path"]))
        {
            appendErrorMessage("You must upload a logo.");
        }
        // check if there were any errors while uploading the file
        elseif ($_FILES["logo_path"]["error"] !== 0)
        {
            /* switch until the value is found, and display the right error message
                I *could* have used an array, but for some of these I want to write to
                the log file.
                Also, when I was writing this, the first thing that came to mind (somehow)
                was using a switch instead of an array or dictionary. */
            switch ($_FILES["logo_path"]["error"])
            {
                // ERR_INI_SIZE; file size exceeds upload_max_filesize setting in php.ini
                case 1:
                    appendErrorMessage(
                        "File is too big to be handled by the server. "
                        . "Please pick a file smaller than " . ini_get("upload_max_filesize"). "."
                    );
                    break;
                // ERR_FORM_SIZE; file size > MAX_FILE_SIZE of form
                case 2:
                    // error message with user-friendly megabytes and byte value, just to be safe
                    appendErrorMessage(
                        "The uploaded file is too big. "
                        . "Please pick a file smaller than " 
                        . MAXIMUM_FILE_SIZE_MB . " (" . MAXIMUM_FILE_SIZE . " bytes)." 
                    );
                    break;  
                // ERR_PARTIAL; partial file upload
                case 3:
                    appendErrorMessage("An error ocurred while uploading the file. Please ensure that your network connection is stable, and try again.");
                    break;
                // ERR_NO_FILE - the file input element exists, but nothing was submitted
                case 4:
                    appendErrorMessage("You must upload a logo.");
                    break;
                // ERR_NO_TMP_DIR - temp directory does not exist/is not set
                case 6:
                    // This is a server-side error; write it to the log
                    appendLog("Temp directory is missing!");
                    // don't break - fall through
                // ERR_CANT_WRITE - unable to write file to disk
                case 7:
                    // Also a server-side error; write to log
                    appendLog("Failed to write file to disk!");
                    // the following error message applies to case 6 and 7
                    appendErrorMessage("A server error occurred. Please try again later.");
                    break;
                // ERR_EXTENSION - some extension not allowed by PHP
                case 8:
                    appendErrorMessage("That file extension is not allowed. Please select a file with a different extension.");
                    break;
                default:
                    // unknown error type - append it to log
                    appendLog("Unknown error " . $_FILES["logo_path"]["error"]);
                    appendErrorMessage("An unknown error ocurred. Please try again later.");
            }
        }
        /* check if the file type is correct
            this can only be done if there are no errors; I found that,
            if there were errors, mimetype would be empty and the error message wouldn't make sense.
            So, the reason the error checks happen before the mimetype check, is to have the reason
            make more sense.           
            see list of mime types: https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/MIME_types */
        elseif ($_FILES["logo_path"]["type"] !== "image/png" 
                && $_FILES["logo_path"]["type"] !== "image/jpeg")
        {
            // uploaded file's mime-type was not an image
            appendErrorMessage(
                "The uploaded file was not an image. Please use a file with one of the following extensions:\n" . 
                ".jpeg\n.jpg\n.jpe\n.png"
            );
        }
        /* file type is correct; now check if the uploaded file size meets
            the limit set out in constants.php
            checking this after the file type, because there is no point
            in checking it if the file type isn't even correct
            better to check this, because a user can change the hidden input
            field containing the MAX_FILE_SIZE and increase that limit; so 
            the error check earlier would not have caught that */
        elseif ($_FILES["logo_path"]["size"] > MAXIMUM_FILE_SIZE)
        {
            /* append an error telling the user of the max file size,
                user-friendly version (in MB) and in bytes, because OSes 
                or perhaps even filesystems, could report the file size
                differently */
            appendErrorMessage(
                "The uploaded file is too big. "
                . "Please select a file smaller than " . MAXIMUM_FILE_SIZE_MB 
                . " (" . MAXIMUM_FILE_SIZE . " bytes)."
            );
        }
        // file is OK; move the file and properly set the logo_path
        else
        {
            $file = $_FILES["logo_path"];
            
            $tmp_name = $file["tmp_name"]; // file path when uploaded to the server (in a temp directory)
            $name = basename($file["name"]); // original file name, as it was uploaded from the user's computer
            $logo_path = "./upload/$name"; // new file path (current directory -> upload/)

            // if the upload directory doesn't exist, make it
            if (!is_dir("./upload"))
            {
                mkdir("./upload");
            }

            /* move the file using move_uploaded_file; if it returns false, there was an error
               could be permission issues, could be something else */
            if (!move_uploaded_file($tmp_name, $logo_path))
            {
                appendErrorMessage("Unable to upload file.");
                
                // write the error to log
                appendLog("Unable to move `$tmp_name` to `$logo_path`; there may issues with the permissions.");

                // logo path is no longer valid
                $logo_path = "";
            }

        }

        /* if none of the fields are empty, and the extension is either NULL or a valid integer,
            and the salesperson ID is set, it's time to try and insert the record into the database */
        if ($firstName !== "" && $lastName !== "" 
            /* extension can be empty (NULL) or it needs to be a positive (or 0) integer 
                using gettype to make sure its an integer 
                if an extension was entered, but was invalid, it would be an empty
                string instead of NULL; strict checking is used to be able to 
                tell the difference */
            && ((gettype($extension) === "integer" && $extension >= 0) || $extension === NULL) 
            && $phoneNumber !== "" && $salespersonId != 0
            && $email !== "" && $logo_path !== "")
        {
            // try to add the client (if the returned value is not an empty string, it's an error)
            $error = create_client($firstName, $lastName, $email, $phoneNumber, $salespersonId, $logo_path, $extension);
            
            if ($error)
            {
                // check if its a duplicate key error
                if (preg_match("/duplicate.+email/", $error))
                {
                    // output a specific error
                    setMessage("There is already an account with this email, please use a different one.", "alert-danger");
                    
                    // clear the email variable
                    $email = "";
                }
                // otherwise, is it that the salesperson id was not found?
                elseif (preg_match("/is not present in/", $error))
                {
                    // display a relevant error
                    setMessage("An error occurred. Salesperson ID " . $salespersonId . " does not exist. Please try again.", "alert-danger");

                    // clear the invalid salesperson ID
                    $salespersonId = 0;
                }
                else // otherwise just output what the error was, verbatim
                    setMessage("An error occurred - " . $error . "; please try again.", "alert-danger");
            }
            else 
            {
                setMessage("Added " . $firstName . " " . $lastName . " as a client!", "alert-success");

                // clear variables
                $firstName = "";
                $lastName = "";
                $email = "";
                $phoneNumber = "";
                $extension = "";
                $salespersonId = 0;
                $logo_path = "";
            }
        }
        else
        {
            appendErrorMessage("Please fix the above error(s) and try again.");
        }
    }
    #endregion

    /* default array - this is what will show if the user is *not* an admin
        all fields are required, except for the extension, which is optional */
    $form_to_show = array(
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
            "type" => "text",
            "name" => "phone_number",
            "value" => $phoneNumber,
            "label" => "Phone number",
            "required" => TRUE
        ),
        // lab 3: file upload
        array(
            "type" => "file",
            "name" => "logo_path",
            // no value needed
            "label" => "Logo",
            "required" => TRUE
        ),
        array(
            "type" => "number",
            "name" => "extension",
            "value" => $extension,
            "label" => "Extension (optional)"
        )
    );

    #region Salesperson selector (Admin only)
    // need to show an input for salesperson id if the user is an admin
    if (get_user_type() == ADMIN)
    {
        // will be used to populate a list of options with each salesperson
        $salespeople = select_all_salespeople();

        // create an array to hold the salespeople
        $salespeople_options = array();

        /* if the salesperson id is not stickied (meaning that it's 0), 
            add the first (default) element.
            otherwise, don't include this placeholder element; 
            the first selected element will be the stickied one */
        if ($salespersonId === 0)
        {
            // first array is a placeholder value
            array_push($salespeople_options, array(
                "value" => "", // if the value is null, it knows that it's a placeholder value
                "content" => "Salesperson" // content is the text for the option
            ));
        }

        // loop through and add options for each salesperson
        for ($idx = 0; $idx < count($salespeople); $idx++)
        {
            $row = $salespeople[$idx];

            /* create an array for the salespeople data
                need to escape html characters, so there's no funny business xss here */
            $data = array("value" => $row["id"], "content" => htmlspecialchars($row["firstname"] . " " . $row["lastname"]));

            /* to keep the input sticky, check if the ID is the 
                one that was already selected */
            if ($row["id"] == $salespersonId)
                // prepend the array with this row
                array_unshift($salespeople_options, $data);
            else
                // otherwise, add it to the end of the array
                array_push($salespeople_options, $data);
        }

        // now, create an array for the select element and add it to the form
        array_push(
            $form_to_show,
            /* add the array for the select element to the array, 
                with the options that were created above 
                for the admin, this field is required */
            array(
                "type" => "select",
                "name" => "salesperson_id",
                "label" => "Salesperson",
                "options" => $salespeople_options,
                "required" => TRUE
            )
        );
    }
    #endregion

    // Display the form
    echo display_form($form_to_show);
?>
</div>
<h2>Clients</h2>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap pb-2 mb-3 border-bottom">
<!-- Table of salespeople -->
<?php 
    #region Table display
    // if the page number is provided in the query string, use that instead of 1
    $page = 1;
    if (isset($_GET["page"]) && is_numeric($_GET["page"]))
    {
        $page = $_GET["page"];
    }

    // empty var to hold rows from the table
    $clients = array();
    $client_count = 0; // and number of clients
    // if the user is an admin...
    if (get_user_type() == ADMIN)
    {
        // they can view all clients
        $clients = select_all_clients($page, ROWS_PER_PAGE);
        $client_count = count_rows_in_clients();
    }
    // otherwise, if it is a salesperson with an id
    elseif (get_user_type() == SALESPERSON)
    {
        /* they can only view their clients
            (assume session data is not corrupt or anything, and that the ID 
            is set) */
        $clients = select_clients_for_salesperson($_SESSION["id"], $page, ROWS_PER_PAGE);
        $client_count = count_rows_in_clients_for_salesperson($_SESSION["id"]);
    }

    // specify the columns
    $columns = array(
        "id" => "ID",
        "email" => "Email",
        "firstname" => "First Name",
        "lastname" => "Last Name",
        "phonenumber" => "Phone Number",
        "extension" => "Extension",
        "logopath" => "Logo"
    );

    // admins can also see the salesperson name
    if (get_user_type() == ADMIN)
        // add the salespersonname column
        $columns["salespersonname"] = "Salesperson Name";

    // build and display the table
    echo display_table(
        $columns,
        $clients, // from right above this call - should be already populated with the needed data
        $client_count,
        $page
    );
    #endregion
?>

<?php
    include "./includes/footer.php";
?>