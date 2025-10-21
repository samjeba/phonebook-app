# Secure Phone Book Application – Project Descriptions

A secure PHP phone book application that encrypts sensitive user data (email, phone) at rest using **CipherSweet**, stores it in **MySQL**, and supports searchable encryption via **blind indexes**.

---

## 📁 Project Root: `/phonebook-app`

```
/phonebook-app
│
├── .env
├── composer.json
├── bootstrap.php
├── config/
│   └── database.php
├── lib/
│   └── CipherSweetManager.php
├── models/
│   └── SecureUser.php
└── examples/
    ├── create_user.php
    └── find_user.php
```

---

### 📄 `.env`
**Environment configuration file** (never committed to version control).  
Contains sensitive settings like:
- Database credentials (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`)
- Encryption key (`CIPHERSWEET_KEY` — a 64-character hex-encoded 256-bit key)  

Used by `vlucas/phpdotenv` to populate `$_ENV`.

---

### 📄 `composer.json`
**PHP dependency and autoloading configuration**.  
- Declares required packages: `paragonie/ciphersweet`, `vlucas/phpdotenv`
- Defines **PSR-4 autoloading** rules:
  - `App\Lib\` → `lib/`
  - `App\Model\` → `models/`
- Ensures classes are autoloaded without manual `require` statements.

---

### 📄 `bootstrap.php`
**Application bootstrap/loader**.  
- Loads Composer’s autoloader (`vendor/autoload.php`)
- Initializes **dotenv** to load `.env` into `$_ENV`
- Included at the top of every script to set up the runtime environment.

---

### 📁 `config/` — Configuration Directory

#### 📄 `config/database.php`
**Database connection factory**.  
- Returns a configured **PDO instance** connected to MySQL
- Uses credentials from `.env`
- Sets secure PDO options (exceptions, fetch mode, no emulation)

---

### 📁 `lib/` — Core Library / Utility Classes

#### 📄 `lib/CipherSweetManager.php`
**Centralized CipherSweet encryption engine manager**.  
- Safely loads and validates the **256-bit hex encryption key** from `.env`
- Provides a singleton-like `CipherSweet` engine instance
- Configures an `EncryptedRow` for the `users` table with:
  - Encrypted fields: `email`, `phone`
  - Blind indexes for searchable encryption (case-normalized email, raw phone)
- Ensures consistent key usage across encryption and decryption.

---

### 📁 `models/` — Data Models (Business Logic)

#### 📄 `models/SecureUser.php`
**Secure user data access object (DAO)**.  
- Wraps database operations with **transparent encryption/decryption**
- Methods:
  - `create($name, $email, $phone)` → encrypts + stores
  - `findByEmail($email)` → searches via blind index + decrypts
  - `findByPhone($phone)` → same for phone
  - `findById($id)` → decrypts full record
- Uses `CipherSweetManager` internally — no crypto logic exposed to app.

---

### 📁 `examples/` — Demo / Test Scripts

#### 📄 `examples/create_user.php`
**Example: Insert a new encrypted user**.  
- Creates a test user (`John Doe`, `john@example.com`, `+1234567890`)
- Demonstrates **encryption at rest**
- Outputs the new user’s database ID.

#### 📄 `examples/find_user.php`
**Example: Search and decrypt a user**.  
- Searches for a user by email (`john@example.com`)
- Uses **blind index** to query encrypted data without decryption
- Fetches and **decrypts sensitive fields** for display
- Proves end-to-end correctness (encrypt → store → search → decrypt).

---

## 🔐 Security Highlights
- **No plaintext secrets** in code (keys in `.env`)
- **All sensitive data encrypted** before DB write
- **Searchable encryption** without leaking plaintext
- **Modern crypto**: libsodium-backed AEAD via CipherSweet
- **Minimal attack surface**: crypto isolated in `lib/` and `models/`

This structure follows **separation of concerns**, avoids code duplication, and ensures security is **baked in**, not bolted on — ideal for both learning and production use.
 
