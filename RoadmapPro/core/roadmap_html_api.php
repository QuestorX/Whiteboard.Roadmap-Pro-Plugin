<?php
require_once ( __DIR__ . '/roadmap_db.php' );
require_once ( __DIR__ . '/../core/roadmap_pro_api.php' );

class roadmap_html_api
{
   /**
    * Prints a radio button element
    *
    * @param $colspan
    * @param $name
    */
   public static function htmlPluginConfigRadio ( $name, $colspan = 1 )
   {
      echo '<td width="100px" colspan="' . $colspan . '">';
      echo '<label>';
      echo '<input type="radio" name="' . $name . '" value="1"';
      echo ( ON == plugin_config_get ( $name ) ) ? 'checked="checked"' : '';
      echo '/>' . lang_get ( 'yes' );
      echo '</label>';
      echo '<label>';
      echo '<input type="radio" name="' . $name . '" value="0"';
      echo ( OFF == plugin_config_get ( $name ) ) ? 'checked="checked"' : '';
      echo '/>' . lang_get ( 'no' );
      echo '</label>';
      echo '</td>';
   }

   /**
    * Prints a category column in the plugin config area
    *
    * @param $class
    * @param $colspan
    * @param $lang_string
    * @return string
    */
   public static function htmlPluginConfigOutputCol ( $class, $lang_string, $colspan = 1 )
   {
      echo '<td class="' . $class . '" colspan="' . $colspan . '">' . plugin_lang_get ( $lang_string ) . '</td>';
   }

   /**
    * prints opening table tag
    *
    * @param $class
    * @param null $id
    */
   public static function htmlPluginConfigOpenTable ( $class, $id = null )
   {
      $htmlString = '<table align="center" cellspacing="1" class="' . $class . '"';
      if ( $id != null )
      {
         $htmlString .= ' id="' . $id . '"';
      }
      $htmlString .= '>';
      echo $htmlString;
   }

   /**
    * prints closing table tag
    */
   public static function htmlPluginConfigCloseTable ()
   {
      echo '</table>';
   }

   private static function htmlRoadmapPageProgress ( $useEta, $tempEta, $hashProgress )
   {
      if ( $useEta == true )
      {
         $calculatedEta = roadmap_pro_api::calculateEtaUnit ( $tempEta );
         echo $calculatedEta[ 0 ] . '&nbsp;' . $calculatedEta[ 1 ];
      }
      else
      {
         echo round ( $hashProgress, 1 ) . '%';
      }
   }

   private static function printSingleProgressbar ( $progress, $progressString )
   {
      echo '<div class="progress9001">';
      echo '<span class="bar single" style="width: ' . $progress . '%; white-space: nowrap;">' . $progressString . '</span>';
      echo '</div>';
   }

   private static function printScaledProgressbar ( roadmap $roadmap )
   {
      $roadmapDb = new roadmap_db();

      $useEta = $roadmap->getEtaIsSet ();
      $profileHashMap = $roadmap->getProfileHashArray ();
      $fullEta = $roadmap->getFullEta ();
      $doneEta = 0;
      echo '<div class="progress9001">';
      if ( empty( $profileHashMap ) == false )
      {
         $profileHashCount = count ( $profileHashMap );
         for ( $index = 0; $index < $profileHashCount; $index++ )
         {
            # extract profile data
            $profileHash = explode ( ';', $profileHashMap[ $index ] );
            $hashProfileId = $profileHash[ 0 ];
            $hashProgress = $profileHash[ 1 ];

            # get profile color
            $dbProfileRow = $roadmapDb->dbGetRoadmapProfile ( $hashProfileId );
            $profileColor = '#' . $dbProfileRow[ 2 ];

            $tempEta = round ( ( ( $hashProgress / 100 ) * $fullEta ), 1 );

            # first bar
            if ( $index == 0 )
            {
               echo '<div class="bar left" style="width: ' . $hashProgress . '%; background: ' . $profileColor . ';">';
               self::htmlRoadmapPageProgress ( $useEta, $tempEta, $hashProgress );
               echo '</div><!--';
            }
            # n - 2 (first, last) following
            elseif ( $index == ( $profileHashCount - 1 ) )
            {
               echo '--><div class="bar right" style="width: ' . $hashProgress . '%; background: ' . $profileColor . ';">';
               self::htmlRoadmapPageProgress ( $useEta, $tempEta, $hashProgress );
               echo '</div>';
            }
            # last bar
            else
            {
               echo '--><div class="bar middle" style="width: ' . $hashProgress . '%; background: ' . $profileColor . ';">';
               self::htmlRoadmapPageProgress ( $useEta, $tempEta, $hashProgress );
               echo '</div><!--';
            }

            $doneEta += $tempEta;
         }
      }

      echo '</div>';
      echo '<div class="progress-suffix">';
      if ( $useEta == true )
      {
         $calculatedDoneEta = roadmap_pro_api::calculateEtaUnit ( $doneEta );
         $calculatedFullEta = roadmap_pro_api::calculateEtaUnit ( $fullEta );
         echo '&nbsp;(' . $calculatedDoneEta[ 0 ] . '&nbsp;' . $calculatedFullEta[ 1 ] . '&nbsp;' . lang_get ( 'from' ) . '&nbsp;' . $calculatedFullEta[ 0 ] . '&nbsp;' . $calculatedFullEta[ 1 ];
      }
      else
      {
         $bugCount = count ( $roadmap->getBugIds () );
         echo '&nbsp;(' . round ( $roadmap->getSclaedProgressPercent (), 1 ) . '%&nbsp;' . lang_get ( 'from' ) . '&nbsp;' . $bugCount . '&nbsp;' . lang_get ( 'issues' );
      }
      echo ')';
      echo '</div>';
   }

