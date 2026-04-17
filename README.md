<div align="center">

# 🐱 MindCat API

**API REST do MindCat — app de saúde mental que conecta pacientes e psicólogos**

[![Laravel](https://img.shields.io/badge/Laravel-10-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.1+-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Sanctum](https://img.shields.io/badge/Auth-Sanctum-red)](https://laravel.com/docs/sanctum)
[![Tests](https://img.shields.io/badge/tests-96_passing-success)]()
[![Status](https://img.shields.io/badge/status-em_desenvolvimento-yellow)]()

</div>

---

## 📖 Sobre

API REST construída em **Laravel 10** que alimenta o aplicativo [MindCat](https://github.com/Kuligowskilucas/mindcat). Responsável por autenticação, gestão de pacientes e profissionais, registro de humor, diário criptografado, tarefas clínicas e vínculos com consentimento.

---

## ✨ Funcionalidades

### 🔐 Autenticação
- Registro com role (`patient` ou `pro`)
- Login com tokens via **Laravel Sanctum**
- Logout invalidando token atual
- Reset de senha via código de 6 dígitos enviado por email (com rate limiting e controle de tentativas)

### 👤 Gestão de usuário
- Perfil com preferências (push, IA, barra de progresso)
- Atualização de dados pessoais
- Exclusão de conta
- Senha exclusiva para o diário

### 📊 Funcionalidades clínicas
- **Humor**: registro diário, histórico filtrado por data
- **Diário**: CRUD com senha própria e conteúdo criptografado no banco
- **Tarefas**: profissional cria, paciente marca como concluída
- **Vínculos**: profissional vincula paciente via email, sujeito a consentimento explícito
- **Resumo clínico**: dados agregados do paciente para o profissional vinculado

### 🛡 Segurança
- Senhas com requisitos fortes (mínimo 8, maiúscula, minúscula, número) via **custom rule**
- Rate limiting em rotas sensíveis (login, forgot-password, reset-password)
- Controle de role via middleware
- Isolamento de dados: usuários só acessam próprios recursos
- Conteúdo do diário criptografado no banco via `encrypted` cast

---

## 🛠 Stack técnica

| Categoria | Tecnologia |
|---|---|
| Framework | Laravel 10 |
| Linguagem | PHP 8.1+ |
| Banco | MySQL (produção) / SQLite in-memory (testes) |
| Auth | Laravel Sanctum 3.3 |
| Email | SMTP (Mailtrap em dev, trocável em produção) |
| Testes | PHPUnit 10 com RefreshDatabase |

---

## 🏗 Decisões arquiteturais

Algumas escolhas técnicas que valem destaque:

- **Service layer separada de controllers** — controllers só orquestram requests/responses. Lógica de negócio fica em `app/Services/` (AuthService, PasswordResetService), facilitando teste e reuso.

- **Form Requests centralizam validação** — cada endpoint tem seu próprio `FormRequest` em `app/Http/Requests/`. Nenhuma regra de validação espalhada por controllers.

- **Custom Rule para senha forte** — `app/Rules/StrongPassword.php` implementa `ValidationRule` do Laravel 10 e é reutilizada em register, reset de senha e update de usuário. Uma única fonte de verdade para a política de senhas.

- **Criptografia de conteúdo sensível** — entradas de diário são criptografadas via cast `encrypted` do Eloquent. Mesmo com acesso ao banco, não há conteúdo legível.

- **Rate limiting granular** — `throttle:5,1` no login, `throttle:3,1` no forgot-password, reset-password também limitado. Evita brute force sem depender de CAPTCHA.

- **Reset de senha com bcrypt hash** — o código de 6 dígitos é armazenado como bcrypt hash, não em texto puro. A coluna `code` foi migrada de VARCHAR(6) para VARCHAR(255) para acomodar o hash.

- **Testes usam SQLite in-memory** — tests rodam em segundos. `phpunit.xml` configura `DB_CONNECTION=sqlite` e `DB_DATABASE=:memory:`. Mesma lógica, outra engine, velocidade altíssima.

---

## 🧪 Testes

A suíte cobre 96 cenários nos fluxos críticos:

```bash
php artisan test
```

| Arquivo | Testes | Cobre |
|---|---:|---|
| `AuthTest` | 13 | Registro (roles, validação de senha forte, duplicado), login, logout |
| `PasswordResetTest` | 8 | Envio de código, reset com código certo/errado, expirado, brute force |
| `UserTest` | 8 | Me (com profile, sem hash), update, delete |
| `ProfileTest` | 7 | Show, consent toggle, diary password |
| `DiaryTest` | 8 | CRUD, senha correta/errada, isolamento entre users |
| `MoodTest` | 9 | Store (com descrição, limites 1-5), index (filtro data), delete, isolamento |
| `TaskTest` | 11 | Pro cria, paciente completa, permissões |
| `LinkTest` | 11 | Vincular, consentimento, busca, desvincular |
| `PatientTest` | 5 | Summary (com permissões e consentimento) |

Cada teste valida cenário feliz, erros de validação, permissões negadas e isolamento entre usuários.

---

## 📁 Estrutura do projeto

```
MindCatApi/
├── app/
│   ├── Http/
│   │   ├── Controllers/       # Controllers finos (só orquestram)
│   │   ├── Requests/          # Form Requests para validação
│   │   └── Middleware/        # RoleMiddleware, etc
│   ├── Models/                # Eloquent models
│   ├── Rules/                 # Custom validation rules (StrongPassword)
│   └── Services/              # Regras de negócio
├── database/
│   ├── factories/             # Factories para testes e seeds
│   ├── migrations/
│   └── seeders/               # Dados realistas para dev
├── routes/
│   └── api.php                # Todas as rotas da API
├── tests/
│   └── Feature/               # 96 testes cobrindo a API
└── config/
```

---

## 🚀 Como rodar localmente

### Pré-requisitos
- PHP 8.1+
- Composer
- MySQL (ou PostgreSQL)
- Conta no Mailtrap (para emails em dev)

### Instalação

```bash
# Clonar e instalar dependências
git clone https://github.com/Kuligowskilucas/MindCatApi.git
cd MindCatApi
composer install

# Configurar ambiente
cp .env.example .env
php artisan key:generate

# Ajustar .env com credenciais do banco e Mailtrap:
# DB_DATABASE=mindcat
# DB_USERNAME=root
# DB_PASSWORD=
# MAIL_USERNAME=seu_mailtrap_user
# MAIL_PASSWORD=seu_mailtrap_pass

# Rodar migrations e popular banco
php artisan migrate:fresh --seed

# Iniciar servidor
php artisan serve
```

API disponível em `http://127.0.0.1:8000`.

### Rodar testes

```bash
php artisan test
```

---

## 🧪 Contas criadas pelo seeder

| Tipo | Email | Senha |
|---|---|---|
| Profissional | `pro@mindcat.app` | `Pro12345` |
| Profissional | `pro2@mindcat.app` | `Pro12345` |
| Paciente | `paciente@mindcat.app` | `Paciente123` |
| Paciente | `maria@mindcat.app` | `Paciente123` |
| Paciente | `joao@mindcat.app` | `Paciente123` |

Os 3 primeiros pacientes têm senha de diário `diario123`. Vínculos entre profissionais e pacientes já vêm populados.

---

## 📡 Endpoints principais

### Públicos
```
POST   /api/register                 Criar conta (patient ou pro)
POST   /api/login                    Login
POST   /api/forgot-password          Solicitar código por email
POST   /api/reset-password           Redefinir senha com código
```

### Autenticados (Bearer token)
```
POST   /api/logout                   Invalidar token atual
GET    /api/me                       Usuário + perfil
GET    /api/user                     Dados básicos do usuário
PUT    /api/user/update              Atualizar usuário
DELETE /api/user/delete              Excluir conta

GET    /api/profile                  Perfil
PUT    /api/profile                  Atualizar perfil
PUT    /api/profile/diary-password   Definir senha do diário

POST   /api/diary                    Criar entrada
POST   /api/diary/list               Listar (exige senha)
DELETE /api/diary/{id}               Remover

POST   /api/moods                    Registrar humor
GET    /api/moods                    Listar humor
DELETE /api/moods/{id}               Remover

GET    /api/tasks                    Listar tarefas
PATCH  /api/tasks/{task}/done        Marcar como concluída

GET    /api/my-professionals         Profissionais do paciente
```

### Exclusivas de profissionais
```
POST   /api/links                    Criar vínculo com paciente
GET    /api/patients                 Listar pacientes vinculados
DELETE /api/links/{patientId}        Desvincular
GET    /api/patients/search          Buscar paciente por email
POST   /api/tasks                    Criar tarefa para paciente
GET    /api/patients/{id}/summary    Resumo clínico (exige consentimento)
DELETE /api/tasks/{task}             Remover tarefa
```

---

## 🗺 Roadmap

- [x] CRUD completo de todas as entidades
- [x] Autenticação Sanctum com roles
- [x] Reset de senha via email com bcrypt hash
- [x] 96 testes automatizados
- [x] Senha forte via custom rule
- [x] Seeders com dados realistas
- [ ] Validação de CRP para profissionais
- [ ] Rate limiting mais granular (endpoints sensíveis)
- [ ] Crash reporting (Sentry ou similar)
- [ ] Deploy em produção

---

## 📄 Licença

Projeto pessoal de estudo e portfólio. Todos os direitos reservados.

---

<div align="center">

Feito com 🐱 por **[Lucas Gabriel Kuligowski](https://github.com/Kuligowskilucas)**

</div>