<?php

/**
 * Controlador: Ingresos
 * Ruta: /ingresos
 */
function ingresosController() {
    global $con;
    require_once 'models/Ingreso.php';

    // Verificar autenticación y validar integridad de sesión
    if(!isLoggedIn() || !validateSessionIntegrity()) {
        redirect('login');
    }
    
    // Validar timeout
    validateSessionTimeout();
    
    $ingresoModel = new Ingreso($con);
    $ingresos = $ingresoModel->getAllIngresos();
    $periodTypes = $ingresoModel->getPeriodTypes();

    // CSS adicional
    $additionalCSS = '
    <link rel="stylesheet" href="/public/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="/public/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="/public/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css">
    ';

    include_once 'templates/head.php';
    ?>
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-plus-circle text-success"></i> Gestión de Ingresos</h1>
                    </div>
                    <div class="col-sm-6">
                        <button class="btn btn-success float-right" data-toggle="modal" data-target="#modalCrearIngreso">
                            <i class="fas fa-plus"></i> Nuevo Ingreso
                        </button>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="content">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Lista de Ingresos</h3>
                </div>
                <div class="card-body">
                    <table id="tablaIngresos" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Descripción</th>
                                <th>Tipo Periodo</th>
                                <th>Importe</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ingresos as $ingreso): ?>
                            <tr id="ingreso-<?php echo $ingreso['id']; ?>">
                                <td><?php echo date('d/m/Y', strtotime($ingreso['fecha'])); ?></td>
                                <td><?php echo escape($ingreso['descripcion']); ?></td>
                                <td><?php echo escape($ingreso['tipo_periodo'] ?? ''); ?></td>
                                <td class="text-success font-weight-bold">
                                    <?php echo number_format($ingreso['importe'], 2, ',', '.'); ?> €
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editarIngreso(<?php echo $ingreso['id']; ?>, '<?php echo $ingreso['fecha']; ?>', <?php echo $ingreso['importe']; ?>, <?php echo $ingreso['period_type_id']; ?>, '<?php echo addslashes($ingreso['descripcion']); ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarIngreso(<?php echo $ingreso['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <!-- Modal Crear Ingreso -->
    <div class="modal fade" id="modalCrearIngreso" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h4 class="modal-title"><i class="fas fa-plus"></i> Nuevo Ingreso</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form id="formCrearIngreso">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="crear_fecha" name="fecha" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Importe (€) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="crear_importe" name="importe" 
                                   step="0.01" min="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Tipo de Periodo <span class="text-danger">*</span></label>
                            <select class="form-control" id="crear_period_type" name="period_type_id" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($periodTypes as $pt): ?>
                                <option value="<?php echo $pt['id']; ?>">
                                    <?php echo escape($pt['description']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea class="form-control" id="crear_descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Ingreso -->
    <div class="modal fade" id="modalEditarIngreso" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h4 class="modal-title"><i class="fas fa-edit"></i> Editar Ingreso</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form id="formEditarIngreso">
                    <input type="hidden" id="editar_id" name="id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="editar_fecha" name="fecha" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Importe (€) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="editar_importe" name="importe" 
                                   step="0.01" min="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Tipo de Periodo <span class="text-danger">*</span></label>
                            <select class="form-control" id="editar_period_type" name="period_type_id" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($periodTypes as $pt): ?>
                                <option value="<?php echo $pt['id']; ?>">
                                    <?php echo escape($pt['description']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Descripción</label>
                            <textarea class="form-control" id="editar_descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Actualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php 
    // Scripts adicionales
    $csrfToken = generateCSRFToken();
    $additionalJS = <<<JAVASCRIPT
    <script src="/public/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="/public/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="/public/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
    <script src="/public/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
    <script src="/public/plugins/sweetalert2/sweetalert2.min.js"></script>
    
    <script>
    console.log('=== INGRESOS.PHP JAVASCRIPT CARGADO ===');
    $(document).ready(function() {
        console.log('=== DOCUMENT.READY EJECUTADO ===');
        // Inicializar DataTable
        var table = $('#tablaIngresos').DataTable({
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "dom": "<'row'<'col-sm-6 d-flex align-items-end' ><'col-sm-6'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row'<'col-sm-4'i><'col-sm-4 d-flex justify-content-center'p><'col-sm-4 d-flex justify-content-end'l>>",
            "language": {
                "search": "Buscar:",
                "lengthMenu": "Mostrar _MENU_ registros",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ ingresos",
                "infoEmpty": "Mostrando 0 a 0 de 0 ingresos",
                "infoFiltered": "(filtrado de _MAX_ total ingresos)",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                },
                "zeroRecords": "No se encontraron ingresos",
                "emptyTable": "No hay ingresos registrados"
            },
            "order": [[0, 'desc']]
        });

        // Reestructurar el campo de búsqueda para que el label esté arriba
        var searchHTML = `
            <div style="text-align: right;">
                <div style="display: inline-block; text-align: left;">
                    <label class="mb-1" style="font-weight: bold; display: block;"><i class="fas fa-search"></i> Buscar:</label>
                    <div class="d-flex">
                        <input type="search" class="form-control form-control-sm" placeholder="" aria-controls="tablaIngresos" id="search_input_ingresos" style="width: 300px;">
                        <button id="btnLimpiarBusqueda" class="btn btn-sm btn-secondary ml-2" title="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#tablaIngresos_filter').html(searchHTML);
        
        // Vincular el nuevo input al filtro de DataTables
        $('#tablaIngresos_filter input').on('keyup search', function() {
            table.search(this.value).draw();
        });
        
        // Botón limpiar búsqueda
        $('#btnLimpiarBusqueda').on('click', function() {
            $('#search_input_ingresos').val('');
            table.search('').draw();
        });

        // Insertar filtros de fecha a la izquierda
        var filtrosHTML = `
            <div class="d-flex align-items-end">
                <div class="mr-2">
                    <label class="mb-1" style="font-weight: bold;"><i class="fas fa-calendar"></i> Desde:</label>
                    <input type="date" id="fecha_desde" class="form-control form-control-sm" style="width: 150px;">
                </div>
                <div class="mr-2">
                    <label class="mb-1" style="font-weight: bold;"><i class="fas fa-calendar"></i> Hasta:</label>
                    <input type="date" id="fecha_hasta" class="form-control form-control-sm" style="width: 150px;">
                </div>
                <div>
                    <button id="btnLimpiarFiltro" class="btn btn-sm btn-secondary" title="Limpiar filtro" style="margin-bottom: 2px;">
                        <i class="fas fa-eraser"></i>
                    </button>
                </div>
            </div>
        `;
        $('#tablaIngresos_wrapper .row:first .col-sm-6:first').html(filtrosHTML);

        // Filtro personalizado por rango de fechas
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'tablaIngresos') {
                    return true;
                }
                
                var fechaDesde = $('#fecha_desde').val();
                var fechaHasta = $('#fecha_hasta').val();
                var fechaIngreso = data[0]; // Columna de fecha
                
                if (!fechaDesde && !fechaHasta) {
                    return true;
                }
                
                // Convertir fecha dd/mm/yyyy a yyyy-mm-dd
                var partes = fechaIngreso.split('/');
                var fechaComparar = partes[2] + '-' + partes[1] + '-' + partes[0];
                
                if (fechaDesde && fechaComparar < fechaDesde) {
                    return false;
                }
                
                if (fechaHasta && fechaComparar > fechaHasta) {
                    return false;
                }
                
                return true;
            }
        );

        // Aplicar filtro cuando cambian las fechas
        $('#fecha_desde, #fecha_hasta').on('change', function() {
            table.draw();
        });

        // Limpiar filtro
        $('#btnLimpiarFiltro').on('click', function() {
            $('#fecha_desde').val('');
            $('#fecha_hasta').val('');
            table.draw();
        });

        // Establecer fecha actual por defecto
        document.getElementById('crear_fecha').valueAsDate = new Date();

        // Enviar formulario crear ingreso
        $('#formCrearIngreso').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('/api/ingreso/create', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: 'Ingreso creado correctamente',
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al crear el ingreso'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión'
                });
            });
        });

        // Enviar formulario editar ingreso
        $('#formEditarIngreso').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('/api/ingreso/edit', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: 'Ingreso actualizado correctamente',
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al actualizar el ingreso'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error de conexión'
                });
            });
        });
    }); // Cierra $(document).ready()

    // Funciones globales (fuera de document.ready para que onclick funcione)
    console.log('=== DEFINIENDO FUNCIONES GLOBALES INGRESOS ===');
    window.editarIngreso = function(id, fecha, importe, period_type_id, descripcion) {
        document.getElementById('editar_id').value = id;
        document.getElementById('editar_fecha').value = fecha;
        document.getElementById('editar_importe').value = importe;
        document.getElementById('editar_period_type').value = period_type_id;
        document.getElementById('editar_descripcion').value = descripcion;
        $('#modalEditarIngreso').modal('show');
    }

    window.eliminarIngreso = function(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('id', id);
                formData.append('csrf_token', '{$csrfToken}');
                
                fetch('/api/ingreso/delete', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminado',
                            text: 'Ingreso eliminado correctamente',
                            timer: 1500
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Error al eliminar el ingreso'
                        });
                    }
                });
            }
        });
    }
    console.log('=== FUNCIONES GLOBALES INGRESOS DEFINIDAS: editarIngreso, eliminarIngreso ===');
    </script>
JAVASCRIPT;
    
    mysqli_close($con);
    include_once 'templates/foot.php'; 
}
?>
