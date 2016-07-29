<?php

require_once ( __DIR__ . '/../core/roadmap_pro_api.php' );
require_once ( __DIR__ . '/../core/roadmap_db.php' );
require_once ( __DIR__ . '/../core/roadmap_constant_api.php' );

$roadmapDb = new roadmap_db();
processPage ();

function processPage ()
{
   global $roadmapDb;
   $defaultProfileColor = 'FFFFFF';
   if ( isset( $_GET[ 'profile_id' ] ) )
   {
      $getProfileId = $_GET[ 'profile_id' ];
      $roadmapProfile = $roadmapDb->dbGetRoadmapProfile ( $getProfileId );
      $defaultProfileColor = $roadmapProfile[ 2 ];
   }

   html_page_top1 ( plugin_lang_get ( 'menu_title' ) );
   echo '<link rel="stylesheet" href="' . ROADMAPPRO_PLUGIN_URL . 'files/roadmappro.css.php?profile_color=' . $defaultProfileColor . '"/>' . "\n";
   echo '<script type="text/javascript" src="' . ROADMAPPRO_PLUGIN_URL . 'files/roadmappro.js"></script>';
   html_page_top2 ();
   if ( plugin_is_installed ( 'WhiteboardMenu' ) &&
      file_exists ( config_get_global ( 'plugin_path' ) . 'WhiteboardMenu' )
   )
   {
      require_once __DIR__ . '/../../WhiteboardMenu/core/whiteboard_print_api.php';
      whiteboard_print_api::printWhiteboardMenu ();
   }

   /** print profile menu bar */
   printProfileSwitcher ();

   if ( isset( $_GET[ 'profile_id' ] ) )
   {
      $getProfileId = $_GET[ 'profile_id' ];
      echo '<div align="center">';
      echo '<hr size="1" width="100%" />';
      echo '<div class="table">';
      processTable ( $getProfileId );
      echo '</div>';
      echo '</div>';
   }

   if ( true )
   {
//      print_successful_redirect( 'plugin.php?page=RoadmapPro/roadmap_page' );
   }

   html_page_bottom ();
}

