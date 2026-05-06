# DBExplorer

[![Website](https://img.shields.io/badge/Website-www.daves--corner.com-0b5cad?style=for-the-badge)](https://www.daves-corner.com)
[![Test it live](https://img.shields.io/badge/Test%20it%20live-DBExplorer%20Login-198754?style=for-the-badge)](https://dev.daves-corner.com/live/DBExplorer)

If there is a feature that could be improved or added, or even just a comment, contact me at [dave@daves-corner.com](mailto:dave@daves-corner.com).

DBExplorer is a browser-based PHP database management and developer utility. It gives developers, administrators, and support teams a lightweight way to connect to a database, inspect its structure, view and edit records, run SQL queries, export data, compare tables, document schemas, and generate starter API or model code from existing tables.

## What It Does

DBExplorer turns a web browser into a practical database workbench. After signing in, you choose a supported database connection and can move through the database without installing a desktop database client. The app helps you understand what tables exist, how they are structured, what data they contain, and how that data can be exported, compared, documented, or used to generate starter application code.

Typical uses include:

- Quickly inspect a database on a server where desktop tools are not available.
- Browse tables and records during development or troubleshooting.
- Run SQL queries from a protected web interface.
- Add, edit, or delete records when maintaining application data.
- Compare table data while checking migrations or environment differences.
- Generate schema documentation for handoff, review, or project notes.
- Export records for backups, reporting, or analysis.
- Generate starter models and API code from existing database tables.

DBExplorer is especially useful for small projects, internal tools, shared hosting environments, and quick database review tasks where a simple PHP-based tool is easier to deploy than a full database administration suite.

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

Supported model generation languages include:

- PHP
- Python
- JavaScript
- TypeScript
- C#
- C++
- Java
- Go
- Ruby
- Kotlin
- Swift
- Rust

C++ generation produces two files:

- A `.h` header file with the class declaration.
- A `.cpp` source file with method implementations.

All other supported languages generate a single source file using the expected file extension for that language.

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

## Docker

DBExplorer includes a PHP 8.3 Apache Docker setup. The container installs PDO drivers for MySQL/MariaDB, PostgreSQL, Firebird, and SQLite.

Build and start the container:

```bash
docker compose up -d --build
```

Then open:

`http://localhost:8082`

Use a different port:

```bash
DBEXPLORER_PORT=8090 docker compose up -d --build
```

From inside the container, databases running on the host machine can usually be reached with:

`host.docker.internal`

For example, use `host.docker.internal` as the host when connecting DBExplorer to a local MySQL, MariaDB, PostgreSQL, or Firebird server running on your computer.

Stop the container:

```bash
docker compose down
```

## Purpose

DBExplorer is meant to make common database exploration and code-generation tasks faster. It is useful when you need a lightweight browser-based tool for inspecting database structure, reviewing table data, generating starter models or APIs, and documenting database schemas.

## Version

1.0.0  Initial release
