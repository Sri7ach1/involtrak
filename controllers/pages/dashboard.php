<?php

/**
 * Controlador: Dashboard
 * Ruta: /panel
 */
function dashboardController() {
    global $con;
    require_once 'models/Ingreso.php';
    require_once 'models/Gasto.php';

    // Verificar autenticación y validar integridad de sesión
    if(!isLoggedIn() || !validateSessionIntegrity()) {
        redirect('login');
    }
    
    // Validar timeout
    validateSessionTimeout();
    
    // Obtener datos económicos
    $ingresoModel = new Ingreso($con);
    $gastoModel = new Gasto($con);
    
    $ingresosMes = $ingresoModel->getIngresosMesActual();
    $gastosMes = $gastoModel->getGastosMesActual();
    $ingresosAno = $ingresoModel->getIngresosAnoActual();
    $gastosAno = $gastoModel->getGastosAnoActual();
    
    $balanceMes = $ingresosMes - $gastosMes;
    $balanceAno = $ingresosAno - $gastosAno;
    
    // CSS adicional
    $additionalCSS = '
    <link rel="stylesheet" href="/public/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="/public/plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
    ';
    
    // Obtener todos los movimientos para la tabla
    $ingresos = $ingresoModel->getAllIngresos();
    $gastos = $gastoModel->getAllGastos();
    
    // Combinar y ordenar por fecha
    $movimientos = [];
    foreach ($ingresos as $ingreso) {
        $movimientos[] = [
            'tipo' => 'ingreso',
            'fecha' => $ingreso['fecha'],
            'concepto' => $ingreso['descripcion'] ?? 'Ingreso',
            'tipo_periodo' => $ingreso['tipo_periodo'] ?? '',
            'importe' => $ingreso['importe'],
            'id' => $ingreso['id']
        ];
    }
    foreach ($gastos as $gasto) {
        $movimientos[] = [
            'tipo' => 'gasto',
            'fecha' => $gasto['fecha'],
            'concepto' => $gasto['descripcion'] ?? 'Gasto',
            'tipo_periodo' => $gasto['tipo_periodo'] ?? '',
            'importe' => $gasto['importe'],
            'id' => $gasto['id']
        ];
    }
    
    // Ordenar por fecha descendente
    usort($movimientos, function($a, $b) {
        return strtotime($b['fecha']) - strtotime($a['fecha']);
    });

    include_once 'templates/head.php';
    ?>
    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><i class="fas fa-chart-line"></i> Balances</h1>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="content">
            <!-- Info boxes -->
            <div class="row">
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box">
                        <span class="info-box-icon bg-success"><i class="fas fa-arrow-up"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Ingresos Mes</span>
                            <span class="info-box-number"><?php echo number_format($ingresosMes, 2, ',', '.'); ?> €</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box">
                        <span class="info-box-icon bg-danger"><i class="fas fa-arrow-down"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Gastos Mes</span>
                            <span class="info-box-number"><?php echo number_format($gastosMes, 2, ',', '.'); ?> €</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box">
                        <span class="info-box-icon bg-info"><i class="fas fa-calendar-alt"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Balance Mes</span>
                            <span class="info-box-number <?php echo $balanceMes >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo number_format($balanceMes, 2, ',', '.'); ?> €
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 col-sm-6 col-12">
                    <div class="info-box">
                        <span class="info-box-icon bg-warning"><i class="fas fa-chart-bar"></i></span>
                        <div class="info-box-content">
                            <span class="info-box-text">Balance Año</span>
                            <span class="info-box-number <?php echo $balanceAno >= 0 ? 'text-success' : 'text-danger'; ?>">
                                <?php echo number_format($balanceAno, 2, ',', '.'); ?> €
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen anual -->
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-success">
                            <h3 class="card-title"><i class="fas fa-plus-circle"></i> Ingresos Año <?php echo date('Y'); ?></h3>
                        </div>
                        <div class="card-body">
                            <h2 class="text-success"><?php echo number_format($ingresosAno, 2, ',', '.'); ?> €</h2>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-danger">
                            <h3 class="card-title"><i class="fas fa-minus-circle"></i> Gastos Año <?php echo date('Y'); ?></h3>
                        </div>
                        <div class="card-body">
                            <h2 class="text-danger"><?php echo number_format($gastosAno, 2, ',', '.'); ?> €</h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de movimientos -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-list"></i> Movimientos</h3>
                </div>
                <div class="card-body">
                    <table id="tablaMovimientos" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Tipo</th>
                                <th>Concepto</th>
                                <th>Periodo</th>
                                <th>Importe</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($movimientos as $mov): ?>
                            <tr>
                                <td data-order="<?php echo strtotime($mov['fecha']); ?>"><?php echo date('d/m/Y', strtotime($mov['fecha'])); ?></td>
                                <td>
                                    <?php if ($mov['tipo'] === 'ingreso'): ?>
                                        <span class="badge badge-success"><i class="fas fa-arrow-up"></i> Ingreso</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><i class="fas fa-arrow-down"></i> Gasto</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo escape($mov['concepto']); ?></td>
                                <td><?php echo escape($mov['tipo_periodo']); ?></td>
                                <td class="<?php echo $mov['tipo'] === 'ingreso' ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $mov['tipo'] === 'ingreso' ? '+' : '-'; ?>
                                    <?php echo number_format($mov['importe'], 2, ',', '.'); ?> €
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>  
    </div>

    <?php 
    // Scripts adicionales
    $additionalJS = <<<'JAVASCRIPT'
    <script src="/public/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="/public/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="/public/plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
    <script src="/public/plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
    
    <script>
    $(document).ready(function() {
        var table = $('#tablaMovimientos').DataTable({
            "responsive": true,
            "lengthChange": true,
            "autoWidth": false,
            "pageLength": 25,
            "dom": "<'row'<'col-sm-6 d-flex align-items-end' ><'col-sm-6'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row'<'col-sm-4'i><'col-sm-4 d-flex justify-content-center'p><'col-sm-4 d-flex justify-content-end'l>>",
            "language": {
                "search": "Buscar:",
                "lengthMenu": "Mostrar _MENU_ registros",
                "info": "Mostrando _START_ a _END_ de _TOTAL_ movimientos",
                "infoEmpty": "Mostrando 0 a 0 de 0 movimientos",
                "infoFiltered": "(filtrado de _MAX_ total movimientos)",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                },
                "zeroRecords": "No se encontraron movimientos",
                "emptyTable": "No hay movimientos registrados"
            },
            "order": [[0, 'desc']],
            "initComplete": function() {
                actualizarEstadisticas();
            },
            "drawCallback": function() {
                actualizarEstadisticas();
            }
        });

        // Reestructurar el campo de búsqueda para que el label esté arriba
        var searchHTML = `
            <div style="text-align: right;">
                <div style="display: inline-block; text-align: left;">
                    <label class="mb-1" style="font-weight: bold; display: block;"><i class="fas fa-search"></i> Buscar:</label>
                    <div class="d-flex">
                        <input type="search" class="form-control form-control-sm" placeholder="" aria-controls="tablaMovimientos" id="search_input_movimientos" style="width: 300px;">
                        <button id="btnLimpiarBusqueda" class="btn btn-sm btn-secondary ml-2" title="Limpiar búsqueda">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#tablaMovimientos_filter').html(searchHTML);
        
        // Vincular el nuevo input al filtro de DataTables
        $('#tablaMovimientos_filter input').on('keyup search', function() {
            table.search(this.value).draw();
        });
        
        // Botón limpiar búsqueda
        $('#btnLimpiarBusqueda').on('click', function() {
            $('#search_input_movimientos').val('');
            table.search('').draw();
        });

        // Insertar filtros de fecha a la izquierda (mismo estilo que gastos/ingresos)
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
        $('#tablaMovimientos_wrapper .row:first .col-sm-6:first').html(filtrosHTML);

        // Filtro personalizado por rango de fechas
        $.fn.dataTable.ext.search.push(
            function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'tablaMovimientos') {
                    return true;
                }
                
                var fechaDesde = $('#fecha_desde').val();
                var fechaHasta = $('#fecha_hasta').val();
                var fechaMovimiento = data[0]; // Columna de fecha (formato dd/mm/yyyy)
                
                if (!fechaDesde && !fechaHasta) {
                    return true;
                }
                
                // Convertir fecha dd/mm/yyyy a yyyy-mm-dd para comparación
                var partes = fechaMovimiento.split('/');
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

        // Función para actualizar estadísticas basadas en las filas visibles
        function actualizarEstadisticas() {
            let totalIngresos = 0;
            let totalGastos = 0;
            
            // Obtener la instancia de DataTable
            let dt = $('#tablaMovimientos').DataTable();
            
            // Recorrer solo las filas filtradas
            dt.rows({search: 'applied'}).every(function() {
                let data = this.data();
                let tipo = data[1]; // Columna tipo (ingreso/gasto)
                let importeTexto = data[4]; // Columna importe
                
                // Extraer el número del texto (eliminar + - € y espacios) y usar valor absoluto
                let importe = Math.abs(parseFloat(importeTexto.replace(/[^\d,.-]/g, '').replace(',', '.')));
                
                if (tipo.includes('Ingreso')) {
                    totalIngresos += importe;
                } else if (tipo.includes('Gasto')) {
                    totalGastos += importe;
                }
            });
            
            let balance = totalIngresos - totalGastos;
            
            // Formatear números
            function formatearNumero(num) {
                return num.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            }
            
            // Actualizar las cajas de información
            $('.info-box:eq(0) .info-box-number').text(formatearNumero(totalIngresos) + ' €');
            $('.info-box:eq(1) .info-box-number').text(formatearNumero(totalGastos) + ' €');
            $('.info-box:eq(2) .info-box-number')
                .text(formatearNumero(balance) + ' €')
                .removeClass('text-success text-danger')
                .addClass(balance >= 0 ? 'text-success' : 'text-danger');
            
            // Actualizar labels dinámicamente
            let labelPeriodo = 'Periodo Filtrado';
            if ($('#fecha_desde').val() || $('#fecha_hasta').val()) {
                $('.info-box:eq(0) .info-box-text').text(labelPeriodo);
                $('.info-box:eq(1) .info-box-text').text(labelPeriodo);
                $('.info-box:eq(2) .info-box-text').text('Balance Filtrado');
            } else {
                $('.info-box:eq(0) .info-box-text').text('Ingresos Totales');
                $('.info-box:eq(1) .info-box-text').text('Gastos Totales');
                $('.info-box:eq(2) .info-box-text').text('Balance Total');
            }
        }

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
        
        // Inicializar estadísticas
        actualizarEstadisticas();
    });
    </script>
JAVASCRIPT;
    
    mysqli_close($con);
    include_once 'templates/foot.php'; 
}
?>
