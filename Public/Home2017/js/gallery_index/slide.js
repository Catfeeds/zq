/*!
* sina.com.cn/license
* svn:../ui/project/slide/trunk/20131128 高清图
* 20140702180913
* [${p_id},${t_id},${d_id}] published at ${publishdate} ${publishtime}
*/
(function(exports) {
    var Util = {
        byId: function(id) {
            return document.getElementById(id)
        },
        byAttr: function(node, attname, attvalue) {
            if (typeof node == "string") {
                node = Util.byId(node)
            }
            var nodes = [];
            attvalue = attvalue || "";
            var getAttr = function(node) {
                return node.getAttribute(attname)
            };
            for (var i = 0,
            l = node.childNodes.length; i < l; i++) {
                if (node.childNodes[i].nodeType == 1) {
                    var fit = false;
                    if (attvalue) {
                        fit = (getAttr(node.childNodes[i]) == attvalue)
                    } else {
                        fit = (getAttr(node.childNodes[i]) != "")
                    }
                    if (fit) {
                        nodes.push(node.childNodes[i])
                    }
                    if (node.childNodes[i].childNodes.length > 0) {
                        nodes = nodes.concat(arguments.callee.call(null, node.childNodes[i], attname, attvalue))
                    }
                }
            }
            return nodes
        },
        hasClass: function(ele, cls) {
            if (!ele) {
                return false
            }
            return ele.className.match(new RegExp("(\\s|^)" + cls + "(\\s|$)"))
        },
        addClass: function(ele, cls) {
            if (!this.hasClass(ele, cls)) {
                ele.className = ele.className.replace(/(^\s*)|(\s*$)/g, "") + " " + cls
            }
        },
        removeClass: function(ele, cls) {
            if (this.hasClass(ele, cls)) {
                var reg = new RegExp("(\\s|^)" + cls + "(\\s|$)");
                ele.className = ele.className.replace(reg, " ")
            }
        },
        toggleClass: function(ele, cls) {
            if (this.hasClass(ele, cls)) {
                this.removeClass(ele, cls)
            } else {
                this.addClass(ele, cls)
            }
        },
        preventDefault: function(e) {
            if (e.preventDefault) {
                e.preventDefault()
            } else {
                e.returnValue = false
            }
        },
        stopPropagation: function(e) {
            var e = e || window.event;
            if (window.event) {
                e.cancelBubble = true
            } else {
                e.stopPropagation()
            }
        },
        contains: function(a, b) {
            try {
                return a.contains ? a != b && a.contains(b) : !!(a.compareDocumentPosition(b) & 16)
            } catch(e) {}
        },
        addEvent: (function() {
            var _fun = function(e, fn) {
                var a = e.currentTarget,
                b = e.relatedTarget;
                if (!Util.contains(a, b) && a != b) {
                    fn.call(e.currentTarget, e)
                }
            };
            var _eventCompat = function(event) {
                var type = event.type;
                if (type == "DOMMouseScroll" || type == "mousewheel") {
                    event.delta = (event.wheelDelta) ? event.wheelDelta / 120 : -(event.detail || 0) / 3
                }
                if (event.srcElement && !event.target) {
                    event.target = event.srcElement
                }
                if (!event.preventDefault && event.returnValue !== undefined) {
                    event.preventDefault = function() {
                        event.returnValue = false
                    }
                }
                return event
            };
            if (window.addEventListener) {
                return function(el, type, fn, capture) {
                    if (type == "mouseenter") {
                        el.addEventListener("mouseover",
                        function(e) {
                            _fun(e, fn)
                        },
                        false)
                    } else {
                        if (type == "mouseleave") {
                            el.addEventListener("mouseout",
                            function(e) {
                                _fun(e, fn)
                            },
                            false)
                        } else {
                            if (type === "mousewheel" && document.mozHidden !== undefined) {
                                type = "DOMMouseScroll"
                            }
                            el.addEventListener(type,
                            function(e) {
                                fn.call(this, _eventCompat(e))
                            },
                            capture || false)
                        }
                    }
                }
            } else {
                if (window.attachEvent) {
                    return function(el, type, fn, capture) {
                        el.attachEvent("on" + type,
                        function(event) {
                            event = event || window.event;
                            fn.call(el, _eventCompat(event))
                        })
                    }
                }
            }
            return function() {}
        })(),
        removeEvent: function(o, s, fn) {
            if (o.addEventListener) {
                o.removeEventListener(s, fn, false)
            } else {
                if (o.attachEvent) {
                    o.detachEvent("on" + s, fn)
                }
            }
            fn[s] = null
        },
        fixEvent: function(e) {
            e = window.event || e;
            e.target = e.target || e.srcElement;
            if (e.pageX == null && e.clientX != null) {
                var doc = document.documentElement,
                body = document.body;
                e.pageX = e.clientX + (doc && doc.scrollLeft || body && body.scrollLeft || 0) - (doc && doc.clientLeft || body && body.clientLeft || 0);
                e.pageY = e.clientY + (doc && doc.scrollTop || body && body.scrollTop || 0) - (doc && doc.clientTop || body && body.clientTop || 0)
            } else {
                e.pageX = null;
                e.pageY = null
            }
            e.layerX = e.layerX || e.offsetX;
            e.layerY = e.layerY || e.offsetY;
            return e
        },
        getPosition: function(ele) {
            var positionX = 0;
            var positionY = 0;
            while (ele != null && ele != document.body) {
                positionX += ele.offsetLeft;
                positionY += ele.offsetTop;
                ele = ele.offsetParent
            }
            return [positionX, positionY]
        },
        winSize: function(_target) {
            var w, h;
            if (_target) {
                target = _target.document
            } else {
                target = document
            }
            if (target.compatMode === "CSS1Compat") {
                w = target.documentElement.clientWidth;
                h = target.documentElement.clientHeight
            } else {
                if (self.innerHeight) {
                    if (_target) {
                        target = _target.self
                    } else {
                        target = self
                    }
                    w = target.innerWidth;
                    h = target.innerHeight
                } else {
                    if (target.documentElement && target.documentElement.clientHeight) {
                        w = target.documentElement.clientWidth;
                        h = target.documentElement.clientHeight
                    } else {
                        if (target.body) {
                            w = target.body.clientWidth;
                            h = target.body.clientHeight
                        }
                    }
                }
            }
            return {
                width: w,
                height: h
            }
        },
        delegatedEvent: (function() {
            var checkContains = function(list, el) {
                for (var i = 0,
                len = list.length; i < len; i += 1) {
                    if (Util.contains(list[i], el)) {
                        return true
                    }
                }
                return false
            };
            var isEmptyObj = function(obj) {
                for (var key in obj) {
                    return false
                }
                return true
            };
            return function(actEl, expEls, aType) {
                if (!expEls) {
                    expEls = []
                }
                if (Util.isArray(expEls)) {
                    expEls = [expEls]
                }
                var evtList = {};
                var aType = aType || "action-type";
                var bindEvent = function(e) {
                    var evt = e || window.event;
                    var el = evt.target || evt.srcElement;
                    var type = e.type;
                    if (checkContains(expEls, el)) {
                        return false
                    } else {
                        if (!Util.contains(actEl, el)) {
                            return false
                        } else {
                            var actionType = null;
                            var checkBuble = function() {
                                if (evtList[type] && evtList[type][actionType]) {
                                    return evtList[type][actionType]({
                                        evt: evt,
                                        el: el,
                                        e: e,
                                        data: Util.queryToJson(el.getAttribute("action-data") || "")
                                    })
                                } else {
                                    return true
                                }
                            };
                            while (el && el !== actEl) {
                                if (!el.getAttribute) {
                                    break
                                }
                                actionType = el.getAttribute(aType);
                                if (checkBuble() === false) {
                                    break
                                }
                                el = el.parentNode
                            }
                        }
                    }
                };
                var that = {};
                that.add = function(funcName, evtType, process) {
                    if (!evtList[evtType]) {
                        evtList[evtType] = {};
                        Util.addEvent(actEl, evtType, bindEvent)
                    }
                    var ns = evtList[evtType];
                    ns[funcName] = process
                };
                that.remove = function(funcName, evtType) {
                    if (evtList[evtType]) {
                        delete evtList[evtType][funcName];
                        if (isEmptyObj(evtList[evtType])) {
                            delete evtList[evtType];
                            Util.removeEvent(actEl, evtType, bindEvent)
                        }
                    }
                };
                that.pushExcept = function(el) {
                    expEls.push(el)
                };
                that.removeExcept = function(el) {
                    if (!el) {
                        expEls = []
                    } else {
                        for (var i = 0,
                        len = expEls.length; i < len; i += 1) {
                            if (expEls[i] === el) {
                                expEls.splice(i, 1)
                            }
                        }
                    }
                };
                that.clearExcept = function(el) {
                    expEls = []
                };
                that.destroy = function() {
                    for (k in evtList) {
                        for (l in evtList[k]) {
                            delete evtList[k][l]
                        }
                        delete evtList[k];
                        Util.removeEvent(actEl, bindEvent, k)
                    }
                };
                return that
            }
        })(),
        builder: function(wrap, type) {
            var list, nodes, ids;
            wrap = (function() {
                if (typeof wrap == "string") {
                    return Util.byId(wrap)
                }
                return wrap
            })();
            nodes = this.byAttr(wrap, type);
            list = {};
            ids = {};
            for (var i = 0,
            len = nodes.length; i < len; i++) {
                var j = nodes[i].getAttribute(type);
                if (!j) {
                    continue
                }
                list[j] || (list[j] = []);
                list[j].push(nodes[i]);
                ids[j] || (ids[j] = nodes[i])
            }
            return {
                box: wrap,
                list: list,
                ids: ids
            }
        },
        strLeft: function(s, n) {
            var ELLIPSIS = "...";
            var s2 = s.slice(0, n),
            i = s2.replace(/[^\x00-\xff]/g, "**").length,
            j = s.length,
            k = s2.length;
            if (i < n) {
                return s2
            } else {
                if (i == n) {
                    if (n == j || k == j) {
                        return s2
                    } else {
                        return s.slice(0, n - 2) + ELLIPSIS
                    }
                }
            }
            i -= s2.length;
            switch (i) {
            case 0:
                return s2;
            case n:
                var s4;
                if (n == j) {
                    s4 = s.slice(0, (n >> 1) - 1);
                    return s4 + ELLIPSIS
                } else {
                    s4 = s.slice(0, n >> 1);
                    return s4
                }
            default:
                var k = n - i,
                s3 = s.slice(k, n),
                j = s3.replace(/[\x00-\xff]/g, "").length;
                return j ? s.slice(0, k) + arguments.callee(s3, j) : s.slice(0, k)
            }
        },
        strLeft2: (function() {
            var byteLen = function(str) {
                if (typeof str == "undefined") {
                    return 0
                }
                var aMatch = str.match(/[^\x00-\x80]/g);
                return (str.length + (!aMatch ? 0 : aMatch.length))
            };
            return function(str, len) {
                var s = str.replace(/\*/g, " ").replace(/[^\x00-\xff]/g, "**");
                str = str.slice(0, s.slice(0, len).replace(/\*\*/g, " ").replace(/\*/g, "").length);
                if (byteLen(str) > len) {
                    str = str.slice(0, str.length - 1)
                }
                return str
            }
        })(),
        numSplit: function(num) {
            num = num + "";
            var re = /(-?\d+)(\d{3})/;
            while (re.test(num)) {
                num = num.replace(re, "$1,$2")
            }
            return num
        },
        isArray: function(o) {
            return Object.prototype.toString.call(o) === "[object Array]"
        },
        getGuid: function() {
            return Math.abs((new Date()).getTime()) + "_" + Math.round(Math.random() * 100000000)
        },
        extend: function(target, source, deep) {
            target = target || {};
            var sType = typeof source,
            i = 1,
            options;
            if (sType === "undefined" || sType === "boolean") {
                deep = sType === "boolean" ? source: false;
                source = target;
                target = this
            }
            if (typeof source !== "object" && Object.prototype.toString.call(source) !== "[object Function]") {
                source = {}
            }
            while (i <= 2) {
                options = i === 1 ? target: source;
                if (options !== null) {
                    for (var name in options) {
                        var src = target[name],
                        copy = options[name];
                        if (target === copy) {
                            continue
                        }
                        if (deep && copy && typeof copy === "object" && !copy.nodeType) {
                            target[name] = this.extend(src || (copy.length !== null ? [] : {}), copy, deep)
                        } else {
                            if (copy !== undefined) {
                                target[name] = copy
                            }
                        }
                    }
                }
                i++
            }
            return target
        },
        setCSS: (function() {
            var doc = document;
            var head = doc.getElementsByTagName("head")[0];
            return function(s, id) {
                var dom = doc.getElementById(id);
                if (dom) {
                    dom.parentNode.removeChild(dom)
                }
                dom = doc.createElement("style");
                dom.id = id;
                dom.type = "text/css";
                dom.styleSheet ? (dom.styleSheet.cssText = s) : dom.appendChild(doc.createTextNode(s));
                head.appendChild(dom);
                return dom
            }
        })(),
        getStyle: function(elem, name) {
            if (elem.style[name]) {
                return elem.style[name]
            } else {
                if (elem.currentStyle) {
                    return elem.currentStyle[name]
                } else {
                    if (document.defaultView && document.defaultView.getComputedStyle) {
                        name = name.replace(/([A-Z])/g, "-$1");
                        name = name.toLowerCase();
                        var s = document.defaultView.getComputedStyle(elem, "");
                        return s && s.getPropertyValue(name)
                    } else {
                        return null
                    }
                }
            }
        },
        setStyle: function(elem, prop) {
            if (!elem) {
                return
            }
            for (var i in prop) {
                elem.style[i] = prop[i]
            }
        },
        cookie: (function() {
            var co = {};
            co.getCookie = function(name) {
                name = name.replace(/([\.\[\]\$])/g, "\\$1");
                var rep = new RegExp(name + "=([^;]*)?;", "i");
                var co = document.cookie + ";";
                var res = co.match(rep);
                if (res) {
                    return unescape(res[1]) || ""
                } else {
                    return ""
                }
            };
            co.setCookie = function(name, value, expire, path, domain, secure) {
                var cstr = [];
                cstr.push(name + "=" + escape(value));
                if (expire) {
                    var dd = new Date();
                    var expires = dd.getTime() + expire * 3600000;
                    dd.setTime(expires);
                    cstr.push("expires=" + dd.toGMTString())
                }
                if (path) {
                    cstr.push("path=" + path)
                }
                if (domain) {
                    cstr.push("domain=" + domain)
                }
                if (secure) {
                    cstr.push(secure)
                }
                document.cookie = cstr.join(";")
            };
            co.deleteCookie = function(name) {
                document.cookie = name + "=;expires=Fri, 31 Dec 1999 23:59:59 GMT;"
            };
            return co
        })(),
        jsonp: function(url, cb) {
            var head = document.getElementsByTagName("head")[0];
            var ojs = Util.byId(url);
            ojs && head.removeChild(ojs);
            if (url.indexOf("&") == -1) {
                url += "?"
            } else {
                url += "&"
            }
            url = url + "_t" + Util.getGuid();
            if (typeof cb == "function") {
                fun = "jsonp_" + Util.getGuid();
                eval(fun + "=function(res){cb(res)}")
            }
            url = url + "&callback=" + fun;
            var js = document.createElement("script");
            js.src = url;
            js.id = url;
            js.type = "text/javascript";
            js.language = "javascript";
            head.appendChild(js)
        },
        jsLoad: function(url, cb) {
            var head = document.getElementsByTagName("head")[0];
            var js = document.createElement("script"),
            isLoaded = false;
            js.onload = js.onreadystatechange = function() {
                if (!isLoaded && (!this.readyState || this.readyState == "loaded" || this.readyState == "complete")) {
                    isLoaded = true;
                    js.onload = js.onreadystatechange = null;
                    typeof cb == "function" && cb()
                }
            };
            js.src = url;
            try {
                head.appendChild(js)
            } catch(e) {}
        },
        log: (function() {
            var trace = location.href.indexOf("log=1") != -1;
            var fiter = function(methods) {
                for (var i = 0,
                len = methods.length; i < len; i++) {
                    var method = methods[i];
                    if (typeof method == "undefined") {
                        method = function() {}
                    }
                }
            };
            if (typeof console == "undefined") {
                console = {}
            }
            fiter([console.log, console.time, console.timeEnd]);
            return function() {
                if (!trace) {
                    console.time = console.timeEnd = function() {};
                    return
                }
                var slice = Array.prototype.slice;
                var args = slice.call(arguments, 0);
                args.unshift("* SLIDE >>");
                try {
                    console.log.apply(console, args)
                } catch(e) {
                    console.log(args)
                }
            }
        })(),
        uaTrack: function(key, val) {
            if (typeof _S_uaTrack == "function") {
                try {
                    _S_uaTrack(key, val)
                } catch(e) {}
            }
        },
        throttle: function(method, context, interval) {
            clearTimeout(method.__tId__);
            method.__tId__ = setTimeout(function() {
                method.call(context)
            },
            interval || 100)
        },
        timeoutHandle: (function() {
            var events = [];
            var handle = {
                success: function(id) {
                    var eve = events[id];
                    if (!eve) {
                        return
                    }
                    eve.isSuccess = true;
                    clearTimeout(eve.timer)
                },
                timeout: function(id, fn) {
                    var eve = events[id];
                    if (!eve) {
                        return
                    }
                    eve.timer = setTimeout(function() {
                        if (eve.isSuccess) {
                            return
                        }
                        Util.log(id + " " + eve.time + " time out");
                        if (typeof fn == "function") {
                            fn.call(this)
                        }
                    },
                    eve.time)
                }
            };
            return function(id, fn, time) {
                if (events[id]) {
                    throw new Error(id + "已经被占用");
                    return
                }
                events[id] = {};
                events[id].time = time || 5000;
                events[id].isSuccess = false;
                if (typeof fn == "function") {
                    fn.call(this, handle)
                }
            }
        })(),
        queryToJson: function(query, isDecode) {
            var qList = query.split("&");
            var json = {};
            for (var i = 0,
            len = qList.length; i < len; i++) {
                if (qList[i]) {
                    hash = qList[i].split("=");
                    key = hash[0];
                    val = hash[1];
                    if (hash.length < 2) {
                        val = ""
                    }
                    if (!json[key]) {
                        json[key] = val
                    }
                }
            }
            return json
        },
        animate: (function() {
            var animate = function(elem, style, duration, callback, isStyle) {
                this.elem = elem || this;
                this.style = style || {};
                this.duration = duration || 400;
                this.callback = callback ||
                function() {};
                this.isStyle = isStyle ? true: false;
                this.init()
            };
            animate.prototype = {
                init: function() {
                    this.fx()
                },
                ontween: function(pos) {
                    var obj, val, from, to, name, unit, css = this.style,
                    direction = 1;
                    for (var i = 0,
                    len = css.length; i < len; i++) {
                        obj = css[i];
                        from = obj[0];
                        to = obj[1];
                        name = obj[2];
                        unit = obj[3];
                        if (to - from < 0) {
                            direction = -1
                        }
                        val = from + (to - from) * pos;
                        if (direction > 0) {
                            val = Math.min(val, to)
                        } else {
                            val = Math.max(val, to)
                        }
                        if (name == "opacity") {
                            val = val.toString();
                            val = val.substring(0, val.charAt(".") + 3);
                            val = val - 0;
                            this.elem.style.filter = "alpha(opacity=" + 100 * val + ")";
                            this.elem.style.opacity = val
                        } else {
                            if (this.isStyle) {
                                this.elem.style[name] = val + unit
                            } else {
                                this.elem[name] = val
                            }
                        }
                    }
                },
                onend: function(pos) {
                    this.ontween(pos);
                    this.callback.call(this.elem)
                },
                fx: function() {
                    var pos, runTime, startTime = +new Date(),
                    _this = this,
                    timer = setInterval(function() {
                        runTime = +new Date() - startTime;
                        pos = runTime / _this.duration;
                        if (pos >= 1) {
                            clearInterval(timer);
                            _this.onend(pos)
                        } else {
                            _this.ontween(pos)
                        }
                    },
                    13)
                }
            };
            return animate
        })()
    };
    var Clz = function(parent) {
        var propertyName = "___ytreporp___";
        var klass = function() {
            this.init.apply(this, arguments)
        };
        if (parent) {
            var subclass = function() {};
            subclass.prototype = parent.prototype;
            klass.prototype = new subclass
        }
        klass.prototype.init = function() {};
        klass.prototype.set = function(k, v) {
            if (!this[propertyName]) {
                this[propertyName] = {}
            }
            var i = 0,
            un = this[propertyName],
            ns = k.split("."),
            len = ns.length,
            upp = len - 1,
            key;
            while (i < len) {
                key = ns[i];
                if (i == upp) {
                    un[key] = v
                }
                if (un[key] === undefined) {
                    un[key] = {}
                }
                un = un[key];
                i++
            }
        };
        klass.prototype.get = function(k) {
            if (!this[propertyName]) {
                this[propertyName] = {}
            }
            var i = 0,
            un = this[propertyName],
            ns = k.split("."),
            len = ns.length,
            upp = len - 1,
            key;
            while (i < len) {
                key = ns[i];
                if (i == upp) {
                    return un[key]
                }
                if (un[key] === undefined) {
                    un[key] = {}
                }
                un = un[key];
                i++
            }
        };
        klass.fn = klass.prototype;
        klass.fn.parent = klass;
        klass._super = klass.__proto__;
        klass.extend = function(obj) {
            var extended = obj.extended;
            for (var i in obj) {
                klass[i] = obj[i]
            }
            if (extended) {
                extended(klass)
            }
        };
        klass.include = function(obj) {
            var included = obj.included;
            for (var i in obj) {
                klass.fn[i] = obj[i]
            }
            if (included) {
                included(klass)
            }
        };
        return klass
    };
    Util.Clz = Clz;
    var Slide = {};
    Slide.ua = (function() {
        var Detect = function() {
            var ua = navigator.userAgent.toLowerCase();
            this.isIE = /msie/.test(ua);
            this.isOPERA = /opera/.test(ua);
            this.isMOZ = /gecko/.test(ua);
            this.isIE5 = /msie 5 /.test(ua);
            this.isIE55 = /msie 5.5/.test(ua);
            this.isIE6 = /msie 6/.test(ua);
            this.isIE7 = /msie 7/.test(ua);
            this.isSAFARI = /safari/.test(ua);
            this.iswinXP = /windows nt 5.1/.test(ua);
            this.iswinVista = /windows nt 6.0/.test(ua);
            this.isFF = /firefox/.test(ua);
            this.isIOS = /\((iPhone|iPad|iPod)/i.test(ua)
        };
        return new Detect()
    })();
    Slide.register = function(namespace, method) {
        var i = 0,
        un = Slide,
        ns = namespace.split("."),
        len = ns.length,
        upp = len - 1,
        key;
        while (i < len) {
            key = ns[i];
            if (i == upp) {
                if (un[key] !== undefined) {
                    throw ns + ":: has registered"
                }
                un[key] = method
            }
            if (un[key] === undefined) {
                un[key] = {}
            }
            un = un[key];
            i++
        }
    };
    Slide.register("util", Util);
    Slide.register("Clz", Clz);
    var EXPORTS_NAME = "SinaSlide";
    var UGLIFY_NAME = "___" + EXPORTS_NAME + "___";
    exports[UGLIFY_NAME] = Slide;
    if (exports[EXPORTS_NAME]) {
        throw '个性化推荐全局变量名"' + EXPORTS_NAME + '"已经被占用，可使用' + UGLIFY_NAME
    } else {
        exports[EXPORTS_NAME] = Slide
    }
})(window); (function(b) {
    var c = "___SinaSlide___";
    var d = b[c];
    var a = (function() {
        var h = [],
        g = null,
        f = function() {
            var j = 0;
            for (; j < h.length; j++) {
                h[j].end ? h.splice(j--, 1) : h[j]()
            } ! h.length && e()
        },
        e = function() {
            clearInterval(g);
            g = null
        };
        return function(j, q, s, p) {
            var r, m, t, o, i;
            var n = new Image();
            n.src = j;
            if (n.complete) {
                q.call(n);
                s && s.call(n);
                return
            }
            m = n.width;
            t = n.height;
            n.onerror = function() {
                p && p.call(n);
                r.end = true;
                n = n.onload = n.onerror = null
            };
            r = function() {
                if (!n) {
                    return
                }
                o = n.width;
                i = n.height;
                if (o !== m || i !== t || o * i > 1024) {
                    q.call(n);
                    r.end = true
                }
            };
            r();
            n.onload = function() { ! r.end && r();
                s && s.call(n);
                n = n.onload = n.onerror = null
            };
            if (!r.end) {
                h.push(r);
                if (g === null) {
                    g = setInterval(f, 40)
                }
            }
        }
    })();
    d.register("imgReady", a)
})(window); (function(a) {
    var b = "___SinaSlide___";
    var c = a[b];
    var g = c.util;
    var d = g.addClass;
    var f = g.removeClass;
    var e = {};
    var h = typeof(a.ontouchstart) === "undefined" ? "click": "touchstart";
    e.anchorGo = function(n) {
        var j = g.byId(n);
        if (!j) {
            return
        }
        var m = g.getPosition(j);
        var i = m[1] || 0;
        document.documentElement.scrollTop = i;
        document.body.scrollTop = i
    };
    e.toComment = function(m) {
        var j = "J_Comment_Wrap";
        var i = null;
        if (location.hash.indexOf(j) > -1) {
            e.anchorGo(j);
            i = setInterval(function() {
                e.anchorGo(j);
                var n = g.byId("J_Comment_Form_B");
                var o = n && n.getElementsByTagName("textarea")[0];
                if (o && typeof a.SAB !== "undefined") {
                    SAB.app.shine(o);
                    clearInterval(i)
                }
            },
            m || 1500)
        }
    };
    e.renderWeiboList = function(n, o) {
        if (typeof o == "undefined" || !g.isArray(o)) {
            return
        }
        var m = 'suda-uatrack="key=hdphotov2&value=weibo"';
        var q = [];
        for (var p = 0,
        r = o.length; p < r; p++) {
            var u = o[p];
            var s = "swpw-item";
            if (p == r - 1) {
                s = s + " " + s + "-last"
            }
            var j = (function() {
                var v = '<i class="swpw-approve swpw-approve-$$" title="$$"></i>';
                var w = "b";
                var i = "机构";
                if (u.verified == "1") {
                    if (u.verified_type == "0") {
                        w = "y";
                        i = "个人"
                    }
                    v = v.replace("$$", w).replace("$$", "全球体育网" + i + "认证")
                } else {
                    v = ""
                }
                return v
            })();
            var t = "";
            if (u.type !== "") {
                t = '<span class="swpw-label">' + u.type + "</span>"
            }
            q.push('<div class="' + s + '"><div class="swpw-pic"><a ' + m + ' href="http://www.weibo.com/' + u.profile_url + '" target="_blank"><img src="' + u.profile_image_url + '" alt="' + u.name + '"></a></div><div class="swpw-txt"><h3>' + t + "<a " + m + ' href="http://www.weibo.com/' + u.profile_url + '" target="_blank" class="swpw-name">' + u.name + j + '</a></h3><div class="swpw-btn-wrap"><p class="swpw-follow" uid="' + u.weibo_id + '"><a title="关注" onclick="javascript:WeiboFollow.follow(' + u.weibo_id + ',this);return false;" href="javascript:;"></a></p></div><div><p>' + u.intro + "</p></div></div></div>")
        }
        n.innerHTML = q.join("");
        n.style.display = ""
    };
    e.bindDownload = (function() {
        var i = function() {
            var n = document.createElement("iframe");
            n.style.position = "absolute";
            n.style.left = "-9999px";
            n.width = "0";
            n.height = "0";
            n.frameborder = "0";
            n.scrolling = "no";
            document.getElementsByTagName("body")[0].appendChild(n);
            return n
        };
        var m = function(n) {
            j.src = "http://slide.news.sina.com.cn/iframe/download.php?img=" + n
        };
        var j = null;
        return function(n) {
            var o = true;
            if (typeof SLIDE_DATA.allowDownload !== "undefined") {
                o = SLIDE_DATA.allowDownload
            }
            if (!n || !o) {
                return
            }
            if (!j) {
                j = i()
            }
            n.style.display = "";
            g.addEvent(n, "click",
            function() {
                try {
                    if (usrIsLogin) {
                        var p = slide_data.images[c.PAGE.Player.get("opt.index")];
                        var r = p.download_img == "" ? p.image_url: p.download_img;
                        if (typeof(a.ontouchstart) !== "undefined") {
                            window.open(r);
                            return
                        }
                        m(r)
                    }
                } catch(q) {}
            })
        }
    })();
    e.getAndBindLike = (function() {
        var r = function(B, C) {
            var D = document.createElement("div");
            D.className = B;
            D.innerHTML = C;
            D.style.display = "none";
            D.style.position = "absolute";
            document.getElementsByTagName("body")[0].appendChild(D);
            return D
        };
        var w = r("slide-voted", "已顶");
        var x = r("slide-vote", "+1");
        var j = null;
        var q = function(B) {
            var C = g.getPosition(n);
            var D = g.getStyle(B, "width");
            D = parseFloat(D);
            D = isNaN(D) ? 30 : D;
            D = D / 2;
            g.setStyle(B, {
                top: C[1] + "px",
                left: (C[0] + 45 - D) + "px",
                display: ""
            });
            new g.animate(B, [[C[1], C[1] - 25, "top", "px"]], 100,
            function() {},
            true);
            j = setTimeout(function() {
                new g.animate(B, [[C[1] - 25, C[1], "top", "px"]], 200,
                function() {
                    B.style.display = "none"
                },
                true)
            },
            1000)
        };
        var A = g.jsonp;
        var t = g.addEvent;
        var m = "http://comment5.news.sina.com.cn/count/info";
        var i = "http://comment5.news.sina.com.cn/count/submit";
        var z = ARTICLE_DATA.channel + "_" + ARTICLE_DATA.newsid;
        var u = false;
        var s = 0;
        var n = null;
        var o = 999999999;
        var v = "&" + SLIDE_DATA.likeBoard;
        var p = function(B) {
            var B = B && B.result && B.result.data;
            if (B.vote) {
                s = B.vote;
                s = parseInt(s, 10);
                s = s > o ? o + "+": s
            }
            n.innerHTML = "<i></i>" + g.numSplit(s)
        };
        var y = function() {
            A(i + "?key=" + z + "&group=vote&pid=3" + v,
            function() {});
            n.className = n.className + " liked";
            if (s !== o + "+") {
                s += 1
            }
            n.innerHTML = "<i></i>" + g.numSplit(s);
            n.title = "已顶";
            u = true;
            q(x);
            return false
        };
        return function(B) {
            if (!B) {
                return
            }
            n = B;
            A(m + "?key=" + z + "&pid=3",
            function(C) {
                p(C)
            });
            t(n, "click",
            function() {
                if (u) {
                    q(w);
                    return
                }
                y()
            })
        }
    })();
    e.bindSearch = function(m) {
        var i = m.getElementsByTagName("input")[0];
        var j = "hover";
        g.addEvent(m, h,
        function() {
            g.addClass(m, j);
            i.focus()
        });
        g.addEvent(document.body, h,
        function(p) {
            var n = p || window.event;
            var o = n.target || n.srcElement;
            if (!g.contains(m, o)) {
                g.removeClass(m, j)
            }
        })
    };
    e.bindBGHover = (function() {
        var r = "rgba-xx";
        var t = false;
        var j = null;
        var i = 500;
        var m = 0;
        var p = "SI_CSS_RGBA_XX";
        var o = function(y) {
            var x = /^#([0-9a-fA-f]{3}|[0-9a-fA-f]{6})$/;
            var w = y.toLowerCase();
            if (w && x.test(w)) {
                if (w.length === 4) {
                    var z = "#";
                    for (var v = 1; v < 4; v += 1) {
                        z += w.slice(v, v + 1).concat(w.slice(v, v + 1))
                    }
                    w = z
                }
                var u = [];
                for (var v = 1; v < 7; v += 2) {
                    u.push(parseInt("0x" + w.slice(v, v + 2)))
                }
                return u.join(",")
            } else {
                return w
            }
        };
        var q = function(y, w, x) {
            x = x / 100;
            var v = w.replace("#", "");
            var A = o(w);
            var u = Math.floor(x * 255).toString(16) + v;
            var z = "" + y + "{background:rgba(" + A + ", " + x + "); filter:progid:DXImageTransform.Microsoft.Gradient(GradientType=0, StartColorStr='#" + u + "', EndColorStr='#" + u + "');*zoom:1;} :root " + y + "{filter:progid:DXImageTransform.Microsoft.Gradient(GradientType=0, StartColorStr='#00" + v + "', EndColorStr='#00" + v + "'); }";
            return z
        };
        var n = g.setCSS;
        var s = function() {
            m = 75;
            var v = i / 20;
            var u = (100 - m) / v;
            j = setInterval(function() {
                if (t) {
                    clearInterval(j)
                } else {
                    m += u;
                    m = Math.min(100, m);
                    n(q("." + r, "#000000", m), p)
                }
            },
            v)
        };
        return function(u) {
            if (h == "touchstart") {
                return
            }
            g.addEvent(u, "mouseenter",
            function() {
                g.addClass(u, r);
                t = false;
                s()
            });
            g.addEvent(u, "mouseleave",
            function() {
                g.removeClass(u, r);
                n("", p);
                t = true
            })
        }
    })();
    e.bindHavList = function(n) {
        var o = g.builder(n, "nav-type");
        var m = "nav-wrap-list";
        var i = m + "-unfold";
        var p = function() {
            return g.hasClass(n, m)
        };
        var j = function() {
            return ! g.hasClass(n, i)
        };
        if (h == "touchstart") {
            g.addEvent(o.ids.listTrigger, h,
            function() {
                if (!p) {
                    return
                }
                if (j()) {
                    g.addClass(n, i)
                } else {
                    g.removeClass(n, i)
                }
            });
            g.addEvent(document.body, h,
            function(s) {
                var q = s || window.event;
                var r = q.target || q.srcElement;
                if (!g.contains(n, r)) {
                    g.removeClass(n, i)
                }
            })
        } else {
            g.addEvent(o.ids.list, "mouseenter",
            function() {
                if (!p) {
                    return
                }
                if (j()) {
                    g.addClass(n, i)
                } else {
                    g.removeClass(n, i)
                }
            });
            g.addEvent(o.ids.list, "mouseleave",
            function() {
                if (!p) {
                    return
                }
                if (j()) {
                    g.addClass(n, i)
                } else {
                    g.removeClass(n, i)
                }
            })
        }
    };
    e.navResize = function(n, m) {
        var j = 645;
        var i = "nav-wrap-list";
        if (m < j) {
            g.addClass(n, i)
        } else {
            g.removeClass(n, i)
        }
    };
    c.register("app", e)
})(window); (function(a) {
    var b = "___SinaSlide___";
    var c = a[b];
    var d = c.util;
    var e = new c.Clz;
    e.include({
        init: function(g) {
            var f = this;
            f.setStat();
            f.setOpt(g);
            f.getData()
        },
        setOpt: function(f) {
            this.set("opt", d.extend({
                api: "",
                dataType: "jsonp",
                data: null,
                loadComplete: function() {},
                time: 3000,
                error: function(g) {}
            },
            f, true))
        },
        setStat: function() {
            var f = this;
            f._data = null
        },
        getData: function() {
            var g = this;
            var i = g.get("opt");
            var h = i.api;
            d.log("请求地址：" + h);
            var f = "loader_" + d.getGuid();
            d.timeoutHandle(f,
            function(m) {
                var j = function(n) {
                    m.success(f);
                    g._data = n;
                    g._loadComplete(n);
                    i.loadComplete(n)
                };
                m.timeout(f,
                function() {
                    i.error({
                        type: "timeout",
                        msg: f + " " + i.time + " timeout"
                    })
                });
                if (i.data) {
                    j(i.data)
                } else {
                    if (i.dataType == "js") {
                        d.jsLoad(h, j)
                    } else {
                        d.jsonp(h, j);
                        return false
                    }
                }
            },
            i.time)
        },
        _loadComplete: function(f) {}
    });
    c.register("Loader", e)
})(window); (function(a) {
    var b = "___SinaSlide___";
    var d = a[b];
    var e = d.util;
    var c = new d.Clz;
    c.include({
        init: function(g) {
            var f = this;
            f.setOpt(g);
            f.render(f.wrap, f.opt.data)
        },
        setOpt: function(f) {
            this.set("opt", e.extend({
                wrap: null,
                data: null,
                complete: function() {},
                error: function(g) {}
            },
            f, true))
        },
        render: function(f, g) {
            this._renderComplete()
        },
        _renderComplete: function() {}
    });
    d.register("Render", c)
})(window); (function(a) {
    var b = "___SinaSlide___";
    var d = a[b];
    var f = d.util;
    var c = new d.Clz;
    var e = f.uaTrack;
    c.include({
        init: function(h) {
            var g = this;
            g.setOpt(h);
            g.render()
        },
        setOpt: function(g) {
            this.set("opt", f.extend({
                wrap: null,
                data: null,
                index: 0,
                status: "normal",
                direction: "v",
                pageWidth: 0,
                itemWidth: 0,
                mousemove: function(h) {},
                complete: function() {},
                error: function(h) {}
            },
            g, true))
        },
        render: function() {
            var r = this;
            var h = r.get("opt");
            var g = (function(s) {
                if (typeof s == "string") {
                    return f.byId(s)
                }
                return s
            })(h.wrap);
            var i = h.data;
            if (!g || !i) {
                return
            }
            var o = i.images;
            var m = (function(t) {
                var v = [];
                for (var u = 0,
                s = t.length; u < s; u++) {
                    var w = t[u];
                    var x = w.image_url;
                    x = x.replace("_img", "_" + SLIDE_DATA.imgType);
                    v.push('<li class="swp-item"  slide-type="item"><div class="swp-img" slide-type="bigWrap" data-src="' + x + '"></div></li>')
                }
                return '<ul class="swp-hd-list clearfix" slide-type="list">' + v.join("") + "</ul>"
            })(o);
            f.byId("SI_Cont").innerHTML = m;
            var j = f.builder(g, "slide-type");
            var p = j.ids;
            var q = i.slide;
            p.time.innerHTML = q.createtime.replace(/-/g, ".");
            if (q.long_intro) {
                p.summaryTit.innerHTML = q.title.replace("_高清图集_全球体育", "");
                p.summaryCont.innerHTML = q.long_intro;
                p.summaryWrap.style.display = "";
                var n = document.getElementById("showAll_btn");
                n.innerHTML = "<a href='javascript:;' suda-uatrack='key=hdphoto_20140213&value=morecontent'>显示所有内容<em></em></a>";
                f.addClass(p.summaryCont, "showAllTxt");
                n.onclick = function() {
                    if (f.hasClass(p.summaryCont, "showAllTxt")) {
                        n.innerHTML = "<a href='#toWeibo' suda-uatrack='key=hdphoto_20140213&value=morecontent'>隐藏所有内容<em class='hideEm'></em></a>";
                        f.removeClass(p.summaryCont, "showAllTxt")
                    } else {
                        n.innerHTML = "<a href='javascript:;' suda-uatrack='key=hdphoto_20140213&value=morecontent'>显示所有内容<em></em></a>";
                        f.addClass(p.summaryCont, "showAllTxt")
                    }
                }
            }
            f.removeClass(j.box.parentNode, "slide-wrap-loading");
            r.set("data", i);
            r.set("builder", j);
            r.set("builderIds", j.ids);
            r.buildWidth(0, h.pageWidth);
            r.bindEvent(g)
        },
        buildWidth: function(m, t) {
            var v = this;
            var h = v.get("opt");
            var o = v.get("builder");
            var s = v.get("builderIds");
            var r = o.list.item;
            var q = t || h.itemWidth;
            v.set("opt.itemWidth", q);
            var p = o.list.item.length;
            t = t || h.pageWidth;
            t = parseInt(t - t % q);
            t = Math.max(t, q);
            t = Math.min(t, q * p);
            v.set("opt.pageWidth", t);
            var g = parseInt(t / q);
            v.set("length", p);
            v.set("showLen", g);
            s.cont.style[h.direction == "v" ? "height": "width"] = t + "px";
            s.list.style[h.direction == "v" ? "height": "width"] = q * p + "px";
            for (var n = 0,
            j = r.length; n < j; n++) {
                var u = r[n];
                u.style[h.direction == "v" ? "height": "width"] = q + "px"
            }
            v.move(v.get("opt.index"))
        },
        bindEvent: function(n) {
            var h = this;
            var j = h.get("opt");
            var g = h.get("builder");
            var i = h.get("builderIds");
            var o = f.delegatedEvent(g.box);
            var m = "fold";
            o.add("slide-move", "mousemove",
            function(s) {
                var r = s.el;
                var p = h.get("opt.status");
                var q = h.getClickPos(s.evt, s.el);
                j.mousemove(q);
                h.btnToggle(q)
            });
            o.add("slide-move", "click",
            function(r) {
                var p = h.get("opt.index");
                var q = h.get("clickPos");
                if (q.indexOf("left") > -1) {
                    h.move(p - 1);
                    e("hdphotov2", "left")
                } else {
                    if (q.indexOf("right") > -1) {
                        h.move(p + 1);
                        e("hdphotov2", "right")
                    }
                }
            });
            o.add("slide-next", "click",
            function(p) {
                h.next()
            });
            o.add("slide-prev", "click",
            function(p) {
                h.prev()
            })
        },
        getClickPos: function(n, m) {
            n = f.fixEvent(n);
            var j = "out";
            var g = n.layerX;
            var p = n.layerY;
            if (n.pageX) {
                var o = f.getPosition(m);
                g = n.pageX - o[0]
            }
            var h = m.offsetWidth;
            var i = m.offsetHeight;
            if (p < i) {
                if (g < h / 2) {
                    j = "left"
                } else {
                    j = "right"
                }
                if (p < i / 2) {
                    j += "_up"
                } else {
                    j += "_down"
                }
            }
            this.set("clickPos", j);
            return j
        },
        prev: function() {
            var g = this;
            var h = g.get("opt.index");
            g.move(h - 1,
            function(j, i) {})
        },
        next: function() {
            var g = this;
            var h = g.get("opt.index");
            g.move(h + 1,
            function(j, i) {})
        },
        btnToggle: function(j) {
            if (typeof(a.ontouchstart) !== "undefined") {
                return
            }
            var g = this;
            var i = g.get("builderIds");
            var h = "none";
            var m = "none";
            if (j.indexOf("right") > -1) {
                h = "none";
                m = ""
            } else {
                if (j.indexOf("left") > -1) {
                    h = "";
                    m = "none"
                }
            }
            i.prev.style.display = h;
            i.next.style.display = m
        },
        prevLoad: function(n, h, i) {
            var q = this;
            var m = q.get("builder");
            var r = m.ids.cont;
            i = i || false;
            var p = "swp-hd-loaded";
            var o = m.list.bigWrap[n];
            if (typeof o == "undefined") {
                return
            }
            var g = o.getAttribute("data-src");
            o.setAttribute("data-src", "");
            var j = function(s) {
                var t = f.winSize();
                q.bigResize(s, t.width, t.height, i)
            };
            if (g) {
                d.imgReady(g,
                function() {
                    o.appendChild(this);
                    h && j(this)
                })
            } else {
                h && j(o.getElementsByTagName("img")[0])
            }
        },
        setArrowTitle: function(j, g) {
            var h = this;
            var i = h.get("builderIds");
            var n = {
                first: "上一图集",
                last: "上一张",
                normal: "上一张"
            };
            var m = {
                first: "下一张",
                last: "下一图集",
                normal: "下一张"
            };
            i.prev.title = n[g];
            i.next.title = m[g]
        },
        move: function(p, t) {
            var x = this;
            var i = x.get("opt");
            var r = i.data.images;
            p = parseInt(p, 10);
            var q = x.get("length");
            var g = x.get("showLen", g);
            var m = "error";
            var y = false;
            var j = "";
            var h = function(z) {
                if (z < 0) {
                    z = 0
                } else {
                    if (z + g > q) {
                        z = q - 1
                    }
                }
                return z
            };
            if (p < 0) {
                p = 0;
                j = "left";
                y = true
            } else {
                if (p + g > q) {
                    p = q - 1;
                    j = "right";
                    y = true
                }
            }
            if (p + g == q && j === "right") {
                m = "last"
            } else {
                if (p == 0 && j === "left") {
                    m = "first"
                } else {
                    m = "normal"
                }
            }
            var u = x.get("builderIds");
            var o = x.get("builder");
            var w = r[p];
            u.title.innerHTML = "<h2>" + w.title + "</h2>";
            u.intro.innerHTML = ['<span class="num"><em>', (p + 1), "</em> / ", r.length, '</span><div class="swpt-1013">', w.intro, p + 1 == q ? ARTICLE_DATA.workinfo: "", "</div>"].join("");
            var s = p * i.itemWidth;
            var v = u.cont[i.direction == "v" ? "scrollTop": "scrollLeft"];
            x.prevLoad(p, 1, true);
            x.prevLoad(h(p + 1), 0);
            x.prevLoad(h(p - 1), 0);
            new f.animate(u.cont, [[s, s, (i.direction == "v" ? "scrollTop": "scrollLeft"), ""]], 0,
            function() {
                u.cont[i.direction == "v" ? "scrollTop": "scrollLeft"] = s
            });
            x.set("postion", s);
            window.location.hash = "p=" + Math.round(p + 1);
            var n = x.get("opt.index");
            o.list.item[n].className = "swp-item";
            o.list.item[p].className = "swp-item current";
            x.set("opt.index", p);
            if (typeof t != "undefined") {
                t(p, m)
            }
            i.move(p, m, y);
            x.setArrowTitle(p, m)
        },
        pxMove: function(n) {
            var h = this;
            var m = h.get("opt");
            var g = h.get("builder");
            var j = h.get("builderIds");
            var o = h.get("postion") + n;
            if (!n) {
                var i = j.cont[m.direction == "v" ? "scrollTop": "scrollLeft"];
                new f.animate(j.cont, [[i, o, (m.direction == "v" ? "scrollTop": "scrollLeft"), ""]], 300,
                function() {})
            } else {
                j.cont[m.direction == "v" ? "scrollTop": "scrollLeft"] = o
            }
        },
        bigResize: function(p, n, o, j) {
            if (!p) {
                return
            }
            j = j || null;
            var y = this;
            var i = y.get("opt");
            var u = y.get("builderIds");
            var x = 950;
            var q = f.winSize();
            var t = f.getPosition(u.cont);
            var w = 6000;
            var h = 145;
            if (p.width && p.height) {
                var s = p.getAttribute("o-width") || p.width;
                var r = p.getAttribute("o-height") || p.height;
                p.setAttribute("o-width", s);
                p.setAttribute("o-height", r);
                var v = "px";
                var g = Math.min(x, s);
                var m = Math.ceil(g * r / s);
                if (m > w) {
                    m = w;
                    g = Math.ceil(s * m / r)
                }
                if (g < h) {
                    g = h;
                    m = Math.ceil(r * g / s)
                }
                p.style.width = g + v;
                p.style.height = m + v
            }
        }
    });
    d.register("PlayerRender", c)
})(window); (function(a) {
    var b = "___SinaSlide___";
    var d = a[b];
    var e = d.util;
    var c = new d.Clz;
    c.include({
        init: function(g) {
            var f = this;
            f.setOpt(g);
            f.render()
        },
        setOpt: function(f) {
            this.set("opt", e.extend({
                wrap: null,
                data: null,
                index: 0,
                complete: function() {},
                error: function(g) {}
            },
            f, true))
        },
        build: function() {
            var g = this;
            var i = g.get("opt");
            var f = e.builder(i.wrap, "list-type");
            var h = f.ids;
            g.set("builder", f);
            g.set("builderIds", h)
        },
        render: function() {
            var h = this;
            var j = h.get("opt");
            var m = (function(o) {
                if (typeof o == "string") {
                    return e.byId(o)
                }
                return o
            })(j.wrap);
            var n = j.data;
            if (!m || !n) {
                return
            }
            var g = n.images;
            var f = (function(t) {
                var r = [];
                for (var q = 0,
                p = t.length; q < p; q++) {
                    var s = t[q];
                    var o = "swl-item-bottom";
                    if (q + 4 < p) {
                        o = ""
                    }
                    if (q == p - 1 || ((q + 1) % 4 == 0)) {
                        o = o + " swl-item-right"
                    }
                    if (q == p - 1 && ((q + 1) % 4 !== 0)) {
                        o = o + " swl-item-notrigth-last"
                    }
                    r.push('<li class="swl-item' + o + '" list-type="item" action-type="select" action-data="index=' + q + '"> <div action-type="mode-toggle" class="swi-hd"> <img src="http://www.sinaimg.cn/dy/deco/2013/1121/slideimg/loading_55.gif" data-src="' + s.thumb_160 + '" alt="' + s.title + '"> </div> <div class="swi-bd" action-type="mode-toggle"> <h3>' + s.title + "<span>(" + (q + 1) + "/" + p + ")</span></h3> <p>" + s.createtime + "</p> </div></li>")
                }
                return r.join("")
            })(g);
            var i = '<div class="sw-list-hd"><h1>' + n.slide.title.replace("_高清图集_全球体育", "") + "</h1><em>" + n.slide.createtime.replace("月", ".").replace("年", ".").replace("日", "") + '</em></div><div class="sw-list-bd"><ul class="clearfix">' + f + "</ul></div>";
            m.innerHTML = i;
            h.build();
            h.bindEvent();
            h.move(h.get("opt.index"))
        },
        bindEvent: function() {
            var g = this;
            var f = g.get("builder");
            var h = e.delegatedEvent(f.box);
            h.add("select", "click",
            function(j) {
                var i = j.data.index;
                g.move(i)
            });
            h.add("select", "mouseover",
            function(j) {
                var i = j.el;
                e.addClass(i, "hover")
            });
            h.add("select", "mouseout",
            function(j) {
                var i = j.el;
                e.removeClass(i, "hover")
            })
        },
    /*    move: function(i, m) {
            var h = this;
            var j = h.get("opt");
            i = parseInt(i, 10);
            var g = h.get("builder");
            var f = g.list.item;
            e.removeClass(f[j.index], "current");
            e.addClass(f[i], "current");
            h.set("opt.index", i);
            if (typeof m != "undefined") {
                m(i, status)
            }
            j.move(i, status)
        }*/
    });
    d.register("ListRender", c)
})(window); (function(a) {
    var b = "___SinaSlide___";
    var d = a[b];
    var f = d.util;
    var c = new d.Clz;
    var e = f.uaTrack;
    c.include({
        init: function(h) {
            var g = this;
            g.setOpt(h);
            g.build()
        },
        setOpt: function(g) {
            this.set("opt", f.extend({
                wrap: "",
                index: 0,
                selectIndex: 0,
                pageWidth: 560,
                itemWidth: 112,
                move: function() {}
            },
            g, true))
        },
        build: function() {
            var s = this;
            var i = s.get("opt");
            var h = (function(u) {
                if (typeof u == "string") {
                    return f.byId(u)
                }
                return u
            })(i.wrap);
            var o = i.data;
            if (!h || !o) {
                return
            }
            var n = function(u) {
                u = u || 0;
                u = parseInt(u);
                if (u < 10) {
                    u = "00" + u
                } else {
                    if (u < 100) {
                        u = "0" + u
                    }
                }
                return u
            };
            var g = (function(A) {
                var w = [];
                var z = "";
                var y = 'suda-uatrack="key=hdphotov2&value=thumb"';
                for (var v = 0,
                u = A.length; v < u; v++) {
                    var x = A[v];
                    z = x.id == SLIDE_DATA.aid ? "current": "";
                    w.push('<li scroll-type="item" class="' + z + '" action-type="select" action-data="index=' + v + '" ' + y + '><a hidefocus href="javascript:;" title="' + x.title + '"><img src="' + x.img_100_100 + '" alt="' + x.title + '" /></a></li>')
                }
                return w.join("")
            })(o);
            var t = slide_data.prev_album;
            var j = slide_data.next_album;
            var m = function(u) {
                //var v = encodeURIComponent(slide_data[u + "_album"].img_url);
                var v = slide_data[u + "_album"].img_url;
                return v;
            };
            var q = '<a href="' + t.url + '" class="swpl-group swpl-group-prev" scroll-type="groupprev" hidefocus title="上一图集" suda-uatrack="key=hdphoto_20140213&value=prephoto"><span class="bg bg-a"><span class="inner"><img src="' + m("prev") + '" alt="' + t.title + '" /></span></span><span class="bg bg-b"></span><span class="bg bg-c"></span><i>&nbsp;</i><em title="' + t.title + '">' + f.strLeft(t.title, 32) + '</em></a><a href="javascript:;" class="swpl-btn swpl-fastprev" scroll-type="fastprev" action-type="fastprev" title="上一组" hidefocus><i></i></a><div class="swp-list-cont" scroll-type="cont"><ul scroll-type="list">' + g + '</ul></div><a href="javascript:;" class="swpl-btn swpl-fastnext" scroll-type="fastnext" action-type="fastnext" hidefocus><i></i></a><a href="' + j.url + '" class="swpl-group swpl-group-next" scroll-type="groupnext" hidefocus title="下一图集" suda-uatrack="key=hdphoto_20140213&value=nextphoto"><span class="bg bg-a"><span class="inner"><img src="' + m("next") + '" alt="' + j.title + '" /></span></span><span class="bg bg-b"></span><span class="bg bg-c"></span><i>&nbsp;</i><em title="' + j.title + '">' + f.strLeft(j.title, 32) + "</em></a>";
            h.innerHTML = '<div class="swp-list">' + q + "</div>";
            var p = f.builder(i.wrap, "scroll-type");
            var r = p.ids;
            s.set("builder", p);
            s.set("builderIds", r);
            s.buildWidth();
            s.move(i.index);
            s.bindEvent()
        },
        buildWidth: function(q) {
            var r = this;
            var h = r.get("opt");
            var m = r.get("builder");
            var p = r.get("builderIds");
            var o = h.itemWidth;
            var n = m.list.item.length;
            q = q || h.pageWidth;
            q = parseInt(q - q % o);
            q = Math.max(q, o);
            q = Math.min(q, o * n);
            q = Math.min(q, 600);
            r.set("opt.pageWidth", q);
            var g = parseInt(q / o);
            var j = Math.floor(g / 2);
            var i = j * o;
            r.set("length", n);
            r.set("showLen", g);
            r.set("centerIndex", j);
            r.set("centerPos", i);
            p.cont.style[h.direction == "v" ? "height": "width"] = q + "px";
            p.list.style[h.direction == "v" ? "height": "width"] = o * n + "px"
        },
        bindEvent: function() {
            var h = this;
            var i = h.opt;
            var g = h.get("builder");
            var j = f.delegatedEvent(g.box);
            j.add("select", "click",
            function(n) {
                var m = n.data.index;
                h.select(m)
            });
            j.add("select", "mouseover",
            function(n) {
                var m = n.el;
                f.addClass(m, "hover")
            });
            j.add("select", "mouseout",
            function(n) {
                var m = n.el;
                f.removeClass(m, "hover")
            });
            j.add("next", "click",
            function(m) {
                h.next()
            });
            j.add("prev", "click",
            function(m) {
                h.prev()
            });
            j.add("fastnext", "click",
            function(m) {
                h.fastnext();
                e("hdphotov2", "small_right")
            });
            j.add("fastprev", "click",
            function(m) {
                h.fastprev();
                e("hdphotov2", "small_left")
            })
        },
        select: function(j) {
            var i = this;
            j = parseInt(j, 10);
            var m = i.get("opt");
            var h = i.get("builder");
            var g = h.list.item;
            f.removeClass(g[m.selectIndex], "current");
            f.addClass(g[j], "current");
            i.set("opt.selectIndex", j);
            i.move(j);
            m.select(j)
        },
        move: function(j, o) {
            var s = this;
            var g = s.get("opt");
            j = parseInt(j, 10);
            var m = s.get("length");
            var h = "error";
            if (j < 0) {
                j = 0
            } else {
                if (j > m - 1) {
                    j = m - 1
                }
            }
            if (j == m - 1) {
                h = "last"
            } else {
                if (j == 0) {
                    h = "first"
                } else {
                    h = "normal"
                }
            }
            var i = s.get("builder");
            var p = s.get("builderIds");
            var n = (j - s.get("centerIndex")) * g.itemWidth;
            var r = p.cont[g.direction == "v" ? "scrollTop": "scrollLeft"];
            new f.animate(p.cont, [[r, n, (g.direction == "v" ? "scrollTop": "scrollLeft"), ""]], 300,
            function() {});
            s.set("postion", n);
            f[(h == "first" ? "add": "remove") + "Class"](p.fastprev, "disabled");
            f[(h == "last" ? "add": "remove") + "Class"](p.fastnext, "disabled");
            f[(h == "first" ? "add": "remove") + "Class"](i.box, "swp-list-prev-last");
            f[(h == "last" ? "add": "remove") + "Class"](i.box, "swp-list-next-last");
            var t = p.fastnext;
            var q = p.fastprev;
            if (h == "last") {
                t.title = ""
            }
            if (h == "first") {
                q.title = ""
            }
            if (h == "normal") {
                q.title = "上一组";
                t.title = "下一组"
            }
            s.set("opt.index", j);
            if (typeof o != "undefined") {
                o(j, h)
            }
            g.move(j, h)
        },
        pxMove: function(m) {
            var h = this;
            var j = h.get("opt");
            var g = h.get("builder");
            var i = h.get("builderIds");
            var n = h.get("postion") + m;
            i.cont[j.direction == "v" ? "scrollTop": "scrollLeft"] = n
        },
        next: function() {
            this.move(this.get("opt.index") + 1)
        },
        prev: function() {
            this.move(this.get("opt.index") - 1)
        },
        fastnext: function(g) {
            var h = g || this.get("showLen");
            this.move(this.get("opt.index") + h)
        },
        fastprev: function(g) {
            var h = g || this.get("showLen");
            this.move(this.get("opt.index") - h)
        }
    });
    d.register("SmallList", c)
})(window); (function(b) {
    var c = "___SinaSlide___";
    var d = b[c];
    var e = d.util;
    var a = new d.Clz;
    a.include({
        init: function(g) {
            var f = this;
            f.setOpt(g);
            f.bindEvent()
        },
        setOpt: function(f) {
            this.set("opt", e.extend({
                up: function() {},
                down: function() {},
                left: function() {},
                right: function() {},
                enter: function() {}
            },
            f, true))
        },
        bindEvent: function() {
            var f = this;
            e.addEvent(document, "keydown",
            function(g) {
                f.keyDown(g)
            });
            e.addEvent(document, "keyup",
            function(g) {
                f.keyUp(g)
            })
        },
        keyDown: function(j) {
            if (this.get("status") == "down") {
                return
            }
            var h = this.get("opt");
            this.set("status", "up");
            j = j || window.event;
            var i = j.target || j.srcElement;
            var g = i.tagName;
            if (g == "INPUT" || g == "SELECT" || g == "TEXTAREA") {
                if (j.stopPropagation) {
                    j.stopPropagation()
                } else {
                    window.event.cancelBubble = true
                }
                return
            }
            var f = false;
            switch (j.keyCode) {
            case 39:
                h.right();
                f = true;
                break;
            case 37:
                h.left();
                f = true;
                break;
            case 38:
                h.down();
                break;
            case 40:
                h.up();
                break;
            default:
            }
            if (f) {
                if (j.preventDefault) {
                    j.preventDefault()
                } else {
                    j.returnValue = false
                }
            }
        },
        keyUp: function() {
            this.set("status", "up")
        }
    });
    d.register("Keyboard", a)
})(window); (function(b) {
    var c = "___SinaSlide___";
    var d = b[c];
    var e = d.util;
    var a = new d.Clz;
    a.include({
        init: function(g) {
            var f = this;
            f.setOpt(g);
            f.bindEvent()
        },
        setOpt: function(f) {
            this.set("opt", e.extend({
                change: function() {}
            },
            f, true))
        },
        bindEvent: function() {
            var f = this;
            f.resize();
            e.addEvent(window, "resize",
            function(g) {
                e.throttle(f.resize, f, 20)
            })
        },
        resize: function(i) {
            var g = this.get("opt");
            var h = document;
            var j = h.documentElement;
            var f = e.winSize();
            g.change(f.width, f.height)
        }
    });
    d.register("Resize", a)
})(window); (function(a) {
    var b = "___SinaSlide___";
    var d = a[b];
    var e = d.util;
    var c = new d.Clz;
    c.include({
        init: function(g) {
            var f = this;
            f.setOpt(g);
            f.iPadStatus = "ok";
            f.bindEvent()
        },
        setOpt: function(f) {
            this.set("opt", e.extend({
                wrap: "",
                move: function(g) {},
                left: function() {},
                right: function() {},
                up: function() {},
                down: function() {}
            },
            f, true))
        },
        touchstart: function(g) {
            var f = this;
            f.iPadX = g.touches[0].pageX;
            f.iPadY = g.touches[0].pageY;
            f.iPadScrollX = window.pageXOffset;
            f.iPadScrollY = window.pageYOffset;
            f.startTime = (new Date()).getTime()
        },
        touchend: function(j) {
            var f = this;
            var g = f.get("opt");
            if (f.iPadStatus != "touch") {
                return
            }
            f.iPadStatus = "ok";
            var n = f.iPadX - f.iPadLastX;
            var m = f.iPadY - f.iPadLastY;
            var i = (new Date()).getTime() - f.startTime;
            var h = n / m;
            if (n < 0) {
                g.left(n, i, h)
            } else {
                g.right(n, i, h)
            }
            if (m < 0) {
                g.down(m, i, h)
            } else {
                g.up(m, i, h)
            }
        },
        touchmove: function(h) {
            var f = this;
            var g = f.get("opt");
            if (h.touches.length > 1) {
                touchend()
            }
            f.iPadLastX = h.touches[0].pageX;
            var j = f.iPadX - f.iPadLastX;
            f.iPadLastY = h.touches[0].pageY;
            var i = f.iPadY - f.iPadLastY;
            if (f.iPadStatus == "ok") {
                if (f.iPadScrollY == a.pageYOffset && f.iPadScrollX == a.pageXOffset && Math.abs(j) > 20) {
                    f.iPadStatus = "touch"
                } else {
                    if (f.iPadScrollX == a.pageXOffset && f.iPadScrollY == a.pageYOffset && Math.abs(i) > 20) {
                        f.iPadStatus = "touch"
                    } else {
                        return
                    }
                }
            }
            g.move(j, i)
        },
        bindEvent: function() {
            if (typeof(a.ontouchstart) === "undefined") {
                return
            }
            var f = this;
            var g = f.get("opt");
            var h = (function(j) {
                if (typeof j == "string") {
                    return e.byId(j)
                }
                return j
            })(g.wrap);
            if (!h) {
                return
            }
            var i = d.util.addEvent;
            i(h, "touchstart",
            function(j) {
                f.touchstart(j)
            });
            i(h, "touchmove",
            function(j) {
                f.touchmove(j);
                d.util.preventDefault(j)
            });
            i(h, "touchend",
            function(j) {
                f.touchend(j)
            })
        }
    });
    d.register("Touch", c)
})/*(window); (function(a) {
    var b = "___SinaSlide___";
    var d = a[b];
    var e = d.util;
    var c = new d.Clz;
    c.include({
        init: function(g) {
            var f = this;
            f.setOpt(g);
            f.bindEvent()
        },
        setOpt: function(f) {
            this.set("opt", e.extend({
                pagerId: null,
                listId: null,
                mode: "player",
                tab: function() {}
            },
            f, true))
        },
        bindEvent: function() {
            var f = this;
            var g = e.delegatedEvent(document.body);
            f.tab();
            g.add("mode-toggle", "click",
            function(h) {
                f.toggle();
                return false
            })
        },
        tab: function() {
            var i = this.get("opt.mode");
            var h = "";
            var g = "none";
            var f = this.get("opt");
            if (i == "player") {
                h = "none";
                g = ""
            }
            e[i == "player" ? "removeClass": "addClass"](e.byId(f.playerId), "sw-player-blur");
            e.byId(f.listId).style.display = h;
            f.tab(i)
        },
        toggle: function() {
            var f = this;
            var g = f.get("opt.mode");
            g = g == "player" ? "list": "player";
            f.set("opt.mode", g);
            f.tab(g)
        }
    });
    d.register("Mode", c)
})*/(window); (function(b) {
    var d = "___SinaSlide___";
    var e = b[d];
    var f = e.util;
    var c = new e.Clz;
    var a = !-[1, ];
    c.include({
        init: function(h) {
            var g = this;
            g.setOpt(h);
            g.soundObj = null;
            g.wrap = null;
            g.setSoundObj()
        },
        setOpt: function(g) {
            this.set("opt", f.extend({
                src: "",
                altSrc: "",
                loop: false
            },
            g, true))
        },
        setSoundObj: function() {
            var g = this;
            var i = g.get("opt");
            if (!i.src || !i.altSrc) {
                return
            }
            var j = g.wrap;
            if (!j) {
                j = document.createElement("div");
                j.style.height = "0";
                j.style.overflow = "hidden";
                document.getElementsByTagName("body")[0].appendChild(j);
                g.wrap = j
            }
            if (g.soundObj) {
                if (a) {
                    g.soundObj.src = i.src
                } else {
                    g.soundObj.innerHTML = '<source src="' + i.src + '" /><source src="' + i.altSrc + '" />'
                }
            } else {
                if (a) {
                    var h = document.createElement("bgsound");
                    h.volume = -1000;
                    h.loop = "1";
                    h.src = i.src;
                    j.appendChild(h);
                    g.soundObj = h
                } else {
                    j.innerHTML = '<audio preload="auto" autobuffer><source src="' + i.src + '" /><source src="' + i.altSrc + '" /></audio>';
                    g.soundObj = j.getElementsByTagName("audio")[0]
                }
            }
        },
        play: function() {
            var g = this.soundObj;
            var h = this.get("opt.src");
            if (g) {
                if (a) {
                    g.volume = 1;
                    g.src = "";
                    g.src = h
                } else {
                    g.play()
                }
            }
        }
    });
    e.register("Sound", c)
})(window); (function(b) {
    var c = "___SinaSlide___";
    var d = b[c];
    var e = d.util;
    var a = new d.Clz;
    a.include({
        init: function(g) {
            var f = this;
            f.inited = false;
            f.setOpt(g);
            f.render()
        },
        setOpt: function(f) {
            this.set("opt", e.extend({
                wrap: null,
                data: null,
                index: 0,
                status: "window",
                flash2js: function(g) {},
                js2flash: function() {
                    return 0
                },
                js2flashNext: function() {},
                js2flashPrev: function() {},
                pv_fromflash: function(h, g) {}
            },
            f, true))
        },
        render: function() {
            var f = this;
            var g = f.get("opt");
            var h = (function(j) {
                if (typeof j == "string") {
                    return e.byId(j)
                }
                return j
            })(g.wrap);
            var i = g.data;
            if (!h || !i) {
                return
            }
            f.set("data", i);
            f.setFullFlash(h, i);
            f.bindEvent(h)
        },
        setFullFlash: function(j, x) {
            if (!j || !x) {
                return
            }
            var y = "http://www.sinaimg.cn/cj/yw/flash/HDFS140409a.swf";
            var w = "fullscreenObj";
            if (window.location.hostname == "dc.sina.com.cn") {
                y = "http://dc.sina.com.cn/Sina/ui/project/slide/trunk/20131128/demo/loop_100629.swf"
            }
            var m = x.images;
            var q = "",
            o = "";
            for (var r = 0,
            t = m.length; r < t; r++) {
                var u = m[r];
                var n = u.image_url;
                var z = u.title;
                var s = u.intro;
                if (q != "") {
                    q += "|"
                }
                q += n;
                if (o != "") {
                    o += "-jsToflaShsTr-"
                }
                o += encodeURIComponent(z) + "#　　" + encodeURIComponent(s.replace(/<.*?>/g, ""))
            }
            var g = x.slide.title.replace("_高清图集_全球体育", "");
            var f;
            if (typeof(x.slide.long_intro) != "undefined") {
                f = encodeURIComponent(x.slide.long_intro)
            } else {
                f = ""
            }
            var p = {
                mylinkpic: q,
                mytxt: o,
                infotxt: g,
                longtext: f
            };
            var v = {};
            v.quality = "high";
            v.bgcolor = "#000000";
            v.allowscriptaccess = "always";
            v.allowfullscreen = "true";
            v.wmode = "transparent";
            var h = {};
            h.id = w;
            h.name = w;
            h.align = "middle";
            swfobject.embedSWF(y, "SI_FullScreenFlash", "90", "16", "10", "", p, v, h)
        },
        bindEvent: function() {
            var f = this;
            var g = f.get("opt");
            b.flash_to_js = function(h) {
                f.set("opt.status", "window");
                g.flash2js(h)
            };
            b.js_to_flash = function() {
                f.set("opt.status", "flash");
                return g.js2flash()
            };
            b.next_jstoflash = function() {
                g.js2flashNext()
            };
            b.pre_jstoflash = function() {
                g.js2flashPrev()
            };
            b.pv_fromflash = function(i, h) {
                g.pv_fromflash(i, h)
            }
        }
    });
    d.register("FullScreen", a)
})(window);