function processTable ( $profileId )
{
   $getVersionId = $_GET[ 'version_id' ];
   $getProjectId = $_GET[ 'project_id' ];

   $projectIds = roadmap_pro_api::prepareProjectIds ();

   /** specific project selected */
   if ( $getProjectId != null )
   {
      $projectIds = array ();
      array_push ( $projectIds, $getProjectId );
   }

   /** iterate through projects */
   foreach ( $projectIds as $projectId )
   {
      $projectSeperator = false;
      $userAccessLevel = user_get_access_level ( auth_get_current_user_id (), $projectId );
      $userHasProjectLevel = access_has_project_level ( $userAccessLevel, $projectId );
      /** skip if user has no access to project */
      if ( $userHasProjectLevel == false )
      {
         continue;
      }

      $printedProjectTitle = false;
      $projectName = string_display ( project_get_name ( $projectId ) );
      $versions = array_reverse ( version_get_all_rows ( $projectId ) );

      /** specific version selected */
      if ( $getVersionId != null )
      {
         $version = array ();
         $version[ 'id' ] = $getVersionId;
         $version[ 'version' ] = version_get_field ( $getVersionId, 'version' );
         $version[ 'date_order' ] = version_get_field ( $getVersionId, 'date_order' );
         $version[ 'released' ] = version_get_field ( $getVersionId, 'released' );
         $version[ 'description' ] = version_get_field ( $getVersionId, 'description' );

         $versions = array ();
         array_push ( $versions, $version );
      }

      /** iterate through versions */
      $versionCount = count ( $versions );
      for ( $index = 0; $index < $versionCount; $index++ )
      {
         $version = $versions[ $index ];
         $versionId = $version[ 'id' ];
         $versionName = $version[ 'version' ];
         $versionDate = $version[ 'date_order' ];
         $versionReleased = $version[ 'released' ];
         $versionDescription = $version[ 'description' ];

         /** skip released versions */
         if ( $versionReleased == 1 )
         {
            continue;
         }

         $versionReleaseDate = string_display_line ( date ( config_get ( 'short_date_format' ), $versionDate ) );

         global $roadmapDb;
         $bugIds = $roadmapDb->dbGetBugIdsByProjectAndVersion ( $projectId, $versionName );
         $overallBugAmount = count ( $bugIds );

         if ( $overallBugAmount > 0 )
         {
            $useEta = roadmap_pro_api::checkEtaIsSet ( $bugIds );
            $doneEta = 0;
            $profileHashMap = array ();
            /** define and print project title */
            if ( $printedProjectTitle == false )
            {
               $profile = $roadmapDb->dbGetRoadmapProfile ( $profileId );
               $profileName = string_display ( $profile[ 1 ] );
               echo '<span class="pagetitle">';
               if ( $profileId == -1 )
               {
                  echo sprintf ( plugin_lang_get ( 'roadmap_page_version_title' ), $projectName, plugin_lang_get ( 'roadmap_page_whole_progress' ) );
               }
               else
               {
                  echo sprintf ( plugin_lang_get ( 'roadmap_page_version_title' ), $projectName, $profileName );
               }
               echo '</span>';
               $printedProjectTitle = true;
            }
            /** define and print release title */
            $releaseTitle = '<a href="' . plugin_page ( 'roadmap_page' )
               . '&amp;profile_id=' . $profileId . '&amp;project_id=' . $projectId . '">'
               . string_display_line ( $projectName ) . '</a>&nbsp;-'
               . '&nbsp;<a href="' . plugin_page ( 'roadmap_page' )
               . '&amp;profile_id=' . $profileId . '&amp;version_id=' . $versionId . '">'
               . string_display_line ( $versionName ) . '</a>';

            $releaseTitleString = $releaseTitle . '&nbsp;(' . lang_get ( 'scheduled_release' ) . '&nbsp;'
               . $versionReleaseDate . ')&nbsp;&nbsp;[&nbsp;<a href="view_all_set.php?type=1&amp;temporary=y&amp;'
               . FILTER_PROPERTY_PROJECT_ID . '=' . $projectId . '&amp;'
               . filter_encode_field_and_value ( FILTER_PROPERTY_TARGET_VERSION, $versionName ) . '">'
               . lang_get ( 'view_bugs_link' ) . '</a>&nbsp;]';

            printWrapperInHTML ( $releaseTitleString );
            /** print version description */
            printWrapperInHTML ( $versionDescription );


            if ( $profileId == -1 )
            {
               $scaledData = calcScaledData ( $bugIds, $useEta, $overallBugAmount );
               $profileHashMap = $scaledData[ 0 ];
               $progressInPercent = $scaledData[ 1 ];
            }
            else
            {
               $singleData = calcSingleData ( $bugIds, $profileId, $useEta, $overallBugAmount );
               $doneEta = $singleData[ 0 ];
               $progressInPercent = $singleData[ 1 ];
            }

            /** print version progress bar */
            printVersionProgress ( $bugIds, $profileId, $progressInPercent, $profileHashMap, $useEta, $doneEta );
            /** print bug list */
            printBugList ( $bugIds, $profileId );
            /** print text progress */
            if ( $profileId >= 0 )
            {
               $doneBugAmount = roadmap_pro_api::getDoneBugAmount ( $bugIds, $profileId );
               printVersionProgressAsText ( $overallBugAmount, $doneBugAmount, $progressInPercent, $useEta );
            }
            /** print spacer */
            echo '<div class="spacer"></div>';
            $projectSeperator = true;
         }
      }
      /** print separator */
      if ( $projectSeperator == true )
      {
         echo '<hr class="project-separator" />';
      }
   }
}

