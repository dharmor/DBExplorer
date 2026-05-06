# DBExplorer

[![Website](https://img.shields.io/badge/Website-www.daves--corner.com-0b5cad?style=for-the-badge)](https://www.daves-corner.com)
[![Test it live](https://img.shields.io/badge/Test%20it%20live-DBExplorer%20Login-198754?style=for-the-badge)](https://dev.daves-corner.com/live/DBExplorer)

If there is a feature that could be improved or added, or even just a comment, contact me at [dave@daves-corner.com](mailto:dave@daves-corner.com).
DBExplorer is a PHP web application for browsing, inspecting, and working with databases from a browser. It is designed as a practical developer and administrator tool for connecting to supported database systems, exploring schemas and data, exporting records, comparing tables, running SQL, and generating starter code from database structures.

## Features

- App-level admin login with changeable admin password.
- Database connection screen for supported database engines.
- Database and table browsing.
- Table data viewing with pagination/search support.
- Record insert, edit, and delete tools.
- Raw SQL query screen.
- Dashboard view for the selected database.
- Schema viewer for table structure and primary keys.
- Data export tools.
- Table comparison tool.
- Data migration utility.
- Schema documentation generator.
- PDF documentation export.
- API generator for building starter API code from database tables.
- Model generator for creating structured model classes from table schemas.
- Model generation support for PHP, Python, JavaScript, TypeScript, C#, C++, Java, Go, Ruby, Kotlin, Swift, and Rust.
- Generated models include getters, setters, table metadata, and JSON file output helpers.
- About page with application version and support link.

## Model Generator

The model generator can create model code from a selected table schema. Generated models include typed fields where the target language supports them, getter and setter methods, and a method for writing the current model values to a JSON file.

C++ generation produces two files:

- A `.h` header file with the class declaration.
- A `.cpp` source file with method implementations.

Other languages generate a single source file using the expected file extension for that language.

## Supported Databases

DBExplorer includes database adapter classes for:

- MySQL
- Microsoft SQL Server
- PostgreSQL
- Firebird
- SQLite

Available database support depends on the PHP extensions installed on the server.

## Authentication

The default app login is:

- Username: `admin`
- Password: `admin`

After signing in, use the Admin Password page to change the admin password. The changed password is stored as a bcrypt hash in a PHP-protected file under `includes/`.

## Requirements

- PHP
- A web server such as Apache
- PDO and the PDO drivers needed for the database engines you want to connect to

## Purpose

DBExplorer is meant to make common database exploration and code-generation tasks faster. It is useful when you need a lightweight browser-based tool for inspecting database structure, reviewing table data, generating starter models or APIs, and documenting database schemas.

## Version
1.0.0  Initial release
