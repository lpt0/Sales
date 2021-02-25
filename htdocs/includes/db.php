<?php
    /*
        Haran
        September 15th, 2020
        WEBD3201
    */
    /**
     * Connect to the database and return the connection.
     * 
     * @return resource The database connection
     */
    function db_connect()
    {
        /* Connect to the Postgres database using the connection params
            (defined in ./constants.php) and return that connection */
        return pg_connect("host=" . DB_HOST . " port=" . DB_PORT . " dbname=" . DATABASE . " user=" . DB_ADMIN . " password=" . DB_PASSWORD);
    }

    $conn = db_connect();
    /** Statements **/
    #region Prepared Statements
    $user_select_stmt = pg_prepare($conn, "user_select", "SELECT * FROM users WHERE id = $1;");
    $user_select_all_stmt = pg_prepare($conn, "user_select_all", "SELECT * FROM users;"); // from 1d_web_security
    $user_update_login_time = pg_prepare($conn, "user_update_login_time", "UPDATE users SET LastLogin = $1 WHERE id = $2;");
    #endregion
    
    /** 
     * Get information for a given user.
     * 
     * If the user exists, an associative array of their data will be 
     * returned.
     * If the user does not exist, this will return false.
     * 
     * @param id The ID of the user to get.
     * 
     * @return array/boolean An associative array of the user's data, if it
     *                          exists; false if it does not.
     * 
     */
    function user_select($id)
    {
        /* Execute the prepared statement (above), and check if only one 
            entry (the user) was returned. */
        $result = pg_execute("user_select", array($id));
        if (pg_num_rows($result) == 1)
        {
            // pg_fetch_assoc($result, $row_num) returns an associative array
            return pg_fetch_assoc($result, 0);
        }
        return FALSE;
    }
    
    /**
     * Attempt to authenticate a user's credentials.
     * 
     * If the passed credentials are valid, an associative array of the 
     * user's data will be returned.
     * If not, false will be returned.
     * 
     * @param email The email to check.
     * @param password The password to compare the database entry against.
     * 
     * @return array/boolean An associative array of user data if it exists; 
     *                          false otherwise.
     */
    function user_authenticate($email, $password)
    {
        $currentTimestamp = getCurrentTimestamp(); // for logging, and setting the last login time
        $isLoginFail = TRUE; // assume the login is bad
        $user_authenticate_stmt = pg_prepare(db_connect(), "user_authenticate", "SELECT * FROM users WHERE email=$1");
        
        // Execute the statement, and make sure only one entry was returned
        $result = pg_execute("user_authenticate", [$email]);
        if (pg_num_rows($result) == 1)
        {
            $userInfo = pg_fetch_assoc($result, 0);

            /* Compare the passed password (hashed by password verify) to
                the password hash in the db. 
                When the row is converted to an associative array, column 
                names become lowercase. */
            if (password_verify($password, $userInfo["passwordhash"]))
            {
                // login success!
                $isLoginFail = FALSE;
                return $userInfo;
            }
        }

        if ($isLoginFail)
        {
            /* I'm adding quotations just in case someone passes an empty 
                string - so I know it's an empty string */
            appendLog("Sign in failed at " . $currentTimestamp . ". User \"" . $email. "\" could not be logged in.");
            return false;
        }
    }

    /**
     * Update a user last login time.
     * 
     * @param timestamp The timestamp of the login (use getCurrentTimestamp).
     * @param id The ID of the user to update the login time for.
     * 
     * @return string An error message, if any; blank otherwise.
     */
    function user_update_login_time($timestamp, $id)
    {
        // execute the statement, suppressing errors
        @pg_execute("user_update_login_time", array($timestamp, $id));

        return pg_last_error();
    }

    /**
     * Attempt to update the password for a given user ID.
     * 
     * @param userId The ID of the user to update the password for.
     * @param password The new password.
     * 
     * @return string If there was an error during the record update, 
     *                the error message; otherwise, blank.
     */
    function user_update_password($userId, $password)
    {
        $user_update_password_stmt = pg_prepare(db_connect(), "user_update_password", "UPDATE users SET PasswordHash = $1 WHERE Id = $2;");
        
        // hash the password with bcrypt
        $hash = password_hash($password, PASSWORD_BCRYPT);

        /* execute the statement
            don't need to keep the result (number of rows affected/the row affected);
            caller will know it worked by checking if the last error is empty or not 
            use `@` to suppress errors from being outputted to browser */
        @pg_execute("user_update_password", array($hash, $userId));

        // return the last error (if empty, there was none)
        return pg_last_error();

    }
    
    /**
     * Select everyone from the users table who has the salesperson type.
     * 
     * @return array An array of associative arrays, with the keys being the
     *               column/field names, and the values being the values.
     */
    function select_all_salespeople_old() 
    {
        // prepare the statement
        $salespeople_select_stmt = pg_prepare(
            db_connect(), 
            "select_all_salespeople", 
            // Only returns the columns needed - not everything (don't want the password hash returned)
            ("SELECT "
                . "users.Id, users.Email, users.FirstName, users.LastName, users.Created, users.LastLogin, users.PhoneExtension "
                . "FROM users WHERE UserType = '" . SALESPERSON . "';")
        );

        // execute and return all rows in the query (by default, it returns an array of associative arrays)
        $result = pg_execute("select_all_salespeople", []);
        return pg_fetch_all($result);
    }

    /**
     * Select everyone from the clients table, along with the associated
     * client.
     * 
     * @return array An array of associative arrays, with the keys being the
     *               column/field names, and the values being the values.
     */
    function select_all_clients_old()
    {
        $client_select_stmt = pg_prepare(
            db_connect(), 
            "select_all_clients", 
            // this is fine since there is no password for clients
            "SELECT clients.id, clients.firstname, clients.lastname, clients.phonenumber, clients.email, clients.extension, clients.created,"
            . "CONCAT(users.firstname, ' ', users.lastname) AS \"salespersonname\" FROM clients "
            . "INNER JOIN users ON clients.SalespersonID = users.ID;" 
        );
        $result = pg_execute("select_all_clients", []);
        return pg_fetch_all($result);
    }

    /**
     * Select all calls from the calls table, as well as the full name 
     * of the client who made the call.
     * 
     * @return array An array of associative arrays, with the keys being the
     *               column/field names, and the values being the values. 
     */
    function select_all_calls_old()
    {
        $call_select_stmt = pg_prepare(
            db_connect(),
            "select_all_calls",
            "SELECT calls.id, calls.created, CONCAT(clients.firstname, ' ', clients.lastname) AS clientname "
            . "FROM calls INNER JOIN clients ON calls.clientid = clients.id;"
        );
        $result = pg_execute("select_all_calls", []);
        return pg_fetch_all($result);
    }

    /**
     * Attempt to insert a salesperson into the database.
     * 
     * @param firstName The first name of the salesperson.
     * @param lastName The last name of the salesperson.
     * @param email The email of the salesperson (must be unique!)
     * @param extension The extension number for this salesperson.
     * @param password The password for this salesperson, to be able to login.
     * 
     * @return string A string with the error, if there is one; otherwise, an empty string.
     */
    function create_salesperson($firstName, $lastName, $email, $extension, $password)
    {
        // create the prepared statement
        $salesperson_create_stmt = pg_prepare(
            db_connect(), 
            "create_salesperson",
            "INSERT INTO users (FirstName, LastName, Email, PhoneExtension, PasswordHash, Created, UserType) VALUES ($1, $2, $3, $4, $5, $6, $7);"
        );

        // need to hash the password - by default, will auto-gen salt
        $hash = password_hash($password, PASSWORD_BCRYPT);

        /* try and execute it with the appropriate values
            the '@' decarator is used to supress pg_execute from echo'ing 
            any warnings to the page */
        $result = @pg_execute("create_salesperson", [$firstName, $lastName, $email, $extension, $hash, getCurrentTimestamp(), SALESPERSON]);

        /* return the last error, as a string
            if there is no error, there will be no string/the string is empty 
            that way, if the string is empty, the caller knows that the insert 
            was a success */
        return pg_last_error(); 
    }

    /**
     * Attempt to insert a client record into the clients table
     * 
     * @param firstName The first name of the client.
     * @param lastName The last name of the client.
     * @param email The email of the client (must be unique within the clients table!)
     * @param phoneNumber The phone number of the client (10 digits/characters)
     * @param salespersonId The ID of the salesperson associated with this client.
     * @param logo_path The path to the uploaded logo.
     * @param extension The extension number for this client (optional).
     */
    function create_client($firstName, $lastName, $email, $phoneNumber, $salespersonId, $logo_path, $extension)
    {
        $client_create_stmt = pg_prepare(
            db_connect(),
            "create_client",
            "INSERT INTO clients (FirstName, LastName, Email, PhoneNumber, Extension, Created, LogoPath, SalespersonID) VALUES ($1, $2, $3, $4, $5, $6, $7, $8);"
        );

        // the '@' is used so that PHP won't spit out any errors directly to the page from here
        $result = @pg_execute("create_client", [$firstName, $lastName, $email, $phoneNumber, $extension, getCurrentTimestamp(), $logo_path, $salespersonId]);

        /* return whatever the last error was; if there is no error, it will be an empty string
            calling this with no parameter means that it will use the most recent database connection */
        return pg_last_error();
    }

    /**
     * Attempt to insert a call into the calls table, 
     * with the current timestamp.
     * 
     * @param clientId The ID of the client to add a call for.
     */
    function create_call($clientId)
    {
        $call_create_stmt = pg_preparE(
            db_connect(),
            "create_call",
            "INSERT INTO calls (Created, ClientId) VALUES ($1, $2)"
        );
        
        // assume it's the current timestamp
        $result = @pg_execute("create_call", [getCurrentTimestamp(), $clientId]);
        
        return pg_last_error();
    }

    /**
     * Get the total number of rows in the `calls` table.
     * 
     * @return number The number of rows in the `calls` table.
     */
    function count_rows_in_calls()
    {
        // all tables have the Id column; no need to select everything
        $result = pg_query(db_connect(), "SELECT COUNT(Id) FROM calls;");

        // return the contents of the first row, count column
        return pg_fetch_result($result, 0, "count");
    }

    /**
     * Get the number of rows in the `calls` table, for clients belonging to
     * the specified salesperson ID.
     * 
     * @param salesperson_id The ID of the salesperson to check for.
     *                       This will check for calls made by clients 
     *                       that belong to this salesperson.
     * 
     * @return number The number of rows of calls for this salesperson.
     */
    function count_rows_in_calls_for_salesperson($salesperson_id)
    {
        $stmt = pg_prepare(
            db_connect(), 
            "count_rows_in_calls_for_salesperson",
            "SELECT COUNT(calls.Id) FROM calls "
                . "INNER JOIN clients ON calls.ClientId = clients.Id "
                . "WHERE clients.SalespersonId = $1;"
        );
        $result = pg_execute("count_rows_in_calls_for_salesperson", array($salesperson_id));
        return pg_fetch_result($result, 0, "count"); // only one row, only one col - count
    }

    /**
     * Get the total number of rows in the `clients` table.
     * 
     * @return number The number of rows in the `clients` table., across all
     *                salespeople.
     */
    function count_rows_in_clients()
    {
        // all tables have the Id column; no need to select everything
        $result = pg_query(db_connect(), "SELECT COUNT(Id) FROM clients;");

        // return the contents of the first row, count column
        return pg_fetch_result($result, 0, "count");
    }

    /**
     * Get the number of rows in the `clients` table for the specified 
     * salesperson.
     * 
     * @param salesperson_id The ID of the salesperson to search for their 
     *                       clients. In other words, the client's associated
     *                       salesperson (ID).
     * 
     * @return number The number of rows, or clients, belonging to this 
     *                salesperson.
     */
    function count_rows_in_clients_for_salesperson($salesperson_id)
    {
        $stmt = pg_prepare(
            db_connect(),
            "count_rows_in_clients_for_salesperson",
            "SELECT COUNT(Id) FROM clients WHERE SalespersonId = $1"
        );
        $result = pg_execute("count_rows_in_clients_for_salesperson", array($salesperson_id));
        
        // there's only one row in the result, and the column is named count by default
        return pg_fetch_result($result, 0, "count");
    }

    /**
     * Get the total number of rows in the `users` table, where the users are
     * salespeople. In other words, get the total number of salespeople in 
     * the database.
     * 
     * @return number The total number of salespeople.
     */
    function count_salespeople()
    {
        /*$stmt = pg_prepare(
            db_connect(),
            "count_rows_in_salespeople",
            "SELECT COUNT(Id) FROM users WHERE UserType = '" . SALESPERSON "';"
        );
        $result = pg_execute("count_rows_in_salespeople", array())*/
        $result = pg_query(db_connect(), "SELECT COUNT(Id) FROM users WHERE UserType = '" . SALESPERSON . "';");
        return pg_fetch_result($result, 0, "count");
    }

    /**
     * Get all of the clients in the `clients` table, limited by the 
     * page number and number of clients per page. 
     * This is used for admin users.
     * 
     * @param page The current page of clients to retrieve.
     *             Used for calculating the offset.
     * @param per_page The limit on number of clients to retrieve, per page.
     *                 Used for calculating the offset, and limiting the
     *                 number of rows returned.
     *                 Default is the ROWS_PER_PAGE constant.
     * 
     * @return array An array of associative arrays, containing each client.
     */
    function select_all_clients($page = 1, $per_page = ROWS_PER_PAGE)
    {
        $offset = (($page - 1) * ($per_page));

        $client_select_all_stmt = pg_prepare(
            db_connect(), 
            "clients_select_all",
            ("SELECT clients.Id, clients.Email, clients.FirstName, 
                clients.LastName, clients.PhoneNumber, clients.Extension, 
                clients.LogoPath, (users.FirstName || ' ' || users.LastName) AS salespersonname "
                . "FROM clients 
                    INNER JOIN users ON clients.salespersonid = users.id
                    LIMIT $1 OFFSET $2;") 
        );
        $result = pg_execute("clients_select_all", array($per_page, $offset));
        return pg_fetch_all($result);
    }

    /**
     * Get all of the clients in the `clients` table for the specified 
     * salesperson.
     * 
     * @param page The current page of clients to retrieve.
     *             Used for calculating the offset.
     * @param per_page The limit on number of clients to retrieve, per page.
     *                 Used for calculating the offset, and limiting the
     *                 number of rows returned.
     *                 Default is the ROWS_PER_PAGE constant.
     * @param id The ID of the salesperson to retrieve clients 
     *                       for. In other words, the clients associated with
     *                       this salesperson ID.
     * 
     * @return array An array of associative arrays, containing each client
     *               associated with the specified salesperson.
     */
    function select_clients_for_salesperson($id, $page = 1, $per_page = ROWS_PER_PAGE)
    {
        $offset = (($page - 1) * ($per_page + 1));
        $conn = db_connect();
        $client_select_for_salesperson_stmt = pg_prepare(
            $conn, 
            "clients_select_for_salesperson",
            ("SELECT Id, Email, FirstName, LastName, PhoneNumber, Extension, LogoPath "
                . "FROM clients WHERE SalespersonId = $1 LIMIT $2 OFFSET $3;") 
        );
        $result = pg_execute($conn, "clients_select_for_salesperson", array($id, $per_page, $offset));
        return pg_fetch_all($result);
    }

    /**
     * Get all of the calls in the `calls` table, limited by the 
     * page number and number of calls per page. 
     * This is used for admin users.
     * 
     * @param page The current page of calls to retrieve.
     *             Used for calculating the offset.
     * @param per_page The limit on number of calls to retrieve, per page.
     *                 Used for calculating the offset, and limiting the
     *                 number of rows returned.
     *                 Default is the ROWS_PER_PAGE constant.
     * 
     * @return array An array of associative arrays, containing each call,
     *               and the name of the client making that call.
     */
    function select_all_calls($page = 1, $per_page = ROWS_PER_PAGE)
    {
        $offset = (($page - 1) * ($per_page));

        $calls_select_all = pg_prepare(
            db_connect(), 
            "calls_select_all",
            ("SELECT calls.Id, calls.Created, 
                (clients.FirstName || ' ' || clients.LastName) AS clientname, 
                (users.FirstName || ' ' || users.LastName) AS salespersonname "
                . "FROM calls "
                . "INNER JOIN clients ON calls.ClientId = clients.Id
                    INNER JOIN users ON clients.SalespersonId = users.Id "
                . "LIMIT $1 OFFSET $2;") 
        );

        $result = pg_execute("calls_select_all", array($per_page, $offset));
        return pg_fetch_all($result);
    }

    /**
     * Get all of the calls in the `calls` table for the specified 
     * salesperson, limited by th  page number and number of calls per page. 
     * 
     * @param page The current page of calls to retrieve.
     *             Used for calculating the offset.
     * @param per_page The limit on number of calls to retrieve, per page.
     *                 Used for calculating the offset, and limiting the
     *                 number of rows returned.
     *                 Default is the ROWS_PER_PAGE constant.
     * @param id The ID of the salesperson to retrieve calls for.
     *           In other words, the calls associated with clients tied to
     *           this salesperson's ID.
     * 
     * @return array An array of associative arrays, containing each call,
     *               and the name of the client making that call.
     */
    function select_calls_for_salesperson($id, $page = 1, $per_page = ROWS_PER_PAGE)
    {
        $offset = (($page - 1) * ($per_page));

        $calls_select_for_salesperson_stmt = pg_prepare(
            db_connect(), 
            "calls_select_for_salesperson",
            ("SELECT calls.Id, calls.Created, (clients.FirstName || ' ' || clients.LastName) AS clientname "
                . "FROM calls "
                . "INNER JOIN clients ON calls.ClientId = clients.Id "
                . "WHERE clients.SalespersonId = $1 LIMIT $2 OFFSET $3;") // $2 = per_age $3 = offset
        );

        $result = pg_execute("calls_select_for_salesperson", array($id, $per_page, $offset));
        return pg_fetch_all($result);
    }

    /**
     * Get all of the salespeople in the `users` table (users of type 'S').
     * 
     * @param page The current page of calls to retrieve.
     *             Used for calculating the offset.
     * @param per_page The limit on number of calls to retrieve, per page.
     *                 Used for calculating the offset, and limiting the
     *                 number of rows returned.
     *                 Default is the ROWS_PER_PAGE constant.
     * 
     * @return array An array of associative arrays, containing each 
     *               salesperson's details.
     */
    function select_all_salespeople($page = 1, $per_page = ROWS_PER_PAGE) 
    {
        $offset = (($page - 1) * ($per_page));

        // prepare the statement
        $salespeople_select_stmt = pg_prepare(
            db_connect(), 
            "select_all_salespeople", 
            // Only returns the columns needed - not everything (don't want the password hash returned)
            ("SELECT "
                . "users.Id, users.Email, users.FirstName, users.LastName, users.Created, users.LastLogin, users.Active, users.PhoneExtension "
                . "FROM users WHERE UserType = '" . SALESPERSON . "' "
                /* ensure that rows are returned in ascending order
                    not important on localhost since it usually does this by default,
                    but on opentech the order can be random on some days */
                . "ORDER BY Id ASC "
                . "LIMIT $1 OFFSET $2;") // $1 = per_page $2 = offset (page)
        );

        // execute and return all rows in the query (by default, it returns an array of associative arrays)
        $result = pg_execute("select_all_salespeople", array($per_page, $offset));
        return pg_fetch_all($result);
    }

    /** 
     * Get a user's ID from their email, if the email is in the database.
     * Useful for checking whether an email exists.
     * 
     * @param email The email address to look for.
     * 
     * @return array/boolean The user's data as an associative array, if it 
     *                       exists (namely, ID and first name). 
     *                       False otherwise.
     */
    function select_user_by_email($email)
    {
        // prep the statement
        $select_user_by_email_stmt = pg_prepare(
            db_connect(),
            "select_user_by_email",
            // only fetch ONE account
            "SELECT ID, FirstName FROM users WHERE Email = $1 LIMIT 1;"
        );

        // execute it with the email to search for
        $result = pg_execute("select_user_by_email", array($email));

        if (pg_num_rows($result) === 1) // a single row returned
        {
            // return an associative array with user data (only 1 row, so only fetch that one)
            return pg_fetch_assoc($result, 0);
        }
        else
        {
            return FALSE; // user does not exist
        }
    }

    /**
     * Update a user's account state, to make it either active or inactive.
     * 
     * @param id The ID of the user to change.
     * @param is_active A boolean (either TRUE or FALSE) to represent the 
     *                  account's new state (active or inactive, 
     *                  respectively).
     * 
     * @return string A string with the last error, if any.
     */
    function user_update_active($id, $is_active)
    {
        $user_update_active_stmt = pg_prepare(
            db_connect(),
            "user_update_active",
            "UPDATE users SET Active = $1 WHERE Id = $2;"
        );
        
        @pg_execute("user_update_active", array($is_active, $id));

        // return the last error (if any)
        return pg_last_error();
    }
?>