/*!
* sina.com.cn/license
* svn:../ui/project/slide/trunk/20131128 高清图
* 20140909105513
* [${p_id},${t_id},${d_id}] published at ${publishdate} ${publishtime}
*/
(function(r) {
    var n = "___SinaSlide___";
    var c = r[n];
    var b = c.util;
    var k = c.app;
    var d = c.util.winSize();
    var m = typeof(r.ontouchstart) === "undefined" ? "click": "touchstart";
    PAGE = window.PAGE || {};
    k.toComment(1500);
    var l = function(t) {
        var v = new RegExp("[\\?&]" + t + "=([^&#]*)");
        var u = v.exec(location.search);
        return u ? u[1] : ""
    };
    var i = function(t) {
        var u = location.hash;
        if (!u) {
            return ""
        }
        hashs = u.match(new RegExp(t + "=([^&]*)"));
        if (!hashs || hashs.length == 0) {
            return ""
        }
        return hashs[1]
    };
    var s = function(y) {
        var x = SLIDE_DATA.aid;
        var u = 0;
        for (var v = 0,
        t = y.length; v < t; v++) {
            var w = y[v];
            if (w.id == x) {
                u = v;
                break
            }
        }
        return u
    };
    var j = (function() {
        var w = location.hash;
        if (!w) {
            return 1
        }
        var t = location.hash.match(/p=(\d+)/i);
        var u = 1;
        try {
            u = t[1]
        } catch(v) {
            u = 1
        }
        if (isNaN(u)) {
            u = 1
        }
        if (u < 1) {
            u = 1
        }
        return u
    })();
    var c = r.SinaSlide;
    var f = (function() {
        var v = null;
        var x = true;
        var t = SLIDE_DATA.pvurl;
        var w = SLIDE_DATA.autoPvurl;
        var y = j - 1;
        var u = function() {
            var z = document.createElement("iframe");
            z.style.height = "0px";
            z.style.width = "1px";
            z.style.overflow = "hidden";
            z.frameBorder = 0;
            z.style.position = "absolute";
            z.style.top = "-100px";
            document.body.appendChild(z);
            return z
        };
        return function(A, z) {
            if (!t || x || (y == z)) {
                x = false;
                return
            }
            y = z;
            if (SLIDE_DATA.onMove && typeof SLIDE_DATA.onMove == "function") {
                SLIDE_DATA.onMove(z)
            }
            if (!v) {
                v = u()
            }
            var B = "?p=" + A + "&hdid=" + SLIDE_DATA.ch + "_" + SLIDE_DATA.sid + "_" + SLIDE_DATA.aid + "&pageid=" + (z + 1) + "&r=" + Math.random();
            v.src = t + B
        }
    })();
    var o = (function() {
        var t = null;
        return function(u) {
            t = t || b.byId("SI_Original_Lnk");
            if (t) {
                t.href = u
            }
        }
    })();
    var a = (function() {
        var t = null;
        var u = null;
        var v = true;
        var w = j - 1;
        return function(x) {
            if (v || (w == x)) {
                v = false;
                return
            }
            w = x;
            if (!t) {
                t = new c.Sound({
                    src: SLIDE_DATA.soundSrc,
                    altSrc: SLIDE_DATA.soundAltSrc
                })
            }
            t.play()
        }
    })();
    var p = function() {
        var t = false;
        if (window.epidiaAdValid && typeof epidiaAdValid == "function") {
            try {
                t = epidiaAdValid(epidiaAdResource.end)
            } catch(u) {}
        }
        if (window.PAGE && PAGE.hasEndAD) {
            t = PAGE.hasEndAD
        }
        return t ? true: false
    };
    var e = function(w) {
        var t = "widthout_ad";
        var v = w + "_";
        if (p()) {
            t = "width_ad"
        }
        v += t;
        try {
            if (window._S_uaTrack) {
                _S_uaTrack("new_photo_stats", v)
            }
        } catch(u) {}
    };
    var h = (function() {
        var C = false;
        var A = null;
        var x = null;
        var t = null;
        var u = "body-end-show";
        var v = false;
        var B = function() {
            b.addClass(document.body, u);
            t && (t.style.visibility = "visible");
            e("pageview")
        };
        var y = function() {
            b.removeClass(document.body, u);
            t && (t.style.visibility = "hidden")
        };
        var w = function() {
            var D = slide_data.next_album.url;
            setTimeout(function() {
                location.href = D
            },
            0)
        };
        var z = function() {
            var D = b.delegatedEvent(t);
            D.add("end-close", "click",
            function(E) {
                y();
                e("close")
            });
            D.add("end-replay", "click",
            function(E) {
                PAGE.Player && PAGE.Player.move(0)
            });
            D.add("end-next-album", "click",
            function(E) {
                w()
            })
        };
        return function(F, D) {
            if (!t) {
                t = b.byId("SI_SlideEnd")
            }
            var E = window.___SinaRecommender___;
            if (typeof E == "undefined" || !t) {
                return
            }
            if (F > (D - 3)) {
                clearTimeout(A);
                setTimeout(function() {
                    if (C) {
                        return
                    }
                    E.slide.render.init(p());
                    z();
                    C = true
                },
                800)
            }
            if (x == F && F != 0) {
                if (!v) {
                    B()
                } else {
                    w()
                }
                v = true
            } else {
                y();
                v = false
            }
            x = F
        }
    })();
    var g = function(t) {
        if (isNaN(t)) {
            return
        }
        PAGE.Player && (PAGE.Player.get("opt.index") !== t) && PAGE.Player.move(t);
        //PAGE.List && (PAGE.List.get("opt.index") !== t) && PAGE.List.move(t);
        PAGE.SmallList && (PAGE.SmallList.get("opt.selectIndex") !== t) && PAGE.SmallList.select(t)
    };
    var q = function(x, t) {
        if (PAGE.Player) {
            var w = PAGE.Player.get("opt.index");
            var v = PAGE.Player.get("builder");
            var u = v.list.item[w].getElementsByTagName("img")[0];
            PAGE.Player.bigResize(u, x, t, true)
        }
    };
    PAGE.Resize = new c.Resize({
        change: function(u, t) {
            q(u, t)
        }
    });
    PAGE.Slide = new c.Loader({
        api: "http://slide.news.sina.com.cn/interface/slide_interface.php?ch=" + SLIDE_DATA.ch + "&sid=" + SLIDE_DATA.sid + "&id=" + SLIDE_DATA.aid + "&active_size=100_100&range=" + SLIDE_DATA.range + "&key=" + SLIDE_DATA.key,
        dataType: "js",
        data: r.slide_data ? slide_data: null,
        loadComplete: function() {
            j = j < 0 ? 0 : j;
            j = (function() {
                var A = slide_data.images;
                var w = A.length;
                var z = Math.min(j, w);
                var x = window.location.search.match(/img=(\d+)/i);
                if (x) {
                    x = x[1];
                    z = 0;
                    for (var y = 0; y < w; y++) {
                        if (parseInt(A[y]["id"]) == parseInt(x)) {
                            z = y + 1;
                            break
                        }
                    }
                }
                return z
            })(j);
            PAGE.List = new c.ListRender({
                wrap: "SI_List",
                data: slide_data,
                index: j - 1,
                move: function(w) {
                    g(w)
                }
            });
            PAGE.Player = new c.PlayerRender({
                wrap: "SI_Player",
                data: slide_data,
                index: j - 1,
                direction: "h",
                itemWidth: 950,
                pageWidth: 950,
                move: function(A, y, x) {
                    var D = c.util.byId("bdshare");
                    var B = slide_data.images[A];
                    var C = (B.intro || "").replace(/<\/?[^>]*>/g, "");
                    var z = location.href + "?";
                    if (D && B) {
                        D.setAttribute("data", "{url:'" + z + "',text:'" + (SLIDE_DATA.shareTopic || "") + B.title + "（组图）——" + C + "',pic:'" + B.image_url + "'}")
                    }
                    var w = (function() {
                        var F = new RegExp("[\\?&]newsid=([^&#]*)");
                        var E = F.exec(B.comment);
                        return E ? E[1] : ""
                    })();
                    ARTICLE_DATA.customNewsId = w;
                    ARTICLE_DATA.customShareUrl = location.href.split("#")[0] + "?p=" + (A + 1);
                    ARTICLE_DATA.customImgUrl = B.image_url;
                    if (x && A == 0) {
                        var z = slide_data.prev_album.url;
                        if (y === "last") {
                            z = slide_data.next_album.url
                        }
                        location.href = z
                    }
                    o(B.image_url);
                    f(B.image_url, A);
                    a(A);
                    h(A, slide_data.images.length);
                    g(A)
                },
                mousemove: function(w) {}
            });
            PAGE.SmallList = new c.SmallList({
                wrap: "SI_SmallList",
                data: slide_data.images || [],
                firstIndex: j - 1,
                pageWidth: 120 * 5,
                itemWidth: 120,
                direction: "h",
                move: function(w) {},
                select: function(w) {
                    g(w)
                }
            });
            PAGE.FullScreen = new c.FullScreen({
                wrap: b.byId("SI_FullScreenFlash"),
                data: slide_data,
                flash2js: function(y) {
                    y = y + "";
                    var w = y.split("|");
                    var x = parseInt(w[0]);
                    if (PAGE.Player) {
                        if (PAGE.Player.get("opt.index") !== x) {
                            PAGE.Player.move(x)
                        }
                    }
                },
                js2flash: function() {
                    var w = PAGE.Player.get("opt.index");
                    return w + "|" + 5
                },
                js2flashNext: function() {},
                js2flashPrev: function() {},
                pv_fromflash: function(x, w) {
                    f(slide_data.images[w].image_url, w, x)
                }
            });
            PAGE.Touch1 = new c.Touch({
                wrap: "SI_SmallList",
                move: function(w, z) {
                    if (Math.abs(w / z) < 1) {
                        return
                    }
                    PAGE.SmallList && PAGE.SmallList.pxMove(w)
                },
                right: function(w, z, y) {
                    if (z > 600 || Math.abs(y) < 1) {
                        PAGE.SmallList && PAGE.SmallList.pxMove(0);
                        return
                    }
                    PAGE.SmallList && PAGE.SmallList.fastnext()
                },
                left: function(w, z, y) {
                    if (z > 600 || Math.abs(y) < 1) {
                        PAGE.SmallList && PAGE.SmallList.pxMove(0);
                        return
                    }
                    PAGE.SmallList && PAGE.SmallList.fastprev()
                }
            });
            PAGE.Touch2 = new c.Touch({
                wrap: "SI_Cont",
                move: function(w, z) {
                    if (Math.abs(w / z) < 1) {
                        return
                    }
                    PAGE.Player && PAGE.Player.pxMove(w)
                },
                right: function(w, A, z) {
                    if (A > 600 || Math.abs(z) < 1) {
                        PAGE.Player && PAGE.Player.pxMove(0);
                        return
                    }
                    var y = PAGE.Player.get("opt.index");
                    y++;
                    PAGE.Player && PAGE.Player.move(y)
                },
                left: function(w, A, z) {
                    if (A > 600 || Math.abs(z) < 1) {
                        PAGE.Player && PAGE.Player.pxMove(0);
                        return
                    }
                    var y = PAGE.Player.get("opt.index");
                    y--;
                    PAGE.Player && PAGE.Player.move(y)
                }
            });
            window.audiojs && audiojs.events.ready(function() {
                var x = document.getElementsByTagName("audio")[0];
                if (!x) {
                    return
                }
                var w = audiojs.create(x, {
                    css: false,
                    createPlayer: {
                        markup: false,
                        playPauseClass: "sw-audio-play-pause",
                        scrubberClass: "sw-audio-scrubber",
                        progressClass: "sw-audio-progress",
                        loaderClass: "sw-audio-loaded",
                        timeClass: "sw-audio-time",
                        durationClass: "sw-audio-duration",
                        playedClass: "sw-audio-played",
                        errorMessageClass: "sw-audio-error-message",
                        playingClass: "sw-audio-playing",
                        loadingClass: "sw-audio-loading",
                        errorClass: "sw-audio-error"
                    }
                })
            });
            PAGE.Keyboard = new c.Keyboard({
                left: function() {
                    PAGE.Player.prev()
                },
                right: function() {
                    PAGE.Player.next()
                }
            });
            var v = (function() {
                var w = 0;
                return function() {
                    if (w) {
                        return
                    }
                    var y = document.getElementById("SI_List");
                    var B = y.getElementsByTagName("img");
                    if (B && B.length > 0) {
                        for (var z = B.length - 1; z >= 0; z--) {
                            var x = B[z];
                            var A = x.getAttribute("data-src");
                            if (A) {
                                x.removeAttribute("data-src");
                                x.src = A
                            }
                        }
                    }
                    w = 1
                }
            })();
            /*PAGE.Mode = new c.Mode({
                playerId: "SI_Player",
                listId: "SI_List",
                tab: function(z) {
                    var y = b.byId("SI_Mode_Trigger");
                    var x = b.byId("SI_Player").parentNode;
                    if (!y) {
                        return
                    }
                    var w = y.getElementsByTagName("a");
                    if (z == "list") {
                        w[0].style.display = "none";
                        w[1].style.display = "";
                        v();
                        b.addClass(x, "slide-wrap-show-list")
                    } else {
                        w[1].style.display = "none";
                        w[0].style.display = "";
                        b.removeClass(x, "slide-wrap-show-list")
                    }
                }
            });*/ (function() {
                var A = r.SinaSlide;
                var w = A.util;
                var x = w.byId;
                var z = x("SI_Commnet_Trigger");
                var D = x("SI_Comment");
                var C = x("SI_Comment_Close");
                var B = w.addEvent;
                var y = function() {
                    SAB.evt.custEvent.add(SAB, "ce_cmntRenderEnd",
                    function() {
                        var F = SAB.job.cmntList;
                        var E = F.data.count ? F.data.count.total: 0;
                        z.innerHTML = "<i></i>评论 " + b.numSplit(E)
                    })
                };
                if (D) {
                    if (r.SAB) {
                        y()
                    } else {
                        b.jsLoad("http://ent.sina.com.cn/js/470/20130123/comment.js",
                        function() {
                            y()
                        })
                    }
                }
            })();
            k.getAndBindLike(b.byId("SI_Heart_Vote"));
            k.bindDownload(b.byId("SI_Download"));
            var u = b.byId("SI_Weibolist");
            k.renderWeiboList(u, slide_data.slide.weibo_list);
            /*if (typeof slide_data.slide.weibo_list == "undefined" && (typeof slide_data.slide.long_intro == "undefined" || slide_data.slide.long_intro == "")) {
                u.parentNode.style.display = "none"
            }*/
            if (typeof slide_data.slide.long_intro !== "undefined" && slide_data.slide.long_intro !== "") {
                var t = document.getElementsByTagName("body")[0];
                b.addClass(t, "slide-has-intro")
            }
        }
    });
    c.register("PAGE", PAGE)
})(window);