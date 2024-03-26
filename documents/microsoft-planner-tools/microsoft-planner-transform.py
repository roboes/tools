## Microsoft Planner Transform
# Last update: 2024-03-26


"""About: Create a tasks summary for the most recent export and compare both existing and new tasks marked as completed during each month, saving the output as a Microsoft Excel file."""


###############
# Initial Setup
###############

# Erase all declared global variables
globals().clear()


# Import packages
from datetime import datetime
import os


# import openpyxl
import pandas as pd

# import xlsxwriter


# Settings

## Copy-on-Write (will be enabled by default in version 3.0)
if pd.__version__ >= '1.5.0' and pd.__version__ < '3.0.0':
    pd.options.mode.copy_on_write = True


###########
# Functions
###########


def microsoft_planner_importer(
    *,
    filepath_or_buffer,
    sheet_name,
    labels_mapping=None,
    labels_not_mapped_remove=False,
):

    # Import dataset
    microsoft_planner_checklists_df = (
        pd.read_excel(
            io=filepath_or_buffer,
            sheet_name=sheet_name,
            header=0,
            index_col=None,
            skiprows=0,
            skipfooter=0,
            dtype=None,
            engine='openpyxl',
        )
        .assign(run_month=lambda row: row['run_date'].dt.strftime('%Y-%m'))
        .assign(
            id=lambda row: row['task_id'].astype(str)
            + '_'
            + row['checklist_id'].astype(str),
        )
        # Reorder columns
        .filter(
            items=[
                'id',
                'run_date',
                'run_month',
                'labels',
                'task_id',
                'task_name',
                'task_created_date',
                'task_due_date',
                'task_completed_percent',
                'task_completed_date',
                'assigned_to_ids',
                'checklist_id',
                'checklist_name',
                'checklist_value',
            ],
        )
        # Remove duplicate rows
        .drop_duplicates(
            subset=[
                'id',
                'run_date',
                'run_month',
                'labels',
                'task_id',
                'task_name',
                'task_created_date',
                'task_due_date',
                'task_completed_percent',
                'task_completed_date',
                'assigned_to_ids',
                'checklist_id',
                'checklist_name',
                'checklist_value',
            ],
            keep='first',
            ignore_index=True,
        )
        .assign(
            labels=lambda row: row['labels'].replace(
                to_replace=r'("):true',
                value=r'\1',
                regex=True,
            ),
        )
        .assign(
            labels=lambda row: row['labels'].replace(
                to_replace=r'^{',
                value=r'[',
                regex=True,
            ),
        )
        .assign(
            labels=lambda row: row['labels'].replace(
                to_replace=r'}$',
                value=r']',
                regex=True,
            ),
        )
    )

    if labels_mapping is not None:
        microsoft_planner_checklists_df = microsoft_planner_checklists_df.assign(
            labels=lambda row: row['labels'].replace(
                to_replace=labels_mapping,
                regex=True,
            ),
        )

    if labels_not_mapped_remove is True:
        microsoft_planner_checklists_df = microsoft_planner_checklists_df.assign(
            labels=lambda row: row['labels'].replace(
                to_replace=r'"category[0-9]{1,2}",?',
                value=r'',
                regex=True,
            ),
        )
        microsoft_planner_checklists_df = microsoft_planner_checklists_df.assign(
            labels=lambda row: row['labels'].replace(
                to_replace=r',(])$',
                value=r'\1',
                regex=True,
            ),
        )

    # Return objects
    return microsoft_planner_checklists_df


