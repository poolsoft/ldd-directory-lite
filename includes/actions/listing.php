<?php

/**
 *
 */


function ld_action__listing( $listing ) {
    global $post;

    ld_bootstrap();

    wp_enqueue_style('font-awesome');

    wp_enqueue_style('bootflat');
    wp_enqueue_style('font-awesome');


    $terms = wp_get_post_terms($listing->ID, LDDLITE_TAX_CAT);
    if ( isset( $terms[0] ) ) {
        $term_link = add_query_arg(array(
            'show' => 'category',
            't'    => $terms[0]->slug,
        ) );
        $term_name = $terms[0]->name;
    }

    $tpl = ldd::tpl();

    $id = $listing->ID;
    $title = $listing->post_title;
    $meta = ld_get_listing_meta( $id );
        $address = $meta['address'];
        $website = $meta['website'];
        $email   = $meta['email'];
        $phone   = $meta['phone'];
    $social = ld_get_social( $id );

    if ( has_post_thumbnail( $id ) )
        $thumbnail = get_the_post_thumbnail( $id, 'directory-listing', array( 'class' => 'img-rounded' ) );
    else
        $thumbnail = '<img src="' . LDDLITE_URL . '/public/images/noimage.png" class="img-rounded">';

    $geocode = array(
        'lat'   => '',
        'lng'   => '',
    );

    if ( !empty( $meta['geocode'] ) ) {

        $get_address = 'http://maps.google.com/maps/api/geocode/json?address=' . $meta['geocode'] . '&sensor=false';
        $geocode = wp_remote_get( $get_address );

        $output = json_decode( $geocode['body'] );

        $geocode['lat'] = $output->results[0]->geometry->location->lat;
        $geocode['lng'] = $output->results[0]->geometry->location->lng;

    }



    $tpl->assign( 'header',     ld_get_page_header( 'category' ) );

    $tpl->assign( 'home',       remove_query_arg( array(
        'show',
        't',
    ) ) );


    $tpl->assign( 'id',         $id );
    $tpl->assign( 'title',      $title );

    $tpl->assign( 'term_link', $term_link );
    $tpl->assign( 'term_name', $term_name );

    $tpl->assign( 'thumbnail',  $thumbnail );

    $tpl->assign( 'address',    $address );
    $tpl->assign( 'website',    $website );
    $tpl->assign( 'phone',      $phone );

    $tpl->assign( 'social',     $social );

    $tpl->assign( 'google_maps', ld_use_google_maps() );
    $tpl->assign( 'geo', $geocode );
    $tpl->assign( 'description', wpautop( $listing->post_content ) );

    // Contact Form
    // @todo we're going to let people override this with Ninja Forms

    $contact_tpl = ldd::tpl();
    $contact_tpl->assign( 'id', $id );
    $contact_tpl->assign( 'form_action', admin_url( 'admin-ajax.php' ) );
    $contact_tpl->assign( 'nonce', wp_create_nonce( 'contact-form-nonce' ) );
    $tpl->assign( 'contact_form', $contact_tpl->draw( 'listing-contact', 1 ) );


    wp_enqueue_script( 'happy' );
    add_action( 'wp_footer', '_f_draw_modal' );
    function _f_draw_modal() {
        $listing = ldd::pull();

        $to = ld_get_listing_email( $listing->ID );
        if ( !$to )
            return;

        $modal = ldd::tpl();
        $modal->assign( 'to', $to );
        $modal->assign( 'ajaxurl', admin_url( 'admin-ajax.php' ) );
        $modal->assign( 'nonce', wp_create_nonce( 'contact-form-nonce' ) );

        $modal->draw( 'modal-contact' );
    }


    return $tpl->draw( 'listing', 1 );
}
