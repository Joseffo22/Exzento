<?php

// index.php
require('header.php');
require('verificacion.php');

require('route.php');
require($ruta.'/nav.php');
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = trim($uri, '/');

switch ($uri) {
    case '':
        require 'pages/home.php';
        break;
   case 'index':
          require 'pages/home.php';
        break;

        case 'login':
            require 'pages/login.php';
            break;
            case 'informacion-personal':
                require 'pages/informacion-personal.php';
                break;
                case 'mis-servicios':
                    require 'pages/mis-servicios.php';
                    break;
                    case 'facturas':
                        require 'pages/facturas.php';
                        break;
    
                        case 'lector-qr':
                            require 'pages/lector-qr.php';
                            break;
                            case 'visualizar-ticket':
                                if (session_status() === PHP_SESSION_NONE) {
                                    session_start();
                                }
                                if (!isset($_SESSION['tipoUsuario'])) {
                                    require 'logout/visualizar-ticket.php';
                                    exit;
                                }
                                require 'pages/visualizar-ticket.php';
                                break;
                                case 'generar-ticket':
                                    require 'pages/generar-ticket.php';
                                    break;
                                    case 'registrar-datos-fiscales':
                                        require 'pages/registrar-datos-fiscales.php';
                                        break;
                                        case 'editar-datos-fiscales':
                                            require 'pages/editar-datos-fiscales.php';
                                            break;
                                            case 'lista-tickets':
                                                require 'pages/lista-tickets.php';
                                                break;

                                                case 'invitacion-amigo':
                                                    require 'pages/invitacion-amigo.php';
                                                    break;

                                                    case 'unirse':
                                                        require 'pages/unirse.php';
                                                        break;
                                                        case 'bienvenida-empresa':
                                                            require 'pages/bienvenida-empresa.php';
                                                            break;
                                                            case 'contactos-frecuentes':
                                                                require 'pages/contactos-frecuentes.php';
                                                                break;
    

    case 'facturacion':
        require 'pages/facturacion.php';
        break;

    default:
        http_response_code(404);
        require 'pages/404.php';
        break;
}
require($ruta."/footer.php");