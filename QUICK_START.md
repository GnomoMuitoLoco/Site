# ⚡ Quick Start - Estrutura Modular

Guia rápido para começar a usar a estrutura modular.

---

## 🚀 Começando (5 minutos)

### 1. Arrancar o servidor
```bash
php -S localhost:8000 router.php
```

### 2. Acessar o painel
```
http://localhost:8000/dashboard/
```

### 3. Fazer login
- **Usuário:** `GnomoMuitoLouco`
- **Senha:** `Brasil2010!`

---

## 📖 Documentação Rápida

### Autenticar um usuário
```php
require_once '/config/config.php';
load_module('MGT-Auth', 'AuthManager.php');

use MGT\Auth\AuthManager;

if (AuthManager::isLoggedIn()) {
    echo "Usuário: " . AuthManager::getUser();
}
```

### Obter dados do dashboard
```php
load_module('MGT-Dashboard', 'DashboardManager.php');
use MGT\Dashboard\DashboardManager;

$stats = DashboardManager::getStats();
echo $stats['status'];      // 'online'
echo $stats['visitors'];    // número aleatório
echo $stats['php_version']; // versão do PHP
```

### Proteger uma página
```php
require_once '/config/config.php';
require_once MODULES_PATH . '/MGT-Auth/middleware.php';
// Se não estiver logado, será redirecionado para login

// Seu código aqui ✅
```

### Sanitizar entrada do usuário
```php
load_module('MGT-Utils', 'Utils.php');
use MGT\Utils\Utils;

$safe_input = Utils::sanitize($_POST['name']);
```

---

## 🎯 Tarefas Comuns

### Adicionar novo item ao menu
1. Vá em `modules/MGT-Dashboard/DashboardManager.php`
2. Edite o método `getMenuItems()`
3. Adicione um novo array com `id`, `label`, `icon`

### Criar um novo módulo
1. Crie pasta: `modules/MGT-SeuModulo/`
2. Crie arquivo: `SeuModuloManager.php`
3. Adicione classe: `class SeuModuloManager { }`
4. Use em outro arquivo:
```php
load_module('MGT-SeuModulo', 'SeuModuloManager.php');
use MGT\SeuModulo\SeuModuloManager;
```

### Adicionar função auxiliar
1. Vá em `modules/MGT-Utils/Utils.php`
2. Adicione método estático público
3. Use em qualquer lugar:
```php
load_module('MGT-Utils', 'Utils.php');
use MGT\Utils\Utils;

$resultado = Utils::meuMetodo($param);
```

---

## 📂 Estrutura em 30 Segundos

```
Site/
├── config/config.php              ← CARREGUE SEMPRE PRIMEIRO
├── modules/                       ← SEUS MÓDULOS
│   ├── MGT-Auth/
│   ├── MGT-Dashboard/
│   ├── MGT-Store/
│   ├── MGT-ServerStatus/
│   ├── MGT-API/
│   └── MGT-Utils/
├── public/                        ← CSS, JS, IMAGENS
├── dashboard/                     ← SUAS PÁGINAS
├── backend/                       ← COMPATIBILIDADE
└── ARCHITECTURE.md               ← DOCUMENTAÇÃO COMPLETA
```

---

## 🔗 Arquivo de Configuração

```php
// config/config.php fornece:

define('ROOT_PATH', dirname(dirname(__FILE__)));
define('MODULES_PATH', ROOT_PATH . '/modules');
define('PUBLIC_PATH', ROOT_PATH . '/public');

function load_module($module_name, $file) {
    // Carrega qualquer módulo com segurança
}

function load_config($file) {
    // Carrega arquivo de config
}

// E muitos mais...
```

---

## ✅ Checklist Rápido

Ao criar/editar código:

- [ ] `require_once /config/config.php` no topo?
- [ ] Usando `load_module()` para dependências?
- [ ] Usando `use` statements?
- [ ] Evitando `require_once` relativo?
- [ ] Código está em uma classe?
- [ ] Classe tem `namespace MGT\...`?

---

## 🆘 Problemas Comuns

### "Failed to open stream: No such file or directory"
**Solução:** Use caminhos absolutos a partir de `ROOT_PATH`
```php
require_once ROOT_PATH . '/modules/...';
// OU
load_module('MGT-Auth', 'AuthManager.php');
```

### "Class not found"
**Solução:** Adicione `use` statement
```php
load_module('MGT-Auth', 'AuthManager.php');
use MGT\Auth\AuthManager;  // ← Necessário!

AuthManager::method();
```

### "Undefined constant 'ROOT_PATH'"
**Solução:** Carregue config.php primeiro
```php
require_once dirname(dirname(__FILE__)) . '/config/config.php';
// Agora ROOT_PATH está disponível
```

---

## 🚀 Próximo Passo

Leia a documentação completa em `ARCHITECTURE.md` para:
- Explicação detalhada de cada módulo
- Padrões de código
- Como escalar o projeto
- Boas práticas profissionais

---

## 📚 Arquivos Principais

| Arquivo | Propósito |
|---------|-----------|
| `config/config.php` | Configuração central e autoloader |
| `ARCHITECTURE.md` | 📖 Documentação completa |
| `ESTRUTURA_MODULAR.md` | 📊 Visão geral da estrutura |
| `MIGRACAO.md` | 🔄 Como migrar código antigo |
| `DIAGRAMA.md` | 🎨 Diagramas visuais |
| `QUICK_START.md` | ⚡ Este arquivo |

---

## 💡 Dica Pro

Use a IDE para autocomplete:
```php
load_module('MGT-Auth', 'AuthManager.php');
use MGT\Auth\AuthManager;

// Ao digitar: AuthManager:: 
// Sua IDE vai sugerir todos os métodos! 🎯
```

---

**Pronto para começar?**

1. Abra o servidor: `php -S localhost:8000 router.php`
2. Acesse: `http://localhost:8000/dashboard/`
3. Leia `ARCHITECTURE.md` para aprofundar
4. Comece a criar seus módulos!

---

*Última atualização: Dezembro 2025*
*Versão: 1.0.0*
