<?php
$titles = collect($titles);

if (isset($page) && $page > 1) {
    $titles->prepend($titles->shift()." (Page: {$page})");
}
?>
<title>{{ $titles->implode(' - ') }} | {{ wp_option('name') }}</title>
