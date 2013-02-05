
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
      $lsIcon = '<img src="images/Pause.png">';
    }
    if ($lsStatus == 'disabled') {
      $lsIcon = '<img src="images/Stop.png">';
    }
    if ($lsStatus == 'failed') {
      $lsUrl .= "/lastBuild/console";
    }
    if ($lsStatus == 'unstable') {
      $lsUrl .= "/lastBuild/testReport";
    }
    $html .= "<li class = 'jobBroken " . implode(" ",$job['status'] ) . "' onclick=\"window.open('$lsUrl')\">
              {$lsIcon}{$job['name']}
             </li>
             <li class = 'lastSuccedBuild '>
              {$job['lastSuccessfulBuildTime']}
             </li>";
  }
  return $html;
}

function displayJobsClaim($claimJobs)
{
  $html = '';
  foreach ($claimJobs as $job) {
    $culprit = $job['claim'];
    array_push($job['status'], "$culprit claimed");
    $claim = '<img src="https://account.corp.ltutech.com/photos.php?user=' . $culprit .'" height="60"  style="float:right"> ';
    $lsUrl = $job['url']."/lastBuild";
    $html .= "<li class = 'jobBroken " . implode(" ",$job['status'] ) . "' onclick=\"window.open('$lsUrl')\">
               {$job['name']}
              </li>
              <li class = 'lastSuccedBuild '>
                {$job['lastSuccessfulBuildTime']}{$claim}
              </li>";
  }
  return $html;
}

function displayJobsBorder($countJobs, $countJobsStable)
{
  $html = '';
  $extra = '';
  if ($countJobs == $countJobsStable) {
    $extra = '<img src="images/ChuckNorris.png">';
  }
  $html .= '<li class = "jobsBorderHeader">'.
             "$extra Successful Builds: $countJobsStable/$countJobs $extra
            </li>";
  return $html;
}

function displayJobsSuccess($jobsStable)
{
  $html = '';
  foreach ($jobsStable as $job) {
    $url = $job['url'];
    $html .="<li class = 'jobSuccess " . implode(" ",$job['status'] ) .
            "' onclick=\"window.open('$url')\">{$job['name']}</li>";
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
  $jobsClaim = array();

  foreach ($jobs as $job) {
    $lsStatus = $job['status'][0];
    if (!empty($job['claim'])) {
      $jobsClaim[] = $job;
      continue;
    }
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
      $jobsStable[] = $job;
    }
  }

  usort($jobsFailed, "isort");
  usort($jobsUnstable, "isort");
  usort($jobsCancel, "isort");
  usort($jobsClaim, "isort");

  $html .= displayJobsProblem($jobsFailed);
  $html .= displayJobsProblem($jobsUnstable);
  $html .= displayJobsClaim($jobsClaim);
  $html .= displayJobsProblem($jobsCancel);
  $html .= displayJobsBorder(count($jobs), count($jobsStable));
  $html .= displayJobsSuccess($jobsStable);

  return $html;
}

$result = '';
if (isset($_GET['view'])) {
  $ci = new JenkinsCI('http://continuousintegration.corp.ltutech.com', $_GET['view']);
} else {
  $ci = new JenkinsCI('http://continuousintegration.corp.ltutech.com');
}
$ci = new JenkinsCI('http://continuousintegration.corp.ltutech.com/view/devteam/');
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
