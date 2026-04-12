
# 📚 Biblioteca Digital

Biblioteca Digital é uma aplicação web em Laravel para gerir livros, autores, editoras, requisições, carrinho, checkout e reviews.
O sistema inclui áreas distintas para cidadão e administrador, dashboards dinâmicos, notificações automáticas, emissão de faturas e pesquisa externa no Google Books.

---

## 📋 Funcionalidades

### 📚 Catálogo
- 📘 Registo, consulta, edição e remoção de livros, autores e editoras
- 🔎 Pesquisa avançada por nome, ISBN, autor, editora e bibliografia
- 🧭 Vistas em tabela e em cartões em várias áreas do sistema

### 🛒 Requisições e Checkout
- 🪪 Requisição de livros por cidadãos autenticados
- 📜 Histórico de requisições, estados e confirmação de devoluções
- 🧺 Carrinho de compras com quantidades, portes, IVA e descontos
- 💳 Checkout com morada de entrega, morada de faturação e pagamento via Stripe
- 🧾 Emissão de fatura em PDF para encomendas concluídas

### ✍️ Reviews e Moradas
- ⭐ Submissão de reviews por cidadãos com estado de moderação
- 🧾 Listagem de reviews aprovados, recusados e pendentes
- 🏠 Gestão de múltiplas moradas de entrega/faturação por utilizador

### 🛠️ Administração e Comunicação
- 📊 Dashboard administrativo com métricas e filtros
- 🔔 Notificações para devoluções, confirmações e lembretes de entrega
- 👥 Gestão de utilizadores administradores
- 🔑 Integração com tokens de API Jetstream

### 🌐 Integrações e Exportação
- 🔍 Pesquisa e importação de livros via Google Books
- 📤 Exportação da lista de livros para Excel
- 🔐 Autenticação segura com Jetstream, Sanctum e 2FA

## 🛠️ Tecnologias Utilizadas

- Laravel 12
- Laravel Jetstream
- Laravel Sanctum
- Laravel Livewire 3
- Maatwebsite Excel
- Stripe
- Chart.js
- Tailwind CSS 3.4
- DaisyUI 5
- Vite 7
- MySQL

## ⚙️ Como Executar o Projeto

### ✅ Pré-requisitos
Certifique-se de ter instalado PHP, Composer, Node.js, npm e MySQL.

### 1️⃣ Clonar o repositório
```bash
git clone <url-do-repositorio>
cd Sistema-Biblioteca
```

### 2️⃣ Instalação e configuração automática
Use o script de setup para instalar dependências, preparar o `.env`, gerar a chave, migrar e compilar assets:
```bash
composer run setup
```

### 3️⃣ Configuração manual
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

### 4️⃣ Executar o ambiente de desenvolvimento
```bash
composer run dev
```

### 5️⃣ Aceder à aplicação
Abra http://localhost:8000

## 📁 Estrutura do Projeto

```
📁 biblioteca/
├── 📄 artisan
├── 📄 composer.json
├── 📄 composer.lock
├── 📄 package.json
├── 📄 package-lock.json
├── 📄 phpunit.xml
├── 📄 postcss.config.js
├── 📄 README.md
├── 📄 tailwind.config.js
├── 📄 vite.config.js
├── 📁 app/
│   ├── 📁 Actions/
│   │   ├── 📁 Fortify/
│   │   └── 📁 Jetstream/
│   ├── 📁 Console/
│   │   ├── 📄 Kernel.php
│   │   └── 📁 Commands/
│   ├── 📁 Exceptions/
│   ├── 📁 Exports/
│   │   └── 📄 LivrosExport.php
│   ├── 📁 Helpers/
│   ├── 📁 Http/
│   │   ├── 📁 Controllers/
│   │   └── 📁 Middleware/
│   ├── 📁 Models/
│   ├── 📁 Notifications/
│   ├── 📁 Providers/
│   └── 📁 View/
├── 📁 bootstrap/
│   ├── 📄 app.php
│   └── 📄 providers.php
├── 📁 config/
├── 📁 database/
│   ├── 📁 factories/
│   ├── 📁 migrations/
│   └── 📁 seeders/
├── 📁 docs/
├── 📁 public/
│   ├── 📁 build/
│   ├── 📁 images/
│   ├── 📄 index.php
│   ├── 📄 robots.txt
│   └── 📁 storage/
├── 📁 resources/
│   ├── 📁 css/
│   ├── 📁 js/
│   ├── 📁 markdown/
│   └── 📁 views/
│       ├── 📁 admin/
│       ├── 📁 api/
│       ├── 📁 auth/
│       ├── 📁 autores/
│       ├── 📁 cidadao/
│       ├── 📁 components/
│       ├── 📁 editoras/
│       ├── 📁 layouts/
│       ├── 📁 livros/
│       ├── 📁 profile/
│       ├── 📁 requisicoes/
│       ├── 📁 reviews/
│       └── 📁 vendor/
├── 📁 routes/
│   ├── 📄 api.php
│   ├── 📄 console.php
│   └── 📄 web.php
├── 📁 storage/
├── 📁 tests/
│   ├── 📄 Pest.php
│   ├── 📄 TestCase.php
│   ├── 📁 Feature/
│   └── 📁 Unit/
└── 📁 vendor/
```

## 📝 Observações

- O layout público usa uma página inicial com destaques de livros.
- O menu e os dashboards adaptam-se ao perfil do utilizador autenticado.
- As views de checkout e encomendas já estão preparadas para fatura, moradas e pagamento online.
