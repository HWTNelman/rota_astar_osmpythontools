<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO baa_motorista (mot_nome, mot_cpf, mot_telefone, mot_cnh, validade_cnh) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $_POST['mot_nome'], $_POST['mot_cpf'], $_POST['mot_telefone'], $_POST['mot_cnh'], $_POST['validade_cnh']);
            $stmt->execute();
        } elseif ($action === 'edit') {
            $stmt = $conn->prepare("UPDATE baa_motorista SET mot_nome=?, mot_cpf=?, mot_telefone=?, mot_cnh=?, validade_cnh=? WHERE id_motorista=?");
            $stmt->bind_param("sssssi", $_POST['mot_nome'], $_POST['mot_cpf'], $_POST['mot_telefone'], $_POST['mot_cnh'], $_POST['validade_cnh'], $_POST['id_motorista']);
            $stmt->execute();
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM baa_motorista WHERE id_motorista=?");
            $stmt->bind_param("i", $_POST['id_motorista']);
            $stmt->execute();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    header("Location: motoristas.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Logistics BAA - Motoristas</title>
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
                <li class="nav-item"><a class="nav-link active" href="motoristas.php">Motoristas</a></li>
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestão de Motoristas</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ Novo Registro</button>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nome</th><th>CPF</th><th>Telefone</th><th>CNH</th><th>Validade CNH</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $stmt = $conn->query("SELECT * FROM baa_motorista ORDER BY id_motorista DESC LIMIT 100");
                    while($row = $stmt->fetch_assoc()) {
                        echo "<tr>";
                        echo '<td>' . htmlspecialchars($row['id_motorista']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['mot_nome']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['mot_cpf']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['mot_telefone']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['mot_cnh']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['validade_cnh']) . '</td>';
                        
                        echo '<td>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal' . $row['id_motorista'] . '">Editar</button>
                            <form method="POST" class="d-inline" onsubmit="return confirm(\'Tem certeza que deseja excluir?\');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id_motorista" value="' . $row['id_motorista'] . '">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                            </form>
                        </td>';
                        echo "</tr>";
                        ?>
                        
                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?php echo $row['id_motorista']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Editar Registro</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id_motorista" value="<?php echo $row['id_motorista']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Nome</label>
                                                <input type="text" name="mot_nome" class="form-control" value="<?php echo htmlspecialchars($row['mot_nome']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">CPF</label>
                                                <input type="text" name="mot_cpf" class="form-control" value="<?php echo htmlspecialchars($row['mot_cpf']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Telefone</label>
                                                <input type="text" name="mot_telefone" class="form-control" value="<?php echo htmlspecialchars($row['mot_telefone']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">CNH</label>
                                                <input type="text" name="mot_cnh" class="form-control" value="<?php echo htmlspecialchars($row['mot_cnh']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Validade CNH</label>
                                                <input type="date" name="validade_cnh" class="form-control" value="<?php echo htmlspecialchars($row['validade_cnh']); ?>" required>
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
                        <label class="form-label">Nome</label>
                        <input type="text" name="mot_nome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">CPF</label>
                        <input type="text" name="mot_cpf" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="mot_telefone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">CNH</label>
                        <input type="text" name="mot_cnh" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Validade CNH</label>
                        <input type="date" name="validade_cnh" class="form-control" required>
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