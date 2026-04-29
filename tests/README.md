# Tests Automatizados - Involtrak

## Estructura de Tests

```
tests/
├── bootstrap.php          # Configuración inicial de tests
├── Unit/                  # Tests unitarios (funciones aisladas)
│   └── HelpersTest.php   # Tests de funciones helpers
└── Integration/           # Tests de integración (DB, APIs)
    └── DatabaseTest.php  # Tests de base de datos
```

## Instalación de PHPUnit

```bash
# Instalar Composer (si no está instalado)
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Instalar dependencias del proyecto
cd /var/www/html/fh
composer install
```

## Ejecutar Tests

```bash
# Todos los tests
composer test

# Solo tests unitarios
./vendor/bin/phpunit tests/Unit

# Solo tests de integración
./vendor/bin/phpunit tests/Integration

# Test específico
./vendor/bin/phpunit tests/Unit/HelpersTest.php

# Con cobertura de código (requiere Xdebug)
composer test-coverage
```

## Tests Implementados

### Unit Tests (HelpersTest.php)

✅ **testValidateEmail** - Validación de correos electrónicos
✅ **testEscapeHtml** - Escapado de caracteres HTML peligrosos
✅ **testValidatePasswordStrength** - Validación de contraseñas robustas (12+ chars)
✅ **testGenerateCSRFToken** - Generación de tokens CSRF
✅ **testVerifyCSRFToken** - Verificación de tokens CSRF
✅ **testHashData** - Hashing HMAC-SHA256
✅ **testVerifyDataIntegrity** - Verificación de integridad de datos
✅ **testValidateUsername** - Validación de nombres de usuario

### Integration Tests (DatabaseTest.php)

✅ **testDatabaseConnection** - Conexión a MySQL
✅ **testUsuariosTableExists** - Existencia de tabla usuarios
✅ **testLoginAttemptsTableExists** - Existencia de tabla login_attempts
✅ **testIngresosTableExists** - Existencia de tabla ingresos
✅ **testGastosTableExists** - Existencia de tabla gastos
✅ **testPreparedStatement** - Ejecución de prepared statements

## Añadir Nuevos Tests

### Test Unitario

```php
<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class MiTest extends TestCase
{
    public function testMiFuncion()
    {
        $resultado = miFuncion('input');
        $this->assertEquals('expected', $resultado);
    }
}
```

### Test de Integración

```php
<?php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class MiIntegrationTest extends TestCase
{
    private $con;
    
    protected function setUp(): void
    {
        global $con;
        $this->con = $con;
    }
    
    public function testMiQuery()
    {
        $stmt = mysqli_prepare($this->con, "SELECT * FROM tabla WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        // ...
    }
}
```

## Buenas Prácticas

1. **Nombres descriptivos**: `testNombreClaro()` en vez de `test1()`
2. **Un assertion por test**: Cada test debe validar una cosa específica
3. **Arrange-Act-Assert**: Preparar datos → Ejecutar acción → Verificar resultado
4. **Tests independientes**: No deben depender de orden de ejecución
5. **Cleanup**: Usar `setUp()` y `tearDown()` para preparar/limpiar estado

## Objetivos de Cobertura

- **Objetivo inicial**: 30% (funciones críticas)
- **Objetivo a 3 meses**: 70% (todo excepto templates)
- **Objetivo a 6 meses**: 90% (incluir controllers)

## CI/CD (Futuro)

```yaml
# .github/workflows/tests.yml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: composer test
```
