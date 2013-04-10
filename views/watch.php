<?php
//
// ZoneMinder web watch feed view file, $Date: 2011-02-06 16:04:11 +0000 (Sun, 06 Feb 2011) $, $Revision: 3281 $
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

if ( !canView( 'Stream' ) )
{
    $view = "error";
    return;
}

$sql = "select C.*, M.* from Monitors as M left join Controls as C on (M.ControlId = C.Id ) where M.Id = '".dbEscape($_REQUEST['mid'])."'";
$monitor = dbFetchOne( $sql );

if ( isset($_REQUEST['showControls']) )
    $showControls = validInt($_REQUEST['showControls']);
else
    $showControls = (canView( 'Control' ) && ($monitor['DefaultView'] == 'Control'));

$showPtzControls = ( ZM_OPT_CONTROL && $monitor['Controllable'] && canView( 'Control' ) );

if ( isset( $_REQUEST['scale'] ) )
    $scale = validInt($_REQUEST['scale']);
else
    $scale = reScale( SCALE_BASE, $monitor['DefaultScale'], ZM_WEB_DEFAULT_SCALE );

$connkey = generateConnKey();

if ( ZM_WEB_STREAM_METHOD == 'mpeg' && ZM_MPEG_LIVE_FORMAT )
{
    $streamMode = "mpeg";
    $streamSrc = getStreamSrc( array( "mode=".$streamMode, "monitor=".$monitor['Id'], "scale=".$scale, "bitrate=".ZM_WEB_VIDEO_BITRATE, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "format=".ZM_MPEG_LIVE_FORMAT ) );
}
elseif ( canStream() )
{
    $streamMode = "jpeg";
    $streamSrc = getStreamSrc( array( "mode=".$streamMode, "monitor=".$monitor['Id'], "scale=".$scale, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "buffer=".$monitor['StreamReplayBuffer'] ) );
}
else
{
    $streamMode = "single";
    $streamSrc = getStreamSrc( array( "mode=".$streamMode, "monitor=".$monitor['Id'], "scale=".$scale ) );
}

$showDvrControls = ( $streamMode == 'jpeg' && $monitor['StreamReplayBuffer'] != 0 );

noCacheHeaders();

xhtmlHeaders( __FILE__, $monitor['Name']." - ".$SLANG['Feed'] );
?>
<body>
  <div class="container-fluid">
    <div class="row-fluid">
      <div id="menuBar">
        <div class="lead" id="monitorName"><?= $monitor['Name'] ?></div>
        <div id="closeControl"><a class="btn btn-small btn-primary" href="#" onclick="closeWindow(); return( false );"><?= $SLANG['Close'] ?></a></div>
        <div id="menuControls">
<?php
if ( $showPtzControls )
{
    if ( canView( 'Control' ) )
    {
?>
          <div id="controlControl"<?= $showControls?' class="hidden"':'' ?>><a id="controlLink" class="btn btn-small btn-info" href="#" onclick="showPtzControls(); return( false );"><?= $SLANG['Control'] ?></a></div>
<?php
    }
    if ( canView( 'Events' ) )
    {
?>
          <div id="eventsControl"<?= $showControls?'':' class="hidden"' ?>><a id="eventsLink" class="btn btn-small btn-primary" href="#" onclick="showEvents(); return( false );"><?= $SLANG['Events'] ?></a></div>
<?php
    }
}
?>
<?php
if ( canView( 'Control' ) && $monitor['Type'] == "Local" )
{
?>
          <div id="settingsControl"><?= makePopupLink( '?view=settings&amp;mid='.$monitor['Id'], 'zmSettings'.$monitor['Id'], 'settings', $SLANG['Settings'], true, 'id="settingsLink"' ) ?></div>
<?php
}
?>
          <div id="scaleControl"><?= $SLANG['Scale'] ?>: <?= buildSelect( "scale", $scales, "changeScale( this );" ); ?></div>
        </div>
      </div>
      <div id="imageFeed">
<?php
if ( $streamMode == "mpeg" )
{
    outputVideoStream( "liveStream", $streamSrc, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), ZM_MPEG_LIVE_FORMAT, $monitor['Name'] );
}
elseif ( $streamMode == "jpeg" )
{
    if ( canStreamNative() )
        outputImageStream( "liveStream", $streamSrc, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), $monitor['Name'] );
    elseif ( canStreamApplet() )
        outputHelperStream( "liveStream", $streamSrc, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), $monitor['Name'] );
}
else
{
    outputImageStill( "liveStream", $streamSrc, reScale( $monitor['Width'], $scale ), reScale( $monitor['Height'], $scale ), $monitor['Name'] );
}
?>
      </div>
      <div id="monitorStatus">
