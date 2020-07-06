<?php
define('PROFILE_ID', 15);
define('CONSENT', 5);
define('MSG_ID_CONSENT', 68);
define('MSG_ID_THANKS', 69);
define('MSG_ID_REQUEST_CONSENT', 70);

require_once 'owngroups.civix.php';

/**
 * Implementation of hook_civicrm_config
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function owngroups_civicrm_config(&$config) {
  _owngroups_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function owngroups_civicrm_xmlMenu(&$files) {
  _owngroups_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function owngroups_civicrm_install() {
  _owngroups_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function owngroups_civicrm_uninstall() {
  _owngroups_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function owngroups_civicrm_enable() {
  _owngroups_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function owngroups_civicrm_disable() {
  _owngroups_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function owngroups_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _owngroups_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function owngroups_civicrm_managed(&$entities) {
  _owngroups_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_caseTypes
 *
 * Generate a list of case-types
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function owngroups_civicrm_caseTypes(&$caseTypes) {
  _owngroups_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implementation of hook_civicrm_alterSettingsFolders
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function owngroups_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _owngroups_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implementation of hook_civicrm_selectWhereClause
 *
 * @param string $entity
 * @param array $clauses
 */
function owngroups_civicrm_selectWhereClause($entity, &$clauses) {
  if ($entity == 'Group' && $cs = CRM_Utils_Request::retrieve('cs', 'String')) {
    if ($id = CRM_Utils_Request::retrieve('id', 'Positive')) {
      if (CRM_Contact_BAO_Contact_Utils::validChecksum($id, $cs)) {
        $validGroups = [42,45,3,36,9,51,2,31,32,7,4,8,5,23,49,6,21];
        $ids = [];
        $groups = (array) civicrm_api3('GroupContact', 'get', [
          'sequential' => 1,
          'return' => ["group_id", "visibility"],
          'contact_id' => $id,
          'status' => "Added",
        ])['values'];
        foreach ($groups as $group) {
          if ($group['visibility'] == 'User and User Admin Only') {
            continue;
          }
          $ids[] = $group['group_id'];
        }
        $ids = array_merge($ids, $validGroups);
        if (!empty($ids)) {
          $clauses['id'][] = 'IN (' . implode(',', $ids) . ')';
        }
        else {
          $clauses['id'][] = 'IN (0)';
        }
      }
      else {
        CRM_Utils_System::permissionDenied();
        CRM_Utils_System::civiExit();
      }
    }
    else {
      CRM_Utils_System::permissionDenied();
      CRM_Utils_System::civiExit();
    }
  }
}

function owngroups_civicrm_pageRun(&$page) {
  if ((get_class($page) == "CRM_Profile_Page_Dynamic" && ($page->getVar('_gid') == PROFILE_ID)) || get_class($page) == "CRM_Mailing_Page_Confirm") {
    CRM_Core_Region::instance('page-body')->add(array(
      'template' => 'CRM/YeeHong.tpl',
    ));
  }
}

/**
 * Implements hook_civicrm_buildForm().
 *
 * @param string $formName
 * @param CRM_Core_Form $form
 */
function owngroups_civicrm_buildForm($formName, &$form) {
  if ($formName == "CRM_Profile_Form_Edit" && $form->getVar('_gid') == PROFILE_ID) {
    if ($cs = CRM_Utils_Request::retrieve('cs', 'String')) {
      if ($id = CRM_Utils_Request::retrieve('id', 'Positive')) {
        if (CRM_Contact_BAO_Contact_Utils::validChecksum($id, $cs)) {
          CRM_Core_Region::instance('page-body')->add(array(
            'template' => 'CRM/YeeHong.tpl',
          ));
          $form->freeze(['email-Primary']);
        }
      }
    }
  }
}

/**
 * Implements hook_civicrm_tokens().
 *
 * @param array $tokens
 */
function owngroups_civicrm_tokens(&$tokens) {
  $tokens['owngroups'] = array(
    'owngroups.grouptitle' => 'Contact Groups',
  );
}
 

/**
 * Implements hook_civicrm_tokenValues().
 *
 * @param array $values
 * @param array $cid
 * @param integer $job
 * @param array $tokens
 * @param string $context
 */
