/****************************************************************************
**
** Copyright (C) 2016 The Qt Company Ltd.
** Copyright (C) 2016 Klarälvdalens Datakonsult AB, a KDAB Group company, info@kdab.com, author Milian Wolff <milian.wolff@kdab.com>
** Contact: https://www.qt.io/licensing/
**
** This file is part of the QtWebChannel module of the Qt Toolkit.
**
** $QT_BEGIN_LICENSE:LGPL$
** Commercial License Usage
** Licensees holding valid commercial Qt licenses may use this file in
** accordance with the commercial license agreement provided with the
** Software or, alternatively, in accordance with the terms contained in
** a written agreement between you and The Qt Company. For licensing terms
** and conditions see https://www.qt.io/terms-conditions. For further
** information use the contact form at https://www.qt.io/contact-us.
**
** GNU Lesser General Public License Usage
** Alternatively, this file may be used under the terms of the GNU Lesser
** General Public License version 3 as published by the Free Software
** Foundation and appearing in the file LICENSE.LGPL3 included in the
** packaging of this file. Please review the following information to
** ensure the GNU Lesser General Public License version 3 requirements
** will be met: https://www.gnu.org/licenses/lgpl-3.0.html.
**
** GNU General Public License Usage
** Alternatively, this file may be used under the terms of the GNU
** General Public License version 2.0 or (at your option) the GNU General
** Public license version 3 or any later version approved by the KDE Free
** Qt Foundation. The licenses are as published by the Free Software
** Foundation and appearing in the file LICENSE.GPL2 and LICENSE.GPL3
** included in the packaging of this file. Please review the following
** information to ensure the GNU General Public License requirements will
** be met: https://www.gnu.org/licenses/gpl-2.0.html and
** https://www.gnu.org/licenses/gpl-3.0.html.
**
** $QT_END_LICENSE$
**
****************************************************************************/

"use strict";

var QWebChannelMessageTypes = {
    signal: 1,
    propertyUpdate: 2,
    init: 3,
    idle: 4,
    debug: 5,
    invokeMethod: 6,
    connectToSignal: 7,
    disconnectFromSignal: 8,
    setProperty: 9,
    response: 10,
};

var QWebChannel = function(transport, initCallback)
{
    if (typeof transport !== "object" || typeof transport.send !== "function") {
        console.error("The QWebChannel expects a transport object with a send function and onmessage callback property." +
                      " Given is: transport: " + typeof(transport) + ", transport.send: " + typeof(transport.send));
        return;
    }

    var channel = this;
    this.transport = transport;

    this.send = function(data)
    {
        if (typeof(data) !== "string") {
            data = JSON.stringify(data);
        }
        channel.transport.send(data);
    }

    this.transport.onmessage = function(message)
    {
        var data = message.data;
        if (typeof data === "string") {
            data = JSON.parse(data);
        }
        switch (data.type) {
            case QWebChannelMessageTypes.signal:
                channel.handleSignal(data);
                break;
            case QWebChannelMessageTypes.response:
                channel.handleResponse(data);
                break;
            case QWebChannelMessageTypes.propertyUpdate:
                channel.handlePropertyUpdate(data);
                break;
            default:
                console.error("invalid message received:", message.data);
                break;
        }
    }

    this.execCallbacks = {};
    this.execId = 0;
    this.exec = function(data, callback)
    {
        if (!callback) {
            // if no callback is given, send directly
            channel.send(data);
            return;
        }
        if (channel.execId === Number.MAX_VALUE) {
            // wrap
            channel.execId = Number.MIN_VALUE;
        }
        if (data.hasOwnProperty("id")) {
            console.error("Cannot exec message with property id: " + JSON.stringify(data));
            return;
        }
        data.id = channel.execId++;
        channel.execCallbacks[data.id] = callback;
        channel.send(data);
    };

    this.objects = {};

    this.handleSignal = function(message)
    {
        var object = channel.objects[message.object];
        if (object) {
            object.signalEmitted(message.signal, message.args);
        } else {
            console.warn("Unhandled signal: " + message.object + "::" + message.signal);
        }
    }

    this.handleResponse = function(message)
    {
        if (!message.hasOwnProperty("id")) {
            console.error("Invalid response message received: ", JSON.stringify(message));
            return;
        }
        channel.execCallbacks[message.id](message.data);
        delete channel.execCallbacks[message.id];
    }

    this.handlePropertyUpdate = function(message)
    {
        for (var i in message.data) {
            var data = message.data[i];
            var object = channel.objects[data.object];
            if (object) {
                object.propertyUpdate(data.signals, data.properties);
            } else {
                console.warn("Unhandled property update: " + data.object + "::" + data.signal);
            }
        }
        channel.exec({type: QWebChannelMessageTypes.idle});
    }

    this.debug = function(message)
    {
        channel.send({type: QWebChannelMessageTypes.debug, data: message});
    };

    channel.exec({type: QWebChannelMessageTypes.init}, function(data) {
        for (var objectName in data) {
            var object = new QObject(objectName, data[objectName], channel);
        }
        // now unwrap properties, which might reference other registered objects
        for (var objectName in channel.objects) {
            channel.objects[objectName].unwrapProperties();
        }
        if (initCallback) {
            initCallback(channel);
        }
        channel.exec({type: QWebChannelMessageTypes.idle});
    });
};

