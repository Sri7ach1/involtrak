<?php

/**
 * Controlador: Lista de Clientes
 * Ruta: /clientes
 */
function clientesController() {
    global $con;
    require_once 'models/Cliente.php';
    
    // Verificar autenticación y validar integridad de sesión
    if(!isLoggedIn() || !validateSessionIntegrity()) {
        redirect('login');
    }
    
    // Validar timeout
    validateSessionTimeout();
    
    // Obtener lista de clientes
    $clienteModel = new Cliente($con);
    $clientes = $clienteModel->getAllClientes();
    $stats = $clienteModel->getEstadisticas();
    
    include_once 'templates/head.php';
    ?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Gestión de Clientes</h1>
                </div>
                <div class="col-sm-6">
                    <button class="btn btn-primary float-right" onclick="abrirModalCrear()">
                        <i class="fas fa-user-plus"></i> Nuevo Cliente
                    </button>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Estadísticas -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $stats['total'] ?></h3>
                            <p>Total Clientes</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= $stats['activos'] ?></h3>
                            <p>Clientes Activos</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-user-check"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $stats['inactivos'] ?></h3>
                            <p>Clientes Inactivos</p>
                        </div>
                        <div class="icon">
                            <i class="fas fa-user-times"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <section class="content">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Lista de Clientes</h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" id="searchCliente" class="form-control float-right" placeholder="Buscar cliente...">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap" id="tablaClientes">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre Completo</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($clientes as $cliente): ?>
                        <tr>
                            <td><?= escape($cliente['id']) ?></td>
                            <td><?= escape($cliente['nombre'] . ' ' . $cliente['apellidos']) ?></td>
                            <td><?= escape($cliente['email']) ?></td>
                            <td><?= escape($cliente['telefono']) ?></td>
                            <td>
                                <?php if($cliente['estado'] === 'activo'): ?>
                                    <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="verCliente(<?= $cliente['id'] ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-warning" onclick="editarCliente(<?= $cliente['id'] ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if($cliente['estado'] === 'activo'): ?>
                                    <button class="btn btn-sm btn-secondary" onclick="desactivarCliente(<?= $cliente['id'] ?>)">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-success" onclick="activarCliente(<?= $cliente['id'] ?>)">
                                        <i class="fas fa-check"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<!-- Modal Crear Cliente -->
<div class="modal fade" id="modalCrearCliente" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">Nuevo Cliente</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCrearCliente">
                <div class="modal-body">
                    <?= getCSRFField() ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="crear_nombre">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="crear_nombre" name="nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="crear_apellidos">Apellidos <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="crear_apellidos" name="apellidos" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="crear_direccion">Dirección</label>
                        <input type="text" class="form-control" id="crear_direccion" name="direccion">
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="crear_poblacion">Población</label>
                                <input type="text" class="form-control" id="crear_poblacion" name="poblacion">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="crear_provincia">Provincia <span class="text-danger">*</span></label>
                                <select class="form-control" id="crear_provincia" name="provincia" required>
                                    <option value="">Seleccionar provincia...</option>
                                    <optgroup label="Península y Baleares (IVA 21%)">
                                        <option value="A Coruña">A Coruña</option>
                                        <option value="Álava">Álava</option>
                                        <option value="Albacete">Albacete</option>
                                        <option value="Alicante">Alicante</option>
                                        <option value="Almería">Almería</option>
                                        <option value="Asturias">Asturias</option>
                                        <option value="Ávila">Ávila</option>
                                        <option value="Badajoz">Badajoz</option>
                                        <option value="Barcelona">Barcelona</option>
                                        <option value="Burgos">Burgos</option>
                                        <option value="Cáceres">Cáceres</option>
                                        <option value="Cádiz">Cádiz</option>
                                        <option value="Cantabria">Cantabria</option>
                                        <option value="Castellón">Castellón</option>
                                        <option value="Ciudad Real">Ciudad Real</option>
                                        <option value="Córdoba">Córdoba</option>
                                        <option value="Cuenca">Cuenca</option>
                                        <option value="Girona">Girona</option>
                                        <option value="Granada">Granada</option>
                                        <option value="Guadalajara">Guadalajara</option>
                                        <option value="Guipúzcoa">Guipúzcoa</option>
                                        <option value="Huelva">Huelva</option>
                                        <option value="Huesca">Huesca</option>
                                        <option value="Islas Baleares">Islas Baleares</option>
                                        <option value="Jaén">Jaén</option>
                                        <option value="La Rioja">La Rioja</option>
                                        <option value="León">León</option>
                                        <option value="Lleida">Lleida</option>
                                        <option value="Lugo">Lugo</option>
                                        <option value="Madrid">Madrid</option>
                                        <option value="Málaga">Málaga</option>
                                        <option value="Murcia">Murcia</option>
                                        <option value="Navarra">Navarra</option>
                                        <option value="Ourense">Ourense</option>
                                        <option value="Palencia">Palencia</option>
                                        <option value="Pontevedra">Pontevedra</option>
                                        <option value="Salamanca">Salamanca</option>
                                        <option value="Segovia">Segovia</option>
                                        <option value="Sevilla">Sevilla</option>
                                        <option value="Soria">Soria</option>
                                        <option value="Tarragona">Tarragona</option>
                                        <option value="Teruel">Teruel</option>
                                        <option value="Toledo">Toledo</option>
                                        <option value="Valencia">Valencia</option>
                                        <option value="Valladolid">Valladolid</option>
                                        <option value="Vizcaya">Vizcaya</option>
                                        <option value="Zamora">Zamora</option>
                                        <option value="Zaragoza">Zaragoza</option>
                                    </optgroup>
                                    <optgroup label="Ceuta y Melilla (IPSI 10%)">
                                        <option value="Ceuta">Ceuta</option>
                                        <option value="Melilla">Melilla</option>
                                    </optgroup>
                                    <optgroup label="Canarias (IGIC 7%)">
                                        <option value="Las Palmas">Las Palmas</option>
                                        <option value="Santa Cruz de Tenerife">Santa Cruz de Tenerife</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="crear_codigo_postal">Código Postal</label>
                                <input type="text" class="form-control" id="crear_codigo_postal" name="codigo_postal">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="crear_pais">País</label>
                        <input type="text" class="form-control" id="crear_pais" name="pais" value="España">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="crear_telefono">Teléfono</label>
                                <input type="text" class="form-control" id="crear_telefono" name="telefono">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="crear_email">Email</label>
                                <input type="email" class="form-control" id="crear_email" name="email">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Cliente -->
<div class="modal fade" id="modalEditarCliente" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Editar Cliente</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formEditarCliente">
                <div class="modal-body">
                    <?= getCSRFField() ?>
                    <input type="hidden" id="editar_id" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editar_nombre">Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editar_nombre" name="nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editar_apellidos">Apellidos <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="editar_apellidos" name="apellidos" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="editar_direccion">Dirección</label>
                        <input type="text" class="form-control" id="editar_direccion" name="direccion">
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editar_poblacion">Población</label>
                                <input type="text" class="form-control" id="editar_poblacion" name="poblacion">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editar_provincia">Provincia <span class="text-danger">*</span></label>
                                <select class="form-control" id="editar_provincia" name="provincia" required>
                                    <option value="">Seleccionar provincia...</option>
                                    <optgroup label="Península y Baleares (IVA 21%)">
                                        <option value="A Coruña">A Coruña</option>
                                        <option value="Álava">Álava</option>
                                        <option value="Albacete">Albacete</option>
                                        <option value="Alicante">Alicante</option>
                                        <option value="Almería">Almería</option>
                                        <option value="Asturias">Asturias</option>
                                        <option value="Ávila">Ávila</option>
                                        <option value="Badajoz">Badajoz</option>
                                        <option value="Barcelona">Barcelona</option>
                                        <option value="Burgos">Burgos</option>
                                        <option value="Cáceres">Cáceres</option>
                                        <option value="Cádiz">Cádiz</option>
                                        <option value="Cantabria">Cantabria</option>
                                        <option value="Castellón">Castellón</option>
                                        <option value="Ciudad Real">Ciudad Real</option>
                                        <option value="Córdoba">Córdoba</option>
                                        <option value="Cuenca">Cuenca</option>
                                        <option value="Girona">Girona</option>
                                        <option value="Granada">Granada</option>
                                        <option value="Guadalajara">Guadalajara</option>
                                        <option value="Guipúzcoa">Guipúzcoa</option>
                                        <option value="Huelva">Huelva</option>
                                        <option value="Huesca">Huesca</option>
                                        <option value="Islas Baleares">Islas Baleares</option>
                                        <option value="Jaén">Jaén</option>
                                        <option value="La Rioja">La Rioja</option>
                                        <option value="León">León</option>
                                        <option value="Lleida">Lleida</option>
                                        <option value="Lugo">Lugo</option>
                                        <option value="Madrid">Madrid</option>
                                        <option value="Málaga">Málaga</option>
                                        <option value="Murcia">Murcia</option>
                                        <option value="Navarra">Navarra</option>
                                        <option value="Ourense">Ourense</option>
                                        <option value="Palencia">Palencia</option>
                                        <option value="Pontevedra">Pontevedra</option>
                                        <option value="Salamanca">Salamanca</option>
                                        <option value="Segovia">Segovia</option>
                                        <option value="Sevilla">Sevilla</option>
                                        <option value="Soria">Soria</option>
                                        <option value="Tarragona">Tarragona</option>
                                        <option value="Teruel">Teruel</option>
                                        <option value="Toledo">Toledo</option>
                                        <option value="Valencia">Valencia</option>
                                        <option value="Valladolid">Valladolid</option>
                                        <option value="Vizcaya">Vizcaya</option>
                                        <option value="Zamora">Zamora</option>
                                        <option value="Zaragoza">Zaragoza</option>
                                    </optgroup>
                                    <optgroup label="Ceuta y Melilla (IPSI 10%)">
                                        <option value="Ceuta">Ceuta</option>
                                        <option value="Melilla">Melilla</option>
                                    </optgroup>
                                    <optgroup label="Canarias (IGIC 7%)">
                                        <option value="Las Palmas">Las Palmas</option>
                                        <option value="Santa Cruz de Tenerife">Santa Cruz de Tenerife</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="editar_codigo_postal">Código Postal</label>
                                <input type="text" class="form-control" id="editar_codigo_postal" name="codigo_postal">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="editar_pais">País</label>
                        <input type="text" class="form-control" id="editar_pais" name="pais">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editar_telefono">Teléfono</label>
                                <input type="text" class="form-control" id="editar_telefono" name="telefono">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="editar_email">Email</label>
                                <input type="email" class="form-control" id="editar_email" name="email">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">Actualizar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Cliente -->
<div class="modal fade" id="modalVerCliente" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">Detalles del Cliente</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detallesCliente">
                <!-- Se llenará con JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
    $additionalJS = <<<'JAVASCRIPT'
    <script>
$(document).ready(function() {
    // Eventos de formularios
    $('#formCrearCliente').on('submit', function(e) {
        e.preventDefault();
        
        console.log('Formulario enviado'); // DEBUG
        
        $.ajax({
            url: '/api/cliente/create',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                console.log('Respuesta:', response); // DEBUG
                if(response.success) {
                    toastr.success(response.message);
                    $('#modalCrearCliente').modal('hide');
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', xhr.status, xhr.responseText); // DEBUG
                toastr.error('Error al crear el cliente: ' + xhr.status);
            }
        });
    });

// Ver cliente
window.verCliente = function(id) {
    $.ajax({
        url: '/api/cliente/get?id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(cliente) {
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>ID:</strong> ${cliente.id}</p>
                        <p><strong>Nombre:</strong> ${cliente.nombre}</p>
                        <p><strong>Apellidos:</strong> ${cliente.apellidos}</p>
                        <p><strong>Email:</strong> ${cliente.email || 'N/A'}</p>
                        <p><strong>Teléfono:</strong> ${cliente.telefono || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Dirección:</strong> ${cliente.direccion || 'N/A'}</p>
                        <p><strong>Población:</strong> ${cliente.poblacion || 'N/A'}</p>
                        <p><strong>Provincia:</strong> ${cliente.provincia || 'N/A'}</p>
                        <p><strong>Código Postal:</strong> ${cliente.codigo_postal || 'N/A'}</p>
                        <p><strong>País:</strong> ${cliente.pais || 'N/A'}</p>
                        <p><strong>Estado:</strong> <span class="badge badge-${cliente.estado === 'activo' ? 'success' : 'warning'}">${cliente.estado}</span></p>
                        <p><strong>Creado:</strong> ${cliente.created_at}</p>
                    </div>
                </div>
            `;
            $('#detallesCliente').html(html);
            $('#modalVerCliente').modal('show');
        },
        error: function() {
            toastr.error('Error al cargar los detalles del cliente');
        }
    });
}

// Editar cliente
window.editarCliente = function(id) {
    $.ajax({
        url: '/api/cliente/get?id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(cliente) {
            $('#editar_id').val(cliente.id);
            $('#editar_nombre').val(cliente.nombre);
            $('#editar_apellidos').val(cliente.apellidos);
            $('#editar_direccion').val(cliente.direccion);
            $('#editar_poblacion').val(cliente.poblacion);
            $('#editar_provincia').val(cliente.provincia);
            $('#editar_codigo_postal').val(cliente.codigo_postal);
            $('#editar_pais').val(cliente.pais);
            $('#editar_telefono').val(cliente.telefono);
            $('#editar_email').val(cliente.email);
            $('#modalEditarCliente').modal('show');
        },
        error: function() {
            toastr.error('Error al cargar los datos del cliente');
        }
    });
}

// Desactivar/Activar cliente
window.desactivarCliente = function(id) {
    cambiarEstadoCliente(id, 'inactivo');
}

window.activarCliente = function(id) {
    cambiarEstadoCliente(id, 'activo');
}

window.cambiarEstadoCliente = function(id, estado) {
    $.ajax({
        url: '/api/cliente/delete',
        type: 'POST',
        data: {
            id: id,
            estado: estado,
            csrf_token: $('input[name="csrf_token"]').val()
        },
        dataType: 'json',
        success: function(response) {
            if(response.success) {
                toastr.success(response.message);
                location.reload();
            } else {
                toastr.error(response.message);
            }
        },
        error: function() {
            toastr.error('Error al cambiar el estado del cliente');
        }
    });
}

// Búsqueda de clientes
    $('#searchCliente').on('keyup', function() {
        let value = $(this).val().toLowerCase();
        $('#tablaClientes tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    $('#formEditarCliente').on('submit', function(e) {
        e.preventDefault();
        
        console.log('Formulario editar enviado'); // DEBUG
        
        $.ajax({
            url: '/api/cliente/edit',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function(response) {
                console.log('Respuesta editar:', response); // DEBUG
                if(response.success) {
                    toastr.success(response.message);
                    $('#modalEditarCliente').modal('hide');
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX editar:', xhr.status, xhr.responseText); // DEBUG
                toastr.error('Error al actualizar el cliente: ' + xhr.status);
            }
        });
    });
});

// Funciones globales (fuera de document.ready para que onclick funcione)
window.abrirModalCrear = function() {
    $('#formCrearCliente')[0].reset();
    $('#modalCrearCliente').modal('show');
}
    </script>
JAVASCRIPT;
    
    include_once 'templates/foot.php';
}
?>
