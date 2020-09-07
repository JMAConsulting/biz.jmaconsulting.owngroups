<?php
use CRM_Owngroups_ExtensionUtil as E;

/**
 * PreferenceGroup.GetPreference API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_preference_group_getpreference($params) {
  $returnValues = CRM_Owngroups_BAO_PreferenceGroup::get($params);
  return civicrm_api3_create_success($returnValues, $params, 'PreferenceGroup', 'GetPreference');
}
