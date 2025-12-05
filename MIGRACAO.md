# 🔄 Guia de Migração - Código Antigo para Estrutura Modular

Este arquivo ajuda a migrar código antigo para a nova estrutura modular.

---

## 📋 Checklista de Migração

Ao mover código para os módulos, siga esta checklist:

- [ ] Arquivo está em uma classe com namespace
- [ ] Usa `require_once /config/config.php` no início
- [ ] Usa `load_module()` para carregar dependências
- [ ] Usa `use` statement para classes
- [ ] Variáveis globais foram evitadas
- [ ] Código tem comentários PHPDoc
- [ ] Testes foram escritos (se aplicável)
- [ ] Documentação foi atualizada

---

## 🔄 Exemplos de Migração

### Exemplo 1: Autenticação

**ANTES (antigo):**
```php
<?php
require_once '../backend/simple-auth.php';

if (!isLoggedIn()) {
    die('Não autenticado');
}

echo "Bem-vindo " . $_SESSION['username'];
?>
```

**DEPOIS (modular):**
```php
<?php
require_once dirname(dirname(__FILE__)) . '/config/config.php';
load_module('MGT-Auth', 'AuthManager.php');

use MGT\Auth\AuthManager;

AuthManager::initSession();

if (!AuthManager::isLoggedIn()) {
    die('Não autenticado');
}

echo "Bem-vindo " . AuthManager::getUser();
?>
```

---

### Exemplo 2: Obter Dados do Dashboard

**ANTES:**
```php
<?php
require_once '../backend/simple-auth.php';

$status = 'online';
$visitors = rand(100, 1000);
$php_version = phpversion();
$current_time = date('H:i:s');
?>
```

**DEPOIS:**
```php
<?php
require_once '/config/config.php';
load_module('MGT-Dashboard', 'DashboardManager.php');

use MGT\Dashboard\DashboardManager;

$stats = DashboardManager::getStats();
// $stats contém: status, visitors, php_version, current_time, current_date
?>
```

---

### Exemplo 3: Sanitizar Entrada

**ANTES:**
```php
<?php
$input = htmlspecialchars($_POST['name']);
?>
```

**DEPOIS:**
```php
<?php
require_once '/config/config.php';
load_module('MGT-Utils', 'Utils.php');

use MGT\Utils\Utils;

$input = Utils::sanitize($_POST['name']);
?>
```

---

### Exemplo 4: Formatação de Data

**ANTES:**
```php
<?php
$data = date('d/m/Y H:i:s', strtotime($timestamp));
?>
```

**DEPOIS:**
```php
<?php
require_once '/config/config.php';
load_module('MGT-Utils', 'Utils.php');

use MGT\Utils\Utils;

$data = Utils::formatDate($timestamp);
?>
```

---

### Exemplo 5: Criar Novo Módulo

**Passos:**

1. **Criar a pasta:**
   ```
   modules/MGT-NovoModule/
   ```

2. **Criar a classe principal:**
   ```php
   <?php
   // modules/MGT-NovoModule/NovoModuleManager.php
   
   namespace MGT\NovoModule;
   
   class NovoModuleManager {
       public static function meuMetodo($parametro) {
           // Implementação
           return $resultado;
       }
   }
   ?>
   ```

3. **Usar em outro arquivo:**
   ```php
   <?php
   require_once '/config/config.php';
   load_module('MGT-NovoModule', 'NovoModuleManager.php');
   
   use MGT\NovoModule\NovoModuleManager;
   
   $resultado = NovoModuleManager::meuMetodo('valor');
   ?>
   ```

---

## 🔧 Mapeamento de Funções Antigas

| Função Antiga | Novo Módulo | Nova Forma |
|---|---|---|
| `isLoggedIn()` | MGT-Auth | `AuthManager::isLoggedIn()` |
| `doLogin()` | MGT-Auth | `AuthManager::login()` |
| `doLogout()` | MGT-Auth | `AuthManager::logout()` |
| `generateCSRFToken()` | MGT-Auth | `AuthManager::generateCSRFToken()` |
| `verifyCSRFToken()` | MGT-Auth | `AuthManager::verifyCSRFToken()` |
| `htmlspecialchars()` | MGT-Utils | `Utils::sanitize()` |
| `filter_var()` | MGT-Utils | `Utils::isValidEmail()` |
| `date('d/m/Y')` | MGT-Utils | `Utils::formatDate()` |
| `bin2hex(random_bytes())` | MGT-Utils | `Utils::generateUUID()` |

---

## 📂 Estrutura de Módulo Completo

Se você quer criar um módulo mais robusto, use essa estrutura:

```
modules/MGT-NovoModulo/
│
├── NovoModuloManager.php         # Classe principal
├── middleware.php                # Middleware de proteção (se aplicável)
├── models/                       # Modelos de dados
│   ├── Entidade.php
│   └── OutraEntidade.php
├── controllers/                  # Controladores (se aplicável)
│   ├── EntidadeController.php
│   └── OutroController.php
├── views/                        # Views/templates (se aplicável)
│   ├── lista.php
│   └── formulario.php
├── helpers/                      # Funções auxiliares específicas
│   └── novomodulo-helpers.php
└── README.md                     # Documentação do módulo
```

---

## ✅ Checklist de Qualidade

Antes de mover código para módulo, certifique-se:

- [ ] Sem $GLOBALS
- [ ] Sem variáveis globais não declaradas
- [ ] Sem require_once relativos (use /config/config.php)
- [ ] Sem código HTML/PHP misturado
- [ ] Nomes de variáveis descritivos
- [ ] Comentários PHPDoc em métodos públicos
- [ ] Tratamento de erros apropriado
- [ ] Validação de inputs
- [ ] Dados sensíveis não em logs

---

## 🚀 Dicas para Manutenção

1. **Sempre use namespaces** - Evita conflitos
2. **Métodos estáticos para utilitários** - Mais fácil de usar
3. **Métodos de instância para estado** - Se precisa guardar dados
4. **PHPDoc em tudo** - Facilita compreensão
5. **Testes para módulos críticos** - MGT-Auth, MGT-API
6. **Versionamento semântico** - Atualize APP_VERSION quando mudar

---

## 📞 Perguntas Comuns

**P: Preciso atualizar ALL files now?**
R: Não! Os wrappers em AuthManager.php mantêm compatibilidade. Migre gradualmente.

**P: Posso misturar código antigo e novo?**
R: Sim! A estrutura foi design para ser compatível. Mas prefira o código novo.

**P: Como organizo CSS/JS modular?**
R: Crie pastas em /public/css/ e /public/js/ com nomes de módulos:
- public/css/mgt-auth.css
- public/css/mgt-dashboard.css
- public/js/mgt-store.js

**P: E dados sensíveis como senhas?**
R: Nunca coloque em módulos. Use variáveis de ambiente (.env) no futuro.

---

## 📚 Referências

- Arquivo de arquitetura: `ARCHITECTURE.md`
- Config central: `config/config.php`
- Exemplo de módulo: `modules/MGT-Auth/AuthManager.php`
- Documentação modular: `ESTRUTURA_MODULAR.md`

---

**Última atualização:** Dezembro 2025
**Versão:** 1.0.0