   public static function printVersionProgressAsText ( roadmap $roadmap )
   {
      $overallBugAmount = count ( $roadmap->getBugIds () );
      $doneBugAmount = count ( $roadmap->getDoneBugIds () );
      $progressPercent = $roadmap->getSingleProgressPercent ();
      $useEta = $roadmap->getEtaIsSet ();
      echo '<div class="tr">' . PHP_EOL;
      echo '<div class="td">';
      if ( $useEta && config_get ( 'enable_eta' ) )
      {
         echo sprintf ( plugin_lang_get ( 'roadmap_page_resolved_time' ), $doneBugAmount, $overallBugAmount );
      }
      else
      {
         echo sprintf ( lang_get ( 'resolved_progress' ), $doneBugAmount, $overallBugAmount, $progressPercent );
      }
      echo '</div>' . PHP_EOL;
      echo '</div>' . PHP_EOL;
   }

   public static function printWrapperInHTML ( $content )
   {
      echo '<div class="tr">' . PHP_EOL;
      echo '<div class="td">';
      echo $content;
      echo '</div>' . PHP_EOL;
      echo '</div>' . PHP_EOL;
   }

   public static function htmlPluginDirectory ()
   {
      echo '<div class="table" id="directory">';
      echo '<span class="pagetitle">' . plugin_lang_get ( 'roadmap_page_directory' ) . '</span>';
      echo '</div>';
      self::htmlPluginSeparator ();
      self::htmlPluginSpacer ();
   }

   public static function printProfileSwitcher ()
   {
      $roadmapDb = new roadmap_db();
      $roadmapProfiles = $roadmapDb->dbGetRoadmapProfiles ();

      echo '<div class="table_center">' . PHP_EOL;
      echo '<div class="tr">' . PHP_EOL;
      # print roadmap_profile-links
      foreach ( $roadmapProfiles as $roadmapProfile )
      {
         $profileId = $roadmapProfile[ 0 ];
         $profileName = $roadmapProfile[ 1 ];

         echo '<div class="td">';
         self::printLinkStringWithGetParameters ( string_display ( $profileName ), $profileId );
         echo '</div>' . PHP_EOL;
      }
      # show whole progress, when there is more then one different profile
      if ( count ( $roadmapProfiles ) > 1 )
      {
         echo '<div class="td">';
         self::printLinkStringWithGetParameters ( plugin_lang_get ( 'roadmap_page_whole_progress' ) );
         echo '</div>' . PHP_EOL;
      }

      echo '</div>' . PHP_EOL;
      echo '</div>' . PHP_EOL;
   }

   private static function printLinkStringWithGetParameters ( $linkDescription, $profileId = null )
   {
      $getVersionId = $_GET[ 'version_id' ];
      $getProjectId = $_GET[ 'project_id' ];
      $currentProjectId = helper_get_current_project ();

      echo '[ <a href="' . plugin_page ( 'roadmap_page' ) . '&amp;profile_id=';
      # check specific profile id is given
      if ( $profileId != null )
      {
         echo $profileId;
      }
      else
      {
         echo '-1';
      }
      # check version id is get parameter
      if ( $getVersionId != null )
      {
         echo '&amp;version_id=' . $getVersionId;
      }
      # check project id is get parameter
      if ( $getProjectId != null )
      {
         echo '&amp;project_id=' . $getProjectId;
      }
      echo '&amp;sproject_id=' . $currentProjectId;
      echo '">';
      echo $linkDescription;
      echo '</a> ]';
   }

