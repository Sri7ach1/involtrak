<?php

function facturaPDFController() {
    global $con;
    
    try {
        require_once 'models/Factura.php';

        // Verificar autenticación
        if (!isLoggedIn() || !validateSessionIntegrity()) {
            http_response_code(401);
            die('No autorizado');
        }

        $id = intval(getGet('id'));

        if ($id <= 0) {
            http_response_code(400);
            die('ID inválido');
        }

        $facturaModel = new Factura($con);
        $factura = $facturaModel->getFacturaById($id);
        
        if (!$factura) {
            http_response_code(404);
            die('Factura no encontrada');
        }
        
        // Datos de la empresa desde variables de entorno
        $empresa = [
            'nombre'   => getenv('COMPANY_NAME')    ?: 'Mi Empresa',
            'cif'      => getenv('COMPANY_CIF')      ?: '',
            'direccion'=> getenv('COMPANY_ADDRESS')  ?: '',
            'email'    => getenv('COMPANY_EMAIL')    ?: '',
            'telefono' => getenv('COMPANY_PHONE')    ?: '',
        ];
        
        // Generar PDF
        generarPDFFactura($factura, $empresa);
        
    } catch (Throwable $t) {
        error_log('[PDF_ERROR] ' . $t->getMessage() . ' en ' . $t->getFile() . ':' . $t->getLine());
        http_response_code(500);
        die('Error al generar el PDF. Contacte al administrador.');
    }
}

