<?php
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            $stmt = $conn->prepare("INSERT INTO baa_cliente (cli_nome, cli_cpf_cnpj, cli_telefone, cli_cidade) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $_POST['cli_nome'], $_POST['cli_cpf_cnpj'], $_POST['cli_telefone'], $_POST['cli_cidade']);
            $stmt->execute();
        } elseif ($action === 'edit') {
            $stmt = $conn->prepare("UPDATE baa_cliente SET cli_nome=?, cli_cpf_cnpj=?, cli_telefone=?, cli_cidade=? WHERE id_cliente=?");
            $stmt->bind_param("ssssi", $_POST['cli_nome'], $_POST['cli_cpf_cnpj'], $_POST['cli_telefone'], $_POST['cli_cidade'], $_POST['id_cliente']);
            $stmt->execute();
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM baa_cliente WHERE id_cliente=?");
            $stmt->bind_param("i", $_POST['id_cliente']);
            $stmt->execute();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    header("Location: clientes.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Logistics BAA - Clientes</title>
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
                <li class="nav-item"><a class="nav-link active" href="clientes.php">Clientes</a></li>
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestão de Clientes</h2>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">+ Novo Registro</button>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Nome</th><th>CPF/CNPJ</th><th>Telefone</th><th>Cidade</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $stmt = $conn->query("SELECT * FROM baa_cliente ORDER BY id_cliente DESC LIMIT 100");
                    while($row = $stmt->fetch_assoc()) {
                        echo "<tr>";
                        echo '<td>' . htmlspecialchars($row['id_cliente']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['cli_nome']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['cli_cpf_cnpj']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['cli_telefone']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['cli_cidade']) . '</td>';
                        
                        echo '<td>
                            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editModal' . $row['id_cliente'] . '">Editar</button>
                            <form method="POST" class="d-inline" onsubmit="return confirm(\'Tem certeza que deseja excluir?\');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id_cliente" value="' . $row['id_cliente'] . '">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                            </form>
                        </td>';
                        echo "</tr>";
                        ?>
                        
                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal<?php echo $row['id_cliente']; ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Editar Registro</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id_cliente" value="<?php echo $row['id_cliente']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Nome</label>
                                                <input type="text" name="cli_nome" class="form-control" value="<?php echo htmlspecialchars($row['cli_nome']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">CPF/CNPJ</label>
                                                <input type="text" name="cli_cpf_cnpj" class="form-control" value="<?php echo htmlspecialchars($row['cli_cpf_cnpj']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Telefone</label>
                                                <input type="text" name="cli_telefone" class="form-control" value="<?php echo htmlspecialchars($row['cli_telefone']); ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Cidade</label>
                                                <input type="text" name="cli_cidade" class="form-control" value="<?php echo htmlspecialchars($row['cli_cidade']); ?>" required>
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
                        <input type="text" name="cli_nome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">CPF/CNPJ</label>
                        <input type="text" name="cli_cpf_cnpj" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefone</label>
                        <input type="text" name="cli_telefone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cidade</label>
                        <input type="text" name="cli_cidade" class="form-control" required>
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