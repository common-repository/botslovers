<?php
/**
* Funncion de Buscar taxonomy
*/
function botsloversSearchTaxonomy($peticionWeb){
    //verificamos si exite la propiedad
    if (array_key_exists('searchTaxonomy', $peticionWeb)){
        //Funcion para obtener las taxonomias
        $taxonomies = get_taxonomies( [ 'object_type' => [ 'product' ] ] );
        $aRespuesta = array('taxonomias' => array());
        //verificamos si esta vacio
        if(!empty($taxonomies)){
            //recorremos el array de taxonomias
            foreach ($taxonomies as $key => $taxonomy) {
                //Quitamos variaciones, tipo de producto y los tags
                if($taxonomy != 'product_type' && $taxonomy != 'product_tag' && strpos($taxonomy, 'pa_' ) === false){
                    array_push($aRespuesta['taxonomias'], $taxonomy);
                }
            }
        }
        return $aRespuesta;
    }
    return FALSE;
}
/**
* Funncion de Buscar taxonomy
*/
function botsloversSearchTerms($peticionWeb){
    //verificamos si exite la propiedad
    if (array_key_exists('searchTerms', $peticionWeb)){
        $searchTerms = $peticionWeb['searchTerms'];
        //verificmaos si trae la informacion
        if(array_key_exists('taxonomy', $searchTerms)){
            //obtenemos las categorias segun los datos recibidos
            $args = array(
                'taxonomy'   => $searchTerms['taxonomy'],
                'hide_empty' => TRUE
            );
            //verificamos si trae
            if(array_key_exists('parent', $searchTerms)){
                $args['parent'] = $searchTerms['parent'];
            }
            if(array_key_exists('search', $searchTerms)){
                $args['search'] = $searchTerms['search'];
            }
            //traemos los terms
            $terms = get_terms($args);
            //Definimos el array de respuesta
            $aRespuesta = array( 'terms' => array());
            if(!empty($terms)){
                foreach ($terms as $key => $term) {
                    //verificamos si tiene una imagen
                    $thumbnail_id = get_woocommerce_term_meta( $term->term_id, 'thumbnail_id', true );
                    $urlImg       = wp_get_attachment_url( $thumbnail_id );
                    array_push($aRespuesta['terms'],
                        array(
                            'term_id'    => $term->term_id,
                            'term_name'  => $term->name,
                            'term_slug'  => $term->slug,
                            'term_parent'=> $term->parent,
                            'term_img'   => $urlImg
                        )
                    );
                }
            }else{
                $aRespuesta = array('mensajeError' => 'No encontramos categorías.');
            }
            return $aRespuesta;
        }
    }
    return FALSE;
}
/**
* Funcion para obtener la informacion de la orden
*/
function botsloversSearchOrder($peticionWeb){
    //verificamos si exite la propiedad
    if (array_key_exists('searchOrder', $peticionWeb)){
        $searchOrder = $peticionWeb['searchOrder'];
        if (array_key_exists('id_orden', $searchOrder)){
            $id_orden = $searchOrder['id_orden'];
            //traemos la clase de de las ordenes
            $order = wc_get_order( $id_orden );
            //verificamos si existe la orden
            if($order){
                $order_data = $order->get_data();
                $responseOrden = array(
                    'order_status'          => $order->get_status(),
                    'order_currency'        => $order->get_currency(),
                    'order_payment_method'  => $order->get_payment_method(),
                    'order_creation'        => $order->get_date_created(),
                    'order_email'           => $order->get_billing_email(),
                    'order_total'           => $order_data['total'],
                    'order_name'            => $order_data['billing']['first_name'],
                    'order_city'            => $order_data['billing']['city'],
                    'order_address'         => $order_data['billing']['address_1'],
                );
            }else{
                $responseOrden = array(
                    'mensajeError' => 'No encontramos la orden.'
                );
            }
            return $responseOrden;
        }
    }
    return FALSE;
}
/**
* Function busqueda de producto
*/
function botsloversGetProduct( $typeSearch, $find, $page, $qnty){
    //definimos array de bisqueda
    $args = array(
        'post_type'			=> 'product',
        'post_status'		=> 'publish',
        'meta_query'		=> array(
            array(
                'key'   => '_stock_status',
                'value' => 'instock'
            )
        )
    );
    //revisams si tiene pagina
    if($page != NULL){
        $args['paged'] = $page;
    }
    //revisams si tiene cantidad
    if($qnty != NULL){
        $args['showposts'] = $qnty;
    }
    //validamos si es por sku
    if($typeSearch == 'sku'){
        array_push($args['meta_query'], array(
            'key'     => '_sku',
            'value'   => $find,
            'compare' => '='
        ));
    }
    //validamos si es por title
    if($typeSearch == 'name'){
        $args['title'] = $find;
    }
    //validamos si es por content
    if($typeSearch == 'content'){
        $args['s'] = $find;
    }
    //validamos si es por content
    if($typeSearch == 'taxonomys'){
        $args['tax_query'] = $find;
    }
    //Declarar Array
    $post_type_arr = array();
    $wp_query = new WP_Query($args);
    while ($wp_query->have_posts()) : $wp_query->the_post();
        $post_id = get_the_id();
        // Get attribs from image
        $imgAttr = wp_get_attachment_image_src(get_post_thumbnail_id($post_id), 'shop_catalog');
        // Traemos el content a traves de "get_the_content" y le damos formato
        $get_content = apply_filters('the_content', get_the_content());
        $get_content = str_replace(']]>', ']]&gt;', $get_content);
        $get_content = preg_replace('/<img[^>]+./', '', $get_content);
        //precio del producto
        $prod = wc_get_product($post_id);
        //creamos array de los productos
        array_push(
            $post_type_arr,
            array(
                'id'            =>  $post_id,
                'img'           => $imgAttr[0],
                'content'       => $get_content,
                'excerpt'       => get_the_excerpt(),
                'url'           => get_permalink(),
                'name'          => get_the_title(),
                'regular_price' => $prod->get_regular_price(),
                'sale_price'    => '',
                'price'         => $prod->get_price()
            )
        );
    endwhile;
    //verificamos si trae info
    if(!empty($post_type_arr)){
        return array('producto' => $post_type_arr, 'total_product' => $wp_query->found_posts);
    }
    return false;
}
/**
* Funcion para obtener la informacion de los productos
*/
function botsloversSearchProducts($peticionWeb){
    //verificamos si exite la propiedad
    if (array_key_exists('searchProducts', $peticionWeb)){
        $searchProducts = $peticionWeb['searchProducts'];
        if (array_key_exists('typeSearch', $searchProducts) && array_key_exists('find', $searchProducts)){
            //definimos page y cantidad
            $page  = NULL;
            $qunty = NULL;
            //definimos si existe la cantidad y la pagina
            if(isset($searchProducts['page'])){
                $page  = $searchProducts['page'];
            }
            if(isset($searchProducts['qnty'])){
                $qunty = $searchProducts['qnty'];
            }
            //traemos la clase de de las ordenes
            $productFind    = botsloversGetProduct($searchProducts['typeSearch'], $searchProducts['find'], $page, $qunty);
            //verificamos si la busqueda es por nombre para mandarlo a buscar por contenido si no encuentra
            if(!$productFind && $searchProducts['typeSearch'] == 'name'){
                $productFind    = botsloversGetProduct('content', $searchProducts['find'], $page, $qunty);
            }
            //verificamos si existe la orden
        }elseif(array_key_exists('typeSearch', $searchProducts) && array_key_exists('taxonomys', $searchProducts)){
            //definimos page y cantidad
            $page  = NULL;
            $qunty = NULL;
            //definimos si existe la cantidad y la pagina
            if(isset($searchProducts['page'])){
                $page  = $searchProducts['page'];
            }
            if(isset($searchProducts['qnty'])){
                $qunty = $searchProducts['qnty'];
            }
            $find = array();
            //Definimos variables
            if(count($searchProducts['taxonomys']) > 1){
                $find['relation'] = 'AND';
            }
            //recorrer array
            foreach ($searchProducts['taxonomys'] as $key => $tax) {
                array_push($find, array(
                    'taxonomy'  => $tax['taxonomy'],
                    'field'     => 'term_id',
                    'terms'     => $tax['id'],
                ));
            }
            //traemos la clase de de las ordenes
            $productFind    = botsloversGetProduct($searchProducts['typeSearch'], $find,  $page, $qunty);
        }
        //verificamos si trae info
        if($productFind){
            //verificamos si trae la propiedad
            $responseOrden = $productFind;
        }else{
            $responseOrden = array(
                'mensajeError' => 'No encontramos el producto.'
            );
        }
        return $responseOrden;
    }
    return FALSE;
}
/**
* Funcion para generar token
*/
function botsloversGenerateToken($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}