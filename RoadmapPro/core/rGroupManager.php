<?php
require_once ( __DIR__ . DIRECTORY_SEPARATOR . 'rProApi.php' );
require_once ( __DIR__ . DIRECTORY_SEPARATOR . 'rGroup.php' );

/**
 * the group manager gets data from multiple group profiles
 *
 * @author Stefan Schwarz
 */
class rGroupManager
{
   /**
    * returns all group ids
    *
    * @return array
    */
   public static function getRGroupIds ()
   {
      $mysqli = rProApi::initializeDbConnection ();

      $query = /** @lang sql */
         "SELECT id FROM mantis_plugin_RoadmapPro_profilegroup_table";

      $result = $mysqli->query ( $query );

      $groupIds = array ();
      if ( 0 != $result->num_rows )
      {
         while ( $row = $result->fetch_row ()[ 0 ] )
         {
            $groupIds[] = $row;
         }
      }

      $mysqli->close ();

      return $groupIds;
   }

   /**
    * iterates the given group ids and returns the assigned group objects
    *
    * @param $groupIds
    * @return array
    */
   public static function getRGroups ( $groupIds )
   {
      $groups = array ();
      foreach ( $groupIds as $groupId )
      {
         $profile = new rGroup( $groupId );
         array_push ( $groups, $profile );
      }

      return $groups;
   }
}