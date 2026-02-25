<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Instancia o gerenciador se ainda não existe
if ( ! class_exists( 'MydPro\Includes\Admin\Customers_Manager' ) ) {
    require_once MYD_PLUGIN_PATH . 'includes/admin/class-customers-manager.php';
}

$customers_manager = new \MydPro\Includes\Admin\Customers_Manager();
$total_customers = $customers_manager->get_customers_count();

// Top clientes por pedidos
$top_orders = $customers_manager->get_top_customers_by_orders(3);
?>
<div class="wrap">
	<h1>
        <?php esc_html_e( 'Clientes do Delivery', 'myd-delivery-pro' ); ?>
        <span class="subtitle count">(<?php echo esc_html( $total_customers ); ?> <?php esc_html_e( 'clientes', 'myd-delivery-pro' ); ?>)</span>
    </h1>

    <!-- Rankings -->
    <div class="myd-customers-rankings" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
        <div style="display: flex; flex-direction: column; gap: 10px; max-width: 400px;">
            <h3 style="margin-top: 0; color: #23282d;"><?php esc_html_e( '🏆 Top 3 - Mais Pedidos', 'myd-delivery-pro' ); ?></h3>
            <?php if ( ! empty( $top_orders ) ) : ?>
                <?php foreach ( $top_orders as $index => $customer ) : ?>
                    <div style="display: flex; align-items: center; padding: 10px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #007cba;">
                        <span style="font-size: 18px; font-weight: bold; margin-right: 12px; color: #007cba;">#<?php echo $index + 1; ?></span>
                        <div style="flex: 1;">
                            <strong><?php echo esc_html( $customer['name'] ); ?></strong><br>
                            <small style="color: #666;"><?php echo esc_html( $customer['orders_count'] ); ?> pedidos</small>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p style="color: #666; font-style: italic;">Nenhum cliente encontrado</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filtros e Busca -->
    <div class="myd-customers-filters" style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
        <div style="display: flex; gap: 15px; align-items: center;">
            <div>
                <input type="text" id="customers-search" placeholder="Buscar por nome, email ou telefone..." 
                       style="width: 300px; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div>
                <button type="button" id="search-customers" class="button button-secondary">
                    <?php esc_html_e( 'Buscar', 'myd-delivery-pro' ); ?>
                </button>
                <button type="button" id="clear-search" class="button">
                    <?php esc_html_e( 'Limpar', 'myd-delivery-pro' ); ?>
                </button>
                <button type="button" id="export-customers-csv" class="button button-primary">
                    <?php esc_html_e( 'Exportar CSV', 'myd-delivery-pro' ); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Loading -->
    <div id="customers-loading" style="display: none; text-align: center; padding: 40px;">
        <span class="spinner is-active" style="float: none; margin: 0;"></span>
        <p><?php esc_html_e( 'Carregando clientes...', 'myd-delivery-pro' ); ?></p>
    </div>

    <!-- Tabela de Clientes -->
    <div id="customers-table-container">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px; cursor: pointer;" data-orderby="id"><?php esc_html_e( 'ID', 'myd-delivery-pro' ); ?> <span class="sort-indicator"></span></th>
                    <th style="cursor: pointer;" data-orderby="nome"><?php esc_html_e( 'Nome', 'myd-delivery-pro' ); ?> <span class="sort-indicator"></span></th>
                    <th><?php esc_html_e( 'Email', 'myd-delivery-pro' ); ?></th>
                    <th><?php esc_html_e( 'Telefone', 'myd-delivery-pro' ); ?></th>
                    <th style="cursor: pointer;" data-orderby="registered"><?php esc_html_e( 'Cadastro', 'myd-delivery-pro' ); ?> <span class="sort-indicator"></span></th>
                    <th style="cursor: pointer;" data-orderby="pedidos"><?php esc_html_e( 'Pedidos', 'myd-delivery-pro' ); ?> <span class="sort-indicator"></span></th>
                    <th style="width: 150px;"><?php esc_html_e( 'Ações', 'myd-delivery-pro' ); ?></th>
                </tr>
            </thead>
            <tbody id="customers-tbody">
                <!-- Dados serão carregados via AJAX -->
            </tbody>
        </table>
    </div>

    <!-- Paginação -->
    <div id="customers-pagination" style="margin-top: 20px; text-align: center;">
        <!-- Paginação será gerada via JavaScript -->
    </div>

    <!-- Modal de Detalhes do Cliente -->
    <div id="customer-details-modal" style="display: none;">
        <div class="myd-modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 100000;"></div>
        <div class="myd-modal-content" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; border-radius: 8px; padding: 0; max-width: 800px; width: 90%; max-height: 90%; overflow-y: auto; z-index: 100001; box-shadow: 0 5px 25px rgba(0,0,0,0.5);">
            <div class="myd-modal-header" style="padding: 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0;"><?php esc_html_e( 'Detalhes do Cliente', 'myd-delivery-pro' ); ?></h2>
                <button type="button" class="myd-modal-close" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
            </div>
            <div class="myd-modal-body" style="padding: 20px;">
                <div id="customer-details-content">
                    <!-- Conteúdo será carregado via AJAX -->
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.myd-customers-stat {
    display: inline-block;
    background: #f1f1f1;
    padding: 8px 12px;
    border-radius: 4px;
    margin-right: 10px;
    font-size: 13px;
}
.myd-customer-actions {
    display: flex;
    gap: 5px;
}
.myd-customer-actions .button {
    font-size: 12px;
    padding: 4px 8px;
    height: auto;
    line-height: 1.4;
    border: none;
}
.myd-customer-orders {
    margin-top: 20px;
}
.myd-customer-orders table {
    width: 100%;
    border-collapse: collapse;
}
.myd-customer-orders th,
.myd-customer-orders td {
    padding: 8px 12px;
    border: 1px solid #ddd;
    text-align: left;
}
.myd-customer-orders th {
    background: #f9f9f9;
    font-weight: bold;
}
.myd-no-customers {
    text-align: center;
    padding: 40px;
    color: #666;
}
.sort-indicator {
    display: none;
    margin-left: 5px;
    transition: opacity 0.2s;
}
th[data-orderby]:hover .sort-indicator {
    display: inline-block;
    opacity: 1;
}
th[data-orderby].sorted-asc .sort-indicator:before {
    content: "↑";
}
th[data-orderby].sorted-desc .sort-indicator:before {
    content: "↓";
}
th[data-orderby].sorted-asc .sort-indicator,
th[data-orderby].sorted-desc .sort-indicator {
    display: inline-block;
    opacity: 1;
}
th[data-orderby].sorted-asc,
th[data-orderby].sorted-desc {
    font-weight: bold;
}
</style>

