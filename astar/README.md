# Módulo A* (OpenStreetMap) — Logistics BAA

Requisitos: Python 3.8+ e `pip install OSMPythonTools` (internet só na 1ª consulta de cada região; depois usa `astar/cache/`).

- `astar_osm_router.py`: grafo OSM, A* e Dijkstra (comparação), custo com combustível, pedágio e radares. Saída em JSON.
- `test_offline.py`: teste sem internet (grafo sintético) — confirma que A* e Dijkstra chegam ao mesmo custo ótimo.
- Chamado por `api_astar_rota.php`; tela em `rotas_astar.php`.
- Se o Python não for achado pelo PHP, ajuste `$python_bin` em `api_astar_rota.php` (ex.: `py` ou caminho completo).
- `README_original_osm.md`: README do projeto OSM original.
