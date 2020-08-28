<?php
use CRM_Owngroups_ExtensionUtil as E;

class CRM_Owngroups_Form_Report_ExtendedContactDetail extends CRM_Report_Form_Contact_Detail {

  protected $_customGroupExtends = [];


  public function __construct() {
    parent::__construct();

    foreach ([
      'civicrm_contribution',
      'civicrm_membership',
      'civicrm_participant',
      'civicrm_relationship',
      'civicrm_activity',
      'civicrm_activity_target',
      'civicrm_activity_assignment',
      'civicrm_activity_source',
      'civicrm_group',
      ] as $table) {
        unset($this->_columns[$table]);
    }
    $this->_columns['civicrm_group'] = [
      'dao' => 'CRM_Contact_DAO_GroupContact',
      'alias' => 'group',
      'fields' => [
        'title' => [
          'title' => ts('Group Name'),
          'name' => 'title',
          'default' => TRUE,
          'type' => CRM_Report_Form::OP_STRING,
        ],
        'status' => [
          'title' => ts('Status'),
          'name' => 'status',
          'dbAlias' => 'group_contact.status',
          'default' => TRUE,
          'type' => CRM_Report_Form::OP_STRING,
        ],
        'date_added' => [
          'title' => ts('Date Added'),
          'name' => 'date_added',
          'dbAlias' => '(SELECT date FROM civicrm_subscription_history ss WHERE ss.status = \'Added\' AND ss.contact_id = contact_civireport.id AND ss.group_id = group_civireport.id ORDER BY id DESC LIMIT 1 )',
          'default' => TRUE,
          'type' => CRM_Report_Form::OP_STRING,
        ],
        'date_removed' => [
          'title' => ts('Date Removed'),
          'name' => 'date_removed',
          'dbAlias' => '(SELECT date FROM civicrm_subscription_history ss WHERE ss.status = \'Removed\' AND ss.contact_id = contact_civireport.id AND ss.group_id = group_civireport.id ORDER BY id DESC LIMIT 1 )',
          'default' => TRUE,
          'type' => CRM_Report_Form::OP_STRING,
        ],
      ],
      'filters' => [
        'group_id' => [
          'name' => 'group_id',
          'title' => ts('Group(s)'),
          'type' => CRM_Utils_Type::T_INT,
          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
          'options' => CRM_Core_PseudoConstant::nestedGroup(),
        ],
        'group_status' => [
          'name' => 'group_status',
          'title' => ts('Group Status'),
          'type' => CRM_Utils_Type::T_STRING,
          'operatorType' => CRM_Report_Form::OP_SELECT,
          'options' => [
            'Added' => ts('Added'),
            'Removed' => ts('Removed')
          ],
        ],
        'date_added' => [
          'name' => 'date_added',
          'title' => ts('Date Added/Removed'),
          'type' => CRM_Utils_Type::T_DATE,
          'operatorType' => CRM_Report_Form::OP_DATE,
        ],
      ],
    ];
  }

  /**
   * Get operators to display on form.
   *
   * Note: $fieldName param allows inheriting class to build operationPairs specific to a field.
   *
   * @param string $type
   * @param string $fieldName
   *
   * @return array
   */
  public function getOperationPair($type = "string", $fieldName = NULL) {
    if ($fieldName == 'group_id' && $type == CRM_Report_Form::OP_MULTISELECT) {
      $result = [
        'in' => ts('Is one of'),
      ];
      return $result;
    }
    else {
      return parent::getOperationPair($type, $fieldName);
    }
  }

