<?php /* Modal de Configuração de Horários */
$myd_opening = get_option( 'myd-delivery-time', array() );
$days = array(
    'monday'   => 'Segunda-feira',
    'tuesday'  => 'Terça-feira',
    'wednesday'=> 'Quarta-feira',
    'thursday' => 'Quinta-feira',
    'friday'   => 'Sexta-feira',
    'saturday' => 'Sábado',
    'sunday'   => 'Domingo',
);
?>
<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <span class="modal-title">Configurar horários</span>
        <span class="modal-subtitle">Estas informações são atualizadas a cada 30 segundos</span>
        <div class="status-card" id="myd-modal-status">
            <!-- Content filled by JS -->
        </div>
        <div class="schedule-table-wrapper">
            <table class="schedule-table">
                <thead>
                    <tr>
                        <th>Dia</th>
                        <th>Horários</th>
                    </tr>
                </thead>
            </table>
            <div class="schedule-table-scroll">
                <table class="schedule-table schedule-table-body">
                    <tbody>
                        <?php foreach ($days as $day_key => $day_label): ?>
                        <tr>
                            <td><?php echo esc_html($day_label); ?></td>
                            <td>
                                <?php
                                if (isset($myd_opening[$day_key]) && is_array($myd_opening[$day_key]) && count($myd_opening[$day_key]) > 0) {
                                    $first = $myd_opening[$day_key][0];
                                    $start = isset($first['start']) ? $first['start'] : '';
                                    $end = isset($first['end']) ? $first['end'] : '';
                                } else {
                                    $start = '';
                                    $end = '';
                                }
                                ?>
                                <input type="time" value="<?php echo esc_attr($start); ?>"> - <input type="time" value="<?php echo esc_attr($end); ?>">
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer myd-modal-footer">
            <button id="btn-toggle-status" class="btn-toggle-status">Carregando...</button>
            <button class="btn-salvar" disabled>Salvar</button>
        </div>
    </div>
</div>
