<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$schema = tnt_get_tool_schema( $tool );

?>

<script type="application/ld+json">

<?php

echo wp_json_encode(

    $schema,

    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE |
    JSON_PRETTY_PRINT

);

?>

</script>