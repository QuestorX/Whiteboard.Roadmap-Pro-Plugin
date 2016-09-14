<?php
require_once ( __DIR__ . DIRECTORY_SEPARATOR . 'rProfileManager.php' );
require_once ( __DIR__ . DIRECTORY_SEPARATOR . 'rProfile.php' );
require_once ( __DIR__ . DIRECTORY_SEPARATOR . 'rEta.php' );

/**
 * roadmap class that represents a roadmap
 *
 * @author Stefan Schwarz
 */
class roadmap
{
   /**
    * @var integer
    */
   private $projectId;
   /**
    * @var integer
    */
   private $versionId;
   /**
    * @var array
    */
   private $bugIds;
   /**
    * @var integer
    */
   private $profileId;
   /**
    * @var integer
    */
   private $groupId;
   /**
    * @var integer
    */
   private $doneEta;
   /**
    * @var float
    */
   private $progressPercent;
   /**
    * @var array
    */
   private $profileHashArray;
   /**
    * @var boolean
    */
   private $etaIsSet;
   /**
    * @var integer
    */
   private $singleEta;
   /**
    * @var integer
    */
   private $fullEta;
   /**
    * @var array
    */
   private $doingBugIds;
   /**
    * @var array
    */
   private $doneBugIds;
   /**
    * @var boolean
    */
   private $issueIsDone;
   /**
    * @var integer
    */
   private $expectedFinishedDate;
   /**
    * @var integer
    */
   private $etaTaggedBugCount;
   /**
    * @var integer
    */
   private $etaNotTaggedBugCount;

   /**
    * roadmap constructor.
    * @param $bugIds
    * @param $profileId
    * @param $groupId
    * @param $projectId
    * @param $versionId
    */
   function __construct ( $bugIds, $profileId, $groupId, $projectId, $versionId )
   {
      $this->bugIds = $bugIds;
      $this->profileId = $profileId;
      $this->groupId = $groupId;
      $this->projectId = $projectId;
      $this->versionId = $versionId;
      $this->doneBugIds = array ();
      $this->doingBugIds = array ();
      $this->profileHashArray = array ();
      $this->etaTaggedBugCount = 0;
      $this->etaNotTaggedBugCount = 0;
      $this->countEtaTaggedBugs ();
   }

   /**
    * @return int
    */
   public function getProjectId ()
   {
      return $this->projectId;
   }

   /**
    * @return int
    */
   public function getGroupId ()
   {
      return $this->groupId;
   }

   /**
    * @return bool
    */
   public function getEtaIsSet ()
   {
      $this->checkEtaIsSet ();
      return $this->etaIsSet;
   }

   /**
    * @param $bugId
    * @return int
    */
   public function getSingleEta ( $bugId )
   {
      $this->calcSingleEta ( $bugId );
      return $this->singleEta;
   }

   /**
    * @return int
    */
   public function getFullEta ()
   {
      $this->calcFullEta ();
      return $this->fullEta;
   }

   /**
    * @return array
    */
   public function getDoneBugIds ()
   {
      $this->calcDoneBugIds ();
      return $this->doneBugIds;
   }

   /**
    * @return array
    */
   public function getDoingBugIds ()
   {
      $this->calcDoingBugIds ();
      return $this->doingBugIds;
   }

   /**
    * @return int
    */
   public function getDoneEta ()
   {
      $this->calcDoneEta ();
      return $this->doneEta;
   }

   /**
    * @param int $doneEta
    */
   public function setDoneEta ( $doneEta )
   {
      $this->doneEta = $doneEta;
   }

   /**
    * @return float
    */
   public function getSingleProgressPercent ()
   {
      $this->calcSingleProgressPercent ();
      return $this->progressPercent;
   }

   /**
    * @param $bugId
    * @return bool
    */
   public function getIssueIsDone ( $bugId )
   {
      $this->checkIssueIsDoneById ( $bugId );
      return $this->issueIsDone;
   }

   /**
    * @return int
    */
   public function getProfileId ()
   {
      return $this->profileId;
   }

   /**
    * @return array
    */
   public function getBugIds ()
   {
      return $this->bugIds;
   }

