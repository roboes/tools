## Brazil Coffee Regions Map Colorizer
# Last update: 2026-02-27

"""
Notes:
Download the Brazil municipalities .svg file from: https://upload.wikimedia.org/wikipedia/commons/d/d1/Brazil_Municipalities.svg

To remove borders from the outputed .svg file
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="680" height="680" viewBox="0 0 680 680">
<style>
  path { stroke: #34432; stroke-width: 0.75px; }
  .outline-group {
    filter: drop-shadow(1px 0 0 black)
            drop-shadow(-1px 0 0 black)
            drop-shadow(0 1px 0 black)
            drop-shadow(0 -1px 0 black);
  }
</style>

<g class="outline-group" fill="#ffffff" fill-rule="nonzero">
    </g>
</svg>
"""

# Import packages
import os
import re

from lxml import etree


HIGHLIGHT_STROKE = '#ffffff'  # stroke for coloured regions only

COLORS = {
    'cerrado_mineiro': '#d17721',  # Cerrado Mineiro
    'matas_de_minas': '#a84e1a',  # Matas de Minas
    'sul_de_minas': '#e8961a',  # Sul de Minas
    'regiao_vulcanica': '#c0392b',  # Região Vulcânica
    'mogiana': '#f0b429',  # Mogiana (SP side)
    'alta_mogiana': '#f5c842',  # Alta Mogiana
    'chapada_diamantina': '#2e7d52',  # Chapada Diamantina
}

REGIONS = {
    'cerrado_mineiro': [
        'Abaeté, MG',
        'Arapuá, MG',
        'Arcos, MG',
        'Araguari, MG',
        'Campos Altos, MG',
        'Carmo do Paranaíba, MG',
        'Coromandel, MG',
        'Córrego Danta, MG',
        'Cruzeiro da Fortaleza, MG',
        'Douradoquara, MG',
        'Estrela do Indaiá, MG',
        'Guarda-Mor, MG',
        'Ibiá, MG',
        'João Pinheiro, MG',
        'Lagamar, MG',
        'Lagoa Formosa, MG',
        'Lagoa Grande, MG',
        'Luz, MG',
        'Matutina, MG',
        'Monte Carmelo, MG',
        'Morada Nova de Minas, MG',
        'Patos de Minas, MG',
        'Patrocínio, MG',
        'Presidente Olegário, MG',
        'Rio Paranaíba, MG',
        'Romaria, MG',
        'Santa Rosa da Serra, MG',
        'São Gonçalo do Abaeté, MG',
        'São Gotardo, MG',
        'Tiros, MG',
        'Unaí, MG',
        'Varjão de Minas, MG',
        'Vazante, MG',
    ],
    'matas_de_minas': [
        'Alto Caparaó, MG',
        'Alto Jequitibá, MG',
        'Caratinga, MG',
        'Carangola, MG',
        'Divino, MG',
        'Espera Feliz, MG',
        'Fervedouro, MG',
        'Itueta, MG',
        'Lajinha, MG',
        'Manhuaçu, MG',
        'Manhumirim, MG',
        'Martins Soares, MG',
        'Matipó, MG',
        'Muriaé, MG',
        'Pedra Dourada, MG',
        'Ponte Nova, MG',
        'Reduto, MG',
        'Simonésia, MG',
        'Tombos, MG',
        'Viçosa, MG',
    ],
    'sul_de_minas': [
        'Alfenas, MG',
        'Cabo Verde, MG',
        'Cambuí, MG',
        'Carmo do Rio Claro, MG',
        'Caxambu, MG',
        'Conceição do Rio Verde, MG',
        'Guaxupé, MG',
        'Heliodora, MG',
        'Lavras, MG',
        'Machado, MG',
        'Monte Belo, MG',
        'Monte Santo de Minas, MG',
        'Muzambinho, MG',
        'Nepomuceno, MG',
        'Paraguaçu, MG',
        'Passos, MG',
        'Pouso Alegre, MG',
        'São Sebastião do Paraíso, MG',
        'São Thomé das Letras, MG',
        'Três Pontas, MG',
        'Varginha, MG',
    ],
    'regiao_vulcanica': [
        'Andradas, MG',
        'Caldas, MG',
        'Campestre, MG',
        'Ipuiúna, MG',
        'Poços de Caldas, MG',
        'Santa Rita de Caldas, MG',
        'Botelhos, MG',
        'Bandeira do Sul, MG',
    ],
    'mogiana': [
        'Altinópolis, SP',
        'Batatais, SP',
        'Brodowski, SP',
        'Cajuru, SP',
        'Casa Branca, SP',
        'Cássia dos Coqueiros, SP',
        'Franca, SP',
        'Igarapava, SP',
        'Itirapuã, SP',
        'Jardinópolis, SP',
        'Mococa, SP',
        'Nuporanga, SP',
        'Orlândia, SP',
        'Pontal, SP',
        'Restinga, SP',
        'Ribeirão Corrente, SP',
        'Sales Oliveira, SP',
        'São Joaquim da Barra, SP',
        'São José da Bela Vista, SP',
        'Santo Antônio da Alegria, SP',
        'Sertãozinho, SP',
    ],
    'alta_mogiana': [
        'Cristais Paulista, SP',
        'Franca, SP',
        'Jeriquara, SP',
        'Patrocínio Paulista, SP',
        'Pedregulho, SP',
        'Rifaina, SP',
        'Buritizal, SP',
        'Guará, SP',
        'Ituverava, SP',
    ],
    'chapada_diamantina': [
        'Abaíra, BA',
        'Andaraí, BA',
        'Barra da Estiva, BA',
        'Boninal, BA',
        'Bonito, BA',
        'Dom Basílio, BA',
        'Ibicoara, BA',
        'Iramaia, BA',
        'Ituaçu, BA',
        'Jussiape, BA',
        'Lençóis, BA',
        'Livramento de Nossa Senhora, BA',
        'Mucugê, BA',
        'Palmeiras, BA',
        'Piatã, BA',
        'Rio de Contas, BA',
        'Seabra, BA',
        'Utinga, BA',
        'Wagner, BA',
    ],
}