<script>
jQuery(document).ready(function($) {
    let currentPage = 1;
    let currentSearch = '';
    let currentOrderby = 'registered';
    let currentOrder = 'DESC';
    
    // Carrega clientes inicialmente
    loadCustomers();
    updateSortIndicators();
    
    // Busca
    $('#search-customers').on('click', function() {
        currentSearch = $('#customers-search').val();
        currentPage = 1;
        loadCustomers();
    });
    
    // Enter na busca
    $('#customers-search').on('keypress', function(e) {
        if (e.which === 13) {
            $('#search-customers').trigger('click');
        }
    });
    
    // Limpar busca
    $('#clear-search').on('click', function() {
        $('#customers-search').val('');
        currentSearch = '';
        currentPage = 1;
        loadCustomers();
    });
    
    // Exportar CSV
    $('#export-customers-csv').on('click', function() {
        exportCustomersCSV();
    });
    
    // Função para exportar clientes em CSV
    function exportCustomersCSV() {
        const button = $('#export-customers-csv');
        const originalText = button.text();
        
        button.prop('disabled', true).text('Exportando...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'myd_admin_export_customers_csv',
                nonce: '<?php echo wp_create_nonce( 'myd_admin_nonce' ); ?>',
                search: currentSearch
            },
            success: function(response) {
                if (response.success) {
                    // Criar e baixar o arquivo CSV
                    const csvContent = response.data.csv;
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const link = document.createElement('a');
                    
                    if (link.download !== undefined) {
                        const url = URL.createObjectURL(blob);
                        link.setAttribute('href', url);
                        link.setAttribute('download', 'clientes_' + new Date().toISOString().split('T')[0] + '.csv');
                        link.style.visibility = 'hidden';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                } else {
                    alert('Erro ao exportar clientes: ' + (response.data || 'Erro desconhecido'));
                }
            },
            error: function() {
                alert('Erro na comunicação com o servidor');
            },
            complete: function() {
                button.prop('disabled', false).text(originalText);
            }
        });
    }
    
    // Ordenação
    $('th[data-orderby]').on('click', function() {
        const orderby = $(this).data('orderby');
        
        if (currentOrderby === orderby) {
            // Mesmo campo, alterna direção
            currentOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
        } else {
            // Novo campo, começa com DESC
            currentOrderby = orderby;
            currentOrder = 'DESC';
        }
        
        currentPage = 1;
        updateSortIndicators();
        loadCustomers();
    });
    
    // Atualiza indicadores de ordenação
    function updateSortIndicators() {
        // Remove classes de todos os cabeçalhos ordenáveis
        $('th[data-orderby]').each(function() {
            $(this).removeClass('sorted-asc sorted-desc');
        });
        
        // Adiciona classe ao cabeçalho ativo
        const activeTh = $(`th[data-orderby="${currentOrderby}"]`);
        if (activeTh.length > 0) {
            activeTh.addClass(currentOrder === 'ASC' ? 'sorted-asc' : 'sorted-desc');
        }
        
        // Debug
        console.log('Current orderby:', currentOrderby, 'Current order:', currentOrder);
    }
    
    // Fechar modal
    $(document).on('click', '.myd-modal-close, .myd-modal-overlay', function() {
        $('#customer-details-modal').hide();
    });
    
    // Função para carregar clientes
    function loadCustomers() {
        $('#customers-loading').show();
        $('#customers-table-container').hide();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'myd_admin_get_customers',
                nonce: '<?php echo wp_create_nonce( 'myd_admin_nonce' ); ?>',
                page: currentPage,
                search: currentSearch,
                orderby: currentOrderby,
                order: currentOrder
            },
            success: function(response) {
                $('#customers-loading').hide();
                $('#customers-table-container').show();
                
                if (response.success) {
                    renderCustomers(response.data.customers);
                    renderPagination(response.data);
                } else {
                    alert('Erro ao carregar clientes');
                }
            },
            error: function() {
                $('#customers-loading').hide();
                alert('Erro na comunicação com o servidor');
            }
        });
    }
    
    // Renderiza lista de clientes
    function renderCustomers(customers) {
        let html = '';
        
        // Helper function para formatar telefone (ex.: (XX) XXXXX-XXXX)
        function formatPhone(phone) {
            if (!phone) return 'N/A';
            const digits = ('' + phone).replace(/\D/g,'').slice(0,11);
            if (digits.length <= 2) return digits;
            if (digits.length <= 6) return '(' + digits.slice(0,2) + ') ' + digits.slice(2);
            if (digits.length <= 10) return '(' + digits.slice(0,2) + ') ' + digits.slice(2,6) + '-' + digits.slice(6);
            return '(' + digits.slice(0,2) + ') ' + digits.slice(2,7) + '-' + digits.slice(7);
        }
        
        if (customers.length === 0) {
            html = '<tr><td colspan="7" class="myd-no-customers">Nenhum cliente encontrado</td></tr>';
        } else {
            customers.forEach(function(customer) {
                html += `
                    <tr>
                        <td>${customer.id}</td>
                        <td><strong>${customer.name || 'N/A'}</strong></td>
                        <td>${customer.email}</td>
                        <td>${formatPhone(customer.phone)}</td>
                        <td>${customer.registered}</td>
                        <td><span class="myd-customers-stat">${customer.orders_count} pedidos</span></td>
                        <td>
                            <div class="myd-customer-actions">
                                <button type="button" class="button button-small view-customer" data-id="${customer.id}" title="Visualizar">
                                    <svg fill="#424242" viewBox="0 0 32 32" width="20" height="20" version="1.1" xmlns="http://www.w3.org/2000/svg" stroke="#9c9c9c" style="vertical-align: middle;"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>eye</title> <path d="M0 16q0.064 0.128 0.16 0.352t0.48 0.928 0.832 1.344 1.248 1.536 1.664 1.696 2.144 1.568 2.624 1.344 3.136 0.896 3.712 0.352 3.712-0.352 3.168-0.928 2.592-1.312 2.144-1.6 1.664-1.632 1.248-1.6 0.832-1.312 0.48-0.928l0.16-0.352q-0.032-0.128-0.16-0.352t-0.48-0.896-0.832-1.344-1.248-1.568-1.664-1.664-2.144-1.568-2.624-1.344-3.136-0.896-3.712-0.352-3.712 0.352-3.168 0.896-2.592 1.344-2.144 1.568-1.664 1.664-1.248 1.568-0.832 1.344-0.48 0.928zM10.016 16q0-2.464 1.728-4.224t4.256-1.76 4.256 1.76 1.76 4.224-1.76 4.256-4.256 1.76-4.256-1.76-1.728-4.256zM12 16q0 1.664 1.184 2.848t2.816 1.152 2.816-1.152 1.184-2.848-1.184-2.816-2.816-1.184-2.816 1.184l2.816 2.816h-4z"></path> </g></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }
        
        $('#customers-tbody').html(html);
    }
    
    // Renderiza paginação
    function renderPagination(data) {
        let html = '';
        
        if (data.total_pages > 1) {
            html += `<div style="display: flex; align-items: center; justify-content: center; gap: 10px;">`;
            
            // Botão anterior
            if (data.page > 1) {
                html += `<button type="button" class="button pagination-btn" data-page="${data.page - 1}">« Anterior</button>`;
            }
            
            // Números das páginas
            for (let i = 1; i <= data.total_pages; i++) {
                if (i === data.page) {
                    html += `<span class="button button-primary" style="pointer-events: none;">${i}</span>`;
                } else {
                    html += `<button type="button" class="button pagination-btn" data-page="${i}">${i}</button>`;
                }
            }
            
            // Botão próximo
            if (data.page < data.total_pages) {
                html += `<button type="button" class="button pagination-btn" data-page="${data.page + 1}">Próximo »</button>`;
            }
            
            html += `</div>`;
            html += `<p style="text-align: center; margin-top: 10px; color: #666;">
                        Mostrando ${((data.page - 1) * data.per_page) + 1} a ${Math.min(data.page * data.per_page, data.total)} de ${data.total} clientes
                     </p>`;
        }
        
        $('#customers-pagination').html(html);
    }
    
    // Clique na paginação
    $(document).on('click', '.pagination-btn', function() {
        currentPage = parseInt($(this).data('page'));
        loadCustomers();
    });
    
    // Ver detalhes do cliente
    $(document).on('click', '.view-customer', function() {
        const customerId = $(this).data('id');
        loadCustomerDetails(customerId);
    });
    
    // Excluir cliente (abre modal de confirmação customizada)
    $(document).on('click', '.delete-customer', function() {
        const customerId = $(this).data('id');
        const customerName = $(this).closest('tr').find('td:nth-child(2)').text().trim();
        showDeleteModal(customerId, customerName, false);
    });
    
    // Carrega detalhes do cliente
    function loadCustomerDetails(customerId) {
        $('#customer-details-content').html('<div style="text-align: center; padding: 20px;"><span class="spinner is-active"></span> Carregando...</div>');
        $('#customer-details-modal').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'myd_admin_customer_details',
                nonce: '<?php echo wp_create_nonce( 'myd_admin_nonce' ); ?>',
                customer_id: customerId
            },
            success: function(response) {
                if (response.success) {
                    renderCustomerDetails(response.data);
                } else {
                    $('#customer-details-content').html('<p>Erro ao carregar detalhes do cliente.</p>');
                }
            },
            error: function() {
                $('#customer-details-content').html('<p>Erro na comunicação com o servidor.</p>');
            }
        });
    }
    
    // Renderiza detalhes do cliente
    function renderCustomerDetails(customer) {
        const lastOrderDate = customer.last_order_date ? new Date(customer.last_order_date).toLocaleDateString('pt-BR') : 'Nunca';
        
        function formatBirthdate(dateStr) {
            if (!dateStr) return 'N/A';
            // YYYY-MM-DD ou YYYY-MM-DD HH:MM:SS
            let main = dateStr.split(' ')[0];
            let parts = main.split('-');
            if (parts.length === 3) {
                // Garante dois dígitos
                let d = parts[2].padStart(2, '0');
                let m = parts[1].padStart(2, '0');
                let y = parts[0];
                return `${d}/${m}/${y}`;
            }
            // DD/MM/YYYY
            if (/^\d{2}\/\d{2}\/\d{4}$/.test(dateStr)) return dateStr;
            return dateStr;
        }

        let html = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                <div>
                    <h3>Informações Pessoais</h3>
                    <p><strong>Nome:</strong> ${customer.name || 'N/A'}</p>
                    <p><strong>Código de Confirmação:</strong> ${customer.confirm_code ? (customer.confirm_code.replace(/(\d{2})(\d{2})/, '$1 $2')) : 'N/A'}</p>
                    <p><strong>Data de Nascimento:</strong> ${formatBirthdate(customer.birthdate)}</p>
                    <p><strong>Email:</strong> ${customer.email}</p>
                    <p><strong>Telefone:</strong> ${formatPhone(customer.phone) || 'N/A'}</p>
                    <p><strong>CPF:</strong> ${formatCpf(customer.cpf) || 'N/A'}</p>
                    <p><strong>Cadastro:</strong> ${new Date(customer.registered).toLocaleDateString('pt-BR')}</p>
                    <div style="margin-top:20px;">
                        <button type="button" class="button button-small edit-customer-modal" style="margin-right:8px;">Editar Cliente</button>
                        <button type="button" class="button button-small delete-customer-modal" style="color: #a00;">Excluir Cliente</button>
                    </div>
                </div>
                <div>
                    <h3>Estatísticas</h3>
                    <p><strong>Total de pedidos finalizados:</strong> ${customer.orders_count}</p>
                    <p><strong>Valor Total Gasto:</strong> R$ ${customer.total_spent.toFixed(2)}</p>
                    <p><strong>Último pedido finalizado:</strong> ${lastOrderDate}</p>
                    <p><strong>Valor Médio:</strong> R$ ${customer.orders_count > 0 ? (customer.total_spent / customer.orders_count).toFixed(2) : '0.00'}</p>
                    ${customer.loyalty && customer.loyalty.active ? `
                        <div style="margin-top:16px;" class="loyalty-info-container">
                            <h3>Fidelidade</h3>
                            <p><strong>Quantidade de pontos:</strong> <span class="loyalty-points-count">${typeof customer.loyalty.points !== 'undefined' ? customer.loyalty.points : '0'}</span></p>
                            <p><strong>Prêmios resgatados:</strong> ${typeof customer.loyalty.redeemed_count !== 'undefined' ? customer.loyalty.redeemed_count : '0'}</p>
                            <div style="margin-top: 10px; display: flex; gap: 8px; align-items: center;">
                                <input type="number" id="manual-loyalty-points" value="1" min="1" style="width: 60px; padding: 4px;">
                                <button type="button" class="button button-secondary add-loyalty-points-btn" data-id="${customer.id}">Dar Pontos</button>
                            </div>
                        </div>
                    ` : ``}
                </div>
            </div>
        `;

        // Helper function para formatar CPF (000.000.000-00)
        function formatCpf(cpf) {
            if (!cpf) return '';
            const digits = ('' + cpf).replace(/\D/g, '');
            if (digits.length !== 11) return cpf; // retorna cru se inválido
            return digits.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
        }

        // Helper function para formatar telefone (ex.: (XX) XXXXX-XXXX)
        function formatPhone(phone) {
            if (!phone) return '';
            const digits = ('' + phone).replace(/\D/g, '').slice(0,11);
            if (digits.length <= 2) return digits;
            if (digits.length <= 6) return '(' + digits.slice(0,2) + ') ' + digits.slice(2);
            if (digits.length <= 10) return '(' + digits.slice(0,2) + ') ' + digits.slice(2,6) + '-' + digits.slice(6);
            return '(' + digits.slice(0,2) + ') ' + digits.slice(2,7) + '-' + digits.slice(7);
        }
        
        if (customer.orders && customer.orders.length > 0) {
            html += `
                <div class="myd-customer-orders">
                    <h3>Últimos Pedidos</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Data</th>
                                <th>Status</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            function mapOrderStatus(status) {
                switch (status) {
                    case 'publish':
                        return 'Publicado';
                    case 'new':
                        return 'Novo';
                    case 'confirmed':
                        return 'Confirmado';
                    case 'in-delivery':
                        return 'Em Entrega';
                    case 'finished':
                    case 'done':
                        return 'Concluído';
                    case 'waiting':
                        return 'Aguardando';
                    case 'canceled':
                        return 'Cancelado';
                    default:
                        return status.charAt(0).toUpperCase() + status.slice(1);
                }
            }

            customer.orders.forEach(function(order) {
                const orderDate = new Date(order.post_date).toLocaleDateString('pt-BR');
                const orderTotal = order.meta?.myd_order_total || '0.00';
                const orderStatus = order.meta?.order_status || 'Sem status';
                const canceledStyle = orderStatus === 'canceled' ? 'style="background:#ffcccc"' : '';
                const statusLabel = mapOrderStatus(orderStatus);

                html += `
                    <tr ${canceledStyle}>
                        <td>#${order.ID}</td>
                        <td>${orderDate}</td>
                        <td>${statusLabel}</td>
                        <td>R$ ${parseFloat(orderTotal).toFixed(2)}</td>
                    </tr>
                `;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
        } else {
            html += '<div class="myd-customer-orders"><h3>Pedidos</h3><p>Este cliente ainda não fez nenhum pedido.</p></div>';
        }
        
        $('#customer-details-content').html(html);

        // Adiciona evento ao botão excluir do modal (abre popup de confirmação)
        $('.delete-customer-modal').off('click').on('click', function() {
            showDeleteModal(customer.id, customer.name || '', true);
        });

        // Botão Editar - abre popup/modal de edição
        $('.edit-customer-modal').off('click').on('click', function() {
            // Cria o modal de edição uma vez, se ainda não existir
            if ( $('#customer-edit-modal').length === 0 ) {
                const modalHtml = `
                    <div id="customer-edit-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:100002;">
                        <div class="customer-edit-overlay" style="position:absolute; inset:0; background:rgba(0,0,0,0.7);"></div>
                        <div class="customer-edit-content" style="position:relative; max-width:560px; margin:60px auto; background:#fff; border-radius:8px; padding:20px; z-index:100003;">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                                <h3 style="margin:0;">Editar Cliente</h3>
                                <button type="button" class="customer-edit-close button" style="background:none; border:none; font-size:20px;">&times;</button>
                            </div>
                            <div class="customer-edit-body" style="display:flex; flex-direction:column; gap:8px;">
                                <label>Nome:<br><input type="text" id="edit-name-input" style="width:100%; padding:6px;"></label>
                                <label>Código de Confirmação:<br><input type="text" id="edit-confirm-code-input" inputmode="numeric" pattern="[0-9]*" maxlength="4" oninput="this.value = this.value.replace(/\\D/g,'').slice(0,4)" style="width:100%; padding:6px;"></label>
                                <label>Email:<br><input type="email" id="edit-email-input" style="width:100%; padding:6px;"></label>
                                <label>Telefone:<br><input type="text" id="edit-phone-input" style="width:100%; padding:6px;"></label>
                                <label>CPF:<br><input type="text" id="edit-cpf-input" style="width:100%; padding:6px;"></label>
                            </div>
                            <div style="margin-top:12px; display:flex; gap:8px; justify-content:flex-end;">
                                <button type="button" class="button" id="customer-edit-cancel">Cancelar</button>
                                <button type="button" class="button button-primary" id="customer-edit-save">Salvar</button>
                            </div>
                        </div>
                    </div>
                `;
                $('body').append(modalHtml);

                // eventos de fechamento do modal
                $(document).on('click', '#customer-edit-modal .customer-edit-close, #customer-edit-modal .customer-edit-overlay, #customer-edit-cancel', function() {
                    $('#customer-edit-modal').hide();
                });
            }

            // Preenche os campos com os valores atuais do cliente (formatados)
            $('#edit-name-input').val(customer.name || '');
            $('#edit-confirm-code-input').val(customer.confirm_code || '');
            $('#edit-email-input').val(customer.email || '');
            $('#edit-phone-input').val(formatPhone(customer.phone || ''));
            $('#edit-cpf-input').val(formatCpf(customer.cpf || ''));

            // Abre o modal
            $('#customer-edit-modal').show();

            // Desabilita o botão Salvar até que haja mudanças
            $('#customer-edit-save').prop('disabled', true);

            // Guarda valores originais para comparação (sem formatação quando aplicável)
            const originalValues = {
                name: (customer.name || '').toString(),
                confirm_code: (customer.confirm_code || '').toString().replace(/\D/g,''),
                email: (customer.email || '').toString().trim(),
                phone: (customer.phone || '').toString().replace(/\D/g,''),
                cpf: (customer.cpf || '').toString().replace(/\D/g,'')
            };

            function checkDirty() {
                const current = {
                    name: $('#edit-name-input').val().toString(),
                    confirm_code: $('#edit-confirm-code-input').val().toString().replace(/\D/g,''),
                    email: $('#edit-email-input').val().toString().trim(),
                    phone: $('#edit-phone-input').val().toString().replace(/\D/g,''),
                    cpf: $('#edit-cpf-input').val().toString().replace(/\D/g,'')
                };

                const changed = current.name !== originalValues.name
                    || current.confirm_code !== originalValues.confirm_code
                    || current.email !== originalValues.email
                    || current.phone !== originalValues.phone
                    || current.cpf !== originalValues.cpf;

                $('#customer-edit-save').prop('disabled', !changed);
            }

            // Observa alterações nos inputs
            $('#edit-name-input, #edit-confirm-code-input, #edit-email-input, #edit-phone-input, #edit-cpf-input').off('input change').on('input change', function() {
                checkDirty();
            });

            // Aplica formatação ao digitar (usa formatCpf/formatPhone do escopo pai)
            $('#edit-cpf-input').off('input').on('input', function() {
                const raw = $(this).val().replace(/\D/g,'').slice(0,11);
                $(this).val( formatCpf(raw) );
            });

            $('#edit-phone-input').off('input').on('input', function() {
                const raw = $(this).val().replace(/\D/g,'').slice(0,11);
                $(this).val( formatPhone(raw) );
            });

            // Evento salvar
            $('#customer-edit-save').off('click').on('click', function() {
                const emailVal = $('#edit-email-input').val().trim();
                if ( emailVal && emailVal.indexOf('@') === -1 ) {
                    alert('Email inválido');
                    return;
                }

                // Prepara valores sem formatação para envio
                const cpfDigits = $('#edit-cpf-input').val().replace(/\D/g,'');
                const phoneDigits = $('#edit-phone-input').val().replace(/\D/g,'');

                // Validações cliente-side
                if ( cpfDigits && cpfDigits.length !== 11 ) {
                    alert('CPF inválido. Informe 11 dígitos.');
                    return;
                }
                if ( phoneDigits && phoneDigits.length !== 11 ) {
                    alert('Telefone inválido. Informe exatamente 11 dígitos.');
                    return;
                }

                const payload = {
                    action: 'myd_admin_update_customer',
                    nonce: '<?php echo wp_create_nonce( 'myd_admin_nonce' ); ?>',
                    customer_id: customer.id,
                    name: $('#edit-name-input').val(),
                    confirm_code: $('#edit-confirm-code-input').val(),
                    email: emailVal,
                    phone: phoneDigits,
                    cpf: cpfDigits
                };

                $('#customer-edit-save').prop('disabled', true).text('Salvando...');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: payload,
                    success: function(response) {
                        if (response.success) {
                            $('#customer-edit-modal').hide();
                            renderCustomerDetails(response.data);
                            loadCustomers();
                        } else {
                            alert('Erro ao atualizar cliente: ' + (response.data || 'Erro desconhecido'));
                        }
                    },
                    error: function() {
                        alert('Erro na comunicação com o servidor');
                    },
                    complete: function() {
                        $('#customer-edit-save').prop('disabled', false).text('Salvar');
                    }
                });
            });
        });
    }

    // Dar pontos de fidelidade
    $(document).on('click', '.add-loyalty-points-btn', function() {
        const customerId = $(this).data('id');
        const points = $('#manual-loyalty-points').val();
        const btn = $(this);
        const originalText = btn.text();

        if (!points || points <= 0) {
            alert('Informe uma quantidade de pontos válida.');
            return;
        }

        btn.prop('disabled', true).text('Processando...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'myd_admin_add_loyalty_points',
                nonce: '<?php echo wp_create_nonce( 'myd_admin_nonce' ); ?>',
                customer_id: customerId,
                points: points
            },
            success: function(response) {
                if (response.success) {
                    alert('Pontos adicionados com sucesso!');
                    renderCustomerDetails(response.data);
                } else {
                    alert('Erro ao adicionar pontos: ' + (response.data || 'Erro desconhecido'));
                }
            },
            error: function() {
                alert('Erro na comunicação com o servidor');
            },
            complete: function() {
                btn.prop('disabled', false).text(originalText);
            }
        });
    });
    
    // Excluir cliente
    function deleteCustomer(customerId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'myd_admin_delete_customer',
                nonce: '<?php echo wp_create_nonce( 'myd_admin_nonce' ); ?>',
                customer_id: customerId
            },
            success: function(response) {
                if (response.success) {
                    alert('Cliente excluído com sucesso!');
                    loadCustomers(); // Recarrega a lista
                } else {
                    alert('Erro ao excluir cliente: ' + (response.data || 'Erro desconhecido'));
                }
            },
            error: function() {
                alert('Erro na comunicação com o servidor');
            }
        });
    }

    // Mostra modal de confirmação de exclusão (cria se necessário)
    function showDeleteModal(customerId, customerName, hideDetailsAfter) {
        if ($('#customer-delete-modal').length === 0) {
            const modalHtml = `
                <div id="customer-delete-modal" style="display:none; position:fixed; inset:0; z-index:100020;">
                    <div class="cdm-overlay" style="position:absolute; inset:0; background:rgba(0,0,0,0.6);"></div>
                    <div class="cdm-content" style="position:relative; width:480px; max-width:90%; margin:80px auto; background:#fff; border-radius:8px; padding:24px; text-align:center;">
                        <div style="display:flex; justify-content:center;">
                            <!-- SVG ícone -->
                            <div style="width:80px; height:80px;">
                                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M10.3094 2.25002H13.6908C13.9072 2.24988 14.0957 2.24976 14.2737 2.27819C14.977 2.39049 15.5856 2.82915 15.9146 3.46084C15.9978 3.62073 16.0573 3.79961 16.1256 4.00494L16.2373 4.33984C16.2562 4.39653 16.2616 4.41258 16.2661 4.42522C16.4413 4.90933 16.8953 5.23659 17.4099 5.24964C17.4235 5.24998 17.44 5.25004 17.5001 5.25004H20.5001C20.9143 5.25004 21.2501 5.58582 21.2501 6.00004C21.2501 6.41425 20.9143 6.75004 20.5001 6.75004H3.5C3.08579 6.75004 2.75 6.41425 2.75 6.00004C2.75 5.58582 3.08579 5.25004 3.5 5.25004H6.50008C6.56013 5.25004 6.5767 5.24998 6.59023 5.24964C7.10488 5.23659 7.55891 4.90936 7.73402 4.42524C7.73863 4.41251 7.74392 4.39681 7.76291 4.33984L7.87452 4.00496C7.94281 3.79964 8.00233 3.62073 8.08559 3.46084C8.41453 2.82915 9.02313 2.39049 9.72643 2.27819C9.90445 2.24976 10.093 2.24988 10.3094 2.25002ZM9.00815 5.25004C9.05966 5.14902 9.10531 5.04404 9.14458 4.93548C9.1565 4.90251 9.1682 4.86742 9.18322 4.82234L9.28302 4.52292C9.37419 4.24941 9.39519 4.19363 9.41601 4.15364C9.52566 3.94307 9.72853 3.79686 9.96296 3.75942C10.0075 3.75231 10.067 3.75004 10.3553 3.75004H13.6448C13.9331 3.75004 13.9927 3.75231 14.0372 3.75942C14.2716 3.79686 14.4745 3.94307 14.5842 4.15364C14.605 4.19363 14.626 4.2494 14.7171 4.52292L14.8169 4.82216L14.8556 4.9355C14.8949 5.04405 14.9405 5.14902 14.992 5.25004H9.00815Z" fill="#ff2424"></path> <path d="M5.91509 8.45015C5.88754 8.03685 5.53016 7.72415 5.11686 7.7517C4.70357 7.77925 4.39086 8.13663 4.41841 8.54993L4.88186 15.5017C4.96736 16.7844 5.03642 17.8205 5.19839 18.6336C5.36679 19.4789 5.65321 20.185 6.2448 20.7385C6.8364 21.2919 7.55995 21.5308 8.4146 21.6425C9.23662 21.7501 10.275 21.7501 11.5606 21.75H12.4395C13.7251 21.7501 14.7635 21.7501 15.5856 21.6425C16.4402 21.5308 17.1638 21.2919 17.7554 20.7385C18.347 20.185 18.6334 19.4789 18.8018 18.6336C18.9638 17.8206 19.0328 16.7844 19.1183 15.5017L19.5818 8.54993C19.6093 8.13663 19.2966 7.77925 18.8833 7.7517C18.47 7.72415 18.1126 8.03685 18.0851 8.45015L17.6251 15.3493C17.5353 16.6971 17.4713 17.6349 17.3307 18.3406C17.1943 19.025 17.004 19.3873 16.7306 19.6431C16.4572 19.8989 16.083 20.0647 15.391 20.1552C14.6776 20.2485 13.7376 20.25 12.3868 20.25H11.6134C10.2626 20.25 9.32255 20.2485 8.60915 20.1552C7.91715 20.0647 7.54299 19.8989 7.26958 19.6431C6.99617 19.3873 6.80583 19.025 6.66948 18.3406C6.52892 17.6349 6.46489 16.6971 6.37503 15.3493L5.91509 8.45015Z" fill="#ff2424"></path> <path d="M9.42546 10.2538C9.83762 10.2125 10.2052 10.5133 10.2464 10.9254L10.7464 15.9254C10.7876 16.3376 10.4869 16.7051 10.0747 16.7463C9.66256 16.7875 9.29503 16.4868 9.25381 16.0747L8.75381 11.0747C8.7126 10.6625 9.01331 10.295 9.42546 10.2538Z" fill="#ff2424"></path> <path d="M14.5747 10.2538C14.9869 10.295 15.2876 10.6625 15.2464 11.0747L14.7464 16.0747C14.7052 16.4868 14.3376 16.7875 13.9255 16.7463C13.5133 16.7051 13.2126 16.3376 13.2538 15.9254L13.7538 10.9254C13.795 10.5133 14.1626 10.2125 14.5747 10.2538Z" fill="#ff2424"></path> </g></svg>
                            </div>
                        </div>
                        <div style="margin-top:12px; color:#c00; font-weight:600;">
                            Tem certeza que deseja excluir o cadastro de <span id="cdm-customer-name" style="font-weight:700"></span>?
                        </div>
                        <div style="margin-top:8px; color:#666;">Esta ação é irreversível</div>
                        <div style="margin-top:16px; text-align:left; display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" id="delete-confirm-checkbox">
                            <label for="delete-confirm-checkbox">Estou de acordo</label>
                        </div>
                        <div style="margin-top:18px; display:flex; gap:8px; justify-content:flex-end;">
                            <button type="button" class="button" id="delete-modal-cancel">Cancelar</button>
                            <button type="button" class="button button-primary" id="confirm-delete-btn" disabled>Excluir</button>
                        </div>
                    </div>
                </div>
            `;
            $('body').append(modalHtml);

            // listeners
            $(document).on('click', '#delete-modal-cancel, #customer-delete-modal .cdm-overlay', function() {
                $('#delete-confirm-checkbox').prop('checked', false);
                $('#confirm-delete-btn').prop('disabled', true);
                $('#customer-delete-modal').hide();
            });

            $(document).on('change', '#delete-confirm-checkbox', function() {
                $('#confirm-delete-btn').prop('disabled', !this.checked);
            });

            $(document).on('click', '#confirm-delete-btn', function() {
                const id = $('#customer-delete-modal').data('customer-id');
                const hideDetails = $('#customer-delete-modal').data('hide-details');
                if ( id ) {
                    deleteCustomer(id);
                }
                $('#customer-delete-modal').hide();
                $('#delete-confirm-checkbox').prop('checked', false);
                $('#confirm-delete-btn').prop('disabled', true);
                if ( hideDetails ) {
                    $('#customer-details-modal').hide();
                }
            });
        }

        // set values and show
        $('#customer-delete-modal').data('customer-id', customerId);
        $('#customer-delete-modal').data('hide-details', hideDetailsAfter);
        $('#cdm-customer-name').text(customerName ? customerName : '');
        $('#delete-confirm-checkbox').prop('checked', false);
        $('#confirm-delete-btn').prop('disabled', true);
        $('#customer-delete-modal').show();
    }
});
</script>