   /**
    * @param $profileId
    */
   public function setProfileId ( $profileId )
   {
      $this->profileId = $profileId;
   }

   /**
    * new initialization of done bug ids
    */
   private function resetDoneBugIds ()
   {
      $this->doneBugIds = array ();
   }

   /**
    * @param $versionId
    */
   public function setVersionId ( $versionId )
   {
      $this->versionId = $versionId;
   }

   /**
    * @return int
    */
   public function getVersionId ()
   {
      return $this->versionId;
   }

   /**
    * @return array
    */
   public function getProfileHashArray ()
   {
      $this->calcScaledData ();
      return $this->profileHashArray;
   }

   /**
    * @return int
    */
   public function getEtaTaggedBugCount ()
   {
      return $this->etaTaggedBugCount;
   }

   /**
    * @return int
    */
   public function getEtaNotTaggedBugCount ()
   {
      return $this->etaNotTaggedBugCount;
   }

   /**
    * @return string
    */
   public function getTextProgressMain ()
   {
      if ( $this->etaIsSet )
      {
         return $this->generateEtaTextProgressMain ();
      }
      else
      {
         return $this->generatePercentTextProgressMain ();
      }
   }

   /**
    * @return string
    */
   public function getTextProgressDir ()
   {
      if ( $this->etaIsSet )
      {
         return $this->generateEtaTextProgressDir ();
      }
      else
      {
         return $this->generatePercentTextProgressDir ();
      }
   }

   /**
    * @return string
    */
   public function getExpectedFinishedDateString ()
   {
      if ( $this->etaIsSet || $this->etaTaggedBugCount > 0 )
      {
         $this->calcExpectedFinishedDate ();
         $dateFinishedExpectedFormat = string_display_line ( date ( config_get ( 'short_date_format' ), $this->expectedFinishedDate ) );
         return ',&nbsp;' . plugin_lang_get ( 'roadmap_page_release_date_expected' ) . ':&nbsp;' . $dateFinishedExpectedFormat . '*';
      }
      else
      {
         return '';
      }
   }

   public function getActualDesiredDeviation ( $versonDesiredDate )
   {
      if ( $this->etaIsSet )
      {
         $this->calcExpectedFinishedDate ();
         return $this->calcDifferenceActualDesiredFinishedDate ( $versonDesiredDate );
      }
      else
      {
         return '';
      }
   }

   /**
    * returns true if every item of bug id array has set eta value
    */
   private function checkEtaIsSet ()
   {
      $this->etaIsSet = true;
      if ( !config_get ( 'enable_eta' ) )
      {
         $this->etaIsSet = false;
      }
      else
      {
         foreach ( $this->bugIds as $bugId )
         {
            $bugEtaValue = bug_get_field ( $bugId, 'eta' );
            if ( ( $bugEtaValue == null ) || ( $bugEtaValue == ETA_NONE ) )
            {
               $this->etaIsSet = false;
            }
         }
      }
   }

   /**
    * returns the eta value of a bunch of bugs
    */
   private function calcFullEta ()
   {
      $this->fullEta = 0;
      foreach ( $this->bugIds as $bugId )
      {
         $bugEtaValue = bug_get_field ( $bugId, 'eta' );

         $etaEnumString = config_get ( 'eta_enum_string' );
         $etaEnumValues = MantisEnum::getValues ( $etaEnumString );

         foreach ( $etaEnumValues as $enumValue )
         {
            if ( $enumValue == $bugEtaValue )
            {
               $eta = new rEta( $enumValue );
               $this->fullEta += $eta->getEtaUser ();
            }
         }
      }
   }

   /**
    * returns the eta value of a single bug
    *
    * @param $bugId
    */
   private function calcSingleEta ( $bugId )
   {
      $bugEtaValue = bug_get_field ( $bugId, 'eta' );

      $etaEnumString = config_get ( 'eta_enum_string' );
      $etaEnumValues = MantisEnum::getValues ( $etaEnumString );

      foreach ( $etaEnumValues as $enumValue )
      {
         if ( $enumValue == $bugEtaValue )
         {
            $eta = new rEta( $enumValue );
            $this->singleEta = $eta->getEtaUser ();
         }
      }
   }

