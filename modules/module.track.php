<?php//add_action('inbound_store_lead_pre','wp_cta_set_conversion',20,1);/***  Increments the conversion count for a call to action variation*  *  @param ARRAY $data contains information related to the hook inbound_store_lead_pre which runs before we attempt to store lead*  *  */function wp_cta_set_conversion($data){	$raw_post_values = json_decode( stripslashes($data['form_input_values']) , true);	if (!isset($raw_post_values['wp_cta_id'])) {		return;	}		$cta_id = $raw_post_values['wp_cta_id'];	$vid = $raw_post_values['wp_cta_vid'];		$lp_conversions = get_post_meta( $cta_id , 'wp-cta-ab-variation-conversions-'.$vid, true );	$lp_conversions++;	update_post_meta(  $cta_id , 'wp-cta-ab-variation-conversions-'.$vid, $lp_conversions );	}