<?php
//
// ZoneMinder web console file, $Date: 2011-06-21 10:19:10 +0100 (Tue, 21 Jun 2011) $, $Revision: 3459 $
// Copyright (C) 2001-2008 Philip Coombes
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
//

$eventCounts = array(
    array(
        "title" => $SLANG['Events'],
        "filter" => array(
            "terms" => array(
            )
        ),
    ),
    array(
        "title" => $SLANG['Hour'],
        "filter" => array(
            "terms" => array(
                array( "attr" => "DateTime", "op" => ">=", "val" => "-1 hour" ),
            )
        ),
    ),
    array(
        "title" => $SLANG['Day'],
        "filter" => array(
            "terms" => array(
                array( "attr" => "DateTime", "op" => ">=", "val" => "-1 day" ),
            )
        ),
    ),
    array(
        "title" => $SLANG['Week'],
        "filter" => array(
            "terms" => array(
                array( "attr" => "DateTime", "op" => ">=", "val" => "-7 day" ),
            )
        ),
    ),
    array(
        "title" => $SLANG['Month'],
        "filter" => array(
            "terms" => array(
                array( "attr" => "DateTime", "op" => ">=", "val" => "-1 month" ),
            )
        ),
    ),
    array(
        "title" => $SLANG['Archived'],
        "filter" => array(
            "terms" => array(
                array( "attr" => "Archived", "op" => "=", "val" => "1" ),
            )
        ),
    ),
);

$running = daemonCheck();
$status = $running?$SLANG['Running']:$SLANG['Stopped'];

if ( $group = dbFetchOne( "select * from Groups where Id = '".(empty($_COOKIE['zmGroup'])?0:dbEscape($_COOKIE['zmGroup']))."'" ) )
    $groupIds = array_flip(explode( ',', $group['MonitorIds'] ));

noCacheHeaders();

