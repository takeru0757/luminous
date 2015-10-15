import $ from 'jquery';
import attachFastClick from 'fastclick';

// https://api.jquery.com/jquery-2/
console.log('jQuery version: ' + $.fn.jquery);

// https://github.com/ftlabs/fastclick
$( () => attachFastClick(document.body) );
