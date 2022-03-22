"use strict";

/**
 * Example JavaScript code that interacts with the page and Web3 wallets
 */

// Unpkg imports
const Web3Modal = window.Web3Modal.default;
const evmChains = window.evmChains;

// Web3modal instance
let web3Modal;

// Chosen wallet provider given by the dialog window
let provider;


// Address of the selected account
let selectedAccount;


/**
 * Setup the orchestra
 */
function init() {
  // Check that the web page is run in a secure context,
  // as otherwise MetaMask won't be available
  if (location.protocol !== 'https:') {
    // https://ethereum.stackexchange.com/a/62217/620
    const alert = document.querySelector("#alert-error-https");
    alert.style.display = "block";
    document.querySelector("#btn-connect").setAttribute("disabled", "disabled")
    return;
  }

  // check if metamask exists
  // todo not everyone uses metamask, so make this optional in later release.
  if (typeof window.ethereum === 'undefined') {
    alert("Please install the MetaMask.io browser extension to log into this website");
    return;
  }

  // Tell Web3modal what providers we have available.
  // Built-in web browser provider (only one can exist as a time)
  // like MetaMask, Brave or Opera is added automatically by Web3modal
  const providerOptions = {};

  web3Modal = new Web3Modal({
    cacheProvider: false, // optional
    providerOptions, // required
    disableInjectedProvider: false, // optional. For MetaMask / Brave / Opera.
    theme: "dark",
    showQRCode: true,
  });



  // remove unnecessary fields.

  // remove #loginform #user_login closest p
  if (document.querySelector("#loginform #user_login") !== null) {
    document.querySelector("#loginform #user_login").closest('p').remove();
  }
  // remove #loginform .user-pass-wrap
  if (document.querySelector("#loginform .user-pass-wrap") !== null) {
    document.querySelector("#loginform .user-pass-wrap").remove();
  }

  // remove #loginform #wp-submit
  if (document.querySelector("#loginform #wp-submit") !== null) {
    document.querySelector("#loginform #wp-submit").remove();
  }

  // remove #loginform .forgetmenot
  if (document.querySelector("#loginform .forgetmenot") !== null) {
    document.querySelector("#loginform .forgetmenot").remove();
  }

  document.querySelector("#connected").style.display = "none";

  document.querySelector("#prepare").style.display = "block";
}


/**
 * Kick in the UI action after Web3modal dialog has chosen a provider
 */
async function fetchAccountData() {

  let formData = {};

  function handleError(error) {
    window.alert(error);
    window.location.reload(true);
    throw error;
  }

  try {
    // Get a Web3 instance for the wallet
    const web3 = new Web3(provider);



    // Get connected chain id from Ethereum node
    const chainId = await web3.eth.getChainId();
    // Load chain information over an HTTP API
    const chainData = evmChains.getChain(chainId);

    // todo add to database


    // Get list of accounts of the connected wallet
    const accounts = await web3.eth.getAccounts();

    // MetaMask does not give you all accounts, only the selected account

    selectedAccount = accounts[0];

    // todo add to database



    // Display fully loaded UI for wallet data
    document.querySelector("#prepare").style.display = "none";
    document.querySelector("#connected").style.display = "block";

    // log the user in

    formData = {
      public_address: selectedAccount,
    };

    jQuery.post(
      web3_wp_login.ajaxurl, {
        action: 'web3_wp',
        _ajax_nonce: web3_wp_login.nonce,
        data: formData,
      },
      function (response) {
        if (!response.success) {
          handleError(response.data);
        }
        if (!response.success) return;
        if (!response.data.redirect_url) return;

        // redirect without caching.
        let redirectUrl = response.data.redirect_url;
        window.location.href = redirectUrl;

      }
    );

  } catch (error) {
    handleError(error);
  }

}



/**
 * Fetch account data for UI when
 * - User switches accounts in wallet
 * - User switches networks in wallet
 * - User connects wallet initially
 */
async function refreshAccountData() {

  // If any current data is displayed when
  // the user is switching acounts in the wallet
  // immediate hide this data
  document.querySelector("#connected").style.display = "none";
  document.querySelector("#prepare").style.display = "block";


  // remove unnecessary fields.

  // remove #loginform #user_login closest p
  if (document.querySelector("#loginform #user_login") !== null) {
    document.querySelector("#loginform #user_login").closest('p').remove();
  }
  // remove #loginform .user-pass-wrap
  if (document.querySelector("#loginform .user-pass-wrap") !== null) {
    document.querySelector("#loginform .user-pass-wrap").remove();
  }

  // remove #loginform #wp-submit
  if (document.querySelector("#loginform #wp-submit") !== null) {
    document.querySelector("#loginform #wp-submit").remove();
  }

  // remove #loginform .forgetmenot
  if (document.querySelector("#loginform .forgetmenot") !== null) {
    document.querySelector("#loginform .forgetmenot").remove();
  }

  // Disable button while UI is loading.
  // fetchAccountData() will take a while as it communicates
  // with Ethereum node via JSON-RPC and loads chain data
  // over an API call.
  document.querySelector("#btn-connect").setAttribute("disabled", "disabled")
  await fetchAccountData(provider);
  document.querySelector("#btn-connect").removeAttribute("disabled")
}


/**
 * Connect wallet button pressed.
 */
async function onConnect(event) {

  event.preventDefault();


  try {
    provider = await web3Modal.connect();
  } catch (e) {

    return;
  }

  // Subscribe to accounts change
  provider.on("accountsChanged", (accounts) => {
    fetchAccountData();
  });

  // Subscribe to chainId change
  provider.on("chainChanged", (chainId) => {
    fetchAccountData();
  });

  // Subscribe to networkId change
  provider.on("networkChanged", (networkId) => {
    fetchAccountData();
  });

  await refreshAccountData();
}

/**
 * Disconnect wallet button pressed.
 */
async function onDisconnect(event) {

  event.preventDefault();



  // TODO: Which providers have close method?
  if (provider.close) {
    await provider.close();

    // If the cached provider is not cleared,
    // WalletConnect will default to the existing session
    // and does not allow to re-scan the QR code with a new wallet.
    // Depending on your use case you may want or want not his behavir.
    await web3Modal.clearCachedProvider();
    provider = null;
  }

  selectedAccount = null;

  // Set the UI back to the initial state
  document.querySelector("#prepare").style.display = "block";
  document.querySelector("#connected").style.display = "none";


  // remove unnecessary fields.

  // remove #loginform #user_login closest p
  if (document.querySelector("#loginform #user_login") !== null) {
    document.querySelector("#loginform #user_login").closest('p').remove();
  }
  // remove #loginform .user-pass-wrap
  if (document.querySelector("#loginform .user-pass-wrap") !== null) {
    document.querySelector("#loginform .user-pass-wrap").remove();
  }

  // remove #loginform #wp-submit
  if (document.querySelector("#loginform #wp-submit") !== null) {
    document.querySelector("#loginform #wp-submit").remove();
  }

  // remove #loginform .forgetmenot
  if (document.querySelector("#loginform .forgetmenot") !== null) {
    document.querySelector("#loginform .forgetmenot").remove();
  }


}


/**
 * Main entry point.
 */
window.addEventListener('load', async () => {
  init();
  document.querySelector("#btn-connect").addEventListener("click", onConnect);
  document.querySelector("#btn-disconnect").addEventListener("click", onDisconnect);
});