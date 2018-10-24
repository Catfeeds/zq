var manual = {
  init: function() {
    var t = this;
    t.$content = $(".manual .manual-container"),
    t.$catalog = $(".manual .manual-catalog"),
    t.createCatalog(),
    $("body").scrollspy({
        target: "#manual-catalog",
        offset: 100
    }),
    t.$catalog.on("activate.bs.scrollspy", function() {
        t.checkCatalog()
    }
    ),
    0 == t.$catalog.find("li.active").length && t.$catalog.find("ul.nav>li:first").addClass("active"),
    t.$catalog.find("ul.nav ul").hide(),
    t.checkCatalog(),
    t.$catalog.find("a").click(function() {
        var a = 75
          , n = t.$content.find($(this).attr("href")).offset().top - a;
        return $("body,html").animate({
            scrollTop: n
        }, 250),
        !1
    }
    )
  },
  createCatalog: function() {
    var t = this
      , a = '<ul class="nav">';
    t.$content.find("h2").each(function(n, l) {
        h2Text = $(l).text(),
        t.toAnchor($(l)),
        a += "<li> " + t.toCatalogAnchor($(l)),
        a += "    <ul>",
        $(l).parents(".paragraph").find("h3").each(function(n, l) {
            h3Text = $(l).text(),
            t.toAnchor($(l)),
            a += "        <li>" + t.toCatalogAnchor($(l)) + "</li>"
        }
        ),
        a += "    </ul></li>"
    }
    ),
    a += "</ul>",
    t.$catalog.html(a)
  },
  checkCatalog: function() {
    var t, a = this, n = a.$catalog.find("ul.nav>li"), l = (a.$catalog.find("ul.nav>li>ul"),
    a.$catalog.find("ul.nav>li>ul>li")), e = l.filter(".active");
    t = e.length > 0 ? e.eq(0).parents("li") : n.filter(".active"),
    n.not(t).find("ul").slideUp(),
    t.find("ul").slideDown()
  },
  bindEvent: function() {},
  renderCatalog: function() {},
  renderDetail: function() {},
  getPureText: function(t) {
    return t ? (t = t.replace(/.{1}、/, ""),
    t = t.replace(/\？$/, ""),
    t = t.replace(/[\/]/, "_")) : ""
  },
  toCatalogAnchor: function(t) {
    var a = t.text();
    return a = this.getPureText(a),
    '<a href="#' + a + '">' + a + "</a>"
  },
  toAnchor: function(t) {
    var a = t.text()
      , n = this.getPureText(a);
    t.attr("id", n)
  }
};
