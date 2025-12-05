# 📁 Estrutura Modular - Servidor Magnatas

Bem-vindo à estrutura modular do projeto Servidor Magnatas! Este documento explica como o código está organizado e como adicionar novos módulos.

## 🏗️ Arquitetura Geral

```
Site/
├── modules/                    # Módulos principais (pacotes)
│   ├── MGT-Auth/              # Autenticação
│   ├── MGT-Dashboard/         # Dashboard administrativo
│   ├── MGT-Store/             # Sistema de loja
│   ├── MGT-ServerStatus/      # Status dos servidores
│   ├── MGT-API/               # API REST
│   └── MGT-Utils/             # Utilitários e helpers
├── public/                     # Arquivos públicos (web root)
│   ├── css/                   # Estilos CSS
│   ├── js/                    # Scripts JavaScript
│   └── assets/                # Imagens e recursos
├── config/                     # Configurações
│   └── config.php             # Configuração principal
├── dashboard/                  # Pages do dashboard (views)
├── backend/                    # Compatibilidade com código antigo
├── router.php                  # Router do servidor PHP built-in
└── index.html                  # Home page
```

## 📦 Módulos Disponíveis

### 🔐 MGT-Auth
**Autenticação e gerenciamento de sessão**

```php
// Usar AuthManager
load_module('MGT-Auth', 'AuthManager.php');

use MGT\Auth\AuthManager;

AuthManager::login($user, $pass);
AuthManager::isLoggedIn();
AuthManager::logout();
```

**Arquivos:**
- `AuthManager.php` - Classe principal de autenticação
- `middleware.php` - Middleware para proteger páginas

---

### 📊 MGT-Dashboard
**Interface de administração e gerenciamento**

```php
// Usar DashboardManager
load_module('MGT-Dashboard', 'DashboardManager.php');

use MGT\Dashboard\DashboardManager;

DashboardManager::getStats();
DashboardManager::getSystemInfo();
DashboardManager::getMenuItems();
DashboardManager::getStoreItems();
```

**Arquivos:**
- `DashboardManager.php` - Gerenciador de dashboard

---

### 🛍️ MGT-Store
**Sistema completo de loja online**

```php
// Usar StoreManager
load_module('MGT-Store', 'StoreManager.php');

use MGT\Store\StoreManager;

StoreManager::getProducts();
StoreManager::getCategories();
StoreManager::getCoupons();
StoreManager::getOrders();
StoreManager::getCommunityGoal();
```

**Módulos:**
- Produtos
- Categorias
- Cupons
- Pedidos
- Meta da Comunidade

---

### 🎮 MGT-ServerStatus
**Gerenciamento e status dos servidores Minecraft**

```php
// Usar ServerStatusManager
load_module('MGT-ServerStatus', 'ServerStatusManager.php');

use MGT\ServerStatus\ServerStatusManager;

ServerStatusManager::checkServerStatus('mgt');
ServerStatusManager::checkAllServersStatus();
ServerStatusManager::deliverProductToPlayer('mgt', 'PlayerName', 'product_id');
```

**Servidores:**
- Servidor Magnatas (Original)
- ATM10 (All The Mods 10)
- ATM10 TTS (SkyBlock)

---

### 🔌 MGT-API
**API REST para integração externa**

```php
// Usar APIManager
load_module('MGT-API', 'APIManager.php');

use MGT\API\APIManager;

APIManager::success($data, 'Mensagem');
APIManager::error('Erro', 400);
APIManager::getJSONData();
```

---

### 🛠️ MGT-Utils
**Funções auxiliares e utilitários**

```php
// Usar Utils
load_module('MGT-Utils', 'Utils.php');

use MGT\Utils\Utils;

Utils::sanitize($input);
Utils::formatMoney($value);
Utils::formatDate($date);
Utils::generateUUID();
Utils::redirect('/page');
```

---

## 🚀 Como Usar os Módulos

