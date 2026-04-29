<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Test de funciones de helpers.php
 */
class HelpersTest extends TestCase
{
    /**
     * Test: Validación de email
     */
    public function testValidateEmail()
    {
        $this->assertTrue(validateEmail('test@example.com'));
        $this->assertTrue(validateEmail('user+tag@domain.co.uk'));
        
        $this->assertFalse(validateEmail('invalid'));
        $this->assertFalse(validateEmail('test@'));
        $this->assertFalse(validateEmail('@example.com'));
    }
    
    /**
     * Test: Escapado de HTML
     */
    public function testEscapeHtml()
    {
        $this->assertEquals('&lt;script&gt;alert(1)&lt;/script&gt;', escape('<script>alert(1)</script>'));
        $this->assertEquals('Test &amp; Company', escape('Test & Company'));
        $this->assertEquals('&quot;quoted&quot;', escape('"quoted"'));
    }
    
    /**
     * Test: Validación de fortaleza de contraseña
     */
    public function testValidatePasswordStrength()
    {
        // Contraseñas válidas (12+ chars, mayúsculas, minúsculas, números, especiales)
        $this->assertTrue(validatePasswordStrength('MyP@ssw0rd123!'));
        $this->assertTrue(validatePasswordStrength('Secure#Pass2024'));
        
        // Contraseñas inválidas
        $this->assertFalse(validatePasswordStrength('short1A!')); // Muy corta
        $this->assertFalse(validatePasswordStrength('nouppercase123!')); // Sin mayúsculas
        $this->assertFalse(validatePasswordStrength('NOLOWERCASE123!')); // Sin minúsculas
        $this->assertFalse(validatePasswordStrength('NoNumbers!@#')); // Sin números
        $this->assertFalse(validatePasswordStrength('NoSpecial123Abc')); // Sin caracteres especiales
    }
    
    /**
     * Test: Generación de token CSRF
     */
    public function testGenerateCSRFToken()
    {
        // Simular sesión
        $_SESSION = [];
        
        $token1 = generateCSRFToken();
        $this->assertNotEmpty($token1);
        $this->assertEquals(64, strlen($token1)); // 32 bytes = 64 hex chars
        
        // Segunda llamada debe retornar el mismo token (mientras no expire)
        $token2 = generateCSRFToken();
        $this->assertEquals($token1, $token2);
    }
    
    /**
     * Test: Verificación de token CSRF
     */
    public function testVerifyCSRFToken()
    {
        $_SESSION = [];
        
        $token = generateCSRFToken();
        
        // Token válido
        $this->assertTrue(verifyCSRFToken($token));
        
        // Token inválido
        $this->assertFalse(verifyCSRFToken('invalid_token'));
        
        // Token vacío
        $this->assertFalse(verifyCSRFToken(''));
    }
    
    /**
     * Test: Hash de datos con HMAC
     */
    public function testHashData()
    {
        $data = 'sensitive_data';
        $hash1 = hashData($data);
        
        $this->assertNotEmpty($hash1);
        $this->assertEquals(64, strlen($hash1)); // SHA-256 = 64 hex chars
        
        // Mismo dato debe generar mismo hash
        $hash2 = hashData($data);
        $this->assertEquals($hash1, $hash2);
        
        // Dato diferente debe generar hash diferente
        $hash3 = hashData('other_data');
        $this->assertNotEquals($hash1, $hash3);
    }
    
    /**
     * Test: Verificación de integridad de datos
     */
    public function testVerifyDataIntegrity()
    {
        $data = 'important_data';
        $hash = hashData($data);
        
        // Hash correcto
        $this->assertTrue(verifyDataIntegrity($data, $hash));
        
        // Hash incorrecto
        $this->assertFalse(verifyDataIntegrity($data, 'wrong_hash'));
        
        // Dato modificado
        $this->assertFalse(verifyDataIntegrity('modified_data', $hash));
    }
    
    /**
     * Test: Validación de nombre de usuario
     */
    public function testValidateUsername()
    {
        // Válidos
        $this->assertTrue(validateUsername('user123'));
        $this->assertTrue(validateUsername('john_doe'));
        $this->assertTrue(validateUsername('admin-user'));
        
        // Inválidos
        $this->assertFalse(validateUsername('ab')); // Muy corto (< 3 chars)
        $this->assertFalse(validateUsername('user@domain')); // Caracteres no permitidos
        $this->assertFalse(validateUsername('user space')); // Espacios
    }
}
