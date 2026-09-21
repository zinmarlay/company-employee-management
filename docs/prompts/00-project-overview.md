You are a senior PHP software architect.

We are planning a professional Company Employee Management System.

The primary learning goal of this project is to develop strong PHP engineering skills suitable for working on advanced real-world PHP projects.

For this task, DO NOT implement any application code.

Create only:

docs/specs/00-project-overview.md

This file will define the master requirements and architecture direction for the entire project.

Individual feature specifications will be created later, one Git branch at a time.

1. Project Name

Company Employee Management System

2. Project Purpose

Build an internal company employee management system.

The company has multiple branches located in different cities.

The system should manage:

- Branches
- Departments
- Employees
- Permanent employees (正社員)
- Dispatched employees (派遣社員)
- Dispatch companies
- Employee contracts
- Employee portfolios
- Skills
- Projects
- Certifications
- System users

This project is also intended as an advanced Pure PHP learning project.

It must demonstrate professional PHP design rather than beginner-style PHP scripts.

3. Technology Direction

Backend and frontend are part of one server-side rendered PHP application.

Use:

- PHP 8.x
- Pure PHP
- MySQL
- PDO
- Composer
- PSR-4 autoloading
- PHP server-side rendering
- HTML5
- CSS
- JavaScript
- Material Design inspired UI
- Git / GitHub

Do NOT use:

- Laravel
- Symfony
- CodeIgniter
- React
- Vue
- PHP ORM

Database access must use PDO.

4. Architecture Direction

The project should gradually implement and study:

- Object-Oriented PHP
- Front Controller pattern
- Custom routing
- MVC principles
- Controllers
- Service layer
- Repository layer
- Models / Domain objects where appropriate
- DTOs where they provide real value
- Middleware
- Dependency Injection principles
- Validation
- Authentication
- Authorization
- Session management
- Exception handling
- Logging
- Configuration management
- PHP Views
- Reusable view components

Do not over-engineer.

Every architectural abstraction must have a clear reason to exist.

The project should help explain:

- Controller responsibility
- Service responsibility
- Repository responsibility
- Where business logic belongs
- Where database logic belongs
- How dependencies flow through the application

5. User Roles

There are two roles.

ADMIN:

- Full CRUD permissions
- Manage employees
- Manage branches
- Manage departments
- Manage dispatch companies
- Manage contracts
- Manage portfolios
- Manage users

USER:

- Read-only access
- Can view permitted system data
- Cannot create
- Cannot update
- Cannot delete

Authorization must be enforced on the server side.

6. Branch Management

The company has multiple branches in different cities.

A branch should contain information such as:

- Branch code
- Branch name
- City
- Address
- Phone
- Status

Required capabilities:

- List
- Detail
- Create
- Update
- Delete
- Search

The system should be able to show employee counts by branch.

7. Department Management

Departments should contain:

- Department code
- Department name
- Description
- Status

Required capabilities:

- List
- Create
- Update
- Delete
- Search

Employees belong to departments.

8. Employee Management

Employee types:

permanent
= 正社員

dispatched
= 派遣社員

Employee information should include:

- Employee number
- Name
- Name Kana
- Email
- Phone
- Photo
- Branch
- Department
- Position
- Employee type
- Hire date
- Status

Required capabilities:

- List
- Detail
- Create
- Update
- Delete
- Search
- Filter
- Sort
- Pagination

9. Employee Search

Users should be able to search employees by:

- Name
- Employee number
- Email

Filters:

- Branch
- Department
- Employee type
- Status

Multiple conditions must be combinable.

Example:

Department = Development
AND
Employee Type = dispatched
AND
Name contains Tanaka

Display:

- Search result count
- Paginated results

Search conditions should remain when navigating pagination.

10. Dispatch Employees

Dispatched employees require additional information.

The system must manage Dispatch Companies separately.

Do not store dispatch company names directly inside the employee record.

Dispatch company information should include:

- Company name
- Contact person
- Email
- Phone
- Address
- Status

11. Employee Contracts

Dispatched employees have employment/dispatch contract information.

Contract information includes:

- Employee
- Dispatch company
- Contract start date
- Contract end date
- Status

The architecture and database design must support:

- Contract renewal
- Contract history
- Multiple historical contracts

Old contracts must not simply be overwritten when a contract is renewed.

12. Contract Expiration Alert

The system must identify contracts as:

- expired
- expiring_soon_7
- expiring_soon_30
- normal

The Dashboard should display:

- Employee
- Dispatch company
- Contract end date
