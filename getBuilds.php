
<?php
require_once ('lib/MockCI.php');
require_once ('lib/JenkinsCI.php');
require_once ('lib/Exceptions.php');

function isort($a,$b) {
    return ($a['timeElapse']) > ($b['timeElapse']);
}

function displayJobsProblem($jobs)
{
  $html = '';
  foreach ($jobs as $job) {
    $lsIcon = '';
    $lsUrl = $job['url'];
    $lsStatus = $job['status'][0];
    if ($lsStatus == 'cancelled') {
      $lsIcon = '<img src="images/pause.png" height=100% style="float:left">';
    }
    if ($lsStatus == 'disabled') {
      $lsIcon = '<img src="images/stop.png" height=100% style="float:left">';
    }
    if ($lsStatus == 'failed') {
      $lsUrl .= "/lastBuild/console";
    }
    if ($lsStatus == 'unstable') {
      $lsUrl .= "/lastBuild/testReport";
    }
    if ($job['status'][1] and ($job['status'][1] == 'building'))
    {
      $lsUrl = $job['url']."/lastBuild/console";
    }

    $claim = '';
    if (!empty($job['claim'])) {
      $culprit = $job['claim'];
      $claim = '<img src="https://account.corp.ltutech.com/photos.php?user=' . $culprit .'" height=60  style="float:right"> ';
    }


    $winImage = '';
    if (preg_match('/Win/', $job['name'], $matches, PREG_OFFSET_CAPTURE, 3)) {
      $winImage = '<img src="images/win-logo.png" height=100% style="float:right">';
    }
    $html .= "<li class = 'box jobBroken " . implode(" ",$job['status'] ) . "' onclick=\"window.open('$lsUrl')\">
              {$lsIcon}<div style=\"float:left\">{$job['name']}</div>{$winImage}<br style=\"clear:both\"/>
             </li>";
  $html .= "<div id=\"building_jobs\">".displayJobsSuccess($jobsStableBuilding)."</div>
             <li class = \"lastSuccedBuild\">
              {$job['lastSuccessfulBuildTime']}{$claim}
             </li>";
  }
  return $html;
}

function imageVictory()
{
  $html = '';
  $html .= '<div class="imageVictoryRight imageVictory"></div>';
  $html .= '<div class="imageVictoryLeft imageVictory flip-horizontal"></div>';


  return $html;
}

function displayRandomTumblrLesJoixDuCode()
{
  $html = '';
  $url = 'http://lesjoiesducode.tumblr.com/random';
  $str = @file_get_contents($url);

  if (preg_match('/<div class="post">(.+?)<h3>(.+?)">(.+?)<\/a>(.+?)<img(.+?)src="(.+?)\.gif"/', $str, $matches)) {
      $lsMessage = $matches[3];
      $lsURLGif = $matches[6] . ".gif";
    }

  $html .= "<img class=tumblrImage src=\"$lsURLGif\">" . "<div class=tumblrMessage><a>{$lsMessage}</a>";

  return $html;
}

function displayJobsBorder($countJobsStable, $countJobsUnstable, $countJobsFailed)
{
  $html = '<ul>
              <li class = "box counter success">'.
                "$countJobsStable
              </li>
              <li class = \"box counter unstable\">".
                "$countJobsUnstable
              </li>
              <li class = \"box counter failed\">".
                "$countJobsFailed
              </li>
            </ul>";
  return $html;
}

function displayJobsSuccess($jobsStable)
{

  $html = '';
  foreach ($jobsStable as $job) {
    $lsUrl = $job['url'];
    if ($job['status'][1] and ($job['status'][1] == 'building'))
    {
      $lsUrl .= "/lastBuild/console";
    }

    $winImage = '';
    if (preg_match('/Win/', $job['name'], $matches, PREG_OFFSET_CAPTURE, 3)) {
      $winImage = '<img src="images/win-logo.png" height=100% style="float:right">';
    }
    $html .="<li class = 'box jobSuccess success " . implode(" ",$job['status'] ) .
            "' onclick=\"window.open('$lsUrl')\">{$job['name']}{$winImage}</li>";
  }
  return $html;
}

function generateHtml($jobs)
{
  $html = '';

  $jobsFailed = array();
  $jobsUnstable = array();
  $jobsCancel = array();
  $jobsStable = array();
  $jobsStableBuilding = array();

  foreach ($jobs as $job) {
    $lsStatus = $job['status'][0];
    if ($lsStatus == 'failed') {
      $jobsFailed[] = $job;
    }
    if ($lsStatus == 'unstable') {
      $jobsUnstable[] = $job;
    }
    if (($lsStatus == 'cancelled')  or ($lsStatus == 'disabled')) {
      $jobsCancel[] = $job;
    }
    if ($lsStatus == 'successful') {
      if ($job['status'][1] and ($job['status'][1] == 'building')) {
        $jobsStableBuilding[] = $job;
      }
      else {
        $jobsStable[] = $job;
      }
    }
  }

  usort($jobsFailed, "isort");
  usort($jobsUnstable, "isort");
  usort($jobsCancel, "isort");
  usort($jobsClaim, "isort");
  $html .= '<ul id="job_alerts">';
  $html .= displayJobsProblem($jobsFailed);
  $html .= displayJobsProblem($jobsUnstable);
  $html .= displayJobsProblem($jobsClaim);
  $html .= displayJobsProblem($jobsCancel);
  $html .= '</ul>';
  $html .= '<ul id="running_jobs">'.displayJobsSuccess($jobsStableBuilding).'</ul>';
  $html .= '<div id="overlayCounter">'.displayJobsBorder(count($jobsStableBuilding) + count($jobsStable), count($jobsUnstable), count($jobsFailed)).'</div>';

  if (count($jobsUnstable) + count($jobsFailed) + count($jobsCancel) == 0) {
    $html .= imageVictory();
    $minutes = intval(date('i'));
    $secondes = intval(date('s'));

    if (($minutes == 0) and ($second < 10))
    {
      $html .= displayRandomTumblrLesJoixDuCode();
    }
  }

  return $html;
}

$result = '';
if (isset($_GET['view'])) {
  $ci = new JenkinsCI('http://continuousintegration.corp.ltutech.com', $_GET['view']);
} else {
  $ci = new JenkinsCI('http://continuousintegration.corp.ltutech.com');
}

try {
  $jobs = $ci->getAllJobs();
} catch (BuildiatorCIServerCommunicationException $e) {
  $result = array('status'  => 'error',	'content' => $e->getMessage());
}
if (!is_array($result)) {
  $html = generateHtml($jobs);
  $result = array('status' => 'ok', 'content' => $html);
}

echo json_encode($result);
?>
