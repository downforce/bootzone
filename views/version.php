<?php
//
// ZoneMinder web version view file, $Date: 2011-05-25 10:15:14 +0100 (Wed, 25 May 2011) $, $Revision: 3364 $
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

if ( !canEdit( 'System' ) )
{
    $view = "error";
    return;
}
$options = array(
    "go" => $SLANG['GoToZoneMinder']
);

if ( verNum( ZM_DYN_CURR_VERSION ) != verNum( ZM_DYN_LAST_VERSION ) )
{
    $options = array_merge( $options, array(
        "ignore" => $SLANG['VersionIgnore'],
        "hour"   => $SLANG['VersionRemindHour'],
        "day"    => $SLANG['VersionRemindDay'],
        "week"   => $SLANG['VersionRemindWeek'],
        "never"  => $SLANG['VersionRemindNever']
    ) );
}

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['Version'] );
?>
<body>
  <div class="container-fluid">
    <div class="row-fluid">
      <h3><?= $SLANG['Version'] ?></h3>
    </div>
    <div id="content" class="row-fluid">
<?php
if ( ZM_DYN_DB_VERSION && (ZM_DYN_DB_VERSION != ZM_VERSION) )
{
?>
      <p class="text-error"><?= sprintf( $CLANG['VersionMismatch'], ZM_VERSION, ZM_DYN_DB_VERSION ) ?></p>
      <p><?= $SLANG['RunLocalUpdate'] ?></p>
      <input type="button" class="btn btn-inverse btn-small" value="<?= $SLANG['Close'] ?>" onclick="closeWindow()"/>
<?php
}
elseif ( verNum( ZM_DYN_LAST_VERSION ) <= verNum( ZM_VERSION ) )
{
?>
      <p><?= sprintf( $CLANG['RunningRecentVer'], ZM_VERSION ) ?></p>
      <p class="text-info"><?= $SLANG['UpdateNotNecessary'] ?></p>
      <input type="button" class="btn btn-info btn-small" value="<?= $SLANG['GoToZoneMinder'] ?>" onclick="zmWindow()"/>
      <input type="button" class="btn btn-inverse btn-small" value="<?= $SLANG['Close'] ?>" onclick="closeWindow()"/>
<?php
}
else
{
?>
      <form name="contentForm" id="contentForm" method="get" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="action" value="version"/>
        <p class="text-warning"><?= $SLANG['UpdateAvailable'] ?></p>
        <p class="text-warning"><?= sprintf( $CLANG['LatestRelease'], ZM_DYN_LAST_VERSION, ZM_VERSION ) ?></p>
        <p class="text-warning"><?= buildSelect( "option", $options ); ?></p>
        <input type="submit" class="btn btn-small btn-primary" value="<?= $SLANG['Apply'] ?>" onclick="submitForm( this )"/> 
        <input type="button" class="btn btn-small btn-inverse" value="<?= $SLANG['Close'] ?>" onclick="closeWindow()"/>
      </form>
<?php
}
?>
    </div>
  </div>
</body>
</html>