<?php
if ( canEdit( 'Monitors' ) )
{
?>
        <div id="enableDisableAlarms"><a id="enableAlarmsLink" href="#" class="btn btn-small btn-primary" onclick="cmdEnableAlarms(); return( false );" class="hidden"><?= $SLANG['EnableAlarms'] ?></a><a id="disableAlarmsLink" href="#"  onclick="cmdDisableAlarms(); return( false );" class="hidden btn btn-small btn-danger"><?= $SLANG['DisableAlarms'] ?></a></div>
        <div id="forceCancelAlarm"><a id="forceAlarmLink" href="#"class="btn btn-small btn-primary" onclick="cmdForceAlarm()" class="hidden"><?= $SLANG['ForceAlarm'] ?></a><a id="cancelAlarmLink" href="#" onclick="cmdCancelForcedAlarm()" class="hidden btn btn-small btn-danger"><?= $SLANG['CancelForcedAlarm'] ?></a></div>
<?php
}
?>
        <div id="monitorState"><?= $SLANG['State'] ?>:&nbsp;<span id="stateValue"></span>&nbsp;-&nbsp;<span id="fpsValue"></span>&nbsp;fps</div>
      </div>
      <div id="dvrControls"<?= $showDvrControls?'':' class="hidden"' ?>>
        <button type="button" id="fastRevBtn" title="<?= $SLANG['Rewind'] ?>" class="unavail" disabled="disabled" onclick="streamCmdFastRev( true )"/><i class="icon-fast-backward icon-align-center"></i></button>
        <button type="button" id="slowRevBtn" title="<?= $SLANG['StepBack'] ?>" class="unavail" disabled="disabled" onclick="streamCmdSlowRev( true )"/><i class="icon-backward"></i></button>
        <button type="button" id="pauseBtn" title="<?= $SLANG['Pause'] ?>" class="inactive" onclick="streamCmdPause( true )"/><i class="icon-pause"></i></button>
        <button type="button" id="stopBtn" title="<?= $SLANG['Stop'] ?>" class="unavail" disabled="disabled" onclick="streamCmdStop( true )"/><i class="icon-stop"></i></button>
        <button type="button" id="playBtn" title="<?= $SLANG['Play'] ?>" class="active" disabled="disabled" onclick="streamCmdPlay( true )"/><i class="icon-play"></i></button>
        <button type="button" id="slowFwdBtn" title="<?= $SLANG['StepForward'] ?>" class="unavail" disabled="disabled" onclick="streamCmdSlowFwd( true )"/><i class="icon-forward"></i></button>
        <button type="button" id="fastFwdBtn" title="<?= $SLANG['FastForward'] ?>" class="unavail" disabled="disabled" onclick="streamCmdFastFwd( true )"/><i class="icon-fast-forward"></i></button>
        <button type="button" id="zoomOutBtn" title="<?= $SLANG['ZoomOut'] ?>" class="avail" onclick="streamCmdZoomOut()"/><i class="icon-zoom-out"></i></button>
      </div>
      <div id="replayStatus"<?= $streamMode=="single"?' class="hidden"':'' ?>>
        <span id="mode">Mode: <span id="modeValue"></span></span>
        <span id="rate">Rate: <span id="rateValue"></span>x</span>
        <span id="delay">Delay: <span id="delayValue"></span>s</span>
        <span id="level">Buffer: <span id="levelValue"></span>%</span>
        <span id="zoom">Zoom: <span id="zoomValue"></span>x</span>
      </div>
<?php
if ( $showPtzControls )
{
    foreach ( getSkinIncludes( 'includes/control_functions.php' ) as $includeFile )
        require_once $includeFile;
?>
      <div id="ptzControls" class="ptzControls<?= $showControls?'':' hidden' ?>">
<?= ptzControls( $monitor ) ?>
      </div>
<?php
}
if ( canView( 'Events' ) )
{
?>
      <div id="events"<?= $showControls?' class="hidden"':'' ?>>
        <table class="table table-condensed table-hover" id="eventList" cellspacing="0">
          <thead>
            <tr>
              <th class="colId"><?= $SLANG['Id'] ?></th>
              <th class="colName"><?= $SLANG['Name'] ?></th>
              <th class="colTime"><?= $SLANG['Time'] ?></th>
              <th class="colSecs"><?= $SLANG['Secs'] ?></th>
              <th class="colFrames"><?= $SLANG['Frames'] ?></th>
              <th class="colScore"><?= $SLANG['Score'] ?></th>
              <!-- TODO: Add to Language file! -->
              <th class="colDelete">Del</th>
            </tr>
          </thead>
          <tbody>
          </tbody>
        </table>
      </div>
<?php
}
if ( ZM_WEB_SOUND_ON_ALARM )
{
    $soundSrc = ZM_DIR_SOUNDS.'/'.ZM_WEB_ALARM_SOUND;
?>
      <div id="alarmSound" class="hidden">
<?php
    if ( ZM_WEB_USE_OBJECT_TAGS && isWindows() )
    {
?>
        <object id="MediaPlayer" width="0" height="0"
          classid="CLSID:22D6F312-B0F6-11D0-94AB-0080C74C7E95"
          codebase="http://activex.microsoft.com/activex/controls/mplayer/en/nsmp2inf.cab#Version=6,0,02,902">
          <param name="FileName" value="<?= $soundSrc ?>"/>
          <param name="autoStart" value="0"/>
          <param name="loop" value="1"/>
          <param name="hidden" value="1"/>
          <param name="showControls" value="0"/>
          <embed src="<?= $soundSrc ?>"
            autostart="true"
            loop="true"
            hidden="true">
          </embed>
        </object>
<?php
    }
    else
    {
?>
        <embed src="<?= $soundSrc ?>"
          autostart="true"
          loop="true"
          hidden="true">
        </embed>
<?php
    }
?>
      </div>
<?php
}
?>
    </div>
  </div>
</body>
</html>
