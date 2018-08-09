/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */
'use strict';

var FAEDebugContainer = React.createClass({
  displayName: 'FAEDebugContainer',
  diaConfig: null,
  popupWindow: null,
  modalMessage: null,

  getInitialState: function getInitialState() {
    return {
      sample_test : 'ready',
      sample_result : null,
      sample_error : false,
    };
  },

  render: function render() {
    const button = (this.state.sample_test == 'ready') ?
      React.createElement(
        'button',
        { className: 'long', onClick: this.ajaxsamples },
        'Generate Debug Log'
      ) :
      React.createElement(
        'div',
        null,
        React.createElement(
        'div',
          {className: 'fae-loader', id:'fae-loader'},
        ),
        'Running Test...',
      );

    const samples_result = (this.state.sample_result) ?
      React.createElement(
        'div',
        {className: (this.state.sample_error) ? 'red-text' : 'green-text'},
        JSON.stringify(this.state.sample_result),
      ) : null;

    const samples_error = (this.state.sample_error) ?
      React.createElement(
        'div',
        null,
        'Error discovered when fetching product samples.',
      ) : null;

    return React.createElement(
      'div',
      { className: 'fae-flow-container' },
      React.createElement(
        'h1',
        null,
        'Welcome to Debug Mode',
      ),
        'If you were redirected here, it is because the extension failed to load required'
        + ' configuration data from your store. Please make sure logging is enabled in Magento, then generate logs below and report the logs via GitHub to the developers.'
      ,
      React.createElement('div', null),
      React.createElement(
        'div',
        {className: 'debug-button-div'},
        button,
        this._logFileLink('debugmode'),
      ),
      React.createElement('div', null),
      samples_error,
      samples_result,
      'After running the test, send the log file and error generated above to the developers ',
      React.createElement(
        'a',
        {
          href: 'https://github.com/facebookincubator/facebook-for-magento/issues',
          target: '_blank',
        },
        'by creating a new issue on GitHub.',
      ),
      React.createElement('div', null),
      'Additional logs are provided below are for general debugging purposes. Consider including these in your issue report if they are not empty.',
      this._logFileLink('General'),
      this._logFileLink('exception'),
      this._logFileLink('feed'),
    );
  },

  _handleTimeout: function _handleTimeout() {
    if (this.state.sample_test == 'ready') {
      return;
    }
    console.log('timeout');
    this.setState({
      sample_state: 'ready',
      sample_result : 'Fatal Error. Check Log for Details.',
      sample_error: true,
    });
  },

  isValidSamples: function isValidSamples(samples) {
    if (!samples) return false;
    return samples.success;
  },

  ajaxsamples: function ajaxsamples() {
    var _this = this;
    if (!window.facebookAdsExtensionAjax || !window.facebookAdsExtensionAjax.debugAjax) {
      return;
    }
    this.setState({ sample_test : 'in_progress' });
    setTimeout(this._handleTimeout, 10000);

    new Ajax.Request(window.facebookAdsExtensionAjax.debugAjax, {
     parameters: {debugfeedsamples: 1},
     onSuccess: function onSuccess(result) {
       console.log('success');
       console.log(result);
       var err = !_this.isValidSamples(result.responseJSON);
       _this.setState({
         sample_test : 'ready',
         sample_result :  (!err) ?
            'No Issues Detected in Test.' : result.responseText,
         sample_error :  err,
       });

     },
     onFailure: function onFailure(result) {
       console.log('fail');
       console.log(result);
       _this.setState({
          sample_test : 'ready',
          sample_result :  result.responseText,
          sample_error :  true,
       });

     }
   });
  },

  _logFileLink: function _logFileLink(param) {
    if (!window.facebookAdsExtensionAjax || !window.facebookAdsExtensionAjax.debugAjax) {
      return React.createElement(
        'div',
        {},
        'Failed to Generate link to Log Files',
      );
    }

    return React.createElement(
      'div',
      {},
      React.createElement(
        'a',
        {
          href: window.facebookAdsExtensionAjax.debugAjax + '?logs=true&' + param + '=true',
          target: '_blank',
        },
        ((param == 'debugmode')? "Debug" : param) + ' Log'
      )
    );
  },
});


// Code to display the above container.
var displayFBModal = function displayFBModal() {
  ReactDOM.render(
    React.createElement(FAEDebugContainer, null),
    document.getElementById('fae-debug-flow')
  );
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
