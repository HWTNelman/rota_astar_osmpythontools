<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO baa_ocorrencia (oco_tipo, oco_descricao, oco_data_ocorrencia, id_viagem) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $_POST['oco_tipo'], $_POST['oco_descricao'], $_POST['oco_data_ocorrencia'], $_POST['id_viagem']);
            $stmt->execute();
        } elseif ($action === 'edit') {
            $stmt = $conn->prepare("UPDATE baa_ocorrencia SET oco_tipo=?, oco_descricao=?, oco_data_ocorrencia=?, id_viagem=? WHERE id_ocorrencia=?");
            $stmt->bind_param("sssii", $_POST['oco_tipo'], $_POST['oco_descricao'], $_POST['oco_data_ocorrencia'], $_POST['id_viagem'], $_POST['id_ocorrencia']);
            $stmt->execute();
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM baa_ocorrencia WHERE id_ocorrencia=?");
            $stmt->bind_param("i", $_POST['id_ocorrencia']);
            $stmt->execute();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    header("Location: ocorrencias.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Logistics BAA - Ocorrências</title>
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
                <li class="nav-item"><a class="nav-link active" href="ocorrencias.php">Ocorrências</a></li>
                <li class="nav-item"><a class="nav-link text-warning" href="rotas.php">Rotas (IA)</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestão de Ocorrências</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ Novo Registro</button>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Tipo</th><th>Descrição</th><th>Data</th><th>ID Viagem</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $stmt = $conn->query("SELECT * FROM baa_ocorrencia ORDER BY id_ocorrencia DESC LIMIT 100");
                    while($row = $stmt->fetch_assoc()) {
                        echo "<tr>";
                        echo '<td>' . htmlspecialchars($row['id_ocorrencia']) . '</td>';
                        echo '<td><span class="badge bg-warning text-dark">' . htmlspecialchars($row['oco_tipo']) . '</span></td>';
                        echo '<td>' . htmlspecialchars($row['oco_descricao']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['oco_data_ocorrencia']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['id_viagem']) . '</td>';
                        
                        echo '<td>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal' . $row['id_ocorrencia'] . '">Editar</button>
                            <form method="POST" class="d-inline" onsubmit="return confirm(\'Tem certeza que deseja excluir?\');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id_ocorrencia" value="' . $row['id_ocorrencia'] . '">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                            </form>
                        </td>';
                        echo "</tr>";
                        ?>
                        
                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?php echo $row['id_ocorrencia']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Editar Registro</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id_ocorrencia" value="<?php echo $row['id_ocorrencia']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Tipo</label>
                                                <input type="text" name="oco_tipo" class="form-control" value="<?php echo htmlspecialchars($row['oco_tipo']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Descrição</label>
                                                <input type="text" name="oco_descricao" class="form-control" value="<?php echo htmlspecialchars($row['oco_descricao']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Data Ocorrência</label>
                                                <input type="date" name="oco_data_ocorrencia" class="form-control" value="<?php echo htmlspecialchars($row['oco_data_ocorrencia']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">ID Viagem</label>
                                                <input type="number" name="id_viagem" class="form-control" value="<?php echo htmlspecialchars($row['id_viagem']); ?>" required>
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
                        <label class="form-label">Tipo</label>
                        <input type="text" name="oco_tipo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="oco_descricao" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Data Ocorrência</label>
                        <input type="date" name="oco_data_ocorrencia" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ID Viagem</label>
                        <input type="number" name="id_viagem" class="form-control" required>
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