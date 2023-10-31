(function ($) {
  "use strict";

  /**
   * All of the code for your admin-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */
})(jQuery);
jQuery(document).ready(function ($) {
  $("#quickies code").click(function () {
    parenttag = $(this).parent().parent().find("strong code").text();
    code = parenttag + "" + $(this).text() + "#";
    editor.session.insert(editor.getCursorPosition(), code);
  });

  // Attach a click event to your button
  $("#openIframeButton").on("click", function (e) {
    e.preventDefault();
    // Define the URL of your iframe content
    var iframeURL = schemapreview + "/preview.php?id=" + $("#previd").val();

    tb_show("Preview", iframeURL, null, null, {
      onLoad: function () {
        setTimeout(() => {
          var editorx = ace.edit("editorpreview");
          editorx.session.setMode("ace/mode/json");
        }, 50);
      },
    });
  });

  // Attach a click event to your button
  $("#openDebugButton").on("click", function (e) {
    e.preventDefault();
    // Define the URL of your iframe content
    var iframeURL =
      schemapreview + "/preview.php?id=" + $("#previd").val() + "&mode=debug";

    tb_show("Preview", iframeURL, null, null, {
      onLoad: function () {
        setTimeout(() => {
          var editorx = ace.edit("editorpreview");
          editorx.session.setMode("ace/mode/json");
        }, 50);
      },
    });
  });
});
