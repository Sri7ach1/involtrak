<?php

class User {
    private $con;

    public function __construct($connection) {
        $this->con = $connection;
    }

    /**
     * Obtener usuario por nombre
     */
    public function getUserByName($name) {
        $sql = "SELECT id, name, mail, pass FROM usuarios WHERE name = ?";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "s", $name);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $user;
    }

    /**
     * Obtener usuario por ID
     */
    public function getUserById($id) {
        $id = intval($id);
        $sql = "SELECT id, name, mail FROM usuarios WHERE id = ?";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $user;
    }

    /**
     * Obtener todos los usuarios
     */
    public function getAllUsers() {
        $sql = "SELECT id, name, mail FROM usuarios ORDER BY id ASC";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $users = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }
        mysqli_stmt_close($stmt);
        return $users;
    }

    /**
     * Crear nuevo usuario (sin contraseña inicial)
     */
    public function createUser($name, $mail) {
        // Validaciones
        if (empty($name)) {
            return ['success' => false, 'message' => 'El nombre de usuario es obligatorio'];
        }
        if (empty($mail)) {
            return ['success' => false, 'message' => 'El correo es obligatorio'];
        }
        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'El formato del correo no es válido'];
        }

        // Verificar duplicados
        $sql_check = "SELECT id FROM usuarios WHERE name = ? OR mail = ?";
        $query_check = mysqli_prepare($this->con, $sql_check);
        mysqli_stmt_bind_param($query_check, "ss", $name, $mail);
        mysqli_stmt_execute($query_check);
        $result_check = mysqli_stmt_get_result($query_check);

        if (mysqli_num_rows($result_check) > 0) {
            mysqli_stmt_close($query_check);
            return ['success' => false, 'message' => 'El nombre de usuario o correo ya está registrado'];
        }
        mysqli_stmt_close($query_check);

        // Crear usuario con contraseña temporal aleatoria (no podrá usarla hasta activar)
        $tempPassword = bin2hex(random_bytes(32)); // Contraseña temporal que nunca se revelará
        $hash = password_hash($tempPassword, PASSWORD_BCRYPT);
        $sql = "INSERT INTO usuarios (name, mail, pass) VALUES (?, ?, ?)";
        $query = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($query, "sss", $name, $mail, $hash);

        if (mysqli_stmt_execute($query)) {
            $user_id = mysqli_insert_id($this->con);
            mysqli_stmt_close($query);
            
            // Generar token de activación
            $token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours')); // Token válido por 24 horas
            $ip_address = $_SERVER['REMOTE_ADDR'];
            
            // Guardar token en la tabla de password_reset_tokens (reutilizamos la misma tabla)
            $sql_token = "INSERT INTO password_reset_tokens (user_id, token, expires_at, ip_address) VALUES (?, ?, ?, ?)";
            $stmt_token = mysqli_prepare($this->con, $sql_token);
            mysqli_stmt_bind_param($stmt_token, "isss", $user_id, $token, $expires_at, $ip_address);
            
            if (mysqli_stmt_execute($stmt_token)) {
                mysqli_stmt_close($stmt_token);
                
                // Enviar email de activación
                $app_url = rtrim(getenv('APP_URL') ?: 'http://localhost', '/');
                $activation_link = $app_url . "/reset-password?token=" . $token;
                $result = sendAccountActivationEmail($mail, $name, $activation_link, $token);
                
                return ['success' => true, 'message' => 'Usuario creado correctamente. Se ha enviado un correo de activación.', 'token' => $token];
            } else {
                mysqli_stmt_close($stmt_token);
                return ['success' => false, 'message' => 'Error al generar token de activación'];
            }
        } else {
            mysqli_stmt_close($query);
            return ['success' => false, 'message' => 'Error al crear el usuario'];
        }
    }

    /**
     * Editar usuario
     */
    public function updateUser($id, $name, $mail, $password = null) {
        // Validaciones
        if (empty($name)) {
            return ['success' => false, 'message' => 'El nombre de usuario es obligatorio'];
        }
        if (empty($mail)) {
            return ['success' => false, 'message' => 'El correo es obligatorio'];
        }
        if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'El formato del correo no es válido'];
        }

        // Validar contraseña si se proporciona
        if (!empty($password)) {
            if (!validatePasswordStrength($password)) {
                return ['success' => false, 'message' => 'La contraseña debe tener mínimo 12 caracteres, mayúsculas, minúsculas, números y caracteres especiales'];
            }
        }

        // Actualizar con contraseña
        if (!empty($password)) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $sql = "UPDATE usuarios SET name = ?, mail = ?, pass = ? WHERE id = ?";
            $query = mysqli_prepare($this->con, $sql);
            mysqli_stmt_bind_param($query, "sssi", $name, $mail, $hash, $id);
        } else {
            // Actualizar sin contraseña
            $sql = "UPDATE usuarios SET name = ?, mail = ? WHERE id = ?";
            $query = mysqli_prepare($this->con, $sql);
            mysqli_stmt_bind_param($query, "ssi", $name, $mail, $id);
        }

        if (mysqli_stmt_execute($query)) {
            mysqli_stmt_close($query);
            return ['success' => true, 'message' => 'Usuario actualizado correctamente'];
        } else {
            mysqli_stmt_close($query);
            return ['success' => false, 'message' => 'Error al actualizar'];
        }
    }

    /**
     * Eliminar usuario
     */
    public function deleteUser($id) {
        $id = intval($id);

        if ($id <= 0) {
            return ['success' => false, 'message' => 'ID de usuario inválido'];
        }

        // Verificar que exista
        $user = $this->getUserById($id);
        if (!$user) {
            return ['success' => false, 'message' => 'Usuario no encontrado'];
        }

        // Eliminar
        $sql = "DELETE FROM usuarios WHERE id = ?";
        $query = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($query, "i", $id);

        if (mysqli_stmt_execute($query)) {
            mysqli_stmt_close($query);
            return ['success' => true, 'message' => 'Usuario eliminado correctamente'];
        } else {
            mysqli_stmt_close($query);
            return ['success' => false, 'message' => 'Error al eliminar el usuario'];
        }
    }

    /**
     * Verificar contraseña
     */
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Obtener usuario por email
     */
    public function getUserByEmail($email) {
        $sql = "SELECT id, name, mail FROM usuarios WHERE mail = ?";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        return $user;
    }

    /**
     * Solicitar recuperación de contraseña
     */
    public function requestPasswordReset($email) {
        $user = $this->getUserByEmail($email);
        
        if (!$user) {
            // Por seguridad, no revelar si el usuario existe
            return ['success' => true, 'message' => 'Email sent'];
        }

        // Generar token único y seguro
        $token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $ip_address = $_SERVER['REMOTE_ADDR'];

        // Limpiar tokens antiguos o expirados del usuario
        $sql_cleanup = "DELETE FROM password_reset_tokens WHERE user_id = ? OR expires_at < NOW()";
        $stmt_cleanup = mysqli_prepare($this->con, $sql_cleanup);
        mysqli_stmt_bind_param($stmt_cleanup, "i", $user['id']);
        mysqli_stmt_execute($stmt_cleanup);
        mysqli_stmt_close($stmt_cleanup);

        // Guardar token
        $sql = "INSERT INTO password_reset_tokens (user_id, token, expires_at, ip_address) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "isss", $user['id'], $token, $expires_at, $ip_address);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            
            // Enviar email con el enlace de recuperación
            $app_url    = rtrim(getenv('APP_URL') ?: 'http://localhost', '/');
            $reset_link = $app_url . "/reset-password?token=" . $token;
            $result = sendPasswordResetEmail($user['mail'], $user['name'], $reset_link, $token);
            
            return ['success' => true, 'message' => 'Email sent'];
        } else {
            mysqli_stmt_close($stmt);
            return ['success' => false, 'message' => 'Error creating reset token'];
        }
    }

    /**
     * Validar token de recuperación
     */
    public function validateResetToken($token) {
        $sql = "SELECT user_id, expires_at, used FROM password_reset_tokens WHERE token = ? AND used = 0 AND expires_at > NOW()";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "s", $token);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
        
        return $data;
    }

    /**
     * Resetear contraseña con token
     */
    public function resetPassword($token, $new_password) {
        // Validar token
        $tokenData = $this->validateResetToken($token);
        
        if (!$tokenData) {
            return ['success' => false, 'message' => 'Token inválido o expirado'];
        }

        // Validar contraseña
        if (!validatePasswordStrength($new_password)) {
            return ['success' => false, 'message' => 'La contraseña no cumple los requisitos de seguridad'];
        }

        // Actualizar contraseña
        $hash = password_hash($new_password, PASSWORD_BCRYPT);
        $sql = "UPDATE usuarios SET pass = ? WHERE id = ?";
        $stmt = mysqli_prepare($this->con, $sql);
        mysqli_stmt_bind_param($stmt, "si", $hash, $tokenData['user_id']);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            
            // Marcar token como usado
            $sql_mark = "UPDATE password_reset_tokens SET used = 1 WHERE token = ?";
            $stmt_mark = mysqli_prepare($this->con, $sql_mark);
            mysqli_stmt_bind_param($stmt_mark, "s", $token);
            mysqli_stmt_execute($stmt_mark);
            mysqli_stmt_close($stmt_mark);
            
            return ['success' => true, 'message' => 'Password reset successful'];
        } else {
            mysqli_stmt_close($stmt);
            return ['success' => false, 'message' => 'Error updating password'];
        }
    }
}
?>
