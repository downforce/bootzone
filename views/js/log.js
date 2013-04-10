var logParms = "view=request&request=log&task=query";
var logReq = new Request.JSON( { url: thisUrl, method: 'post', timeout: AJAX_TIMEOUT, link: 'cancel', onSuccess: logResponse } );
var logTimer = undefined;
var logTable = undefined;

var logCodes = new Object({
    '0': 'INF',
    '-1': 'WAR',
    '-2': 'ERR',
    '-3': 'FAT',
    '-4': 'PNC',
});

var minSampleTime = 1000;
var maxSampleTime = 16000;
var minLogTime = 0;
var maxLogTime = 0;
var logCount = 0;
var maxLogFetch = 100;
var filter = {};
var logTimeout = maxSampleTime;
var firstLoad = true;
var initialDisplayLimit = 200;
var sortReversed = false;
var filterFields = [ 'Component', 'Pid', 'Level', 'File', 'Line'];
var options = {};

function buildFetchParms( parms )
{
    var fetchParms = logParms+'&limit='+maxLogFetch;
    if ( parms )
        fetchParms += '&'+parms;
    Object.each(filter,
        function( value, key )
        {
            fetchParms += '&filter['+key+']='+value;
        }
    );
    return( fetchParms );
}

function fetchNextLogs()
{
    logReq.send( buildFetchParms( 'minTime='+maxLogTime ) );
}

function fetchPrevLogs()
{
    logReq.send( buildFetchParms( 'maxTime='+minLogTime ) );
}

function logResponse( respObj )
{
    if ( logTimer )
        logTimer = clearTimeout( logTimer );

    if ( respObj.result == 'Ok' )
    {
        if ( respObj.logs.length > 0 )
        {
            logTimeout = minSampleTime;
            logCount += respObj.logs.length;
            try {
                respObj.logs.each(
                    function( log )
                    {
                        if ( !maxLogTime || log.TimeKey > maxLogTime )
                            maxLogTime = log.TimeKey;
                        if ( !minLogTime || log.TimeKey < minLogTime )
                            minLogTime = log.TimeKey;
                        var row = logTable.push( [ { content: log.DateTime, properties: { style: 'white-space: nowrap' }}, log.Component, log.Pid, log.Code, log.Message, log.File, log.Line ] );
                        delete log.Message;
                        row.tr.store( 'log', log );
                        if ( log.Level <= -3 )
                            row.tr.addClass( 'log-fat' );
                        else if ( log.Level <= -2 )
                            row.tr.addClass( 'log-err' );
                        else if ( log.Level <= -1 )
                            row.tr.addClass( 'log-war' );
                        else if ( log.Level > 0 )
                            row.tr.addClass( 'log-dbg' );
                        if ( !firstLoad )
                        {
                            new Fx.Tween( row.tr, { duration: 10000, transition: Fx.Transitions.Sine } ).start( 'color', '#6495ED', '#000000' );
                        }
                    }
                );
                options = respObj.options;
                updateFilterSelectors();
                $('lastUpdate').set('text',respObj.updated);
                $('logState').set('text',respObj.state);
                $('logState').removeClass('ok');
                $('logState').removeClass('alert');
                $('logState').removeClass('alarm');
                $('logState').addClass(respObj.state);
                $('totalLogs').set('text',respObj.total);
                $('availLogs').set('text',respObj.available);
                $('displayLogs').set('text',logCount);
                if ( firstLoad )
                {
                    if ( logCount < displayLimit )
                        fetchPrevLogs();
                }
                logTable.reSort();
            }
            catch( e )
            {
                console.error( e );
            }
            logTimeout /= 2;
            if ( logTimeout < minSampleTime )
                logTimeout = minSampleTime;
        }
        else
        {
            firstLoad = false;
            logTimeout *= 2;
            if ( logTimeout > maxSampleTime )
                logTimeout = maxSampleTime;
        }
    }
    logTimer = fetchNextLogs.delay( logTimeout );
} 

function refreshLog()
{
    options = {};
    logTable.empty();
    firstLoad = true;
    maxLogTime = 0;
    minLogTime = 0;
    logCount = 0;
    logTimeout = maxSampleTime;
    displayLimit = initialDisplayLimit;
    fetchNextLogs();
}

function expandLog()
{
    displayLimit += maxLogFetch;
    fetchPrevLogs();
}

