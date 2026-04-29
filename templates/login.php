<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Involtrak Test | Login</title>
  <!-- <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback"> -->
  <link rel="stylesheet" href="public/plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="public/plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="public/dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="login-logo">
    <strong>Fallen</strong>Honey-TEST
  </div>
  <div class="card">
    <div class="card-body login-card-body">
      
      <form action="/login" method="post">
        <?php echo getCSRFField(); ?>
        <?php if(!empty($errores['otro'])): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($errores['otro']); ?></div>
        <?php endif; ?>
        <?php if(!empty($errores['csrf'])): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($errores['csrf']); ?></div>
        <?php endif; ?>
        <div class="input-group mb-3">
          <input type="text" class="form-control" placeholder="Usuario" name="usuario" value="<?php if(isset($usuario)) echo htmlspecialchars($usuario); ?>">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <?php if(!empty($errores['usuario'])): ?>
          <div class="text-danger small mb-2"><?php echo htmlspecialchars($errores['usuario']); ?></div>
        <?php endif; ?>
        <div class="input-group mb-3">
          <input type="password" class="form-control" placeholder="Contraseña" name="password">
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <?php if(!empty($errores['contra'])): ?>
          <div class="text-danger small mb-2"><?php echo htmlspecialchars($errores['contra']); ?></div>
        <?php endif; ?>
        <div class="row">
          <div class="col-12">
            <button type="submit" class="btn bg-purple color-palette col-sm-12" name="entrar">Iniciar Sesión</button>
          </div>
        </div>
      </form>

      <p class="mt-3 mb-1">
        <a href="/forgot-password">¿Olvidaste tu contraseña?</a>
      </p>
  </div>
</div>
<script src="public/plugins/jquery/jquery.min.js"></script>
<script src="public/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="public/dist/js/adminlte.min.js"></script>
</body>
</html>
