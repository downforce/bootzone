<?php
//
// ZoneMinder web event view file, $Date: 2010-11-03 15:49:46 +0000 (Wed, 03 Nov 2010) $, $Revision: 3159 $
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

$eid = validInt( $_REQUEST['eid'] );
$fid = !empty($_REQUEST['fid'])?validInt($_REQUEST['fid']):1;

if ( $user['MonitorIds'] )
    $midSql = " and MonitorId in (".join( ",", preg_split( '/["\'\s]*,["\'\s]*/', dbEscape($user['MonitorIds']) ) ).")";
else
    $midSql = '';

$sql = "select E.*,M.Name as MonitorName,M.Width,M.Height,M.DefaultRate,M.DefaultScale from Events as E inner join Monitors as M on E.MonitorId = M.Id where E.Id = '".dbEscape($eid)."'".$midSql;
$event = dbFetchOne( $sql );

if ( isset( $_REQUEST['rate'] ) )
    $rate = validInt($_REQUEST['rate']);
else
    $rate = reScale( RATE_BASE, $event['DefaultRate'], ZM_WEB_DEFAULT_RATE );
if ( isset( $_REQUEST['scale'] ) )
    $scale = validInt($_REQUEST['scale']);
else
    $scale = reScale( SCALE_BASE, $event['DefaultScale'], ZM_WEB_DEFAULT_SCALE );

$replayModes = array(
    'single' => $SLANG['ReplaySingle'],
    'all' => $SLANG['ReplayAll'],
    'gapless' => $SLANG['ReplayGapless'],
);

if ( isset( $_REQUEST['streamMode'] ) )
    $streamMode = validHtmlStr($_REQUEST['streamMode']);
else
    $streamMode = canStream()?'stream':'stills';

if ( isset( $_REQUEST['replayMode'] ) )
    $replayMode = validHtmlStr($_REQUEST['replayMode']);
if ( isset( $_COOKIE['replayMode']) && preg_match('#^[a-z]+$#', $_COOKIE['replayMode']) )
    $replayMode = validHtmlStr($_COOKIE['replayMode']);
 else
     $replayMode = array_shift( array_keys( $replayModes ) );

parseSort();
parseFilter( $_REQUEST['filter'] );
$filterQuery = $_REQUEST['filter']['query'];

$panelSections = 40;
$panelSectionWidth = (int)ceil(reScale($event['Width'],$scale)/$panelSections);
$panelWidth = ($panelSections*$panelSectionWidth-1);

$connkey = generateConnKey();

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['Event'] );
?>
<body>
  <div class="container-fluid">
    <div class="row-fluid">
      <a href="#" class="btn btn-small btn-inverse" onclick="closeWindow();"><?= $SLANG['Close'] ?></a>
    </div>
    <div class="row-fluid">
      <table class="table table-condensed table-hover" cellspacing="0">
        <tr>
          <td><span title="<?= $SLANG['Id'] ?>"><?= $event['Id'] ?></span></td>
          <td><span title="<?= $event['Notes']?validHtmlStr($event['Notes']):$SLANG['AttrCause'] ?>"><?= validHtmlStr($event['Cause']) ?></span></td>
          <td><span title="<?= $SLANG['Time'] ?>"><?= strftime( STRF_FMT_DATETIME_SHORT, strtotime($event['StartTime'] ) ) ?></span></td>
          <td><span title="<?= $SLANG['Duration'] ?>"><?= $event['Length'] ?></span>s</td>
          <td><span title="<?= $SLANG['AttrFrames']."/".$SLANG['AttrAlarmFrames'] ?>"><?= $event['Frames'] ?>/<?= $event['AlarmFrames'] ?></span></td>
          <td><span title="<?= $SLANG['AttrTotalScore']."/".$SLANG['AttrAvgScore']."/".$SLANG['AttrMaxScore'] ?>"><?= $event['TotScore'] ?>/<?= $event['AvgScore'] ?>/<?= $event['MaxScore'] ?></span></td>
        </tr>
      </table>
    </div>
    <div class="row-fluid">
      <label for="scale"><?= $SLANG['Scale'] ?></label><?= buildSelect( "scale", $scales, "changeScale();" ); ?> <label for="replayMode"><?= $SLANG['Replay'] ?></label><?= buildSelect( "replayMode", $replayModes, "changeReplayMode();" ); ?>
      <input type="text" name="eventName" value="<?= validHtmlStr($event['Name']) ?>" size="16"/> <input type="button" class="btn btn-small btn-primary" value="<?= $SLANG['Rename'] ?>" onclick="renameEvent()"<?php if ( !canEdit( 'Events' ) ) { ?> disabled="disabled"<?php } ?>/>
    </div>
    <div class="row-fluid">
