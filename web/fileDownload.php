<?php
//
// Description
// ===========
// This function will return the file details and content so it can be sent to the client.
//
// Returns
// -------
//
function ciniki_events_web_fileDownload($ciniki, $tnid, $event_permalink, $file_permalink) {

    //
    // Get the file details
    //
    $strsql = "SELECT ciniki_event_files.id, "
        . "ciniki_event_files.name, "
        . "ciniki_event_files.permalink, "
        . "ciniki_event_files.extension, "
        . "ciniki_event_files.binary_content "
        . "FROM ciniki_events, ciniki_event_files "
        . "WHERE ciniki_events.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_events.permalink = '" . ciniki_core_dbQuote($ciniki, $event_permalink) . "' "
        . "AND ciniki_events.id = ciniki_event_files.event_id "
        . "AND (ciniki_events.flags&0x01) = 0x01 "
        . "AND ciniki_event_files.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND CONCAT_WS('.', ciniki_event_files.permalink, ciniki_event_files.extension) = '" . ciniki_core_dbQuote($ciniki, $file_permalink) . "' "
        . "AND (ciniki_event_files.webflags&0x01) = 0 "     // Make sure file is to be visible
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'noexist', 'err'=>array('code'=>'ciniki.events.62', 'msg'=>'Unable to find requested file'));
    }
    $rc['file']['filename'] = $rc['file']['name'] . '.' . $rc['file']['extension'];

    return array('stat'=>'ok', 'file'=>$rc['file']);
}
?>
