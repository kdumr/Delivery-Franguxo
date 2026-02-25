<?php

namespace MydPro\Includes\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Gerenciador de Clientes Admin
 */
class Customers_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'wp_ajax_myd_admin_get_customers', array( $this, 'ajax_get_customers' ) );
        add_action( 'wp_ajax_myd_admin_delete_customer', array( $this, 'ajax_delete_customer' ) );
        add_action( 'wp_ajax_myd_admin_customer_details', array( $this, 'ajax_customer_details' ) );
        add_action( 'wp_ajax_myd_admin_update_customer', array( $this, 'ajax_update_customer' ) );
        add_action( 'wp_ajax_myd_admin_export_customers_csv', array( $this, 'ajax_export_customers_csv' ) );
        add_action( 'wp_ajax_myd_admin_add_loyalty_points', array( $this, 'ajax_add_loyalty_points' ) );
    }
    
    /**
     * Busca todos os clientes cadastrados
     */
    public function get_customers( $args = array() ) {
        $defaults = array(
            'number' => 20,
            'offset' => 0,
            'orderby' => 'registered',
            'order' => 'DESC',
            'search' => '',
            'role' => 'client'
        );
        
        $args = wp_parse_args( $args, $defaults );
        
        // Mapeia orderby para valores aceitos pelo WP_User_Query
        $orderby_map = array(
            'id' => 'ID',
            'nome' => 'display_name',
            'registered' => 'user_registered'
        );
        
        $query_orderby = isset( $orderby_map[ $args['orderby'] ] ) ? $orderby_map[ $args['orderby'] ] : 'user_registered';
        
        $user_query = new \WP_User_Query( array(
            'role' => $args['role'],
            'number' => $args['number'],
            'offset' => $args['offset'],
            'orderby' => $query_orderby,
            'order' => $args['order'],
            'search' => '*' . $args['search'] . '*',
            'search_columns' => array( 'user_login', 'user_nicename', 'user_email', 'display_name' ),
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => 'myd_customer_phone',
                    'value' => $args['search'],
                    'compare' => 'LIKE'
                ),
                array(
                    'key' => 'first_name',
                    'value' => $args['search'],
                    'compare' => 'LIKE'
                )
            )
        ));
        
        return $user_query;
    }
    
    /**
     * Conta total de clientes
     */
    public function get_customers_count() {
        $user_query = new \WP_User_Query( array(
            'role' => 'client',
            'count_total' => true,
            'fields' => 'ID'
        ));
        
        return $user_query->get_total();
    }
    
    /**
     * Busca pedidos de um cliente
     */
    public function get_customer_orders( $customer_id, $limit = 10 ) {
        $orders = get_posts( array(
            'post_type' => 'mydelivery-orders',
            'posts_per_page' => $limit,
            'post_status' => 'publish', // Apenas pedidos publicados (não rascunhos)
            'meta_query' => array(
                array(
                    'key' => 'myd_customer_id',
                    'value' => $customer_id,
                    'compare' => '='
                )
            ),
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        // Adiciona metadados aos pedidos
        foreach ( $orders as $order ) {
            $order->meta = array();
            
            // Tenta diferentes chaves para o total do pedido
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
            
            $order->meta['myd_order_total'] = $total ?: '0.00';
            $order->meta['myd_order_status'] = get_post_meta( $order->ID, 'myd_order_status', true );
            $order->meta['order_status'] = get_post_meta( $order->ID, 'order_status', true );
        }
        
        return $orders;
    }
    
    /**
     * Dados detalhados de um cliente
     */
    public function get_customer_details( $customer_id ) {
        $user = get_user_by( 'ID', $customer_id );
        if ( ! $user || ! in_array( 'client', $user->roles ) ) {
            return false;
        }

        $phone = get_user_meta( $customer_id, 'myd_customer_phone', true );
        $orders = $this->get_customer_orders( $customer_id );

        // Filtra apenas pedidos finalizados
        $finished_orders = array();
        foreach ( $orders as $order ) {
            $order_status = get_post_meta( $order->ID, 'order_status', true );
            if ( $order_status === 'finished' ) {
                $finished_orders[] = $order;
            }
        }

        $orders_count = count( $finished_orders );
        $total_spent = 0;
        $last_order_date = null;
        $order_values = array();

        foreach ( $finished_orders as $order ) {
            $total = get_post_meta( $order->ID, 'order_total', true );
            if ( empty( $total ) || $total == '0' || $total == '0.00' ) {
                $total = get_post_meta( $order->ID, 'myd_order_total', true );
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
            // Remove formatação de preço se houver (vírgulas, pontos extras, etc)
            if ( $total ) {
                $total = preg_replace( '/[^\d.,]/', '', $total );
                $total = str_replace( ',', '.', $total );
                $total = floatval( $total );
                if ( $total > 0 ) {
                    $total_spent += $total;
                    $order_values[] = $total;
                }
            }
            // Último pedido finalizado (mais recente)
            if ( ! $last_order_date || strtotime( $order->post_date ) > strtotime( $last_order_date ) ) {
                $last_order_date = $order->post_date;
            }
        }

        $valor_medio = $orders_count > 0 ? ($total_spent / $orders_count) : 0;

        return array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'phone' => $phone,
            'cpf' => get_user_meta( $customer_id, 'myd_customer_cpf', true ),
            'birthdate' => get_user_meta( $customer_id, 'myd_customer_birthdate', true ),
            'confirm_code' => get_user_meta( $customer_id, 'myd_delivery_confirm_code', true ),
            'registered' => $user->user_registered,
            'orders_count' => $orders_count, // Total de pedidos finalizados
            'total_spent' => $total_spent, // Valor total gasto (finalizados)
            'valor_medio' => $valor_medio, // Valor médio gasto (finalizados)
            'last_order_date' => $last_order_date, // Último pedido finalizado
            'orders' => $orders
            ,
            // Dados de fidelidade (se ativo)
            'loyalty' => $this->get_customer_loyalty_data( $customer_id )
        );
    }

    /**
     * Retorna dados de fidelidade para um cliente
     */
    public function get_customer_loyalty_data( $customer_id ) {
        $active = get_option( 'myd_fidelidade_ativo', 'off' ) === 'on';
        if ( ! $active ) return array( 'active' => false, 'points' => 0, 'redeemed_count' => 0 );

        $tipo = get_option( 'myd_fidelidade_tipo', 'loyalty_value' );
        $valor_raw = get_option( 'myd_fidelidade_valor', '' );
        $pontos_needed = intval( get_option( 'myd_fidelidade_pontos_necessarios', 0 ) );

        // last reset timestamp (user meta)
        $last_reset = get_user_meta( $customer_id, 'myd_loyalty_reset_at', true );

        // pega todos os pedidos do cliente (publicados)
        $orders = $this->get_customer_orders( $customer_id, -1 );

        // helper parse currency
        $parse_currency = function( $v ) {
            $v = str_replace( array( 'R$', ' ' ), '', $v );
            $v = str_replace( ',', '.', str_replace( '.', '', $v ) );
            return floatval( $v );
        };

        // now points are stored in usermeta as a single integer
        $points = intval( get_user_meta( $customer_id, 'myd_loyalty_points', true ) );
        $redeemed = 0;

        // count redeemed occurrences (all time), ignore canceled orders
        foreach ( $orders as $o ) {
            $order_status = get_post_meta( $o->ID, 'order_status', true );
            if ( $order_status === 'canceled' ) continue;
            $r = get_post_meta( $o->ID, 'order_loyalty_redeemed', true );
            if ( ! empty( $r ) && (string) $r === '1' ) $redeemed++;
        }

        // NOTE: points are maintained in usermeta by order workflows (increment/decrement)
        // The legacy computation from orders is no longer used; keep $points as stored value.

        return array(
            'active' => true,
            'points' => intval( $points ),
            'redeemed_count' => intval( $redeemed )
        );
    }
    
    /**
     * AJAX: Buscar clientes
     */
    public function ajax_get_customers() {
        check_ajax_referer( 'myd_admin_nonce', 'nonce' );
        
        if ( ! myd_user_is_allowed_admin() ) {
            wp_die( __( 'Unauthorized', 'myd-delivery-pro' ) );
        }
        
        $page = intval( $_POST['page'] ?? 1 );
        $search = sanitize_text_field( $_POST['search'] ?? '' );
        $orderby = sanitize_text_field( $_POST['orderby'] ?? 'registered' );
        $order = sanitize_text_field( $_POST['order'] ?? 'DESC' );
        $per_page = 20;
        $offset = ( $page - 1 ) * $per_page;
        
        $user_query = $this->get_customers( array(
            'number' => $per_page,
            'offset' => $offset,
            'search' => $search,
            'orderby' => $orderby,
            'order' => $order
        ));
        
        $customers = array();
        $users = $user_query->get_results();
        
        // Se ordenar por pedidos, precisamos calcular a contagem primeiro
        if ( $orderby === 'pedidos' ) {
            $users_with_orders = array();
            foreach ( $users as $user ) {
                $published_orders = get_posts( array(
                    'post_type' => 'mydelivery-orders',
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => 'myd_customer_id',
                            'value' => $user->ID,
                            'compare' => '='
                        ),
                        array(
                            'key' => 'order_status',
                            'value' => 'canceled',
                            'compare' => '!=',
                        )
                    ),
                    'fields' => 'ids'
                ));
                $users_with_orders[] = array(
                    'user' => $user,
                    'orders_count' => count( $published_orders )
                );
            }
            
            // Ordena por orders_count
            usort( $users_with_orders, function( $a, $b ) use ( $order ) {
                if ( $order === 'ASC' ) {
                    return $a['orders_count'] <=> $b['orders_count'];
                } else {
                    return $b['orders_count'] <=> $a['orders_count'];
                }
            });
            
            $users = array_column( $users_with_orders, 'user' );
        }
        
        foreach ( $users as $user ) {
            $phone = get_user_meta( $user->ID, 'myd_customer_phone', true );
            
            // Conta pedidos apenas se não for ordenação por pedidos (já calculado acima)
            if ( $orderby !== 'pedidos' ) {
                $published_orders = get_posts( array(
                    'post_type' => 'mydelivery-orders',
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                    'meta_query' => array(
                        'relation' => 'AND',
                        array(
                            'key' => 'myd_customer_id',
                            'value' => $user->ID,
                            'compare' => '='
                        ),
                        array(
                            'key' => 'order_status',
                            'value' => 'canceled',
                            'compare' => '!=',
                        )
                    ),
                    'fields' => 'ids'
                ));
                $orders_count = count( $published_orders );
            } else {
                // Já temos a contagem, encontra no array
                foreach ( $users_with_orders as $item ) {
                    if ( $item['user']->ID === $user->ID ) {
                        $orders_count = $item['orders_count'];
                        break;
                    }
                }
            }
            
            $customers[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'phone' => $phone,
                'registered' => date( 'd/m/Y', strtotime( $user->user_registered ) ),
                'orders_count' => $orders_count
            );
        }
        
        wp_send_json_success( array(
            'customers' => $customers,
            'total' => $user_query->get_total(),
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil( $user_query->get_total() / $per_page )
        ));
    }
    
    /**
     * AJAX: Detalhes do cliente
     */
    public function ajax_customer_details() {
        check_ajax_referer( 'myd_admin_nonce', 'nonce' );
        
        if ( ! myd_user_is_allowed_admin() ) {
            wp_die( __( 'Unauthorized', 'myd-delivery-pro' ) );
        }
        
        $customer_id = intval( $_POST['customer_id'] );
        $details = $this->get_customer_details( $customer_id );
        
        if ( ! $details ) {
            wp_send_json_error( __( 'Cliente não encontrado', 'myd-delivery-pro' ) );
        }
        
        wp_send_json_success( $details );
    }
    
    /**
     * AJAX: Deletar cliente
     */
    public function ajax_delete_customer() {
        check_ajax_referer( 'myd_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'delete_users' ) ) {
            wp_die( __( 'Unauthorized', 'myd-delivery-pro' ) );
        }
        
        $customer_id = intval( $_POST['customer_id'] );
        $user = get_user_by( 'ID', $customer_id );
        
        if ( ! $user || ! in_array( 'client', $user->roles ) ) {
            wp_send_json_error( __( 'Cliente não encontrado', 'myd-delivery-pro' ) );
        }
        
        // Remove o usuário
        $deleted = wp_delete_user( $customer_id );
        
        if ( $deleted ) {
            wp_send_json_success( __( 'Cliente removido com sucesso', 'myd-delivery-pro' ) );
        } else {
            wp_send_json_error( __( 'Erro ao remover cliente', 'myd-delivery-pro' ) );
        }
    }

    /**
     * AJAX: Atualizar cliente
     */
    public function ajax_update_customer() {
        check_ajax_referer( 'myd_admin_nonce', 'nonce' );

        if ( ! myd_user_is_allowed_admin() ) {
            wp_die( __( 'Unauthorized', 'myd-delivery-pro' ) );
        }

        $customer_id = intval( $_POST['customer_id'] ?? 0 );
        if ( ! $customer_id ) {
            wp_send_json_error( __( 'ID de cliente inválido', 'myd-delivery-pro' ) );
        }

        $user = get_user_by( 'ID', $customer_id );
        if ( ! $user || ! in_array( 'client', $user->roles ) ) {
            wp_send_json_error( __( 'Cliente não encontrado', 'myd-delivery-pro' ) );
        }

        // Sanitiza campos recebidos
        $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $cpf = isset( $_POST['cpf'] ) ? sanitize_text_field( wp_unslash( $_POST['cpf'] ) ) : '';
        $confirm_code = isset( $_POST['confirm_code'] ) ? sanitize_text_field( wp_unslash( $_POST['confirm_code'] ) ) : '';

        // Sanitiza o código de confirmação: aceita apenas dígitos
        if ( $confirm_code !== '' ) {
            $confirm_code = preg_replace( '/\D/', '', $confirm_code );
            if ( strlen( $confirm_code ) > 4 ) {
                wp_send_json_error( __( 'Código de confirmação deve ter no máximo 4 dígitos', 'myd-delivery-pro' ) );
            }
        }

        $update_data = array( 'ID' => $customer_id );
        if ( $name !== '' ) {
            $update_data['display_name'] = $name;
        }

        // Validação do email: formato e unicidade
        if ( $email !== '' ) {
            if ( ! is_email( $email ) ) {
                wp_send_json_error( __( 'Email inválido', 'myd-delivery-pro' ) );
            }
            $existing = get_user_by( 'email', $email );
            if ( $existing && intval( $existing->ID ) !== $customer_id ) {
                wp_send_json_error( __( 'Email já cadastrado para outro usuário', 'myd-delivery-pro' ) );
            }
            $update_data['user_email'] = $email;
        }

        // Atualiza usuário
        $result = wp_update_user( $update_data );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        // Validação e atualização de CPF e telefone
        if ( $cpf !== '' ) {
            // aceita apenas dígitos
            $cpf_digits = preg_replace( '/\D/', '', $cpf );
            if ( strlen( $cpf_digits ) !== 11 ) {
                wp_send_json_error( __( 'CPF inválido. Deve conter 11 dígitos.', 'myd-delivery-pro' ) );
            }
            // checa unicidade (compara dígitos)
            $all_users = get_users( array( 'fields' => array( 'ID' ) ) );
            foreach ( $all_users as $u ) {
                $other_cpf = get_user_meta( $u->ID, 'myd_customer_cpf', true );
                $other_cpf_digits = preg_replace( '/\D/', '', $other_cpf );
                if ( $other_cpf_digits && $other_cpf_digits === $cpf_digits && intval( $u->ID ) !== $customer_id ) {
                    wp_send_json_error( __( 'CPF já cadastrado para outro usuário', 'myd-delivery-pro' ) );
                }
            }
            update_user_meta( $customer_id, 'myd_customer_cpf', $cpf_digits );
        }

        if ( $phone !== '' ) {
            $phone_digits = preg_replace( '/\D/', '', $phone );
            if ( strlen( $phone_digits ) !== 11 ) {
                wp_send_json_error( __( 'Telefone inválido. Deve conter exatamente 11 dígitos.', 'myd-delivery-pro' ) );
            }
            // checa unicidade do telefone
            $all_users = get_users( array( 'fields' => array( 'ID' ) ) );
            foreach ( $all_users as $u ) {
                $other_phone = get_user_meta( $u->ID, 'myd_customer_phone', true );
                $other_phone_digits = preg_replace( '/\D/', '', $other_phone );
                if ( $other_phone_digits && $other_phone_digits === $phone_digits && intval( $u->ID ) !== $customer_id ) {
                    wp_send_json_error( __( 'Telefone já cadastrado para outro usuário', 'myd-delivery-pro' ) );
                }
            }
            update_user_meta( $customer_id, 'myd_customer_phone', $phone_digits );
        }
        if ( $confirm_code !== '' ) {
            update_user_meta( $customer_id, 'myd_delivery_confirm_code', $confirm_code );
        }

        // Retorna detalhes atualizados
        $details = $this->get_customer_details( $customer_id );
        wp_send_json_success( $details );
    }
    
    /**
     * AJAX: Exportar clientes em CSV
     */
    public function ajax_export_customers_csv() {
        check_ajax_referer( 'myd_admin_nonce', 'nonce' );
        
        if ( ! myd_user_is_allowed_admin() ) {
            wp_die( __( 'Unauthorized', 'myd-delivery-pro' ) );
        }
        
        $search = sanitize_text_field( $_POST['search'] ?? '' );
        
        // Busca todos os clientes sem paginação
        $user_query = $this->get_customers( array(
            'number' => -1, // Todos os clientes
            'offset' => 0,
            'search' => $search,
            'orderby' => 'registered',
            'order' => 'DESC'
        ));
        
        $customers = array();
        foreach ( $user_query->get_results() as $user ) {
            $phone = get_user_meta( $user->ID, 'myd_customer_phone', true );
            $confirm_code = get_user_meta( $user->ID, 'myd_delivery_confirm_code', true );
            $birthdate = get_user_meta( $user->ID, 'myd_customer_birthdate', true );
            $cpf = get_user_meta( $user->ID, 'myd_customer_cpf', true );
            
            // Conta pedidos (ignora cancelados)
            $published_orders = get_posts( array(
                'post_type' => 'mydelivery-orders',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'meta_query' => array(
                    'relation' => 'AND',
                    array(
                        'key' => 'myd_customer_id',
                        'value' => $user->ID,
                        'compare' => '='
                    ),
                    array(
                        'key' => 'order_status',
                        'value' => 'canceled',
                        'compare' => '!=',
                    )
                ),
                'fields' => 'ids'
            ));
            $orders_count = count( $published_orders );
            // Calcula valor total gasto
            $total_spent = 0;
            foreach ( $published_orders as $order_id ) {
                $order_total = get_post_meta( $order_id, 'myd_order_total', true );
                // Considera apenas valores numéricos válidos e maiores que zero
                if ( is_numeric($order_total) && floatval($order_total) > 0 ) {
                    $total_spent += floatval( $order_total );
                }
            }
            
            $customers[] = array(
                'ID' => $user->ID,
                'Nome' => $user->display_name,
                'Email' => $user->user_email,
                'Telefone' => $phone,
                'CPF' => $cpf,
                'Data_Nascimento' => $birthdate,
                'Data_Cadastro' => $user->user_registered,
                'Total_Pedidos' => $orders_count
            );
        }
        
        // Gera CSV
        $csv = "ID,Nome,Email,Telefone,CPF,Data_Nascimento,Data_Cadastro,Total_Pedidos\n";
        
        foreach ( $customers as $customer ) {
            $csv .= '"' . implode( '","', array_map( 'addslashes', $customer ) ) . "\"\n";
        }
        
        wp_send_json_success( array( 'csv' => $csv ) );
    }
    
    /**
     * Busca top clientes por número de pedidos
     */
    public function get_top_customers_by_orders( $limit = 3 ) {
        global $wpdb;
        
        $query = $wpdb->prepare( "
            SELECT 
                u.ID,
                u.display_name,
                u.user_email,
                COUNT(p.ID) as orders_count
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = '{$wpdb->prefix}capabilities'
            INNER JOIN {$wpdb->posts} p ON p.post_type = 'mydelivery-orders' AND p.post_status = 'publish'
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'myd_customer_id' AND pm.meta_value = u.ID
            LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'order_status'
            WHERE um.meta_value LIKE %s
              AND (pm_status.meta_value IS NULL OR pm_status.meta_value != 'canceled')
            GROUP BY u.ID
            ORDER BY orders_count DESC
            LIMIT %d
        ", '%"client"%', $limit );
        
        $results = $wpdb->get_results( $query );
        
        $top_customers = array();
        foreach ( $results as $result ) {
            $top_customers[] = array(
                'id' => $result->ID,
                'name' => $result->display_name,
                'email' => $result->user_email,
                'orders_count' => $result->orders_count
            );
        }
        
        return $top_customers;
    }
    
    /**
     * Busca top clientes por valor gasto
     */
    public function get_top_customers_by_spent( $limit = 3 ) {
        global $wpdb;
        
        $query = $wpdb->prepare( "
            SELECT 
                u.ID,
                u.display_name,
                u.user_email,
                SUM(CASE WHEN pm_total.meta_value IS NOT NULL AND pm_total.meta_value != '' AND pm_total.meta_value != '0' THEN CAST(pm_total.meta_value AS DECIMAL(10,2)) ELSE 0 END) as total_spent
            FROM {$wpdb->users} u
            INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = '{$wpdb->prefix}capabilities'
            INNER JOIN {$wpdb->posts} p ON p.post_type = 'mydelivery-orders' AND p.post_status = 'publish'
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = 'myd_customer_id' AND pm.meta_value = u.ID
            INNER JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id AND pm_total.meta_key = 'myd_order_total'
            LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'order_status'
            WHERE um.meta_value LIKE %s
              AND (pm_status.meta_value IS NULL OR pm_status.meta_value != 'canceled')
            GROUP BY u.ID
            ORDER BY total_spent DESC
            LIMIT %d
        ", '%"client"%', $limit );
        
        $results = $wpdb->get_results( $query );
        
        $top_customers = array();
        foreach ( $results as $result ) {
            $top_customers[] = array(
                'id' => $result->ID,
                'name' => $result->display_name,
                'email' => $result->user_email,
                'total_spent' => floatval( $result->total_spent )
            );
        }
        
        return $top_customers;
    }
    /**
     * AJAX: Adicionar pontos de fidelidade
     */
    public function ajax_add_loyalty_points() {
        check_ajax_referer( 'myd_admin_nonce', 'nonce' );

        if ( ! myd_user_is_allowed_admin() ) {
            wp_die( __( 'Unauthorized', 'myd-delivery-pro' ) );
        }

        $customer_id = intval( $_POST['customer_id'] ?? 0 );
        $points_to_add = intval( $_POST['points'] ?? 0 );

        if ( ! $customer_id || $points_to_add <= 0 ) {
            wp_send_json_error( __( 'Dados inválidos', 'myd-delivery-pro' ) );
        }

        $user = get_user_by( 'ID', $customer_id );
        if ( ! $user || ! in_array( 'client', $user->roles ) ) {
            wp_send_json_error( __( 'Cliente não encontrado', 'myd-delivery-pro' ) );
        }

        // Atualiza pontos
        $current_points = intval( get_user_meta( $customer_id, 'myd_loyalty_points', true ) );
        $new_points = $current_points + $points_to_add;
        update_user_meta( $customer_id, 'myd_loyalty_points', $new_points );

        // Calcula expiração
        $expiration_days = get_option( 'myd_fidelidade_expiracao', 'never' );
        
        if ( $expiration_days !== 'never' ) {
            $days = intval( $expiration_days );
            $expires_at = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + ( $days * DAY_IN_SECONDS ) );
            update_user_meta( $customer_id, 'myd_loyalty_expires_at', $expires_at );
        } else {
            // Se configurado como nunca, remove meta de expiração se existir
            delete_user_meta( $customer_id, 'myd_loyalty_expires_at' );
        }

        // Retorna detalhes atualizados
        $details = $this->get_customer_details( $customer_id );
        wp_send_json_success( $details );
    }
}
