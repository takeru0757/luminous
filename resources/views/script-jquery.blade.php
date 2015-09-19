@if (isset($v1))
<!--[if lt IE 9]><script src="https://ajax.googleapis.com/ajax/libs/jquery/{{ $v1 }}/jquery.min.js"></script><![endif]-->
@endif
<!--[if gte IE 9]><!-->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/{{ isset($v2) ? $v2 : '2.1.4' }}/jquery.min.js"></script>
<script>window.jQuery || document.write('<script src="{{ asset('vendor/jquery/jquery.min.js') }}"><\/script>')</script>
<!--<![endif]-->