$maxWidth = 0;
$maxHeight = 0;
$cycleCount = 0;
$minSequence = 0;
$maxSequence = 1;
$seqIdList = array();
$monitors = dbFetchAll( "select * from Monitors order by Sequence asc" );
$displayMonitors = array();
for ( $i = 0; $i < count($monitors); $i++ )
{
    if ( !visibleMonitor( $monitors[$i]['Id'] ) )
    {
        continue;
    }
    if ( $group && !empty($groupIds) && !array_key_exists( $monitors[$i]['Id'], $groupIds ) )
    {
        continue;
    }
    $monitors[$i]['Show'] = true;
    if ( empty($minSequence) || ($monitors[$i]['Sequence'] < $minSequence) )
    {
        $minSequence = $monitors[$i]['Sequence'];
    }
    if ( $monitors[$i]['Sequence'] > $maxSequence )
    {
        $maxSequence = $monitors[$i]['Sequence'];
    }
    $monitors[$i]['zmc'] = zmcStatus( $monitors[$i] );
    $monitors[$i]['zma'] = zmaStatus( $monitors[$i] );
    $monitors[$i]['ZoneCount'] = dbFetchOne( "select count(Id) as ZoneCount from Zones where MonitorId = '".$monitors[$i]['Id']."'", "ZoneCount" );
    $counts = array();
    for ( $j = 0; $j < count($eventCounts); $j++ )
    {
        $filter = addFilterTerm( $eventCounts[$j]['filter'], count($eventCounts[$j]['filter']['terms']), array( "cnj" => "and", "attr" => "MonitorId", "op" => "=", "val" => $monitors[$i]['Id'] ) );
        parseFilter( $filter );
        $counts[] = "count(if(1".$filter['sql'].",1,NULL)) as EventCount$j";
        $monitors[$i]['eventCounts'][$j]['filter'] = $filter;
    }
    $sql = "select ".join($counts,", ")." from Events as E where MonitorId = '".$monitors[$i]['Id']."'";
    $counts = dbFetchOne( $sql );
    if ( $monitors[$i]['Function'] != 'None' )
    {
        $cycleCount++;
        $scaleWidth = reScale( $monitors[$i]['Width'], $monitors[$i]['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
        $scaleHeight = reScale( $monitors[$i]['Height'], $monitors[$i]['DefaultScale'], ZM_WEB_DEFAULT_SCALE );
        if ( $maxWidth < $scaleWidth ) $maxWidth = $scaleWidth;
        if ( $maxHeight < $scaleHeight ) $maxHeight = $scaleHeight;
    }
    $monitors[$i] = array_merge( $monitors[$i], $counts );
    $seqIdList[] = $monitors[$i]['Id'];
    $displayMonitors[] = $monitors[$i];
}
$lastId = 0;
$seqIdUpList = array();
foreach ( $seqIdList as $seqId )
{
    if ( !empty($lastId) )
        $seqIdUpList[$seqId] = $lastId;
    else
        $seqIdUpList[$seqId] = $seqId;
    $lastId = $seqId;
}
$lastId = 0;
$seqIdDownList = array();
foreach ( array_reverse($seqIdList) as $seqId )
{
    if ( !empty($lastId) )
        $seqIdDownList[$seqId] = $lastId;
    else
        $seqIdDownList[$seqId] = $seqId;
    $lastId = $seqId;
}

$cycleWidth = $maxWidth;
$cycleHeight = $maxHeight;

$eventsView = ZM_WEB_EVENTS_VIEW;
$eventsWindow = 'zm'.ucfirst(ZM_WEB_EVENTS_VIEW);

$eventCount = 0;
for ( $i = 0; $i < count($eventCounts); $i++ )
{
    $eventCounts[$i]['total'] = 0;
}
$zoneCount = 0;
foreach( $displayMonitors as $monitor )
{
    for ( $i = 0; $i < count($eventCounts); $i++ )
    {
        $eventCounts[$i]['total'] += $monitor['EventCount'.$i];
    }
    $zoneCount += $monitor['ZoneCount'];
}

$seqUpFile = getSkinFile( 'graphics/seq-u.gif' );
$seqDownFile = getSkinFile( 'graphics/seq-d.gif' );

$versionClass = (ZM_DYN_DB_VERSION&&(ZM_DYN_DB_VERSION!=ZM_VERSION))?'text-error':'';

xhtmlHeaders( __FILE__, $SLANG['Console'] );
?>
<body>
  <div class="container-fluid" id="page">
    <form name="monitorForm" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="<?= $view ?>"/>
        <input type="hidden" name="action" value=""/>
        <div class="row-fluid">
          <h5 class="span3"><?= preg_match( '/%/', DATE_FMT_CONSOLE_LONG )?strftime( DATE_FMT_CONSOLE_LONG ):date( DATE_FMT_CONSOLE_LONG ) ?></h5>
          <h4 class="span6 text-center"><a href="http://www.zoneminder.com" target="ZoneMinder">ZoneMinder</a> <?= $SLANG['Console'] ?> - <?= makePopupLink( '?view=state', 'zmState', 'state', $status, canEdit( 'System' ) ) ?> - <?= makePopupLink( '?view=version', 'zmVersion', 'version', '<span class="'.$versionClass.'">v'.ZM_VERSION.'</span>', canEdit( 'System' ) ) ?></h4>
          <h5 class="span3 text-right"><?= $SLANG['Load'] ?>: <?= getLoad() ?> / <?= $SLANG['Disk'] ?>: <?= getDiskPercent() ?>%</h5>
        </div>
        <div class="row-fluid">
            <div class="text-center"><?php
    if ( ZM_OPT_USE_AUTH )
    {
    ?><?= $SLANG['LoggedInAs'] ?> <?= makePopupLink( '?view=logout', 'zmLogout', 'logout', $user['Username'], (ZM_AUTH_TYPE == "builtin") ) ?>.<br> <?= $SLANG['ConfiguredFor'] ?><?php
    }
    else
    {
    ?><?= $SLANG['ConfiguredFor'] ?><?php
    }
    ?>&nbsp;<?= makePopupLink( '?view=bandwidth', 'zmBandwidth', 'bandwidth', $bwArray[$_COOKIE['zmBandwidth']], ($user && $user['MaxBandwidth'] != 'low' ) ) ?> <?= $SLANG['Bandwidth'] ?>
          </div>
        </div>
        <div class="row-fluid">
          <div class="span6">
            <?= makePopupLink( '?view=groups', 'zmGroups', 'groups', sprintf( $CLANG['MonitorCount'], count($displayMonitors), zmVlang( $VLANG['Monitor'], count($displayMonitors) ) ).($group?' ('.$group['Name'].')':''), canView( 'System' ) ,'class="btn btn-small btn-info"' ); ?>
    <?php
    if ( ZM_OPT_X10 && canView( 'Devices' ) )
    {
    ?>
          <?= makePopupLink( '?view=devices', 'zmDevices', 'devices', $SLANG['Devices'] ) ?>
    <?php
    }
    ?>
          </div>
          <div class="span6 text-right">
    <?php
    if ( canView( 'System' ) )
    {
    ?>
          <?= makePopupLink( '?view=options', 'zmOptions', 'options', '<span class="btn btn-small">'.$SLANG['Options']."</span>") ?><?php if ( logToDatabase() > Logger::NOLOG ) { ?>
          <?= makePopupLink( '?view=log', 'zmLog', 'log', '<span class="'.logState().' btn btn-small"">'.$SLANG['Log'].'</span>' ) ?><?php } ?>
    <?php
    }
    if ( canView( 'Stream' ) && $cycleCount > 1 )
    {
        $cycleGroup = isset($_COOKIE['zmGroup'])?$_COOKIE['zmGroup']:0;
    ?>
          <?= makePopupLink( '?view=cycle&amp;group='.$cycleGroup, 'zmCycle'.$cycleGroup, array( 'cycle', $cycleWidth, $cycleHeight ), $SLANG['Cycle'], $running ) ?>&nbsp;/&nbsp;<?= makePopupLink( '?view=montage&amp;group='.$cycleGroup, 'zmMontage'.$cycleGroup, 'montage', $SLANG['Montage'], $running ) ?>
    <?php
    }
    else
    {
    ?>
    <?php
    }
    ?>
          </div>
        </div>
        </div>
        <div class="row-fluid">
          <table class="table table-condensed table-hover" cellspacing="0">
            <thead>
              <tr>
                <th class="colName"><?= $SLANG['Name'] ?></th>
                <th class="colFunction"><?= $SLANG['Function'] ?></th>
                <th class="colSource"><?= $SLANG['Source'] ?></th>
    <?php
    for ( $i = 0; $i < count($eventCounts); $i++ )
    {
    ?>
                <th class="colEvents"><?= $eventCounts[$i]['title'] ?></th>
    <?php
    }
    ?>
                <th class="colZones"><?= $SLANG['Zones'] ?></th>
    <?php
    if ( canEdit('Monitors') )
    {
    ?>
                <th class="colOrder"><?= $SLANG['Order'] ?></th>
    <?php
    }
    ?>
                <th class="colMark"><?= $SLANG['Mark'] ?></th>
              </tr>
            </thead>
            <tfoot>
              <tr>
                <td class="colLeft`Buttons" colspan="3">
                  <input type="button" class="btn btn-primary btn-small" value="<?= $SLANG['Refresh'] ?>" onclick="location.reload(true);"/>
                  <?= makePopupButton( '?view=monitor', 'zmMonitor0', 'monitor', $SLANG['AddNewMonitor'], (canEdit( 'Monitors' ) && !$user['MonitorIds']), "class='btn btn-primary btn-small'" ) ?>
                  <?= makePopupButton( '?view=filter&amp;filter[terms][0][attr]=DateTime&amp;filter[terms][0][op]=%3c&amp;filter[terms][0][val]=now', 'zmFilter', 'filter', $SLANG['Filters'], canView( 'Events' ),"class='btn btn-primary btn-small'" ) ?>
                </td>
    <?php
    for ( $i = 0; $i < count($eventCounts); $i++ )
    {
        parseFilter( $eventCounts[$i]['filter'] );
    ?>
                <td class="colEvents"><?= makePopupLink( '?view='.$eventsView.'&amp;page=1'.$eventCounts[$i]['filter']['query'], $eventsWindow, $eventsView, $eventCounts[$i]['total'], canView( 'Events' ) ) ?></td>
    <?php
    }
    ?>
                <td class="colZones"><?= $zoneCount ?></td>
                <td class="colRightButtons" colspan="<?= canEdit('Monitors')?2:1 ?>"><input type="button" name="editBtn" class="btn btn-primary btn-small" value="<?= $SLANG['Edit'] ?>" onclick="editMonitor( this )" disabled="disabled"/><input type="button" name="deleteBtn" class="btn btn-danger btn-small" value="<?= $SLANG['Delete'] ?>" onclick="deleteMonitor( this )" disabled="disabled"/></td>
              </tr>
            </tfoot>
            <tbody>
    <?php
    foreach( $displayMonitors as $monitor )
    {
    ?>
              <tr>
    <?php
        if ( !$monitor['zmc'] )
            $dclass = "text-error";
        else
        {
            if ( !$monitor['zma'] )
                $dclass = "text-warning";
            else
                $dclass = "text-info";
        }
        if ( $monitor['Function'] == 'None' )
            $fclass = "text-error";
        elseif ( $monitor['Function'] == 'Monitor' )
            $fclass = "text-warning";
        else
            $fclass = "text-info";
        if ( !$monitor['Enabled'] )
            $fclass .= " muted";
        $scale = max( reScale( SCALE_BASE, $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );
    ?>
                <td class="colName"><?= makePopupLink( '?view=watch&amp;mid='.$monitor['Id'], 'zmWatch'.$monitor['Id'], array( 'watch', reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ) ), $monitor['Name'], $running && ($monitor['Function'] != 'None') && canView( 'Stream' ) ) ?></td>
                <td class="colFunction"><?= makePopupLink( '?view=function&amp;mid='.$monitor['Id'], 'zmFunction', 'function', '<span class="'.$fclass.'">'.$monitor['Function'].'</span>', canEdit( 'Monitors' ) ) ?></td>
    <?php if ( $monitor['Type'] == "Local" ) { ?>
                <td class="colSource"><?= makePopupLink( '?view=monitor&amp;mid='.$monitor['Id'], 'zmMonitor'.$monitor['Id'], 'monitor', '<span class="'.$dclass.'">'.$monitor['Device'].' ('.$monitor['Channel'].')</span>', canEdit( 'Monitors' ) ) ?></td>
    <?php } elseif ( $monitor['Type'] == "Remote" ) { ?>
                <td class="colSource"><?= makePopupLink( '?view=monitor&amp;mid='.$monitor['Id'], 'zmMonitor'.$monitor['Id'], 'monitor', '<span class="'.$dclass.'">'.preg_replace( '/^.*@/', '', $monitor['Host'] ).'</span>', canEdit( 'Monitors' ) ) ?></td>
    <?php } elseif ( $monitor['Type'] == "File" ) { ?>
                <td class="colSource"><?= makePopupLink( '?view=monitor&amp;mid='.$monitor['Id'], 'zmMonitor'.$monitor['Id'], 'monitor', '<span class="'.$dclass.'">'.preg_replace( '/^.*\//', '', $monitor['Path'] ).'</span>', canEdit( 'Monitors' ) ) ?></td>
    <?php } elseif ( $monitor['Type'] == "Ffmpeg" ) { ?>
                <td class="colSource"><?= makePopupLink( '?view=monitor&amp;mid='.$monitor['Id'], 'zmMonitor'.$monitor['Id'], 'monitor', '<span class="'.$dclass.'">'.preg_replace( '/^.*\//', '', $monitor['Path'] ).'</span>', canEdit( 'Monitors' ) ) ?></td>
    <?php } else { ?>
                <td class="colSource">&nbsp;</td>
    <?php } ?>
    <?php
        for ( $i = 0; $i < count($eventCounts); $i++ )
        {
    ?>
                <td class="colEvents"><?= makePopupLink( '?view='.$eventsView.'&amp;page=1'.$monitor['eventCounts'][$i]['filter']['query'], $eventsWindow, $eventsView, $monitor['EventCount'.$i], canView( 'Events' ) ) ?></td>
    <?php
        }
    ?>
                <td class="colZones"><?= makePopupLink( '?view=zones&amp;mid='.$monitor['Id'], 'zmZones', array( 'zones', $monitor['Width'], $monitor['Height'] ), $monitor['ZoneCount'], canView( 'Monitors' ) ) ?></td>
    <?php
        if ( canEdit('Monitors') )
        {
    ?>
                <td class="colOrder"><?= makeLink( '?view='.$view.'&amp;action=sequence&amp;mid='.$monitor['Id'].'&amp;smid='.$seqIdUpList[$monitor['Id']], '<img src="'.$seqUpFile.'" alt="Up"/>', $monitor['Sequence']>$minSequence ) ?><?= makeLink( '?view='.$view.'&amp;action=sequence&amp;mid='.$monitor['Id'].'&amp;smid='.$seqIdDownList[$monitor['Id']], '<img src="'.$seqDownFile.'" alt="Down"/>', $monitor['Sequence']<$maxSequence ) ?></td>
    <?php
        }
    ?>
                <td class="colMark"><input type="checkbox" name="markMids[]" value="<?= $monitor['Id'] ?>" onclick="setButtonStates( this )"<?php if ( !canEdit( 'Monitors' ) ) { ?> disabled="disabled"<?php } ?>/></td>
              </tr>
    <?php
    }
    ?>
            </tbody>
          </table>
        </div>
    </form>
  </div>
</body>
</html>
