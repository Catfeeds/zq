/*
 * mqtt封装，对外只提供指定方法，避免变量冲突
 */

var MqInit = (function () {
    //随机uuid
    var getUuid = function () {
        var s = [];
        var hexDigits = "0123456789abcdef";
        for (var i = 0; i < 36; i++) {
            s[i] = hexDigits.substr(Math.floor(Math.random() * 0x10), 1);
        }
        s[14] = "4";
        s[19] = hexDigits.substr((s[19] & 0x3) | 0x8, 1);
        s[8] = s[13] = s[18] = s[23] = "-";

        return s.join("");
    };

    var client;

    //连接
    if(mqHost != undefined && mqHost != ''){
        var clientId = 'mqttjs_' + getUuid();
        var clientHost = mqHost + '/mqtt';
        var options = {
            keepalive: 0,
            clientId: clientId,
            protocolId: 'MQTT',
            protocolVersion: 4,
            clean: true,
            reconnectPeriod: 3000,
            connectTimeout: 8000,
            username: mqUser.userName,
            password: mqUser.password,
            rejectUnauthorized: false
        };

        client = mqtt.connect(clientHost, options);

        //监听错误
        client.on('error', function (err) {
            console.log(err);
        });

        //监听连接成功
        client.on('connect', function () {
            console.log('CONNECT OK!')
        });

        //监听断开连接
        client.on('close', function () {
            console.log(clientId + ' DISCONNECTED');
        });
    };


    //动态订阅主题
    var subscribeTopic = function (topics) {
        if (topics) {
            for (var i = 0; i < topics.length; i++) {
                client.subscribe(topics[i], {qos: 1});
            }
        }
    };

    //动态监听，只调用一次
    var onMessage = function (callBack, topics) {

        if (topics) {
            for (var i = 0; i < topics.length; i++) {
                client.subscribe(topics[i], {qos: 1});
            }
        }

        client.on('message', function (topic, message) {
            return callBack(topic, message.toString());
        });
    };

    //主题发布消息
    var publishToTopic = function (topic, jsonStr) {
        client.publish(topic, jsonStr, 1);
    };

    return {
        subscribeTopic: subscribeTopic,
        onMessage: onMessage,
        publishToTopic: publishToTopic
    };

})();
