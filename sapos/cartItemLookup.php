<?php
//
// Description
// ===========
// This function will lookup an item that is being added to a shopping cart online.  This function
// has extra checks to make sure the requested item is available to the customer.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_events_sapos_cartItemLookup($ciniki, $tnid, $customer, $args) {

    if( !isset($args['object']) || $args['object'] == '' 
        || !isset($args['object_id']) || $args['object_id'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.43', 'msg'=>'No event specified.'));
    }

    //
    // Lookup the requested event if specified along with a price_id
    //
    if( $args['object'] == 'ciniki.events.event' && isset($args['price_id']) && $args['price_id'] > 0 ) {
        $strsql = "SELECT ciniki_events.id AS event_id, "
            . "ciniki_events.name AS description, "
            . "ciniki_events.reg_flags, "
            . "ciniki_events.num_tickets, "
            . "ciniki_event_prices.id AS price_id, "
            . "ciniki_event_prices.name AS price_name, "
            . "ciniki_event_prices.available_to, "
            . "ciniki_event_prices.unit_amount, "
            . "ciniki_event_prices.unit_discount_amount, "
            . "ciniki_event_prices.unit_discount_percentage, "
            . "ciniki_event_prices.unit_donation_amount, "
            . "ciniki_event_prices.taxtype_id, "
            . "ciniki_event_prices.webflags, "
            . "ciniki_event_prices.num_tickets AS price_num_tickets "
            . "FROM ciniki_event_prices "
            . "LEFT JOIN ciniki_events ON ("
                . "ciniki_event_prices.event_id = ciniki_events.id "
                . "AND ciniki_events.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND ciniki_events.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
                . ") "
            . "WHERE ciniki_event_prices.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_event_prices.id = '" . ciniki_core_dbQuote($ciniki, $args['price_id']) . "' "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
        $rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
            array('container'=>'events', 'fname'=>'event_id',
                'fields'=>array('event_id', 'price_id', 'price_name', 'description', 'reg_flags', 'num_tickets', 
                    'available_to', 'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 'unit_donation_amount', 
                    'taxtype_id', 'webflags', 'price_num_tickets',
                    )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( !isset($rc['events']) || count($rc['events']) < 1 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.44', 'msg'=>'No event found.'));      
        }
        $item = array_pop($rc['events']);
        if( isset($item['price_name']) && $item['price_name'] != '' ) {
            $item['description'] .= ' - ' . $item['price_name'];
        }

        //
        // Check the available_to is correct for the specified customer
        //
        if( ($item['available_to']|0xF0) > 0 ) {
            if( ($item['available_to']&$customer['price_flags']) == 0 ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.45', 'msg'=>"I'm sorry, but this product is not available to you."));
            }
        }

        $item['flags'] = 0x20;

        //
        // Specify this item has a donation portion
        //
        if( $item['unit_donation_amount'] > 0 ) {
            $item['flags'] |= 0x0800;
        }
    
        //
        // Check the number of seats remaining
        //
        $item['tickets_sold'] = 0;
        $strsql = "SELECT 'num_tickets', SUM(num_tickets) AS num_tickets "
            . "FROM ciniki_event_registrations "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_event_registrations.event_id = '" . ciniki_core_dbQuote($ciniki, $item['event_id']) . "' "
            . "";
        //
        // Only get registrations for this price if price specific limits
        //
        if( ($item['webflags']&0x80) == 0x80 ) { 
            $strsql .= "AND ciniki_event_registrations.price_id = '" . ciniki_core_dbQuote($ciniki, $item['price_id']) . "' ";
            $item['num_tickets'] = $item['price_num_tickets'];
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.events', 'num');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['num']['num_tickets']) ) {
            $item['tickets_sold'] = $rc['num']['num_tickets'];
        }
        $item['units_available'] = $item['num_tickets'] - $item['tickets_sold'];
        $item['limited_units'] = 'yes';
        
        //
        // Individual Ticket
        //
        if( ($item['webflags']&0x02) > 0 ) {
            $item['limited_units'] = 'yes';
            $item['units_available'] = 1;
            $item['flags'] |= 0x08;
        }
        // Mapped ticket to image
        if( ($item['webflags']&0x08) == 0x08 ) {
            $item['limited_units'] = 'yes';
            $item['units_available'] = 1;
            $item['flags'] |= 0x88;
        }
        // Check if ticket is sold out
        if( ($item['webflags']&0x04) == 0x04 ) {
            $item['limited_units'] = 'yes';
            $item['units_available'] = 0;
        }

        return array('stat'=>'ok', 'item'=>$item);
    }

    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.events.46', 'msg'=>'No event specified.'));
}
?>
