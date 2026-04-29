<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Involtrak | Restablecer Contraseña</title>
  <!-- <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback"> -->
  <link rel="stylesheet" href="public/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="public/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="public/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="login-logo">
    <strong>Fallen</strong>Honey
  </div>
  <div class="card">
    <div class="card-body login-card-body">
      <?php if (!empty($message)): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible">
          <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
          <?php echo htmlspecialchars($message); ?>
        </div>
      <?php endif; ?>

      <?php if ($validToken && $messageType !== 'success'): ?>
        <p class="login-box-msg">Establece tu contraseña</p>

        <div class="callout callout-info">
          <p style="font-size: 12px; margin-bottom: 0;">
            <i class="fas fa-info-circle"></i> <strong>Requisitos de seguridad:</strong><br>
            • Mínimo 12 caracteres<br>
            • Al menos una mayúscula y una minúscula<br>
            • Al menos un número<br>
            • Al menos un carácter especial (!@#$%^&*)</p>
        </div>

        <form method="post">
          <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
          
          <div class="input-group mb-3">
            <input type="password" name="password" class="form-control" placeholder="Nueva contraseña" required autofocus>
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-lock"></span>
              </div>
            </div>
          </div>

          <div class="input-group mb-3">
            <input type="password" name="password_confirm" class="form-control" placeholder="Confirmar contraseña" required>
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-lock"></span>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <button type="submit" class="btn bg-purple color-palette col-sm-12">Restablecer contraseña</button>
            </div>
          </div>
        </form>
      <?php elseif ($messageType === 'success'): ?>
        <p class="text-center">
          <a href="/login" class="btn bg-purple color-palette">Ir al inicio de sesión</a>
        </p>
      <?php else: ?>
        <p class="text-center">
          <a href="/forgot-password" class="btn bg-purple color-palette">Solicitar nuevo enlace</a>
        </p>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="public/plugins/jquery/jquery.min.js"></script>
<script src="public/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="public/dist/js/adminlte.min.js"></script>
</body>
</html>
