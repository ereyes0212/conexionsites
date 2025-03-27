<?php
/*
Plugin Name: Conexion Sites tiempo.hn
Description: Plugin para realizar conexión entre tiempo.hn e integrarle funcionalidades
Version: 1.0.0
Author: Medios Publicitarios
Text Domain: th_conexion_sites
*/

define("TH_CONEXION_SITES", dirname(__FILE__));
define("TH_CONEXION_SITES_TOKEN", "tu_token_secreto"); // Cambia este valor por uno seguro

if (!defined('ABSPATH')) {
    exit; // Evita acceso directo
}

// Registrar endpoint REST API para crear usuario
add_action('rest_api_init', function () {
    register_rest_route('conexion-sites/v1', '/crear_usuario', array(
        'methods'  => 'POST',
        'callback' => 'conexion_sites_crear_usuario',
        'permission_callback' => 'conexion_sites_validar_token'
    ));
    
    // Registrar endpoint REST API para eliminar usuario por ID
    register_rest_route('conexion-sites/v1', '/eliminar_usuario/(?P<id>[0-9]+)', array(
        'methods'  => 'DELETE',
        'callback' => 'conexion_sites_eliminar_usuario',
        'permission_callback' => 'conexion_sites_validar_token'
    ));
});

// Función de validación del token
function conexion_sites_validar_token() {
    $token = '';

    // Intentar obtenerlo con getallheaders()
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (!empty($headers['Authorization'])) {
            $token = trim($headers['Authorization']);
        }
    }
    
    // Si no se obtuvo el token, verificar en $_SERVER
    if (empty($token) && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $token = trim($_SERVER['HTTP_AUTHORIZATION']);
    }

    // Comparar con el token definido
    if ($token === TH_CONEXION_SITES_TOKEN) {
        return true;
    }
    return false;
}

// Callback del endpoint para crear usuario
function conexion_sites_crear_usuario(WP_REST_Request $request) {
    $datos = $request->get_json_params();
    
    if (empty($datos['email']) || empty($datos['username']) || empty($datos['password'])) {
        return new WP_Error('datos_invalidos', 'Faltan datos obligatorios', array('status' => 400));
    }
    
    $user_id = wp_create_user($datos['username'], $datos['password'], $datos['email']);
    
    if (is_wp_error($user_id)) {
        return new WP_Error('error_creacion', 'No se pudo crear el usuario', array('status' => 500));
    }
    
    return array('success' => true, 'user_id' => $user_id);
}

// Callback del endpoint para eliminar usuario
function conexion_sites_eliminar_usuario(WP_REST_Request $request) {
    $user_id = (int) $request->get_param('id');
    
    if (empty($user_id)) {
        return new WP_Error('id_invalido', 'ID de usuario no válido', array('status' => 400));
    }
    
    if (!get_userdata($user_id)) {
        return new WP_Error('usuario_no_existe', 'El usuario no existe', array('status' => 404));
    }
    
    $resultado = wp_delete_user($user_id);
    
    if (!$resultado) {
        return new WP_Error('error_eliminacion', 'No se pudo eliminar el usuario', array('status' => 500));
    }
    
    return array('success' => true, 'user_id' => $user_id);
}
