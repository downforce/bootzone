<?php
//
// ZoneMinder web frame view file, $Date: 2010-02-23 09:12:12 +0000 (Tue, 23 Feb 2010) $, $Revision: 3031 $
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

if ( !canView( 'Events' ) )
{
    $view = "error";
    return;
}

$eid = validInt($_REQUEST['eid']);
if ( !empty($_REQUEST['fid']) )
    $fid = validInt($_REQUEST['fid']);

$sql = "select E.*,M.Name as MonitorName,M.Width,M.Height,M.DefaultScale from Events as E inner join Monitors as M on E.MonitorId = M.Id where E.Id = '".dbEscape($eid)."'";
$event = dbFetchOne( $sql );

if ( !empty($fid) )
{
    $sql = "select * from Frames where EventId = '".dbEscape($eid)."' and FrameId = '".dbEscape($fid)."'";
    if ( !($frame = dbFetchOne( $sql )) )
        $frame = array( 'FrameId'=>$fid, 'Type'=>'Normal', 'Score'=>0 );
}
else
{
    $frame = dbFetchOne( "select * from Frames where EventId = '".dbEscape($eid)."' and Score = '".$event['MaxScore']."'" );
}

$maxFid = $event['Frames'];

$firstFid = 1;
$prevFid = $frame['FrameId']-1;
$nextFid = $frame['FrameId']+1;
$lastFid = $maxFid;

$alarmFrame = $frame['Type']=='Alarm';

if ( isset( $_REQUEST['scale'] ) )
    $scale = validInt($_REQUEST['scale']);
else
    $scale = max( reScale( SCALE_BASE, $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE ), SCALE_BASE );

$imageData = getImageSrc( $event, $frame, $scale, (isset($_REQUEST['show']) && $_REQUEST['show']=="capt") );

$imagePath = $imageData['thumbPath'];
$eventPath = $imageData['eventPath'];
$dImagePath = sprintf( "%s/%0".ZM_EVENT_IMAGE_DIGITS."d-diag-d.jpg", $eventPath, $frame['FrameId'] );
$rImagePath = sprintf( "%s/%0".ZM_EVENT_IMAGE_DIGITS."d-diag-r.jpg", $eventPath, $frame['FrameId'] );

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['Frame']." - ".$event['Id']." - ".$frame['FrameId'] );
?>
<body>
  <div class="container-fluid">
    <div class="row-fluid">
      <?php if ( ZM_RECORD_EVENT_STATS && $alarmFrame ) { echo makePopupLink( '?view=stats&amp;eid='.$event['Id'].'&amp;fid='.$frame['FrameId'].'" class="btn btn-small btn-primary"', 'zmStats', 'stats', $SLANG['Stats'] ); } ?>
      <?php if ( canEdit( 'Events' ) ) { ?><a href="?view=none&amp;action=delete&amp;markEid=<?= $event['Id'] ?>" class="btn btn-small btn-danger"><?= $SLANG['Delete'] ?></a><?php } ?>
      <a href="#" class="btn btn-small btn-inverse" onclick="closeWindow(); return( false );"><?= $SLANG['Close'] ?></a>
      <h4><?= $SLANG['Frame'] ?> <?= $event['Id']."-".$frame['FrameId']." (".$frame['Score'].")" ?></h4>
    </div>
    <div class="row-fluid">
      <p id="image"><?php if ( $imageData['hasAnalImage'] ) { ?><a href="?view=frame&amp;eid=<?= $event['Id'] ?>&amp;fid=<?= $frame['FrameId'] ?>&amp;scale=<?= $scale ?>&amp;show=<?= $imageData['isAnalImage']?"capt":"anal" ?>"><?php } ?><img src="<?= viewImagePath( $imagePath ) ?>" width="<?= reScale( $event['Width'], $event['DefaultScale'], $scale ) ?>" height="<?= reScale( $event['Height'], $event['DefaultScale'], $scale ) ?>" alt="<?= $frame['EventId']."-".$frame['FrameId'] ?>" class="<?= $imageData['imageClass'] ?>"/><?php if ( $imageData['hasAnalImage'] ) { ?></a><?php } ?></p>
    </div>
    <div class="row-fluid text-center">
<?php if ( $frame['FrameId'] > 1 ) { ?>
        <a href="?view=frame&amp;eid=<?= $event['Id'] ?>&amp;fid=<?= $firstFid ?>&amp;scale=<?= $scale ?>" class="btn btn-small btn-primary"><?= $SLANG['First'] ?></a>
<?php } if ( $frame['FrameId'] > 1 ) { ?>
        <a href="?view=frame&amp;eid=<?= $event['Id'] ?>&amp;fid=<?= $prevFid ?>&amp;scale=<?= $scale ?>" class="btn btn-small btn-primary"><?= $SLANG['Prev'] ?></a>
<?php } if ( $frame['FrameId'] < $maxFid ) { ?>
        <a href="?view=frame&amp;eid=<?= $event['Id'] ?>&amp;fid=<?= $nextFid ?>&amp;scale=<?= $scale ?>" class="btn btn-small btn-primary"><?= $SLANG['Next'] ?></a>
<?php } if ( $frame['FrameId'] < $maxFid ) { ?>
        <a href="?view=frame&amp;eid=<?= $event['Id'] ?>&amp;fid=<?= $lastFid ?>&amp;scale=<?= $scale ?>" class="btn btn-small btn-primary"><?= $SLANG['Last'] ?></a>
<?php } ?>
<?php if (file_exists ($dImagePath)) { ?>
    </div>
    <div class="row-fluid">
      <p id="diagImagePath"><?= $dImagePath ?></p>
      <p id="diagImage"><img src=?"<?= viewImagePath( $dImagePath ) ?>" width="<?= reScale( $event['Width'], $event['DefaultScale'], $scale ) ?>" height="<?= reScale( $event['Height'], $event['DefaultScale'], $scale ) ?>" class="<?= $imageData['imageClass'] ?>"/></p>
<?php } if (file_exists ($rImagePath)) { ?>
      <p id="refImagePath"><?= $rImagePath ?></p>
      <p id="refImage"><img src="<?= viewImagePath( $rImagePath ) ?>" width="<?= reScale( $event['Width'], $event['DefaultScale'], $scale ) ?>" height="<?= reScale( $event['Height'], $event['DefaultScale'], $scale ) ?>" class="<?= $imageData['imageClass'] ?>"/></p>
<?php } ?>
    </div>
  </div>
</body>
</html>
