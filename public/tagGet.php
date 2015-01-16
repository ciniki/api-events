<?php
//
// Description
// ===========
// This method will return the list of categories used in the events.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get the list from.
// 
// Returns
// -------
//
function ciniki_events_tagGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'),
        'tag_type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'),
        'tag_permalink'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Permalink'),
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
    $rc = ciniki_events_checkAccess($ciniki, $args['business_id'], 'ciniki.events.tagGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the settings for the events
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQueryDash');	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_event_settings', 
		'business_id', $args['business_id'], 'ciniki.events', 'settings', 
		"tag-" . $args['tag_type'] . '-' . $args['tag_permalink']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( isset($rc['settings']) ) {
		$settings = $rc['settings'];
	} else {
		$settings = array();
	}

	$details = array();
	foreach($settings as $setting => $value) {
		$setting = str_replace('tag-' . $args['tag_type'] . '-' . $args['tag_permalink'] . '-', '', $setting);
		$details[$setting] = $value;
	}
	
	return array('stat'=>'ok', 'details'=>$details);
}
?>
