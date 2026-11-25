<?php
// Endpoint NOVO: calcula rota com A* (Python + OpenStreetMap) e, opcionalmente, salva em baa_rota.
// Nao altera nenhum arquivo existente do projeto.
require_once 'db.php';
header('Content-Type: application/json; charset=utf-8');
set_time_limit(240);

// Comando do Python. No XAMPP/Windows normalmente 'python' (ou 'py'); no Linux/Mac 'python3'.
// Ordem: variavel de ambiente BAA_PYTHON > constante BAA_PYTHON (config.php) > padrao do sistema.
$python_bin = getenv('BAA_PYTHON') ?: (defined('BAA_PYTHON') ? BAA_PYTHON : (stripos(PHP_OS, 'WIN') === 0 ? 'python' : 'python3'));
$script = __DIR__ . DIRECTORY_SEPARATOR . 'astar' . DIRECTORY_SEPARATOR . 'astar_osm_router.py';

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['origem']) || empty($data['destino'])) {
    echo json_encode(['success' => false, 'error' => 'Origem e destino são obrigatórios']);
    exit;
}
$modo = in_array($data['modo'] ?? 'weighted', ['time', 'distance', 'cost', 'weighted'], true) ? $data['modo'] : 'weighted';
$origem = trim($data['origem']);
$destino = trim($data['destino']);

$cmd = escapeshellarg($python_bin) . ' ' . escapeshellarg($script)
     . ' --origem ' . escapeshellarg($origem)
     . ' --destino ' . escapeshellarg($destino)
     . ' --modo ' . escapeshellarg($modo);

$desc = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$proc = @proc_open($cmd, $desc, $pipes, __DIR__);
if (!is_resource($proc)) {
    echo json_encode(['success' => false, 'error' => 'Não foi possível executar o Python. Verifique a variável $python_bin.']);
    exit;
}
$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]); fclose($pipes[2]);
proc_close($proc);

$res = json_decode($stdout, true);
if (!$res) {
    echo json_encode(['success' => false, 'error' => 'Falha ao executar o A*. ' . substr(trim($stderr), -400)]);
    exit;
}
if (empty($res['success'])) {
    echo json_encode($res);
    exit;
}

// Salva (opcional) usando as MESMAS colunas que rotas.php ja usa em baa_rota.
$res['id_rota'] = null;
if (!empty($data['salvar'])) {
    $analise = sprintf(
        "Rota calculada pelo algoritmo A* sobre dados do OpenStreetMap (modo: %s). Nós do grafo: %d; nós expandidos: %d "
        . "(Dijkstra: %d). Radares no trajeto: %d. Custo total estimado: R$ %s.",
        $modo, $res['nos_grafo'], $res['nos_expandidos'],
        $res['comparacao']['dijkstra']['nos_expandidos'] ?? 0, $res['radares'],
        number_format($res['custo_total'], 2, ',', '.')
    );
    try {
        $stmt = $conn->prepare("
            INSERT INTO baa_rota (rot_origem_nome, rot_destino_nome, rot_distancia_km,
                rot_tempo_estimado_min, rot_custo_estimado_combustivel, rot_custo_pedagios, rot_ia_analise)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $tempo = (int)round($res['tempo_min']);
        $dist = (float)$res['distancia_km'];
        $comb = (float)$res['custo_combustivel'];
        $ped = (float)$res['custo_pedagios'];
        $stmt->bind_param("ssdidds", $origem, $destino, $dist, $tempo, $comb, $ped, $analise);
        $stmt->execute();
        $res['id_rota'] = $conn->insert_id;
    } catch (Throwable $e) {
        $res['aviso_bd'] = 'Rota calculada, mas não salva no banco: ' . $e->getMessage()
            . ' (rode migracao_baa_rota.sql no phpMyAdmin)';
    }
}
echo json_encode($res, JSON_UNESCAPED_UNICODE);
