jQuery(function($) {

    // todo keep username/email, so people are not so doubtful of web3 sign-in.
    // todo plugin setting: hide username/email input field. Warning, users may be able to spam the form...
    // todo plugin setting: hide password input field. This will introduce users to web3 as a powerful passwordless login.

    // hide unnecessary fields.
    $('#loginform #user_login').closest('p').remove();
    $('#loginform .user-pass-wrap').remove();
    $('#loginform #wp-submit').remove();
    $('#loginform .forgetmenot').remove();

    // todo replace with popup multi-wallet connect.
    // Check if MetaMask is installed
    if (typeof window.ethereum !== 'undefined') {
        init();
    } else {
        // todo use better error output
        window.alert('Please install MetaMask first.');
    }

    $(document).on('click', '.sc-1hmbv05-0', function() {
        $('.sc-jajvtp-0').fadeOut(250);
    });
    $(document).on('click', '.js-c-wp_web3-signIn', function() {
        $('.sc-jajvtp-0').fadeIn(250);
    });
    $(document).on('click', '#connect-METAMASK', function() {
        $('.sc-14xbiak-0').show();
        $(this).hide();
        maybeSignIn();
    });

    // prevent default sign-in
    // todo replace with popup multi-wallet connect.
    // todo allow plugin option for password fallback, if user doesn't have metamask installed.
    // $(document).on('submit', '#loginform', function(event) {
    //     event.preventDefault();
    // });

    function init() {

        if (typeof wp_web3_login.pluginurl === 'undefined') {
            throw new TypeError('wp_web3_login.pluginurl is not defined');
        }

        // create sign-in
        // todo allow translation button text.
        $('#loginform').append(`
            <div style="display: block; clear: both;"></div>
            <div style="text-align: center; margin-top: 1rem;">
                <button type="button" class="button button-secondary button-hero hide-if-no-js js-c-wp_web3-signIn">Connect to a wallet</button>
            </div>

            <div class="sc-jajvtp-0 llYYyh" data-reach-dialog-overlay="" style="opacity: 1; display: none;">
                <div aria-modal="true" role="dialog" tabindex="-1" aria-label="dialog" class="sc-jajvtp-1 cTUQSm"
                    data-reach-dialog-content="">
                    <div class="sc-1hmbv05-2 krQIyi">
                        <div class="sc-1hmbv05-5 ftSGZx">
                            <div class="sc-1hmbv05-0 ddnRyg"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                                    viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" class="sc-1hmbv05-1 bQFFsG">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg></div>
                            <div class="sc-1hmbv05-3 gvzZBq">
                                <div class="sc-1hmbv05-7 egLPnp">Connect to a wallet</div>
                            </div>
                            <div class="sc-1hmbv05-4 cYHWmo">
                                <div class="sc-1hmbv05-6 eYlBaO">
                                    <button id="connect-METAMASK"
                                        class="sc-us24id-0 sc-us24id-1 sc-us24id-3 kUkPXg PBzB fAwvab">
                                        <div class="sc-us24id-2 jHqHqW">
                                            <div color="#E8831D" class="sc-us24id-6 cwjrNn">MetaMask</div>
                                        </div>
                                        <div class="sc-us24id-8 koEAFT"><img src="${wp_web3_login.pluginurl}static/media/metamask.02e3ec27.png" alt="Icon">
                                        </div>
                                    </button>
                                    <div class="sc-14xbiak-0 gtyZtN" style="display: none;">
                                        <div class="sc-14xbiak-2 goEtRe">
                                            <div class="sc-14xbiak-5 iRbKAI"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"
                                                    class="sc-x3cipg-0 iOprLv sc-14xbiak-1 kBRrmU">
                                                    <path
                                                        d="M12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12C22 9.27455 20.9097 6.80375 19.1414 5"
                                                        stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"></path>
                                                </svg>Initializing...</div>
                                        </div>
                                        <button class="sc-us24id-0 sc-us24id-1 sc-us24id-3 kUkPXg PBzB fPlLvh">
                                            <div class="sc-us24id-2 jHqHqW">
                                                <div color="#E8831D" class="sc-us24id-6 cwjrNn">MetaMask</div>
                                                <div class="sc-us24id-7 fgFWsd">Easy-to-use browser extension.</div>
                                            </div>
                                            <div class="sc-us24id-8 koEAFT"><img src="${wp_web3_login.pluginurl}static/media/metamask.02e3ec27.png" alt="Icon"></div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);

        // todo set button loading icon state

        if (typeof window.ethereum !== 'undefined') {

            $(document).on('click', '.js-c-wp_web3-signIn', function(event) {
                event.preventDefault();
                // todo replace with popup multi-wallet connect.
                // maybeSignIn();

            });

            $(document).on('submit', '#loginform', function(event) {
                event.preventDefault();
                // todo replace with popup multi-wallet connect.
                // maybeSignIn();
            });
    
        }

    }

    function maybeSignIn() {
        let publicAddress, formData;
        
        // todo replace with popup multi-wallet connect.
        try {

            let currentAccount = null;

            // Request account access if needed
            async function requestPublicAddress() {
                return publicAddress = await window.ethereum.request({ method: 'eth_accounts' });
            }

            requestPublicAddress()
                .then(handleAccountsChanged)
                .then(function() {

                formData = {
                    public_address: publicAddress,
                };

                // todo ajax
                $.post(
                    wp_web3_login.ajaxurl, {
                        action: 'wp_web3',
                        _ajax_nonce: wp_web3_login.nonce,
                        data: formData,
                    },
                    function (response) {
                        if (!response.success) {
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

            function handleAccountsChanged(accounts) {
                if (accounts.length === 0) {
                    // MetaMask is locked or the user has not connected any accounts
                    console.log('Please connect to MetaMask.');
                } else if (accounts[0] !== currentAccount) {
                    currentAccount = accounts[0];
                    // Do any other work!
                }
            }

        } catch (error) {
            window.alert(error);
            return;
        }
    }

});