function calcScaledData ( $bugIds, $useEta, $overallBugAmount )
{
   global $roadmapDb;
   $profileProgressValueArray = array ();
   $roadmapProfiles = $roadmapDb->dbGetRoadmapProfiles ();
   $profileCount = count ( $roadmapProfiles );
   $sumProgressDoneBugAmount = 0;
   $sumProgressDoneBugPercent = 0;
   $sumProgressDoneEta = 0;
   $fullEta = ( roadmap_pro_api::getFullEta ( $bugIds ) ) * $profileCount;
   for ( $index = 0; $index < $profileCount; $index++ )
   {
      $roadmapProfile = $roadmapProfiles[ $index ];
      $tProfileId = $roadmapProfile[ 0 ];
      $tDoneBugAmount = roadmap_pro_api::getDoneBugAmount ( $bugIds, $tProfileId );
      $sumProgressDoneBugAmount += $tDoneBugAmount;
      if ( $useEta )
      {
         /** calculate eta for profile */
         $doneEta = 0;
         $doneBugIds = roadmap_pro_api::getDoneBugIds ( $bugIds, $tProfileId );
         foreach ( $doneBugIds as $doneBugId )
         {
            $doneEta += roadmap_pro_api::getSingleEta ( $doneBugId );
         }
         $doneEtaPercent = round ( ( ( $doneEta / $fullEta ) * 100 ), 1 );
         $sumProgressDoneEta += $doneEta;

         $profileHash = $tProfileId . ';' . $doneEtaPercent;
      }
      else
      {
         $tVersionProgress = ( $tDoneBugAmount / $overallBugAmount );
         $progessDonePercent = round ( ( $tVersionProgress * 100 / $profileCount ), 1 );
         if ( round ( ( $sumProgressDoneBugPercent + $progessDonePercent ), 1 ) == 99.9 )
         {
            $progessDonePercent = 100 - $sumProgressDoneBugPercent;
         }
         $sumProgressDoneBugPercent += $progessDonePercent;

         $profileHash = $tProfileId . ';' . $progessDonePercent;
      }

      array_push ( $profileProgressValueArray, $profileHash );
   }

   /** whole progress of the version */
   if ( $useEta )
   {
      $wholeProgress = ( $sumProgressDoneEta / $fullEta );
   }
   else
   {
      $wholeProgress = ( ( $sumProgressDoneBugAmount / $profileCount ) / $overallBugAmount );
   }
   $progressPercent = round ( ( $wholeProgress * 100 ), 1 );

   $result = [ 0 => $profileProgressValueArray, 1 => $progressPercent ];

   return $result;
}

function calcSingleData ( $bugIds, $profileId, $useEta, $overallBugAmount )
{
   $fullEta = ( roadmap_pro_api::getFullEta ( $bugIds ) );
   $doneEta = 0;
   if ( $useEta )
   {
      $doneBugIds = roadmap_pro_api::getDoneBugIds ( $bugIds, $profileId );
      foreach ( $doneBugIds as $doneBugId )
      {
         $doneEta += roadmap_pro_api::getSingleEta ( $doneBugId );
      }

      $progressPercent = 0;
      if ( $fullEta > 0 )
      {
         $progressPercent = round ( ( ( $doneEta / $fullEta ) * 100 ), 1 );
      }
   }
   else
   {
      $doneBugAmount = roadmap_pro_api::getDoneBugAmount ( $bugIds, $profileId );
      $progress = ( $doneBugAmount / $overallBugAmount );
      $progressPercent = round ( ( $progress * 100 ), 1 );
   }

   $result = [ 0 => $doneEta, 1 => $progressPercent ];

   return $result;
}

function printWrapperInHTML ( $content )
{
   echo '<div class="tr">' . PHP_EOL;
   echo '<div class="td">';
   echo $content;
   echo '</div>' . PHP_EOL;
   echo '</div>' . PHP_EOL;
}

function printProfileSwitcher ()
{
   global $roadmapDb;
   $roadmapProfiles = $roadmapDb->dbGetRoadmapProfiles ();

   echo '<div class="table_center">' . PHP_EOL;
   echo '<div class="tr">' . PHP_EOL;
   /** print roadmap_profile-links */
   foreach ( $roadmapProfiles as $roadmapProfile )
   {
      $profileId = $roadmapProfile[ 0 ];
      $profileName = $roadmapProfile[ 1 ];

      echo '<div class="td">';
      printLinkStringWithGetParameters ( string_display ( $profileName ), $profileId );
      echo '</div>' . PHP_EOL;
   }
   /** show whole progress, when there is more then one different profile */
   if ( count ( $roadmapProfiles ) > 1 )
   {
      echo '<div class="td">';
      printLinkStringWithGetParameters ( plugin_lang_get ( 'roadmap_page_whole_progress' ) );
      echo '</div>' . PHP_EOL;
   }

   echo '</div>' . PHP_EOL;
   echo '</div>' . PHP_EOL;
}