   /**
    * get the done bug ids and save them in the done bug id array
    */
   private function calcDoneBugIds ()
   {
      foreach ( $this->bugIds as $bugId )
      {
         $this->getIssueIsDone ( $bugId );
         if ( $this->issueIsDone )
         {
            array_push ( $this->doneBugIds, $bugId );
            $this->doneBugIds = array_unique ( $this->doneBugIds );
         }
      }
   }

   /**
    * get the doing bug ids and save them in the doing bug id array
    */
   private function calcDoingBugIds ()
   {
      foreach ( $this->bugIds as $bugId )
      {
         $this->getIssueIsDone ( $bugId );
         if ( $this->issueIsDone == false )
         {
            array_push ( $this->doingBugIds, $bugId );
            $this->doingBugIds = array_unique ( $this->doingBugIds );
         }
      }
   }

   /**
    * check if specified bug is done
    *
    * @param $bugId
    */
   private function checkIssueIsDoneById ( $bugId )
   {
      $this->issueIsDone = false;

      $bugStatus = bug_get_field ( $bugId, 'status' );
      $roadmapProfile = new rProfile( $this->profileId );
      $dbRaodmapStatus = $roadmapProfile->getProfileStatus ();
      $roadmapStatusArray = explode ( ';', $dbRaodmapStatus );

      foreach ( $roadmapStatusArray as $roadmapStatus )
      {
         if ( $bugStatus == $roadmapStatus )
         {
            $this->issueIsDone = true;
         }
      }
   }

   /**
    * calculate the sum of eta for all done bugs
    */
   private function calcDoneEta ()
   {
      $this->doneEta = 0;
      $doneBugIds = $this->getDoneBugIds ();
      if ( $this->getEtaIsSet () )
      {
         foreach ( $doneBugIds as $doneBugId )
         {
            $this->doneEta += $this->getSingleEta ( $doneBugId );
         }
      }
   }

   /**
    * calc the percentage of done progress for a single roadmap
    */
   private function calcSingleProgressPercent ()
   {
      $this->doneEta = 0;
      $doneBugIds = $this->getDoneBugIds ();
      if ( $this->getEtaIsSet () )
      {
         $fullEta = $this->getFullEta ();
         foreach ( $doneBugIds as $doneBugId )
         {
            $this->doneEta += $this->getSingleEta ( $doneBugId );
         }

         if ( $fullEta > 0 )
         {
            $this->progressPercent = ( ( $this->doneEta / $fullEta ) * 100 );
         }
      }
      else
      {
         $doneBugAmount = count ( $doneBugIds );
         $allBugCount = count ( $this->bugIds );
         $progress = ( $doneBugAmount / $allBugCount );
         $this->progressPercent = ( $progress * 100 );
      }
   }

   /**
    * calc the roadmap data for a roadmap group
    */
   private function calcScaledData ()
   {
      # variables
      $roadmapProfileIds = rProfileManager::getGroupSpecProfileIds ( $this->groupId );
      $profileCount = count ( $roadmapProfileIds );
      $useEta = $this->getEtaIsSet ();
      $allBugCount = count ( $this->bugIds );
      $wholeProgress = 0;
      # iterate through profiles
      for ( $index = 0; $index < $profileCount; $index++ )
      {
         $roadmapProfileId = $roadmapProfileIds[ $index ];
         $roadmapProfile = new rProfile( $roadmapProfileId );
         $tProfileId = $roadmapProfile->getProfileId ();
         $this->setProfileId ( $tProfileId );
         # effort factor
         $profileEffortFactor = rProApi::getProfileEffortFactor ( $this );
         # bug data
         $doneBugIds = $this->getDoneBugIds ();
         $tDoneBugAmount = count ( $doneBugIds );
         if ( $useEta )
         {
            # calculate eta for profile
            $fullEta = ( $this->getFullEta () ) * $profileCount;
            $doneEta = 0;
            foreach ( $doneBugIds as $doneBugId )
            {
               $doneEta += $this->getSingleEta ( $doneBugId );
            }
            $doneEtaPercent = 0;
            if ( $fullEta > 0 )
            {
               $doneEtaPercent = ( ( $doneEta / $fullEta ) * 100 );
            }
            $doneEtaPercent = $doneEtaPercent * $profileCount * $profileEffortFactor;
            $wholeProgress += $doneEtaPercent;
            $profileHash = $tProfileId . ';' . $doneEtaPercent;
         }
         else
         {
            $tVersionProgress = ( $tDoneBugAmount / $allBugCount );
            $progessDonePercent = ( $tVersionProgress * 100 * $profileEffortFactor );
            $wholeProgress += $progessDonePercent;
            $profileHash = $tProfileId . ';' . $progessDonePercent;
         }

         array_push ( $this->profileHashArray, $profileHash );
         $this->resetDoneBugIds ();
         $this->setProfileId ( -1 );
      }

      $this->progressPercent = $wholeProgress;
   }