def microsoft_planner_transform(
    *,
    filepath_or_buffer,
    sheet_name,
    labels_mapping=None,
    labels_not_mapped_remove=False,
):
    """Create a tasks summary for the most recent export and compare both existing and new tasks marked as completed during each month, saving the output as a Microsoft Excel file."""
    # Create variables
    execution_start = datetime.now()

    # Import and transform Microsoft Planner
    microsoft_planner_tasks_summary_df = microsoft_planner_importer(
        filepath_or_buffer=filepath_or_buffer,
        sheet_name=sheet_name,
        labels_mapping=labels_mapping,
        labels_not_mapped_remove=labels_not_mapped_remove,
    )

    # Copy dataset
    microsoft_planner_checklists_df = microsoft_planner_tasks_summary_df

    # Tasks Summary
    microsoft_planner_tasks_summary_df = (
        microsoft_planner_tasks_summary_df
        # Keep last 'run_date' rows
        .query(expr='run_date == run_date.max()')
        # Reorder columns
        .filter(
            items=[
                'run_date',
                'run_month',
                'labels',
                'task_id',
                'task_name',
                'task_created_date',
                'task_due_date',
                'task_completed_percent',
                'task_completed_date',
            ],
        )
        # Remove duplicate rows
        .drop_duplicates(
            subset=['task_id', 'run_date'],
            keep='first',
            ignore_index=True,
        )
        # Reorder rows
        .sort_values(by=['run_date', 'labels', 'task_id'], ignore_index=True)
    )

    ## Checklists Comparer

    # Keep last 'id' value for each 'run_month' rows
    microsoft_planner_checklists_df = pd.merge(
        left=microsoft_planner_checklists_df,
        right=microsoft_planner_checklists_df.groupby(
            by=['run_month', 'id'],
            level=None,
            as_index=False,
            sort=True,
            dropna=True,
        )['run_date'].max(),
        how='inner',
        on=['id', 'run_date', 'run_month'],
        indicator=False,
    ).sort_values(by=['id', 'run_date', 'checklist_value'], ignore_index=True)

    # Create 'previous_value' column
    microsoft_planner_checklists_df['previous_value'] = (
        microsoft_planner_checklists_df.groupby(
            by=['checklist_id'],
            level=None,
            as_index=False,
            sort=True,
            dropna=True,
        )['checklist_value'].shift(periods=1)
    )

    # Create 'completed' column
    microsoft_planner_checklists_df['completed'] = False

    microsoft_planner_checklists_df = (
        microsoft_planner_checklists_df.assign(
            completed=lambda row: (
                (
                    (row['checklist_value']) & (row['previous_value'].isna())
                )  # New 'id' checklists marked as completed
                | (
                    (row['checklist_value'])
                    & (row['checklist_value'] != row['previous_value'])
                )  # Existing 'id' marked as completed
            ),
        )
        # Remove columns
        .drop(
            columns=['id', 'assigned_to_ids', 'previous_value'],
            axis=1,
            errors='ignore',
        )
        # Reorder rows
        .sort_values(
            by=['run_date', 'labels', 'task_id', 'checklist_id'],
            ignore_index=True,
        )
    )

    # Execution time
    print(f'Execution time: {datetime.now() - execution_start}')

    if len(microsoft_planner_checklists_df) > 0:

        with pd.ExcelWriter(
            path=os.path.join(
                os.path.expanduser('~'),
                'Downloads',
                'Microsoft Planner Export Transformed.xlsx',
            ),
            date_format='YYYY-MM-DD',
            datetime_format='YYYY-MM-DD',
            mode='w',
            engine='xlsxwriter',
            engine_kwargs={'options': {'strings_to_formulas': False}},
        ) as writer:
            if len(microsoft_planner_tasks_summary_df) > 0:
                microsoft_planner_tasks_summary_df.to_excel(
                    excel_writer=writer,
                    sheet_name='Tasks Summary',
                    na_rep='',
                    header=True,
                    index=False,
                    index_label=None,
                    freeze_panes=(1, 0),
                )
            if len(microsoft_planner_checklists_df) > 0:
                microsoft_planner_checklists_df.to_excel(
                    excel_writer=writer,
                    sheet_name='Checklists Comparer',
                    na_rep='',
                    header=True,
                    index=False,
                    index_label=None,
                    freeze_panes=(1, 0),
                )

        print('')
        print(
            '\'Microsoft Planner Export Transformed.xlsx\' file was saved to the Downloads folder.',
        )


############################
# Microsoft Planner Comparer
############################

# Labels mapping dictionary
labels_mapping = {
    '"category1"': '"Squad - ARC Governance"',
    '"category2"': '"Squad - Data & Business Services"',
    '"category3"': '"Squad - Technology, Innovation & Business Development"',
    '"category5"': '"Squad - Productivity Solutions"',
    '"category6"': '"Squad - ARC Core Apps"',
}

microsoft_planner_transform(
    filepath_or_buffer=os.path.join(
        os.path.expanduser('~'),
        'Allianz',
        'ARC Business Solutions & Transformation - Documents',
        'SQD - ARC Governance',
        'Microsoft Planner Export.xlsx',
    ),
    sheet_name='PlannerExport',
    labels_mapping=labels_mapping,
    labels_not_mapped_remove=True,
)
