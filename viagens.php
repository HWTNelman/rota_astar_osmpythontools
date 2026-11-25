<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_rota = !empty($_POST['id_rota']) ? $_POST['id_rota'] : null;
    
    try {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO baa_viagem (via_data_saida, via_data_chegada, via_status, id_motorista, id_veiculo, id_rota) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssiii", $_POST['via_data_saida'], $_POST['via_data_chegada'], $_POST['via_status'], $_POST['id_motorista'], $_POST['id_veiculo'], $id_rota);
            $stmt->execute();
        } elseif ($action === 'edit') {
            $stmt = $conn->prepare("UPDATE baa_viagem SET via_data_saida=?, via_data_chegada=?, via_status=?, id_motorista=?, id_veiculo=?, id_rota=? WHERE id_viagem=?");
            $stmt->bind_param("sssiiii", $_POST['via_data_saida'], $_POST['via_data_chegada'], $_POST['via_status'], $_POST['id_motorista'], $_POST['id_veiculo'], $id_rota, $_POST['id_viagem']);
            $stmt->execute();
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM baa_viagem WHERE id_viagem=?");
            $stmt->bind_param("i", $_POST['id_viagem']);
            $stmt->execute();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    header("Location: viagens.php");
    exit;
}

// Buscar rotas para o dropdown com alerta de erros
$rotas = [];
$db_error = "";
try {
    $res = $conn->query("SELECT * FROM baa_rota");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $rotas[] = $r;
        }
    } else {
        $db_error = $conn->error;
    }
} catch(Exception $e) {
    $db_error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Logistics BAA - Viagens</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-radius: 8px; border: none; }
        .table-responsive { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php"><strong>Logistics BAA</strong></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="clientes.php">Clientes</a></li>
                <li class="nav-item"><a class="nav-link" href="motoristas.php">Motoristas</a></li>
                <li class="nav-item"><a class="nav-link" href="veiculos.php">Veículos</a></li>
                <li class="nav-item"><a class="nav-link" href="cargas.php">Cargas</a></li>
                <li class="nav-item"><a class="nav-link" href="viagens.php">Viagens</a></li>
                <li class="nav-item"><a class="nav-link" href="ocorrencias.php">Ocorrências</a></li>
                <li class="nav-item"><a class="nav-link text-warning" href="rotas.php">Rotas (IA)</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container">
    
    <?php if (!empty($db_error)): ?>
        <div class="alert alert-danger">
            <strong>Erro ao carregar Rotas do BD:</strong> <?php echo htmlspecialchars($db_error); ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestão de Viagens</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ Novo Registro</button>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Saída</th><th>Chegada</th><th>Status</th><th>Motorista</th><th>Veículo</th><th>ID Rota</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $stmt = $conn->query("SELECT * FROM baa_viagem ORDER BY id_viagem DESC LIMIT 100");
                    while($row = $stmt->fetch_assoc()) {
                        echo "<tr>";
                        echo '<td>' . htmlspecialchars($row['id_viagem']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['via_data_saida']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['via_data_chegada']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['via_status']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['id_motorista']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['id_veiculo']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['id_rota'] ?? 'N/A') . '</td>';
                        
                        echo '<td>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal' . $row['id_viagem'] . '">Editar</button>
                            <form method="POST" class="d-inline" onsubmit="return confirm(\'Tem certeza que deseja excluir?\');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id_viagem" value="' . $row['id_viagem'] . '">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                            </form>
                        </td>';
                        echo "</tr>";
                        ?>
                        
                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?php echo $row['id_viagem']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Editar Registro</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id_viagem" value="<?php echo $row['id_viagem']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Data de Saída</label>
                                                <input type="date" name="via_data_saida" class="form-control" value="<?php echo htmlspecialchars($row['via_data_saida']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Data de Chegada</label>
                                                <input type="date" name="via_data_chegada" class="form-control" value="<?php echo htmlspecialchars($row['via_data_chegada']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <input type="text" name="via_status" class="form-control" value="<?php echo htmlspecialchars($row['via_status']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">ID Motorista</label>
                                                <input type="number" name="id_motorista" class="form-control" value="<?php echo htmlspecialchars($row['id_motorista']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">ID Veículo</label>
                                                <input type="number" name="id_veiculo" class="form-control" value="<?php echo htmlspecialchars($row['id_veiculo']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Rota Associada</label>
                                                <select name="id_rota" class="form-select">
                                                    <option value="">Sem Rota</option>
                                                    <?php foreach($rotas as $rt): 
                                                        $rt_id = $rt['id_rota'] ?? $rt['id'] ?? '';
                                                        $rt_nome = $rt['nome_rota'] ?? $rt['nome'] ?? (isset($rt['origem'], $rt['destino']) ? $rt['origem'].' -> '.$rt['destino'] : 'Rota '.$rt_id);
                                                    ?>
                                                        <option value="<?php echo htmlspecialchars($rt_id); ?>" <?php echo (isset($row['id_rota']) && $row['id_rota'] == $rt_id) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($rt_nome); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                            <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } catch(Exception $e) {
                    echo "<tr><td colspan='100%'>Erro: " . $e->getMessage() . "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Adicionar Novo Registro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Data de Saída</label>
                        <input type="date" name="via_data_saida" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data de Chegada</label>
                        <input type="date" name="via_data_chegada" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <input type="text" name="via_status" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ID Motorista</label>
                        <input type="number" name="id_motorista" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ID Veículo</label>
                        <input type="number" name="id_veiculo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rota Associada</label>
                        <select name="id_rota" class="form-select">
                            <option value="">Sem Rota</option>
                            <?php foreach($rotas as $rt): 
                                $rt_id = $rt['id_rota'] ?? $rt['id'] ?? '';
                                $rt_nome = $rt['nome_rota'] ?? $rt['nome'] ?? (isset($rt['origem'], $rt['destino']) ? $rt['origem'].' -> '.$rt['destino'] : 'Rota '.$rt_id);
                            ?>
                                <option value="<?php echo htmlspecialchars($rt_id); ?>">
                                    <?php echo htmlspecialchars($rt_nome); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>