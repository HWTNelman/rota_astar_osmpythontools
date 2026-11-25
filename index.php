<?php require_once 'db.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Logistics BAA - Dashboard</title>
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

    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-4">Painel de Controle</h2>
        </div>
    </div>
    <div class="row g-4">
        <!-- Cards -->
        <div class="col-md-4"><div class="card bg-primary text-white p-4"><h5>Clientes Registrados</h5><a href="clientes.php" class="text-white">Gerenciar -></a></div></div>
        <div class="col-md-4"><div class="card bg-success text-white p-4"><h5>Motoristas Ativos</h5><a href="motoristas.php" class="text-white">Gerenciar -></a></div></div>
        <div class="col-md-4"><div class="card bg-warning text-dark p-4"><h5>Veículos da Frota</h5><a href="veiculos.php" class="text-dark">Gerenciar -></a></div></div>
        <div class="col-md-4"><div class="card bg-info text-white p-4"><h5>Cargas Pendentes</h5><a href="cargas.php" class="text-white">Gerenciar -></a></div></div>
        <div class="col-md-4"><div class="card bg-secondary text-white p-4"><h5>Viagens em Curso</h5><a href="viagens.php" class="text-white">Gerenciar -></a></div></div>
        <div class="col-md-4"><div class="card bg-danger text-white p-4"><h5>Ocorrências / Alertas</h5><a href="ocorrencias.php" class="text-white">Gerenciar -></a></div></div>
        
        <!-- NEW CARD FOR ROTAS CADASTRADAS -->
        <div class="col-md-12"><div class="card bg-dark text-white p-4 text-center"><h5>Agente IA de Roteamento - Rotas Calculadas</h5><a href="api_recalcular_rota.php" class="btn btn-outline-light mt-2">Acessar Tabela de Rotas</a></div></div>
    </div>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
