/**
 * Copyright (c) 2016-present, Facebook, Inc.
 * All rights reserved.
 *
 * This source code is licensed under the BSD-style license found in the
 * LICENSE file in the root directory of this source tree. An additional grant
 * of patent rights can be found in the PATENTS file in the code directory.
 */
'use strict';

var React = require('./react');

var IEOverlay = function () {
    var Overlay = React.createClass({
        displayName: 'Overlay',

        render: function render() {
            var overLayStyles = {
                width: '600px',
                height: '150px',
                position: 'relative',
                top: '50%',
                left: '50%',
                marginTop: '-75px',
                marginLeft: '-300px',
                backgroundColor: 'white',
                textAlign: 'center',
                fontFamily: 'helvetica, arial, sans-serif',
                zIndex: '11'
            };

            var h1Styles = {
                fontSize: '24px',
                lineHeight: '28px',
                color: '#141823',
                fontWeight: 'normal',
                paddingTop: '44px'
            };

            var h2Styles = {
                fontSize: '14px',
                lineHeight: '20px',
                color: '#9197a3',
                fontWeight: 'normal'
            };

            return React.createElement(
                'div',
                { style: overLayStyles, id: 'ieOverlay' },
                React.createElement(
                    'h1',
                    { style: h1Styles },
                    'Internet Explorer Is Not Supported'
                ),
                React.createElement(
                    'h2',
                    { style: h2Styles },
                    'Please use a modern browser such as Google Chrome or Mozilla Firefox'
                )
            );
        }
    });

    return {
        render: function render() {
            var containerId = 'page:main-container';
            var containerEl = document.getElementById(containerId);
            containerEl.style.position = 'relative';

            var ieContainer = document.createElement('div');
            ieContainer.id = 'ie-container';

            ieContainer.style.width = '100%';
            ieContainer.style.height = '100%';
            ieContainer.style.position = 'absolute';
            ieContainer.style.top = '0';
            ieContainer.style.left = '0';
            ieContainer.style.backgroundColor = 'rgba(0,0,0,0.3)';

            containerEl.appendChild(ieContainer);
            ReactDOM.render(React.createElement(Overlay, null), ieContainer);
        }
    };
}();

module.exports = IEOverlay;
