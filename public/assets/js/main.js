'use strict';

/* Defer the loading of font-awesome that slow down the site */
var link = document.createElement('link');
link.setAttribute('rel', 'stylesheet');
link.setAttribute('type', 'text/css');
link.setAttribute('href', window.location.origin + '/assets/css/font-awesome.min.css');
document.getElementsByTagName('head')[0].appendChild(link);

/* Go back in forms */
function goBack() {
    window.history.back();
}