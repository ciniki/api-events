<?php
//
// Description
// ===========
// This method will return all the information about an event.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business the event is attached to.
// event_id:		The ID of the event to get the details for.
// 
// Returns
// -------
// <event id="419" name="Event Name" url="http://myevent.com" 
//		description="Event description" start_date="July 18, 2012" end_date="July 19, 2012"
//		date_added="2012-07-19 03:08:05" last_updated="2012-07-19 03:08:05" />
//
function ciniki_events_eventGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'event_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Event'), 
		'images'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Images'),
		'files'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Files'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'events', 'private', 'checkAccess');
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.eventGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	$strsql = "SELECT ciniki_events.id, "
		. "ciniki_events.name, "
		. "ciniki_events.permalink, "
		. "ciniki_events.url, "
		. "ciniki_events.description, "
		. "ciniki_events.num_tickets, "
		. "ciniki_events.reg_flags, "
		. "DATE_FORMAT(ciniki_events.start_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS start_date, "
		. "DATE_FORMAT(ciniki_events.end_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS end_date, "
		. "ciniki_events.primary_image_id, "
		. "ciniki_events.long_description ";
	if( isset($args['images']) && $args['images'] == 'yes' ) {
		$strsql .= ", "
			. "ciniki_event_images.id AS img_id, "
			. "ciniki_event_images.name AS image_name, "
			. "ciniki_event_images.webflags AS image_webflags, "
			. "ciniki_event_images.image_id, "
			. "ciniki_event_images.description AS image_description, "
			. "ciniki_event_images.url AS image_url "
			. "";
	}
	$strsql .= "FROM ciniki_events ";
	if( isset($args['images']) && $args['images'] == 'yes' ) {
		$strsql .= "LEFT JOIN ciniki_event_images ON (ciniki_events.id = ciniki_event_images.event_id "
			. "AND ciniki_event_images.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. ") ";
	}
	$strsql .= "WHERE ciniki_events.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_events.id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
		. "";
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	if( isset($args['images']) && $args['images'] == 'yes' ) {
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
			array('container'=>'events', 'fname'=>'id', 'name'=>'event',
				'fields'=>array('id', 'name', 'permalink', 'url', 'primary_image_id', 
					'start_date', 'end_date', 'description', 'num_tickets', 'reg_flags', 'long_description')),
			array('container'=>'images', 'fname'=>'img_id', 'name'=>'image',
				'fields'=>array('id'=>'img_id', 'name'=>'image_name', 'webflags'=>'image_webflags',
					'image_id', 'description'=>'image_description', 'url'=>'image_url')),
		));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['events']) || !isset($rc['events'][0]) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1330', 'msg'=>'Unable to find event'));
		}
		$event = $rc['events'][0]['event'];
		ciniki_core_loadMethod($ciniki, 'ciniki', 'images', 'private', 'loadCacheThumbnail');
		if( isset($event['images']) ) {
			foreach($event['images'] as $img_id => $img) {
				if( isset($img['image']['image_id']) && $img['image']['image_id'] > 0 ) {
					$rc = ciniki_images_loadCacheThumbnail($ciniki, $img['image']['image_id'], 75);
					if( $rc['stat'] != 'ok' ) {
						return $rc;
					}
					$event['images'][$img_id]['image']['image_data'] = 'data:image/jpg;base64,' . base64_encode($rc['image']);
				}
			}
		}
	} else {
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
			array('container'=>'events', 'fname'=>'id', 'name'=>'event',
				'fields'=>array('id', 'name', 'permalink', 'url', 'primary_image_id', 
					'start_date', 'end_date', 'description', 'num_tickets', 'reg_flags', 'long_description')),
		));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( !isset($rc['events']) || !isset($rc['events'][0]) ) {
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1331', 'msg'=>'Unable to find event'));
		}
		$event = $rc['events'][0]['event'];
	}
	
	//
	// Check how many registrations
	//
	if( ($event['reg_flags']&0x03) > 0 ) {
		$event['tickets_sold'] = 0;
		$strsql = "SELECT 'num_tickets', SUM(num_tickets) AS num_tickets "	
			. "FROM ciniki_event_registrations "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_event_registrations.event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
		$rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.events', 'num');
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['num']['num_tickets']) ) {
			$event['tickets_sold'] = $rc['num']['num_tickets'];
		}
	}

	//
	// Get any files if requested
	//
	if( isset($args['files']) && $args['files'] == 'yes' ) {
		$strsql = "SELECT id, name, extension, permalink "
			. "FROM ciniki_event_files "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND ciniki_event_files.event_id = '" . ciniki_core_dbQuote($ciniki, $args['event_id']) . "' "
			. "";
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.events', array(
			array('container'=>'files', 'fname'=>'id', 'name'=>'file',
				'fields'=>array('id', 'name', 'extension', 'permalink')),
		));
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
		if( isset($rc['files']) ) {
			$event['files'] = $rc['files'];
		}
	}

	return array('stat'=>'ok', 'event'=>$event);
}
?>