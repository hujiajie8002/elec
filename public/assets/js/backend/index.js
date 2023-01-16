define(['jquery', 'bootstrap', 'backend', 'addtabs', 'adminlte', 'form'], function ($, undefined, Backend, undefined, AdminLTE, Form) {
    var Controller = {
        index: function () {
            //双击重新加载页面
            $(document).on("dblclick", ".sidebar-menu li > a", function (e) {
                $("#con_" + $(this).attr("addtabs") + " iframe").attr('src', function (i, val) {
                    return val;
                });
                e.stopPropagation();
            });

            //修复在移除窗口时下拉框不隐藏的BUG
            $(window).on("blur", function () {
                $("[data-toggle='dropdown']").parent().removeClass("open");
                if ($("body").hasClass("sidebar-open")) {
                    $(".sidebar-toggle").trigger("click");
                }
            });

            //快捷搜索
            $(".menuresult").width($("form.sidebar-form > .input-group").width());
            var searchResult = $(".menuresult");
            $("form.sidebar-form").on("blur", "input[name=q]", function () {
                searchResult.addClass("hide");
            }).on("focus", "input[name=q]", function () {
                if ($("a", searchResult).size() > 0) {
                    searchResult.removeClass("hide");
                }
            }).on("keyup", "input[name=q]", function () {
                searchResult.html('');
                var val = $(this).val();
                var html = [];
                if (val != '') {
                    $("ul.sidebar-menu li a[addtabs]:not([href^='javascript:;'])").each(function () {
                        if ($("span:first", this).text().indexOf(val) > -1 || $(this).attr("py").indexOf(val) > -1 || $(this).attr("pinyin").indexOf(val) > -1) {
                            html.push('<a data-url="' + $(this).attr("href") + '" href="javascript:;">' + $("span:first", this).text() + '</a>');
                            if (html.length >= 100) {
                                return false;
                            }
                        }
                    });
                }
                $(searchResult).append(html.join(""));
                if (html.length > 0) {
                    searchResult.removeClass("hide");
                } else {
                    searchResult.addClass("hide");
                }
            });
            //快捷搜索点击事件
            $("form.sidebar-form").on('mousedown click', '.menuresult a[data-url]', function () {
                Backend.api.addtabs($(this).data("url"));
            });

            //切换左侧sidebar显示隐藏
            $(document).on("click fa.event.toggleitem", ".sidebar-menu li > a", function (e) {
                var nextul = $(this).next("ul");
                if (nextul.length == 0 && (!$(this).parent("li").hasClass("treeview") || ($("body").hasClass("multiplenav") && $(this).parent().parent().hasClass("sidebar-menu")))) {
                    $(".sidebar-menu li").removeClass("active");
                }
                //当外部触发隐藏的a时,触发父辈a的事件
                if (!$(this).closest("ul").is(":visible")) {
                    //如果不需要左侧的菜单栏联动可以注释下面一行即可
                    $(this).closest("ul").prev().trigger("click");
                }

                var visible = nextul.is(":visible");
                if (!visible) {
                    $(this).parents("li").addClass("active");
                } else {
                }
                e.stopPropagation();
            });

            //清除缓存
            $(document).on('click', "ul.wipecache li a,a.wipecache", function () {
                $.ajax({
                    url: 'ajax/wipecache',
                    dataType: 'json',
                    data: {type: $(this).data("type")},
                    cache: false,
                    success: function (ret) {
                        if (ret.hasOwnProperty("code")) {
                            var msg = ret.hasOwnProperty("msg") && ret.msg != "" ? ret.msg : "";
                            if (ret.code === 1) {
                                Toastr.success(msg ? msg : __('Wipe cache completed'));
                            } else {
                                Toastr.error(msg ? msg : __('Wipe cache failed'));
                            }
                        } else {
                            Toastr.error(__('Unknown data format'));
                        }
                    }, error: function () {
                        Toastr.error(__('Network error'));
                    }
                });
            });

            //全屏事件
            $(document).on('click', "[data-toggle='fullscreen']", function () {
                var doc = document.documentElement;
                if ($(document.body).hasClass("full-screen")) {
                    $(document.body).removeClass("full-screen");
                    document.exitFullscreen ? document.exitFullscreen() : document.mozCancelFullScreen ? document.mozCancelFullScreen() : document.webkitExitFullscreen && document.webkitExitFullscreen();
                } else {
                    $(document.body).addClass("full-screen");
                    doc.requestFullscreen ? doc.requestFullscreen() : doc.mozRequestFullScreen ? doc.mozRequestFullScreen() : doc.webkitRequestFullscreen ? doc.webkitRequestFullscreen() : doc.msRequestFullscreen && doc.msRequestFullscreen();
                }
            });

            var multiplenav = $("#secondnav").size() > 0 ? true : false;
            var firstnav = $("#firstnav .nav-addtabs");
            var nav = multiplenav ? $("#secondnav .nav-addtabs") : firstnav;

            //刷新菜单事件
            $(document).on('refresh', '.sidebar-menu', function () {
                Fast.api.ajax({
                    url: 'index/index',
                    data: {action: 'refreshmenu'},
                    loading: false
                }, function (data) {
                    $(".sidebar-menu li:not([data-rel='external'])").remove();
                    $(".sidebar-menu").prepend(data.menulist);
                    if (multiplenav) {
                        firstnav.html(data.navlist);
                    }
                    $("li[role='presentation'].active a", nav).trigger('click');
                    return false;
                }, function () {
                    return false;
                });
            });

            if (multiplenav) {
                firstnav.css("overflow", "inherit");
                //一级菜单自适应
                $(window).resize(function () {
                    var siblingsWidth = 0;
                    firstnav.siblings().each(function () {
                        siblingsWidth += $(this).outerWidth();
                    });
                    firstnav.width(firstnav.parent().width() - siblingsWidth);
                    firstnav.refreshAddtabs();
                });

                //点击顶部第一级菜单栏
                firstnav.on("click", "li a", function () {
                    $("li", firstnav).removeClass("active");
                    $(this).closest("li").addClass("active");
                    $(".sidebar-menu > li[pid]").addClass("hidden");
                    if ($(this).attr("url") == "javascript:;") {
                        var sonlist = $(".sidebar-menu > li[pid='" + $(this).attr("addtabs") + "']");
                        sonlist.removeClass("hidden");
                        var sidenav;
                        var last_id = $(this).attr("last-id");
                        if (last_id) {
                            sidenav = $(".sidebar-menu > li[pid='" + $(this).attr("addtabs") + "'] a[addtabs='" + last_id + "']");
                        } else {
                            sidenav = $(".sidebar-menu > li[pid='" + $(this).attr("addtabs") + "']:first > a");
                        }
                        if (sidenav) {
                            sidenav.attr("href") != "javascript:;" && sidenav.trigger('click');
                        }
                    } else {

                    }
                });

                var mobilenav = $(".mobilenav");
                $("#firstnav .nav-addtabs li a").each(function () {
                    mobilenav.append($(this).clone().addClass("btn btn-app"));
                });

                //点击移动端一级菜单
                mobilenav.on("click", "a", function () {
                    $("a", mobilenav).removeClass("active");
                    $(this).addClass("active");
                    $(".sidebar-menu > li[pid]").addClass("hidden");
                    if ($(this).attr("url") == "javascript:;") {
                        var sonlist = $(".sidebar-menu > li[pid='" + $(this).attr("addtabs") + "']");
                        sonlist.removeClass("hidden");
                    }
                });

                //点击左侧菜单栏
                $(document).on('click', '.sidebar-menu li a[addtabs]', function (e) {
                    var parents = $(this).parentsUntil("ul.sidebar-menu", "li");
                    var top = parents[parents.length - 1];
                    var pid = $(top).attr("pid");
                    if (pid) {
                        var obj = $("li a[addtabs=" + pid + "]", firstnav);
                        var last_id = obj.attr("last-id");
                        if (!last_id || last_id != pid) {
                            obj.attr("last-id", $(this).attr("addtabs"));
                            if (!obj.closest("li").hasClass("active")) {
                                obj.trigger("click");
                            }
                        }
                        mobilenav.find("a").removeClass("active");
                        mobilenav.find("a[addtabs='" + pid + "']").addClass("active");
                    }
                });
            }

            //这一行需要放在点击左侧链接事件之前
            var addtabs =  null;

            //绑定tabs事件,如果需要点击强制刷新iframe,则请将iframeForceRefresh置为true,iframeForceRefreshTable只强制刷新表格
            nav.addtabs({iframeHeight: "100%", iframeForceRefresh: false, iframeForceRefreshTable: true, nav: nav});

            if ($("ul.sidebar-menu li.active a").size() > 0) {
                $("ul.sidebar-menu li.active a").trigger("click");
            } else {
                if (multiplenav) {
                    $("li:first > a", firstnav).trigger("click");
                } else {
                    $("ul.sidebar-menu li a[url!='javascript:;']:first").trigger("click");
                }
            }

            //如果是刷新操作则直接返回刷新前的页面
            if (Config.referer) {

                    //刷新页面后跳到到刷新前的页面
                    Backend.api.addtabs(Config.referer);

            }

            var my_skins = [
                "skin-blue",
                "skin-black",
                "skin-red",
                "skin-yellow",
                "skin-purple",
                "skin-green",
                "skin-blue-light",
                "skin-black-light",
                "skin-red-light",
                "skin-yellow-light",
                "skin-purple-light",
                "skin-green-light",
                "skin-black-blue",
                "skin-black-purple",
                "skin-black-red",
                "skin-black-green",
                "skin-black-yellow",
                "skin-black-pink",
            ];
            setup();

            function change_layout(cls) {
                $("body").toggleClass(cls);
                AdminLTE.layout.fixSidebar();
                //Fix the problem with right sidebar and layout boxed
                if (cls == "layout-boxed")
                    AdminLTE.controlSidebar._fix($(".control-sidebar-bg"));
                if ($('body').hasClass('fixed') && cls == 'fixed') {
                    AdminLTE.pushMenu.expandOnHover();
                    AdminLTE.layout.activate();
                }
                AdminLTE.controlSidebar._fix($(".control-sidebar-bg"));
                AdminLTE.controlSidebar._fix($(".control-sidebar"));
            }

            function change_skin(cls) {

            }

            function setup() {
                var tmp = localStorage.getItem('skin');
                if (tmp && $.inArray(tmp, my_skins) != -1)
                    change_skin(tmp);

                // 皮肤切换
                $("[data-skin]").on('click', function (e) {
                    if ($(this).hasClass('knob'))
                        return;
                    e.preventDefault();
                    change_skin($(this).data('skin'));
                });

                // 布局切换
                $("[data-layout]").on('click', function () {
                    change_layout($(this).data('layout'));
                });

                // 切换子菜单显示和菜单小图标的显示
                $("[data-menu]").on('click', function () {
                    if ($(this).data("menu") == 'show-submenu') {
                        $("ul.sidebar-menu").toggleClass("show-submenu");
                    } else {
                        nav.toggleClass("disable-top-badge");
                    }
                });

                // 右侧控制栏切换
                $("[data-controlsidebar]").on('click', function () {
                    change_layout($(this).data('controlsidebar'));
                    var slide = !AdminLTE.options.controlSidebarOptions.slide;
                    AdminLTE.options.controlSidebarOptions.slide = slide;
                    if (!slide)
                        $('.control-sidebar').removeClass('control-sidebar-open');
                });

                // 右侧控制栏背景切换
                $("[data-sidebarskin='toggle']").on('click', function () {
                    var sidebar = $(".control-sidebar");
                    if (sidebar.hasClass("control-sidebar-dark")) {
                        sidebar.removeClass("control-sidebar-dark")
                        sidebar.addClass("control-sidebar-light")
                    } else {
                        sidebar.removeClass("control-sidebar-light")
                        sidebar.addClass("control-sidebar-dark")
                    }
                });

                // 菜单栏展开或收起
                $("[data-enable='expandOnHover']").on('click', function () {
                    $(this).attr('disabled', true);
                    AdminLTE.pushMenu.expandOnHover();
                    if (!$('body').hasClass('sidebar-collapse'))
                        $("[data-layout='sidebar-collapse']").click();
                });

                // 重设选项
                if ($('body').hasClass('fixed')) {
                    $("[data-layout='fixed']").attr('checked', 'checked');
                }
                if ($('body').hasClass('layout-boxed')) {
                    $("[data-layout='layout-boxed']").attr('checked', 'checked');
                }
                if ($('body').hasClass('sidebar-collapse')) {
                    $("[data-layout='sidebar-collapse']").attr('checked', 'checked');
                }
                if ($('ul.sidebar-menu').hasClass('show-submenu')) {
                    $("[data-menu='show-submenu']").attr('checked', 'checked');
                }
                if (nav.hasClass('disable-top-badge')) {
                    $("[data-menu='disable-top-badge']").attr('checked', 'checked');
                }

            }

            $(window).resize();

        },
        login: function () {
            var lastlogin = localStorage.getItem("lastlogin");
            if (lastlogin) {
                lastlogin = JSON.parse(lastlogin);
                $("#profile-img").attr("src", Backend.api.cdnurl(lastlogin.avatar));
                $("#profile-name").val(lastlogin.username);
            }

            //让错误提示框居中
            Fast.config.toastr.positionClass = "toast-top-center";

            //本地验证未通过时提示
            $("#login-form").data("validator-options", {
                invalid: function (form, errors) {
                    $.each(errors, function (i, j) {
                        Toastr.error(j);
                    });
                },
                target: '#errtips'
            });

            //为表单绑定事件
            // Form.api.bindevent($("#login-form"), function (data) {
            //     localStorage.setItem("lastlogin", JSON.stringify({
            //         id: data.id,
            //         username: data.username,
            //         avatar: data.avatar
            //     }));
            //     location.href = Backend.api.fixurl(data.url);
            // }, function (data) {
            //     $("input[name=captcha]").next(".input-group-addon").find("img").trigger("click");
            // });


















            /*
 * A JavaScript implementation of the RSA Data Security, Inc. MD5 Message
 * Digest Algorithm, as defined in RFC 1321.
 * Version 2.1 Copyright (C) Paul Johnston 1999 - 2002.
 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * Distributed under the BSD License
 * See http://pajhome.org.uk/crypt/md5 for more info.
 */

            /*
             * Configurable variables. You may need to tweak these to be compatible with
             * the server-side, but the defaults work in most cases.
             */
            var hexcase = 0;  /* hex output format. 0 - lowercase; 1 - uppercase        */
            var b64pad  = ""; /* base-64 pad character. "=" for strict RFC compliance   */
            var chrsz   = 8;  /* bits per input character. 8 - ASCII; 16 - Unicode      */

            /*
             * These are the functions you'll usually want to call
             * They take string arguments and return either hex or base-64 encoded strings
             */
            function hex_md5(s){ return binl2hex(core_md5(str2binl(s), s.length * chrsz));}
            function b64_md5(s){ return binl2b64(core_md5(str2binl(s), s.length * chrsz));}
            function str_md5(s){ return binl2str(core_md5(str2binl(s), s.length * chrsz));}
            function hex_hmac_md5(key, data) { return binl2hex(core_hmac_md5(key, data)); }
            function b64_hmac_md5(key, data) { return binl2b64(core_hmac_md5(key, data)); }
            function str_hmac_md5(key, data) { return binl2str(core_hmac_md5(key, data)); }

            /*
             * Perform a simple self-test to see if the VM is working
             */
            function md5_vm_test()
            {
                return hex_md5("abc") == "900150983cd24fb0d6963f7d28e17f72";
            }

            /*
             * Calculate the MD5 of an array of little-endian words, and a bit length
             */
            function core_md5(x, len)
            {
                /* append padding */
                x[len >> 5] |= 0x80 << ((len) % 32);
                x[(((len + 64) >>> 9) << 4) + 14] = len;

                var a =  1732584193;
                var b = -271733879;
                var c = -1732584194;
                var d =  271733878;

                for(var i = 0; i < x.length; i += 16)
                {
                    var olda = a;
                    var oldb = b;
                    var oldc = c;
                    var oldd = d;

                    a = md5_ff(a, b, c, d, x[i+ 0], 7 , -680876936);
                    d = md5_ff(d, a, b, c, x[i+ 1], 12, -389564586);
                    c = md5_ff(c, d, a, b, x[i+ 2], 17,  606105819);
                    b = md5_ff(b, c, d, a, x[i+ 3], 22, -1044525330);
                    a = md5_ff(a, b, c, d, x[i+ 4], 7 , -176418897);
                    d = md5_ff(d, a, b, c, x[i+ 5], 12,  1200080426);
                    c = md5_ff(c, d, a, b, x[i+ 6], 17, -1473231341);
                    b = md5_ff(b, c, d, a, x[i+ 7], 22, -45705983);
                    a = md5_ff(a, b, c, d, x[i+ 8], 7 ,  1770035416);
                    d = md5_ff(d, a, b, c, x[i+ 9], 12, -1958414417);
                    c = md5_ff(c, d, a, b, x[i+10], 17, -42063);
                    b = md5_ff(b, c, d, a, x[i+11], 22, -1990404162);
                    a = md5_ff(a, b, c, d, x[i+12], 7 ,  1804603682);
                    d = md5_ff(d, a, b, c, x[i+13], 12, -40341101);
                    c = md5_ff(c, d, a, b, x[i+14], 17, -1502002290);
                    b = md5_ff(b, c, d, a, x[i+15], 22,  1236535329);

                    a = md5_gg(a, b, c, d, x[i+ 1], 5 , -165796510);
                    d = md5_gg(d, a, b, c, x[i+ 6], 9 , -1069501632);
                    c = md5_gg(c, d, a, b, x[i+11], 14,  643717713);
                    b = md5_gg(b, c, d, a, x[i+ 0], 20, -373897302);
                    a = md5_gg(a, b, c, d, x[i+ 5], 5 , -701558691);
                    d = md5_gg(d, a, b, c, x[i+10], 9 ,  38016083);
                    c = md5_gg(c, d, a, b, x[i+15], 14, -660478335);
                    b = md5_gg(b, c, d, a, x[i+ 4], 20, -405537848);
                    a = md5_gg(a, b, c, d, x[i+ 9], 5 ,  568446438);
                    d = md5_gg(d, a, b, c, x[i+14], 9 , -1019803690);
                    c = md5_gg(c, d, a, b, x[i+ 3], 14, -187363961);
                    b = md5_gg(b, c, d, a, x[i+ 8], 20,  1163531501);
                    a = md5_gg(a, b, c, d, x[i+13], 5 , -1444681467);
                    d = md5_gg(d, a, b, c, x[i+ 2], 9 , -51403784);
                    c = md5_gg(c, d, a, b, x[i+ 7], 14,  1735328473);
                    b = md5_gg(b, c, d, a, x[i+12], 20, -1926607734);

                    a = md5_hh(a, b, c, d, x[i+ 5], 4 , -378558);
                    d = md5_hh(d, a, b, c, x[i+ 8], 11, -2022574463);
                    c = md5_hh(c, d, a, b, x[i+11], 16,  1839030562);
                    b = md5_hh(b, c, d, a, x[i+14], 23, -35309556);
                    a = md5_hh(a, b, c, d, x[i+ 1], 4 , -1530992060);
                    d = md5_hh(d, a, b, c, x[i+ 4], 11,  1272893353);
                    c = md5_hh(c, d, a, b, x[i+ 7], 16, -155497632);
                    b = md5_hh(b, c, d, a, x[i+10], 23, -1094730640);
                    a = md5_hh(a, b, c, d, x[i+13], 4 ,  681279174);
                    d = md5_hh(d, a, b, c, x[i+ 0], 11, -358537222);
                    c = md5_hh(c, d, a, b, x[i+ 3], 16, -722521979);
                    b = md5_hh(b, c, d, a, x[i+ 6], 23,  76029189);
                    a = md5_hh(a, b, c, d, x[i+ 9], 4 , -640364487);
                    d = md5_hh(d, a, b, c, x[i+12], 11, -421815835);
                    c = md5_hh(c, d, a, b, x[i+15], 16,  530742520);
                    b = md5_hh(b, c, d, a, x[i+ 2], 23, -995338651);

                    a = md5_ii(a, b, c, d, x[i+ 0], 6 , -198630844);
                    d = md5_ii(d, a, b, c, x[i+ 7], 10,  1126891415);
                    c = md5_ii(c, d, a, b, x[i+14], 15, -1416354905);
                    b = md5_ii(b, c, d, a, x[i+ 5], 21, -57434055);
                    a = md5_ii(a, b, c, d, x[i+12], 6 ,  1700485571);
                    d = md5_ii(d, a, b, c, x[i+ 3], 10, -1894986606);
                    c = md5_ii(c, d, a, b, x[i+10], 15, -1051523);
                    b = md5_ii(b, c, d, a, x[i+ 1], 21, -2054922799);
                    a = md5_ii(a, b, c, d, x[i+ 8], 6 ,  1873313359);
                    d = md5_ii(d, a, b, c, x[i+15], 10, -30611744);
                    c = md5_ii(c, d, a, b, x[i+ 6], 15, -1560198380);
                    b = md5_ii(b, c, d, a, x[i+13], 21,  1309151649);
                    a = md5_ii(a, b, c, d, x[i+ 4], 6 , -145523070);
                    d = md5_ii(d, a, b, c, x[i+11], 10, -1120210379);
                    c = md5_ii(c, d, a, b, x[i+ 2], 15,  718787259);
                    b = md5_ii(b, c, d, a, x[i+ 9], 21, -343485551);

                    a = safe_add(a, olda);
                    b = safe_add(b, oldb);
                    c = safe_add(c, oldc);
                    d = safe_add(d, oldd);
                }
                return Array(a, b, c, d);

            }

            /*
             * These functions implement the four basic operations the algorithm uses.
             */
            function md5_cmn(q, a, b, x, s, t)
            {
                return safe_add(bit_rol(safe_add(safe_add(a, q), safe_add(x, t)), s),b);
            }
            function md5_ff(a, b, c, d, x, s, t)
            {
                return md5_cmn((b & c) | ((~b) & d), a, b, x, s, t);
            }
            function md5_gg(a, b, c, d, x, s, t)
            {
                return md5_cmn((b & d) | (c & (~d)), a, b, x, s, t);
            }
            function md5_hh(a, b, c, d, x, s, t)
            {
                return md5_cmn(b ^ c ^ d, a, b, x, s, t);
            }
            function md5_ii(a, b, c, d, x, s, t)
            {
                return md5_cmn(c ^ (b | (~d)), a, b, x, s, t);
            }

            /*
             * Calculate the HMAC-MD5, of a key and some data
             */
            function core_hmac_md5(key, data)
            {
                var bkey = str2binl(key);
                if(bkey.length > 16) bkey = core_md5(bkey, key.length * chrsz);

                var ipad = Array(16), opad = Array(16);
                for(var i = 0; i < 16; i++)
                {
                    ipad[i] = bkey[i] ^ 0x36363636;
                    opad[i] = bkey[i] ^ 0x5C5C5C5C;
                }

                var hash = core_md5(ipad.concat(str2binl(data)), 512 + data.length * chrsz);
                return core_md5(opad.concat(hash), 512 + 128);
            }

            /*
             * Add integers, wrapping at 2^32. This uses 16-bit operations internally
             * to work around bugs in some JS interpreters.
             */
            function safe_add(x, y)
            {
                var lsw = (x & 0xFFFF) + (y & 0xFFFF);
                var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
                return (msw << 16) | (lsw & 0xFFFF);
            }

            /*
             * Bitwise rotate a 32-bit number to the left.
             */
            function bit_rol(num, cnt)
            {
                return (num << cnt) | (num >>> (32 - cnt));
            }

            /*
             * Convert a string to an array of little-endian words
             * If chrsz is ASCII, characters >255 have their hi-byte silently ignored.
             */
            function str2binl(str)
            {
                var bin = Array();
                var mask = (1 << chrsz) - 1;
                for(var i = 0; i < str.length * chrsz; i += chrsz)
                    bin[i>>5] |= (str.charCodeAt(i / chrsz) & mask) << (i%32);
                return bin;
            }

            /*
             * Convert an array of little-endian words to a string
             */
            function binl2str(bin)
            {
                var str = "";
                var mask = (1 << chrsz) - 1;
                for(var i = 0; i < bin.length * 32; i += chrsz)
                    str += String.fromCharCode((bin[i>>5] >>> (i % 32)) & mask);
                return str;
            }

            /*
             * Convert an array of little-endian words to a hex string.
             */
            function binl2hex(binarray)
            {
                var hex_tab = hexcase ? "0123456789ABCDEF" : "0123456789abcdef";
                var str = "";
                for(var i = 0; i < binarray.length * 4; i++)
                {
                    str += hex_tab.charAt((binarray[i>>2] >> ((i%4)*8+4)) & 0xF) +
                        hex_tab.charAt((binarray[i>>2] >> ((i%4)*8  )) & 0xF);
                }
                return str;
            }

            /*
             * Convert an array of little-endian words to a base-64 string
             */
            function binl2b64(binarray)
            {
                var tab = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
                var str = "";
                for(var i = 0; i < binarray.length * 4; i += 3)
                {
                    var triplet = (((binarray[i   >> 2] >> 8 * ( i   %4)) & 0xFF) << 16)
                        | (((binarray[i+1 >> 2] >> 8 * ((i+1)%4)) & 0xFF) << 8 )
                        |  ((binarray[i+2 >> 2] >> 8 * ((i+2)%4)) & 0xFF);
                    for(var j = 0; j < 4; j++)
                    {
                        if(i * 8 + j * 6 > binarray.length * 32) str += b64pad;
                        else str += tab.charAt((triplet >> 6*(3-j)) & 0x3F);
                    }
                }
                return str;
            }






            $("#login-form").submit(function (e) {
                e.preventDefault();
                var username = $('#pd-form-username').val(),
                    password = hex_md5($('#pd-form-password').val()),
                    token = $('input[name=__token__]').val(),
                    captcha = $('input[name=captcha]').val();

                //此处可对用户名、密码进行加密操作

                Fast.api.ajax({
                    url: 'index/login',
                    data: {
                        __token__: token,
                        username: username,
                        password: password,
                        captcha: captcha,
                    },
                    complete: function (xhr) {
                        var token = xhr.getResponseHeader('__token__');
                        if (token) {
                            $("input[name='__token__']").val(token);
                        }
                    }
                }, function (data, ret) {
                    localStorage.setItem("lastlogin", JSON.stringify({
                        id: data.id,
                        username: data.username,
                        avatar: data.avatar
                    }));

                    window.location.href = 'index/index';

                }, function () {
                    $("input[name=captcha]").next(".input-group-addon").find("img").trigger("click");
                })
            });
        }
    };

    return Controller;
});
