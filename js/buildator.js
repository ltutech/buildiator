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
  var count = 0;
  setInterval(function() {
    count++;
    var time = new Date().getTime();
      op = (count%2 +0.25).toFixed(2);
      if (op>1) {
        op = 1;
      }
      $('.building').animate({
        opacity: op,
        easing: 'swing'
      },1000)
    },
    1000
  );
});