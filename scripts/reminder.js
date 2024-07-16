$(document).ready(function() {

    $('input:first').focus();

    let timer = null;
    let eventBound = false;

    // Function to check form visibility and bind/unbind event handlers
    function adjustEventHandlers() {
        const formIsVisible = $('#recordForm:visible').length > 0; //form
        testing = formIsVisible;

        if (formIsVisible && !eventBound) {
            $(document).on('input click keypress', resetTimer);
            eventBound = true;
        } else if (!formIsVisible) { // && eventBound
            $(document).off('input click keypress', resetTimer);
            clearTimeout(timer);
            eventBound = false;
        }
    }

    adjustEventHandlers();
    setInterval(adjustEventHandlers, 1000);

    let audioSource = null;
    let audioContext = new (window.AudioContext || window.webkitAudioContext)();

    function playSound(url) {
        stopSound();
        fetch(url)
            .then(response => response.arrayBuffer())
            .then(arrayBuffer => audioContext.decodeAudioData(arrayBuffer))
            .then(audioBuffer => {
                const source = audioContext.createBufferSource();
                source.buffer = audioBuffer;
                source.loop = true; // Set the loop property to true
                source.connect(audioContext.destination);
                source.start(0);
                audioSource = source;
            })
            .catch(e => console.error("Error playing audio:", e));
    }

    function stopSound() {
        if (audioSource) {
            audioSource.stop();
            audioSource = null;
        }
    }

    function resetTimer() {
        clearTimeout(timer);
        stopSound();
        // Ignore for the green saving screen
        if ($('#notification img.blink').length > 0) {
            $('#notification, .notification_bg').hide();
        }
        timer = setTimeout(() => {
            // Ignore for the green saving screen
            if ($('#notification img.blink').length > 0) {
                $('#notification').html('<img class="blink" src="images/sound.png"><p>Kliknij aby dokończyć wypełnianie formularza</p>');
                $('.notification_bg').css('background-color', '#C00');
                $('#notification, .notification_bg').show();
                playSound('sounds/reminder.mp3');
            }
        }, 60 * 1000); // X seconds * milliseconds
    }
});