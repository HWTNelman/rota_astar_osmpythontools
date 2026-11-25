#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Roteamento com A* sobre dados do OpenStreetMap (Logistics BAA).

Uso (CLI, saida sempre em JSON no stdout):
  python astar_osm_router.py --origem "Araras, SP" --destino "Uniararas, Araras, SP" --modo weighted
  python astar_osm_router.py --origem "-22.357,-47.384" --destino "-22.370,-47.390"

Modos de otimizacao (funcao de custo):
  time      -> minimiza tempo (h)
  distance  -> minimiza distancia (km)
  cost      -> minimiza custo financeiro (combustivel + pedagio + risco de radar, R$)
  weighted  -> custo financeiro + VALOR_HORA * tempo   (padrao, usado no artigo)

Nos pedagios e radares:
  - pedagio: ways com toll=yes cobram TOLL_PER_KM (R$/km) por km percorrido
  - radar:   nos highway=speed_camera (OSM) ligados ao no do grafo mais proximo (<= 60 m);
             cruzar um radar soma RADAR_RISK_COST (R$) e RADAR_SLOWDOWN_S (s)
"""
import argparse
import hashlib
import heapq
import json
import math
import os
import re
import sys
import time

HERE = os.path.dirname(os.path.abspath(__file__))
CACHE_DIR = os.path.join(HERE, "cache")

# --------------------------- PARAMETROS (editaveis) ---------------------------
FUEL_L_PER_KM = 0.085      # ~11,7 km/L
FUEL_PRICE = 6.00          # R$/L
TOLL_PER_KM = 0.12         # R$/km em vias toll=yes
RADAR_RISK_COST = 2.00     # R$ de custo esperado (risco de multa) por radar cruzado
RADAR_SLOWDOWN_S = 6.0     # segundos perdidos desacelerando num radar
VALUE_OF_TIME_PER_H = 30.0 # R$/h usado no modo weighted
RADAR_SNAP_M = 60.0        # distancia maxima para ligar um radar a um no do grafo

DEFAULT_SPEEDS = {
    "motorway": 110, "trunk": 90, "primary": 70, "secondary": 60,
    "tertiary": 50, "residential": 30, "unclassified": 40,
}


# ------------------------------- GEOMETRIA ------------------------------------
def haversine_km(lat1, lon1, lat2, lon2):
    r = 6371.0
    p1, p2 = math.radians(lat1), math.radians(lat2)
    dphi = math.radians(lat2 - lat1)
    dl = math.radians(lon2 - lon1)
    a = math.sin(dphi / 2) ** 2 + math.cos(p1) * math.cos(p2) * math.sin(dl / 2) ** 2
    return 2 * r * math.atan2(math.sqrt(a), math.sqrt(1 - a))


def parse_speed(tags):
    ms = tags.get("maxspeed")
    if ms:
        m = re.search(r"\d+(\.\d+)?", str(ms))
        if m:
            return float(m.group(0))
    hw = str(tags.get("highway", "unclassified")).replace("_link", "")
    return float(DEFAULT_SPEEDS.get(hw, 40))


# ------------------------------ CONSTRUCAO DO GRAFO ---------------------------
def graph_from_elements(elements):
    """
    Constroi o grafo dirigido a partir do JSON bruto do Overpass (out geom).
    nodes[id]  = (lat, lon)
    graph[id]  = lista de arestas (vizinho, dict_metricas)
    Retorna (nodes, graph, vmax_kmh, n_radares_ligados)
    """
    nodes, graph = {}, {}
    cameras = []
    vmax = 0.0

    for el in elements:
        if el.get("type") == "node" and el.get("tags", {}).get("highway") == "speed_camera":
            cameras.append((el["lat"], el["lon"]))

    ways = [e for e in elements if e.get("type") == "way" and e.get("geometry") and e.get("nodes")]
    pending = []  # (id1, id2, dist, tags, mode) arestas; radar e aplicado depois
    for w in ways:
        tags = w.get("tags", {})
        ids = w["nodes"]
        geom = w["geometry"]
        if len(ids) != len(geom):
            continue
        oneway = tags.get("oneway") in ("yes", "true", "1") or tags.get("highway") == "motorway" \
            or tags.get("junction") == "roundabout"
        reverse = tags.get("oneway") == "-1"
        speed = parse_speed(tags)
        vmax = max(vmax, speed)
        toll = tags.get("toll") == "yes"
        for i in range(len(ids) - 1):
            a, b = ids[i], ids[i + 1]
            pa, pb = geom[i], geom[i + 1]
            if pa is None or pb is None:
                continue
            nodes[a] = (pa["lat"], pa["lon"])
            nodes[b] = (pb["lat"], pb["lon"])
            graph.setdefault(a, [])
            graph.setdefault(b, [])
            d = haversine_km(pa["lat"], pa["lon"], pb["lat"], pb["lon"])
            if reverse:
                pending.append((b, a, d, speed, toll))
            elif oneway:
                pending.append((a, b, d, speed, toll))
            else:
                pending.append((a, b, d, speed, toll))
                pending.append((b, a, d, speed, toll))

    # liga cada radar ao no mais proximo (se estiver perto o bastante)
    radar_nodes = set()
    for (clat, clon) in cameras:
        best, best_d = None, None
        for nid, (nlat, nlon) in nodes.items():
            # filtro grosseiro barato antes do haversine
            if abs(nlat - clat) > 0.002 or abs(nlon - clon) > 0.002:
                continue
            dd = haversine_km(clat, clon, nlat, nlon) * 1000.0
            if best_d is None or dd < best_d:
                best, best_d = nid, dd
        if best is not None and best_d <= RADAR_SNAP_M:
            radar_nodes.add(best)

    for (a, b, d, speed, toll) in pending:
        radar = 1 if b in radar_nodes else 0
        time_h = d / speed + radar * (RADAR_SLOWDOWN_S / 3600.0)
        fuel_l = d * FUEL_L_PER_KM
        fuel_cost = fuel_l * FUEL_PRICE
        toll_cost = d * TOLL_PER_KM if toll else 0.0
        radar_cost = radar * RADAR_RISK_COST
        graph[a].append((b, {
            "distance_km": d, "time_hours": time_h, "fuel_liters": fuel_l,
            "fuel_cost": fuel_cost, "toll_cost": toll_cost, "radars": radar,
            "total_cost": fuel_cost + toll_cost + radar_cost,
        }))
    return nodes, graph, (vmax or 110.0), len(radar_nodes)


def nearest_node(nodes, lat, lon):
    return min(nodes, key=lambda n: haversine_km(lat, lon, nodes[n][0], nodes[n][1]))


# ------------------------------ BUSCA (A* / Dijkstra) -------------------------
def search(nodes, graph, start, goal, mode="weighted", vmax=110.0, use_heuristic=True):
    """
    A* (use_heuristic=True) ou Dijkstra (use_heuristic=False, h=0).
    Heuristicas admissiveis e consistentes: usam apenas limites inferiores
    do custo por km (distancia em linha reta, velocidade maxima, combustivel).
    """
    glat, glon = nodes[goal]
    fuel_per_km = FUEL_L_PER_KM * FUEL_PRICE

    def h(n):
        if not use_heuristic:
            return 0.0
        d = haversine_km(nodes[n][0], nodes[n][1], glat, glon)
        if mode == "time":
            return d / vmax
        if mode == "distance":
            return d
        if mode == "cost":
            return d * fuel_per_km
        return d * fuel_per_km + VALUE_OF_TIME_PER_H * d / vmax  # weighted

    def w(m):
        if mode == "time":
            return m["time_hours"]
        if mode == "distance":
            return m["distance_km"]
        if mode == "cost":
            return m["total_cost"]
        return m["total_cost"] + VALUE_OF_TIME_PER_H * m["time_hours"]

    t0 = time.perf_counter()
    g = {start: 0.0}
    came, rec = {}, {}
    closed = set()
    heap = [(h(start), 0, start)]
    counter = 0
    expanded = 0
    while heap:
        _, _, cur = heapq.heappop(heap)
        if cur in closed:
            continue
        closed.add(cur)
        expanded += 1
        if cur == goal:
            path, tot = [], {"distance_km": 0.0, "time_hours": 0.0, "fuel_liters": 0.0,
                             "fuel_cost": 0.0, "toll_cost": 0.0, "radars": 0}
            n = cur
            while n in came:
                path.append(n)
                for k in tot:
                    tot[k] += rec[n][k]
                n = came[n]
            path.append(start)
            path.reverse()
            return {"path": path, "totals": tot, "objective": g[goal], "expanded": expanded,
                    "ms": (time.perf_counter() - t0) * 1000.0}
        for nb, m in graph.get(cur, []):
            if nb in closed:
                continue
            ng = g[cur] + w(m)
            if ng < g.get(nb, float("inf")):
                g[nb] = ng
                came[nb] = cur
                rec[nb] = m
                counter += 1
                heapq.heappush(heap, (ng + h(nb), counter, nb))
    return None


# ------------------------------ OSM (rede) + CACHE ----------------------------
COORD_RE = re.compile(r"^\s*(-?\d+(?:\.\d+)?)\s*,\s*(-?\d+(?:\.\d+)?)\s*$")


def _cache_path(prefix, key):
    os.makedirs(CACHE_DIR, exist_ok=True)
    return os.path.join(CACHE_DIR, "%s_%s.json" % (prefix, hashlib.sha1(key.encode("utf-8")).hexdigest()[:16]))


def geocode(text):
    m = COORD_RE.match(text)
    if m:
        return float(m.group(1)), float(m.group(2))
    cp = _cache_path("geo", text.strip().lower())
    if os.path.exists(cp):
        with open(cp, "r", encoding="utf-8") as f:
            d = json.load(f)
        return d["lat"], d["lon"]
    from OSMPythonTools.nominatim import Nominatim
    res = Nominatim().query(text, params={"countrycodes": "br"}).toJSON()
    if not res:
        raise ValueError("Local nao encontrado no Nominatim: %s" % text)
    lat, lon = float(res[0]["lat"]), float(res[0]["lon"])
    with open(cp, "w", encoding="utf-8") as f:
        json.dump({"lat": lat, "lon": lon}, f)
    return lat, lon


def highway_regex(straight_km):
    """Para distancias maiores usa so vias principais (senao o Overpass estoura)."""
    if straight_km <= 12:
        base = "motorway|trunk|primary|secondary|tertiary|residential|unclassified"
    elif straight_km <= 60:
        base = "motorway|trunk|primary|secondary|tertiary"
    else:
        base = "motorway|trunk|primary"
    links = "|".join(b + "_link" for b in base.split("|") if b in ("motorway", "trunk", "primary", "secondary", "tertiary"))
    return "^(%s%s)$" % (base, ("|" + links) if links else "")


OVERPASS_ENDPOINTS = [
    "https://overpass-api.de/api/",
    "https://overpass.kumi.systems/api/",
    "https://maps.mail.ru/osm/tools/overpass/api/",
    "https://overpass.private.coffee/api/",
]

def fetch_elements(s, e, max_km):
    straight = haversine_km(s[0], s[1], e[0], e[1])
    if straight > max_km:
        raise ValueError("Distancia em linha reta (%.0f km) acima do limite de %d km para consulta ao OSM." % (straight, max_km))
    pad = 0.03 if straight <= 12 else 0.05 + 0.1 * straight / 111.0
    south, north = min(s[0], e[0]) - pad, max(s[0], e[0]) + pad
    west, east = min(s[1], e[1]) - pad, max(s[1], e[1]) + pad
    hw = highway_regex(straight)
    key = "%.3f,%.3f,%.3f,%.3f|%s" % (south, west, north, east, hw)
    cp = _cache_path("osm", key)
    if os.path.exists(cp):
        with open(cp, "r", encoding="utf-8") as f:
            return json.load(f), True

    # A biblioteca OSMPythonTools ja adiciona "[timeout][out:json];" sozinha.
    q = ('('
         'way["highway"~"%s"](%f,%f,%f,%f);'
         'node["highway"="speed_camera"](%f,%f,%f,%f););out geom;'
         % (hw, south, west, north, east, south, west, north, east))

    from OSMPythonTools.overpass import Overpass
    last_err = None
    erros = []
    for endpoint in OVERPASS_ENDPOINTS:
        for attempt in range(2):
            try:
                op = Overpass(endpoint=endpoint)
                data = op.query(q, timeout=180).toJSON()
                elements = data.get("elements", [])
                with open(cp, "w", encoding="utf-8") as f:
                    json.dump(elements, f)
                return elements, False
            except Exception as ex:
                last_err = ex
                erros.append("%s -> %s" % (endpoint, str(ex)[:150]))
                sys.stderr.write("[overpass] falhou: %s\n" % erros[-1])
                time.sleep(1.5)

    raise RuntimeError("Todos os servidores Overpass falharam (502/timeout). Tente novamente ou use uma rota mais curta. Detalhes: %s" % " | ".join(erros[-3:]))


# ------------------------------------ MAIN ------------------------------------
def summarize(r, nodes):
    t = r["totals"]
    return {
        "distancia_km": round(t["distance_km"], 3),
        "tempo_min": round(t["time_hours"] * 60, 2),
        "combustivel_litros": round(t["fuel_liters"], 3),
        "custo_combustivel": round(t["fuel_cost"], 2),
        "custo_pedagios": round(t["toll_cost"], 2),
        "radares": int(t["radars"]),
        "custo_total": round(t["fuel_cost"] + t["toll_cost"] + t["radars"] * RADAR_RISK_COST, 2),
        "nos_no_caminho": len(r["path"]),
        "nos_expandidos": r["expanded"],
        "tempo_execucao_ms": round(r["ms"], 2),
        "objetivo": round(r["objective"], 5),
    }


def run(origem, destino, modo="weighted", compare=True, max_km=250, elements=None):
    t_all = time.perf_counter()
    s, e = geocode(origem), geocode(destino)
    cached = None
    if elements is None:
        elements, cached = fetch_elements(s, e, max_km)
    nodes, graph, vmax, n_rad = graph_from_elements(elements)
    if not nodes:
        raise ValueError("Nenhuma via encontrada na regiao consultada.")
    ns, ne = nearest_node(nodes, *s), nearest_node(nodes, *e)
    a = search(nodes, graph, ns, ne, modo, vmax, True)
    if a is None:
        raise ValueError("Nao ha caminho viavel entre origem e destino na area consultada.")
    out = {"success": True, "algoritmo": "A*", "modo": modo,
           "origem": {"nome": origem, "lat": s[0], "lon": s[1]},
           "destino": {"nome": destino, "lat": e[0], "lon": e[1]},
           "nos_grafo": len(nodes), "arestas_grafo": sum(len(v) for v in graph.values()),
           "radares_no_grafo": n_rad, "cache_osm": cached,
           "caminho": [[nodes[n][0], nodes[n][1]] for n in a["path"]]}
    out.update(summarize(a, nodes))
    if compare:
        d = search(nodes, graph, ns, ne, modo, vmax, False)
        out["comparacao"] = {
            "astar": {"nos_expandidos": a["expanded"], "tempo_ms": round(a["ms"], 2), "objetivo": round(a["objective"], 5)},
            "dijkstra": {"nos_expandidos": d["expanded"], "tempo_ms": round(d["ms"], 2), "objetivo": round(d["objective"], 5)},
            "mesma_solucao_otima": abs(a["objective"] - d["objective"]) < 1e-9,
            "reducao_nos_pct": round(100.0 * (1 - a["expanded"] / d["expanded"]), 1) if d["expanded"] else 0.0,
        }
    out["tempo_total_ms"] = round((time.perf_counter() - t_all) * 1000.0, 1)
    return out


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--origem", required=True)
    ap.add_argument("--destino", required=True)
    ap.add_argument("--modo", default="weighted", choices=["time", "distance", "cost", "weighted"])
    ap.add_argument("--sem-comparacao", action="store_true")
    ap.add_argument("--max-km", type=int, default=250)
    args = ap.parse_args()
    try:
        res = run(args.origem, args.destino, args.modo, not args.sem_comparacao, args.max_km)
    except Exception as ex:  # noqa
        res = {"success": False, "error": str(ex)}
    sys.stdout.write(json.dumps(res, ensure_ascii=False))


if __name__ == "__main__":
    main()