def build_lookup(regions):
    """Build a flat dict: normalized_name -> (region_id, color)"""
    lookup = {}
    for region_id, munis in regions.items():
        color = COLORS[region_id]
        for name in munis:
            key = name.strip().lower()
            lookup[key] = (region_id, color)
    return lookup


def process_svg(*, input_file, output_file):
    parser = etree.XMLParser(remove_blank_text=False, huge_tree=True)
    tree = etree.parse(input_file, parser)
    root = tree.getroot()

    # Handle .svg namespace
    ns = root.nsmap.get(None, '')
    svg_ns = f'{{{ns}}}' if ns else ''

    lookup = build_lookup(REGIONS)
    matched_keys = set()
    colored = {k: 0 for k in REGIONS}
    not_found = []

    total_paths = 0
    matched_paths = 0

    for elem in root.iter():
        tag = elem.tag.replace(svg_ns, '')

        if tag == 'path':
            total_paths += 1
            data_name = elem.get('data-name', '')
            key = data_name.strip().lower()

            if key in lookup:
                region_id, color = lookup[key]
                existing_style = elem.get('style', '')
                new_style = re.sub(r'fill\s*:[^;]+;?', '', existing_style).strip()
                new_style = f'fill:{color};stroke:{HIGHLIGHT_STROKE};stroke-width:0.3;{new_style}'
                elem.set('style', new_style)
                colored[region_id] += 1
                matched_paths += 1
                matched_keys.add(key)

    tree.write(output_file, xml_declaration=True, encoding='UTF-8', pretty_print=False)

    print(f'\n✅ Done! {matched_paths}/{total_paths} paths colored.')
    print('\nPer-region count:')
    for rid, count in colored.items():
        print(f'  {rid:12s}: {count} municipalities colored')

    if not_found:
        print(f'\n⚠️  {len(not_found)} names not matched (check spelling):')
        for n in not_found[:20]:
            print(f'   - {n}')

    # Find names that were in your list but not in the .svg
    not_found_list = [name for name in lookup.keys() if name not in matched_keys]

    if not_found_list:
        print(f'\n❌ {len(not_found_list)} cities were NOT found in the .svg:')
        for name in sorted(not_found_list):
            print(f'  - {name}')


process_svg(input_file=os.path.join(os.path.expanduser('~'), 'Downloads', 'Brazil_Municipalities.svg'), output_file=os.path.join(os.path.expanduser('~'), 'Downloads', 'brazil_coffee_regions.svg'))