   /**
    * generate and return text progress string for eta-calculated progress for main roadmap
    *
    * @return string
    */
   private function generateEtaTextProgressMain ()
   {
      $calculatedDoneEta = rProApi::calculateEtaUnit ( $this->doneEta );
      $calculatedFullEta = rProApi::calculateEtaUnit ( $this->fullEta );
      return '&nbsp;' . round ( $this->progressPercent ) .
      '%&nbsp;(' . round ( ( $calculatedDoneEta[ 0 ] ), 1 ) . $calculatedFullEta[ 1 ] .
      '&nbsp;' . plugin_lang_get ( 'roadmap_page_bar_from' ) .
      '&nbsp;' . round ( ( $calculatedFullEta[ 0 ] ), 1 ) . $calculatedFullEta[ 1 ] . ')';
   }

   /**
    * generate and return text progress string for eta-calculated progress for directory
    *
    * @return string
    */
   private function generateEtaTextProgressDir ()
   {
      $calculatedFullEta = rProApi::calculateEtaUnit ( $this->fullEta );
      return '&nbsp;' . round ( $this->progressPercent ) . '%&nbsp;' . plugin_lang_get ( 'roadmap_page_bar_from' ) .
      '&nbsp;' . round ( ( $calculatedFullEta[ 0 ] ), 1 ) . $calculatedFullEta[ 1 ];
   }

   /**
    * generate and return text progress string for percentage-calculated progress for main roadmap and directory
    *
    * @return string
    */
   private function generatePercentTextProgressMain ()
   {
      $doneBugCount = count ( rProApi::getDoneIssueIdsForAllProfiles ( $this->bugIds, $this->groupId ) );
      return '&nbsp;' . round ( $this->progressPercent ) .
      '%&nbsp;(' . $doneBugCount .
      '&nbsp;' . plugin_lang_get ( 'roadmap_page_bar_from' ) .
      '&nbsp;' . count ( $this->bugIds ) .
      '&nbsp;' . lang_get ( 'issues' ) . ')';
   }

   /**
    * generate and return text progress string for percentage-calculated progress for main roadmap and directory
    *
    * @return string
    */
   private function generatePercentTextProgressDir ()
   {
      return '&nbsp;' . round ( $this->progressPercent ) . '%&nbsp;' . plugin_lang_get ( 'roadmap_page_bar_from' ) .
      '&nbsp;' . count ( $this->bugIds ) . '&nbsp;' . lang_get ( 'issues' );
   }