function printLinkStringWithGetParameters ( $linkDescription, $profileId = null )
{
   $getVersionId = $_GET[ 'version_id' ];
   $getProjectId = $_GET[ 'project_id' ];
   $currentProjectId = helper_get_current_project ();

   echo '[ <a href="' . plugin_page ( 'roadmap_page' ) . '&amp;profile_id=';
   /** check specific profile id is given */
   if ( is_null ( $profileId ) == false )
   {
      echo $profileId;
   }
   else
   {
      echo '-1';
   }
   /** check version id is get parameter */
   if ( $getVersionId != null )
   {
      echo '&amp;version_id=' . $getVersionId;
   }
   /** check project id is get parameter */
   if ( $getProjectId != null )
   {
      echo '&amp;project_id=' . $getProjectId;
   }
   echo '&amp;sproject_id=' . $currentProjectId;
   echo '">';
   echo $linkDescription;
   echo '</a> ]';
}

function printVersionProgress ( $bugIds, $profileId, $progressPercent, $profileHashMap, $useEta, $doneEta )
{
   echo '<div class="tr">' . PHP_EOL;
   echo '<div class="td">';
   if ( $useEta && config_get ( 'enable_eta' ) )
   {
      if ( $profileId == -1 )
      {
         printScaledProgressbar ( $profileHashMap, $progressPercent, $bugIds, true );
      }
      else
      {
         $fullEta = roadmap_pro_api::getFullEta ( $bugIds );
         /** TODO dynamic eta unit */
         $progressString = $doneEta . '&nbsp;' . lang_get ( 'from' ) . '&nbsp;' . $fullEta . '&nbsp;' . plugin_lang_get ( 'config_page_eta_unit' );
         printSingleProgressbar ( $progressPercent, $progressString );
      }
   }
   else
   {
      if ( $profileId == -1 )
      {
         printScaledProgressbar ( $profileHashMap, $progressPercent, $bugIds );
      }
      else
      {
         $bugCount = count ( $bugIds );
         $progressString = $progressPercent . '%&nbsp;' . lang_get ( 'from' ) . '&nbsp;' . $bugCount . '&nbsp;' . lang_get ( 'issues' );
         printSingleProgressbar ( $progressPercent, $progressString );
      }
   }
   echo '</div>' . PHP_EOL;
   echo '</div>' . PHP_EOL;
}

function printSingleProgressbar ( $progress, $progressString )
{
   echo '<div class="progress9001">';
   echo '<span class="bar single" style="width: ' . $progress . '%; white-space: nowrap;">' . $progressString . '</span>';
   echo '</div>';
}

function printScaledProgressbar ( $profileHashMap, $progressPercent, $bugIds, $useEta = false )
{
   global $roadmapDb;
   $fullEta = roadmap_pro_api::getFullEta ( $bugIds );
   $doneEta = 0;
   echo '<div class="progress9001">';
   if ( empty( $profileHashMap ) == false )
   {
      $profileHashCount = count ( $profileHashMap );
      for ( $index = 0; $index < $profileHashCount; $index++ )
      {
         /** extract profile data */
         $profileHash = explode ( ';', $profileHashMap[ $index ] );
         $hashProfileId = $profileHash[ 0 ];
         $hashProgress = round ( $profileHash[ 1 ], 1 );

         /** get profile color */
         $dbProfileRow = $roadmapDb->dbGetRoadmapProfile ( $hashProfileId );
         $profileColor = '#' . $dbProfileRow[ 2 ];

         $tempEta = round ( ( ( $hashProgress / 100 ) * $fullEta ), 1 );

         /** first bar */
         if ( $index == 0 )
         {
            echo '<div class="bar left" style="width: ' . $hashProgress . '%; background: ' . $profileColor . ';">';
            if ( $useEta == true )
            {

               echo $tempEta . '&nbsp;' . plugin_lang_get ( 'config_page_eta_unit' );
            }
            else
            {
               echo $hashProgress . '%';
            }
            echo '</div><!--';
         }
         /** n - 2 (first, last) following */
         elseif ( $index == ( $profileHashCount - 1 ) )
         {
            echo '--><div class="bar right" style="width: ' . $hashProgress . '%; background: ' . $profileColor . ';">';
            if ( $useEta == true )
            {
               echo $tempEta . '&nbsp;' . plugin_lang_get ( 'config_page_eta_unit' );
            }
            else
            {
               echo $hashProgress . '%';
            }
            echo '</div>';
         }
         /** last bar */
         else
         {
            echo '--><div class="bar middle" style="width: ' . $hashProgress . '%; background: ' . $profileColor . ';">';
            if ( $useEta == true )
            {
               echo $tempEta . '&nbsp;' . plugin_lang_get ( 'config_page_eta_unit' );
            }
            else
            {
               echo $hashProgress . '%';
            }
            echo '</div><!--';
         }

         $doneEta += $tempEta;
      }
   }

   echo '</div>';
   echo '<div class="progress-suffix">';
   if ( $useEta == true )
   {
      /** TODO dynamic eta unit */
      echo '&nbsp;(' . $doneEta . '&nbsp;' . lang_get ( 'from' ) . '&nbsp;' . $fullEta . '&nbsp;' . plugin_lang_get ( 'config_page_eta_unit' );
   }
   else
   {
      $bugCount = count ( $bugIds );
      echo '&nbsp;(' . $progressPercent . '%&nbsp;' . lang_get ( 'from' ) . '&nbsp;' . $bugCount . '&nbsp;' . lang_get ( 'issues' );
   }
   echo ')';
   echo '</div>';
}

