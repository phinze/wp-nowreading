<?php
/**
 * These filters are pretty self-explanatory. Comment them out or remove them with remove_filter() if you don't want them.
 */

add_filter('book_title', 'wptexturize');
add_filter('book_author', 'wptexturize');

add_filter('book_review', 'wptexturize');
add_filter('book_review', 'convert_smilies');
add_filter('book_review', 'convert_chars');
add_filter('book_review', 'wpautop');

add_filter('book_meta_key', 'wptexturize');

add_filter('book_meta_val', 'wptexturize');
add_filter('book_meta_val', 'wpautop');

?>