   /**
    * calculate the expected finishing date for a roadmap
    */
   private function calcExpectedFinishedDate ()
   {
      # time difference in seconds
      $overallLossFactor = ( 1 / rProApi::getWeekWorkDayAmount () ) * ( DAYSPERWEEK ) * LOSSFACTOR;
      $etaDifferenceInSec = ( ( ( $this->fullEta - $this->doneEta ) * HOURINSEC ) * ( HOURSPERDAY / rProApi::getAverageHoursPerDay () ) ) * $overallLossFactor;

      # set user specifiv timezone
      date_default_timezone_set ( rProApi::getUserPrefTimeZone ( auth_get_current_user_id () ) );
      # expected time => now + difference
      $dateFinishedExpectedInSec = time () + $etaDifferenceInSec;
      $finishedExpectedDay = date ( 'D', $dateFinishedExpectedInSec );
      if ( $finishedExpectedDay == 'Mon' )
      {
         $dayIsValid = rProApi::checkDayIsValid ( MON );
         # calc time to add to finished day
         if ( !$dayIsValid )
         {
            $dateFinishedExpectedInSec = $this->calcDelay ( $dateFinishedExpectedInSec, MON );
         }
      }
      if ( $finishedExpectedDay == 'Tue' )
      {
         $dayIsValid = rProApi::checkDayIsValid ( TUE );
         # calc time to add to finished day
         if ( !$dayIsValid )
         {
            $dateFinishedExpectedInSec = $this->calcDelay ( $dateFinishedExpectedInSec, TUE );
         }
      }
      if ( $finishedExpectedDay == 'Wed' )
      {
         $dayIsValid = rProApi::checkDayIsValid ( WED );
         # calc time to add to finished day
         if ( !$dayIsValid )
         {
            $dateFinishedExpectedInSec = $this->calcDelay ( $dateFinishedExpectedInSec, WED );
         }
      }
      if ( $finishedExpectedDay == 'Thu' )
      {
         $dayIsValid = rProApi::checkDayIsValid ( THU );
         # calc time to add to finished day
         if ( !$dayIsValid )
         {
            $dateFinishedExpectedInSec = $this->calcDelay ( $dateFinishedExpectedInSec, THU );
         }
      }
      if ( $finishedExpectedDay == 'Fri' )
      {
         $dayIsValid = rProApi::checkDayIsValid ( FRI );
         # calc time to add to finished day
         if ( !$dayIsValid )
         {
            $dateFinishedExpectedInSec = $this->calcDelay ( $dateFinishedExpectedInSec, FRI );
         }
      }
      if ( $finishedExpectedDay == 'Sat' )
      {
         $dayIsValid = rProApi::checkDayIsValid ( SAT );
         # calc time to add to finished day
         if ( !$dayIsValid )
         {
            $dateFinishedExpectedInSec = $this->calcDelay ( $dateFinishedExpectedInSec, SAT );
         }
      }
      if ( $finishedExpectedDay == 'Sun' )
      {
         $dayIsValid = rProApi::checkDayIsValid ( SUN );
         # calc time to add to finished day
         if ( !$dayIsValid )
         {
            $dateFinishedExpectedInSec = $this->calcDelay ( $dateFinishedExpectedInSec, SUN );
         }
      }

      $this->expectedFinishedDate = $dateFinishedExpectedInSec;
   }

   /**
    * calculate delay for free days
    *
    * @param $dateFinishedExpectedInSec
    * @param $day
    * @return bool|int
    */
   private function calcDelay ( $dateFinishedExpectedInSec, $day )
   {
      $dateFinishedExpectedInSec += SECONDS_PER_DAY;
      for ( $index = 1; $index < 8; $index++ )
      {
         if ( $index == 7 )
         {
            return false;
         }

         if ( !rProApi::checkDayIsValid ( ( $day + $index ) % DAYSPERWEEK ) )
         {
            $dateFinishedExpectedInSec += SECONDS_PER_DAY;
         }
         else
         {
            break;
         }
      }

      return $dateFinishedExpectedInSec;
   }

   /**
    * calculate the difference between actual and desired finished date in days
    *
    * @param $versonDesiredDate
    * @return string
    */
   private function calcDifferenceActualDesiredFinishedDate ( $versonDesiredDate )
   {
      $difference = $this->expectedFinishedDate - $versonDesiredDate;
      if ( $difference >= 0 )
      {
         $operator = '+';
      }
      else
      {
         $operator = '-';
      }
      $differenceInDay = ceil ( abs ( $difference ) / 86400 );

      return $operator . $differenceInDay . 'd';
   }

   /**
    * count eta tagged and not eta tagged bugs
    */
   private function countEtaTaggedBugs ()
   {
      foreach ( $this->bugIds as $bugId )
      {
         $bugEtaValue = bug_get_field ( $bugId, 'eta' );
         if ( ( $bugEtaValue == null ) || ( $bugEtaValue == ETA_NONE ) )
         {
            $this->etaNotTaggedBugCount++;
         }
         else
         {
            $this->etaTaggedBugCount++;
         }
      }
   }
}