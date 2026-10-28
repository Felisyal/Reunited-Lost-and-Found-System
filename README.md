
# Reunited — AI-Assisted Lost and Found Management System

**Reunited** is a web-based lost and found management system for the **Polytechnic University of the Philippines – Parañaque City Campus**. It replaces the campus's logbook, bulletin-board and word-of-mouth process with one central platform where students, faculty and staff can report, search for and claim lost items.

Two AI features make it more than a digital logbook:

- **Semantic Matching**: every approved lost or found report is turned into a vector embedding. The system compares lost reports against found reports by meaning, not exact keywords, and ranks likely matches for an administrator to review.
- **Retrieval-Augmented Generation (RAG) Chatbot**: the *Reunited Assistant* pulls matching item records and FAQ answers from the system before it responds. Its answers are grounded in real campus data instead of the model's general knowledge.

> Developed as an undergraduate thesis project for the Bachelor of Science in Information Technology program, PUP Parañaque City Campus.

---

## Table of Contents

- [Features](#features)
- [How the AI Works](#how-the-ai-works)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Getting Started](#getting-started)
- [Configuration](#configuration)
- [Item Status Lifecycle](#item-status-lifecycle)
- [Security Notes](#security-notes)

---

## Features

### Student Portal
- Register and log in with an official PUP email (`@iskolarngbayan.pup.edu.ph`)
- Report a lost item with its name, description, category, last-seen location, date and an optional photo
- Browse and search all approved lost and found items
- Track the status of submitted reports (**My Reports**)
- Submit claim requests for found items and track them (**My Claims**)
- Comment on item listings
- Ask the Reunited Assistant chatbot for help

### Staff / Faculty Portal
- Report found items with a photo, category, location and date found
- View and manage the items they have turned in (**My Items**)

### Admin Dashboard
- Dashboard overview of reports, items, claims and users
- Approve or reject lost and found reports
- Manage items and add items directly
- **AI-Assisted Matching**: generate embeddings, run the matching engine and review the ranked candidate matches
- See a score breakdown for each match (overall, description, item name, category and location)
- Approve or reject AI-suggested matches
- Process claim requests and mark items as **Ready for Claim** or **Claimed**
- Manage student, staff and admin accounts
- Automatically expire items that pass their expiration date

### Notifications and Accounts
- Styled HTML emails sent over SMTP (PHPMailer): report status updates, "matched" notices to finders, and **Ready for Claim** notices to owners
- Forgot / reset password by email
- "Remember me" login using hashed selector/token pairs (30-day expiry)
- Password strength rules (upper case, lower case, number and special character)
- Admin registration protected by a hashed registration code

---

## How the AI Works

### 1. Semantic Matching

```
Report approved ──► Embedding generated from "item name - description"
                    (OpenAI text-embedding-3-small)
                         │
                         ▼
        Stored as JSON in lost_items / found_items.embedding
                         │
                         ▼
   Admin runs matching ──► every approved Lost × Found pair is scored
                         │
                         ▼
        Pairs scoring ≥ 70% saved to ai_matches as "Pending"
        (a Pending match that drops below 70% becomes "Outdated")
                         │
                         ▼
          Admin reviews ──► Approve (notify users) / Reject
```

The embedding text combines the item name and description, for example `Wallet - black leather with PUP ID`. This lets the model treat names with the same meaning, such as *pitaka* and *wallet*, as similar. Embeddings are created in `admin-pages/generate-item-embedding.php`.

Each lost–found pair gets a **weighted overall score**:

| Component    | Weight | How it is computed                                                                 |
|--------------|:------:|------------------------------------------------------------------------------------|
| Description  | 45%    | Cosine similarity between the two embeddings (item name + description)            |
| Item name    | 20%    | Word matching (*wallet* vs *black leather wallet* = 90%), or `similar_text()` for typos, whichever is higher |
| Category     | 20%    | 100 if the categories match exactly, otherwise 0                                   |
| Location     | 15%    | 100 for an exact match, otherwise PHP `similar_text()` percentage                  |

```
Overall = (Description × 0.45) + (Item name × 0.20) + (Category × 0.20) + (Location × 0.15)
```

Pairs with an overall score of **70% or higher** are flagged as candidate matches. The threshold is set at 70% so real matches are not missed. Every match still needs an administrator's approval before anyone is notified, so a weak suggestion can simply be rejected. The weights and threshold are set in `admin-pages/generate-matches.php`.

Each run recalculates every pair:

| Match status | Meaning                                                                                  |
|--------------|------------------------------------------------------------------------------------------|
| `Pending`    | Scored 70% or higher and waiting for admin review                                        |
| `Approved`   | Confirmed by an admin; both items move to **Ready for Claim**                            |
| `Rejected`   | Declined by an admin                                                                     |
| `Outdated`   | Was Pending, but now scores below 70% (for example after an item was edited). It is hidden from review and returns to `Pending` if its score reaches 70% again |

Only `Pending` matches can be approved or rejected. Approved and rejected matches are never changed by a later run.

### 2. RAG Chatbot (Reunited Assistant)

```
User message
   │
   ├─► FAQ keyword hit?  ──yes──► return the FAQ answer (faqs.php)
   │
   └─► no ─► extract keywords (English + Filipino stop words removed)
                │
                ▼
        Retrieve matching rows from found_items and lost_items
                │
                ▼
   Send retrieved records + rules to OpenAI gpt-4.1-mini (Responses API)
                │
                ▼
     Grounded reply + item cards (name, category, location, status, photo)
```

The assistant answers only questions about lost and found items and politely declines anything else. Retrieval is handled in `assistant.php` and the FAQ knowledge base is in `faqs.php`.

---

## Tech Stack

| Layer        | Technology                                                       |
|--------------|------------------------------------------------------------------|
| Frontend     | HTML, CSS, vanilla JavaScript (responsive for mobile)            |
| Backend      | PHP (mysqli)                                                     |
| Database     | MySQL / MariaDB                                                  |
| AI           | OpenAI `text-embedding-3-small` (embeddings), `gpt-4.1-mini` (chatbot) |
| Email        | PHPMailer 7 over Gmail SMTP (STARTTLS, port 587)                 |
| Config       | vlucas/phpdotenv (`.env`)                                        |
| Dependencies | Composer                                                         |

---

## Project Structure

```
Reunited-Lost-and-Found-System/
├── index.php                 # Landing page, login and registration (student / staff / admin)
├── student-portal.php        # Student portal shell
├── staff-faculty.php         # Staff / faculty portal shell
├── admin-portal.php          # Admin dashboard shell
├── assistant.php             # RAG chatbot endpoint (FAQ + DB retrieval → gpt-4.1-mini)
├── faqs.php                  # FAQ knowledge base used by the chatbot
├── embeddings.php            # generateEmbedding() and cosineSimilarity() helpers
├── forgot_password.php       # Sends password reset emails
├── reset_password.php        # Password reset form
├── script.js                 # Frontend logic for all portals
├── styles.css                # Global styles
├── composer.json / .lock     # PHP dependencies
│
├── config/                   # Blocked from web access by config/.htaccess
│   ├── api_config.example.php   # Template for the OpenAI API key
│   ├── db.php                   # Database credentials (not committed)
│   ├── .env                     # SMTP credentials (not committed)
│   └── audit.php                # logAudit() helper for the audit trail
│
├── student-pages/            # Browse items, my reports, my claims, claims & comments
│   └── uploads/              # Photos attached to lost item reports
├── staff-pages/              # Found item form and "my items"
│   └── uploads/              # Photos attached to found item reports
├── admin-pages/              # Dashboard, reports, items, users, claims,
│                             # embeddings, matching, review, mailer, expiry
│
├── admin-images/  staff-images/  student-images/  image/   # UI assets
└── .gitignore
```

---

## Getting Started

### Prerequisites

- **PHP 8.0+** with the `curl`, `mysqli`, `mbstring` and `openssl` extensions
- **MySQL 5.7+ / MariaDB 10.4+**
- **Composer**
- A local server stack such as **XAMPP**, WAMP or Laragon
- An **OpenAI API key**
- A **Gmail account with an App Password** for sending email

### 1. Clone into your web root

The app expects to be served from `/Reunited/`, so clone it into a folder with that name:

```bash
# XAMPP on Windows
cd C:\xampp\htdocs
git clone https://github.com/Felisyal/Reunited-Lost-and-Found-System.git Reunited
cd Reunited
```

### 2. Install dependencies

```bash
composer install
```

### 3. Set up the database

1. Start Apache and MySQL in the XAMPP Control Panel.
2. Open **phpMyAdmin** (`http://localhost/phpmyadmin`) and create a database named **`reunited_db`**.
3. Import the schema file `database/reunited_db.sql`.

The system uses these tables:

| Table                                      | Purpose                                           |
|--------------------------------------------|---------------------------------------------------|
| `student_register`, `staff_register`, `admin_register` | User accounts for each role           |
| `lost_items`, `found_items`                | Item reports, including status, photo, expiration date and embedding |
| `lost_items_reports`, `found_items_reports`| Report records and their review status            |
| `ai_matches`                               | Candidate matches with overall, description, item name, category and location scores, plus a status (`Pending`, `Approved`, `Rejected`, `Outdated`) |
| `claims_table`                             | Claim requests                                    |
| `item_comments`                            | Comments on item listings                         |
| `remember_tokens`                          | "Remember me" login tokens                        |
| `admin_code`                               | Hashed admin registration code (`admin_reg_code_hash`) |

### 4. Configure secrets

See [Configuration](#configuration) below.

### 5. Run it

Open **http://localhost/Reunited/** in your browser.

---

## Configuration

### OpenAI API key

```bash
cp config/api_config.example.php config/api_config.php
```

Then edit `config/api_config.php`:

```php
define('OPENAI_API_KEY', 'sk-...your key...');
```

### Email (SMTP)

Create a `.env` file in the `config/` folder (`config/.env`):

```env
SMTP_USERNAME=your-account@gmail.com
SMTP_PASSWORD=your-16-character-app-password
```

Use a **Gmail App Password** (Google Account → Security → 2-Step Verification → App passwords), not your normal Gmail password.

### Database connection

Database credentials are in `config/db.php`, which is not committed. The file picks the connection automatically:

- **On `localhost`**: `localhost` / `root` / *(no password)* / `reunited_db`, which matches a fresh XAMPP install
- **On the live server**: the hosting provider's database credentials

Create `config/db.php` with this structure and fill in your own values:

```php
<?php
$isLocal = in_array($_SERVER['SERVER_NAME'] ?? 'localhost', ['localhost', '127.0.0.1'], true);

if ($isLocal) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'reunited_db');
    define('BASE_URL', 'http://localhost/Reunited');
} else {
    define('DB_HOST', 'your-live-db-host');
    define('DB_USER', 'your-live-db-user');
    define('DB_PASS', 'your-live-db-password');
    define('DB_NAME', 'your-live-db-name');
    define('BASE_URL', 'https://your-live-domain');
}
```

### Item expiration (optional)

`admin-pages/expire_items.php` marks items as **Expired** once they pass their `expiration_date`. You can run it on a schedule, for example with Windows Task Scheduler or cron:

```bash
php /path/to/Reunited/admin-pages/expire_items.php
```

> `config/db.php`, `config/api_config.php`, `.env` files, `vendor/`, debug/test scripts and the user `uploads/` folders are listed in `.gitignore`. **Never commit your real keys or passwords.**

---

## Item Status Lifecycle

```
Pending ──► Approved ──► (AI match approved) ──► Ready for Claim ──► Claimed
   │            │
   ▼            ▼
Rejected     Expired (past expiration date)
```

| Status            | Meaning                                                          |
|-------------------|------------------------------------------------------------------|
| `Pending`         | Submitted and waiting for admin review                           |
| `Approved`        | Verified by an admin, visible to users and eligible for matching |
| `Rejected`        | Declined by an admin                                             |
| `Ready for Claim` | Ownership verified; the owner is emailed to pick the item up     |
| `Claimed`         | Returned to its owner                                            |
| `Expired`         | Unclaimed past its expiration date                               |

---

## Security Notes

- Passwords are hashed and must pass strength rules before an account is created.
- "Remember me" tokens are stored as hashes and use a selector/validator pattern.
- Admin self-registration needs a secret registration code, stored only as a hash.
- API keys, database credentials and SMTP credentials are kept out of source control, and the `config/` folder is blocked from web access.
- Photos uploaded by users are not committed to the repository.
- The chatbot receives only item details (name, category, location and status), never reporters' personal contact information. This follows the **Data Privacy Act of 2012 (RA 10173)**.

---

<p align="center">
  Aligned with UN SDG 9 (Industry, Innovation and Infrastructure) and SDG 16 (Peace, Justice and Strong Institutions)
</p>
