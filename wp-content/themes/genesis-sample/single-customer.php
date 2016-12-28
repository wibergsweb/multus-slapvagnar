<?php
get_header();
$post_id = get_the_ID();
echo do_shortcode( '[pdfcrowd_generate debug_mode="no" create_downloadlink="yes" out_files="foljesedel2" overwrite_pdf="yes" convert_urls="{22}" data_postid="' . $post_id . '" data_fields="acf_customer_phone;acf_customer_name;acf_customer_trailers" data_acfkeys="field_585b0870afcf6;field_585b064d43bec;field_585b0b3288d2e" link_titles="Följesedel kund"]');
get_footer();
?>