function QObject(name, data, webChannel)
{
    this.__id__ = name;
    webChannel.objects[name] = this;

    // List of callbacks that get invoked upon signal emission
    this.__objectSignals__ = {};

    // Cache of all properties, updated when a notify signal is emitted
    this.__propertyCache__ = {};

    var object = this;

    // ----------------------------------------------------------------------

    this.unwrapQObject = function(response)
    {
        if (response instanceof Array) {
            // support list of objects
            var ret = new Array(response.length);
            for (var i = 0; i < response.length; ++i) {
                ret[i] = object.unwrapQObject(response[i]);
            }
            return ret;
        }
        if (!response
            || !response["__QObject*__"]
            || response.id === undefined) {
            return response;
        }

        var objectId = response.id;
        if (webChannel.objects[objectId])
            return webChannel.objects[objectId];

        if (!response.data) {
            console.error("Cannot unwrap unknown QObject " + objectId + " without data.");
            return;
        }

        var qObject = new QObject( objectId, response.data, webChannel );
        qObject.destroyed.connect(function() {
            if (webChannel.objects[objectId] === qObject) {
                delete webChannel.objects[objectId];
                // reset the now deleted QObject to an empty {} object
                // just assigning {} though would not have the desired effect, but the
                // below also ensures all external references will see the empty map
                // NOTE: this detour is necessary to workaround QTBUG-40021
                var propertyNames = [];
                for (var propertyName in qObject) {
                    propertyNames.push(propertyName);
                }
                for (var idx in propertyNames) {
                    delete qObject[propertyNames[idx]];
                }
            }
        });
        // here we are already initialized, and thus must directly unwrap the properties
        qObject.unwrapProperties();
        return qObject;
    }

    this.unwrapProperties = function()
    {
        for (var propertyIdx in object.__propertyCache__) {
            object.__propertyCache__[propertyIdx] = object.unwrapQObject(object.__propertyCache__[propertyIdx]);
        }
    }

    function addSignal(signalData, isPropertyNotifySignal)
    {
        var signalName = signalData[0];
        var signalIndex = signalData[1];
        object[signalName] = {
            connect: function(callback) {
                if (typeof(callback) !== "function") {
                    console.error("Bad callback given to connect to signal " + signalName);
                    return;
                }

                object.__objectSignals__[signalIndex] = object.__objectSignals__[signalIndex] || [];
                object.__objectSignals__[signalIndex].push(callback);

                if (!isPropertyNotifySignal && signalName !== "destroyed") {
                    // only required for "pure" signals, handled separately for properties in propertyUpdate
                    // also note that we always get notified about the destroyed signal
                    webChannel.exec({
                        type: QWebChannelMessageTypes.connectToSignal,
                        object: object.__id__,
                        signal: signalIndex
                    });
                }
            },
            disconnect: function(callback) {
                if (typeof(callback) !== "function") {
                    console.error("Bad callback given to disconnect from signal " + signalName);
                    return;
                }
                object.__objectSignals__[signalIndex] = object.__objectSignals__[signalIndex] || [];
                var idx = object.__objectSignals__[signalIndex].indexOf(callback);
                if (idx === -1) {
                    console.error("Cannot find connection of signal " + signalName + " to " + callback.name);
                    return;
                }
                object.__objectSignals__[signalIndex].splice(idx, 1);
                if (!isPropertyNotifySignal && object.__objectSignals__[signalIndex].length === 0) {
                    // only required for "pure" signals, handled separately for properties in propertyUpdate
                    webChannel.exec({
                        type: QWebChannelMessageTypes.disconnectFromSignal,
                        object: object.__id__,
                        signal: signalIndex
                    });
                }
            }
        };
    }

    /**
     * Invokes all callbacks for the given signalname. Also works for property notify callbacks.
     */
    function invokeSignalCallbacks(signalName, signalArgs)
    {
        var connections = object.__objectSignals__[signalName];
        if (connections) {
            connections.forEach(function(callback) {
                callback.apply(callback, signalArgs);
            });
        }
    }

    this.propertyUpdate = function(signals, propertyMap)
    {
        // update property cache
        for (var propertyIndex in propertyMap) {
            var propertyValue = propertyMap[propertyIndex];
            object.__propertyCache__[propertyIndex] = propertyValue;
        }

        for (var signalName in signals) {
            // Invoke all callbacks, as signalEmitted() does not. This ensures the
            // property cache is updated before the callbacks are invoked.
            invokeSignalCallbacks(signalName, signals[signalName]);
        }
    }

    this.signalEmitted = function(signalName, signalArgs)
    {
        invokeSignalCallbacks(signalName, this.unwrapQObject(signalArgs));
    }

    function addMethod(methodData)
    {
        var methodName = methodData[0];
        var methodIdx = methodData[1];
        object[methodName] = function() {
            var args = [];
            var callback;
            for (var i = 0; i < arguments.length; ++i) {
                var argument = arguments[i];
                if (typeof argument === "function")
                    callback = argument;
                else if (argument instanceof QObject && webChannel.objects[argument.__id__] !== undefined)
                    args.push({
                        "id": argument.__id__
                    });
                else
                    args.push(argument);
            }

            webChannel.exec({
                "type": QWebChannelMessageTypes.invokeMethod,
                "object": object.__id__,
                "method": methodIdx,
                "args": args
            }, function(response) {
                if (response !== undefined) {
                    var result = object.unwrapQObject(response);
                    if (callback) {
                        (callback)(result);
                    }
                }
            });
        };
    }

    function bindGetterSetter(propertyInfo)
    {
        var propertyIndex = propertyInfo[0];
        var propertyName = propertyInfo[1];
        var notifySignalData = propertyInfo[2];
        // initialize property cache with current value
        // NOTE: if this is an object, it is not directly unwrapped as it might
        // reference other QObject that we do not know yet
        object.__propertyCache__[propertyIndex] = propertyInfo[3];

        if (notifySignalData) {
            if (notifySignalData[0] === 1) {
                // signal name is optimized away, reconstruct the actual name
                notifySignalData[0] = propertyName + "Changed";
            }
            addSignal(notifySignalData, true);
        }

        Object.defineProperty(object, propertyName, {
            configurable: true,
            get: function () {
                var propertyValue = object.__propertyCache__[propertyIndex];
                if (propertyValue === undefined) {
                    // This shouldn't happen
                    console.warn("Undefined value in property cache for property \"" + propertyName + "\" in object " + object.__id__);
                }

                return propertyValue;
            },
            set: function(value) {
                if (value === undefined) {
                    console.warn("Property setter for " + propertyName + " called with undefined value!");
                    return;
                }
                object.__propertyCache__[propertyIndex] = value;
                var valueToSend = value;
                if (valueToSend instanceof QObject && webChannel.objects[valueToSend.__id__] !== undefined)
                    valueToSend = { "id": valueToSend.__id__ };
                webChannel.exec({
                    "type": QWebChannelMessageTypes.setProperty,
                    "object": object.__id__,
                    "property": propertyIndex,
                    "value": valueToSend
                });
            }
        });

    }

    // ----------------------------------------------------------------------

    data.methods.forEach(addMethod);

    data.properties.forEach(bindGetterSetter);

    data.signals.forEach(function(signal) { addSignal(signal, false); });

    for (var name in data.enums) {
        object[name] = data.enums[name];
    }
}

