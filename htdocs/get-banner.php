<?php
    /*
        Haran
        WEBD3201
        October 18th, 2020
    */
    // This page serves as a way to get the current banner info
    /* not using header.php since all I need to return is the banner data 
        - I can do that as JSON if I don't include the header */
    // need to include the functions file separately though, since I'm not using the header
    include("./includes/functions.php");

    // start an output buffer
    ob_start();

    // open the session, so there's a banner to read from
    session_start(); 

    // create an array for the data
    $data = array(
        "message" => flashMessage(), // this will get and delete the message - careful!
        "type" => flashMessageType() // same but for banner type
    );

    // return the data as JSON
    echo json_encode($data);

    // set the content type as json
    header("Content-Type: application/json");
    
    // flush the output buffer
    ob_flush();
?>