  public function select() {
    $select = [];
    $this->_columnHeaders = [];
    $this->_component = [
      'contribution_civireport',
      'group_civireport',
    ];
    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (!empty($field['required']) ||
            !empty($this->_params['fields'][$fieldName])
          ) {
            //isolate the select clause compoenent wise
            if (in_array($table['alias'], $this->_component)) {
              $select[$table['alias']][] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeadersComponent[$table['alias']]["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
              $this->_columnHeadersComponent[$table['alias']]["{$tableName}_{$fieldName}"]['title'] = $field['title'] ?? NULL;
            }
            else {
              $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = $field['type'] ?? NULL;
              $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            }
          }
        }
      }
    }

    foreach ($this->_component as $val) {
      if (!empty($select[$val])) {
        $this->_selectComponent[$val] = "SELECT " . implode(', ', $select[$val]) . " ";
        unset($select[$val]);
      }
    }

    $this->_select = "SELECT " . implode(', ', $select) . " ";
  }

  public function from() {
    $this->_from = "
      FROM civicrm_contact {$this->_aliases['civicrm_contact']} {$this->_aclFrom}
    ";

    $this->joinAddressFromContact();
    $this->joinCountryFromAddress();
    $this->joinPhoneFromContact();
    $this->joinEmailFromContact();

    if (!empty($this->_params['fields']['group_id']) || !empty($this->_params['group_id_value']) || !empty($this->_params['group_status_value'])) {
      $groupTable = $this->buildGroupTempTable();
      if ($groupTable) {
        $this->_from .= "
        INNER JOIN $groupTable group_temp_table ON {$this->_aliases['civicrm_contact']}.id = group_temp_table.contact_id ";
      }
    }

    // only include tables that are in from clause
    $componentTables = array_intersect($this->_aliases, $this->_component);
    $componentTables = array_flip($componentTables);
    $this->_selectedTables = array_diff($this->_selectedTables, $componentTables);


    if (!empty($this->_selectComponent['group_civireport'])) {
      $filteredStatus = $this->_params['group_status_value'];
      $this->_formComponent['group_civireport'] = <<<HERESQL
      ,{$this->_aliases['civicrm_contact']}.display_name as contact_b_name, group_contact.contact_id as civicrm_group_contact_id
      FROM
        civicrm_contact {$this->_aliases['civicrm_contact']}
       INNER JOIN civicrm_group_contact group_contact ON group_contact.contact_id = {$this->_aliases['civicrm_contact']}.id AND group_contact.status = '$filteredStatus'
       INNER JOIN civicrm_group {$this->_aliases['civicrm_group']} ON {$this->_aliases['civicrm_group']}.id = group_contact.group_id
HERESQL;
    }
  }

  public function where() {
    $clauses = [];

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('filters', $table)) {
        foreach ($table['filters'] as $fieldName => $field) {
          $clause = NULL;
          if (in_array($fieldName, ['group_id', 'group_status', 'date_added'])) {
            continue;
          }
          if (CRM_Utils_Array::value('operatorType', $field) & CRM_Report_Form::OP_DATE
          ) {
            $relative = $this->_params["{$fieldName}_relative"] ?? NULL;
            $from = $this->_params["{$fieldName}_from"] ?? NULL;
            $to = $this->_params["{$fieldName}_to"] ?? NULL;

            $clause = $this->dateClause($field['dbAlias'], $relative, $from, $to);
          }
          else {
            $op = $this->_params["{$fieldName}_op"] ?? NULL;
            $clause = $this->whereClause($field,
              $op,
              CRM_Utils_Array::value("{$fieldName}_value", $this->_params),
              CRM_Utils_Array::value("{$fieldName}_min", $this->_params),
              CRM_Utils_Array::value("{$fieldName}_max", $this->_params)
            );
          }
          if (!empty($clause)) {
            $clauses[] = $clause;
          }
        }
      }
    }

    if (empty($clauses)) {
      $this->_where = "WHERE ( 1 ) ";
    }
    else {
      $this->_where = "WHERE " . implode(' AND ', $clauses);
    }

    if ($this->_aclWhere) {
      $this->_where .= " AND {$this->_aclWhere} ";
    }
  }

  /**
   * @return array
   */
  public function clauseComponent() {
    $selectedContacts = implode(',', $this->_contactSelected);
    $contribution = $membership = $participant = NULL;
    $eligibleResult = $rows = $tempArray = [];
    foreach ($this->_component as $val) {
      if (!empty($this->_selectComponent[$val]) &&
        ($val != 'activity_civireport' && $val != 'relationship_civireport')
      ) {
        $sql = <<<HERESQL
        {$this->_selectComponent[$val]} {$this->_formComponent[$val]}
        WHERE {$this->_aliases['civicrm_contact']}.id IN ( $selectedContacts )
HERESQL;

        $dao = CRM_Core_DAO::executeQuery($sql);
        while ($dao->fetch()) {
          $countRecord = 0;
          $eligibleResult[$val] = $val;
          $CC = 'civicrm_' . substr_replace($val, '', -11, 11) . '_contact_id';
          $row = [];
          foreach ($this->_columnHeadersComponent[$val] as $key => $value) {
            $countRecord++;
            $row[$key] = $dao->$key;
          }

          //if record exist for component(except contact_id)
          //since contact_id is selected for every component
          if ($countRecord > 0) {
            $rows[$dao->$CC][$val][] = $row;
          }
          $tempArray[$dao->$CC] = $dao->$CC;
        }
      }
    }

    return $rows;
  }


  public function postProcess() {

    $this->beginPostProcess();

    // get the acl clauses built before we assemble the query
    $this->buildACLClause($this->_aliases['civicrm_contact']);

    $sql = $this->buildQuery(TRUE);

    $rows = $graphRows = $this->_contactSelected = [];
    $this->buildRows($sql, $rows);
    foreach ($rows as $key => $val) {
      $rows[$key]['contactID'] = $val['civicrm_contact_id'];
      $this->_contactSelected[] = $val['civicrm_contact_id'];
    }

    $this->formatDisplay($rows);

    if (!empty($this->_contactSelected)) {
      $componentRows = $this->clauseComponent();
      $this->alterComponentDisplay($componentRows);

      //unset Conmponent id and contact id from display
      foreach ($this->_columnHeadersComponent as $componentTitle => $headers) {
        $id_header = 'civicrm_' . substr_replace($componentTitle, '', -11, 11) . '_' .
          substr_replace($componentTitle, '', -11, 11) . '_id';
        $contact_header = 'civicrm_' . substr_replace($componentTitle, '', -11, 11) .
          '_contact_id';
        if ($componentTitle == 'activity_civireport') {
          $id_header = 'civicrm_' . substr_replace($componentTitle, '', -11, 11) . '_id';
        }

        unset($this->_columnHeadersComponent[$componentTitle][$id_header]);
        unset($this->_columnHeadersComponent[$componentTitle][$contact_header]);
      }

      $this->assign_by_ref('columnHeadersComponent', $this->_columnHeadersComponent);
      $this->assign_by_ref('componentRows', $componentRows);
    }

    $this->doTemplateAssignment($rows);
    $this->endPostProcess();
  }


  /**
   * @param $componentRows
   */
  public function alterComponentDisplay(&$componentRows) {
    // custom code to alter rows
    $activityTypes = CRM_Core_PseudoConstant::activityType(TRUE, TRUE, FALSE, 'label', TRUE);
    $activityStatus = CRM_Core_PseudoConstant::activityStatus();

    $entryFound = FALSE;
    foreach ($componentRows as $contactID => $components) {
      foreach ($components as $component => $rows) {
        foreach ($rows as $rowNum => $row) {
          // handle contribution
          if ($component == 'contribution_civireport') {
            if ($val = CRM_Utils_Array::value('civicrm_contribution_financial_type_id', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_contribution_financial_type_id'] = CRM_Contribute_PseudoConstant::financialType($val, FALSE);
            }

            if ($val = CRM_Utils_Array::value('civicrm_contribution_contribution_status_id', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_contribution_contribution_status_id'] = CRM_Contribute_PseudoConstant::contributionStatus($val, 'label');
            }
            $entryFound = TRUE;
          }

          if ($component == 'membership_civireport') {
            if ($val = CRM_Utils_Array::value('civicrm_membership_membership_type_id', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_membership_membership_type_id'] = CRM_Member_PseudoConstant::membershipType($val, FALSE);
            }

            if ($val = CRM_Utils_Array::value('civicrm_membership_status_id', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_membership_status_id'] = CRM_Member_PseudoConstant::membershipStatus($val, FALSE);
            }
            $entryFound = TRUE;
          }

          if ($component == 'participant_civireport') {
            if ($val = CRM_Utils_Array::value('civicrm_participant_event_id', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_participant_event_id'] = CRM_Event_PseudoConstant::event($val, FALSE);
              $url = CRM_Report_Utils_Report::getNextUrl('event/income',
                'reset=1&force=1&id_op=in&id_value=' . $val,
                $this->_absoluteUrl, $this->_id
              );
              $componentRows[$contactID][$component][$rowNum]['civicrm_participant_event_id_link'] = $url;
              $componentRows[$contactID][$component][$rowNum]['civicrm_participant_event_id_hover'] = ts('View Event Income details for this Event.');
              $entryFound = TRUE;
            }

            if ($val = CRM_Utils_Array::value('civicrm_participant_participant_status_id', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_participant_participant_status_id'] = CRM_Event_PseudoConstant::participantStatus($val, FALSE);
            }
            if ($val = CRM_Utils_Array::value('civicrm_participant_role_id', $row)) {
              $roles = explode(CRM_Core_DAO::VALUE_SEPARATOR, $val);
              $value = [];
              foreach ($roles as $role) {
                $value[$role] = CRM_Event_PseudoConstant::participantRole($role, FALSE);
              }
              $componentRows[$contactID][$component][$rowNum]['civicrm_participant_role_id'] = implode(', ', $value);
            }

            $entryFound = TRUE;
          }

          if ($component == 'activity_civireport') {
            if ($val = CRM_Utils_Array::value('civicrm_activity_activity_type_id', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_activity_activity_type_id'] = $activityTypes[$val];
            }
            if ($val = CRM_Utils_Array::value('civicrm_activity_activity_status_id', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_activity_activity_status_id'] = $activityStatus[$val];
            }

            $entryFound = TRUE;
          }
          if ($component == 'membership_civireport') {
            if ($val = CRM_Utils_Array::value('civicrm_membership_membership_status_id', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_membership_membership_status_id'] = CRM_Member_PseudoConstant::membershipStatus($val);
            }
            $entryFound = TRUE;
          }

          if ($component == 'group_civireport') {
            if ($val = CRM_Utils_Array::value('civicrm_group_title', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_group_title'] = $val;
            }
            if ($val = CRM_Utils_Array::value('civicrm_group_date_added', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_group_date_added'] = CRM_Utils_Date::customFormat(CRM_Utils_Date::IsoToMysql($val));
            }
            if ($val = CRM_Utils_Array::value('civicrm_group_date_removed', $row)) {
              $componentRows[$contactID][$component][$rowNum]['civicrm_group_date_removed'] = CRM_Utils_Date::customFormat(CRM_Utils_Date::IsoToMysql($val));
            }
            $entryFound = TRUE;
          }

          // skip looking further in rows, if first row itself doesn't
          // have the column we need
          if (!$entryFound) {
            break;
          }
        }
      }
    }
  }

  public function buildGroupTempTable($override = FALSE) {
   $filteredGroups = (array) $this->_params['group_id_value'];
   $filteredStatus = $this->_params['group_status_value'];
   $op = 'in';

   $relative = $this->_params["date_added_relative"] ?? NULL;
   $from = $this->_params["date_added_from"] ?? NULL;
   $to = $this->_params["date_added_to"] ?? NULL;
   $dateClause = $this->dateClause('sh.date', $relative, $from, $to) ?? '(1)';

   if (in_array($op, ['in', 'notin']) && !empty($filteredGroups)) {
     $op = $op == 'in' || $override ? 'IN' : 'NOT IN';
     $where = sprintf(' group_contact.group_id %s (%s)', $op, implode(', ', $filteredGroups));
   }
   elseif (!empty($this->_params['fields']['title'])) {
     $where = '(1)';
   }

   $groupFieldSelect = (!$override && !empty($this->_params['fields']['group_id']) && $op == 'IN');

   $whereUsed = $groupFieldSelect ? '(1)' : $where;
   $whereUsed .= ' AND ' . $dateClause;

   $filteredStatus = ($filteredStatus == '') ? 'Added' : $filteredStatus;

   $query = "
      SELECT DISTINCT group_contact.contact_id,  GROUP_CONCAT(DISTINCT g.title) as group_id
      FROM civicrm_group_contact group_contact
      INNER JOIN civicrm_group g ON g.id = group_contact.group_id
      LEFT JOIN civicrm_subscription_history sh ON sh.group_id = g.id AND sh.status = '$filteredStatus'
      WHERE $whereUsed
      AND group_contact.status = '$filteredStatus'
      GROUP BY group_contact.contact_id
      ";
   $query = CRM_Core_I18n_Schema::rewriteQuery($query);

   $groupTempTable = $this->createTemporaryTable('rptgrp', $query);
   CRM_Core_DAO::executeQuery("ALTER TABLE $groupTempTable ADD INDEX i_id(contact_id)");

   if ($groupFieldSelect && !empty($filteredGroups) && !$override) {
     $excludeQuery = str_replace('(1)', $where, $query);
     CRM_Core_DAO::executeQuery("
     DELETE FROM $groupTempTable WHERE contact_id NOT IN (
       SELECT DISTINCT contact_id
       FROM ( $excludeQuery ) temp
     ) ");
   }

   return $groupTempTable;
 }

}
