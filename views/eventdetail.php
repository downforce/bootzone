<?php
//
// ZoneMinder web event detail view file, $Date: 2009-10-16 18:09:16 +0100 (Fri, 16 Oct 2009) $, $Revision: 2982 $
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

if ( !canEdit( 'Events' ) )
{
    $view = "error";
    return;
}
if ( isset($_REQUEST['eid']) )
{
    $mode = 'single';
    $eid = validInt($_REQUEST['eid']);
    $sql = "select E.* from Events as E where E.Id = '".dbEscape($eid)."'";
    $newEvent = dbFetchOne( $sql );
}
elseif ( isset($_REQUEST['eids']) )
{
    $mode = 'multi';
    $sql = "select E.* from Events as E where ";
    $sqlWhere = array();
    foreach ( $_REQUEST['eids'] as $eid )
    {
        $sqlWhere[] = "E.Id = '".dbEscape($eid)."'";
    }
    unset( $eid );
    $sql .= join( " or ", $sqlWhere );
    foreach( dbFetchAll( $sql ) as $row )
    {
        if ( !isset($newEvent) )
        {
            $newEvent = $row;
        }
        else
        {
            if ( $newEvent['Cause'] && $newEvent['Cause'] != $row['Cause'] )
                $newEvent['Cause'] = "";
            if ( $newEvent['Notes'] && $newEvent['Notes'] != $row['Notes'] )
                $newEvent['Notes'] = "";
        }
    }
}
else
{
    $mode = '';
}

$focusWindow = true;

if ( $mode == 'single' )
    xhtmlHeaders(__FILE__, $SLANG['Event']." - ".$eid );
else
    xhtmlHeaders(__FILE__, $SLANG['Events'] );
?>
<body>
  <div class="container-fluid">
    <div class="row-fluid">
<?php
if ( $mode == 'single' )
{
?>
      <h4><?= $SLANG['Event'] ?> <?= $eid ?></h4>
<?php
}
else
{
?>
      <h4><?= $SLANG['Events'] ?></h4>
<?php
}
?>
    </div>
    <div id="content" class="row-fluid">
      <form name="contentForm" id="contentForm" method="post" action="<?= $_SERVER['PHP_SELF'] ?>">
        <input type="hidden" name="view" value="none"/>
<?php
if ( $mode == 'single' )
{
?>
        <input type="hidden" name="view" value="<?= $view ?>"/>
        <input type="hidden" name="action" value="eventdetail"/>
        <input type="hidden" name="eid" value="<?= $eid ?>"/>
<?php
}
elseif ( $mode = 'multi' )
{
?>
        <input type="hidden" name="view" value="none"/>
        <input type="hidden" name="action" value="eventdetail"/>
<?php
    foreach ( $_REQUEST['eids'] as $eid )
    {
?>
        <input type="hidden" name="markEids[]" value="<?= validHtmlStr($eid) ?>"/>
<?php
    }
}
?>
        <table class="table table-condensed" cellspacing="0">
          <tbody>
            <tr>
              <th scope="row"><?= $SLANG['Cause'] ?></th>
              <td><input type="text" name="newEvent[Cause]" value="<?= validHtmlStr($newEvent['Cause']) ?>" size="32"/></td>
            </tr>
            <tr>
              <th scope="row"><?= $SLANG['Notes'] ?></th>
              <td><textarea name="newEvent[Notes]" rows="6" cols="50"><?= validHtmlStr($newEvent['Notes']) ?></textarea></td>
            </tr>
          </tbody>
        </table>
        <div class="row-fluid">
          <input type="submit" class="btn btn-small btn-success" value="<?= $SLANG['Save'] ?>"<?php if ( !canEdit( 'Events' ) ) { ?> disabled="disabled"<?php } ?>/> <input type="button" class="btn btn-small btn-inverse" value="<?= $SLANG['Cancel'] ?>" onclick="closeWindow()"/>
        </div>
      </form>
    </div>
  </div>
</body>
</html>
