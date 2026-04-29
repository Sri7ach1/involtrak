<?php

class Router {
    private $route;
    private $routes = [];
    
    public function __construct() {
        // Obtener la ruta solicitada
        // Soporta tanto ?route=... como /ruta si hay .htaccess
        if (isset($_GET['route']) && !empty($_GET['route'])) {
            $this->route = trim($_GET['route'], '/');
        } elseif (isset($_SERVER['PATH_INFO']) && !empty($_SERVER['PATH_INFO'])) {
            // Para Apache con mod_rewrite
            $this->route = trim($_SERVER['PATH_INFO'], '/');
        } else {
            // Para el servidor PHP incorporado, parsear REQUEST_URI
            $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $script_name = dirname($_SERVER['SCRIPT_NAME']);
            
            // Remover el script_name de request_uri
            if (substr($request_uri, 0, strlen($script_name)) === $script_name) {
                $request_uri = substr($request_uri, strlen($script_name));
            }
            
            $this->route = trim($request_uri, '/');
        }
        
        // Validar que la ruta solo contenga caracteres permitidos (prevenir inyección)
        $this->route = preg_replace('/[^a-zA-Z0-9_\\/-]/', '', $this->route);
    }
    
    /**
     * Registrar una ruta
     */
    public function add($path, $callback) {
        $this->routes[$path] = $callback;
    }
    
    /**
     * Ejecutar el router
     */
    public function dispatch() {
        // Log de debug solo si APP_DEBUG está activo
        if (filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN)) {
            error_log('[ROUTER DEBUG] Ruta detectada: ' . $this->route);
        }
        
        // Ruta por defecto si está vacía
        if (empty($this->route)) {
            $this->route = 'login';
        }
        
        // Logging para depuración (opcional, comentar en producción)
        // error_log("Router: Ruta detectada: " . $this->route);
        
        // Si la ruta existe, ejecutarla
        if (isset($this->routes[$this->route])) {
            call_user_func($this->routes[$this->route]);
        } else {
            // Ruta no encontrada
            http_response_code(404);
            include_once 'templates/head.php';
            ?>
            <div class="content-wrapper">
                <section class="content-header">
                    <div class="container-fluid">
                        <div class="row mb-2">
                            <div class="col-sm-6">
                                <h1>404 - Página no encontrada</h1>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="content">
                    <div class="card">
                        <div class="card-body">
                            <p>La ruta <strong>/<?php echo htmlspecialchars($this->route); ?></strong> no existe.</p>
                            <p><a href="/panel" class="btn btn-primary">Volver al Panel</a></p>
                        </div>
                    </div>
                </section>  
            </div>
            <?php 
            include_once 'templates/foot.php';
        }
    }
}

?>
