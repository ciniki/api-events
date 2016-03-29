<?php
//
// Description
// ===========
// This function completes the event registration when the customer has submitted a payment and checkout cart.
//
// Arguments
// =========
// 
// Returns
// =======
//
function ciniki_events_sapos_cartItemPaymentReceived($ciniki, $business_id, $customer, $args) {

	if( !isset($args['object']) || $args['object'] == '' 
		|| !isset($args['object_id']) || $args['object_id'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3216', 'msg'=>'No event specified.'));
	}

	if( !isset($args['price_id']) || $args['price_id'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3217', 'msg'=>'No event specified.'));
	}
	if( !isset($args['invoice_id']) || $args['invoice_id'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3219', 'msg'=>'No event specified.'));
	}

    if( $args['object'] == 'ciniki.events.event' ) {
        //
        // Get the event details
        //
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
			. "ciniki_event_prices.taxtype_id "
			. "FROM ciniki_event_prices "
			. "LEFT JOIN ciniki_events ON ("
				. "ciniki_event_prices.event_id = ciniki_events.id "
				. "AND ciniki_events.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
				. "AND ciniki_events.id = '" . ciniki_core_dbQuote($ciniki, $args['object_id']) . "' "
				. ") "
            . "WHERE ciniki_event_prices.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND ciniki_event_prices.id = '" . ciniki_core_dbQuote($ciniki, $args['price_id']) . "' "
            . "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryIDTree');
		$rc = ciniki_core_dbHashQueryIDTree($ciniki, $strsql, 'ciniki.products', array(
			array('container'=>'events', 'fname'=>'event_id',
				'fields'=>array('event_id', 'price_id', 'price_name', 'description', 'reg_flags', 'num_tickets', 
					'available_to', 'unit_amount', 'unit_discount_amount', 'unit_discount_percentage', 'taxtype_id',
                    )),
			));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['events']) || count($rc['events']) < 1 ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'3218', 'msg'=>'No event found.'));		
		}
		$event = array_pop($rc['events']);
        
		//
		// Create the registration for the customer
		//
		$reg_args = array('event_id'=>$event['event_id'],
			'customer_id'=>$args['customer_id'],
			'num_tickets'=>(isset($args['quantity'])?$args['quantity']:1),
			'invoice_id'=>$args['invoice_id'],
			'customer_notes'=>'',
			'notes'=>'',
			);
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
		$rc = ciniki_core_objectAdd($ciniki, $business_id, 'ciniki.events.registration', $reg_args, 0x04);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		$reg_id = $rc['id'];
        
        return array('stat'=>'ok', 'object'=>'ciniki.events.registration', 'object_id'=>$reg_id);
    }

	return array('stat'=>'ok');
}
?>
