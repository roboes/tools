## DKB Bank Transactions
# Last update: 2025-12-21


"""About: Import Deutsche Kreditbank AG (DKB) bank transactions (Umsätze) from a .csv export file and perform data cleansing and transformations."""


###############
# Initial Setup
###############

# Erase all declared global variables
globals().clear()


# Import packages
import os

import pandas as pd
import numpy as np


##############
# Transactions
##############

transactions = (
    pd.read_csv(
        filepath_or_buffer=os.path.join(os.path.expanduser('~'), 'Downloads', 'DKB Transactions.csv'),
        sep=';',
        header=0,
        index_col=None,
        skiprows=4,
        skipfooter=0,
        dtype='str',
        engine='python',
        encoding='utf-8',
        keep_default_na=True,
    )
    # Rename columns
    .rename(
        columns={
            'Buchungsdatum': 'booking_date',
            'Wertstellung': 'value_date',
            'Status': 'status',
            'Zahlungspflichtige*r': 'debtor',
            'Zahlungsempfänger*in': 'creditor',
            'Verwendungszweck': 'purpose',
            'Umsatztyp': 'transaction_type',
            'IBAN': 'iban',
            'Betrag (€)': 'amount_eur',
            'Gläubiger-ID': 'creditor_id',
            'Mandatsreferenz': 'mandate_reference',
            'Kundenreferenz': 'end_to_end_reference',
        },
    )
    # Change dtypes
    .assign(
        value_date=lambda row: pd.to_datetime(
            arg=row['value_date'],
            utc=False,
            format='%d.%m.%y',
        ),
        booking_date=lambda row: pd.to_datetime(
            arg=row['booking_date'],
            utc=False,
            format='%d.%m.%y',
        ),
    )
    # Create columns
    .assign(
        payment_method=lambda row: 'Debit Card',
        industry=lambda row: None,
        amount=lambda row: row['purpose'].str.extract(
            pat=r'Original ([0-9]+,[0-9]+ [A-Z]{3}) 1 Euro=',
            flags=0,
            expand=False,
        ),
    )
    # Transform columns
    .assign(
        amount=lambda row: row['amount'].str.replace(
            pat=r'^([0-9]+,[0-9]+) ([A-Z]{3})$',
            repl=r'\2 \1',
            regex=True,
        ),
    )
    .assign(
        amount=lambda row: row['amount'].str.replace(pat=r'\.', repl=r'', regex=True).str.replace(pat=r',', repl=r'.', regex=True),
        amount_eur=lambda row: row['amount_eur'].str.replace(pat=r'\.', repl=r'', regex=True).str.replace(pat=r',', repl=r'.', regex=True).astype(float),
    )
    # Select columns
    .filter(
        items=[
            'booking_date',
            'value_date',
            'payment_method',
            # 'status',
            # 'debtor',
            'industry',
            'creditor',
            # 'transaction_type',
            'iban',
            'amount',
            'amount_eur',
            'purpose',
            'end_to_end_reference',
            # 'creditor_id',
            'mandate_reference',
        ],
    )
)


creditor_mapping = {
    'Air.Europa': 'Air Europa',
    'Aldi.Sued|Aldi Sued': 'Aldi Süd',
    'Allianz Versicherungs-AG': 'Allianz',
    'Amazon': 'Amazon',
    'Anker': 'Anker',
    'BackWerk': 'BackWerk',
    'Bauhaus': 'Bauhaus',
    'Bayerische Regiobahn': 'Bayerische Regiobahn GmbH (BRB)',
    'Billa': 'Billa',
    'Bipa': 'Bipa',
    'DB Vertrieb GmbH': 'Deutsche Bahn (DB)',
    'Decathlon': 'Decathlon',
    'Deutsche.Post': 'Deutsche Post',
    'DM': 'DM Drogerie Markt',
    'EDEKA': 'EDEKA',
    'EGYM Wellpass': 'EGYM Wellpass',
    'eprimo': 'eprimo',
    'Eurospar': 'Eurospar',
    'Fitinn': 'Fitinn',
    'Go.Asia': 'Go Asia',
    'Goldgas GmbH': 'Goldgas GmbH',
    'Hagebau': 'Hagebau',
    'Hanse.Merkur': 'Hanse Merkur',
    'Hofer': 'Hofer',
    'IKEA': 'IKEA',
    'Kaufland': 'Kaufland',
    'Lidl': 'Lidl',
    'Lufthansa': 'Lufthansa',
    'M-net Telek. GmbH': 'M-net',
    'Mc Donalds|McDonalds': "McDonald's",
    'McFit|RSG Group Osterreich Ges.mbH': 'McFit',
    'Media Markt': 'MediaMarkt',
    'Metro.Sagt.Danke': 'METRO',
    'MUE VERKEHRSGESELLS|Muenchner Verkehrsge': 'Münchner Verkehrsgesellschaft (MVG)',
    'Muller': 'Müller',
    'Musikverein Wien': 'Musikverein Wien',
    'MVV|MVG Automaten': 'Münchner Verkehrs- und Tarifverbund (MVV)',
    'Netflix': 'Netflix',
    'Netto': 'Netto',
    'OBB|ÖBB': 'Österreichische Bundesbahnen (ÖBB)',
    'Oberosterreichische Versicherung Aktiengesellschaft': 'Oberösterreichischen Versicherung AG',
    'Penny': 'Penny',
    'Primark': 'Primark',
    'REWE': 'REWE',
    'Rossmann': 'Rossmann',
    'Rundfunk': 'Rundfunkbeitrag',
    'SIXT': 'SIXT',
    'Spar': 'Spar',
    'Subway': 'Subway',
    'TAP.Airlines': 'TAP Airlines',
    'Tegut': 'Tegut',
    'TEMU': 'TEMU',
    'Tuerkis': 'Türkis',
    'Uber': 'Uber',
    'Verbund AG': 'Verbund AG',
    'Wiener Linien': 'Wiener Linien',
    'Wiener Netze GmbH': 'Wiener Netze GmbH',
}