function clearLog()
{
    logReq.cancel();
    minLogTime = 0;
    logCount = 0;
    logTimeout = maxSampleTime;
    displayLimit = initialDisplayLimit;
    $('displayLogs').set('text',logCount);
    options = {};
    logTable.empty();
}

function filterLog()
{
    filter = {};
    filterFields.each(
        function( field )
        {
            var selector = $('filter['+field+']');
            var value = selector.get('value');
            if ( value )
                filter[field] = value;
        }
    );
    refreshLog();
}

function resetLog()
{
    filter = {};
    refreshLog();
}

var exportFormValidator;

function exportLog()
{
    exportFormValidator.reset();
    $('exportLog').overlayShow();
}

function exportResponse( response )
{
    $('exportLog').unspin();
    if ( response.result == 'Ok' )
    {
        window.location.replace( thisUrl+'?view=request&request=log&task=download&key='+response.key+'&format='+response.format );
    }
}

function exportFail( request )
{
    $('exportLog').unspin();
    $('exportErrorText').set('text', request.status+" / "+request.statusText );
    $('exportError').show();
    Error( "Export request failed: "+request.status+" / "+request.statusText );
}

function exportRequest()
{
    var form = $('exportForm');
    $('exportErrorText').set('text', "" );
    $('exportError').hide();
    if ( form.validate() )
    {
        var exportParms = "view=request&request=log&task=export";
        var exportReq = new Request.JSON( { url: thisUrl, method: 'post', link: 'cancel', onSuccess: exportResponse, onFailure: exportFail } );
        var selection = form.getElement('input[name=selector]:checked').get('value');
        if ( selection == 'filter' || selection == 'current' )
        {
            $$('#filters select').each(
                function( select )
                {
                    exportParms += "&"+select.get('id')+"="+select.get('value');
                }
            );
        }
        if ( selection == 'current' )
        {
            var tbody = $(logTable).getElement( 'tbody' );
            var rows = tbody.getElements( 'tr' );
            if ( rows )
            {
                var minTime = rows[0].getElement('td').get('text');
                exportParms += "&minTime="+encodeURIComponent(minTime);
                var maxTime = rows[rows.length-1].getElement('td').get('text');
                exportParms += "&maxTime="+encodeURIComponent(maxTime);
            }
        }
        exportReq.send( exportParms+"&"+form.toQueryString() );
        $('exportLog').spin();
    }
}

function updateFilterSelectors()
{
    Object.each(options,
        function( values, key )
        {
            var selector = $('filter['+key+']');
            selector.options.length = 1;
            if ( key == 'Level' )
            {
                Object.each(values,
                    function( value, label )
                    {
                        selector.options[selector.options.length] = new Option( value, label );
                    }
                );
            }
            else
            {
                values.each(
                    function( value )
                    {
                        selector.options[selector.options.length] = new Option( value );
                    }
                );
            }
            if ( filter[key] )
                selector.set('value',filter[key]);
        }
    );
}

function initPage()
{
    displayLimit = initialDisplayLimit;
    for ( var i = 1; i <= 9; i++ )
        logCodes[''+i] = 'DB'+i;
    logTable = new HtmlTable( $('logTable'),
        {
            zebra: true,
            sortable: true,
            sortReverse: true
        }
    );
    logTable.addEvent( 'sort', function( tbody, index )
        {
            var header = tbody.getParent( 'table' ).getElement( 'thead' );
            var columns = header.getElement( 'tr' ).getElements( 'th' );
            var column = columns[index];
            sortReversed = column.hasClass( 'table-th-sort-rev' );
            if ( logCount > displayLimit )
            {
                var rows = tbody.getElements( 'tr' );
                var startIndex;
                if ( sortReversed )
                    startIndex = displayLimit;
                else
                    startIndex = 0;;
                for ( var i = startIndex; logCount > displayLimit; i++ )
                {
                    rows[i].destroy();
                    logCount--;
                }
                $('displayLogs').set('text',logCount);
            }
        }
    );
    exportFormValidator = new Form.Validator.Inline($('exportForm'), {
        useTitles: true,
        warningPrefix: "",
        errorPrefix: ""
    });
    new Asset.css( "/css/spinner.css" );
    fetchNextLogs();
}

// Kick everything off
window.addEvent( 'domready', initPage );
