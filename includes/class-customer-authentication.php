<?php

namespace MydPro\Includes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Sistema de Autenticação de Clientes
 * MyD Delivery Pro
 * 
 * Permite que clientes criem contas e façam login para nunca perder pedidos
 */
class Customer_Authentication {

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'wp_ajax_myd_customer_register', array( $this, 'handle_customer_register' ) );
        add_action( 'wp_ajax_nopriv_myd_customer_register', array( $this, 'handle_customer_register' ) );
        add_action( 'wp_ajax_myd_customer_login', array( $this, 'handle_customer_login' ) );
        add_action( 'wp_ajax_nopriv_myd_customer_login', array( $this, 'handle_customer_login' ) );
        add_action( 'wp_ajax_myd_customer_logout', array( $this, 'handle_customer_logout' ) );
        add_action( 'wp_ajax_myd_get_customer_orders', array( $this, 'get_customer_orders' ) );
        add_action( 'wp_ajax_myd_get_customer_profile', array( $this, 'get_customer_profile' ) );
        add_action( 'wp_ajax_myd_update_customer_profile', array( $this, 'update_customer_profile' ) );
        
        // Novo endpoint para verificar se email existe
        add_action( 'wp_ajax_myd_check_email_exists', array( $this, 'handle_check_email_exists' ) );
        add_action( 'wp_ajax_nopriv_myd_check_email_exists', array( $this, 'handle_check_email_exists' ) );
        
        add_action( 'myd-delivery/order/after-create', array( $this, 'link_order_to_customer' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
    // AJAX: get current user's latest draft order
    add_action( 'wp_ajax_myd_get_customer_draft', array( $this, 'get_customer_draft' ) );
        
        // Hook para modificar criação de draft order com usuário logado
        add_filter( 'myd-delivery/order/before-create-draft', array( $this, 'add_customer_to_draft' ), 10, 2 );

        // Endpoint para polling de sessão no frontend
        add_action('wp_ajax_myd_check_session', array($this, 'check_session'));
        add_action('wp_ajax_nopriv_myd_check_session', array($this, 'check_session'));
    }

    /**
     * Check if user is still logged in
     */
    public function check_session() {
        if ( is_user_logged_in() ) {
            wp_send_json_success( array( 'logged_in' => true ) );
        } else {
            wp_send_json_error( array( 'logged_in' => false, 'message' => 'Sessão expirada' ) );
        }
    }

    /**
     * Initialize
     */
    public function init() {
        // Usa role 'client' padrão do WordPress para clientes delivery
        if ( ! get_role( 'client' ) ) {
            add_role( 'client', __( 'Client', 'myd-delivery-pro' ), array(
                'read' => true,
                'myd_place_orders' => true,
                'myd_view_orders' => true,
            ));
        }
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        // Só carrega se estivermos na página do delivery
        if ( $this->is_delivery_page() ) {
            // Script simplificado de autenticação
            wp_register_script( 'myd-simple-auth', MYD_PLUGN_URL . 'assets/js/simple-auth.js', array( 'jquery' ), MYD_CURRENT_VERSION, true );
            wp_enqueue_script( 'myd-simple-auth' );
            
            // CSS para modal
            wp_register_style( 'myd-loginmodal', MYD_PLUGN_URL . 'assets/css/loginmodal.css', array(), MYD_CURRENT_VERSION );
            wp_enqueue_style( 'myd-loginmodal' );
            
            // Dados para o JavaScript
            wp_localize_script( 'myd-simple-auth', 'mydCustomerAuth', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'myd_customer_auth' ),
                'current_user' => $this->get_current_customer_data(),
                'messages' => array(
                    'login_success' => __( 'Login realizado com sucesso!', 'myd-delivery-pro' ),
                    'register_success' => __( 'Conta criada com sucesso! Agora você está logado.', 'myd-delivery-pro' ),
                    'logout_success' => __( 'Logout realizado com sucesso!', 'myd-delivery-pro' ),
                    'invalid_credentials' => __( 'Email ou senha incorretos.', 'myd-delivery-pro' ),
                    'email_exists' => __( 'Este email já está cadastrado.', 'myd-delivery-pro' ),
                    'phone_exists' => __( 'Este telefone já está cadastrado.', 'myd-delivery-pro' ),
                    'required_fields' => __( 'Preencha todos os campos obrigatórios.', 'myd-delivery-pro' ),
                )
            ));
        }
    }

    /**
     * Check if we're on a delivery page
     */
    private function is_delivery_page() {
        // Para debug, vamos carregar em todas as páginas por enquanto
        if ( is_admin() ) {
            return false;
        }
        
        // Carrega se for página do frontend (não admin)
        return true;
        
        // Condições originais (comentadas para debug):
        /*
        global $post;
        return ( $post && ( 
            has_shortcode( $post->post_content, 'mydelivery-list-products' ) ||
            has_shortcode( $post->post_content, 'mydelivery-track-order' ) ||
            strpos( $post->post_content, 'myd-delivery' ) !== false
        ));
        */
    }

    /**
     * Get current customer data
     */
    private function get_current_customer_data() {
        if ( ! is_user_logged_in() ) {
            return null;
        }

        $user_id = get_current_user_id();
        $user = get_userdata( $user_id );
        
        if ( ! $user ) {
            return null;
        }

        // Retorna para clientes do delivery e também para administradores / equipes autorizadas
        if ( ! in_array( 'client', $user->roles ) && ! myd_user_is_allowed_admin( $user ) ) {
            return null;
        }

        return array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'phone' => get_user_meta( $user->ID, 'myd_customer_phone', true ),
            'birthdate' => get_user_meta( $user->ID, 'myd_customer_birthdate', true ),
            'address' => array(
                'street' => get_user_meta( $user->ID, 'myd_customer_address', true ),
                'number' => get_user_meta( $user->ID, 'myd_customer_address_number', true ),
                'complement' => get_user_meta( $user->ID, 'myd_customer_address_complement', true ),
                'neighborhood' => get_user_meta( $user->ID, 'myd_customer_neighborhood', true ),
                'zipcode' => get_user_meta( $user->ID, 'myd_customer_zipcode', true ),
            ),
            'orders_count' => $this->get_customer_orders_count( $user->ID ),
        );
    }

    /**
     * Handle customer registration
     */
    public function handle_customer_register() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'myd_customer_auth' ) ) {
            wp_send_json_error( array( 'message' => __( 'Erro de segurança.', 'myd-delivery-pro' ) ) );
            return;
        }

        if ( function_exists('myd_check_ip_rate_limit') && myd_check_ip_rate_limit( 'customer_register', 10, 3600 ) ) {
            wp_send_json_error( array( 'message' => __( 'Muitas tentativas. Tente novamente mais tarde.', 'myd-delivery-pro' ) ) );
            return;
        }

        $name = sanitize_text_field( $_POST['name'] );
        $email = sanitize_email( $_POST['email'] );
        $phone = sanitize_text_field( $_POST['phone'] );
    $birthdate = isset( $_POST['birthdate'] ) ? sanitize_text_field( $_POST['birthdate'] ) : '';
        $cpf = isset( $_POST['cpf'] ) ? preg_replace( '/\D/', '', $_POST['cpf'] ) : '';
        $password = sanitize_text_field( $_POST['password'] );

        // Validações
        if ( empty( $name ) || empty( $email ) || empty( $phone ) || empty( $password ) ) {
            wp_send_json_error( array( 'message' => __( 'Preencha todos os campos obrigatórios.', 'myd-delivery-pro' ) ) );
            return;
        }

        if ( ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Email inválido.', 'myd-delivery-pro' ) ) );
            return;
        }

        if ( email_exists( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Este email já está cadastrado.', 'myd-delivery-pro' ) ) );
            return;
        }

        // Valida CPF básico (11 dígitos) se preenchido
        if ( ! empty( $cpf ) && ! preg_match( '/^\d{11}$/', $cpf ) ) {
            wp_send_json_error( array( 'message' => __( 'CPF inválido.', 'myd-delivery-pro' ) ) );
            return;
        }

        // Verifica se CPF já existe se preenchido
        if ( ! empty( $cpf ) ) {
            $users_with_cpf = get_users( array(
                'meta_key' => 'myd_customer_cpf',
                'meta_value' => $cpf,
                'number' => 1,
            ) );

            if ( ! empty( $users_with_cpf ) ) {
                wp_send_json_error( array( 'message' => __( 'Este CPF já está cadastrado.', 'myd-delivery-pro' ) ) );
                return;
            }
        }

        // Verifica se telefone já existe
        if ( $this->phone_exists( $phone ) ) {
            wp_send_json_error( array( 'message' => __( 'Este telefone já está cadastrado.', 'myd-delivery-pro' ) ) );
            return;
        }

        // Cria o usuário
        $user_id = wp_create_user( $email, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( array( 'message' => $user_id->get_error_message() ) );
            return;
        }

        // Define role de client
        $user = new \WP_User( $user_id );
        $user->set_role( 'client' );

        // Define display name e first name
        wp_update_user( array(
            'ID' => $user_id,
            'display_name' => $name,
            'first_name' => $name
        ) );

        // Adiciona metadados (telefone apenas dígitos)
        $phone_digits = preg_replace( '/\D/', '', $phone );
        update_user_meta( $user_id, 'myd_customer_phone', $phone_digits );
        // Armazena CPF (formato apenas dígitos)
        if ( ! empty( $cpf ) ) {
            update_user_meta( $user_id, 'myd_customer_cpf', $cpf );
        }
        if ( ! empty( $birthdate ) ) {
            // Normaliza e armazena como datetime 'Y-m-d H:i:s'
            $normalized = $this->normalize_birthdate_to_datetime( $birthdate );
            if ( $normalized ) {
                update_user_meta( $user_id, 'myd_customer_birthdate', $normalized );
            } else {
                // Se não conseguir normalizar, grava o valor original para não perder dados
                update_user_meta( $user_id, 'myd_customer_birthdate', $birthdate );
            }
        }
        // Salva código de confirmação (DDMM) com base na data de nascimento apenas na primeira vez
        if ( ! empty( $birthdate ) ) {
            $existing_code = get_user_meta( $user_id, 'myd_delivery_confirm_code', true );
            if ( empty( $existing_code ) ) {
                // Espera formato yyyy-mm-dd
                $parts = explode( '-', $birthdate );
                if ( count( $parts ) === 3 ) {
                    $year = $parts[0];
                    $month = str_pad( $parts[1], 2, '0', STR_PAD_LEFT );
                    $day = str_pad( $parts[2], 2, '0', STR_PAD_LEFT );
                    $confirm_code = $day . $month; // DDMM
                    update_user_meta( $user_id, 'myd_delivery_confirm_code', $confirm_code );
                }
            }
        }
        update_user_meta( $user_id, 'first_name', $name );
        update_user_meta( $user_id, 'display_name', $name );

        // Atualiza display name
        wp_update_user( array( 'ID' => $user_id, 'display_name' => $name ) );

        // Faz login automático
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, true );

        // Preenche endereço se fornecido
        if ( ! empty( $_POST['address'] ) ) {
            $this->save_customer_address( $user_id, $_POST['address'] );
        }

        wp_send_json_success( array(
            'message' => __( 'Conta criada com sucesso! Agora você está logado.', 'myd-delivery-pro' ),
            'customer' => $this->get_current_customer_data()
        ));
    }

    /**
     * Handle customer login
     */
    public function handle_customer_login() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'myd_customer_auth' ) ) {
            wp_send_json_error( array( 'message' => __( 'Erro de segurança.', 'myd-delivery-pro' ) ) );
            return;
        }

        if ( function_exists('myd_check_ip_rate_limit') && myd_check_ip_rate_limit( 'customer_login', 15, 1800 ) ) {
            wp_send_json_error( array( 'message' => __( 'Muitas tentativas. Tente novamente em 30 minutos.', 'myd-delivery-pro' ) ) );
            return;
        }

        // Accept either email or CPF as identifier
        $identifier = isset( $_POST['identifier'] ) ? trim( wp_unslash( $_POST['identifier'] ) ) : '';
        $password = isset( $_POST['password'] ) ? sanitize_text_field( $_POST['password'] ) : '';
        $remember = ! empty( $_POST['remember'] );

        if ( empty( $identifier ) || empty( $password ) ) {
            wp_send_json_error( array( 'message' => __( 'Preencha email/CPF e senha.', 'myd-delivery-pro' ) ) );
            return;
        }

        $user = null;
        // If looks like an email, use wp_authenticate which supports email/username
        if ( is_email( $identifier ) ) {
            $user = wp_authenticate( sanitize_email( $identifier ), $password );
            if ( is_wp_error( $user ) ) {
                wp_send_json_error( array( 'message' => __( 'Email ou senha incorretos.', 'myd-delivery-pro' ) ) );
                return;
            }
        } else {
            // Possibly CPF or Phone - strip non-digits
            $digits = preg_replace( '/\D/', '', $identifier );
            if ( strlen( $digits ) === 10 || strlen( $digits ) === 11 ) {
                $users = get_users( array(
                    'meta_query' => array(
                        'relation' => 'OR',
                        array(
                            'key' => 'myd_customer_cpf',
                            'value' => $digits,
                            'compare' => '='
                        ),
                        array(
                            'key' => 'myd_customer_phone',
                            'value' => $digits,
                            'compare' => '='
                        )
                    ),
                    'number' => 1,
                ) );
                if ( empty( $users ) ) {
                    wp_send_json_error( array( 'message' => __( 'Email, CPF ou Telefone incorretos.', 'myd-delivery-pro' ) ) );
                    return;
                }
                $user_candidate = $users[0];
                // Verify password against WP hash
                if ( wp_check_password( $password, $user_candidate->user_pass, $user_candidate->ID ) ) {
                    $user = $user_candidate;
                } else {
                    wp_send_json_error( array( 'message' => __( 'Email, CPF ou Telefone incorretos.', 'myd-delivery-pro' ) ) );
                    return;
                }
            } else {
                wp_send_json_error( array( 'message' => __( 'Identificador inválido. Use email, CPF (11 dígitos) ou telefone.', 'myd-delivery-pro' ) ) );
                return;
            }
        }


        // Verifica se é client do delivery ou usuário autorizado (admin-like)
        if ( ! in_array( 'client', $user->roles ) && ! myd_user_is_allowed_admin( $user ) ) {
            wp_send_json_error( array( 'message' => __( 'Acesso não autorizado.', 'myd-delivery-pro' ) ) );
            return;
        }

        // Faz login
        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, $remember );

        wp_send_json_success( array(
            'message' => __( 'Login realizado com sucesso!', 'myd-delivery-pro' ),
            'customer' => $this->get_current_customer_data()
        ));
    }

    /**
     * Handle customer logout
     */
    public function handle_customer_logout() {
        wp_logout();
        wp_send_json_success( array(
            'message' => __( 'Logout realizado com sucesso!', 'myd-delivery-pro' )
        ));
    }

    /**
     * Check if phone exists (ignoring formatting)
     */
    private function phone_exists( $phone, $exclude_user_id = 0 ) {
        $digits = preg_replace( '/\D/', '', $phone );
        if ( empty( $digits ) ) return false;

        $args = array(
            'meta_key' => 'myd_customer_phone',
            'meta_value' => array( $phone, $digits ), // Busca tanto formatado quanto limpo
            'meta_compare' => 'IN',
            'number' => 1
        );

        if ( $exclude_user_id ) {
            $args['exclude'] = array( $exclude_user_id );
        }

        $users = get_users( $args );
        
        return ! empty( $users );
    }

    /**
     * Save customer address
     */
    private function save_customer_address( $user_id, $address ) {
        if ( ! empty( $address['street'] ) ) {
            update_user_meta( $user_id, 'myd_customer_address', sanitize_text_field( $address['street'] ) );
        }
        if ( ! empty( $address['number'] ) ) {
            update_user_meta( $user_id, 'myd_customer_address_number', sanitize_text_field( $address['number'] ) );
        }
        if ( ! empty( $address['complement'] ) ) {
            update_user_meta( $user_id, 'myd_customer_address_complement', sanitize_text_field( $address['complement'] ) );
        }
        if ( ! empty( $address['neighborhood'] ) ) {
            update_user_meta( $user_id, 'myd_customer_neighborhood', sanitize_text_field( $address['neighborhood'] ) );
        }
        if ( ! empty( $address['zipcode'] ) ) {
            update_user_meta( $user_id, 'myd_customer_zipcode', sanitize_text_field( $address['zipcode'] ) );
        }
    }

    /**
     * Get customer orders count
     */
    private function get_customer_orders_count( $user_id ) {
        $orders = get_posts( array(
            'post_type' => 'mydelivery-orders',
            'posts_per_page' => -1,
            'post_status' => 'publish', // considerar apenas pedidos publicados
            'meta_query' => array(
                array(
                    'key' => 'myd_customer_id',
                    'value' => $user_id,
                    'compare' => '='
                ),
                array(
                    'key' => 'order_status',
                    'value' => 'finished',
                    'compare' => '='
                )
            ),
            'fields' => 'ids'
        ));

        return count( $orders );
    }

    /**
     * Get customer orders via AJAX
     */
    public function get_customer_orders() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Usuário não logado.', 'myd-delivery-pro' ) ) );
            return;
        }

        $user_id = get_current_user_id();

        $orders = get_posts( array(
            'post_type' => 'mydelivery-orders',
            'posts_per_page' => 20,
            'post_status' => 'publish', // Apenas pedidos publicados
            'meta_query' => array(
                array(
                    'key' => 'myd_customer_id',
                    'value' => $user_id,
                    'compare' => '='
                ),
                array(
                    'key' => 'order_status',
                    'value' => 'draft',
                    'compare' => '!='
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        $orders_data = array();
        foreach ( $orders as $order ) {
            // Busca o total igual ao admin
            $total = get_post_meta( $order->ID, 'myd_order_total', true );
            if ( empty( $total ) || $total == '0' || $total == '0.00' ) {
                $total = get_post_meta( $order->ID, 'order_total', true );
            }
            if ( empty( $total ) || $total == '0' || $total == '0.00' ) {
                $total = get_post_meta( $order->ID, 'total', true );
            }
            if ( empty( $total ) || $total == '0' || $total == '0.00' ) {
                $total = get_post_meta( $order->ID, 'myd_total', true );
            }
            if ( empty( $total ) || $total == '0' || $total == '0.00' ) {
                $total = get_post_meta( $order->ID, 'fdm_order_total', true );
            }

            // Busca o nome do produto principal (primeiro item do array myd_order_items)
            $items = Myd_Orders_Front_Panel::parse_order_items( get_post_meta( $order->ID, 'myd_order_items', true ) );
            $product_name = '';
            if (is_array($items) && count($items) > 0) {
                if (isset($items[0]['product_name']) && !empty($items[0]['product_name'])) {
                    $product_name = $items[0]['product_name'];
                } elseif (isset($items[0]['name']) && !empty($items[0]['name'])) {
                    $product_name = $items[0]['name'];
                }
            }

            $orders_data[] = array(
                'id' => $order->ID,
                'date' => get_post_meta( $order->ID, 'order_date', true ),
                'status' => get_post_meta( $order->ID, 'order_status', true ),
                'total' => $total ?: '0.00',
                'payment_status' => get_post_meta( $order->ID, 'order_payment_status', true ),
                'track_url' => get_permalink( get_option( 'fdm-page-order-track' ) ) . '?hash=' . base64_encode( $order->ID ),
                'items_count' => is_array($items) ? count($items) : 0,
                'myd_customer_id' => get_post_meta( $order->ID, 'myd_customer_id', true ),
                'product_name' => $product_name
            );
        }

        wp_send_json_success( $orders_data );
    }

    /**
     * Link order to customer after creation
     */
    public function link_order_to_customer( $order_data ) {
        if ( is_user_logged_in() && isset( $order_data['id'] ) ) {
            $user_id = get_current_user_id();
            $user = wp_get_current_user();
            
            // Só vincula se for client
            if ( in_array( 'client', $user->roles ) ) {
                update_post_meta( $order_data['id'], 'myd_customer_id', $user_id );

                // Tenta obter o código do usuário e salvá-lo no pedido (sobrescreve se existir)
                $order_id = $order_data['id'];
                $user_code = get_user_meta( $user_id, 'myd_delivery_confirm_code', true );
                if ( ! empty( $user_code ) ) {
                    update_post_meta( $order_id, 'myd_order_confirmation_code', $user_code );
                    update_post_meta( $order_id, 'order_confirmation_code', $user_code );
                }

                // Atualiza dados do cliente se necessário
                $this->update_customer_data_from_order( $user_id, $order_data );
            }
        }
    }

    /**
     * Update customer data from order
     */
    private function update_customer_data_from_order( $user_id, $order_data ) {
        $order_id = $order_data['id'];
        
        // Atualiza telefone se não existir
        $current_phone = get_user_meta( $user_id, 'myd_customer_phone', true );
        if ( empty( $current_phone ) ) {
            $order_phone = get_post_meta( $order_id, 'customer_phone', true );
            if ( ! empty( $order_phone ) ) {
                update_user_meta( $user_id, 'myd_customer_phone', $order_phone );
            }
        }

        // Atualiza endereço se não existir
        $current_address = get_user_meta( $user_id, 'myd_customer_address', true );
        if ( empty( $current_address ) ) {
            $address_fields = array(
                'myd_customer_address' => 'order_address',
                'myd_customer_address_number' => 'order_address_number',
                'myd_customer_address_complement' => 'order_address_comp',
                'myd_customer_neighborhood' => 'order_neighborhood',
                'myd_customer_zipcode' => 'order_zipcode'
            );

            foreach ( $address_fields as $user_meta_key => $order_meta_key ) {
                $order_value = get_post_meta( $order_id, $order_meta_key, true );
                if ( ! empty( $order_value ) ) {
                    update_user_meta( $user_id, $user_meta_key, $order_value );
                }
            }
        }
    }

    /**
     * Add customer to draft order
     */
    public function add_customer_to_draft( $data, $request_data ) {
        if ( is_user_logged_in() ) {
            $user_id = get_current_user_id();
            $user = wp_get_current_user();
            
            if ( in_array( 'client', $user->roles ) ) {
                // Preenche dados do cliente automaticamente se não fornecidos
                if ( empty( $request_data['customer']['name'] ) ) {
                    $data['customer']['name'] = $user->display_name;
                }
                
                if ( empty( $request_data['customer']['phone'] ) ) {
                    $phone = get_user_meta( $user_id, 'myd_customer_phone', true );
                    if ( ! empty( $phone ) ) {
                        $data['customer']['phone'] = $phone;
                    }
                }

                // Preenche endereço se não fornecido
                if ( empty( $request_data['customer']['address']['street'] ) ) {
                    $address = array(
                        'street' => get_user_meta( $user_id, 'myd_customer_address', true ),
                        'number' => get_user_meta( $user_id, 'myd_customer_address_number', true ),
                        'complement' => get_user_meta( $user_id, 'myd_customer_address_complement', true ),
                        'neighborhood' => get_user_meta( $user_id, 'myd_customer_neighborhood', true ),
                        'zipcode' => get_user_meta( $user_id, 'myd_customer_zipcode', true ),
                    );

                    // Remove campos vazios
                    $address = array_filter( $address );
                    
                    if ( ! empty( $address ) ) {
                        $data['customer']['address'] = array_merge( 
                            $data['customer']['address'] ?? array(), 
                            $address 
                        );
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Get customer profile for editing
     */
    public function get_customer_profile() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Usuário não logado.', 'myd-delivery-pro' ) ) );
            return;
        }

        wp_send_json_success( array( 'customer' => $this->get_current_customer_data() ) );
    }

    /**
     * Return the latest draft order for the current logged-in customer (if any)
     */
    public function get_customer_draft() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Usuário não logado.', 'myd-delivery-pro' ) ) );
            return;
        }

        $user_id = get_current_user_id();

        $orders = get_posts( array(
            'post_type' => 'mydelivery-orders',
            'posts_per_page' => 1,
            'post_status' => 'draft',
            'meta_key' => 'myd_customer_id',
            'meta_value' => $user_id,
            'orderby' => 'date',
            'order' => 'DESC'
        ) );

        if ( empty( $orders ) ) {
            wp_send_json_success( array() );
            return;
        }

        $order = $orders[0];
        $order_id = $order->ID;

        // Gather basic fields to provide to frontend
        $items = Myd_Orders_Front_Panel::parse_order_items( get_post_meta( $order_id, 'myd_order_items', true ) );

        $total = get_post_meta( $order_id, 'order_total', true ) ?: get_post_meta( $order_id, 'myd_order_total', true ) ?: '0.00';

        $customer = array(
            'name' => get_post_meta( $order_id, 'order_customer_name', true ),
            'phone' => get_post_meta( $order_id, 'customer_phone', true ),
            'address' => array(
                'street' => get_post_meta( $order_id, 'order_address', true ),
                'number' => get_post_meta( $order_id, 'order_address_number', true ),
                'complement' => get_post_meta( $order_id, 'order_address_comp', true ),
                'neighborhood' => get_post_meta( $order_id, 'order_neighborhood', true ),
                'zipcode' => get_post_meta( $order_id, 'order_zipcode', true ),
            )
        );

        $data = array(
            'id' => $order_id,
            'cart' => array(
                'items' => $items,
                'total' => $total,
                'itemsQuantity' => is_array( $items ) ? count( $items ) : 0,
            ),
            'subtotal' => $total,
            'total' => $total,
            'customer' => $customer,
        );

        wp_send_json_success( array( 'id' => $order_id, 'data' => $data ) );
    }

    /**
     * Update customer profile
     */
    public function update_customer_profile() {
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => __( 'Usuário não logado.', 'myd-delivery-pro' ) ) );
            return;
        }

        if ( ! wp_verify_nonce( $_POST['nonce'], 'myd_customer_auth' ) ) {
            wp_send_json_error( array( 
                'message' => __( 'Erro de segurança.', 'myd-delivery-pro' ),
                'customer' => $this->get_current_customer_data() 
            ) );
            return;
        }

        $user_id = get_current_user_id();
        $name = sanitize_text_field( $_POST['name'] );
        $phone = sanitize_text_field( $_POST['phone'] );
        // Remove tudo que não for dígito para validar comprimento
        $phone_digits = preg_replace( '/\D/', '', $phone );
        
        $birthdate = isset( $_POST['birthdate'] ) ? sanitize_text_field( $_POST['birthdate'] ) : '';

        if ( empty( $name ) || empty( $phone ) ) {
            wp_send_json_error( array( 
                'message' => __( 'Nome e telefone são obrigatórios.', 'myd-delivery-pro' ),
                'customer' => $this->get_current_customer_data() 
            ) );
            return;
        }

        // Validação de telefone (mínimo 11 dígitos)
        if ( strlen( $phone_digits ) !== 11 ) {
            wp_send_json_error( array( 
                'message' => __( 'O telefone deve ter 11 dígitos (DDD + número).', 'myd-delivery-pro' ),
                'customer' => $this->get_current_customer_data() 
            ) );
            return;
        }

        // Validação de nome (mínimo 4 caracteres sem considerar espaços)
        $name_no_spaces = str_replace( ' ', '', $name );
        if ( mb_strlen( $name_no_spaces ) < 4 ) {
            wp_send_json_error( array( 
                'message' => __( 'O nome deve ter pelo menos 4 letras (sem contar espaços).', 'myd-delivery-pro' ),
                'customer' => $this->get_current_customer_data() 
            ) );
            return;
        }

        // Atualiza nome e verifica erros
        $user_update_result = wp_update_user( array( 'ID' => $user_id, 'display_name' => $name ) );
        if ( is_wp_error( $user_update_result ) ) {
            wp_send_json_error( array( 'message' => $user_update_result->get_error_message() ) );
            return;
        }
        update_user_meta( $user_id, 'first_name', $name );

        // Atualiza telefone (verifica duplicata)
        if ( $this->phone_exists( $phone_digits, $user_id ) ) {
            wp_send_json_error( array( 
                'message' => __( 'Este telefone já está em uso por outro usuário.', 'myd-delivery-pro' ),
                'customer' => $this->get_current_customer_data() 
            ) );
            return;
        }
        
        // Salva apenas os dígitos para manter o padrão no banco
        update_user_meta( $user_id, 'myd_customer_phone', $phone_digits );

        // Atualiza data de nascimento se fornecida (normaliza para datetime)
        if ( ! empty( $birthdate ) ) {
            $normalized = $this->normalize_birthdate_to_datetime( $birthdate );
            if ( $normalized ) {
                update_user_meta( $user_id, 'myd_customer_birthdate', $normalized );
            } else {
                update_user_meta( $user_id, 'myd_customer_birthdate', $birthdate );
            }
        }

        // Atualiza endereço se fornecido
        if ( ! empty( $_POST['address'] ) ) {
            $this->save_customer_address( $user_id, $_POST['address'] );
        }

        wp_send_json_success( array(
            'message' => __( 'Perfil atualizado com sucesso!', 'myd-delivery-pro' ),
            'customer' => $this->get_current_customer_data()
        ));
    }

    /**
     * Verifica se um email já existe no sistema
     */
    public function handle_check_email_exists() {
        // Verifica nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'myd_customer_auth' ) ) {
            wp_send_json_error( array( 'message' => __( 'Erro de segurança', 'myd-delivery-pro' ) ) );
            return;
        }

        // Accept either email or CPF as identifier
        $identifier = isset( $_POST['identifier'] ) ? trim( wp_unslash( $_POST['identifier'] ) ) : '';
        if ( empty( $identifier ) ) {
            wp_send_json_error( array( 'message' => __( 'Identificador inválido', 'myd-delivery-pro' ) ) );
            return;
        }

        $exists = false;
        $type = 'unknown';
        $value = $identifier;

        if ( is_email( $identifier ) ) {
            $value = sanitize_email( $identifier );
            $user = get_user_by( 'email', $value );
            $exists = (bool) $user;
            $type = 'email';
        } else {
            // try CPF or Phone: digits only
            $digits = preg_replace( '/\D/', '', $identifier );
            if ( strlen( $digits ) === 10 || strlen( $digits ) === 11 ) {
                $users = get_users( array(
                    'meta_query' => array(
                        'relation' => 'OR',
                        array(
                            'key' => 'myd_customer_cpf',
                            'value' => $digits,
                            'compare' => '='
                        ),
                        array(
                            'key' => 'myd_customer_phone',
                            'value' => $digits,
                            'compare' => '='
                        )
                    ),
                    'number' => 1,
                ) );
                $exists = ! empty( $users );
                $type = strlen( $digits ) === 11 ? 'cpf' : 'phone'; // Simple heuristic, might be either
                $value = $digits;
            } else {
                // invalid identifier
                wp_send_json_error( array( 'message' => __( 'Email, CPF ou Telefone inválido', 'myd-delivery-pro' ) ) );
                return;
            }
        }

        $response = array(
            'exists' => $exists,
            'type' => $type,
            'value' => $value,
        );

        // If we resolved a user, include their email for display (useful when searching by CPF/Phone)
        if ( $exists ) {
            if ( $type === 'email' ) {
                $response['email'] = $value;
            } elseif ( $type === 'cpf' || $type === 'phone' ) {
                // get the user object found earlier
                if ( empty( $users ) ) {
                    // nothing
                } else {
                    $u = $users[0];
                    $response['email'] = $u->user_email;
                }
            }
        }

        wp_send_json_success( $response );
    }

    /**
     * Normalize various birthdate inputs to a MySQL datetime string (Y-m-d H:i:s)
     * Accepts: YYYY-MM-DD, YYYY-MM-DDTHH:MM:SS, DD/MM/YYYY, timestamp, or already datetime
     * Returns normalized datetime string or false on failure
     */
    private function normalize_birthdate_to_datetime( $value ) {
        if ( empty( $value ) ) return false;

        // If already in Y-m-d H:i:s format, try to parse
        $ts = false;

        // YYYY-MM-DD
        if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
            $ts = strtotime( $value . ' 00:00:00' );
        }

        // YYYY-MM-DDTHH:MM:SS or YYYY-MM-DD HH:MM:SS
        if ( $ts === false && preg_match( '/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}/', $value ) ) {
            $ts = strtotime( $value );
        }

        // DD/MM/YYYY
        if ( $ts === false && preg_match( '/^\d{2}\/\d{2}\/\d{4}$/', $value ) ) {
            $parts = explode( '/', $value );
            $d = intval( $parts[0] );
            $m = intval( $parts[1] );
            $y = intval( $parts[2] );
            $ts = mktime( 0, 0, 0, $m, $d, $y );
        }

        // Numeric timestamp
        if ( $ts === false && is_numeric( $value ) ) {
            $ts = intval( $value );
        }

        if ( $ts === false ) return false;

        return date( 'Y-m-d H:i:s', $ts );
    }
}
