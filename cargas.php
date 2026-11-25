<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO baa_carga (car_descricao, car_peso_kg, car_origem, car_destino, car_status, id_cliente) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sissis", $_POST['car_descricao'], $_POST['car_peso_kg'], $_POST['car_origem'], $_POST['car_destino'], $_POST['car_status'], $_POST['id_cliente']);
            $stmt->execute();
        } elseif ($action === 'edit') {
            $stmt = $conn->prepare("UPDATE baa_carga SET car_descricao=?, car_peso_kg=?, car_origem=?, car_destino=?, car_status=?, id_cliente=? WHERE id_carga=?");
            $stmt->bind_param("sissisi", $_POST['car_descricao'], $_POST['car_peso_kg'], $_POST['car_origem'], $_POST['car_destino'], $_POST['car_status'], $_POST['id_cliente'], $_POST['id_carga']);
            $stmt->execute();
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM baa_carga WHERE id_carga=?");
            $stmt->bind_param("i", $_POST['id_carga']);
            $stmt->execute();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    header("Location: cargas.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Logistics BAA - Cargas</title>
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
                <li class="nav-item"><a class="nav-link active" href="cargas.php">Cargas</a></li>
                <li class="nav-item"><a class="nav-link" href="viagens.php">Viagens</a></li>
                <li class="nav-item"><a class="nav-link" href="ocorrencias.php">Ocorrências</a></li>
                <li class="nav-item"><a class="nav-link text-warning" href="rotas.php">Rotas (IA)</a></li>
            </ul>
        </div>
    </div>
</nav>
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestão de Cargas</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ Novo Registro</button>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Descrição</th><th>Peso (kg)</th><th>Origem</th><th>Destino</th><th>Status</th><th>ID Cliente</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $stmt = $conn->query("SELECT * FROM baa_carga ORDER BY id_carga DESC LIMIT 100");
                    while($row = $stmt->fetch_assoc()) {
                        echo "<tr>";
                        echo '<td>' . htmlspecialchars($row['id_carga']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['car_descricao']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['car_peso_kg']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['car_origem']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['car_destino']) . '</td>';
                        echo '<td><span class="badge bg-secondary">' . htmlspecialchars($row['car_status']) . '</span></td>';
                        echo '<td>' . htmlspecialchars($row['id_cliente']) . '</td>';
                        
                        echo '<td>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal' . $row['id_carga'] . '">Editar</button>
                            <form method="POST" class="d-inline" onsubmit="return confirm(\'Tem certeza que deseja excluir?\');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id_carga" value="' . $row['id_carga'] . '">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                            </form>
                        </td>';
                        echo "</tr>";
                        ?>
                        
                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?php echo $row['id_carga']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Editar Registro</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id_carga" value="<?php echo $row['id_carga']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Descrição</label>
                                                <input type="text" name="car_descricao" class="form-control" value="<?php echo htmlspecialchars($row['car_descricao']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Peso (kg)</label>
                                                <input type="number" name="car_peso_kg" class="form-control" value="<?php echo htmlspecialchars($row['car_peso_kg']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Origem</label>
                                                <input type="text" name="car_origem" class="form-control" value="<?php echo htmlspecialchars($row['car_origem']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Destino</label>
                                                <input type="text" name="car_destino" class="form-control" value="<?php echo htmlspecialchars($row['car_destino']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <input type="text" name="car_status" class="form-control" value="<?php echo htmlspecialchars($row['car_status']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">ID Cliente</label>
                                                <input type="number" name="id_cliente" class="form-control" value="<?php echo htmlspecialchars($row['id_cliente']); ?>" required>
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
                        <label class="form-label">Descrição</label>
                        <input type="text" name="car_descricao" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Peso (kg)</label>
                        <input type="number" name="car_peso_kg" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Origem</label>
                        <input type="text" name="car_origem" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Destino</label>
                        <input type="text" name="car_destino" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <input type="text" name="car_status" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ID Cliente</label>
                        <input type="number" name="id_cliente" class="form-control" required>
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