<?php

/**
 * Controlador: Gastos
 * Ruta: /gastos
 */
function gastosController() {
    global $con;
    require_once 'models/Gasto.php';

    // Verificar autenticación y validar integridad de sesión
    if(!isLoggedIn() || !validateSessionIntegrity()) {
        redirect('login');
    }
    
    // Validar timeout
    validateSessionTimeout();
    
    $gastoModel = new Gasto($con);
    $gastos = $gastoModel->getAllGastos();
    $periodTypes = $gastoModel->getPeriodTypes();

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
                        <h1><i class="fas fa-minus-circle text-danger"></i> Gestión de Gastos</h1>
                    </div>
                    <div class="col-sm-6">
                        <button class="btn btn-danger float-right" data-toggle="modal" data-target="#modalCrearGasto">
                            <i class="fas fa-plus"></i> Nuevo Gasto
                        </button>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="content">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Lista de Gastos</h3>
                </div>
                <div class="card-body">
                    <table id="tablaGastos" class="table table-bordered table-striped">
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
                            <?php foreach ($gastos as $gasto): ?>
                            <tr id="gasto-<?php echo $gasto['id']; ?>">
                                <td><?php echo date('d/m/Y', strtotime($gasto['fecha'])); ?></td>
                                <td><?php echo escape($gasto['descripcion']); ?></td>
                                <td><?php echo escape($gasto['tipo_periodo'] ?? ''); ?></td>
                                <td class="text-danger font-weight-bold">
                                    <?php echo number_format($gasto['importe'], 2, ',', '.'); ?> €
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="editarGasto(<?php echo $gasto['id']; ?>, '<?php echo $gasto['fecha']; ?>', <?php echo $gasto['importe']; ?>, <?php echo $gasto['period_type_id']; ?>, '<?php echo addslashes($gasto['descripcion']); ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" onclick="eliminarGasto(<?php echo $gasto['id']; ?>)">
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

    <!-- Modal Crear Gasto -->
    <div class="modal fade" id="modalCrearGasto" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger">
                    <h4 class="modal-title"><i class="fas fa-plus"></i> Nuevo Gasto</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form id="formCrearGasto">
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
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Gasto -->
    <div class="modal fade" id="modalEditarGasto" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h4 class="modal-title"><i class="fas fa-edit"></i> Editar Gasto</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form id="formEditarGasto">
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
    console.log('=== GASTOS.PHP JAVASCRIPT CARGADO ===');
    $(document).ready(function() {
        console.log('=== DOCUMENT.READY EJECUTADO ===');
        // Inicializar DataTable
        var table = $('#tablaGastos').DataTable({
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "dom": "<'row'<'col-sm-6 d-flex align-items-end' ><'col-sm-6'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row'<'col-sm-4'i><'col-sm-4 d-flex justify-content-center'p><'col-sm-4 d-flex justify-content-end'l>>",
            "language": {
                "search": "Buscar:",
                "lengthMenu": "Mostrar _MENU_ registros",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ gastos",
                "infoEmpty": "Mostrando 0 a 0 de 0 gastos",
                "infoFiltered": "(filtrado de _MAX_ total gastos)",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                },
                "zeroRecords": "No se encontraron gastos",
                "emptyTable": "No hay gastos registrados"
            },
            "order": [[0, 'desc']]
        });

        // Reestructurar el campo de búsqueda para que el label esté arriba
        var searchHTML = `
            <div style="text-align: right;">
                <div style="display: inline-block; text-align: left;">
                    <label class="mb-1" style="font-weight: bold; display: block;"><i class="fas fa-search"></i> Buscar:</label>
                    <div class="d-flex">
                        <input type="search" class="form-control form-control-sm" placeholder="" aria-controls="tablaGastos" id="search_input_gastos" style="width: 300px;">
                        <button id="btnLimpiarBusqueda" class="btn btn-sm btn-secondary ml-2" title="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#tablaGastos_filter').html(searchHTML);
        
        // Vincular el nuevo input al filtro de DataTables
        $('#tablaGastos_filter input').on('keyup search', function() {
            table.search(this.value).draw();
        });
        
        // Botón limpiar búsqueda
        $('#btnLimpiarBusqueda').on('click', function() {
            $('#search_input_gastos').val('');
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
        $('#tablaGastos_wrapper .row:first .col-sm-6:first').html(filtrosHTML);

        // Filtro personalizado por rango de fechas
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'tablaGastos') {
                    return true;
                }
                
                var fechaDesde = $('#fecha_desde').val();
                var fechaHasta = $('#fecha_hasta').val();
                var fechaGasto = data[0]; // Columna de fecha
                
                if (!fechaDesde && !fechaHasta) {
                    return true;
                }
                
                // Convertir fecha dd/mm/yyyy a yyyy-mm-dd
                var partes = fechaGasto.split('/');
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

        // Enviar formulario crear gasto
        $('#formCrearGasto').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('/api/gasto/create', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: 'Gasto creado correctamente',
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al crear el gasto'
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
        
        // Enviar formulario editar gasto
        $('#formEditarGasto').on('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('/api/gasto/edit', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: 'Gasto actualizado correctamente',
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error al actualizar el gasto'
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
    });

    console.log('=== DEFINIENDO FUNCIONES GLOBALES GASTOS ===');
    window.editarGasto = function(id, fecha, importe, period_type_id, descripcion) {
        document.getElementById('editar_id').value = id;
        document.getElementById('editar_fecha').value = fecha;
        document.getElementById('editar_importe').value = importe;
        document.getElementById('editar_period_type').value = period_type_id;
        document.getElementById('editar_descripcion').value = descripcion;
        $('#modalEditarGasto').modal('show');
    }

    window.eliminarGasto = function(id) {
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
                
                fetch('/api/gasto/delete', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminado',
                            text: 'Gasto eliminado correctamente',
                            timer: 1500
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Error al eliminar el gasto'
                        });
                    }
                });
            }
        });
    }
    console.log('=== FUNCIONES GLOBALES GASTOS DEFINIDAS: editarGasto, eliminarGasto ===');
    </script>
JAVASCRIPT;
    
    mysqli_close($con);
    include_once 'templates/foot.php'; 
}
?>
