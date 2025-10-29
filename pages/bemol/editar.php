<?php
session_start();
require_once '../../includes/auth_check.php';
require_once '../../config/config.php';
require_once '../../includes/helpers.php';

// Apenas admins podem acessar esta página
if ($user_tipo !== 'admin') {
    header("Location: ../dashboard.php");
    exit();
}

$grupo_parcela_id = $_GET['grupo_parcela_id'] ?? null;

if (!$grupo_parcela_id) {
    header("Location: ../despesas/index.php?error=bemol_id_nao_encontrado");
    exit();
}

$parcelas_bemol = [];
$total_original_compra = 0;
$valor_entrada_original = 0;
$numero_parcelas_original = 0;
$descricao_original = '';
$data_despesa_original = '';
$dono_divida_id_original = '';
$comprador_id_original = '';
$dia_vencimento_parcela_bemol_original = '';

try {
    // Buscar todas as despesas (parcelas e entrada) associadas a este grupo
    $stmt_despesas = $pdo->prepare("
        SELECT 
            d.id, d.descricao, d.valor, d.data_despesa, d.data_compra, d.dono_divida_id, d.comprador_id, d.metodo_pagamento
        FROM despesas d
        WHERE d.grupo_parcela_id = :grupo_parcela_id
        ORDER BY d.data_despesa ASC
    ");
    $stmt_despesas->execute([':grupo_parcela_id' => $grupo_parcela_id]);
    $todas_despesas_do_grupo = $stmt_despesas->fetchAll(PDO::FETCH_ASSOC);

    if (empty($todas_despesas_do_grupo)) {
        header("Location: ../despesas/index.php?error=bemol_compra_nao_encontrada");
        exit();
    }

    // Identificar a entrada e as parcelas
    foreach ($todas_despesas_do_grupo as $despesa) {
        if (strpos($despesa['descricao'], 'Entrada Bemol:') === 0) {
            $valor_entrada_original = $despesa['valor'];
        } else {
            $parcelas_bemol[] = $despesa;
        }
        $total_original_compra += $despesa['valor'];
    }

    // A partir da primeira parcela (ou qualquer uma que não seja entrada), pegamos os dados principais
    $primeira_parcela_info = null;
    foreach ($todas_despesas_do_grupo as $despesa) {
        if (strpos($despesa['descricao'], 'Entrada Bemol:') === false) {
            $primeira_parcela_info = $despesa;
            break;
        }
    }

    if (!$primeira_parcela_info) {
        // Isso não deveria acontecer se houver parcelas, mas é uma salvaguarda
        header("Location: ../despesas/index.php?error=bemol_compra_sem_parcelas");
        exit();
    }

    // Preencher os dados para o formulário
    $descricao_original = preg_replace('/ \(Parcela \d+\/\d+\)$/', '', $primeira_parcela_info['descricao']);
    $data_despesa_original = $primeira_parcela_info['data_compra'] ?? $primeira_parcela_info['data_despesa']; // Usar data_compra ou data_despesa como fallback
    $dono_divida_id_original = $primeira_parcela_info['dono_divida_id'];
    $comprador_id_original = $primeira_parcela_info['comprador_id'];
    $numero_parcelas_original = count($parcelas_bemol); // Contar apenas as parcelas, não a entrada

    // Buscar o dia de vencimento da primeira parcela para preencher o campo dia_vencimento_parcela_bemol
    // A data_despesa da primeira parcela é o seu vencimento
    $data_primeira_parcela_vencimento = new DateTime($primeira_parcela_info['data_despesa']);
    $dia_vencimento_parcela_bemol_original = (int)$data_primeira_parcela_vencimento->format('d');

    // Buscar todos os usuários para os selects
    $stmt_usuarios = $pdo->query("SELECT id, nome FROM usuarios ORDER BY nome");
    $usuarios = $stmt_usuarios->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erro ao carregar compra Bemol para edição: " . $e->getMessage());
    header("Location: ../despesas/index.php?error=db_error");
    exit();
}

// O valor total da compra é a soma de todas as parcelas + entrada
// No formulário, o usuário vai digitar o valor total da compra, não o valor financiado.
$valor_total_compra_para_form = $total_original_compra;

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Compra Bemol</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="../../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include '../../includes/sidebar.php'; ?>
        
        <div id="content">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <span id="sidebar-toggle" class="top-bar-toggle"><i class="bi bi-list fs-4"></i></span>
                </div>
            </nav>
            <main class="p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Editar Compra Bemol</h1>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger">
                        <?php
                            if ($_GET['error'] === 'campos_obrigatorios') echo 'Por favor, preencha todos os campos obrigatórios.';
                            else if ($_GET['error'] === 'db_error') echo 'Ocorreu um erro ao salvar a despesa no banco de dados.';
                            else echo 'Ocorreu um erro: ' . htmlspecialchars($_GET['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form action="../../actions/salvar_despesa.php" method="POST">
                            <input type="hidden" name="metodo_pagamento" value="bemol_crediario">
                            <input type="hidden" name="bemol_grupo_parcela_id" value="<?php echo htmlspecialchars($grupo_parcela_id); ?>">

                            <div class="mb-3">
                                <label for="descricao" class="form-label">Descrição da Compra</label>
                                <input type="text" class="form-control" id="descricao" name="descricao" value="<?php echo htmlspecialchars($descricao_original); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="valor" class="form-label">Valor Total da Compra (incluindo entrada)</label>
                                <input type="number" step="0.01" class="form-control" id="valor" name="valor" value="<?php echo htmlspecialchars($valor_total_compra_para_form); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="data_despesa" class="form-label">Data da Compra</label>
                                <input type="date" class="form-control" id="data_despesa" name="data_despesa" value="<?php echo htmlspecialchars($data_despesa_original); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="dono_divida_id" class="form-label">Dono da Dívida</label>
                                <select class="form-select" id="dono_divida_id" name="dono_divida_id" required>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <option value="<?php echo $usuario['id']; ?>" <?php echo ($dono_divida_id_original == $usuario['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($usuario['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="comprador_id" class="form-label">Comprador</label>
                                <select class="form-select" id="comprador_id" name="comprador_id" required>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <option value="<?php echo $usuario['id']; ?>" <?php echo ($comprador_id_original == $usuario['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($usuario['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="teve_entrada" name="teve_entrada" <?php echo ($valor_entrada_original > 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="teve_entrada">Teve Entrada?</label>
                            </div>

                            <div class="mb-3" id="div_valor_entrada" style="<?php echo ($valor_entrada_original > 0) ? '' : 'display: none;'; ?>">
                                <label for="valor_entrada" class="form-label">Valor da Entrada</label>
                                <input type="number" step="0.01" class="form-control" id="valor_entrada" name="valor_entrada" value="<?php echo htmlspecialchars($valor_entrada_original); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="numero_parcelas_bemol" class="form-label">Número de Parcelas (Bemol)</label>
                                <input type="number" class="form-control" id="numero_parcelas_bemol" name="numero_parcelas_bemol" value="<?php echo htmlspecialchars($numero_parcelas_original); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="dia_vencimento_parcela_bemol" class="form-label">Dia de Vencimento das Parcelas (Bemol)</label>
                                <input type="number" class="form-control" id="dia_vencimento_parcela_bemol" name="dia_vencimento_parcela_bemol" min="1" max="31" value="<?php echo htmlspecialchars($dia_vencimento_parcela_bemol_original); ?>" required>
                            </div>

                            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-2"></i>Salvar Alterações</button>
                            <a href="../despesas/index.php" class="btn btn-secondary">Cancelar</a>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/scripts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const teveEntradaCheckbox = document.getElementById('teve_entrada');
            const divValorEntrada = document.getElementById('div_valor_entrada');
            const valorEntradaInput = document.getElementById('valor_entrada');

            teveEntradaCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    divValorEntrada.style.display = 'block';
                    valorEntradaInput.setAttribute('required', 'required');
                } else {
                    divValorEntrada.style.display = 'none';
                    valorEntradaInput.removeAttribute('required');
                    valorEntradaInput.value = ''; // Limpa o valor se a entrada for desmarcada
                }
            });

            // Trigger change on load if already checked
            if (teveEntradaCheckbox.checked) {
                teveEntradaCheckbox.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>