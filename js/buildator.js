var lastdata;

function updateJobs(){
  $.getJSON('getBuilds.php?view=' + VIEW_NAME, function(data){
    if (data.content != lastdata){
      lastdata = data.content;
      $('#jobs').html(data.content);
    }
    setTimeout('updateJobs()', 5000);
  });
}

$(document).ajaxError(function() {
  //if there was an error updating, wait a bit longer and try again
  setTimeout('updateJobs()', 10000);
});

$(document).ready(function(){
  updateJobs();
  $(".box > span").each(function() {
    $(this)
      .data("origWidth", $(this).width())
      .width(0)
      .animate({
        width: $(this).data("origWidth")
      }, 1200);
  });

});