<?php
if ( canEdit( 'Events' ) )
{
?>
        <a href="#" id="deleteEvent" class="btn btn-mini btn-danger" onclick="deleteEvent()"><?= $SLANG['Delete'] ?></a>
        <a href="#" id="editEvent" class="btn btn-mini btn-primary" onclick="editEvent()"><?= $SLANG['Edit'] ?></a>
<?php
}
if ( canView( 'Events' ) )
{
?>
        <a href="#" id="exportEvent" class="btn btn-mini btn-primary" onclick="exportEvent()"><?= $SLANG['Export'] ?></a>
<?php
}
if ( canEdit( 'Events' ) )
{
?>
        <a href="#" id="archiveEvent" class="hidden btn btn-mini btn-primary" onclick="archiveEvent()"><?= $SLANG['Archive'] ?></a>
        <a href="#" id="unarchiveEvent" class="hidden btn btn-mini btn-primary" onclick="unarchiveEvent()"><?= $SLANG['Unarchive'] ?></a>
<?php
}
?>
        <a href="#" class="btn btn-mini btn-primary" onclick="showEventFrames()"><?= $SLANG['Frames'] ?></a>
        <a href="#" class="<?php if ( $streamMode == 'stream' ) { ?>hidden<?php } ?> btn btn-mini btn-primary" onclick="showStream()"><?= $SLANG['Stream'] ?></a>
        <a href="#" class="<?php if ( $streamMode == 'still' ) { ?>hidden<?php } ?> btn btn-mini btn-primary" onclick="showStills()"><?= $SLANG['Stills'] ?></a>
<?php
if ( ZM_OPT_FFMPEG )
{
?>
        <a href="#" class="btn btn-mini btn-primary" onclick="videoEvent()"><?= $SLANG['Video'] ?></a>
<?php
}
?>
      </div>
      <div class="row-fluid">
        <div id="imageFeed">
