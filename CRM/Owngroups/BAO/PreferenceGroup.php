<?php
use CRM_Owngroups_ExtensionUtil as E;

class CRM_Owngroups_BAO_PreferenceGroup extends CRM_Owngroups_DAO_PreferenceGroup {

  /**
   * Create a new PreferenceGroup based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Owngroups_DAO_PreferenceGroup|NULL
   */
  public static function create($params) {
    $className = 'CRM_Owngroups_DAO_PreferenceGroup';
    $entityName = 'PreferenceGroup';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Get a PreferenceGroup based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Owngroups_DAO_PreferenceGroup|NULL
   */
  public function get($params) {
    $className = 'CRM_Owngroups_DAO_PreferenceGroup';
    $entityName = 'PreferenceGroup';
    $instance = new $className();
    $instance->copyValues($params);
    $instance->find(TRUE);
    $returnValues = [];
    if ($instance->N > 0) {
      $returnValues = [
        $instance->id => [
          "id" => $instance->id,
          "group_id" => $instance->group_id,
          "is_preference" => $instance->is_preference,
        ],
      ];
    }
    return $returnValues;
  }

}
