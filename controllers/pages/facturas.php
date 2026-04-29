<?php

/**
 * Controlador: Lista de Facturas
 * Ruta: /facturas
 */
function facturasController() {
    global $con;
    require_once 'models/Factura.php';
    
    // Verificar autenticación y validar integridad de sesión
    if(!isLoggedIn() || !validateSessionIntegrity()) {
        redirect('login');
    }
    
    // Validar timeout
    validateSessionTimeout();
    
    // Obtener lista de facturas
    $facturaModel = new Factura($con);
    $facturas = $facturaModel->getAllFacturas();
    $stats = $facturaModel->getEstadisticas();
    
    include_once 'templates/head.php';
    ?>
<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Gestión de Facturas</h1>
                </div>
                <div class="col-sm-6">
                    <button class="btn btn-primary float-right" onclick="abrirModalCrear()">
                        <i class="fas fa-file-invoice"></i> Nueva Factura
                    </button>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Estadísticas -->
    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-info">
                        <div class="inner">
                            <h3><?= $stats['total'] ?></h3>
                            <p>Total Facturas</p>
                            <small>&nbsp;</small>
                        </div>
                        <div class="icon">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-warning">
                        <div class="inner">
                            <h3><?= $stats['pendientes'] ?></h3>
                            <p>Pendientes</p>
                            <small><?= formatPrecio($stats['total_pendiente'] ?? 0) ?></small>
                        </div>
                        <div class="icon">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-success">
                        <div class="inner">
                            <h3><?= $stats['pagadas'] ?></h3>
                            <p>Pagadas</p>
                            <small><?= formatPrecio($stats['total_cobrado'] ?? 0) ?></small>
                        </div>
                        <div class="icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box bg-danger">
                        <div class="inner">
                            <h3><?= $stats['anuladas'] ?></h3>
                            <p>Anuladas</p>
                            <small>&nbsp;</small>
                        </div>
                        <div class="icon">
                            <i class="fas fa-ban"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <section class="content">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Lista de Facturas</h3>
                <div class="card-tools">
                    <div class="input-group input-group-sm" style="width: 250px;">
                        <input type="text" id="searchFactura" class="form-control float-right" placeholder="Buscar factura...">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-default">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover text-nowrap" id="tablaFacturas">
                    <thead>
                        <tr>
                            <th>Nº Factura</th>
                            <th>Cliente</th>
                            <th>Fecha Emisión</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($facturas as $factura): ?>
                        <tr>
                            <td><strong><?= escape($factura['numero_factura']) ?></strong></td>
                            <td><?= escape($factura['cliente_nombre']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($factura['fecha_emision'])) ?></td>
                            <td><strong><?= formatPrecio($factura['total']) ?></strong></td>
                            <td>
                                <?php if($factura['estado'] === 'pendiente'): ?>
                                    <span class="badge badge-warning">Pendiente</span>
                                <?php elseif($factura['estado'] === 'pagada'): ?>
                                    <span class="badge badge-success">Pagada</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Anulada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="verFactura(<?= $factura['id'] ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="generarPDF(<?= $factura['id'] ?>)">
                                    <i class="fas fa-file-pdf"></i>
                                </button>
                                <?php if($factura['estado'] !== 'anulada'): ?>
                                    <div class="btn-group">
                                        <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-toggle="dropdown">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <?php if($factura['estado'] === 'pendiente'): ?>
                                                <a class="dropdown-item" href="#" onclick="cambiarEstado(<?= $factura['id'] ?>, 'pagada'); return false;">
                                                    <i class="fas fa-check text-success"></i> Marcar como Pagada
                                                </a>
                                            <?php endif; ?>
                                            <?php if($factura['estado'] === 'pagada'): ?>
                                                <a class="dropdown-item" href="#" onclick="cambiarEstado(<?= $factura['id'] ?>, 'pendiente'); return false;">
                                                    <i class="fas fa-clock text-warning"></i> Marcar como Pendiente
                                                </a>
                                            <?php endif; ?>
                                            <a class="dropdown-item" href="#" onclick="cambiarEstado(<?= $factura['id'] ?>, 'anulada'); return false;">
                                                <i class="fas fa-ban text-danger"></i> Anular Factura
                                            </a>
                                        </div>
                                    </div>
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

<!-- Modal Crear Factura -->
<div class="modal fade" id="modalCrearFactura" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary">
                <h5 class="modal-title">Nueva Factura</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formCrearFactura">
                <div class="modal-body">
                    <?= getCSRFField() ?>
                    
                    <!-- Selección de Cliente -->
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="cliente_id">Cliente <span class="text-danger">*</span></label>
                                <select class="form-control" id="cliente_id" name="cliente_id" required>
                                    <option value="">Seleccione un cliente...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="porcentaje_impuesto">% Impuesto <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="porcentaje_impuesto" name="porcentaje_impuesto" 
                                           value="21.00" min="0" max="100" step="0.01" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">IVA: 21%, 10% | IPSI: 10% | IGIC: 7%</small>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Líneas de Factura -->
                    <h5>Artículos</h5>
                    <div id="lineasFactura">
                        <!-- Se añadirán dinámicamente -->
                    </div>
                    
                    <button type="button" class="btn btn-sm btn-success mb-3" onclick="agregarLinea()">
                        <i class="fas fa-plus"></i> Agregar Artículo
                    </button>
                    
                    <hr>
                    
                    <!-- Totales -->
                    <div class="row">
                        <div class="col-md-8"></div>
                        <div class="col-md-4">
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Subtotal:</strong></td>
                                    <td class="text-right" id="subtotalDisplay">0,00 €</td>
                                </tr>
                                <tr>
                                    <td><strong>Impuesto (<span id="impuestoPorcentaje">21</span>%):</strong></td>
                                    <td class="text-right" id="impuestoDisplay">0,00 €</td>
                                </tr>
                                <tr class="bg-light">
                                    <td><strong>TOTAL:</strong></td>
                                    <td class="text-right"><strong id="totalDisplay">0,00 €</strong></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Crear Factura</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Factura -->
<div class="modal fade" id="modalVerFactura" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">Detalles de la Factura</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="detallesFactura">
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
let lineasCount = 0;

$(document).ready(function() {
    // Evento submit crear factura
    $('#formCrearFactura').on('submit', function(e) {
        e.preventDefault();
        
        console.log('=== INICIO CREAR FACTURA ==='); // DEBUG
        
        let cliente_id = $('#cliente_id').val();
        let porcentaje_impuesto = parseFloat($('#porcentaje_impuesto').val());
        console.log('Cliente ID:', cliente_id, 'Porcentaje:', porcentaje_impuesto); // DEBUG
        
        if (!cliente_id) {
            toastr.error('Debe seleccionar un cliente');
            return;
        }
        
        if (isNaN(porcentaje_impuesto) || porcentaje_impuesto < 0 || porcentaje_impuesto > 100) {
            toastr.error('El porcentaje de impuesto debe estar entre 0% y 100%');
            return;
        }
        
        // Recopilar líneas
        let lineas = [];
        $('#lineasFactura .card').each(function() {
            let id = $(this).attr('id').split('_')[1];
            let articulo = $(`input[name="linea_articulo_${id}"]`).val();
            let descripcion = $(`input[name="linea_descripcion_${id}"]`).val();
            let cantidad = parseFloat($(`input[name="linea_cantidad_${id}"]`).val());
            let precio_unitario = parseFloat($(`input[name="linea_precio_${id}"]`).val());
            let subtotal = cantidad * precio_unitario;
            
            lineas.push({
                articulo: articulo,
                descripcion: descripcion,
                cantidad: cantidad,
                precio_unitario: precio_unitario,
                subtotal: subtotal
            });
        });
        
        console.log('Líneas recopiladas:', lineas); // DEBUG
        
        if (lineas.length === 0) {
            toastr.error('Debe agregar al menos un artículo');
            return;
        }
        
        console.log('Enviando AJAX...'); // DEBUG
        
        $.ajax({
            url: '/api/factura/create',
            type: 'POST',
            data: {
                cliente_id: cliente_id,
                porcentaje_impuesto: porcentaje_impuesto,
                lineas: JSON.stringify(lineas),
                csrf_token: $('input[name="csrf_token"]').val()
            },
            dataType: 'json',
            success: function(response) {
                console.log('Respuesta recibida:', response); // DEBUG
                if(response.success) {
                    toastr.success(response.message);
                    $('#modalCrearFactura').modal('hide');
                    location.reload();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error AJAX:', xhr.status, xhr.responseText); // DEBUG
                toastr.error('Error al crear la factura: ' + xhr.status);
            }
        });
    });
    
    // Búsqueda de facturas
    $('#searchFactura').on('keyup', function() {
        let value = $(this).val().toLowerCase();
        $('#tablaFacturas tbody tr').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // Detectar cambio de porcentaje de impuesto para recalcular totales
    $(document).on('input change', '#porcentaje_impuesto', function() {
        actualizarTotales();
    });
});

// Funciones globales
window.abrirModalCrear = function() {
    $('#formCrearFactura')[0].reset();
    $('#lineasFactura').html('');
    $('#porcentaje_impuesto').val('21.00'); // Reset a IVA por defecto
    lineasCount = 0;
    actualizarTotales();
    
    // Cargar clientes activos
    $.ajax({
        url: '/api/cliente/activos',
        type: 'GET',
        dataType: 'json',
        success: function(clientes) {
            let options = '<option value="">Seleccione un cliente...</option>';
            clientes.forEach(function(cliente) {
                options += `<option value="${cliente.id}">${cliente.nombre} ${cliente.apellidos}</option>`;
            });
            $('#cliente_id').html(options);
        },
        error: function() {
            toastr.error('Error al cargar clientes');
        }
    });
    
    // Agregar primera línea
    agregarLinea();
    
    $('#modalCrearFactura').modal('show');
}

// Actualizar tipo de impuesto según provincia
// Agregar línea de factura
window.agregarLinea = function() {
    lineasCount++;
    let html = `
        <div class="card card-outline card-primary mb-2" id="linea_${lineasCount}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Artículo</label>
                            <input type="text" class="form-control form-control-sm" name="linea_articulo_${lineasCount}" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Descripción</label>
                            <input type="text" class="form-control form-control-sm" name="linea_descripcion_${lineasCount}" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Cantidad</label>
                            <input type="number" class="form-control form-control-sm linea-cantidad" name="linea_cantidad_${lineasCount}" 
                                   min="0.01" step="0.01" value="1" data-linea="${lineasCount}" required>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <label>Precio Unit.</label>
                            <input type="number" class="form-control form-control-sm linea-precio" name="linea_precio_${lineasCount}" 
                                   min="0" step="0.01" value="0" data-linea="${lineasCount}" required>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <div class="form-group">
                            <label>Subtotal</label>
                            <input type="text" class="form-control form-control-sm" id="linea_subtotal_${lineasCount}" readonly value="0,00 €">
                        </div>
                    </div>
                    <div class="col-md-1">
                        <label>&nbsp;</label>
                        <button type="button" class="btn btn-danger btn-sm btn-block" onclick="eliminarLinea(${lineasCount})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    $('#lineasFactura').append(html);
    
    // Eventos para calcular subtotal
    $(`input[name="linea_cantidad_${lineasCount}"], input[name="linea_precio_${lineasCount}"]`).on('input', calcularSubtotalLinea);
}

// Eliminar línea
window.eliminarLinea = function(id) {
    if ($('#lineasFactura .card').length > 1) {
        $(`#linea_${id}`).remove();
        actualizarTotales();
    } else {
        toastr.warning('Debe haber al menos un artículo');
    }
}

// Calcular subtotal de una línea
window.calcularSubtotalLinea = function() {
    let linea = $(this).data('linea');
    let cantidad = parseFloat($(`input[name="linea_cantidad_${linea}"]`).val()) || 0;
    let precio = parseFloat($(`input[name="linea_precio_${linea}"]`).val()) || 0;
    let subtotal = cantidad * precio;
    
    $(`#linea_subtotal_${linea}`).val(formatearPrecio(subtotal));
    actualizarTotales();
}

// Actualizar totales de la factura
window.actualizarTotales = function() {
    let subtotal = 0;
    
    $('#lineasFactura .card').each(function() {
        let id = $(this).attr('id').split('_')[1];
        let cantidad = parseFloat($(`input[name="linea_cantidad_${id}"]`).val()) || 0;
        let precio = parseFloat($(`input[name="linea_precio_${id}"]`).val()) || 0;
        subtotal += cantidad * precio;
    });
    
    // Obtener porcentaje de impuesto del campo input
    let porcentajeImpuesto = parseFloat($('#porcentaje_impuesto').val()) || 21;
    let impuesto = subtotal * (porcentajeImpuesto / 100);
    let total = subtotal + impuesto;
    
    // Actualizar visualización del porcentaje en el resumen
    $('#impuestoPorcentaje').text(porcentajeImpuesto.toFixed(2));
    
    $('#subtotalDisplay').text(formatearPrecio(subtotal));
    $('#impuestoDisplay').text(formatearPrecio(impuesto));
    $('#totalDisplay').text(formatearPrecio(total));
}

// Formatear precio
window.formatearPrecio = function(valor) {
    return new Intl.NumberFormat('es-ES', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(valor) + ' €';
}

// Ver factura
window.verFactura = function(id) {
    $.ajax({
        url: '/api/factura/get?id=' + id,
        type: 'GET',
        dataType: 'json',
        success: function(factura) {
            let estadoBadge = '';
            if (factura.estado === 'pendiente') {
                estadoBadge = '<span class="badge badge-warning">Pendiente</span>';
            } else if (factura.estado === 'pagada') {
                estadoBadge = '<span class="badge badge-success">Pagada</span>';
            } else {
                estadoBadge = '<span class="badge badge-danger">Anulada</span>';
            }
            
            let lineasHtml = '';
            factura.lineas.forEach(function(linea) {
                lineasHtml += `
                    <tr>
                        <td>${linea.articulo}</td>
                        <td>${linea.descripcion}</td>
                        <td class="text-center">${linea.cantidad}</td>
                        <td class="text-right">${formatearPrecio(linea.precio_unitario)}</td>
                        <td class="text-right"><strong>${formatearPrecio(linea.subtotal)}</strong></td>
                    </tr>
                `;
            });
            
            let html = `
                <div class="row">
                    <div class="col-md-6">
                        <h5>Datos de la Factura</h5>
                        <p><strong>Número:</strong> ${factura.numero_factura}</p>
                        <p><strong>Fecha Emisión:</strong> ${factura.fecha_emision}</p>
                        <p><strong>Estado:</strong> ${estadoBadge}</p>
                        <p><strong>Emitida por:</strong> ${factura.usuario_nombre}</p>
                    </div>
                    <div class="col-md-6">
                        <h5>Cliente</h5>
                        <p><strong>Nombre:</strong> ${factura.cliente_nombre} ${factura.cliente_apellidos}</p>
                        <p><strong>Email:</strong> ${factura.cliente_email || 'N/A'}</p>
                        <p><strong>Teléfono:</strong> ${factura.cliente_telefono || 'N/A'}</p>
                        <p><strong>Dirección:</strong> ${factura.cliente_direccion || 'N/A'}</p>
                    </div>
                </div>
                <hr>
                <h5>Artículos</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr class="bg-light">
                            <th>Artículo</th>
                            <th>Descripción</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-right">Precio Unit.</th>
                            <th class="text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${lineasHtml}
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-right"><strong>Subtotal:</strong></td>
                            <td class="text-right">${formatearPrecio(factura.subtotal)}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-right">
                                <strong>${factura.tipo_impuesto_codigo || 'IVA'} (${factura.porcentaje_impuesto || 21}%):</strong>
                            </td>
                            <td class="text-right">${formatearPrecio(factura.impuesto || factura.iva)}</td>
                        </tr>
                        <tr class="bg-light">
                            <td colspan="4" class="text-right"><strong>TOTAL:</strong></td>
                            <td class="text-right"><strong>${formatearPrecio(factura.total)}</strong></td>
                        </tr>
                    </tfoot>
                </table>
            `;
            
            $('#detallesFactura').html(html);
            $('#modalVerFactura').modal('show');
        },
        error: function() {
            toastr.error('Error al cargar los detalles de la factura');
        }
    });
}

// Cambiar estado de factura
window.cambiarEstado = function(id, estado) {
    // Solo confirmar para anulada
    if (estado === 'anulada') {
        if (!confirm('¿Está seguro de anular esta factura? Esta acción no se puede deshacer fácilmente.')) {
            return;
        }
    }
    
    $.ajax({
        url: '/api/factura/edit',
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
            toastr.error('Error al cambiar el estado de la factura');
        }
    });
}

// Generar PDF (implementaremos esto después)
window.generarPDF = function(id) {
    window.open('/api/factura/pdf?id=' + id, '_blank');
}
    </script>
JAVASCRIPT;
    
    include_once 'templates/foot.php';
}
?>