<?php
if ( ZM_WEB_STREAM_METHOD == 'mpeg' && ZM_MPEG_LIVE_FORMAT )
{
    $streamSrc = getStreamSrc( array( "source=event", "mode=mpeg", "event=".$eid, "frame=".$fid, "scale=".$scale, "rate=".$rate, "bitrate=".ZM_WEB_VIDEO_BITRATE, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "format=".ZM_MPEG_REPLAY_FORMAT, "replay=".$replayMode ) );
    outputVideoStream( "evtStream", $streamSrc, reScale( $event['Width'], $scale ), reScale( $event['Height'], $scale ), ZM_MPEG_LIVE_FORMAT );
}
else
{
    $streamSrc = getStreamSrc( array( "source=event", "mode=jpeg", "event=".$eid, "frame=".$fid, "scale=".$scale, "rate=".$rate, "maxfps=".ZM_WEB_VIDEO_MAXFPS, "replay=".$replayMode) );
    if ( canStreamNative() )
    {
        outputImageStream( "evtStream", $streamSrc, reScale( $event['Width'], $scale ), reScale( $event['Height'], $scale ), validHtmlStr($event['Name']) );
    }
    else
    {
        outputHelperStream( "evtStream", $streamSrc, reScale( $event['Width'], $scale ), reScale( $event['Height'], $scale ) );
    }
}
?>
        </div>
        <p id="dvrControls">
          <button type="button" class="btn btn-small" id="prevBtn" title="<?= $SLANG['Prev'] ?>" class="inactive" onclick="streamPrev( true )"/><i class="icon-step-backward"></i></button>
          <button type="button" class="btn btn-small" id="fastRevBtn" title="<?= $SLANG['Rewind'] ?>" class="inactive" disabled="disabled" onclick="streamFastRev( true )"/><i class="icon-fast-backward icon-align-center"></i>
          <button type="button" class="btn btn-small" id="slowRevBtn" title="<?= $SLANG['StepBack'] ?>" class="unavail" disabled="disabled" onclick="streamSlowRev( true )"/><i class="icon-backward"></i></button>
          <button type="button" class="btn btn-small" id="pauseBtn" title="<?= $SLANG['Pause'] ?>" class="inactive" onclick="streamPause( true )"/><i class="icon-pause"></i></button>
          <button type="button" class="btn btn-small" id="playBtn" title="<?= $SLANG['Play'] ?>" class="active" disabled="disabled" onclick="streamPlay( true )"/><i class="icon-play"></i></button>
          <button type="button" class="btn btn-small" id="slowFwdBtn" title="<?= $SLANG['StepForward'] ?>" class="unavail" disabled="disabled" onclick="streamSlowFwd( true )"/><i class="icon-forward"></i></button>
          <button type="button" class="btn btn-small" id="fastFwdBtn" title="<?= $SLANG['FastForward'] ?>" class="inactive" disabled="disabled" onclick="streamFastFwd( true )"/><i class="icon-fast-forward"></i></button>
          <button type="button" class="btn btn-small" id="zoomOutBtn" title="<?= $SLANG['ZoomOut'] ?>" class="avail" onclick="streamZoomOut()"/><i class="icon-zoom-out"></i></button>
          <button type="button" class="btn btn-small" id="nextBtn" title="<?= $SLANG['Next'] ?>" class="inactive" onclick="streamNext( true )"/><i class="icon-step-forward"></i></button>
        </p>
        <div id="replayStatus">
          <span id="mode">Mode: <span id="modeValue">&nbsp;</span></span>
          <span id="rate">Rate: <span id="rateValue"></span>x</span>
          <span id="progress">Progress: <span id="progressValue"></span>s</span>
          <span id="zoom">Zoom: <span id="zoomValue"></span>x</span>
        </div>
        <div id="progressBar" class="invisible">
<?php
        for ( $i = 0; $i < $panelSections; $i++ )
        {
?>
           <div class="progressBox" id="progressBox<?= $i ?>" title=""></div>
<?php
        }
?>
        </div>
      </div>
      <div id="eventStills" class="hidden">
        <div id="eventThumbsPanel">
          <div id="eventThumbs">
          </div>
        </div>
        <div id="eventImagePanel" class="hidden">
          <div id="eventImageFrame">
            <img id="eventImage" src="graphics/transparent.gif" alt=""/>
            <div id="eventImageBar">
              <div id="eventImageClose"><input type="button" class="btn btn-small btn-primary" value="<?= $SLANG['Close'] ?>" onclick="hideEventImage()"/></div>
              <div id="eventImageStats" class="hidden"><input type="button" class="btn btn-small btn-primary" value="<?= $SLANG['Stats'] ?>" onclick="showFrameStats()"/></div>
              <div id="eventImageData">Frame <span id="eventImageNo"></span></div>
            </div>
          </div>
        </div>
        <div id="eventImageNav">
          <div id="eventImageButtons">
            <div id="prevButtonsPanel">
              <input id="prevEventBtn" type="button" value="&lt;E" onclick="prevEvent()" disabled="disabled"/>
              <input id="prevThumbsBtn" type="button" value="&lt;&lt;" onclick="prevThumbs()" disabled="disabled"/>
              <input id="prevImageBtn" type="button" value="&lt;" onclick="prevImage()" disabled="disabled"/>
              <input id="nextImageBtn" type="button" value="&gt;" onclick="nextImage()" disabled="disabled"/>
              <input id="nextThumbsBtn" type="button" value="&gt;&gt;" onclick="nextThumbs()" disabled="disabled"/>
              <input id="nextEventBtn" type="button" value="E&gt;" onclick="nextEvent()" disabled="disabled"/>
            </div>
          </div>
          <div id="thumbsSliderPanel">
            <div id="thumbsSlider">
              <div id="thumbsKnob">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
