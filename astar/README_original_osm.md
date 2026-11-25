# Simulador de Roteamento com Algoritmo A* e OpenStreetMap (`OSMPythonTools`)

Este projeto implementa um algoritmo de busca **A* (A-Estrela)** em Python utilizando dados reais do **OpenStreetMap** através da biblioteca `OSMPythonTools`. O sistema calcula a rota mais viável entre dois pontos, estimando distância total, tempo médio de viagem, consumo de combustível e custos com pedágios.

---

## 📋 Pré-requisitos

* **Python 3.8 ou superior** instalado na máquina.
* Conexão com a internet (necessária para consultar as APIs *Nominatim* e *Overpass* do OpenStreetMap).

---

## 🚀 Como Instalar a Biblioteca e Configurar o Ambiente

### 1. Crie um Ambiente Virtual (Recomendado)
Abra o terminal na pasta do projeto e execute:

* **No Windows:**
  ```bash
  python -m venv venv
  venv\Scripts\activate
  ```

* **No Linux / macOS:**
  ```bash
  python3 -m venv venv
  source venv/bin/activate
  ```

### 2. Instale o `OSMPythonTools`
Com o ambiente virtual ativado, instale o pacote principal via `pip`:

```bash
pip install OSMPythonTools
```

*(Opcional)* Caso encontre erros de certificado SSL ao consultar as APIs no Windows ou macOS, atualize o pacote `certifi`:
```bash
pip install --upgrade certifi
```

---

## ▶️ Como Executar o Projeto

1. Abra o arquivo `astar_osm_router.py` em seu editor de código.
2. Na seção **4. EXECUTION EXAMPLE** (final do arquivo), altere os locais de origem e destino desejados:
   ```python
   start_place = "Parque Municipal Fabio da Silva Prado, Araras, SP"
   end_place = "Uniararas, Araras, SP"
   ```
3. Você também pode ajustar os parâmetros do veículo no início do script:
   * `FUEL_CONSUMPTION_L_PER_KM`: Consumo médio do veículo em litros por km (ex.: `0.085` equivale a ~11,7 km/L).
   * `FUEL_PRICE_PER_LITER`: Preço do combustível por litro.
   * `TOLL_FLAT_FEE`: Valor estimado de tarifa ao passar por vias com pedágio (`toll=yes`).
   * `optimize_for`: No método `a_star_search(...)`, escolha entre `'time'` (menor tempo), `'distance'` (menor distância) ou `'cost'` (menor custo financeiro).

4. Execute o script no terminal:
   ```bash
   python astar_osm_router.py
   ```

---

## 📁 Estrutura dos Arquivos

* `astar_osm_router.py`: Código-fonte completo contendo a consulta ao OpenStreetMap, construção do grafo direcionado e implementação do algoritmo A*.
* `README.md`: Documentação e instruções de instalação e uso.
