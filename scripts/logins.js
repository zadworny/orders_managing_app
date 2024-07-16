$(document).ready(function() {
    
    $('#forgotPasswordLink').click(function(e) {
        e.preventDefault();
        $('#loginForm, #notification').hide();
        $('#resetForm').show();
    });

    $('.backtoLoginLink').click(function(e) {
        e.preventDefault();
        $('#resetForm, #newpassForm, #notification').hide();
        $('#loginForm').show();
    });

    // AJAX submit for login form
    let failedAttempts = 0;
    $('#loginForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: 'auth.php',
            data: $(this).serialize(),
            success: function(response) {
                //console.log(response);
                var jsonResponse = JSON.parse(response);
                //console.log(jsonResponse);
                if (jsonResponse.success) {
                    $('#notification').css({"background-color": "#d7f8da","border": "1px solid #c6f5cb"}).html(jsonResponse.message).show();
                    failedAttempts = 0; // Reset counter on successful login
                    window.location.href = '../index.php'; // Redirect on successful login
                } else {
                    failedAttempts++;
                    if (failedAttempts >= 3) {
                        $('#captchaInput').val('').attr('type', 'text');
                        $('#captchaSection').show();
                    }
                    $('#notification').css({"background-color": "#f8d7da","border": "1px solid #f5c6cb"}).html(jsonResponse.message).show();
                }
            }
        });
    });

    // AJAX submit for email form
    $('#resetForm').submit(function(e) {
        e.preventDefault();
        $('#notification').css({"background-color": "#f8f8d7","border": "1px solid #f5f5c6"}).html("Wysyłanie...").show();
        $.ajax({
            type: 'POST',
            url: 'email.php',
            data: $(this).serialize(),
            success: function(response) {
                console.log(response);
                var jsonResponse = JSON.parse(response); // Parse the JSON response from the server
                console.log(jsonResponse);
                if (jsonResponse.success) {
                    $('#notification').css({"background-color": "#d7f8da","border": "1px solid #c6f5cb"}).html("Email wysłano").show();
                } else {
                    $('#notification').css({"background-color": "#f8d7da","border": "1px solid #f5c6cb"}).html(jsonResponse.error ? jsonResponse.error : "Błąd wysyłania").show();
                }
            },
            error: function() {
                $('#notification').css({"background-color": "#f8d7da","border": "1px solid #f5c6cb"}).html("Błąd wysyłania").show();
            }
        });
    });

    // AJAX submit for reset form
    $('#newpassForm').submit(function(e) {
        e.preventDefault();
        $.ajax({
            type: 'POST',
            url: 'reset.php',
            data: $(this).serialize(),
            success: function(response) {
                console.log(response);
                var jsonResponse = JSON.parse(response);
                //console.log(jsonResponse);
                if (jsonResponse.success) {
                    //$('#notification').css({"background-color": "#d7f8da","border": "1px solid #c6f5cb"}).html(jsonResponse.message).show();
                    window.location.href = '../login/index.php?pass=changed'; // Redirect on successful login
                } else {
                    $('#notification').css({"background-color": "#f8d7da","border": "1px solid #f5c6cb"}).html(jsonResponse.message).show();
                }
            }
        });
    });

    $('#newPassword').keyup(function(){
        let password = $(this).val();
        var confirmPassword = $('#confirmPassword').val();
        let strength = 0;
        let clower = cupper = cnumbs = cspec = false;

        // If password contains lowercase letters, increase strength
        if (password.match(/[a-z]/)) {
            strength += 1;
            clower = true;
        }
        // If password contains uppercase letters, increase strength
        if (password.match(/[A-Z]/)) {
            strength += 1;
            cupper = true;
        }
        // If password contains numbers, increase strength
        if (password.match(/[0-9]/)) {
            strength += 1;
            cnumbs = true;
        }
        // If password contains special characters, increase strength
        if (password.match(/[!@#$%^&*(),.?":{}|<>]/)) {
            strength += 1;
            cspec = true;
        }
        // If password length is greater than or equal to 8, increase strength
        if (password.length >= 8 && clower && cupper && cnumbs && cspec) {
            strength += 1;
            $('#confirmPassword').prop('disabled', false);
        } else {
            $('#confirmPassword').prop('disabled', true);
            $('#submit-btn').removeClass('enabled').prop('disabled', true);
        }

        // Update password strength indicator
        switch(strength) {
            case 0:
                $('#password-strength-indicator').removeClass();
                break;
            case 1:
            case 2:
                $('#password-strength-indicator').removeClass().addClass('strength-weak');
                break;
            case 3:
            case 4:
                $('#password-strength-indicator').removeClass().addClass('strength-medium');
                break;
            case 5:
                $('#password-strength-indicator').removeClass().addClass('strength-strong');
                break;
        }

        // Check if confirmPassword matches password
        if (confirmPassword === password) {
            $('#submit-btn').addClass('enabled').prop('disabled', false);
        } else {
            $('#submit-btn').removeClass('enabled').prop('disabled', true);
        }
    });

    // Check confirmPassword on keyup
    $('#confirmPassword').keyup(function(){
        var password = $('#newPassword').val();
        var confirmPassword = $(this).val();

        if (confirmPassword === password) {
            $('#submit-btn').addClass('enabled').prop('disabled', false);
        } else {
            $('#submit-btn').removeClass('enabled').prop('disabled', true);
        }
    });
});

function refreshCaptcha() {
    var captchaImage = document.getElementById('captchaImage');
    captchaImage.src = 'captcha.php?' + Date.now();
}