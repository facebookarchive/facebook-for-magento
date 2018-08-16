/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */
'use strict';

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol ? "symbol" : typeof obj; };

var FAEFlowContainer = React.createClass({
  displayName: 'FAEFlowContainer',
  diaConfig: null,
  popupWindow: null,
  modalMessage: null,


  getInitialState: function getInitialState() {
    return {
      diaSettingId: window.facebookAdsExtensionConfig.diaSettingId,
      exceptionTrace:
        (Array.isArray(window.facebookAdsExtensionConfig.feedPrepared.samples)) ?
        null :
        window.facebookAdsExtensionConfig.feedPrepared.samples,
      showAdvancedOptions: false,
      showModal: false,
      upgradeUrl: false,
    };
  },
  bindMessageEvents: function bindMessageEvents(callback) {
    if (FBUtils.isIE() && window.MessageChannel) {
      // do nothing, wait for our messaging utils to be ready
    } else {
      window.addEventListener('message', function (event) {
        if (window.facebookAdsExtensionConfig.devEnv) {
          console.log('get event', event);
        }
        var origin = event.origin || event.originalEvent.origin;
        if (FBUtils.urlFromSameDomain(origin, window.facebookAdsExtensionConfig.popupOrigin)) {
          FBUtils.togglePopupOriginWeb(origin);
          callback && callback(event.data);
        }
      }, false);
    }
  },
  _showModal: function _showModal(msg) {
    if (msg && msg.trim().length > 0) {
      this.modalMessage = msg;
      this.setState({ showModal: true });
    }
  },

  // Read the Facebook Ads Extension Developer Doc for an overview of this
  // protocol.
  onEvent: function onEvent(evdata) {
    var _this = this;

    var evswitch = {
      'get dia settings': function getDiaSettings(params) {
        _this.sendDiaConfigToPopup();
      },

      'reset': function reset(params) {
        _this.setState({ diaSettingId: null });
        new Ajax.Request(window.facebookAdsExtensionAjax.setDiaSettingId, {
          parameters: {diaSettingId: 0},
          onSuccess: function onSuccess() {
            _this.ackToPopup('reset', params);
          },
          onFailure: function onFailure() {
            _this.failAckToPopup('reset', params);
          }
        });
      },

      'set merchant settings': function setMerchantSettings(params) {
        if (!params.setting_id) {
          console.error('Facebook Ads Extension Error: find no merchant settings', params);
          return;
        }
        var payload = { diaSettingId: params.setting_id };
        new Ajax.Request(window.facebookAdsExtensionAjax.setDiaSettingId, {
          parameters: {
            diaSettingId: payload.diaSettingId
          },
          onSuccess: function onSuccess() {
            _this.setState({ diaSettingId: payload.diaSettingId });
            _this.ackToPopup('set merchant settings', params);
          },
          onFailure: function onFailure() {
            _this.failAckToPopup('set merchant settings', params);
          }
        });
      },

      'set pixel': function setPixel(params) {
        if (!params.pixel_id) {
          console.error('Facebook Ads Extension Error: got no pixel_id', params);
          return;
        }
        new Ajax.Request(window.facebookAdsExtensionAjax.setPixelId, {
          parameters: {
            pixelId: params.pixel_id,
            pixelUsePii: params.pixel_use_pii
          },
          onSuccess: function onSuccess(transport) {
            var response = transport.responseText.evalJSON();
            var msg = '';
            if (response.success) {
              window.setCurrentPixelId = response.pixelid;
              msg = "The Facebook Pixel with ID: " + response.pixelId + " is now installed on your website.";
            } else {
              msg = "There was a problem saving the pixel. Please try again";
            }
            if (window.facebookAdsExtensionConfig.devEnv) {
              _this._showModal(msg);
            }
            _this.ackToPopup('set pixel', params);
          },
          onFailure: function onFailure() {
            _this.failAckToPopup('set pixel', params);
          }
        });
      },

      'set msger chat': function setMsgerChatSetup(params) {
        new Ajax.Request(window.facebookAdsExtensionAjax.setMsgerChatSetup, {
          parameters: params,
          onSuccess: function onSuccess(transport) {
            var response = transport.responseText.evalJSON();
            if (response.success) {
              _this.ackToPopup('msger chat', params);
            } else {
              _this.failAckToPopup('msger chat', params);
            }
          },
          onFailure: function onFailure() {
            _this.failAckToPopup('msger chat', params);
          }
        });
      },

      'gen feed': function genFeed(params) {
        new Ajax.Request(window.facebookAdsExtensionAjax.generateFeedNow, {
          parameters: {},
          onSuccess: function onSuccess(transport) {
            var response = transport.responseText.evalJSON();
            if (response.success) {
              _this.ackToPopup('feed', params);
            } else {
              _this.failAckToPopup('feed', params);
            }
          },
          onFailure: function onFailure() {
            _this.failAckToPopup('feed', params);
          }
        });
      }
    };

    if (evdata !== null && (typeof evdata === 'undefined' ? 'undefined' : _typeof(evdata)) === 'object' && evdata.type) {
      evswitch[evdata.type] && evswitch[evdata.type](evdata.params);
    } else {
      console.error('Facebook Ads Extension Error: get unsupport msg:', evdata);
    }
  },
  ackToPopup: function ackToPopup(type, params) {
    this.popupWindow.postMessage({
      type: 'ack ' + type,
      params: params
    }, window.facebookAdsExtensionConfig.popupOrigin);
  },
  failAckToPopup: function failAckToPopup(type, params) {
    this.popupWindow.postMessage({
      type: 'fail ' + type,
      params: params
    }, window.facebookAdsExtensionConfig.popupOrigin);
  },
  sendDiaConfigToPopup: function sendDiaConfigToPopup() {
    this.popupWindow.postMessage({
      type: 'dia settings',
      params: this.diaConfig
    }, window.facebookAdsExtensionConfig.popupOrigin);
  },
  openPopup: function openPopup() {
    if (!this.state.diaSettingId && window.facebookAdsExtensionConfig.feed.totalVisibleProducts < 10000) {
      new Ajax.Request(window.facebookAdsExtensionAjax.generateFeedNow, {
        parameters: {useCache : true},
        onSuccess: function onSuccess() {}
      });
    }

    var width = 1153;
    var height = 808;
    var topPos = screen.height / 2 - height / 2;
    var leftPos = screen.width / 2 - width / 2;
    var originParam = window.location.protocol + '//' + window.location.host;
    var popupUrl = window.facebookAdsExtensionConfig.popupOrigin;

    if (this.popupWindow) {
      this.popupWindow.close();
    }

    this.popupWindow = window.open(popupUrl + '?origin=' + encodeURIComponent(originParam) + (this.state.diaSettingId ? '&merchant_settings_id=' + this.state.diaSettingId : ''), 'DiaWizard', ['toolbar=no', 'location=no', 'directories=no', 'status=no', 'menubar=no', 'scrollbars=no', 'resizable=no', 'copyhistory=no', 'width=' + width, 'height=' + height, 'top=' + topPos, 'left=' + leftPos].join(','));
  },
  launchAutomatedAds: function launchAutomatedAds() {
    window.open(
      'https://www.facebook.com/ads/dia/redirect/?settings_id=' +
      this.state.diaSettingId + '&version=2',
      '_blank',
    );
  },
  launchDiaWizard: function launchDiaWizard() {
    this.diaConfig = { 'clientSetup': window.facebookAdsExtensionConfig };
    this.openPopup();
  },
  closeModal: function closeModal() {
    this.setState({ showModal: false });
  },
  componentDidMount: function componentDidMount() {
    var _this = this;
    this.bindMessageEvents(this.onEvent);
    new Ajax.Request(window.facebookAdsExtensionAjax.upgrade, {
      parameters: {},
      onSuccess: function onSuccess(result) {
        var response = result.responseJSON;
        console.log(result);
        if (response && response.upgrade_needed) {
          _this.setState({ upgradeUrl: response.url });
        }
      },
      onFailure: function onFailure(result) {
        console.log("Failed to reach Upgrade URL on GitHub", result);
      },
    });
  },
  selectorOnChange: function selectorOnChange() {
    var sel = document.getElementById('fbStoreSelector');
    var new_store_id = sel.options[sel.selectedIndex].value;

    // Submit a request to the controller to update the store id
    var loc = window.location.pathname + 'store_id/' + new_store_id + '/';

    // This isn't bound when ajax call returns
    var fbWindow = this;
    new Ajax.Request(window.facebookAdsExtensionAjax.setStoreId, {
      parameters: {
        storeId: new_store_id
      },
      onSuccess: function onSuccess(transport) {
        var response = transport.responseText.evalJSON();
        // Update product count in the popup
        window.facebookAdsExtensionConfig.feed.totalVisibleProducts =
          response.product_count;
        window.facebookAdsExtensionConfig.defaultStoreId = new_store_id;

        if (fbWindow) {
          fbWindow.sendDiaConfigToPopup();
          const params = {
            storeId: new_store_id
          }
          fbWindow.ackToPopup('set store id', params);
        }
      },
      onFailure: function onFailure(message) {
        if (fbWindow) {
          const failParams = {
            exception: message.transport.responseText,
            storeId: new_store_id
          }
          fbWindow.failAckToPopup('set store id', failParams);
        }
      }
    });

  },
  showAdvancedOptions: function showAdvancedOptions(e) {
    if (!this.state.showAdvancedOptions) {
      document.getElementById('fbAdvancedOptions').show();
    } else {
      document.getElementById('fbAdvancedOptions').hide();
    }
    this.setState({showAdvancedOptions: !this.state.showAdvancedOptions});
  },

  displayError: function displayError(error) {
    return React.createElement(
      'div',
      {className: 'fae-flow-container', style: {color: 'red'}},
      React.createElement('div', null),
      'Fatal exception when loading configuration. Please record the error given below, check the JS console for more errors, and launch debug mode in order to diagnose the problem. ',
      React.createElement('a', {href: window.facebookAdsExtensionAjax.debug}, 'Enter Debug Mode'),
      React.createElement('div', null),
      React.createElement('div', {padding : '4px'}),
      React.createElement(
        'div',
        {style: {color: 'black', padding: '200px 10px 10px 10px'}, align: 'left'},
        error,
      )
    );
  },

  render: function render() {
   try {

    var currentDiaSettingId = this.state.diaSettingId ? React.createElement(
      'h2',
      null,
      'Your store is connected to Facebook. Store ID: ',
      this.state.diaSettingId,
    ) : '';
    var loader = document.getElementById('fae-loader');
    if (loader) {
      loader.hidden = true;
    }

    // Add store options
    const options = [];
    const stores = JSON.parse(window.facebookAdsExtensionConfig.stores);
    const default_id = window.facebookAdsExtensionConfig.defaultStoreId

    Object.keys(stores).forEach(function(key, index) {
      var optionValues = { value: stores[key] };
      if (default_id === stores[key]) {
          optionValues.selected = "selected";
      }
      options.push(React.createElement("option", optionValues, key));
    });

    var storeSelector = React.createElement(
      'select',
      {id: 'fbStoreSelector', onChange: this.selectorOnChange},
      options
    );

    var advancedOptionsText = (this.state.showAdvancedOptions ? 'Hide' : 'Show') + ' Advanced Options';
    var advancedOptionsLink = React.createElement(
      'a',
      {onClick: this.showAdvancedOptions},
      advancedOptionsText
    );

    var advancedOptions = React.createElement(
      'div',
      {id: 'fbAdvancedOptions', style: {display: 'none'}},
      React.createElement(
        'h2',
        null,
        'Store Synced with Facebook'
      ),
      storeSelector,
      React.createElement(
        'h2',
        null,
        React.createElement(
          'a',
          {
            href: window.facebookAdsExtensionAjax.debug,
            title: 'Debug mode lets you view various Log files generated by the extension.',
          },
          'Launch Debug Mode'
        ),
      ),
      React.createElement('div', {style: {padding: '4px'}}),
    );

    var feedWritePermissionError = window.facebookAdsExtensionConfig.feedWritePermissionError;
    var modal = this.state.showModal ? React.createElement(FBModal, { onClose: this.closeModal, message: this.modalMessage }) : null;

    if (this.state.exceptionTrace !== null) {
      window.facebookAdsExtensionConfig.exception = this.state.exceptionTrace;
      return this.displayError(this.state.exceptionTrace);
    }
    var hrElement = React.createElement('hr', {className:'fae-hr'}, null);
    var upgradeNotice = (this.state.upgradeUrl) ?
      React.createElement(
        'div',
        { className: 'fae-upgrade-notice'},
        'A newer version of this plugin is available. To download it, ',
        React.createElement(
          'a',
          {
            href: this.state.upgradeUrl,
            target: '_blank'
          },
          'go to GitHub.',
        ),
      )
    : null;

    return React.createElement(
      'div',
      { className: 'fae-flow-container' },
      modal,
      React.createElement(
        'h1',
        null,
        (this.state.diaSettingId) ?
          'Reach the right people and sell more products' :
          'Grow your business on Facebook',
      ),
      React.createElement(
        'h2',
        null,
        (this.state.diaSettingId) ?
        React.createElement(
          'div',
          null,
          'Use this Magento and Facebook integration to:',
          React.createElement(
            'ul',
            null,
            React.createElement('li', null, 'Create an ad in a few steps'),
            React.createElement('li', null, 'Use built-in best practices for online sales'),
            React.createElement('li', null, 'Get reporting on sales and revenue'),
          ),
        )
        :
        React.createElement(
          'div',
          null,
          'Use this Magento and Facebook integration to:',
          React.createElement(
            'ul',
            null,
            React.createElement('li', null, 'Easily install a tracking pixel'),
            React.createElement('li', null, 'Upload your products and create a shop'),
            React.createElement('li', null, 'Create dynamic ads with your products and pixel'),
          ),
        ),
      ),
      (!feedWritePermissionError) ?
        React.createElement(
          'span',
          null,
          this.state.diaSettingId ?
            React.createElement(
              'button',
              { className: 'blue', onClick: this.launchAutomatedAds },
              'Create Ad',
            ) :
            React.createElement(
              'button',
              { className: 'blue', onClick: this.launchDiaWizard },
              'Get Started',
            ),
        ) : null,
      hrElement,
      React.createElement(
        'div',
        { className: 'settings', hidden: !this.state.diaSettingId},
        React.createElement(
          'h1',
          null,
          'Settings',
        ),
        upgradeNotice,
        currentDiaSettingId,
        React.createElement(
          'div',
          null,
            (this.state.diaSettingId) ? React.createElement(
              'button',
              { className: 'small', onClick: this.launchDiaWizard, title: 'Manage Settings'},
              'Settings'
            )
            :
            React.createElement(
              'h2',
              {style: {color: 'red'}},
              'Please enable write permissions in the ',
              feedWritePermissionError,
              ' directory to use this extension.'
            )
          ),
          hrElement,
      ),
      advancedOptionsLink,
      advancedOptions,
    );
   } catch (err) {
     console.log(err);
     return this.displayError(err.name + ' : ' + err.message + ' : ' + err.stack);
   }
  }
});

