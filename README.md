# REDCap External Module: DRW Tweaks

## Functionality
Provides some enhancements to REDCap's Data Resolution Workflow functionality, namely:
* Adds a button to data entry forms that will do the "verified value" step for all fields not yet verified or queried.
* CSV exports have all data in separate columns (rather than combining, for example, username and comments). This tweak has no effect from REDCap v9.5.1 when the separation of data into distinct columns was implemented as standard behaviour.
* Queries can be assigned to any user that may access the DAG (i.e. using DAG Switcher). By default, only users that are *currently* assigned to the DAG (or have no DAG assignment) are listed for selection. This tweak applies when raising or editing queries on data entry forms, and when editing queries on the Data Quality "Resolve Issues" page.


## Screenshots
### Verify All Fields
![Verify all](./verify-all.gif)

### CSV Exports (
This module has no effect after v9.5.1 when this was implemented as standard behaviour.
#### Standard CSV exports 
Columns contain multiple pieces of information:

![DRW Export Standard](./drw-export-standard.png)

#### Module CSV exports
Each piece of information in a separate column:

![DRW Export Module](./drw-export-module.png)