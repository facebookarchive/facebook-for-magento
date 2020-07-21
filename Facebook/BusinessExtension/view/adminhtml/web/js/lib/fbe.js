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
var ReactDOM = require('./react-dom');
var IEOverlay = require('./IEOverlay');
var FBModal = require('./Modal');
var FBUtils = require('./utils');

var jQuery = (function (jQuery) {
  if (jQuery && typeof jQuery === 'function') {
    return jQuery;
  } else {
    console.error('window.jQuery is not valid or loaded, please check your magento 2 installation!');
    // if jQuery is not there, we return a dummy jQuery obejct with ajax,
    // so it will not break our following code
    return {
      ajax: function () {
      }
    };
  }
})(window.jQuery);

var ajaxify = function (url) {
  return url + '?isAjax=true';
};

var ajaxParam = function (params) {
  if (window.FORM_KEY) {
    params.form_key = window.FORM_KEY;
  }
  return params;
};

var FBEFlowContainer = React.createClass({

  bindMessageEvents: function bindMessageEvents() {
    var _this = this;
    if (FBUtils.isIE() && window.MessageChannel) {
      // do nothing, wait for our messaging utils to be ready
    } else {
      window.addEventListener('message', function (event) {
        var origin = event.origin || event.originalEvent.origin;
        if (FBUtils.urlFromSameDomain(origin, window.facebookBusinessExtensionConfig.popupOrigin)) {
          // Make ajax calls to store data from fblogin and fb installs
          _this.consoleLog("Message from fblogin ");
          _this.saveFBLoginData(event.data);
        }
      }, false);
    }
  },
  saveFBLoginData: function saveFBLoginData(data) {
    var _this = this;
    if (data) {
      var responseObj = JSON.parse(data);
      _this.consoleLog("Response from fb login -- " + responseObj);
      var accessToken = responseObj.access_token;
      var success = responseObj.success;
      var pixelId = responseObj.pixel_id;
      var profiles = responseObj.profiles;
      var catalogId = responseObj.catalog_id;

      if(success) {
        let action = responseObj.action;
        if(action != null && action === 'delete') {
          // Delete asset ids stored in db instance.
          _this.consoleLog("Successfully uninstalled FBE");
          _this.deleteFBAssets();
        }else if(action != null && action === 'create') {
          _this.savePixelId(pixelId);
          _this.saveAccessToken(accessToken);
          _this.saveProfilesData(profiles);
          _this.pushFeed(accessToken, catalogId);
          _this.saveAAMSettings(pixelId);
        }
      }else {
        _this.consoleLog("No response received after setup");
      }
    }
  },
  savePixelId: function savePixelId(pixelId) {
    var _this = this;
    if (!pixelId) {
      console.error('Facebook Business Extension Error: got no pixel_id');
      return;
    }
    jQuery.ajax({
      type: 'post',
      url: ajaxify(window.facebookBusinessExtensionConfig.setPixelId),
      data: ajaxParam({
        pixelId: pixelId,
      }),
      success: function onSuccess(data, _textStatus, _jqXHR) {
        var response = data;
        let msg = '';
        if (response.success) {
          _this.setState({pixelId: response.pixelId});
          msg = "The Facebook Pixel with ID: " + response.pixelId + " is now installed on your website.";
        } else {
          msg = "There was a problem saving the pixel. Please try again";
        }
        _this.consoleLog(msg);
        location.reload();
      },
      error: function () {
        console.error('There was a problem saving the pixel with id', pixelId);
      }
    });
  },
  saveAccessToken: function saveAccessToken(accessToken) {
    var _this = this;
    if (!accessToken) {
      console.error('Facebook Business Extension Error: got no access token');
      return;
    }
    jQuery.ajax({
      type: 'post',
      url: ajaxify(window.facebookBusinessExtensionConfig.setAccessToken),
      data: ajaxParam({
        accessToken: accessToken,
      }),
      success: function onSuccess(data, _textStatus, _jqXHR) {
        _this.consoleLog('Access token saved successfully');
      },
      error: function () {
        console.error('There was an error saving access token');
      }
    });
  },
  saveProfilesData: function saveProfilesData(profiles) {
    var _this = this;
    if (!profiles) {
      console.error('Facebook Business Extension Error: got no profiles data');
      return;
    }
    jQuery.ajax({
      type: 'post',
      url: ajaxify(window.facebookBusinessExtensionConfig.setProfilesData),
      data: ajaxParam({
        profiles: JSON.stringify(profiles),
      }),
      success: function onSuccess(data, _textStatus, _jqXHR) {
        _this.consoleLog('set profiles data ' +  data.profiles);
      },
      error: function () {
        console.error('There was problem saving profiles data', profiles);
      }
    });
  },
  saveAAMSettings: function saveAAMSettings(pixelId){
    var _this = this;
    jQuery.ajax({
      'type': 'post',
      url: ajaxify(window.facebookBusinessExtensionConfig.setAAMSettings),
      data: ajaxParam({
        pixelId: pixelId,
      }),
      success: function onSuccess(data, _textStatus, _jqXHR) {
        if(data.success){
          _this.consoleLog('AAM settings successfully saved '+data.settings);
        }
        else{
          _this.consoleLog('AAM settings could not be read for the given pixel');
        }
      },
      error: function (){
        _this.consoleLog('There was an error retrieving AAM settings');
      }
    });
  },
  pushFeed: function pushFeed(accessToken, catalogId) {
    var _this = this;
    if (!accessToken) {
      console.error('Facebook Business Extension Error: got no access token to push product feed');
      return;
    }
    jQuery.ajax({
      type: 'post',
      url: ajaxify(window.facebookBusinessExtensionConfig.setFeedPush),
      data: ajaxParam({
        accessToken: accessToken,
        externalBusinessId: window.facebookBusinessExtensionConfig.externalBusinessId,
        catalogId: catalogId,
      }),
      success: function onSuccess(data, _textStatus, _jqXHR) {
        if(data.success) {
          _this.consoleLog('Feed push successful');
        }
      },
      error: function() {
        console.error('There was problem pushing data feed');
      }
    });
  },
  deleteFBAssets: function deleteFBAssets() {
    var _this = this;
    jQuery.ajax({
      type: 'delete',
      url: ajaxify(window.facebookBusinessExtensionConfig.deleteConfigKeys),
      success: function onSuccess(data, _textStatus, _jqXHR) {
        let msg = '';
        if(data.success) {
          msg = data.message;
        }else {
          msg = data.error_message;
        }
        _this.consoleLog(msg);
        location.reload();
      },
      error: function() {
        console.error('There was a problem deleting the connection, Please try again.');
      }
    });
  },
  componentDidMount: function componentDidMount() {
    this.bindMessageEvents();
  },
  consoleLog: function consoleLog(message) {
    if(window.facebookBusinessExtensionConfig.debug) {
      console.log(message);
    }
  },
  queryParams: function queryParams() {
    return 'app_id='+window.facebookBusinessExtensionConfig.appId +
            '&timezone='+window.facebookBusinessExtensionConfig.timeZone+
            '&external_business_id='+window.facebookBusinessExtensionConfig.externalBusinessId+
            '&installed='+window.facebookBusinessExtensionConfig.installed+
            '&system_user_name='+window.facebookBusinessExtensionConfig.systemUserName+
            '&business_vertical='+window.facebookBusinessExtensionConfig.businessVertical+
            '&version='+window.facebookBusinessExtensionConfig.version+
            '&currency='+ window.facebookBusinessExtensionConfig.currency +
            '&business_name='+ window.facebookBusinessExtensionConfig.businessName;
  },
  render: function render() {
    var _this = this;
    try {
      _this.consoleLog("query params --"+_this.queryParams());
      return React.createElement(
        'iframe',
        {
          src:window.facebookBusinessExtensionConfig.fbeLoginUrl + _this.queryParams(),
          style: {border:'none',width:'1100px',height:'700px'}
        }
      );
    } catch (err) {
      console.error(err);
    }
  }
});

// Render
ReactDOM.render(
  React.createElement(FBEFlowContainer, null),
  document.getElementById('fbe-iframe')
);

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
    window.facebookBusinessExtensionConfig.popupOrigin = QueryString.p;
  }
};

(function main() {
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
})();
