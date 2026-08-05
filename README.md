# Online Result Management and Verification System

**A Case Study of Caleb University, Imota, Lagos**

## Author
Oyeniyi Ayomide David
Matric No: 23/12696
Department of Computer Science, Caleb University, Imota, Lagos

## Project Description
This is a final year project that presents the design and implementation of a web-based Online Result Management and Verification System for tertiary institutions, using Caleb University as a case study. The system automates the computation, submission, approval, storage, and verification of student academic results, while providing a public verification portal that allows third parties (such as employers or other institutions) to confirm the authenticity of a student's result using a unique verification code.

## Key Features
- Four role-based portals: **Admin**, **Lecturer**, **Student**, and **Public Verification**
- Automatic GPA and CGPA computation based on the Nigerian five-point grading scale
- Secure result upload, review, and approval workflow
- Unique alphanumeric verification codes for result authentication
- Role-based access control (RBAC)

## Tech Stack
| Layer | Technology |
|---|---|
| Front-end | HTML5, CSS3, Bootstrap 5, JavaScript |
| Back-end | PHP 8.0 |
| Database | MySQL 8.0 |
| Local Server | XAMPP |
| PDF Generation | FPDF |

## Folder Structure
├── Docs/           → Full project write-up (Chapters 1–5)
├── admin/          → Admin portal pages
├── assets/         → Images, CSS, and static assets
├── config/         → Database connection configuration
├── fpdf/           → FPDF library for PDF generation
├── makefont/       → FPDF font conversion utility
├── student/        → Student portal pages
├── database.sql    → Database schema and structure
├── index.php       → Application entry point
├── login.php       → Login page
├── register.php    → Student registration
└── verify_result.php → Public result verification portal

## Supervisor
Dr. (Mrs.) Ajilore O. Omotayo
