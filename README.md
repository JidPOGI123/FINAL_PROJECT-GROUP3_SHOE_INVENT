# SHStorage — Shoe Storage Management System

A web-based storage management system that allows an admin to manage shoe inventory and process stock requests from branch stores.

---

## Project Description

SHStorage is a PHP and MySQL-powered web application built for managing shoe inventory across a central warehouse and multiple branch stores. The system supports two user roles — **Admin** and **Branch** — and provides full CRUD functionality for shoes, brands, colorways, inventory stock, and stock requests.

---

## Members

| Name | Role |
|------|------|
| Benitez, Dexter I. - Developer, Documentation
| Fernandez, Jhade Zymond R. - Developer, Documentation
| Regulto, Kyle Cyrus T. Developer, Documentation

---

## Technologies Used

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **Local Server:** XAMPP / Apache
- **Version Control:** Git & GitHub

---

## Installation Instructions

1. **Clone the repository**
   ```bash
   git clone https://github.com/JidPOGI123/FINAL_PROJECT-GROUP3_SHOE_INVENT
   ```

2. **Move the project to your server directory**
   - For XAMPP: place the folder inside `htdocs/`

3. **Import the database**
   - Open **phpMyAdmin**
   - Create a new database named `shstorage`
   - Import the provided `shstorage.sql` file

4. **Configure the database connection**
   - Open `config/db.php`
   - Update the credentials to match your local setup:
   ```php
   $host = "localhost";
   $dbname = "shstorage";
   $username = "root";
   $password = "";
   ```

5. **Run the project**
   - Start Apache and MySQL in XAMPP
   - Visit `http://localhost/shstorage` in your browser

---

## Deployment Link

- infinityfree
>http://shoestorage.kesug.com/login.php 
