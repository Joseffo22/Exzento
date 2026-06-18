<?php 
session_start();
if(isset($_SESSION['tipoUsuario'])){
$tipoUsuario=$_SESSION['tipoUsuario'];

}
?>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Exzento</title>
  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <!-- Custom Styles -->
  <link href="/assets/css/styles.css" rel="stylesheet">
  <link rel="icon" href="/assets/img/logo.png" type="image/png">
  <!-- Web App Manifest -->
  <link rel="manifest" href="/assets/manifest.json">
  <!-- <link href="assets/css/estilo.css" rel="stylesheet"> -->
</head>

<!-- Bootstrap 5 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="/assets/js/session-keepalive.js"></script>