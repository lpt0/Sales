<?php
    /*
        Haran
        WEBD3201
        October 5th, 2020
    */
    $title = "Calls";
    $filename = "calls.php";
    $date = "October 5th, 2020";
    $description = "Create and view clients";

    include "./includes/header.php";

    // Require the user to sign in to access this page
    requiresSignIn();

    // Only admins and salespeople can access this page (the extra signed in check is unnecessary, but better to be safe)
    if (isSignedIn() && get_user_type() != ADMIN && get_user_type() != SALESPERSON)
    {
        // redirect to sign-in with proper message
        setMessage("You are not permitted to view that page.", "alert-danger");
        redirect("sign-in.php"); //pointless since they're already signed in - just redirect them to the dash and give them an error
    }

    /* nothing needs to be sticky, technically - if the user submits a call,
        it is expected that the next call they submit will be for someone else
        since there is only one form input element here, no need to make it sticky */
    $clientId = 0;

    if ($_SERVER["REQUEST_METHOD"] === "POST")
    {
        // no whitespace in my numeric input!
        $clientId = trim($_POST["client_id"]);
        
        // Make sure that the value submitted for client id is an integer greater than 0 (valid)
        if (filter_var($clientId, FILTER_VALIDATE_INT) != NULL && $clientId > 0)
        {
            // Add the entry; assumes it uses the current timestamp
            $error = create_call($clientId);

            // if an error occurred, the error string will not be empty; on the other hand, NOT'ing error will check if its empty
            if ($error) 
            {
                // is it that the client id was not found in clients?
                if (preg_match("/is not present in/", $error))
                {
                    // display a proper error
                    setMessage("An error occurred. Client ID " . $clientId . " does not exist. Please try again.", "alert-danger");

                    // clear the client ID so its not sticky
                    $clientId = 0;
                }
                else // otherwise display the whole error
                    setMessage("An error occurred. " . $error . " Please try again.", "alert-danger");
            }
            else // it inserted correctly
            {
                // success message
                setMessage("Added the call to the database.", "alert-success");

                // clear the client id so it isn't sticky
                $clientId = 0;
            }
        }
        else
        {
            // update the message
            setMessage("Please select a client from the list and try again.", "alert-danger");
        }
    }

    // Define an empty form, which will need to be populated with the clients
    $form_to_show = array();

    $max_clients = count_rows_in_clients(); // return the MAXIMUM number of clients possible
    $clients = array(); // blank variable for later
    /* get a list of clients and create an array to hold the selector options 
        (similar to clients.php, if the user is an admin except this is for everyone) */
    // if the user is an admin, return all clients
    if (get_user_type() == ADMIN)
    {
    $clients = select_all_clients(1, $per_page = $max_clients); // select ALL clients in the table
    }
    // otherwise, only return the clients for this salesperson
    elseif (get_user_type() == SALESPERSON)
    {
        /* select for the maximum number of clients in the table
            (assume that session data was not lost, and session ID is set) */
        $clients = select_clients_for_salesperson($_SESSION["id"], 1, $per_page = $max_clients);
    }
    $client_options = array();

    /* only include the placeholder value if the client id is not stickied 
        (=== 0; clients.php has reasoning for this) */
    if ($clientId === 0)
    {
        array_push($client_options, array(
            "value" => "",
            "content" => "Client Name"
        ));
    }

    // only go through the clients if the salesperson HAS clients
    if (!empty($clients))
    {
        // loop through it
        for ($idx = 0; $idx < count($clients); $idx++)
        {
            $row = $clients[$idx];

            // create an array for the option
            $data = array("value" => $row["id"], "content" => htmlspecialchars($row["firstname"] . " " . $row["lastname"]));

            /* to keep the input sticky, check if the ID is the 
                    one that was already selected */
            if ($row["id"] == $clientId)
                // prepend the array with this row
                array_unshift($client_options, $data);
            else
                // otherwise, add it to the end of the array
                array_push($client_options, $data);
        }
    }

    // display the form with only one element - a selector for the clients
    echo display_form(
        array(
            array(
                "type" => "select",
                "name" => "client_id",
                "label" => "Client Name",
                "required" => TRUE, // this field is required
                "options" => $client_options
            )
        )
    );
?>
</div>
<h2>Calls</h2>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap pb-2 mb-3 border-bottom">
<!-- Table of salespeople -->
<?php 
    // page
    $page = 1;

    // handle pagination; if `page` is present in the query string, use that
    if (isset($_GET["page"]) && is_numeric($_GET["page"]))
    {
        $page = $_GET["page"];
    }

    // variables to hold call data - populated based on user type
    $calls = array();
    $call_count = 0;

    // admins can view all calls
    if (get_user_type() == ADMIN)
    {
        // they can view all clients
        $calls = select_all_calls($page, ROWS_PER_PAGE);
        $call_count = count_rows_in_calls();
    }
    // salespeople can only view their calls (assume session is not corrupt, and that ID is set)
    elseif (get_user_type() == SALESPERSON)
    {
        // they can only view their clients
        $calls = select_calls_for_salesperson($_SESSION["id"], $page, ROWS_PER_PAGE);
        $call_count = count_rows_in_calls_for_salesperson($_SESSION["id"]);
    }

    // define the columns for the table
    $columns = array(
        "id" => "ID",
        "clientname" => "Client Name",
        "created" => "Created"
    );

    // admins can also view the client's associated salesperson
    if (get_user_type() == ADMIN)
    {
        $columns["salespersonname"] = "Salesperson Name";
    }

    // output the table
    echo display_table(
        $columns,
        $calls,
        $call_count,
        $page
    );
?>

<?php
    include "./includes/footer.php";
?>