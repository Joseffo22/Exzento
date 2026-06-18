<?php 
session_start();
if(isset($_SESSION['tipoUsuario'])){
$tipoUsuario=$_SESSION['tipoUsuario'];

}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Exzento</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link href="/assets/css/styles.css" rel="stylesheet">
  <link href="/assets/css/landing.css" rel="stylesheet">
  <link rel="icon" href="/assets/img/logo.png" type="image/png">
  <link rel="manifest" href="/assets/manifest.json">
</head>
<body class="d-flex flex-column min-vh-100">