function printBugList ( $bugIds, $profileId )
{
   $bugIdsDetailed = roadmap_pro_api::calculateBugRelationships ( $bugIds );
   foreach ( $bugIdsDetailed as $bug )
   {
      $bugId = $bug[ 'id' ];
      $userId = bug_get_field ( $bugId, 'handler_id' );
      $bugEta = bug_get_field ( $bugId, 'eta' );
      $bugBlockingIds = $bug[ 'blocking_ids' ];
      $bugBlockedIds = $bug[ 'blocked_ids' ];
      $bugIsDone = roadmap_pro_api::checkIssueIsDoneById ( $bugId, $profileId );
      $hasBlocked = ( empty ( $bugBlockedIds ) == false );
      $hasBlocking = ( empty ( $bugBlockingIds ) == false );
      $useEta = ( $bugEta > 10 ) && config_get ( 'enable_eta' );

      $htmlOutput = '<div class="tr">';
      /** line through, if bug is done */
      if ( $bugIsDone )
      {
         $htmlOutput .= '<div class="td done">';
      }
      else
      {
         $htmlOutput .= '<div class="td">';
      }
      $htmlOutput .= string_get_bug_view_link ( $bugId ) . '&nbsp;';
      /** symbol when eta is set */
      if ( $useEta )
      {
         $htmlOutput .= '<img class="symbol" src="' . ROADMAPPRO_PLUGIN_URL . 'files/clock.png' . '" alt="clock" />&nbsp;';
      }
      /** symbol when bug is blocking */
      if ( $hasBlocked )
      {
         $blockedIdString = roadmap_pro_api::generateBlockIdString ( $bugBlockedIds, true );
         $htmlOutput .= '<img class="symbol" src="' . ROADMAPPRO_PLUGIN_URL . 'files/sign_warning.png' . '" alt="' . $blockedIdString .
            '" title="' . $blockedIdString . '" />&nbsp;';
      }
      /** symbol when bug is blocked by */
      if ( $hasBlocking )
      {
         $blockingIdString = roadmap_pro_api::generateBlockIdString ( $bugBlockingIds, false );
         $htmlOutput .= '<img class="symbol" src="' . ROADMAPPRO_PLUGIN_URL . 'files/sign_stop.png' . '" alt="' . $blockingIdString .
            '" title="' . $blockingIdString . '" />&nbsp;';
      }
      $htmlOutput .= string_display ( bug_get_field ( $bugId, 'summary' ) );
      if ( $userId > 0 )
      {
         $htmlOutput .= '&nbsp;(<a href="' . config_get ( 'path' ) . '/view_user_page.php?id=' . $userId . '">' .
            user_get_name ( $userId ) . '</a>' . ')';
      }

      $htmlOutput .= '&nbsp;-&nbsp;'
         . string_display_line ( get_enum_element ( 'status', bug_get_field ( $bugId, 'status' ) ) ) . '.';
      $htmlOutput .= '</div>' . PHP_EOL;
      $htmlOutput .= '</div>' . PHP_EOL;

      echo $htmlOutput;
   }
}

function printVersionProgressAsText ( $overallBugAmount, $doneBugAmount, $progressPercent, $useEta )
{
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