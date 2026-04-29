<?php

/**
 * Controlador: Lista de Usuarios
 * Ruta: /usuarios
 */
function usersController() {
    global $con;
    require_once 'models/User.php';
    
    // Verificar autenticación y validar integridad de sesión
    if(!isLoggedIn() || !validateSessionIntegrity()) {
        redirect('login');
    }
    
    // Validar timeout
    validateSessionTimeout();
    
    include_once 'templates/head.php';
    
    // Obtener lista de usuarios
    $userModel = new User($con);
    $users = $userModel->getAllUsers();
    $currentUser = getCurrentUsername();
    ?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Gestión de Usuarios</h1>
                </div>
                <div class="col-sm-6">
                    <button class="btn btn-primary float-right" onclick="abrirModalCrear()">
                        <i class="fas fa-user-plus"></i> Nuevo Usuario
                    </button>
                </div>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Lista de Usuarios</h3>
            </div>
            <div class="card-body p-0">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th><i class="fas fa-user"></i> Nombre de Usuario</th>
                                <th><i class="fas fa-envelope"></i> Mail</th>
                                <th><i class="fas fa-edit"></i> Editar</th>
                                <th><i class="fas fa-trash"></i> Borrar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach($users as $row) {
                                echo "<tr>";
                                echo "<td>" . escape($row['name']) . "</td>";
                                echo "<td>" . escape($row['mail']) . "</td>";
                                
                                // Botón de editar: solo habilitado para el propio usuario
                                if($row['name'] === $currentUser) {
                                    echo "<td><button class='btn btn-warning btn-sm' onclick='abrirModalEditar(" . $row['id'] . ", \"" . escapeAttr($row['name']) . "\", \"" . escapeAttr($row['mail']) . "\")'><i class='fas fa-edit'></i> Editar</button></td>";
                                } else {
                                    echo "<td><button class='btn btn-secondary btn-sm' disabled><i class='fas fa-ban'></i> Editar</button></td>";
                                }
                                
                                // Botón de borrar: deshabilitado para el propio usuario
                                if($row['name'] !== $currentUser) {
                                    echo "<td><button class='btn btn-danger btn-sm' onclick='abrirModalBorrar(" . $row['id'] . ", \"" . escapeAttr($row['name']) . "\")'><i class='fas fa-trash'></i> Borrar</button></td>";
                                } else {
                                    echo "<td><button class='btn btn-secondary btn-sm' disabled><i class='fas fa-ban'></i> Borrar</button></td>";
                                }
                                
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
    </section>
</div>

<!-- Modal de Edición -->
<div class="modal fade" id="modalEditarUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-purple">
                <h4 class="modal-title"><i class="fas fa-edit"></i> Editar Usuario</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="formEditarUsuario">
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">
                    <input type="hidden" name="csrf_token" value="<?php echo escape(generateCSRFToken()); ?>">
                    
                    <div class="form-group">
                        <label for="edit_name">Usuario</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_mail">Correo</label>
                        <input type="email" class="form-control" id="edit_mail" name="mail" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_pass1">Nueva Contraseña</label>
                        <input type="password" class="form-control" id="edit_pass1" name="pass1" placeholder="Dejar en blanco para no cambiar">
                        <small class="form-text text-muted">Solo completa si quieres cambiar la contraseña</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_pass2">Repetir Contraseña</label>
                        <input type="password" class="form-control" id="edit_pass2" name="pass2" placeholder="Dejar en blanco para no cambiar">
                    </div>
                    
                    <div id="mensajeErrorEditar" class="alert alert-danger" style="display:none;"></div>
                    <div id="mensajeExitoEditar" class="alert alert-success" style="display:none;"></div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn bg-purple color-palette">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Crear Usuario -->
<div class="modal fade" id="modalCrearUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h4 class="modal-title"><i class="fas fa-user-plus"></i> Crear Usuario</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <form id="formCrearUsuario">
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo escape(generateCSRFToken()); ?>">
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>Activación de cuenta:</strong> Se enviará un correo al usuario para que establezca su propia contraseña.
                    </div>
                    
                    <div class="form-group">
                        <label for="crear_name">Usuario</label>
                        <input type="text" class="form-control" id="crear_name" name="name" required>
                        <small class="form-text text-muted">Este será el nombre de usuario para iniciar sesión</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="crear_mail">Correo Electrónico</label>
                        <input type="email" class="form-control" id="crear_mail" name="mail" required>
                        <small class="form-text text-muted">Se enviará un enlace de activación a este correo</small>
                    </div>
                    
                    <div id="mensajeErrorCrear" class="alert alert-danger" style="display:none;"></div>
                    <div id="mensajeExitoCrear" class="alert alert-success" style="display:none;"></div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmar Eliminación -->
<div class="modal fade" id="modalBorrarUsuario" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger">
                <h4 class="modal-title"><i class="fas fa-trash"></i> Eliminar Usuario</h4>
                <button type="button" class="close" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="csrf_token_delete" value="<?php echo escape(generateCSRFToken()); ?>">
                <p>¿Estás seguro de que deseas eliminar al usuario <strong id="nombreBorrar"></strong>?</p>
                <p class="text-danger"><small>Esta acción no se puede deshacer.</small></p>
                <div id="mensajeErrorBorrar" class="alert alert-danger" style="display:none;"></div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="confirmarBorrar()"><i class="fas fa-trash"></i> Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Variables globales para almacenar datos del usuario a borrar
    let usuarioABorrarId = null;
    let usuarioABorrarNombre = null;

    // Función para abrir modal de crear usuario
    window.abrirModalCrear = function() {
        document.getElementById('crear_name').value = '';
        document.getElementById('crear_mail').value = '';
        document.getElementById('mensajeErrorCrear').style.display = 'none';
        document.getElementById('mensajeExitoCrear').style.display = 'none';
        
        // Usar setTimeout para asegurar que jQuery esté disponible
        setTimeout(function() {
            if (typeof $ !== 'undefined') {
                $('#modalCrearUsuario').modal('show');
            } else {
                console.error('jQuery no está cargado');
            }
        }, 50);
    }

    // Función para abrir modal de editar usuario
    window.abrirModalEditar = function(id, nombre, email) {
        document.getElementById('edit_id').value = id;
        document.getElementById('edit_name').value = nombre;
        document.getElementById('edit_mail').value = email;
        document.getElementById('edit_pass1').value = '';
        document.getElementById('edit_pass2').value = '';
        document.getElementById('mensajeErrorEditar').style.display = 'none';
        document.getElementById('mensajeExitoEditar').style.display = 'none';
        
        setTimeout(function() {
            if (typeof $ !== 'undefined') {
                $('#modalEditarUsuario').modal('show');
            }
        }, 50);
    }

    // Función para abrir modal de borrar usuario
    window.abrirModalBorrar = function(id, nombre) {
        usuarioABorrarId = id;
        usuarioABorrarNombre = nombre;
        document.getElementById('nombreBorrar').textContent = nombre;
        document.getElementById('mensajeErrorBorrar').style.display = 'none';
        
        setTimeout(function() {
            if (typeof $ !== 'undefined') {
                $('#modalBorrarUsuario').modal('show');
            }
        }, 50);
    }

    // Función para confirmar borrado
    window.confirmarBorrar = function() {
        const formData = new FormData();
        formData.append('id', usuarioABorrarId);
        formData.append('csrf_token', document.getElementById('csrf_token_delete').value);
        
        fetch('api/user/delete', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                setTimeout(function() {
                    if (typeof $ !== 'undefined') {
                        $('#modalBorrarUsuario').modal('hide');
                    }
                }, 50);
                setTimeout(function() {
                    location.reload();
                }, 500);
            } else {
                const msgError = document.getElementById('mensajeErrorBorrar');
                msgError.textContent = data.message;
                msgError.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const msgError = document.getElementById('mensajeErrorBorrar');
            msgError.textContent = 'Error al procesar la solicitud';
            msgError.style.display = 'block';
        });
    }

    // Manejo del formulario de crear usuario
    document.addEventListener('DOMContentLoaded', function() {
        const formCrear = document.getElementById('formCrearUsuario');
        if (formCrear) {
            formCrear.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                document.getElementById('mensajeErrorCrear').style.display = 'none';
                document.getElementById('mensajeExitoCrear').style.display = 'none';
                
                fetch('api/user/create', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const msgExito = document.getElementById('mensajeExitoCrear');
                        msgExito.textContent = data.message;
                        msgExito.style.display = 'block';
                        
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        const msgError = document.getElementById('mensajeErrorCrear');
                        msgError.textContent = data.message;
                        msgError.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const msgError = document.getElementById('mensajeErrorCrear');
                    msgError.textContent = 'Error al procesar la solicitud';
                    msgError.style.display = 'block';
                });
            });
        }

        // Manejo del formulario de editar usuario
        const formEditar = document.getElementById('formEditarUsuario');
        if (formEditar) {
            formEditar.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                
                document.getElementById('mensajeErrorEditar').style.display = 'none';
                document.getElementById('mensajeExitoEditar').style.display = 'none';
                
                fetch('api/user/edit', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        const msgExito = document.getElementById('mensajeExitoEditar');
                        msgExito.textContent = data.message;
                        msgExito.style.display = 'block';
                        
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        const msgError = document.getElementById('mensajeErrorEditar');
                        msgError.textContent = data.message;
                        msgError.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const msgError = document.getElementById('mensajeErrorEditar');
                    msgError.textContent = 'Error al procesar la solicitud';
                    msgError.style.display = 'block';
                });
            });
        }
    });
</script>

<?php 
mysqli_close($con);
include_once 'templates/foot.php'; 
}

?>
