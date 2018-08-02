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
    return {};
  },

  render: function render() {
    return React.createElement(
      'div',
      { className: 'fae-flow-container' },
      React.createElement(
        'h1',
        null,
        'Welcome to Debug Mode',
      ),
      'Debug mode is under development. Please ',
      React.createElement(
        'a',
        {
          href: 'https://github.com/facebookincubator/facebook-for-magento/issues',
          target: '_blank',
        },
        'file an issue on GitHub',
      ),
      ' and include the log files below (if nonempty) in your issue.',
      this._logFileLink('General'),
      this._logFileLink('exception'),
      this._logFileLink('store'),
      this._logFileLink('feed'),
      this._logFileLink('store_verify'),
    );
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
        param + ' Log'
      )
    );
  }
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
