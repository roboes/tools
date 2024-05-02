## DKB Bank Transactions
# Last update: 2024-01-02


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


# Settings

## Set working directory
os.chdir(path=os.path.join(os.path.expanduser('~'), 'Documents', 'Transactions'))

## Copy-on-Write (will be enabled by default in version 3.0)
if pd.__version__ >= '1.5.0' and pd.__version__ < '3.0.0':
    pd.options.mode.copy_on_write = True


##############
# Transactions
##############

transactions = (
    pd.read_csv(
        filepath_or_buffer='DKB Transactions.csv',
        sep=';',
        header=0,
        index_col=None,
        skiprows=6,
        skipfooter=0,
        dtype='str',
        engine='python',
        encoding='ISO-8859-1',
        keep_default_na=True,
    )
    # Rename columns
    .rename(
        columns={
            'Buchungstag': 'date_booking',
            'Wertstellung': 'date_value',
            'Buchungstext': 'type',
            'Auftraggeber / Begünstigter': 'company',
            'Verwendungszweck': 'purpose',
            'Kontonummer': 'bank_account_number',
            'BLZ': 'sort_code',
            'Betrag (EUR)': 'amount_eur',
            'Gläubiger-ID': 'creditor_id',
            'Mandatsreferenz': 'mandate_reference',
            'Kundenreferenz': 'end_to_end_reference',
        },
    )
    # Change dtypes
    .assign(
        date_value=lambda row: pd.to_datetime(
            arg=row['date_value'],
            utc=False,
            format='%d.%m.%Y',
        ),
        date_booking=lambda row: pd.to_datetime(
            arg=row['date_booking'],
            utc=False,
            format='%d.%m.%Y',
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
        amount=lambda row: row['amount'].replace(
            to_replace=r'^([0-9]+,[0-9]+) ([A-Z]{3})$',
            value=r'\2 \1',
            regex=True,
        ),
    )
    .assign(
        amount=lambda row: row['amount'].replace(to_replace=r'\.', value=r'', regex=True).replace(to_replace=r',', value=r'.', regex=True),
        amount_eur=lambda row: row['amount_eur'].replace(to_replace=r'\.', value=r'', regex=True).replace(to_replace=r',', value=r'.', regex=True).astype(float),
    )
    # Select columns
    .filter(
        items=[
            'date_booking',
            'date_value',
            'payment_method',
            'type',
            'industry',
            'company',
            'amount',
            'amount_eur',
            'purpose',
            'end_to_end_reference',
            'sort_code',
            'bank_account_number',
            'creditor_id',
            'mandate_reference',
        ],
    )
)


company_mapping = {
    'Aldi Sued': 'Aldi Süd',
    'Amazon': 'Amazon',
    'Anker': 'Anker',
    'BackWerk': 'BackWerk',
    'Bayerische Regiobahn': 'Bayerische Regiobahn GmbH (BRB)',
    'Billa': 'Billa',
    'Bipa': 'Bipa',
    'DB Vertrieb GmbH': 'Deutsche Bahn (DB)',
    'Decathlon': 'Decathlon',
    'DM': 'DM Drogerie Markt',
    'Eurospar': 'Eurospar',
    'Fitinn': 'Fitinn',
    'Goldgas GmbH': 'Goldgas GmbH',
    'Hofer': 'Hofer',
    'IKEA': 'IKEA',
    'Lidl': 'Lidl',
    'Lufthansa': 'Lufthansa',
    'Mc Donalds|McDonalds': "McDonald's",
    'McFit|RSG Group Osterreich Ges.mbH': 'McFit',
    'Media Markt': 'MediaMarkt',
    'Muller': 'Müller',
    'Musikverein Wien': 'Musikverein Wien',
    'MVV|MVG Automaten': 'Münchner Verkehrs- und Tarifverbund (MVV)',
    'OBB|ÖBB': 'Österreichische Bundesbahnen (ÖBB)',
    'Oberosterreichische Versicherung Aktiengesellschaft': 'Oberösterreichischen Versicherung AG',
    'Penny': 'Penny',
    'Primark': 'Primark',
    'REWE': 'REWE',
    'Rossmann': 'Rossmann',
    'SIXT': 'SIXT',
    'Spar': 'Spar',
    'Subway': 'Subway',
    'Tuerkis': 'Türkis',
    'Verbund AG': 'Verbund AG',
    'Wiener Linien': 'Wiener Linien',
    'Wiener Netze GmbH': 'Wiener Netze GmbH',
}

for pattern, company in company_mapping.items():
    transactions['company'] = np.where(
        transactions['company'].str.contains(
            pat=pattern,
            case=False,
            flags=0,
            na=None,
            regex=True,
        ),
        company,
        transactions['company'],
    )

# Delete objects
del company_mapping, pattern, company


industry_mapping = {
    'Anker|BackWerk': 'Bakery',
    'Bipa|DM Drogerie Markt|Müller|Rossmann': 'Drugstore',
    'MediaMarkt': 'Electronics',
    'Musikverein Wien': 'Leisure',
    'Goldgas GmbH|Oberösterreichischen Versicherung AG|Verbund AG|Wiener Netze GmbH': 'Residence',
    "McDonald's|Subway|Türkis": 'Restaurant',
    'IKEA': 'Retail',
    'Decathlon|Fitinn|McFit': 'Sports',
    'Aldi Süd|Billa|Billa Plus|Eurospar|Hofer|Lidl|Penny|REWE|Spar': 'Supermarket',
    'Primark': 'Textiles',
    'Bayerische Regiobahn GmbH \\(BRB\\)|BlaBlaCar|Deutsche Bahn \\(DB\\)|Lufthansa|Münchner Verkehrs- und Tarifverbund \\(MVV\\)|Österreichische Bundesbahnen \\(ÖBB\\)|SIXT|Wiener Linien': 'Transportation',
}

for pattern, industry in industry_mapping.items():
    transactions['industry'] = np.where(
        transactions['company'].str.contains(
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


# Rearrange rows
transactions = transactions.sort_values(
    by=['date_value', 'payment_method', 'type', 'industry', 'company'],
    ignore_index=True,
)
