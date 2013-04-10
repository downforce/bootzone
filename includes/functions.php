<?php
//
// ZoneMinder web function library, $Date: 2008-07-08 16:06:45 +0100 (Tue, 08 Jul 2008) $, $Revision: 2484 $
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

function xhtmlHeaders( $file, $title )
{
    $skinCssFile = getSkinFile( 'css/skin.css' );
    $skinCssPhpFile = getSkinFile( 'css/skin.css.php' );
    $skinJsFile = getSkinFile( 'js/skin.js' );
    $skinJsPhpFile = getSkinFile( 'js/skin.js.php' );

    $basename = basename( $file, '.php' );
    $viewCssFile = getSkinFile( 'views/css/'.$basename.'.css' );
    $viewCssPhpFile = getSkinFile( 'views/css/'.$basename.'.css.php' );
    $viewJsFile = getSkinFile( 'views/js/'.$basename.'.js' );
    $viewJsPhpFile = getSkinFile( 'views/js/'.$basename.'.js.php' );

    // Bootstrap CSS/JS
    $skinJQueryFile = getSkinFile( 'js/jquery-1.9.1.min.js' );
    $skinCssBootFile = getSkinFile( 'css/bootstrap-min.css' );
    $skinCssBootRFile = getSkinFile( 'css/bootstrap-responsive-min.css' );
    $skinJsBootFile = getSkinFile( 'js/bootstrap-min.js' );
    extract( $GLOBALS, EXTR_OVERWRITE );
?>
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title><?= ZM_WEB_TITLE_PREFIX ?> - <?= validHtmlStr($title) ?></title>
  <link rel="icon" type="image/ico" href="graphics/favicon.ico"/>
  <link rel="shortcut icon" href="graphics/favicon.ico"/>
  <!-- <link rel="stylesheet" href="css/reset.css" type="text/css"/> -->
  <!-- <link rel="stylesheet" href="css/overlay.css" type="text/css"/> -->
  <!-- <link rel="stylesheet" href="<?= $skinCssFile ?>" type="text/css" media="screen"/> -->
  <!-- <link rel="stylesheet" href="<?= $skinCssPhpFile ?>" type="text/css" media="screen"/> -->
  <link rel="stylesheet" href="<?= $skinCssBootFile ?>" type="text/css" media="screen"/>
  <link rel="stylesheet" href="<?= $skinCssBootRFile ?>" type="text/css" media="screen"/>
<?php 
  if ( $viewCssFile ) { 
?>
  <link rel="stylesheet" href="<?= $viewCssFile ?>" type="text/css" media="screen"/>
<?php } 
  if ( $viewCssPhpFile ) { 
?>
  <style type="text/css">
  /*<![CDATA[*/
<?php
         require_once( $viewCssPhpFile );
?>
  /*]]>*/
  </style>
<?php
    }
?>
  <script type="text/javascript" src="/javascript/mootools/mootools-core-nc.js"></script>
  <script type="text/javascript" src="/javascript/mootools/mootools-more-nc.js"></script>
  <script type="text/javascript" src="js/logger.js"></script>
  <script type="text/javascript" src="js/overlay.js"></script>
<?php
    if ( $skinJsPhpFile )
    {
?>
  <script type="text/javascript">
  //<![CDATA[
  <!--
<?php
    require_once( $skinJsPhpFile );
?>
  //-->
  //]]>
  </script>
<?php
    }
    if ( $viewJsPhpFile )
    {
?>
  <script type="text/javascript">
  //<![CDATA[
  <!--
<?php
        require_once( $viewJsPhpFile );
?>
  //-->
  //]]>
  </script>
<?php
    }
?>
  <script type="text/javascript" src="<?= $skinJsFile ?>"></script>
  <script type="text/javascript" src="<?= $skinJsBootFile ?>"></script>
<?php
    if ( $viewJsFile )
    {
?>
  <script type="text/javascript" src="<?= $viewJsFile ?>"></script>
<?php
    }
?>
</head>
<?php
}

function getPaginationBoot( $pages, $page, $maxShortcuts, $query, $querySep='&amp;' )
{
    global $view;

    $pageText = "";
    if ( $pages > 1 )
    {
        if ( $page )
        {
            if ( $page < 0 )
                $page = 1;
            if ( $page > $pages )
                $page = $pages;

            if ( $page > 1 )
            {
                if ( false && $page > 2 )
                {
                    $pageText .= '<li><a href="?view='.$view.$querySep.'page=1'.$query.'">&lt;&lt;</a></li>';
                }
                $pageText .= '<li><a href="?view='.$view.$querySep.'page='.($page-1).$query.'">&lt;</a></li>';

                $newPages = array();
                $pagesUsed = array();
                $lo_exp = max(2,log($page-1)/log($maxShortcuts));
                for ( $i = 0; $i < $maxShortcuts; $i++ )
                {
                    $newPage = round($page-pow($lo_exp,$i));
                    if ( isset($pagesUsed[$newPage]) )
                        continue;
                    if ( $newPage <= 1 )
                        break;
                    $pagesUsed[$newPage] = true;
                    array_unshift( $newPages, $newPage );
                }
                if ( !isset($pagesUsed[1]) )
                    array_unshift( $newPages, 1 );

                foreach ( $newPages as $newPage )
                {
                    $pageText .= '<li><a href="?view='.$view.$querySep.'page='.$newPage.$query.'">'.$newPage.'</a>&nbsp;</li>';
                }

            }
            $pageText .= '<li class="active"><a href="#">'.$page.'</a></li>';
            if ( $page < $pages )
            {
                $newPages = array();
                $pagesUsed = array();
                $hi_exp = max(2,log($pages-$page)/log($maxShortcuts));
                for ( $i = 0; $i < $maxShortcuts; $i++ )
                {
                    $newPage = round($page+pow($hi_exp,$i));
                    if ( isset($pagesUsed[$newPage]) )
                        continue;
                    if ( $newPage > $pages )
                        break;
                    $pagesUsed[$newPage] = true;
                    array_push( $newPages, $newPage );
                }
                if ( !isset($pagesUsed[$pages]) )
                    array_push( $newPages, $pages );

                foreach ( $newPages as $newPage )
                {
                    $pageText .= '&nbsp;<li><a href="?view='.$view.$querySep.'page='.$newPage.$query.'">'.$newPage.'</a></li>';
                }
                $pageText .= '<li><a href="?view='.$view.$querySep.'page='.($page+1).$query.'">&gt;</a></li>';
                if ( false && $page < ($pages-1) )
                {
                    $pageText .= '<li><a href="?view='.$view.$querySep.'page='.$pages.$query.'">&gt;&gt;</a></li>';
                }
            }
        }
    }
    return( $pageText );
}

function sortTagBoot( $field ) {
    if ( $_REQUEST['sort_field'] == $field )
        if ( $_REQUEST['sort_asc'] )
            return( "<i class='icon-arrow-up'></i>" );
        else
            return( "<i class='icon-arrow-down'></i>" );
    return( false );
}


?>