function owngroups_civicrm_tokenValues(&$values, $cids, $job = null, $tokens = array(), $context = null) {
  if (!empty($tokens['owngroups'])) {
    foreach ($cids as $cid) {
      $groups = (array) civicrm_api3('GroupContact', 'get', [
        'sequential' => 1,
        'return' => ["group_id", "visibility"],
        'contact_id' => $cid,
        'status' => "Added",
      ])['values'];
      foreach ($groups as $group) {
        if (in_array($group['group_id'], [42,45,3,36,9,51,2,31,32,7,4,8,5,23,49,6,21])) {
          $groupTitles[] = $group['title'];
        }
      }
      if (!empty($groupTitles)) {
        $values[$cid]['owngroups.grouptitle'] = implode(',', $groupTitles);
      }
      else {
        $values[$cid]['owngroups.grouptitle'] = "No campaign(s) selected.";
      }
    }
  }
}

/**
 * Implements hook_civicrm_postProcess().
 *
 * @param string $formName
 * @param CRM_Core_Form $form
 */
function owngroups_civicrm_postProcess($formName, &$form) {
  if ($formName == "CRM_Profile_Form_Edit" && $form->getVar('_gid') == PROFILE_ID) {
    // Check groups and get group names.
    if (!empty(array_filter($form->_submitValues['group']))) {
      foreach ($form->_submitValues['group'] as $group => $value) {
        if ($value) {
          $groups[] = civicrm_api3('Group', 'getvalue', [
            'return' => "title",
            'id' => $group,
          ]);
        }
      }
    }
    $activityParams = array( 
      'activity_type_id' => 'Preferences Updated',
      'subject' => 'Preferences have been updated',
      'status_id' => 'Completed',
      'activity_date_time' => date('YmdHis'),
      'source_contact_id' => $form->getVar('_id'),
      'target_contact_id' => $form->getVar('_id'),
    );
    
    if (!empty($groups)) {
      $form->assign('groupsContact', implode(',', $groups));
      $activityParams['details'] = "Campaigns selected: " . implode(',', $groups);
    }
    else {
      $form->assign('groupsContact', ts('No campaigns selected.'));
      $activityParams['details'] = ts('No campaigns selected.');
    }
    civicrm_api3('Activity', 'create', $activityParams);
    // Check if contact has consented previously.
    $consent = CRM_Utils_Array::collect('custom_' . CONSENT, civicrm_api3('Contact', 'get', [
      'id' => $form->getVar('_id'),
      'sequential' => 1,
      'return' => 'custom_' . CONSENT
    ])['values']);
    $isSent = FALSE;
    if (empty($consent[0])) {
      // Send email since this is the first time of visit.
      $email = civicrm_api3('Email', 'send', [
        'contact_id' => $form->getVar('_id'),
        'template_id' => MSG_ID_CONSENT,
      ]);
      $email = civicrm_api3('Email', 'send', [
        'contact_id' => $form->getVar('_id'),
        'template_id' => MSG_ID_THANKS,
      ]);
      $isSent = TRUE;
      if (!$email['is_error']) {
        civicrm_api3('CustomValue', 'create', [
          'entity_id' => $form->getVar('_id'),
          'custom_' . CONSENT => 1,
        ]);
        $displayName = CRM_Contact_BAO_Contact::displayName($form->getVar('_id'));
        $consentActivity = array(
          'activity_type_id' => 'Consent Given',
          'subject' => "Contact $displayName has granted consent",
          'status_id' => 'Completed',
          'activity_date_time' => date('YmdHis'),
          'source_contact_id' => $form->getVar('_id'),
          'target_contact_id' => $form->getVar('_id'),
        );
        civicrm_api3('Activity', 'create', $consentActivity);
      }
    }
    if (!$isSent) {
      $email = civicrm_api3('Email', 'send', [
        'contact_id' => $form->getVar('_id'),
        'template_id' => MSG_ID_THANKS,
      ]);
    }
  }
}

function owngroups_civicrm_alterMailingRecipients(&$mailingObject, &$criteria, $context) {
  if ($context == 'pre' && !empty($mailingObject->msg_template_id) && $mailingObject->msg_template_id == MSG_ID_REQUEST_CONSENT) {
    // criteria to exclude contacts who have already consented.
    $criteria['consent_filter'] = CRM_Utils_SQL_Select::fragment()
                                  ->join('civicrm_value_consent_2', "LEFT JOIN civicrm_value_consent_2 et ON et.entity_id = civicrm_contact.id")
                                  ->where("et.has_consented__5 IS NULL OR et.has_consented__5 <> 1");
  }
}
