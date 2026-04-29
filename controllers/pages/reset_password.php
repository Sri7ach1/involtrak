<?php

function resetPasswordController() {
    if(isLoggedIn()) {
        header('Location: /panel');
        exit();
    }
    
    $token = isset($_GET['token']) ? $_GET['token'] : '';
    $csrf_token = generateCSRFToken();
    $message = '';
    $messageType = '';
    $validToken = false;
    
    if(empty($token)) {
        header('Location: /login');
        exit();
    }
    
    global $con;
    require_once 'models/User.php';
    
    $userModel = new User($con);
    
    // Verificar si el token es válido
    $tokenData = $userModel->validateResetToken($token);
    
    if(!$tokenData) {
        $message = 'El enlace de recuperación es inválido o ha expirado';
        $messageType = 'danger';
    } else {
        $validToken = true;
        
        if($_SERVER['REQUEST_METHOD'] === 'POST') {
            $csrf_token_post = getPost('csrf_token');
            
            if (!verifyCSRFToken($csrf_token_post)) {
                $message = 'Token de seguridad inválido';
                $messageType = 'danger';
            } else {
                $password = getPost('password');
                $password_confirm = getPost('password_confirm');
                
                if($password !== $password_confirm) {
                    $message = 'Las contraseñas no coinciden';
                    $messageType = 'warning';
                } elseif(!validatePasswordStrength($password)) {
                    $message = 'La contraseña debe tener mínimo 12 caracteres, mayúsculas, minúsculas, números y caracteres especiales';
                    $messageType = 'warning';
                } else {
                    $result = $userModel->resetPassword($token, $password);
                    
                    if($result['success']) {
                        securityLog('INFO', "Password successfully reset for user ID: {$tokenData['user_id']}", ['ip' => $_SERVER['REMOTE_ADDR']]);
                        
                        $message = 'Contraseña actualizada correctamente. Ahora puedes iniciar sesión.';
                        $messageType = 'success';
                        $validToken = false; // Ya no mostrar el formulario
                    } else {
                        $message = $result['message'];
                        $messageType = 'danger';
                    }
                }
            }
        }
    }
    
    include_once 'templates/reset_password.php';
}
?>
