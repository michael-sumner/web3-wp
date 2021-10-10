jQuery(function($) {

    // todo keep username/email, so people are not so doubtful of ethereum sign-in.
    // todo plugin setting: hide username/email input field.

    // hide unnecessary fields.
    $('#loginform .user-pass-wrap').remove();
    $('#loginform #wp-submit').remove();
    $('#loginform .forgetmenot').remove();

    // Check if MetaMask is installed
    if (typeof window.ethereum !== 'undefined') {
        init();
    } else {
        // todo use better error output
        window.alert('Please install MetaMask first.');
    }

    // prevent default sign-in
    // todo allow plugin option for password fallback, if user doesn't have metamask installed.
    // $(document).on('submit', '#loginform', function(event) {
    //     event.preventDefault();
    // });

    function init() {

        // create sign-in
        // todo allow translation button text.
        $('#loginform #user_login').parent().append('<div style="text-align: center;"><button type="button" class="button button-secondary button-hero hide-if-no-js js-c-wp_eth-signIn">Sign in with Ethereum</button></div>');

        // todo set button loading icon state

        if (typeof window.ethereum !== 'undefined') {

            $(document).on('click', '.js-c-wp_eth-signIn', function(event) {
                event.preventDefault();
                maybeSignIn();

            });

            $(document).on('submit', '#loginform', function(event) {
                event.preventDefault();
                maybeSignIn();
            });
    
        }

    }

    function maybeSignIn() {
        let userLogin, publicAddress, formData;
        
        try {
            // Request account access if needed
            async function requestPublicAddress() {
                return publicAddress = await window.ethereum.request({ method: 'eth_requestAccounts' });
            }

            requestPublicAddress().then(function() {
                userLogin = $('#user_login').val();

                formData = {
                    public_address: publicAddress,
                    user_login: userLogin,
                };

                // todo ajax
                $.post(
                    wp_eth_login.ajaxurl, {
                        action: 'wp_eth_login',
                        _ajax_nonce: wp_eth_login.nonce,
                        data: formData,
                    },
                    function (response) {
                        if (!response.success) {
                            // todo display error output
                            window.alert(response.data);
                            window.location.reload(true);
                        }
                        if (!response.success) return;
                        if (!response.data.redirect_url) return;

                        // redirect without caching.
                        let redirectUrl = response.data.redirect_url;
                        window.location.href = redirectUrl;
        
                    }
                );
            });

        } catch (error) {
            window.alert('You need to allow MetaMask.');
            return;
        }
    }

});