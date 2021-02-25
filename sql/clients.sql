-- Haran
-- October 7th 2020
-- WEBD3201
-- Remove existing data and re-create it
DROP SEQUENCE IF EXISTS clients_id_seq CASCADE; -- need to cascade so it'll drop dependents
DROP TABLE IF EXISTS clients CASCADE; -- need to cascade since calls depends on this

-- Create a new sequence of user ids, starting at ~~1000~~ (similar to AutoInt)
-- Start at 1007, since this script inserts up to 1006.
CREATE SEQUENCE clients_id_seq START 1007;

CREATE TABLE clients (
    Id INT PRIMARY KEY DEFAULT nextval('clients_id_seq'),
    PhoneNumber CHAR(10) NOT NULL, -- assuming it's NANP 
    Email VARCHAR(320) UNIQUE NOT NULL, -- can assume unique, per doc
    Extension INT, -- this is the only field that the document states is optional
    FirstName VARCHAR(255) NOT NULL,
    LastName VARCHAR(255) NOT NULL,
    Created TIMESTAMP NOT NULL,
    LogoPath TEXT, -- the length of this can vary based on the filesystem used, probably
    SalespersonId INT REFERENCES users(Id)
);

INSERT INTO clients (Id, PhoneNumber, Email, FirstName, LastName, Created, LogoPath, SalespersonId) 
    VALUES(1000, '4165551234', 'king-koopa@on.some.airship.local', 'Bowser', 'Koopa', '2020-10-16 01:17:00', './upload/placeholder.png', 1003);
INSERT INTO clients (Id, PhoneNumber, Email, FirstName, LastName, Created, LogoPath, SalespersonId) 
    VALUES(1001, '2895551337', 'princess-toadstool@mushroomkingdom.local', 'Peach', 'Toadstool', '2020-10-23 12:21:12', './upload/placeholder.png', 1003);
INSERT INTO clients (Id, PhoneNumber, Email, FirstName, LastName, Created, LogoPath, SalespersonId) 
    VALUES(1002, '9055551234', 'luigi@mushroomkingdom.local', 'Luigi', 'Mario', '2020-11-10 11:10:50', './upload/placeholder.png', 1003);
INSERT INTO clients (Id, PhoneNumber, Email, FirstName, LastName, Created, LogoPath, SalespersonId) 
    VALUES(1003, '4155551234', 'daron.malakian@soad.local', 'Daron', 'Malakian', '2020-11-20 12:00:00', './upload/placeholder.png', 1004);
INSERT INTO clients (Id, PhoneNumber, Email, FirstName, LastName, Created, LogoPath, SalespersonId) 
    VALUES(1004, '4155554321', 'shavo.odadjian@soad.local', 'Shavo', 'Odadjian', '2020-11-20 12:00:00', './upload/placeholder.png', 1004);
INSERT INTO clients (Id, PhoneNumber, Email, FirstName, LastName, Created, LogoPath, SalespersonId) 
    VALUES(1005, '4155550000', 'john.dolmayan@soad.local', 'John', 'Dolmayan', '2020-11-20 14:18:52', './upload/placeholder.png', 1004);