### 1. Carregar um módulo
```php
// No início do arquivo
require_once '/config/config.php';

// Carrega o módulo
load_module('MGT-Auth', 'AuthManager.php');

// Usa as classes/funções
use MGT\Auth\AuthManager;
AuthManager::login('user', 'pass');
```

### 2. Usar funções wrapper
```php
// Para compatibilidade com código antigo
isLoggedIn();
doLogin($user, $pass);
doLogout();
generateCSRFToken();
verifyCSRFToken($token);
```

---

## 📝 Criando um Novo Módulo

Se você precisa adicionar uma nova funcionalidade, crie um novo módulo seguindo este padrão:

### Passo 1: Criar a pasta
```
modules/MGT-NovoModulo/
```

### Passo 2: Criar a classe principal
```php
<?php
/**
 * MGT-NovoModulo Module
 * Descrição do módulo
 */

namespace MGT\NovoModulo;

class NovoModuloManager {
    public static function funcao() {
        // Implementação
    }
}
?>
```

### Passo 3: Usar em outro arquivo
```php
require_once '/config/config.php';
load_module('MGT-NovoModulo', 'NovoModuloManager.php');

use MGT\NovoModulo\NovoModuloManager;

NovoModuloManager::funcao();
```

---

## 🔄 Fluxo de Autenticação

```
Login → process_login.php 
      → AuthManager::login() 
      → SetSession 
      → Redirect /dashboard/
```

```
Protected Page → middleware.php 
              → AuthManager::isLoggedIn() 
              → Allow/Redirect
```

---

## 📂 Organização de Views (Pages)

As páginas HTML/PHP do dashboard ficam em `/dashboard/`:

```
dashboard/
├── index.php           # Home do dashboard
├── login.php           # Página de login
├── dashboard.css       # Estilos específicos
└── [futuro] /
    ├── loja/
    │   ├── produtos.php
    │   ├── categorias.php
    │   └── cupons.php
    ├── servidores.php
    └── usuarios.php
```

---

## 🎨 Estilo e Assets

- **CSS**: `/public/css/` - Organizar por tema/funcionalidade
- **JS**: `/public/js/` - Scripts modulares
- **Assets**: `/public/assets/` - Imagens e recursos

---

## 🔒 Segurança

- ✅ Autenticação obrigatória via middleware
- ✅ CSRF Token em todos os forms
- ✅ Sanitização de inputs com `Utils::sanitize()`
- ✅ Validação de emails com `Utils::isValidEmail()`
- ❌ Nunca deixe dados sensíveis em logs

---

## 📚 Boas Práticas

1. **Use namespaces** - Evita conflitos de nomes
2. **Crie classes estáticas** - Para funções utilitárias
3. **Use load_module()** - Para carregar dependências
4. **Documente o código** - Use PHPDoc
5. **Separe lógica de apresentação** - Business logic nos modules, HTML nas views
6. **Trate exceções** - Use try/catch quando necessário

---

## 🔗 Migração de Código Antigo

Se você tem código em `/backend/`, migre seguindo este padrão:

**Antes:**
```php
require_once '../backend/simple-auth.php';
isLoggedIn();
```

**Depois:**
```php
require_once '/config/config.php';
load_module('MGT-Auth', 'AuthManager.php');
use MGT\Auth\AuthManager;
AuthManager::isLoggedIn();
```

---

## 🤝 Contribuindo

Ao adicionar novos módulos:
1. Siga a convenção de nomenclatura `MGT-NomeModulo`
2. Crie uma classe manager principal
3. Adicione documentação no README
4. Use namespaces apropriados
5. Mantenha a compatibilidade com código existente

---

## 📞 Suporte

Para dúvidas sobre a estrutura modular:
- Consulte o arquivo de configuração: `/config/config.php`
- Veja exemplos nos módulos existentes
- Adicione novos helpers em `MGT-Utils`

---

**Status:** Estrutura modular em evolução
**Última atualização:** Dezembro 2025
**Versão:** 1.0.0
