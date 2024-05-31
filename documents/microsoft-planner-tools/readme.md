# Microsoft Planner Tools

## Description

Automate the export of Microsoft Planner tasks and their checklists to Microsoft Excel on the last day of each month using Microsoft Power Automate ([Flow template](./tools/microsoft-power-automate-planner-to-excel-flow.zip)). Additionally, create a tasks summary for the most recent export and compare both existing and new tasks marked as completed during each month, saving the output as a Microsoft Excel file ([Code](./microsoft-planner-transform.py)).

To identify the tasks and checklists completed in a given month (`run_month`), apply a filter to the `completed` column to select only the rows with `True` values.
