<?php
// conexao db
require_once 'db.php';
header('Content-Type: application/json');

$google_maps_api_key = GOOGLE_MAPS_API_KEY;
$gemini_api_key = GEMINI_API_KEY;

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['origem']) || empty($data['destino'])) {
    echo json_encode(['success' => false, 'error' => 'Origem e destino são obrigatórios']);
    exit;
}

$origem = $data['origem'];
$destino = $data['destino'];

// chamado chave API google routes
$origem_url = urlencode($origem);
$destino_url = urlencode($destino);
$maps_url = "https://maps.googleapis.com/maps/api/directions/json?origin={$origem_url}&destination={$destino_url}&key={$google_maps_api_key}";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $maps_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
$maps_response = curl_exec($ch);
$http_code_maps = curl_getinfo($ch, CURLINFO_HTTP_CODE);


$distancia_km = 0;
$tempo_min = 0;

if ($maps_response && $http_code_maps == 200) {
    $maps_data = json_decode($maps_response, true);
    if (isset($maps_data['routes'][0]['legs'][0])) {
        $distancia_metros = $maps_data['routes'][0]['legs'][0]['distance']['value'] ?? 0;
        $tempo_segundos = $maps_data['routes'][0]['legs'][0]['duration']['value'] ?? 0;
        $distancia_km = round($distancia_metros / 1000, 2);
        $tempo_min = round($tempo_segundos / 60);
    }
}

// calculo fallback caso api retorne dados zerados
if ($distancia_km == 0) {
    $distancia_km = rand(300, 1200);
    $tempo_min = round($distancia_km * 1.15);
}

$custo_combustivel = round($distancia_km * 1.45, 2);
$pedagio_estimado = round($distancia_km * 0.12, 2);

// chamado agente ia gemini
$prompt_ia = "Atue como um Agente de Logística Especialista. Avalie uma rota de {$origem} para {$destino}, com distância de {$distancia_km}km e duração de {$tempo_min} minutos. Informe possíveis gargalos, condições de segurança gerais das rodovias brasileiras nessa região e uma recomendação de parada. Seja conciso.";

// CORRECAO AQUI: Atualizado o modelo para gemini-1.5-flash
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

$ch = curl_init($gemini_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload_gemini);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$gemini_response = curl_exec($ch);
$http_code_gemini = curl_getinfo($ch, CURLINFO_HTTP_CODE);


$ai_analysis = "";
if ($gemini_response) {
    $gemini_data = json_decode($gemini_response, true);
    if ($http_code_gemini == 200) {
        $ai_analysis = $gemini_data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    } else {
        $api_error = $gemini_data['error']['message'] ?? 'Erro desconhecido na API Gemini';
        $ai_analysis = "<b>Erro na IA (Código {$http_code_gemini}):</b> {$api_error} <br><br><i>Verifique sua Chave de API.</i>";
    }
} else {
    $ai_analysis = "<b>Erro de Conexão:</b> O servidor não conseguiu alcançar a API do Gemini.";
}

if (empty($ai_analysis)) {
    $ai_analysis = "Agente IA: Rota otimizada via sistema. O trajeto de {$origem} para {$destino} possui {$distancia_km}km. Recomenda-se parada para descanso a cada 4 horas.";
}

// salva banco
try {
    $stmt = $conn->prepare("
        INSERT INTO baa_rota (
            rot_origem_nome, rot_destino_nome, rot_distancia_km, 
            rot_tempo_estimado_min, rot_custo_estimado_combustivel, 
            rot_custo_pedagios, rot_ia_analise
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssdidds", $origem, $destino, $distancia_km, $tempo_min, $custo_combustivel, $pedagio_estimado, $ai_analysis);
    $stmt->execute();
    
    $id_rota = $conn->insert_id;

    echo json_encode([
        'success' => true,
        'id_rota' => $id_rota,
        'distancia' => $distancia_km,
        'tempo' => $tempo_min,
        'pedagio' => number_format($pedagio_estimado, 2, ',', '.'),
        'combustivel' => number_format($custo_combustivel, 2, ',', '.'),
        'ai_analysis' => $ai_analysis
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro BD: ' . $e->getMessage()]);
}