//required for use with nodejs
if (typeof module === 'object') {
    module.exports = {
        QWebChannel: QWebChannel
    };
}


window.pack_qweb_app = false;
if(navigator.userAgent.toLowerCase().indexOf('pigcmspacksoftwebviewapp') > 0){
	window.pack_qweb_app = true;
}
window.jsManual = null;
window.jsManualLoadFunc = [];
if(typeof qt != 'undefined'){
	new QWebChannel(qt.webChannelTransport, function(channel) {
		window.jsManual = channel.objects.jsManual;
		if(window.jsManualLoadFunc.length > 0){
			for(var i in window.jsManualLoadFunc){
				var tmpFunc = window.jsManualLoadFunc[i].func;
				if(tmpFunc && window[tmpFunc]){
					var options = window.jsManualLoadFunc[i].options;
					var func = window[tmpFunc];
					func.apply(tmpFunc,options);
				}
			}
			window.jsManualLoadFunc = [];
		}
	});
}

// 窗口最小化
function min_window(){
	if (jsManual) {
		jsManual.min_window();
	}else{
		window.jsManualLoadFunc.push({
			func:'min_window'
		});
	}
}
// 窗口最大化
function max_window() {
	if (jsManual) {
		jsManual.max_window();
    }else{
		window.jsManualLoadFunc.push({
			func:'max_window'
		});
	}
}
// 关闭窗口
function close_window() {
	if (jsManual) {
		jsManual.close_window();
	}else{
		window.jsManualLoadFunc.push({
			func:'close_window'
		});
	}
}
// 变更窗口
function change_window(width,height){
	if (jsManual) {
		jsManual.change_window(width,height);
	}else{
		window.jsManualLoadFunc.push({
			func:'change_window',
			options:[width,height]
		});
	}
}
// 播放MP3，url：播放地址；unique_no:MP3唯一标识，play_count：播放次数
function play_mp3(url,unique_no,play_count) {
	if (jsManual) {
		jsManual.play_mp3(url, unique_no, play_count);
	}else{
		window.jsManualLoadFunc.push({
			func:'play_mp3',
			options:[url,unique_no,play_count]
		});
	}
}
// 停止播放
function stop_mp3() {
	if (jsManual) {
		jsManual.stop_mp3();
	}else{
		window.jsManualLoadFunc.push({
			func:'stop_mp3'
		});
	}
}
// 检测软件，soft_name:软件名称，start_params：启动参数，start_type：启动类型，tips：提示语
function check_soft(soft_name,start_params,start_type,tips) {
	if(jsManual){
		jsManual.check_soft(soft_name,start_params,start_type,tips)
	}else{
		window.jsManualLoadFunc.push({
			func:'check_soft',
			options:[soft_name,start_params,start_type,tips]
		});
	}
}
// 调用系统默认浏览器打开url
function open_url(url) {
	if (jsManual) {
		jsManual.open_url(url);
	}else{
		window.jsManualLoadFunc.push({
			func:'open_url',
			options:[url]
		});
	}
}
// 读文件
function read_file(path,callback){
	if (jsManual) {
		jsManual.read_file(path,callback);
	}else{
		window.jsManualLoadFunc.push({
			func:'read_file',
			options:[path,callback]
		});
	}
}

// 写文件
function write_file(path,strtext,callback){
	if (jsManual) {
		jsManual.write_file(path,strtext,callback);
	}else{
		window.jsManualLoadFunc.push({
			func:'write_file',
			options:[path,strtext,callback]
		});
	}
}
// 检查文件是否存在
function check_file(path,callback) {
    if (jsManual) {
		jsManual.check_file(path,callback);
    }else{
		window.jsManualLoadFunc.push({
			func:'check_file',
			options:[path,callback]
		});
	}
}
// 删除文件
function remove_file(path) {
	if (jsManual) {
		jsManual.remove_file(path);
    }else{
		window.jsManualLoadFunc.push({
			func:'remove_file',
			options:[path]
		});
	}
}
//得到软件名称
function get_soft_name(callback){
	if (jsManual) {
		jsManual.get_soft_name(callback);
    }else{
		window.jsManualLoadFunc.push({
			func:'get_soft_name',
			options:[callback]
		});
	}
}