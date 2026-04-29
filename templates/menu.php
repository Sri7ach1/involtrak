<li class="nav-header">GESTIONES PRINCIPALES</li>
<li class="nav-item">
    <a href="/panel" class="nav-link">
        <i class="nav-icon fas fa-tachometer-alt"></i>
        <p>Balances</p>
    </a>
</li>
<li class="nav-item">
    <a href="/ingresos" class="nav-link">
        <i class="nav-icon fas fa-arrow-up text-success"></i>
        <p>Ingresos</p>
    </a>
</li>
<li class="nav-item">
    <a href="/gastos" class="nav-link">
        <i class="nav-icon fas fa-arrow-down text-danger"></i>
        <p>Gastos</p>
    </a>
</li>
<li class="nav-header">FACTURACIÓN</li>
<li class="nav-item">
    <a href="/clientes" class="nav-link">
        <i class="nav-icon fas fa-users"></i>
        <p>Clientes</p>
    </a>
</li>
<li class="nav-item">
    <a href="/facturas" class="nav-link">
        <i class="nav-icon fas fa-file-invoice-dollar"></i>
        <p>Facturas</p>
    </a>
</li>
<li class="nav-header">GESTIÓN DE USUARIOS</li>
<li class="nav-item">
    <a href="/usuarios" class="nav-link">
        <i class="nav-icon fas fa-user"></i>
        <p>Usuarios</p>
    </a>
</li>
<li class="nav-header">SALIR</li>
<li class="nav-item">
    <a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="nav-link">
        <i class="nav-icon fas fa-sign-out-alt"></i>
        <p>Logout</p>
    </a>
</li>

<!-- Formulario oculto para logout (POST requerido por seguridad) -->
<form id="logout-form" action="/logout" method="POST" style="display:none;">
    <?= getCSRFField() ?>
</form>