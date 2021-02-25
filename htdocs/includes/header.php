<?php
    /*
        Haran
        September 11th, 2020
        WEBD3201
    */
    /* Require three filers:
        - constants.php which has site-wide constants
        - functions.php which includes helpful miscallenous functions
        - db.php which contains all the database logic
        By including it here, any script that calls this one will have these
        functions and constants defined
    */
    require("constants.php");
    require("functions.php");
    require("db.php");
    
    
    // And start an output buffer, as well as session for tracking user state
    ob_start();
    session_start();
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="<?php echo $description; ?>">
    <meta name="author" content="Haran">
    <link rel="icon" href="/docs/4.0/assets/img/favicons/favicon.ico">
    <!--
        Author: Haran
        Filename: <?php echo $filename . "\n"; ?>
        Date: <?php echo $date . "\n"; ?>
        Description: <?php echo $description . "$n"; ?>
    -->
    <title><?php echo $title; ?></title>

    <!-- Bootstrap core CSS -->
    <link href="./css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom styles for this template -->
    <link href="./css/styles.css" rel="stylesheet">

    <!-- Banner messaging script -->
    <script>
            // whether or not the banner is set already
            var isBannerSet = false;

            /**
             * Check whether there's a banner message available.
             * If so, display it.
             */
            function getAndFlashBanner()
            {
                // the base part of the URL (for accessing the get-banner script)
                var baseUrl = "";

                // fix the URL for opentech
                if (document.documentURI.match("opentech"))
                    /* since, on opentech, the script is not at the root of the server, I need to account for that
                        the first 5 segments of the URL are needed, since that's where it's stored */
                    baseUrl = document.documentURI.split("/").slice(0, 5).join("/");
                // manual XHR time! hopefully I remember how to do this from ~3-4 years ago? (gosh it's been that long)
                var xhr = new XMLHttpRequest();
                
                xhr.open("GET", `${baseUrl}/get-banner.php`); // leaving the async param empty, so it send asynchronously (by default its async)
                xhr.onload = () => { // set a callback
                    // parse the received json
                    // wrap in a try-catch to be safe
                    try 
                    {
                        var data = JSON.parse(xhr.response);

                        // make sure a message is set, just so the class won't be set incorrectly
                        if (data.message !== "") { 
                            /* set the banner text element to contain the banner message
                                I'm specifying the whole path, just to be explicit and make 
                                sure it selects the correct center element 
                                ~~I chose to use innerHTML instead of innerText because for some errors,~~
                                ~~I add line breaks (<br />)~~
                                decided to use innerText instead, just to be safe */
                            document.querySelector("div#banner").innerText = data.message;
                            /* set the banner div's class to be the alert type
                                use javascript's template strings, because I love them */
                            document.querySelector("div#banner").className = `alert ${data.type}`;

                            // banner is now set
                            isBannerSet = true;
                        }
                    }
                    catch (ex)
                    {
                        console.warn(`Caught error: "${ex.toString()}"`);
                    }
                }
                xhr.send(); // fire it off
            }
        </script>
  </head>
  <!-- 
      Per Austin's suggestion, execute the banner function after 
      the entire document is loaded.
    -->
  <body onload="getAndFlashBanner();">
    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0">
        <a class="navbar-brand col-sm-3 col-md-2 mr-0" href="index.php">
            <?php echo(isSignedIn() ? ("Hi, " . $_SESSION["firstname"]) . "." : ("Sales Management")); ?>
        </a>
        <ul class="navbar-nav px-3">
        <li class="nav-item text-nowrap">
            <?php 
                /* Display a sign out button if they're signed in; otherwise,
                    display a sign in button */
                if (isSignedIn()) 
                {
                    echo "<a class=\"nav-link\" href=\"logout.php\">Sign out</a>";
                }
                else
                {
                    echo "<a class=\"nav-link\" href=\"sign-in.php\">Sign in</a>";
                }
            ?>
        </li>
        </ul>
    </nav>
    <div class="container-fluid">
      <div class="row">
        <nav class="col-md-2 d-none d-md-block bg-light sidebar">
            <div class="sidebar-sticky">
            <ul class="nav flex-column">
                <?php
                    /**
                     * Only show a link to the dashboard if user is signed in.
                     * Also, only set it to active (text is blue) if the user
                     * *is* on the dashboard. This is done by using a ternary
                     * operator and checking the script name; if it's the 
                     * dashboard, it'll add "active" to the class
                     * It does the same check before adding the screen reader
                     * element.
                     * 
                     * This is done using the linkWithCurrent() function, which
                     * takes care of doing all this within a function, in the 
                     * functions.php file.
                     */
                    if (isSignedIn()) 
                    {
                        // All of the URLs that need to be shown if signed in
                        // Everyone can access the dashboard
                        echo(linkWithCurrent("Dashboard", "dashboard.php"));

                        // Only admins can access the salespeople page
                        if (isSignedIn() && get_user_type() == ADMIN)
                            echo(linkWithcurrent("Salespeople", "salespeople.php"));
                        
                        // Salespeople and admins can access the clients and calls page
                        if (isSignedIn() && (get_user_type() == ADMIN || get_user_type() == SALESPERSON))
                        {
                            echo(linkWithCurrent("Clients", "clients.php"));
                            echo(linkWithCurrent("Calls", "calls.php"));
                        }

                        // everyone can see the change password page
                        echo linkWithCurrent("Change Password", "change-password.php");
                    }

                    // only people who aren't signed in can see the reset page
                    if (!isSignedIn())
                    {
                        echo linkWithcurrent("Reset Password", "reset.php");
                    }
                ?>
                <li class="nav-item">
                <a class="nav-link" href="#">
                    <span data-feather="file"></span>
                    Orders
                </a>
                </li>
                <li class="nav-item">
                <a class="nav-link" href="#">
                    <span data-feather="shopping-cart"></span>
                    Products
                </a>
                </li>
                <li class="nav-item">
                <a class="nav-link" href="#">
                    <span data-feather="users"></span>
                    Customers
                </a>
                </li>
                <li class="nav-item">
                <a class="nav-link" href="#">
                    <span data-feather="bar-chart-2"></span>
                    Reports
                </a>
                </li>
                <li class="nav-item">
                <a class="nav-link" href="#">
                    <span data-feather="layers"></span>
                    Integrations
                </a>
                </li>
            </ul>

            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                <span>Saved reports</span>
                <a class="d-flex align-items-center text-muted" href="#">
                <span data-feather="plus-circle"></span>
                </a>
            </h6>
            <ul class="nav flex-column mb-2">
                <li class="nav-item">
                <a class="nav-link" href="#">
                    <span data-feather="file-text"></span>
                    Current month
                </a>
                </li>
                <li class="nav-item">
                <a class="nav-link" href="#">
                    <span data-feather="file-text"></span>
                    Last quarter
                </a>
                </li>
                <li class="nav-item">
                <a class="nav-link" href="#">
                    <span data-feather="file-text"></span>
                    Social engagement
                </a>
                </li>
                <li class="nav-item">
                <a class="nav-link" href="#">
                    <span data-feather="file-text"></span>
                    Year-end sale
                </a>
                </li>
            </ul>
            </div>
        </nav>
        
        <main class="col-md-9 ml-sm-auto col-lg-10 pt-3 px-4">
        <!-- Banner element -->
        <div id="banner"></div>
          <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">