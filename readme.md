---

# 🔐 Secure Phone Book – Encrypted User Data with CipherSweet

A PHP phone book application that **optionally encrypts sensitive user data** (`email`, `phone`) at rest using **CipherSweet**, stores it in **MySQL**, and supports **searchable encryption** via blind indexes.

> ✅ **Encryption is enabled only when `CIPHERSWEET_KEY` is set**  
> 📖 **Plaintext mode** (no encryption) is used when the key is empty or unset — ideal for development, testing, or migration.

---

## 🌟 Features

- **Transparent encryption**: Sensitive fields encrypted before DB write, decrypted on read
- **Searchable encryption**: Find users by email/phone without decrypting all data
- **Dual-mode support**:
  - 🔐 **Encrypted mode**: Full CipherSweet protection (production)
  - 📖 **Plaintext mode**: No encryption (development/testing)
- **Modern cryptography**: XChaCha20-Poly1305 AEAD via libsodium
- **Idempotent utilities**: Safely migrate plaintext → encrypted data

---

## 📁 Project Structure

```
/phonebook-app
│
├── .env                     # Environment config (key controls encryption mode)
├── composer.json
├── bootstrap.php
├── config/
│   └── database.php         # PDO connection
├── lib/
│   └── CipherSweetManager.php  # Encryption engine (optional)
├── models/
│   └── SecureUser.php       # Secure DAO (auto-switches mode)
├── examples/
│   ├── create_user.php      # Create user (encrypts if key set)
│   └── find_user.php        # Search + decrypt (or plaintext)
└── utils/
    └── encrypt_decrypt_util.php  # Migration & debugging tools
```

---

## ⚙️ Configuration

### `.env` Controls Encryption Mode

| Setting | Behavior |
|--------|---------|
| `CIPHERSWEET_KEY=64_hex_chars` | 🔐 **Encrypted mode** (e.g., production) |
| `CIPHERSWEET_KEY=` or unset | 📖 **Plaintext mode** (e.g., development) |

> 🔑 Generate a key:  
> ```bash
> php -r "echo bin2hex(random_bytes(32));"
> ```

### Database Schema (MySQL)

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email TEXT NOT NULL,          -- plaintext or CSv1:... encrypted
    phone TEXT NOT NULL,          -- plaintext or CSv1:... encrypted
    email_idx VARBINARY(32) NULL, -- blind index (NULL in plaintext mode)
    phone_idx VARBINARY(32) NULL  -- blind index (NULL in plaintext mode)
);
```

> ✅ Columns `email_idx` and `phone_idx` are **nullable** to support both modes.

---

## ▶️ Usage

### 1. Install Dependencies
```bash
composer install
```

### 2. Set Up `.env`
```env
# Encrypted mode (production)
CIPHERSWEET_KEY=1a2b3c4d...64_hex_chars

# OR plaintext mode (development)
CIPHERSWEET_KEY=
```

### 3. Run Examples
```bash
# Create a user (auto-encrypts if key set)
php examples/create_user.php

# Find a user (auto-decrypts if needed)
php examples/find_user.php
```

---

## 🛠️ Utilities (`utils/`)

### Encrypt Existing Data
Migrate plaintext records to encrypted storage:
```bash
php utils/encrypt_decrypt_util.php --encrypt
```

### Verify Encryption
Show cipher details and decrypt records (debug only!):
```bash
# Show encryption algorithm
php utils/encrypt_decrypt_util.php --cipher-info

# Decrypt and display (⚠️ never in production!)
php utils/encrypt_decrypt_util.php --decrypt
```

> 📌 **Utility scripts respect `.env`**:  
> - If `CIPHERSWEET_KEY` is set → encrypt/decrypt  
> - If empty → operate on plaintext

---

## 🔐 Security Notes

- **Encryption**: Uses **XChaCha20-Poly1305** (256-bit AEAD) via libsodium
- **Key management**: Key never stored in code — only in `.env`
- **Search safety**: Blind indexes prevent plaintext leakage
- **Production use**: Always set `CIPHERSWEET_KEY`; remove `utils/` from production servers

---

## 🧪 Development Workflow

1. **Start in plaintext mode** (`CIPHERSWEET_KEY=`) for easy debugging
2. **Test encryption** by setting a valid key
3. **Migrate data** using `--encrypt` utility
4. **Deploy to production** with key enabled and utilities removed

---

## 📚 Dependencies

- PHP 8.0+
- `ext-sodium` (enabled by default in PHP 7.2+)
- MySQL 5.7+
- Packages:
  - `paragonie/ciphersweet`
  - `vlucas/phpdotenv`

---

> 💡 **Tip**: Use plaintext mode for unit tests, encrypted mode for staging/production!

This design gives you **flexibility without compromising security** — encrypt when it matters, simplify when it doesn’t.