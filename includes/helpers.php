<?php

/**
 * Gera o HTML para o badge de status de uma despesa.
 *
 * @param string $status O status atual ('pago', 'pendente').
 * @param string $data_vencimento A data de vencimento da despesa no formato 'Y-m-d'.
 * @return string O HTML do elemento <span> do badge.
 */
function get_status_badge(string $status, string $data_vencimento): string {
    if ($status === 'pago') {
        return '<span class="badge bg-success">Pago</span>';
    }

    $hoje = new DateTime();
    $vencimento = new DateTime($data_vencimento);

    return ($vencimento < $hoje)
        ? '<span class="badge bg-danger">Atrasado</span>'
        : '<span class="badge bg-warning text-dark">Pendente</span>';
}