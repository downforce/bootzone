<?php
//
// ZoneMinder web option help view file, $Date: 2011-01-20 18:38:24 +0000 (Thu, 20 Jan 2011) $, $Revision: 3229 $
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

$optionHelpIndex = preg_replace( '/^ZM_/', '', $_REQUEST['option'] );
$optionHelpText = !empty($OLANG[$optionHelpIndex])?$OLANG[$optionHelpIndex]:$config[$_REQUEST['option']]['Help'];
$optionHelpText = validHtmlStr($optionHelpText);
$optionHelpText = preg_replace( "/~~/", "<br/>", $optionHelpText );

$focusWindow = true;

xhtmlHeaders(__FILE__, $SLANG['OptionHelp'] );
?>
<body>
  <div class="container-fluid">
    <div class="row-fluid">
      <h3><?= $SLANG['OptionHelp'] ?></h3>
    </div>
    <div class="row-fluid">
        <a href="#" class="btn btn-small btn-inverse" onclick="closeWindow();"><?= $SLANG['Close'] ?></a>
    </div>
    <div id="content" class="row-fluid">
      <h5><?= validHtmlStr($_REQUEST['option']) ?></h5>
      <p class="textblock"><?= $optionHelpText ?></p>
    </div>
  </div>
</body>
</html>
