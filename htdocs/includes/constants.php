<?php
    /*
        Haran
        September 15th, 2020
        WEBD3201
    */
    // A single character that will represent the Administrator user type
    define("ADMIN", "a"); 

    // Types for salespeople and clients
    define("SALESPERSON", "s");
    define("CLIENT", "c");

    /** DB Constants */
    define("DB_HOST", "127.0.0.1");
    define("DB_PORT", 5432);
    define("DATABASE", "prod_db");
    define("DB_ADMIN", "production");
    define("DB_PASSWORD", "22Cj3hUwxv6uQDaDNmiTqHG85zQ5NY");
    
    // Lifespan of a sesion cookie (from lesson 1a)
    define("COOKIE_LIFESPAN", "2592000"); //1a

    // Minimum password length
    define("MIN_PASSWORD_LENGTH", 3);

    // Number of rows per page in tables
    define("ROWS_PER_PAGE", 10);

    // maximum file size in bytes, and megabytes (user-friendly version)
    define("MAXIMUM_FILE_SIZE", 1000000);
    define("MAXIMUM_FILE_SIZE_MB", MAXIMUM_FILE_SIZE / 1000 / 1000 . " MB");
?>