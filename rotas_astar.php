<?php require_once 'db.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Logistics BAA - Rotas com A* (OpenStreetMap)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { margin-bottom: 30px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.05); border-radius: 8px; border: none; }
        #map { height: 600px; width: 100%; border-radius: 8px; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php"><strong>Logistics BAA</strong></a>
        <ul class="navbar-nav ms-auto flex-row gap-3">
            <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
            <li class="nav-item"><a class="nav-link" href="rotas.php">Rotas (Google)</a></li>
            <li class="nav-item"><a class="nav-link text-warning" href="api_recalcular_rota.php">Rotas Cadastradas</a></li>
        </ul>
    </div>
</nav>
<div class="container my-4">
    <h2 class="mb-4">Rotas com A* (OpenStreetMap)</h2>
    <div class="row">
        <div class="col-md-4">
            <div class="card p-3">
                <div class="mb-3"><label class="form-label">Origem</label>
                    <input id="origem" class="form-control" value="Parque Municipal Fabio da Silva Prado, Araras, SP"></div>
                <div class="mb-3"><label class="form-label">Destino</label>
                    <input id="destino" class="form-control" value="Uniararas, Araras, SP"></div>
                <div class="mb-3"><label class="form-label">Otimizar por</label>
                    <select id="modo" class="form-select">
                        <option value="weighted">Custo ponderado (R$ + tempo)</option>
                        <option value="time">Menor tempo</option>
                        <option value="distance">Menor distância</option>
                        <option value="cost">Menor custo (R$)</option>
                    </select></div>
                <div class="form-check mb-3"><input class="form-check-input" type="checkbox" id="salvar">
                    <label class="form-check-label" for="salvar">Salvar em Rotas Cadastradas</label></div>
                <button class="btn btn-primary w-100" id="btn" onclick="calcular()">Calcular com A*</button>
                <div id="msg" class="small mt-3"></div>
                <div id="res" style="display:none" class="mt-3">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between"><span>Distância</span><strong id="rDist"></strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Tempo</span><strong id="rTempo"></strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Combustível</span><strong id="rComb"></strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Pedágios</span><strong id="rPed"></strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Radares no trajeto</span><strong id="rRad"></strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Custo total</span><strong id="rTot"></strong></li>
                    </ul>
                    <div class="alert alert-info small mt-3 mb-0" id="rCmp"></div>
                </div>
            </div>
        </div>
        <div class="col-md-8"><div id="map" class="shadow-sm border"></div></div>
    </div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const map = L.map('map').setView([-22.357, -47.384], 13);
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution: '&copy; OpenStreetMap'}).addTo(map);
let layer = null;
const brl = v => 'R$ ' + Number(v).toFixed(2).replace('.', ',');

function calcular() {
    const btn = document.getElementById('btn'), msg = document.getElementById('msg');
    btn.disabled = true; msg.className = 'small mt-3 text-muted';
    msg.textContent = 'Consultando OpenStreetMap e executando A*... (a 1ª consulta de uma região pode levar 1–2 min; depois fica em cache)';
    fetch('api_astar_rota.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            origem: origem.value, destino: destino.value, modo: modo.value, salvar: salvar.checked
        })
    }).then(r => r.json()).then(d => {
        btn.disabled = false;
        if (!d.success) { msg.className = 'small mt-3 text-danger'; msg.textContent = d.error; return; }
        msg.className = 'small mt-3 text-success';
        msg.textContent = d.aviso_bd ? d.aviso_bd : (d.id_rota ? 'Rota salva (#' + d.id_rota + ').' : 'Rota calculada.');
        if (layer) map.removeLayer(layer);
        layer = L.layerGroup([
            L.polyline(d.caminho, {color: '#0d6efd', weight: 5}),
            L.marker([d.origem.lat, d.origem.lon]).bindPopup('Origem'),
            L.marker([d.destino.lat, d.destino.lon]).bindPopup('Destino')
        ]).addTo(map);
        map.fitBounds(L.polyline(d.caminho).getBounds(), {padding: [30, 30]});
        rDist.textContent = d.distancia_km.toFixed(2) + ' km';
        rTempo.textContent = d.tempo_min.toFixed(1) + ' min';
        rComb.textContent = brl(d.custo_combustivel) + ' (' + d.combustivel_litros.toFixed(2) + ' L)';
        rPed.textContent = brl(d.custo_pedagios);
        rRad.textContent = d.radares;
        rTot.textContent = brl(d.custo_total);
        const c = d.comparacao;
        rCmp.innerHTML = '<b>A*</b> expandiu ' + c.astar.nos_expandidos + ' nós (' + c.astar.tempo_ms + ' ms) · '
            + '<b>Dijkstra</b> ' + c.dijkstra.nos_expandidos + ' nós (' + c.dijkstra.tempo_ms + ' ms).<br>'
            + 'Redução de nós: ' + c.reducao_nos_pct + '% · mesma solução ótima: ' + (c.mesma_solucao_otima ? 'sim' : 'NÃO')
            + '<br>Grafo: ' + d.nos_grafo + ' nós, ' + d.arestas_grafo + ' arestas.';
        res.style.display = 'block';
    }).catch(e => { btn.disabled = false; msg.className = 'small mt-3 text-danger'; msg.textContent = 'Erro: ' + e; });
}
</script>
</body>
</html>
