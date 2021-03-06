<?php
//
// Description
// ===========
// This method will be called whenever a item is updated in an invoice.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_events_sapos_itemDelete($ciniki, $tnid, $invoice_id, $item) {

    //
    // An event was added to an invoice item, get the details and see if we need to 
    // create a registration for this event
    //
    if( isset($item['object']) && $item['object'] == 'ciniki.events.registration' && isset($item['object_id']) ) {
        //
        // Check the event registration exists
        //
        $strsql = "SELECT id, uuid, event_id, customer_id, num_tickets "
            . "FROM ciniki_event_registrations "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $item['object_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.events', 'registration');
        if( $rc['stat'] != 'ok' ) { 
            return $rc;
        }
        if( !isset($rc['registration']) ) {
            // Don't worry if can't find existing reg, probably database error
            return array('stat'=>'ok');
        }
        $registration = $rc['registration'];

        //
        // Delete the registration
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
        $rc = ciniki_core_objectDelete($ciniki, $tnid, 'ciniki.events.registration', $registration['id'], $registration['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        return array('stat'=>'ok');
    }

    return array('stat'=>'ok');
}
?>
