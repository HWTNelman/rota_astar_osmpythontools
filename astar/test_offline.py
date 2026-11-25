"""Teste offline (sem internet): grade sintetica no formato do Overpass 'out geom'."""
import astar_osm_router as R

def way(wid, pts, tags):
    return {"type": "way", "id": wid, "nodes": [p[0] for p in pts],
            "geometry": [{"lat": p[1], "lon": p[2]} for p in pts], "tags": tags}

def build(n=25, step=0.002):
    els, nid, ids = [], 1, {}
    for i in range(n):
        for j in range(n):
            ids[(i, j)] = nid; nid += 1
    wid = 1
    for i in range(n):  # ruas horizontais / verticais
        els.append(way(wid, [(ids[(i, j)], -22.0 + i * step, -47.0 + j * step) for j in range(n)], {"highway": "residential"})); wid += 1
        els.append(way(wid, [(ids[(j, i)], -22.0 + j * step, -47.0 + i * step) for j in range(n)], {"highway": "residential"})); wid += 1
    # "rodovia" pedagiada rapida pela borda inferior
    els.append(way(wid, [(ids[(0, j)], -22.0, -47.0 + j * step) for j in range(n)],
                   {"highway": "primary", "maxspeed": "100", "toll": "yes"})); wid += 1
    # radar perto do no (0,12)
    els.append({"type": "node", "id": 99999, "lat": -22.0 + 0.0001, "lon": -47.0 + 12 * step, "tags": {"highway": "speed_camera"}})
    return els

els = build()
nodes, graph, vmax, nrad = R.graph_from_elements(els)
s = R.nearest_node(nodes, -22.0, -47.0); e = R.nearest_node(nodes, -22.0 + 24 * 0.002, -47.0 + 24 * 0.002)
print("nos", len(nodes), "vmax", vmax, "radares ligados", nrad)
for mode in ("time", "distance", "cost", "weighted"):
    a = R.search(nodes, graph, s, e, mode, vmax, True)
    d = R.search(nodes, graph, s, e, mode, vmax, False)
    assert abs(a["objective"] - d["objective"]) < 1e-9, (mode, a["objective"], d["objective"])
    assert a["expanded"] <= d["expanded"]
    t = a["totals"]
    print("%-9s ok  A*=%4d exp, Dijkstra=%4d exp | %.2f km, %.1f min, pedagio R$%.2f, radares %d"
          % (mode, a["expanded"], d["expanded"], t["distance_km"], t["time_hours"] * 60, t["toll_cost"], t["radars"]))
r = R.run("-22.0,-47.0", "-22.048,-46.952", "weighted", True, elements=els)
assert r["success"] and r["comparacao"]["mesma_solucao_otima"]
print("run() ok:", r["distancia_km"], "km;", r["comparacao"])
