<?php
/*****************************************************************************************
**  REDCap is only available through a license agreement with Vanderbilt University
******************************************************************************************/

require_once APP_PATH_DOCROOT . 'ProjectGeneral/math_functions.php';

// namespace MCRI\DRWTweaks; // best not to do this so do not have to prepend \ to all class references in methods here

/**
 * DATA QUALITY - tweaked!
 */
class DataQualityTweaked extends \DataQuality
{
	public function renderResolutionTable($issueStatusType='', $fieldRuleFilter='', $event_id='', $group_id='', $assigned_user_id='', $returnCSV=false)
	{
                if (!$returnCSV) return false;
                
		global $Proj, $lang, $user_rights, $longitudinal;
		// Increase memory limit in case needed for lots of output
		System::increaseMemory(2048);
		// Set max comment length to display in table
		$maxCommentLen = 1000; // 100;
		// Create array to store all fields involved for this status (key=field, value=count)
		$fieldsThisStatus = array();
		// Put user_id=>username in array so we don't have to query each every time
		$userids = array();
		// Create array to store all rules involved for this status (key=rule, value=count)
		$rulesThisStatus = array();
		// Load the DQ custom rules
		$this->loadRules();
		// Check if any DAGs exist. If so, create a new column in the table for each DAG.
		$dags = REDCap::getGroupNames(true); // $Proj->getGroups();
		// Validate group_id input
		if (!isset($dags[$group_id])) $group_id = '';
		// Load array of records as key with their corresponding DAG as value
		if (!empty($dags)) $this->loadDagRecords();
		// Retrieve all data resolution info to fill the table
		$dataIssues = $this->getDataIssuesByStatus($issueStatusType);
		// If longitudinal and filtering by event, then remove results not in that event
		if ($longitudinal && $event_id != '')
		{
			// Loop though all results
			foreach ($dataIssues as $key=>$results)
			{
				if ($event_id != $results['event_id']) {
					// Remove result if not on the selected event
					unset($dataIssues[$key]);
				}
			}
		}
		// If DAGs exist, then reorder results grouped by DAG
		if (!empty($dags) && $user_rights['group_id'] == "")
		{
			// Add group_id, record, and event_id to arrays so we can do a multisort to sort them by DAG
			$groupRecEvts = $dataIssues2 = array();
			// Loop though all results
			foreach ($dataIssues as $key=>$results)
			{
				// Get group_id for this result (if exists)
				$this_group_id = (isset($this->dag_records[$results['record']])) ? $this->dag_records[$results['record']] : '0';
				// If filtering by DAG, then ignore any issues for records not in this DAG
				if ($group_id != '' && $group_id != $this_group_id) {
					unset($dataIssues[$key]);
					continue;
				}
				// Add values to respective arrays
				$groupRecEvts[$key] = $this_group_id . "-" . $results['record'] . "-" . $results['event_id'];
			}
			// Sort according to group, record, event
			asort($groupRecEvts);
			// Now sort the results by DAG, thus grouping them by DAG in the list
			foreach (array_keys($groupRecEvts) as $key) {
				$dataIssues2[$key] = $dataIssues[$key];
			}
			// Replace arrays and unset things no longer needed
			$dataIssues = $dataIssues2;
			unset($groupRecEvts, $results, $dataIssues2);
		}
                
                $resData = array();

                // Add CSV headers
		$resData[] = array($Proj->table_pk);
                if (!empty($dags) && $user_rights['group_id'] == "") {
                        $resData[0][] = 'redcap_data_access_group';
                }
                if ($longitudinal) {
                        $event_names = REDCap::getEventNames(true);
                        $resData[0][] = 'redcap_event_name';
                }
                $resData[0][] = 'instrument';
                $resData[0][] = 'instance';
                $resData[0][] = 'field_or_rule';
                $resData[0][] = 'field_label';
                $resData[0][] = 'number_of_comments';
                $resData[0][] = 'user_assigned';
                $resData[0][] = 'days_open';
                $resData[0][] = 'first_update_user';
                $resData[0][] = 'first_update_time';
                $resData[0][] = 'first_update_comment';
                $resData[0][] = 'last_update_user';
                $resData[0][] = 'last_update_time';
                $resData[0][] = 'last_update_comment';

                // Loop through all data resolution rows
                foreach ($dataIssues as $status_id=>&$attr)
		{
			// If first comment is over X characters, then truncate with ellipsis
			if (strlen($attr['comment_first']) > $maxCommentLen) {
				$attr['comment_first'] = substr($attr['comment_first'], 0, $maxCommentLen-2) . "...";
			}
			// If last comment is over X characters, then truncate with ellipsis
			if (strlen($attr['comment_last']) > $maxCommentLen) {
				$attr['comment_last'] = substr($attr['comment_last'], 0, $maxCommentLen-2) . "...";
			}
			// If is assigned to user, then get username info and also put in $userids array
			$userAssignedItem = '-';
			if ($attr['assigned_user_id'] != '') {
				$user_assigned = $userids[$attr['assigned_user_id']] = (isset($userids[$attr['assigned_user_id']]))
							? $userids[$attr['assigned_user_id']] : User::getUserInfoByUiid($attr['assigned_user_id']);
				$userAssignedItem = $user_assigned['username'];
			}
			$user_first = $userids[$attr['user_id_first']] = (isset($userids[$attr['user_id_first']]))
						? $userids[$attr['user_id_first']] : User::getUserInfoByUiid($attr['user_id_first']);
                        $user_last = $userids[$attr['user_id_last']] = (isset($userids[$attr['user_id_last']]))
                                                ? $userids[$attr['user_id_last']] : User::getUserInfoByUiid($attr['user_id_last']);
                        
			// Display the field name (if field-level) or rule name (if rule-level)
			if ($attr['field_name'] != '') {
                                $instrument = $Proj->metadata[$attr['field_name']]['form_name'];
                                $fieldRule = $attr['field_name'];
				// If field label is long, truncate it
				$field_label = strip_tags($Proj->metadata[$attr['field_name']]['element_label']);
				//if (strlen($field_label) > $this->maxFieldLabelLen) {
				//	$field_label = substr($field_label, 0, $this->maxFieldLabelLen-2) . "...";
				//}
				$rule_id = '';
				// Add fieldname to array and/or increment count
				if (isset($fieldsThisStatus[$attr['field_name']])) {
					$fieldsThisStatus[$attr['field_name']]++;
				} else {
					$fieldsThisStatus[$attr['field_name']] = 1;
				}
			} else {
                                $instrument = '-';
				// Rule-level: Display rule name
				$rule_id = ($attr['pd_rule_id'] != '') ? 'pd-'.$attr['pd_rule_id'] : $attr['rule_id'];
				$this_rule = $this->rules[$rule_id];
				$fieldRule = $lang['dataqueries_14'] . " " . (is_numeric($rule_id) ? '#' : '') . $this_rule['order'] . $lang['colon'] . $this_rule['name'];
				// If also field-level, then display field name as well
				if ($attr['field_name'] != '') {
					// If field label is long, truncate it
					$field_label = strip_tags($Proj->metadata[$attr['field_name']]['element_label']);
					//if (strlen($field_label) > $this->maxFieldLabelLen) {
					//	$field_label = substr($field_label, 0, $this->maxFieldLabelLen-2) . "...";
					//}
                                        $fieldRule .= " {$lang['reporting_49']} {$attr['field_name']}";
				}
				// Add rule to array and/or increment count
				if (isset($rulesThisStatus[$rule_id])) {
					$rulesThisStatus[$rule_id]++;
				} else {
					$rulesThisStatus[$rule_id] = 1;
				}
			}
			// Add filter to limit by rule or fieldname
			if ($fieldRuleFilter != '') {
				if ($fieldRuleFilter == 'all-rules' && $attr['rule_id'] == '' && $attr['pd_rule_id'] == '') {
					// All rules
					continue;
				} elseif ($fieldRuleFilter == 'all-fields' && ($attr['rule_id'] != '' || $attr['pd_rule_id'] != '')) {
					// All fields
					continue;
				} elseif (is_numeric($fieldRuleFilter) && $fieldRuleFilter != $attr['rule_id']) {
					// Custom rule
					continue;
				} elseif (substr($fieldRuleFilter, 0, 3) == 'pd-' && is_numeric(substr($fieldRuleFilter, 3)) && substr($fieldRuleFilter, 3) != $attr['pd_rule_id']) {
					// Pre-defined rule
					continue;
				} elseif (isset($Proj->metadata[$fieldRuleFilter]) && $fieldRuleFilter != $attr['field_name']) {
					// Field name
					continue;
				}
			}
			// If filtering by assigned user, then skip this loop if not assigned to that user
			if ($assigned_user_id != '--NOTASSIGNED--' && $assigned_user_id != '' && $assigned_user_id != $attr['assigned_user_id']) continue;
			if ($assigned_user_id == '--NOTASSIGNED--' && $attr['assigned_user_id'] != '') continue;
			
			// Display instance number if a repeating form/event
			$instanceLabel = ($Proj->isRepeatingEvent($attr['event_id']) || $Proj->isRepeatingForm($attr['event_id'], $Proj->metadata[$attr['field_name']]['form_name']))
							? "{$attr['instance']}"
							: "";

			// Show event name if longitudinal
			if ($longitudinal) {
				$event_name = $event_names[$attr['event_id']];
			}
			// Show DAG label if record is in a DAG
			if (!empty($dags) && isset($this->dag_records[$attr['record']]) && $user_rights['group_id'] == "") {
				$group_name = $dags[$this->dag_records[$attr['record']]];
			} else {
                                $group_name = '';
                        }

                        // Calculate the number of days the query has been open
			$thisIssueStatusType = $attr['query_status'];
			if ($thisIssueStatusType == 'CLOSED') {
				// For closed queries, do datediff from when opened till when closed
				$daysOpen = rounddown(datediff($attr['ts_first'],$attr['ts_last'],"d"),1);
			} elseif ($thisIssueStatusType == 'VERIFIED' || $thisIssueStatusType == 'DEVERIFIED') {
				// For just [de]verified, do not show anything
				$daysOpen = "-";
			} else {
				// For open queries, do datediff from when opened till now
				$daysOpen = rounddown(datediff($attr['ts_first'],NOW,"d"),1);
			}
			// Add row
                        $row = array($attr['record']);
			if (!empty($dags) && $user_rights['group_id'] == "") { $row[] = $group_name; }
                        if ($longitudinal) { $row[] = $event_name; }
                        $row[] = $instrument;
                        $row[] = $instanceLabel;
                        $row[] = $fieldRule;
                        $row[] = $field_label;
                        $row[] = $attr['num_comments'];
                        $row[] = $userAssignedItem;
                        $row[] = $daysOpen;
                        $row[] = $user_first['username'];
                        $row[] = $attr['ts_first'];
                        $row[] = $attr['comment_first'];
                        $row[] = $user_last['username'];
                        $row[] = $attr['ts_last'];
                        $row[] = $attr['comment_last'];
                        $resData[] = $row;
			unset($dataIssues[$status_id], $attr);
		}
                
                return arrayToCsv($resData, false);
	}
        
	// Load array of records as key with their corresponding DAG as value 
        // (Copied as DataQuality class method is private)
	private function loadDagRecords()
	{
		global $Proj;
		// Check if any DAGs exist. If so, create a new column in the table for each DAG.
		$dags = $Proj->getGroups();
		// Get a list of records in all DAGs
		$sql = "select record, value from redcap_data where project_id = " . PROJECT_ID . " and field_name = '__GROUPID__'";
		$q = db_query($sql);
		while ($row = db_fetch_assoc($q))
		{
			$this_group_id = $row['value'];
			// Make sure the DAG actually exists (in case was deleted but value remained in redcap_data)
			if (isset($dags[$this_group_id])) {
				$this->dag_records[$row['record']] = $this_group_id;
			}
		}
	}
}
