CREATE TABLE User (
    id INT PRIMARY KEY,
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    email VARCHAR(255),
    login VARCHAR(255),
    password VARCHAR(255), -- Dodane pole "password"
    phone_number VARCHAR(20),
    rank INT,
    number_of_houses INT
);

CREATE TABLE Rank (
    id INT PRIMARY KEY,
    name VARCHAR(255)
);

INSERT INTO Rank (id, name) VALUES (1, 'admin');
INSERT INTO Rank (id, name) VALUES (2, 'user');

CREATE TABLE House (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    family_id INT,
    city VARCHAR(255)
);

CREATE TABLE Family (
    id INT PRIMARY KEY,
    id_admin INT,
    admin_user1 INT,
    admin_user2 INT,
    admin_user3 INT,
    admin_user4 INT,
    admin_user5 INT,
    admin_user6 INT
);

CREATE TABLE Device (
    id INT PRIMARY KEY,
    ip VARCHAR(15),
    mac VARCHAR(17),
    name VARCHAR(255),
    room_id INT,
    state INT
);


CREATE TABLE Room (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    house_id INT
);