// Code to display the above container.
var displayFBModal = function displayFBModal() {
  if (FBUtils.isIE()) {
    IEOverlay().render();
  }
  var QueryString = function () {
    // This function is anonymous, is executed immediately and
    // the return value is assigned to QueryString!
    var query_string = {};
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i = 0; i < vars.length; i++) {
      var pair = vars[i].split("=");
      // If first entry with this name
      if (typeof query_string[pair[0]] === "undefined") {
        query_string[pair[0]] = decodeURIComponent(pair[1]);
        // If second entry with this name
      } else if (typeof query_string[pair[0]] === "string") {
        var arr = [query_string[pair[0]], decodeURIComponent(pair[1])];
        query_string[pair[0]] = arr;
        // If third or later entry with this name
      } else {
        query_string[pair[0]].push(decodeURIComponent(pair[1]));
      }
    }
    return query_string;
  }();
  if (QueryString.p) {
    window.facebookAdsExtensionConfig.popupOrigin = QueryString.p;
    window.facebookAdsExtensionConfig.devEnv = true;
  }

  // Render
  ReactDOM.render(
    React.createElement(FAEFlowContainer, null),
    document.getElementById('fae-flow')
  );

  // Backwards Compatibility warning for accidental multiple install.
  setTimeout(function(){
    if (window.facebookAdsToolboxConfig) {
        var warning = React.createElement(
          'div',
          {className: 'fae-flow-container', style: {color: 'red'}},
          React.createElement('div', null),
          'We have detected that you have two versions of the Facebook Ads Extension plugin installed. Please uninstall the older version: It should appear as "Facebook_Ads_Toolbox" in your Magento Connect Manager.',
          React.createElement('div', null)
        );
        var diaFlow = document.getElementById('dia-flow');
        diaFlow.removeChild(diaFlow.firstChild);
        ReactDOM.render(
          warning,
          diaFlow
        );
        ReactDOM.render(
          warning,
          document.getElementById('fae-flow')
        );
    }
  }, 500);
};

// Logic for when to display the container.
if (document.readyState === 'interactive') {
  // in case the document is already rendered
  displayFBModal();
} else if (document.addEventListener) {
  // modern browsers
  document.addEventListener('DOMContentLoaded', displayFBModal);
} else {
  document.attachEvent('onreadystatechange', function () {
    // IE <= 8
    if (document.readyState === 'complete') {
      displayFBModal();
    }
  });
}
