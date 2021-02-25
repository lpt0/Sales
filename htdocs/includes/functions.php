<?php
    /**
     * Redirect a user to a different page.
     * @param url The URL to redirect the user to
     */
    function redirect($url)
    {
        // Send them to a different location and flush the output buffer
        header("Location: " . $url);
        ob_flush();
    }

    #region Banner messaging functions
    /** 
     * Set the banner variable, and the type (Bootstrap class) for 
     * the banner.
     * @param message The banner message
     * @param type The Bootstrap alert type 
     *             (see: https://getbootstrap.com/docs/4.0/components/alerts/)
    */
    function setMessage($message, $type)
    {
        $_SESSION["message"] = $message;
        $_SESSION["message-type"] = (isset($type) ? $type : "alert-primary");
    }

    /** 
     * Set the message type.
     * Allows for the current message type to be overriden.
     * This will check whether there is a message in the first place;
     * if not, this will not work.
     * 
     * @param type The Bootstrap alert type.
     */
    function setMessageType($type)
    {
        if (isset($_SESSION["message"]))
            // have a fallback alert type, just in case
            $_SESSION["message-type"] = (isset($type) ? $type : "alert-primary");
    }

    /** Get the banner message 
     * 
     * @return string The contents of the session message
    */
    function getMessage()
    {
        return $_SESSION["message"];
    }

    /** Check if a banner is set 
     * 
     * @return boolean True, if there is a banner message; false otherwise.
    */
    function isMessage()
    {
        return isset($_SESSION["message"]);
    }

    /** Clear the banner */
    function removeMessage()
    {
        unset($_SESSION["message"]);
    }

    /* Same functions as above, but for the message type. */
    /** Get the type of message for the banner. */
    function getMessageType()
    {
        return $_SESSION["message-type"];
    }

    /** Check if there is a message type.
     * 
     * There are some cases where a message is not set, but this will be.
     * For example, if the message is flashed but the message type has not 
     * been flashed yet - in this case, there won't be a message but there 
     * will be a message type.
     * 
     * @return boolean True, if there is a message type; false otherwise.
     */
    function isMessageType()
    {
        return isset($_SESSION["message-type"]);
    }

    /** Remove the message type */
    function removeMessageType()
    {
        unset($_SESSION["message-type"]);
    }

    /** Flash the message
     * 
     * This will check whether there is a message.
     * If there is, it will get it, clear the contents of that session variable, and
     * return the message.
     * 
     * @return string The banner message, if one exists.
     */
    function flashMessage()
    {
        $message = "";
        if (isMessage())
        {
            $message = getMessage();
            removeMessage();
        }
        return $message;
    }

    /** Flash the message type.
     * 
     * Same as flashMessage, but for the message/banner type.
     * Check if there is a message type; if there is, store it in a local var
     * and clear the session variable, then return the copied message type.
     * 
     * @return string The message type, if there is one.
     */
    function flashMessageType()
    {
        $messageType = "";
        if (isMessageType())
        {
            $messageType = getMessageType();
            removeMessageType();
        }
        return $messageType;
    }
    #endregion

    /**
     * Specific functionality for the W3 Nu validator.
     */
    function check_nu()
    {
        if ($_SERVER["HTTP_USER_AGENT"] == "Validator.nu/LV http://validator.w3.org/services")
        {
            return FALSE;
            /*$_SESSION["id"] = 1000;
            $_SESSION["firstname"] = "W3 Nu Validator";
            $_SESSION["usertype"] = ADMIN;
            if (!isset($_SESSION["nu-shown"]))
            {
                appendMessage("You have full access to the site.", "alert-primary");
                $_SESSION["nu-shown"] = "yes";
                appendText("access.log", "Nu access from " . $_SERVER["REMOTE_ADDR"]);
            }
            return TRUE;*/
        }
        else
        {
            return FALSE;
        }
    }

    /** Check if there is a user signed into the current session.
     * 
     * This checks if a user id is present in the session variables.
     * 
     * @return boolean True, if there is a user signed in; false otherwise.
     */
    function isSignedIn()
    {
        return isset($_SESSION["id"]);
    }

    /**
     * Essentially, acts as a sort of middleware that enforces user sign in.
     * If the user is not signed in, it'll kick them back to the sign in page.
     */
    function requiresSignIn()
    {
        // If the user isn't signed in, send them back to the sign in page with an appropriate banner message
        if (!isSignedIn())
        {
            setMessage("You must be signed in to view this page.", "alert-danger");
            redirect("sign-in.php");
        }
    }

    /**
     * Get the type of the logged in user.
     * 
     * @return string The user's type (i.e. ADMIN, or SALESPERSON). 
     *                Empty if the user has no type (not logged in).
     */
    function get_user_type()
    {
        if (isset($_SESSION["usertype"]))
        {
            return $_SESSION["usertype"];
        }
    }

    /** Get the full URL of the current page.
     * 
     * This includes the protocol (http/https, in case opentech gets a cert)
     * hostname and page location, of the page that calls this function.
     * 
     * @return string The full URL (example: http(s)?://hostname/page.php)
     */
    function getFullUrl()
    {
        /* I'm making an assumption here; if the server is on 443, it's HTTPS;
            but I doubt that servers on any other ports will have a certificate,
            so I'm assuming it'll be HTTP if it's not on port 443. */

        return (($_SERVER["SERVER_PORT"] == 443 ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"]);
    }

    /** Get the name of the current page.
     * 
     * This will return the last segment of the URL, such as index.php.
     * Useful for figuring out what page a user is on.
     * 
     * @return string The current page/script name, by itself.
     */
    function getCurrentPage()
    {
        /* Split the URL into segments (split on /), and return the last
            segment 
            (see: https://www.php.net/manual/en/function.explode.php
            for equivalent to "".split)
            (see: https://www.php.net/manual/en/function.end.php 
            for closest to equivalent to [-1] on this version of PHP)
        */
        $segments = explode('/', $_SERVER["PHP_SELF"]);
        return end($segments);
    }

    #region File functions
    /**
     * Append text to a file, with a new line added.
     * 
     * @param filename The name of the file to append to
     * @param text The text to append
     */
    function appendText($filename, $text)
    {
        /* Open a file for appending, write the text and a new line, close it. */
        $f = fopen($filename, "a+");
        fwrite($f, $text . "\n");
        fclose($f);
    }

    /** 
     * Write to log file, with a new line appended at the end of it.
     * 
     * @param text Text to write to the log.
     */
    function appendLog($text)
    {
        // YYYYmmdd form - 20201002
        $filename = date("Ymd", time()) . "_log.txt";
        appendText($filename, $text);
    }
    #endregion

    /**
     * Get the current timestamp
     * 
     * @return string The timestamp, in the format of YYYY-MM-DD hh:mm:ss
     */
    function getCurrentTimestamp()
    {
        // https://www.php.net/manual/en/function.date.php
        // format: https://www.php.net/manual/en/datetime.format.php
        // time() => current unix epoch
        return date('Y-m-d H:i:s', time());
    }

    /**
     * Create and return a list item, with the specified name and url,
     * and having it set as active if the user is currently on that page.
     * 
     * @param name The name, or innerText, of the anchor tag.
     * @param url The page to link to, and check if the user is on this page.
     * 
     * @return string A string with the created HTML element.
     */
    function linkWithCurrent($name, $url)
    {
        // Current page is used to decide whether the 'active' class needs to be added (if the user is on that page)
        //$currentPage = getCurrentPage();
        $isCurrentPage = getCurrentPage() == $url;
        return ("<li class=\"nav-item\">\n"
        . "<a class=\"nav-link"
        . ($isCurrentPage ? " active" : "") . "\" "
        . "href=\"" . $url . "\">\n"
        . "<span data-feather=\"home\"></span>\n"
        . $name
        . ($isCurrentPage ? " <span class=\"sr-only\">(current)</span>\n" : "")
        . "\n</a>\n"
        . "</li>\n");
    }

    #region Display functions
    /**
     * Given an array of associative arrays, create and return a 
     * self-submitting form element.
     * 
     * @param data The data for the form.
     * 
     * @return string The form element, ready for printing.
     */
    function display_form($data)
    {
        // start the form with the opening element; getCurrentPage() will make sure it's self referring
        $form = "<form 
            id=\"form-create\" class=\"form-registration\" method=\"post\" 
            enctype=\"multipart/form-data\" 
            action=\"" . getCurrentPage() . "\">";

        /* add max file size field for lab 3
            see example 1: https://www.php.net/manual/en/features.file-upload.post-method.php
            required to be in bytes, so the constant in constants.php is used */
        $form .= "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"" . MAXIMUM_FILE_SIZE . "\" />";

        // Go through the array and add the fields specified
        for ($i = 0; $i < count($data); $i++)
        {
            $row = $data[$i]; // use a shorthand to access the data
            $inputName = "input_" . $row["name"];
            
            /* creating a select element is different, 
                so check if that's what the current array requires */
            if ($row["type"] == "select") 
            {

                // Add the screen reader label
                $form .= ("<label class=\"sr-only\" for=\"" . $inputName . "\">" 
                            . $row["label"] . "</label>");

                // begin to create the actual select element
                $form .= "<select class=\"form-control\" ";
                // if this field is required, add the required attribute
                if (isset($row["required"]) && $row["required"]) $form .= " required ";
                // add the input id and name
                $form .= "id=\"" . $inputName . "\" name=\"" . $row["name"] . "\">";

                // Loop through the options, adding them 
                for ($idx = 0; $idx < count($row["options"]); $idx++)
                {
                    $option = $row["options"][$idx];
                    $form .= "<option class=\"form-control\" value=\"" . $option["value"] . "\">" . $option["content"] . "</option>";
                }

                // close the select element
                $form .= "</select>";
            }
            else 
            {
                /* use the value from the row, or a blank string if 
                    there is no value (file inputs) */
                $value = isset($row["value"]) ? $row["value"] : "";

                // append the label (only shown on screen readers)
                $form .= ("<label class=\"sr-only\" for=\"" . $inputName . "\">" 
                            . $row["label"] . "</label>");

                // then append the field
                $form .= ("<input name=\"" . $row["name"] . "\" type=\"" 
                            . $row["type"] . "\" id=\"" . $inputName . "\""
                            // if this field is required, add the required attribute; otherwise, nothing
                            . (isset($row["required"]) && $row["required"] ? " required" : "")
                            . " class=\"form-control\""
                            // only show placeholder if input type is not file (for validation)
                            . ($row["type"] !== "file" ? " placeholder=\"" . $row["label"] . "\" " : "")
                            // if the field is a number, make sure the minimum is 0, and it must be every whole number
                            . ($row["type"] === "number" ? "min=0 step=1 " : "")
                            // if the value is not null, use it so it's sticky; if it is, just use a blank string
                            . ($value !== null ? "value=\"" . $value . "\"" : "") 
                            . " />");
            }
            
        }

        // Add the submit button
        $form .= "<button class=\"btn btn-lg btn-primary btn-block\" type=\"submit\">Submit</button>";

        // and close off the form
        $form .= "</form>";
        return $form;
    }

    /**
     * Create a table that displays data in a table format, complete with
     * column headers, pagination (the ability to select the page of data 
     * to be viewed, limited by the number of rows per table page), and the
     * actual row data.
     * 
     * @param columns An associative array, containing keys representing the
     *                column names within the database, and the display name
     *                for the column, within this table.
     * @param data The data, or rows of data, that will be displayed within
     *             the table.
     * @param total The total number of rows of data; alternatively, the 
     *              COUNT for the number of rows in the database table.
     *              Used for calculating the total number of pages to 
     *              display in the page selector.
     * @param page The current page the user is on. Used for displaying the
     *             selected page within the selector, as well as whether or 
     *             not the "Next" or "Previous" buttons are enabled.
     * @param per_page The number of rows per page. Used for calculating the
     *                 total number of pages.
     * @param extra_cols Any extra column data for radio buttons, including 
     *                   what should be shown for the true and false radio 
     *                   buttons, and if the array indice should use another
     *                   column - such as an ID - and what that column's name
     *                   is.
     * 
     * @return string A string representation of the complete HTML table, 
     *                ready to be `echo`ed.
     */
    function display_table($columns, $data, $total, $page, $per_page = ROWS_PER_PAGE, $extra_cols = array())
    {   
        // round the total number of pages up (a.k.a get the ceiling)
        $total_page_count = ceil($total / $per_page);

        // build the table the same way the dashboard has it
        $table_string = 
            "<div class=\"table-responsive\">
                <table class=\"table table-striped table-sm\">
                    <thead>
                        <tr>";
        // append each header
        foreach ($columns as $column => $display_name)
        {
            $table_string .= "<th>" . $display_name . "</th>";
        }
        // close the table header, open the body
        $table_string .= "</tr></thead><tbody>";

        // only go through the rows if the data array is not empty
        if (!empty($data)) 
        {
            // go through the rows and append them to the table
            foreach ($data as $idx => $row)
            {
                $table_string .= "<tr>";
                /* need to go through the columns array to get the column name, 
                    as it is in the database */
                foreach ($columns as $column => $display_name)
                {
                    // special functionality is needed for the logo
                    if ($column === "logopath")
                    {
                        // use an img element
                        $table_string .= "<td><img height=\"128\" width=\"128\" alt=\"Client logo\" src=\"$row[$column]\"/></td>";
                    }
                    // special case for anything in $extra_cols - radio button captions
                    elseif (isset($extra_cols[$column]))
                    {
                        // get the label name for when its TRUE, and when its FALSE
                        $true_name = $extra_cols[$column][TRUE];
                        $false_name = $extra_cols[$column][FALSE];

                        /* check if the index portion of the radio group name should
                            another use a value from another column (i.e a specific ID)
                            if so, use that; otherwise, just use the index 
                            it will always contain the column name at the start*/
                        /* I need to concatenate the square brackets to the name, as without it,
                            it will interpret those square brackets as if getting the index of the
                            $column string */
                        $group_name = "$column" . "["
                            . (isset($extra_cols[$column]["index"]) ? $row[$extra_cols[$column]["index"]] : $idx)
                            . "]";

                        // add the form with the radio buttons
                        $table_string .= "<td><form method=\"POST\" action=\"" . $_SERVER["PHP_SELF"] . "\">";

                        // output the radio button for when its true
                        $table_string .= "<div>"
                                    . "<input type=\"radio\" id=\"$idx-$true_name\" "
                                    . "name=\"$group_name\" value=\"$true_name\""
                                    /* Postgres returns boolean values as 't' or 'f', 
                                        so that needs to be checked for instead of PHP's consts */
                                    . ($row[$column] == 't' ? " checked" : "") . " />"
                                    . "<label for=\"$idx-$true_name\">$true_name</label>"
                                    . "</div>";

                        // do the same for when it is false
                        $table_string .= "<div>"
                                    . "<input type=\"radio\" id=\"$idx-$false_name\" "
                                    . "name=\"$group_name\" value=\"$false_name\""
                                    . ($row[$column] == 'f' ? " checked" : "") . " />"
                                    . "<label for=\"$idx-$false_name\">$false_name</label>"
                                    . "</div>";
                        

                        // add the submit button
                        $table_string .= "<input type=\"submit\" value=\"Update\" />";

                        // end the form and cell
                        $table_string .= "</form></td>";
                    }
                    // default case for everything else
                    else
                    {
                        // use htmlspecialchars to stop an easy XSS
                        $table_string .= "<td>" . htmlspecialchars($row[$column]) . "</td>";
                    }
                }
                $table_string .= "</tr>";
            }
        }

        // close off the table
        $table_string .= "</tbody></table>";

        // add pagination elements
        // https://getbootstrap.com/docs/4.0/components/pagination/
        $table_string .= "<nav aria-label=\"select-page\">";
        $table_string .= "<ul class=\"pagination\">";
        
        // if it's on the first page, the `Previous` button should be disabled
        $table_string .= "<li class=\"page-item" . ($page == 1 ? " disabled " : "")  . "\">";

        // if it's on the first page, leave href blank; otherwise, set it to page - 1
        $table_string .= "<a class=\"page-link\" href=\"" . 
            ($page == 1 ? "" : $_SERVER["PHP_SELF"] . "?page=" . strval(($page - 1)))
            . "\" tabindex=\"-1\">Previous</a>";
        $table_string .= "</li>";

        // add the pagination links
        for ($page_item = 1; $page_item <= $total_page_count; $page_item++)
        {
            // if it's the current page, set as active
            $table_string .= "<li class=\"page-item" . ($page == $page_item ? " active" : "") . "\">";
            $table_string .= "<a class=\"page-link\" href=\"" . getCurrentPage() . "?page=" . $page_item . "\">" . $page_item . "</a>";
            $table_string .= "</li>";
        }

        // is the current page the last page, or are there no pages available?
        $is_last_page = (($page == $total_page_count) || ($total_page_count == 0));

        // add the `Next` element; disable it if it's already on the last page/there are no pages
        $table_string .= "<li class=\"page-item" . ($is_last_page ? " disabled " : "")  . "\">";

        // if it's on the last page, leave href blank; otherwise, set it to page - 1
        // alternatively, if there are no pages, leave href blank
        $table_string .= "<a class=\"page-link\" href=\"" . 
            ($is_last_page ? "" : $_SERVER["PHP_SELF"] . "?page=" . (intval($page) + 1)) . "\">Next</a>";
        $table_string .= "</li>";

        $table_string .= "</ul></nav></div>";
        return $table_string;
    }
    #endregion

    /** 
     * Append a message to the current banner message, if there is one.
     * Otherwise, set the banner message.
     * This will not override any pre-existing banner type.
     * 
     * @param messageToAppend The message to append.
     */
    function appendMessage($messageToAppend)
    {
        $message = "";

        /* if there's an existing message, get it and append a new line character
            (since this is using innerText instead of innerHTML, this will work) */
        if (isMessage())
            $message = getMessage() . "\n";
        
        // append to the message
        $message .= $messageToAppend;

        // set the message, with the current message type (if it exists)
        setMessage($message, (isMessageType() ? getMessageType() : "alert-primary"));
    }

    /** 
     * Append/set the banner message, but ensure it is of the
     * message-type 'alert-danger' (an error message).
     * 
     * @param messageToAppend The message to append.
     */
    function appendErrorMessage($messageToAppend)
    {
        // call appendMessage since it does the same thing
        appendMessage($messageToAppend);

        // and override the banner type
        setMessageType("alert-danger");
        
    }

    /**
     * Check whether a field is empty; if so, add an error to the 
     * banner message.
     * 
     * @param variable The variable to check if empty.
     * @param name The name of the input field, to append to the error message.
     * 
     * @return boolean True, if the variable is empty; false otherwise.
     */
    function checkIfEmpty($variable, $name)
    {
        if ($variable == "")
        {
            // if the message type is not set, set it
            if (!isMessageType()) setMessageType("alert-danger");

            // append the error message
            appendErrorMessage($name . " cannot be blank.");

            return TRUE;
        }

        return FALSE; // not empty
    }

    /**
     * Check if a string variable (by-ref) is under a certain length.
     * If not, output an error message and clear the variable.
     * 
     * @param variable The variable to check, by reference (for clearing).
     * @param name The name of this field, to be added to the error message.
     * @param length The length that the contents of the variable should be under.
     */
    function checkLength(&$variable, $name, $length)
    {
        // make sure it's a string, and not an array
        if (is_string($variable))
            // check if it's outside of bounds
            if (strlen($variable) > $length)
            {
                // append the error
                appendErrorMessage($name . " cannot have more than " . $length . " characters." 
                    . " You entered " . strlen($variable) . " characters.");
                
                // clear the variable
                $variable = "";
            }
    }
?>