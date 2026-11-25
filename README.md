# Logistics BAA: rotas de entrega com A* e OpenStreetMap

Sistema web de gestão logística (PHP + MySQL) com um motor de roteamento em Python.
O motor aplica o algoritmo **A\*** sobre a malha viária do OpenStreetMap com uma função de
custo em reais (combustível, pedágio, risco de radar e valor do tempo) e compara o resultado
com o **Dijkstra** (mesmo grafo e mesma função de custo). Projeto de apoio ao artigo
*Cálculo para Rotas de Entrega: Implementação de Dados Geoespaciais na Busca Heurística para
Eficiência de Entregas* (Fundação Hermínio Ometto).

## Estrutura

- `astar/astar_osm_router.py`: motor A* / Dijkstra (saída em JSON). `astar/test_offline.py`: teste sem internet.
- `api_astar_rota.php` e `rotas_astar.php`: API e tela de rotas com A* e mapa (Leaflet).
- `rotas.php` e `api_calcular_rota.php`: rotas via Google Directions e texto de análise via Gemini (módulo de referência).
- `sql/log_baa_struct.sql`: estrutura do banco. `sql/migracao_baa_rota.sql`: colunas extras de `baa_rota`.

## Requisitos

XAMPP (Apache + MySQL), Python 3.8+ e `python -m pip install OSMPythonTools`.

## Como rodar

1. Copie a pasta para `C:\xampp\htdocs\logistics_baa\` e inicie Apache e MySQL.
2. No phpMyAdmin, crie o banco `log_baa`, importe `sql/log_baa_struct.sql` e depois `sql/migracao_baa_rota.sql`.
   Ajuste usuário e senha em `db.php`, se necessário.
3. Copie `config.example.php` para `config.php` e preencha as chaves do Google Maps e do Gemini
   (necessárias só para `rotas.php`). **Nunca suba o `config.php` para o GitHub.**
   Se o Apache não achar o Python, defina `BAA_PYTHON` com o caminho completo do `python.exe` no `config.php`.
4. Teste o motor pelo terminal, dentro da pasta `astar`:

   ```
   python astar_osm_router.py --origem "Parque Municipal Fabio da Silva Prado, Araras, SP" --destino "Uniararas, Araras, SP"
   ```

   A primeira consulta de uma região leva alguns minutos (download do Overpass). Depois ela fica em `astar/cache/`.
5. Abra `http://localhost/logistics_baa/rotas_astar.php`.

Modos de otimização (`--modo`): `weighted` (padrão), `time`, `distance`, `cost`.
Parâmetros de custo (combustível, pedágio, radar, valor do tempo) ficam no topo de `astar/astar_osm_router.py`.

## Limitações

Pedágio aproximado por tarifa por km em vias `toll=yes`; radares vêm do OpenStreetMap (cobertura irregular);
sem tráfego em tempo real.