function generarPDFFactura($factura, $empresa) {
    // Configurar headers para HTML imprimible
    header('Content-Type: text/html; charset=UTF-8');
    
    // Generar HTML que se puede imprimir como PDF desde el navegador
    // En producción, usar TCPDF, FPDF o mPDF para generar PDF real
    
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura <?= htmlspecialchars($factura['numero_factura']) ?></title>
    <style>
        @page {
            size: A4;
            margin: 20mm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12pt;
            line-height: 1.5;
            color: #333;
        }
        
        .container {
            max-width: 210mm;
            margin: 0 auto;
            padding: 10mm;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #2c3e50;
        }
        
        .empresa {
            flex: 1;
        }
        
        .empresa h1 {
            color: #2c3e50;
            font-size: 24pt;
            margin-bottom: 5px;
        }
        
        .empresa p {
            margin: 2px 0;
            color: #666;
        }
        
        .factura-info {
            text-align: right;
        }
        
        .factura-numero {
            font-size: 18pt;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 10px;
        }
        
        .info-section {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
        }
        
        .cliente-info, .fecha-info {
            flex: 1;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
            margin: 0 5px;
        }
        
        .cliente-info h3, .fecha-info h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 14pt;
        }
        
        .cliente-info p, .fecha-info p {
            margin: 5px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        table thead {
            background: #2c3e50;
            color: white;
        }
        
        table th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        
        table th.text-right,
        table td.text-right {
            text-align: right;
        }
        
        table th.text-center,
        table td.text-center {
            text-align: center;
        }
        
        table tbody tr {
            border-bottom: 1px solid #ddd;
        }
        
        table tbody tr:hover {
            background: #f8f9fa;
        }
        
        table td {
            padding: 10px 12px;
        }
        
        table tfoot {
            font-weight: bold;
        }
        
        table tfoot tr {
            border-top: 2px solid #2c3e50;
        }
        
        table tfoot td {
            padding: 10px 12px;
        }
        
        .totales {
            background: #ecf0f1;
        }
        
        .total-final {
            background: #2c3e50;
            color: white;
            font-size: 14pt;
        }
        
        .estado-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 10pt;
        }
        
        .estado-pendiente {
            background: #f39c12;
            color: white;
        }
        
        .estado-pagada {
            background: #27ae60;
            color: white;
        }
        
        .estado-anulada {
            background: #e74c3c;
            color: white;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 10pt;
        }
        
        @media print {
            body {
                margin: 0;
            }
            
            .container {
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #3498db;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 14pt;
            border-radius: 5px;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        
        .print-button:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Imprimir / Guardar PDF</button>
    
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="empresa">
                <h1><?= htmlspecialchars($empresa['nombre']) ?></h1>
                <p><strong>CIF:</strong> <?= htmlspecialchars($empresa['cif']) ?></p>
                <p><?= htmlspecialchars($empresa['direccion']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($empresa['email']) ?></p>
                <p><strong>Teléfono:</strong> <?= htmlspecialchars($empresa['telefono']) ?></p>
            </div>
            <div class="factura-info">
                <div class="factura-numero">
                    FACTURA<br><?= htmlspecialchars($factura['numero_factura']) ?>
                </div>
                <?php
                $estadoClass = 'estado-' . $factura['estado'];
                $estadoTexto = strtoupper($factura['estado']);
                ?>
                <span class="estado-badge <?= $estadoClass ?>">
                    <?= htmlspecialchars($estadoTexto) ?>
                </span>
            </div>
        </div>
        
        <!-- Información Cliente y Fecha -->
        <div class="info-section">
            <div class="cliente-info">
                <h3>CLIENTE</h3>
                <p><strong><?= htmlspecialchars($factura['cliente_nombre'] . ' ' . $factura['cliente_apellidos']) ?></strong></p>
                <?php if (!empty($factura['cliente_direccion'])): ?>
                <p><?= htmlspecialchars($factura['cliente_direccion']) ?></p>
                <?php endif; ?>
                <?php if (!empty($factura['cliente_poblacion']) || !empty($factura['cliente_provincia'])): ?>
                <p><?= htmlspecialchars(trim($factura['cliente_poblacion'] . ' ' . $factura['cliente_provincia'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($factura['cliente_codigo_postal']) || !empty($factura['cliente_pais'])): ?>
                <p><?= htmlspecialchars(trim($factura['cliente_codigo_postal'] . ' - ' . $factura['cliente_pais'])) ?></p>
                <?php endif; ?>
                <?php if (!empty($factura['cliente_email'])): ?>
                <p><strong>Email:</strong> <?= htmlspecialchars($factura['cliente_email']) ?></p>
                <?php endif; ?>
                <?php if (!empty($factura['cliente_telefono'])): ?>
                <p><strong>Teléfono:</strong> <?= htmlspecialchars($factura['cliente_telefono']) ?></p>
                <?php endif; ?>
            </div>
            <div class="fecha-info">
                <h3>FECHA DE EMISIÓN</h3>
                <p><strong><?= date('d/m/Y', strtotime($factura['fecha_emision'])) ?></strong></p>
                <p><?= date('H:i', strtotime($factura['fecha_emision'])) ?> horas</p>
            </div>
        </div>
        
        <!-- Tabla de Artículos -->
        <table>
            <thead>
                <tr>
                    <th style="width: 20%;">Artículo</th>
                    <th style="width: 35%;">Descripción</th>
                    <th class="text-center" style="width: 10%;">Cantidad</th>
                    <th class="text-right" style="width: 15%;">Precio Unit.</th>
                    <th class="text-right" style="width: 20%;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($factura['lineas'] as $linea): ?>
                <tr>
                    <td><?= htmlspecialchars($linea['articulo']) ?></td>
                    <td><?= htmlspecialchars($linea['descripcion']) ?></td>
                    <td class="text-center"><?= number_format($linea['cantidad'], 2, ',', '.') ?></td>
                    <td class="text-right"><?= number_format($linea['precio_unitario'], 2, ',', '.') ?> €</td>
                    <td class="text-right"><?= number_format($linea['subtotal'], 2, ',', '.') ?> €</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="totales">
                    <td colspan="4" class="text-right"><strong>SUBTOTAL</strong></td>
                    <td class="text-right"><?= number_format($factura['subtotal'], 2, ',', '.') ?> €</td>
                </tr>
                <tr class="totales">
                    <?php 
                    $porcentaje = $factura['porcentaje_impuesto'] ?? 21;
                    $labelImpuesto = 'IVA (' . number_format($porcentaje, 2, ',', '.') . '%)';
                    $impuestoValor = $factura['impuesto'] ?? 0;
                    ?>
                    <td colspan="4" class="text-right"><strong><?= $labelImpuesto ?></strong></td>
                    <td class="text-right"><?= number_format($impuestoValor, 2, ',', '.') ?> €</td>
                </tr>
                <tr class="total-final">
                    <td colspan="4" class="text-right"><strong>TOTAL</strong></td>
                    <td class="text-right"><strong><?= number_format($factura['total'], 2, ',', '.') ?> €</strong></td>
                </tr>
            </tfoot>
        </table>
        
        <!-- Footer -->
        <div class="footer">
            <p>Factura generada el <?= date('d/m/Y H:i:s') ?></p>
            <p><?= htmlspecialchars($empresa['nombre']) ?> - <?= htmlspecialchars($empresa['email']) ?></p>
        </div>
    </div>
    
    <script>
        // Auto imprimir al abrir (opcional)
        // window.print();
    </script>
</body>
</html>
    <?php
    exit;
}
