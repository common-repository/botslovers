<?php
/**
Plugin Name: Bostlovers
Plugin URI: https://www.botslovers.com/
Description: Integración woocommerce
Version: 1.0.3
Author: Botslovers
Author https://www.botslovers.com/
License: GPL2
*/
//Traemos el helper de las funciones
include_once 'botslovers-helper-ecommerce.php';
//Verificamos que tenga woocomerce activado
function botsloversActivatePlugin() {
    if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
        include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
    }
    if ( current_user_can( 'activate_plugins' ) && ! class_exists( 'WooCommerce' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        $error_message = '<p style="font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Oxygen-Sans,Ubuntu,Cantarell,\'Helvetica Neue\',sans-serif;font-size: 13px;line-height: 1.5;color:#444;">' . esc_html__( 'This plugin requires ', 'WooCommerce' ) . '<a href="' . esc_url( 'https://wordpress.org/plugins/woocommerce/' ) . '">WooCommerce</a>' . esc_html__( ' plugin to be active.', 'WooCommerce' ) . '</p>';
        die( $error_message );
    }
}
register_activation_hook( __FILE__, 'botsloversActivatePlugin' );
//Mostramos icono en el menu administrador
function botsloversMenuOption() {
	add_menu_page ( 'Botslovers', 'Botslovers', 'read', 'customBotslovers.php', 'botsloversTemplateAdmin', 'dashicons-store', 101);
}
add_action( 'admin_menu', 'botsloversMenuOption');
//Panel administrativo
function botsloversTemplateAdmin(){ 
    $plugins_url = plugins_url('Botslovers');
    //obtenemos el token
    $token       = get_option( 'botsloversTokenIntegration' );
    //verificamos si no existe para crear el token
    if(!$token){
        $token = botsloversGenerateToken(20);
        add_option('botsloversTokenIntegration',$token);
    }
    ?>
	<div class="wrap">
		<div style="display: flex;align-items: center;">
            <img style="width: 60px;margin-right: 15px;border-radius: 6px;" src="<?php echo esc_url($plugins_url) ?>/logo.jpg" alt="">
            <h1>Integración Botslovers</h1>
        </div>
		<p>Inserte el token generado en tu <strong>plataforma de Botslovers</strong> para que se pueda conectar al ecommerce. <br> </p>
        <div>
            <h3>Token: <span style="text-decoration: underline;"><?php echo esc_html($token) ?></span></h3>
        </div>
        <div>
            <h3>URL: <span style="text-decoration: underline;"><?php echo esc_url($_SERVER['SERVER_NAME' ])?></span></h3>
        </div>
        <p><strong>Recuerda que para poder habilitar el asistente virtual y las funcionalidades de este plugin debes tener una cuenta de Botslovers. <br> Si todavía no tienes una, puedes ponerte en contacto con nosotros a través de la página web <a target="_blank" href="https://www.botslovers.com/">www.botslovers.com</a>. <br> No esperes más y comienza a automatizar el 90% de tus conversaciones.</strong></p>
        <p><strong>In order to enable the virtual assistant and the functionalities of this plugin you must have a Botslovers account. <br> If you still do not have one, you can contact us through the website <a target="_blank" href="https://www.botslovers.com/">www.botslovers.com</a>. <br> Wait no more and start automating 90% of your conversations.</strong></p>
	</div>
	
<?php
}
//Verificamos al integrar desde la plataformna si las credenciales estan bien
//Registro de los endpoint
add_action('rest_api_init', function () {
    register_rest_route( 'botslovers/v1', 'verify/',array(
        'methods'  => 'POST',
        'callback' => 'botsloversVerifyTokenIntegration'
    ));
});
/**
* Funcion principal para las peticiones desde la plataforma
*/
function botsloversVerifyTokenIntegration($request) {
    //verificamos si el token es igual al registrado
    $header = $request->get_header('authorization');
    //Sacamos el base 64
    $aToke = explode(' ', $header);
    if(isset($aToke[1])){
        //obtenemos el token
        $tokenWP = get_option('botsloversTokenIntegration');
        $tokenBO = base64_decode($aToke[1]);
        //verificamos el token de llegada
        if($tokenBO == 'admin:'.$tokenWP){
            //obetenemos la direcion del widget
            $peticionWeb = $request->get_params();
            if (array_key_exists('locationScript', $peticionWeb)) {
                //variables por defecto
                $fullscreen = 'FALSE';
                $wp_print   = '';
                //verificamos la ruta del script
                $locationScript = $peticionWeb['locationScript'];
                //verificamos si es full screen
                if(array_key_exists('fullscreen', $peticionWeb)){
                    $fullscreen = $peticionWeb['fullscreen'];
                }
                //verificamos si es full screen
                if(array_key_exists('wp_print', $peticionWeb)){
                    $wp_print = $peticionWeb['wp_print'];
                }
                //definimos el nuevo script
                $script = '<script is-fullscreen="'.$fullscreen.'" '.$wp_print.' client-url="'.$locationScript.'" src="'.$locationScript.'/theme/widget/js/loadwidget.js" defer></script>';
                $reponse  = array(
                    'conectApi'         => true,
                    'locationScript'    => $script
                );
                //guardamos la url de lozacion del plugin web
                update_option('locationScriptBotslovers',$locationScript);
            }else{
                $reponse  = array(
                    'conectApi' => false
                ); 
            }
        }else{
            $reponse  = array(
                'conectApi' => false
            );
        }
    }else{
        $reponse  = array(
            'conectApi' => false
        );
    }    
    $response = new WP_REST_Response($reponse);
    $response->set_status(200);
    return $response;
}
//Creamos el endpoint las unfciones del ecommerce
add_action('rest_api_init', function () {
    register_rest_route( 'botslovers/v1', 'ecommerce/',array(
        'methods'  => 'POST',
        'callback' => 'botsloversEcommerceIntegration'
    ));
});
/**
* Funcion principal para las peticiones desde la plataforma
*/
function botsloversEcommerceIntegration($request) {
    //verificamos si el token es igual al registrado
    $header = $request->get_header('authorization');
    //Sacamos el base 64
    $aToke = explode(' ', $header);
    if(isset($aToke[1])){
        //obtenemos el token
        $tokenWP = get_option('botsloversTokenIntegration');
        $tokenBO = base64_decode($aToke[1]);
        //verificamos el token de llegada
        if($tokenBO == 'admin:'.$tokenWP){
            //definimos el error
            $error = FALSE;
            //traemos los valores
            $peticionWeb = $request->get_params();
            //verificamos si trae el action
            if (array_key_exists('action', $peticionWeb)) {
                //obtenemos el action del post
                $action = $peticionWeb['action'];
                //definimos que funcion ejecutar segun el action
                switch ( $action ) {
                    //Buscamos la orden
                    case "searchOrder":
                        $dataResponse = botsloversSearchOrder($peticionWeb);
                    break;
                    //Buscamos los productos
                    case "searchProducts":
                        $dataResponse = botsloversSearchProducts($peticionWeb);
                    break;
                    //Buscar los terms de las taxonomias para mostrar
                    case "searchTerms":
                        $dataResponse = botsloversSearchTerms($peticionWeb);
                    break;
                    case "searchTaxonomy":
                        $dataResponse = botsloversSearchTaxonomy($peticionWeb);
                    break;
                    //Caso default
                    default:
                        $error = TRUE;
                    break;
                }
                //Verificamos la respuesta de las funciones
                if($dataResponse == FALSE){
                    $error = TRUE;
                }
                //array de respuesta
                $reponse = array(
                    'action' => $action,
                    'data'   => $dataResponse 
                );
            }else{
                $error = TRUE;
            }
            //action, searchProducts(typeSearch, taxonomy(cuantas y cuales), sku, despcripcion), searchOrder(idorden), searchTerms (nilve taxonomy, nombre a buscar)
            //Si encontramos error en la peticion
            if($error == TRUE){
                return new WP_Error( 'Error!!', 'Encontramos un error, revise el json enviado', array('status' => 404) );
            }
            $response = new WP_REST_Response($reponse);
            $response->set_status(200);
            return $response;
        }else{
            return new WP_Error( 'Error!!', 'En la autentificación del token', array('status' => 404) );
        }
    }else{
        return new WP_Error( 'Error!!', 'No hay credenciales de seguridad', array('status' => 404) );
    }    
}
/**
* Verificamos si ya estan conectados para mostrar widget
*/
function loadWidgetChatBotslovers() {
    $locationScriptBotslovers = get_option( 'locationScriptBotslovers' );
    //verificamos si no existe para crear el token
    if($locationScriptBotslovers){
        add_action('wp_footer', function () use ($locationScriptBotslovers) {
			echo $locationScriptBotslovers;
		}, 20, 1);
    }
}
add_action( 'wp_enqueue_scripts', 'loadWidgetChatBotslovers' );
?>