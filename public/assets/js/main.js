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

/* Event listener for page poster selection */
var inputPoster = document.getElementById('input-locandina');
var filenamePoster = document.getElementById('locandina-filename');

if (inputPoster !== null) {
    inputPoster.addEventListener('change', function () {
        filenamePoster.textContent = inputPoster.value;
    });
}

/* Remove free web hosting div */
var divCount = document.getElementsByTagName('div').length - 1;
var hostingSpamDiv = document.getElementsByTagName('div')[divCount];

if (hostingSpamDiv.innerHTML.indexOf('000') !== -1) {
    hostingSpamDiv.remove();
}
