<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Tests de integración con base de datos
 * Requiere base de datos de testing configurada
 */
class DatabaseTest extends TestCase
{
    private $con;
    
    protected function setUp(): void
    {
        // Conexión a base de datos de testing
        global $con;
        $this->con = $con;
        
        // Verificar que estamos en entorno de testing
        $this->assertNotEmpty($this->con, 'Database connection required for integration tests');
    }
    
    /**
     * Test: Conexión a base de datos
     */
    public function testDatabaseConnection()
    {
        $this->assertNotFalse($this->con);
        $this->assertTrue(mysqli_ping($this->con));
    }
    
    /**
     * Test: Tabla usuarios existe
     */
    public function testUsuariosTableExists()
    {
        $result = mysqli_query($this->con, "SHOW TABLES LIKE 'usuarios'");
        $this->assertEquals(1, mysqli_num_rows($result));
    }
    
    /**
     * Test: Tabla login_attempts existe
     */
    public function testLoginAttemptsTableExists()
    {
        $result = mysqli_query($this->con, "SHOW TABLES LIKE 'login_attempts'");
        $this->assertEquals(1, mysqli_num_rows($result));
    }
    
    /**
     * Test: Tabla ingresos existe
     */
    public function testIngresosTableExists()
    {
        $result = mysqli_query($this->con, "SHOW TABLES LIKE 'ingresos'");
        $this->assertEquals(1, mysqli_num_rows($result));
    }
    
    /**
     * Test: Tabla gastos existe
     */
    public function testGastosTableExists()
    {
        $result = mysqli_query($this->con, "SHOW TABLES LIKE 'gastos'");
        $this->assertEquals(1, mysqli_num_rows($result));
    }
    
    /**
     * Test: Prepared statement básico
     */
    public function testPreparedStatement()
    {
        $sql = "SELECT COUNT(*) as total FROM usuarios";
        $stmt = mysqli_prepare($this->con, $sql);
        
        $this->assertNotFalse($stmt, 'Prepared statement should be created');
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        
        $this->assertArrayHasKey('total', $row);
        $this->assertIsNumeric($row['total']);
        
        mysqli_stmt_close($stmt);
    }
}
