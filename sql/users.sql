-- Haran
-- September 23rd 2020
-- WEBD3201

-- Implement the 'pgcrypto' module for bcrypt
-- (not needed on opentech; extension already exists, and can't create with our user perms)
--CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- Drop any existing sequence so there's no duplicates
DROP SEQUENCE IF EXISTS users_id_seq CASCADE; -- need to cascade so it'll drop dependents
-- Create a new sequence of user ids, starting at 1000 (similar to AutoInt)
CREATE SEQUENCE users_id_seq START 1000;

-- DROP any existing TABLE named users, so there isn't duplicate information
DROP TABLE IF EXISTS users CASCADE; -- cascade so dependents are dropped too

-- CREATE a TABLE for the site's users
CREATE TABLE users (
    Id INT PRIMARY KEY DEFAULT nextval('users_id_seq'),
    Email VARCHAR(320) UNIQUE, -- max length comes from RFC3696, section 3
    FirstName VARCHAR(255), 
    LastName VARCHAR(255), -- First and last names can be quite long in some cultures
    PasswordHash TEXT,
    Created TIMESTAMP,
    LastLogin TIMESTAMP,
    PhoneExtension INT,
    Active BOOLEAN,
    UserType VARCHAR(2) -- CAN store up to 2 (not DOES store up to 2 - VARCHAR vs CHAR)
);

INSERT INTO users (Email, FirstName, LastName, PasswordHash, Created, LastLogin, PhoneExtension, Active, UserType) VALUES (
    'jane.doe@sales.2082.ca',
    'Jane',
    'Doe',
    crypt('letmein', gen_salt('bf')),
    '2020-09-20 03:00:00',
    NULL,
    1,
    TRUE,
    'a'
);

INSERT INTO users (Email, FirstName, LastName, PasswordHash, Created, LastLogin, PhoneExtension, Active, UserType) VALUES (
    'haran@nxdomain.ca',
    'Haran',
    '',
    crypt('password', gen_salt('bf')),
    '2020-09-20 03:00:00',
    NULL,
    2,
    TRUE,
    'a'
);

INSERT INTO users (Email, FirstName, LastName, PasswordHash, Created, LastLogin, PhoneExtension, Active, UserType) VALUES (
    'admin@sales.2082.ca',
    'Sales',
    'Admin',
    crypt('hunter2', gen_salt('bf')),
    '2020-09-20 03:00:00',
    NULL,
    3,
    TRUE,
    'a'
);

INSERT INTO users (Email, FirstName, LastName, PasswordHash, Created, LastLogin, PhoneExtension, Active, UserType) VALUES (
    'salesperson@sales.2082.ca',
    'Salesperson',
    'Personsales',
    crypt('My secure password', gen_salt('bf')),
    '2020-10-16 12:59:00',
    NULL,
    2010,
    TRUE,
    's'
);

-- Some more users to test pagination
INSERT INTO users (Email, FirstName, LastName, PasswordHash, Created, LastLogin, PhoneExtension, Active, UserType) VALUES (
    'personsales@sales.2082.ca',
    'Person',
    'Personsales',
    crypt('Aerials', gen_salt('bf')),
    '2020-11-20 12:00:00',
    NULL,
    2010,
    TRUE,
    's'
);