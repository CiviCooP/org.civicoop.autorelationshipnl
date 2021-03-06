<?php

/**
 * Collection of upgrade steps
 */
class CRM_Autorelationship_Upgrader extends CRM_Autorelationship_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed
   */
  public function install() {
    $this->executeSqlFile('sql/install.sql');
    
    $this->addRelationshipType('city_based', 'Op basis van woonplaats (A-B)', 'city_based', 'Op basis van woonplaats (B-A)', array(
      'is_reserved' => '1',
      'description' => 'Automatische relatie op basis van woonplaats',
    ));

		$this->executeCustomDataFile('xml/customfields.xml');
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled
   */
  public function uninstall() {
    $this->removeCustomGroup('automatic_relationship');
    $this->removeCustomGroup('autorelationship_city_based');
    $this->removeRelationshipType('city_based', 'city_based');
    $this->executeSqlFile('sql/uninstall.sql');
  }
  
  /**
   * Add an relationship type to CiviCRM
   * 
   * @param String $name_a_b
   * @param String $label_a_b
   * @param String $name_b_a
   * @param String $label_b_a
   * @param (optional) array $params additional parameters for the activity type (e.g. 'reserved' => 1)
   * @return type
   */
  protected function addRelationshipType($name_a_b, $label_a_b, $name_b_a, $label_b_a, $params = array()) {
    //try {      
      $checkParams['name_a_b'] = $name_a_b;
      $checkParams['name_b_a'] = $name_b_a;
      $checkResult = civicrm_api3('RelationshipType', 'get', $checkParams);
      if (isset($checkResult['id']) && $checkResult['id']) {
        //activity type exists, update this one
        $params['id'] = $checkResult['id'];
      } else {
         //if ID is set then unset the id parameter so that we create a new one
        if (isset($params['id'])) {
          unset($params['id']);
        }
      }
      $params['name_a_b'] = $name_a_b;
      $params['name_b_a'] = $name_b_a;
      $params['label_a_b'] = $label_a_b;
			$params['label_b_a'] = $label_b_a;
      
      $ids = array();
      if (isset($params['id'])) {
        $ids['relationshipType'] = CRM_Utils_Array::value('id', $params);
      }
      $relationType = CRM_Contact_BAO_RelationshipType::add($params, $ids);
      
      //civicrm_api3('RelationshipType', 'Create', $params);
      
    //} catch (Exception $ex) {
    //   return; 
   // }
  }
  
  public function removeRelationshipType($name_a_b, $name_b_a) {
    $checkParams['name_a_b'] = $name_a_b;
    $checkParams['name_b_a'] = $name_b_a;
    $checkResult = civicrm_api3('RelationshipType', 'get', $checkParams);
    if (isset($checkResult['id']) && $checkResult['id']) {
      $params['id'] = $checkResult['id'];
      civicrm_api3('RelationshipType', 'Delete', $params);
    }
  }
  
  protected function removeCustomGroup($group_name) {
    $gid = civicrm_api3('CustomGroup', 'getValue', array('return' => 'id', 'name' => $group_name));
    if ($gid) {
      $fields = civicrm_api3('CustomField', 'get', array('custom_group_id' => $gid));
      foreach($fields['values'] as $field) {
        civicrm_api3('CustomField', 'delete', array('id' => $field['id']));
      }
      civicrm_api3('CustomGroup', 'delete', array('id' => $gid));
    }
  }


  /**
   * Example: Run a simple query when a module is enabled
   *
  public function enable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a simple query when a module is disabled
   *
  public function disable() {
    CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  }

  /**
   * Example: Run a couple simple queries
   *
   * @return TRUE on success
   * @throws Exception
   */
  /*public function upgrade_1001() {
    $this->ctx->log->info('Applying update 1001');
    $this->executeSqlFile('sql/install.sql');
    return TRUE;
  }*/


  /**
   * Example: Run an external SQL script
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4201() {
    $this->ctx->log->info('Applying update 4201');
    // this path is relative to the extension base dir
    $this->executeSqlFile('sql/upgrade_4201.sql');
    return TRUE;
  } // */


  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4202() {
    $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

    $this->addTask(ts('Process first step'), 'processPart1', $arg1, $arg2);
    $this->addTask(ts('Process second step'), 'processPart2', $arg3, $arg4);
    $this->addTask(ts('Process second step'), 'processPart3', $arg5);
    return TRUE;
  }
  public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  public function processPart3($arg5) { sleep(10); return TRUE; }
  // */


  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws Exception
  public function upgrade_4203() {
    $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

    $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
    $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = ts('Upgrade Batch (%1 => %2)', array(
        1 => $startId,
        2 => $endId,
      ));
      $sql = '
        UPDATE civicrm_contribution SET foobar = whiz(wonky()+wanker)
        WHERE id BETWEEN %1 and %2
      ';
      $params = array(
        1 => array($startId, 'Integer'),
        2 => array($endId, 'Integer'),
      );
      $this->addTask($title, 'executeSql', $sql, $params);
    }
    return TRUE;
  } // */

}
