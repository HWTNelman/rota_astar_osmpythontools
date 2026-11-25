<?php
// conexao db
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM baa_rota WHERE id_rota=?");
            $stmt->bind_param("i", $_POST['id_rota']);
            $stmt->execute();
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
    header("Location: api_recalcular_rota.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Logistics BAA - Rotas Cadastradas</title>
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestão de Rotas Cadastradas</h2>
        <div><a href="rotas_astar.php" class="btn btn-outline-primary me-2">Rota com A* (OSM)</a><a href="rotas.php" class="btn btn-warning">+ Criar Nova Rota (Mapa)</a></div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Origem</th>
                    <th>Destino</th>
                    <th>Distância (km)</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                try {
                    $stmt = $conn->query("SELECT * FROM baa_rota ORDER BY id_rota DESC LIMIT 100");
                    while($row = $stmt->fetch_assoc()) {
                        echo "<tr>";
                        echo '<td>' . htmlspecialchars($row['id_rota']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['rot_origem_nome']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['rot_destino_nome']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['rot_distancia_km']) . '</td>';
                        
                        echo '<td>
                            <a href="rotas.php?id_rota=' . urlencode($row['id_rota']) . '&origem=' . urlencode($row['rot_origem_nome']) . '&destino=' . urlencode($row['rot_destino_nome']) . '" class="btn btn-sm btn-outline-primary">Recalcular no Mapa</a>
                            
                            <form method="POST" class="d-inline" onsubmit="return confirm(&quot;Tem certeza que deseja excluir esta rota permanentemente?&quot;);">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id_rota" value="' . $row['id_rota'] . '">
                                <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                            </form>
                        </td>';
                        echo "</tr>";
                    }
                } catch(Exception $e) {
                    echo "<tr><td colspan='100%'>Erro: " . $e->getMessage() . "</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
