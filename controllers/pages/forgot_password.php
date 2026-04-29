<?php

function forgotPasswordController() {
    if(isLoggedIn()) {
        header('Location: /panel');
        exit();
    }
    
    $csrf_token = generateCSRFToken();
    $message = '';
    $messageType = '';
    
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        $csrf_token_post = getPost('csrf_token');
        
        if (!verifyCSRFToken($csrf_token_post)) {
            $message = 'Token de seguridad inválido';
            $messageType = 'danger';
        } else {
            $email = getPost('email');
            
            if(!validateEmail($email)) {
                $message = 'Por favor ingresa un email válido';
                $messageType = 'warning';
            } else {
                global $con;
                require_once 'models/User.php';
                
                $userModel = new User($con);
                $result = $userModel->requestPasswordReset($email);
                
                // Siempre mostrar el mismo mensaje para evitar enumeración de usuarios
                $message = 'Si el email existe en nuestro sistema, recibirás un enlace de recuperación en breve.';
                $messageType = 'success';
                
                // Log del evento
                if($result['success']) {
                    securityLog('INFO', "Password reset requested for email: {$email}", ['ip' => $_SERVER['REMOTE_ADDR']]);
                }
            }
        }
    }
    
    include_once 'templates/forgot_password.php';
}
?>
