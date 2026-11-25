<?php
// conexao db
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? 'calculate';

    // salva banco
    if ($action === 'save') {
        $id_rota = !empty($data['id_rota']) ? (int)$data['id_rota'] : null;
        $origem = $data['origem'] ?? '';
        $destino = $data['destino'] ?? '';
        $distancia = $data['distancia'] ?? 0;
        $tempo = $data['tempo'] ?? 0;
        $combustivel = $data['combustivel'] ?? 0;
        $pedagio = $data['pedagio'] ?? 0;
        $ai_analysis = $data['ai_analysis'] ?? '';

        try {
            if ($id_rota) {
                // substitui rota existente
                $stmt = $conn->prepare("
                    UPDATE baa_rota SET 
                        rot_origem_nome = ?, 
                        rot_destino_nome = ?, 
                        rot_distancia_km = ?, 
                        rot_tempo_estimado_min = ?, 
                        rot_custo_estimado_combustivel = ?, 
                        rot_custo_pedagios = ?, 
                        rot_ia_analise = ?
                    WHERE id_rota = ?
                ");
                $stmt->bind_param("ssdiddsi", $origem, $destino, $distancia, $tempo, $combustivel, $pedagio, $ai_analysis, $id_rota);
                $stmt->execute();
            } else {
                // cria nova rota
                $stmt = $conn->prepare("
                    INSERT INTO baa_rota (
                        rot_origem_nome, rot_destino_nome, rot_distancia_km, 
                        rot_tempo_estimado_min, rot_custo_estimado_combustivel, 
                        rot_custo_pedagios, rot_ia_analise
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param("ssdidds", $origem, $destino, $distancia, $tempo, $combustivel, $pedagio, $ai_analysis);
                $stmt->execute();
                $id_rota = $conn->insert_id;
            }

            echo json_encode(['success' => true, 'id_rota' => $id_rota]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'Erro BD: ' . $e->getMessage()]);
        }
        exit;
    }

    // calculo da rota
    $origem = $data['origem'] ?? '';
    $destino = $data['destino'] ?? '';

    if (empty($origem) || empty($destino)) {
        echo json_encode(['success' => false, 'error' => 'Origem e destino são obrigatórios']);
        exit;
    }

    // chamado chave API rotas
    $apiKey = GOOGLE_MAPS_API_KEY;
    $url = 'https://routes.googleapis.com/directions/v2:computeRoutes';
    
    $payload = json_encode([
        'origin' => ['address' => $origem],
        'destination' => ['address' => $destino],
        'travelMode' => 'DRIVE',
    ]);

    $headers = [
        'Content-Type: application/json',
        'X-Goog-Api-Key: ' . $apiKey,
        'X-Goog-FieldMask: routes.distanceMeters,routes.duration,routes.polyline.encodedPolyline'
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $maps_response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    $distancia_km = 0;
    $tempo_min = 0;
    $polyline = '';

    if ($maps_response && $http_code == 200) {
        $maps_data = json_decode($maps_response, true);
        if (isset($maps_data['routes']) && count($maps_data['routes']) > 0) {
            $distancia_metros = $maps_data['routes'][0]['distanceMeters'];
            $tempo_segundos = (int) rtrim($maps_data['routes'][0]['duration'], 's');
            $polyline = $maps_data['routes'][0]['polyline']['encodedPolyline'];
            
            $distancia_km = round($distancia_metros / 1000, 2);
            $tempo_min = round($tempo_segundos / 60);
        } else {
            echo json_encode(['success' => false, 'error' => 'A API não encontrou uma rota válida.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Erro na API do Google (Código HTTP ' . $http_code . ').']);
        exit;
    }

    $horas = floor($tempo_min / 60);
    $minutos = $tempo_min % 60;
    $tempo_formatado = $horas > 0 ? sprintf("%dh %02dmin", $horas, $minutos) : "{$minutos}min";

    $custo_combustivel = round($distancia_km * 1.45, 2);
    $pedagio_estimado = round($distancia_km * 0.12, 2);
    
    // chamado agente ia gemini
    $gemini_api_key = GEMINI_API_KEY;
    $prompt_ia = "Atue como um Especialista em Logística e Gestão de Frotas. Avalie a rota de '{$origem}' para '{$destino}'. Distância: {$distancia_km}km. Tempo estimado: {$tempo_formatado}. Escreva um plano logístico DIRECIONADO AO MOTORISTA contendo:
1. Melhor horário sugerido para partida.
2. Estratégia de paradas (sugestões para refeições, abastecimento e se é necessário pernoitar/dormir devido à duração).
3. Condições e alertas de segurança gerais para esse trajeto.

Responda APENAS com código HTML básico formatado (use <b> para negrito, <br> para quebras de linha, e <ul><li> para listas). NÃO use formatação Markdown como asteriscos (**). Seja conciso e direto.";

    $gemini_url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-3.5-flash-lite:generateContent?key=" . $gemini_api_key;
    
    $payload_gemini = json_encode([
    'contents' => [
        [
            'parts' => [
                ['text' => $prompt_ia]
            ]
        ]
    ]
]);
    
    $ch_gemini = curl_init($gemini_url);
    curl_setopt($ch_gemini, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch_gemini, CURLOPT_POST, true);
    curl_setopt($ch_gemini, CURLOPT_POSTFIELDS, $payload_gemini);
    curl_setopt($ch_gemini, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch_gemini, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch_gemini, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch_gemini, CURLOPT_TIMEOUT, 15);
    
    $gemini_response = curl_exec($ch_gemini);
    $http_code_gemini = curl_getinfo($ch_gemini, CURLINFO_HTTP_CODE);

    $ai_analysis = "";
    if ($gemini_response) {
        $gemini_data = json_decode($gemini_response, true);
        if ($http_code_gemini == 200) {
            // Sucesso: extrai o texto da IA
            $ai_analysis = $gemini_data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        } else {
            // Falha da API: captura a mensagem de erro exata do Google
            $api_error = $gemini_data['error']['message'] ?? 'Erro desconhecido na API Gemini';
            $ai_analysis = "<b>Erro na IA (Código {$http_code_gemini}):</b> {$api_error} <br><br><i>Verifique sua Chave de API.</i>";
        }
    } else {
        $ai_analysis = "<b>Erro de Conexão:</b> O servidor não conseguiu alcançar a API do Gemini.";
    }

    if (empty($ai_analysis)) {
        $ai_analysis = "<b>Agente IA:</b> Rota validada. Distância total de {$distancia_km}km. O percurso demandará {$tempo_formatado}. Recomenda-se pausa a cada 4 horas.";
    }

    echo json_encode([
        'success' => true,
        'origem' => $origem,
        'destino' => $destino,
        'distancia' => $distancia_km,
        'tempo' => $tempo_min,
        'tempo_formatado' => $tempo_formatado,
        'pedagio_num' => $pedagio_estimado,
        'combustivel_num' => $custo_combustivel,
        'pedagio' => number_format($pedagio_estimado, 2, ',', '.'),
        'combustivel' => number_format($custo_combustivel, 2, ',', '.'),
        'ai_analysis' => $ai_analysis,
        'polyline' => $polyline
    ]);
    exit;
}

$prefill_id_rota = isset($_GET['id_rota']) ? (int)$_GET['id_rota'] : '';
$prefill_origem = isset($_GET['origem']) ? htmlspecialchars($_GET['origem']) : '';
$prefill_destino = isset($_GET['destino']) ? htmlspecialchars($_GET['destino']) : '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Logistics BAA - Gestão Inteligente de Rotas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo htmlspecialchars(GOOGLE_MAPS_API_KEY); ?>&libraries=geometry"></script>
    <style>
        body { background-color: #f8f9fa; }
        .navbar { margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-radius: 8px; border: none; }
        #map { height: 600px; width: 100%; border-radius: 8px; }
        .sidebar-card { background: #f8f9fa; border-radius: 8px; padding: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        
        /* Estilos do container colapsável da IA */
        .ai-box-container {
            max-height: 120px;
            overflow: hidden;
            transition: max-height 0.3s ease-in-out;
            cursor: pointer;
            position: relative;
        }
        .ai-box-container.expanded {
            max-height: 1000px;
        }
        .ai-expand-badge {
            font-size: 0.75rem;
            text-align: center;
            color: #0c5460;
            background: rgba(209, 236, 241, 0.9);
            padding: 2px 8px;
            border-radius: 4px;
            margin-top: 6px;
            cursor: pointer;
            user-select: none;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php"><strong>Logistics BAA</strong></a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link text-warning" href="api_recalcular_rota.php">Rotas Cadastradas</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-4">
    <h2 class="mb-4">Otimização de Rota (Modern API)</h2>
    <div class="row">
        <div class="col-md-4">
            <div class="card sidebar-card">
                <h5><?php echo $prefill_id_rota ? "Recalcular Rota #".$prefill_id_rota : "Planejar Nova Rota"; ?></h5>
                <form id="routeForm">
                    <input type="hidden" id="id_rota" value="<?php echo $prefill_id_rota; ?>">
                    <div class="mb-3">
                        <label class="form-label">Origem</label>
                        <input type="text" id="origem" class="form-control" value="<?php echo $prefill_origem; ?>" placeholder="Digite a cidade ou endereço" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Destino</label>
                        <input type="text" id="destino" class="form-control" value="<?php echo $prefill_destino; ?>" placeholder="Digite a cidade ou endereço" required>
                    </div>
                    <button type="button" class="btn btn-primary w-100" onclick="calculateAIRoute()" id="btnCalcular">
                        Calcular Rota
                    </button>
                </form>
                <hr>
                
                <div id="aiSummary" class="mt-3" style="display:none;">
                    <!-- 1. Estatísticas Numéricas Exibidas Primeiro -->
                    <ul class="list-group list-group-flush small mb-3">
                        <li class="list-group-item d-flex justify-content-between"><span>Distância:</span> <strong id="resDistancia"></strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Tempo:</span> <strong id="resTempo"></strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Pedágios (Est.):</span> <strong id="resPedagio"></strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Combustível (Est.):</span> <strong id="resCombustivel"></strong></li>
                    </ul>

                    <!-- 2. Box de Resposta da IA (Expansível / Colapsável via Clique) -->
                    <div class="ai-box-wrapper" onclick="toggleAIBox()">
                        <div id="aiBoxContainer" class="ai-box-container alert alert-info small mb-1" style="line-height: 1.6;">
                            <div id="aiAnalysisText"></div>
                        </div>
                    </div>
                    
                    <div class="mt-4 d-flex gap-2">
                        <button type="button" class="btn btn-success w-50" onclick="saveRoute()" id="btnRegistrar">
                            <?php echo $prefill_id_rota ? "Atualizar" : "Registrar"; ?>
                        </button>
                        <button type="button" class="btn btn-outline-secondary w-50" onclick="recalculateRoute()" id="btnRecalcular">Recalcular</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div id="map" class="shadow-sm border"></div>
        </div>
    </div>
</div>

<script>
let map, routePolyline;
let currentRouteData = null; 

function initMap() {
    map = new google.maps.Map(document.getElementById("map"), {
        zoom: 5,
        center: { lat: -15.793889, lng: -47.882778 } 
    });
    
    const origem = document.getElementById('origem').value;
    const destino = document.getElementById('destino').value;
    if (origem && destino) {
        calculateAIRoute();
    }
}

function toggleAIBox() {
    const container = document.getElementById('aiBoxContainer');
    const badge = document.getElementById('aiExpandBadge');
    
    if (container.classList.contains('expanded')) {
        container.classList.remove('expanded');
    } else {
        container.classList.add('expanded');
    }
}

function calculateAIRoute() {
    const origem = document.getElementById('origem').value;
    const destino = document.getElementById('destino').value;
    const btn = document.getElementById('btnCalcular');
    const recBtn = document.getElementById('btnRecalcular');

    if(!origem || !destino) return alert("Preencha origem e destino!");

    btn.disabled = true;
    btn.innerText = "Processando...";
    if (recBtn) {
        recBtn.disabled = true;
        recBtn.innerText = "Processando...";
    }
    
    // Mostra feedback no quadro
    document.getElementById('aiSummary').style.display = 'block';
    document.getElementById('aiAnalysisText').innerHTML = "<i>Analisando tráfego e solicitando planejamento logístico à IA...</i>";

    fetch('rotas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'calculate', origem, destino }) 
    })
    .then(res => res.text())
    .then(text => {
        btn.disabled = false;
        btn.innerText = "Calcular Rota";
        if (recBtn) {
            recBtn.disabled = false;
            recBtn.innerText = "Recalcular";
        }
        
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            alert("Erro no servidor PHP.");
            return;
        }

        if(data.success) {
            currentRouteData = data; 
            
            if (routePolyline) routePolyline.setMap(null);
            const decodedPath = google.maps.geometry.encoding.decodePath(data.polyline);
            routePolyline = new google.maps.Polyline({
                path: decodedPath,
                geodesic: true,
                strokeColor: '#0d6efd',
                strokeOpacity: 1.0,
                strokeWeight: 4,
                map: map
            });

            const bounds = new google.maps.LatLngBounds();
            decodedPath.forEach(point => bounds.extend(point));
            map.fitBounds(bounds);

            // 1. Atualiza métricas numéricas primeiro
            document.getElementById('resDistancia').innerText = data.distancia + ' km';
            document.getElementById('resTempo').innerText = data.tempo_formatado; 
            document.getElementById('resPedagio').innerText = 'R$ ' + data.pedagio;
            document.getElementById('resCombustivel').innerText = 'R$ ' + data.combustivel;

            // 2. Atualiza a análise da IA logo abaixo
            document.getElementById('aiAnalysisText').innerHTML = data.ai_analysis;
            
            // Reseta para o estado colapsado inicial
            const container = document.getElementById('aiBoxContainer');
            const badge = document.getElementById('aiExpandBadge');
            container.classList.remove('expanded');

        } else {
            alert("Erro: " + data.error);
            document.getElementById('aiSummary').style.display = 'none';
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerText = "Calcular Rota";
        if (recBtn) {
            recBtn.disabled = false;
            recBtn.innerText = "Recalcular";
        }
        alert("Falha de comunicação.");
    });
}

function recalculateRoute() {
    calculateAIRoute();
}

function saveRoute() {
    if (!currentRouteData) return;
    
    const id_rota = document.getElementById('id_rota').value;
    const btn = document.getElementById('btnRegistrar');
    btn.disabled = true;
    btn.innerText = "Salvando...";

    fetch('rotas.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            action: 'save',
            id_rota: id_rota,
            origem: currentRouteData.origem,
            destino: currentRouteData.destino,
            distancia: currentRouteData.distancia,
            tempo: currentRouteData.tempo,
            combustivel: currentRouteData.combustivel_num, 
            pedagio: currentRouteData.pedagio_num, 
            ai_analysis: currentRouteData.ai_analysis
        })
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerText = id_rota ? "Atualizar" : "Registrar";

        if(data.success) {
            alert(id_rota ? "Rota #" + id_rota + " atualizada com sucesso!" : "Rota criada com sucesso!");
            window.location.href = "api_recalcular_rota.php";
        } else {
            alert("Erro ao salvar: " + data.error);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerText = id_rota ? "Atualizar" : "Registrar";
        alert("Falha de comunicação.");
    });
}

window.onload = initMap;
</script>
</body>
</html>
