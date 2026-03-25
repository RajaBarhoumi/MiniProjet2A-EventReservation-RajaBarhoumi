# 🎟️ EventRes — Web Application for Event Reservation Management

A full-stack web application built with **Symfony 7**, implementing modern
authentication using **JWT** and **Passkeys (WebAuthn/FIDO2)**.

Developed as part of the FIA3-GL mini project at ISSAT Sousse.

---

## 🧰 Technologies Used

| Layer | Technology |
|---|---|
| Backend | Symfony 7, PHP 8.2 |
| Database | PostgreSQL 15 |
| Authentication | JWT (LexikJWTBundle) + Passkeys (WebAuthn) |
| Frontend | Twig, Bootstrap 5, Vanilla JS |
| Containerization | Docker, Docker Compose |
| Version Control | Git, GitHub |

---

## ✨ Features

### User Side
- Register and login with password or Passkey (biometric/PIN)
- Browse upcoming events
- View event details
- Reserve a spot at an event
- Confirmation page after reservation

### Admin Side
- Secure login (separate from users)
- Dashboard with stats
- Full CRUD on events (with image upload)
- View all reservations per event
- Secure logout

### Security
- JWT tokens for stateless API authentication
- Passkeys (WebAuthn/FIDO2) — passwordless login
- Refresh tokens (30 day validity)
- Role-based access control (ROLE_USER, ROLE_ADMIN)
- CORS configured for API

---

## 🚀 Installation

### Prerequisites
- Docker
- Docker Compose
- Git

### Steps

#### 1. Clone the repository
```bash
git clone https://github.com/RajaBarhoumi/MiniProjet2A-EventReservation-RajaBarhoumi
cd MiniProjet2A-EventReservation-RajaBarhoumi
```

#### 2. Create environment file
```bash
cp .env .env.local
```

Edit `.env.local` with your values:
```bash
DATABASE_URL="postgresql://appuser:apppassword@db:5432/event_reservation?serverVersion=15"
JWT_PASSPHRASE=your_secret_passphrase
APP_DOMAIN=localhost
WEBAUTHN_RP_NAME="Event Reservation App"
```

#### 3. Start Docker containers
```bash
docker compose up -d --build
```

#### 4. Install dependencies
```bash
docker exec -it symfony_php composer install
```

#### 5. Generate JWT keys
```bash
docker exec -it symfony_php bash
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
chmod 600 config/jwt/*.pem
exit
```

#### 6. Run database migrations
```bash
docker exec -it symfony_php php bin/console doctrine:migrations:migrate --no-interaction
```

#### 7. Load test data
```bash
docker exec -it symfony_php php bin/console doctrine:fixtures:load --no-interaction
```

#### 8. Open in browser
```
http://localhost:8080
```

---

## 🔐 Default Credentials

| Role | Username | Password |
|---|---|---|
| Admin | admin | admin123 |

---

## 🧪 Running Tests
```bash
docker exec -it symfony_php php bin/phpunit --testdox
```

---

## 📁 Project Structure
```
src/
├── Controller/
│   ├── Admin/          # Admin controllers
│   ├── Api/            # JWT + Passkey API
│   └── User/           # User-facing controllers
├── Entity/             # Doctrine entities
├── Repository/         # Database queries
├── Service/            # PasskeyAuthService
└── DataFixtures/       # Test data

templates/
├── admin/              # Admin panel templates
└── user/               # User-facing templates

public/js/
└── auth.js             # WebAuthn frontend logic
```

---

## 📚 References

- [Symfony Documentation](https://symfony.com/doc)
- [LexikJWT Bundle](https://github.com/lexik/LexikJWTAuthenticationBundle)
- [WebAuthn Level 2 Spec](https://www.w3.org/TR/webauthn-2/)
- [FIDO Alliance Passkeys](https://fidoalliance.org/passkeys/)