for pattern, creditor in creditor_mapping.items():
    transactions['creditor'] = np.where(
        transactions['creditor'].str.contains(
            pat=pattern,
            case=False,
            flags=0,
            na=None,
            regex=True,
        ),
        creditor,
        transactions['creditor'],
    )

# Delete objects
del creditor_mapping, pattern, creditor


industry_mapping = {
    'Anker|BackWerk': 'Bakery',
    'Bipa|DM Drogerie Markt|Müller|Rossmann': 'Drugstore',
    'MediaMarkt': 'Electronics',
    'Apotheke': 'Health',
    'Bauhaus|Hagebau': 'Household',
    'Allianz|Hanse Merkur': 'Insurance',
    'Musikverein Wien|Netflix': 'Leisure',
    'eprimo|Goldgas GmbH|Oberösterreichischen Versicherung AG|M-net|Verbund AG|Wiener Netze GmbH': 'Residence',
    "McDonald's|Subway|Türkis": 'Restaurant',
    'Amazon|IKEA|TEMU': 'Retail',
    'Deutsche Post': 'Services',
    'Decathlon|EGYM Wellpass|Fitinn|McFit': 'Sports',
    'Aldi Süd|Billa|Billa Plus|EDEKA|Eurospar|Go Asia|Hofer|Kaufland|Lidl|METRO|Netto|Penny|REWE|Spar|Tegut': 'Supermarket',
    'Rundfunkbeitrag': 'Taxes',
    'Primark': 'Textiles',
    'Air Europa|Bayerische Regiobahn GmbH \\(BRB\\)|BlaBlaCar|Deutsche Bahn \\(DB\\)|Lufthansa|Münchner Verkehrs- und Tarifverbund \\(MVV\\)|Münchner Verkehrsgesellschaft \\(MVG\\)|Österreichische Bundesbahnen \\(ÖBB\\)|SIXT|TAP Airlines|Uber|Wiener Linien': 'Transportation',
}

for pattern, industry in industry_mapping.items():
    transactions['industry'] = np.where(
        transactions['creditor'].str.contains(
            pat=pattern,
            case=True,
            flags=0,
            na=None,
            regex=True,
        ),
        industry,
        transactions['industry'],
    )

# Delete objects
del industry_mapping, pattern, industry

purpose_mapping = {
    'Hundesteuer': 'Taxes',
}

for pattern, purpose in purpose_mapping.items():
    transactions['industry'] = np.where(
        transactions['purpose'].str.contains(
            pat=pattern,
            case=True,
            flags=0,
            na=None,
            regex=True,
        ),
        purpose,
        transactions['industry'],
    )

# Delete objects
del purpose_mapping, pattern, purpose


# Rearrange rows
transactions = transactions.sort_values(
    by=['value_date', 'payment_method', 'industry', 'creditor'],
    ignore_index=True,
)

# Save
with pd.ExcelWriter(
    path=os.path.join(os.path.expanduser('~'), 'Downloads', 'DKB Transactions.xlsx'),
    date_format='YYYY-MM-DD',
    datetime_format='YYYY-MM-DD',
    engine='xlsxwriter',
    engine_kwargs={'options': {'strings_to_formulas': False, 'strings_to_urls': False}},
) as writer:
    # Income
    transactions.query(expr='amount_eur >= 0').to_excel(excel_writer=writer, sheet_name='Income', na_rep='', header=True, index=False, index_label=None, freeze_panes=(1, 0))
    # Expenses
    transactions.query(expr='amount_eur < 0').assign(amount_eur=lambda row: row['amount_eur'].abs()).to_excel(
        excel_writer=writer, sheet_name='Expenses', na_rep='', header=True, index=False, index_label=None, freeze_panes=(1, 0)
    )