   public static function printVersionProgress ( roadmap $roadmap )
   {
      echo '<div class="tr">' . PHP_EOL;
      echo '<div class="td">';
      $profileId = $roadmap->getProfileId ();
      if ( $profileId == -1 )
      {
         self::printScaledProgressbar ( $roadmap );
      }
      else
      {
         $useEta = $roadmap->getEtaIsSet ();
         $doneEta = $roadmap->getDoneEta ();
         $fullEta = $roadmap->getFullEta ();
         $progressPercent = $roadmap->getSingleProgressPercent ();
         if ( $useEta && config_get ( 'enable_eta' ) )
         {
            $calculatedDoneEta = roadmap_pro_api::calculateEtaUnit ( $doneEta );
            $calculatedFullEta = roadmap_pro_api::calculateEtaUnit ( $fullEta );
            $progressString = $calculatedDoneEta[ 0 ] . '&nbsp;' . $calculatedDoneEta[ 1 ] .
               '&nbsp;' . lang_get ( 'from' ) . '&nbsp;' . $calculatedFullEta[ 0 ] . '&nbsp;' . $calculatedFullEta[ 1 ];
            self::printSingleProgressbar ( $progressPercent, $progressString );
         }
         else
         {
            $bugIds = $roadmap->getBugIds ();
            $bugCount = count ( $bugIds );
            $progressString = $progressPercent . '%&nbsp;' . lang_get ( 'from' ) . '&nbsp;' . $bugCount . '&nbsp;' . lang_get ( 'issues' );
            self::printSingleProgressbar ( $progressPercent, $progressString );
         }
      }

      echo '</div>' . PHP_EOL;
      echo '</div>' . PHP_EOL;
   }

   public static function printBugList ( $bugIds, $doneBugs = false )
   {
      foreach ( $bugIds as $bugId )
      {
         $bug = bug_get ( $bugId );
         $userId = $bug->handler_id;
         echo '<div class="tr">';
         # line through, if bug is done
         self::htmlPluginBugCol ( $doneBugs );
         # bug id
         echo string_get_bug_view_link ( $bugId ) . '&nbsp;';
         # bug category
         echo string_display_line ( category_full_name ( $bug->category_id ) );
         # bug symbols
         roadmap_pro_api::calcBugSmybols ( $bugId );
         # bug summary
         echo string_display_line ( $bug->summary );
         # bug assigned user
         if ( $userId > 0 )
         {
            echo '&nbsp;(<a href="' . config_get ( 'path' ) . '/view_user_page.php?id=' . $userId . '">' .
               user_get_name ( $userId ) . '</a>' . ')';
         }
         # bug status
         echo '&nbsp;-&nbsp;' . string_display_line ( get_enum_element ( 'status', $bug->status ) ) . '.';
         echo '</div>' . PHP_EOL . '</div>' . PHP_EOL;
      }
   }


   public static function htmlPluginBugCol ( $bugIsDone )
   {
      if ( $bugIsDone )
      {
         echo '<div class="td done">';
      }
      else
      {
         echo '<div class="td">';
      }
   }

   public static function htmlPluginProjectTitle ( $profileId, $projectId )
   {
      $roadmapDb = new roadmap_db();
      $profile = $roadmapDb->dbGetRoadmapProfile ( $profileId );
      $profileName = string_display ( $profile[ 1 ] );
      $projectName = string_display ( project_get_name ( $projectId ) );

      echo '<span class="pagetitle" id="' . $projectId . $projectName . '">';
      if ( $profileId == -1 )
      {
         echo sprintf ( plugin_lang_get ( 'roadmap_page_version_title' ), $projectName, plugin_lang_get ( 'roadmap_page_whole_progress' ) );
      }
      else
      {
         echo sprintf ( plugin_lang_get ( 'roadmap_page_version_title' ), $projectName, $profileName );
      }
      echo '</span>';
   }

   public static function htmlPluginSpacer ()
   {
      echo '<div class="spacer"></div>';
   }

   public static function htmlPluginSeparator ()
   {
      echo '<hr class="project-separator" />';
   }

   public static function htmlPluginTriggerWhiteboardMenu ()
   {
      if ( plugin_is_installed ( 'WhiteboardMenu' ) &&
         file_exists ( config_get_global ( 'plugin_path' ) . 'WhiteboardMenu' )
      )
      {
         require_once __DIR__ . '/../../WhiteboardMenu/core/whiteboard_print_api.php';
         whiteboard_print_api::printWhiteboardMenu ();
      }
   }

   public static function htmlPluginAddDirectoryVersionEntry ( $projectName, $versionName )
   {
      echo '<script type="text/javascript">';
      echo 'addVersionEntryToDirectory (\'' . $projectName . '\',\'' . $versionName . '\')';
      echo '</script>';
   }

   public static function htmlPluginAddDirectoryProjectEntry ( $projectId )
   {
      $projectName = project_get_name ( $projectId );
      echo '<script type="text/javascript">';
      echo 'addProjectEntryToDirectory (\'directory\',\'' . $projectId . '\',\'' . $projectName . '\')';
      echo '</script>';
   }
}