jQuery(document).ready(function($) {
    $(".open-popup").click(function(e) {
        e.preventDefault();
        var embedUrl = $(this).data("url");
        var description = $(this).data("description");
        var title = $(this).data("title");

        $("#rss-popup-frame").attr("src", embedUrl);
        $("#rss-popup-description").html("<h2><b>" + title + "</b></h2>" + description);
        $("#rss-popup").fadeIn();
    });

    $(".rss-popup-close, #rss-popup").click(function() {
        $("#rss-popup").fadeOut();
        $("#rss-popup-frame").attr("src", "");
        $("#rss-popup-description").html("");
    });

    $(".rss-popup-content").click(function(e) {
        e.stopPropagation();
    });
});
